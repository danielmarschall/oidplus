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

if (!defined('IN_OIDPLUS')) die();

class OIDplusOid extends OIDplusObject {
	private $oid;

	public function __construct($oid) {
		$bak_oid = $oid;

		$oid = sanitizeOID($oid, 'auto');
		if ($oid === false) {
			throw new Exception("Invalid OID '$bak_oid'");
		}

		if (($oid != '') && (!oid_valid_dotnotation($oid, false, true, 0))) {
			// avoid OIDs like 3.0
			throw new Exception("Invalid OID '$bak_oid'");
		}

		$this->oid = $oid;
	}

	public static function parse($node_id) {
		@list($namespace, $oid) = explode(':', $node_id, 2);
		if ($namespace !== 'oid') return false;
		return new self($oid);
	}

	public static function objectTypeTitle() {
		return "Object Identifier (OID)";
	}

	public static function objectTypeTitleShort() {
		return "OID";
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

	public function nodeId() {
		return 'oid:'.$this->oid;
	}

	public function addString($str) {
		if (!$this->isRoot()) {
			if (strpos($str,'.') !== false) die("Please only submit one arc (not an absolute OID or multiple arcs).");
		}
		return $this->appendArcs($str)->nodeId();
	}

	public function crudShowId(OIDplusObject $parent) {
		return $this->deltaDotNotation($parent);
	}

	public function crudInsertPrefix() {
		return '';
	}

	public function jsTreeNodeName(OIDplusObject $parent = null) {
		if ($parent == null) return $this->objectTypeTitle();
		return $this->viewGetArcAsn1s($parent);
	}

	public function defaultTitle() {
		return 'OID ' . $this->oid;
	}

	public function isLeafNode() {
		return false;
	}

	public function getContentPage(&$title, &$content, &$icon) {
		$icon = file_exists(__DIR__.'/icon_big.png') ? 'plugins/objectTypes/'.basename(__DIR__).'/icon_big.png' : '';

		if ($this->isRoot()) {
			$title = OIDplusOid::objectTypeTitle();

			$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where parent = '".OIDplus::db()->real_escape_string(self::root())."'");
			if (OIDplus::db()->num_rows($res) > 0) {
				$content = 'Please select an OID in the tree view at the left to show its contents.';
			} else {
				$content = 'Currently, no OID is registered in the system.';
			}

			if (!$this->isLeafNode()) {
				if (OIDplus::authUtils()::isAdminLoggedIn()) {
					$content .= '<h2>Manage your root OIDs</h2>';
				} else {
					$content .= '<h2>Root OIDs</h2>';
				}
				$content .= '%%CRUD%%';
			}
		} else {
			$content = "<h2>Technical information</h2>".$this->oidInformation().
			           "<h2>Description</h2>%%DESC%%".
			           "<h2>Registration Authority</h2>%%RA_INFO%%";

			if (!$this->isLeafNode()) {
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

	private function oidInformation() {
		$weid = WeidOidConverter::oid2weid($this->getDotNotation());
		$weid = ($weid === false) ? "" : "<br>WEID notation: <code>" . htmlentities($weid) . "</code>";
		return "<p>Dot notation: <code>" . $this->getDotNotation() . "</code><br>" .
		       "ASN.1 notation: <code>{ " . $this->getAsn1Notation() . " }</code><br>" .
		       "OID-IRI notation: <code>" . $this->getIriNotation() . "</code><br>" .
		       "SHA1 namebased UUID: <code>".gen_uuid_sha1_namespace(UUID_NAMEBASED_NS_OID, $this->getDotNotation())."</code><br>" .
		       "MD5 namebased UUID: <code>".gen_uuid_md5_namespace(UUID_NAMEBASED_NS_OID, $this->getDotNotation())."</code>$weid</p>";
	}

	public function __clone() {
		return new self($this->oid);
	}

	public function appendArcs(String $arcs) {
		$out = clone $this;

		if ($out->isRoot()) {
			$out->oid .= $arcs;
		} else {
			$out->oid .= '.' . $arcs;
		}

		$bak_oid = $out->oid;
		$out->oid = sanitizeOID($out->oid);
		if ($out->oid === false) throw new Exception("$bak_oid is not a valid OID!");

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
			$res2 = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."asn1id where oid = '".OIDplus::db()->real_escape_string("oid:".$this->oid)."' order by lfd");
			while ($row2 = OIDplus::db()->fetch_array($res2)) {
				$asn_ids[] = $row2['name'].'('.$part.')';
			}
		}

		if (count($asn_ids) == 0) $asn_ids = array($part);
		return implode($asn_ids, $separator);
	}

	public function getAsn1Notation($withAbbr=true) {
		$asn1_notation = '';
		$arcs = explode('.', $this->oid);

		foreach ($arcs as $arc) {
			$res = OIDplus::db()->query("select name, standardized from ".OIDPLUS_TABLENAME_PREFIX."asn1id where oid = '".OIDplus::db()->real_escape_string('oid:'.implode('.',$arcs))."' order by lfd");

			$names = array();
			while ($row = OIDplus::db()->fetch_array($res)) {
				$names[] = $row['name']."(".end($arcs).")";
				if ($row['standardized']) {
					$names[] = $row['name'];
				}
			}

			$numeric = array_pop($arcs);
			if (count($names) > 1) {
				$first_name = array_shift($names);
				$abbr = 'Other identifiers:&#10;      '.implode('&#10;      ',$names);
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

		return $asn1_notation;
	}

	public function getIriNotation($withAbbr=true) {
		$iri_notation = '';
		$arcs = explode('.', $this->oid);

		foreach ($arcs as $arc) {
			$res = OIDplus::db()->query("select name, longarc from ".OIDPLUS_TABLENAME_PREFIX."iri where oid = '".OIDplus::db()->real_escape_string('oid:'.implode('.',$arcs))."' order by lfd");

			$is_longarc = false;
			$names = array();
			while ($row = OIDplus::db()->fetch_array($res)) {
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
				$abbr = 'Other identifiers:&#10;      '.implode('&#10;      ',$names).'&#10;Numeric value: '.$numeric;
				$iri_notation = $withAbbr ? '<abbr title="'.$abbr.'">'.$first_name.'</abbr>/'.$iri_notation : $first_name.'/'.$iri_notation;
			} else if (count($names) > 1) {
				$first_name = array_shift($names);
				$abbr = 'Numeric value: '.array_shift($names);
				$iri_notation = $withAbbr ? '<abbr title="'.$abbr.'">'.$first_name.'</abbr>/'.$iri_notation : $first_name.'/'.$iri_notation;
			} else if (count($names) == 1) {
				$iri_notation = array_shift($names) . '/' . $iri_notation;
			}

			if ($is_longarc) break; // we don't write /ITU-T/ at the beginning, when /ITU-T/xxx is a long arc
		}
		$iri_notation = '/' . substr($iri_notation, 0, strlen($iri_notation)-1);

		return $iri_notation;
	}

	public function getDotNotation() {
		return $this->oid;
	}

	public function isWellKnown() {
		$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."asn1id where oid = '".OIDplus::db()->real_escape_string("oid:".$this->oid)."' and well_known = 1");
		if (OIDplus::db()->num_rows($res) > 0) return true;

		$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."iri where oid = '".OIDplus::db()->real_escape_string("oid:".$this->oid)."' and well_known = 1");
		if (OIDplus::db()->num_rows($res) > 0) return true;

		return false;
	}

	public function replaceAsn1Ids($demandedASN1s=array(), $simulate=false) {
		if ($this->isWellKnown()) {
			throw new Exception("OID ".$this->oid." is a 'well-known' OID. Its identifiers cannot be changed.");
		}

		// First do a few checks
		foreach ($demandedASN1s as &$asn1) {
			$asn1 = trim($asn1);

			// Validate identifier
			if (!oid_id_is_valid($asn1)) throw new Exception("'$asn1' is not a valid ASN.1 identifier!");

			// Check if the (real) parent has any conflict
			$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."asn1id where name = '".OIDplus::db()->real_escape_string($asn1)."'");
			while ($row = OIDplus::db()->fetch_array($res)) {
				$check_oid = OIDplusOid::parse($row['oid'])->oid;
				if ((oid_up($check_oid) === oid_up($this->oid)) && // same parent
				   ($check_oid !== $this->oid))                    // different OID
				{
					throw new Exception("ASN.1 identifier '$asn1' is already used by another OID ($check_oid)");
				}
			}
		}

		// Now do the real replacement
		if (!$simulate) {
			OIDplus::db()->query("delete from ".OIDPLUS_TABLENAME_PREFIX."asn1id where oid = '".OIDplus::db()->real_escape_string("oid:".$this->oid)."'");
			foreach ($demandedASN1s as &$asn1) {
				if (!OIDplus::db()->query("insert into ".OIDPLUS_TABLENAME_PREFIX."asn1id (oid, name) values ('".OIDplus::db()->real_escape_string("oid:".$this->oid)."', '".OIDplus::db()->real_escape_string($asn1)."')")) {
					throw new Exception("Insertion of ASN.1 ID $asn1 to OID ".$this->oid." failed!");
				}
			}
		}
	}

	public function replaceIris($demandedIris=array(), $simulate=false) {
		if ($this->isWellKnown()) {
			throw new Exception("OID ".$this->oid." is a 'well-known' OID. Its identifiers cannot be changed.");
		}

		// First do a few checks
		foreach ($demandedIris as &$iri) {
			$iri = trim($iri);

			// Validate identifier
			if (!iri_arc_valid($iri, false)) throw new Exception("'$iri' is not a valid IRI!");

			// Check if the (real) parent has any conflict
			$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."iri where name = '".OIDplus::db()->real_escape_string($iri)."'");
			while ($row = OIDplus::db()->fetch_array($res)) {
				$check_oid = OIDplusOid::parse($row['oid'])->oid;
				if ((oid_up($check_oid) === oid_up($this->oid)) && // same parent
				   ($check_oid !== $this->oid))                    // different OID
				{
					throw new Exception("IRI '$iri' is already used by another OID ($check_oid)");
				}
			}
		}

		// Now do the real replacement
		if (!$simulate) {
			OIDplus::db()->query("delete from ".OIDPLUS_TABLENAME_PREFIX."iri where oid = '".OIDplus::db()->real_escape_string("oid:".$this->oid)."'");
			foreach ($demandedIris as &$iri) {
				if (!OIDplus::db()->query("insert into ".OIDPLUS_TABLENAME_PREFIX."iri (oid, name) values ('".OIDplus::db()->real_escape_string("oid:".$this->oid)."', '".OIDplus::db()->real_escape_string($iri)."')")) {
					throw new Exception("Insertion of IRI $iri to OID ".$this->oid." failed!");
				}
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
}

OIDplus::registerObjectType('OIDplusOid');
