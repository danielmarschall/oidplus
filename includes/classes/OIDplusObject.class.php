<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2022 Daniel Marschall, ViaThinkSoft
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

if (!defined('INSIDE_OIDPLUS')) die();

abstract class OIDplusObject extends OIDplusBaseClass {
	const UUID_NAMEBASED_NS_OidPlusMisc = 'ad1654e6-7e15-11e4-9ef6-78e3b5fc7f22';

	public static function parse($node_id) { // please overwrite this function!
		foreach (OIDplus::getEnabledObjectTypes() as $ot) {
			try {
				$good = false;
				if (get_parent_class($ot) == 'OIDplusObject') {
					$reflector = new \ReflectionMethod($ot, 'parse');
					$isImplemented = ($reflector->getDeclaringClass()->getName() === $ot);
					if ($isImplemented) { // avoid endless loop if parse is not overriden
						$good = true;
					}
				}
				// We need to do the workaround with "$good", otherwise PHPstan shows
				// "Call to an undefined static method object::parse()"
				if ($good && $obj = $ot::parse($node_id)) return $obj;
			} catch (Exception $e) {}
		}
		return null;
	}

	public function /*OIDplusAltId[]*/ getAltIds() {
		if ($this->isRoot()) return array();

		$ids = array();
		if ($this->ns() != 'oid') {
			// Creates an OIDplus-Hash-OID
			$sid = OIDplus::getSystemId(true);
			if (!empty($sid)) {
				$ns_oid = $this->getPlugin()->getManifest()->getOid();
				if (str_starts_with($ns_oid, '1.3.6.1.4.1.37476.2.5.2.')) {
					// Official ViaThinkSoft object type plugins
					// For backwards compatibility with existing IDs,
					// set the hash_payload as '<namespace>:<id>'
					$hash_payload = $this->nodeId(true);
				} else {
					// Third-party object type plugins
					// Set the hash_payload as '<plugin oid>:<id>'
					$hash_payload = $ns_oid.':'.$this->nodeId(false);
				}
				$oid = $sid . '.' . smallhash($hash_payload);
				$ids[] = new OIDplusAltId('oid', $oid, _L('OIDplus Information Object ID'));
			}

			// Make a namebased UUID, but...
			// ... exclude GUID, because a GUID is already a GUID
			// ... exclude OID, because an OID already has a record UUID_NAMEBASED_NS_OID set  by class OIDplusOid
			if ($this->ns() != 'guid') {
				$ids[] = new OIDplusAltId('guid', gen_uuid_md5_namebased(self::UUID_NAMEBASED_NS_OidPlusMisc, $this->nodeId()), _L('Name based version 3 / MD5 UUID with namespace %1','UUID_NAMEBASED_NS_OidPlusMisc'));
				$ids[] = new OIDplusAltId('guid', gen_uuid_sha1_namebased(self::UUID_NAMEBASED_NS_OidPlusMisc, $this->nodeId()), _L('Name based version 5 / SHA1 UUID with namespace %1','UUID_NAMEBASED_NS_OidPlusMisc'));
			}
		}
		return $ids;
	}

	public abstract static function objectTypeTitle();

	public abstract static function objectTypeTitleShort();

	public function getPlugin()/*: ?OIDplusObjectTypePlugin */ {
		$res = null;
		$plugins = OIDplus::getObjectTypePlugins();
		foreach ($plugins as $plugin) {
			if (get_class($this) == $plugin::getObjectTypeClassName($this)) {
				return $plugin;
			}
		}
		return $res;
	}

	public abstract static function ns();

	public abstract static function root();

	public abstract function isRoot();

	public abstract function nodeId($with_ns=true);

	public abstract function addString($str);

	public abstract function crudShowId(OIDplusObject $parent);

	public function crudInsertPrefix() {
		return '';
	}

	public function crudInsertSuffix() {
		return '';
	}

	public abstract function jsTreeNodeName(OIDplusObject $parent = null);

	public abstract function defaultTitle();

	public abstract function isLeafNode();

	public abstract function getContentPage(&$title, &$content, &$icon);

