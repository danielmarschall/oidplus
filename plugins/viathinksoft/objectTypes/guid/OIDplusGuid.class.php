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
	/**
	 * @var string
	 */
	private $guid;

	/**
	 * @param string $guid
	 */
	public function __construct(string $guid) {
		if (uuid_valid($guid)) {
			$this->guid = strtolower(uuid_canonize($guid)); // It is a real GUID (leaf node)
		} else {
			$this->guid = $guid; // It is a category name
		}
	}

	/**
	 * @param string $node_id
	 * @return OIDplusGuid|null
	 */
	public static function parse(string $node_id)/*: ?OIDplusGuid*/ {
		@list($namespace, $guid) = explode(':', $node_id, 2);
		if ($namespace !== self::ns()) return null;
		return new self($guid);
	}

	/**
	 * @return string
	 */
	public static function objectTypeTitle(): string {
		return _L('Globally Unique Identifier (GUID)');
	}

	/**
	 * @return string
	 */
	public static function objectTypeTitleShort(): string {
		return _L('GUID');
	}

	/**
	 * @return string
	 */
	public static function ns(): string {
		return 'guid';
	}

	/**
	 * @return string
	 */
	public static function root(): string {
		return self::ns().':';
	}

	/**
	 * @return bool
	 */
	public function isRoot(): bool {
		return $this->guid == '';
	}

	/**
	 * @param bool $with_ns
	 * @return string
	 */
	public function nodeId(bool $with_ns=true): string {
		return $with_ns ? self::root().$this->guid : $this->guid;
	}

	/**
	 * @param string $str
	 * @return string
	 */
	public function addString(string $str): string {
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

	/**
	 * @param OIDplusObject $parent
	 * @return string
	 */
	public function crudShowId(OIDplusObject $parent): string {
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

	/**
	 * @param OIDplusObject|null $parent
	 * @return string
	 */
	public function jsTreeNodeName(OIDplusObject $parent = null): string {
		if ($parent == null) return $this->objectTypeTitle();
		return $this->crudShowId($parent);
	}

	/**
	 * @return string
	 */
	public function defaultTitle(): string {
		return $this->guid;
	}

	/**
	 * @return bool
	 */
	public function isLeafNode(): bool {
		return uuid_valid($this->guid);
	}

	/**
	 * @return array
	 */
	private function getTechInfo(): array {
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

	/**
	 * @param string $title
	 * @param string $content
	 * @param string $icon
	 * @return void
	 * @throws OIDplusException
	 */
	public function getContentPage(string &$title, string &$content, string &$icon) {
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

	/**
	 * @return OIDplusGuid|null
	 */
	public function one_up()/*: ?OIDplusGuid*/ {
		// A GUID is a GUID, there is no hierarchy
		return self::parse(self::ns().':');
	}

	/**
	 * @param OIDplusObject|string $to
	 * @return int|null
	 */
	public function distance($to) {
		// Distance between GUIDs is not possible
		return null;
	}

	/**
	 * @return array|OIDplusAltId[]
	 * @throws OIDplusException
	 */
	public function getAltIds(): array {
		if ($this->isRoot()) return array();
		if (!$this->isLeafNode()) return array();
		$ids = parent::getAltIds();
		$ids[] = new OIDplusAltId('oid', uuid_to_oid($this->guid), _L('OID representation of UUID'));
		return $ids;
	}

	/**
	 * @return string
	 * @throws OIDplusException
	 */
	public function getDirectoryName(): string {
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

	/**
	 * @param string $mode
	 * @return string
	 */
	public static function treeIconFilename(string $mode): string {
		return 'img/'.$mode.'_icon16.png';
	}
}
