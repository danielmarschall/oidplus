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

class OIDplusX500DN extends OIDplusObject {
	/**
	 * @var string
	 */
	private $identifier;

	/**
	 * @param string $identifier
	 */
	public function __construct(string $identifier) {
		// No syntax checks
		$this->identifier = $identifier;
	}

	/**
	 * @param string $node_id
	 * @return OIDplusX500DN|null
	 */
	public static function parse(string $node_id)/*: ?OIDplusX500DN*/ {
		@list($namespace, $identifier) = explode_with_escaping(':', $node_id, 2);
		if ($namespace !== self::ns()) return null;
		return new self($identifier);
	}

	/**
	 * @return string
	 */
	public static function objectTypeTitle(): string {
		return _L('X.500 Distinguished Name');
	}

	/**
	 * @return string
	 */
	public static function objectTypeTitleShort(): string {
		return _L('X.500 DN');
	}

	/**
	 * @return string
	 */
	public static function ns(): string {
		return 'x500dn';
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
		return $this->identifier == '';
	}

	/**
	 * @param bool $with_ns
	 * @return string
	 */
	public function nodeId(bool $with_ns=true): string {
		return $with_ns ? self::root().$this->identifier : $this->identifier;
	}

	/**
	 * @return string[]
	 */
	public static function getKnownAttributeNames(): array {
		return [

			// Source: http://oid-info.com/get/2.5.4
			"objectClass" => ["2.5.4.0", "objectClass"],
			"aliasedEntryName" => ["2.5.4.1", "aliasedEntryName"],
			"knowledgeInformation" => ["2.5.4.2", "knowledgeInformation"],
			"commonName" => ["2.5.4.3", "commonName"],
			"surname" => ["2.5.4.4", "surname"],
			"serialNumber" => ["2.5.4.5", "serialNumber"],
			"countryName" => ["2.5.4.6", "countryName"],
			"localityName" => ["2.5.4.7", "localityName"],
			"stateOrProvinceName" => ["2.5.4.8", "stateOrProvinceName"],
			"streetAddress" => ["2.5.4.9", "streetAddress"],
			"organizationName" => ["2.5.4.10", "organizationName"],
			"organizationalUnitName" => ["2.5.4.11", "organizationalUnitName"],
			"title" => ["2.5.4.12", "title"],
			"description" => ["2.5.4.13", "description"],
			"searchGuide" => ["2.5.4.14", "searchGuide"],
			"businessCategory" => ["2.5.4.15", "businessCategory"],
			"postalAddress" => ["2.5.4.16", "postalAddress"],
			"postalCode" => ["2.5.4.17", "postalCode"],
			"postOfficeBox" => ["2.5.4.18", "postOfficeBox"],
			"physicalDeliveryOfficeName" => ["2.5.4.19", "physicalDeliveryOfficeName"],
			"telephoneNumber" => ["2.5.4.20", "telephoneNumber"],
			"telexNumber" => ["2.5.4.21", "telexNumber"],
			"teletexTerminalIdentifier" => ["2.5.4.22", "teletexTerminalIdentifier"],
			"facsimileTelephoneNumber" => ["2.5.4.23", "facsimileTelephoneNumber"],
			"x121Address" => ["2.5.4.24", "x121Address"],
			"internationalISDNNumber" => ["2.5.4.25", "internationalISDNNumber"],
			"registeredAddress" => ["2.5.4.26", "registeredAddress"],
			"destinationIndicator" => ["2.5.4.27", "destinationIndicator"],
			"preferredDeliveryMethod" => ["2.5.4.28", "preferredDeliveryMethod"],
			"presentationAddress" => ["2.5.4.29", "presentationAddress"],
			"supportedApplicationContext" => ["2.5.4.30", "supportedApplicationContext"],
			"member" => ["2.5.4.31", "member"],
			"owner" => ["2.5.4.32", "owner"],
			"roleOccupant" => ["2.5.4.33", "roleOccupant"],
			"seeAlso" => ["2.5.4.34", "seeAlso"],
			"userPassword" => ["2.5.4.35", "userPassword"],
			"userCertificate" => ["2.5.4.36", "userCertificate"],
			"cACertificate" => ["2.5.4.37", "cACertificate"],
			"authorityRevocationList" => ["2.5.4.38", "authorityRevocationList"],
			"certificateRevocationList" => ["2.5.4.39", "certificateRevocationList"],
			"crossCertificatePair" => ["2.5.4.40", "crossCertificatePair"],
			"name" => ["2.5.4.41", "name"],
			"givenName" => ["2.5.4.42", "givenName"],
			"initials" => ["2.5.4.43", "initials"],
			"generationQualifier" => ["2.5.4.44", "generationQualifier"],
			"uniqueIdentifier" => ["2.5.4.45", "uniqueIdentifier"],
			"dnQualifier" => ["2.5.4.46", "dnQualifier"],
			"enhancedSearchGuide" => ["2.5.4.47", "enhancedSearchGuide"],
			"protocolInformation" => ["2.5.4.48", "protocolInformation"],
			"distinguishedName" => ["2.5.4.49", "distinguishedName"],
			"uniqueMember" => ["2.5.4.50", "uniqueMember"],
			"houseIdentifier" => ["2.5.4.51", "houseIdentifier"],
			"supportedAlgorithms" => ["2.5.4.52", "supportedAlgorithms"],
			"deltaRevocationList" => ["2.5.4.53", "deltaRevocationList"],
			"dmdName" => ["2.5.4.54", "dmdName"],
			"clearance" => ["2.5.4.55", "clearance"],
			"defaultDirQop" => ["2.5.4.56", "defaultDirQop"],
			"attributeIntegrityInfo" => ["2.5.4.57", "attributeIntegrityInfo"],
			"attributeCertificate" => ["2.5.4.58", "attributeCertificate"],
			"attributeCertificateRevocationList" => ["2.5.4.59", "attributeCertificateRevocationList"],
			"confKeyInfo" => ["2.5.4.60", "confKeyInfo"],
			"aACertificate" => ["2.5.4.61", "aACertificate"],
			"attributeDescriptorCertificate" => ["2.5.4.62", "attributeDescriptorCertificate"],
			"attributeAuthorityRevocationList" => ["2.5.4.63", "attributeAuthorityRevocationList"],
			"family-information" => ["2.5.4.64", "family-information"],
			"pseudonym" => ["2.5.4.65", "pseudonym"],
			"communicationsService" => ["2.5.4.66", "communicationsService"],
			"communicationsNetwork" => ["2.5.4.67", "communicationsNetwork"],
			"certificationPracticeStmt" => ["2.5.4.68", "certificationPracticeStmt"],
			"certificatePolicy" => ["2.5.4.69", "certificatePolicy"],
			"pkiPath" => ["2.5.4.70", "pkiPath"],
			"privPolicy" => ["2.5.4.71", "privPolicy"],
			"role" => ["2.5.4.72", "role"],
			"delegationPath" => ["2.5.4.73", "delegationPath"],
			"protPrivPolicy" => ["2.5.4.74", "protPrivPolicy"],
			"xMLPrivilegeInfo" => ["2.5.4.75", "xMLPrivilegeInfo"],
			"xmlPrivPolicy" => ["2.5.4.76", "xmlPrivPolicy"],
			"uuidpair" => ["2.5.4.77", "uuidpair"],
			"tagOid" => ["2.5.4.78", "tagOid"],
			"uiiFormat" => ["2.5.4.79", "uiiFormat"],
			"uiiInUrh" => ["2.5.4.80", "uiiInUrh"],
			"contentUrl" => ["2.5.4.81", "contentUrl"],
			"permission" => ["2.5.4.82", "permission"],
			"uri" => ["2.5.4.83", "uri"],
			"pwdAttribute" => ["2.5.4.84", "pwdAttribute"],
			"userPwd" => ["2.5.4.85", "userPwd"],
			"urn" => ["2.5.4.86", "urn"],
			"url" => ["2.5.4.87", "url"],
			"utmCoordinates" => ["2.5.4.88", "utmCoordinates"],
			"urnC" => ["2.5.4.89", "urnC"],
			"uii" => ["2.5.4.90", "uii"],
			"epc" => ["2.5.4.91", "epc"],
			"tagAfi" => ["2.5.4.92", "tagAfi"],
			"epcFormat" => ["2.5.4.93", "epcFormat"],
			"epcInUrn" => ["2.5.4.94", "epcInUrn"],
			"ldapUrl" => ["2.5.4.95", "ldapUrl"],
			"id-at-tagLocation" => ["2.5.4.96", "id-at-tagLocation"],
			"organizationIdentifier" => ["2.5.4.97", "organizationIdentifier"],
			"id-at-countryCode3c" => ["2.5.4.98", "id-at-countryCode3c"],
			"id-at-countryCode3n" => ["2.5.4.99", "id-at-countryCode3n"],
			"id-at-dnsName" => ["2.5.4.100", "id-at-dnsName"],
			"id-at-eepkCertificateRevocationList" => ["2.5.4.101", "id-at-eepkCertificateRevocationList"],
			"id-at-eeAttrCertificateRevocationList" => ["2.5.4.102", "id-at-eeAttrCertificateRevocationList"],
			"id-at-supportedPublicKeyAlgorithms" => ["2.5.4.103", "id-at-supportedPublicKeyAlgorithms"],
			"id-at-intEmail" => ["2.5.4.104", "id-at-intEmail"],
			"id-at-jid" => ["2.5.4.105", "id-at-jid"],
			"id-at-objectIdentifier" => ["2.5.4.106", "id-at-objectIdentifier"],
			// Source: https://www.ibm.com/docs/en/zos/2.2.0?topic=SSLTBW_2.2.0/com.ibm.tcp.ipsec.ipsec.help.doc/com/ibm/tcp/ipsec/nss/NssImageServerPs.RB_X500.htm
			// TODO: Translate human-friendly names using _L()
			"C" => ["2.5.4.6", "Country"],
			"CN" => ["2.5.4.3", "Common name"],
			"DC" => ["0.9.2342.19200300.100.1.25", "Domain component"],
			"E" => ["1.2.840.113549.1.9.1", "E-mail address"],
			"EMAIL" => ["1.2.840.113549.1.9.1", "E-mail address"], //(preferred)
			"EMAILADDRESS" => ["1.2.840.113549.1.9.1", "E-mail address"],
			"L" => ["2.5.4.7", "Locality"],
			"O" => ["2.5.4.10", "Organization name"],
			"OU" => ["2.5.4.11", "Organizational unit name"],
			"PC" => ["2.5.4.17", "Postal code"],
			"S" => ["2.5.4.8", "State or province"],
			"SN" => ["2.5.4.4", "Family name"], // SN=Surname
			"SP" => ["2.5.4.8", "State or province"],
			"ST" => ["2.5.4.8", "State or province"], //(preferred)
			"STREET" => ["2.5.4.9", "Street"],
			"T" => ["2.5.4.12", "Title"],
			// Source: https://www.cryptosys.net/pki/manpki/pki_distnames.html
			"TITLE" => ["2.5.4.12", "Title"],
			"G" => ["2.5.4.42", "Given name"],
			"GN" => ["2.5.4.42", "Given name"],
			"UID" => ["0.9.2342.19200300.100.1.1", "User ID"],
			"SERIALNUMBER", ["2.5.4.5", "Serial number"]
		];
	}

