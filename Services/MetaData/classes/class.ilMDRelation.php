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


/**
* Meta Data class (element relation)
*
* @package ilias-core
* @version $Id$
*/
include_once 'class.ilMDBase.php';

class ilMDRelation extends ilMDBase
{
	function ilMDRelation($a_rbac_id = 0,$a_obj_id = 0,$a_obj_type = '')
	{
		parent::ilMDBase($a_rbac_id,
						 $a_obj_id,
						 $a_obj_type);
	}


	// METHODS OF CHILD OBJECTS (Taxon)
	function &getIdentifier_Ids()
	{
		include_once 'Services/MetaData/classes/class.ilMDIdentifier_.php';

		return ilMDIdentifier_::_getIds($this->getRBACId(),$this->getObjId(),$this->getMetaId(),'meta_relation');
	}
	function &getIdentifier_($a_identifier__id)
	{
		include_once 'Services/MetaData/classes/class.ilMDIdentifier_.php';

		if(!$a_identifier__id)
		{
			return false;
		}
		$ide =& new ilMDIdentifier_();
		$ide->setMetaId($a_identifier__id);

		return $ide;
	}
	function &addIdentifier_()
	{
		include_once 'Services/MetaData/classes/class.ilMDIdentifier_.php';

		$ide =& new ilMDIdentifier_($this->getRBACId(),$this->getObjId(),$this->getObjType());
		$ide->setParentId($this->getMetaId());
		$ide->setParentType('meta_relation');

		return $ide;
	}

	function &getDescriptionIds()
	{
		include_once 'Services/MetaData/classes/class.ilMDDescription.php';

		return ilMdDescription::_getIds($this->getRBACId(),$this->getObjId(),$this->getMetaId(),'meta_relation');
	}
	function &getDescription($a_description_id)
	{
		include_once 'Services/MetaData/classes/class.ilMDDescription.php';
		
		if(!$a_description_id)
		{
			return false;
		}
		$des =& new ilMDDescription();
		$des->setMetaId($a_description_id);

		return $des;
	}
	function &addDescription()
	{
		include_once 'Services/MetaData/classes/class.ilMDDescription.php';
		
		$des =& new ilMDDescription($this->getRBACId(),$this->getObjId(),$this->getObjType());
		$des->setParentId($this->getMetaId());
		$des->setParentType('meta_relation');

		return $des;
	}
	// SET/GET
	function setKind($a_kind)
	{
		switch($a_kind)
		{
			case 'IsPartOf':
			case 'HasPart':
			case 'IsVersionOf':
			case 'HasVersion':
			case 'IsFormatOf':
			case 'HasFormat':
			case 'References':
			case 'IsReferencedBy':
			case 'IsBasedOn':
			case 'IsBasisFor':
			case 'Requires':
			case 'IsRequiredBy':
				$this->kind = $a_kind;
				return true;

			default:
				return false;
		}
	}
	function getKind()
	{
		return $this->kind;
	}


	function save()
	{
		if($this->db->autoExecute('il_meta_relation',
								  $this->__getFields(),
								  DB_AUTOQUERY_INSERT))
		{
			$this->setMetaId($this->db->getLastInsertId());

			return $this->getMetaId();
		}
		return false;
	}

	function update()
	{
		if($this->getMetaId())
		{
			if($this->db->autoExecute('il_meta_relation',
									  $this->__getFields(),
									  DB_AUTOQUERY_UPDATE,
									  "meta_relation_id = '".$this->getMetaId()."'"))
			{
				return true;
			}
		}
		return false;
	}

	function delete()
	{
		if($this->getMetaId())
		{
			$query = "DELETE FROM il_meta_relation ".
				"WHERE meta_relation_id = '".$this->getMetaId()."'";
			
			$this->db->query($query);

			foreach($this->getIdentifier_Ids() as $id)
			{
				$ide = $this->getIdentifier_($id);
				$ide->delete();
			}
			foreach($this->getDescriptionIds() as $id)
			{
				$des = $this->getDescription();
				$des->delete();
			}
			
			return true;
		}
		return false;
	}
			

	function __getFields()
	{
		return array('rbac_id'	=> $this->getRBACId(),
					 'obj_id'	=> $this->getObjId(),
					 'obj_type'	=> ilUtil::prepareDBString($this->getObjType()),
					 'kind'		=> ilUtil::prepareDBString($this->getKind()));
	}

	function read()
	{
		if($this->getMetaId())
		{
			$query = "SELECT * FROM il_meta_relation ".
				"WHERE meta_relation_id = '".$this->getMetaId()."'";

			$res = $this->db->query($query);
			while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
			{
				$this->setRBACId($row->rbac_id);
				$this->setObjId($row->obj_id);
				$this->setObjType($row->obj_type);
				$this->setKind(ilUtil::stripSlashes($row->kind));
			}
		}
		return true;
	}

	/*
	 * XML Export of all meta data
	 * @param object (xml writer) see class.ilMD2XML.php
	 * 
	 */
	function toXML(&$writer)
	{
		$writer->xmlStartTag('Relation',array('Kind' => $this->getKind()));
		$writer->xmlStartTag('Resource');

		// Identifier_
		foreach($this->getIdentifier_Ids() as $id)
		{
			$ide =& $this->getIdentifier_($id);
			$ide->toXML($writer);
		}
		// Description
		foreach($this->getDescriptionIds() as $id)
		{
			$des =& $this->getDescription($id);
			$des->toXML($writer);
		}
		$writer->xmlEndTag('Resource');
		$writer->xmlEndTag('Relation');
	}

				

	// STATIC
	function _getIds($a_rbac_id,$a_obj_id)
	{
		global $ilDB;

		$query = "SELECT meta_relation_id FROM il_meta_relation ".
			"WHERE rbac_id = '".$a_rbac_id."' ".
			"AND obj_id = '".$a_obj_id."' ";

		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$ids[] = $row->meta_relation_id;
		}
		return $ids ? $ids : array();
	}
}
?>