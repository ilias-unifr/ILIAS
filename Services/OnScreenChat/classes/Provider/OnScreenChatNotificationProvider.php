<?php declare(strict_types=1);

namespace ILIAS\OnScreenChat\Provider;

use ILIAS\GlobalScreen\Identification\IdentificationInterface;
use ILIAS\GlobalScreen\Scope\Notification\Provider\AbstractNotificationProvider;
use ILIAS\GlobalScreen\Scope\Notification\Provider\NotificationProvider;
use ILIAS\Filesystem\Stream\Streams;
use Psr\Http\Message\ResponseInterface;

/**
 * Class OnScreenChatNotificationProvider
 * @author Michael Jansen <mjansen@databay.de>
 */
class OnScreenChatNotificationProvider extends AbstractNotificationProvider implements NotificationProvider
{
    /**
     * @param array $notificationItems
     * @return ResponseInterface
     */
    private function getResponseWithNotificationItems(array $notificationItems) : ResponseInterface
    {
        return $this->dic->http()->response()->withBody(Streams::ofString(
            $this->dic->ui()->renderer()->renderAsync($notificationItems)
        ));
    }

    /**
     * @inheritDoc
     */
    public function getNotifications(): array
    {
        $id = function (string $id): IdentificationInterface {
            return $this->if->identifier($id);
        };

        if (0 === (int)$this->dic->user()->getId() || $this->dic->user()->isAnonymous()) {
            return [];
        }

        $chatSettings = new \ilSetting('chatroom');
        $isEnabled = $chatSettings->get('chat_enabled') && $chatSettings->get('enable_osc');
        if (!$isEnabled) {
            return [];
        }

        $factory = $this->globalScreen()->notifications()->factory();

        $this->dic->language()->loadLanguageModule('chatroom');

        $showAcceptMessageChange = (
            !\ilUtil::yn2tf($this->dic->user()->getPref('chat_osc_accept_msg')) &&
            !(bool)$this->dic->settings()->get('usr_settings_hide_chat_osc_accept_msg', false) &&
            !(bool)$this->dic->settings()->get('usr_settings_disable_chat_osc_accept_msg', false)
        );

        $description = $this->dic->language()->txt('chat_osc_nc_no_conv');
        if ($showAcceptMessageChange) {
            $description = sprintf(
                $this->dic->language()->txt('chat_osc_dont_accept_msg'),
                $this->dic->ctrl()->getLinkTargetByClass(
                    ['ilDashboardGUI', 'ilPersonalSettingsGUI', 'ilPersonalChatSettingsFormGUI'],
                    'showChatOptions'
                )
            );
        }

        $icon = $this->dic->ui()->factory()
            ->symbol()
            ->icon()
            ->standard('chtr', 'conversations');
        $title = $this->dic->language()->txt('chat_osc_conversations');
        if (!$showAcceptMessageChange) {
            $title = $this->dic->language()->txt('chat_osc_conversations');
        }

        $notificationItem = $this->dic->ui()->factory()
            ->item()
            ->notification($title, $icon)
            ->withDescription($description);
        if (!$showAcceptMessageChange) {
            $notificationItem = $notificationItem
                ->withAdditionalOnLoadCode(
                    function($id) {
                        return "
                            il.OnScreenChat.setNotificationItemId('$id');
                        ";
                    }
                );
        }

        $group = $factory
            ->standardGroup($id('chat_bucket_group'))
            ->withTitle('Chat')
            ->addNotification(
                $factory->standard($id('chat_bucket'))
                    ->withNotificationItem($notificationItem)
                    ->withNewAmount(0)
            );

        return [
            $group,
        ];
    }

