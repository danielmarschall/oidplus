<?php

/*
 * UUID utils for PHP
 * Copyright 2011 - 2023 Daniel Marschall, ViaThinkSoft
 * Version 2023-11-11
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

# This library requires either the GMP extension (or BCMath if gmp_supplement.inc.php is present)
// TODO: If we are on 64 bit PHP (PHP_INT_SIZE > 4), then replace GMP with normal PHP operations

if (file_exists($f = __DIR__ . '/mac_utils.inc.php')) include_once $f;
else if (file_exists($f = __DIR__ . '/mac_utils.inc.phps')) include_once $f;

if (file_exists($f = __DIR__ . '/gmp_supplement.inc.php')) include_once $f;
else if (file_exists($f = __DIR__ . '/gmp_supplement.inc.phps')) include_once $f;

if (file_exists($f = __DIR__ . '/OidDerConverter.class.php')) include_once $f;
else if (file_exists($f = __DIR__ . '/OidDerConverter.class.phps')) include_once $f;

// Note: The RFC allows various notations as payload, not a strict notation constraint
const UUID_NAMEBASED_NS_DNS = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
const UUID_NAMEBASED_NS_URL = '6ba7b811-9dad-11d1-80b4-00c04fd430c8';
const UUID_NAMEBASED_NS_OID = '6ba7b812-9dad-11d1-80b4-00c04fd430c8';
const UUID_NAMEBASED_NS_X500_DN = '6ba7b814-9dad-11d1-80b4-00c04fd430c8';

if (!function_exists('_random_int')) {
	function _random_int($min, $max) {
		// This function tries a CSRNG and falls back to a RNG if no CSRNG is available
		try {
			return random_int($min, $max);
		} catch (Exception $e) {
			return mt_rand($min, $max);
		}
	}
}

function uuid_valid($uuid) {
	$uuid = str_replace(array('-', '{', '}'), '', $uuid);
	$uuid = strtoupper($uuid);
	#$uuid = trim($uuid);

	if (strlen($uuid) != 32) return false;

	$uuid = preg_replace('@[0-9A-F]@i', '', $uuid);

	return ($uuid == '');
}

function uuid_equal($uuid1, $uuid2) {
	$uuid1 = uuid_canonize($uuid1);
	if (!$uuid1) return false;
	$uuid2 = uuid_canonize($uuid2);
	if (!$uuid2) return false;
	return $uuid1 === $uuid2;
}

function uuid_version($uuid) {
	$uuid = uuid_canonize($uuid);
	if (!$uuid) return false;
	return substr($uuid, 19, 1);
}

function uuid_info($uuid, $echo=true) {
	if (!uuid_valid($uuid)) return false;

	$oid = uuid_to_oid($uuid);

	echo sprintf("%-32s %s\n", "Your input:", $uuid);
	echo "\n";
	echo "<u>Various notations:</u>\n";
	echo "\n";
	echo sprintf("%-32s %s\n", "URN:", 'urn:uuid:' . strtolower(oid_to_uuid(uuid_to_oid($uuid))));
	echo sprintf("%-32s %s\n", "URI:", 'uuid:' . strtolower(oid_to_uuid(uuid_to_oid($uuid))));
	echo sprintf("%-32s %s\n", "Microsoft GUID syntax:", '{' . strtoupper(oid_to_uuid(uuid_to_oid($uuid))) . '}');
	echo sprintf("%-32s %s\n", "C++ struct syntax:", uuid_c_syntax($uuid));
	echo "\n";

	echo sprintf("%-32s %s\n", "As OID (ISO/ITU-T 128 bits):", $oid=uuid_to_oid($uuid, '2.25'));
	# Removed because it is too much information (we would also need to add this to the other OIDs too)
	#if (class_exists('OidDerConverter')) {
	#	echo sprintf("%-32s %s\n", "DER encoding of OID:", OidDerConverter::hexarrayToStr(OidDerConverter::oidToDER($oid)));
	#}
	echo sprintf("%-32s %s\n", "As OID (Microsoft):", $oid=uuid_to_oid($uuid, '1.2.840.113556.1.8000.2554'));
	echo sprintf("%-32s %s\n", "As OID (Waterjuice 2x64 bits):", $oid=uuid_to_oid($uuid, '1.3.6.1.4.1.54392.1'));
	echo sprintf("%-32s %s\n", "As OID (Waterjuice 4x32 bits):", $oid=uuid_to_oid($uuid, '1.3.6.1.4.1.54392.2'));
	echo sprintf("%-32s %s\n", "As OID (Waterjuice 8x16 bits):", $oid=uuid_to_oid($uuid, '1.3.6.1.4.1.54392.3'));

	echo "\n";

	echo "<u>Interpretation of the UUID:</u>\n\n";

	if (!$echo) ob_start();

	#$uuid = trim($uuid);
	# $uuid = str_replace(array('-', '{', '}'), '', $uuid);
	$uuid = strtolower($uuid);
	$uuid = preg_replace('@[^0-9A-F]@i', '', $uuid);

	$x = hexdec(substr($uuid, 16, 1));
	     if ($x >= 14 /* 0b1110 */) $variant = 3;
	else if ($x >= 12 /* 0b110_ */) $variant = 2;
	else if ($x >=  8 /* 0b10__ */) $variant = 1;
	else if ($x >=  0 /* 0b0___ */) $variant = 0;
	else $variant = -1; // should not happen

	if ($uuid == '00000000000000000000000000000000') {
		echo sprintf("%-32s %s\n", "Special Use:", "Nil UUID");
		echo "\n";
	}
	else if ($uuid == 'ffffffffffffffffffffffffffffffff') {
		echo sprintf("%-32s %s\n", "Special Use:", "Max UUID");
		echo "\n";
	}

	switch ($variant) {
		case 0:
			echo sprintf("%-32s %s\n", "Variant:", "[0b0__] Network Computing System (NCS)");

			/*
			 * Internal structure of variant #0 UUIDs
			 *
			 * The first 6 octets are the number of 4 usec units of time that have
			 * passed since 1/1/80 0000 GMT.  The next 2 octets are reserved for
			 * future use.  The next octet is an address family.  The next 7 octets
			 * are a host ID in the form allowed by the specified address family.
			 *
			 * Note that while the family field (octet 8) was originally conceived
			 * of as being able to hold values in the range [0..255], only [0..13]
			 * were ever used.  Thus, the 2 MSB of this field are always 0 and are
			 * used to distinguish old and current UUID forms.
			 */

			/*
			Variant 0 UUID
			- 32 bit High Time
			- 16 bit Low Time
			- 16 bit Reserved
			-  1 bit Variant (fix 0b0)
			-  7 bit Family
			- 56 bit Node
			*/

			// Example of an UUID: 333a2276-0000-0000-0d00-00809c000000

			// TODO: also show legacy format, e.g. 458487b55160.02.c0.64.02.03.00.00.00

			# see also some notes at See https://github.com/cjsv/uuid/blob/master/Doc

			/*
			NOTE: A generator is not possible, because there are no timestamps left!
			The last possible timestamp was:
			    [0xFFFFFFFFFFFF] 2015-09-05 05:58:26'210655 GMT
			That is in the following UUID:
			    ffffffff-ffff-0000-027f-000001000000
			Current timestamp generator:
			    echo dechex(round((microtime(true)+315532800)*250000));
			*/

			# Timestamp: Count of 4us intervals since 01 Jan 1980 00:00:00 GMT
			# 1/0,000004 = 250000
			# Seconds between 1970 and 1980 : 315532800
			# 250000*315532800=78883200000000
			$timestamp = substr($uuid, 0, 12);
			$ts = gmp_init($timestamp, 16);
			$ts = gmp_add($ts, gmp_init("78883200000000", 10));
			$ms = gmp_mod($ts, gmp_init("250000", 10));
			$ts = gmp_div($ts, gmp_init("250000", 10));
			$ts = gmp_strval($ts, 10);
			$ms = gmp_strval($ms, 10);
			$ts = gmdate('Y-m-d H:i:s', intval($ts))."'".str_pad($ms, 6/*us*/, '0', STR_PAD_LEFT).' GMT';
			echo sprintf("%-32s %s\n", "Timestamp:", "[0x$timestamp] $ts");

			$reserved = substr($uuid, 12, 4);
			echo sprintf("%-32s %s\n", "Reserved:", "[0x$reserved]");

			$family_hex = substr($uuid, 16, 2);
			$family_dec = hexdec($family_hex);
			$nodeid_hex = substr($uuid, 18, 14);
			$nodeid_dec = hexdec($nodeid_hex);

			// Sources:
			// - https://bitsavers.org/pdf/ibm/rs6000/aix_3.0/SC23-2206-0_AIX_Version_3_for_RS6000_Communications_Programming_Concepts_199003.pdf
			// - (For comparison) https://github.com/uuid6/uuid6-ietf-draft/issues/26#issuecomment-1062164457
			// - (For comparison) https://learn.microsoft.com/en-us/dotnet/api/system.net.sockets.addressfamily?view=net-7.0 [numbers 0..13 are mostly identical]

			if ($family_dec == 0) {
				# Microsoft's AdressFamily: Unspecified	0	Unspecified address family.
				# AIX 3.0 Manual:  0   unspec = Unspecified
				$family_name = 'socket_$unspec (Unspecified)';
				$nodeid_desc = ''; // TODO: how to interprete the Node-ID of that family?
			}
			else if ($family_dec == 1) {
				# Microsoft's AdressFamily: Unix	1	Unix local to host address.
				# AIX 3.0 Manual:  1   unix = Local to host (pipes, portals)
				$family_name = 'socket_$unix (Local to host, e.g. pipes, portals)';
				$nodeid_desc = ''; // TODO: how to interprete the Node-ID of that family?
			}
			else if ($family_dec == 2) {
				# Microsoft's AdressFamily: InterNetwork	2	Address for IP version 4.
				# AIX 3.0 Manual:  2   ip = Internet Protocols
				$family_name = 'socket_$internet (Internet Protocols, e.g. IPv4)';
				// https://www.ibm.com/docs/en/aix/7.1?topic=u-uuid-gen-command-ncs (AIX 7.1) shows the following example output for /etc/ncs/uuid_gen -P
				// := [
				//    time_high := 16#458487df,
				//    time_low := 16#9fb2,
				//    reserved := 16#000,
				//    family := chr(16#02),
				//    host := [chr(16#c0), chr(16#64), chr(16#02), chr(16#03),
				//             chr(16#00), chr(16#00), chr(16#00)]
				//    ]
				// This means that the IP address is 32 bits hex, and 32 bits are unused
				$nodeid_desc = hexdec(substr($nodeid_hex,0,2)).'.'.
				               hexdec(substr($nodeid_hex,2,2)).'.'.
				               hexdec(substr($nodeid_hex,4,2)).'.'.
				               hexdec(substr($nodeid_hex,6,2));
				$rest = substr($nodeid_hex,8,6);
				if ($rest != '000000') $nodeid_desc .= " + unexpected rest 0x$rest";
			}
			else if ($family_dec == 3) {
				# Microsoft's AdressFamily: ImpLink	3	ARPANET IMP address.
				# AIX 3.0 Manual:  3   implink = ARPANET imp addresses
				$family_name = 'socket_$implink (ARPANET imp addresses)';
				$nodeid_desc = ''; // TODO: how to interprete the Node-ID of that family?
			}
			else if ($family_dec == 4) {
				# Microsoft's AdressFamily: Pup	4	Address for PUP protocols.
				# AIX 3.0 Manual:  4   pup = Pup protocols (for example, BSP)
				$family_name = 'socket_$pup (Pup protocols, e.g. BSP)';
				$nodeid_desc = ''; // TODO: how to interprete the Node-ID of that family?
			}
			else if ($family_dec == 5) {
				# Microsoft's AdressFamily: Chaos	5	Address for MIT CHAOS protocols.
				# AIX 3.0 Manual:  5   chaos = MIT CHAOS protocols
				$family_name = 'socket_$chaos (MIT CHAOS protocols)';
				$nodeid_desc = ''; // TODO: how to interprete the Node-ID of that family?
			}
			else if ($family_dec == 6) {
				# Microsoft's AdressFamily: NS	6	Address for Xerox NS protocols.
				# Microsoft's AdressFamily: Ipx	6	IPX or SPX address.
				# AIX 3.0 Manual:  6   ns = XEROX NS protocols
				$family_name = 'socket_$ns (XEROX NS protocols)';
				$nodeid_desc = ''; // TODO: how to interprete the Node-ID of that family?
			}
			else if ($family_dec == 7) {
				# Microsoft's AdressFamily: Osi	7	Address for OSI protocols.
				# Microsoft's AdressFamily: Iso	7	Address for ISO protocols.
				# AIX 3.0 Manual:  7   nbs = NBS protocols
				$family_name = 'socket_$nbs (NBS protocols)';
				$nodeid_desc = ''; // TODO: how to interprete the Node-ID of that family?
			}
			else if ($family_dec == 8) {
				# Microsoft's AdressFamily: Ecma	8	European Computer Manufacturers Association (ECMA) address.
				# AIX 3.0 Manual:  8   ecma = European computer manufacturers
				$family_name = 'socket_$ecma (European computer manufacturers protocols)';
				$nodeid_desc = ''; // TODO: how to interprete the Node-ID of that family?
			}
			else if ($family_dec == 9) {
				# Microsoft's AdressFamily: DataKit	9	Address for Datakit protocols.
				# AIX 3.0 Manual:  9   datakit = Datakit protocols
				$family_name = 'socket_$datakit (Datakit protocols)';
				$nodeid_desc = ''; // TODO: how to interprete the Node-ID of that family?
			}
			else if ($family_dec == 10) {
				# Microsoft's AdressFamily: Ccitt	10	Addresses for CCITT protocols, such as X.25.
				# AIX 3.0 Manual:  A   ccitt = CCITT protocols (for example, X.25)
				$family_name = 'socket_$ccitt (CCITT protocols, e.g. X.25)';
				$nodeid_desc = ''; // TODO: how to interprete the Node-ID of that family?
			}
			else if ($family_dec == 11) {
				# Microsoft's AdressFamily: Sna	11	IBM SNA address.
				# AIX 3.0 Manual:  B   sna = IBM SNA
				$family_name = 'socket_$sna (IBM SNA)';
				$nodeid_desc = ''; // TODO: how to interprete the Node-ID of that family?
			}
			else if ($family_dec == 12) {
				# Microsoft's AdressFamily: DecNet	12	DECnet address.
				# AIX 3.0 Manual:  C   unspec2 = Unspecified
				$family_name = 'socket_$unspec2 (Unspecified)';
				$nodeid_desc = ''; // TODO: how to interprete the Node-ID of that family?
			}
			else if ($family_dec == 13) {
				# Microsoft's AdressFamily: DataLink	13	Direct data-link interface address.
				# AIX 3.0 Manual:  D   dds = Domain DDS protocol
				# Some also call this "Data Link" ... Is that correct?
				$family_name = 'socket_$dds (Domain DDS protocol)';
				// https://www.ibm.com/docs/en/aix/7.1?topic=u-uuid-gen-command-ncs (AIX 7.1) shows the following example output for /etc/ncs/uuid_gen -C
				// = { 0x34dc23af,
				//    0xf000,
				//    0x0000,
				//    0x0d,
				//    {0x00, 0x00, 0x7c, 0x5f, 0x00, 0x00, 0x00} };
				// https://github.com/cjsv/uuid/blob/master/Doc writes:
				//    "Family 13 (dds) looks like node is 00 | nnnnnn 000000."

				$nodeid_desc = '';

				$start = substr($nodeid_hex,0,2);
				if ($start != '00') $nodeid_desc .= "unexpected start 0x$start + ";

				$nodeid_desc .= ($nodeid_dec >> 24) & 0xFFFFFF;

				$rest = substr($nodeid_hex,8,6);
				if ($rest != '000000') $nodeid_desc .= " + unexpected rest 0x$rest";
			} else {
				$family_name = "Unknown (Family $family_dec)"; # There are probably no more families
				$nodeid_desc = "Unknown";
			}
			echo sprintf("%-32s %s\n", "Family:", "[0x$family_hex] $family_name");

			echo sprintf("%-32s %s\n", "Node ID:", "[0x$nodeid_hex] $nodeid_desc");

			break;
		case 1:
			// TODO: Show byte order: 00112233-4455-6677-8899-aabbccddeeff => 00 11 22 33 44 55 66 77 88 99 aa bb cc dd ee ff

			$version = hexdec(substr($uuid, 12, 1));

			if ($version <= 2) {
				echo sprintf("%-32s %s\n", "Variant:", "[0b10_] RFC 4122 (Leach-Mealling-Salz) / DCE 1.1");
			} else if (($version >= 3) && ($version <= 5)) {
				echo sprintf("%-32s %s\n", "Variant:", "[0b10_] RFC 4122 (Leach-Mealling-Salz)");
			} else if (($version >= 6) && ($version <= 8)) {
				echo sprintf("%-32s %s\n", "Variant:", "[0b10_] RFC draft-ietf-uuidrev-rfc4122bis (Davis-Peabody-Leach)"); // TODO: When new RFC is published, replace the RFC number
			} else {
				echo sprintf("%-32s %s\n", "Variant:", "[0b10_] Unknown RFC");
			}

			switch ($version) {
				case 6:
					/*
					Variant 1, Version 6 UUID
					- 48 bit High Time
					-  4 bit Version (fix 0x6)
					- 12 bit Low Time
					-  2 bit Variant (fix 0b10)
					-  6 bit Clock Sequence High
					-  8 bit Clock Sequence Low
					- 48 bit MAC Address
					*/
					echo sprintf("%-32s %s\n", "Version:", "[0x6] Reordered Time-Based");
					$uuid = substr($uuid,  0, 8).'-'.
					        substr($uuid,  8, 4).'-'.
					        substr($uuid, 12, 4).'-'.
					        substr($uuid, 16, 4).'-'.
					        substr($uuid, 20, 12);
					$uuid = uuid6_to_uuid1($uuid);
					$uuid = str_replace('-', '', $uuid);

				/* fallthrough */
				case 1:
					/*
					Variant 1, Version 1 UUID
					- 32 bit Low Time
					- 16 bit Mid Time
					-  4 bit Version (fix 0x1)
					- 12 bit High Time
					-  2 bit Variant (fix 0b10)
					-  6 bit Clock Sequence High
					-  8 bit Clock Sequence Low
					- 48 bit MAC Address
					*/

					if ($version == 1) echo sprintf("%-32s %s\n", "Version:", "[0x1] Time-based with unique host identifier");

					# Timestamp: Count of 100ns intervals since 15 Oct 1582 00:00:00
					# 1/0,0000001 = 10000000
					$timestamp = substr($uuid, 13, 3).substr($uuid, 8, 4).substr($uuid, 0, 8);
					$ts = gmp_init($timestamp, 16);
					$ts = gmp_sub($ts, gmp_init("122192928000000000", 10));
					$ms = gmp_mod($ts, gmp_init("10000000", 10));
					$ts = gmp_div($ts, gmp_init("10000000", 10));
					$ts = gmp_strval($ts, 10);
					$ms = gmp_strval($ms, 10);
					$ts = gmdate('Y-m-d H:i:s', intval($ts))."'".str_pad($ms, 7/*0.1us*/, '0', STR_PAD_LEFT).' GMT';
					echo sprintf("%-32s %s\n", "Timestamp:", "[0x$timestamp] $ts");

					$x = hexdec(substr($uuid, 16, 4));
					$dec = $x & 0x3FFF; // The highest 2 bits are used by "variant" (10x)
					$hex = substr($uuid, 16, 4);
					$hex = '<abbr title="The highest 2 bits are used by the UUID variant (10xx)">'.$hex[0].'</abbr>'.substr($hex,1);
					echo sprintf("%-32s %s\n", "Clock ID:", "[0x$hex] $dec");

					$x = substr($uuid, 20, 12);
					$nodeid = '';
					for ($i=0; $i<6; $i++) {
						$nodeid .= substr($x, $i*2, 2);
						if ($i != 5) $nodeid .= '-';
					}
					$nodeid = strtoupper($nodeid);
					echo sprintf("%-32s %s\n", "Node ID:", "[0x$x] $nodeid");

					echo "\n<u>In case that this Node ID is a MAC address, here is the interpretation of that MAC address:</u>\n\n";
					decode_mac(strtoupper($nodeid));

					break;
				case 2:
					/*
					Variant 1, Version 2 UUID
					- 32 bit Local Domain Number
					- 16 bit Mid Time
					-  4 bit Version (fix 0x2)
					- 12 bit High Time
					-  2 bit Variant (fix 0b10)
					-  6 bit Clock Sequence
					-  8 bit Local Domain
					- 48 bit MAC Address
					*/

					// see also https://unicorn-utterances.com/posts/what-happened-to-uuid-v2

					echo sprintf("%-32s %s\n", "Version:", "[0x2] DCE Security version");

					# The clock_seq_low field (which represents an integer in the range [0, 28-1]) is interpreted as a local domain (as represented by sec_rgy_domain_t; see sec_rgy_domain_t ); that is, an identifier domain meaningful to the local host. (Note that the data type sec_rgy_domain_t can potentially hold values outside the range [0, 28-1]; however, the only values currently registered are in the range [0, 2], so this type mismatch is not significant.) In the particular case of a POSIX host, the value sec_rgy_domain_person is to be interpreted as the "POSIX UID domain", and the value sec_rgy_domain_group is to be interpreted as the "POSIX GID domain".
					$x = substr($uuid, 18, 2);
					if ($x == '00') $domain_info = 'Person (e.g. POSIX UID)';
					else if ($x == '01') $domain_info = 'Group (e.g. POSIX GID)';
					else if ($x == '02') $domain_info = 'Organization';
					else $domain_info = 'site-defined (Domain '.hexdec($x).')';
					echo sprintf("%-32s %s\n", "Local Domain:", "[0x$x] $domain_info");

					# The time_low field (which represents an integer in the range [0, 232-1]) is interpreted as a local-ID; that is, an identifier (within the domain specified by clock_seq_low) meaningful to the local host. In the particular case of a POSIX host, when combined with a POSIX UID or POSIX GID domain in the clock_seq_low field (above), the time_low field represents a POSIX UID or POSIX GID, respectively.
					$x = substr($uuid, 0, 8);
					$dec = hexdec($x);
					echo sprintf("%-32s %s\n", "Local Domain Number:", "[0x$x] $dec");

					# Timestamp: Count of 100ns intervals since 15 Oct 1582 00:00:00
					# 1/0,0000001 = 10000000
					$timestamp = substr($uuid, 13, 3).substr($uuid, 8, 4).'00000000';
					$ts = gmp_init($timestamp, 16);
					$ts = gmp_sub($ts, gmp_init("122192928000000000", 10));
					$ms = gmp_mod($ts, gmp_init("10000000", 10));
					$ts = gmp_div($ts, gmp_init("10000000", 10));
					$ts = gmp_strval($ts, 10);
					$ms = gmp_strval($ms, 10);
					$ts_min = gmdate('Y-m-d H:i:s', intval($ts))."'".str_pad($ms, 7/*0.1us*/, '0', STR_PAD_LEFT).' GMT';

					$timestamp = substr($uuid, 13, 3).substr($uuid, 8, 4).'FFFFFFFF';
					$ts = gmp_init($timestamp, 16);
					$ts = gmp_sub($ts, gmp_init("122192928000000000", 10));
					$ms = gmp_mod($ts, gmp_init("10000000", 10));
					$ts = gmp_div($ts, gmp_init("10000000", 10));
					$ts = gmp_strval($ts, 10);
					$ms = gmp_strval($ms, 10);
					$ts_max = gmdate('Y-m-d H:i:s', intval($ts))."'".str_pad($ms, 7/*0.1us*/, '0', STR_PAD_LEFT).' GMT';

					$timestamp = substr($uuid, 13, 3).substr($uuid, 8, 4)/*.'xxxxxxxx'*/;
					echo sprintf("%-32s %s\n", "Timestamp:", "[0x$timestamp] $ts_min - $ts_max");

					$x = hexdec(substr($uuid, 16, 2));
					$dec = $x & 0x3F; // The highest 2 bits are used by "variant" (10xx)
					$hex = substr($uuid, 16, 2);
					$hex = '<abbr title="The highest 2 bits are used by the UUID variant (10xx)">'.$hex[0].'</abbr>'.substr($hex,1);
					echo sprintf("%-32s %s\n", "Clock ID:", "[0x$hex] $dec");

					$x = substr($uuid, 20, 12);
					$nodeid = '';
					for ($i=0; $i<6; $i++) {
						$nodeid .= substr($x, $i*2, 2);
						if ($i != 5) $nodeid .= '-';
					}
					$nodeid = strtoupper($nodeid);
					echo sprintf("%-32s %s\n", "Node ID:", "[0x$x] $nodeid");

					echo "\n<u>In case that this Node ID is a MAC address, here is the interpretation of that MAC address:</u>\n\n";
					decode_mac(strtoupper($nodeid));

					break;
				case 3:
					/*
					Variant 1, Version 3 UUID
					- 48 bit Hash High
					-  4 bit Version (fix 0x3)
					- 12 bit Hash Mid
					-  2 bit Variant (fix 0b10)
					- 62 bit Hash Low
					*/

					echo sprintf("%-32s %s\n", "Version:", "[0x3] Name-based (MD5 hash)");

					$hash = str_replace('-', '', strtolower($uuid));

					$hash[12] = '?'; // was overwritten by version

					$var16a = strtoupper(dechex(hexdec($hash[16]) & 0b0011 | 0b0000));
					$var16b = strtoupper(dechex(hexdec($hash[16]) & 0b0011 | 0b0100));
					$var16c = strtoupper(dechex(hexdec($hash[16]) & 0b0011 | 0b1000));
					$var16d = strtoupper(dechex(hexdec($hash[16]) & 0b0011 | 0b1100));
					$hash[16] = '?'; // was partially overwritten by variant

					$p = 16;
					$hash = substr($hash,0,$p)."<abbr title=\"$var16a, $var16b, $var16c, or $var16d\">".substr($hash,$p,1).'</abbr>'.substr($hash,$p+1);
					echo sprintf("%-32s %s\n", "MD5(Namespace+Subject):", "[0x$hash]");

					break;
				case 4:
					/*
					Variant 1, Version 4 UUID
					- 48 bit Random High
					-  4 bit Version (fix 0x4)
					- 12 bit Random Mid
					-  2 bit Variant (fix 0b10)
					- 62 bit Random Low
					*/

					echo sprintf("%-32s %s\n", "Version:", "[0x4] Random");

					$rand_line1 = '';
					$rand_line2 = '';
					for ($i=0; $i<16; $i++) {
						$bin = base_convert(substr($uuid, $i*2, 2), 16, 2);
						$bin = str_pad($bin, 8, "0", STR_PAD_LEFT);

						if ($i == 6) {
							// was overwritten by version
							$bin[0] = '?';
							$bin[1] = '?';
							$bin[2] = '?';
							$bin[3] = '?';
						} else if ($i == 8) {
							// was partially overwritten by variant
							$bin[0] = '?';
							$bin[1] = '?';
						}

						if ($i<8) $rand_line1 .= "$bin ";
						if ($i>=8) $rand_line2 .= "$bin ";
					}
					echo sprintf("%-32s %s\n", "Random bits:", trim($rand_line1));
					echo sprintf("%-32s %s\n", "",             trim($rand_line2));

					$rand_bytes = str_replace('-', '', strtolower($uuid));
					$rand_bytes[12] = '?'; // was overwritten by version
					$var16a = strtoupper(dechex(hexdec($rand_bytes[16]) & 0b0011 | 0b0000));
					$var16b = strtoupper(dechex(hexdec($rand_bytes[16]) & 0b0011 | 0b0100));
					$var16c = strtoupper(dechex(hexdec($rand_bytes[16]) & 0b0011 | 0b1000));
					$var16d = strtoupper(dechex(hexdec($rand_bytes[16]) & 0b0011 | 0b1100));
					$rand_bytes[16] = '?'; // was partially overwritten by variant

					$p = 16;
					$rand_bytes = substr($rand_bytes,0,$p)."<abbr title=\"$var16a, $var16b, $var16c, or $var16d\">".substr($rand_bytes,$p,1).'</abbr>'.substr($rand_bytes,$p+1);
					echo sprintf("%-32s %s\n", "Random bytes:", "[0x$rand_bytes]");

					break;
				case 5:
					/*
					Variant 1, Version 5 UUID
					- 48 bit Hash High
					-  4 bit Version (fix 0x5)
					- 12 bit Hash Mid
					-  2 bit Variant (fix 0b10)
					- 62 bit Hash Low
					*/

					echo sprintf("%-32s %s\n", "Version:", "[0x5] Name-based (SHA-1 hash)");

					$hash = str_replace('-', '', strtolower($uuid));

					$hash[12] = '?'; // was overwritten by version

					$var16a = strtoupper(dechex(hexdec($hash[16]) & 0b0011 | 0b0000));
					$var16b = strtoupper(dechex(hexdec($hash[16]) & 0b0011 | 0b0100));
					$var16c = strtoupper(dechex(hexdec($hash[16]) & 0b0011 | 0b1000));
					$var16d = strtoupper(dechex(hexdec($hash[16]) & 0b0011 | 0b1100));
					$hash[16] = '?'; // was partially overwritten by variant

					$hash .= '????????'; // was cut off

					$p = 16;
					$hash = substr($hash,0,$p)."<abbr title=\"$var16a, $var16b, $var16c, or $var16d\">".substr($hash,$p,1).'</abbr>'.substr($hash,$p+1);
					echo sprintf("%-32s %s\n", "SHA1(Namespace+Subject):", "[0x$hash]");

					break;
				case 7:
					/*
					Variant 1, Version 7 UUID
					- 48 bit Unix Time in milliseconds
					-  4 bit Version (fix 0x7)
					- 12 bit Data
					-  2 bit Variant (fix 0b10)
					- 62 bit Data

					Structure of data (74 bits):
					- OPTIONAL : Sub-millisecond timestamp fraction (0-12 bits)
					- OPTIONAL : Carefully seeded counter
					- Random generated bits for any remaining space

					Since we don't know if timestamp fraction or counters are implemented
					(and if so, how many bits), we don't decode this information
					*/

					echo sprintf("%-32s %s\n", "Version:", "[0x7] Unix Epoch Time");

					$timestamp = substr($uuid, 0, 12);

					// Timestamp: Split into seconds and milliseconds
					$ts = gmp_init($timestamp, 16);
					$ms = gmp_mod($ts, gmp_init("1000", 10));
					$ts = gmp_div($ts, gmp_init("1000", 10));
					$ts = gmp_strval($ts, 10);
					$ms = gmp_strval($ms, 10);
					$ts = gmdate('Y-m-d H:i:s', intval($ts))."'".str_pad($ms, 3/*ms*/, '0', STR_PAD_LEFT).' GMT';
					echo sprintf("%-32s %s\n", "Timestamp:", "[0x$timestamp] $ts");

					$rand = '';
					for ($i=6; $i<16; $i++) {
						$bin = base_convert(substr($uuid, $i*2, 2), 16, 2);
						$bin = str_pad($bin, 8, "0", STR_PAD_LEFT);

						if ($i == 6) {
							// was overwritten by version
							$bin[0] = '?';
							$bin[1] = '?';
							$bin[2] = '?';
							$bin[3] = '?';
						} else if ($i == 8) {
							// was partially overwritten by variant
							$bin[0] = '?';
							$bin[1] = '?';
						}

						$rand .= "$bin ";
					}
					echo sprintf("%-32s %s\n", "Random bits:", trim($rand));

					$rand_bytes = substr(str_replace('-', '', strtolower($uuid)),13);
					$var16a = strtoupper(dechex(hexdec($rand_bytes[3]) & 0b0011 | 0b0000));
					$var16b = strtoupper(dechex(hexdec($rand_bytes[3]) & 0b0011 | 0b0100));
					$var16c = strtoupper(dechex(hexdec($rand_bytes[3]) & 0b0011 | 0b1000));
					$var16d = strtoupper(dechex(hexdec($rand_bytes[3]) & 0b0011 | 0b1100));
					$rand_bytes[3] = '?'; // was partially overwritten by variant

					$p = 3;
					$rand_bytes = substr($rand_bytes,0,$p)."<abbr title=\"$var16a, $var16b, $var16c, or $var16d\">".substr($rand_bytes,$p,1).'</abbr>'.substr($rand_bytes,$p+1);
					echo sprintf("%-32s %s\n", "Random bytes:", "[0x$rand_bytes]");

					// TODO: convert to and from Base32 CROCKFORD ULID (make 2 methods in uuid_utils.inc.php)
					// e.g. ULID: 01GCZ05N3JFRKBRWKNGCQZGP44
					// "Be aware that all version 7 UUIDs may be converted to ULIDs but not all ULIDs may be converted to UUIDs."

					break;
				case 8:
					/*
					Variant 1, Version 8 UUID
					- 48 bit Custom data [Block 1+2]
					-  4 bit Version (fix 0x8)
					- 12 bit Custom data [Block 3]
					-  2 bit Variant (fix 0b10)
					- 62 bit Custom data [Block 4+5]
					*/

					echo sprintf("%-32s %s\n", "Version:", "[0x8] Custom implementation");

					$custom_data = substr($uuid,0,12).substr($uuid,13); // exclude version nibble
					$custom_data[15] = dechex(hexdec($custom_data[15]) & 0b0011); // nibble was partially overwritten by variant
					$custom_data = strtolower($custom_data);

					$custom_block1 = substr($uuid,  0, 8);
					$custom_block2 = substr($uuid,  8, 4);
					$custom_block3 = substr($uuid, 12, 4);
					$custom_block4 = substr($uuid, 16, 4);
					$custom_block5 = substr($uuid, 20);

					$custom_block3 = substr($custom_block3, 1); // remove version
					$custom_block4[0] = dechex(hexdec($custom_block4[0]) & 0b0011); // remove variant

					echo sprintf("%-32s %s\n", "Custom data:", "[0x$custom_data]");
					echo sprintf("%-32s %s\n", "Custom data block1 (32 bit):", "[0x$custom_block1]");
					echo sprintf("%-32s %s\n", "Custom data block2 (16 bit):", "[0x$custom_block2]");
					echo sprintf("%-32s %s\n", "Custom data block3 (12 bit):", "[0x$custom_block3]");
					echo sprintf("%-32s %s\n", "Custom data block4 (14 bit):", "[0x$custom_block4]");
					echo sprintf("%-32s %s\n", "Custom data block5 (48 bit):", "[0x$custom_block5]");

					// START: Check if Custom UUIDv8 is likely an OIDplus 2.0 Custom UUID

					$oidplus_systemid_hex = $custom_block1;
					$oidplus_systemid_int = hexdec($oidplus_systemid_hex); // 31 bit hash of public key
					$oidplus_systemid_valid = hexdec($custom_block1) < 0x80000000;

					$oidplus_creation_hex = $custom_block2;
					$oidplus_creation_int = hexdec($oidplus_creation_hex); // days since 1 January 1970, or 0 if unknown
					//$oidplus_creation_valid = ($oidplus_creation_int >= 14610/*1 Jan 2010*/) && ($oidplus_creation_int <= floor(time()/24/60/60)/*Today*/);
					$oidplus_creation_unknown = $oidplus_creation_int == 0;

					$oidplus_reserved_hex = $custom_block3;
					$oidplus_reserved_int = hexdec($oidplus_reserved_hex);

					$oidplus_namespace_hex = $custom_block4;
					$oidplus_namespace_int = hexdec($oidplus_namespace_hex);

					$oidplus_data_hex = $custom_block5;
					$oidplus_data_int = (PHP_INT_SIZE == 4) ? gmp_strval(gmp_init($oidplus_data_hex,16),10) : hexdec($custom_block5);

					if ($oidplus_systemid_valid && ($oidplus_reserved_int == 0)) {
						if (($oidplus_namespace_int == 0) && $oidplus_creation_unknown && (strtolower($oidplus_data_hex) == '1890afd80709')) {
							// System GUID, e.g. 6e932dd7-0000-8000-8000-1890afd80709
							echo "\n<u>Interpretation of <a href=\"https://github.com/danielmarschall/oidplus/blob/master/doc/oidplus_custom_guid.md\">OIDplus 2.0 Custom UUID</a></u>\n\n";
							echo sprintf("%-32s %s\n", "System ID:", "[0x$oidplus_systemid_hex] ".$oidplus_systemid_int);
							echo sprintf("%-32s %s\n", "Creation time:", "[0x$oidplus_creation_hex] ".($oidplus_creation_unknown ? 'Unknown' : date('Y-m-d', $oidplus_creation_int*24*60*60))); /**@phpstan-ignore-line*/
							echo sprintf("%-32s %s\n", "Reserved:", "[0x$oidplus_reserved_hex]");
							echo sprintf("%-32s %s\n", "Namespace:", "[0x$oidplus_namespace_hex] $oidplus_namespace_int=System");
							echo sprintf("%-32s %s\n", "Data (empty string hash):", "[0x$oidplus_data_hex] SHA1('') = ????????????????????????????$oidplus_data_hex");
						}
						else if (($oidplus_namespace_int == 1) && $oidplus_creation_unknown) {
							// User GUID, e.g. 6e932dd7-0000-8000-8001-2938f50e857e (User), 6e932dd7-0000-8000-8001-000000000000 (Admin)
							echo "\n<u>Interpretation of <a href=\"https://github.com/danielmarschall/oidplus/blob/master/doc/oidplus_custom_guid.md\">OIDplus 2.0 Custom UUID</a></u>\n\n";
							echo sprintf("%-32s %s\n", "System ID:", "[0x$oidplus_systemid_hex] ".$oidplus_systemid_int);
							echo sprintf("%-32s %s\n", "Creation time:", "[0x$oidplus_creation_hex] ".($oidplus_creation_unknown ? 'Unknown' : date('Y-m-d', $oidplus_creation_int*24*60*60)));  /**@phpstan-ignore-line*/
							echo sprintf("%-32s %s\n", "Reserved:", "[0x$oidplus_reserved_hex]");
							echo sprintf("%-32s %s\n", "Namespace:", "[0x$oidplus_namespace_hex] $oidplus_namespace_int=User");
							if ($oidplus_data_int == 0) {
								echo sprintf("%-32s %s\n", "Data (Username):", "[0x$oidplus_data_hex] 0=Admin");
							} else {
								echo sprintf("%-32s %s\n", "Data (Username):", "[0x$oidplus_data_hex] SHA1(UserName) = ????????????????????????????$oidplus_data_hex");
							}
						}
						else if (($oidplus_namespace_int == 2)/* && $oidplus_creation_valid*/) {
							// Log entry GUID, e.g. 6e932dd7-458c-8000-8002-0000000004d2
							echo "\n<u>Interpretation of <a href=\"https://github.com/danielmarschall/oidplus/blob/master/doc/oidplus_custom_guid.md\">OIDplus 2.0 Custom UUID</a></u>\n\n";
							echo sprintf("%-32s %s\n", "System ID:", "[0x$oidplus_systemid_hex] ".$oidplus_systemid_int);
							echo sprintf("%-32s %s\n", "Creation time:", "[0x$oidplus_creation_hex] ".($oidplus_creation_unknown ? 'Unknown' : date('Y-m-d', $oidplus_creation_int*24*60*60)));
							echo sprintf("%-32s %s\n", "Reserved:", "[0x$oidplus_reserved_hex]");
							echo sprintf("%-32s %s\n", "Namespace:", "[0x$oidplus_namespace_hex] $oidplus_namespace_int=Log Entry");
							echo sprintf("%-32s %s\n", "Data (Sequence number):", "[0x$oidplus_data_hex] $oidplus_data_int");
						}
						else if (($oidplus_namespace_int == 3) && $oidplus_creation_unknown) {
							// Configuration entry GUID, e.g. 6e932dd7-0000-8000-8003-f14dda42862a
							echo "\n<u>Interpretation of <a href=\"https://github.com/danielmarschall/oidplus/blob/master/doc/oidplus_custom_guid.md\">OIDplus 2.0 Custom UUID</a></u>\n\n";
							echo sprintf("%-32s %s\n", "System ID:", "[0x$oidplus_systemid_hex] ".$oidplus_systemid_int);
							echo sprintf("%-32s %s\n", "Creation time:", "[0x$oidplus_creation_hex] ".($oidplus_creation_unknown ? 'Unknown' : date('Y-m-d', $oidplus_creation_int*24*60*60))); /**@phpstan-ignore-line*/
							echo sprintf("%-32s %s\n", "Reserved:", "[0x$oidplus_reserved_hex]");
							echo sprintf("%-32s %s\n", "Namespace:", "[0x$oidplus_namespace_hex] $oidplus_namespace_int=Configuration Entry");
							echo sprintf("%-32s %s\n", "Data (Setting name hash):", "[0x$oidplus_data_hex] SHA1(SettingName) = ????????????????????????????$oidplus_data_hex");
						}
						else if ($oidplus_namespace_int == 4) {
							// ASN.1 Alpahnumeric identifier GUID, e.g. 6e932dd7-0000-8000-8004-208ded8a3f8f
							echo "\n<u>Interpretation of <a href=\"https://github.com/danielmarschall/oidplus/blob/master/doc/oidplus_custom_guid.md\">OIDplus 2.0 Custom UUID</a></u>\n\n";
							echo sprintf("%-32s %s\n", "System ID:", "[0x$oidplus_systemid_hex] ".$oidplus_systemid_int);
							echo sprintf("%-32s %s\n", "Creation time:", "[0x$oidplus_creation_hex] ".($oidplus_creation_unknown ? 'Unknown' : date('Y-m-d', $oidplus_creation_int*24*60*60)));
							echo sprintf("%-32s %s\n", "Reserved:", "[0x$oidplus_reserved_hex]");
							echo sprintf("%-32s %s\n", "Namespace:", "[0x$oidplus_namespace_hex] $oidplus_namespace_int=ASN.1 Alphanumeric ID");
							$oidplus_data_24hi_hex = substr($oidplus_data_hex, 0, 6);
							$oidplus_data_24lo_hex = substr($oidplus_data_hex, 6, 6);
							echo sprintf("%-32s %s\n", "Data (OID hash):", "[0x$oidplus_data_24hi_hex] SHA1(OID) = ????????????????????????????$oidplus_data_24hi_hex");
							echo sprintf("%-32s %s\n", "Data (Name hash):", "[0x$oidplus_data_24lo_hex] SHA1(AlphaNumId) = ????????????????????????????$oidplus_data_24lo_hex");
						}
						else if ($oidplus_namespace_int == 5) {
							// Unicode Label entry GUID, e.g. 6e932dd7-0000-8000-8005-208dedaf9a96
							echo "\n<u>Interpretation of <a href=\"https://github.com/danielmarschall/oidplus/blob/master/doc/oidplus_custom_guid.md\">OIDplus 2.0 Custom UUID</a></u>\n\n";
							echo sprintf("%-32s %s\n", "System ID:", "[0x$oidplus_systemid_hex] ".$oidplus_systemid_int);
							echo sprintf("%-32s %s\n", "Creation time:", "[0x$oidplus_creation_hex] ".($oidplus_creation_unknown ? 'Unknown' : date('Y-m-d', $oidplus_creation_int*24*60*60)));
							echo sprintf("%-32s %s\n", "Reserved:", "[0x$oidplus_reserved_hex]");
							echo sprintf("%-32s %s\n", "Namespace:", "[0x$oidplus_namespace_hex] $oidplus_namespace_int=Unicode Label");
							$oidplus_data_24hi_hex = substr($oidplus_data_hex, 0, 6);
							$oidplus_data_24lo_hex = substr($oidplus_data_hex, 6, 6);
							echo sprintf("%-32s %s\n", "Data (OID hash):", "[0x$oidplus_data_24hi_hex] SHA1(OID) = ????????????????????????????$oidplus_data_24hi_hex");
							echo sprintf("%-32s %s\n", "Data (Name hash):", "[0x$oidplus_data_24lo_hex] SHA1(UnicodeLabel) = ????????????????????????????$oidplus_data_24lo_hex");
						}
						else if (($oidplus_namespace_int >= 6) && ($oidplus_namespace_int <= 0xF)) {
							// System reserved
							echo "\n<u>Interpretation of <a href=\"https://github.com/danielmarschall/oidplus/blob/master/doc/oidplus_custom_guid.md\">OIDplus 2.0 Custom UUID</a></u>\n\n";
							echo sprintf("%-32s %s\n", "System ID:", "[0x$oidplus_systemid_hex] ".$oidplus_systemid_int);
							echo sprintf("%-32s %s\n", "Creation time:", "[0x$oidplus_creation_hex] ".($oidplus_creation_unknown ? 'Unknown' : date('Y-m-d', $oidplus_creation_int*24*60*60)));
							echo sprintf("%-32s %s\n", "Reserved:", "[0x$oidplus_reserved_hex]");
							echo sprintf("%-32s %s\n", "Namespace:", "[0x$oidplus_namespace_hex] $oidplus_namespace_int=Unknown (System Reserved)");
							echo sprintf("%-32s %s\n", "Data (Setting name hash):", "[0x$oidplus_data_hex] Unknown");
						}
						else if ($oidplus_namespace_int > 0xF) {
							// Information Object GUID, e.g. 6e932dd7-458c-8000-b9e9-c1e3894d1105
							$known_objecttype_plugins = array(
								// Latest list here: https://github.com/danielmarschall/oidplus/blob/master/doc/oidplus_custom_guid.md
								'1.3.6.1.4.1.37476.2.5.2.4.8.1' => 'doi (ViaThinkSoft plugin)',
								'1.3.6.1.4.1.37476.2.5.2.4.8.2' => 'gs1 (ViaThinkSoft plugin)',
								'1.3.6.1.4.1.37476.2.5.2.4.8.3' => 'guid (ViaThinkSoft plugin)',
								'1.3.6.1.4.1.37476.2.5.2.4.8.4' => 'ipv4 (ViaThinkSoft plugin)',
								'1.3.6.1.4.1.37476.2.5.2.4.8.5' => 'ipv6 (ViaThinkSoft plugin)',
								'1.3.6.1.4.1.37476.2.5.2.4.8.6' => 'java (ViaThinkSoft plugin)',
								'1.3.6.1.4.1.37476.2.5.2.4.8.7' => 'oid (ViaThinkSoft plugin)',
								'1.3.6.1.4.1.37476.2.5.2.4.8.8' => 'other (ViaThinkSoft plugin)',
								'1.3.6.1.4.1.37476.2.5.2.4.8.9' => 'domain (ViaThinkSoft plugin)',
								'1.3.6.1.4.1.37476.2.5.2.4.8.10' => 'fourcc (ViaThinkSoft plugin)',
								'1.3.6.1.4.1.37476.2.5.2.4.8.11' => 'aid (ViaThinkSoft plugin)',
								'1.3.6.1.4.1.37476.2.5.2.4.8.12' => 'php (ViaThinkSoft plugin)',
								'1.3.6.1.4.1.37476.2.5.2.4.8.13' => 'mac (ViaThinkSoft plugin)',
								'1.3.6.1.4.1.37553.8.1.8.8.53354196964.27255728261' => 'circuit (Frdlweb plugin)',
								'1.3.6.1.4.1.37476.9000.108.19361.856' => 'ns (Frdlweb plugin)',
								'1.3.6.1.4.1.37553.8.1.8.8.53354196964.32927' => 'pen (Frdlweb plugin)',
								'1.3.6.1.4.1.37553.8.1.8.8.53354196964.39870' => 'uri (Frdlweb plugin)',
								'1.3.6.1.4.1.37553.8.1.8.8.53354196964.1958965295' => 'web+fan (Frdlweb plugin)'
							);
							$namespace_desc = 'Unknown object type';
							foreach ($known_objecttype_plugins as $oid => $name) {
								if ((hexdec(substr(sha1($oid), -4)) & 0x3fff) == $oidplus_namespace_int) $namespace_desc = "$oid = $name";
							}
							echo "\n<u>Interpretation of <a href=\"https://github.com/danielmarschall/oidplus/blob/master/doc/oidplus_custom_guid.md\">OIDplus 2.0 Custom UUID</a></u>\n\n";
							echo sprintf("%-32s %s\n", "System ID:", "[0x$oidplus_systemid_hex] ".$oidplus_systemid_int);
							echo sprintf("%-32s %s\n", "Creation time:", "[0x$oidplus_creation_hex] ".($oidplus_creation_unknown ? 'Unknown' : date('Y-m-d', $oidplus_creation_int*24*60*60)));
							echo sprintf("%-32s %s\n", "Reserved:", "[0x$oidplus_reserved_hex]");
							echo sprintf("%-32s %s\n", "Namespace (Obj.type OID hash):", "[0x$oidplus_namespace_hex] $namespace_desc");
							echo sprintf("%-32s %s\n", "Data (Object name hash):", "[0x$oidplus_data_hex] SHA1(ObjectName) = ????????????????????????????$oidplus_data_hex");
						}
					}

					// END: OIDplus 2.0 Custom UUID Interpretation

					break;
				default:
					echo sprintf("%-32s %s\n", "Version:", "[0x".dechex($version)."] Unknown");
					break;
			}

			break;
		case 2:
			// TODO: Show byte order: 00112233-4455-6677-8899-aabbccddeeff => 33 22 11 00 55 44 77 66 88 99 aa bb cc dd ee ff

			// TODO: Is there any scheme in that legacy Microsoft GUIDs?
			echo sprintf("%-32s %s\n", "Variant:", "[0b110] Reserved for Microsoft Corporation");
			break;
		case 3:
			echo sprintf("%-32s %s\n", "Variant:", "[0b111] Reserved for future use");
			break;
	}

	if (!$echo) {
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	} else {
		return true;
	}
}

