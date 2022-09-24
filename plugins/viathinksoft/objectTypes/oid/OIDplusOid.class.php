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

class OIDplusOid extends OIDplusObject {
	private $oid;

	public function __construct($oid) {
		$bak_oid = $oid;

		$oid = sanitizeOID($oid, 'auto');
		if ($oid === false) {
			throw new OIDplusException(_L('Invalid OID %1',$bak_oid));
		}

		if (($oid != '') && (!oid_valid_dotnotation($oid, false, true, 0))) {
			// avoid OIDs like 3.0
			throw new OIDplusException(_L('Invalid OID %1',$bak_oid));
		}

		$this->oid = $oid;
	}

	public static function parse($node_id) {
		@list($namespace, $oid) = explode(':', $node_id, 2);
		if ($namespace !== self::ns()) return false;
		return new self($oid);
	}

	public static function objectTypeTitle() {
		return _L('Object Identifier (OID)');
	}

	public static function objectTypeTitleShort() {
		return _L('OID');
	}

	public static function ns() {
		return 'oid';
	}

	public static function root() {
		return self::ns().':';
	}

	public function isRoot() {
		return $this->oid == '';
	}

	public function nodeId($with_ns=true) {
		return $with_ns ? self::root().$this->oid : $this->oid;
	}

	public function addString($str) {
		if (!$this->isRoot()) {
			if (strpos($str,'.') !== false) throw new OIDplusException(_L('Please only submit one arc (not an absolute OID or multiple arcs).'));
		}

		return $this->appendArcs($str)->nodeId();
	}

	public function crudShowId(OIDplusObject $parent) {
		if ($parent instanceof OIDplusOid) {
			return $this->deltaDotNotation($parent);
		}
	}

	public function jsTreeNodeName(OIDplusObject $parent = null) {
		if ($parent == null) return $this->objectTypeTitle();
		if ($parent instanceof OIDplusOid) {
			return $this->viewGetArcAsn1s($parent);
		} else {
			return '';
		}
	}

	public function defaultTitle() {
		return _L('OID %1',$this->oid);
	}

	public function isLeafNode() {
		return false;
	}

	private function getTechInfo() {
		$tech_info = array();

		$tmp = _L('Dot notation');
		$tmp = str_replace(explode(' ', $tmp, 2)[0], '<a href="http://oid-info.com/faq.htm#14" target="_blank">'.explode(' ', $tmp, 2)[0].'</a>', $tmp);
		$tech_info[$tmp] = $this->getDotNotation();

		$tmp = _L('ASN.1 notation');
		$tmp = str_replace(explode(' ', $tmp, 2)[0], '<a href="http://oid-info.com/faq.htm#17" target="_blank">'.explode(' ', $tmp, 2)[0].'</a>', $tmp);
		$tech_info[$tmp] = $this->getAsn1Notation();

		$tmp = _L('OID-IRI notation');
		$tmp = str_replace(explode(' ', $tmp, 2)[0], '<a href="http://oid-info.com/faq.htm#iri" target="_blank">'.explode(' ', $tmp, 2)[0].'</a>', $tmp);
		$tech_info[$tmp] = $this->getIriNotation();

		$tmp = _L('WEID notation');
		$tmp = str_replace(explode(' ', $tmp, 2)[0], '<a href="https://weid.info/" target="_blank">'.explode(' ', $tmp, 2)[0].'</a>', $tmp);
		$tech_info[$tmp] = $this->getWeidNotation();

		$tmp = _L('DER encoding');
		$tmp = str_replace(explode(' ', $tmp, 2)[0], '<a href="https://misc.daniel-marschall.de/asn.1/oid-converter/online.php" target="_blank">'.explode(' ', $tmp, 2)[0].'</a>', $tmp);
		$tech_info[$tmp] = str_replace(' ', ':', OidDerConverter::hexarrayToStr(OidDerConverter::oidToDER($this->nodeId(false))));

		return $tech_info;
	}

	protected function isClassCWeid() {
		$dist = oid_distance($this->oid, '1.3.6.1.4.1.37553.8');
		if ($dist === false) return false;
		return $dist >= 0;
	}

