<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
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

require_once("./Services/COPages/classes/class.ilPCTable.php");
require_once("./Services/COPages/classes/class.ilPageContentGUI.php");

/**
* Class ilPCTableGUI
*
* User Interface for Table Editing
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package content
*/
class ilPCTableGUI extends ilPageContentGUI
{

	/**
	* Constructor
	* @access	public
	*/
	function ilPCTableGUI(&$a_pg_obj, &$a_content_obj, $a_hier_id)
	{
		parent::ilPageContentGUI($a_pg_obj, $a_content_obj, $a_hier_id);
	}

	/**
	* execute command
	*/
	function &executeCommand()
	{
		// get next class that processes or forwards current command
		$next_class = $this->ctrl->getNextClass($this);

		// get current command
		$cmd = $this->ctrl->getCmd();

		switch($next_class)
		{
			default:
				$ret =& $this->$cmd();
				break;
		}

		return $ret;
	}


	/**
	* edit properties form
	*/
	function edit()
	{

		$this->setTabs();

		// add paragraph edit template
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.table_properties.html", "content");
		$this->tpl->setVariable("TXT_ACTION", $this->lng->txt("cont_edit_tab_properties"));
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		$this->displayValidationError();

		// table
		$this->tpl->setVariable("TXT_TABLE", $this->lng->txt("cont_table"));
		$this->tpl->setVariable("INPUT_TD_WIDTH", "td_width");
		$this->tpl->setVariable("BTN_WIDTH", "setWidth");
		$this->tpl->setVariable("BTN_TXT_WIDTH", $this->lng->txt("cont_set_width"));
		// todo: we need a css concept here!
		$select_class = ilUtil::formSelect ("","td_class",
			array("" => $this->lng->txt("none"), "ilc_Cell1" => "Cell1", "ilc_Cell2" => "Cell2",
			"ilc_Cell3" => "Cell3", "ilc_Cell4" => "Cell4"),false,true);
		$this->tpl->setVariable("SELECT_CLASS", $select_class);
		$this->tpl->setVariable("BTN_CLASS", "setClass");
		$this->tpl->setVariable("BTN_TXT_CLASS", $this->lng->txt("cont_set_class"));
		$tab_node = $this->content_obj->getNode();
		$content = $this->dom->dump_node($tab_node);
		//$dom2 =& domxml_open_mem($this->xml);
		$trans =& $this->pg_obj->getLanguageVariablesXML();
		$content = "<dummy>".$content.$trans."</dummy>";

		$xsl = file_get_contents("./content/page.xsl");
		$args = array( '/_xml' => $content, '/_xsl' => $xsl );
		$xh = xslt_create();
//echo "<b>XML</b>:".htmlentities($content).":<br>";
//echo "<b>XSLT</b>:".htmlentities($xsl).":<br>";
		$med_disabled_path = ilUtil::getImagePath("media_disabled.gif");
		$params = array ('mode' => 'table_edit', 'med_disabled_path' => $med_disabled_path);
		$output = xslt_process($xh,"arg:/_xml","arg:/_xsl",NULL,$args, $params);
		echo xslt_error($xh);
		xslt_free($xh);

		// unmask user html
		$output = str_replace("&lt;","<",$output);
		$output = str_replace("&gt;",">",$output);
		$output = str_replace("&amp;","&",$output);

//echo "<b>HTML</b>".htmlentities($output);
		$this->tpl->setVariable("CONT_TABLE", $output);


		// language
		$this->tpl->setVariable("TXT_LANGUAGE", $this->lng->txt("language"));
		$lang = ilMetaData::getLanguages();
		$select_lang = ilUtil::formSelect ($this->content_obj->getLanguage(),"tab_language",$lang,false,true);
		$this->tpl->setVariable("SELECT_LANGUAGE", $select_lang);

		// width
		$this->tpl->setVariable("TXT_TABLE_WIDTH", $this->lng->txt("cont_table_width"));
		$this->tpl->setVariable("INPUT_TABLE_WIDTH", "tab_width");
		$this->tpl->setVariable("VAL_TABLE_WIDTH", $this->content_obj->getWidth());

		// border
		$this->tpl->setVariable("TXT_TABLE_BORDER", $this->lng->txt("cont_table_border"));
		$this->tpl->setVariable("INPUT_TABLE_BORDER", "tab_border");
		$this->tpl->setVariable("VAL_TABLE_BORDER", $this->content_obj->getBorder());

		// padding
		$this->tpl->setVariable("TXT_TABLE_PADDING", $this->lng->txt("cont_table_cellpadding"));
		$this->tpl->setVariable("INPUT_TABLE_PADDING", "tab_padding");
		$this->tpl->setVariable("VAL_TABLE_PADDING", $this->content_obj->getCellPadding());

		// spacing
		$this->tpl->setVariable("TXT_TABLE_SPACING", $this->lng->txt("cont_table_cellspacing"));
		$this->tpl->setVariable("INPUT_TABLE_SPACING", "tab_spacing");
		$this->tpl->setVariable("VAL_TABLE_SPACING", $this->content_obj->getCellSpacing());

		// caption
		$caption = $this->content_obj->getCaption();
		$caption = str_replace("&", "&amp;", $caption);
		$this->tpl->setVariable("TXT_CAPTION", $this->lng->txt("cont_caption"));
		$this->tpl->setVariable("INPUT_CAPTION", "tab_caption");
		$this->tpl->setVariable("VAL_CAPTION", $caption);
		$select_align = ilUtil::formSelect ($this->content_obj->getCaptionAlign(),"tab_cap_align",
			array("top" => $this->lng->txt("cont_top"), "bottom" => $this->lng->txt("cont_bottom")),false,true);
		$this->tpl->setVariable("SELECT_CAPTION", $select_align);

		$this->tpl->parseCurrentBlock();

		// operations
		$this->tpl->setCurrentBlock("commands");
		$this->tpl->setVariable("BTN_NAME", "saveProperties");
		$this->tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
		$this->tpl->setVariable("BTN_CANCEL", "cancelUpdate");
		$this->tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
		$this->tpl->parseCurrentBlock();

	}