function uuid_canonize($uuid) {
	if (!uuid_valid($uuid)) return false;
	return oid_to_uuid(uuid_to_oid($uuid));
}

function oid_to_uuid($oid) {
	// TODO: Also support Non-2.25 base UUID-to-OID
	if (!is_uuid_oid($oid,true)) return false;

	if (substr($oid,0,1) == '.') {
		$oid = substr($oid, 1);
	}
	$ary = explode('.', $oid);

	if (!isset($ary[2])) return false;

	$val = $ary[2];

	$x = gmp_init($val, 10);
	$y = gmp_strval($x, 16);
	$y = str_pad($y, 32, "0", STR_PAD_LEFT);
	return substr($y,  0, 8).'-'.
	       substr($y,  8, 4).'-'.
	       substr($y, 12, 4).'-'.
	       substr($y, 16, 4).'-'.
	       substr($y, 20, 12);
}

function is_uuid_oid($oid, $only_allow_root=false) {
	// TODO: Also support Non-2.25 base UUID-to-OID
	if (substr($oid,0,1) == '.') $oid = substr($oid, 1); // remove leading dot

	$ary = explode('.', $oid);

	if ($only_allow_root) {
		if (count($ary) != 3) return false;
	} else {
		if (count($ary) < 3) return false;
	}

	if ($ary[0] != '2') return false;
	if ($ary[1] != '25') return false;
	for ($i=2; $i<count($ary); $i++) {
		$v = $ary[$i];
		if (!is_numeric($v)) return false;
		if ($i == 2) {
			// Must be in the range of 128 bit UUID
			$test = gmp_init($v, 10);
			if (strlen(gmp_strval($test, 16)) > 32) return false;
		}
		if ($v < 0) return false;
	}

	return true;
}