	public function getContentPage(&$title, &$content, &$icon) {
		if ($this->isClassCWeid()) {
			// TODO: Also change treeview menu mini-icon?
			$icon = file_exists(__DIR__.'/img/weid_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/weid_icon.png' : '';
		} else {
			$icon = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';
		}

		if ($this->isRoot()) {
			$title = OIDplusOid::objectTypeTitle();

			$res = OIDplus::db()->query("select id from ###objects where parent = ?", array(self::root()));
			if ($res->any()) {
				$content = _L('Please select an OID in the tree view at the left to show its contents.');
			} else {
				$content = _L('Currently, no OID is registered in the system.');
			}

			if (!$this->isLeafNode()) {
				if (OIDplus::authUtils()->isAdminLoggedIn()) {
					$content .= '<h2>'._L('Manage your root OIDs').'</h2>';
				} else {
					$content .= '<h2>'._L('Root OIDs').'</h2>';
				}
				$content .= '%%CRUD%%';
			}
		} else {
			$title = $this->getTitle();

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

			$content .= '<h2>'._L('Description').'</h2>%%DESC%%'.
			            '<h2>'._L('Registration Authority').'</h2>%%RA_INFO%%';

			if (!$this->isLeafNode()) {
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

	// Gets the last arc of an WEID
	public function weidArc() {
		// Dirty hack: We prepend '0.' in front of the OID to enforce the
		//             creation of a Class A weid (weid:root:) . Otherwise we could not
		//             get the hidden arc value "8" from "weid:4" (which is actually "weid:pen:SZ5-8-?"
		$weid = WeidOidConverter::oid2weid('0.'.$this->getDotNotation());
		if ($weid === false) return false;
		$ary = explode(':', $weid);
		$weid = array_pop($ary); // remove namespace and sub-namespace if existing
		$x = explode('-', $weid);
		if (count($x) < 2) return ''; // WEID root arc. Has no name
		return $x[count($x)-2];
	}

	public function getWeidNotation($withAbbr=true) {
		$weid = WeidOidConverter::oid2weid($this->getDotNotation());
		if ($withAbbr) {
			$ary = explode(':', $weid);
			$weid = array_pop($ary); // remove namespace and sub-namespace if existing
			$ns = implode(':', $ary).':';

			$weid_arcs = explode('-', $weid);
			foreach ($weid_arcs as $i => &$weid) {
				if ($i == count($weid_arcs)-1) {
					$weid = '<abbr title="'._L('weLuhn check digit').'">'.$weid.'</abbr>';
				} else {
					$oid_arcs = explode('.',$this->oid);
					$weid_num = $oid_arcs[(count($oid_arcs)-1)-(count($weid_arcs)-1)+($i+1)];
					if ($weid_num != $weid) {
						$weid = '<abbr title="'._L('Numeric value').': '.$weid_num.'">'.$weid.'</abbr>';
					}
				}
			}
			$base_arc = '???';
			if ($ns === 'weid:')      $base_arc = '1.3.6.1.4.1.37553.8';
			if ($ns === 'weid:pen:')  $base_arc = '1.3.6.1.4.1';
			if ($ns === 'weid:root:') $base_arc = _L('OID tree root');

			$weid = '<abbr title="'._L('Base OID').': '.$base_arc.'">' . rtrim($ns,':') . '</abbr>:' . implode('-',$weid_arcs);
		}
		return $weid;
	}

	public function appendArcs(String $arcs) {
		$out = new self($this->oid);

		if ($out->isRoot()) {
			$out->oid .= $arcs;
		} else {
			$out->oid .= '.' . $arcs;
		}

		$bak_oid = $out->oid;
		$out->oid = sanitizeOID($out->oid);
		if ($out->oid === false) throw new OIDplusException(_L('%1 is not a valid OID!',$bak_oid));

		$maxlen = OIDplus::baseConfig()->getValue('LIMITS_MAX_ID_LENGTH')-strlen(self::root());
		if (strlen($out->oid) > $maxlen) {
			throw new OIDplusException(_L('The resulting OID "%1" is too long (max allowed length: %2).',$out->oid,$maxlen));
		}

		$depth = 0;
		foreach (explode('.',$out->oid) as $arc) {
			if (strlen($arc) > OIDplus::baseConfig()->getValue('LIMITS_MAX_OID_ARC_SIZE')) {
				$maxlen = OIDplus::baseConfig()->getValue('LIMITS_MAX_OID_ARC_SIZE');
				throw new OIDplusException(_L('Arc "%1" is too long and therefore cannot be appended to the OID "%2" (max allowed arc size is "%3")',$arc,$this->oid,$maxlen));
			}
			$depth++;
		}
		if ($depth > OIDplus::baseConfig()->getValue('LIMITS_MAX_OID_DEPTH')) {
			$maxdepth = OIDplus::baseConfig()->getValue('LIMITS_MAX_OID_DEPTH');
			throw new OIDplusException(_L('OID %1 has too many arcs (current depth %2, max depth %3)',$out->oid,$depth,$maxdepth));
		}

		return $out;
	}

	public function deltaDotNotation(OIDplusOid $parent) {
		if (!$parent->isRoot()) {
			if (substr($this->oid, 0, strlen($parent->oid)+1) == $parent->oid.'.') {
				return substr($this->oid, strlen($parent->oid)+1);
			} else {
				return false;
			}
		} else {
			return $this->oid;
		}
	}

	public function viewGetArcAsn1s(OIDplusOid $parent=null, $separator = ' | ') {
		$asn_ids = array();

		if (is_null($parent)) $parent = OIDplusOid::parse(self::root());

		$part = $this->deltaDotNotation($parent);

		if (strpos($part, '.') === false) {
			$res2 = OIDplus::db()->query("select name from ###asn1id where oid = ? order by lfd", array("oid:".$this->oid));
			while ($row2 = $res2->fetch_array()) {
				$asn_ids[] = $row2['name'].'('.$part.')';
			}
		}

		if (count($asn_ids) == 0) $asn_ids = array($part);
		return implode($separator, $asn_ids);
	}

	public function getAsn1Notation($withAbbr=true) {
		$asn1_notation = '';
		$arcs = explode('.', $this->oid);

		foreach ($arcs as $arc) {
			$res = OIDplus::db()->query("select name, standardized from ###asn1id where oid = ? order by lfd", array(self::root().implode('.',$arcs)));

			$names = array();
			while ($row = $res->fetch_array()) {
				$names[] = $row['name']."(".end($arcs).")";
				if ($row['standardized']) {
					$names[] = $row['name'];
				}
			}

			$numeric = array_pop($arcs);
			if (count($names) > 1) {
				$first_name = array_shift($names);
				$abbr = _L('Other identifiers').':&#10;      '.implode('&#10;      ',$names);
				if ($withAbbr) {
					$asn1_notation = '<abbr title="'.$abbr.'">'.$first_name.'</abbr> '.$asn1_notation;
				} else {
					$asn1_notation = $first_name.' '.$asn1_notation;
				}
			} else if (count($names) == 1) {
				$asn1_notation = array_shift($names).' '.$asn1_notation;
			} else {
				$asn1_notation = $numeric.' '.$asn1_notation;
			}
		}

		return "{ ".trim($asn1_notation)." }";
	}

	public function getIriNotation($withAbbr=true) {
		$iri_notation = '';
		$arcs = explode('.', $this->oid);

		foreach ($arcs as $arc) {
			$res = OIDplus::db()->query("select name, longarc from ###iri where oid = ? order by lfd", array(self::root().implode('.',$arcs)));

			$is_longarc = false;
			$names = array();
			while ($row = $res->fetch_array()) {
				$is_longarc = $row['longarc'];
				$names[] = $row['name'];

				if ($is_longarc) {
					$names[] = 'Joint-ISO-ITU-T/'.$row['name']; // Long arcs can only be inside root OID 2
				}
			}

			$names[] = array_pop($arcs);
			if (count($names) > 2) {
				$first_name = array_shift($names);
				$numeric = array_pop($names);
				$abbr = _L('Other identifiers').':&#10;      '.implode('&#10;      ',$names).'&#10;'._L('Numeric value').': '.$numeric;
				$iri_notation = $withAbbr ? '<abbr title="'.$abbr.'">'.$first_name.'</abbr>/'.$iri_notation : $first_name.'/'.$iri_notation;
			} else if (count($names) > 1) {
				$first_name = array_shift($names);
				$abbr = _L('Numeric value').': '.array_shift($names);
				$iri_notation = $withAbbr ? '<abbr title="'.$abbr.'">'.$first_name.'</abbr>/'.$iri_notation : $first_name.'/'.$iri_notation;
			} else if (count($names) == 1) {
				$iri_notation = array_shift($names) . '/' . $iri_notation;
			}

			if ($is_longarc) break; // we don't write /ITU-T/ at the beginning, when /ITU-T/xyz is a long arc
		}
		$iri_notation = '/' . substr($iri_notation, 0, strlen($iri_notation)-1);

		return $iri_notation;
	}

	public function getDotNotation() {
		return $this->oid;
	}

	public function isWellKnown() {
		$res = OIDplus::db()->query("select oid from ###asn1id where oid = ? and well_known = ?", array("oid:".$this->oid,true));
		if ($res->any()) return true;

		$res = OIDplus::db()->query("select oid from ###iri where oid = ? and well_known = ?", array("oid:".$this->oid,true));
		if ($res->any()) return true;

		return false;
	}

	public function replaceAsn1Ids($demandedASN1s=array(), $simulate=false) {
		if ($this->isWellKnown()) {
			throw new OIDplusException(_L('OID "%1" is a "well-known" OID. Its identifiers cannot be changed.',$this->oid));
		}

		// First do a few checks
		foreach ($demandedASN1s as &$asn1) {
			$asn1 = trim($asn1);

			if (strlen($asn1) > OIDplus::baseConfig()->getValue('LIMITS_MAX_OID_ASN1_ID_LEN')) {
				$maxlen = OIDplus::baseConfig()->getValue('LIMITS_MAX_OID_ASN1_ID_LEN');
				throw new OIDplusException(_L('ASN.1 alphanumeric identifier "%1" is too long (max allowed length %2)',$asn1,$maxlen));
			}

			// Validate identifier
			if (!oid_id_is_valid($asn1)) throw new OIDplusException(_L('"%1" is not a valid ASN.1 identifier!',$asn1));

			// Check if the (real) parent has any conflict
			// Unlike IRI identifiers, ASN.1 identifiers may be used multiple times (not recommended), except if one of them is standardized
			$res = OIDplus::db()->query("select oid from ###asn1id where name = ? and standardized = ?", array($asn1,true));
			while ($row = $res->fetch_array()) {
				$check_oid = OIDplusOid::parse($row['oid'])->oid;
				if ((oid_up($check_oid) === oid_up($this->oid)) && // same parent
				   ($check_oid !== $this->oid))                    // different OID
				{
					throw new OIDplusException(_L('ASN.1 identifier "%1" is a standardized identifier belonging to OID %2',$asn1,$check_oid));
				}
			}
		}

		// Now do the real replacement
		if (!$simulate) {
			OIDplus::db()->query("delete from ###asn1id where oid = ?", array("oid:".$this->oid));
			foreach ($demandedASN1s as &$asn1) {
				OIDplus::db()->query("insert into ###asn1id (oid, name) values (?, ?)", array("oid:".$this->oid, $asn1));
			}
		}
	}

	public function replaceIris($demandedIris=array(), $simulate=false) {
		if ($this->isWellKnown()) {
			throw new OIDplusException(_L('OID "%1" is a "well-known" OID. Its identifiers cannot be changed.',$this->oid));
		}

		// First do a few checks
		foreach ($demandedIris as &$iri) {
			$iri = trim($iri);

			if (strlen($iri) > OIDplus::baseConfig()->getValue('LIMITS_MAX_OID_UNICODE_LABEL_LEN')) {
				$maxlen = OIDplus::baseConfig()->getValue('LIMITS_MAX_OID_UNICODE_LABEL_LEN');
				throw new OIDplusException(_L('Unicode label "%1" is too long (max allowed length %2)',$iri,$maxlen));
			}

			// Validate identifier
			if (!iri_arc_valid($iri, false)) throw new OIDplusException(_L('"%1" is not a valid IRI!',$iri));

			// Check if the (real) parent has any conflict
			$res = OIDplus::db()->query("select oid from ###iri where name = ?", array($iri));
			while ($row = $res->fetch_array()) {
				$check_oid = OIDplusOid::parse($row['oid'])->oid;
				if ((oid_up($check_oid) === oid_up($this->oid)) && // same parent
				   ($check_oid !== $this->oid))                    // different OID
				{
					throw new OIDplusException(_L('IRI "%1" is already used by another OID (%2)',$iri,$check_oid));
				}
			}
		}

		// Now do the real replacement
		if (!$simulate) {
			OIDplus::db()->query("delete from ###iri where oid = ?", array("oid:".$this->oid));
			foreach ($demandedIris as &$iri) {
				OIDplus::db()->query("insert into ###iri (oid, name) values (?, ?)", array("oid:".$this->oid, $iri));
			}
		}
	}

	public function one_up() {
		return self::parse(self::ns().':'.oid_up($this->oid));
	}

	public function distance($to) {
		if (!is_object($to)) $to = OIDplusObject::parse($to);
		if (!($to instanceof $this)) return false;
		return oid_distance($to->oid, $this->oid);
	}

	public function getAltIds() {
		if ($this->isRoot()) return array();
		$ids = parent::getAltIds();

		if ($uuid = oid_to_uuid($this->oid)) {
			// UUID-OIDs are representation of an UUID
			$ids[] = new OIDplusAltId('guid', $uuid, _L('GUID representation of this OID'));
		} else {
			// All other OIDs can be formed into an UUID by making them a namebased OID
			// You could theoretically also do this to an UUID-OID, but we exclude this case to avoid that users are confused
			$ids[] = new OIDplusAltId('guid', gen_uuid_md5_namebased(UUID_NAMEBASED_NS_OID, $this->oid), _L('Name based version 3 / MD5 UUID with namespace %1','UUID_NAMEBASED_NS_OID'));
			$ids[] = new OIDplusAltId('guid', gen_uuid_sha1_namebased(UUID_NAMEBASED_NS_OID, $this->oid), _L('Name based version 5 / SHA1 UUID with namespace %1','UUID_NAMEBASED_NS_OID'));
		}

		// (VTS F0) IANA PEN to AID Mapping (PIX allowed)
		$oid_parts = explode('.',$this->nodeId(false));
		if ((count($oid_parts) == 7) && ($oid_parts[0] == '1') && ($oid_parts[1] == '3') && ($oid_parts[2] == '6') && ($oid_parts[3] == '1') && ($oid_parts[4] == '4') && ($oid_parts[5] == '1')) {
			$pen = $oid_parts[6];
			$aid = 'D276000186F0'.$pen;
			if (strlen($aid)%2 == 1) $aid .= 'F';
			$aid_is_ok = aid_canonize($aid);
			if ($aid_is_ok) $ids[] = new OIDplusAltId('aid', $aid, _L('Application Identifier (ISO/IEC 7816)'), ' ('._L('Optional PIX allowed, with "FF" prefix').')');
			$ids[] = new OIDplusAltId('iana-pen', $pen, _L('IANA Private Enterprise Number (PEN)'));
		}

		// (VTS F1) FreeOID to AID Mapping (PIX allowed)
		$oid_parts = explode('.',$this->nodeId(false));
		if ((count($oid_parts) == 9) && ($oid_parts[0] == '1') && ($oid_parts[1] == '3') && ($oid_parts[2] == '6') && ($oid_parts[3] == '1') && ($oid_parts[4] == '4') && ($oid_parts[5] == '1') && ($oid_parts[6] == '37476') && ($oid_parts[7] == '9000')) {
			$number = $oid_parts[8];
			$aid = 'D276000186F1'.$number;
			if (strlen($aid)%2 == 1) $aid .= 'F';
			$aid_is_ok = aid_canonize($aid);
			if ($aid_is_ok) $ids[] = new OIDplusAltId('aid', $aid, _L('Application Identifier (ISO/IEC 7816)'), ' ('._L('Optional PIX allowed, with "FF" prefix').')');
		}

		// (VTS F6) Mapping OID-to-AID if possible
		try {
			$test_der = OidDerConverter::hexarrayToStr(OidDerConverter::oidToDER($this->nodeId(false)));
		} catch (Exception $e) {
			$test_der = '00'; // error, should not happen
		}
		if (substr($test_der,0,3) == '06 ') { // 06 = ASN.1 type of Absolute ID
			$oid_parts = explode('.',$this->nodeId(false));
			if (($oid_parts[0] == '2') && ($oid_parts[1] == '999')) {
				// Note that "ViaThinkSoft E0" AID are not unique!
				// OIDplus will use the relative DER of the 2.999.xx OID as PIX
				$aid_candidate = 'D2 76 00 01 86 E0 ' . substr($test_der, strlen('06 xx 88 37 ')); // Remove ASN.1 06=Type, xx=Length and the 2.999 arcs "88 37"
				$aid_is_ok = aid_canonize($aid_candidate);
				if (!$aid_is_ok) {
					// If DER encoding is not possible (too long), then we will use a 32 bit small hash.
					$small_hash = str_pad(dechex(smallhash($this->nodeId(false))),8,'0',STR_PAD_LEFT);
					$aid_candidate = 'D2 76 00 01 86 E0 ' . strtoupper(implode(' ',str_split($small_hash,2)));
					$aid_is_ok = aid_canonize($aid_candidate);
				}
				if ($aid_is_ok) $ids[] = new OIDplusAltId('aid', $aid_candidate, _L('Application Identifier (ISO/IEC 7816)'));
			} else if (($oid_parts[0] == '0') && ($oid_parts[1] == '4') && ($oid_parts[2] == '0') && ($oid_parts[3] == '127') && ($oid_parts[4] == '0') && ($oid_parts[5] == '7')) {
				// Illegal usage of E8 by German BSI, plus using E8+Len+OID instead of E8+OID like ISO does
				// PIX probably not used
				$aid_candidate = 'E8 '.substr($test_der, strlen('06 ')); // Remove ASN.1 06=Type
				$aid_is_ok = aid_canonize($aid_candidate);
				if ($aid_is_ok) $ids[] = new OIDplusAltId('aid', $aid_candidate, _L('Application Identifier (ISO/IEC 7816)'));
			} else if (($oid_parts[0] == '1') && ($oid_parts[1] == '0')) {
				// ISO Standard AID (OID 1.0.xx)
				// Optional PIX allowed
				$aid_candidate = 'E8 '.substr($test_der, strlen('06 xx ')); // Remove ASN.1 06=Type and xx=Length
				$aid_is_ok = aid_canonize($aid_candidate);
				if ($aid_is_ok) $ids[] = new OIDplusAltId('aid', $aid_candidate, _L('Application Identifier (ISO/IEC 7816)'), ' ('._L('Optional PIX allowed, without prefix').')');
			} else {
				// All other OIDs can be mapped using the "ViaThinkSoft F6" scheme, but only if the DER encoding is not too long
				// No PIX allowed
				$aid_candidate = 'D2 76 00 01 86 F6 '.substr($test_der, strlen('06 xx ')); // Remove ASN.1 06=Type and xx=Length
				$aid_is_ok = aid_canonize($aid_candidate);
				if ($aid_is_ok) $ids[] = new OIDplusAltId('aid', $aid_candidate, _L('Application Identifier (ISO/IEC 7816)'), ' ('._L('No PIX allowed').')');
			}
		}

		return $ids;
	}

	public function getDirectoryName() {
		if ($this->isRoot()) return $this->ns();
		$oid = $this->nodeId(false);
		return $this->ns().'_'.str_replace('.', '_', $oid);
	}

	public static function treeIconFilename($mode) {
		return 'img/'.$mode.'_icon16.png';
	}
}