	public static function getRaRoots($ra_email=null) {
		if ($ra_email instanceof OIDplusRA) $ra_email = $ra_email->raEmail();

		$out = array();

		if (!OIDplus::baseConfig()->getValue('OBJECT_CACHING', true)) {
			if (is_null($ra_email)) {
				$res = OIDplus::db()->query("select oChild.id as id, oChild.ra_email as child_mail, oParent.ra_email as parent_mail from ###objects as oChild ".
				                            "left join ###objects as oParent on oChild.parent = oParent.id ".
				                            "order by ".OIDplus::db()->natOrder('oChild.id'));
				while ($row = $res->fetch_array()) {
					if (!OIDplus::authUtils()->isRaLoggedIn($row['parent_mail']) && OIDplus::authUtils()->isRaLoggedIn($row['child_mail'])) {
						$x = self::parse($row['id']); // can be FALSE if namespace was disabled
						if ($x) $out[] = $x;
					}
				}
			} else {
				$res = OIDplus::db()->query("select oChild.id as id from ###objects as oChild ".
				                            "left join ###objects as oParent on oChild.parent = oParent.id ".
				                            "where (".OIDplus::db()->getSlang()->isNullFunction('oParent.ra_email',"''")." <> ? and ".
				                            OIDplus::db()->getSlang()->isNullFunction('oChild.ra_email',"''")." = ?) or ".
				                            "      (oParent.ra_email is null and ".OIDplus::db()->getSlang()->isNullFunction('oChild.ra_email',"''")." = ?) ".
				                            "order by ".OIDplus::db()->natOrder('oChild.id'), array($ra_email, $ra_email, $ra_email));
				while ($row = $res->fetch_array()) {
					$x = self::parse($row['id']); // can be FALSE if namespace was disabled
					if ($x) $out[] = self::parse($row['id']);
				}
			}
		} else {
			if (is_null($ra_email)) {
				$ra_mails_to_check = OIDplus::authUtils()->loggedInRaList();
				if (count($ra_mails_to_check) == 0) return $out;
			} else {
				$ra_mails_to_check = array($ra_email);
			}

			self::buildObjectInformationCache();

			foreach ($ra_mails_to_check as $check_ra_mail) {
				$out_part = array();

				foreach (self::$object_info_cache as $id => $cacheitem) {
					// If the OID RA is the RA we are searching, then add the object to the choice list
					$ra_email = $cacheitem[self::CACHE_RA_EMAIL];
					if ($ra_email == $check_ra_mail) $out_part[] = $id;
				}

				foreach (self::$object_info_cache as $id => $cacheitem) {
					$parent = $cacheitem[self::CACHE_PARENT];
					if (isset(self::$object_info_cache[$parent])) {
						if (self::$object_info_cache[$parent][self::CACHE_RA_EMAIL] == $ra_email) {
							// if the parent has the same RA, then this OID cannot be a root => remove the element from the choice list
							foreach (array_keys($out_part, $id) as $key) unset($out_part[$key]);
						}
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

		if (!OIDplus::baseConfig()->getValue('OBJECT_CACHING', true)) {
			$res = OIDplus::db()->query("select id from ###objects where confidential = ? order by ".OIDplus::db()->natOrder('id'), array(false));

			while ($row = $res->fetch_array()) {
				$obj = self::parse($row['id']); // will be NULL if the object type is not registered
				if ($obj && (!$obj->isConfidential())) {
					$out[] = $row['id'];
				}
			}
		} else {
			self::buildObjectInformationCache();

			foreach (self::$object_info_cache as $id => $cacheitem) {
				$confidential = $cacheitem[self::CACHE_CONFIDENTIAL];
				if (!$confidential) {
					$obj = self::parse($id); // will be NULL if the object type is not registered
					if ($obj && (!$obj->isConfidential())) {
						$out[] = $id;
					}
				}
			}
		}

		return $out;
	}

	public function isConfidential() {
		if (!OIDplus::baseConfig()->getValue('OBJECT_CACHING', true)) {
			//static $confidential_cache = array();
			$curid = $this->nodeId();
			//$orig_curid = $curid;
			//if (isset($confidential_cache[$curid])) return $confidential_cache[$curid];
			// Recursively search for the confidential flag in the parents
			while (($res = OIDplus::db()->query("select parent, confidential from ###objects where id = ?", array($curid)))->any()) {
				$row = $res->fetch_array();
				if ($row['confidential']) {
					//$confidential_cache[$curid] = true;
					//$confidential_cache[$orig_curid] = true;
					return true;
				} else {
					//$confidential_cache[$curid] = false;
				}
				$curid = $row['parent'];
				//if (isset($confidential_cache[$curid])) {
					//$confidential_cache[$orig_curid] = $confidential_cache[$curid];
					//return $confidential_cache[$curid];
				//}
			}

			//$confidential_cache[$orig_curid] = false;
			return false;
		} else {
			self::buildObjectInformationCache();

			$curid = $this->nodeId();
			// Recursively search for the confidential flag in the parents
			while (isset(self::$object_info_cache[$curid])) {
				if (self::$object_info_cache[$curid][self::CACHE_CONFIDENTIAL]) return true;
				$curid = self::$object_info_cache[$curid][self::CACHE_PARENT];
			}
			return false;
		}
	}

	public function isChildOf(OIDplusObject $obj) {
		if (!OIDplus::baseConfig()->getValue('OBJECT_CACHING', true)) {
			$curid = $this->nodeId();
			while (($res = OIDplus::db()->query("select parent from ###objects where id = ?", array($curid)))->any()) {
				$row = $res->fetch_array();
				if ($curid == $obj->nodeId()) return true;
				$curid = $row['parent'];
			}
			return false;
		} else {
			self::buildObjectInformationCache();

			$curid = $this->nodeId();
			while (isset(self::$object_info_cache[$curid])) {
				if ($curid == $obj->nodeId()) return true;
				$curid = self::$object_info_cache[$curid][self::CACHE_PARENT];
			}
			return false;
		}
	}

	public function getChildren() {
		$out = array();
		if (!OIDplus::baseConfig()->getValue('OBJECT_CACHING', true)) {
			$res = OIDplus::db()->query("select id from ###objects where parent = ?", array($this->nodeId()));
			while ($row = $res->fetch_array()) {
				$obj = self::parse($row['id']);
				if (!$obj) continue;
				$out[] = $obj;
			}
		} else {
			self::buildObjectInformationCache();

			foreach (self::$object_info_cache as $id => $cacheitem) {
				$parent = $cacheitem[self::CACHE_PARENT];
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

		// If it is not confidential, everybody can read/see it.
		// Note: This also checks if superior OIDs are confidential.
		if (!$this->isConfidential()) return true;

		if (is_null($ra_email)) {
			// Admin may do everything
			if (OIDplus::authUtils()->isAdminLoggedIn()) return true;

			// If the RA is logged in, then they can see the OID.
			if (OIDplus::authUtils()->isRaLoggedIn($this->getRaMail())) return true;
		} else {
			// If this OID belongs to the requested RA, then they may see it.
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

		$dirs = glob(OIDplus::localpath().'plugins/'.'*'.'/objectTypes/'.$namespace.'/');

		if (count($dirs) == 0) return null; // default icon (folder)

		$dir = substr($dirs[0], strlen(OIDplus::localpath()));

		// We use $this:: instead of self:: , because we want to call the overridden methods
		if (OIDplus::authUtils()->isRaLoggedIn($ra_email)) {
			$icon = $dir.'/'.$this::treeIconFilename('own');
		} else {
			$icon = $dir.'/'.$this::treeIconFilename('general');
		}

		if (!file_exists($icon)) return null; // default icon (folder)

		return $icon;
	}

	public static function exists($id) {
		if (!OIDplus::baseConfig()->getValue('OBJECT_CACHING', true)) {
			$res = OIDplus::db()->query("select id from ###objects where id = ?", array($id));
			return $res->any();
		} else {
			self::buildObjectInformationCache();
			return isset(self::$object_info_cache[$id]);
		}
	}

	// Get parent gives the next possible parent which is EXISTING in OIDplus
	// It does not give the immediate parent
	public function getParent() {
		if (!OIDplus::baseConfig()->getValue('OBJECT_CACHING', true)) {
			$res = OIDplus::db()->query("select parent from ###objects where id = ?", array($this->nodeId()));
			if (!$res->any()) return null;
			$row = $res->fetch_array();
			$parent = $row['parent'];
			$obj = OIDplusObject::parse($parent);
			if ($obj) return $obj;
			// TODO: Also implement one_up() like below
		} else {
			self::buildObjectInformationCache();
			if (isset(self::$object_info_cache[$this->nodeId()])) {
				$parent = self::$object_info_cache[$this->nodeId()][self::CACHE_PARENT];
				$obj = OIDplusObject::parse($parent);
				if ($obj) return $obj;
			}

			// If this OID does not exist, the SQL query "select parent from ..." does not work. So we try to find the next possible parent using one_up()
			$cur = $this->one_up();
			if (!$cur) return null;
			do {
				// findFitting() checks if that OID exists
				if ($fitting = self::findFitting($cur->nodeId())) return $fitting;

				$prev = $cur;
				$cur = $cur->one_up();
				if (!$cur) return null;
			} while ($prev != $cur);

			return null;
		}
	}

	public function getRaMail() {
		if (!OIDplus::baseConfig()->getValue('OBJECT_CACHING', true)) {
			$res = OIDplus::db()->query("select ra_email from ###objects where id = ?", array($this->nodeId()));
			if (!$res->any()) return null;
			$row = $res->fetch_array();
			return $row['ra_email'];
		} else {
			self::buildObjectInformationCache();
			if (isset(self::$object_info_cache[$this->nodeId()])) {
				return self::$object_info_cache[$this->nodeId()][self::CACHE_RA_EMAIL];
			}
			return false;
		}
	}

	public function getTitle() {
		if (!OIDplus::baseConfig()->getValue('OBJECT_CACHING', true)) {
			$res = OIDplus::db()->query("select title from ###objects where id = ?", array($this->nodeId()));
			if (!$res->any()) return null;
			$row = $res->fetch_array();
			return $row['title'];
		} else {
			self::buildObjectInformationCache();
			if (isset(self::$object_info_cache[$this->nodeId()])) {
				return self::$object_info_cache[$this->nodeId()][self::CACHE_TITLE];
			}
			return false;
		}
	}

	public function getDescription() {
		if (!OIDplus::baseConfig()->getValue('OBJECT_CACHING', true)) {
			$res = OIDplus::db()->query("select description from ###objects where id = ?", array($this->nodeId()));
			if (!$res->any()) return null;
			$row = $res->fetch_array();
			return $row['description'];
		} else {
			self::buildObjectInformationCache();
			if (isset(self::$object_info_cache[$this->nodeId()])) {
				return self::$object_info_cache[$this->nodeId()][self::CACHE_DESCRIPTION];
			}
			return false;
		}
	}

	public function getComment() {
		if (!OIDplus::baseConfig()->getValue('OBJECT_CACHING', true)) {
			$res = OIDplus::db()->query("select comment from ###objects where id = ?", array($this->nodeId()));
			if (!$res->any()) return null;
			$row = $res->fetch_array();
			return $row['comment'];
		} else {
			self::buildObjectInformationCache();
			if (isset(self::$object_info_cache[$this->nodeId()])) {
				return self::$object_info_cache[$this->nodeId()][self::CACHE_COMMENT];
			}
			return false;
		}
	}

	public function getCreatedTime() {
		if (!OIDplus::baseConfig()->getValue('OBJECT_CACHING', true)) {
			$res = OIDplus::db()->query("select created from ###objects where id = ?", array($this->nodeId()));
			if (!$res->any()) return null;
			$row = $res->fetch_array();
			return $row['created'];
		} else {
			self::buildObjectInformationCache();
			if (isset(self::$object_info_cache[$this->nodeId()])) {
				return self::$object_info_cache[$this->nodeId()][self::CACHE_CREATED];
			}
			return false;
		}
	}

	public function getUpdatedTime() {
		if (!OIDplus::baseConfig()->getValue('OBJECT_CACHING', true)) {
			$res = OIDplus::db()->query("select updated from ###objects where id = ?", array($this->nodeId()));
			if (!$res->any()) return null;
			$row = $res->fetch_array();
			return $row['updated'];
		} else {
			self::buildObjectInformationCache();
			if (isset(self::$object_info_cache[$this->nodeId()])) {
				return self::$object_info_cache[$this->nodeId()][self::CACHE_UPDATED];
			}
			return false;
		}
	}

	public function userHasParentalWriteRights($ra_email=null) {
		if ($ra_email instanceof OIDplusRA) $ra_email = $ra_email->raEmail();

		if (is_null($ra_email)) {
			if (OIDplus::authUtils()->isAdminLoggedIn()) return true;
		}

		$objParent = $this->getParent();
		if (!$objParent) return false;
		return $objParent->userHasWriteRights($ra_email);
	}

	public function userHasWriteRights($ra_email=null) {
		if ($ra_email instanceof OIDplusRA) $ra_email = $ra_email->raEmail();

		if (is_null($ra_email)) {
			if (OIDplus::authUtils()->isAdminLoggedIn()) return true;
			return OIDplus::authUtils()->isRaLoggedIn($this->getRaMail());
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
		if (!$obj) return false; // e.g. if ObjectType plugin is disabled

		if (!OIDplus::baseConfig()->getValue('OBJECT_CACHING', true)) {
			$res = OIDplus::db()->query("select id from ###objects where id like ?", array($obj->ns().':%'));
			while ($row = $res->fetch_object()) {
				$test = OIDplusObject::parse($row->id);
				if ($obj->equals($test)) return $test;
			}
			return false;
		} else {
			self::buildObjectInformationCache();
			foreach (self::$object_info_cache as $id => $cacheitem) {
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

	const CACHE_ID = 'id';
	const CACHE_PARENT = 'parent';
	const CACHE_TITLE = 'title';
	const CACHE_DESCRIPTION = 'description';
	const CACHE_RA_EMAIL = 'ra_email';
	const CACHE_CONFIDENTIAL = 'confidential';
	const CACHE_CREATED = 'created';
	const CACHE_UPDATED = 'updated';
	const CACHE_COMMENT = 'comment';

	private static function buildObjectInformationCache() {
		if (is_null(self::$object_info_cache)) {
			self::$object_info_cache = array();
			$res = OIDplus::db()->query("select * from ###objects");
			while ($row = $res->fetch_array()) {
				self::$object_info_cache[$row['id']] = $row;
			}
		}
	}

	// override this function if you want your object type to save
	// attachments in directories with easy names.
	// Take care that your custom directory name will not allow jailbreaks (../) !
	public function getDirectoryName() {
		if ($this->isRoot()) return $this->ns();
		return $this->getLegacyDirectoryName();
	}

	public final function getLegacyDirectoryName() {
		if ($this::ns() == 'oid') {
			$oid = $this->nodeId(false);
		} else {
			$oid = null;
			$alt_ids = $this->getAltIds();
			foreach ($alt_ids as $alt_id) {
				if ($alt_id->getNamespace() == 'oid') {
					$oid = $alt_id->getId();
					break; // we prefer the first OID (for GUIDs, the first OID is the OIDplus-OID, and the second OID is the UUID OID)
				}
			}
		}

		if (!is_null($oid) && ($oid != '')) {
			// For OIDs, it is the OID, for other identifiers
			// it it the OID alt ID (generated using the SystemID)
			return str_replace('.', '_', $oid);
		} else {
			// Can happen if you don't have a system ID (due to missing OpenSSL plugin)
			return md5($this->nodeId(true)); // we don't use $id, because $this->nodeId(true) is possibly more canonical than $id
		}
	}

	public static function treeIconFilename($mode) {
		// for backwards-compatibility with older plugins
		return 'img/treeicon_'.$mode.'.png';
	}

}