function uuid_to_oid($uuid, $base='2.25') {
	if (!uuid_valid($uuid)) return false;
	#$base = oid_sanitize($base);

	$uuid = str_replace(array('-', '{', '}'), '', $uuid);

	// Information about Microsoft and Waterjuice UUID-OID: https://waterjuiceweb.wordpress.com/2019/09/24/guids-to-oids/

	if ($base == '2.25') {
		$x = gmp_init($uuid, 16);
		return $base.'.'.gmp_strval($x, 10);
	} else if ($base == '1.2.840.113556.1.8000.2554') {
		return $base.'.'.
			gmp_strval(gmp_init(substr($uuid,0,4),16),10).'.'.
			gmp_strval(gmp_init(substr($uuid,4,4),16),10).'.'.
			gmp_strval(gmp_init(substr($uuid,8,4),16),10).'.'.
			gmp_strval(gmp_init(substr($uuid,12,4),16),10).'.'.
			gmp_strval(gmp_init(substr($uuid,16,4),16),10).'.'.
			gmp_strval(gmp_init(substr($uuid,20,6),16),10).'.'.
			gmp_strval(gmp_init(substr($uuid,26,6),16),10);
	} else if ($base == '1.3.6.1.4.1.54392.1') {
		return $base.'.'.
			gmp_strval(gmp_init(substr($uuid,0,16),16),10).'.'.
			gmp_strval(gmp_init(substr($uuid,16,16),16),10);
	} else if ($base == '1.3.6.1.4.1.54392.2') {
		return $base.'.'.
			gmp_strval(gmp_init(substr($uuid,0,8),16),10).'.'.
			gmp_strval(gmp_init(substr($uuid,8,8),16),10).'.'.
			gmp_strval(gmp_init(substr($uuid,16,8),16),10).'.'.
			gmp_strval(gmp_init(substr($uuid,24,8),16),10);
	} else if ($base == '1.3.6.1.4.1.54392.3') {
		return $base.'.'.
			gmp_strval(gmp_init(substr($uuid,0,4),16),10).'.'.
			gmp_strval(gmp_init(substr($uuid,4,4),16),10).'.'.
			gmp_strval(gmp_init(substr($uuid,8,4),16),10).'.'.
			gmp_strval(gmp_init(substr($uuid,12,4),16),10).'.'.
			gmp_strval(gmp_init(substr($uuid,16,4),16),10).'.'.
			gmp_strval(gmp_init(substr($uuid,20,4),16),10).'.'.
			gmp_strval(gmp_init(substr($uuid,24,4),16),10).'.'.
			gmp_strval(gmp_init(substr($uuid,28,4),16),10);
	} else {
		throw new Exception("Unsupported UUID-to-OID base");
	}
}

