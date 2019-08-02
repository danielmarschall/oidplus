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

if (!defined('IN_OIDPLUS')) die();

define('OIDPLUS_OBJECT_CACHING', true);

abstract class OIDplusObject {
	public static function parse($node_id) { // please overwrite this function!
		// TODO: in case we are not calling this class directly, check if function is overwritten and throw exception otherwise
		foreach (OIDplus::getRegisteredObjectTypes() as $ot) {
			if ($obj = $ot::parse($node_id)) return $obj;
		}
		return null;
	}

	public function getOid() {
		if ($this->ns() == 'oid') {
			return $this->getDotNotation();
		} else {
			$sid = OIDplus::system_id(true);
			if (empty($sid)) return false;
			return $sid . '.' . smallhash($this->nodeId());
		}
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

	public abstract function getContentPage(&$title, &$content, &$icon);

	public static function getRaRoots($ra_email=null) {
		if ($ra_email instanceof OIDplusRA) $ra_email = $ra_email->raEmail();

		$out = array();

		if (!OIDPLUS_OBJECT_CACHING) {
			if (is_null($ra_email)) {
				$res = OIDplus::db()->query("select oChild.id as id, oChild.ra_email as child_mail, oParent.ra_email as parent_mail from ".OIDPLUS_TABLENAME_PREFIX."objects as oChild ".
				                            "left join ".OIDPLUS_TABLENAME_PREFIX."objects as oParent on oChild.parent = oParent.id ".
				                            "order by ".OIDplus::db()->natOrder('oChild.id'));
				while ($row = OIDplus::db()->fetch_array($res)) {
					if (!OIDplus::authUtils()::isRaLoggedIn($row['parent_mail']) && OIDplus::authUtils()::isRaLoggedIn($row['child_mail'])) {
						$x = self::parse($row['id']); // can be FALSE if namespace was disabled
						if ($x) $out[] = $x;
					}
				}
			} else {
				$res = OIDplus::db()->query("select oChild.id as id from ".OIDPLUS_TABLENAME_PREFIX."objects as oChild ".
				                            "left join ".OIDPLUS_TABLENAME_PREFIX."objects as oParent on oChild.parent = oParent.id ".
				                            "where (ifnull(oParent.ra_email,'') <> ? and ifnull(oChild.ra_email,'') = ?) or ".
				                            "      (oParent.ra_email is null and ifnull(oChild.ra_email,'') = ?) ".
				                            "order by ".OIDplus::db()->natOrder('oChild.id'), array($ra_email, $ra_email, $ra_email));
				while ($row = OIDplus::db()->fetch_array($res)) {
					$x = self::parse($row['id']); // can be FALSE if namespace was disabled
					if ($x) $out[] = self::parse($row['id']);
				}
			}
		} else {
			if (is_null($ra_email)) {
				$ra_mails_to_check = OIDplusAuthUtils::loggedInRaList();
				if (count($ra_mails_to_check) == 0) return $out;
			} else {
				$ra_mails_to_check = array($ra_email);
			}

			self::buildObjectInformationCache();

			foreach ($ra_mails_to_check as $check_ra_mail) {
				$tmp = self::$object_info_cache;

				foreach ($tmp as $id => list($confidential, $parent, $ra_email)) {
					$tmp[$id][] = $ra_email == $check_ra_mail; // add a temporary "choose flag"
				}

				foreach ($tmp as $id => list($confidential, $parent, $ra_email, $choose_flag)) {
					if (isset($tmp[$parent])) {
						if ($tmp[$parent][self::CACHE_RA_EMAIL] == $ra_email) {
							$tmp[$id][3] = false; // if the parent has the same RA, then this OID cannot be a root => remove "choose flag"
						}
					}
				}

				$out_part = array();

				foreach ($tmp as $id => list($confidential, $parent, $ra_email, $choose_flag)) {
					if ($choose_flag) {
						$out_part[] = $id;
					}
				}

				natsort($out_part);

				foreach ($out_part as $id) {
					$obj = self::parse($id);
					if ($obj) $out[] = $obj;
				}
			}
		}

		return $out;
	}

	public static function getAllNonConfidential() {
		$out = array();

		if (!OIDPLUS_OBJECT_CACHING) {
			$res = OIDplus::db()->query("select id from ".OIDPLUS_TABLENAME_PREFIX."objects where confidential = 0 order by ".OIDplus::db()->natOrder('id'), $roots);

			while ($row = OIDplus::db()->fetch_array($res)) {
				$obj = self::parse($row['id']); // will be NULL if the object type is not registered
				if ($obj) {
					$out[] = $row['id'];
				}
			}
		} else {
			self::buildObjectInformationCache();

			foreach (self::$object_info_cache as $id => list($confidential, $parent, $ra_email)) {
				if (!$confidential) {
					$obj = self::parse($id); // will be NULL if the object type is not registered
					if ($obj) {
						$out[] = $id;
					}
				}
			}
		}

		return $out;
	}

	public function isConfidential() {
		if (!OIDPLUS_OBJECT_CACHING) {
			$curid = $this->nodeId();
			$orig_curid = $curid;
			if (isset(self::$object_info_cache[$curid])) return self::$object_info_cache[$curid];
			// Recursively search for the confidential flag in the parents
			while (OIDplus::db()->num_rows($res = OIDplus::db()->query("select parent, confidential from ".OIDPLUS_TABLENAME_PREFIX."objects where id = ?", array($curid))) > 0) {
				$row = OIDplus::db()->fetch_array($res);
				if ($row['confidential']) {
					self::$object_info_cache[$curid] = true;
					self::$object_info_cache[$orig_curid] = true;
					return true;
				} else {
					self::$object_info_cache[$curid] = false;
				}
				$curid = $row['parent'];
				if (isset(self::$object_info_cache[$curid])) {
					self::$object_info_cache[$orig_curid] = self::$object_info_cache[$curid];
					return self::$object_info_cache[$curid];
				}
			}

			self::$object_info_cache[$orig_curid] = false;
			return false;
		} else {
			self::buildObjectInformationCache();

			$curid = $this->nodeId();
			// Recursively search for the confidential flag in the parents
			if (isset(self::$object_info_cache[$curid])) {
				if (self::$object_info_cache[$curid][self::CACHE_CONFIDENTIAL]) return true;
				$curid = self::$object_info_cache[$curid][self::CACHE_PARENT];
			}
			return false;
		}
	}

	public function isChildOf(OIDplusObject $obj) {
		if (!OIDPLUS_OBJECT_CACHING) {
			$curid = $this->nodeId();
			while (OIDplus::db()->num_rows($res = OIDplus::db()->query("select parent from ".OIDPLUS_TABLENAME_PREFIX."objects where id = ?", array($curid))) > 0) {
				$row = OIDplus::db()->fetch_array($res);
				if ($curid == $obj->nodeId()) return true;
				$curid = $row['parent'];
			}
			return false;
		} else {
			self::buildObjectInformationCache();

			$curid = $this->nodeId();
			if (isset(self::$object_info_cache[$curid])) {
				if ($curid == $obj->nodeId()) return true;
				$curid = self::$object_info_cache[$curid][self::CACHE_PARENT];
			}
			return false;
		}
	}

	public function getChildren() {
		$out = array();
		if (!OIDPLUS_OBJECT_CACHING) {
			$res = OIDplus::db()->query("select id from ".OIDPLUS_TABLENAME_PREFIX."objects where parent = ?", array($this->nodeId()));
			while ($row = OIDplus::db()->fetch_array($res)) {
				$obj = self::parse($row['id']);
				if (!$obj) continue;
				$out[] = $obj;
			}
		} else {
			self::buildObjectInformationCache();

			foreach (self::$object_info_cache as $id => list($confidential, $parent, $ra_email)) {
				if ($parent == $this->nodeId()) {
					$obj = self::parse($id);
					if (!$obj) continue;
					$out[] = $obj;
				}
			}
		}
		return $out;
	}

	public function getRa() {
		return new OIDplusRA($this->getRaMail());
	}

	public function userHasReadRights($ra_email=null) {
		if ($ra_email instanceof OIDplusRA) $ra_email = $ra_email->raEmail();

		// Admin may do everything
		if (OIDplus::authUtils()::isAdminLoggedIn()) return true;

		// If it is not confidential, everybody can read/see it.
		if (!$this->isConfidential()) return true;

		// If we own the object, we may see it
		if (is_null($ra_email)) {
			if ($this->userHasWriteRights()) return true;
		} else {
			if ($this->getRaMail() == $ra_email) return true;
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
			$ra_email = $this->getRaMail();
		} else {
			$ra_email = $row['ra_email'];
		}
		// TODO: have different icons for Leaf-Nodes
		if (OIDplus::authUtils()::isRaLoggedIn($ra_email)) {
			$icon = 'plugins/objectTypes/'.$namespace.'/img/treeicon_own.png';
		} else {
			$icon = 'plugins/objectTypes/'.$namespace.'/img/treeicon_general.png';
		}
		if (!file_exists($icon)) $icon = null; // default icon (folder)
		return $icon;
	}

	public static function exists($id) {
		if (!OIDPLUS_OBJECT_CACHING) {
			$res = OIDplus::db()->query("select id from ".OIDPLUS_TABLENAME_PREFIX."objects where id = ?", array($id));
			return OIDplus::db()->num_rows($res) > 0;
		} else {
			self::buildObjectInformationCache();
			return isset(self::$object_info_cache[$id]);
		}
	}

	public function getParent() {
		if (!OIDPLUS_OBJECT_CACHING) {
			$res = OIDplus::db()->query("select parent from ".OIDPLUS_TABLENAME_PREFIX."objects where id = ?", array($this->nodeId()));
			$row = OIDplus::db()->fetch_array($res);
			$parent = $row['parent'];
			$obj = OIDplusObject::parse($parent);
			if ($obj) return $obj;
		} else {
			self::buildObjectInformationCache();
			if (isset(self::$object_info_cache[$this->nodeId()])) {
				$parent = self::$object_info_cache[$this->nodeId()][self::CACHE_PARENT];
				$obj = OIDplusObject::parse($parent);
				if ($obj) return $obj;
			}

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
	}

	public function getRaMail() {
		if (!OIDPLUS_OBJECT_CACHING) {
			$res = OIDplus::db()->query("select ra_email from ".OIDPLUS_TABLENAME_PREFIX."objects where id = ?", array($this->nodeId()));
			$row = OIDplus::db()->fetch_array($res);
			return $row['ra_email'];
		} else {
			self::buildObjectInformationCache();
			if (isset(self::$object_info_cache[$this->nodeId()])) {
				return self::$object_info_cache[$this->nodeId()][self::CACHE_RA_EMAIL];
			}
			return false;
		}
	}

	public function userHasParentalWriteRights($ra_email=null) {
		if ($ra_email instanceof OIDplusRA) $ra_email = $ra_email->raEmail();

		if (is_null($ra_email)) {
			if (OIDplus::authUtils()::isAdminLoggedIn()) return true;
		}

		$objParent = $this->getParent();
		if (is_null($objParent)) return false;
		return $objParent->userHasWriteRights($ra_email);
	}

	public function userHasWriteRights($ra_email=null) {
		if ($ra_email instanceof OIDplusRA) $ra_email = $ra_email->raEmail();

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

		if (!OIDPLUS_OBJECT_CACHING) {
			$res = OIDplus::db()->query("select id from ".OIDPLUS_TABLENAME_PREFIX."objects where id like ?", array($obj->ns().':%'));
			while ($row = OIDplus::db()->fetch_object($res)) {
				$test = OIDplusObject::parse($row->id);
				if ($obj->equals($test)) return $test;
			}
			return false;
		} else {
			self::buildObjectInformationCache();
			foreach (self::$object_info_cache as $id => list($confidential, $parent, $ra_email)) {
				if (strpos($id, $obj->ns().':') === 0) {
					$test = OIDplusObject::parse($id);
					if ($obj->equals($test)) return $test;
				}
			}
			return false;
		}
	}

	public function one_up() {
		return null; // not implemented
	}

	// Caching stuff

	protected static $object_info_cache = null;

	public static function resetObjectInformationCache() {
		self::$object_info_cache = null;
	}

	const CACHE_CONFIDENTIAL = 0;
	const CACHE_PARENT = 1;
	const CACHE_RA_EMAIL = 2;

	private static function buildObjectInformationCache() {
		if (is_null(self::$object_info_cache)) {
			self::$object_info_cache = array();
			$res = OIDplus::db()->query("select id, parent, confidential, ra_email from ".OIDPLUS_TABLENAME_PREFIX."objects");
			while ($row = OIDplus::db()->fetch_array($res)) {
				if ($row['confidential'] == chr(0)) $row['confidential'] = false; // ODBC...
				if ($row['confidential'] == chr(1)) $row['confidential'] = true; // ODBC...
				self::$object_info_cache[$row['id']] = array($row['confidential'], $row['parent'], $row['ra_email']);
			}
		}
	}
}
