<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2023 Daniel Marschall, ViaThinkSoft
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

namespace ViaThinkSoft\OIDplus\Core;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

abstract class OIDplusObject extends OIDplusBaseClass {

	/**
	 *
	 */
	//const UUID_NAMEBASED_NS_OidPlusMisc = 'ad1654e6-7e15-11e4-9ef6-78e3b5fc7f22';

	/**
	 * Please overwrite this function!
	 * @param string $node_id
	 * @return OIDplusObject|null
	 */
	public static function parse(string $node_id): ?OIDplusObject {
		foreach (OIDplus::getEnabledObjectTypes() as $ot) {
			try {
				$good = false;
				if (get_parent_class($ot) == OIDplusObject::class) {
					$reflector = new \ReflectionMethod($ot, 'parse');
					$isImplemented = ($reflector->getDeclaringClass()->getName() === $ot);
					if ($isImplemented) { // avoid endless loop if parse is not overriden
						$good = true;
					}
				}
				// We need to do the workaround with "$good", otherwise PHPstan shows
				// "Call to an undefined static method object::parse()"
				if ($good && $obj = $ot::parse($node_id)) return $obj;
			} catch (\Exception $e) {}
		}
		return null;
	}

	/**
	 * @return OIDplusAltId[]
	 * @throws OIDplusException
	 */
	public function getAltIds(): array {
		if ($this->isRoot()) return array();

		$ids = array();

		// Create Information Object OID/AID/UUID
		// ... but not for OIDs below oid:1.3.6.1.4.1.37476.30.9, because these are the definition of these Information Object AID/OID/UUID (which will be decoded in the OID object type plugin)
		if (!str_starts_with($this->nodeId(true), 'oid:1.3.6.1.4.1.37476.30.9.')) {
			// Creates an OIDplus-Hash-OID
			// ... exclude OIDs, because an OID is already an OID
			if ($this->ns() != 'oid') {
				$sid = OIDplus::getSystemId(true);
				if (!empty($sid)) {
					$ns_oid = $this->getPlugin()->getManifest()->getOid();
					$hash_payload = $ns_oid.':'.$this->nodeId(false);
					$oid = $sid . '.' . smallhash($hash_payload);
					$ids[] = new OIDplusAltId('oid', $oid, _L('OIDplus Information Object OID'));
				}
			}

			// Make a OIDplus-UUID, but...
			// ... exclude GUID, because a GUID is already a GUID
			// ... exclude OIDs which are 2.25, because these are basically GUIDs (but 2nd, 3rd, 4th, ... level is OK)
			// Previously, we excluded OID, because an OID already has a record UUID_NAMEBASED_NS_OID (defined by IETF) set by class OIDplusOid
			if (($this->ns() != 'guid') && ($this->ns() != 'oid' || $this->one_up()->nodeId(true) != 'oid:2.25') /*&& ($this->ns() != 'oid')*/) {
				// Obsolete custom namespace for UUIDv3 and UUIDv5:
				//$ids[] = new OIDplusAltId('guid', gen_uuid_md5_namebased(self::UUID_NAMEBASED_NS_OidPlusMisc, $this->nodeId()), _L('Name based version 3 / MD5 UUID with namespace %1','UUID_NAMEBASED_NS_OidPlusMisc'));
				//$ids[] = new OIDplusAltId('guid', gen_uuid_sha1_namebased(self::UUID_NAMEBASED_NS_OidPlusMisc, $this->nodeId()), _L('Name based version 5 / SHA1 UUID with namespace %1','UUID_NAMEBASED_NS_OidPlusMisc'));
				// New custom UUIDv8:
				$sysid = OIDplus::getSystemId(false);
				$sysid_int = $sysid ? $sysid : 0;
				$unix_ts = $this->getCreatedTime() ? strtotime($this->getCreatedTime()) : 0;
				$ns_oid = $this->getPlugin()->getManifest()->getOid();
				$obj_name = $this->nodeId(false);
				$ids[] = new OIDplusAltId('guid',
					gen_uuid_v8(
						dechex($sysid_int),
						dechex((int)round($unix_ts/60/60/24)),
						dechex(0),
						sha1($ns_oid), // Note: No 14bit collission between 1.3.6.1.4.1.37476.2.5.2.4.8.[0-185]
						sha1($obj_name)
					),
					_L('OIDplus Information Object Custom UUID (RFC 9562)'),
					'',
					'https://github.com/danielmarschall/oidplus/blob/master/doc/oidplus_custom_guid.md'
					);
			}

			// Make a AID based on ViaThinkSoft schema
			// ... exclude AIDs, because an AID is already an AID
			if ($this->ns() != 'aid') {
				$sid = OIDplus::getSystemId(false);
				if ($sid !== false) {
					$ns_oid = $this->getPlugin()->getManifest()->getOid();
					$hash_payload = $ns_oid.':'.$this->nodeId(false);
					$sid_hex = strtoupper(str_pad(dechex((int)$sid),8,'0',STR_PAD_LEFT));
					$obj_hex = strtoupper(str_pad(dechex(smallhash($hash_payload)),8,'0',STR_PAD_LEFT));
					$aid = 'D276000186B20005'.$sid_hex.$obj_hex;
					$ids[] = new OIDplusAltId('aid', $aid,
						_L('OIDplus Information Object Application Identifier (ISO/IEC 7816)'),
						' ('._L('No PIX allowed').')',
						'https://hosted.oidplus.com/viathinksoft/?goto=aid%3AD276000186B20005');
				}
			}

			// Make a MAC based on AAI (not 100% worldwide unique!)
			// ... exclude MACs, because an MAC is already a MAC
			if ($this->ns() != 'mac') {
				$ns_oid = $this->getPlugin()->getManifest()->getOid();
				$obj_name = $this->nodeId(false);
				$mac = strtoupper(substr(sha1($ns_oid.':'.$obj_name),-12));
				$mac = rtrim(chunk_split($mac, 2, '-'),'-');

				$mac[1] = '2'; // 2=AAI Unicast
				$ids[] = new OIDplusAltId('mac', $mac, _L('OIDplus Information Object MAC address, Unicast (AAI)'));

				$mac[1] = '3'; // 3=AAI Multicast
				$ids[] = new OIDplusAltId('mac', $mac, _L('OIDplus Information Object MAC address, Multicast (AAI)'));
			}

			// Make a DN based on DN
			// ... exclude DN, because an DN is already a DN
			if ($this->ns() != 'x500dn') {
				$sysid = OIDplus::getSystemId(false);
				if ($sysid !== false) {
					$ns_oid = $this->getPlugin()->getManifest()->getOid();
					$hash_payload = $ns_oid.':'.$this->nodeId(false);
					$objhash = smallhash($hash_payload);

					$oid_at_sysid = '1.3.6.1.4.1.37476.2.5.2.9.4.1';
					$oid_at_objhash = '1.3.6.1.4.1.37476.2.5.2.9.4.2';
					$dn = '/dc=com/dc=example/cn=oidplus/'.$oid_at_sysid.'='.$sysid.'/'.$oid_at_objhash.'='.$objhash;

					$ids[] = new OIDplusAltId('x500dn', $dn, _L('OIDplus Information Object X.500 DN'));
				}
			}
		}

		return $ids;
	}

