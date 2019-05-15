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

class OIDplusGs1 extends OIDplusObject {
	private $number;

	public function __construct($number) {
		// TODO: syntax checks
		$this->number = $number;
	}

	public static function parse($node_id) {
		@list($namespace, $number) = explode(':', $node_id, 2);
		if ($namespace !== 'gs1') return false;
		return new self($number);
	}

	public static function objectTypeTitle() {
		return "GS1 Based IDs (GLN/GTIN/SSCC/...)";
	}

	public static function objectTypeTitleShort() {
		return "GS1";
	}

	public static function ns() {
		return 'gs1';
	}

	public static function root() {
		return 'gs1:';
	}

	public function isRoot() {
		return $this->number == '';
	}

	public function nodeId() {
		return 'gs1:'.$this->number;
	}

	public function addString($str) {
		if (!preg_match('@^\\d+$@', $str, $m)) {
			throw new Exception('GS1 value needs to be numeric');
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
		$icon = file_exists(__DIR__.'/icon_big.png') ? 'plugins/objectTypes/'.basename(__DIR__).'/icon_big.png' : '';

		if ($this->isRoot()) {
			$title = OIDplusGs1::objectTypeTitle();

			$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where parent = '".OIDplus::db()->real_escape_string(self::root())."'");
			if (OIDplus::db()->num_rows($res) > 0) {
				$content  = 'Please select an item in the tree view at the left to show its contents.';
			} else {
				$content  = 'Currently, no GS1 based numbers are registered in the system.';
			}

			if (!$this->isLeafNode()) {
				if (OIDplus::authUtils()::isAdminLoggedIn()) {
					$content .= '<h2>Manage root objects</h2>';
				} else {
					$content .= '<h2>Available objects</h2>';
				}
				$content .= '%%CRUD%%';
			}
		} else {
			if ($this->isLeafNode()) {
				$chunked = $this->chunkedNotation(true);
				$checkDigit = $this->checkDigit();
				$content = '<h2>'.$chunked.' - <abbr title="check digit">'.$checkDigit.'</abbr></h2>';
				$content .= '<p><a target="_blank" href="https://www.ean-search.org/?q='.htmlentities($this->fullNumber()).'">Lookup in ean-search.org</a></p>';
				$content .= '<img src="plugins/objectTypes/'.basename(__DIR__).'/barcode.php?number='.urlencode($this->fullNumber()).'">';
				$content .= '<h2>Description</h2>%%DESC%%'; // TODO: add more meta information about the object type
			} else {
				$chunked = $this->chunkedNotation(true);
				$content = '<h2>'.$chunked.'</h2>';
				$content .= '<h2>Description</h2>%%DESC%%'; // TODO: add more meta information about the object type
				if ($this->userHasWriteRights()) {
					$content .= '<h2>Create or change subsequent objects</h2>';
				} else {
					$content .= '<h2>Subsequent objects</h2>';
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
		$curid = 'gs1:'.$this->number;

		$res = OIDplus::db()->query("select id, title from ".OIDPLUS_TABLENAME_PREFIX."objects where id = '".OIDplus::db()->real_escape_string($curid)."'");
		if (OIDplus::db()->num_rows($res) == 0) return $this->number();

		$hints = array();
		$lengths = array(strlen($curid));
		while (OIDplus::db()->num_rows($res = OIDplus::db()->query("select parent, title from ".OIDPLUS_TABLENAME_PREFIX."objects where id = '".OIDplus::db()->real_escape_string($curid)."'")) > 0) {
			$row = OIDplus::db()->fetch_array($res);
			$curid = $row['parent'];
			$hints[] = $row['title'];
			$lengths[] = strlen($curid);
		}

		array_shift($lengths);
		$chunks = array();

		$full = 'gs1:'.$this->number;
		foreach ($lengths as $len) {
			$chunks[] = substr($full, $len);
			$full = substr($full, 0, $len);
		}

		$hints = array_reverse($hints);
		$chunks = array_reverse($chunks);

		$full = array();
		foreach ($chunks as $c) {
			$full[] = $withAbbr ? '<abbr title="'.htmlentities(array_shift($hints)).'">'.$c.'</abbr>' : $c;
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
		return  OIDplusObject::parse($this->ns().':'.substr($this->number,0,strlen($this->number)-1));
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
}

OIDplus::registerObjectType('OIDplusGs1');
