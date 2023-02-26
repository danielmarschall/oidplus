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

namespace ViaThinkSoft\OIDplus;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusGuid extends OIDplusObject {
	private $guid;

	public function __construct($guid) {
		if (uuid_valid($guid)) {
			$this->guid = strtolower(uuid_canonize($guid)); // It is a real GUID (leaf node)
		} else {
			$this->guid = $guid; // It is a category name
		}
	}

	public static function parse($node_id) {
		@list($namespace, $guid) = explode(':', $node_id, 2);
		if ($namespace !== self::ns()) return false;
		return new self($guid);
	}

	public static function objectTypeTitle() {
		return _L('Globally Unique Identifier (GUID)');
	}

	public static function objectTypeTitleShort() {
		return _L('GUID');
	}

	public static function ns() {
		return 'guid';
	}

	public static function root() {
		return self::ns().':';
	}

	public function isRoot() {
		return $this->guid == '';
	}

	public function nodeId($with_ns=true) {
		return $with_ns ? self::root().$this->guid : $this->guid;
	}

	public function addString($str) {
		if (uuid_valid($str)) {
			// real GUID
			return self::root() . strtolower(uuid_canonize($str));
		} else {
			// just a category
			if ($this->isRoot()) {
				return self::root() . $str;
			} else {
				return $this->nodeId() . '/' . $str;
			}
		}
	}

	public function crudShowId(OIDplusObject $parent) {
		if ($this->isLeafNode()) {
			// We don't parse '/' in a valid FourCC code (i.e. Leaf node)
			return $this->nodeId(false);
		} else {
			if ($parent->isRoot()) {
				return substr($this->nodeId(), strlen($parent->nodeId()));
			} else {
				return substr($this->nodeId(), strlen($parent->nodeId())+1);
			}
		}
	}

	public function jsTreeNodeName(OIDplusObject $parent = null) {
		if ($parent == null) return $this->objectTypeTitle();
		return $this->crudShowId($parent);
	}

	public function defaultTitle() {
		return $this->guid;
	}

	public function isLeafNode() {
		return uuid_valid($this->guid);
	}

	private function getTechInfo() {
		$tech_info = array();
		$tech_info[_L('UUID')] = strtolower(uuid_canonize($this->guid));
		$tech_info[_L('C++ notation')] = uuid_c_syntax($this->guid);

		ob_start();
		uuid_info($this->guid);
		$info = ob_get_contents();
		preg_match_all('@([^:]+):\s*(.+)\n@ismU', $info, $m, PREG_SET_ORDER);
		foreach ($m as $m1) {
			$key = $m1[1];
			$value = $m1[2];
			$tech_info[$key] = $value;
		}
		ob_end_clean();

		return $tech_info;
	}

	public function getContentPage(&$title, &$content, &$icon) {
		$icon = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

		if ($this->isRoot()) {
			$title = OIDplusGuid::objectTypeTitle();

			$res = OIDplus::db()->query("select * from ###objects where parent = ?", array(self::root()));
			if ($res->any()) {
				$content  = '<p>'._L('Please select a GUID in the tree view at the left to show its contents.').'</p>';
			} else {
				$content  = '<p>'._L('Currently, no GUID is registered in the system.').'</p>';
			}

			if (!$this->isLeafNode()) {
				if (OIDplus::authUtils()->isAdminLoggedIn()) {
					$content .= '<h2>'._L('Manage root objects / categories').'</h2>';
				} else {
					$content .= '<h2>'._L('Available objects / categories').'</h2>';
				}
				$content .= '%%CRUD%%';
			}
		} else {
			$title = $this->getTitle();

			if ($this->isLeafNode()) {
				$tech_info = $this->getTechInfo();
				$tech_info_html = '';
				if (count($tech_info) > 0) {
					$tech_info_html .= '<h2>'._L('Technical information').'</h2>';
					$tech_info_html .= '<table border="0">';
					foreach ($tech_info as $key => $value) {
						$tech_info_html .= '<tr><td>'.$key.': </td><td><code>'.$value.'</code></td></tr>';
					}
					$tech_info_html .= '</table>';
				}

				$content = $tech_info_html;

				// $content .= "<p><a href=\"https://misc.daniel-marschall.de/tools/uuid_mac_decoder/interprete_uuid.php?uuid=".urlencode($this->guid)."\">More technical information</a></p>";
			} else {
				$content = '';
			}

			$content .= '<h2>'._L('Description').'</h2>%%DESC%%';

			if (!$this->isLeafNode()) {
				if ($this->userHasWriteRights()) {
					$content .= '<h2>'._L('Create or change subordinate objects / categories').'</h2>';
				} else {
					$content .= '<h2>'._L('Subordinate objects / categories').'</h2>';
				}
				$content .= '%%CRUD%%';
			}
		}
	}

	public function getIcon($row=null) {
		$in_login_treenode = false;
		foreach (debug_backtrace() as $trace) {
			// If we are inside the "Login" area (i.e. "Root object links"), we want the
			// correct icon, not a folder icon!
			if ($trace['class'] === OIDplusPagePublicLogin::class) $in_login_treenode = true;
		}

		if (!$in_login_treenode && !$this->isLeafNode()) return null; // foldericon

		return parent::getIcon($row);
	}

	public function one_up() {
		// A GUID is a GUID, there is no hierarchy
		return false;
	}

	public function distance($to) {
		// Distance between GUIDs is not possible
		return null;
	}

	public function getAltIds() {
		if ($this->isRoot()) return array();
		if (!$this->isLeafNode()) return array();
		$ids = parent::getAltIds();
		$ids[] = new OIDplusAltId('oid', uuid_to_oid($this->guid), _L('OID representation of UUID'));
		return $ids;
	}

	public function getDirectoryName() {
		if ($this->isLeafNode()) {
			// Leaf (UUID)
			// Example output: "guid_adb0b042_5b57_11eb_b0d9_3c4a92df8582"
			$str = $this->nodeId(false);
			$str = str_replace('-', '_', $str);
			$str = strtolower($str);
			return $this->ns().'_'.$str;
		} else {
			// Category
			return parent::getDirectoryName();
		}
	}

	public static function treeIconFilename($mode) {
		return 'img/'.$mode.'_icon16.png';
	}
}
