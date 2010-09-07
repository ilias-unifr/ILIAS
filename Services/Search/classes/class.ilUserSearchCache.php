<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/

/** 
* Class for storing search result. Allows paging of result sets
* 
* 
* @author Stefan Meyer <meyer@leifos.com>
* @version $Id$
* 
* 
* @ilCtrl_Calls 
* @ingroup ServicesSearch 
*/
class ilUserSearchCache
{
	const DEFAULT_SEARCH = 0;
	const ADVANCED_SEARCH = 1;
	const SHOP_CONTENT = 2;
	const SHOP_ADVANCED_SEARCH = 3;
	const ADVANCED_MD_SEARCH = 4;
	const LUCENE_DEFAULT = 5;
	const LUCENE_ADVANCED = 6;

	private static $instance = null;
	private $db;
	
	private $usr_id;
	private $search_type = self::DEFAULT_SEARCH;
	
	private $search_result = array();
	private $checked = array();
	private $failed = array();
	private $page_number = 1;
	private $query;
	private $root = ROOT_FOLDER_ID;
	
	private $item_filter = array();
	
	
	/**
	 * Constructor
	 *
	 * @access private
	 * 
	 */
	private function __construct($a_usr_id)
	{
		global $ilDB;
		
		$this->db = $ilDB;
	 	$this->usr_id = $a_usr_id;
	 	$this->search_type = self::DEFAULT_SEARCH;
	 	$this->read();
	}
	
	/**
	 * Get singleton instance
	 *
	 * @access public
	 * @static
	 *
	 * @param int usr_id
	 */
	public static function _getInstance($a_usr_id)
	{
		if(is_object(self::$instance) and self::$instance)
		{
			return self::$instance;
		}
		return self::$instance = new ilUserSearchCache($a_usr_id);
	}
	
	/**
	 * switch to search type
	 * reads entries from database
	 * 
	 * @access public
	 * @param int search type
	 * 
	 */
	public function switchSearchType($a_type)
	{
	 	$this->search_type = $a_type;
	 	$this->read();
	 	return true;
	}
	
	/**
	 * Get results
	 *
	 * @access public
	 * 
	 */
	public function getResults()
	{
		return $this->search_result ? $this->search_result : array();
	}
	
	/**
	 * Set results
	 *
	 * @access public
	 * @param array(int => array(int,int,string)) array(ref_id => array(ref_id,obj_id,type))
	 * 
	 */
	public function setResults($a_results)
	{
	 	$this->search_result = $a_results;
	}
	
	/**
	 * Append result
	 *
	 * @access public
	 * @param array(int,int,string) array(ref_id,obj_id,type)
	 * 
	 */
	public function addResult($a_result_item)
	{
	 	$this->search_result[$a_result_item['ref_id']]['ref_id'] = $a_result_item['ref_id'];
	 	$this->search_result[$a_result_item['ref_id']]['obj_id'] = $a_result_item['obj_id'];
	 	$this->search_result[$a_result_item['ref_id']]['type'] = $a_result_item['type'];
		return true;
	}

	/**
	 * Append failed id
	 *
	 * @access public
	 * @param int ref_id of failed access 
	 * 
	 */
	public function appendToFailed($a_ref_id)
	{
	 	$this->failed[$a_ref_id] = $a_ref_id;
	}
	
	/**
	 * check if reference has failed access
	 *
	 * @access public
	 * @param int ref_id
	 * 
	 */
	public function isFailed($a_ref_id)
	{
	 	return in_array($a_ref_id,$this->failed) ? true : false;
	}
	
	/**
	 * Append checked id
	 *
	 * @access public
	 * @param int checked reference id
	 * @param int checked obj_id
	 * 
	 */
	public function appendToChecked($a_ref_id,$a_obj_id)
	{
	 	$this->checked[$a_ref_id] = $a_obj_id;
	}
	
	/**
	 * Check if reference was already checked
	 *
	 * @access public
	 * @param int ref_id
	 * 
	 */
	public function isChecked($a_ref_id)
	{
	 	return array_key_exists($a_ref_id,$this->checked) and $this->checked[$a_ref_id];
	}
	
	/**
	 * Get all checked items
	 *
	 * @access public
	 * @return array array(ref_id => obj_id)
	 * 
	 */
	public function getCheckedItems()
	{
	 	return $this->checked ? $this->checked : array();
	}
	
	/**
	 * Set result page number
	 *
	 * @access public
	 * 
	 */
	public function setResultPageNumber($a_number)
	{
	 	if($a_number)
	 	{
	 		$this->page_number = $a_number;
	 	}
	}
	
	/**
	 * get result page number
	 *
	 * @access public
	 * 
	 */
	public function getResultPageNumber()
	{
	 	return $this->page_number ? $this->page_number : 1; 
	}
	
	/**
	 * set query 
	 * @param mixed query string or array (for advanced search)
	 * @return
	 */
	public function setQuery($a_query)
	{
		$this->query = $a_query;
	}
	
	/**
	 * get query
	 *  
	 * @return
	 */
	public function getQuery()
	{
		return $this->query;
	}
	
