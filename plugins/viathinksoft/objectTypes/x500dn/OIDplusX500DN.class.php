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
	 * @return array Format: [oid => [source, englishName, [ldapNames, ...], oidName], ...]
	 */
	public static function getKnownAttributeNames(): array {
		$ldap_attributes = [
			// Extracted from https://www.itu.int/itu-t/recommendations/rec.aspx?rec=X.520
			// Note: The most common ones are also defined in RFC 4519
			// ITU-T X.520 (10/2019), clause 6.1 System attribute types
			"2.5.4.2" => ["ITU-T X.520 (10/2019), clause 6.1.1", "Knowledge information", [], "id-at-knowledgeInformation"],
			// ITU-T X.520 (10/2019), clause 6.2 Labelling attribute types
			"2.5.4.41" => ["ITU-T X.520 (10/2019), clause 6.2.1", "Name", ["name"], "id-at-name"],
			"2.5.4.3" => ["ITU-T X.520 (10/2019), clause 6.2.2", "Common name", ["cn", "commonName"], "id-at-commonName"],
			"2.5.4.4" => ["ITU-T X.520 (10/2019), clause 6.2.3", "Surname", ["sn"], "id-at-surname"],
			"2.5.4.42" => ["ITU-T X.520 (10/2019), clause 6.2.4", "Given Name", ["givenName"], "id-at-givenName"],
			"2.5.4.43" => ["ITU-T X.520 (10/2019), clause 6.2.5", "Initials", ["initials"], "id-at-initials"],
			"2.5.4.44" => ["ITU-T X.520 (10/2019), clause 6.2.6", "Generation Qualifier", ["generationQualifier"], "id-at-generationQualifier"],
			"2.5.4.45" => ["ITU-T X.520 (10/2019), clause 6.2.7", "Unique Identifier", ["x500UniqueIdentifier"], "id-at-uniqueIdentifier"],
			"2.5.4.46" => ["ITU-T X.520 (10/2019), clause 6.2.8", "DN Qualifier", ["dnQualifier"], "id-at-dnQualifier"],
			"2.5.4.5" => ["ITU-T X.520 (10/2019), clause 6.2.9", "Serial Number", ["serialNumber"], "id-at-serialNumber"],
			"2.5.4.65" => ["ITU-T X.520 (10/2019), clause 6.2.10", "Pseudonym", [], "id-at-pseudonym"],
			"2.5.4.77" => ["ITU-T X.520 (10/2019), clause 6.2.11", "Universal Unique Identifier Pair", [], "id-at-uuidpair"],
			"2.5.4.83" => ["ITU-T X.520 (10/2019), clause 6.2.12", "URI", ["uri"], "id-at-uri"],
			"2.5.4.86" => ["ITU-T X.520 (10/2019), clause 6.2.13", "URN", ["urn"], "id-at-urn"],
			"2.5.4.87" => ["ITU-T X.520 (10/2019), clause 6.2.14", "URL", ["url"], "id-at-url"],
			"2.5.4.100" => ["ITU-T X.520 (10/2019), clause 6.2.15", "Domain name", ["DNS name"], "id-at-dnsName"],
			"2.5.4.104" => ["ITU-T X.520 (10/2019), clause 6.2.16", "Internationalized email address", ["Internationalized Email"], "id-at-intEmail"],
			"2.5.4.105" => ["ITU-T X.520 (10/2019), clause 6.2.17", "Jabber identifier", ["Jabber identifier"], "id-at-jid"],
			"2.5.4.106" => ["ITU-T X.520 (10/2019), clause 6.2.18", "Object identifier", ["Object Identifier"], "id-at-objectIdentifier"],
			// ITU-T X.520 (10/2019), clause 6.3 Geographical attribute types
			"2.5.4.6" => ["ITU-T X.520 (10/2019), clause 6.3.1", "Country Name", ["c"], "id-at-countryName"],
			"2.5.4.98" => ["ITU-T X.520 (10/2019), clause 6.3.2", "Country code with three characters", ["c3"], "id-at-countryCode3c"],
			"2.5.4.99" => ["ITU-T X.520 (10/2019), clause 6.3.3", "Numeric character country code", ["n3"], "id-at-countryCode3n"],
			"2.5.4.7" => ["ITU-T X.520 (10/2019), clause 6.3.4", "Locality Name", ["l"], "id-at-localityName"],
			"2.5.4.7.1" => ["ITU-T X.520 (10/2019), clause 6.3.4", "Collective Locality Name", ["c-l"], "id-at-collectiveLocalityName"],
			"2.5.4.8" => ["ITU-T X.520 (10/2019), clause 6.3.5", "State or Province Name", ["st"], "id-at-stateOrProvinceName"],
			"2.5.4.8.1" => ["ITU-T X.520 (10/2019), clause 6.3.5", "Collective State or Province Name", ["c-st"], "id-at-collectiveStateOrProvinceName"],
			"2.5.4.9" => ["ITU-T X.520 (10/2019), clause 6.3.6", "Street Address", ["street"], "id-at-streetAddress"],
			"2.5.4.9.1" => ["ITU-T X.520 (10/2019), clause 6.3.6", "Collective Street Address", ["c-street"], "id-at-collectiveStreetAddress"],
			"2.5.4.51" => ["ITU-T X.520 (10/2019), clause 6.3.7", "House Identifier", ["houseIdentifier"], "id-at-houseIdentifier"],
			"2.5.4.88" => ["ITU-T X.520 (10/2019), clause 6.3.8", "UTM coordinates", ["utmCoordinates"], "id-at-utmCoordinates"],
			// ITU-T X.520 (10/2019), clause 6.4 Organizational attribute types
			"2.5.4.10" => ["ITU-T X.520 (10/2019), clause 6.4.1", "Organization Name", ["o"], "id-at-organizationName"],
			"2.5.4.10.1" => ["ITU-T X.520 (10/2019), clause 6.4.1", "Collective Organization Name", ["c-o"], "id-at-collectiveOrganizationName"],
			"2.5.4.11" => ["ITU-T X.520 (10/2019), clause 6.4.2", "Organizational Unit Name", ["ou"], "id-at-organizationalUnitName"],
			"2.5.4.11.1" => ["ITU-T X.520 (10/2019), clause 6.4.2", "Collective Organizational Unit Name", ["c-ou"], "id-at-collectiveOrganizationalUnitName"],
			"2.5.4.12" => ["ITU-T X.520 (10/2019), clause 6.4.3", "Title", ["title"], "id-at-title"],
			"2.5.4.97" => ["ITU-T X.520 (10/2019), clause 6.4.4", "Organization identifier", ["organizationIdentifier"], "id-at-organizationIdentifier"],
			// ITU-T X.520 (10/2019), clause 6.5 Explanatory attribute types
			"2.5.4.13" => ["ITU-T X.520 (10/2019), clause 6.5.1", "Description", ["description"], "id-at-description"],
			"2.5.4.14" => ["ITU-T X.520 (10/2019), clause 6.5.2", "Search Guide", ["searchGuide"], "id-at-searchGuide"],
			"2.5.4.47" => ["ITU-T X.520 (10/2019), clause 6.5.3", "Enhanced Search Guide", ["enhancedSearchGuide"], "id-at-enhancedSearchGuide"],
			"2.5.4.15" => ["ITU-T X.520 (10/2019), clause 6.5.4", "Business Category", ["businessCategory"], "id-at-businessCategory"],
			// ITU-T X.520 (10/2019), clause 6.6 Postal addressing attribute types
			"2.5.4.16" => ["ITU-T X.520 (10/2019), clause 6.6.1", "Postal Address", ["postalAddress"], "id-at-postalAddress"],
			"2.5.4.16.1" => ["ITU-T X.520 (10/2019), clause 6.6.1", "Collective Postal Address", ["c-PostalAddress"], "id-at-collectivePostalAddress"],
			"2.5.4.17" => ["ITU-T X.520 (10/2019), clause 6.6.2", "Postal Code", ["postalCode"], "id-at-postalCode"],
			"2.5.4.17.1" => ["ITU-T X.520 (10/2019), clause 6.6.2", "Collective Postal Code", ["c-PostalCode"], "id-at-collectivePostalCode"],
			"2.5.4.18" => ["ITU-T X.520 (10/2019), clause 6.6.3", "Post Office Box", ["postOfficeBox"], "id-at-postOfficeBox"],
			"2.5.4.18.1" => ["ITU-T X.520 (10/2019), clause 6.6.3", "Collective Post Office Box", ["c-PostOfficeBox"], "id-at-collectivePostOfficeBox"],
			"2.5.4.19" => ["ITU-T X.520 (10/2019), clause 6.6.4", "Physical delivery office name", ["physicalDeliveryOfficeName"], "id-at-physicalDeliveryOfficeName"],
			"2.5.4.19.1" => ["ITU-T X.520 (10/2019), clause 6.6.4", "Collective Physical Delivery Office Name", ["c-PhysicalDeliveryOfficeName"], "id-at-collectivePhysicalDeliveryOfficeName"],
			// ITU-T X.520 (10/2019), clause 6.7 Telecommunications addressing attribute types
			"2.5.4.20" => ["ITU-T X.520 (10/2019), clause 6.7.1", "Telephone number", ["telephoneNumber"], "id-at-telephoneNumber"],
			"2.5.4.20.1" => ["ITU-T X.520 (10/2019), clause 6.7.1", "Collective Telephone number", ["c-TelephoneNumber"], "id-at-collectiveTelephoneNumber"],
			"2.5.4.21" => ["ITU-T X.520 (10/2019), clause 6.7.2", "Telex Number", ["telexNumber"], "id-at-telexNumber"],
			"2.5.4.21.1" => ["ITU-T X.520 (10/2019), clause 6.7.2", "Collective Telex Number", ["c-TelexNumber"], "id-at-collectiveTelexNumber"],
			"2.5.4.22" => ["ITU-T X.520 (10/2019), clause 6.7.3", "Teletex Terminal Identifier", [], "id-at-teletexTerminalIdentifier"],
			"2.5.4.22.1" => ["ITU-T X.520 (10/2019), clause 6.7.3", "Collective Teletex Terminal Identifier", [], "id-at-collectiveTeletexTerminalIdentifier"],
			"2.5.4.23" => ["ITU-T X.520 (10/2019), clause 6.7.4", "Facsimile telephone number", ["facsimileTelephoneNumber"], "id-at-facsimileTelephoneNumber"],
			"2.5.4.23.1" => ["ITU-T X.520 (10/2019), clause 6.7.4", "Collective Facsimile Telephone Number", ["c-FacsimileTelephoneNumber"], "id-at-collectiveFacsimileTelephoneNumber"],
			"2.5.4.24" => ["ITU-T X.520 (10/2019), clause 6.7.5", "X.121 Address", ["x121Address"], "id-at-x121Address"],
			"2.5.4.25" => ["ITU-T X.520 (10/2019), clause 6.7.6", "International ISDN Number", ["internationalISDNNumber"], "id-at-internationalISDNNumber"],
			"2.5.4.25.1" => ["ITU-T X.520 (10/2019), clause 6.7.6", "Collective International ISDN Number", ["c-InternationalISDNNumber"], "id-at-collectiveInternationalISDNNumber"],
			"2.5.4.26" => ["ITU-T X.520 (10/2019), clause 6.7.7", "Registered Address", ["registeredAddress"], "id-at-registeredAddress"],
			"2.5.4.27" => ["ITU-T X.520 (10/2019), clause 6.7.8", "Destination indicator", ["destinationIndicator"], "id-at-destinationIndicator"],
			"2.5.4.66" => ["ITU-T X.520 (10/2019), clause 6.7.9", "Communications Service", ["communicationsService"], "id-at-communicationsService"],
			"2.5.4.67" => ["ITU-T X.520 (10/2019), clause 6.7.10", "Communications Network", ["communicationsNetwork"], "id-at-communicationsNetwork"],
			// ITU-T X.520 (10/2019), clause 6.8 Preferences attribute types
			"2.5.4.28" => ["ITU-T X.520 (10/2019), clause 6.8.1", "Preferred Delivery Method", ["preferredDeliveryMethod"], "id-at-preferredDeliveryMethod"],
			// ITU-T X.520 (10/2019), clause 6.9 OSI application attribute types
			"2.5.4.29" => ["ITU-T X.520 (10/2019), clause 6.9.1", "Presentation Address", ["presentationAddress"], "id-at-presentationAddress"],
			"2.5.4.30" => ["ITU-T X.520 (10/2019), clause 6.9.2", "Supported Application Context", ["supportedApplicationContext"], "id-at-supportedApplicationContext"],
			"2.5.4.48" => ["ITU-T X.520 (10/2019), clause 6.9.3", "Protocol Information", [], "id-at-protocolInformation"],
			// ITU-T X.520 (10/2019), clause 6.10 Relational attribute types
			"2.5.4.49" => ["ITU-T X.520 (10/2019), clause 6.10.1", "Distinguished Name", ["distinguishedName"], "id-at-distinguishedName"],
			"2.5.4.31" => ["ITU-T X.520 (10/2019), clause 6.10.2", "Member", ["member"], "id-at-member"],
			"2.5.4.50" => ["ITU-T X.520 (10/2019), clause 6.10.3", "Unique Member", ["uniqueMember"], "id-at-uniqueMember"],
			"2.5.4.32" => ["ITU-T X.520 (10/2019), clause 6.10.4", "Owner", ["owner"], "id-at-owner"],
			"2.5.4.33" => ["ITU-T X.520 (10/2019), clause 6.10.5", "Role Occupant", ["roleOccupant"], "id-at-roleOccupant"],
			"2.5.4.34" => ["ITU-T X.520 (10/2019), clause 6.10.6", "See Also", ["seeAlso"], "id-at-seeAlso"],
			// ITU-T X.520 (10/2019), clause 6.11 Domain attribute types
			"2.5.4.54" => ["ITU-T X.520 (10/2019), clause 6.11.1", "DMD Name", [], "id-at-dmdName"],
			// ITU-T X.520 (10/2019), clause 6.12 Hierarchical attribute types
			"2.17.1.2.0" => ["ITU-T X.520 (10/2019), clause 6.12.1", "Top level object identifier arc", [], "id-oidC1"],
			"2.17.1.2.1" => ["ITU-T X.520 (10/2019), clause 6.12.2", "Second level object identifier arc", [], "id-oidC2"],
			"2.17.1.2.2" => ["ITU-T X.520 (10/2019), clause 6.12.3", "Lower level object identifier arc", [], "id-oidC"],
			"2.5.4.89" => ["ITU-T X.520 (10/2019), clause 6.12.4", "URN component", ["urnC"], "id-at-urnC"],
			// ITU-T X.520 (10/2019), clause 6.13 Attributes for applications using tag-based identification
			"2.5.4.78" => ["ITU-T X.520 (10/2019), clause 6.13.1", "Tag OID", ["tagOid"], "id-at-tagOid"],
			"2.5.4.79" => ["ITU-T X.520 (10/2019), clause 6.13.2", "UII Format", ["uiiFormat"], "id-at-uiiFormat"],
			"2.5.4.80" => ["ITU-T X.520 (10/2019), clause 6.13.3 (LDAP-NAME found in Annex A only)", "UII in URN", ["uiiInUrn"], "id-at-uiiInUrn"],
			"2.5.4.81" => ["ITU-T X.520 (10/2019), clause 6.13.4", "Content URL", ["contentUrl"], "id-at-contentUrl"],
			"2.5.4.90" => ["ITU-T X.520 (10/2019), clause 6.13.5", "UII", ["uii"], "id-at-uii"],
			"2.5.4.91" => ["ITU-T X.520 (10/2019), clause 6.13.6", "EPC", ["epc"], "id-at-epc"],
			"2.5.4.92" => ["ITU-T X.520 (10/2019), clause 6.13.7", "Tag AFI", ["tagAfi"], "id-at-tagAfi"],
			"2.5.4.93" => ["ITU-T X.520 (10/2019), clause 6.13.8", "EPC format", ["epcFormat"], "id-at-epcFormat"],
			"2.5.4.94" => ["ITU-T X.520 (10/2019), clause 6.13.9", "EPC in URN", ["epcInUrn"], "id-at-epcInUrn"],
			"2.5.4.95" => ["ITU-T X.520 (10/2019), clause 6.13.10", "LDAP URL", ["ldapUrl"], "id-at-ldapUrl"],
			"2.5.4.96" => ["ITU-T X.520 (10/2019), clause 6.13.11", "Tag location", ["tagLocation"], "id-at-tagLocation"],
			// ITU-T X.520 (10/2019), clause 6.14 Simple Authentication attributes held by object entries
			"2.5.4.35" => ["ITU-T X.520 (10/2019), clause 6.14.1 | X.509, Part 8", "Multi-valued user password", ["userPassword"], "id-at-userPassword"],
			"2.5.4.85" => ["ITU-T X.520 (10/2019), clause 6.14.2 | Annex B", "Single-valued user password", ["userPwd"], "id-at-userPwd"],
			"2.5.18.22" => ["ITU-T X.520 (10/2019), clause 6.14.3", "Password Start Time", ["pwdStartTime"], "id-oa-pwdStartTime"],
			"2.5.18.23" => ["ITU-T X.520 (10/2019), clause 6.14.4", "Password expiry time", ["pwdExpiryTime"], "id-oa-pwdExpiryTime"],
			"2.5.18.24" => ["ITU-T X.520 (10/2019), clause 6.14.5", "Password End Time", ["pwdEndTime"], "id-oa-pwdEndTime"],
			"2.5.18.25" => ["ITU-T X.520 (10/2019), clause 6.14.6", "Password fails", ["pwdFails"], "id-oa-pwdFails"],
			"2.5.18.26" => ["ITU-T X.520 (10/2019), clause 6.14.7", "Password failure time", ["pwdFailureTime"], "id-oa-pwdFailureTime"],
			"2.5.18.27" => ["ITU-T X.520 (10/2019), clause 6.14.8", "Password graces used", ["pwdGracesUsed"], "id-oa-pwdGracesUsed"],
			"2.5.18.28" => ["ITU-T X.520 (10/2019), clause 6.14.9", "User password history", [], "id-oa-userPwdHistory"],
			"2.5.18.29" => ["ITU-T X.520 (10/2019), clause 6.14.10", "User password recently expired", [], "id-oa-userPwdRecentlyExpired"],
			// ITU-T X.520 (10/2019), clause 6.15 Password policy attributes
			"2.5.18.30" => ["ITU-T X.520 (10/2019), clause 6.15.1", "Password modify entry allowed", ["pwdModifyEntryAllowed"], "id-oa-pwdModifyEntryAllowed"],
			"2.5.18.31" => ["ITU-T X.520 (10/2019), clause 6.15.2", "Password change allowed", ["pwdChangeAllowed"], "id-oa-pwdChangeAllowed"],
			"2.5.18.32" => ["ITU-T X.520 (10/2019), clause 6.15.3", "Password maximum age", ["pwdMaxAge"], "id-oa-pwdMaxAge"],
			"2.5.18.33" => ["ITU-T X.520 (10/2019), clause 6.15.4", "Password expiry age", ["pwdExpiryAge"], "id-oa-pwdExpiryAge"],
			// ITU-T X.520 (10/2019), clause 6.15.5 Password quality rule attribute types
			"2.5.18.34" => ["ITU-T X.520 (10/2019), clause 6.15.5.1", "Password minimum length", ["pwdMinLength"], "id-oa-pwdMinLength"],
			"2.5.18.35" => ["ITU-T X.520 (10/2019), clause 6.15.5.2", "Password vocabulary", ["pwdVocabulary"], "id-oa-pwdVocabulary"],
			"2.5.18.36" => ["ITU-T X.520 (10/2019), clause 6.15.5.3", "Password alphabet", ["pwdAlphabet"], "id-oa-pwdAlphabet"],
			"2.5.18.37" => ["ITU-T X.520 (10/2019), clause 6.15.5.4", "Password dictionaries", ["pwdDictionaries"], "id-oa-pwdDictionaries"],
			"2.5.18.38" => ["ITU-T X.520 (10/2019), clause 6.15.6", "Password expiry warning", ["pwdExpiryWarning"], "id-oa-pwdExpiryWarning"],
			"2.5.18.39" => ["ITU-T X.520 (10/2019), clause 6.15.7", "Password graces", ["pwdGraces"], "id-oa-pwdGraces"],
			"2.5.18.40" => ["ITU-T X.520 (10/2019), clause 6.15.8", "Password failure duration", ["pwdFailureDuration"], "id-oa-pwdFailureDuration"],
			"2.5.18.41" => ["ITU-T X.520 (10/2019), clause 6.15.9", "Password lockout duration", ["pwdLockoutDuration"], "id-oa-pwdLockoutDuration"],
			"2.5.18.42" => ["ITU-T X.520 (10/2019), clause 6.15.10", "Password maximum failures", ["pwdMaxFailures"], "id-oa-pwdMaxFailures"],
			"2.5.18.43" => ["ITU-T X.520 (10/2019), clause 6.15.11", "Password maximum time in history", ["pwdMaxTimeInHistory"], "id-oa-pwdMaxTimeInHistory"],
			"2.5.18.44" => ["ITU-T X.520 (10/2019), clause 6.15.12", "Password minimum time in history", ["pwdMinTimeInHistory"], "id-oa-pwdMinTimeInHistory"],
			"2.5.18.45" => ["ITU-T X.520 (10/2019), clause 6.15.13", "Password history slots", ["pwdHistorySlots"], "id-oa-pwdHistorySlots"],
			"2.5.18.46" => ["ITU-T X.520 (10/2019), clause 6.15.14", "Password recently expired duration", ["pwdRecentlyExpiredDuration"], "id-oa-pwdRecentlyExpiredDuration"],
			"2.5.18.47" => ["ITU-T X.520 (10/2019), clause 6.15.15", "Password encryption algorithm", ["pwdEncAlg"], "id-oa-pwdEncAlg"],
			// ITU-T X.520 (10/2019), clause 6.16 Notification attributes
			// ITU-T X.520 (10/2019), clause 6.16.1 DSA problem
			// ITU-T X.520 (10/2019), clause 6.16.2 Search service problem
			// ITU-T X.520 (10/2019), clause 6.16.3 Service-type
			// ITU-T X.520 (10/2019), clause 6.16.4 Attribute type list
			// ITU-T X.520 (10/2019), clause 6.16.5 Matching rule list
			// ITU-T X.520 (10/2019), clause 6.16.6 Filter item
			// ITU-T X.520 (10/2019), clause 6.16.7 Attribute combinations
			// ITU-T X.520 (10/2019), clause 6.16.8 Context type list
			// ITU-T X.520 (10/2019), clause 6.16.9 Context list
			// ITU-T X.520 (10/2019), clause 6.16.10 Context combinations
			// ITU-T X.520 (10/2019), clause 6.16.11 Hierarchy select list
			// ITU-T X.520 (10/2019), clause 6.16.12 Search control options list
			// ITU-T X.520 (10/2019), clause 6.16.13 Service Control Options List
			// ITU-T X.520 (10/2019), clause 6.16.14 Multiple matching localities
			// ITU-T X.520 (10/2019), clause 6.16.15 Proposed relaxation
			// ITU-T X.520 (10/2019), clause 6.16.16 Applied relaxation
			// ITU-T X.520 (10/2019), clause 6.16.17 Password response
			// ITU-T X.520 (10/2019), clause 6.16.18 LDAP diagnostic message
			// ITU-T X.520 (10/2019), clause 6.17 LDAP defined attribute types
			"0.9.2342.19200300.100.1.1" => ["ITU-T X.520 (10/2019), clause 6.17.1", "User ID", ["uid"], "id-coat-uid"],
			"0.9.2342.19200300.100.1.25" => ["ITU-T X.520 (10/2019), clause 6.17.2", "Domain component", ["dc"], "id-coat-dc"],
			"0.9.2342.19200300.100.1.3" => ["ITU-T X.520 (10/2019), clause 6.17.3", "Mail", ["mail"], "id-coat-mail"],

			// Extracted from https://www.itu.int/rec/T-REC-X.509-201910-I/en
			"2.5.4.36" => ["Rec. ITU-T X.509 (10/2019), clause 13.2.1", "X.509 user certificate", ["userCertificate"], "id-at-userCertificate"],
			"2.5.4.37" => ["Rec. ITU-T X.509 (10/2019), clause 13.2.2", "X.509 CA certificate", ["cACertificate"], "id-at-cAcertificate"],
			"2.5.4.40" => ["Rec. ITU-T X.509 (10/2019), clause 13.2.3", "X.509 cross certificate pair", ["crossCertificatePair"], "id-at-crossCertificatePair"],
			"2.5.4.39" => ["Rec. ITU-T X.509 (10/2019), clause 13.2.4", "X.509 certificate revocation list", ["certificateRevocationList"], "id-at-certificateRevocationList"],
			"2.5.4.101" => ["Rec. ITU-T X.509 (10/2019), clause 13.2.5", "X.509 EEPK certificate revocation list", ["eepkCertificateRevocationList"], "id-at-eepkCertificateRevocationList"],
			"2.5.4.38" => ["Rec. ITU-T X.509 (10/2019), clause 13.2.6", "X.509 CA revocation list", ["authorityRevocationList"], "id-at-authorityRevocationList"],
			"2.5.4.53" => ["Rec. ITU-T X.509 (10/2019), clause 13.2.7", "X.509 delta revocation list", ["deltaRevocationList"], "id-at-deltaRevocationList"],
			"2.5.4.52" => ["Rec. ITU-T X.509 (10/2019), clause 13.2.8", "X.509 support algorithms", ["supportedAlgorithms"], "id-at-supportedAlgorithms"],
			"2.5.4.68" => ["Rec. ITU-T X.509 (10/2019), clause 13.2.9", "Certification practice statement", [], "id-at-certificationPracticeStmt"],
			"2.5.4.69" => ["Rec. ITU-T X.509 (10/2019), clause 13.2.10", "Certificate policy", [], "id-at-certificatePolicy"],
			"2.5.4.70" => ["Rec. ITU-T X.509 (10/2019), clause 13.2.11", "PKI path", [], "id-at-pkiPath"],
			"2.5.4.103" => ["Rec. ITU-T X.509 (10/2019), clause 13.2.12", "X.509 supported publiv key algorithms", ["supportedPublicKeyAlgorithms"], "id-at-supportedPublicKeyAlgorithms"],
			// Rec. ITU-T X.509 (10/2019), clause 19.2 PMI directory attributes
			"2.5.4.58" => ["Rec. ITU-T X.509 (10/2019), clause 19.2.1", "Attribute certificate", [], "id-at-attributeCertificate"],
			"2.5.4.61" => ["Rec. ITU-T X.509 (10/2019), clause 19.2.2", "AA certificate", [], "id-at-aACertificate"],
			"2.5.4.62" => ["Rec. ITU-T X.509 (10/2019), clause 19.2.3", "Attribute descriptor certificate", [], "id-at-attributeDescriptorCertificate"],
			"2.5.4.59" => ["Rec. ITU-T X.509 (10/2019), clause 19.2.4", "X.509 Attr certificate revocation list", ["AttrCertificateRevocationList"], "id-at-attributeCertificateRevocationList"],
			"2.5.4.102" => ["Rec. ITU-T X.509 (10/2019), clause 19.2.5", "X.509 EEAttr certificate revocation list", ["EEAttrCertificateRevocationList"], "id-at-eeAttrCertificateRevocationList"],
			"2.5.4.63" => ["Rec. ITU-T X.509 (10/2019), clause 19.2.6", "X.509 AA certificate revocation list", ["AACertificateRevocationList"], "id-at-attributeAuthorityRevocationList"],
			"2.5.4.73" => ["Rec. ITU-T X.509 (10/2019), clause 19.2.7", "Delegation path", [], "id-at-delegationPath"],
			"2.5.4.71" => ["Rec. ITU-T X.509 (10/2019), clause 19.2.8", "Privilege policy", [], "id-at-privPolicy"],
			"2.5.4.74" => ["Rec. ITU-T X.509 (10/2019), clause 19.2.9", "Protected privilege policy", [], "id-at-protPrivPolicy"],
			"2.5.4.76" => ["Rec. ITU-T X.509 (10/2019), clause 19.2.10", "XML Protected privilege policy", [], "id-at-xmlPrivPolicy"],
			"2.5.4.72" => ["Rec. ITU-T X.509 (10/2019), clause 16.5.1", "Role", [], "id-at-role"],
			"2.5.4.75" => ["Rec. ITU-T X.509 (10/2019), clause 16.7", "XML privilege information", [], "id-at-xMLPrivilegeInfo"],
			"2.5.4.82" => ["Rec. ITU-T X.509 (10/2019), clause 16.8.1 ", "Permission", [], "id-at-permission"],

			// Extracted from https://www.itu.int/rec/T-REC-X.501-201910-I/en
			"2.5.4.0"  => ["Rec. ITU-T X.501 (10/2019), clause 13.4.8", "Object Class", ["objectClass"], "id-at-objectClass"],
			"2.5.4.1"  => ["Rec. ITU-T X.501 (10/2019), clause 13.4.8", "Aliased Object Name", ["aliasedObjectName"], "id-at-aliasedEntryName"],
			"2.5.4.84" => ["Rec. ITU-T X.501 (10/2019), clause 14.9", "Password attribute", ["pwdAttribute"], "id-at-pwdAttribute"],
			"2.5.4.55" => ["Rec. ITU-T X.501 (10/2019), clause 19.5", "Clearance", [], "id-at-clearance"],
			"2.5.4.56" => ["Rec. ITU-T X.501, obsolete", "Default Dir Qop", [], "id-at-defaultDirQop"],
			"2.5.4.57" => ["Rec. ITU-T X.501 (10/2019), clause 20.2", "Attribute integrity info", [], "id-at-attributeIntegrityInfo"],
			"2.5.4.60" => ["Rec. ITU-T X.501, obsolete", "Conf key info", [], "id-at-confKeyInfo"],

			// Extracted from https://www.itu.int/rec/T-REC-X.511-201910-I/en
			"2.5.4.64" => ["Rec. ITU-T X.511 (10/2019), clause 7.7.2", "Family information in entry information", [], "id-at-family-information"],

			// Extracted from https://www.rfc-editor.org/rfc/RFC 4524.html
			// IETF RFC 4524, clause 2 (COSINE Attribute Types)
			"0.9.2342.19200300.100.1.37" => ["IETF RFC 4524, clause 2.1", "Associated Domain", ["associatedDomain"], "associatedDomain"],
			"0.9.2342.19200300.100.1.38" => ["IETF RFC 4524, clause 2.2", "Associated Name", ["associatedName"], "associatedName"],
			"0.9.2342.19200300.100.1.48" => ["IETF RFC 4524, clause 2.3", "Building Name", ["buildingName"], "buildingName"],
			"0.9.2342.19200300.100.1.43" => ["IETF RFC 4524, clause 2.4", "Friendly Country Name", ["co", "friendCountryName"], "friendCountryName"],
			"0.9.2342.19200300.100.1.14" => ["IETF RFC 4524, clause 2.5", "Document Author", ["documentAuthor"], "documentAuthor"],
			"0.9.2342.19200300.100.1.11" => ["IETF RFC 4524, clause 2.6", "Document Identifier", ["documentIdentifier"], "documentIdentifier"],
			"0.9.2342.19200300.100.1.15" => ["IETF RFC 4524, clause 2.7", "Document Location", ["documentLocation"], "documentLocation"],
			"0.9.2342.19200300.100.1.56" => ["IETF RFC 4524, clause 2.8", "Document Publisher", ["documentPublisher"], "documentPublisher"],
			"0.9.2342.19200300.100.1.12" => ["IETF RFC 4524, clause 2.9", "Document Title", ["documentTitle"], "documentTitle"],
			"0.9.2342.19200300.100.1.13" => ["IETF RFC 4524, clause 2.10", "Document Version", ["documentVersion"], "documentVersion"],
			"0.9.2342.19200300.100.1.5" => ["IETF RFC 4524, clause 2.11", "Favorite Drink", ["drink", "favoriteDrink", "favouriteDrink"], "drink"],
			"0.9.2342.19200300.100.1.20" => ["IETF RFC 4524, clause 2.12", "Home Phone", ["homeTelephoneNumber", "homePhone"], "homePhone"],
			"0.9.2342.19200300.100.1.39" => ["IETF RFC 4524, clause 2.13", "Home Postal Address", ["homePostalAddress"], "homePostalAddress"],
			"0.9.2342.19200300.100.1.9" => ["IETF RFC 4524, clause 2.14", "Host", ["host"], "host"],
			"0.9.2342.19200300.100.1.4" => ["IETF RFC 4524, clause 2.15", "Info", ["info"], "info"],
			//(already defined in X.520) "0.9.2342.19200300.100.1.3" => ["IETF RFC 4524, clause 2.16", "Mail", ["rfc822Mailbox", "mail"], "mail"],
			"0.9.2342.19200300.100.1.10" => ["IETF RFC 4524, clause 2.17", "Manager", ["manager"], "manager"],
			"0.9.2342.19200300.100.1.41" => ["IETF RFC 4524, clause 2.18", "Mobile", ["mobileTelephoneNumber", "mobile"], "mobile"],
			"0.9.2342.19200300.100.1.45" => ["IETF RFC 4524, clause 2.19", "Organizational Status", ["organizationalStatus"], "organizationalStatus"],
			"0.9.2342.19200300.100.1.42" => ["IETF RFC 4524, clause 2.20", "Pager", ["pagerTelephoneNumber", "pager"], "pager"],
			"0.9.2342.19200300.100.1.40" => ["IETF RFC 4524, clause 2.21", "Personal Title", ["personalTitle"], "personalTitle"],
			"0.9.2342.19200300.100.1.6" => ["IETF RFC 4524, clause 2.22", "Room Number", ["roomNumber"], "roomNumber"],
			"0.9.2342.19200300.100.1.21" => ["IETF RFC 4524, clause 2.23", "secretary", ["secretary"], "secretary"],
			"0.9.2342.19200300.100.1.44" => ["IETF RFC 4524, clause 2.24", "Unique Identifier", ["uniqueIdentifier"], "uniqueIdentifier"],
			"0.9.2342.19200300.100.1.8" => ["IETF RFC 4524, clause 2.25", "User Class", ["userClass"], "userClass"],

			// Extracted from https://www.rfc-editor.org/rfc/rfc1274.html (only the ones which don't exist above)
			"0.9.2342.19200300.100.1.2" => ["IETF RFC 1274, clause 9.3.2", "Text Encoded O/R Address", ["textEncodedORAddress"], "textEncodedORAddress"],
			"0.9.2342.19200300.100.1.7" => ["IETF RFC 1274, clause 9.3.7", "Photo", ["photo"], "photo"],
			"0.9.2342.19200300.100.1.22" => ["IETF RFC 1274, clause 9.3.18", "Other Mailbox", ["otherMailbox"], "otherMailbox"],
			"0.9.2342.19200300.100.1.23" => ["IETF RFC 1274, clause 9.3.19", "Last Modified Time", ["lastModifiedTime"], "lastModifiedTime"],
			"0.9.2342.19200300.100.1.24" => ["IETF RFC 1274, clause 9.3.20", "Last Modified By", ["lastModifiedBy"], "lastModifiedBy"],
			"0.9.2342.19200300.100.1.26" => ["IETF RFC 1274, clause 9.3.22", "DNS ARecord", ["aRecord"], "aRecord"],
			"0.9.2342.19200300.100.1.27" => ["IETF RFC 1274 ???", "MD Record", ["mDRecord"], "mDRecord"],
			"0.9.2342.19200300.100.1.28" => ["IETF RFC 1274, clause 9.3.23", "MX Record", ["mXRecord"], "mXRecord"],
			"0.9.2342.19200300.100.1.29" => ["IETF RFC 1274, clause 9.3.24", "NS Record", ["nSRecord"], "nSRecord"],
			"0.9.2342.19200300.100.1.30" => ["IETF RFC 1274, clause 9.3.25", "SOA Record", ["sOARecord"], "sOARecord"],
			"0.9.2342.19200300.100.1.31" => ["IETF RFC 1274, clause 9.3.26", "CNAME Record", ["cNAMERecord"], "cNAMERecord"],
			"0.9.2342.19200300.100.1.46" => ["IETF RFC 1274, clause 9.3.36", "Janet Mailbox", ["janetMailbox"], "janetMailbox"],
			"0.9.2342.19200300.100.1.47" => ["IETF RFC 1274, clause 9.3.37", "Mail Preference Option", ["mailPreferenceOption"], "mailPreferenceOption"],
			"0.9.2342.19200300.100.1.49" => ["IETF RFC 1274, clause 9.3.39", "DSA Quality", ["dSAQuality"], "dSAQuality"],
			"0.9.2342.19200300.100.1.50" => ["IETF RFC 1274, clause 9.3.40", "Single Level Quality", ["singleLevelQuality"], "singleLevelQuality"],
			"0.9.2342.19200300.100.1.51" => ["IETF RFC 1274, clause 9.3.41", "Subtree Minimum Quality", ["subtreeMinimumQuality"], "subtreeMinimumQuality"],
			"0.9.2342.19200300.100.1.52" => ["IETF RFC 1274, clause 9.3.42", "Subtree Maximum Quality", ["subtreeMaximumQuality"], "subtreeMaximumQuality"],
			"0.9.2342.19200300.100.1.53" => ["IETF RFC 1274, clause 9.3.43", "Personal Signature", ["personalSignature"], "personalSignature"],
			"0.9.2342.19200300.100.1.54" => ["IETF RFC 1274, clause 9.3.44", "DIT Redirect", ["dITRedirect"], "dITRedirect"],
			"0.9.2342.19200300.100.1.55" => ["IETF RFC 1274, clause 9.3.45", "Audio", ["audio"], "audio"],

			// Extracted from https://www.rfc-editor.org/rfc/rfc2798.html
			// IETF RFC 2798, clause 2 Attribute Types Used in the inetOrgPerson Object Class
			"2.16.840.1.113730.3.1.1" => ["IETF RFC 2798, clause 2.1", "Vehicle license or registration plate", ["carLicense"], "carLicense"],
			"2.16.840.1.113730.3.1.2" => ["IETF RFC 2798, clause 2.2", "Department number", ["departmentNumber"], "departmentNumber"],
   			"2.16.840.1.113730.3.1.241" => ["IETF RFC 2798, clause 2.3", "Display Name", ["displayName"], "displayName"],
   			"2.16.840.1.113730.3.1.3" => ["IETF RFC 2798, clause 2.4", "Employee Number", ["employeeNumber"], "employeeNumber"],
   			"2.16.840.1.113730.3.1.4" => ["IETF RFC 2798, clause 2.5", "Employee Type", ["employeeType"], "employeeType"],
   			"0.9.2342.19200300.100.1.60" => ["IETF RFC 2798, clause 2.6", "JPEG Photograph", ["jpegPhoto"], "jpegPhoto"],
   			"2.16.840.1.113730.3.1.39" => ["IETF RFC 2798, clause 2.7", "Preferred Language", ["preferredLanguage"], "preferredLanguage"],
   			"2.16.840.1.113730.3.1.40" => ["IETF RFC 2798, clause 2.8", "User S/MIME Certificate", ["userSMIMECertificate"], "userSMIMECertificate"],
   			"2.16.840.1.113730.3.1.216" => ["IETF RFC 2798, clause 2.9", "User PKCS #12", ["userPKCS12"], "userPKCS12"],

		];

		// Additional identifiers found at https://www.ibm.com/docs/en/zos/2.2.0?topic=SSLTBW_2.2.0/com.ibm.tcp.ipsec.ipsec.help.doc/com/ibm/tcp/ipsec/nss/NssImageServerPs.RB_X500.htm
		$ldap_attributes["1.2.840.113549.1.9.1"] = ["???", "E-mail address", ["E", "EMAIL", "EMAILADDRESS"], "pkcs-9-at-emailAddress"]; //(preferred: EMAIL)
		$ldap_attributes["2.5.4.17"][2][] = "PC"; // Postal code
		$ldap_attributes["2.5.4.8"][2][] = "S"; // State or province
		$ldap_attributes["2.5.4.8"][2][] = "SP"; // State or province
		$ldap_attributes["2.5.4.12"][2][] = "T"; // Title

		// Additional identifiers found at https://www.cryptosys.net/pki/manpki/pki_distnames.html
		$ldap_attributes["2.5.4.42"][2][] = "G"; // Given name
		$ldap_attributes["2.5.4.42"][2][] = "GN"; // Given name

		// Found in OpenSSL https://github.com/openssl/openssl/blob/a2608e4bc430d6216bbf36f50a29278e8759103a/include/openssl/obj_mac.h
		$ldap_attributes["2.5.4.100"][2][] = "dnsName"; // X.520 says "DNS Name"
		$ldap_attributes["2.5.4.72"][2][] = "role";

		// Merge identifiers from X.520 and RFC 4524
		$ldap_attributes["0.9.2342.19200300.100.1.3"][2][] = "rfc822Mailbox";

		// IETF RFC 1274 uses the identifier "userid" instead of "uid".
		$ldap_attributes["0.9.2342.19200300.100.1.1"][2][] = "userId";

		// Additional identifiers by Daniel Marschall (these attributes don't have a LDAP-NAME property in X.520, so we set something based on the ASN.1 alphanumeric identifier)
		$ldap_attributes["2.5.4.2"][2][] = "knowledgeInformation";
		$ldap_attributes["2.5.4.65"][2][] = "pseudonym";
		$ldap_attributes["2.5.4.77"][2][] = "uuidpair";
		$ldap_attributes["2.5.4.22"][2][] = "teletexTerminalIdentifier";
		$ldap_attributes["2.5.4.22.1"][2][] = "collectiveTeletexTerminalIdentifier";
		$ldap_attributes["2.5.4.22.1"][2][] = "c-teletexTerminalIdentifier";
		$ldap_attributes["2.5.4.48"][2][] = "protocolInformation";
		$ldap_attributes["2.5.4.54"][2][] = "dmdName";
		$ldap_attributes["2.17.1.2.0"][2][] = "oidC1";
		$ldap_attributes["2.17.1.2.1"][2][] = "oidC2";
		$ldap_attributes["2.17.1.2.2"][2][] = "oidC";
		$ldap_attributes["2.5.18.28"][2][] = "userPwdHistory";
		$ldap_attributes["2.5.18.29"][2][] = "userPwdRecentlyExpired";

		// Vendor specific stuff
		$ldap_attributes["1.3.6.1.4.1.37476.2.5.2.9.4.1"] = ["ViaThinkSoft: OidplusAttributeTypes ASN.1 Module, Version 1", "OIDplus System", ["OidplusSystemId"], "system-id"];
		$ldap_attributes["1.3.6.1.4.1.37476.2.5.2.9.4.2"] = ["ViaThinkSoft: OidplusAttributeTypes ASN.1 Module, Version 1", "OIDplus Object Hash", ["OidplusObjectHash"], "object-hash"];

		// Test data
		/*
		for ($i=0; $i<=106; $i++) {
			if (!isset($ldap_attributes["2.5.4.$i"])) echo "WARNING: 2.5.4.$i MISSING<br>\n";
		}
		for ($i=1; $i<=60; $i++) {
			if (($i>15) && ($i<20)) continue;
			if (($i>31) && ($i<37)) continue;
			if (($i>56) && ($i<60)) continue;
			if (!isset($ldap_attributes["0.9.2342.19200300.100.1.$i"])) echo "WARNING: 0.9.2342.19200300.100.1.$i MISSING<br>\n";
		}
		foreach($ldap_attributes as $a) {
			if (count($a) !== 4) echo "WARNING: ".print_r($a,true)."<br>\n";
		}
		*/

		return $ldap_attributes;
	}

	/**
	 * @param string $val
	 * @param bool $escape_equal_sign
	 * @param bool $escape_backslash
	 * @param bool $allow_ber
	 * @return string
	 */
	protected static function escapeAttributeValue(string $val, bool $escape_equal_sign, bool $escape_backslash, bool $allow_ber): string {
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

		if (!$allow_ber) {
			if (substr($val, 0, 1) == '#') {
				$val = '\\' . $val;
			}
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
					$v = self::escapeAttributeValue($v, /*$escape_equal_sign=*/false, /*$escape_backslash=*/false, /*$allow_ber=*/true);
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

			$ary[0] = self::escapeAttributeValue($ary[0], /*$escape_equal_sign=*/false, /*$escape_backslash=*/false, /*$allow_ber=*/true);
			$ary[1] = self::escapeAttributeValue($ary[1], /*$escape_equal_sign=*/true,  /*$escape_backslash=*/false, /*$allow_ber=*/true);

			if (oid_valid_dotnotation($ary[0], false, false, 1)) {
				$arc = $ary[0] . '=' . $ary[1]; // return the auto-corrected identifier
				return true;
			}

			if (substr($ary[1],0,1) == '#') {
				$hex_code = substr($ary[1],1);
				$is_valid_hexstr = preg_match("/^[a-f0-9]{2,}$/i", $hex_code) && !(strlen($hex_code) & 1);
				if (!$is_valid_hexstr) {
					throw new OIDplusException(_L('"%1" is not a valid hex string. Note: In case you want a string starting with a hashtag, you need to add a backslash in front of it.', $ary[1]));
				}

				// TODO: Theoretically, we should also check if the hex string is valid BER code... but that is a very hard task
				//       Also, if we go even a step further, then we could also check if the data is valid (correct ASN.1 type).

			}

			$known_attr_names = self::getKnownAttributeNames();
			foreach ($known_attr_names as $oid => list($source, $englishName, $ldapNames, $oidName)) {
				foreach ($ldapNames as $abbr) {
					if (strtolower($abbr) === strtolower($ary[0])) {
						$arc = $ary[0] . '=' . $ary[1]; // return the auto-corrected identifier
						return true;
					}
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
			$found_hf_name = _L('Unknown attribute');

			foreach ($known_attr_names as $oid => list($source, $englishName, $ldapNames, $oidName)) {
				if ($ary[0] == $oid) {
					$found_oid = $oid;
					$found_hf_name = $englishName;
					break;
				}
				foreach ($ldapNames as $abbr) {
					if (strtolower($abbr) == strtolower($ary[0])) {
						$found_oid = $oid;
						$found_hf_name = $englishName;
						break 2;
					}
				}
			}

			$html_dce_ad_notation .= '/<abbr title="'.htmlentities($found_hf_name).'">'.htmlentities(strtoupper($ary[0])).'</abbr>='.htmlentities($ary[1]);
			$html_ldap_notation = '<abbr title="'.htmlentities($found_hf_name).'">'.htmlentities(strtoupper($ary[0])).'</abbr>='.htmlentities(str_replace(',','\\,',$ary[1])) . ($html_ldap_notation == '' ? '' : ', ' . $html_ldap_notation);

			// TODO: how are multi-valued values handled?
			// TODO: We cannot simply encode everything to UTF8String, because some attributes need to be encoded as binary, integer, datetime, etc.!
			$html_encoded_str = '#<abbr title="'._L('ASN.1: UTF8String').'">'.sprintf('%02s', strtoupper(dechex(0x0C/*UTF8String*/))).'</abbr>';
			if (substr($ary[1],0,1) == '#') {
				$html_encoded_str = htmlentities(strtoupper($ary[1]));
			} else {
				$utf8 = vts_utf8_encode($ary[1]);
				$html_encoded_str .= '<abbr title="'._L('Length').'">'.sprintf('%02s', strtoupper(dechex(strlen($utf8)))).'</abbr>'; // TODO: This length does only work for length <= 0x7F! The correct implementation is described here: https://misc.daniel-marschall.de/asn.1/oid_facts.html#chap1_2
				$html_encoded_str .= '<abbr title="'.htmlentities($ary[1]).'">';
				for ($i=0; $i<strlen($utf8); $i++) {
					$char = substr($utf8, $i, 1);
					$html_encoded_str .= sprintf('%02s', strtoupper(dechex(ord($char))));
				}
				$html_encoded_str .= '</abbr>';
			}
			if ($ary[0] == $found_oid) {
				$html_encoded_string_notation = '<abbr title="'.htmlentities($found_hf_name).'">'.htmlentities($found_oid).'</abbr>='.$html_encoded_str . ($html_encoded_string_notation == '' ? '' : ',' . $html_encoded_string_notation);
			} else {
				$html_encoded_string_notation = '<abbr title="'.htmlentities(strtoupper($ary[0]) . ' = ' . $found_hf_name).'">'.htmlentities($found_oid).'</abbr>='.$html_encoded_str . ($html_encoded_string_notation == '' ? '' : ',' . $html_encoded_string_notation);
			}
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