function uuid_numeric_value($uuid) {
	$oid = uuid_to_oid($uuid);
	if (!$oid) return false;
	return substr($oid, strlen('2.25.'));
}

function uuid_c_syntax($uuid) {
	$uuid = str_replace('{', '', $uuid);
	return '{ 0x' . substr($uuid, 0, 8) .
		', 0x' . substr($uuid, 9, 4) .
		', 0x' . substr($uuid, 14, 4) .
		', { 0x' . substr($uuid, 19, 2).
		', 0x' . substr($uuid, 21, 2) .
		', 0x' . substr($uuid, 24, 2) .
		', 0x' . substr($uuid, 26, 2) .
		', 0x' . substr($uuid, 28, 2) .
		', 0x' . substr($uuid, 30, 2) .
		', 0x' . substr($uuid, 32, 2) .
		', 0x' . substr($uuid, 34, 2) . ' } }';
}

function gen_uuid($prefer_mac_address_based = true) {
	$uuid = $prefer_mac_address_based ? gen_uuid_reordered()/*UUIDv6*/ : false;
	if ($uuid === false) $uuid = gen_uuid_unix_epoch()/*UUIDv7*/;
	return $uuid;
}

# --------------------------------------
// Variant 1, Version 1 (Time based) UUID
# --------------------------------------