    /**
     * @return ResponseInterface
     * @throws \ilWACException
     */
    public function getAsyncItem() : ResponseInterface 
    {
        $noAggregates = (string) ($this->dic->http()->request()->getQueryParams()['no_aggregates'] ?? '');
        $conversationIds = array_filter(explode(',',
            (string) ($this->dic->http()->request()->getQueryParams()['ids'] ?? '')
        ));

        $this->dic->language()->loadLanguageModule('chatroom');

        $icon = $this->dic->ui()->factory()
            ->symbol()
            ->icon()
            ->standard('chtr', 'conversations');
        $title = $this->dic->language()->txt('chat_osc_conversations');
        if ('true' !== $noAggregates && count($conversationIds) > 0) {
            $title = $this->dic->ui()->factory()
                ->link()
                ->standard($title, '#');
        }
        $notificationItem = $this->dic->ui()->factory()
            ->item()
            ->notification($title, $icon)
            ->withDescription($this->dic->language()->txt('chat_osc_nc_no_conv'))
            ->withAdditionalOnLoadCode(
                function($id) {
                    return "
                    il.OnScreenChat.setNotificationItemId('$id');
                ";
                }
            );

        if (0 === count($conversationIds)) {
            return $this->getResponseWithNotificationItems([$notificationItem]);
        }

        if (!$this->dic->user()->getId() || $this->dic->user()->isAnonymous()) {
            return $this->getResponseWithNotificationItems([$notificationItem]);
        }

        /**
         * TODO: Move to some kind of repository or use ActiveRecord/Some other querying class
         */
        
        $res = $this->dic->database()->query(
            'SELECT * FROM osc_conversation WHERE ' . $this->dic->database()->in(
                'id', $conversationIds, false, 'text'
            )
        );

        $allUsrIds = [];
        $validConversations = [];
        while ($row = $this->dic->database()->fetchAssoc($res)) {
            $participants = json_decode($row['participants'], true);
            $participantIds = array_filter(array_map(function($value) {
                if (is_array($value) && isset($value['id'])) {
                    return (int) $value['id'];
                }
                
                return 0;
            }, $participants));

            if (in_array((int) $this->dic->user()->getId(), $participantIds)) {
                $allUsrIds = array_unique(array_merge($allUsrIds, $participantIds));

                $this->dic->database()->setLimit(1, 0);
                $msgRes = $this->dic->database()->queryF(
                    'SELECT * FROM osc_messages WHERE conversation_id = %s AND ' . $this->dic->database()->in(
                        'user_id', $participantIds, false, 'text'
                    ) .
                    ' ORDER BY timestamp DESC',
                    ['text'],
                    [$row['id']]
                );
                $row['message'] = '';
                while ($msgRow = $this->dic->database()->fetchAssoc($msgRes)) {
                    $row['message'] = $msgRow['message'];
                    break;
                }

                $row['participantIds'] = array_combine($participantIds, $participantIds);

                $validConversations[$row['id']] = $row;
            }
        }

        $userProvider = new \ilOnScreenChatUserDataProvider($this->dic->database(), $this->dic->user());
        $allUsrData = $userProvider->getDataByUserIds($allUsrIds);

        if ('true' !== $noAggregates) {
            $aggregatedItems = [];
            foreach ($validConversations as $conversationId => $data) {
                $convUsrData = array_filter($allUsrData, function($key) use ($data) {
                    return isset($data['participantIds'][$key]);
                }, ARRAY_FILTER_USE_KEY);
                
                $convUsrNames = array_map(function($value) {
                    return $value['public_name'];
                }, $convUsrData);

                $name = implode(', ', $convUsrNames);
                $message = $data['message'];
                
                $aggregateTitle = $this->dic->ui()->factory()
                    ->button()
                    ->shy($name, '') // Important: Do not pass any action here, otherwise there will be onClick/return false;
                    ->withAdditionalOnLoadCode(
                        function($id) use($conversationId) {
                            return "
                                 $('#$id').attr('data-onscreenchat-menu-item', '');
                                 $('#$id').attr('data-onscreenchat-conversation', '$conversationId');
                            ";
                        }
                    );
                $aggregatedItems[] = $this->dic->ui()->factory()
                    ->item()
                    ->notification($aggregateTitle, $icon)
                    ->withDescription($message)
                    ->withAdditionalOnLoadCode(
                        function($id) use($conversationId) {
                            return "
                                $('#$id').find('.il-item-description').html(
                                    il.OnScreenChat.getMessageFormatter().format(
                                        $('#$id').find('.il-item-description').html()
                                    )                                    
                                );
                                $('#$id').find('button.close')
                                    .attr('data-onscreenchat-menu-remove-conversation', '')
                                    .attr('data-onscreenchat-conversation', '$conversationId');
                            ";
                        }
                    )
                    ->withCloseAction('#'); // Important: The # prevents the default onClick handler is triggered
            }
            
            $description = sprintf($this->dic->language()->txt('chat_osc_nc_conv_x_p'), count($aggregatedItems));
            if (1 === count($aggregatedItems)) {
                $description = $this->dic->language()->txt('chat_osc_nc_conv_x_s');
            }

            $notificationItem = $notificationItem
                ->withAggregateNotifications($aggregatedItems)
                ->withDescription($description);
        }

        return $this->getResponseWithNotificationItems([$notificationItem]);
    }
}