	/**
	 * @param string $val
	 * @param bool $escape_equal_sign
	 * @param bool $escape_backslash
	 * @return string
	 */
	protected static function escapeAttributeValue(string $val, bool $escape_equal_sign, bool $escape_backslash): string {
		// Escaping required by https://datatracker.ietf.org/doc/html/rfc2253#section-2.4

		$val = trim($val); // we don't escape whitespaces. It is very unlikely that someone wants whitespaces at the beginning or end (it is rather a copy-paste error)

		if ($escape_backslash) $val = str_replace('\\', '\\\\', $val); // important: do this first

		$chars_to_escape = array(',', '+', '"', '<', '>', ';'); // listed in RFC 2253
		$chars_to_escape[] = '/'; // defined by us (OIDplus)
		if ($escape_equal_sign) $chars_to_escape[] = '='; // defined by us (OIDplus)

		foreach ($chars_to_escape as $char) {
			$dummy = find_nonexisting_substr($val);
			if (!$escape_backslash) $val = str_replace('\\'.$char, $dummy, $val);
			$val = str_replace($char, '\\'.$char, $val);
			if (!$escape_backslash) $val = str_replace($dummy, '\\'.$char, $val);
		}

		if (substr($val, 0, 1) == '#') {
			$val = '\\' . $val;
		}

		return $val;
	}

