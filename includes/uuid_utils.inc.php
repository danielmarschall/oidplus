<?php

/*
 * UUID utils for PHP
 * Copyright 2011-2020 Daniel Marschall, ViaThinkSoft
 * Version 2020-02-28
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

if (file_exists(__DIR__ . '/mac_utils.inc.phps')) include_once __DIR__ . '/mac_utils.inc.phps';
if (file_exists(__DIR__ . '/gmp_supplement.inc.php')) include_once __DIR__ . '/gmp_supplement.inc.php';

define('UUID_NAMEBASED_NS_DNS',     '6ba7b810-9dad-11d1-80b4-00c04fd430c8');
define('UUID_NAMEBASED_NS_URL',     '6ba7b811-9dad-11d1-80b4-00c04fd430c8');
define('UUID_NAMEBASED_NS_OID',     '6ba7b812-9dad-11d1-80b4-00c04fd430c8');
define('UUID_NAMEBASED_NS_X500_DN', '6ba7b814-9dad-11d1-80b4-00c04fd430c8');

function uuid_valid($uuid) {
	$uuid = str_replace(array('-', '{', '}'), '', $uuid);
	$uuid = strtoupper($uuid);

	if (strlen($uuid) != 32) return false;

	$uuid = preg_replace('@[0-9A-F]@', '', $uuid);

	return ($uuid == '');
}

# TODO: Don't echo
function uuid_info($uuid) {
	if (!uuid_valid($uuid)) return false;

	# $uuid = str_replace(array('-', '{', '}'), '', $uuid);
	$uuid = strtoupper($uuid);
	$uuid = preg_replace('@[^0-9A-F]@', '', $uuid);

	$x = hexdec(substr($uuid, 16, 1));
	     if ($x >= 14 /* 1110 */) $variant = 3;
	else if ($x >= 12 /* 1100 */) $variant = 2;
	else if ($x >=  8 /* 1000 */) $variant = 1;
	else if ($x >=  0 /* 0000 */) $variant = 0;


	switch ($variant) {
		case 0:
			echo sprintf("%-24s %s\n", "Variant:", "[0xx] NCS (reserved for backward compatibility)");

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
			 *
			 * +--------------------------------------------------------------+
			 * |                    high 32 bits of time                      |  0-3  .time_high
			 * +-------------------------------+-------------------------------
			 * |     low 16 bits of time       |  4-5               .time_low
			 * +-------+-----------------------+
			 * |         reserved              |  6-7               .reserved
			 * +---------------+---------------+
			 * |    family     |   8                                .family
			 * +---------------+----------...-----+
			 * |            node ID               |  9-16           .node
			 * +--------------------------...-----+
			 *
			 */

			// Example of an UUID: 333a2276-0000-0000-0d00-00809c000000

			# TODO: See https://github.com/cjsv/uuid/blob/master/Doc

			# Timestamp: Count of 4us intervals since 01 Jan 1980 00:00:00 GMT
			# 1/0,000004 = 250000
			# Seconds between 1970 and 1980 : 315532800
			# 250000*315532800=78883200000000
			$timestamp = substr($uuid, 0, 12);
			$ts = gmp_init($timestamp, 16);
			$ts = gmp_add($ts, gmp_init("78883200000000"));
			$ms = gmp_mod($ts, gmp_init("250000"));
			$ts = gmp_div($ts, gmp_init("250000"));
			$ts = gmp_strval($ts);
			$ms = gmp_strval($ms);
			$ts = gmdate('Y-m-d H:i:s', $ts)."'".str_pad($ms, 7, '0', STR_PAD_LEFT).' GMT';
			echo sprintf("%-24s %s\n", "Timestamp:", "[0x$timestamp] $ts");

			$reserved = substr($uuid, 12, 4);
			echo sprintf("%-24s %s\n", "Reserved:", "0x$reserved");

			# Family 13 (dds) looks like node is 00 | nnnnnn 000000.
			# Family 2 is presumably (ip).
			# Not sure if anything else was used.
			$family_hex = substr($uuid, 16, 2);
			$family_dec = hexdec($family_hex);
			if ($family_dec == 2) {
				$family_ = 'IP';
			} else if ($family_dec == 13) {
				$family_ = 'DDS (Data Link)';
			} else {
				$family_ = "Unknown ($family_dec)"; # There are probably no more families
			}
			echo sprintf("%-24s %s\n", "Family:", "[0x$family_hex = $family_dec] $family_");

			$nodeid = substr($uuid, 18, 14);
			echo sprintf("%-24s %s\n", "Node ID:", "0x$nodeid");
			# TODO: interprete node id (the family specifies it)

			break;
		case 1:
			echo sprintf("%-24s %s\n", "Variant:", "[10x] RFC 4122 (Leach-Mealling-Salz)");

			$version = hexdec(substr($uuid, 12, 1));
			switch ($version) {
				case 1:
					echo sprintf("%-24s %s\n", "Version:", "[1] Time-based with unique random host identifier");

					# Timestamp: Count of 100ns intervals since 15 Oct 1582 00:00:00
					# 1/0,0000001 = 10000000
					$timestamp = substr($uuid, 13, 3).substr($uuid, 8, 4).substr($uuid, 0, 8);
					$ts = gmp_init($timestamp, 16);
					$ts = gmp_sub($ts, gmp_init("122192928000000000"));
					$ms = gmp_mod($ts, gmp_init("10000000"));
					$ts = gmp_div($ts, gmp_init("10000000"));
					$ts = gmp_strval($ts);
					$ms = gmp_strval($ms);
					$ts = gmdate('Y-m-d H:i:s', $ts)."'".str_pad($ms, 7, '0', STR_PAD_LEFT).' GMT';
					echo sprintf("%-24s %s\n", "Timestamp:", "[0x$timestamp] $ts");

					$x = hexdec(substr($uuid, 16, 4));
					$dec = $x & 0x3FFF; // The highest 2 bits are used by "variant" (10x)
					$hex = substr($uuid, 16, 4);
					echo sprintf("%-24s %s\n", "Clock ID:", "[0x$hex] $dec");

					$x = substr($uuid, 20, 12);
					$nodeid = '';
					for ($i=0; $i<6; $i++) {
						$nodeid .= substr($x, $i*2, 2);
						if ($i != 5) $nodeid .= ':';
					}
					echo sprintf("%-24s %s\n", "Node ID:", "$nodeid");

					if (function_exists('decode_mac')) {
						echo "\nIn case that this Node ID is a MAC address, here is the interpretation of that MAC address:\n";
						echo decode_mac($nodeid);
					}

					break;
				case 2:
					echo sprintf("%-24s %s\n", "Version:", "[2] DCE Security version");

					# The time_low field (which represents an integer in the range [0, 232-1]) is interpreted as a local-ID; that is, an identifier (within the domain specified by clock_seq_low) meaningful to the local host. In the particular case of a POSIX host, when combined with a POSIX UID or POSIX GID domain in the clock_seq_low field (above), the time_low field represents a POSIX UID or POSIX GID, respectively. 
					$x = substr($uuid, 0, 8);
					echo sprintf("%-24s %s\n", "Local ID:", "0x$x");

					# The clock_seq_low field (which represents an integer in the range [0, 28-1]) is interpreted as a local domain (as represented by sec_rgy_domain_t; see sec_rgy_domain_t ); that is, an identifier domain meaningful to the local host. (Note that the data type sec_rgy_domain_t can potentially hold values outside the range [0, 28-1]; however, the only values currently registered are in the range [0, 2], so this type mismatch is not significant.) In the particular case of a POSIX host, the value sec_rgy_domain_person is to be interpreted as the "POSIX UID domain", and the value sec_rgy_domain_group is to be interpreted as the "POSIX GID domain".
					$x = substr($uuid, 18, 2);
					if ($x == '00') $domain_info = 'POSIX: User-ID / Non-POSIX: site-defined';
					else if ($x == '01') $domain_info = 'POSIX: Group-ID / Non-POSIX: site-defined';
					else $domain_info = 'site-defined';
					echo sprintf("%-24s %s\n", "Local Domain:", "0x$x ($domain_info)");

					# Timestamp: Count of 100ns intervals since 15 Oct 1582 00:00:00
					# 1/0,0000001 = 10000000
					$timestamp = substr($uuid, 13, 3).substr($uuid, 8, 4).'00000000';
					$ts = gmp_init($timestamp, 16);
					$ts = gmp_sub($ts, gmp_init("122192928000000000"));
					$ms = gmp_mod($ts, gmp_init("10000000"));
					$ts = gmp_div($ts, gmp_init("10000000"));
					$ts = gmp_strval($ts);
					$ms = gmp_strval($ms);
					$ts_min = gmdate('Y-m-d H:i:s', $ts)."'".str_pad($ms, 7, '0', STR_PAD_LEFT).' GMT';

					$timestamp = substr($uuid, 13, 3).substr($uuid, 8, 4).'FFFFFFFF';
					$ts = gmp_init($timestamp, 16);
					$ts = gmp_sub($ts, gmp_init("122192928000000000"));
					$ms = gmp_mod($ts, gmp_init("10000000"));
					$ts = gmp_div($ts, gmp_init("10000000"));
					$ts = gmp_strval($ts);
					$ms = gmp_strval($ms);
					$ts_max = gmdate('Y-m-d H:i:s', $ts)."'".str_pad($ms, 7, '0', STR_PAD_LEFT).' GMT';

					$timestamp = substr($uuid, 13, 3).substr($uuid, 8, 4).'xxxxxxxx';
					echo sprintf("%-24s %s\n", "Timestamp:", "[0x$timestamp] $ts_min - $ts_max");

					$x = hexdec(substr($uuid, 16, 2).'00');
					$dec_min = $x & 0x3FFF; // The highest 2 bits are used by "variant" (10x)
					$x = hexdec(substr($uuid, 16, 2).'FF');
					$dec_max = $x & 0x3FFF; // The highest 2 bits are used by "variant" (10x)
					$hex = substr($uuid, 16, 2).'xx';
					echo sprintf("%-24s %s\n", "Clock ID:", "[0x$hex] $dec_min - $dec_max");

					$x = substr($uuid, 20, 12);
					$nodeid = '';
					for ($i=0; $i<6; $i++) {
						$nodeid .= substr($x, $i*2, 2);
						if ($i != 5) $nodeid .= ':';
					}
					echo sprintf("%-24s %s\n", "Node ID:", "$nodeid");

					if (function_exists('decode_mac')) {
						echo "\nIn case that this Node ID is a MAC address, here is the interpretation of that MAC address:\n";
						echo decode_mac($nodeid);
					}

					break;
				case 3:
					echo sprintf("%-24s %s\n", "Version:", "[3] Name-based (MD5 hash)");

					$hash = str_replace('-', '', strtolower($uuid));
					$hash[12] = '?'; // was overwritten by version
					$hash[16] = '?'; // was partially overwritten by variant

					echo sprintf("%-24s %s\n", "MD5(Namespace+Subject):", "$hash");

					break;
				case 4:
					echo sprintf("%-24s %s\n", "Version:", "[4] Random");

					$rand = '';
					for ($i=0; $i<16; $i++) {
						$bin = base_convert(substr($uuid, $i*2, 2), 16, 2);
						$bin = str_pad($bin, 8, "0", STR_PAD_LEFT);

						if ($i == 6) {
							$bin[0] = 'x';
							$bin[1] = 'x';
						} else if ($i == 8) {
							$bin[0] = 'x';
							$bin[1] = 'x';
							$bin[2] = 'x';
							$bin[3] = 'x';
						}

						$rand .= "$bin ";
					}

					echo sprintf("%-24s %s\n", "Random bits:", trim($rand));

					break;
				case 5:
					echo sprintf("%-24s %s\n", "Version:", "[5] Name-based (SHA-1 hash)");

					$hash = str_replace('-', '', strtolower($uuid));
					$hash[12] = '?'; // was overwritten by version
					$hash[16] = '?'; // was partially overwritten by variant
					$hash .= '????????'; // was cut off

					echo sprintf("%-24s %s\n", "SHA1(Namespace+Subject):", "$hash");


					break;
				default:
					echo sprintf("%-24s %s\n", "Version:", "[$version] Unknown");
					break;
			}

			break;
		case 2:
			echo sprintf("%-24s %s\n", "Variant:", "[110] Reserved for Microsoft Corporation");
			break;
		case 3:
			echo sprintf("%-24s %s\n", "Variant:", "[111] Reserved for future use");
			break;
	}
}

