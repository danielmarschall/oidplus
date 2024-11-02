[
	{
		"dummy": "<?php die('For security reasons, this file can only be accessed locally (without PHP).'.base64_decode('IgogICAgfQpdCg==')); /* @phpstan-ignore-line */ ?>"
	},
	{
		"version": "2.0.2.11",
		"date": "2024-11-02 22:00:00 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed showstopper bug when opening a root node",
			"REST API Documentation now also contains example values",
			"The lag in software distribution (meaning the delay between detecting a new release and making it available for download) has been resolved."
		]
	},
	{
		"version": "2.0.2.10",
		"date": "2024-10-27 11:20:00 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"REST API Documentation now in OpenAPI 3.1 standard, with Swagger UI visualization",
			"Implemented Attachment plugin upload/delete/download events",
			"Bugfix: ASN.1 and IRI fields could be edited for Non-OIDs. Fixed",
			"Various improvements to the code"
		]
	},
	{
		"version": "2.0.2.9",
		"date": "2024-10-15 23:10:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"URN object type: NID is now case insensitive",
			"MAC object type: URN is now urn:dev:mac instead of urn:x-oidplus:mac"
		]
	},
	{
		"version": "2.0.2.8",
		"date": "2024-09-26 21:40:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small fixes"
		]
	},
	{
		"version": "2.0.2.7",
		"date": "2024-09-12 23:20:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"DBMS Version is now shown in the system info plugin. Fixes https://github.com/danielmarschall/oidplus/issues/71",
			"Implemented URN syntax checks. Fixes https://github.com/danielmarschall/oidplus/issues/73",
			"Fixed potential issue with incompatibility between URNview layout and OT plugins where root<>ns:",
			"OOBE failure because of PKI, fixes https://github.com/danielmarschall/oidplus/issues/72",
			"Fix wrong \"Multi-Tenancy base system\" text at systeminfo plugin",
			"Link fixes",
			"URN welcome page add urn:x-weid:"
		]
	},
	{
		"version": "2.0.2.6",
		"date": "2024-09-11 01:05:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Improvements of the custom URN object type",
			"A lot of small fixes to the 'WEID tree' feature"
		]
	},
	{
		"version": "2.0.2.5",
		"date": "2024-09-10 01:25:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Plugins can now be in userdata_pub/plugins or userdata_pub/tenant/.../plugins",
			"New Object Type:  Custom Uniform Resource Names (URN)",
			"If URN Plugin is enabled, then all Object Types are inside this new URN type (in addition with custom URN types)",
			"Separate WEID-OID tree that is a clone of the OID tree, but with WEID identifiers.",
			"OID-IP now returns the new MIME Media Types application/vnd.viathinksoft.oidip+xml (XML), application/vnd.viathinksoft.oidip+json (JSON) and text/vnd.viathinksoft.oidip (TEXT)"
		]
	},
	{
		"version": "2.0.2.4",
		"date": "2024-09-07 22:05:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added URN Pseudo-Object-Type (currently dynamic clone of OID, UUID, DOI)",
			"Bug fixes"
		]
	},
	{
		"version": "2.0.2.3",
		"date": "2024-08-08 15:20:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed critical bug affecting custom design colors, introduced in 2.0.2.0"
		]
	},
	{
		"version": "2.0.2.2",
		"date": "2024-08-08 01:05:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed migration/compatibility issue"
		]
	},
	{
		"version": "2.0.2.1",
		"date": "2024-08-08 00:20:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Nostalgia plugin: Download URLs changed and 64-Bit Windows version added."
		]
	},
	{
		"version": "2.0.2.0",
		"date": "2024-08-03 23:30:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"!!! Minimum required PHP version raised from PHP 7.0 to PHP 7.4 !!!",
			"Support for PHP 8.4",
			"!!! Large tech update: New class autoloader for plugins. Custom plugins require an update, see more information here https://github.com/danielmarschall/oidplus/blob/master/doc/developer_notes/plugin_202_migration.md !!!",
			"Updated Nostalgia versions: Win95, Win311, DOS now with an uniform file format that can be shared between Windows and DOS"
		]
	},
	{
		"version": "2.0.1.28",
		"date": "2024-07-21 10:55:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Released OID-IP draft 8 which introduces the HTTP communication channel"
		]
	},
	{
		"version": "2.0.1.27",
		"date": "2024-07-19 13:00:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"In case the private/public key pair is broken or cannot be decrypted, it will not be regenerated automatically.",
			"The privkey secret file filename now contains the system ID, so it is easier to recognize"
		]
	},
	{
		"version": "2.0.1.26",
		"date": "2024-07-01 21:55:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Replaced polyfill.io with Cloudflare replacement ( https://github.com/danielmarschall/oidplus/issues/54 )",
			"Fixed OIDplus::webpath() not working on Windows servers",
			"Fixed severe graphic bug on OID pages (superior RA box)",
			"Outsourced polyfill from Core to Plugin, and let other plugins request polyfills using a feature interface"
		]
	},
	{
		"version": "2.0.1.25",
		"date": "2024-06-19 00:20:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Show \"RA Info\" also for Non-OID object types ( https://github.com/danielmarschall/oidplus/issues/45 )",
			"Allow to edit the Parent RA delegation info in the object page ( https://github.com/danielmarschall/oidplus/issues/52 )"
		]
	},
	{
		"version": "2.0.1.24",
		"date": "2024-06-15 22:40:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Internal change: Plugins now have a manifest.json rather than a manifest.xml"
		]
	},
	{
		"version": "2.0.1.23",
		"date": "2024-06-14 16:12:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Object pages now have a Delete button (only visible if the parent has write rights)"
		]
	},
	{
		"version": "2.0.1.22",
		"date": "2024-06-13 00:35:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added multi-tenancy support"
		]
	},
	{
		"version": "2.0.1.21",
		"date": "2024-04-20 01:40:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed problem with num_rows() for OCI and PDO database plugins"
		]
	},
	{
		"version": "2.0.1.20",
		"date": "2024-04-07 20:30:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fix invitation emails don't contain a link"
		]
	},
	{
		"version": "2.0.1.19",
		"date": "2024-03-07 01:05:00 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Application Identifier (AID): VTS F4 02 (Ringgold) introduced.",
			"Application Identifier (AID): VTS F4 03 (DOI) introduced.",
			"Application Identifier (AID): VTS F7 01 (ISNI) changed definition."
		]
	},
	{
		"version": "2.0.1.18",
		"date": "2024-03-07 02:05:00 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Generate Random AID functionality fixed"
		]
	},
	{
		"version": "2.0.1.17",
		"date": "2024-03-06 02:05:00 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Application Identifier (AID): VTS F4 changed to VTS F4 01 (D-U-N-S)",
			"Application Identifier (AID): VTS F7 01 (ISNI) introduced"
		]
	},
	{
		"version": "2.0.1.16",
		"date": "2024-02-10 20:00:00 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Release of Alt-Id-Plugin 1.0.8"
		]
	},
	{
		"version": "2.0.1.15",
		"date": "2024-01-25 22:15:00 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New definition of VTS-F3 AID (Device Vendor/Product ID)"
		]
	},
	{
		"version": "2.0.1.14",
		"date": "2024-01-21 23:55:00 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Increased performance for large databases",
			"Various smaller bugfixes"
		]
	},
	{
		"version": "2.0.1.13",
		"date": "2023-12-31 00:45:00 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Log entries are now displayed in a table instead of a monospace text block.",
			"JavaScript: JavaScript can now handle errors raised by PHP and show something instead of just silently failing."
		]
	},
	{
		"version": "2.0.1.12",
		"date": "2023-12-26 23:55:00 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fix broken update procedure introcuced with 2.0.1.11"
		]
	},
	{
		"version": "2.0.1.11",
		"date": "2023-12-26 16:55:00 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RA and System log plugin: Split into pages.",
			"Object Log view: only the last 100 items are shown due to overload protection (currently, no scrolling possible).",
			"Overload protection: For now, an OID with more than 1000 children cannot show its children.",
			"REST API: \"oid:\" prefix is now optional. \"weid:\" is also possible to refer to an OID.",
			"REST API: GET request now also returns the fields \"created\" and \"updated\".",
			"Admin area: Viewing RA accounts: Added link to RA log entries."
		]
	},
	{
		"version": "2.0.1.10",
		"date": "2023-12-25 23:20:00 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Login with two users simultanously is now possible again.",
			"REST API Objects Endpoint: Added output field \"children\".",
			"REST API Objects Endpoint: PUT and POST works again.",
			"Updates are now also stored in a GitHub repo."
		]
	},
	{
		"version": "2.0.1.9",
		"date": "2023-12-03 18:45:00 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Updated definition to \"VTS F2\" AID (added padding for odd number of nibbles)"
		]
	},
	{
		"version": "2.0.1.8",
		"date": "2023-12-02 22:49:00 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Introduced support for PHP 8.3",
			"oidplus.viathinksoft.com is now www.oidplus.com"
		]
	},
	{
		"version": "2.0.1.7",
		"date": "2023-11-18 21:38:00 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Implemented OID-to-R74n-Multiplane AltID",
			"Implemented Microsoft OID-to-UUID AltID",
			"Implemented Waterjuice OID-to-UUID AltID"
		]
	},
	{
		"version": "2.0.1.6",
		"date": "2023-11-16 11:53:00 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed problems with canonical URLs (baseconfig was not used in CSS/JS)"
		]
	},
	{
		"version": "2.0.1.5",
		"date": "2023-11-15 22:01:00 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Software update: Fixed problem with outdated changelog due to caching (GitHub issue #38)",
			"Various smaller improvements"
		]
	},
	{
		"version": "2.0.1.4",
		"date": "2023-11-15 14:56:00 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed issue \"polyfill.min.js.php does not work without baseconfig file\" (GitHub issue #36)"
		]
	},
	{
		"version": "2.0.1.3",
		"date": "2023-11-15 14:41:00 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed SSL detection for Setup (GitHub issue #35)"
		]
	},
	{
		"version": "2.0.1.2",
		"date": "2023-11-15 13:29:00 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small fixes for the oid-info.com importer (GitHub issue #37 and internal)"
		]
	},
	{
		"version": "2.0.1.1",
		"date": "2023-11-15 00:58:00 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Waterjuice UUID-to-OID and Microsoft UUID-to-OID will not be transmitted to oid-info.com anymore."
		]
	},
	{
		"version": "2.0.1",
		"date": "2023-11-12 19:21:00 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New version schema. Simplified version checks and update and preferring GIT rather than SVN as distribution channel.",
			"System file check tool: Checksum files are now included with OIDplus and don't need to be downloaded from a server anymore."
		]
	},
	{
		"version": "2.0.0.1425",
		"date": "2023-11-11 11:13:24 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added Waterjuice FreeOID and R74n FreeOID to well-known OIDs"
		]
	},
	{
		"version": "2.0.0.1424",
		"date": "2023-11-11 10:41:44 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Implemented Waterjuice UUID-to-OID and Microsoft UUID-to-OID",
			"Vendor update"
		]
	},
	{
		"version": "2.0.0.1423",
		"date": "2023-10-31 00:01:15 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1422",
		"date": "2023-10-22 11:48:58 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"SVN/GIT distribution channel: Web system update shows a warning when there are changes in the working copy which will be reverted",
			"Vendor update"
		]
	},
	{
		"version": "2.0.0.1421",
		"date": "2023-10-15 01:30:09 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update (VNag new folder structure)"
		]
	},
	{
		"version": "2.0.0.1420",
		"date": "2023-10-08 23:38:36 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"DNS: Wireformat shows now binary octets instead of decimal digits"
		]
	},
	{
		"version": "2.0.0.1419",
		"date": "2023-10-08 13:08:45 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update (fixed cache problem)"
		]
	},
	{
		"version": "2.0.0.1418",
		"date": "2023-10-08 13:02:27 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Alt IDs: Equal size columns"
		]
	},
	{
		"version": "2.0.0.1417",
		"date": "2023-10-08 12:58:52 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Domain/DNS plugin: Implemented tech details (notations and punycode)",
			"Domain/DNS plugin: Implemented name-based UUIDv3/5",
			"X500 plugin: Implemented name-based UUIDv3/5",
			"Vendor update"
		]
	},
	{
		"version": "2.0.0.1416",
		"date": "2023-10-04 00:03:45 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update / Fixed internal dev tools"
		]
	},
	{
		"version": "2.0.0.1415",
		"date": "2023-09-30 21:28:27 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Updated examples: ViaThinkSoft new IP addresses"
		]
	},
	{
		"version": "2.0.0.1414",
		"date": "2023-09-30 00:34:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fix assention error in OID-IP"
		]
	},
	{
		"version": "2.0.0.1413",
		"date": "2023-09-30 00:03:45 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1412",
		"date": "2023-09-29 20:41:15 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1411",
		"date": "2023-09-25 22:35:25 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update"
		]
	},
	{
		"version": "2.0.0.1410",
		"date": "2023-09-25 22:31:19 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Newly created objects now get automatically opened, without popup confirmation dialog.",
			"If a RA does not exist during creation, no popup will be shown. Instead, at the OID page there will be an invitation button.",
			"During invitations, the email address will be syntactically checked.",
			"(Fixes https://github.com/danielmarschall/oidplus/issues/26)"
		]
	},
	{
		"version": "2.0.0.1409",
		"date": "2023-09-25 11:14:31 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed PHP error in OIDplusPagePublicRaInfo.class.php"
		]
	},
	{
		"version": "2.0.0.1408",
		"date": "2023-09-17 21:28:37 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Disable Ctrl+Shift+LeftArrow hotkey (fixes https://github.com/danielmarschall/oidplus/issues/28 )"
		]
	},
	{
		"version": "2.0.0.1407",
		"date": "2023-09-16 02:03:33 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update"
		]
	},
	{
		"version": "2.0.0.1406",
		"date": "2023-09-16 01:57:02 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"After OIDplus::invoke_shutdown(), no OIDplus methods or objects should be used"
		]
	},
	{
		"version": "2.0.0.1405",
		"date": "2023-09-03 11:17:33 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Documentation update"
		]
	},
	{
		"version": "2.0.0.1404",
		"date": "2023-09-02 23:16:05 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"X.500 AltID in RDAP/OID-IP contained \"\\n\". Fixed."
		]
	},
	{
		"version": "2.0.0.1403",
		"date": "2023-09-01 23:14:07 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update"
		]
	},
	{
		"version": "2.0.0.1402",
		"date": "2023-08-31 16:00:11 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1401",
		"date": "2023-08-31 15:15:53 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Replaced gray text with half-opaque text, to improve compatibility with colored background designs."
		]
	},
	{
		"version": "2.0.0.1400",
		"date": "2023-08-31 15:04:07 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed problems with file uploads after canonization through the goto box"
		]
	},
	{
		"version": "2.0.0.1399",
		"date": "2023-08-31 00:21:28 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"X500DN small changes"
		]
	},
	{
		"version": "2.0.0.1398",
		"date": "2023-08-30 23:49:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Another small X500DN bug fixed"
		]
	},
	{
		"version": "2.0.0.1397",
		"date": "2023-08-30 23:33:40 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added OIDplus Information Object X.500 DN"
		]
	},
	{
		"version": "2.0.0.1396",
		"date": "2023-08-30 22:55:42 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"X500DN more minor fixes. OIDplus Systems now get a RDN."
		]
	},
	{
		"version": "2.0.0.1395",
		"date": "2023-08-30 22:18:55 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"X500DN plugin various bug fixes and improvements"
		]
	},
	{
		"version": "2.0.0.1394",
		"date": "2023-08-30 03:07:33 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"X.500 DN plugin: More attribute types extracted from RFCs"
		]
	},
	{
		"version": "2.0.0.1393",
		"date": "2023-08-30 01:15:37 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"X.500 DN plugin: Added attribute types from X.501, X.509, X.511. All 107 attributes in { 2 5 4 } are now added"
		]
	},
	{
		"version": "2.0.0.1392",
		"date": "2023-08-29 23:59:22 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"X.500 DN plugin: Added attribute type list extracted from recommendation X.520"
		]
	},
	{
		"version": "2.0.0.1391",
		"date": "2023-08-29 16:41:36 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"AID Object Type: Interpretation now contains a scrollbox and no word-breaks"
		]
	},
	{
		"version": "2.0.0.1390",
		"date": "2023-08-29 16:28:14 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Replaced unprofessional usage of chr(1), chr(2), ... as replacement tokens, Part 2"
		]
	},
	{
		"version": "2.0.0.1389",
		"date": "2023-08-29 16:26:27 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Replaced unprofessional usage of chr(1), chr(2), ... as replacement tokens"
		]
	},
	{
		"version": "2.0.0.1388",
		"date": "2023-08-29 16:13:54 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"share/backarrow image now has a white glow for compatibility with dark themes"
		]
	},
	{
		"version": "2.0.0.1387",
		"date": "2023-08-29 15:45:50 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1386",
		"date": "2023-08-29 15:06:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"\"Technical information\" boxes now have a scroll-bar"
		]
	},
	{
		"version": "2.0.0.1385",
		"date": "2023-08-29 14:32:01 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"X.500 DN object type plugin: Support for multi-valued RDN as well as improved escape sequences"
		]
	},
	{
		"version": "2.0.0.1384",
		"date": "2023-08-29 00:45:08 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update"
		]
	},
	{
		"version": "2.0.0.1383",
		"date": "2023-08-29 00:11:22 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"NEW OBJECT TYPE: X.500 Distinguished Name (GitHub issue https://github.com/danielmarschall/oidplus/issues/23 )"
		]
	},
	{
		"version": "2.0.0.1382",
		"date": "2023-08-25 13:42:44 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Random AIDs can now be generated inside existing AIDs, and it is checked if there are conflicts with existing nodes (fixes GitHub issue https://github.com/danielmarschall/oidplus/issues/25)"
		]
	},
	{
		"version": "2.0.0.1381",
		"date": "2023-08-25 12:00:05 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed problem with TinyMCE at Proxy/Canonical systems"
		]
	},
	{
		"version": "2.0.0.1380",
		"date": "2023-08-15 20:16:40 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"GS1 plugin: Repaired barcodes, and added cache functionality for them"
		]
	},
	{
		"version": "2.0.0.1379",
		"date": "2023-08-11 00:03:56 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"PHP Weid Converter is now hosted at WEID repository"
		]
	},
	{
		"version": "2.0.0.1378",
		"date": "2023-08-10 23:48:06 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"WEID Converter for PHP: Upgrade to Spec Change 11"
		]
	},
	{
		"version": "2.0.0.1376",
		"date": "2023-08-10 01:44:22 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update",
			"WEID Converter for JavaScript: Upgrade to Spec Change 11"
		]
	},
	{
		"version": "2.0.0.1375",
		"date": "2023-08-06 01:57:35 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Some URLs are now canonical only (e.g. OID-IP schema)"
		]
	},
	{
		"version": "2.0.0.1374",
		"date": "2023-08-05 17:31:08 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Backup plugin: Backup filename now contains system id, so you can make sure you downloaded the file from the correct system (useful if you have a cloned system with the same title)"
		]
	},
	{
		"version": "2.0.0.1373",
		"date": "2023-08-05 17:20:44 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"COOKIE_DOMAIN baseconfig setting is highly recommend to '' to avoid bricking the login"
		]
	},
	{
		"version": "2.0.0.1372",
		"date": "2023-08-05 17:00:48 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed problems with OAuth2 with canonical URLs (multiple domains / reverse prixy), fixes https://github.com/danielmarschall/oidplus/issues/19"
		]
	},
	{
		"version": "2.0.0.1371",
		"date": "2023-08-03 23:20:05 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Changed all URLs oid-rep.orange-labs.fr and www.oid-info.com to oid-info.com"
		]
	},
	{
		"version": "2.0.0.1370",
		"date": "2023-08-03 23:16:33 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1369",
		"date": "2023-08-02 16:38:46 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Avoid double-registering a FreeOID using action_Activate()"
		]
	},
	{
		"version": "2.0.0.1368",
		"date": "2023-08-02 00:40:44 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small documentation update"
		]
	},
	{
		"version": "2.0.0.1367",
		"date": "2023-08-01 23:50:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Minor changes"
		]
	},
	{
		"version": "2.0.0.1366",
		"date": "2023-08-01 20:22:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Admin control panel logs: Log files for admin target will be printed bold",
			"",
			"Logger: Messages without target user will not be logged anymore",
			"",
			"... existing invalid log entries can be selected with this command",
			"select base.*",
			"from oidplus_log base",
			"left join oidplus_log_user target1 on target1.log_id = base.id",
			"left join oidplus_log_object target2 on target2.log_id = base.id",
			"where target1.id is null and target2.id is null;",
			"",
			"... to delete, replace \"select base.*\" with \"delete base\""
		]
	},
	{
		"version": "2.0.0.1365",
		"date": "2023-08-01 16:21:12 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"ADO+OLEDB now fully support Unicode including emojis!"
		]
	},
	{
		"version": "2.0.0.1364",
		"date": "2023-08-01 13:58:20 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"SQL Schemas updated"
		]
	},
	{
		"version": "2.0.0.1363",
		"date": "2023-08-01 01:57:42 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Various fixes for Backup/Restore plugin. Implemented GZip compression."
		]
	},
	{
		"version": "2.0.0.1362",
		"date": "2023-07-31 23:53:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Backup plugin: Backups are now compressed (3,14 MB becomes 177 KB, wow)"
		]
	},
	{
		"version": "2.0.0.1361",
		"date": "2023-07-31 22:58:22 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Backup plugin: Download file name now contains the name of the system"
		]
	},
	{
		"version": "2.0.0.1360",
		"date": "2023-07-31 22:45:26 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1359",
		"date": "2023-07-31 22:30:38 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"NEW FEATURE: Database backup/restore (beta! use with caution!)"
		]
	},
	{
		"version": "2.0.0.1358",
		"date": "2023-07-31 18:32:21 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1357",
		"date": "2023-07-31 15:40:34 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Backup WIP: During backup restore, check if the user wants to import \"X\" but the file was not exported with \"X\" (i.e. \"num_dataset\" is \"n/a\"), in that case throw an Exception and do not start the import"
		]
	},
	{
		"version": "2.0.0.1356",
		"date": "2023-07-31 15:40:02 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Renamed plugin \"Data Transfer\" to \"Data Transfer (oid-info.com)\" in admin control panel"
		]
	},
	{
		"version": "2.0.0.1355",
		"date": "2023-07-31 15:10:29 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Backup WIP: Create a JSON schema for the backup format + Reject import if the schema is different"
		]
	},
	{
		"version": "2.0.0.1354",
		"date": "2023-07-31 14:00:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Backup WIP: Added backup/restore of public/private key"
		]
	},
	{
		"version": "2.0.0.1353",
		"date": "2023-07-31 13:24:10 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Backup WIP: Added config and logs"
		]
	},
	{
		"version": "2.0.0.1352",
		"date": "2023-07-31 12:23:46 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Backup WIP: Put backup and restore into methods and give boolean flags of what to import/export and what not"
		]
	},
	{
		"version": "2.0.0.1351",
		"date": "2023-07-31 12:13:14 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Backup WIP: Delete the contents from the tables before starting the import! (Very important, I have forgotten it)"
		]
	},
	{
		"version": "2.0.0.1350",
		"date": "2023-07-30 23:48:26 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Various smaller fixes"
		]
	},
	{
		"version": "2.0.0.1349",
		"date": "2023-07-30 12:08:52 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Dropped support for Internet Explorer"
		]
	},
	{
		"version": "2.0.0.1348",
		"date": "2023-07-30 11:41:49 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Replaced rel=\"shortcut icon\" with rel=\"icon\""
		]
	},
	{
		"version": "2.0.0.1347",
		"date": "2023-07-30 01:33:29 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Deprecated favicon.ico . It must now be called favicon.png (If you have your own favicon.png, please place it into userdata/favicon/favicon.png !)"
		]
	},
	{
		"version": "2.0.0.1346",
		"date": "2023-07-30 01:21:40 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Renamed img/favicon.ico to img/default_favicon.ico to make it more clear to the user that they need to put their favicon in userdata/ instead of overwriting the file in img/"
		]
	},
	{
		"version": "2.0.0.1345",
		"date": "2023-07-30 00:06:21 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Introduced setting XFF_TRUSTED_PROXIES which allows whitelisting proxies of which their HTTP_X_FORWARDED_FOR to determine the IP address of the web-visitor."
		]
	},
	{
		"version": "2.0.0.1344",
		"date": "2023-07-29 19:23:06 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Cookie Path/Domain now respects HTTP_X_FORWARDED_HOST (however, Cookie Path is \"/\" in that case, because the server cannot know the relative path being behind the proxy)"
		]
	},
	{
		"version": "2.0.0.1343",
		"date": "2023-07-29 01:10:20 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1342",
		"date": "2023-07-29 01:07:01 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Extended the schema of the OIDplus Custom UUIDs"
		]
	},
	{
		"version": "2.0.0.1341",
		"date": "2023-07-28 00:29:08 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1340",
		"date": "2023-07-27 23:58:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Made sure that Cookies are placed for the system directory and not for root '/', since there could be problems if there is already a directory-cookie overwriting the new root-cookie"
		]
	},
	{
		"version": "2.0.0.1339",
		"date": "2023-07-27 23:29:28 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed problems with auth keys if max ra invite time is 0 (for infinite time)",
			"Vendor update"
		]
	},
	{
		"version": "2.0.0.1338",
		"date": "2023-07-25 22:00:53 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RFC Update: draft-viathinksoft-oidip-06"
		]
	},
	{
		"version": "2.0.0.1337",
		"date": "2023-07-25 13:14:24 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RFC draft-viathinksoft-oidip-06 WIP"
		]
	},
	{
		"version": "2.0.0.1336",
		"date": "2023-07-22 23:42:21 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Implemented OIDplus System GUID based on UUIDv8"
		]
	},
	{
		"version": "2.0.0.1335",
		"date": "2023-07-17 16:19:08 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added FAQ"
		]
	},
	{
		"version": "2.0.0.1334",
		"date": "2023-07-16 01:04:34 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1333",
		"date": "2023-07-15 20:19:50 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Debug mode: Added check for block4 hash conflicts"
		]
	},
	{
		"version": "2.0.0.1332",
		"date": "2023-07-15 00:17:12 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1331",
		"date": "2023-07-15 00:06:10 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1330",
		"date": "2023-07-15 00:04:02 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Introduced OIDplus Information Objects MAC address based on AAI"
		]
	},
	{
		"version": "2.0.0.1329",
		"date": "2023-07-14 23:41:08 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Alt Id \"more info\" attribute"
		]
	},
	{
		"version": "2.0.0.1328",
		"date": "2023-07-14 14:41:43 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1327",
		"date": "2023-07-14 14:33:32 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1326",
		"date": "2023-07-14 14:06:26 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Documentation of OIDplus Information Object AID and GUID"
		]
	},
	{
		"version": "2.0.0.1325",
		"date": "2023-07-14 11:58:49 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"uuid_mac_utils Update"
		]
	},
	{
		"version": "2.0.0.1324",
		"date": "2023-07-13 12:27:54 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Improved UUID and MAC decoding. Support for UUIDv6 and UUIDv7."
		]
	},
	{
		"version": "2.0.0.1323",
		"date": "2023-07-12 12:02:53 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added Custom UUIDv8 for Information Objects, replacing name-based UUIDv3 and UUIDv5"
		]
	},
	{
		"version": "2.0.0.1322",
		"date": "2023-07-03 14:17:33 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1321",
		"date": "2023-06-25 01:15:07 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Documentation update / included oidplus.com copy to SVN"
		]
	},
	{
		"version": "2.0.0.1320",
		"date": "2023-06-24 17:04:35 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1319",
		"date": "2023-06-24 16:43:14 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1318",
		"date": "2023-06-24 16:03:12 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1317",
		"date": "2023-06-24 16:01:15 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Various bugfixes. Changed JWT audience (users will be logged out once)."
		]
	},
	{
		"version": "2.0.0.1316",
		"date": "2023-06-24 01:46:45 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Bugfix: Problem when adding multiple ASN.1 / IRI identifiers for one OID"
		]
	},
	{
		"version": "2.0.0.1315",
		"date": "2023-06-23 23:27:24 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1314",
		"date": "2023-06-23 15:57:27 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixes in re JWT"
		]
	},
	{
		"version": "2.0.0.1313",
		"date": "2023-06-23 10:31:30 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed bug where some hidden items are not shown gray in the menu"
		]
	},
	{
		"version": "2.0.0.1312",
		"date": "2023-06-21 00:13:45 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added base config settings JWT_FIXED_IP_USER and JWT_FIXED_IP_ADMIN to increase security.",
			"Default values of JWT_TTL_LOGIN_USER and JWT_TTL_LOGIN_ADMIN has been changed from 10 years to 30 days."
		]
	},
	{
		"version": "2.0.0.1311",
		"date": "2023-06-20 23:51:41 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Search plugin: Search is now case-sensitive (even if the database collation is case-sensitive, which is recommended)"
		]
	},
	{
		"version": "2.0.0.1310",
		"date": "2023-06-20 00:08:38 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small change to JWT"
		]
	},
	{
		"version": "2.0.0.1309",
		"date": "2023-06-18 23:46:26 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1308",
		"date": "2023-06-18 23:44:45 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1307",
		"date": "2023-06-18 22:51:36 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1306",
		"date": "2023-06-18 20:01:33 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Code improvements in re JWT"
		]
	},
	{
		"version": "2.0.0.1305",
		"date": "2023-06-18 16:17:39 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Simplified web browser login: Regular \"PHP Session login\" was removed and replaced by JWT cookie login (previously known as \"remember me\").",
			"If you had previously disabled JWT_ALLOW_LOGIN_USER or JWT_ALLOW_LOGIN_ADMIN, please enable them again.",
			"JWT tokens now contain registered claims (OIDs)."
		]
	},
	{
		"version": "2.0.0.1304",
		"date": "2023-06-17 21:24:25 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixing https://github.com/danielmarschall/oidplus/issues/16"
		]
	},
	{
		"version": "2.0.0.1303",
		"date": "2023-06-13 01:59:05 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1302",
		"date": "2023-06-13 01:43:55 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"ID \"0\" gets now correctly displayed als WEID \"0\" in the CRUD grid"
		]
	},
	{
		"version": "2.0.0.1301",
		"date": "2023-06-01 00:04:36 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Simplified OIDplusAuthContentStore* classes"
		]
	},
	{
		"version": "2.0.0.1300",
		"date": "2023-05-30 01:04:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1299",
		"date": "2023-05-30 00:12:02 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1298",
		"date": "2023-05-30 00:06:43 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Security Feature: JWT token can now be invalidated by changing the Server Secret (in the base configuration). The update invalidates all JWT once. You need to log-in again."
		]
	},
	{
		"version": "2.0.0.1297",
		"date": "2023-05-29 23:12:54 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-IP URL is now canonical"
		]
	},
	{
		"version": "2.0.0.1296",
		"date": "2023-05-29 21:44:44 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1295",
		"date": "2023-05-29 20:43:10 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Code cosmetics"
		]
	},
	{
		"version": "2.0.0.1294",
		"date": "2023-05-29 01:44:49 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1293",
		"date": "2023-05-28 23:30:41 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Code cosmetics"
		]
	},
	{
		"version": "2.0.0.1292",
		"date": "2023-05-28 22:42:47 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1291",
		"date": "2023-05-28 22:22:44 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Code cosmetics"
		]
	},
	{
		"version": "2.0.0.1290",
		"date": "2023-05-28 20:51:58 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Creating objects using AJAX was not working. Fixed."
		]
	},
	{
		"version": "2.0.0.1289",
		"date": "2023-05-26 22:14:27 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed issue with \"Static link to this page\" ( https://github.com/danielmarschall/oidplus/issues/15 )"
		]
	},
	{
		"version": "2.0.0.1288",
		"date": "2023-05-26 21:11:13 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1287",
		"date": "2023-05-26 13:46:25 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed problem with canonical URLs https://github.com/danielmarschall/oidplus/issues/14"
		]
	},
	{
		"version": "2.0.0.1286",
		"date": "2023-05-26 13:44:06 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1285",
		"date": "2023-05-19 13:24:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Implemented REST \"OPTIONS\""
		]
	},
	{
		"version": "2.0.0.1284",
		"date": "2023-05-18 22:05:03 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update"
		]
	},
	{
		"version": "2.0.0.1283",
		"date": "2023-05-18 21:50:11 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"makeAuthKey and validateAuthKey can now be used to make temporary keys with limited lifetime.",
			"makeAuthKey and makeSecret now accept array inputs"
		]
	},
	{
		"version": "2.0.0.1282",
		"date": "2023-05-18 00:23:38 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Plugins can no longer access the SERVER_SECRET base configuration settings through OIDplusBaseConfig. Instead, makeAuthKey and makeSecret must be used. The bundled plugins are already updated."
		]
	},
	{
		"version": "2.0.0.1281",
		"date": "2023-05-17 21:44:02 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"JWT Tokens IAT time is checked against the future"
		]
	},
	{
		"version": "2.0.0.1280",
		"date": "2023-05-17 00:38:02 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Security fix: RDAP \"GET\" could be used to extract confidential OIDs. Fixed."
		]
	},
	{
		"version": "2.0.0.1279",
		"date": "2023-05-15 21:52:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RDAP, Whois, and REST  links are now grouped together"
		]
	},
	{
		"version": "2.0.0.1278",
		"date": "2023-05-15 21:16:29 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1277",
		"date": "2023-05-15 13:45:50 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1276",
		"date": "2023-05-15 10:52:06 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"REST API: New output field \"status_bits\""
		]
	},
	{
		"version": "2.0.0.1275",
		"date": "2023-05-15 09:53:57 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"REST API fixes"
		]
	},
	{
		"version": "2.0.0.1274",
		"date": "2023-05-15 00:53:20 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1273",
		"date": "2023-05-15 00:45:07 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"*** Objects REST API is done. Now in BETA stage for testing!"
		]
	},
	{
		"version": "2.0.0.1272",
		"date": "2023-05-15 00:36:49 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1271",
		"date": "2023-05-15 00:09:41 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1270",
		"date": "2023-05-14 22:37:52 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1269",
		"date": "2023-05-14 22:31:27 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1268",
		"date": "2023-05-14 11:28:37 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1267",
		"date": "2023-05-14 02:47:49 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Introduced new version of Logger Maskcodes",
			"!!! Attention! If you have installed foreign plugins (not bundled with OIDplus), you MUST update their logging maskcodes;",
			"!!! if you are the developer of the plugin, please run dev/logger/verify_maskcodes.phps to verify the plugins",
			"!!! A documentation of the new maskcodes can be found in doc/developer_notes/logger_maskcodes.md"
		]
	},
	{
		"version": "2.0.0.1266",
		"date": "2023-05-13 02:26:37 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Exceptions can now carry an HTTP Response Code"
		]
	},
	{
		"version": "2.0.0.1265",
		"date": "2023-05-13 01:26:05 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"NEW FEATURE: REST API (Framework Beta Stage; endpoints are not implemented yet)"
		]
	},
	{
		"version": "2.0.0.1264",
		"date": "2023-05-12 22:48:31 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1263",
		"date": "2023-05-12 22:47:57 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1262",
		"date": "2023-05-10 10:01:20 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1261",
		"date": "2023-05-07 20:21:34 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Misc smaller improvements"
		]
	},
	{
		"version": "2.0.0.1260",
		"date": "2023-05-06 23:46:56 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1259",
		"date": "2023-05-06 23:36:08 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Implemented feature to generate AAI MAC address.",
			"UUID-GUID/UUID-OID generation: Admin can choose if they want Timebased-UUID or Random-UUID"
		]
	},
	{
		"version": "2.0.0.1258",
		"date": "2023-05-06 20:22:46 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1257",
		"date": "2023-05-05 00:16:09 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1256",
		"date": "2023-05-04 23:52:08 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New MAC<=>AID (VTS F2 AID) definition as of 4 May 2023 implemented"
		]
	},
	{
		"version": "2.0.0.1255",
		"date": "2023-05-04 01:26:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"MAC Object Type plugin: Implemented SAI and AAI"
		]
	},
	{
		"version": "2.0.0.1254",
		"date": "2023-05-01 21:00:42 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small changes in the ELI/EUI plugin"
		]
	},
	{
		"version": "2.0.0.1253",
		"date": "2023-05-01 17:17:04 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Implemented support for ELI (CID+vendor specific parts)"
		]
	},
	{
		"version": "2.0.0.1252",
		"date": "2023-05-01 12:42:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"MAC/EUI-decoding improved"
		]
	},
	{
		"version": "2.0.0.1251",
		"date": "2023-04-30 21:38:33 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1250",
		"date": "2023-04-30 21:34:04 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Introduced EUI64 <=> AID mapping (modified VTS F2)"
		]
	},
	{
		"version": "2.0.0.1249",
		"date": "2023-04-30 00:12:50 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update"
		]
	},
	{
		"version": "2.0.0.1248",
		"date": "2023-04-30 00:10:35 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New object type: MAC / EUI-48 / EUI-64"
		]
	},
	{
		"version": "2.0.0.1247",
		"date": "2023-04-28 22:15:16 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Automatically redirect to prefiltered queries"
		]
	},
	{
		"version": "2.0.0.1246",
		"date": "2023-04-28 16:55:53 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Whitespaces at the start end end of the query are now accepted in the \"goto\" box"
		]
	},
	{
		"version": "2.0.0.1245",
		"date": "2023-04-28 16:55:17 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Canonical URL now contains the result of the \"goto\" prefiltering"
		]
	},
	{
		"version": "2.0.0.1244",
		"date": "2023-04-28 11:25:47 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"internal change: .sql setup files are now packed in the sqlSlang plugin folder"
		]
	},
	{
		"version": "2.0.0.1243",
		"date": "2023-04-28 10:20:09 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"If someone enters an OID or GUID in the goto-box, the system will automatically add \"oid:\" and \"guid:\", respectively"
		]
	},
	{
		"version": "2.0.0.1242",
		"date": "2023-04-28 01:43:45 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1241",
		"date": "2023-04-28 01:36:10 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1240",
		"date": "2023-04-28 00:30:05 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Firebird Database: LastInsertId is now implemented"
		]
	},
	{
		"version": "2.0.0.1239",
		"date": "2023-04-27 17:00:42 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Firebird fix"
		]
	},
	{
		"version": "2.0.0.1238",
		"date": "2023-04-27 16:52:44 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1237",
		"date": "2023-04-27 12:13:59 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added example data and wellknown data for Firebird"
		]
	},
	{
		"version": "2.0.0.1236",
		"date": "2023-04-27 11:59:36 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"PDO: Fixed incompatibility with Oracle and Firebird"
		]
	},
	{
		"version": "2.0.0.1235",
		"date": "2023-04-27 02:47:56 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added Firebird SQL slang (beta)"
		]
	},
	{
		"version": "2.0.0.1234",
		"date": "2023-04-26 22:53:50 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1233",
		"date": "2023-04-26 22:47:39 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Various improvements of SQLSRV database plugin"
		]
	},
	{
		"version": "2.0.0.1232",
		"date": "2023-04-26 16:49:28 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added database plugin SQLSRV"
		]
	},
	{
		"version": "2.0.0.1231",
		"date": "2023-04-26 13:54:13 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed various problems"
		]
	},
	{
		"version": "2.0.0.1230",
		"date": "2023-04-22 02:20:08 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Updated database connectivity diagram"
		]
	},
	{
		"version": "2.0.0.1229",
		"date": "2023-04-21 16:50:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1228",
		"date": "2023-04-20 23:25:27 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed systeminfo plugin compatibility with Oracle DB"
		]
	},
	{
		"version": "2.0.0.1227",
		"date": "2023-04-20 16:42:46 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1226",
		"date": "2023-04-19 23:50:31 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed insert_id() issues with ADO connections"
		]
	},
	{
		"version": "2.0.0.1225",
		"date": "2023-04-19 21:30:25 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1224",
		"date": "2023-04-19 20:32:19 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1223",
		"date": "2023-04-19 20:16:31 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Some fixes for the ADO database connection"
		]
	},
	{
		"version": "2.0.0.1222",
		"date": "2023-04-19 17:13:59 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1221",
		"date": "2023-04-19 14:51:39 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1220",
		"date": "2023-04-19 02:25:35 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"System Information plugin: Database plugins can now report extended information like their database name, username, connection properties, etc."
		]
	},
	{
		"version": "2.0.0.1219",
		"date": "2023-04-19 01:49:46 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added new database connection plugin: ADO (required Windows server system)"
		]
	},
	{
		"version": "2.0.0.1218",
		"date": "2023-04-18 11:41:17 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1217",
		"date": "2023-04-18 11:23:16 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"SQL Server is now Unicode and emoji compatible. Existing databases need to change [text] to [ntext] and [varchar] to [nvarchar]"
		]
	},
	{
		"version": "2.0.0.1216",
		"date": "2023-04-18 02:01:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"UTF8MB4 for ODBC/PDO (not tested)"
		]
	},
	{
		"version": "2.0.0.1215",
		"date": "2023-04-18 01:53:09 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1214",
		"date": "2023-04-18 01:30:34 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"MySQLi DBMS: Database fields can now contain emojis. Existing MySQL databases need to update the collation from utf8* to utf8mb4*"
		]
	},
	{
		"version": "2.0.0.1213",
		"date": "2023-04-16 23:28:06 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1212",
		"date": "2023-04-16 23:04:45 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Design and RA auth plugins are now identified by an internal ID (set in PHP) instead of the foldername"
		]
	},
	{
		"version": "2.0.0.1211",
		"date": "2023-04-16 22:29:14 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1210",
		"date": "2023-04-16 22:22:28 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1209",
		"date": "2023-04-15 03:08:37 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Documentation update"
		]
	},
	{
		"version": "2.0.0.1208",
		"date": "2023-04-15 03:00:53 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Documentation update"
		]
	},
	{
		"version": "2.0.0.1207",
		"date": "2023-04-15 02:29:09 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Documentation update"
		]
	},
	{
		"version": "2.0.0.1206",
		"date": "2023-04-14 00:24:03 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"More Exception refactoring"
		]
	},
	{
		"version": "2.0.0.1205",
		"date": "2023-04-13 23:31:50 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1204",
		"date": "2023-04-13 02:38:29 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1203",
		"date": "2023-04-13 01:38:56 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"More Exception Refactoring"
		]
	},
	{
		"version": "2.0.0.1202",
		"date": "2023-04-13 01:07:27 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1201",
		"date": "2023-04-13 00:53:49 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"First part of a refactoring of the Exception handling. Made distinction between HTML-Exception and Non-HTML-Exception clear."
		]
	},
	{
		"version": "2.0.0.1200",
		"date": "2023-04-12 01:12:42 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1199",
		"date": "2023-04-11 15:02:59 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Log method: Added functionality to add arguments like in _L()"
		]
	},
	{
		"version": "2.0.0.1198",
		"date": "2023-04-11 10:41:36 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fix SVN version detection"
		]
	},
	{
		"version": "2.0.0.1197",
		"date": "2023-04-11 01:00:28 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OIDplusLogger: Changed array-of-arrays into an object oriented structure"
		]
	},
	{
		"version": "2.0.0.1196",
		"date": "2023-04-10 21:11:20 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update"
		]
	},
	{
		"version": "2.0.0.1195",
		"date": "2023-04-10 20:09:21 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed problem where OIDplus::findGitFolder() output one extra slash"
		]
	},
	{
		"version": "2.0.0.1194",
		"date": "2023-04-10 19:35:40 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed issue where failed version check wrote wrong value to config key \"last_known_version\""
		]
	},
	{
		"version": "2.0.0.1193",
		"date": "2023-04-10 04:15:32 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Another fix in re Git, probably fixing https://github.com/danielmarschall/oidplus/issues/11"
		]
	},
	{
		"version": "2.0.0.1192",
		"date": "2023-04-10 00:05:07 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"GIT with delta objects can now be read for version detection"
		]
	},
	{
		"version": "2.0.0.1191",
		"date": "2023-04-09 01:35:56 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed partial issue with Git version recognition"
		]
	},
	{
		"version": "2.0.0.1190",
		"date": "2023-04-08 21:40:11 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1189",
		"date": "2023-04-08 21:33:05 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Changed interface \"getNotifications\", replaced \"array of array\" with \"array of OIDplusNotification\""
		]
	},
	{
		"version": "2.0.0.1188",
		"date": "2023-04-08 20:52:33 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1187",
		"date": "2023-04-08 20:40:57 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Microsoft Access SQL time function is now now() instead of date()"
		]
	},
	{
		"version": "2.0.0.1186",
		"date": "2023-04-08 20:32:34 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Methods in OIDplusAuthUtils, OIDplusGui, OIDplusMailUtils, OIDplusMenuUtils are now not static anymore"
		]
	},
	{
		"version": "2.0.0.1185",
		"date": "2023-04-08 19:58:20 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Logger methods are now not static anymore"
		]
	},
	{
		"version": "2.0.0.1184",
		"date": "2023-04-08 19:28:11 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Bugfix: \"Static link to this page\" leading to nowhere"
		]
	},
	{
		"version": "2.0.0.1183",
		"date": "2023-04-08 19:22:53 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"System check plugin: Scan now starts only after the user pressed a button"
		]
	},
	{
		"version": "2.0.0.1182",
		"date": "2023-04-08 19:02:11 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Introduced base configuration settings OFFLINE_MODE, as suggested by https://github.com/danielmarschall/oidplus/issues/5"
		]
	},
	{
		"version": "2.0.0.1181",
		"date": "2023-04-08 18:14:23 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Introduction of url_get_contents_available()"
		]
	},
	{
		"version": "2.0.0.1180",
		"date": "2023-04-08 16:06:10 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"File attachments plugin: If directory is not writeable or otherwise invalid, the admin will see a warning in the \"notifcations\" area"
		]
	},
	{
		"version": "2.0.0.1179",
		"date": "2023-04-08 00:48:52 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1178",
		"date": "2023-04-08 00:44:12 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1177",
		"date": "2023-04-07 22:53:18 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added empty Access and SQLite3 databases which can be used as template"
		]
	},
	{
		"version": "2.0.0.1176",
		"date": "2023-04-07 22:28:05 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"SQLite3: Removed foreign key reference, because it conflicts with \"well known\" ASN1/IRI"
		]
	},
	{
		"version": "2.0.0.1175",
		"date": "2023-04-07 20:13:29 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Attachment plugin: Removed \"unlock file\" feature. (It was supposed to avoid that admins upload files to directories where they don't suppose to upload. However, this security feature was nonsense, because admins could write and execute their own .php files - if we assume that the OIDplus admin is the same person which has FTP access)."
		]
	},
	{
		"version": "2.0.0.1174",
		"date": "2023-04-07 16:18:46 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed bug where IPv4 and IPv6 were not displayed"
		]
	},
	{
		"version": "2.0.0.1173",
		"date": "2023-04-07 02:09:07 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Announced Microsoft Access compatibility"
		]
	},
	{
		"version": "2.0.0.1172",
		"date": "2023-04-07 01:32:15 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"More problems with types in prepared statements adressed"
		]
	},
	{
		"version": "2.0.0.1171",
		"date": "2023-04-06 16:21:31 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed more issues with Microsoft Access database connectivity (we are close to the approval)"
		]
	},
	{
		"version": "2.0.0.1170",
		"date": "2023-04-06 02:28:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"More tests with Microsoft Access (not officially supported yet)"
		]
	},
	{
		"version": "2.0.0.1169",
		"date": "2023-04-06 02:14:30 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Removed limitations for OID max arc size and max depth"
		]
	},
	{
		"version": "2.0.0.1168",
		"date": "2023-04-06 02:01:35 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Important bugfix: Timeout error when root node (e.g. \"oid:\") is selected, rendering a fresh installation of OIDplus useless"
		]
	},
	{
		"version": "2.0.0.1167",
		"date": "2023-04-06 00:42:44 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1166",
		"date": "2023-04-05 20:38:55 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Removed texts which forced the user to mouse-hover"
		]
	},
	{
		"version": "2.0.0.1165",
		"date": "2023-04-05 20:20:50 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"System info plugin: Fixed issue with Windows servers"
		]
	},
	{
		"version": "2.0.0.1164",
		"date": "2023-04-05 16:58:12 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1163",
		"date": "2023-04-05 02:57:47 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1162",
		"date": "2023-04-05 02:37:36 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1161",
		"date": "2023-04-05 02:10:55 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fix error in PHP-Info on darkmode"
		]
	},
	{
		"version": "2.0.0.1160",
		"date": "2023-04-05 02:06:14 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"PDO+MySQL PHP testcases are now passed"
		]
	},
	{
		"version": "2.0.0.1159",
		"date": "2023-04-05 00:24:25 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Oracle, PgSQL, and Sqlite database-testcases are now passed"
		]
	},
	{
		"version": "2.0.0.1158",
		"date": "2023-04-04 12:06:04 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed some smaller issues with MSSQL insert-id (test-cases are now all passed)"
		]
	},
	{
		"version": "2.0.0.1157",
		"date": "2023-04-04 01:55:40 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1156",
		"date": "2023-04-04 01:47:03 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Lots of changes in re database driver results",
			"$res = new OIDplusNaturalSortedQueryResult($res, 'id');",
			"changes to",
			"$res->naturalSortByField('id');"
		]
	},
	{
		"version": "2.0.0.1155",
		"date": "2023-04-04 01:36:54 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed MySQLi error handling. Database test cases now passed (again?)"
		]
	},
	{
		"version": "2.0.0.1154",
		"date": "2023-04-04 01:06:27 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Admin plugin overview: Wrong display of \"active\" suffix"
		]
	},
	{
		"version": "2.0.0.1153",
		"date": "2023-04-03 23:05:28 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1152",
		"date": "2023-04-03 22:55:16 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Changed OIDplusQueryResult class definition"
		]
	},
	{
		"version": "2.0.0.1151",
		"date": "2023-04-03 21:23:20 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update"
		]
	},
	{
		"version": "2.0.0.1150",
		"date": "2023-04-03 21:16:32 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1149",
		"date": "2023-04-03 16:46:20 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New method url_post_contents() replaces all cURL calls inside the plugins. url_post_contents_available() replaces the checking for the cURL PHP extension."
		]
	},
	{
		"version": "2.0.0.1148",
		"date": "2023-04-03 14:16:22 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"The \"natural sorting\" (i.e. \"A10\" is after \"A9\") is now applied to all object types, not only to OIDs. The \"natOrder\" method of the SQL-Slang-Interface has been removed."
		]
	},
	{
		"version": "2.0.0.1147",
		"date": "2023-04-03 13:49:19 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Improved performance of admin-page \"Well known OIDs\""
		]
	},
	{
		"version": "2.0.0.1146",
		"date": "2023-04-03 13:46:21 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Wrong error message \"INTF_OID\" when class is not found"
		]
	},
	{
		"version": "2.0.0.1145",
		"date": "2023-03-30 23:44:31 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added JetBrains/PhpStorm to the acknowledgements"
		]
	},
	{
		"version": "2.0.0.1144",
		"date": "2023-03-29 12:07:48 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1143",
		"date": "2023-03-28 23:28:22 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed more possible type errors"
		]
	},
	{
		"version": "2.0.0.1142",
		"date": "2023-03-28 22:33:20 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"oid-info.com XML Export fixed type error message"
		]
	},
	{
		"version": "2.0.0.1141",
		"date": "2023-03-27 00:20:16 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1140",
		"date": "2023-03-26 23:45:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1139",
		"date": "2023-03-26 22:51:54 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"GS1 object type: GS1 Application Identifier is now shown"
		]
	},
	{
		"version": "2.0.0.1138",
		"date": "2023-03-26 21:33:11 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"\"Alternate identifiers\" is now sorted and displayed as table"
		]
	},
	{
		"version": "2.0.0.1137",
		"date": "2023-03-26 20:28:22 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed type-errors in re getRaMail() can be null"
		]
	},
	{
		"version": "2.0.0.1136",
		"date": "2023-03-26 12:37:23 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fix type error message"
		]
	},
	{
		"version": "2.0.0.1135",
		"date": "2023-03-26 12:36:48 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Frdl AltID plugin: Sort alternate-identifier, handle-identifier, and canonical-identifier"
		]
	},
	{
		"version": "2.0.0.1134",
		"date": "2023-03-26 11:50:16 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update"
		]
	},
	{
		"version": "2.0.0.1133",
		"date": "2023-03-26 11:19:21 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"de-de Language update"
		]
	},
	{
		"version": "2.0.0.1132",
		"date": "2023-03-26 04:26:18 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1131",
		"date": "2023-03-26 03:38:01 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Removed \"implementsFeature\" interface and replaced it with PHP interfaces with the prefix INTF_OID.",
			"These have a special treatment in the OIDplus class autoloader.",
			"!!! Attention: Third-Party plugins (not bundled with OIDplus) might not be compatible with this change and must be altered (we can help you with this task)"
		]
	},
	{
		"version": "2.0.0.1130",
		"date": "2023-03-26 00:38:14 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Another large bunch of type-safety changes"
		]
	},
	{
		"version": "2.0.0.1129",
		"date": "2023-03-26 00:32:23 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fix PHP 8.0 deprecation warning for JSON-OIDIP"
		]
	},
	{
		"version": "2.0.0.1128",
		"date": "2023-03-25 12:11:05 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Bugfix for MySQLi"
		]
	},
	{
		"version": "2.0.0.1127",
		"date": "2023-03-25 03:04:21 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1126",
		"date": "2023-03-25 02:19:06 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1125",
		"date": "2023-03-25 01:16:44 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1124",
		"date": "2023-03-25 00:45:48 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Updated folder icons for non-leaf nodes of object types GUID, PHP, and FourCC"
		]
	},
	{
		"version": "2.0.0.1123",
		"date": "2023-03-25 00:25:26 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1122",
		"date": "2023-03-25 00:11:30 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1121",
		"date": "2023-03-24 22:53:33 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Misc bugfixes"
		]
	},
	{
		"version": "2.0.0.1120",
		"date": "2023-03-24 17:01:04 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1119",
		"date": "2023-03-24 16:54:53 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1118",
		"date": "2023-03-24 16:32:34 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added new object type: PHP namespaces/classes/interfaces"
		]
	},
	{
		"version": "2.0.0.1117",
		"date": "2023-03-24 01:13:28 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1116",
		"date": "2023-03-23 23:09:25 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"BIG CHANGE: All methods have received a PHPdoc comment and a lot of parameter and return types have been added (as far as PHP 7.0 allows)",
			"!!! PLEASE NOTE THAT THE NEW VERSION OF OIDPLUS IS NOT COMPATIBLE WITH OLD THIRD PARTY PLUGINS (EXCEPT THE ONES THAT ARE BUNDLED WITH OIDPLUS)",
			"!!! IF YOU HAVE THIRD PARTY PLUGINS INSTALLED (OR WRITTEN YOURSELF),  THEN YOU *WILL* RECEIVE ERROR MESSAGES AFTER THE UPDATE",
			"!!! AND NEED TO CHANGE THE METHOD SIGNATURES IN THESE PLUGINS TO MAKE THEM WORK AGAIN. (We can help you with this task if you need help!)",
			"Please note that due to the amount of changes, there could have been a few bugs introduced; please send all bug reports via GitHub or email",
			"and if you have the possibility, it is recommended to test the version of a test system before applying the update on a productive system.",
			"Thank you very much!"
		]
	},
	{
		"version": "2.0.0.1115",
		"date": "2023-03-20 13:18:34 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small changes in re HTML Exception handling"
		]
	},
	{
		"version": "2.0.0.1114",
		"date": "2023-03-17 00:38:45 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1113",
		"date": "2023-03-16 23:51:30 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1112",
		"date": "2023-03-14 01:37:17 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1111",
		"date": "2023-03-03 12:58:59 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update"
		]
	},
	{
		"version": "2.0.0.1110",
		"date": "2023-03-03 12:17:54 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1109",
		"date": "2023-03-03 00:11:31 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1108",
		"date": "2023-03-02 17:06:38 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Security improvement: On every login, the password of the user gets rehashed to ensure that they are always using the current default auth plugin with the best possible settings.",
			"Note: It is highly recommend that you remove the value of the config setting \"default_ra_auth_method\" in order to let OIDplus decide about the best plugin."
		]
	},
	{
		"version": "2.0.0.1107",
		"date": "2023-03-01 13:26:17 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1106",
		"date": "2023-03-01 02:22:19 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1105",
		"date": "2023-02-28 23:54:47 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update"
		]
	},
	{
		"version": "2.0.0.1104",
		"date": "2023-02-28 17:16:41 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1103",
		"date": "2023-02-28 17:06:29 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1102",
		"date": "2023-02-27 16:03:57 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Renaming of some functions in vts_crypt.inc.php"
		]
	},
	{
		"version": "2.0.0.1101",
		"date": "2023-02-27 13:43:00 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update"
		]
	},
	{
		"version": "2.0.0.1100",
		"date": "2023-02-27 13:26:52 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"MSSQL DB Update fix"
		]
	},
	{
		"version": "2.0.0.1099",
		"date": "2023-02-27 12:52:20 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RA Auth plugins can now be only-hash or only-verify"
		]
	},
	{
		"version": "2.0.0.1098",
		"date": "2023-02-27 12:02:09 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added more arguments to random_bytes_ex() to force CSRNG"
		]
	},
	{
		"version": "2.0.0.1097",
		"date": "2023-02-27 11:38:38 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"More changes in re VTS MCF 1.0 auth"
		]
	},
	{
		"version": "2.0.0.1096",
		"date": "2023-02-27 09:52:19 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1095",
		"date": "2023-02-27 01:58:30 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1094",
		"date": "2023-02-27 01:50:46 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added auth plugin A6_crypt"
		]
	},
	{
		"version": "2.0.0.1093",
		"date": "2023-02-26 23:54:33 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Changed default VTS MCF algorithm from salted sha3-512 to sha3-512-hmac"
		]
	},
	{
		"version": "2.0.0.1092",
		"date": "2023-02-26 23:48:28 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1091",
		"date": "2023-02-26 23:43:12 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1090",
		"date": "2023-02-26 23:28:25 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"DATABASE UPDATE (v1002): The database fields ra.salt and ra.authkey have been merged.",
			"Auth plugins A1_phpgeneric_salted_hex and A2_sha3_salted_base64 have been removed and replaced by A5_vts_mcf.",
			"Auth plugin A3_bcrypt/OIDplusAuthPluginBCrypt.class.php does not accept the A3# prefix anymore (gets removed in the migration procedure).",
			"Hashes of A1*# and A2# get migrated to the ViaThinkSoft MCF 1.0 hashes.",
			"!!!!! It is recommended to make a backup of your \"ra\" table in case something goes wrong with the migration of the hashes !!!!!"
		]
	},
	{
		"version": "2.0.0.1089",
		"date": "2023-02-26 19:05:49 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1088",
		"date": "2023-02-26 19:00:05 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added Argon2 auth plugin (only RAs).",
			"Config: Auth plugin setting can (and should) be empty, which means that OIDplus automatically chooses the best auth plugin.",
			"RA Auth plugins: Added available() function to OIDplusAuthPlugin.",
			"Removed \"A3#\" prefix from password hashes created by plugin A3_bcrypt."
		]
	},
	{
		"version": "2.0.0.1087",
		"date": "2023-02-26 01:18:19 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1086",
		"date": "2023-02-26 01:12:29 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Avoid calling *.class.php files directly to avoid PHP errors (Github Issue #4)"
		]
	},
	{
		"version": "2.0.0.1085",
		"date": "2023-02-26 00:37:36 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1084",
		"date": "2023-02-26 00:10:51 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Auth plugin \"A1\" does now also accepts base64 payload in addition to hex code. Also, 4 more algorithms are unlocked."
		]
	},
	{
		"version": "2.0.0.1083",
		"date": "2023-02-03 00:51:09 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Minor changes"
		]
	},
	{
		"version": "2.0.0.1082",
		"date": "2023-02-03 00:14:42 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Minor changes"
		]
	},
	{
		"version": "2.0.0.1081",
		"date": "2023-01-24 00:15:33 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Release Internet Draft draft-viathinksoft-oidip-05"
		]
	},
	{
		"version": "2.0.0.1080",
		"date": "2023-01-11 00:07:41 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1079",
		"date": "2023-01-08 22:12:42 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"VTS E0 AID => OID mapping"
		]
	},
	{
		"version": "2.0.0.1078",
		"date": "2023-01-08 20:31:10 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Introduced OIDplus System Application Identifier (AID) and OIDplus Information Object Application Identifier (AID)"
		]
	},
	{
		"version": "2.0.0.1077",
		"date": "2023-01-04 01:34:48 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Application Identifier (AID) \"VTS B1\" (member) and \"VTS B2\" (products) bidirectional AltID mapping OID<=>AID established"
		]
	},
	{
		"version": "2.0.0.1076",
		"date": "2023-01-04 00:50:34 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"More design fixes"
		]
	},
	{
		"version": "2.0.0.1075",
		"date": "2023-01-04 00:02:12 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small design fixes"
		]
	},
	{
		"version": "2.0.0.1074",
		"date": "2023-01-03 23:10:43 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Extended systeminfo.php. Also, SystemID now contains the ID, not the OID."
		]
	},
	{
		"version": "2.0.0.1073",
		"date": "2023-01-03 22:56:18 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"BUGFIX: OIDplus can now work with PKI again, even if OpenSSL is not installed",
			"Added System GUID (SHA1-Namebased UUID based on your public key)"
		]
	},
	{
		"version": "2.0.0.1072",
		"date": "2022-12-30 01:21:42 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"More internal plugin checks"
		]
	},
	{
		"version": "2.0.0.1071",
		"date": "2022-12-29 02:27:59 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Implemented dependency check output for CLI"
		]
	},
	{
		"version": "2.0.0.1070",
		"date": "2022-12-28 23:24:12 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1069",
		"date": "2022-12-28 23:21:20 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(Internal code change)"
		]
	},
	{
		"version": "2.0.0.1068",
		"date": "2022-12-28 01:20:39 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Improved \"low PHP version\" error handling"
		]
	},
	{
		"version": "2.0.0.1067",
		"date": "2022-12-27 19:13:31 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed problem with color replacement"
		]
	},
	{
		"version": "2.0.0.1066",
		"date": "2022-12-27 11:34:50 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(Internal code change)"
		]
	},
	{
		"version": "2.0.0.1065",
		"date": "2022-12-27 10:00:55 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OIDplus Setup/OOBE: <head> fields are now the same as in index.php"
		]
	},
	{
		"version": "2.0.0.1064",
		"date": "2022-12-26 23:30:18 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OIDplus DOS/Win311/95 small fixes"
		]
	},
	{
		"version": "2.0.0.1063",
		"date": "2022-12-26 22:23:12 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added PHPStan to the acknowledgements"
		]
	},
	{
		"version": "2.0.0.1062",
		"date": "2022-12-26 22:14:36 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed PHP 8.2.0 incompatibility (\"Documents and Resources\" root node)"
		]
	},
	{
		"version": "2.0.0.1061",
		"date": "2022-12-26 22:10:15 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Software update: Explicit warning if CURL is not installed rather than a \"something went wrong\" error"
		]
	},
	{
		"version": "2.0.0.1060",
		"date": "2022-12-26 22:04:49 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed problem with language switcher in new Setup design"
		]
	},
	{
		"version": "2.0.0.1059",
		"date": "2022-12-26 22:03:43 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed problem with SSL redirection cookie set by Setup"
		]
	},
	{
		"version": "2.0.0.1058",
		"date": "2022-12-26 22:02:50 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed PHP 8.2.0 incompatibility (Object type root nodes)"
		]
	},
	{
		"version": "2.0.0.1057",
		"date": "2022-12-26 19:42:21 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1056",
		"date": "2022-12-26 19:28:38 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update"
		]
	},
	{
		"version": "2.0.0.1055",
		"date": "2022-12-26 18:17:44 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OIDplus Setup and OOBE now have the main design (and is dark-theme compatible)"
		]
	},
	{
		"version": "2.0.0.1054",
		"date": "2022-12-26 01:49:20 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"FourCC: Added integer representation"
		]
	},
	{
		"version": "2.0.0.1053",
		"date": "2022-12-26 00:48:36 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OIDplus for DOS / Windows95 Export splits the root parents now correct"
		]
	},
	{
		"version": "2.0.0.1052",
		"date": "2022-12-25 22:41:57 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-Info URLs prefer HTTPS variant https://oid-rep.orange-labs.fr/ instead of HTTP variant http://oid-info.com/"
		]
	},
	{
		"version": "2.0.0.1051",
		"date": "2022-12-22 00:45:05 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small fix in config migration procedure"
		]
	},
	{
		"version": "2.0.0.1050",
		"date": "2022-12-21 01:13:04 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Huge change in internal code structure!",
			"All OIDplus classes are now in the class namespace \"ViaThinkSoft\\OIDplus\".",
			"!!! WARNING:",
			"!!! All plugins MUST put their classes in a namespace and the constant \"INSIDE_OIDPLUS\" must not be used anymore.",
			"!!! If you have a third-party plugin installed which is NOT bundled with OIDplus, you MUST update it.",
			"!!! Recommendation: Remove the plugin first, then update OIDplus, then ask the author to change the plugin.",
			"!!! If you have not installed any third-party plugins, then it is safe to update now."
		]
	},
	{
		"version": "2.0.0.1049",
		"date": "2022-12-20 13:33:21 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fix setup not working (bug introduced in SVN Rev 1041 on 9 Dec 2022)"
		]
	},
	{
		"version": "2.0.0.1048",
		"date": "2022-12-11 02:20:19 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1047",
		"date": "2022-12-11 01:37:48 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed PHPInfo for PHP 8.2"
		]
	},
	{
		"version": "2.0.0.1046",
		"date": "2022-12-11 01:22:05 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Removal of deprecated utf8_encode()"
		]
	},
	{
		"version": "2.0.0.1045",
		"date": "2022-12-10 23:53:01 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1044",
		"date": "2022-12-09 23:58:50 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1043",
		"date": "2022-12-09 23:19:02 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1042",
		"date": "2022-12-09 22:05:45 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"PHP 8.2.0 compatibility"
		]
	},
	{
		"version": "2.0.0.1041",
		"date": "2022-12-09 20:32:43 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New base-config setting DEFAULT_LANGUAGE (possible values: enus, dede)"
		]
	},
	{
		"version": "2.0.0.1040",
		"date": "2022-12-06 01:31:40 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed altids plugin (release 1.0.2)"
		]
	},
	{
		"version": "2.0.0.1039",
		"date": "2022-11-30 01:11:00 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1038",
		"date": "2022-11-27 12:14:30 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Automated AJAX plugin: Blacklist button now has a confirmation dialog"
		]
	},
	{
		"version": "2.0.0.1037",
		"date": "2022-11-27 02:15:58 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update"
		]
	},
	{
		"version": "2.0.0.1036",
		"date": "2022-11-27 00:54:59 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Server errors are now shown to the user instead of error message \"SyntaxError: Unexpected token < in JSON at position 0\""
		]
	},
	{
		"version": "2.0.0.1035",
		"date": "2022-11-12 00:36:44 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"GUID and FourCC plugins have \"folder\" icons in the treeview if they are no leaf-nodes"
		]
	},
	{
		"version": "2.0.0.1034",
		"date": "2022-11-09 01:24:51 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Composer no-dev (doesn't do any difference atm)"
		]
	},
	{
		"version": "2.0.0.1033",
		"date": "2022-11-09 01:06:17 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Replaced some $_REQUEST with $_GET and $_POST.",
			"Made sure \"request_order\" is in a defined state. (Important: Cookies must not be $_REQUEST)"
		]
	},
	{
		"version": "2.0.0.1032",
		"date": "2022-11-05 01:36:23 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update"
		]
	},
	{
		"version": "2.0.0.1031",
		"date": "2022-11-01 19:08:32 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1030",
		"date": "2022-10-29 13:07:35 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Setup assistant looks now different dependent if the base config file already exists or not (especially to avoid accidental overwriting of the database)"
		]
	},
	{
		"version": "2.0.0.1029",
		"date": "2022-10-28 10:11:40 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed bug in System Registration request after OOBE"
		]
	},
	{
		"version": "2.0.0.1028",
		"date": "2022-10-28 10:06:29 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed problem in RA Root Object Listing"
		]
	},
	{
		"version": "2.0.0.1027",
		"date": "2022-10-25 00:49:59 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1026",
		"date": "2022-10-24 16:43:14 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1025",
		"date": "2022-10-23 18:28:48 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"VTS Captcha: Re-Added \"autosolve\" (configurable)"
		]
	},
	{
		"version": "2.0.0.1024",
		"date": "2022-10-23 18:03:29 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed some race-conditions with VTS Client Challenge CAPTCHA"
		]
	},
	{
		"version": "2.0.0.1023",
		"date": "2022-10-22 21:33:46 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"VTS Challenge CAPTCHA : Based on version 1.1.1 now"
		]
	},
	{
		"version": "2.0.0.1022",
		"date": "2022-10-22 15:46:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Updated VTS Client Challenge plugin to version 1.1 (mitigate replay attack)"
		]
	},
	{
		"version": "2.0.0.1021",
		"date": "2022-10-22 15:10:43 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Make use of php-sha3 fork by danielmarschall (contains hash_hmac)",
			"TinyMCE update"
		]
	},
	{
		"version": "2.0.0.1020",
		"date": "2022-10-22 01:11:10 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Setup: Added \"Canonical URL\" option"
		]
	},
	{
		"version": "2.0.0.1019",
		"date": "2022-10-22 00:35:54 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed issues with hCaptcha plugin"
		]
	},
	{
		"version": "2.0.0.1018",
		"date": "2022-10-22 00:20:02 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Applied more fixes to the ViaThinkSoft Client Challenge CAPTCHA. Also, removed captchaDomHead() from the CAPTCHA API"
		]
	},
	{
		"version": "2.0.0.1017",
		"date": "2022-10-21 22:32:34 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed issue where ViaThinkSoft Challenge CAPTCHA blocked the UI, and it didn't work on subfolders"
		]
	},
	{
		"version": "2.0.0.1016",
		"date": "2022-10-21 17:45:33 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Implemented reCAPTCHA V2 Invisible and reCAPTCHA V3 (score based)"
		]
	},
	{
		"version": "2.0.0.1015",
		"date": "2022-10-20 23:31:45 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Repaired ReCAPTCHA"
		]
	},
	{
		"version": "2.0.0.1014",
		"date": "2022-10-18 00:47:31 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1013",
		"date": "2022-10-17 23:02:34 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1012",
		"date": "2022-10-17 22:52:45 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1011",
		"date": "2022-10-17 21:39:44 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Made privacy documentation more pretty"
		]
	},
	{
		"version": "2.0.0.1010",
		"date": "2022-10-17 13:27:32 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"hCaptcha fixes"
		]
	},
	{
		"version": "2.0.0.1009",
		"date": "2022-10-17 03:47:02 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fix hCaptcha"
		]
	},
	{
		"version": "2.0.0.1008",
		"date": "2022-10-17 02:39:09 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1007",
		"date": "2022-10-17 00:29:13 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1006",
		"date": "2022-10-17 00:25:58 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Notifications plugin: Added checks if confidential directories are world-readable, and if the cache directory is writeable"
		]
	},
	{
		"version": "2.0.0.1005",
		"date": "2022-10-16 22:40:43 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1004",
		"date": "2022-10-16 04:18:37 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1003",
		"date": "2022-10-16 04:16:40 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"hCaptcha cannot be selected anymore if php_curl is missing"
		]
	},
	{
		"version": "2.0.0.1002",
		"date": "2022-10-16 03:34:50 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.1001",
		"date": "2022-10-16 03:31:34 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New plugin: hCaptcha"
		]
	},
	{
		"version": "2.0.0.1000",
		"date": "2022-10-15 23:40:20 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"NEW PLUGIN: Notifications for RA or Administrator"
		]
	},
	{
		"version": "2.0.0.999",
		"date": "2022-10-15 23:30:24 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.998",
		"date": "2022-10-15 14:56:58 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Error in Non-Javascript menu fixed"
		]
	},
	{
		"version": "2.0.0.997",
		"date": "2022-10-15 14:21:45 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"VNag: Special case for version compare added"
		]
	},
	{
		"version": "2.0.0.996",
		"date": "2022-10-15 09:55:11 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed GitHub bug #3: Setup \"Copy to clipboard\" button copied non-breaking whitespaces instead of normal whitespaces, generating a syntax error in the base config file (bug introduced in SVN Rev 983 on 3 Oct 2022)"
		]
	},
	{
		"version": "2.0.0.991",
		"date": "2022-10-10 00:48:29 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.990",
		"date": "2022-10-09 18:33:43 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Bundled new third-party plugin \"AltIDs\" by Frdlweb"
		]
	},
	{
		"version": "2.0.0.989",
		"date": "2022-10-09 11:03:39 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added function getScalar to OIDplusDatabaseConnection"
		]
	},
	{
		"version": "2.0.0.988",
		"date": "2022-10-05 16:52:34 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.987",
		"date": "2022-10-04 19:11:29 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Color plugin: \"Invert colors\" is now a checkbox instead of a 0/1 slider"
		]
	},
	{
		"version": "2.0.0.986",
		"date": "2022-10-04 14:15:46 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"BUGFIX: Invitation email of freshly created objects could not be sent"
		]
	},
	{
		"version": "2.0.0.985",
		"date": "2022-10-04 00:16:46 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Setup DB command lines: Added \"copy to clipboard\" buttons"
		]
	},
	{
		"version": "2.0.0.984",
		"date": "2022-10-03 23:55:25 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"VNag and AJAX plugins: Added \"copy to clipboard\" buttons"
		]
	},
	{
		"version": "2.0.0.983",
		"date": "2022-10-03 23:34:01 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"\"Forgot admin password\" and \"Change admin password\": Added \"copy to clipboard\" button"
		]
	},
	{
		"version": "2.0.0.982",
		"date": "2022-10-03 23:23:38 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Setup: Added \"copy to clipboard\" button"
		]
	},
	{
		"version": "2.0.0.981",
		"date": "2022-10-03 21:07:23 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.980",
		"date": "2022-10-03 00:06:04 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update"
		]
	},
	{
		"version": "2.0.0.979",
		"date": "2022-10-02 22:39:17 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.978",
		"date": "2022-10-02 21:16:40 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"After a \"delete from ###objects\" (3x), \"update ###objects\" (12x), or \"insert into ###objects\" (3x), call OIDplusObject::resetObjectInformationCache()"
		]
	},
	{
		"version": "2.0.0.977",
		"date": "2022-10-02 03:06:52 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Make use of new getters of OIDplusObject in order to save unnecessary database queries"
		]
	},
	{
		"version": "2.0.0.976",
		"date": "2022-10-02 03:04:25 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fix OID-IP"
		]
	},
	{
		"version": "2.0.0.975",
		"date": "2022-10-01 22:31:42 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added getters for description, comment, updatedTime, createdTime to OIDplusObject instances"
		]
	},
	{
		"version": "2.0.0.974",
		"date": "2022-10-01 20:21:07 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fix bug where HTTP was not possible after a HTTPS call (Chrome blocks secure CSRF cookie overwrite). HTTPS is now enforced if the page was previously loaded using HTTPS"
		]
	},
	{
		"version": "2.0.0.973",
		"date": "2022-10-01 18:59:39 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"ViaThinkSoft plugins now identify with their system SVN version"
		]
	},
	{
		"version": "2.0.0.972",
		"date": "2022-10-01 18:39:42 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Updated frdl RDAP plugin to 0.3.1, fixing a bug in AltID integration"
		]
	},
	{
		"version": "2.0.0.971",
		"date": "2022-10-01 18:33:15 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-IP: Using findFitting() to avoid making unnecessary SQL queries"
		]
	},
	{
		"version": "2.0.0.970",
		"date": "2022-10-01 14:45:52 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Removed unnecessary try-catch around OIDplusObject::parse, because itself catches internal errors"
		]
	},
	{
		"version": "2.0.0.969",
		"date": "2022-10-01 00:42:37 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OIDplusObject::findFitting() does NOT throw an Exception anymore if the object type is unknown",
			"Update to FRDLWeb RDAP plugin 0.3"
		]
	},
	{
		"version": "2.0.0.968",
		"date": "2022-09-30 23:51:32 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OIDplusPagePublicObjects::getAlternativesForQuery() now takes care that the own ID is not in the list"
		]
	},
	{
		"version": "2.0.0.967",
		"date": "2022-09-30 23:37:02 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-IP plugins calls getAlternativesForQuery to find alternative identifiers if the object cannot be found"
		]
	},
	{
		"version": "2.0.0.966",
		"date": "2022-09-27 23:24:26 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.964",
		"date": "2022-09-26 00:43:34 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.963",
		"date": "2022-09-26 00:20:39 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.962",
		"date": "2022-09-25 23:10:12 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"AID: Possibility to generate a random AID directly in OIDplus"
		]
	},
	{
		"version": "2.0.0.961",
		"date": "2022-09-24 16:00:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added \"iana-pen\" AltID to OID and AID (VTS F0)"
		]
	},
	{
		"version": "2.0.0.960",
		"date": "2022-09-24 13:48:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"FreeOID: Added note about free Application Identifiers (AID)"
		]
	},
	{
		"version": "2.0.0.959",
		"date": "2022-09-20 21:18:16 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.958",
		"date": "2022-09-18 21:42:05 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Upgrade to composer 2"
		]
	},
	{
		"version": "2.0.0.957",
		"date": "2022-09-18 21:16:25 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small fixes in re AID alt ids"
		]
	},
	{
		"version": "2.0.0.956",
		"date": "2022-09-18 14:36:02 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Avoid endless loop if an object type plugin does not implement parse()"
		]
	},
	{
		"version": "2.0.0.955",
		"date": "2022-09-18 12:28:26 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fix problems with \"goto\" object detection"
		]
	},
	{
		"version": "2.0.0.954",
		"date": "2022-09-17 23:14:07 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.953",
		"date": "2022-09-17 22:54:11 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fix problem \"Invalid OID\" for non-found Non-OIDs"
		]
	},
	{
		"version": "2.0.0.952",
		"date": "2022-09-17 01:50:24 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.951",
		"date": "2022-09-17 01:29:43 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added feature that the Objects plugin calls other plugins for help if it cannot find an object"
		]
	},
	{
		"version": "2.0.0.950",
		"date": "2022-09-16 16:16:43 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Release RDAP plugin 0.2"
		]
	},
	{
		"version": "2.0.0.949",
		"date": "2022-09-14 10:50:19 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"MSSQL/ODBC: Fixed error message \"Syntax error or access violation\" at each registration (= hourly).",
			"Workaround for a bug known to Microsoft since 2010! (see PHP bug report #36561. Status from Microsoft",
			"\"To be resolved in a future release of the SQL Server Native Access Client.\", wow.)"
		]
	},
	{
		"version": "2.0.0.948",
		"date": "2022-09-13 21:57:48 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Implemented 404 handler for NGINX"
		]
	},
	{
		"version": "2.0.0.947",
		"date": "2022-09-13 14:56:17 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Implemented 404 handler for Microsoft IIS"
		]
	},
	{
		"version": "2.0.0.946",
		"date": "2022-09-12 23:58:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added \"HTTP 404\" API that can be used by plugins. Currently only supported by Apache 2"
		]
	},
	{
		"version": "2.0.0.945",
		"date": "2022-09-12 22:46:55 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"More AID <=> AltID conversions"
		]
	},
	{
		"version": "2.0.0.944",
		"date": "2022-09-11 21:09:57 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.943",
		"date": "2022-09-11 20:46:30 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Published RDAP plugin v0.1.2"
		]
	},
	{
		"version": "2.0.0.942",
		"date": "2022-09-11 20:16:31 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Published RDAP plugin v0.1.1"
		]
	},
	{
		"version": "2.0.0.941",
		"date": "2022-09-11 20:07:04 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added class OIDplusOIDIP"
		]
	},
	{
		"version": "2.0.0.940",
		"date": "2022-09-11 20:03:50 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.939",
		"date": "2022-09-11 17:26:45 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Published RDAP plugin v0.1"
		]
	},
	{
		"version": "2.0.0.938",
		"date": "2022-09-11 17:26:06 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.937",
		"date": "2022-09-11 17:25:46 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Newest version of phpstan does not show warnings at OIDplusPluginManifest.class.php anymore"
		]
	},
	{
		"version": "2.0.0.936",
		"date": "2022-09-11 17:18:50 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Output of HTTP 404 when a non-existing plugin is opened"
		]
	},
	{
		"version": "2.0.0.935",
		"date": "2022-09-11 12:18:25 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.934",
		"date": "2022-09-09 00:07:13 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.933",
		"date": "2022-09-08 13:59:21 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-to-AID: Implemented OID 1.0.xx (E8 = ISO Standard) and OID 2.999.xx (ViaThinkSoft E0) cases"
		]
	},
	{
		"version": "2.0.0.932",
		"date": "2022-09-07 23:48:27 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.931",
		"date": "2022-09-07 22:57:33 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"AID Decoder: Implemented case \"E8\" (ISO Standard by OID)",
			"AIDs can be entered in the notation '00:11:22:33'"
		]
	},
	{
		"version": "2.0.0.930",
		"date": "2022-09-07 00:52:39 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID DER encoding is now visible",
			"AID: OID-AID added (ViaThinkSoft-Foreign-6 AID)",
			"AID: RID and PIX can now be mixed in a single node again (removed restriction again); this is handy for ViaThinkSoft-Foreign-AIDs"
		]
	},
	{
		"version": "2.0.0.929",
		"date": "2022-08-28 02:26:09 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Removed some cases of Alternative Identifiers to avoid confusing users:",
			"- UUID-OIDs no longer show namebased UUIDs",
			"- OIDs no longer show namebased UUIDs with namespace UUID_NAMEBASED_NS_OidPlusMisc (since they have the normal RFC namebased UUID namespace for OIDs = UUID_NAMEBASED_NS_OID)"
		]
	},
	{
		"version": "2.0.0.928",
		"date": "2022-08-25 23:54:44 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Replaced word \"subsequent\" with word \"subordinate\""
		]
	},
	{
		"version": "2.0.0.927",
		"date": "2022-08-19 17:03:15 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.926",
		"date": "2022-08-19 17:01:53 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"aid_decoder.inc.php : Added ASCII view in addition to the hex-representation"
		]
	},
	{
		"version": "2.0.0.925",
		"date": "2022-08-19 00:08:18 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"AID Object type: It is now forbidden that a node mixes RID and PIX"
		]
	},
	{
		"version": "2.0.0.924",
		"date": "2022-07-31 13:02:49 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Some changes on the AID decoder"
		]
	},
	{
		"version": "2.0.0.923",
		"date": "2022-07-31 00:51:50 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed OOBE problems with AID example data"
		]
	},
	{
		"version": "2.0.0.922",
		"date": "2022-07-31 00:27:10 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added AID decoder"
		]
	},
	{
		"version": "2.0.0.921",
		"date": "2022-07-30 19:50:23 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed BUG#0000220 in OOBE"
		]
	},
	{
		"version": "2.0.0.920",
		"date": "2022-07-29 16:17:22 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.919",
		"date": "2022-07-29 16:14:20 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New object type \"Application Identifier (ISO/IEC 7816-5)\""
		]
	},
	{
		"version": "2.0.0.918",
		"date": "2022-07-25 02:32:05 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Prepare for OIDIP-05 (uses JSON schema 2020-12)"
		]
	},
	{
		"version": "2.0.0.917",
		"date": "2022-07-25 01:32:01 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.916",
		"date": "2022-07-24 12:53:27 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Release of draft-viathinksoft-oidip-04"
		]
	},
	{
		"version": "2.0.0.915",
		"date": "2022-07-23 00:10:56 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.914",
		"date": "2022-07-23 00:04:27 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.913",
		"date": "2022-07-22 17:36:59 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.912",
		"date": "2022-07-22 17:22:06 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-IP"
		]
	},
	{
		"version": "2.0.0.911",
		"date": "2022-07-22 01:39:12 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.910",
		"date": "2022-07-22 01:27:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.909",
		"date": "2022-07-22 01:18:12 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-IP XML/JSON: Removed \"ra-\" prefix from fields"
		]
	},
	{
		"version": "2.0.0.908",
		"date": "2022-07-22 00:14:43 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-IP: Added \"$lang\" argument and \"lang\" response fields"
		]
	},
	{
		"version": "2.0.0.907",
		"date": "2022-07-21 22:34:48 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.906",
		"date": "2022-07-20 00:45:24 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.905",
		"date": "2022-07-19 01:35:35 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.904",
		"date": "2022-07-18 15:39:26 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-IP JSON-Schema and XSD are now again in the InternetDraft"
		]
	},
	{
		"version": "2.0.0.903",
		"date": "2022-07-18 14:45:03 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-IP"
		]
	},
	{
		"version": "2.0.0.902",
		"date": "2022-07-18 14:21:12 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.901",
		"date": "2022-07-18 12:25:22 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OIDIP: Regex replace [0-9] with \\d"
		]
	},
	{
		"version": "2.0.0.900",
		"date": "2022-07-18 11:18:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.899",
		"date": "2022-07-18 02:16:19 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-IP: Attachments URL were relative URLs. Corrected to absolute URLs.",
			"OID-IP: Attachment plugin now generates correct XSD"
		]
	},
	{
		"version": "2.0.0.898",
		"date": "2022-07-18 00:19:38 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-IP: JSON is now an associative array. JSON-Schema adjusted.",
			"OID-IP: Completely rewrote XSD schema file. Elements are now sequential.",
			"OID-IP: Order of fields corrected (since XSD is now sequential)",
			"OID-IP: XSD/JSON: distance is now an integer instead of a string",
			"OID-IP: XSD/JSON: added simple regex for query and object fields",
			"OID-IP: XSD/JSON: added support for ra1, ra2, ra3, ...",
			"Important bugfix for openssl_supplement.inc.php"
		]
	},
	{
		"version": "2.0.0.897",
		"date": "2022-07-17 02:44:44 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OIP-IP minor changes"
		]
	},
	{
		"version": "2.0.0.896",
		"date": "2022-07-17 02:40:45 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-IP XSD/JSON schema bugfix: October timestamps were not accepted. Fixed."
		]
	},
	{
		"version": "2.0.0.895",
		"date": "2022-07-15 15:42:29 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.894",
		"date": "2022-07-15 01:26:42 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.893",
		"date": "2022-07-15 00:19:43 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.892",
		"date": "2022-07-14 16:57:02 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.891",
		"date": "2022-07-14 16:19:47 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-IP"
		]
	},
	{
		"version": "2.0.0.890",
		"date": "2022-07-14 13:40:06 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-IP"
		]
	},
	{
		"version": "2.0.0.889",
		"date": "2022-07-13 23:18:38 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"GUIDs can now also be accessed via the \"uuid:\" namespace prefix"
		]
	},
	{
		"version": "2.0.0.888",
		"date": "2022-07-13 16:33:24 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-IP RFC trivia"
		]
	},
	{
		"version": "2.0.0.887",
		"date": "2022-07-13 10:46:06 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RFC: Updated reference cites according to https://www.rfc-editor.org/refs/"
		]
	},
	{
		"version": "2.0.0.886",
		"date": "2022-07-12 21:25:45 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-IP RFC: Fixed ABNF ( thanks to Bill's ABNF checker at https://tools.ietf.org/tools/bap/abnf.cgi )"
		]
	},
	{
		"version": "2.0.0.885",
		"date": "2022-07-12 21:07:57 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-IP \"$token=\" has been renamed to \"$auth=\""
		]
	},
	{
		"version": "2.0.0.884",
		"date": "2022-07-12 15:49:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.883",
		"date": "2022-07-12 14:55:03 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RFC \"server commands\" and \"authentication tokens\" are now merged into \"arguments\""
		]
	},
	{
		"version": "2.0.0.882",
		"date": "2022-07-12 01:42:15 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Release of draft-viathinksoft-oidip-03"
		]
	},
	{
		"version": "2.0.0.881",
		"date": "2022-07-12 01:14:49 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.880",
		"date": "2022-07-11 22:42:59 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-IP: Removed \"html\" format from the RFC"
		]
	},
	{
		"version": "2.0.0.879",
		"date": "2022-07-11 21:22:19 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-IP: Implemented RA \"information partially available\""
		]
	},
	{
		"version": "2.0.0.878",
		"date": "2022-07-11 21:13:42 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.877",
		"date": "2022-07-11 20:53:59 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-IP: Words like \"unknown\" or \"redacted\" will not be translated anymore (Because output must be consistant)",
			"OID-IP: Added \"url\" property for all objects"
		]
	},
	{
		"version": "2.0.0.876",
		"date": "2022-07-11 17:09:09 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Admin/RA Automated Ajax: Token can now be copied into clipBoard"
		]
	},
	{
		"version": "2.0.0.875",
		"date": "2022-07-10 13:30:17 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added config setting global_bcc for all outgoing emails"
		]
	},
	{
		"version": "2.0.0.874",
		"date": "2022-07-10 12:58:31 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update"
		]
	},
	{
		"version": "2.0.0.873",
		"date": "2022-07-10 02:41:19 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"XML/JSON schema and RFC update"
		]
	},
	{
		"version": "2.0.0.872",
		"date": "2022-07-10 01:23:08 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.871",
		"date": "2022-07-10 01:11:40 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.870",
		"date": "2022-07-10 01:07:21 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-IP : XML/JSON Signature error catching"
		]
	},
	{
		"version": "2.0.0.869",
		"date": "2022-07-09 22:48:57 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Described the new XML/JSON signatures in the RFC (work-in-progress)."
		]
	},
	{
		"version": "2.0.0.868",
		"date": "2022-07-09 21:33:15 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-IP: XML and JSON now have standardized (W3C/RFC) signatures. The OID-IP RFC draft will be edited soon."
		]
	},
	{
		"version": "2.0.0.867",
		"date": "2022-07-09 15:45:15 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-IP : XML und JSON schema had relative schema URL. Fixed to absolute URL."
		]
	},
	{
		"version": "2.0.0.866",
		"date": "2022-06-19 20:21:58 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"CSRF token debug"
		]
	},
	{
		"version": "2.0.0.865",
		"date": "2022-06-05 00:53:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed SQLite3 OOBE issues"
		]
	},
	{
		"version": "2.0.0.864",
		"date": "2022-06-02 01:52:01 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.863",
		"date": "2022-06-02 01:40:40 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed Oracle OOBE example scripts"
		]
	},
	{
		"version": "2.0.0.862",
		"date": "2022-06-01 00:41:05 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Oracle DB: On connection error, the error message from OCI is now displayed"
		]
	},
	{
		"version": "2.0.0.861",
		"date": "2022-05-30 23:21:04 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed phpstan warning"
		]
	},
	{
		"version": "2.0.0.860",
		"date": "2022-05-29 22:55:17 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Extended plugin check: The namespace of an object type plugin must be lower-case"
		]
	},
	{
		"version": "2.0.0.859",
		"date": "2022-05-29 20:44:58 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.858",
		"date": "2022-05-29 20:30:30 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"BUGFIX: GUID and FourCC: category in treeview was \"/examples\" instead of \"examples\" for the top level."
		]
	},
	{
		"version": "2.0.0.857",
		"date": "2022-05-29 20:25:19 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fix runtime error that lead to a stalled update"
		]
	},
	{
		"version": "2.0.0.856",
		"date": "2022-05-29 20:06:06 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"BUGFIX: IPv4, IPv6 and GUID identifier were not correctly canonized, therefore they could not be found if e.g. the search term had the wrong upper/lower case"
		]
	},
	{
		"version": "2.0.0.855",
		"date": "2022-05-29 18:12:38 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Object-IDs are now case-sensitive (this is important for object types like FourCC)"
		]
	},
	{
		"version": "2.0.0.854",
		"date": "2022-05-29 17:16:33 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"BUGFIX: Could not create objects on an Oracle database (error: Cannot insert NULL into (\"HR\".\"OBJECTS\".\"CONFIDENTIAL\"))"
		]
	},
	{
		"version": "2.0.0.853",
		"date": "2022-05-29 12:22:19 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small fix in example SQL"
		]
	},
	{
		"version": "2.0.0.852",
		"date": "2022-05-29 01:58:55 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed performance (in re autopublish) if cronjobs are used"
		]
	},
	{
		"version": "2.0.0.851",
		"date": "2022-05-29 01:37:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"BUGFIX: OID Autopublishing caused error message on a web visitors screen, because HTML5 and XML were mixed up"
		]
	},
	{
		"version": "2.0.0.850",
		"date": "2022-05-28 23:27:15 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Bugfix: GUID and FourCC categories can now contain the slash character (/)"
		]
	},
	{
		"version": "2.0.0.849",
		"date": "2022-05-28 21:29:05 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.848",
		"date": "2022-05-28 01:39:22 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Updated example data"
		]
	},
	{
		"version": "2.0.0.847",
		"date": "2022-05-27 20:36:06 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"BUGFIX: No login sessions could be created using PHP 7.0 (Bug introduced in SVN Rev 711)"
		]
	},
	{
		"version": "2.0.0.846",
		"date": "2022-05-27 17:19:54 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update"
		]
	},
	{
		"version": "2.0.0.845",
		"date": "2022-05-27 17:16:09 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.844",
		"date": "2022-05-27 00:30:36 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New object type: Four-Character-Code (FourCC)"
		]
	},
	{
		"version": "2.0.0.843",
		"date": "2022-04-21 00:29:38 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"When an invalid OID was entered in the \"GoTo\" box, the user received a JavaScript error message. Now, they receive a page, as intended."
		]
	},
	{
		"version": "2.0.0.842",
		"date": "2022-04-15 00:59:56 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update"
		]
	},
	{
		"version": "2.0.0.841",
		"date": "2022-04-15 00:54:45 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added Reply-To, because some servers might change the 'From' attribute (Anti-Spoof?)"
		]
	},
	{
		"version": "2.0.0.840",
		"date": "2022-04-15 00:38:08 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Software update page: Added notice to run \"chown -R\" after manual git/svn update."
		]
	},
	{
		"version": "2.0.0.839",
		"date": "2022-04-15 00:08:41 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Add pageLoadedCallbacks structure and triggers as an unified interface for pageLoaded event callbacks (GitHub PR#2, thanks to Simon Tushev)"
		]
	},
	{
		"version": "2.0.0.838",
		"date": "2022-04-15 00:02:46 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Upgraded tushevorg uitweeks plugin to version 1.1 (adding feature: \"Prefer `Login as administrator` tab at login\")"
		]
	},
	{
		"version": "2.0.0.837",
		"date": "2022-04-15 00:00:04 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Wrong SVN path in Software Update page, fixed"
		]
	},
	{
		"version": "2.0.0.836",
		"date": "2022-04-14 23:49:19 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Bugfix: Attachments plugin wrong error message when graylist is not enabled and file ext is not in whitelist"
		]
	},
	{
		"version": "2.0.0.835",
		"date": "2022-04-14 23:39:53 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Avoid that FreeOID users masquerade as ViaThinkSoft object type plugins"
		]
	},
	{
		"version": "2.0.0.834",
		"date": "2022-04-14 00:03:55 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added whitelist to file types in the attachment plugins"
		]
	},
	{
		"version": "2.0.0.833",
		"date": "2022-04-13 23:42:25 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Success alert() messages are now non-blocking Toasts",
			"Added JavaScript callback ajaxPageLoadedCallbacks (gets only executed for page loads via ajax.php, not F5-Key-Pageloads)",
			"Vendor update: Renewed TinyMCE"
		]
	},
	{
		"version": "2.0.0.832",
		"date": "2022-04-11 01:37:26 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Updated ViaThinkSoft FreeOID ToS"
		]
	},
	{
		"version": "2.0.0.831",
		"date": "2022-04-11 00:47:35 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fix bug that caused system ID to get lost"
		]
	},
	{
		"version": "2.0.0.830",
		"date": "2022-04-10 23:35:04 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"- Security improvement: The private key stored in the database configuration table in the database is now encrypted using a key that will be stored in a file inside userdata ( userdata/privkey_secret.php ).",
			"- !!! ATTENTION: If you have multiple systems access the same database (e.g. you have example.org/oidplus and example.org/oidplus_test ), then the file userdata/privkey_secret.php must kept synchronous between both, otherwise you will lose your private/public key-pair and get a new system-id every time you restart OIDplus !!!",
			"- OIDplus can't connect to databases that are newer than the own program files anymore, avoiding data corruption.",
			"- Changed database version from 205 to 1000."
		]
	},
	{
		"version": "2.0.0.829",
		"date": "2022-04-10 19:07:24 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Bundled tushevorg/publicPages/2000_uitweaks plugin to OIDplus 2.0",
			"New features:",
			"- Fully expand Objects tree on page reload",
			"- Collapse Login tree on page reload",
			"- Collapse Documents&Resources tree on page reload",
			"- Change default tree pane width",
			"- Remember tree pane width in browser.localStorage across page reloads"
		]
	},
	{
		"version": "2.0.0.828",
		"date": "2022-04-09 23:12:50 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added <license> entry in the manifest.xml files, and added it to the plugin overview in the admin login area"
		]
	},
	{
		"version": "2.0.0.827",
		"date": "2022-04-09 18:00:39 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added polyfill that enables some openssl functions using phpseclib emulation, if openssl is not available"
		]
	},
	{
		"version": "2.0.0.826",
		"date": "2022-04-09 12:03:36 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed wrong hash_pbkdf2 length (has problems with OpenSSL supplement)"
		]
	},
	{
		"version": "2.0.0.825",
		"date": "2022-04-09 12:00:32 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"- If plugins tried to log things before the logger plugins were initialized, then nothing happened. The log messages are now submitted delayed.",
			"- Improved compatibility with OpenSSL not working out of the box if openssl.cnf file is missing."
		]
	},
	{
		"version": "2.0.0.824",
		"date": "2022-04-08 20:08:07 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Bugfix: Wrong HTML comment removal of static content"
		]
	},
	{
		"version": "2.0.0.823",
		"date": "2022-04-08 00:57:07 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.822",
		"date": "2022-04-08 00:50:56 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.821",
		"date": "2022-04-08 00:38:17 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added page-plugin method \"htmlPostprocess\" and moved anti-spam-filter from base files into a new plugin."
		]
	},
	{
		"version": "2.0.0.820",
		"date": "2022-04-07 23:46:57 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.819",
		"date": "2022-04-07 23:22:33 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added page-plugin method htmlHeaderUpdate() to modify contents in the HTML <head>, e.g. to insert dynamic JavaScript or changing meta-tags on-the-fly."
		]
	},
	{
		"version": "2.0.0.818",
		"date": "2022-04-06 23:29:45 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.817",
		"date": "2022-04-05 16:51:30 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Changed algorithm for OIDplus Information Object OIDs for third-party objectTypes"
		]
	},
	{
		"version": "2.0.0.816",
		"date": "2022-04-05 16:29:54 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.815",
		"date": "2022-04-05 00:53:12 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update"
		]
	},
	{
		"version": "2.0.0.814",
		"date": "2022-04-05 00:49:02 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"PostgreSQL connection can now be established via socket"
		]
	},
	{
		"version": "2.0.0.813",
		"date": "2022-04-05 00:26:28 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"MySQL connection can now be established via socket"
		]
	},
	{
		"version": "2.0.0.812",
		"date": "2022-04-05 00:11:59 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added baseconfig settings COOKIE_DOMAIN and COOKIE_PATH"
		]
	},
	{
		"version": "2.0.0.811",
		"date": "2022-04-04 21:58:34 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed out-of-the-box bug that prevented redirection to setup/ if userdata/baseconfig/config.inc.php was missing"
		]
	},
	{
		"version": "2.0.0.810",
		"date": "2022-03-30 15:00:20 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Publishing of RFC draft-viathinksoft-oidip-02"
		]
	},
	{
		"version": "2.0.0.809",
		"date": "2022-03-26 23:27:36 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"WeidOidConverter.js : WEID \"weid:root:?\" and OID \".\" (OID tree root) can now be handled correctly."
		]
	},
	{
		"version": "2.0.0.808",
		"date": "2022-03-25 21:11:34 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed problem with cookie.path in combination with reverse-proxy"
		]
	},
	{
		"version": "2.0.0.807",
		"date": "2022-03-25 21:08:54 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Updated SVN-Snapshot (TAR.GZ) update procedure. It now also allows to update systems which are protected by htpasswd."
		]
	},
	{
		"version": "2.0.0.806",
		"date": "2022-03-25 00:56:45 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Base-config setting EXPLICIT_ABSOLUTE_SYSTEM_URL has been removed. Its functionality has now been merged with the setting CANONICAL_SYSTEM_URL. Use this instead."
		]
	},
	{
		"version": "2.0.0.805",
		"date": "2022-03-24 17:19:22 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Updated logo",
			"Fixed some small bugs"
		]
	},
	{
		"version": "2.0.0.804",
		"date": "2022-03-24 16:49:56 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.803",
		"date": "2022-03-24 16:48:37 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.802",
		"date": "2022-03-24 16:17:28 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.801",
		"date": "2022-03-24 16:15:23 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Changed OIDplus::webpath() method to include canonical paths"
		]
	},
	{
		"version": "2.0.0.800",
		"date": "2022-03-24 14:34:40 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Renamed all PNG files in plugin folders and moved them in img/ directories."
		]
	},
	{
		"version": "2.0.0.799",
		"date": "2022-03-22 14:43:26 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed issue with OAuth/PHPSessions not working. Bug introduced in svn-778 (13 march 2022) due to the change of the webpath(...,false) behavior."
		]
	},
	{
		"version": "2.0.0.798",
		"date": "2022-03-22 00:20:27 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.797",
		"date": "2022-03-21 23:40:49 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Heavily increased performance of web-updater for installation channel \"TAR.GZ\" (SVN snapshot)"
		]
	},
	{
		"version": "2.0.0.796",
		"date": "2022-03-21 01:13:02 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"phpinfo() cosmetics"
		]
	},
	{
		"version": "2.0.0.795",
		"date": "2022-03-21 00:42:29 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Systeminfo plugin now also shows phpinfo()"
		]
	},
	{
		"version": "2.0.0.794",
		"date": "2022-03-20 23:46:24 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.793",
		"date": "2022-03-20 23:22:22 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New logo, created with Microsoft Paint 3D"
		]
	},
	{
		"version": "2.0.0.792",
		"date": "2022-03-20 00:11:07 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New logo"
		]
	},
	{
		"version": "2.0.0.791",
		"date": "2022-03-19 12:42:23 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Developer documentation"
		]
	},
	{
		"version": "2.0.0.790",
		"date": "2022-03-18 14:03:05 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Introduced method OIDplusQueryResult::any() as alternative to OIDplusQueryResult::num_rows()>0"
		]
	},
	{
		"version": "2.0.0.789",
		"date": "2022-03-18 12:51:47 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.788",
		"date": "2022-03-18 01:30:41 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.787",
		"date": "2022-03-18 01:26:18 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.786",
		"date": "2022-03-18 01:03:21 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"NEW: Native OCI8 PHP plugin support for Oracle databases!"
		]
	},
	{
		"version": "2.0.0.785",
		"date": "2022-03-17 18:45:22 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Oracle DB tutorial"
		]
	},
	{
		"version": "2.0.0.784",
		"date": "2022-03-17 01:03:15 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OOBE was not possible in combination with ViaThinkSoft CAPTCHA. Fixed."
		]
	},
	{
		"version": "2.0.0.783",
		"date": "2022-03-16 23:38:45 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Introduced compatibility with Oracle DB (connect via PDO or ODBC)"
		]
	},
	{
		"version": "2.0.0.782",
		"date": "2022-03-16 21:13:45 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed issues with JavaScripts not working in some situations"
		]
	},
	{
		"version": "2.0.0.781",
		"date": "2022-03-14 00:06:32 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed compatibility issue with Internet Explorer 11"
		]
	},
	{
		"version": "2.0.0.780",
		"date": "2022-03-13 16:24:01 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small fix in the canonical URL algorithm"
		]
	},
	{
		"version": "2.0.0.779",
		"date": "2022-03-13 11:38:17 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(Minor changes)"
		]
	},
	{
		"version": "2.0.0.778",
		"date": "2022-03-13 11:31:06 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"- Plugins can now control the output of HTTP headers (especially the Content-Security-Policy header)",
			"- Added baseconfig setting CANONICAL_SYSTEM_URL",
			"- Fixed issue with relative paths (OIDplus::webpath(...,fase) over a proxy"
		]
	},
	{
		"version": "2.0.0.777",
		"date": "2022-03-10 01:45:11 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update"
		]
	},
	{
		"version": "2.0.0.776",
		"date": "2022-03-10 01:43:36 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New feature: Registered systems will now be abled to be reached via WHOIS/OID-IP address \"whois.viathinksoft.de:43\". This information will be shown at the WHOIS/OID-IP page.",
			"Additionally, the user can also overwrite this value with their own WHOIS/OID-IP server, if they have one."
		]
	},
	{
		"version": "2.0.0.775",
		"date": "2022-03-08 23:12:20 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.774",
		"date": "2022-03-08 21:56:30 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Gotobox/Whois: Namespace e.g. \"oid:\" is now case insensitive"
		]
	},
	{
		"version": "2.0.0.773",
		"date": "2022-03-08 20:34:14 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-IP(WHOIS), and \"Go\" bar now accept \"WEID\" (they get converted to \"OID\" during the processing)"
		]
	},
	{
		"version": "2.0.0.772",
		"date": "2022-03-08 02:20:51 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"The \"weid:\" Syntax can now be used when creating a root OID.",
			"Class C WEIDs now have an \"WEID\" icon instead of an \"OID\" icon."
		]
	},
	{
		"version": "2.0.0.771",
		"date": "2022-03-06 12:12:12 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.770",
		"date": "2022-03-06 11:48:01 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.769",
		"date": "2022-03-05 18:10:05 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"WeidOidConverter.js: Fixed weLuhn checksum bug (.0 arcs)",
			"WeidOidConverter.js: Added OID validation checks",
			"WeidOidConverter.js: Added \"UMD\" module code by Webfan",
			"Added weid_converter.html (for internal use / testing)"
		]
	},
	{
		"version": "2.0.0.768",
		"date": "2022-03-03 01:31:39 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.767",
		"date": "2022-03-03 01:12:47 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Theme color (URL bar color) can now be set by plugins and can be changed by the color plugin.",
			"Mobile design fixed problem with border at the bottom of the content pane."
		]
	},
	{
		"version": "2.0.0.766",
		"date": "2022-03-02 16:18:42 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RFC"
		]
	},
	{
		"version": "2.0.0.765",
		"date": "2022-03-02 15:30:50 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OIDIP RFC draft update.",
			"OIDIP Removed \"txt\" format (correct is \"text\").",
			"OIDIP Unimplemented formats raise now a \"Service error\" as defined in the new RFC draft."
		]
	},
	{
		"version": "2.0.0.764",
		"date": "2022-03-01 17:00:00 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RFC"
		]
	},
	{
		"version": "2.0.0.763",
		"date": "2022-03-01 00:09:40 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.762",
		"date": "2022-02-28 10:37:03 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.761",
		"date": "2022-02-28 10:35:05 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RFC Update (WIP)"
		]
	},
	{
		"version": "2.0.0.760",
		"date": "2022-02-27 19:17:40 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.759",
		"date": "2022-02-27 18:19:50 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Update to TinyMCE 5.10.3"
		]
	},
	{
		"version": "2.0.0.758",
		"date": "2022-02-27 18:10:02 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-IP RFC (draft-viathinksoft-oidip-02, Work-In-Progress): Added XML and JSON. Renamed \"whois\" node in \"oidip\".",
			"OIDplus for DOS: Implemented PgUp and PgDown. You can now jump to an OID from the TreeView. Release 2022-02-27."
		]
	},
	{
		"version": "2.0.0.757",
		"date": "2022-02-25 14:51:46 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.756",
		"date": "2022-02-23 22:33:01 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Security fix"
		]
	},
	{
		"version": "2.0.0.755",
		"date": "2022-02-23 21:43:48 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Nostalgia plugin fixed"
		]
	},
	{
		"version": "2.0.0.754",
		"date": "2022-02-23 13:24:51 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New plugin \"Nostalgia\" (in admin control panel) to create a database for OIDplus for DOS, Windows 3.11, or Windows 95."
		]
	},
	{
		"version": "2.0.0.753",
		"date": "2022-02-23 01:28:19 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"GUID+IPv4+IPv6+OID: Technical info visual changes and link to help topics",
			"TinyMCE: 'imagetools' and 'toc' added 23 February 2022, because they are declared as deprecated and marked for removal in TinyMCE 6.0 (\"moving to premium\")"
		]
	},
	{
		"version": "2.0.0.752",
		"date": "2022-02-23 00:35:58 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Changed WEID converter code so that it doesn't require the package mikemcl/bignumber.js anymore. (Removed now from composer.json)"
		]
	},
	{
		"version": "2.0.0.751",
		"date": "2022-02-22 21:48:49 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Changed WeidOidConverter.js"
		]
	},
	{
		"version": "2.0.0.750",
		"date": "2022-02-22 17:02:58 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"WEID<=>OID Converter in JavaScript"
		]
	},
	{
		"version": "2.0.0.732",
		"date": "2022-01-27 19:18:31 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update"
		]
	},
	{
		"version": "2.0.0.731",
		"date": "2022-01-23 22:12:33 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.730",
		"date": "2022-01-23 22:10:52 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"\"ImplementsFeature\" is now available for all PHP classes of OIDplus, not just Plugin classes.",
			"Therefore, OID-WHOIS can now also receive WHOIS attributes from Objects (not just the Object Plugin) or the OIDplusRA class."
		]
	},
	{
		"version": "2.0.0.729",
		"date": "2022-01-23 22:07:02 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"BUGFIX: Whois page did not work if you just had non-OIDs but single OID in your system"
		]
	},
	{
		"version": "2.0.0.728",
		"date": "2022-01-08 00:14:54 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update (PHPStan)"
		]
	},
	{
		"version": "2.0.0.727",
		"date": "2022-01-07 19:42:52 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Local GIT-Version could not be detected successfully! Fixed!"
		]
	},
	{
		"version": "2.0.0.726",
		"date": "2022-01-07 13:54:49 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Updated Alpine Linux installation steps"
		]
	},
	{
		"version": "2.0.0.725",
		"date": "2022-01-07 02:04:54 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"PHP extension iconv is no longer be needed if extension mbstring is installed"
		]
	},
	{
		"version": "2.0.0.724",
		"date": "2022-01-07 01:37:31 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OIDInfo Export will check if extension \"sockets\" is installed"
		]
	},
	{
		"version": "2.0.0.723",
		"date": "2022-01-07 01:21:37 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.722",
		"date": "2022-01-07 00:02:23 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Adding Alpine Linux install notes. Checking for dependencies for lightweight PHP installations"
		]
	},
	{
		"version": "2.0.0.721",
		"date": "2022-01-06 23:38:52 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Bugfixes"
		]
	},
	{
		"version": "2.0.0.720",
		"date": "2022-01-06 22:06:30 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OIDplus doesn't require the PHP extensions php-ctype and php-posix anymore"
		]
	},
	{
		"version": "2.0.0.719",
		"date": "2021-12-29 00:42:04 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed PHP warnings"
		]
	},
	{
		"version": "2.0.0.718",
		"date": "2021-12-28 00:05:12 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed problems with the update script generator (TAR.GZ distribution channel)",
			"- Update 707 did not correctly create plugins/viathinksoft/objectTypes/domain/img/* (please manually create these files if you are affected)",
			"- Update 708 did not correctly delete vendor/google (please delete manually if you are affected)"
		]
	},
	{
		"version": "2.0.0.717",
		"date": "2021-12-27 18:26:18 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"BUGFIX: Saving a design permanently did not work. Fixed."
		]
	},
	{
		"version": "2.0.0.716",
		"date": "2021-12-27 17:31:12 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed update failure of version 698 => 699",
			"Revision log (software update check) is now compressed using GZip"
		]
	},
	{
		"version": "2.0.0.715",
		"date": "2021-12-27 01:36:01 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Outgoing HTTP transfer will have the User Agent \"ViaThinkSoft-OIDplus/2.0\""
		]
	},
	{
		"version": "2.0.0.714",
		"date": "2021-12-26 22:03:26 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Whois opens in new browser window"
		]
	},
	{
		"version": "2.0.0.713",
		"date": "2021-12-26 21:41:08 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed bug in OID-IP (OID WHOIS): Superior detection did not work for non-OIDs. Fixed."
		]
	},
	{
		"version": "2.0.0.712",
		"date": "2021-12-26 18:44:52 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.711",
		"date": "2021-12-26 18:33:19 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Auth keys (internally used for email verification etc.) now use HMAC instead of normal hash",
			"Added new base config setting RA_PASSWORD_PEPPER_ALGO (dangerous! Only for experts!)",
			"OIDplusSessionHandler.class.php: Improved internal encryption!",
			"",
			"ATTENTION",
			"!!! If you are updating from the TAR.GZ distibution channel, then the update *will* temporarily",
			"!!! FAIL with the error message \"Authentication failed\". Once the error appears, close your",
			"!!! browser window and delete the cookies, then log-in again, and continue the update process.",
			"!!! (The error happens because of the update of the internal session encryption procedure)"
		]
	},
	{
		"version": "2.0.0.710",
		"date": "2021-12-26 17:38:24 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.709",
		"date": "2021-12-26 17:36:56 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New CAPTCHA Method: ViaThinkSoft Client Challenge (lets the CPU of the user calculate a cryptographical problem in the background)"
		]
	},
	{
		"version": "2.0.0.708",
		"date": "2021-12-26 15:58:03 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Removed unnecessary ReCAPTCHA composer dependency"
		]
	},
	{
		"version": "2.0.0.707",
		"date": "2021-12-26 15:54:36 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New object type \"Domain\""
		]
	},
	{
		"version": "2.0.0.706",
		"date": "2021-12-26 01:58:56 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OOBE/Setup: Setting \"SSL enforcement\" will be pre-selected during setup. Self-signed certs (e.g. on a XAMPP installation) will result in \"no SSL enforce\" in order to avoid unexpected browser warnings during the Out-Of-Box-Experience (OOBE)."
		]
	},
	{
		"version": "2.0.0.705",
		"date": "2021-12-26 01:38:47 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"PHPStan 1.2.0 pass"
		]
	},
	{
		"version": "2.0.0.704",
		"date": "2021-12-26 01:33:58 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"BUGFIX: Search plugin highlighting did not work correctly if the search term was found inside the OID dot-notation or RA email address. Fixed.",
			"BUGFIX: Entering an OID in the GoTo-Box or the Search did not open it in the left panel. Fixed.",
			"BUGFIX: Link \"Go back to RA listing\" (only works for logged in admins) not visible at public RA listing anymore."
		]
	},
	{
		"version": "2.0.0.703",
		"date": "2021-12-26 00:55:00 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small fix"
		]
	},
	{
		"version": "2.0.0.702",
		"date": "2021-12-26 00:26:02 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New plugin type: CAPTCHA plugins!"
		]
	},
	{
		"version": "2.0.0.701",
		"date": "2021-12-20 01:48:32 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Remove Docker files from vendor dir"
		]
	},
	{
		"version": "2.0.0.700",
		"date": "2021-12-17 16:54:04 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Syntax error fixed"
		]
	},
	{
		"version": "2.0.0.699",
		"date": "2021-12-17 16:48:07 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"The new file edition.ini contains constants which might be useful if somebody wants to fork OIDplus",
			"(However, we would appreciate it if you would try to contribute to the original OIDplus system rather than forking it!)"
		]
	},
	{
		"version": "2.0.0.698",
		"date": "2021-12-15 17:10:50 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small refactoring"
		]
	},
	{
		"version": "2.0.0.697",
		"date": "2021-12-13 00:16:37 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Admin system info plugin: \"PHP Installed extensions\" is now listed",
			"Admin system update plugin: Actual GIT and SVN commands are now visible, and GIT PULL command slightly changed (added origin master)"
		]
	},
	{
		"version": "2.0.0.696",
		"date": "2021-12-12 13:22:08 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Software update visual mistake"
		]
	},
	{
		"version": "2.0.0.695",
		"date": "2021-12-12 13:13:31 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.694",
		"date": "2021-12-12 12:41:17 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"In the OID grid, you can now directly register an IANA or ViaThinkSoft OID!",
			"Globally, every link that opens a new window gets marked by an icon"
		]
	},
	{
		"version": "2.0.0.693",
		"date": "2021-12-12 12:08:23 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"\"Generate\" links can now be defined by the plugin type",
			"WEID input is now enforced upper case"
		]
	},
	{
		"version": "2.0.0.692",
		"date": "2021-12-12 02:52:14 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed \"Generate UUID OID\" link in OID 2.25.",
			"Added \"Generate UUID OID\" to the root OID page.",
			"Added \"Generate GUID\" to the root GUID page."
		]
	},
	{
		"version": "2.0.0.691",
		"date": "2021-12-10 01:48:37 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"GIT-Software update can now also be executed for non .git directories, e.g. if hosted via Plesk GIT (requires shell access)"
		]
	},
	{
		"version": "2.0.0.690",
		"date": "2021-12-10 00:03:21 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed more cases of Plesk Git format"
		]
	},
	{
		"version": "2.0.0.689",
		"date": "2021-12-10 00:00:20 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fix in re Base36 column on weid:? and weid:pen:? arc"
		]
	},
	{
		"version": "2.0.0.688",
		"date": "2021-12-09 15:16:38 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"System information plugin minor changes"
		]
	},
	{
		"version": "2.0.0.687",
		"date": "2021-12-09 10:00:54 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Software update: Even after an error occurred, you can click a \"Reload page\" button now"
		]
	},
	{
		"version": "2.0.0.686",
		"date": "2021-12-08 23:08:36 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed problem with WEID weLuhn check digit if an arc was 0.",
			"Improved update procedure on VTS server-side."
		]
	},
	{
		"version": "2.0.0.685",
		"date": "2021-12-08 21:40:08 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"System information plugin: Display of username optimized. Catched errors. Shows effective process user instead of script file owner"
		]
	},
	{
		"version": "2.0.0.684",
		"date": "2021-12-08 21:39:33 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small fix in re WEID Base36 column in the CRUD grid"
		]
	},
	{
		"version": "2.0.0.683",
		"date": "2021-12-08 17:01:54 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Definition of class A, B, C WEIDs. In the latest specification, every OID can be represented as a WEID. Therefore, the column \"Base36\" is now present for every OID."
		]
	},
	{
		"version": "2.0.0.682",
		"date": "2021-12-08 14:44:42 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Plugins can now alter the Visible/Protected flag in the settings even after they were initialized"
		]
	},
	{
		"version": "2.0.0.681",
		"date": "2021-12-08 00:27:40 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Plesk Git is now supported"
		]
	},
	{
		"version": "2.0.0.680",
		"date": "2021-12-06 15:23:58 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Autoupdate for ZIP-WC systems accidentally deleted files, breaking the installation. Affected version was SVN Rev 679. Fixed. If you were affected, please download the ZIP and extract it over your installation."
		]
	},
	{
		"version": "2.0.0.679",
		"date": "2021-12-04 22:47:34 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Updated 3P. Fixed deprecated JWT parameter."
		]
	},
	{
		"version": "2.0.0.678",
		"date": "2021-11-24 23:16:13 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fix of XML exporter in regards Unicode characters"
		]
	},
	{
		"version": "2.0.0.677",
		"date": "2021-10-25 12:54:17 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.676",
		"date": "2021-10-11 00:37:25 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Page \"show RA details\": Added link \"Create RA manually\" (only if admin is logged in)"
		]
	},
	{
		"version": "2.0.0.675",
		"date": "2021-10-11 00:16:40 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"BUGFIX: Superior RAs were not able to update delegated objects (e.g. to change ASN.1/IRI/EMail/HiddenFlag)"
		]
	},
	{
		"version": "2.0.0.674",
		"date": "2021-10-06 23:57:10 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-IP (Whois): Fixed problem with word-wrap"
		]
	},
	{
		"version": "2.0.0.673",
		"date": "2021-10-06 22:36:30 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"BUGFIX: URL in whois plugin was wrong. Fixed"
		]
	},
	{
		"version": "2.0.0.672",
		"date": "2021-10-06 22:03:02 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"BUGFIX: Adding a new object type plugin to an existing system raised an error. Fixed.",
			"BUGFIX: WEID with mixed upper/lower-case could not be converted to numeric value. Fixed."
		]
	},
	{
		"version": "2.0.0.671",
		"date": "2021-10-06 17:12:12 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"TinyMCE: Deprecated 'spellchecker' plugin is now excluded"
		]
	},
	{
		"version": "2.0.0.670",
		"date": "2021-10-05 15:56:02 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.669",
		"date": "2021-10-05 15:38:22 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"cron.sh is now executable (only applies to Linux/Mac)"
		]
	},
	{
		"version": "2.0.0.668",
		"date": "2021-10-05 12:43:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.667",
		"date": "2021-10-05 12:30:54 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.666",
		"date": "2021-10-05 12:10:03 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added compatibility with webfan plugin 'weid'"
		]
	},
	{
		"version": "2.0.0.665",
		"date": "2021-10-04 23:10:48 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.664",
		"date": "2021-10-04 22:39:22 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added possibility to execute cronjobs (e.g. to increase performance with auto publishing)",
			"Small improvement to auto updater conflict backup"
		]
	},
	{
		"version": "2.0.0.663",
		"date": "2021-10-04 16:25:17 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Snapshot distribution channel: If files have been modified outside the updater, a backup is now automatically created (NOT for Git/SVN distribution channel!)"
		]
	},
	{
		"version": "2.0.0.662",
		"date": "2021-10-04 00:27:48 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"System update page: GIT-WorkingCopyUpdate and SVN-WorkingCopyUpdate can now be executed online (execution and write permissions required)",
			"System information page: System user account will be shown"
		]
	},
	{
		"version": "2.0.0.661",
		"date": "2021-10-03 21:13:11 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Snapshot distribution channel: oidplus_version.txt is now .version.php (to avoid that the version is exposed)"
		]
	},
	{
		"version": "2.0.0.660",
		"date": "2021-10-03 12:04:58 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"git distribution channel: added function to read the gitsvn version without the requirement of having access to the \"git\" commandline"
		]
	},
	{
		"version": "2.0.0.659",
		"date": "2021-10-01 21:33:40 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Web-update JavaScript translation"
		]
	},
	{
		"version": "2.0.0.658",
		"date": "2021-10-01 16:22:55 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Web-update: Success message and reload-button are now displayed"
		]
	},
	{
		"version": "2.0.0.657",
		"date": "2021-09-30 22:42:16 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Uploaded new RFC draft"
		]
	},
	{
		"version": "2.0.0.656",
		"date": "2021-09-30 16:28:50 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.655",
		"date": "2021-09-30 00:08:37 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.654",
		"date": "2021-09-29 00:31:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Web-Update small changes"
		]
	},
	{
		"version": "2.0.0.653",
		"date": "2021-09-29 00:04:58 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small changes to the Web-Updater"
		]
	},
	{
		"version": "2.0.0.652",
		"date": "2021-09-28 23:03:39 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.651",
		"date": "2021-09-27 14:33:33 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Updates are now digitally signed.",
			"If an update outputs \"FATAL ERROR\", then the update process will be aborted."
		]
	},
	{
		"version": "2.0.0.650",
		"date": "2021-09-27 00:34:49 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Update packages can now be optionally be downloaded compressed (GZ)"
		]
	},
	{
		"version": "2.0.0.649",
		"date": "2021-09-26 23:53:54 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.648",
		"date": "2021-09-26 22:04:28 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Now completely get rid off the WebSVN classes! The distribution update procedures are now easier, quicker and safer!"
		]
	},
	{
		"version": "2.0.0.647",
		"date": "2021-09-26 20:14:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Renewed update system (part 1): Updates are now downloaded as \"update scripts\" instead of being pulled from SVN"
		]
	},
	{
		"version": "2.0.0.646",
		"date": "2021-09-25 21:24:12 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Plugins are again sorted by their type and name, as if they would be in a single vendor-folder"
		]
	},
	{
		"version": "2.0.0.645",
		"date": "2021-09-25 20:13:12 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"\"System check\" plugin doesn't list third-party plugins and composer.lock file anymore"
		]
	},
	{
		"version": "2.0.0.644",
		"date": "2021-09-25 18:42:44 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.643",
		"date": "2021-09-25 00:06:45 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.642",
		"date": "2021-09-25 00:01:41 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed mime types of JS and XML files, so that they are treated as text files again (can be diffed etc.)"
		]
	},
	{
		"version": "2.0.0.641",
		"date": "2021-09-24 23:30:14 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Internet Explorer 11 is supported again"
		]
	},
	{
		"version": "2.0.0.640",
		"date": "2021-09-24 18:36:17 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Updated wellknown country OIDs (added Canada) and developer script"
		]
	},
	{
		"version": "2.0.0.639",
		"date": "2021-09-24 16:31:57 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Replaced \"register_shutdown_function\" function with an individual function (since JWT login didn't work with Strato provider)"
		]
	},
	{
		"version": "2.0.0.638",
		"date": "2021-09-24 16:23:22 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Ironbase design: Button texts are now white, like in the default design"
		]
	},
	{
		"version": "2.0.0.637",
		"date": "2021-09-24 12:19:16 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Vendor update"
		]
	},
	{
		"version": "2.0.0.636",
		"date": "2021-09-24 12:16:06 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed warning \"legacyoutput\" deprecated in TinyMCE"
		]
	},
	{
		"version": "2.0.0.635",
		"date": "2021-09-24 12:12:32 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Changed plugins path structure",
			"Old: plugins/[plugintype]/[pluginname]",
			"New: plugins/[vendor]/[plugintype]/[pluginname]",
			"",
			"!!!!!! ATTENTION !!!!!! ATTENTION !!!!!! ATTENTION !!!!!!",
			"ALL DIRECTORIES INSIDE THE FOLDER plugin/ WILL BE DELETED",
			"PLEASE MAKE A BACKUP OF THESE FOLDERS BEFORE UPDATING!",
			"!!!!!! ATTENTION !!!!!! ATTENTION !!!!!! ATTENTION !!!!!!",
			"",
			"If you have individual third-party plugins, please make",
			"sure that they make use of the new plugin directory structure."
		]
	},
	{
		"version": "2.0.0.634",
		"date": "2021-09-24 11:18:52 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"VTS plugins are now independent from the \"plugins/\" directory"
		]
	},
	{
		"version": "2.0.0.633",
		"date": "2021-09-23 22:03:27 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Software update changelog: Very important messages (containing three exclamation marks) are now marked red."
		]
	},
	{
		"version": "2.0.0.632",
		"date": "2021-09-23 21:16:24 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Temporarily, third-party plugins must be moved in the folder plugins/_thirdParty instead of plugins/ (will be changed again in a few days!)"
		]
	},
	{
		"version": "2.0.0.631",
		"date": "2021-09-23 20:38:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Sorted \"acknowledgements\" third party products"
		]
	},
	{
		"version": "2.0.0.630",
		"date": "2021-09-13 00:46:57 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-WHOIS: Added server command \"$format=json|txt|xml\". This allows the usage of JSON and XML even over the WhoIs protocol, so that web-whois is not neccessary"
		]
	},
	{
		"version": "2.0.0.629",
		"date": "2021-09-06 22:50:02 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Preparing for the next version of the RFC!"
		]
	},
	{
		"version": "2.0.0.628",
		"date": "2021-06-14 13:32:26 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed LDAP plugin"
		]
	},
	{
		"version": "2.0.0.627",
		"date": "2021-06-12 23:37:09 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed problem in SimpleXML supplement"
		]
	},
	{
		"version": "2.0.0.626",
		"date": "2021-06-12 23:34:42 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.625",
		"date": "2021-06-12 23:10:32 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"LDAP: Added multi-domain support"
		]
	},
	{
		"version": "2.0.0.624",
		"date": "2021-06-11 16:23:10 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"LDAP: The search for RA/Admin group membershop can now also include sub-groups"
		]
	},
	{
		"version": "2.0.0.623",
		"date": "2021-06-11 11:11:28 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"LDAP: Removed feature LDAP_ADMIN_IS_OIDPLUS_ADMIN; instead introduced settings LDAP_ADMIN_GROUP and LDAP_RA_GROUP"
		]
	},
	{
		"version": "2.0.0.622",
		"date": "2021-06-11 00:37:28 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Change to JWT key of HS512 (if no PKI is available)"
		]
	},
	{
		"version": "2.0.0.621",
		"date": "2021-06-11 00:27:12 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"ViaThinkSoft repos switched from SVN to Packagist/GitHub"
		]
	},
	{
		"version": "2.0.0.620",
		"date": "2021-06-10 16:16:05 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"LDAP authentication plugin: The main authentication now works using UPN (userPrincipalName) instead of the mail address of the user. The control user is not required in the base configuration anymore, and there is no requirement in adding email addresses for the domain users."
		]
	},
	{
		"version": "2.0.0.619",
		"date": "2021-06-04 15:52:28 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added: Tool to find out best bcrypt cost (<1s)"
		]
	},
	{
		"version": "2.0.0.618",
		"date": "2021-06-02 00:11:33 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Updated firebase/php-jwt : 5.2.1 => 5.3.0"
		]
	},
	{
		"version": "2.0.0.617",
		"date": "2021-06-02 00:03:52 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added new high-security feature RA Password Pepper (use with extreme caution! Existing passwords will become invalid)",
			"RA password generation: BCrypt \"cost\" parameter can now be configured."
		]
	},
	{
		"version": "2.0.0.616",
		"date": "2021-05-31 01:43:58 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Made vendor/ directory a bit more slim (removed unnecessary bootstrap files)"
		]
	},
	{
		"version": "2.0.0.615",
		"date": "2021-05-31 00:55:04 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Additional security for individual attachment directories"
		]
	},
	{
		"version": "2.0.0.614",
		"date": "2021-05-30 20:34:14 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed race-condition of configuration table after software update."
		]
	},
	{
		"version": "2.0.0.613",
		"date": "2021-05-30 19:56:57 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fix"
		]
	},
	{
		"version": "2.0.0.612",
		"date": "2021-05-30 19:49:45 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New feature: Attachment path can now be changed by the administrator (system configuration)."
		]
	},
	{
		"version": "2.0.0.611",
		"date": "2021-05-30 00:19:54 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.610",
		"date": "2021-05-30 00:04:39 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.609",
		"date": "2021-05-29 23:09:04 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"The administrator account can now have more than one valid password.",
			"webwhois.php is now disabled if the WHOIS plugin is disabled"
		]
	},
	{
		"version": "2.0.0.608",
		"date": "2021-05-29 20:58:08 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.607",
		"date": "2021-05-29 10:37:35 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New feature: Plugins can now offer an ajax.php interface without CSRF verification",
			"ViaThinkSoft Registration: System URL verification now uses a separate function (was previously WHOIS signature verification)"
		]
	},
	{
		"version": "2.0.0.606",
		"date": "2021-05-28 14:04:18 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed critical bug"
		]
	},
	{
		"version": "2.0.0.605",
		"date": "2021-05-27 16:31:56 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.604",
		"date": "2021-05-26 14:50:57 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.603",
		"date": "2021-05-26 14:38:39 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New SVN repository php_utilities by ViaThinkSoft"
		]
	},
	{
		"version": "2.0.0.602",
		"date": "2021-05-26 13:44:57 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"uuid_utils.inc.php now comes from a SVN repository"
		]
	},
	{
		"version": "2.0.0.601",
		"date": "2021-05-26 00:44:36 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"README is now in the MarkDown (MD) format"
		]
	},
	{
		"version": "2.0.0.600",
		"date": "2021-05-25 22:17:18 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed SimpleXML supplement"
		]
	},
	{
		"version": "2.0.0.599",
		"date": "2021-05-25 00:44:22 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed bcrypt worker"
		]
	},
	{
		"version": "2.0.0.598",
		"date": "2021-05-24 23:48:14 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.597",
		"date": "2021-05-24 23:46:37 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"NOW USING COMPOSER FOR DEPENDENCIES. Removed directory \"3p\" and replaced it with directory \"vendor\".",
			"Note that the \"vendor\" directory is still pushed via SVN in order to make WebSVN updater work.",
			"",
			"!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!",
			"!! ATTENTION!",
			"!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!",
			"!! The WebSVN updater might crash due to a timeout because",
			"!! there are to many changes.",
			"!! For this update, it is safer to download the .tar.gz file",
			"!! and extract it in your directory",
			"!! https://www.oidplus.com/download.php",
			"!! As long as you put all of your data in the userdata/ directory,",
			"!! your data should be safe.",
			"!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!"
		]
	},
	{
		"version": "2.0.0.596",
		"date": "2021-05-24 02:20:25 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.595",
		"date": "2021-05-24 02:17:38 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.594",
		"date": "2021-05-24 01:54:21 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(Mostly) reached PHPStan Level 6"
		]
	},
	{
		"version": "2.0.0.593",
		"date": "2021-05-23 23:17:28 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.592",
		"date": "2021-05-23 22:53:31 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(Nearly) reached PHPStan level 5"
		]
	},
	{
		"version": "2.0.0.591",
		"date": "2021-05-23 20:19:56 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"PHPStan Level 4 reached"
		]
	},
	{
		"version": "2.0.0.590",
		"date": "2021-05-23 18:45:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"PHPStan Level 3 reached"
		]
	},
	{
		"version": "2.0.0.589",
		"date": "2021-05-23 18:26:08 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"PHPStan Level 2 reached"
		]
	},
	{
		"version": "2.0.0.588",
		"date": "2021-05-23 18:01:30 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"PHPStan Level 1 reached"
		]
	},
	{
		"version": "2.0.0.587",
		"date": "2021-05-23 17:39:47 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.586",
		"date": "2021-05-23 17:39:27 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.585",
		"date": "2021-05-23 17:36:08 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Very large refactoring of login methods; JWT methods encapsulated",
			"\"Remember me\" (JWT cookie) and regular logins (PHP session) cannot be mixed anymore (which didn't work anyway)"
		]
	},
	{
		"version": "2.0.0.584",
		"date": "2021-05-23 16:50:24 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed \"Create RA manually\" plugin in admin login area"
		]
	},
	{
		"version": "2.0.0.583",
		"date": "2021-05-22 11:32:35 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added JWT TTL (time to live) config value"
		]
	},
	{
		"version": "2.0.0.582",
		"date": "2021-05-17 22:41:55 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Automated AJAX calls: Added Python example"
		]
	},
	{
		"version": "2.0.0.581",
		"date": "2021-05-17 22:41:35 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Privacy documentation"
		]
	},
	{
		"version": "2.0.0.580",
		"date": "2021-05-17 19:23:50 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Cookies now get the \"secure\" flag if OIDplus is visited from HTTPS connection"
		]
	},
	{
		"version": "2.0.0.579",
		"date": "2021-05-17 17:51:20 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Login \"remember me\" feature (using JWT authentication)"
		]
	},
	{
		"version": "2.0.0.578",
		"date": "2021-05-17 01:27:25 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"JWT authentication security improvements"
		]
	},
	{
		"version": "2.0.0.577",
		"date": "2021-05-16 21:28:47 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"JWT cosmetics"
		]
	},
	{
		"version": "2.0.0.576",
		"date": "2021-05-16 20:24:19 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small improvements to JWT authentication. Renamed \"NBF\" to \"Blacklisted\" to avoid confusion"
		]
	},
	{
		"version": "2.0.0.575",
		"date": "2021-05-16 11:55:28 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Security fix"
		]
	},
	{
		"version": "2.0.0.574",
		"date": "2021-05-16 03:04:22 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"NEW: JWT tokens of Automated AJAX calls can now be blacklisted",
			"REMOVED: Automated AJAX calls using \"batch_username\" arguments"
		]
	},
	{
		"version": "2.0.0.573",
		"date": "2021-05-15 22:22:16 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.572",
		"date": "2021-05-15 21:50:39 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added possibility to disable JWT token authentication in the base configuration"
		]
	},
	{
		"version": "2.0.0.571",
		"date": "2021-05-15 21:17:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OIDplusConfigInterface renamed to OIDplusGetterSetterInterface",
			"Improved OIDplus class autoloader (supports namespaces)"
		]
	},
	{
		"version": "2.0.0.570",
		"date": "2021-05-15 17:00:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Security: JWTs are now signed using RSA if OpenSSL is available.",
			"Security: If not, then the key of JWT (which is the server secret) is processed via PBKDF2"
		]
	},
	{
		"version": "2.0.0.569",
		"date": "2021-05-15 16:00:35 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OOP"
		]
	},
	{
		"version": "2.0.0.568",
		"date": "2021-05-15 12:40:35 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Automated AJAX: Updated examples to JWT token"
		]
	},
	{
		"version": "2.0.0.567",
		"date": "2021-05-14 16:56:56 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Removed: Session handler cannot be accessed outside the authentification utilities anymore"
		]
	},
	{
		"version": "2.0.0.566",
		"date": "2021-05-14 16:07:03 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Automated AJAX requests are now protected via a JWT, which is signed and doesn't contain the user's password anymore. The old method (username+password+antiBruteforceUnlockKey) is still accepted for backwards compatibility.",
			"REMOVED: OIDplusSessionHandler->simulate",
			"Created new class \"OIDplusAuthContentStore\" to make \"OIDplusAuthUtils\" more flexible"
		]
	},
	{
		"version": "2.0.0.565",
		"date": "2021-05-13 22:08:23 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Security: Google OAuth Security Token (JWT) is now verified (optional)"
		]
	},
	{
		"version": "2.0.0.564",
		"date": "2021-05-10 20:46:59 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Proper fix for the CSRF Token issue with OAuth (BUG#0000213)"
		]
	},
	{
		"version": "2.0.0.563",
		"date": "2021-05-09 20:32:36 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OAuth plugins display warning if SameSite policy is \"Strict\". A different approach follows later. (BUG#0000213)"
		]
	},
	{
		"version": "2.0.0.562",
		"date": "2021-05-02 22:20:07 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"VNag: Prevent DoS attack by caching the result for 60 seconds"
		]
	},
	{
		"version": "2.0.0.561",
		"date": "2021-04-28 19:45:58 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Refactoring of JavaScript code (using AJAX instead of document.getElementByxxx), and other small fixes"
		]
	},
	{
		"version": "2.0.0.560",
		"date": "2021-04-26 18:18:48 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Content is now loaded via jQuery AJAX instead of fetch() API; long page loads can now be aborted by clicking a different menu item [only if the server allows multiple connections for that client?!]"
		]
	},
	{
		"version": "2.0.0.559",
		"date": "2021-04-26 13:21:18 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"\"Please log in\" error messages now automatically select the correct RA/admin in the linked log in form"
		]
	},
	{
		"version": "2.0.0.558",
		"date": "2021-04-26 11:45:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.557",
		"date": "2021-04-25 22:06:14 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Cookie SAMESITE policy can now be configured in the base configuration file.",
			"New clas \"OIDplusCookieUtils\".",
			"In shebang, using \"env php\" instead of \"/usr/bin/php\"."
		]
	},
	{
		"version": "2.0.0.556",
		"date": "2021-04-24 22:47:36 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New 3D logo"
		]
	},
	{
		"version": "2.0.0.555",
		"date": "2021-04-23 17:28:56 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Cookie handling is now in function op_setcookie() instead setcookie().",
			"Now using SameSite=Strict (experimental)"
		]
	},
	{
		"version": "2.0.0.554",
		"date": "2021-04-23 17:00:34 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed bug in WebSVN Updater (files with spaces, e.g. \"Internet Draft.url\" were written with 0 bytes)",
			"Fixed error handling in WebSVN updater"
		]
	},
	{
		"version": "2.0.0.553",
		"date": "2021-04-23 12:20:17 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.552",
		"date": "2021-04-23 00:31:59 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.551",
		"date": "2021-04-22 16:13:57 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed OID-WHOIS bug"
		]
	},
	{
		"version": "2.0.0.550",
		"date": "2021-04-21 22:11:50 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"JavaScript code improvements"
		]
	},
	{
		"version": "2.0.0.549",
		"date": "2021-04-21 18:00:35 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"JavaScript functions of plugins are now put into \"namespaces\" to avoid name conflicts between plugins",
			"Changed \"OIDplus::authUtils()::\" to \"OIDplus::authUtils()->\" everywhere"
		]
	},
	{
		"version": "2.0.0.548",
		"date": "2021-04-20 23:32:56 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.547",
		"date": "2021-04-20 23:22:45 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Renamed OID-WHOIS to OID-IP (OID Information Protocol) and uploaded draft-viathinksoft-oidip-00 to IETF DataTracker"
		]
	},
	{
		"version": "2.0.0.546",
		"date": "2021-04-19 12:36:23 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Search plugin improvements"
		]
	},
	{
		"version": "2.0.0.545",
		"date": "2021-04-19 00:47:23 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.544",
		"date": "2021-04-18 22:12:33 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Running AJAX requests now get aborted if the user decides to do something else (e.g. click something in the jsTree)",
			"Fixed small bug in search plugin"
		]
	},
	{
		"version": "2.0.0.543",
		"date": "2021-04-18 19:47:15 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Search plugin: Search request can now also be invoked via AJAX, while still being NonJS compatible"
		]
	},
	{
		"version": "2.0.0.542",
		"date": "2021-04-17 21:44:25 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Minor changes"
		]
	},
	{
		"version": "2.0.0.541",
		"date": "2021-04-16 17:38:03 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"JsTree: Target will be displayed in the page footer and right-click \"Open in new tab\" now works!"
		]
	},
	{
		"version": "2.0.0.540",
		"date": "2021-04-12 21:29:04 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small fix"
		]
	},
	{
		"version": "2.0.0.539",
		"date": "2021-04-11 19:59:24 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"VNag password update"
		]
	},
	{
		"version": "2.0.0.538",
		"date": "2021-04-10 23:20:47 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.537",
		"date": "2021-04-10 20:53:54 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"VNag is now password protected",
			"WebWHOIS uses CR LF as line ending"
		]
	},
	{
		"version": "2.0.0.536",
		"date": "2021-03-29 17:45:11 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.535",
		"date": "2021-03-27 16:00:38 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small improvements for plugin \"system file check\""
		]
	},
	{
		"version": "2.0.0.534",
		"date": "2021-03-26 22:45:10 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.533",
		"date": "2021-03-26 22:44:46 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New plugin: System file check"
		]
	},
	{
		"version": "2.0.0.532",
		"date": "2021-03-23 23:15:07 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"WebSVN update moved from \"update/\" into admin login area",
			"New plugin: \"VNag version check\" (in admin login area)",
			"Discontinued: \"File completeness check\" tool (will be replaced soon)"
		]
	},
	{
		"version": "2.0.0.531",
		"date": "2021-03-18 16:51:09 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.530",
		"date": "2021-03-13 22:51:57 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Updated 3P: jQuery, jsTree, Certs, bignumber.js, mbstring polyfill"
		]
	},
	{
		"version": "2.0.0.529",
		"date": "2021-03-13 21:18:18 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixing warnings of Eclipse for \"Minify\""
		]
	},
	{
		"version": "2.0.0.528",
		"date": "2021-03-13 20:25:05 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Updated 3P"
		]
	},
	{
		"version": "2.0.0.527",
		"date": "2021-03-13 14:05:48 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Class autoloader: Class names are now case insensitive",
			"Class autoloader: OIDplus classes are now first loaded before any plugin classes"
		]
	},
	{
		"version": "2.0.0.526",
		"date": "2021-03-12 22:17:49 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Security improvement: Class autoloader only searches in known plugin type directories"
		]
	},
	{
		"version": "2.0.0.525",
		"date": "2021-03-12 19:21:28 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Security improvement: Plugins are now loaded only from publicPages, raPages and adminPages, not from *Pages"
		]
	},
	{
		"version": "2.0.0.524",
		"date": "2021-03-08 21:28:51 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RFC Published draft-viathinksoft-oidwhois-02"
		]
	},
	{
		"version": "2.0.0.523",
		"date": "2021-03-07 22:56:33 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"FreeOID: RAs which already exists can now obtain a FreeOID (thinkBug #699)",
			"FreeOID: Bugfix: Size check failed for OIDs inside root arc 2. Fixed."
		]
	},
	{
		"version": "2.0.0.522",
		"date": "2021-03-01 22:29:36 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.521",
		"date": "2021-02-28 19:30:19 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Bugfix: Could not add OID with Unicode label"
		]
	},
	{
		"version": "2.0.0.520",
		"date": "2021-02-21 20:57:58 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Link to IETF Internet Draft"
		]
	},
	{
		"version": "2.0.0.519",
		"date": "2021-02-20 19:18:33 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Release of draft-viathinksoft-oidwhois-01.txt at https://datatracker.ietf.org/doc/draft-viathinksoft-oidwhois/"
		]
	},
	{
		"version": "2.0.0.518",
		"date": "2021-02-20 19:02:50 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RFC: Added (empty) IANA Considerations section; quoted OIDs to avoid idnits to detect \"Invalid IPv4 addresses\""
		]
	},
	{
		"version": "2.0.0.517",
		"date": "2021-02-14 22:00:04 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small changes to the RFC draft"
		]
	},
	{
		"version": "2.0.0.516",
		"date": "2021-02-09 13:47:58 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Uploaded internet draft: https://datatracker.ietf.org/doc/draft-viathinksoft-oidwhois/"
		]
	},
	{
		"version": "2.0.0.515",
		"date": "2021-01-23 12:19:09 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New feature: Generate UUID OID (requires that \"2.25\" is created as root OID in OIDplus)"
		]
	},
	{
		"version": "2.0.0.514",
		"date": "2021-01-22 11:51:02 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed backwards incompatibility with attachments plugin"
		]
	},
	{
		"version": "2.0.0.513",
		"date": "2021-01-20 21:25:03 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Attachments plugin: Upload directory names are now more useful"
		]
	},
	{
		"version": "2.0.0.512",
		"date": "2021-01-19 22:55:10 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed race condition bug in jsTree conditionalselect"
		]
	},
	{
		"version": "2.0.0.511",
		"date": "2021-01-18 01:13:07 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Security: Added \"INSIDE_OIDPLUS\" constant to include files to avoid generating error messages when an include file is accessed directly",
			"Updated copyright notices to 2021"
		]
	},
	{
		"version": "2.0.0.510",
		"date": "2021-01-14 23:59:55 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Improved SimpleXML supplement in order to make WebSVN updater compatible.",
			"Fixed: \"System information\" showed wrong operating system on some servers."
		]
	},
	{
		"version": "2.0.0.509",
		"date": "2021-01-14 14:22:14 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed small bug in WebSVN updater"
		]
	},
	{
		"version": "2.0.0.508",
		"date": "2021-01-13 01:21:54 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.507",
		"date": "2021-01-12 14:56:31 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed problem with ODBC database provider",
			"After the creation of an object, the user can now decide if they want to jump to the new object"
		]
	},
	{
		"version": "2.0.0.506",
		"date": "2021-01-11 00:20:22 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.505",
		"date": "2021-01-10 22:51:31 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"WebSVN Updater now internally uses SimpleXML",
			"WebSVN Updater is now translated"
		]
	},
	{
		"version": "2.0.0.504",
		"date": "2021-01-09 00:27:46 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Setup, OOBE and Updater has now an icon",
			"Setup revision log didn't show new-lines on some systems. Fixed."
		]
	},
	{
		"version": "2.0.0.503",
		"date": "2021-01-08 23:05:29 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"BUGFIX: System tried to save attachments to \"userdata/attachmentsXXX/*.*\" instead of \"userdata/attachments/XXX/*.*\"",
			"BUGFIX: Fixed problems with MySQLi database provider if server does not support MySQLnd (Native Driver)",
			"BUGFIX: If config.inc.php is wrong (e.g. outdated version), setup could not be started. Fixed."
		]
	},
	{
		"version": "2.0.0.502",
		"date": "2021-01-03 21:19:54 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Improved ODBC and PDO database plugins in order to support more database drivers.",
			"EXPERIMENTAL: Support for Microsoft Access database",
			"Small bugfixes",
			"Improved database connectivity test cases"
		]
	},
	{
		"version": "2.0.0.501",
		"date": "2021-01-01 21:42:58 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"MsSQL OOBE Fix"
		]
	},
	{
		"version": "2.0.0.500",
		"date": "2020-12-20 14:51:31 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.499",
		"date": "2020-12-19 22:13:18 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Translated German comments to English"
		]
	},
	{
		"version": "2.0.0.498",
		"date": "2020-12-12 20:49:10 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.497",
		"date": "2020-12-12 20:34:43 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.496",
		"date": "2020-12-12 20:29:51 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Large refactoring of path functions.",
			"DEPRECATED: OIDplus::basePath() becomes OIDplus::localpath()",
			"DEPRECATED: OIDplus::getSystemUrl(X) becomes OIDplus::webpath(null, X)",
			"OIDplus::webpath(X) becomes OIDplus::webpath(X, true)"
		]
	},
	{
		"version": "2.0.0.495",
		"date": "2020-12-12 14:02:12 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"getSystemUrl(): CLI support improved"
		]
	},
	{
		"version": "2.0.0.494",
		"date": "2020-12-12 13:05:01 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed a problem where getSystemUrl() did not end with '/' if EXPLICIT_ABSOLUTE_SYSTEM_URL is used (VTS BUG#0000209 ?)"
		]
	},
	{
		"version": "2.0.0.493",
		"date": "2020-12-09 09:20:14 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"\"update/\" page now loads faster, avoids being used as DoS attack vector"
		]
	},
	{
		"version": "2.0.0.492",
		"date": "2020-12-08 16:39:44 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed some problems with WebSVN Updater"
		]
	},
	{
		"version": "2.0.0.491",
		"date": "2020-12-07 21:48:09 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed problems with the WebSVN updater"
		]
	},
	{
		"version": "2.0.0.490",
		"date": "2020-12-07 16:13:54 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"BUGFIX: Admin-plugin \"Designs\": Button \"Test\" did not work on some systems.",
			"BUGFIX: Logo and other resources were not loaded if OIDplus is running on a Windows server system (backslash issues)"
		]
	},
	{
		"version": "2.0.0.489",
		"date": "2020-12-06 21:38:52 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed errors found by PHPStan"
		]
	},
	{
		"version": "2.0.0.488",
		"date": "2020-12-06 13:48:10 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Removed external SVN sources. This increases compatibility with GitHub working copies and simplifies the WebSVN updater.",
			"NOTE: In case you are receiving error messages during the \"svn update\" command, delete 3p/vts_vnag and 3p/vts_fileformats and try again."
		]
	},
	{
		"version": "2.0.0.487",
		"date": "2020-12-06 01:20:58 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added .gitignore"
		]
	},
	{
		"version": "2.0.0.486",
		"date": "2020-12-05 22:48:47 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New distribution channel: GitHub"
		]
	},
	{
		"version": "2.0.0.485",
		"date": "2020-12-04 22:11:27 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New product page www.oidplus.com is online!"
		]
	},
	{
		"version": "2.0.0.484",
		"date": "2020-12-04 14:53:25 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OIDplus can now also communicate with HTTPS servers if CURL is wrongly configured (Windows)"
		]
	},
	{
		"version": "2.0.0.483",
		"date": "2020-12-04 00:21:05 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Extended IIS installation guide: PHP extensions"
		]
	},
	{
		"version": "2.0.0.482",
		"date": "2020-12-02 20:55:41 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New optional LDAP base config setting: LDAP_USER_FILTER"
		]
	},
	{
		"version": "2.0.0.481",
		"date": "2020-12-01 22:57:30 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Cache files are not hidden anymore on Linux systems (filename beginning with dot).",
			"Fixed problem if CSS/JS scripts output a PHP warning in Debug mode"
		]
	},
	{
		"version": "2.0.0.480",
		"date": "2020-11-28 12:34:41 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OIDplus is no longer dependent on SimpleXML (but it is highly recommended to install SimpleXML!)"
		]
	},
	{
		"version": "2.0.0.479",
		"date": "2020-11-26 19:28:07 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.478",
		"date": "2020-11-24 14:44:50 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.477",
		"date": "2020-11-22 19:09:24 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Resource plugin: Security check is now before redirect check"
		]
	},
	{
		"version": "2.0.0.476",
		"date": "2020-11-22 18:55:28 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New feature: Resources can now be restricted to RAs or Admin. The res/ directory now may not be world-readable anymore."
		]
	},
	{
		"version": "2.0.0.475",
		"date": "2020-11-22 13:34:42 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New feature: Resource redirects"
		]
	},
	{
		"version": "2.0.0.474",
		"date": "2020-11-19 20:20:42 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added php-fig-cache to make Eclipse IDE happy"
		]
	},
	{
		"version": "2.0.0.473",
		"date": "2020-11-19 11:16:11 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Design plugins now have a \"css\" key in the manifest.xml, which needs to be set!"
		]
	},
	{
		"version": "2.0.0.472",
		"date": "2020-11-18 20:13:43 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Security: Hide system version"
		]
	},
	{
		"version": "2.0.0.471",
		"date": "2020-11-17 17:11:51 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed several problems with designs in inverted color mode and dark-theme browser plugins"
		]
	},
	{
		"version": "2.0.0.470",
		"date": "2020-11-16 16:45:31 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small things"
		]
	},
	{
		"version": "2.0.0.469",
		"date": "2020-11-15 12:51:37 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.468",
		"date": "2020-11-15 00:57:37 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Translation XML contents are now cached as PHP serialization to improve performance"
		]
	},
	{
		"version": "2.0.0.467",
		"date": "2020-11-14 16:18:58 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OIDplus can now also run without MBString, if iconv is available"
		]
	},
	{
		"version": "2.0.0.466",
		"date": "2020-11-13 21:39:13 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.465",
		"date": "2020-11-13 13:43:58 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OIDplus can now also run without OpenSSL installed"
		]
	},
	{
		"version": "2.0.0.464",
		"date": "2020-11-08 01:15:18 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.463",
		"date": "2020-11-07 14:10:03 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed severe compatibility problems with fresh Linux installations.",
			"Clean setup procedure tested on a fresh Apache+PHP8.0RC3 RaspberryOS (Debian) system"
		]
	},
	{
		"version": "2.0.0.462",
		"date": "2020-11-05 22:42:02 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"TinyMCE 5.4.2 => 5.5.1",
			"Bootstrap 4.5.2 => 4.5.3",
			"Fixed possible preg_replace code injection"
		]
	},
	{
		"version": "2.0.0.461",
		"date": "2020-11-04 22:30:07 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.460",
		"date": "2020-11-04 15:21:09 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.459",
		"date": "2020-11-04 14:32:51 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New class: OIDplusRAAuthInfo"
		]
	},
	{
		"version": "2.0.0.458",
		"date": "2020-10-27 15:54:15 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.457",
		"date": "2020-10-27 15:32:00 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Various smaller security tweaks"
		]
	},
	{
		"version": "2.0.0.456",
		"date": "2020-10-27 01:23:03 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Security: Admin passwords can now be BCrypt instead of SHA3-512",
			"Security: BCrypt is now the default auth method for newly created RAs"
		]
	},
	{
		"version": "2.0.0.455",
		"date": "2020-10-26 17:33:27 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.454",
		"date": "2020-10-26 17:17:01 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.453",
		"date": "2020-10-26 14:21:59 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Security: Auth-Plugins now also generate hashes.",
			"Security: New setting to select default RA hashing algorithm.",
			"Security: New auth plugin A3 \"BCrypt\"."
		]
	},
	{
		"version": "2.0.0.452",
		"date": "2020-10-26 00:07:30 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Animated logo"
		]
	},
	{
		"version": "2.0.0.451",
		"date": "2020-10-25 23:08:27 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Admin page \"Colors\" was renamed into \"Design\".",
			"Design plugin can now be chosen in the \"Design\" page in the admin login area.",
			"There is now a possibility to insert a logo."
		]
	},
	{
		"version": "2.0.0.450",
		"date": "2020-10-25 20:29:31 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Bugfixes"
		]
	},
	{
		"version": "2.0.0.449",
		"date": "2020-10-25 19:17:14 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"DESIGN Plugin interface is now final (plugin type 1.3.6.1.4.1.37476.2.5.2.4.10)"
		]
	},
	{
		"version": "2.0.0.448",
		"date": "2020-10-25 18:39:08 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"NEW FEATURE: Design plugins (plugin format is not yet final, since there is no manifest XML!)",
			"Added new design \"IronBASE\""
		]
	},
	{
		"version": "2.0.0.447",
		"date": "2020-10-22 00:53:49 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.446",
		"date": "2020-10-21 23:09:13 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"SECURITY patch: System registration \"Live status\" is now protected from public view.",
			"System registration \"Live status\" is now translated to German."
		]
	},
	{
		"version": "2.0.0.445",
		"date": "2020-10-21 16:59:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.444",
		"date": "2020-10-21 12:29:27 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Increased performance!"
		]
	},
	{
		"version": "2.0.0.443",
		"date": "2020-10-21 01:23:38 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed compatibility issue with PgSQL and SQLite3"
		]
	},
	{
		"version": "2.0.0.442",
		"date": "2020-10-20 23:43:26 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.441",
		"date": "2020-10-20 15:52:39 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Converted some fields from NOT NULL to NULL (DB Version is now 205)"
		]
	},
	{
		"version": "2.0.0.440",
		"date": "2020-10-18 21:51:48 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.439",
		"date": "2020-10-18 13:32:36 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"UUID Utils update (can now create time based UUIDs on Windows, too)"
		]
	},
	{
		"version": "2.0.0.438",
		"date": "2020-10-18 11:07:08 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Additional plugin verification steps to avoid implementation mistakes"
		]
	},
	{
		"version": "2.0.0.437",
		"date": "2020-10-17 22:05:23 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.436",
		"date": "2020-10-17 19:48:02 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"NEW FEATURE: Facebook authentication"
		]
	},
	{
		"version": "2.0.0.435",
		"date": "2020-10-17 19:47:26 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Resolve endless recursion when an IP address changed during an active session"
		]
	},
	{
		"version": "2.0.0.434",
		"date": "2020-10-17 12:49:29 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added privacy documentation for OAuth2 and LDAP login methods"
		]
	},
	{
		"version": "2.0.0.433",
		"date": "2020-10-16 15:33:37 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed problems in regards changing email addresses when an user is using an alternative login method (OAuth).",
			"Fixed problem where an object could not be transferred to a new RA at Microsoft SQL Server or PostgreSQL (\"ifnull\" SQL function)."
		]
	},
	{
		"version": "2.0.0.432",
		"date": "2020-10-15 22:13:23 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"NEW FEATURE: Google OAuth2 authentication"
		]
	},
	{
		"version": "2.0.0.431",
		"date": "2020-10-15 14:45:02 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Smaller fixes"
		]
	},
	{
		"version": "2.0.0.430",
		"date": "2020-10-14 23:52:02 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"NEW FEATURE: Authentication via LDAP / ActiveDirectory"
		]
	},
	{
		"version": "2.0.0.429",
		"date": "2020-10-14 00:32:11 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"When a JS file is missing in a plugin manifest, an error will be printed to the JavaScript console"
		]
	},
	{
		"version": "2.0.0.428",
		"date": "2020-10-12 10:54:11 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Failsafe: When an \"onsubmit\" function fails, the page is not reloaded"
		]
	},
	{
		"version": "2.0.0.427",
		"date": "2020-10-03 19:08:44 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.426",
		"date": "2020-10-03 18:19:34 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.425",
		"date": "2020-10-02 23:29:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Automated AJAX calls: Added VBScript (WSH) example"
		]
	},
	{
		"version": "2.0.0.424",
		"date": "2020-10-02 22:22:14 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New security feature: CSRF Tokens.",
			"ATTENTION TO PLUGIN DEVELOPERS: You need to add \"csrf_token:csrf_token\" to your JavaScript's AJAX request fields!"
		]
	},
	{
		"version": "2.0.0.423",
		"date": "2020-10-02 13:25:27 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.422",
		"date": "2020-10-02 13:21:31 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"BUGFIX: Creation of a new object did not cause a reload of the page"
		]
	},
	{
		"version": "2.0.0.421",
		"date": "2020-09-30 11:30:14 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Newly generated administrator passwords are now salted (equally to the \"A2\" auth plugin), to avoid that equal passwords generate equal password strings in the configuration file"
		]
	},
	{
		"version": "2.0.0.420",
		"date": "2020-09-30 00:06:57 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Upgraded 3P Bootstrap 3.4.1 => 4.5.2"
		]
	},
	{
		"version": "2.0.0.419",
		"date": "2020-09-29 14:52:43 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Updated 3P PHP SHA3 lib 2017-05-21 => 2017-11-22",
			"Updated 3P bignumber.js: 2019-11-10 => 2020-09-29",
			"Updated 3P Bootstrap: 3.3.7 => 3.4.1",
			"Updated 3P Minify 2019-11-24 => 2020-01-21"
		]
	},
	{
		"version": "2.0.0.418",
		"date": "2020-09-29 14:37:23 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.417",
		"date": "2020-09-29 14:06:16 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Updated 3P jsTree: 3.3.7 => 3.3.10",
			"Updated 3P jQuery Core: 2.2.1 => 3.5.1",
			"Updated 3P allpro layout 1.4.3 => GedMarc layout fork, 2020-08-22"
		]
	},
	{
		"version": "2.0.0.416",
		"date": "2020-09-27 21:41:41 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-WHOIS: Additional Auth Tokens per OID and/or per RA can be used to display confidential information"
		]
	},
	{
		"version": "2.0.0.415",
		"date": "2020-09-25 19:22:52 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"When OOBE is called inside Admin login area, you don't need to enter the admin password anymore"
		]
	},
	{
		"version": "2.0.0.414",
		"date": "2020-09-24 21:01:15 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.413",
		"date": "2020-09-24 17:20:46 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.412",
		"date": "2020-09-24 14:12:04 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Software updates of OIDplus are now logged"
		]
	},
	{
		"version": "2.0.0.411",
		"date": "2020-09-24 13:08:21 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Database plugins manifests now contain references to setup JavaScripts"
		]
	},
	{
		"version": "2.0.0.410",
		"date": "2020-09-24 12:27:38 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Renamed setup.js, setup.css, script.js and style.css. They now have the plugin name as filename."
		]
	},
	{
		"version": "2.0.0.409",
		"date": "2020-09-24 11:42:15 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Data sent to the ViaThinkSoft server is now compressed"
		]
	},
	{
		"version": "2.0.0.408",
		"date": "2020-09-23 21:14:13 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Admin plugin \"List RAs\": Added link to manually create RAs, and back-links.",
			"Admin plugin \"Installed plugins\": Added back-links."
		]
	},
	{
		"version": "2.0.0.407",
		"date": "2020-09-23 16:03:26 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.406",
		"date": "2020-09-23 09:32:57 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.405",
		"date": "2020-09-22 16:49:03 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"COMPATIBILITY: Possible firefox bug: Browser History is now shown again (e.g. when right-clicking the back-button)",
			"BUGFIX: Browser history showed the current node instead of the previous node. Corrected."
		]
	},
	{
		"version": "2.0.0.404",
		"date": "2020-09-22 15:58:49 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"REVERT: Transparent TinyMCE (has problems with inverted color theme, as text color is black on black background)",
			"Smaller fixes of TinyMCE code"
		]
	},
	{
		"version": "2.0.0.403",
		"date": "2020-09-21 22:12:05 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed menu entries without title for NonJS-browsers/search engines"
		]
	},
	{
		"version": "2.0.0.402",
		"date": "2020-09-21 21:57:20 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"TinyMCE is now transparent",
			"BUGFIX: TinyMCE works now also on browsers which do not support \"document.currentScript\""
		]
	},
	{
		"version": "2.0.0.401",
		"date": "2020-09-21 09:32:08 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"It is now possible to have multiple translation message files, e.g. \"plugins/language/dede/messagesHelloWorldPlugin.xml\" if you want to have a translation for your own plugins."
		]
	},
	{
		"version": "2.0.0.400",
		"date": "2020-09-20 17:11:53 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"BUGFIX: RAs cannot login and RAs cannot be created (BUG#0000208/1)",
			"BUGFIX: Infinite loop in e-mail-sending (BUG#0000208/2)"
		]
	},
	{
		"version": "2.0.0.399",
		"date": "2020-09-20 00:03:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"When the user tries to change to another page without saving the description of an OID, the page will send a warning"
		]
	},
	{
		"version": "2.0.0.398",
		"date": "2020-09-18 22:38:58 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.397",
		"date": "2020-09-17 21:25:04 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Updates to TinyMCE 5.4.2 , \"Style\" dropdown box is now working"
		]
	},
	{
		"version": "2.0.0.396",
		"date": "2020-09-17 16:04:38 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.395",
		"date": "2020-09-17 11:51:46 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"It is now possible to add a well-known OID (e.g. 2.999 or 1.3.6.1.4.1) to your system."
		]
	},
	{
		"version": "2.0.0.394",
		"date": "2020-09-16 23:38:10 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Registration/OIDinfo-Interface: Special case where the IANA PEN or UUID OID root is used as system root (but the legal root is inside it) is now supported."
		]
	},
	{
		"version": "2.0.0.393",
		"date": "2020-09-15 23:50:52 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"* TinyMCE Editor is now also translated",
			"* A warning is shown when you try to enter an ASN.1 identifier which is already existing at the same arc",
			"* A warning is shown when you try to create an OID without ASN.1 identifier"
		]
	},
	{
		"version": "2.0.0.392",
		"date": "2020-09-15 19:59:14 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Removed md5() and sha1() from security-relevant areas [although the security impact was VERY small]"
		]
	},
	{
		"version": "2.0.0.391",
		"date": "2020-09-14 17:23:30 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.390",
		"date": "2020-09-14 17:06:23 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.389",
		"date": "2020-09-14 17:06:17 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"XML Schema for plugin manifests"
		]
	},
	{
		"version": "2.0.0.388",
		"date": "2020-09-12 23:56:10 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.387",
		"date": "2020-09-12 22:37:23 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.386",
		"date": "2020-09-12 22:00:01 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed various smaller things detected by warnings/errors of \"Eclipse for PHP\""
		]
	},
	{
		"version": "2.0.0.385",
		"date": "2020-09-11 14:07:18 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"* Web-WHOIS opens in new window",
			"* Web-WHOIS: Showing of URL without opening it, and be able to copy to clipboard",
			"* Added German license translation disclaimer"
		]
	},
	{
		"version": "2.0.0.384",
		"date": "2020-09-03 15:25:10 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"BUGFIX: Object root page showed file attachments \"info.txt\" and \"index.html\". Fixed."
		]
	},
	{
		"version": "2.0.0.383",
		"date": "2020-09-01 15:04:49 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Apache 2.0 Lizenz deutsche \u00dcbersetzung"
		]
	},
	{
		"version": "2.0.0.382",
		"date": "2020-08-31 21:12:54 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Minor changes"
		]
	},
	{
		"version": "2.0.0.381",
		"date": "2020-08-31 16:45:05 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Status codes: Negative = Error, Zero = Normal OK, Positive = OK, but with additional information"
		]
	},
	{
		"version": "2.0.0.380",
		"date": "2020-08-30 15:08:02 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Plugin-related code improvements"
		]
	},
	{
		"version": "2.0.0.379",
		"date": "2020-08-30 00:58:28 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Improved compatibility with iPhone Safari"
		]
	},
	{
		"version": "2.0.0.378",
		"date": "2020-08-29 11:19:36 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.377",
		"date": "2020-08-29 11:14:27 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"BUGFIX: Language flags are broken if OIDplus is located in the domain's root directory"
		]
	},
	{
		"version": "2.0.0.376",
		"date": "2020-08-29 11:05:10 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Introduced cache folder, and cached polyfill replies"
		]
	},
	{
		"version": "2.0.0.375",
		"date": "2020-08-29 01:07:14 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Privacy improvement: Polyfill.io is called server-side (not from proxy). Improved compatibility with Internet Explorer."
		]
	},
	{
		"version": "2.0.0.374",
		"date": "2020-08-28 00:22:53 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"test_database_plugins development tool is now only available on console/CLI"
		]
	},
	{
		"version": "2.0.0.373",
		"date": "2020-08-26 16:18:18 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.372",
		"date": "2020-08-25 16:04:05 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.371",
		"date": "2020-08-25 15:03:01 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"German translation fixes"
		]
	},
	{
		"version": "2.0.0.370",
		"date": "2020-08-25 15:02:34 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Communication between OIDplus and ViaThinkSoft server is now fully AJAX/JSON"
		]
	},
	{
		"version": "2.0.0.369",
		"date": "2020-08-24 17:18:42 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Some German translation fixes"
		]
	},
	{
		"version": "2.0.0.368",
		"date": "2020-08-24 10:31:24 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Smaller fixed.",
			"DROPPED support for setting \"resource_plugin_title\" (due to multilinguality)"
		]
	},
	{
		"version": "2.0.0.367",
		"date": "2020-08-24 00:09:05 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added CONTRIBUTING file"
		]
	},
	{
		"version": "2.0.0.366",
		"date": "2020-08-23 23:54:11 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Various smaller things"
		]
	},
	{
		"version": "2.0.0.365",
		"date": "2020-08-23 19:44:30 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Introduced directories \"userdata_pub\" and \"userdata/private\""
		]
	},
	{
		"version": "2.0.0.364",
		"date": "2020-08-23 18:59:04 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"DROPPED support for \"welcome.local.html\". Use \"userdata/welcome/welcome.html\" (English) and \"userdata/welcome/welcome$dede.html\" (German) instead!",
			"DROPPED support for \"oidplus_base.local.css\". Use \"userdata/styles/oidplus_base.css\" instead!"
		]
	},
	{
		"version": "2.0.0.363",
		"date": "2020-08-23 16:59:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.362",
		"date": "2020-08-23 16:49:43 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Setup and OOBE is now translated to German, too.",
			"Freshly installed plugins can request that the OOBE is shown in order to ask the user to check the settings of these plugins.",
			"",
			"ATTENTION: Existing users will see the Setup/OOBE screen after Update. Just enter your administrator password and continue."
		]
	},
	{
		"version": "2.0.0.361",
		"date": "2020-08-23 00:57:17 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"UTF-8 Fix"
		]
	},
	{
		"version": "2.0.0.360",
		"date": "2020-08-23 00:28:31 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"LARGE UPDATE: Made everything multilingual; Translation to German!"
		]
	},
	{
		"version": "2.0.0.359",
		"date": "2020-08-19 23:25:54 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.357",
		"date": "2020-08-16 01:30:39 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.356",
		"date": "2020-08-11 21:41:11 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Made language flags more pretty. Increased compatibility with Internet Explorer."
		]
	},
	{
		"version": "2.0.0.355",
		"date": "2020-08-10 14:34:03 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added framework for multilinguality (PHP/JS). Currently, nothing is translated, though."
		]
	},
	{
		"version": "2.0.0.354",
		"date": "2020-08-08 20:00:12 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"FreeOID ToS proofed by grammarly.com"
		]
	},
	{
		"version": "2.0.0.353",
		"date": "2020-08-08 19:34:56 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"IIS Installation Routine updated"
		]
	},
	{
		"version": "2.0.0.352",
		"date": "2020-08-02 21:05:48 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OOBE Bugfix: Redirection didn't work if the port was not 80/443"
		]
	},
	{
		"version": "2.0.0.351",
		"date": "2020-08-02 19:59:44 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small changes"
		]
	},
	{
		"version": "2.0.0.350",
		"date": "2020-07-31 16:54:27 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.349",
		"date": "2020-07-31 11:14:23 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed OOBE!"
		]
	},
	{
		"version": "2.0.0.348",
		"date": "2020-07-30 21:10:06 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.347",
		"date": "2020-07-30 15:35:15 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OIDinfo plugin import/export fixed"
		]
	},
	{
		"version": "2.0.0.346",
		"date": "2020-07-30 14:50:27 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New plugin: System info"
		]
	},
	{
		"version": "2.0.0.345",
		"date": "2020-07-29 16:49:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RFC"
		]
	},
	{
		"version": "2.0.0.344",
		"date": "2020-07-28 11:27:56 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"privacy_documentation.html proofed by grammarly.com"
		]
	},
	{
		"version": "2.0.0.343",
		"date": "2020-07-27 19:30:29 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RFC"
		]
	},
	{
		"version": "2.0.0.342",
		"date": "2020-07-14 21:30:11 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RFC"
		]
	},
	{
		"version": "2.0.0.341",
		"date": "2020-07-06 21:52:16 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RFC proofed by grammarly.com"
		]
	},
	{
		"version": "2.0.0.340",
		"date": "2020-07-05 21:25:04 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RFC"
		]
	},
	{
		"version": "2.0.0.339",
		"date": "2020-07-05 13:57:13 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-WHOIS updated JSON and XML schemas"
		]
	},
	{
		"version": "2.0.0.338",
		"date": "2020-06-29 12:05:17 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RFC"
		]
	},
	{
		"version": "2.0.0.337",
		"date": "2020-06-22 16:10:39 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RFC"
		]
	},
	{
		"version": "2.0.0.336",
		"date": "2020-06-18 13:02:44 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RFC Large changes"
		]
	},
	{
		"version": "2.0.0.335",
		"date": "2020-06-17 00:18:56 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RFC: Removed one section in IANA considerations; removed IPv6 alternative namespace example"
		]
	},
	{
		"version": "2.0.0.334",
		"date": "2020-06-15 23:22:25 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RFC: Small addition to Digital Signature chapter"
		]
	},
	{
		"version": "2.0.0.333",
		"date": "2020-06-15 17:27:07 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RFC: Smaller changes; Referencing style updated to \"www.rfc-editor.org/ref-example/\", re-structured chapters"
		]
	},
	{
		"version": "2.0.0.332",
		"date": "2020-06-14 22:55:56 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Updated RFC draft"
		]
	},
	{
		"version": "2.0.0.331",
		"date": "2020-06-12 21:17:52 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Various smaller fixes"
		]
	},
	{
		"version": "2.0.0.330",
		"date": "2020-06-12 00:15:47 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"NEW: XML (XSD) and JSON schema for OID-over-WHOIS"
		]
	},
	{
		"version": "2.0.0.329",
		"date": "2020-06-11 23:05:09 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"NEW: \"Automated AJAX calls\" plugin to execute privileged AJAX requests programmatically"
		]
	},
	{
		"version": "2.0.0.328",
		"date": "2020-06-11 20:35:28 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Plugin API: action() method returns data as array instead of printing the JSON by itself"
		]
	},
	{
		"version": "2.0.0.327",
		"date": "2020-06-11 01:09:37 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"NEW: Implemented XML import tool (admin interface). Added plugin to import/export OIDs from/to oid-info.com"
		]
	},
	{
		"version": "2.0.0.326",
		"date": "2020-06-07 02:11:39 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.325",
		"date": "2020-06-04 22:25:28 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Various bugfixes"
		]
	},
	{
		"version": "2.0.0.324",
		"date": "2020-06-04 20:58:34 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Bugfix: Attachment URLs inside WHOIS CLI responses are now absolute URLs (requires that the page was accessed via web browser once)"
		]
	},
	{
		"version": "2.0.0.323",
		"date": "2020-05-23 22:39:18 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Resource plugin: URL format simplified (now human friendly because there is no authentication key included in the goto-URL anymore)"
		]
	},
	{
		"version": "2.0.0.322",
		"date": "2020-05-22 23:56:47 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-over-WhoIs (TXT/JSON) now also shows attachments (added feature 1.3.6.1.4.1.37476.2.5.2.3.4)"
		]
	},
	{
		"version": "2.0.0.321",
		"date": "2020-05-22 21:23:11 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Prepared AJAX actions for automated tests; removed actionBefore() and actionAfter() and introduced feature 1.3.6.1.4.1.37476.2.5.2.3.3 as replacement"
		]
	},
	{
		"version": "2.0.0.320",
		"date": "2020-05-22 19:52:25 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"AJAX actions now use plugin OIDs as their namespace"
		]
	},
	{
		"version": "2.0.0.319",
		"date": "2020-05-22 14:06:59 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Corrected syntax check of ASN.1 and IRI identifiers"
		]
	},
	{
		"version": "2.0.0.318",
		"date": "2020-05-21 21:15:12 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.317",
		"date": "2020-05-21 19:34:52 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"When OID is deleted, all attachments will be deleted, too.",
			"If last attachment was deleted, empty folder will be deleted."
		]
	},
	{
		"version": "2.0.0.316",
		"date": "2020-05-21 18:47:12 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Various smaller bugfixes and compatibility issues with database providers fixed"
		]
	},
	{
		"version": "2.0.0.315",
		"date": "2020-05-18 22:44:40 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Bugfixes"
		]
	},
	{
		"version": "2.0.0.314",
		"date": "2020-05-18 21:32:18 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.313",
		"date": "2020-05-18 21:06:08 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Bugfixes"
		]
	},
	{
		"version": "2.0.0.312",
		"date": "2020-05-18 20:24:55 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.311",
		"date": "2020-05-18 20:19:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.310",
		"date": "2020-05-18 16:37:59 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New plugin: File attachments"
		]
	},
	{
		"version": "2.0.0.309",
		"date": "2020-05-16 11:24:36 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.308",
		"date": "2020-05-15 00:22:05 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Plugin manifests are now XML instead of INI files; plugins can now have an optional OID"
		]
	},
	{
		"version": "2.0.0.307",
		"date": "2020-05-14 22:08:02 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Plugin manifests are now capsulated in objects"
		]
	},
	{
		"version": "2.0.0.306",
		"date": "2020-05-14 11:32:07 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New: File Completeness Check tool"
		]
	},
	{
		"version": "2.0.0.305",
		"date": "2020-05-13 22:21:33 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Smaller changes"
		]
	},
	{
		"version": "2.0.0.304",
		"date": "2020-05-13 17:24:39 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"modifyContent() is now a loose interface"
		]
	},
	{
		"version": "2.0.0.303",
		"date": "2020-05-12 23:55:15 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Smaller fixes"
		]
	},
	{
		"version": "2.0.0.302",
		"date": "2020-05-12 15:35:25 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"There was a problem with WebSVN not updating directory contents when a directory was renamed. Fixed."
		]
	},
	{
		"version": "2.0.0.301",
		"date": "2020-05-12 09:30:19 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.300",
		"date": "2020-05-12 00:30:23 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.299",
		"date": "2020-05-12 00:19:57 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Setup will now check if directories dev/, userdata/ etc. are restricted by the web server and output a warning if they are not"
		]
	},
	{
		"version": "2.0.0.298",
		"date": "2020-05-11 23:09:41 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"fail2ban Integration"
		]
	},
	{
		"version": "2.0.0.297",
		"date": "2020-05-11 22:00:07 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added base config setting \"DISABLE_PLUGIN_...\" to disable a plugin without needing to remove it from the file system."
		]
	},
	{
		"version": "2.0.0.296",
		"date": "2020-05-11 21:34:42 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.295",
		"date": "2020-05-10 23:18:06 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"- Introduced isolated database connection for secure logging inside transactions.",
			"- Added new plugin \"userdata log file\" logger",
			"- Important bugfix for syslog logger"
		]
	},
	{
		"version": "2.0.0.294",
		"date": "2020-05-10 11:29:27 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New folder \"userdata\" which now contains all data specific to this OIDplus installation (configuration, resources, databases, log files etc.)"
		]
	},
	{
		"version": "2.0.0.293",
		"date": "2020-05-07 22:11:58 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.292",
		"date": "2020-05-06 16:15:49 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Split plugin \"Registration\" into \"OOBE\" and \"Registration\". OOBE is now a core part of OIDplus while the Registration is not. Added color theme to OOBE. Added \"feature OID\" functionality."
		]
	},
	{
		"version": "2.0.0.291",
		"date": "2020-05-05 10:31:20 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small bugfixes"
		]
	},
	{
		"version": "2.0.0.290",
		"date": "2020-05-04 23:03:45 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added a \"secure\" folder for various purposes (e.g. SQlite3 database file)"
		]
	},
	{
		"version": "2.0.0.289",
		"date": "2020-05-03 21:33:03 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added new plugin type \"Logger\". Added Windows Log Event logging (only available on Windows) and syslog logging (only available on Linux)."
		]
	},
	{
		"version": "2.0.0.288",
		"date": "2020-05-01 23:48:54 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"NEW: Log messages can now have a severity (Success, Informational, Warning, Error, Critical). Database version is now 204."
		]
	},
	{
		"version": "2.0.0.287",
		"date": "2020-04-27 12:16:16 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed design incompatibility with Firefox; oidplus_base.local.css can be used to create an individual CSS that won't be overriden by software updates."
		]
	},
	{
		"version": "2.0.0.286",
		"date": "2020-04-26 12:39:58 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Color plugins: Colors can now be inverted, so you can create your own dark theme!"
		]
	},
	{
		"version": "2.0.0.285",
		"date": "2020-04-26 00:48:21 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"SVN revision can now be also queried via PDO"
		]
	},
	{
		"version": "2.0.0.284",
		"date": "2020-04-25 14:31:49 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.283",
		"date": "2020-04-25 14:20:17 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.282",
		"date": "2020-04-25 14:12:46 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added sitemap plugin API"
		]
	},
	{
		"version": "2.0.0.281",
		"date": "2020-04-25 13:49:37 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Plugin architecture: Removed explicit type() of page plugins"
		]
	},
	{
		"version": "2.0.0.280",
		"date": "2020-04-25 11:30:52 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.279",
		"date": "2020-04-25 11:02:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Slighly altered plugin architecture again: Manifest now only contains the plugin main class (the other classes are loaded using autoloading) and the page priority attribute was removed."
		]
	},
	{
		"version": "2.0.0.278",
		"date": "2020-04-25 02:35:17 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.277",
		"date": "2020-04-25 02:27:11 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Introducing new plugin architecture (manifest.ini)"
		]
	},
	{
		"version": "2.0.0.276",
		"date": "2020-04-24 01:37:24 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.275",
		"date": "2020-04-23 17:46:50 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"NGINX configuration file"
		]
	},
	{
		"version": "2.0.0.274",
		"date": "2020-04-23 00:56:16 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Add new plugin type \"SQL slang\". The database connection is now an union of \"Database provider plugin\" (e.g. PDO) and \"SQL slang plugin\" (MySQL)."
		]
	},
	{
		"version": "2.0.0.273",
		"date": "2020-04-22 22:38:40 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed problems with OOBE database import"
		]
	},
	{
		"version": "2.0.0.272",
		"date": "2020-04-22 17:03:39 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.271",
		"date": "2020-04-22 00:32:14 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Support for Microsoft Internet Information Services (IIS)"
		]
	},
	{
		"version": "2.0.0.270",
		"date": "2020-04-21 23:36:39 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.269",
		"date": "2020-04-21 21:39:23 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Backwards compatible with PHP 7.0"
		]
	},
	{
		"version": "2.0.0.268",
		"date": "2020-04-21 00:24:17 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.267",
		"date": "2020-04-20 22:30:10 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OIDInfo: Ignore addresses without country and town"
		]
	},
	{
		"version": "2.0.0.266",
		"date": "2020-04-20 21:42:49 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"More database plugin testcases; SQLite3 now supports 128 bit natural sorting"
		]
	},
	{
		"version": "2.0.0.265",
		"date": "2020-04-20 00:30:04 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.264",
		"date": "2020-04-19 20:07:10 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"NEW: Support for SQLite3 database (currently without natural sorting though)"
		]
	},
	{
		"version": "2.0.0.263",
		"date": "2020-04-19 14:19:13 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Refactoring: Moved business logic out of the OIDplusConfig class. Validation functionalities of config keys are now implemented as callback to the prepareConfigKey function"
		]
	},
	{
		"version": "2.0.0.262",
		"date": "2020-04-18 16:45:55 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.261",
		"date": "2020-04-18 16:38:21 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Large refactoring: \"config.inc.php\" is now in format 2.1; the configuration settings are now stored in a class OIDplusBaseConfis and can therefore be altered in automated test environments.",
			"Characters \"###\" inside a query now get replaced by the table prefix."
		]
	},
	{
		"version": "2.0.0.260",
		"date": "2020-04-18 10:32:38 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.259",
		"date": "2020-04-17 12:18:26 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.258",
		"date": "2020-04-17 00:16:45 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.257",
		"date": "2020-04-16 23:35:13 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"NEW: NATIVE POSTGRESQL SUPPORT"
		]
	},
	{
		"version": "2.0.0.256",
		"date": "2020-04-16 01:09:31 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Further improved and extended object oriented classes and plugin structure"
		]
	},
	{
		"version": "2.0.0.255",
		"date": "2020-04-15 01:58:32 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.254",
		"date": "2020-04-15 01:43:40 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.253",
		"date": "2020-04-15 01:39:36 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.252",
		"date": "2020-04-15 01:18:07 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"ajax.php now checks if the IDs are existing at all"
		]
	},
	{
		"version": "2.0.0.251",
		"date": "2020-04-15 01:07:37 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed various problems when query results are empty"
		]
	},
	{
		"version": "2.0.0.250",
		"date": "2020-04-14 22:46:54 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Further improved object oriented design; added class diagram and database connectivity diagram for easier understanding"
		]
	},
	{
		"version": "2.0.0.249",
		"date": "2020-04-12 15:39:20 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"mssql scripts now executable"
		]
	},
	{
		"version": "2.0.0.248",
		"date": "2020-04-12 15:39:02 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Update dependency \"Minify\" from version 17 Dec 2018 to version 24 Nov 2019"
		]
	},
	{
		"version": "2.0.0.247",
		"date": "2020-04-12 15:31:43 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"To ensure that sorting will succeed, the max length/depth/etc. will now be verified (values of limits.inc.php can be changed in config.inc.php)"
		]
	},
	{
		"version": "2.0.0.246",
		"date": "2020-04-12 13:54:01 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Classes: \"DataBase\" is now written \"Database\""
		]
	},
	{
		"version": "2.0.0.245",
		"date": "2020-04-12 13:42:48 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Improved object oriented database classes"
		]
	},
	{
		"version": "2.0.0.244",
		"date": "2020-04-11 02:20:55 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"MSSQL Natural sort order is now UUID (128 bit arc) compatible"
		]
	},
	{
		"version": "2.0.0.243",
		"date": "2020-04-11 00:28:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Followed PHP's recommendation not to put \";\" at the end of a query"
		]
	},
	{
		"version": "2.0.0.242",
		"date": "2020-04-11 00:24:27 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Implemented natural search order in MS SQL (Database version is now 203)"
		]
	},
	{
		"version": "2.0.0.241",
		"date": "2020-04-10 14:34:15 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Code optimization: Introduced function OIDplus::webpath()"
		]
	},
	{
		"version": "2.0.0.240",
		"date": "2020-04-10 13:55:19 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Code optimization: Introduced new class OIDplusConfigInitializationException for more flexibility"
		]
	},
	{
		"version": "2.0.0.239",
		"date": "2020-04-10 12:30:53 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"NEW: SUPPORT FOR POSTGRESQL AND MICROSOFT SQL SERVER"
		]
	},
	{
		"version": "2.0.0.238",
		"date": "2020-04-07 22:24:15 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"BUGFIX: HTML editor TinyMCE works again (broke in SVN Rev 215 @ 15 March 2020)."
		]
	},
	{
		"version": "2.0.0.237",
		"date": "2020-04-07 15:52:55 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Various code and OOP optimizations.",
			"Fixed some bugs in OOBE (Out-Of-Box-Experience).",
			"ODBC bugfix."
		]
	},
	{
		"version": "2.0.0.236",
		"date": "2020-04-07 01:02:59 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Large refactoring at the database classes. PHP 7.0 is now required."
		]
	},
	{
		"version": "2.0.0.231",
		"date": "2020-03-23 01:36:50 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OOP fix"
		]
	},
	{
		"version": "2.0.0.230",
		"date": "2020-03-23 01:35:25 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OOP"
		]
	},
	{
		"version": "2.0.0.229",
		"date": "2020-03-21 00:12:27 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Autoloading"
		]
	},
	{
		"version": "2.0.0.228",
		"date": "2020-03-21 00:07:01 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Support for ports other than 80 and 443; OOP improvements"
		]
	},
	{
		"version": "2.0.0.227",
		"date": "2020-03-20 22:27:50 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Various fixes and OOP changes"
		]
	},
	{
		"version": "2.0.0.225",
		"date": "2020-03-19 22:02:33 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Bugfix: System URL could not determined"
		]
	},
	{
		"version": "2.0.0.224",
		"date": "2020-03-19 20:32:14 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OIDplus now automatically registeres the plugins. The plugins do not need to register themselves through the singleton."
		]
	},
	{
		"version": "2.0.0.223",
		"date": "2020-03-19 20:01:23 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Removed all instances of SQL backticks because of compatibility with other DBMS"
		]
	},
	{
		"version": "2.0.0.222",
		"date": "2020-03-19 15:13:37 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Plugins now contain following information: name, version, author, description; visible in admin area"
		]
	},
	{
		"version": "2.0.0.221",
		"date": "2020-03-18 17:40:03 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New plugin type \"RA authentication plugin\""
		]
	},
	{
		"version": "2.0.0.220",
		"date": "2020-03-18 15:23:28 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Generic password auth types \"A1\" for easier migration from other systems"
		]
	},
	{
		"version": "2.0.0.219",
		"date": "2020-03-15 21:54:37 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.218",
		"date": "2020-03-15 15:37:55 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.217",
		"date": "2020-03-15 01:14:55 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Privacy documentation is now included in the OIDplus installation itself"
		]
	},
	{
		"version": "2.0.0.216",
		"date": "2020-03-15 00:47:53 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed problem with Web SVN client not removing \"oidplus.js\""
		]
	},
	{
		"version": "2.0.0.215",
		"date": "2020-03-15 00:02:33 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Made amount of CSS/JS files loaded through index.php smaller"
		]
	},
	{
		"version": "2.0.0.214",
		"date": "2020-03-14 23:23:30 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Privacy: polyfill.io JavaScript is only loaded if web browser is detected as Internet Explorer"
		]
	},
	{
		"version": "2.0.0.213",
		"date": "2020-03-06 23:14:57 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed security vulnerability!"
		]
	},
	{
		"version": "2.0.0.212",
		"date": "2020-03-01 01:18:26 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.211",
		"date": "2020-02-29 11:27:50 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Misc"
		]
	},
	{
		"version": "2.0.0.210",
		"date": "2020-02-29 11:04:58 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OIDplus can now also work if the GMP extension is not installed, but BCMath is installed"
		]
	},
	{
		"version": "2.0.0.209",
		"date": "2020-02-28 16:05:49 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Logging"
		]
	},
	{
		"version": "2.0.0.208",
		"date": "2020-02-28 15:44:19 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OIDplus is now compatible with hosts that do not support MySQLnd (Native Driver)"
		]
	},
	{
		"version": "2.0.0.207",
		"date": "2020-02-27 17:01:27 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Dependency-Check"
		]
	},
	{
		"version": "2.0.0.206",
		"date": "2020-01-23 23:44:12 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Registration service now handles system ID hash conflicts"
		]
	},
	{
		"version": "2.0.0.205",
		"date": "2020-01-08 20:21:23 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Wellknown generators"
		]
	},
	{
		"version": "2.0.0.204",
		"date": "2019-12-26 12:25:28 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added new field \"comment\", so that the superior RA can comment on the name of an OID they allocate. Database version is now 201."
		]
	},
	{
		"version": "2.0.0.203",
		"date": "2019-12-12 00:56:06 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.202",
		"date": "2019-12-11 20:37:05 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"WebWHOIS: Example ID is the first root of the system (OID preferred)"
		]
	},
	{
		"version": "2.0.0.201",
		"date": "2019-12-10 14:36:07 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New feature: WebWHOIS in JSON and XML format",
			"Fix: Alphanumeric identifiers don't need to be unique anymore (except for standardized identifiers)"
		]
	},
	{
		"version": "2.0.0.200",
		"date": "2019-11-26 22:30:14 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Bugfix for WebSVN updater (directories were created in wrong hierarchical order). IMPORTANT: If updating using the WebSVN updater failed, please create the listed directories manually."
		]
	},
	{
		"version": "2.0.0.199",
		"date": "2019-11-25 00:31:31 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Improved WEID user experience (adding OIDs)"
		]
	},
	{
		"version": "2.0.0.198",
		"date": "2019-11-21 01:05:37 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OID-Info Export fix"
		]
	},
	{
		"version": "2.0.0.197",
		"date": "2019-11-18 00:51:53 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"WEID bugfix"
		]
	},
	{
		"version": "2.0.0.196",
		"date": "2019-11-08 14:27:28 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"WEID update and other small fixes"
		]
	},
	{
		"version": "2.0.0.195",
		"date": "2019-11-07 00:27:31 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.194",
		"date": "2019-11-06 20:48:18 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Bugfix at OID-Info export"
		]
	},
	{
		"version": "2.0.0.193",
		"date": "2019-11-03 23:26:43 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New feature: Alternative Identifiers",
			"Fixed bug where the \"Jump to RA\" list was wrong",
			"Fixed bug in UUID interpretation"
		]
	},
	{
		"version": "2.0.0.192",
		"date": "2019-10-29 00:40:59 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed bug: Box icons don't show the object type name",
			"Fixed bug: Title of object type root was missing"
		]
	},
	{
		"version": "2.0.0.191",
		"date": "2019-10-27 11:45:08 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Bug in name base generated UUIDs fixed"
		]
	},
	{
		"version": "2.0.0.190",
		"date": "2019-10-24 13:17:25 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Re-canonize script to correct database entries"
		]
	},
	{
		"version": "2.0.0.189",
		"date": "2019-10-19 12:26:22 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed BUG#0000207"
		]
	},
	{
		"version": "2.0.0.188",
		"date": "2019-10-18 19:14:23 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed BUG#0000205: IPv4 module did not work (Logger maskcode conflict)",
			"Fixed BUG#0000206: IPv6 normalization did not work",
			"Fixed bug: Entering a wrong IPv4/IPv6 address lead to an invalid object with id='ipv4:' or id='ipv6:' which caused the treeview to run into an endless loop"
		]
	},
	{
		"version": "2.0.0.187",
		"date": "2019-09-22 01:06:53 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Bugfix"
		]
	},
	{
		"version": "2.0.0.186",
		"date": "2019-09-16 00:22:22 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Minor changes"
		]
	},
	{
		"version": "2.0.0.185",
		"date": "2019-09-15 11:51:39 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Goto box for mobile"
		]
	},
	{
		"version": "2.0.0.184",
		"date": "2019-09-15 10:28:30 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Objects are saved with a canonical name"
		]
	},
	{
		"version": "2.0.0.183",
		"date": "2019-09-11 22:05:09 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"NEW feature: \"Goto\" quick access bar",
			"CHANGED: Incorrect written object identifiers (e.g. 2.0999) will now be auto-corrected"
		]
	},
	{
		"version": "2.0.0.182",
		"date": "2019-08-26 20:57:09 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.181",
		"date": "2019-08-26 18:58:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.180",
		"date": "2019-08-24 20:14:44 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.179",
		"date": "2019-08-22 16:32:41 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small fixes"
		]
	},
	{
		"version": "2.0.0.178",
		"date": "2019-08-21 17:03:50 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OIDplus is now compatible with Microsoft Edge"
		]
	},
	{
		"version": "2.0.0.177",
		"date": "2019-08-21 14:27:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"BUGFIX: Web SVN update fixed"
		]
	},
	{
		"version": "2.0.0.176",
		"date": "2019-08-21 14:21:06 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"BUGFIX: Confidential flag could not be set for OIDs. Fixed."
		]
	},
	{
		"version": "2.0.0.175",
		"date": "2019-08-21 13:42:37 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Registration procedure update"
		]
	},
	{
		"version": "2.0.0.174",
		"date": "2019-08-18 19:22:04 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Setup assistant cannot be started when config file is missing"
		]
	},
	{
		"version": "2.0.0.173",
		"date": "2019-08-18 19:12:54 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Bugfix: Wrong error message when MySQLi connection failed. Linked to setup again."
		]
	},
	{
		"version": "2.0.0.172",
		"date": "2019-08-16 10:37:14 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Update procedure: More revision information is now shown in the preview"
		]
	},
	{
		"version": "2.0.0.171",
		"date": "2019-08-15 16:30:41 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.170",
		"date": "2019-08-14 14:31:08 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"System version and installation type are now included in the Registration"
		]
	},
	{
		"version": "2.0.0.169",
		"date": "2019-08-14 12:57:23 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fix: Confidential OID detection fix"
		]
	},
	{
		"version": "2.0.0.168",
		"date": "2019-08-14 11:55:28 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.167",
		"date": "2019-08-14 11:48:34 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.166",
		"date": "2019-08-14 11:44:35 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.165",
		"date": "2019-08-13 15:48:23 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Update page contains now an invisible VNag status tag"
		]
	},
	{
		"version": "2.0.0.164",
		"date": "2019-08-13 15:12:13 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Update assistant bugfix"
		]
	},
	{
		"version": "2.0.0.163",
		"date": "2019-08-13 14:49:13 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"NEW FEATURE: Software update (web SVN)"
		]
	},
	{
		"version": "2.0.0.162",
		"date": "2019-08-13 13:14:08 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Version detection update"
		]
	},
	{
		"version": "2.0.0.161",
		"date": "2019-08-12 15:09:12 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Misc"
		]
	},
	{
		"version": "2.0.0.160",
		"date": "2019-08-09 13:49:22 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Adjusted \"Content-Security-Policy\""
		]
	},
	{
		"version": "2.0.0.159",
		"date": "2019-08-09 13:40:47 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed problem in system URL if system is hosted directly under a domain"
		]
	},
	{
		"version": "2.0.0.158",
		"date": "2019-08-09 13:40:11 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed OOBE problem"
		]
	},
	{
		"version": "2.0.0.157",
		"date": "2019-08-08 20:01:02 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Setup assistant 2/2 is now protected by ReCAPTCHA, if enabled"
		]
	},
	{
		"version": "2.0.0.156",
		"date": "2019-08-05 20:15:17 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added setup background image"
		]
	},
	{
		"version": "2.0.0.155",
		"date": "2019-08-03 23:20:57 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Invitations can now be disabled.",
			"OOBE Bugfix."
		]
	},
	{
		"version": "2.0.0.154",
		"date": "2019-08-03 22:54:53 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"document.location => window.location.href"
		]
	},
	{
		"version": "2.0.0.153",
		"date": "2019-08-03 22:44:14 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New feature: Admin can now create a RA manually (without email verification/invitation)"
		]
	},
	{
		"version": "2.0.0.152",
		"date": "2019-08-03 21:22:26 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Smaller design fixes. Admin can now change RA contact data, passwords and emails."
		]
	},
	{
		"version": "2.0.0.151",
		"date": "2019-08-03 10:04:02 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Menu entry \"Plugins\" can now be exanded. RA info has now the RA as page title."
		]
	},
	{
		"version": "2.0.0.150",
		"date": "2019-08-03 00:30:01 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"LARGE UPDATE: Added database providers; now using prepared statements, and many more changes"
		]
	},
	{
		"version": "2.0.0.149",
		"date": "2019-08-01 22:58:12 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New feature: Admin password reset plugin"
		]
	},
	{
		"version": "2.0.0.148",
		"date": "2019-07-25 14:05:21 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New plugin \"Plugins\" that lists all plugins"
		]
	},
	{
		"version": "2.0.0.147",
		"date": "2019-07-24 23:24:38 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Smaller bugfixes"
		]
	},
	{
		"version": "2.0.0.146",
		"date": "2019-07-22 12:06:11 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Plugins \"Links\" and \"Documents\" have been merged into new plugin \"Documents and resources\""
		]
	},
	{
		"version": "2.0.0.145",
		"date": "2019-07-21 23:09:18 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.144",
		"date": "2019-07-20 11:35:24 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.143",
		"date": "2019-07-18 16:52:57 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small fixes in re color plugin"
		]
	},
	{
		"version": "2.0.0.142",
		"date": "2019-07-18 16:19:28 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New feature: Admin can set individual colors for their systems!"
		]
	},
	{
		"version": "2.0.0.141",
		"date": "2019-07-17 09:02:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Registration live status page can now be only accessed with signature"
		]
	},
	{
		"version": "2.0.0.140",
		"date": "2019-07-16 23:01:16 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Registratoin: After privacy change, the VTS server will be called immediately"
		]
	},
	{
		"version": "2.0.0.139",
		"date": "2019-07-16 13:41:23 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Updated whole registration process"
		]
	},
	{
		"version": "2.0.0.138",
		"date": "2019-07-14 20:17:35 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.137",
		"date": "2019-07-11 13:06:57 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Ugly workaround for jQueryUI bugs"
		]
	},
	{
		"version": "2.0.0.136",
		"date": "2019-07-11 10:22:01 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Disabled buggy \"quickbars\" plugin at TineMCE editors"
		]
	},
	{
		"version": "2.0.0.135",
		"date": "2019-06-13 20:52:08 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Improved OOBE"
		]
	},
	{
		"version": "2.0.0.134",
		"date": "2019-06-10 18:21:56 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.133",
		"date": "2019-06-09 21:45:25 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added sitemap script"
		]
	},
	{
		"version": "2.0.0.132",
		"date": "2019-06-03 11:08:23 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.131",
		"date": "2019-06-03 10:51:52 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.130",
		"date": "2019-06-03 10:40:30 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.129",
		"date": "2019-05-29 00:31:04 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.128",
		"date": "2019-05-28 13:12:47 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Chrome bug workaround"
		]
	},
	{
		"version": "2.0.0.127",
		"date": "2019-05-28 11:05:33 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"More SEO + Chrome bug workaround"
		]
	},
	{
		"version": "2.0.0.126",
		"date": "2019-05-28 00:34:33 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Reordered content and removed cookieconsent, trying to improve SEO"
		]
	},
	{
		"version": "2.0.0.125",
		"date": "2019-05-26 21:11:55 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small RFC update"
		]
	},
	{
		"version": "2.0.0.124",
		"date": "2019-05-26 16:05:24 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.123",
		"date": "2019-05-21 08:36:37 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Editing of content (via tinyMCE) now mobile friendly"
		]
	},
	{
		"version": "2.0.0.122",
		"date": "2019-05-20 22:05:33 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Responsive design bugfix"
		]
	},
	{
		"version": "2.0.0.121",
		"date": "2019-05-20 16:45:16 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixes to OIDinfo export"
		]
	},
	{
		"version": "2.0.0.120",
		"date": "2019-05-20 13:27:30 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Introcued Responsive Webdesign"
		]
	},
	{
		"version": "2.0.0.119",
		"date": "2019-05-20 09:37:58 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.118",
		"date": "2019-05-19 18:57:01 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.117",
		"date": "2019-05-19 18:52:04 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added logging functionality"
		]
	},
	{
		"version": "2.0.0.116",
		"date": "2019-05-19 14:14:14 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Logger Work in Progress"
		]
	},
	{
		"version": "2.0.0.115",
		"date": "2019-05-19 13:15:45 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Logger Work In Progress"
		]
	},
	{
		"version": "2.0.0.114",
		"date": "2019-05-17 23:46:02 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"openOidInPanel() is now faster (loads content before tree)"
		]
	},
	{
		"version": "2.0.0.113",
		"date": "2019-05-17 22:54:05 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.112",
		"date": "2019-05-17 21:27:53 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.111",
		"date": "2019-05-17 13:48:15 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Improved OOBE DBs"
		]
	},
	{
		"version": "2.0.0.110",
		"date": "2019-05-17 09:00:07 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.109",
		"date": "2019-05-17 00:44:28 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.108",
		"date": "2019-05-16 23:15:23 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Bugfixes; \"List RA\" now in tree expandable"
		]
	},
	{
		"version": "2.0.0.107",
		"date": "2019-05-16 18:45:56 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Another big update"
		]
	},
	{
		"version": "2.0.0.106",
		"date": "2019-05-16 10:46:39 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed but in menu"
		]
	},
	{
		"version": "2.0.0.105",
		"date": "2019-05-16 10:06:09 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Mobile: System menu button animations"
		]
	},
	{
		"version": "2.0.0.104",
		"date": "2019-05-16 00:12:49 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Big update!"
		]
	},
	{
		"version": "2.0.0.103",
		"date": "2019-05-15 11:58:31 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Welcome page has now links to the object types"
		]
	},
	{
		"version": "2.0.0.102",
		"date": "2019-05-15 11:22:51 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Theme colors"
		]
	},
	{
		"version": "2.0.0.101",
		"date": "2019-05-15 10:52:18 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Whois is now a plugin"
		]
	},
	{
		"version": "2.0.0.100",
		"date": "2019-05-15 00:35:02 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.99",
		"date": "2019-05-15 00:10:05 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small menu button design change"
		]
	},
	{
		"version": "2.0.0.98",
		"date": "2019-05-14 16:25:13 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"In the content pane, you can now navigate to parent nodes"
		]
	},
	{
		"version": "2.0.0.97",
		"date": "2019-05-14 14:35:11 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.96",
		"date": "2019-05-14 14:33:49 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Mobildesign"
		]
	},
	{
		"version": "2.0.0.95",
		"date": "2019-05-14 13:10:32 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"First attempt to a mobile design"
		]
	},
	{
		"version": "2.0.0.94",
		"date": "2019-05-10 11:54:27 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Empty index pages"
		]
	},
	{
		"version": "2.0.0.93",
		"date": "2019-05-09 22:14:34 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Catched Exceptions for invalid OIDs"
		]
	},
	{
		"version": "2.0.0.92",
		"date": "2019-05-07 11:09:37 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.91",
		"date": "2019-05-02 14:43:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small fixes in re OIDinfo export"
		]
	},
	{
		"version": "2.0.0.90",
		"date": "2019-05-01 20:41:59 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"OIDinfo export \"more information\" link"
		]
	},
	{
		"version": "2.0.0.89",
		"date": "2019-04-15 00:37:37 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Weird TinyMCE encoding issue fixed"
		]
	},
	{
		"version": "2.0.0.88",
		"date": "2019-04-13 12:25:54 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.87",
		"date": "2019-04-12 21:13:52 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.86",
		"date": "2019-04-11 15:34:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Session now protectede against hijacking (IP change)"
		]
	},
	{
		"version": "2.0.0.85",
		"date": "2019-04-09 14:00:29 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Session Cookies are now only sent if the user actually log ins."
		]
	},
	{
		"version": "2.0.0.84",
		"date": "2019-04-07 20:31:33 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.83",
		"date": "2019-04-07 20:22:06 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Non-OIDs are now transmitted via XML, too"
		]
	},
	{
		"version": "2.0.0.82",
		"date": "2019-04-07 12:24:34 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Various fixes"
		]
	},
	{
		"version": "2.0.0.81",
		"date": "2019-04-07 11:47:27 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Setup fix"
		]
	},
	{
		"version": "2.0.0.80",
		"date": "2019-04-07 11:31:52 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"More OOBE fixes"
		]
	},
	{
		"version": "2.0.0.79",
		"date": "2019-04-07 01:07:11 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"During OOBE, only OID is listed as enabled object type"
		]
	},
	{
		"version": "2.0.0.78",
		"date": "2019-04-07 00:51:12 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Verified that the product runs out-of-the-box"
		]
	},
	{
		"version": "2.0.0.77",
		"date": "2019-04-06 23:21:32 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Replaced deprecated mysql_* functions"
		]
	},
	{
		"version": "2.0.0.76",
		"date": "2019-04-06 20:01:39 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed various problems with the registration. E-Mail address in now in the database and not in the config"
		]
	},
	{
		"version": "2.0.0.75",
		"date": "2019-04-06 13:11:16 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Solved chicken-egg problem in re config initialization"
		]
	},
	{
		"version": "2.0.0.74",
		"date": "2019-04-06 12:07:30 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Introduces registration procedure / OOBE"
		]
	},
	{
		"version": "2.0.0.73",
		"date": "2019-04-04 13:35:39 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.72",
		"date": "2019-04-04 13:35:21 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.71",
		"date": "2019-04-02 09:25:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Redirect after admin RA delete"
		]
	},
	{
		"version": "2.0.0.70",
		"date": "2019-04-02 09:04:24 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.69",
		"date": "2019-04-02 08:57:17 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.68",
		"date": "2019-04-01 23:26:06 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Plugins can now influence conditional tree selection.",
			"Added plugin \"External resources\""
		]
	},
	{
		"version": "2.0.0.67",
		"date": "2019-04-01 14:02:40 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"UUIDs of OIDs will be shown"
		]
	},
	{
		"version": "2.0.0.66",
		"date": "2019-04-01 13:34:27 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Object types are registered in admin config, not in a file anymore"
		]
	},
	{
		"version": "2.0.0.65",
		"date": "2019-04-01 00:03:00 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Bugfix: Symlink doc/ error"
		]
	},
	{
		"version": "2.0.0.64",
		"date": "2019-03-31 11:25:25 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added \"visible\" and \"protected\" fields to configuration table"
		]
	},
	{
		"version": "2.0.0.63",
		"date": "2019-03-31 11:02:30 +0200",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"\"Documents\" plugin: Added support for folders"
		]
	},
	{
		"version": "2.0.0.62",
		"date": "2019-03-31 01:23:22 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New plugin: \"Documents\""
		]
	},
	{
		"version": "2.0.0.61",
		"date": "2019-03-30 20:20:21 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Plugin API heavily improved"
		]
	},
	{
		"version": "2.0.0.60",
		"date": "2019-03-28 13:32:30 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Configuration moved into plugins"
		]
	},
	{
		"version": "2.0.0.59",
		"date": "2019-03-27 14:58:11 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.58",
		"date": "2019-03-27 14:56:20 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Search icon"
		]
	},
	{
		"version": "2.0.0.57",
		"date": "2019-03-27 14:55:20 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added search functionality.",
			"Fixed bug: Back-button did not add icon to the title."
		]
	},
	{
		"version": "2.0.0.56",
		"date": "2019-03-26 23:36:03 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Object icon is now a cube instead of a box"
		]
	},
	{
		"version": "2.0.0.55",
		"date": "2019-03-26 21:51:42 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.54",
		"date": "2019-03-26 19:36:06 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.53",
		"date": "2019-03-26 16:53:06 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RFC"
		]
	},
	{
		"version": "2.0.0.52",
		"date": "2019-03-25 13:01:18 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.51",
		"date": "2019-03-25 12:40:27 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"IRI notation view: long arcs marked"
		]
	},
	{
		"version": "2.0.0.50",
		"date": "2019-03-25 12:13:56 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added \"standardized\" ASN.1 identifier attribute"
		]
	},
	{
		"version": "2.0.0.49",
		"date": "2019-03-23 23:28:25 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Auto-SSL fix"
		]
	},
	{
		"version": "2.0.0.48",
		"date": "2019-03-23 23:07:08 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.47",
		"date": "2019-03-23 22:55:04 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.46",
		"date": "2019-03-23 12:08:11 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"New functionality: Disable RA email address change"
		]
	},
	{
		"version": "2.0.0.45",
		"date": "2019-03-23 01:14:35 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.44",
		"date": "2019-03-23 01:13:43 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Change email address script"
		]
	},
	{
		"version": "2.0.0.43",
		"date": "2019-03-22 11:58:14 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Graphical improvements"
		]
	},
	{
		"version": "2.0.0.42",
		"date": "2019-03-22 09:45:58 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Automatic redirection to HTTPS"
		]
	},
	{
		"version": "2.0.0.41",
		"date": "2019-03-21 22:43:56 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RFC"
		]
	},
	{
		"version": "2.0.0.40",
		"date": "2019-03-21 21:26:54 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RFC"
		]
	},
	{
		"version": "2.0.0.39",
		"date": "2019-03-21 15:09:52 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.38",
		"date": "2019-03-21 13:11:06 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"DOI: Distance API"
		]
	},
	{
		"version": "2.0.0.37",
		"date": "2019-03-21 13:00:43 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.36",
		"date": "2019-03-21 12:58:28 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"GS1: Whois Find with and without check digit",
			"Java: Distance API implemented",
			"IPv4/IPv6: Unnecessary bits are removed at the one_up() function"
		]
	},
	{
		"version": "2.0.0.35",
		"date": "2019-03-21 10:13:36 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RFC proof read by cheery314"
		]
	},
	{
		"version": "2.0.0.34",
		"date": "2019-03-21 09:54:20 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Icons"
		]
	},
	{
		"version": "2.0.0.33",
		"date": "2019-03-21 09:53:52 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.32",
		"date": "2019-03-20 23:48:55 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Content pages now have large 48x48 icons"
		]
	},
	{
		"version": "2.0.0.31",
		"date": "2019-03-20 22:51:44 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.30",
		"date": "2019-03-20 22:39:40 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.29",
		"date": "2019-03-20 17:36:24 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Fixed bug in recursive deletion"
		]
	},
	{
		"version": "2.0.0.28",
		"date": "2019-03-20 17:34:54 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.27",
		"date": "2019-03-20 17:30:23 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"GUID \"root detection\" improved; performance upgrade"
		]
	},
	{
		"version": "2.0.0.26",
		"date": "2019-03-20 16:01:03 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.25",
		"date": "2019-03-20 15:52:46 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Logout icon"
		]
	},
	{
		"version": "2.0.0.24",
		"date": "2019-03-20 15:41:19 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Icons"
		]
	},
	{
		"version": "2.0.0.23",
		"date": "2019-03-20 12:56:40 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RFC"
		]
	},
	{
		"version": "2.0.0.22",
		"date": "2019-03-19 22:53:10 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"RFC"
		]
	},
	{
		"version": "2.0.0.21",
		"date": "2019-03-19 22:51:27 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"\"Web Whois\" links"
		]
	},
	{
		"version": "2.0.0.20",
		"date": "2019-03-19 12:12:03 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"IPv4+IPv6: Whois distance search now possible"
		]
	},
	{
		"version": "2.0.0.19",
		"date": "2019-03-19 10:38:53 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"GUID: nested categories are now allowed"
		]
	},
	{
		"version": "2.0.0.18",
		"date": "2019-03-19 00:24:19 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"IPv4/IPv6: Enforce that addresses are inside CIDR of superior range"
		]
	},
	{
		"version": "2.0.0.17",
		"date": "2019-03-19 00:03:23 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"IPv4/IPv6 technical information"
		]
	},
	{
		"version": "2.0.0.16",
		"date": "2019-03-18 23:14:01 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.15",
		"date": "2019-03-18 12:46:52 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Admin page \"Well known OIDs\""
		]
	},
	{
		"version": "2.0.0.14",
		"date": "2019-03-18 12:23:17 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Config values are now checked before they are saved."
		]
	},
	{
		"version": "2.0.0.13",
		"date": "2019-03-18 11:54:33 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Added configuration page in admin area; fixed some critical bugs"
		]
	},
	{
		"version": "2.0.0.12",
		"date": "2019-03-17 23:54:20 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.11",
		"date": "2019-03-14 15:15:35 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Small design tweaks"
		]
	},
	{
		"version": "2.0.0.10",
		"date": "2019-03-14 13:33:27 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Design improvement at login page"
		]
	},
	{
		"version": "2.0.0.9",
		"date": "2019-03-14 12:59:27 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"CSS and HTML now valid. JS has no warnings in JSHint anymore."
		]
	},
	{
		"version": "2.0.0.8",
		"date": "2019-03-14 00:59:30 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Whois: Subordinate natural order of OIDs"
		]
	},
	{
		"version": "2.0.0.7",
		"date": "2019-03-14 00:07:32 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.6",
		"date": "2019-03-13 23:59:07 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Formatting"
		]
	},
	{
		"version": "2.0.0.5",
		"date": "2019-03-13 22:16:10 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.4",
		"date": "2019-03-13 16:45:37 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Smaller fixes, TinyMCE updated to 5.0.2, updated title bar"
		]
	},
	{
		"version": "2.0.0.3",
		"date": "2019-03-13 00:06:12 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"(No comment)"
		]
	},
	{
		"version": "2.0.0.2",
		"date": "2019-03-12 23:20:11 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Initial Work-In-Progress SVN release"
		]
	},
	{
		"version": "2.0.0.1",
		"date": "2019-03-12 23:07:50 +0100",
		"author": "Daniel Marschall (ViaThinkSoft)",
		"changes": [
			"Created SVN root directories"
		]
	}
]
