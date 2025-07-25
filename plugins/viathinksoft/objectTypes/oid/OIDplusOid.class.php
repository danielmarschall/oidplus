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

namespace ViaThinkSoft\OIDplus\Plugins\ObjectTypes\OID;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusAltId;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusObject;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusOid extends OIDplusObject {
	/**
	 * @var string
	 */
	private $oid;

	/**
	 * @param string $oid
	 * @throws OIDplusException
	 */
	public function __construct(string $oid) {
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

	/**
	 * @param string $node_id
	 * @return OIDplusOid|null
	 * @throws OIDplusException
	 */
	public static function parse(string $node_id): ?OIDplusOid {
		@list($namespace, $oid) = explode(':', $node_id, 2);
		if ($namespace !== self::ns()) return null;
		return new self($oid);
	}

	/**
	 * @return string
	 */
	public static function objectTypeTitle(): string {
		return _L('Object Identifier (OID)');
	}

	/**
	 * @return string
	 */
	public static function objectTypeTitleShort(): string {
		return _L('OID');
	}

	/**
	 * @return string
	 */
	public static function ns(): string {
		return 'oid';
	}

	/**
	 * @return array
	 */
	public static function urnNs(): array {
		return array('oid'); // 'oid' means 'urn:oid:'
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
		return $this->oid == '';
	}

	/**
	 * @param bool $with_ns
	 * @return string
	 */
	public function nodeId(bool $with_ns=true): string {
		return $with_ns ? self::root().$this->oid : $this->oid;
	}

	/**
	 * @param string $str
	 * @return string
	 * @throws OIDplusException
	 */
	public function addString(string $str): string {
		if (!$this->isRoot()) {
			if (strpos($str,'.') !== false) throw new OIDplusException(_L('Please only submit one arc (not an absolute OID or multiple arcs).'));
		}

		return $this->appendArcs($str)->nodeId();
	}

	/**
	 * @param OIDplusObject $parent
	 * @return string
	 */
	public function crudShowId(OIDplusObject $parent): string {
		if ($parent instanceof OIDplusOid) {
			return $this->deltaDotNotation($parent);
		} else {
			return '';
		}
	}

	/**
	 * @param OIDplusObject|null $parent
	 * @return string
	 * @throws OIDplusException
	 */
	public function jsTreeNodeName(?OIDplusObject $parent=null): string {
		if ($parent == null) return $this->objectTypeTitle();
		if ($parent instanceof OIDplusOid) {
			return $this->viewGetArcAsn1s($parent);
		} else {
			return '';
		}
	}

	/**
	 * @return string
	 */
	public function defaultTitle(): string {
		return _L('OID %1',$this->oid);
	}

	/**
	 * @return bool
	 */
	public function isLeafNode(): bool {
		return false;
	}

	/**
	 * @return string[]
	 * @throws OIDplusException
	 */
	private function getTechInfo(): array {
		$tech_info = array();

		$tmp = _L('Dot notation');
		$tmp = str_replace(explode(' ', $tmp, 2)[0], '<a href="https://www.oid-base.com/faq.htm#14" target="_blank">'.explode(' ', $tmp, 2)[0].'</a>', $tmp);
		$tech_info[$tmp] = $this->getDotNotation();

		$tmp = _L('ASN.1 notation');
		$tmp = str_replace(explode(' ', $tmp, 2)[0], '<a href="https://www.oid-base.com/faq.htm#17" target="_blank">'.explode(' ', $tmp, 2)[0].'</a>', $tmp);
		$tech_info[$tmp] = $this->getAsn1Notation();

		$tmp = _L('OID-IRI notation');
		$tmp = str_replace(explode(' ', $tmp, 2)[0], '<a href="https://www.oid-base.com/faq.htm#iri" target="_blank">'.explode(' ', $tmp, 2)[0].'</a>', $tmp);
		$tech_info[$tmp] = $this->getIriNotation();

		$tmp = _L('WEID notation');
		$tmp = str_replace(explode(' ', $tmp, 2)[0], '<a href="https://weid.info/spec.html" target="_blank">'.explode(' ', $tmp, 2)[0].'</a>', $tmp);
		$tech_info[$tmp] = $this->getWeidNotation(true);

		$tmp = _L('DER encoding');
		$tmp = str_replace(explode(' ', $tmp, 2)[0], '<a href="https://misc.daniel-marschall.de/asn.1/oid-converter/online.php" target="_blank">'.explode(' ', $tmp, 2)[0].'</a>', $tmp);
		$tech_info[$tmp] = str_replace(' ', ':', \OidDerConverter::hexarrayToStr(\OidDerConverter::oidToDER($this->nodeId(false))));

		return $tech_info;
	}

	/**
	 * @return bool
	 */
	protected function isClassCWeid(): bool {
		$dist = oid_distance($this->oid, '1.3.6.1.4.1.37553.8');
		if ($dist === false) return false;
		return $dist >= 0;
	}

	/**
	 * @param string $title
	 * @param string $content
	 * @param string $icon
	 * @return void
	 * @throws OIDplusException
	 */
	public function getContentPage(string &$title, string &$content, string &$icon) {
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
				$tech_info_html .= '<div style="overflow:auto"><table border="0">';
				foreach ($tech_info as $key => $value) {
					$tech_info_html .= '<tr><td valign="top" style="white-space: nowrap;">'.$key.': </td><td><code>'.$value.'</code></td></tr>';
				}
				$tech_info_html .= '</table></div>';
			}

			$content = $tech_info_html;

			$oa = new \OIDInfoAPI();
			if ($oa->illegalOid($this->oid, $illegal_root, $explanation)) {
				$content .= '<p><font color="red" size="+1">'._L('Attention! This OID is probably illegal: %1', $explanation).'</font></p>';
			}
			$oa = null;

			if ($this->userHasParentalWriteRights()) {
				$content .= '<h2>'._L('Superior RA Allocation Info').'</h2>%%SUPRA%%';
			}

			$content .= '<h2>'._L('Description').'</h2>%%DESC%%';
			$content .= '<h2>'._L('Registration Authority').'</h2>%%RA_INFO%%';

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

	/**
	 * Gets the last arc of an WEID
	 * @return false|string
	 */
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

	/**
	 * @param bool $withAbbr
	 * @return string
	 */
	public function getWeidNotation(bool $withAbbr=true): string {
		$weid = (strtolower($this->getDotNotation()) == 'oid:') ? 'weid:' : WeidOidConverter::oid2weid($this->getDotNotation());
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
			if (strtolower($ns) === 'weid:')      $base_arc = '1.3.6.1.4.1.37553.8';
			if (strtolower($ns) === 'weid:pen:')  $base_arc = '1.3.6.1.4.1';
			if (strtolower($ns) === 'weid:root:') $base_arc = _L('OID tree root');
			if (strtolower($ns) === 'weid:uuid:') $base_arc = '2.25'; // special case 2.25 = weid:uuid:?
			if (preg_match('@^weid:uuid:(.+):@ismU', $ns, $m)) $base_arc = uuid_to_oid($m[1]);

			$weid = '<abbr title="'._L('Base OID').': '.$base_arc.'&#10;'._L('Other identifiers').':&#10;      urn:x-'.$ns.'">'.rtrim($ns,':').'</abbr>:'.implode('-',$weid_arcs);
		}
		return $weid;
	}