	/**
	 * @param string &$arc A RDN (Relative Distinguished Name), e.g. C=DE, CN=test, or 2.999=example.
	 *                     It *might* be auto-corrected (adding escape values).
	 * @param bool $allow_multival Are multi-valued arcs (e.g. "uid=4711+cn=John Doe") allowed?
	 * @return bool
	 */
	protected static function isValidArc(string &$arc, bool $allow_multival=true): bool {
		if ($allow_multival) {
			// We allow unescaped "+" and try to escape it, but at the same time we try to allow multi-valued names
			// Example:
			// "/cn=A+B Consulting"  will get corrected to  "/cn=A\+B Consulting"
			// "/cn=X+cn=Y" stays the same (multi-valued)
			// "/cn=X+cn=A+B Consulting"  will get corrected to  "/cn=X+cn=A\+B Consulting"
			// But we will also accept escape sequences by the user!
			// "/cn=X\+cn=A\+B Consulting" stays the same (not multi-valued)

			$values = explode_with_escaping('+', $arc);

			$corrected_identifier = '';
			foreach ($values as $v) {
				$dummy = find_nonexisting_substr($v);
				$v = str_replace('\\=', $dummy, $v);
				$is_rdn = strpos($v, '=');
				$v = str_replace($dummy, '\\=', $v);

				if ($is_rdn) {
					if (!self::isValidArc($v, false)) return false; // Note: isValidArc() also corrects the escaping of $v
				} else {
					$v = self::escapeAttributeValue($v, /*$escape_equal_sign=*/false, /*$escape_backslash=*/false);
				}

				if ($corrected_identifier == '') { // 1st value
					if ($is_rdn) {
						// "cn=hello" (values = ["cn=hello"]) is valid
						$corrected_identifier = $v;
					} else {
						// "hello+cn=world" (values = ["hello", "cn=world"]) is always invalid
						return false;
					}
				} else { // 2nd, 3rd, ... value
					if ($is_rdn) {
						// "cn=hello+cn=world" (values = ["cn=hello", "cn=world"]) stays "cn=hello+cn=world"
						$corrected_identifier .= '+' . $v;
					} else {
						// "cn=hello+world" (values = ["cn=hello", "world"]) becomes "cn=hello\+world"
						$corrected_identifier .= '\\+' . $v;
					}
				}
			}
			$arc = $corrected_identifier; // return the auto-corrected identifier
			return true;
		} else {
			$ary = explode_with_escaping('=', $arc, 2);
			if (count($ary) !== 2) return false;
			if ($ary[0] == "") return false;
			if ($ary[1] == "") return false;

			$ary[0] = self::escapeAttributeValue($ary[0], /*$escape_equal_sign=*/false, /*$escape_backslash=*/false);
			$ary[1] = self::escapeAttributeValue($ary[1], /*$escape_equal_sign=*/true,  /*$escape_backslash=*/false);

			if (oid_valid_dotnotation($ary[0], false, false, 1)) {
				$arc = $ary[0] . '=' . $ary[1]; // return the auto-corrected identifier
				return true;
			}

			$accepted_attribute_names = self::getKnownAttributeNames();
			foreach ($accepted_attribute_names as $abbr => list($oid, $human_friendly_name)) {
				if (strtolower($abbr) === strtolower($ary[0])) {
					$arc = $ary[0] . '=' . $ary[1]; // return the auto-corrected identifier
					return true;
				}
			}

			return false;
		}
	}

