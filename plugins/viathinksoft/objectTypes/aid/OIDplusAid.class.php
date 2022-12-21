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

namespace ViaThinkSoft\OIDplus;

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
		return _L('Application Identifier (ISO/IEC 7816)');
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
		$str = str_replace(':','',$str);

		if (!preg_match('@^[0-9a-fA-F]+$@', $str, $m)) {
			throw new OIDplusException(_L('AID part needs to be hexadecimal'));
		}

		if (strlen($this->nodeId(false).$str) > 32) {
			throw new OIDplusException(_L('An AID has a maximum length of 16 bytes'));
		}

		// removed, because for D2 76 00 01 86 F... it makes sense to have your root (which is inside a foreign RID) being your OIDplus root
		/*
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
		*/

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
		// We don't know when an AID is "leaf", because an AID can have an arbitary length <= 16 Bytes.
		// But if it is 16 bytes long (32 nibbles), then we are 100% certain that it is a leaf node.
		return (strlen($this->nodeId(false)) == 32);
	}

	public function getContentPage(&$title, &$content, &$icon) {
		$icon = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

		if ($this->isRoot()) {
			$title = OIDplusAid::objectTypeTitle();

			$res = OIDplus::db()->query("select * from ###objects where parent = ?", array(self::root()));
			if ($res->any()) {
				$content  = '<p>'._L('Please select an item in the tree view at the left to show its contents.').'</p>';
			} else {
				$content  = '<p>'._L('Currently, no Application Identifiers are registered in the system.').'</p>';
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
			$tmp = preg_replace('@(warning|invalid|error|illegal(&nbsp;usage){0,1})@i', '<span class="errortext">\\1</span>', $tmp);
			$tmp = preg_replace('@(\\\\\\d{3})@i', '<span class="specialhexchar">\\1</span>', $tmp);

			$content .= '<h2>'._L('Decoding').'</h2>';
			$content .= '<table border="0">';
			$content .= '<code>'.$tmp.'</code>';
			$content .= '</table>';

			$content .= '<h2>'._L('Description').'</h2>%%DESC%%';
			if ($this->userHasWriteRights()) {
				$content .= '<h2>'._L('Create or change subordinate objects').'</h2>';
			} else {
				$content .= '<h2>'._L('Subordinate objects').'</h2>';
			}
			$content .= '%%CRUD%%';
		}
	}

	# ---

	public function chunkedNotation($withAbbr=true) {
		$curid = self::root().$this->aid;

		$obj = OIDplusObject::findFitting($curid);
		if (!$obj) return $this->aid;

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

		$full = self::root().$this->aid;
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

	public function getAltIds() {
		if ($this->isRoot()) return array();
		$ids = parent::getAltIds();

		$aid = $this->nodeId(false);
		$aid = strtoupper($aid);

		// ViaThinkSoft "Foreign" AIDs

		// (VTS F0) IANA PEN + PIX
		// Resolve only if there is no PIX
		if (str_starts_with($aid,'D276000186F0')) {
			$rest = substr($aid,strlen('D276000186F0'));
			$p = strpos($rest,'F');
			if ($p !== false) {
				$pen = substr($rest,0,$p);
				$pix = substr($rest,$p+1);
			} else {
				$pen = $rest;
				$pix = '';
			}
			if (($pix === '') && preg_match('/^[0-9]+$/',$pen,$m)) {
				$oid = '1.3.6.1.4.1.'.$pen;
				$ids[] = new OIDplusAltId('oid', $oid, _L('Object Identifier (OID)'));
				$ids[] = new OIDplusAltId('iana-pen', $pen, _L('IANA Private Enterprise Number (PEN)'));
			}
		}

		// (VTS F1) ViaThinkSoft FreeOID + PIX
		// Resolve only if there is no PIX
		if (str_starts_with($aid,'D276000186F1')) {
			$rest = substr($aid,strlen('D276000186F1'));
			$p = strpos($rest,'F');
			if ($p !== false) {
				$number = substr($rest,0,$p);
				$pix = substr($rest,$p+1);
			} else {
				$number = $rest;
				$pix = '';
			}
			if (($pix === '') && preg_match('/^[0-9]+$/',$number,$m)) {
				$oid = '1.3.6.1.4.1.37476.9000.'.$number;
				$ids[] = new OIDplusAltId('oid', $oid, _L('Object Identifier (OID)'));
			}
		}

		// (VTS F2) MAC address + PIX
		// Resolve only if there is no PIX
		if (str_starts_with($aid,'D276000186F2')) {
			$rest = substr($aid,strlen('D276000186F2'));
			if (strlen($rest) == 12) {
				$mac = $rest;
				$ids[] = new OIDplusAltId('mac', $mac, _L('MAC address'));
			}
		}

		// (VTS F3) USB-IF VendorID + PIX
		// Resolve only if there is no PIX
		if (str_starts_with($aid,'D276000186F3')) {
			$rest = substr($aid,strlen('D276000186F3'));
			if (strlen($rest) == 4) {
				$vid = $rest;
				$ids[] = new OIDplusAltId('usb-vendor-id', $vid, _L('USB-IF (usb.org) VendorID'));
			}
		}

		// (VTS F4) D-U-N-S number + PIX
		// Resolve only if there is no PIX
		if (str_starts_with($aid,'D276000186F4')) {
			$rest = substr($aid,strlen('D276000186F4'));
			$p = strpos($rest,'F');
			if ($p !== false) {
				$duns = substr($rest,0,$p);
				$pix = substr($rest,$p+1);
			} else {
				$duns = $rest;
				$pix = '';
			}
			if (($pix === '') && preg_match('/^[0-9]+$/',$duns,$m)) {
				$ids[] = new OIDplusAltId('duns', $duns, _L('Data Universal Numbering System (D-U-N-S)'));
			}
		}

		// (VTS F5) GS1 number + PIX
		// Resolve only if there is no PIX
		if (str_starts_with($aid,'D276000186F5')) {
			$rest = substr($aid,strlen('D276000186F5'));
			$p = strpos($rest,'F');
			if ($p !== false) {
				$gs1 = substr($rest,0,$p);
				$pix = substr($rest,$p+1);
			} else {
				$gs1 = $rest;
				$pix = '';
			}
			if (($pix === '') && preg_match('/^[0-9]+$/',$gs1,$m)) {
				$ids[] = new OIDplusAltId('gs1', $gs1, _L('GS1 Based IDs (GLN/GTIN/SSCC/...)'), ' ('._L('without check-digit').')');
			}
		}

		// (VTS F6) OID<->AID, no PIX
		if (str_starts_with($aid,'D276000186F6')) {
			$der = substr($aid,strlen('D276000186F6'));
			$len = strlen($der);
			if ($len%2 == 0) {
				$len /= 2;
				$len = str_pad("$len", 2, '0', STR_PAD_LEFT);
				$type = '06'; // absolute OID
				$der = "$type $len $der";
				$oid = \OidDerConverter::derToOID(\OidDerConverter::hexStrToArray($der));
				if ($oid) {
					$oid = ltrim($oid,'.');
					$ids[] = new OIDplusAltId('oid', $oid, _L('Object Identifier (OID)'));
				}
			}
		}

		// The case E8... (Standard OID 1.0) doesn't need to be addressed here, because it is already shown in the AID decoder (and it is ambiguous since DER and PIX are mixed)
		// TODO: If it has no pix, then resolve it !!! but how do we know if there is a PIX or a part ID ?

		return $ids;
	}

	public function getDirectoryName() {
		if ($this->isRoot()) return $this->ns();
		return $this->ns().'_'.$this->nodeId(false); // safe, because there are only AIDs
	}

	public static function treeIconFilename($mode) {
		return 'img/'.$mode.'_icon16.png';
	}
}
