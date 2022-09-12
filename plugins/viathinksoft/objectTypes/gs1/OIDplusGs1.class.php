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

class OIDplusGs1 extends OIDplusObject {
	private $number;

	public function __construct($number) {
		// TODO: syntax checks
		$this->number = $number;
	}

	public static function parse($node_id) {
		@list($namespace, $number) = explode(':', $node_id, 2);
		if ($namespace !== self::ns()) return false;
		return new self($number);
	}

	public static function objectTypeTitle() {
		return _L('GS1 Based IDs (GLN/GTIN/SSCC/...)');
	}

	public static function objectTypeTitleShort() {
		return _L('GS1');
	}

	public static function ns() {
		return 'gs1';
	}

	public static function root() {
		return self::ns().':';
	}

	public function isRoot() {
		return $this->number == '';
	}

	public function nodeId($with_ns=true) {
		return $with_ns ? self::root().$this->number : $this->number;
	}

	public function addString($str) {
		$m = array();
		if (!preg_match('@^\\d+$@', $str, $m)) {
			throw new OIDplusException(_L('GS1 value needs to be numeric'));
		}

		return $this->nodeId() . $str;
	}

	public function crudShowId(OIDplusObject $parent) {
		return $this->chunkedNotation(false);
	}

	public function crudInsertPrefix() {
		return $this->isRoot() ? '' : $this->chunkedNotation(false);
	}

	public function jsTreeNodeName(OIDplusObject $parent = null) {
		if ($parent == null) return $this->objectTypeTitle();
		return substr($this->nodeId(), strlen($parent->nodeId()));
	}

	public function defaultTitle() {
		return $this->number;
	}

	public function isLeafNode() {
		return !$this->isBaseOnly();
	}

	public function getContentPage(&$title, &$content, &$icon) {
		$icon = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

		if ($this->isRoot()) {
			$title = OIDplusGs1::objectTypeTitle();

			$res = OIDplus::db()->query("select * from ###objects where parent = ?", array(self::root()));
			if ($res->any()) {
				$content  = _L('Please select an item in the tree view at the left to show its contents.');
			} else {
				$content  = _L('Currently, no GS1 based numbers are registered in the system.');
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

			if ($this->isLeafNode()) {
				$chunked = $this->chunkedNotation(true);
				$checkDigit = $this->checkDigit();
				$content = '<h2>'.$chunked.' - <abbr title="'._L('check digit').'">'.$checkDigit.'</abbr></h2>';
				$content .= '<p><a target="_blank" href="https://www.ean-search.org/?q='.htmlentities($this->fullNumber()).'">'._L('Lookup at ean-search.org').'</a></p>';
				$content .= '<img src="'.OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'barcode.php?number='.urlencode($this->fullNumber()).'">';
				$content .= '<h2>'._L('Description').'</h2>%%DESC%%'; // TODO: add more meta information about the object type
			} else {
				$chunked = $this->chunkedNotation(true);
				$content = '<h2>'.$chunked.'</h2>';
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

	public function isBaseOnly() {
		return strlen($this->number) <= 7;
	}

	public function chunkedNotation($withAbbr=true) {
		$curid = self::root().$this->number;

		$res = OIDplus::db()->query("select id, title from ###objects where id = ?", array($curid));
		if (!$res->any()) return $this->number;

		$hints = array();
		$lengths = array(strlen($curid));
		while (($res = OIDplus::db()->query("select parent, title from ###objects where id = ?", array($curid)))->any()) {
			$row = $res->fetch_array();
			$curid = $row['parent'];
			$hints[] = $row['title'];
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

	public function fullNumber() {
		return $this->number . $this->checkDigit();
	}

	public function checkDigit() {
		$mul = 3;
		$sum = 0;
		for ($i=strlen($this->number)-1; $i>=0; $i--) {
			$sum += $this->number[$i] * $mul;
			$mul = $mul == 3 ? 1 : 3;
		}
		return 10 - ($sum % 10);
	}

	public function one_up() {
		return OIDplusObject::parse($this->ns().':'.substr($this->number,0,strlen($this->number)-1));
	}

	private static function distance_($a, $b) {
		$min_len = min(strlen($a), strlen($b));

		for ($i=0; $i<$min_len; $i++) {
			if ($a[$i] != $b[$i]) return false;
		}

		return strlen($a) - strlen($b);
	}

	public function distance($to) {
		if (!is_object($to)) $to = OIDplusObject::parse($to);
		if (!($to instanceof $this)) return false;

		// This is pretty tricky, because the whois service should accept GS1 numbers with and without checksum
		if ($this->number == $to->number) return 0;
		if ($this->number.$this->checkDigit() == $to->number) return 0;
		if ($this->number == $to->number.$to->checkDigit()) return 0;

		$b = $this->number;
		$a = $to->number;
		$tmp = self::distance_($a, $b);
		if ($tmp != false) return $tmp;

		$b = $this->number.$this->checkDigit();
		$a = $to->number;
		$tmp = self::distance_($a, $b);
		if ($tmp != false) return $tmp;

		$b = $this->number;
		$a = $to->number.$to->checkDigit();
		$tmp = self::distance_($a, $b);
		if ($tmp != false) return $tmp;

		return null;
	}

	public function getAltIds() {
		if ($this->isRoot()) return array();
		$ids = parent::getAltIds();

		// (VTS F5) GS1 to AID (PIX allowed)
		$gs1 = $this->nodeId(false);
		$aid = 'D276000186F5'.$gs1;
		if (strlen($aid)%2 == 1) $aid .= 'F';
		$aid_is_ok = aid_canonize($aid);
		if ($aid_is_ok) $ids[] = new OIDplusAltId('aid', $aid, _L('Application Identifier (ISO/IEC 7816-5)'), ' ('._L('Optional PIX allowed, with "FF" prefix').')');

		return $ids;
	}

	public function getDirectoryName() {
		if ($this->isRoot()) return $this->ns();
		return $this->ns().'_'.$this->nodeId(false); // safe, because there are only numbers
	}

	public static function treeIconFilename($mode) {
		return 'img/'.$mode.'_icon16.png';
	}
}
