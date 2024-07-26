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

class OIDplusMac extends OIDplusObject {
	/**
	 * @var string
	 */
	private $number;

	/**
	 * @param string $number
	 */
	public function __construct(string $number) {
		// TODO: syntax checks
		$this->number = $number;
	}

	/**
	 * @param string $node_id
	 * @return OIDplusMac|null
	 */
	public static function parse(string $node_id): ?OIDplusMac {
		@list($namespace, $number) = explode(':', $node_id, 2);
		if ($namespace !== self::ns()) return null;
		return new self($number);
	}

	/**
	 * @return string
	 */
	public static function objectTypeTitle(): string {
		return _L('MAC adresses (EUI/ELI/AAI/SAI)');
	}

	/**
	 * @return string
	 */
	public static function objectTypeTitleShort(): string {
		return _L('MAC');
	}

	/**
	 * @return string
	 */
	public static function ns(): string {
		return 'mac';
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
		return $this->number == '';
	}

	/**
	 * @param bool $with_ns
	 * @return string
	 */
	public function nodeId(bool $with_ns=true): string {
		return $with_ns ? self::root().$this->number : $this->number;
	}

	/**
	 * @param string $str
	 * @return string
	 * @throws OIDplusException
	 */
	public function addString(string $str): string {
		$str = str_replace(array('-', ':', ' '), '', $str);
		$str = strtoupper($str);

		$test = preg_replace('@[0-9A-F]@', '', $str);
		if ($test != '') throw new OIDplusException(_L("Invalid characters entered"));

		$new_mac = $this->nodeId(false) . $str;

		$type = mac_type(str_pad($new_mac, 12, '0', STR_PAD_RIGHT));
		$type = substr($type, 0, 3);

		if (($type == 'ELI') || ($type == 'EUI')) {
			if ($this->isRoot() && (strlen($str) < 6)) {
				throw new OIDplusException(_L("The first node must be at least 24 bits long, since this is the smallest assignment for OUI/CID from IEEE."));
			}
		}

		if (($type == 'ELI') || ($type == 'EUI') || ($type == 'AAI') || ($type == 'SAI')) {
			// Note: AAI-48, AAI-64, SAI-48, and SAI-64 are defined in IEEE 802c-2017
			if (strlen($new_mac) > 16) {
				throw new OIDplusException(_L("The max length of an EUI-64 or ELI-64 is 64 bit"));
			}
		}

		return $this->root().$new_mac;
	}

	/**
	 * @param OIDplusObject $parent
	 * @return string
	 * @throws OIDplusException
	 */
	public function crudShowId(OIDplusObject $parent): string {
		//return $this->chunkedNotation(false);
		return rtrim(chunk_split($this->number, 2, '-'), '-');
	}

	/**
	 * @return string
	 * @throws OIDplusException
	 */
	public function crudInsertPrefix(): string {
		return $this->isRoot() ? '' : $this->chunkedNotation(false);
	}

	/**
	 * @param OIDplusObject|null $parent
	 * @return string
	 */
	public function jsTreeNodeName(?OIDplusObject $parent=null): string {
		if ($parent == null) return $this->objectTypeTitle();
		return substr($this->nodeId(), strlen($parent->nodeId()));
	}

	/**
	 * @return string
	 */
	public function defaultTitle(): string {
		//return $this->number;
		return rtrim(chunk_split($this->number, 2, '-'), '-');
	}

	/**
	 * @return bool
	 */
	public function isLeafNode(): bool {
		// Problem with this approach: If we are EUI-48 and want to add more (to get EUI-64), we couldn't.
		/*
		return mac_valid($this->nodeId(false));
		*/
		return eui_bits($this->nodeId(false)) == 64;
	}