function gen_uuid_v1() {
	return gen_uuid_timebased();
}
function gen_uuid_timebased($force_php_implementation=false) {
	# On Debian: apt-get install php-uuid
	# extension_loaded('uuid')
	if (!$force_php_implementation && function_exists('uuid_create')) {
		# OSSP uuid extension like seen in php5-uuid at Debian 8
		/*
		$x = uuid_create($context);
		uuid_make($context, UUID_MAKE_V1);
		uuid_export($context, UUID_FMT_STR, $uuid);
		return trim($uuid);
		*/

		# PECL uuid extension like seen in php-uuid at Debian 9
		return trim(uuid_create(UUID_TYPE_TIME));
	}

	# On Debian: apt-get install uuid-runtime
	if (!$force_php_implementation && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
		$out = array();
		$ec = -1;
		exec('uuidgen -t 2>/dev/null', $out, $ec);
		if ($ec == 0) return trim($out[0]);
	}

	# If we hadn't any success yet, then implement the time based generation routine ourselves!
	# Based on https://github.com/fredriklindberg/class.uuid.php/blob/master/class.uuid.php

	$uuid = array(
		'time_low' => 0,		/* 32-bit */
		'time_mid' => 0,		/* 16-bit */
		'time_hi' => 0,			/* 16-bit */
		'clock_seq_hi' => 0,		/*  8-bit */
		'clock_seq_low' => 0,		/*  8-bit */
		'node' => array()		/* 48-bit */
	);

	/*
	 * Get current time in 100 ns intervals. The magic value
	 * is the offset between UNIX epoch and the UUID UTC
	 * time base October 15, 1582.
	 */
	if (time_nanosleep(0,100) !== true) usleep(1); // Wait 100ns, to make sure that the time part changes if multiple UUIDs are generated
	$tp = gettimeofday();
	if (PHP_INT_SIZE == 4) {
		$tp['sec'] = gmp_init($tp['sec'],10);
		$tp['usec'] = gmp_init($tp['usec'],10);
		$time = gmp_add(gmp_add(gmp_mul($tp['sec'], gmp_init('10000000',10)),gmp_mul($tp['usec'], gmp_init('10',10))),gmp_init('01B21DD213814000',16));
		$uuid['time_low'] = gmp_and($time, gmp_init('ffffffff',16));
		$high = gmp_shiftr($time,32);
		$uuid['time_mid'] = gmp_and($high, gmp_init('ffff',16));
		$uuid['time_hi'] = gmp_intval(gmp_and(gmp_shiftr($high,16),gmp_init('fff',16))) | (1/*TimeBased*/ << 12);
	} else {
		$time = ($tp['sec'] * 10000000) + ($tp['usec'] * 10) + 0x01B21DD213814000;
		$uuid['time_low'] = $time & 0xffffffff;
		/* Work around PHP 32-bit bit-operation limits */
		$high = intval($time / 0xffffffff);
		$uuid['time_mid'] = $high & 0xffff;
		$uuid['time_hi'] = (($high >> 16) & 0xfff) | (1/*TimeBased*/ << 12);
	}

	/*
	 * We don't support saved state information and generate
	 * a random clock sequence each time.
	 */
	$uuid['clock_seq_hi'] = _random_int(0, 255) & 0b00111111 | 0b10000000; // set variant to 0b10__ (RFC 4122)
	$uuid['clock_seq_low'] = _random_int(0, 255);

	/*
	 * Node should be set to the 48-bit IEEE node identifier
	 */
	$mac = get_mac_address();
	if ($mac) {
		$node = str_replace('-','',str_replace(':','',$mac));
		for ($i = 0; $i < 6; $i++) {
			$uuid['node'][$i] = hexdec(substr($node, $i*2, 2));
		}
	} else {
		// If we cannot get a MAC address, then generate a random AAI
		// RFC 4122 requires the multicast bit to be set, to make sure
		// that a UUID from a system with network card never conflicts
		// with a UUID from a system without network ard.
		// We are additionally defining the other 3 bits as AAI,
		// to avoid that we are misusing the CID or OUI from other vendors
		// if we would create multicast ELI (based on CID) or EUI (based on OUI).
		$uuid['node'] = explode('-', gen_aai(48, true/*Multicast*/));
		$uuid['node'] = array_map('hexdec', $uuid['node']);
	}

	/*
	 * Now output the UUID
	 */
	return sprintf(
		'%08x-%04x-%04x-%02x%02x-%02x%02x%02x%02x%02x%02x',
		($uuid['time_low']), ($uuid['time_mid']), ($uuid['time_hi']),
		$uuid['clock_seq_hi'], $uuid['clock_seq_low'],
		$uuid['node'][0], $uuid['node'][1], $uuid['node'][2],
		$uuid['node'][3], $uuid['node'][4], $uuid['node'][5]);
}