	/**
	* set width of selected table data cells
	*/
	function setWidth()
	{
		if (is_array($_POST["target"]))
		{
			foreach ($_POST["target"] as $hier_id)
			{
				$this->content_obj->setTDWidth($hier_id, $_POST["td_width"]);
			}
		}
		$this->setProperties();
		$this->updated = $this->pg_obj->update();
		$this->pg_obj->addHierIDs();
		$this->edit();
	}

	/**
	* set class of selected table data cells
	*/
	function setClass()
	{
		if (is_array($_POST["target"]))
		{
			foreach ($_POST["target"] as $hier_id)
			{
				$this->content_obj->setTDClass($hier_id, $_POST["td_class"]);
			}
		}
		$this->setProperties();
		$this->updated = $this->pg_obj->update();
		$this->pg_obj->addHierIDs();
		$this->edit();
	}

	
	function setProperties()
	{
		// mask html
		$caption = $_POST["tab_caption"];
		$caption = str_replace("&","&amp;", $caption);
		$caption = str_replace("<","&lt;", $caption);
		$caption = str_replace(">","&gt;", $caption);

		$this->content_obj->setLanguage($_POST["tab_language"]);
		$this->content_obj->setWidth($_POST["tab_width"]);
		$this->content_obj->setBorder($_POST["tab_border"]);
		$this->content_obj->setCellSpacing($_POST["tab_spacing"]);
		$this->content_obj->setCellPadding($_POST["tab_padding"]);
		$this->content_obj->setCaption($caption, $_POST["tab_cap_align"]);
	}
	
	/**
	* save table properties in db and return to page edit screen
	*/
	function saveProperties()
	{
		$this->setProperties();
		$this->updated = $this->pg_obj->update();
		if ($this->updated === true)
		{
			$this->ctrl->returnToParent($this, "jump".$this->hier_id);
		}
		else
		{
			$this->pg_obj->addHierIDs();
			$this->edit();
		}
	}

	/**
	* align table to right
	*/
	function rightAlign()
	{
		$this->content_obj->setHorizontalAlign("Right");
		$_SESSION["il_pg_error"] = $this->pg_obj->update();
		$this->ctrl->returnToParent($this, "jump".$this->hier_id);
	}

	/**
	* align table to left
	*/
	function leftAlign()
	{
		$this->content_obj->setHorizontalAlign("Left");
		$_SESSION["il_pg_error"] = $this->pg_obj->update();
		$this->ctrl->returnToParent($this, "jump".$this->hier_id);
	}

	/**
	* align table to left
	*/
	function centerAlign()
	{
		$this->content_obj->setHorizontalAlign("Center");
		$_SESSION["il_pg_error"] = $this->pg_obj->update();
		$this->ctrl->returnToParent($this, "jump".$this->hier_id);
	}

	/**
	* align table to left float
	*/
	function leftFloatAlign()
	{
		$this->content_obj->setHorizontalAlign("LeftFloat");
		$_SESSION["il_pg_error"] = $this->pg_obj->update();
		$this->ctrl->returnToParent($this, "jump".$this->hier_id);
	}

	/**
	* align table to left
	*/
	function rightFloatAlign()
	{
		$this->content_obj->setHorizontalAlign("RightFloat");
		$_SESSION["il_pg_error"] = $this->pg_obj->update();
		$this->ctrl->returnToParent($this, "jump".$this->hier_id);
	}