	/**
	 * @return string
	 */
	public abstract static function objectTypeTitle(): string;

	/**
	 * @return string
	 */
	public abstract static function objectTypeTitleShort(): string;

	/**
	 * @return OIDplusObjectTypePlugin|null
	 */
	public function getPlugin(): ?OIDplusObjectTypePlugin {
		$plugins = OIDplus::getObjectTypePlugins();
		foreach ($plugins as $plugin) {
			if (get_class($this) == $plugin::getObjectTypeClassName()) {
				return $plugin;
			}
		}
		return null;
	}

	/**
	 * @return string
	 */
	public abstract static function ns(): string;

	/**
	 * If the plugin has a commonly known URN namespace, then return it, e.g. "uuid" which means "urn:uuid:"
	 * @return array
	 */
	public static function urnNs(): array {
		return [];
	}

	/**
	 * @return string
	 */
	public abstract static function root(): string;

	/**
	 * @return bool
	 */
	public abstract function isRoot(): bool;

	/**
	 * @param bool $with_ns
	 * @return string
	 */
	public abstract function nodeId(bool $with_ns=true): string;

	/**
	 * @param string $str
	 * @return string
	 * @throws OIDplusException
	 */
	public abstract function addString(string $str): string;

	/**
	 * @param OIDplusObject $parent
	 * @return string
	 */
	public abstract function crudShowId(OIDplusObject $parent): string;