	/**
	 * @param string $str
	 * @return string
	 */
	public function addString(string $str): string {
		if (substr($str,0,1) == '/') $str = substr($str, 1);

		$new_arcs = explode_with_escaping('/', $str);
		foreach ($new_arcs as $n => &$test_arc) {
			if (!self::isValidArc($test_arc, true)) {
				throw new OIDplusException(_L("Arc %1 (%2) is not a valid Relative Distinguished Name (RDN).", $n+1, $test_arc));
			}
		}
		unset($test_arc);
		$str = implode('/', $new_arcs); // correct escaping which was auto-corrected by isValidArc()

		if ($this->isRoot()) {
			if (substr($str,0,1) != '/') $str = '/'.$str;
			return self::root() . $str;
		} else {
			if (strpos($str,'/') !== false) throw new OIDplusException(_L('Please only submit one arc.'));
			return $this->nodeId() . '/' . $str;
		}
	}

	/**
	 * @param OIDplusObject $parent
	 * @return string
	 */
	public function crudShowId(OIDplusObject $parent): string {
		if ($parent->isRoot()) {
			return substr($this->nodeId(), strlen($parent->nodeId()));
		} else {
			return substr($this->nodeId(), strlen($parent->nodeId())+1);
		}
	}

	/**
	 * @param OIDplusObject|null $parent
	 * @return string
	 */
	public function jsTreeNodeName(OIDplusObject $parent = null): string {
		if ($parent == null) return $this->objectTypeTitle();
		if ($parent->isRoot()) {
			return substr($this->nodeId(), strlen($parent->nodeId()));
		} else {
			return substr($this->nodeId(), strlen($parent->nodeId())+1);
		}
	}

