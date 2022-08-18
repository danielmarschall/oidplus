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

class OIDplusAid extends OIDplusObject {
	private $aid;

	public function __construct($aid) {
		// TODO: syntax checks
		$this->aid = $aid;
	}

	public static function parse($node_id) {
		@list($namespace, $aid) = explode(':', $node_id, 2);
		if ($namespace !== self::ns()) return false;
		return new self($aid);
	}

	public static function objectTypeTitle() {
		return _L('Application Identifier (ISO/IEC 7816-5)');
	}

	public static function objectTypeTitleShort() {
		return _L('AID');
	}

	public static function ns() {
		return 'aid';
	}

	public static function root() {
		return self::ns().':';
	}

	public function isRoot() {
		return $this->aid == '';
	}

	public function nodeId($with_ns=true) {
		return $with_ns ? self::root().$this->aid : $this->aid;
	}

	public function addString($str) {
		$m = array();

		$str = str_replace(' ','',$str);

		if (!preg_match('@^[0-9a-fA-F]+$@', $str, $m)) {
			throw new OIDplusException(_L('AID part needs to be hexadecimal'));
		}

		if (strlen($this->nodeId(false).$str) > 32) {
			throw new OIDplusException(_L('An AID has a maximum length of 16 bytes'));
		}

		$pre   = $this->nodeId(false);
		$add   = strtoupper($str);
		$after = $pre.$add;
		$rid = '?';
		$pix = '?';
		$p = aid_split_rid_pix($after, $rid, $pix);
		if ($p > 1) { // Why $p>1? For "F", there is no RID. We allow that somebody include "F" in the first node
			if ((strlen($pre)<$p) && (strlen($after)>$p)) {
				$rid = substr($rid,strlen($pre));
				throw new OIDplusException(_L('This node would mix RID (registry ID) and PIX (application specific). Please split it into two nodes "%1" and "%2".',$rid,$pix));
			}
		}

		return $this->nodeId(true).strtoupper($str);
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
		return $this->aid;
	}

	public function isLeafNode() {
		return false; // We don't know when it is a leaf node!
	}

	public function getContentPage(&$title, &$content, &$icon) {
		$icon = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

		if ($this->isRoot()) {
			$title = OIDplusAid::objectTypeTitle();

			$res = OIDplus::db()->query("select * from ###objects where parent = ?", array(self::root()));
			if ($res->any()) {
				$content  = _L('Please select an item in the tree view at the left to show its contents.');
			} else {
				$content  = _L('Currently, no Application Identifiers are registered in the system.');
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

			$chunked = $this->chunkedNotation(true);
			$content = '<h2>'.$chunked.'</h2>';

			$tmp = decode_aid($this->aid,true);
			$tmp = htmlentities($tmp);
			$tmp = str_replace(' ','&nbsp;',$tmp);
			$tmp = nl2br($tmp);
			$tmp = preg_replace('@(warning|invalid|error)@i', '<font color="red">\\1</font>', $tmp);

			$content .= '<h2>'._L('Decoding').'</h2>';
			$content .= '<table border="0">';
			$content .= '<code>'.$tmp.'</code>';
			$content .= '</table>';

			$content .= '<h2>'._L('Description').'</h2>%%DESC%%';
			if ($this->userHasWriteRights()) {
				$content .= '<h2>'._L('Create or change subsequent objects').'</h2>';
			} else {
				$content .= '<h2>'._L('Subsequent objects').'</h2>';
			}
			$content .= '%%CRUD%%';
		}
	}

	# ---

	public function chunkedNotation($withAbbr=true) {
		$curid = self::root().$this->aid;

		$res = OIDplus::db()->query("select id, title from ###objects where id = ?", array($curid));
		if (!$res->any()) return $this->aid;

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

		$full = self::root().$this->aid;
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

	public function one_up() {
		return OIDplusObject::parse($this->ns().':'.substr($this->aid,0,strlen($this->aid)-1));
	}

	public function distance($to) {
		if (!is_object($to)) $to = OIDplusObject::parse($to);
		if (!($to instanceof $this)) return false;

		$a = $to->aid;
		$b = $this->aid;

		$ary = $a;
		$bry = $b;

		$min_len = min(strlen($ary), strlen($bry));

		for ($i=0; $i<$min_len; $i++) {
			if ($ary[$i] != $bry[$i]) return false;
		}

		return strlen($ary) - strlen($bry);
	}

	public function getDirectoryName() {
		if ($this->isRoot()) return $this->ns();
		return $this->ns().'_'.$this->nodeId(false); // safe, because there are only AIDs
	}

	public static function treeIconFilename($mode) {
		return 'img/'.$mode.'_icon16.png';
	}
}
