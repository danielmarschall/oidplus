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

class OIDplusFourCC extends OIDplusObject {
	/**
	 * @var string
	 */
	private $fourcc;

	/**
	 * FourCC Syntax examples:
	 * fourcc_transform('8BIM')       === array(56,66,73,77);   // Adobe Photoshop
	 * fourcc_transform('AVI')        === array(65,86,73,32);   // AVI File (padded with whitespace)
	 * fourcc_transform('Y3[10][10]') === array(89,51,10,10);   // 10bit Y'CbCr 4:2:2 video
	 * Non-FourCC:  fourcc_transform returns false.
	 * @param string $fourcc
	 * @return array|false
	 */
	private function fourcc_transform(string $fourcc) {
		$out = array();
		if ($fourcc === '') return false;
		for ($i=0; $i<4; $i++) {
			if (strlen($fourcc) === 0) {
				$out[] = 0x20; // fill with whitespace
			} else {
				if ($fourcc[0] !== '[') {
					$out[] = ord($fourcc[0]);
					$fourcc = substr($fourcc,1);
				} else {
					$p = strpos($fourcc,']');
					$out[] = (int)substr($fourcc,1,$p-1);
					$fourcc = substr($fourcc,$p+1);
				}
			}
		}
		if ($fourcc !== '') return false;
		return $out;
	}

	/**
	 * @param string $fourcc
	 */
	public function __construct(string $fourcc) {
		if (self::fourcc_transform($fourcc) !== false) {
			$this->fourcc = $fourcc; // leaf node
		} else {
			$this->fourcc = $fourcc; // It is a category name
		}
	}

	/**
	 * @param string $node_id
	 * @return OIDplusFourCC|null
	 */
	public static function parse(string $node_id)/*: ?OIDplusFourCC*/ {
		@list($namespace, $fourcc) = explode(':', $node_id, 2);
		if ($namespace !== self::ns()) return null;
		return new self($fourcc);
	}

	/**
	 * @return string
	 */
	public static function objectTypeTitle(): string {
		return _L('Four-Character-Code (FourCC)');
	}

	/**
	 * @return string
	 */
	public static function objectTypeTitleShort(): string {
		return _L('FourCC');
	}

	/**
	 * @return string
	 */
	public static function ns(): string {
		return 'fourcc';
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
		return $this->fourcc == '';
	}

	/**
	 * @param bool $with_ns
	 * @return string
	 */
	public function nodeId(bool $with_ns=true): string {
		return $with_ns ? self::root().$this->fourcc : $this->fourcc;
	}

	/**
	 * @param string $str
	 * @return string
	 */
	public function addString(string $str): string {

		// Y3[10] [10] --> Y3[10][10]
		$test_str = trim($str);
		do {
			$test_str2 = $test_str;
			$test_str = str_replace(' [', '[', $test_str);
			$test_str = str_replace('] ', ']', $test_str);
		} while ($test_str2 != $test_str);

		if (self::fourcc_transform($test_str) !== false) {
			// real FourCC
			return self::root() . $test_str;
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
		return $this->fourcc;
	}

	/**
	 * @return bool
	 */
	public function isLeafNode(): bool {
		return self::fourcc_transform($this->fourcc) !== false;
	}

	/**
	 * @return array
	 */
	private function getTechInfo(): array {
		$tech_info = array();
		$tech_info[_L('FourCC code')]   = $this->fourcc;
		$tech_info[_L('C/C++ Literal')] = $this->getMultiCharLiteral();
		$tech_info[_L('Hex Dump')]      = strtoupper(implode(' ', str_split($this->getHex(true),2)));
		$tech_info[_L('Big Endian')]    = '0x'.$this->getHex(true).' ('.$this->getInt(true).')';
		$tech_info[_L('Little Endian')] = '0x'.$this->getHex(false).' ('.$this->getInt(false).')';
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
			$title = OIDplusFourCC::objectTypeTitle();

			$res = OIDplus::db()->query("select * from ###objects where parent = ?", array(self::root()));
			if ($res->any()) {
				$content  = '<p>'._L('Please select a FourCC in the tree view at the left to show its contents.').'</p>';
			} else {
				$content  = '<p>'._L('Currently, no FourCC is registered in the system.').'</p>';
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
					$tech_info_html .= '<div style="overflow:auto"><table border="0">';
					foreach ($tech_info as $key => $value) {
						$tech_info_html .= '<tr><td valign="top" style="white-space: nowrap;">'.$key.': </td><td><code>'.str_replace(' ','&nbsp;',$value).'</code></td></tr>';
					}
					$tech_info_html .= '</table></div>';
				}

				$content = $tech_info_html;
			} else {
				$content = '';
			}

			if ($this->userHasParentalWriteRights()) {
				$content .= '<h2>'._L('Superior RA Allocation Info').'</h2>%%SUPRA%%';
			}

			$content .= '<h2>'._L('Description').'</h2>%%DESC%%';
			$content .= '<h2>'._L('Registration Authority').'</h2>%%RA_INFO%%';

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
	 * @return OIDplusFourCC|null
	 */
	public function one_up()/*: ?OIDplusFourCC*/ {
		// A FourCC is a FourCC, there is no hierarchy
		return self::parse(self::ns().':');
	}

	/**
	 * @param OIDplusObject|string $to
	 * @return int|null
	 */
	public function distance($to) {
		// Distance between FourCCs is not possible
		return null;
	}

	/**
	 * @return array|OIDplusAltId[]
	 * @throws OIDplusException
	 */
	public function getAltIds(): array {
		if ($this->isRoot()) return array();
		if (!$this->isLeafNode()) return array();
		return parent::getAltIds();
	}

	/**
	 * @param bool $big_endian
	 * @return false|int
	 */
	private function getInt(bool $big_endian) {
		$type = self::fourcc_transform($this->fourcc);
		if ($type === false) return false;
		$dec = 0;
		if (!$big_endian) $type = array_reverse($type);
		for ($i=0;$i<4;$i++) $dec = ($dec<<8) + $type[$i];
		return $dec;
	}

	/**
	 * @param bool $big_endian
	 * @return string
	 */
	private function getHex(bool $big_endian): string {
		$dec = $this->getInt($big_endian);
		return str_pad(dechex($dec), 8, "0", STR_PAD_LEFT);
	}

	/**
	 * @return false|string
	 */
	private function getMultiCharLiteral() {
		$type = self::fourcc_transform($this->fourcc);
		if ($type === false) return false;
		return c_literal($type);
	}

	/**
	 * @return string
	 * @throws OIDplusException
	 */
	public function getDirectoryName(): string {
		if ($this->isLeafNode()) {
			// Leaf (FourCC)
			// Example output: "fourcc_23496d52" for 'fourcc:#ImR'
			return $this->ns().'_'.$this->getHex(true);
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