# --------------------------------------
// Variant 1, Version 2 (DCE Security) UUID
# --------------------------------------

define('DCE_DOMAIN_PERSON', 0);
define('DCE_DOMAIN_GROUP', 1);
define('DCE_DOMAIN_ORG', 2);
function gen_uuid_v2($domain, $id) {
	return gen_uuid_dce($domain, $id);
}
function gen_uuid_dce($domain, $id) {
	if (($domain ?? '') === '') throw new Exception("Domain ID missing");
	if (!is_numeric($domain)) throw new Exception("Invalid Domain ID");
	if (($domain < 0) || ($domain > 0xFF)) throw new Exception("Domain ID must be in range 0..255");

	if (($id ?? '') === '') throw new Exception("ID value missing");
	if (!is_numeric($id)) throw new Exception("Invalid ID value");
	if (($id < 0) || ($id > 0xFFFFFFFF)) throw new Exception("ID value must be in range 0..4294967295");

	# Start with a version 1 UUID
	$uuid = gen_uuid_timebased();

	# Add Domain Number
	$uuid = str_pad(dechex($id), 8, '0', STR_PAD_LEFT) . substr($uuid, 8);

	# Add Domain (this overwrites part of the clock sequence)
	$uuid = substr($uuid,0,21) . str_pad(dechex($domain), 2, '0', STR_PAD_LEFT) . substr($uuid, 23);

	# Change version to 2
	$uuid[14] = '2';

	return $uuid;
}

