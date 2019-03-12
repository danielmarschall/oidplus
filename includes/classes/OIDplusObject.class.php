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

	public abstract function getContentPage(&$title, &$content);

	private static function getRaRoots_rec($parent=null, $ra_email=null, &$out, $prev_owns) {
		if (is_null($parent)) {
			$roots = array();
			foreach (self::$registeredObjectTypes as $ot) {
				$roots[] = "parent = '" . OIDplus::db()->real_escape_string($ot::root()) . "'";
			}
			$roots = implode(' or ', $roots);
		} else {
			$roots = "parent = '" . OIDplus::db()->real_escape_string($parent) . "'";
		}

		$res = OIDplus::db()->query("select id, ra_email from ".OIDPLUS_TABLENAME_PREFIX."objects where $roots order by ".OIDplus::db()->natOrder('id'));

		$this_owns = array();
		$this_all = array();
		while ($row = OIDplus::db()->fetch_array($res)) {
			if (is_null($ra_email)) {
				// if $ra_email is null, we want to query only the roots of the currently logged in user
				$owns = OIDplus::authUtils()::isRaLoggedIn($row['ra_email']);
			} else {
				$owns = $row['ra_email'] == $ra_email;
			}

			if ($owns) $this_owns[] = $row['id'];
			$this_all[] = $row['id'];
		}

		foreach ($this_owns as $this_ra) {
			$nogap = true;
			if (!is_null($prev_owns)) {
				// Check if we have any gaps. If there is a "gap" in the hierarchy, then we need to count that as a second root of that RA ("reintroduce ownership")
				foreach ($prev_owns as $prev) {
					if (oid_up(explode(':',$this_ra)[1]) == explode(':',$prev)[1]) $nogap = false;
				}
			}
			if ($nogap) $out[] = self::parse($this_ra);
		}

		foreach ($this_all as $this_ra) {
			self::getRaRoots_rec($this_ra, $ra_email, $out, $this_owns);
		}
	}

	public static function getRaRoots($ra_email=null) {
		$out = array();
		self::getRaRoots_rec(null, $ra_email, $out, null);
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
		$namespace = self::parse($this->nodeId())::ns(); // TODO: warum muss ich das machen??? $this::ns() gibt abstrakten fehler

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

	public function getParent() {
		$res = OIDplus::db()->query("select parent from ".OIDPLUS_TABLENAME_PREFIX."objects where id = '".OIDplus::db()->real_escape_string($this->nodeId())."'");
		$row = OIDplus::db()->fetch_array($res);
		$parent = $row['parent'];
		return OIDplusObject::parse($parent);
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
}