	/**
	 * @return string
	 */
	public function defaultTitle(): string {
		return $this->identifier;
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

		$known_attr_names = self::getKnownAttributeNames();

		// Note: There are some notation rules if names contain things like backslashes, see https://www.cryptosys.net/pki/manpki/pki_distnames.html
		// We currently do not fully implement these! (TODO)

		$html_dce_ad_notation = '';
		$html_ldap_notation = '';
		$html_encoded_string_notation = '';

		$arcs = explode_with_escaping('/', ltrim($this->identifier,'/'));
		foreach ($arcs as $arc) {
			$ary = explode_with_escaping('=', $arc, 2);

			$found_oid = '';
			$found_hf_name = '???';
			foreach ($known_attr_names as $name => list($oid, $human_friendly_name)) {
				if (strtolower($name) == strtolower($ary[0])) {
					$found_oid = $oid;
					$found_hf_name = $human_friendly_name;
					break;
				}
			}

			$html_dce_ad_notation .= '/<abbr title="'.htmlentities($found_hf_name).'">'.htmlentities(strtoupper($ary[0])).'</abbr>='.htmlentities($ary[1]);
			$html_ldap_notation = '<abbr title="'.htmlentities($found_hf_name).'">'.htmlentities(strtoupper($ary[0])).'</abbr>='.htmlentities(str_replace(',','\\,',$ary[1])) . ($html_ldap_notation == '' ? '' : ', ' . $html_ldap_notation);

			// TODO: how are multi-valued values handled?
			$html_encoded_str = '#<abbr title="'._L('ASN.1: UTF8String').'">'.sprintf('%02s', strtoupper(dechex(0x0C/*UTF8String*/))).'</abbr>';
			$utf8 = vts_utf8_encode($ary[1]);
			$html_encoded_str .= '<abbr title="'._L('Length').'">'.sprintf('%02s', strtoupper(dechex(strlen($utf8)))).'</abbr>'; // TODO: This length does only work for length <= 0x7F! The correct implementation is described here: https://misc.daniel-marschall.de/asn.1/oid_facts.html#chap1_2
			$html_encoded_str .= '<abbr title="'.htmlentities($ary[1]).'">';
			for ($i=0; $i<strlen($utf8); $i++) {
				$char = substr($utf8, $i, 1);
				$html_encoded_str .= sprintf('%02s', strtoupper(dechex(ord($char))));
			}
			$html_encoded_str .= '</abbr>';
			$html_encoded_string_notation = '<abbr title="'.htmlentities(strtoupper($ary[0]) . ' = ' . $found_hf_name).'">'.htmlentities($found_oid).'</abbr>='.$html_encoded_str . ($html_encoded_string_notation == '' ? '' : ',' . $html_encoded_string_notation);
		}

		$tmp = _L('DCE/MSAD notation');
		$tmp = str_replace('DCE', '<abbr title="'._L('Distributed Computing Environment').'">DCE</abbr>', $tmp);
		$tmp = str_replace('MSAD', '<abbr title="'._L('Microsoft ActiveDirectory').'">MSAD</abbr>', $tmp);
		$tech_info[$tmp] = $html_dce_ad_notation;

		$tmp = _L('LDAP notation');
		$tmp = str_replace('LDAP', '<abbr title="'._L('Lightweight Directory Access Protocol').'">LDAP</abbr>', $tmp);
		$tech_info[$tmp] = $html_ldap_notation;

		$tmp = _L('Encoded string notation');
		$tech_info[$tmp] = $html_encoded_string_notation;

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
			$title = OIDplusX500DN::objectTypeTitle();

			$res = OIDplus::db()->query("select * from ###objects where parent = ?", array(self::root()));
			if ($res->any()) {
				$content  = '<p>'._L('Please select an object in the tree view at the left to show its contents.').'</p>';
			} else {
				$content  = '<p>'._L('Currently, no X.500 Distinguished Names are registered in the system.').'</p>';
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
					$tech_info_html .= '<tr><td valign="top" style="white-space: nowrap;">'.$key.': </td><td><code>'.$value.'</code></td></tr>';
				}
				$tech_info_html .= '</table></div>';
			}