function uuid_canonize($uuid) {
	if (!uuid_valid($uuid)) return false;
	return oid_to_uuid(uuid_to_oid($uuid));
}

function oid_to_uuid($oid) {
	if (!is_uuid_oid($oid)) return false;

	if ($oid[0] == '.') {
		$oid = substr($oid, 1);
	}
	$ary = explode('.', $oid);
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
	if ($oid[0] == '.') $oid = substr($oid, 1); // remove leading dot

	$ary = explode('.', $oid);

	if ($only_allow_root) {
		if (count($ary) != 3) return false;
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

function uuid_to_oid($uuid) {
	if (!uuid_valid($uuid)) return false;

	$uuid = str_replace(array('-', '{', '}'), '', $uuid);
	$x = gmp_init($uuid, 16);
	return '2.25.'.gmp_strval($x, 10); # TODO: parameter with or without leading dot
}

function gen_uuid($prefer_timebased = true) {
	if ($prefer_timebased) $uuid = gen_uuid_timebased();
	if ($uuid === false) $uuid = gen_uuid_random();
	return $uuid;
}

// Version 1 (Time based) UUID
function gen_uuid_timebased() {
	# On Debian: aptitude install php-uuid
	# extension_loaded('uuid')
	if (function_exists('uuid_create')) {
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

	# On Debian: aptitude install uuid-runtime
	$out = array();
	exec('uuidgen -t', $out, $ec);
	if ($ec == 0) return $out[0];

	# TODO: Implement the time based generation routine ourselves!
	# At the moment we cannot determine the time based UUID
	return false;
}

// Version 2 (DCE Security) UUID
function gen_uuid_dce($domain, $id) {
	# Start with a version 1 UUID
	$uuid = gen_uuid_timebased();

	# Add ID
	$uuid = str_pad(dechex($id), 8, '0', STR_PAD_LEFT) . substr($uuid, 8);

	# Add domain
	$uuid = substr($uuid,0,21) . str_pad(dechex($domain), 2, '0', STR_PAD_LEFT) . substr($uuid, 23);

	# Change version to 2
	$uuid[14] = '2';

	return $uuid;
}

// Version 3 (MD5 name based) UUID
function gen_uuid_md5_namebased($namespace_uuid, $name) {
	if (!uuid_valid($namespace_uuid)) return false;
	$namespace_uuid = uuid_canonize($namespace_uuid);
	$namespace_uuid = str_replace('-', '', $namespace_uuid);
	$namespace_uuid = hex2bin($namespace_uuid);

	$hash = md5($namespace_uuid.$name);
	$hash[12] = '3'; // Set version: 3 = MD5
	$hash[16] = dechex(hexdec($hash[16]) & 0x3 | 0x8); // Set variant to "10xx" (RFC4122)

	return substr($hash,  0, 8).'-'.
	       substr($hash,  8, 4).'-'.
	       substr($hash, 12, 4).'-'.
	       substr($hash, 16, 4).'-'.
	       substr($hash, 20, 12);
}

// Version 4 (Random) UUID
function gen_uuid_random() {
	# On Debian: aptitude install php-uuid
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

	# On Debian: aptitude install uuid-runtime
	$out = array();
	exec('uuidgen -r', $out, $ec);
	if ($ec == 0) return $out[0];

	# On Debian Jessie: UUID V4 (Random)
	if (file_exists('/proc/sys/kernel/random/uuid')) {
		return file_get_contents('/proc/sys/kernel/random/uuid');
	}

	# Make the UUID by ourselves
	# Source: http://rogerstringer.com/2013/11/15/generate-uuids-php
	return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
		mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
		mt_rand( 0, 0xffff ),
		mt_rand( 0, 0x0fff ) | 0x4000,
		mt_rand( 0, 0x3fff ) | 0x8000,
		mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
	);
}

// Version 5 (SHA1 name based) UUID
function gen_uuid_sha1_namebased($namespace_uuid, $name) {
	$namespace_uuid = str_replace('-', '', $namespace_uuid);
	$namespace_uuid = hex2bin($namespace_uuid);

	$hash = sha1($namespace_uuid.$name);
	$hash[12] = '5'; // Set version: 5 = SHA1
	$hash[16] = dechex(hexdec($hash[16]) & 0x3 | 0x8); // Set variant to "10xx" (RFC4122)

	return substr($hash,  0, 8).'-'.
	       substr($hash,  8, 4).'-'.
	       substr($hash, 12, 4).'-'.
	       substr($hash, 16, 4).'-'.
	       substr($hash, 20, 12);
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

# ---

// http://php.net/manual/de/function.hex2bin.php#113057
if ( !function_exists( 'hex2bin' ) ) {
    function hex2bin( $str ) {
        $sbin = "";
        $len = strlen( $str );
        for ( $i = 0; $i < $len; $i += 2 ) {
            $sbin .= pack( "H*", substr( $str, $i, 2 ) );
        }

        return $sbin;
    }
}
