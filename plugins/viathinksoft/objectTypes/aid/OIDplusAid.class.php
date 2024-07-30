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

namespace ViaThinkSoft\OIDplus\Plugins\ObjectTypes\AID;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusAltId;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusObject;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusAid extends OIDplusObject {
	/**
	 * @var string
	 */
	private $aid;

	/**
	 * @param string $aid
	 */
	public function __construct(string $aid) {
		// TODO: syntax checks
		$this->aid = $aid;
	}

	/**
	 * @param string $node_id
	 * @return OIDplusAid|null
	 */
	public static function parse(string $node_id): ?OIDplusAid {
		@list($namespace, $aid) = explode(':', $node_id, 2);
		if ($namespace !== self::ns()) return null;
		return new self($aid);
	}

	/**
	 * @return string
	 */
	public static function objectTypeTitle(): string {
		return _L('Application Identifier (ISO/IEC 7816)');
	}

	/**
	 * @return string
	 */
	public static function objectTypeTitleShort(): string {
		return _L('AID');
	}

	/**
	 * @return string
	 */
	public static function ns(): string {
		return 'aid';
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
		return $this->aid == '';
	}

	/**
	 * @param bool $with_ns
	 * @return string
	 */
	public function nodeId(bool $with_ns=true): string {
		return $with_ns ? self::root().$this->aid : $this->aid;
	}