	/**
	 * @param string|int $arcs
	 * @return OIDplusOid
	 * @throws OIDplusException
	 */
	public function appendArcs($arcs): OIDplusOid {
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

		return $out;
	}

	/**
	 * @param OIDplusOid $parent
	 * @return false|string
	 */
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

	/**
	 * @return OIDplusOidAsn1Id[]
	 * @throws OIDplusException
	 */
	public function getAsn1Ids(): array {
		$asn_ids = array();
		$res_asn = OIDplus::db()->query("select * from ###asn1id where oid = ? order by lfd", array("oid:".$this->oid));
		while ($row_asn = $res_asn->fetch_array()) {
			$name = $row_asn['name'];
			$standardized = $row_asn['standardized'] ?? false;
			$well_known = $row_asn['well_known'] ?? false;
			$asn_ids[] = new OIDplusOidAsn1Id($name, $standardized, $well_known);
		}
		return $asn_ids;
	}

	/**
	 * @return OIDplusOidIri[]
	 * @throws OIDplusException
	 */
	public function getIris(): array {
		$iri_ids = array();
		$res_iri = OIDplus::db()->query("select * from ###iri where oid = ? order by lfd", array("oid:".$this->oid));
		while ($row_iri = $res_iri->fetch_array()) {
			$name = $row_iri['name'];
			$longarc = $row_iri['longarc'] ?? false;
			$well_known = $row_iri['well_known'] ?? false;
			$iri_ids[] = new OIDplusOidIri($name, $longarc, $well_known);
		}
		return $iri_ids;
	}