	/**
	 * @return string
	 */
	public function crudInsertPrefix(): string {
		return '';
	}

	/**
	 * @return string
	 */
	public function crudInsertSuffix(): string {
		return '';
	}

	/**
	 * @param OIDplusObject|null $parent
	 * @return string
	 */
	public abstract function jsTreeNodeName(?OIDplusObject $parent=null): string;

	/**
	 * @return string
	 */
	public abstract function defaultTitle(): string;

	/**
	 * @return bool
	 */
	public abstract function isLeafNode(): bool;

	/**
	 * @param string $title
	 * @param string $content
	 * @param string $icon
	 * @return void
	 */
	public abstract function getContentPage(string &$title, string &$content, string &$icon);

	/**
	 * @param OIDplusRA|string|null $ra
	 * @return array
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	public static function getRaRoots(/*OIDplusRA|string|null*/ $ra=null) : array {
		if ($ra instanceof OIDplusRA) $ra = $ra->raEmail();

		$out = array();

		if (!OIDplus::baseConfig()->getValue('OBJECT_CACHING', true)) {
			if (!$ra) {
				$res = OIDplus::db()->query("select oChild.id as child_id, oChild.ra_email as child_mail, oParent.ra_email as parent_mail from ###objects as oChild ".
				                            "left join ###objects as oParent on oChild.parent = oParent.id");
				$res->naturalSortByField('child_id');
				while ($row = $res->fetch_array()) {
					if (!OIDplus::authUtils()->isRaLoggedIn($row['parent_mail']) && OIDplus::authUtils()->isRaLoggedIn($row['child_mail'])) {
						$x = self::parse($row['child_id']); // can be NULL if namespace was disabled
						if ($x) $out[] = $x;
					}
				}
			} else {
				$res = OIDplus::db()->query("select oChild.id as child_id from ###objects as oChild ".
				                            "left join ###objects as oParent on oChild.parent = oParent.id ".
				                            "where (".OIDplus::db()->getSlang()->isNullFunction('oParent.ra_email',"''")." <> ? and ".
				                            OIDplus::db()->getSlang()->isNullFunction('oChild.ra_email',"''")." = ?) or ".
				                            "      (oParent.ra_email is null and ".OIDplus::db()->getSlang()->isNullFunction('oChild.ra_email',"''")." = ?) ",
				                            array($ra, $ra, $ra));
				$res->naturalSortByField('child_id');
				while ($row = $res->fetch_array()) {
					$x = self::parse($row['child_id']); // can be NULL if namespace was disabled
					if ($x) $out[] = $x;
				}
			}
		} else {
			if (!$ra) {
				$ra_mails_to_check = OIDplus::authUtils()->loggedInRaList();
				if (count($ra_mails_to_check) == 0) return $out;
			} else {
				$ra_mails_to_check = array($ra);
			}

			self::buildObjectInformationCache();

			foreach ($ra_mails_to_check as $check_ra_mail) {
				$out_part = array();

				foreach (self::$object_info_cache as $id => $cacheitem) {
					if ($cacheitem[self::CACHE_RA_EMAIL] == $check_ra_mail) {
						$parent = $cacheitem[self::CACHE_PARENT];
						if (!isset(self::$object_info_cache[$parent]) || (self::$object_info_cache[$parent][self::CACHE_RA_EMAIL] != $check_ra_mail)) {
							$out_part[] = $id;
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

	/**
	 * @return array
	 * @throws OIDplusException
	 */
	public static function getAllNonConfidential(): array {
		$out = array();

		if (!OIDplus::baseConfig()->getValue('OBJECT_CACHING', true)) {
			$res = OIDplus::db()->query("select id from ###objects where confidential = ?", array(false));
			$res->naturalSortByField('id');
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

	/**
	 * @return bool
	 * @throws OIDplusException
	 */
	public function isConfidential(): bool {
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

	/**
	 * @param OIDplusObject $obj
	 * @return bool
	 * @throws OIDplusException
	 */
	public function isChildOf(OIDplusObject $obj): bool {
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

	/**
	 * @return array
	 * @throws OIDplusException
	 */
	public function getChildren(): array {
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

	/**
	 * @return OIDplusRA|null
	 * @throws OIDplusException
	 */
	public function getRa(): ?OIDplusRA {
		$ra = $this->getRaMail();
		return $ra ? new OIDplusRA($ra) : null;
	}

	/**
	 * @param OIDplusRA|string|null $ra
	 * @return bool
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	public function userHasReadRights(/*OIDplusRA|string|null*/ $ra=null): bool {
		if ($ra instanceof OIDplusRA) $ra = $ra->raEmail();

		// If it is not confidential, everybody can read/see it.
		// Note: This also checks if superior OIDs are confidential.
		if (!$this->isConfidential()) return true;

		if (!$ra) {
			// Admin may do everything
			if (OIDplus::authUtils()->isAdminLoggedIn()) return true;

			// If the RA is logged in, then they can see the OID.
			$ownRa = $this->getRaMail();
			if ($ownRa && OIDplus::authUtils()->isRaLoggedIn($ownRa)) return true;
		} else {
			// If this OID belongs to the requested RA, then they may see it.
			if ($this->getRaMail() == $ra) return true;
		}

		// If someone has rights to an object below our confidential node,
		// we let him see the confidential node,
		// Otherwise he could not browse through to his own node.
		$roots = $this->getRaRoots($ra);
		foreach ($roots as $root) {
			if ($root->isChildOf($this)) return true;
		}

		return false;
	}

	/**
	 * @param array|null $row
	 * @return string|null
	 * @throws OIDplusException
	 */
	public function getIcon(?array $row=null): ?string {
		$namespace = $this->ns(); // must use $this, not self::, otherwise the virtual method will not be called

		if (is_null($row)) {
			$ra_email = $this->getRaMail();
		} else {
			$ra_email = $row['ra_email'];
		}

		// $dirs = glob(OIDplus::localpath().'plugins/'.'*'.'/objectTypes/'.$namespace.'/');
		// if (count($dirs) == 0) return null; // default icon (folder)
		// $dir = substr($dirs[0], strlen(OIDplus::localpath()));
		$reflection = new \ReflectionClass($this);
		$dir = dirname($reflection->getFilename());
		$dir = substr($dir, strlen(OIDplus::localpath()));
		$dir = str_replace('\\', '/', $dir);

		if ($this->isRoot()) {
			$icon = $dir . '/' . $this::treeIconFilename('root');
		} else {
			// We use $this:: instead of self:: , because we want to call the overridden methods
			if ($ra_email && OIDplus::authUtils()->isRaLoggedIn($ra_email)) {
				if ($this->isLeafNode()) {
					$icon = $dir . '/' . $this::treeIconFilename('own_leaf');
					if (!file_exists($icon)) $icon = $dir . '/' . $this::treeIconFilename('own');
				} else {
					$icon = $dir . '/' . $this::treeIconFilename('own');
				}
			} else {
				if ($this->isLeafNode()) {
					$icon = $dir . '/' . $this::treeIconFilename('general_leaf');
					if (!file_exists($icon)) $icon = $dir . '/' . $this::treeIconFilename('general');
				} else {
					$icon = $dir . '/' . $this::treeIconFilename('general');
				}
			}
		}

		if (!file_exists($icon)) return null; // default icon (folder)

		return $icon;
	}

	/**
	 * @param string $id
	 * @return bool
	 * @throws OIDplusException
	 */
	public static function exists(string $id): bool {
		if (!OIDplus::baseConfig()->getValue('OBJECT_CACHING', true)) {
			$res = OIDplus::db()->query("select id from ###objects where id = ?", array($id));
			return $res->any();
		} else {
			self::buildObjectInformationCache();
			return isset(self::$object_info_cache[$id]);
		}
	}

	/**
	 * Get parent gives the next possible parent which is EXISTING in OIDplus
	 * It does not give the immediate parent
	 * @return OIDplusObject|null
	 * @throws OIDplusException
	 */
	public function getParent(): ?OIDplusObject {
		if (!OIDplus::baseConfig()->getValue('OBJECT_CACHING', true)) {
			$res = OIDplus::db()->query("select parent from ###objects where id = ?", array($this->nodeId()));
			if ($res->any()) {
				$row = $res->fetch_array();
				$parent = $row['parent'];
				$obj = OIDplusObject::parse($parent);
				if ($obj) return $obj;
			}
		} else {
			self::buildObjectInformationCache();
			if (isset(self::$object_info_cache[$this->nodeId()])) {
				$parent = self::$object_info_cache[$this->nodeId()][self::CACHE_PARENT];
				$obj = OIDplusObject::parse($parent);
				if ($obj) return $obj;
			}
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
		} while ($prev->nodeId() !== $cur->nodeId());

		return null;
	}

	/**
	 * @return string|null
	 * @throws OIDplusException
	 */
	public function getRaMail(): ?string {
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
			return null;
		}
	}

	/**
	 * @return string|null
	 * @throws OIDplusException
	 */
	public function getTitle(): ?string {
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
			return null;
		}
	}

	/**
	 * @return string|null
	 * @throws OIDplusException
	 */
	public function getDescription(): ?string {
		if (!OIDplus::baseConfig()->getValue('OBJECT_CACHING', true)) {
			// Also included a non-ntext field in the query, see https://bugs.php.net/bug.php?id=72503
			$res = OIDplus::db()->query("select id, description from ###objects where id = ?", array($this->nodeId()));
			if (!$res->any()) return null;
			$row = $res->fetch_array();
			return $row['description'];
		} else {
			self::buildObjectInformationCache();
			if (isset(self::$object_info_cache[$this->nodeId()])) {
				return self::$object_info_cache[$this->nodeId()][self::CACHE_DESCRIPTION];
			}
			return null;
		}
	}

	/**
	 * @return string|null
	 * @throws OIDplusException
	 */
	public function getComment(): ?string {
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
			return null;
		}
	}

	/**
	 * @return string|null
	 * @throws OIDplusException
	 */
	public function getCreatedTime(): ?string {
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
			return null;
		}
	}

	/**
	 * @return string|null
	 * @throws OIDplusException
	 */
	public function getUpdatedTime(): ?string {
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
			return null;
		}
	}

	/**
	 * @param OIDplusRA|string|null $ra
	 * @return bool
	 * @throws OIDplusException
	 */
	public function userHasParentalWriteRights(/*OIDplusRA|string|null*/ $ra=null): bool {
		if ($ra instanceof OIDplusRA) $ra = $ra->raEmail();

		if (!$ra) {
			if (OIDplus::authUtils()->isAdminLoggedIn()) return true;
		}

		$objParent = $this->getParent();
		if (!$objParent) return false;
		return $objParent->userHasWriteRights($ra);
	}

	/**
	 * @param OIDplusRA|string|null $ra
	 * @return bool
	 * @throws OIDplusException
	 */
	public function userHasWriteRights(/*OIDplusRA|string|null*/ $ra=null): bool {
		if ($ra instanceof OIDplusRA) $ra = $ra->raEmail();

		if (!$ra) {
			if (OIDplus::authUtils()->isAdminLoggedIn()) return true;
			// TODO: should we allow that the parent RA also may update title/description about this OID (since they delegated it?)
			$ownRa = $this->getRaMail();
			return $ownRa && OIDplus::authUtils()->isRaLoggedIn($ownRa);
		} else {
			return $this->getRaMail() == $ra;
		}
	}

	/**
	 * @param string|OIDplusObject $to
	 * @return int|null
	 */
	public function distance(/*string|OIDplusObject*/ $to): ?int {
		return null; // not implemented
	}

	/**
	 * @param OIDplusObject|string $obj
	 * @return bool
	 */
	public function equals(/*string|OIDplusObject*/ $obj): bool {
		if (!$obj) return false;
		if (!is_object($obj)) {
			if ($this->nodeId(true) === $obj) return true; // simplest case
			$obj = OIDplusObject::parse($obj);
			if (!$obj) return false;
		} else {
			if ($this->nodeId(true) === $obj->nodeId(true)) return true; // simplest case
		}
		if (!($obj instanceof $this)) return false;

		$distance = $this->distance($obj);
		if (is_numeric($distance)) return $distance === 0; // if the distance function is implemented, use it

		return $this->nodeId() == $obj->nodeId(); // otherwise compare the node id case-sensitive
	}

	/**
	 * @param string $search_id
	 * @return OIDplusObject|false
	 * @throws OIDplusException
	 */
	public static function findFitting(string $search_id)/*: OIDplusObject|false*/ {
		$obj = OIDplusObject::parse($search_id);
		if (!$obj) return false; // e.g. if ObjectType plugin is disabled

		if ($obj->nodeId(false) == '') return false; // speed optimization. "oid:" is not equal to any object in the database

		if (!OIDplus::baseConfig()->getValue('OBJECT_CACHING', true)) {
			$res = OIDplus::db()->query("select id from ###objects where id like ?", array($obj->ns().':%'));
			while ($row = $res->fetch_object()) {
				$test = OIDplusObject::parse($row->id);
				if ($test && $obj->equals($test)) return $test;
			}
			return false;
		} else {
			self::buildObjectInformationCache();
			foreach (self::$object_info_cache as $id => $cacheitem) {
				if (strpos($id, $obj->ns().':') === 0) {
					$test = OIDplusObject::parse($id);
					if ($test && $obj->equals($test)) return $test;
				}
			}
			return false;
		}
	}

	/**
	 * @return OIDplusObject|null
	 */
	public function one_up(): ?OIDplusObject {
		return null; // not implemented
	}

	// Caching stuff

	/**
	 * @var ?array
	 */
	protected static ?array $object_info_cache = null;

	/**
	 * @return void
	 */
	public static function resetObjectInformationCache(): void {
		self::$object_info_cache = null;
	}

	public const CACHE_ID = 'id';
	public const CACHE_PARENT = 'parent';
	public const CACHE_TITLE = 'title';
	public const CACHE_DESCRIPTION = 'description';
	public const CACHE_RA_EMAIL = 'ra_email';
	public const CACHE_CONFIDENTIAL = 'confidential';
	public const CACHE_CREATED = 'created';
	public const CACHE_UPDATED = 'updated';
	public const CACHE_COMMENT = 'comment';

	/**
	 * @return void
	 * @throws OIDplusException
	 */
	private static function buildObjectInformationCache(): void {
		if (is_null(self::$object_info_cache)) {
			self::$object_info_cache = array();
			$res = OIDplus::db()->query("select * from ###objects");
			while ($row = $res->fetch_array()) {
				self::$object_info_cache[$row['id']] = $row;
			}
		}
	}

	/**
	 * override this function if you want your object type to save
	 * attachments in directories with easy names.
	 * Take care that your custom directory name will not allow jailbreaks (../) !
	 * @return string
	 * @throws OIDplusException
	 */
	public function getDirectoryName(): string {
		if ($this->isRoot()) return $this->ns();
		return $this->getLegacyDirectoryName();
	}

	/**
	 * @return string
	 * @throws OIDplusException
	 */
	public final function getLegacyDirectoryName(): string {
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

	/**
	 * @param string $mode
	 * @return string
	 */
	public static function treeIconFilename(string $mode): string {
		// for backwards-compatibility with older plugins
		return 'img/treeicon_'.$mode.'.png';
	}

}