	/**
	 * @param string $str
	 * @return string
	 * @throws OIDplusException
	 */
	public function addString(string $str): string {
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
	public function jsTreeNodeName(?OIDplusObject $parent=null): string {
		if ($parent == null) return $this->objectTypeTitle();
		return substr($this->nodeId(), strlen($parent->nodeId()));
	}

	/**
	 * @return string
	 */
	public function defaultTitle(): string {
		//return $this->aid;
		return rtrim(chunk_split($this->aid, 2, ' '), ' ');
	}

	/**
	 * @return bool
	 */
	public function isLeafNode(): bool {
		// We don't know when an AID is "leaf", because an AID can have an arbitary length <= 16 Bytes.
		// But if it is 16 bytes long (32 nibbles), then we are 100% certain that it is a leaf node.
		return (strlen($this->nodeId(false)) == 32);
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
			$tmp = preg_replace('@(warning|invalid|error|illegal(&nbsp;usage){0,1})@i', '<span class="aid_decoder_errortext">\\1</span>', $tmp);

			# TODO: am besten farbmarkierung innerhalb c_literal_machen ? mit <abbr> und dann <abbr> irgendwie behandeln?
			$tmp = preg_replace('@(\\\\\\d{3})@i', '<span class="aid_decoder_specialhexchar">\\1</span>', $tmp);
			$tmp = preg_replace('@(\\\\x[0-9A-Fa-f]{2})@i', '<span class="aid_decoder_specialhexchar">\\1</span>', $tmp);

			$content .= '<h2>'._L('Decoding').'</h2>';
			$content .= '<table border="0">';
			$content .= '<div style="overflow:auto;white-space:nowrap"><code>'.$tmp.'</code></div>';
			$content .= '</table>';

			if ($this->userHasParentalWriteRights()) {
				$content .= '<h2>'._L('Superior RA Allocation Info').'</h2>%%SUPRA%%';
			}

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

	# ---

	/**
	 * @param bool $withAbbr
	 * @return string
	 * @throws OIDplusException
	 */
	public function chunkedNotation(bool $withAbbr=true): string {
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

	/**
	 * @return OIDplusAid|null
	 */
	public function one_up(): ?OIDplusAid {
		return self::parse($this->ns().':'.substr($this->aid,0,strlen($this->aid)-1));
	}

	/**
	 * @param OIDplusObject|string $to
	 * @return int|null
	 */
	public function distance($to): ?int {
		if (!is_object($to)) $to = OIDplusObject::parse($to);
		if (!$to) return null;
		if (!($to instanceof $this)) return null;

		$a = $to->aid;
		$b = $this->aid;

		$ary = $a;
		$bry = $b;

		$min_len = min(strlen($ary), strlen($bry));

		for ($i=0; $i<$min_len; $i++) {
			if ($ary[$i] != $bry[$i]) return null;
		}

		return strlen($ary) - strlen($bry);
	}

	/**
	 * @return array|OIDplusAltId[]
	 * @throws OIDplusException
	 */
	public function getAltIds(): array {
		if ($this->isRoot()) return array();
		$ids = parent::getAltIds();

		$aid = $this->nodeId(false);
		$aid = strtoupper($aid);

		// ViaThinkSoft proprietary AIDs

		// (VTS B1) Members
		if ($aid == 'D276000186B1') {
			$oid = '1.3.6.1.4.1.37476.1';
			$ids[] = new OIDplusAltId('oid', $oid, _L('Object Identifier (OID)'));
		}

		if (preg_match('@^D276000186B1(....)$@', $aid, $m)) {
			$oid = '1.3.6.1.4.1.37476.1.'.ltrim($m[1],'0');
			$ids[] = new OIDplusAltId('oid', $oid, _L('Object Identifier (OID)'));
		}

		// (VTS B2) Products
		if ($aid == 'D276000186B2') {
			$oid = '1.3.6.1.4.1.37476.2';
			$ids[] = new OIDplusAltId('oid', $oid, _L('Object Identifier (OID)'));
		}

		if (preg_match('@^D276000186B2(....)$@', $aid, $m)) {
			$oid = '1.3.6.1.4.1.37476.2.'.ltrim($m[1],'0');
			$ids[] = new OIDplusAltId('oid', $oid, _L('Object Identifier (OID)'));
		}

		// (VTS B2 00 05) OIDplus Information Objects AID
		// Attention: D276000186B20005 does NOT represent 1.3.6.1.4.1.37476.30.9
		//            because the mapping to OIDplus systems only applies for 00......-7F...... (31 bit hash)

		if (preg_match('@^D276000186B20005([0-7].......)$@', $aid, $m)) {
			$oid = '1.3.6.1.4.1.37476.30.9.'.hexdec($m[1]);
			$ids[] = new OIDplusAltId('oid', $oid, _L('Object Identifier (OID)'));
		}

		if (preg_match('@^D276000186B20005([0-7].......)([0-7].......)$@', $aid, $m)) {
			$oid = '1.3.6.1.4.1.37476.30.9.'.hexdec($m[1]).'.'.hexdec($m[2]);
			$ids[] = new OIDplusAltId('oid', $oid, _L('Object Identifier (OID)'));
		}

		// ViaThinkSoft "Example" AID

		if ($aid == 'D276000186E0') {
			// Note that the OID object type plugin also maps children of 2.999 to AID,
			// using a hash. But since this is not unique and cannot be reverted,
			// we cannot have an reverse lookup/map.
			$ids[] = new OIDplusAltId('oid', '2.999', _L('Object Identifier (OID)'), ' ('._L('Optional PIX allowed, without prefix').')');
		}

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

		// (VTS F2) MAC address (EUI/ELI/...) + PIX
		// Resolve only if there is no PIX
		if (str_starts_with($aid,'D276000186F2')) {
			$size_nibble = substr($aid,strlen('D276000186F2'),1);
			if ($size_nibble != '') {
				$mac = substr($aid, strlen('D276000186F2'.$size_nibble), hexdec($size_nibble) + 1);
				$test_aid = 'D276000186F2'.$size_nibble.$mac;
				if (strlen($test_aid)%2 == 1) $test_aid .= 'F'; // padding
				if ($aid == $test_aid) {
					$mac_type = mac_type(str_pad($mac, 12, '0', STR_PAD_RIGHT));
					$ids[] = new OIDplusAltId('mac', $mac, $mac_type);
				}
			}
		}

		// (VTS F3 01) USB-IF VendorID + ProductID + PIX
		// Resolve only if there is no PIX
		if (str_starts_with($aid,'D276000186F301')) {
			$rest = substr($aid,strlen('D276000186F301'));
			if (strlen($rest) == 4) {
				$vid = $rest;
				$ids[] = new OIDplusAltId('usb-vendor-id', $vid, _L('USB-IF (usb.org) VendorID'));
			} else if (strlen($rest) == 8) {
				$vid_pid = substr($rest, 0, 4) . ':' . substr($rest, 4);;
				$ids[] = new OIDplusAltId('usb-vendor-product-id', $vid_pid, _L('USB-IF (usb.org) VendorID/ProductID'));
			}
		}

		// (VTS F3 02) PCI-SIG VendorID + ProductID + PIX
		// Resolve only if there is no PIX
		if (str_starts_with($aid,'D276000186F302')) {
			$rest = substr($aid,strlen('D276000186F302'));
			if (strlen($rest) == 4) {
				$vid = $rest;
				$ids[] = new OIDplusAltId('pci-vendor-id', $vid, _L('PCI-SIG (pcisig.com) VendorID'));
			} else if (strlen($rest) == 8) {
				$vid_pid = substr($rest, 0, 4) . ':' . substr($rest, 4);;
				$ids[] = new OIDplusAltId('pci-vendor-product-id', $vid_pid, _L('PCI-SIG (pcisig.com)VendorID/ProductID'));
			}
		}

		// (VTS F4 01) D-U-N-S number + PIX
		// Resolve only if there is no PIX
		if (str_starts_with($aid,'D276000186F401')) {
			$rest = substr($aid,strlen('D276000186F401'));
			$p = strpos($rest,'F');
			if ($p !== false) {
				$duns = substr($rest,0,$p);
				$pix = substr($rest,$p+1);
			} else {
				$duns = $rest;
				$pix = '';
			}
			if (($pix === '') && preg_match('/^[0-9]+$/',$duns,$m)) {
				$duns = substr($duns,0,2).'-'.substr($duns,2,3).'-'.substr($duns,5);
				$ids[] = new OIDplusAltId('duns', $duns, _L('Data Universal Numbering System (D-U-N-S)'));
			}
		}

		// (VTS F4 02) Ringgold ID + PIX
		// Resolve only if there is no PIX
		if (str_starts_with($aid,'D276000186F402')) {
			$rest = substr($aid,strlen('D276000186F402'));
			$p = strpos($rest,'F');
			if ($p !== false) {
				$number = substr($rest,0,$p);
				$pix = substr($rest,$p+1);
			} else {
				$number = $rest;
				$pix = '';
			}
			if (($pix === '') && preg_match('/^[0-9]+$/',$number,$m)) {
				$ids[] = new OIDplusAltId('rin', $number, _L('Ringgold ID'));
			}
		}

		// (VTS F4 03) DOI + PIX
		// Resolve only if there is no PIX
		if (str_starts_with($aid,'D276000186F403')) {
			$rest = substr($aid,strlen('D276000186F403'));
			$p = strpos($rest,'F');
			if ($p !== false) {
				$number = substr($rest,0,$p);
				$pix = substr($rest,$p+1);
			} else {
				$number = $rest;
				$pix = '';
			}
			if (($pix === '') && preg_match('/^[0-9]+$/',$number,$m)) {
				$doi = "10.$number";
				$ids[] = new OIDplusAltId('doi', $doi, _L('Digital Object Identifier (DOI)'));
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

		// (VTS F7 01 X) ISNI compatible + PIX
		// Resolve only if there is no PIX
		if (str_starts_with($aid,'D276000186F701')) {
			$isni_subtype = substr($aid,strlen('D276000186F701'),1);
			$rest = substr($aid,strlen('D276000186F701')+1);
			if (strlen($rest) >= 13) {
				$isni_bin = substr($rest,0,13);
				$pix = substr($rest,14);
				if (($pix === '') && preg_match('/^[A-F0-9]+$/',$isni_bin,$m)) {
					// Example: "38D7EA4C67FFF" => "999999999999999"
					$isni_no_checksum = self::base_convert_bigint($isni_bin,16,10);
					$isni_dec = $isni_no_checksum . self::generateIsniCheckdigit($isni_no_checksum);
					// Now format to "9999-9999-9999-9999"
					$isni = rtrim(chunk_split(str_pad($isni_dec,16,'0',STR_PAD_LEFT),4,'-'),'-');
					if ($isni_subtype == '1') {
						$ids[] = new OIDplusAltId('isni', $isni, _L('International Standard Name Identifier (ISNI)'));
					} else if ($isni_subtype == '2') {
						$ids[] = new OIDplusAltId('orcid', $isni, _L('Open Researcher and Contributor ID (ORCID)'));
					} else {
						$ids[] = new OIDplusAltId('???', $isni, _L('Unknown ISNI compatible identifier'));
					}
				}
			}
		}


		// The case E8... (Standard OID 1.0) doesn't need to be addressed here, because it is already shown in the AID decoder (and it is ambiguous since DER and PIX are mixed)
		// TODO: If it has no pix, then resolve it !!! but how do we know if there is a PIX or a part ID ?

		return $ids;
	}

	/**
	  * Generates check digit as per ISO 7064 11,2.
	  *
	  */
	private static function generateIsniCheckdigit(string $baseDigits) {
	    $total = 0;
	    for ($i = 0; $i < strlen($baseDigits); $i++) {
	        $digit = (int)$baseDigits[$i];
	        $total = ($total + $digit) * 2;
	    }
	    $remainder = $total % 11;
	    $result = (12 - $remainder) % 11;
	    return $result == 10 ? "X" : $result;
	}
	//assert(generateIsniCheckdigit('000000010929605') == '3');
	//asserr(generateIsniCheckdigit('000000012281955') == 'X');

	/**
	 * @param string $numstring
	 * @param int $frombase
	 * @param int $tobase
	 * @return string
	 */
	protected static function base_convert_bigint(string $numstring, int $frombase, int $tobase): string {
		// TODO: put this (used here and in OID WeidConverter) to functions.inc.php ?

		$frombase_str = '';
		for ($i=0; $i<$frombase; $i++) {
			$frombase_str .= strtoupper(base_convert((string)$i, 10, 36));
		}

		$tobase_str = '';
		for ($i=0; $i<$tobase; $i++) {
			$tobase_str .= strtoupper(base_convert((string)$i, 10, 36));
		}

		$length = strlen($numstring);
		$result = '';
		$number = array();
		for ($i = 0; $i < $length; $i++) {
			$number[$i] = stripos($frombase_str, $numstring[$i]);
		}
		do { // Loop until whole number is converted
			$divide = 0;
			$newlen = 0;
			for ($i = 0; $i < $length; $i++) { // Perform division manually (which is why this works with big numbers)
				$divide = $divide * $frombase + $number[$i];
				if ($divide >= $tobase) {
					$number[$newlen++] = (int)($divide / $tobase);
					$divide = $divide % $tobase;
				} else if ($newlen > 0) {
					$number[$newlen++] = 0;
				}
			}
			$length = $newlen;
			$result = $tobase_str[$divide] . $result; // Divide is basically $numstring % $tobase (i.e. the new character)
		}
		while ($newlen != 0);

		return $result;
	}

	/**
	 * @return string
	 */
	public function getDirectoryName(): string {
		if ($this->isRoot()) return $this->ns();
		return $this->ns().'_'.$this->nodeId(false); // safe, because there are only AIDs
	}

	/**
	 * @param string $mode
	 * @return string
	 */
	public static function treeIconFilename(string $mode): string {
		return 'img/'.$mode.'_icon16.png';
	}
}
