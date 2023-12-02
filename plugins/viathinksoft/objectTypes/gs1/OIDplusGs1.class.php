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

class OIDplusGs1 extends OIDplusObject {
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
	 * @return OIDplusGs1|null
	 */
	public static function parse(string $node_id)/*: ?OIDplusGs1*/ {
		@list($namespace, $number) = explode(':', $node_id, 2);
		if ($namespace !== self::ns()) return null;
		return new self($number);
	}

	/**
	 * @return string
	 */
	public static function objectTypeTitle(): string {
		return _L('GS1 Based IDs (GLN/GTIN/SSCC/...)');
	}

	/**
	 * @return string
	 */
	public static function objectTypeTitleShort(): string {
		return _L('GS1');
	}

	/**
	 * @return string
	 */
	public static function ns(): string {
		return 'gs1';
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
		$m = array();
		if (!preg_match('@^\\d+$@', $str, $m)) {
			throw new OIDplusException(_L('GS1 value needs to be numeric'));
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
		return !$this->isBaseOnly();
	}

	/**
	 * @return array
	 */
	private function getTechInfo(): array {
		require_once __DIR__ . '/gs1_utils.inc.php'; // TODO: Move to ViaThinkSoft PHP-Utils
		$tech_info = array();
		// TODO: Also show Format and Regular Expression?
		// TODO: Maybe even check if the Regular Expression matches, i.e. the barcode is valid?
		$tech_info['<a href="https://www.gs1.org/standards/barcodes/application-identifiers?lang=en" target="_blank">'._L('GS1 Application Identifier').'</a>']   = gs1_barcode_show_appidentifier($this->number);
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
			$title = OIDplusGs1::objectTypeTitle();

			$res = OIDplus::db()->query("select * from ###objects where parent = ?", array(self::root()));
			if ($res->any()) {
				$content  = '<p>'._L('Please select an item in the tree view at the left to show its contents.').'</p>';
			} else {
				$content  = '<p>'._L('Currently, no GS1 based numbers are registered in the system.').'</p>';
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
					$tech_info_html .= '<tr><td valign="top" style="white-space: nowrap;">'.$key.': </td><td><code>'.str_replace(' ','&nbsp;',$value).'</code></td></tr>';
				}
				$tech_info_html .= '</table></div>';
			}

			if ($this->isLeafNode()) {
				$chunked = $this->chunkedNotation(true);
				$checkDigit = $this->checkDigit();
				$content  = '<h2>'._L('Barcode').' '.$chunked.' - <abbr title="'._L('check digit').'">'.$checkDigit.'</abbr></h2>';
				$content .= '<p><a target="_blank" href="https://www.ean-search.org/?q='.htmlentities($this->fullNumber()).'">'._L('Lookup at ean-search.org').'</a></p>';
				if (url_get_contents_available(true, $reason)) {
					$content .= '<p><img alt="'._L('Barcode').'" src="' . OIDplus::webpath(__DIR__, OIDplus::PATH_RELATIVE) . 'barcode.php?number=' . urlencode($this->fullNumber()) . '"></p>';
				}
				$content .= $tech_info_html;
				$content .= '<h2>'._L('Description').'</h2>%%DESC%%'; // TODO: add more meta information about the object type
			} else {
				$chunked = $this->chunkedNotation(true);
				$content  = '<h2>'._L('Barcode').' '.$chunked.'</h2>';
				$content .= $tech_info_html;
				$content .= '<h2>'._L('Description').'</h2>%%DESC%%'; // TODO: add more meta information about the object type
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
	 * @return bool
	 */
	public function isBaseOnly(): bool {
		// TODO: This is actually not correct, since there are many GS1 Application Identifiers which can have less than 7 digits
		return strlen($this->number) <= 7;
	}

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
	 * @return string
	 */
	public function fullNumber(): string {
		return $this->number . $this->checkDigit();
	}

	/**
	 * @return int
	 */
	public function checkDigit(): int {
		$mul = 3;
		$sum = 0;
		for ($i=strlen($this->number)-1; $i>=0; $i--) {
			$str = "".$this->number;
			$sum += (int)$str[$i] * $mul;
			$mul = $mul == 3 ? 1 : 3;
		}
		return 10 - ($sum % 10);
	}

	/**
	 * @return OIDplusGs1|null
	 */
	public function one_up()/*: ?OIDplusGs1*/ {
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

		// This is pretty tricky, because the whois service should accept GS1 numbers with and without checksum
		if ($this->number == $to->number) return 0;
		if ($this->number.$this->checkDigit() == $to->number) return 0;
		if ($this->number == $to->number.$to->checkDigit()) return 0;

		$b = $this->number;
		$a = $to->number;
		$tmp = self::distance_($a, $b);
		if ($tmp !== false) return $tmp;

		$b = $this->number.$this->checkDigit();
		$a = $to->number;
		$tmp = self::distance_($a, $b);
		if ($tmp !== false) return $tmp;

		$b = $this->number;
		$a = $to->number.$to->checkDigit();
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

		// (VTS F5) GS1 to AID (PIX allowed)
		$gs1 = $this->nodeId(false);
		$aid = 'D276000186F5'.$gs1;
		if (strlen($aid)%2 == 1) $aid .= 'F';
		$aid_is_ok = aid_canonize($aid);
		if ($aid_is_ok) $ids[] = new OIDplusAltId('aid', $aid, _L('Application Identifier (ISO/IEC 7816)'), ' ('._L('Optional PIX allowed, with "FF" prefix').')', 'https://hosted.oidplus.com/viathinksoft/?goto=aid%3AD276000186F5');

		return $ids;
	}

	/**
	 * @return string
	 */
	public function getDirectoryName(): string {
		if ($this->isRoot()) return $this->ns();
		return $this->ns().'_'.$this->nodeId(false); // safe, because there are only numbers
	}

	/**
	 * @param string $mode
	 * @return string
	 */
	public static function treeIconFilename(string $mode): string {
		return 'img/'.$mode.'_icon16.png';
	}
}