	/**
	* insert new table form
	*/
	function insert()
	{
		global $ilUser;

		$this->setTabs();

		// new table form (input of rows and columns)
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.table_new.html", "content");
		$this->tpl->setVariable("TXT_ACTION", $this->lng->txt("cont_insert_table"));
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		$this->displayValidationError();

		for($i=1; $i<=10; $i++)
		{
			$nr[$i] = $i;
		}

		if ($_SESSION["il_text_lang_".$_GET["ref_id"]] != "")
		{
			$s_lang = $_SESSION["il_text_lang_".$_GET["ref_id"]];
		}
		else
		{
			$s_lang = $ilUser->getLanguage();
		}

		// select fields for number of columns
		$this->tpl->setVariable("TXT_LANGUAGE", $this->lng->txt("language"));
		$lang = ilMetaData::getLanguages();
		$select_language = ilUtil::formSelect ($s_lang, "tab_language", $lang, false, true);
		$this->tpl->setVariable("SELECT_LANGUAGE", $select_language);
		$this->tpl->setVariable("TXT_COLS", $this->lng->txt("cont_nr_cols"));
		$select_cols = ilUtil::formSelect ("2","nr_cols",$nr,false,true);
		$this->tpl->setVariable("SELECT_COLS", $select_cols);
		$this->tpl->setVariable("TXT_ROWS", $this->lng->txt("cont_nr_rows"));
		$select_rows = ilUtil::formSelect ("2","nr_rows",$nr,false,true);
		$this->tpl->setVariable("SELECT_ROWS", $select_rows);
		
		//import html table
		$this->tpl->setVariable("TXT_HTML_IMPORT", $this->lng->txt("cont_table_html_import"));
		$this->tpl->setVariable("TXT_SPREADSHEET", $this->lng->txt("cont_table_spreadsheet_import"));		
		$this->tpl->setVariable("TXT_BTN_HTML_IMPORT", $this->lng->txt("import"));		
		$this->tpl->setVariable("TXT_HTML_IMPORT_INFO", $this->lng->txt("cont_table_html_import_info"));
		$this->tpl->setVariable("TXT_SPREADSHEET_IMPORT_INFO", $this->lng->txt("cont_table_spreadsheet_import_info"));		
		$this->tpl->setVariable("CMD_HTML_IMPORT", "create_tab");
		$this->tpl->setVariable("SELECT_ROWS", $select_rows);
					
		$this->tpl->parseCurrentBlock();

		// operations
		$this->tpl->setCurrentBlock("commands");
		$this->tpl->setVariable("BTN_NAME", "create_tab");
		$this->tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
		$this->tpl->setVariable("BTN_CANCEL", "cancelCreate");
		$this->tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
		$this->tpl->parseCurrentBlock();

	}
	

	/**
	* create new table in dom and update page in db
	*/
	function create()
	{
		global	$lng;
		$this->content_obj = new ilPCTable($this->dom);
		$this->content_obj->create($this->pg_obj, $this->hier_id);
		$this->content_obj->setLanguage($_POST["tab_language"]);
		$import_table = trim($_POST["import_table"]);
		
		// import xhtml or spreadsheet table
		if (!empty ($import_table))
		{
			switch($_POST["import_type"])
			{
				// xhtml import
				case "html":
					if (!$this->content_obj->importHtml ($_POST["tab_language"], $import_table))
					{
						$this->insert();
						return;	
					}
					break;
					
				// spreadsheet
				case "spreadsheet":
					$this->content_obj->importSpreadsheet($_POST["tab_language"], $import_table);
					break;
			}
		}
		else
		{		
			$this->content_obj->addRows($_POST["nr_rows"], $_POST["nr_cols"]);
		}
		
		$this->updated = $this->pg_obj->update();
		
		if ($this->updated === true)
		{
			$this->ctrl->returnToParent($this, "jump".$this->hier_id);
		}
		else
		{
			$this->insert();
		}
	}

	/**
	* output tabs
	*/
	function setTabs()
	{
		global $ilTabs;

		// catch feedback message
		#include_once("classes/class.ilTabsGUI.php");
		#$tabs_gui =& new ilTabsGUI();
		$this->getTabs($ilTabs);
		#$this->tpl->setVariable("TABS", $tabs_gui->getHTML());
	}

	/**
	* adds tabs to tab gui object
	*
	* @param	object		$tabs_gui		ilTabsGUI object
	*/
	function getTabs(&$tabs_gui)
	{
		// back to upper context
		/*
		$tabs_gui->addTarget("cont_back",
			$this->ctrl->getParentReturn($this), "",
			"");
		*/
	}

}
?>