			$content = $tech_info_html;

			$content .= '<h2>'._L('Description').'</h2>%%DESC%%';

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

	/**
	 * @return OIDplusX500DN|null
	 */
	public function one_up()/*: ?OIDplusX500DN*/ {
		$oid = $this->identifier;

		$p = strrpos($oid, '/');
		if ($p === false) return self::parse($oid);
		if ($p == 0) return self::parse('/');

		$oid_up = substr($oid, 0, $p);

		return self::parse(self::ns().':'.$oid_up);
	}

	/**
	 * @param OIDplusObject|string $to
	 * @return int|null
	 */
	public function distance($to) {
		if (!is_object($to)) $to = OIDplusObject::parse($to);
		if (!$to) return null;
		if (!($to instanceof $this)) return null;

		$a = $to->identifier;
		$b = $this->identifier;

		if (substr($a,0,1) == '/') $a = substr($a,1);
		if (substr($b,0,1) == '/') $b = substr($b,1);

		$ary = explode_with_escaping('/', $a);
		$bry = explode_with_escaping('/', $b);

		$min_len = min(count($ary), count($bry));

		for ($i=0; $i<$min_len; $i++) {
			if ($ary[$i] != $bry[$i]) return null;
		}

		return count($ary) - count($bry);
	}

	/**
	 * @return string
	 */
	public function getDirectoryName(): string {
		if ($this->isRoot()) return $this->ns();
		return $this->ns().'_'.md5($this->nodeId(false));
	}

	/**
	 * @param string $mode
	 * @return string
	 */
	public static function treeIconFilename(string $mode): string {
		return 'img/'.$mode.'_icon16.png';
	}
}