	/**
	 * @param OIDplusOid|null $parent
	 * @param string $separator
	 * @return string
	 * @throws OIDplusException
	 */
	public function viewGetArcAsn1s(?OIDplusOid $parent=null, string $separator = ' | '): string {
		$asn_ids = array();

		if (is_null($parent)) $parent = OIDplusOid::parse(self::root());

		$part = $this->deltaDotNotation($parent);

		if (strpos($part, '.') === false) {
			$asn_id_objs = $this->getAsn1Ids();
			foreach ($asn_id_objs as $asn_id_obj) {
				$asn_ids[] = $asn_id_obj->getName().'('.$part.')';
			}
		}

		if (count($asn_ids) == 0) $asn_ids = array($part);
		return implode($separator, $asn_ids);
	}

	/**
	 * @param bool $withAbbr
	 * @return string
	 * @throws OIDplusException
	 */
	public function getAsn1Notation(bool $withAbbr=true): string {
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

	/**
	 * @param bool $withAbbr
	 * @return string
	 * @throws OIDplusException
	 */
	public function getIriNotation(bool $withAbbr=true): string {
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
			} else /*if (count($names) == 1)*/ {
				$iri_notation = array_shift($names) . '/' . $iri_notation;
			}

			if ($is_longarc) break; // we don't write /ITU-T/ at the beginning, when /ITU-T/xyz is a long arc
		}
		return '/' . substr($iri_notation, 0, strlen($iri_notation)-1);
	}

	/**
	 * @return string
	 */
	public function getDotNotation(): string {
		return $this->oid;
	}

	/**
	 * @return bool
	 * @throws OIDplusException
	 */
	public function isWellKnown(): bool {
		$res = OIDplus::db()->query("select oid from ###asn1id where oid = ? and well_known = ?", array("oid:".$this->oid,true));
		if ($res->any()) return true;

		$res = OIDplus::db()->query("select oid from ###iri where oid = ? and well_known = ?", array("oid:".$this->oid,true));
		if ($res->any()) return true;

		return false;
	}

	/**
	 * @param array $demandedASN1s
	 * @param bool $simulate
	 * @return void
	 * @throws OIDplusException
	 */
	public function replaceAsn1Ids(array $demandedASN1s=array(), bool $simulate=false) {
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
			$res = OIDplus::db()->query("select oid from ###asn1id where name = ? and standardized = ?", array($asn1,true)); // Attention: Requires case-SENSITIVE database collation!!
			while ($row = $res->fetch_array()) {
				$check_oid = OIDplusOid::parse($row['oid'])->oid;
				if ((oid_up($check_oid) === oid_up($this->oid)) && // same parent
				   ($check_oid !== $this->oid))                    // different OID
				{
					throw new OIDplusException(_L('ASN.1 identifier "%1" is a standardized identifier belonging to OID %2',$asn1,$check_oid));
				}
			}
		}
		unset($asn1); // Very important, otherwise we would modify the array if we later use "foreach ($demandedASN1s as $asn1)"

		// Now do the real replacement
		if (!$simulate) {
			OIDplus::db()->query("delete from ###asn1id where oid = ?", array("oid:".$this->oid));
			foreach ($demandedASN1s as $asn1) {
				OIDplus::db()->query("insert into ###asn1id (oid, name, well_known, standardized) values (?, ?, ?, ?)", array("oid:".$this->oid, $asn1, false, false));
			}
		}
	}

	/**
	 * @param array $demandedIris
	 * @param bool $simulate
	 * @return void
	 * @throws OIDplusException
	 */
	public function replaceIris(array $demandedIris=array(), bool $simulate=false) {
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
			$res = OIDplus::db()->query("select oid from ###iri where name = ?", array($iri)); // Attention: Requires case-SENSITIVE database collation!!
			while ($row = $res->fetch_array()) {
				$check_oid = OIDplusOid::parse($row['oid'])->oid;
				if ((oid_up($check_oid) === oid_up($this->oid)) && // same parent
				   ($check_oid !== $this->oid))                    // different OID
				{
					throw new OIDplusException(_L('IRI "%1" is already used by another OID (%2)',$iri,$check_oid));
				}
			}
		}
		unset($iri); // Very important, otherwise we would modify the array if we later use "foreach ($demandedIris as $iri)"

		// Now do the real replacement
		if (!$simulate) {
			OIDplus::db()->query("delete from ###iri where oid = ?", array("oid:".$this->oid));
			foreach ($demandedIris as $iri) {
				OIDplus::db()->query("insert into ###iri (oid, name, longarc, well_known) values (?, ?, ?, ?)", array("oid:".$this->oid, $iri, false, false));
			}
		}
	}

	/**
	 * @return OIDplusOid|null
	 */
	public function one_up(): ?OIDplusOid {
		return self::parse(self::ns().':'.oid_up($this->oid));
	}

	/**
	 * @param OIDplusObject|string $to
	 * @return int|null
	 */
	public function distance($to): ?int {
		if (!is_object($to)) $to = OIDplusObject::parse($to);
		if (!$to) return null;
		if (!($to instanceof $this)) return null;
		$res = oid_distance($to->oid, $this->oid);
		return $res !== false ? $res : null;
	}

	/**
	 * @return OIDplusAltId[]
	 * @throws OIDplusException
	 */
	public function getAltIds(): array {
		if ($this->isRoot()) return array();
		$ids = parent::getAltIds();

		$ids[] = new OIDplusAltId('weid', explode(':',$this->getWeidNotation(false),2)[1], _L('WEID notation'), '', 'https://weid.info/spec.html');

		// R74n "Multiplane", see https://r74n.com/multiplane/
		// Vendor space:
		// [0x2aabb] = 1.3.6.1.4.1.61117.1.[0x2aa00].[0xbb]
		// Every other space:
		// [0xcaabb] = 1.3.6.1.4.1.61117.1.[0xcaabb]
		$multiplane = null;
		$tmp_oid = oid_up($this->oid);
		for ($i=0; $i<=0xFF; $i++) {
			$vid = 0x20000 + ($i<<16);
			if ($tmp_oid == '1.3.6.1.4.1.61117.1.'.$vid) {
				$j = (int)substr($this->oid,strlen($tmp_oid)+1);
				if (($j>=0) && ($j<=0xFF)) {
					$xi = str_pad(dechex($i), 2, '0', STR_PAD_LEFT);
					$xj = str_pad(dechex($j), 2, '0', STR_PAD_LEFT);
					$multiplane = strtoupper('R2'.$xi.$xj);
				}
				break;
			}
		}
		if ($tmp_oid == '1.3.6.1.4.1.61117.1') {
			$ij = (int)substr($this->oid,strlen('1.3.6.1.4.1.61117.1.'));
			if ((($ij>=0) && ($ij<=0x1FFFF)) || (($ij>0x30000) && ($ij<=0xFFFFF))) {
				$multiplane = strtoupper('R'.str_pad(dechex($ij), 5, '0', STR_PAD_LEFT));
			}
		}
		if (!is_null($multiplane)) {
			$ids[] = new OIDplusAltId('r74n-multiplane', $multiplane, _L('R74n-Multiplane'));
		}

		if ($uuid = oid_to_uuid($this->oid)) {
			// UUID-OIDs are representation of an UUID
			$ids[] = new OIDplusAltId('guid', $uuid, _L('UUID representation of this OID'));
		} else {
			// All other OIDs can be formed into an UUID by making them a namebased OID
			// You could theoretically also do this to an UUID-OID, but we exclude this case to avoid that users are confused
			$ids[] = new OIDplusAltId('guid', gen_uuid_md5_namebased(UUID_NAMEBASED_NS_OID, $this->oid), _L('Name based version 3 / MD5 UUID with namespace %1','UUID_NAMEBASED_NS_OID'));
			$ids[] = new OIDplusAltId('guid', gen_uuid_sha1_namebased(UUID_NAMEBASED_NS_OID, $this->oid), _L('Name based version 5 / SHA1 UUID with namespace %1','UUID_NAMEBASED_NS_OID'));
		}

		$oid = $this->nodeId(false);
		$oid_parts = explode('.',$oid);
		$oid_len = count($oid_parts);

		// (VTS B1) Members
		if ($oid == '1.3.6.1.4.1.37476.1') {
			$aid = 'D276000186B1';
			$aid_is_ok = aid_canonize($aid);
			if ($aid_is_ok) $ids[] = new OIDplusAltId('aid', $aid, _L('Application Identifier (ISO/IEC 7816)'), ' ('._L('No PIX allowed').')', 'https://hosted.oidplus.com/viathinksoft/?goto=aid%3AD276000186B1');
		} else {
			if (($oid_len == 9) && str_starts_with($oid,'1.3.6.1.4.1.37476.1.')) {
				$number = str_pad($oid_parts[8],4,'0',STR_PAD_LEFT);
				$aid = 'D276000186B1'.$number;
				$aid_is_ok = aid_canonize($aid);
				if ($aid_is_ok) $ids[] = new OIDplusAltId('aid', $aid, _L('Application Identifier (ISO/IEC 7816)'), ' ('._L('Optional PIX allowed, without prefix').')', 'https://hosted.oidplus.com/viathinksoft/?goto=aid%3AD276000186B1');
			}
		}

		// (VTS B2) Products
		if ($oid == '1.3.6.1.4.1.37476.2') {
			$aid = 'D276000186B2';
			$aid_is_ok = aid_canonize($aid);
			if ($aid_is_ok) $ids[] = new OIDplusAltId('aid', $aid, _L('Application Identifier (ISO/IEC 7816)'), ' ('._L('No PIX allowed').')', 'https://hosted.oidplus.com/viathinksoft/?goto=aid%3AD276000186B2');
		} else {
			if (($oid_len == 9) && str_starts_with($oid,'1.3.6.1.4.1.37476.2.')) {
				$number = str_pad($oid_parts[8],4,'0',STR_PAD_LEFT);
				$aid = 'D276000186B2'.$number;
				$aid_is_ok = aid_canonize($aid);
				if ($aid_is_ok) $ids[] = new OIDplusAltId('aid', $aid, _L('Application Identifier (ISO/IEC 7816)'), ' ('._L('Optional PIX allowed, without prefix').')', 'https://hosted.oidplus.com/viathinksoft/?goto=aid%3AD276000186B2');
			}
		}

		// (VTS B2 00 05) OIDplus System AID / Information Object AID
		if (($oid_len == 10) && str_starts_with($oid,'1.3.6.1.4.1.37476.30.9.')) {
			$sid = $oid_parts[9];
			$sid_hex = strtoupper(str_pad(dechex((int)$sid),8,'0',STR_PAD_LEFT));
			$aid = 'D276000186B20005'.$sid_hex;
			$aid_is_ok = aid_canonize($aid);
			if ($aid_is_ok) $ids[] = new OIDplusAltId('aid', $aid, _L('OIDplus System Application Identifier (ISO/IEC 7816)'), ' ('._L('No PIX allowed').')', 'https://hosted.oidplus.com/viathinksoft/?goto=aid%3AD276000186B20005');
		}
		else if (($oid_len == 11) && str_starts_with($oid,'1.3.6.1.4.1.37476.30.9.')) {
			$sid = $oid_parts[9];
			$obj = $oid_parts[10];
			$sid_hex = strtoupper(str_pad(dechex((int)$sid),8,'0',STR_PAD_LEFT));
			$obj_hex = strtoupper(str_pad(dechex((int)$obj),8,'0',STR_PAD_LEFT));
			$aid = 'D276000186B20005'.$sid_hex.$obj_hex;
			$aid_is_ok = aid_canonize($aid);
			if ($aid_is_ok) $ids[] = new OIDplusAltId('aid', $aid, _L('OIDplus Information Object Application Identifier (ISO/IEC 7816)'), ' ('._L('No PIX allowed').')', 'https://hosted.oidplus.com/viathinksoft/?goto=aid%3AD276000186B20005');
		}

		// (VTS F0) IANA PEN to AID Mapping (PIX allowed)
		if (($oid_len == 7) && str_starts_with($oid,'1.3.6.1.4.1.')) {
			$pen = $oid_parts[6];
			$aid = 'D276000186F0'.$pen;
			if (strlen($aid)%2 == 1) $aid .= 'F';
			$aid_is_ok = aid_canonize($aid);
			if ($aid_is_ok) $ids[] = new OIDplusAltId('aid', $aid, _L('Application Identifier (ISO/IEC 7816)'), ' ('._L('Optional PIX allowed, with "FF" prefix').')', 'https://hosted.oidplus.com/viathinksoft/?goto=aid%3AD276000186F0');
			$ids[] = new OIDplusAltId('iana-pen', $pen, _L('IANA Private Enterprise Number (PEN)'));
		}

		// (VTS F1) FreeOID to AID Mapping (PIX allowed)
		if (($oid_len == 9) && str_starts_with($oid,'1.3.6.1.4.1.37476.9000.')) {
			$number = $oid_parts[8];
			$aid = 'D276000186F1'.$number;
			if (strlen($aid)%2 == 1) $aid .= 'F';
			$aid_is_ok = aid_canonize($aid);
			if ($aid_is_ok) $ids[] = new OIDplusAltId('aid', $aid, _L('Application Identifier (ISO/IEC 7816)'), ' ('._L('Optional PIX allowed, with "FF" prefix').')', 'https://hosted.oidplus.com/viathinksoft/?goto=aid%3AD276000186F1');
		}

		// (VTS F6) Mapping OID-to-AID if possible
		try {
			$test_der = \OidDerConverter::hexarrayToStr(\OidDerConverter::oidToDER($oid));
		} catch (\Exception $e) {
			$test_der = '00'; // error, should not happen
		}
		if (substr($test_der,0,3) == '06 ') { // 06 = ASN.1 type of Absolute ID
			if (str_starts_with("$oid.","2.999.")) {
				// Note that "ViaThinkSoft E0" AID are not unique!
				// OIDplus will use the relative DER of the 2.999.xx OID as PIX
				$aid_candidate = 'D2 76 00 01 86 E0 ' . substr($test_der, strlen('06 xx 88 37 ')); // Remove ASN.1 06=Type, xx=Length and the 2.999 arcs "88 37"
				$aid_is_ok = aid_canonize($aid_candidate);
				if (!$aid_is_ok) {
					// If DER encoding is not possible (too long), then we will use a 32 bit small hash.
					$small_hash = str_pad(dechex(smallhash($oid)),8,'0',STR_PAD_LEFT);
					$aid_candidate = 'D2 76 00 01 86 E0 ' . strtoupper(implode(' ',str_split($small_hash,2)));
					$aid_is_ok = aid_canonize($aid_candidate);
				}
				if ($aid_is_ok) $ids[] = new OIDplusAltId('aid', $aid_candidate, _L('Application Identifier (ISO/IEC 7816)'), '', 'https://hosted.oidplus.com/viathinksoft/?goto=aid%3AD276000186E0');
			} else if (str_starts_with("$oid.","0.4.0.127.0.7.")) {
				// Illegal usage of E8 by German BSI, plus using E8+Len+OID instead of E8+OID like ISO does
				// PIX probably not used
				$aid_candidate = 'E8 '.substr($test_der, strlen('06 ')); // Remove ASN.1 06=Type
				$aid_is_ok = aid_canonize($aid_candidate);
				if ($aid_is_ok) $ids[] = new OIDplusAltId('aid', $aid_candidate, _L('Application Identifier (ISO/IEC 7816)'));
			} else if (str_starts_with("$oid.","1.0.")) {
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
				if ($aid_is_ok) $ids[] = new OIDplusAltId('aid', $aid_candidate, _L('Application Identifier (ISO/IEC 7816)'), ' ('._L('No PIX allowed').')', 'https://hosted.oidplus.com/viathinksoft/?goto=aid%3AD276000186F6');
			}
		}

		if (str_starts_with($oid,'1.3.6.1.4.1.37476.9001.')) {
			// TODO: Implement 9001 mac-based (and vice versa)
		}

		if (($oid_len == 11) && str_starts_with($oid,'1.3.6.1.4.1.37476.9002.1.16.') && ($oid_parts[10]<=0xFFFF)) {
			$ids[] = new OIDplusAltId('device-vendor', "USB\\VID_".sprintf("%04X", $oid_parts[10]), _L('USB Vendor'), '', '');
		}

		if (($oid_len == 12) && str_starts_with($oid,'1.3.6.1.4.1.37476.9002.1.32.') && ($oid_parts[10]<=0xFFFF) && ($oid_parts[11]<=0xFFFF)) {
			$ids[] = new OIDplusAltId('device-vendor', "USB\\VID_".sprintf("%04X", $oid_parts[10])."&PID_".sprintf("%04X", $oid_parts[11]), _L('USB Vendor and Product'), '', '');
		}

		if (($oid_len == 11) && str_starts_with($oid,'1.3.6.1.4.1.37476.9002.2.16.') && ($oid_parts[10]<=0xFFFF)) {
			$ids[] = new OIDplusAltId('device-vendor', "PCI\\VID_".sprintf("%04X", $oid_parts[10]), _L('PCI Vendor'), '', '');
		}

		if (($oid_len == 12) && str_starts_with($oid,'1.3.6.1.4.1.37476.9002.2.32.') && ($oid_parts[10]<=0xFFFF) && ($oid_parts[11]<=0xFFFF)) {
			$ids[] = new OIDplusAltId('device-vendor', "PCI\\VID_".sprintf("%04X", $oid_parts[10])."&PID_".sprintf("%04X", $oid_parts[11]), _L('PCI Vendor and Product'), '', '');
		}

		if (($oid_len == 12) && str_starts_with($oid,'1.3.6.1.4.1.37476.9003.1.')) {
			$duns =
				str_pad($oid_parts[9],2,'0',STR_PAD_LEFT).'-'.
				str_pad($oid_parts[10],3,'0',STR_PAD_LEFT).'-'.
				str_pad($oid_parts[11],4,'0',STR_PAD_LEFT);
			$ids[] = new OIDplusAltId('duns', $duns, _L('Data Universal Numbering System (D-U-N-S)'), '', '');
		}

		if (($oid_len == 10) && str_starts_with($oid,'1.3.6.1.4.1.37476.9003.2.')) {
			$rin = $oid_parts[9];
			$ids[] = new OIDplusAltId('rin', $rin, _L('Ringgold ID'), '', '');
		}

		if (($oid_len == 11) && str_starts_with($oid,'1.3.6.1.4.1.37476.9003.3.')) {
			// TODO: Implement vice versa for doi object type
			$doi = $oid_parts[9].'.'.$oid_parts[10];
			$ids[] = new OIDplusAltId('doi', $doi, _L('Digital Object Identifier (DOI)'), '', '');
		}

		if (str_starts_with($oid,'1.3.6.1.4.1.37476.9004.')) {
			// TODO: Implement 9004 gs1 based (and vice versa)
		}

		if (($oid_len == 14) && str_starts_with($oid,'1.3.6.1.4.1.37476.9005.1.1.')) {
			$isni =
				str_pad($oid_parts[10],4,'0',STR_PAD_LEFT).'-'.
				str_pad($oid_parts[11],4,'0',STR_PAD_LEFT).'-'.
				str_pad($oid_parts[12],4,'0',STR_PAD_LEFT).'-'.
				str_pad($oid_parts[13],4,'0',STR_PAD_LEFT);
			$ids[] = new OIDplusAltId('isni', $isni, _L('International Standard Name Identifier (ISNI)'), '', '');
		}

		if (($oid_len == 14) && str_starts_with($oid,'1.3.6.1.4.1.37476.9005.1.2.')) {
			$orcid =
				str_pad($oid_parts[10],4,'0',STR_PAD_LEFT).'-'.
				str_pad($oid_parts[11],4,'0',STR_PAD_LEFT).'-'.
				str_pad($oid_parts[12],4,'0',STR_PAD_LEFT).'-'.
				str_pad($oid_parts[13],4,'0',STR_PAD_LEFT);
			$ids[] = new OIDplusAltId('orcid', $orcid, _L('Open Researcher and Contributor ID (ORCID)'), '', '');
		}

		if (($oid_len == 12) && str_starts_with($oid,'1.3.6.1.4.1.37476.9006.189.')) {
			$ebid =
				$oid_parts[9].' '.
				str_pad($oid_parts[10],6,'0',STR_PAD_LEFT).' '.
				str_pad($oid_parts[11],6,'0',STR_PAD_LEFT);
			$ids[] = new OIDplusAltId('ebid', $ebid, _L('European Business Identifier (EBID)'), '', '');
		}

		return $ids;
	}

	/**
	 * @return string
	 */
	public function getDirectoryName(): string {
		if ($this->isRoot()) return $this->ns();
		$oid = $this->nodeId(false);
		return $this->ns().'_'.str_replace('.', '_', $oid);
	}

	/**
	 * @param string $mode
	 * @return string
	 */
	public static function treeIconFilename(string $mode): string {
		// TODO: Class C WEID should have a different icon!
		return 'img/'.$mode.'_icon16.png';
	}
}