# --------------------------------------
// Variant 1, Version 3 (MD5 name based) UUID
# --------------------------------------

function gen_uuid_v3($namespace_uuid, $name) {
	return gen_uuid_md5_namebased($namespace_uuid, $name);
}
function gen_uuid_md5_namebased($namespace_uuid, $name) {
	if (($namespace_uuid ?? '') === '') throw new Exception("Namespace UUID missing");
	if (!uuid_valid($namespace_uuid)) throw new Exception("Invalid namespace UUID '$namespace_uuid'");

	$namespace_uuid = uuid_canonize($namespace_uuid);
	$namespace_uuid = str_replace('-', '', $namespace_uuid);
	$namespace_uuid = hex2bin($namespace_uuid);

	$hash = md5($namespace_uuid.$name);
	$hash[12] = '3'; // Set version: 3 = MD5
	$hash[16] = dechex(hexdec($hash[16]) & 0b0011 | 0b1000); // Set variant to "10xx" (RFC4122)

	return substr($hash,  0, 8).'-'.
	       substr($hash,  8, 4).'-'.
	       substr($hash, 12, 4).'-'.
	       substr($hash, 16, 4).'-'.
	       substr($hash, 20, 12);
}

# --------------------------------------
// Variant 1, Version 4 (Random) UUID
# --------------------------------------

function gen_uuid_v4() {
	return gen_uuid_random();
}
function gen_uuid_random() {
	# On Windows: Requires
	#    extension_dir = "C:\php-8.0.3-nts-Win32-vs16-x64\ext"
	#    extension=com_dotnet
	if (function_exists('com_create_guid')) {
		$uuid = trim(com_create_guid(), '{}');
		if (uuid_version($uuid) === '4') { // <-- just to make 100% sure that Windows's CoCreateGuid() did output UUIDv4
			return strtolower($uuid);
		}
	}

	# On Debian: apt-get install php-uuid
	# extension_loaded('uuid')
	if (function_exists('uuid_create')) {
		# OSSP uuid extension like seen in php5-uuid at Debian 8
		/*
		$x = uuid_create($context);
		uuid_make($context, UUID_MAKE_V4);
		uuid_export($context, UUID_FMT_STR, $uuid);
		return trim($uuid);
		*/

		# PECL uuid extension like seen in php-uuid at Debian 9
		return trim(uuid_create(UUID_TYPE_RANDOM));
	}

	if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
		# On Debian Jessie: UUID V4 (Random)
		if (file_exists($uuidv4_file = '/proc/sys/kernel/random/uuid')) {
			$uuid = file_get_contents($uuidv4_file);
			if (uuid_version($uuid) === '4') { // <-- just to make 100% sure that it did output UUIDv4
				return $uuid;
			}
		}

		# On Debian: apt-get install uuid-runtime
		$out = array();
		$ec = -1;
		exec('uuidgen -r 2>/dev/null', $out, $ec);
		if ($ec == 0) return trim($out[0]);
	}

	# Make the UUID by ourselves

	if (function_exists('openssl_random_pseudo_bytes')) {
		// Source: https://www.php.net/manual/en/function.com-create-guid.php#119168
		$data = openssl_random_pseudo_bytes(16);
		$data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	} else {
		// Source: http://rogerstringer.com/2013/11/15/generate-uuids-php
		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			_random_int( 0, 0xffff ), _random_int( 0, 0xffff ),
			_random_int( 0, 0xffff ),
			_random_int( 0, 0x0fff ) | 0x4000,
			_random_int( 0, 0x3fff ) | 0x8000,
			_random_int( 0, 0xffff ), _random_int( 0, 0xffff ), _random_int( 0, 0xffff )
		);
	}
}

# --------------------------------------
// Variant 1, Version 5 (SHA1 name based) UUID
# --------------------------------------

function gen_uuid_v5($namespace_uuid, $name) {
	return gen_uuid_sha1_namebased($namespace_uuid, $name);
}
function gen_uuid_sha1_namebased($namespace_uuid, $name) {
	if (($namespace_uuid ?? '') === '') throw new Exception("Namespace UUID missing");
	if (!uuid_valid($namespace_uuid)) throw new Exception("Invalid namespace UUID '$namespace_uuid'");

	$namespace_uuid = str_replace('-', '', $namespace_uuid);
	$namespace_uuid = hex2bin($namespace_uuid);

	$hash = sha1($namespace_uuid.$name);
	$hash[12] = '5'; // Set version: 5 = SHA1
	$hash[16] = dechex(hexdec($hash[16]) & 0b0011 | 0b1000); // Set variant to "0b10__" (RFC4122/DCE1.1)

	return substr($hash,  0, 8).'-'.
	       substr($hash,  8, 4).'-'.
	       substr($hash, 12, 4).'-'.
	       substr($hash, 16, 4).'-'.
	       substr($hash, 20, 12);
}

# --------------------------------------
// Variant 1, Version 6 (Reordered) UUID
# --------------------------------------

function gen_uuid_v6() {
	return gen_uuid_reordered();
}
function gen_uuid_reordered() {
	// Start with a UUIDv1
	$uuid = gen_uuid_timebased();

	// Convert to UUIDv6
	return uuid1_to_uuid6($uuid);
}
function uuid6_to_uuid1($hex) {
	$hex = uuid_canonize($hex);
	if ($hex === false) return false;
	$hex = preg_replace('@[^0-9A-F]@i', '', $hex);
	$hex = substr($hex, 7, 5).
	       substr($hex, 13, 3).
	       substr($hex, 3, 4).
	       '1' . substr($hex, 0, 3).
	       substr($hex, 16);
	return substr($hex,  0, 8).'-'.
	       substr($hex,  8, 4).'-'.
	       substr($hex, 12, 4).'-'.
	       substr($hex, 16, 4).'-'.
	       substr($hex, 20, 12);
}
function uuid1_to_uuid6($hex) {
	$hex = uuid_canonize($hex);
	if ($hex === false) return false;
	$hex = preg_replace('@[^0-9A-F]@i', '', $hex);
	$hex = substr($hex, 13, 3).
	       substr($hex, 8, 4).
	       substr($hex, 0, 5).
	       '6' . substr($hex, 5, 3).
	       substr($hex, 16);
	return substr($hex,  0, 8).'-'.
	       substr($hex,  8, 4).'-'.
	       substr($hex, 12, 4).'-'.
	       substr($hex, 16, 4).'-'.
	       substr($hex, 20, 12);
}

# --------------------------------------
// Variant 1, Version 7 (Unix Epoch) UUID
# --------------------------------------