	public function getUrlEncodedQuery()
	{
		return urlencode(str_replace('"', '.', $this->getQuery()));
	}
	
	/**
	 * set root node of search
	 * @param int root id
	 * @return
	 */
	public function setRoot($a_root)
	{
		$this->root = $a_root;
	}
	
	/**
	 * get root node
	 *  
	 * @return
	 */
	public function getRoot()
	{
		return $this->root ? $this->root : ROOT_FOLDER_ID;
	}
	
	public function setItemFilter($a_filter)
	{
		$this->item_filter = $a_filter;
	}
	
	public function getItemFilter()
	{
		return (array) $this->item_filter;
	}
	
	/**
	 * delete cached entries
	 * @param
	 * @return
	 */
	public function deleteCachedEntries()
	{
		global $ilDB;
		
		$query = "SELECT COUNT(*) num FROM usr_search ".
			"WHERE usr_id = ".$ilDB->quote($this->usr_id,'integer')." ".
			"AND search_type = ".$ilDB->quote($this->search_type,'integer');
		$res = $ilDB->query($query);
		$row = $res->fetchRow(DB_FETCHMODE_OBJECT);
		
		if($row->num > 0)
		{
			$ilDB->update('usr_search',
				array(
					'search_result'	=> array('clob',serialize(array(0))),
					'checked'		=> array('clob',serialize(array(0))),
					'failed'		=> array('clob',serialize(array(0))),
					'page'			=> array('integer',0)),
				array(
					'usr_id'		=> array('integer',(int) $this->usr_id),
					'search_type'	=> array('integer',(int) $this->search_type)
			));			
		}
		else
		{
			$ilDB->insert('usr_search',
				array(
					'search_result'	=> array('clob',serialize(array(0))),
					'checked'		=> array('clob',serialize(array(0))),
					'failed'		=> array('clob',serialize(array(0))),
					'page'			=> array('integer',0),
					'usr_id'		=> array('integer',(int) $this->usr_id),
					'search_type'	=> array('integer',(int) $this->search_type)
			));			
		}
		
		$this->setResultPageNumber(1);
		$this->search_result = array();
		$this->checked = array();
		$this->failed = array();
	}	
	
	
	/**
	 * Delete user entries
	 *
	 * @access public
	 * 
	 */
	public function delete()
	{
	 	global $ilDB;

	 	$query = "DELETE FROM usr_search ".
	 		"WHERE usr_id = ".$this->db->quote($this->usr_id ,'integer')." ".
	 		"AND search_type = ".$this->db->quote($this->search_type ,'integer');
		$res = $ilDB->manipulate($query);
		
	 	$this->read();
		return true;
	}
	
	/**
	 * Save entries
	 *
	 * @access public
	 * 
	 */
	public function save()
	{
		global $ilDB,$ilLog;
		
		if($this->usr_id == ANONYMOUS_USER_ID)
		{
			return false;
		}
		if(!$this->usr_id) $this->usr_id = 0;
		if(!$this->page_number) $this->page_number = 0;
		if(!$this->search_type) $this->search_type = 0;
		
		$query = "DELETE FROM usr_search ".
			"WHERE usr_id = ".$ilDB->quote($this->usr_id ,'integer')." ".
			"AND search_type = ".$ilDB->quote($this->search_type ,'integer');
		$res = $ilDB->manipulate($query);
		
		$ilDB->insert('usr_search',array(
			'usr_id'		=> array('integer',$this->usr_id),
			'search_result'	=> array('clob',serialize($this->search_result)),
			'checked'		=> array('clob',serialize($this->checked)),
			'failed'		=> array('clob',serialize($this->failed)),
			'page'			=> array('integer',$this->page_number),
			'search_type'	=> array('integer',$this->search_type),
			'query'			=> array('clob',serialize($this->getQuery())),
			'root'			=> array('integer',$this->getRoot()),
			'item_filter'	=> array('text',serialize($this->getItemFilter()))));
		
	}
	
	
	/**
	 * Read user entries
	 *
	 * @access private
	 * 
	 */
	private function read()
	{
		$this->failed = array();
		$this->checked = array();
		$this->search_result = array();
		$this->page_number = 0;

		if($this->usr_id == ANONYMOUS_USER_ID)
		{
			return false;
		}

	 	$query = "SELECT * FROM usr_search ".
	 		"WHERE usr_id = ".$this->db->quote($this->usr_id ,'integer')." ".
	 		"AND search_type = ".$this->db->quote($this->search_type ,'integer');
		
	 	$res = $this->db->query($query);
	 	while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
	 	{
	 		$this->search_result = unserialize(stripslashes($row->search_result));
	 		if(strlen($row->checked))
	 		{
		 		$this->checked = unserialize(stripslashes($row->checked));
	 		}
	 		if(strlen($row->failed))
	 		{	
	 			$this->failed = unserialize(stripslashes($row->failed));
	 		}
	 		$this->page_number = $row->page;
			$this->setQuery(unserialize($row->query));
			$this->setRoot($row->root);
			$this->setItemFilter(unserialize($row->item_filter));
	 	}
		return true;			
	}
}


?>