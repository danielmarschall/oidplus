<?php

/*
 * OIDplus 2.0
 * Copyright 2019 Daniel Marschall, ViaThinkSoft
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

abstract class OIDplusObject {
	public static $registeredObjectTypes = array();

	public static function parse($node_id) { // please overwrite this function!
		// TODO: in case we are not calling this class directly, check if function is overwritten and throw exception otherwise
		foreach (self::$registeredObjectTypes as $ot) {
			if ($obj = $ot::parse($node_id)) return $obj;
		}
		return null;
	}

	public abstract static function objectTypeTitle();

	public abstract static function objectTypeTitleShort();

	public abstract static function ns();

	public abstract static function root();

	public abstract function isRoot();

	public abstract function nodeId();

	public abstract function addString($str);

	public abstract function crudShowId(OIDplusObject $parent);

	public abstract function crudInsertPrefix();

	public abstract function jsTreeNodeName(OIDplusObject $parent = null);

	public abstract function defaultTitle();

	public abstract function isLeafNode();

	public abstract function getContentPage(&$title, &$content);

	public static function getRaRoots($ra_email=null) {
		$out = array();
		if (is_null($ra_email)) {
			$res = OIDplus::db()->query("select oChild.id as id, oChild.ra_email as child_mail, oParent.ra_email as parent_mail from ".OIDPLUS_TABLENAME_PREFIX."objects as oChild ".
			                            "left join ".OIDPLUS_TABLENAME_PREFIX."objects as oParent on oChild.parent = oParent.id ".
			                            "order by ".OIDplus::db()->natOrder('oChild.id'));
			while ($row = OIDplus::db()->fetch_array($res)) {
				if (!OIDplus::authUtils()::isRaLoggedIn($row['parent_mail']) && OIDplus::authUtils()::isRaLoggedIn($row['child_mail'])) {
					$out[] = self::parse($row['id']);
				}
			}
		} else {
			$res = OIDplus::db()->query("select oChild.id as id from ".OIDPLUS_TABLENAME_PREFIX."objects as oChild ".
			                            "left join ".OIDPLUS_TABLENAME_PREFIX."objects as oParent on oChild.parent = oParent.id ".
			                            "where ifnull(oParent.ra_email,'') <> '".OIDplus::db()->real_escape_string($ra_email)."' and oChild.ra_email = '".OIDplus::db()->real_escape_string($ra_email)."' ".
			                            "order by ".OIDplus::db()->natOrder('oChild.id'));
			while ($row = OIDplus::db()->fetch_array($res)) {
				$out[] = self::parse($row['id']);
			}
		}
		return $out;
	}

	private static function getAllNonConfidential_rec($parent=null, &$out) {
		if (is_null($parent)) {
			$roots = array();
			foreach (self::$registeredObjectTypes as $ot) {
				$roots[] = "parent = '" . OIDplus::db()->real_escape_string($ot::root()) . "'";
			}
			$roots = implode(' or ', $roots);
		} else {
			$roots = "parent = '" . OIDplus::db()->real_escape_string($parent) . "'";
		}

		$res = OIDplus::db()->query("select id, confidential from ".OIDPLUS_TABLENAME_PREFIX."objects where $roots order by ".OIDplus::db()->natOrder('id'));

		while ($row = OIDplus::db()->fetch_array($res)) {
			if ($row['confidential'] == '1') {
				// do nothing
			} else {
				$out[] = $row['id'];
				self::getAllNonConfidential_rec($row['id'], $out);
			}
		}
	}

	public static function getAllNonConfidential() {
		$out = array();
		self::getAllNonConfidential_rec(null, $out);
		return $out;
	}

	public function isConfidential() {
		$curid = $this->nodeId();
		// Recursively search for the confidential flag in the parents
		while (OIDplus::db()->num_rows($res = OIDplus::db()->query("select parent, confidential from ".OIDPLUS_TABLENAME_PREFIX."objects where id = '".OIDplus::db()->real_escape_string($curid)."'")) > 0) {
			$row = OIDplus::db()->fetch_array($res);
			if ($row['confidential']) return true;
			$curid = $row['parent'];
		}

		return false;
	}

	public function isChildOf(OIDplusObject $obj) {
		$curid = $this->nodeId();
		while (OIDplus::db()->num_rows($res = OIDplus::db()->query("select parent from ".OIDPLUS_TABLENAME_PREFIX."objects where id = '".OIDplus::db()->real_escape_string($curid)."'")) > 0) {
			$row = OIDplus::db()->fetch_array($res);
			if ($curid == $obj->nodeId()) return true;
			$curid = $row['parent'];
		}

		return false;
	}

	public function userHasReadRights($ra_email=null) {
		// Admin may do everything
		if (OIDplus::authUtils()::isAdminLoggedIn()) return true;

		// If it is not confidential, everybody can read/see it.
		if (!$this->isConfidential()) return true;

		// If we own the object, we may see it
		if (is_null($ra_email)) {
			if ($this->userHasWriteRights()) return true;
		} else {
			$res = OIDplus::db()->query("select ra_email from ".OIDPLUS_TABLENAME_PREFIX."objects where id = '".OIDplus::db()->real_escape_string($this->nodeId())."'");
			$row = OIDplus::db()->fetch_array($res);
			if ($row['ra_email'] == $ra_email) return true;
		}

		// If someone has rights to an object below our confidential node,
		// we let him see the confidential node,
		// Otherwise he could not browse through to his own node.
		$roots = $this->getRaRoots($ra_email);
		foreach ($roots as $root) {
			if ($root->isChildOf($this)) return true;
		}

		return false;
	}

	public function getIcon($row=null) {
		$namespace = $this->ns(); // must use $this, not self::, otherwise the virtual method will not be called

		if (is_null($row)) {
			$res = OIDplus::db()->query("select ra_email from ".OIDPLUS_TABLENAME_PREFIX."objects where id = '".OIDplus::db()->real_escape_string($this->nodeId())."'");
			$row = OIDplus::db()->fetch_array($res);
		}
		// TODO: have different icons for Leaf-Nodes
		if (OIDplus::authUtils()::isRaLoggedIn($row['ra_email'])) {
			$icon = 'plugins/objectTypes/'.$namespace.'/img/treeicon_own.png';
		} else {
			$icon = 'plugins/objectTypes/'.$namespace.'/img/treeicon_general.png';
		}
		if (!file_exists($icon)) $icon = null; // default icon (folder)
		return $icon;
	}

	public static function exists($id) {
		$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where id = '".OIDplus::db()->real_escape_string($id)."'");
		return OIDplus::db()->num_rows($res) > 0;
	}

	public function getParent() {
		$res = OIDplus::db()->query("select parent from ".OIDPLUS_TABLENAME_PREFIX."objects where id = '".OIDplus::db()->real_escape_string($this->nodeId())."'");
		$row = OIDplus::db()->fetch_array($res);
		$parent = $row['parent'];
		$obj = OIDplusObject::parse($parent);
		if ($obj) return $obj;

		// If this OID does not exist, the SQL query "select parent from ..." does not work. So we try to find the next possible parent using one_up()
		$cur = $this->one_up();
		if (!$cur) return false;
		do {
			if ($fitting = self::findFitting($cur->nodeId())) return $fitting;

			$prev = $cur;
			$cur = $cur->one_up();
			if (!$cur) return false;
		} while ($prev != $cur);

		return false;
	}

	public function getRaMail() {
		$res = OIDplus::db()->query("select ra_email from ".OIDPLUS_TABLENAME_PREFIX."objects where id = '".OIDplus::db()->real_escape_string($this->nodeId())."'");
		$row = OIDplus::db()->fetch_array($res);
		return $row['ra_email'];
	}

	public function userHasParentalWriteRights($ra_email=null) {
		if (is_null($ra_email)) {
			if (OIDplus::authUtils()::isAdminLoggedIn()) return true;
		}

		$objParent = $this->getParent();
		if (is_null($objParent)) return false;
		return $objParent->userHasWriteRights($ra_email);
	}

	public function userHasWriteRights($ra_email=null) {
		if (is_null($ra_email)) {
			if (OIDplus::authUtils()::isAdminLoggedIn()) return true;
			return OIDplus::authUtils()::isRaLoggedIn($this->getRaMail());
		} else {
			return $this->getRaMail() == $ra_email;
		}
	}

	public function distance($to) {
		return null; // not implemented
	}

	public function equals($obj) {
		if (!is_object($obj)) $obj = OIDplusObject::parse($obj);
		if (!($obj instanceof $this)) return false;

		$distance = $this->distance($obj);
		if (is_numeric($distance)) return $distance === 0; // if the distance function is implemented, use it

		return $this->nodeId() == $obj->nodeId(); // otherwise compare the node id case-sensitive
	}

	public static function findFitting($id) {
		$obj = OIDplusObject::parse($id);
		if (!$obj) throw new Exception("findFitting: Parse failed\n");

		$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where id like '".OIDplus::db()->real_escape_string($obj->ns()).":%'");
		while ($row = OIDplus::db()->fetch_object($res)) {
			$test = OIDplusObject::parse($row->id);
			if ($obj->equals($test)) return $test;
		}
		return false;
	}

	public function one_up() {
		return null; // not implemented
	}
}