	/**
	 * @return array
	 */
	private function getTechInfo(): array {
		$tech_info = array();

		ob_start();
		try {
			// TODO: OIDplus should download the *.txt files at "web-data"
			decode_mac(mac_canonize($this->nodeId(false)));
			$tech_info = ob_get_contents();


			$lines = explode("\n", $tech_info);
			$tech_info = [];
			$key = '';
			foreach ($lines as $line) {
				$m1 = explode(':', $line);
				if (!isset($m1[1])) $m1 = array($key, $m1[0]);
				$key = $m1[0];
				if (isset($tech_info[$key])) {
					$value = $tech_info[$key].'<br>'.$m1[1];
				} else {
					$value = $m1[1];
				}
				$tech_info[$key] = $value;
			}

		} catch (\Exception $e) {
			$tech_info = [];
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
			$title = OIDplusMac::objectTypeTitle();

			$res = OIDplus::db()->query("select * from ###objects where parent = ?", array(self::root()));
			if ($res->any()) {
				$content  = '<p>'._L('Please select an item in the tree view at the left to show its contents.').'</p>';
			} else {
				$content  = '<p>'._L('Currently, no MAC addresses are registered in the system.').'</p>';
			}

			if (!$this->isLeafNode()) {
				if (OIDplus::authUtils()->isAdminLoggedIn()) {
					$content .= '<h2>'._L('Manage root objects').'</h2>';
				} else {
					$content .= '<h2>'._L('Available objects').'</h2>';
				}
				$content .= '%%CRUD%%';
			}
		} else {
			$title = $this->getTitle();

			$tech_info = $this->getTechInfo();
			$tech_info_html = '';
			if (count($tech_info) > 0) {
				$tech_info_html .= '<h2>'._L('Technical information').'</h2>';
				$tech_info_html .= '<div style="overflow:auto"><table border="0">';
				foreach ($tech_info as $key => $value) {
					$tech_info_html .= '<tr><td valign="top" style="white-space: nowrap;">'.$key.': </td><td><code>'.$value.'</code></td></tr>';
				}
				$tech_info_html .= '</table></div>';
			}
			$content = $tech_info_html;

			$chunked = $this->chunkedNotation(true);
			if (!mac_valid($this->number)) {
				$chunked .= ' ...';
			}

			$type = '';
			try {
				$type_raw = mac_type(str_pad($this->number, 12, '0', STR_PAD_RIGHT));
				if (preg_match('@(.+) \\((.+)\\)@ismU', $type_raw, $m)) {
					$type_short = $m[1];
					$type_long = $m[2];
					$type = '<abbr title="'.htmlentities($type_long).'">'.htmlentities($type_short).'</abbr>';
				} else {
					$type = htmlentities($type_raw);
				}
			} catch (\Exception $e) {};

			$content  = '<h2>'.$type.' <strong>'.$chunked.'</strong></h2>';
			$content .= $tech_info_html;

			if ($this->userHasParentalWriteRights()) {
				$content .= '<h2>'._L('Superior RA Allocation Info').'</h2>%%SUPRA%%';
			}

			if ($this->isLeafNode()) {
				$content .= '<h2>'._L('Description').'</h2>%%DESC%%';
				$content .= '<h2>'._L('Registration Authority').'</h2>%%RA_INFO%%';
			} else {
				$content .= '<h2>'._L('Description').'</h2>%%DESC%%';
				$content .= '<h2>'._L('Registration Authority').'</h2>%%RA_INFO%%';
				if ($this->userHasWriteRights()) {
					$content .= '<h2>'._L('Create or change subordinate objects').'</h2>';
				} else {
					$content .= '<h2>'._L('Subordinate objects').'</h2>';
				}
				$content .= '%%CRUD%%';
			}
		}
	}

	# ---

	/**
	 * @param bool $withAbbr
	 * @return string
	 * @throws OIDplusException
	 */
	public function chunkedNotation(bool $withAbbr=true): string {
		$curid = self::root().$this->number;

		$obj = OIDplusObject::findFitting($curid);
		if (!$obj) return $this->number;

		$hints = array();
		$lengths = array(strlen($curid));
		while ($obj = OIDplusObject::findFitting($curid)) {
			$objParent = $obj->getParent();
			if (!$objParent) break;
			$curid = $objParent->nodeId(true);
			$hints[] = $obj->getTitle();
			$lengths[] = strlen($curid);
		}

		array_shift($lengths);
		$chunks = array();

		$full = self::root().$this->number;
		foreach ($lengths as $len) {
			$chunks[] = substr($full, $len);
			$full = substr($full, 0, $len);
		}

		$hints = array_reverse($hints);
		$chunks = array_reverse($chunks);

		$full = array();
		foreach ($chunks as $c) {
			$hint = array_shift($hints);
			$full[] = $withAbbr && ($hint !== '') ? '<abbr title="'.htmlentities($hint).'">'.$c.'</abbr>' : $c;
		}

		return implode(' ', $full);
	}

	/**
	 * @return OIDplusMac|null
	 */
	public function one_up(): ?OIDplusMac {
		return self::parse($this->ns().':'.substr($this->number,0,strlen($this->number)-1));
	}

	/**
	 * @param string $a
	 * @param string $b
	 * @return null|int
	 */
	private static function distance_(string $a, string $b): ?int {
		$min_len = min(strlen($a), strlen($b));

		for ($i=0; $i<$min_len; $i++) {
			if ($a[$i] != $b[$i]) return null;
		}

		return strlen($a) - strlen($b);
	}

	/**
	 * @param OIDplusObject|string $to
	 * @return int|null
	 */
	public function distance($to): ?int {
		if (!is_object($to)) $to = OIDplusObject::parse($to);
		if (!$to) return null;
		if (!($to instanceof $this)) return null;

		if ($this->number == $to->number) return 0;

		$b = $this->number;
		$a = $to->number;
		$tmp = self::distance_($a, $b);
		if ($tmp !== null) return $tmp;

		return null;
	}

	/**
	 * @return array|OIDplusAltId[]
	 * @throws OIDplusException
	 */
	public function getAltIds(): array {
		if ($this->isRoot()) return array();
		$ids = parent::getAltIds();

		// (VTS F2) MAC address (EUI/ELI/...) to AID (PIX allowed)
		$size_nibble = strlen($this->number)-1;
		if (($size_nibble >= 0) && ($size_nibble <= 0xF)) {
			$aid = 'D276000186F2'.strtoupper(dechex($size_nibble)).$this->number;
			if ((strlen($aid)%2) == 1) $aid .= 'F';
			$aid_is_ok = aid_canonize($aid);
			if ($aid_is_ok) $ids[] = new OIDplusAltId('aid', $aid, _L('Application Identifier (ISO/IEC 7816)'), ' ('._L('Optional PIX allowed, without prefix').')', 'https://hosted.oidplus.com/viathinksoft/?goto=aid%3AD276000186F2');
		}

		return $ids;
	}

	/**
	 * @return string
	 */
	public function getDirectoryName(): string {
		if ($this->isRoot()) return $this->ns();
		return $this->ns().'_'.$this->nodeId(false); // safe, because there are only hexadecimal numbers
	}

	/**
	 * @param string $mode
	 * @return string
	 */
	public static function treeIconFilename(string $mode): string {
		return 'img/'.$mode.'_icon16.png';
	}
}
