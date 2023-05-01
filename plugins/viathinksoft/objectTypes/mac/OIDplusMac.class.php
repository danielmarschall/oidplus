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
	 * @var int|string
	 */
	private $number;

	/**
	 * @param string|int $number
	 */
	public function __construct($number) {
		// TODO: syntax checks
		$this->number = $number;
	}

	/**
	 * @param string $node_id
	 * @return OIDplusMac|null
	 */
	public static function parse(string $node_id)/*: ?OIDplusMac*/ {
		@list($namespace, $number) = explode(':', $node_id, 2);
		if ($namespace !== self::ns()) return null;
		return new self($number);
	}

	/**
	 * @return string
	 */
	public static function objectTypeTitle(): string {
		return _L('MAC/EUI/ELI Addresses');
	}

	/**
	 * @return string
	 */
	public static function objectTypeTitleShort(): string {
		return _L('MAC/EUI/ELI');
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
		$str = str_replace(array('-', ':'), '', $str);
		$str = strtoupper($str);

		$test = preg_replace('@[0-9A-F]@', '', $str);
		if ($test != '') throw new OIDplusException(_L("Invalid characters entered"));

		if ($this->isRoot() && (strlen($str) < 6)) {
			throw new OIDplusException(_L("The first node must be at least 24 bits long, since this is the smallest assignment for OUI/CID from IEEE."));
		}

		$new_mac = $this->nodeId() . $str;

		if (strlen($new_mac) > 16) {
			throw new OIDplusException(_L("The max length of an EUI-64 or ELI-64 is 64 bit"));
		}

		return $this->nodeId() . $str;
	}

	/**
	 * @param OIDplusObject $parent
	 * @return string
	 * @throws OIDplusException
	 */
	public function crudShowId(OIDplusObject $parent): string {
		return $this->chunkedNotation(false);
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
	public function jsTreeNodeName(OIDplusObject $parent = null): string {
		if ($parent == null) return $this->objectTypeTitle();
		return substr($this->nodeId(), strlen($parent->nodeId()));
	}

	/**
	 * @return string
	 */
	public function defaultTitle(): string {
		return $this->number;
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
				$content  = '<p>'._L('Currently, no MAC/EUI/ELI are registered in the system.').'</p>';
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

			// TODO: OIDplus should download the *.txt files at "web-data"
			// TODO: Use getTechInfo(), so we can show a nice table. (Caveat: "Address of registrant" is multi-line)
			ob_start();
			try {
				decode_mac(mac_canonize($this->nodeId(false)));
				$tech_info = ob_get_contents();
				$tech_info_html = '<h2>'._L('Technical information').'</h2>';
				$tech_info_html .= '<pre>'.$tech_info.'</pre>';
			} catch (\Exception $e) {
				$tech_info_html = '';
			}
			ob_end_clean();

			$chunked = $this->chunkedNotation(true);
			if (!mac_valid($this->number)) {
				$chunked .= ' ...';
			}

			$type = (strtoupper(substr($this->number,1,1)) == 'A') ? _L('ELI') : _L('EUI');

			$content  = '<h2>'.$type.' '.$chunked.'</h2>';
			$content .= $tech_info_html;

			if ($this->isLeafNode()) {
				$content .= '<h2>'._L('Description').'</h2>%%DESC%%';
			} else {
				$content .= '<h2>'._L('Description').'</h2>%%DESC%%';
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
			$curid = $objParent->nodeId();
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
	public function one_up()/*: ?OIDplusMac*/ {
		return self::parse($this->ns().':'.substr($this->number,0,strlen($this->number)-1));
	}

	/**
	 * @param string $a
	 * @param string $b
	 * @return false|int
	 */
	private static function distance_(string $a, string $b) {
		$min_len = min(strlen($a), strlen($b));

		for ($i=0; $i<$min_len; $i++) {
			if ($a[$i] != $b[$i]) return false;
		}

		return strlen($a) - strlen($b);
	}

	/**
	 * @param OIDplusObject|string $to
	 * @return int|null
	 */
	public function distance($to) {
		if (!is_object($to)) $to = OIDplusObject::parse($to);
		if (!$to) return null;
		if (!($to instanceof $this)) return null;

		if ($this->number == $to->number) return 0;

		$b = $this->number;
		$a = $to->number;
		$tmp = self::distance_($a, $b);
		if ($tmp !== false) return $tmp;

		return null;
	}

	/**
	 * @return array|OIDplusAltId[]
	 * @throws OIDplusException
	 */
	public function getAltIds(): array {
		if ($this->isRoot()) return array();
		$ids = parent::getAltIds();

		// (VTS F2) EUI/ELI-64 to AID (PIX allowed)
		$eui64 = mac_canonize(eui48_to_eui64($this->number),'');
		if (!$eui64) $eui64 = $this->number;
		$eui64 = str_pad($eui64, 16, '0', STR_PAD_RIGHT);
		$aid = 'D276000186F2'.$eui64;
		$aid_is_ok = aid_canonize($aid);
		if ($aid_is_ok) $ids[] = new OIDplusAltId('aid', $aid, _L('Application Identifier (ISO/IEC 7816)'), ' ('._L('Optional PIX allowed, without prefix').')');

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