function gen_uuid_v7(int $num_ms_frac_bits=12) {
	return gen_uuid_unix_epoch($num_ms_frac_bits);
}
function gen_uuid_unix_epoch(int $num_ms_frac_bits=12) {
	$uuid_nibbles = '';

	// Add the timestamp (milliseconds Unix)
	if (function_exists('gmp_init')) {
		list($ms,$sec) = explode(' ', microtime(false));
		$sec = gmp_init($sec, 10);
		$ms = gmp_init(substr($ms,2,3), 10);
		$unix_ts = gmp_strval(gmp_add(gmp_mul($sec, '1000'), $ms),16);
	} else {
		$unix_ts = dechex((int)ceil(microtime(true)*1000));
	}
	$unix_ts = str_pad($unix_ts, 12, '0', STR_PAD_LEFT);
	$uuid_nibbles = $unix_ts;

	// Version = 7
	$uuid_nibbles .= '7';

	// Optional: millisecond fraction (max 12 bits)
	if (($num_ms_frac_bits < 0) || ($num_ms_frac_bits > 12)) throw new Exception("Invalid msec frac bits (must be 0..12)");
	$resolution_ns = 1000000 / pow(2,$num_ms_frac_bits);
	if ($num_ms_frac_bits > 0) {
		$seconds_fraction = (float)explode(' ',microtime(false))[0]; // <sec=0>,<msec>

		$ms_fraction = $seconds_fraction * 1000; // <msec>,<us>
		$ms_fraction -= floor($ms_fraction); // <msec=0>,<us>

		$ns_fraction = $ms_fraction * 1000000; // <ns>
		$val = (int)ceil($ns_fraction / $resolution_ns);

		// Currently, for the output we only allow frac bits 0, 4, 8, 12 (0-3 nibbles),
		// since UUIDs are usually sorted in their hex notation, and one of the main
		// reasons for using the sub-millisecond fractions it to increase monotonicity
		$num_nibbles = (int)ceil($num_ms_frac_bits/4);
		$uuid_nibbles .= str_pad(dechex($val), $num_nibbles, '0', STR_PAD_LEFT);
	}

	// TODO Not implemented: Optional counter (to be defined as parameter to this method)
	// The counter bits need to be spread before and after the variant bits

	// Fill with random bits (and the variant bits)
	$uuid = gen_uuid_random();
	$uuid = str_replace('-', '', $uuid);
	for ($i=0; $i<strlen($uuid_nibbles); $i++) $uuid[$i] = $uuid_nibbles[$i];

	// Wait to make sure that the time part changes if multiple UUIDs are generated
	if (time_nanosleep(0,(int)ceil($resolution_ns)) !== true) usleep((int)ceil($resolution_ns/1000));

	// Output
	return substr($uuid,  0, 8).'-'.
	       substr($uuid,  8, 4).'-'.
	       substr($uuid, 12, 4).'-'.
	       substr($uuid, 16, 4).'-'.
	       substr($uuid, 20, 12);
}

# --------------------------------------
// Variant 1, Version 8 (Custom) UUID
# --------------------------------------

function gen_uuid_v8($block1_32bit, $block2_16bit, $block3_12bit, $block4_14bit, $block5_48bit) {
	return gen_uuid_custom($block1_32bit, $block2_16bit, $block3_12bit, $block4_14bit, $block5_48bit);
}
function gen_uuid_custom($block1_32bit, $block2_16bit, $block3_12bit, $block4_14bit, $block5_48bit) {
	if (preg_replace('@[0-9A-F]@i', '', $block1_32bit) != '') throw new Exception("Invalid data for block 1. Must be hex input");
	if (preg_replace('@[0-9A-F]@i', '', $block2_16bit) != '') throw new Exception("Invalid data for block 2. Must be hex input");
	if (preg_replace('@[0-9A-F]@i', '', $block3_12bit) != '') throw new Exception("Invalid data for block 3. Must be hex input");
	if (preg_replace('@[0-9A-F]@i', '', $block4_14bit) != '') throw new Exception("Invalid data for block 4. Must be hex input");
	if (preg_replace('@[0-9A-F]@i', '', $block5_48bit) != '') throw new Exception("Invalid data for block 5. Must be hex input");

	$block1 = str_pad(substr($block1_32bit, -8),  8, '0', STR_PAD_LEFT);
	$block2 = str_pad(substr($block2_16bit, -4),  4, '0', STR_PAD_LEFT);
	$block3 = str_pad(substr($block3_12bit, -4),  4, '0', STR_PAD_LEFT);
	$block4 = str_pad(substr($block4_14bit, -4),  4, '0', STR_PAD_LEFT);
	$block5 = str_pad(substr($block5_48bit,-12), 12, '0', STR_PAD_LEFT);

	$block3[0] = '8'; // Version 8 = Custom
	$block4[0] = dechex(hexdec($block4[0]) & 0b0011 | 0b1000); // Variant 0b10__ = RFC4122

	return strtolower($block1.'-'.$block2.'-'.$block3.'-'.$block4.'-'.$block5);
}

function gen_uuid_v8_namebased($hash_algo, $namespace_uuid, $name) {
	if (($hash_algo ?? '') === '') throw new Exception("Hash algorithm argument missing");

	if (($namespace_uuid ?? '') === '') throw new Exception("Namespace UUID missing");
	if (!uuid_valid($namespace_uuid)) throw new Exception("Invalid namespace UUID '$namespace_uuid'");

	$uuid1 = uuid_valid($hash_algo) ? hex2bin(str_replace('-','',uuid_canonize($hash_algo))) : ''; // old "hash space" concept (dropped in Internet Draft 12)
	$uuid2 = hex2bin(str_replace('-','',uuid_canonize($namespace_uuid)));
	$payload = $uuid1 . $uuid2 . $name;

	if (uuid_valid($hash_algo)) {
		if (uuid_equal($hash_algo, '59031ca3-fbdb-47fb-9f6c-0f30e2e83145')) $hash_algo = 'sha224';
		if (uuid_equal($hash_algo, '3fb32780-953c-4464-9cfd-e85dbbe9843d')) $hash_algo = 'sha256';
		if (uuid_equal($hash_algo, 'e6800581-f333-484b-8778-601ff2b58da8')) $hash_algo = 'sha384';
		if (uuid_equal($hash_algo, '0fde22f2-e7ba-4fd1-9753-9c2ea88fa3f9')) $hash_algo = 'sha512';
		if (uuid_equal($hash_algo, '003c2038-c4fe-4b95-a672-0c26c1b79542')) $hash_algo = 'sha512/224';
		if (uuid_equal($hash_algo, '9475ad00-3769-4c07-9642-5e7383732306')) $hash_algo = 'sha512/256';
		if (uuid_equal($hash_algo, '9768761f-ac5a-419e-a180-7ca239e8025a')) $hash_algo = 'sha3-224';
		if (uuid_equal($hash_algo, '2034d66b-4047-4553-8f80-70e593176877')) $hash_algo = 'sha3-256';
		if (uuid_equal($hash_algo, '872fb339-2636-4bdd-bda6-b6dc2a82b1b3')) $hash_algo = 'sha3-384';
		if (uuid_equal($hash_algo, 'a4920a5d-a8a6-426c-8d14-a6cafbe64c7b')) $hash_algo = 'sha3-512';
		if (uuid_equal($hash_algo, '7ea218f6-629a-425f-9f88-7439d63296bb')) $hash_algo = 'shake128';
		if (uuid_equal($hash_algo, '2e7fc6a4-2919-4edc-b0ba-7d7062ce4f0a')) $hash_algo = 'shake256';
	}

	if ($hash_algo == 'shake128') $hash = shake128($payload, 16/*min. required bytes*/, false);
	else if ($hash_algo == 'shake256') $hash = shake256($payload, 16/*min. required bytes*/, false);
	else $hash = hash($hash_algo, $payload, false);

	if ($hash == null) {
		throw new Exception("Unknown Hash Algorithm $hash_algo");
	}

	$hash = str_pad($hash, 32, '0', STR_PAD_RIGHT); // fill short hashes with zeros to the right

	$hash[12] = '8'; // Set version: 8 = Custom
	$hash[16] = dechex(hexdec($hash[16]) & 0b0011 | 0b1000); // Set variant to "0b10__" (RFC4122/DCE1.1)

	return substr($hash,  0, 8).'-'.
	       substr($hash,  8, 4).'-'.
	       substr($hash, 12, 4).'-'.
	       substr($hash, 16, 4).'-'.
	       substr($hash, 20, 12);
}

# --------------------------------------

// http://php.net/manual/de/function.hex2bin.php#113057
if (!function_exists('hex2bin')) {
    function hex2bin($str) {
        $sbin = "";
        $len = strlen($str);
        for ( $i = 0; $i < $len; $i += 2 ) {
            $sbin .= pack("H*", substr($str, $i, 2));
        }
        return $sbin;
    }
}

// https://stackoverflow.com/questions/72127764/shift-right-left-bitwise-operators-in-php7-gmp-extension
if (!function_exists('gmp_shiftl')) {
    function gmp_shiftl($x,$n) { // shift left
        return(gmp_mul($x,gmp_pow(2,$n)));
    }
}

if (!function_exists('gmp_shiftr')) {
    function gmp_shiftr($x,$n) { // shift right
        return(gmp_div_q($x,gmp_pow(2,$n)));
    }
}

function shake128(string $msg, int $outputLength=512, bool $binary=false): string {
	include_once __DIR__.'/SHA3.php';
	$sponge = SHA3::init(SHA3::SHAKE128);
	$sponge->absorb($msg);
	$bin = $sponge->squeeze($outputLength);
	return $binary ? $bin : bin2hex($bin);
}

function shake256(string $msg, int $outputLength=512, bool $binary=false): string {
	include_once __DIR__.'/SHA3.php';
	$sponge = SHA3::init(SHA3::SHAKE256);
	$sponge->absorb($msg);
	$bin = $sponge->squeeze($outputLength);
	return $binary ? $bin : bin2hex($bin);
}
