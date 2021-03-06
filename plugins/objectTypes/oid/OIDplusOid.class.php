<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2021 Daniel Marschall, ViaThinkSoft
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
		if ($namespace !== 'oid') return false;
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
		return 'oid:';
	}

	public function isRoot() {
		return $this->oid == '';
	}

	public function nodeId($with_ns=true) {
		return $with_ns ? 'oid:'.$this->oid : $this->oid;
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

	public function crudInsertPrefix() {
		return '';
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

	public function getContentPage(&$title, &$content, &$icon) {
		$icon = file_exists(__DIR__.'/icon_big.png') ? 'plugins/objectTypes/'.basename(__DIR__).'/icon_big.png' : '';

		if ($this->isRoot()) {
			$title = OIDplusOid::objectTypeTitle();

			$res = OIDplus::db()->query("select id from ###objects where parent = ?", array(self::root()));
			if ($res->num_rows() > 0) {
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

			$content = '<h2>'._L('Technical information').'</h2>'.$this->oidInformation().
			           '<h2>'._L('Description').'</h2>%%DESC%%'.
			           '<h2>'._L('Registration Authority').'</h2>%%RA_INFO%%';

			if (!$this->isLeafNode()) {
				if ($this->userHasWriteRights()) {
					$content .= '<h2>'._L('Create or change subsequent objects').'</h2>';
				} else {
					$content .= '<h2>'._L('Subsequent objects').'</h2>';
				}
				$content .= '%%CRUD%%';
			}
		}
	}

	# ---

	public function isWeid($allow_root) {
		$weid = WeidOidConverter::oid2weid($this->getDotNotation());
		if (!$allow_root && ($weid === 'weid:4')) return false;
		return $weid !== false;
	}

	public function weidArc() {
		$weid = WeidOidConverter::oid2weid($this->getDotNotation());
		if ($weid === false) return false;
		list($ns,$weid) = explode(':', $weid, 2);
		$x = explode('-', $weid);
		if (count($x) < 2) return ''; // WEID root arc. Has no name
		return $x[count($x)-2];
	}

	public function getWeidNotation($withAbbr=true) {
		$weid = WeidOidConverter::oid2weid($this->getDotNotation());
		if ($withAbbr) {
			list($ns,$weid) = explode(':', $weid);
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
			$weid = '<abbr title="'._L('Root arc').': 1.3.6.1.4.1.37553.8">' . $ns . '</abbr>:' . implode('-',$weid_arcs);
		}
		return $weid;
	}

	private function oidInformation() {
		$out = array();
		$out[] = _L('Dot notation').': <code>' . $this->getDotNotation() . '</code>';
		$out[] = _L('ASN.1 notation').': <code>' . $this->getAsn1Notation(true) . '</code>';
		$out[] = _L('OID-IRI notation').': <code>' . $this->getIriNotation(true) . '</code>';
		if ($this->isWeid(true)) {
			$out[] = _L('WEID notation').': <code>' . $this->getWeidNotation() . '</code>';
		}
		return '<p>'.implode('<br>',$out).'</p>';
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

		$maxlen = OIDplus::baseConfig()->getValue('LIMITS_MAX_ID_LENGTH')-strlen('oid:');
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

		if (is_null($parent)) $parent = OIDplusOid::parse('oid:');

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
			$res = OIDplus::db()->query("select name, standardized from ###asn1id where oid = ? order by lfd", array('oid:'.implode('.',$arcs)));

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

		return "{ $asn1_notation }";
	}

	public function getIriNotation($withAbbr=true) {
		$iri_notation = '';
		$arcs = explode('.', $this->oid);

		foreach ($arcs as $arc) {
			$res = OIDplus::db()->query("select name, longarc from ###iri where oid = ? order by lfd", array('oid:'.implode('.',$arcs)));

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
		if ($res->num_rows() > 0) return true;

		$res = OIDplus::db()->query("select oid from ###iri where oid = ? and well_known = ?", array("oid:".$this->oid,true));
		if ($res->num_rows() > 0) return true;

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
			$ids[] = new OIDplusAltId('guid', $uuid, _L('GUID representation of this OID'));
		}
		$ids[] = new OIDplusAltId('guid', gen_uuid_md5_namebased(UUID_NAMEBASED_NS_OID, $this->oid), _L('Name based version 3 / MD5 UUID with namespace %1','UUID_NAMEBASED_NS_OID'));
		$ids[] = new OIDplusAltId('guid', gen_uuid_sha1_namebased(UUID_NAMEBASED_NS_OID, $this->oid), _L('Name based version 5 / SHA1 UUID with namespace %1','UUID_NAMEBASED_NS_OID'));
		return $ids;
	}

	public function getDirectoryName() {
		if ($this->isRoot()) return $this->ns();
		$oid = $this->nodeId(false);
		return $this->ns().'_'.str_replace('.', '_', $oid);
	}
}
