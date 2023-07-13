<?php

/*
 * MAC (EUI-48 and EUI-64) utils for PHP
 * Copyright 2017 - 2023 Daniel Marschall, ViaThinkSoft
 * Version 2023-07-13
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

// Very good resources for information about OUI, EUI, MAC, ...
// - https://mac-address.alldatafeeds.com/faq#how-to-recognise-mac-address-application
// - https://standards.ieee.org/wp-content/uploads/import/documents/tutorials/eui.pdf
// - https://en.m.wikipedia.org/wiki/Organizationally_unique_identifier

const IEEE_MAC_REGISTRY = __DIR__ . '/../web-data';

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

/**
 * Generates a random new AAI.
 * @param int $bits Must be 48 or 64
 * @param bool $multicast Should it be multicast?
 */
function gen_aai(int $bits, bool $multicast): string {
	if (($bits != 48) && ($bits != 64)) throw new Exception("Invalid bits for gen_aai(). Must be 48 or 64.");
	$bytes = [];
	for ($i=0; $i<($bits==48?6:8); $i++) {
		$val = _random_int(0x00, 0xFF);
		if ($i == 0) {
			// Make it an AAI
			$val = $val & 0xF0 | ($multicast ? 0x03 : 0x02);
		}
		$bytes[] = sprintf('%02x',$val);
	}
	return strtoupper(implode('-',$bytes));
}

/**
 * Checks if a MAC, EUI, ELI, AAI, SAI, or IPv6-Link-Local address is valid
 * @param string $mac MAC, EUI, or IPv6-Link-Local Address
 * @return bool True if it is valid
 */
function mac_valid(string $mac): bool {
	$tmp = ipv6linklocal_to_mac48($mac);
	if ($tmp !== false) $mac = $tmp;

	$mac = str_replace(array('-', ':'), '', $mac);
	$mac = strtoupper($mac);

	if ((strlen($mac) != 12) && (strlen($mac) != 16)) return false;

	$mac = preg_replace('@[0-9A-F]@', '', $mac);

	return ($mac === '');
}

/**
 * Returns the amount of bits of a MAC, EUI, ELI, AAI, or SAI
 * @param string $mac
 * @return false|int
 */
function eui_bits(string $mac) {
	if (!mac_valid($mac)) return false;
	$mac = mac_canonize($mac, '');
	return (int)(strlen($mac)*4);
}

/**
 * Canonizes a MAC, EUI, ELI, AAI, SAI, or IPv6-Link-Local address
 * @param string $mac MAC, EUI, ELI, or IPv6-Link-Local Address
 * @param string $delimiter Desired delimiter for inserting between each octet
 * @return string|false The canonized address (Note: IPv6-Link-Local becomes EUI-64)
 */
function mac_canonize(string $mac, string $delimiter="-") {
	if (!mac_valid($mac)) return false;

	$tmp = ipv6linklocal_to_mac48($mac);
	if ($tmp !== false) $mac = $tmp;

	$mac = strtoupper($mac);
	$mac = preg_replace('@[^0-9A-F]@', '', $mac);
	if ((strlen($mac) != 12) && (strlen($mac) != 16)) return false;
	$mac = preg_replace('@^(..)(..)(..)(..)(..)(..)(..)(..)$@', '\\1'.$delimiter.'\\2'.$delimiter.'\\3'.$delimiter.'\\4'.$delimiter.'\\5'.$delimiter.'\\6'.$delimiter.'\\7'.$delimiter.'\\8', $mac);
	return preg_replace('@^(..)(..)(..)(..)(..)(..)$@', '\\1'.$delimiter.'\\2'.$delimiter.'\\3'.$delimiter.'\\4'.$delimiter.'\\5'.$delimiter.'\\6', $mac);
}

/**
 * @param string $file
 * @param string $registry_name
 * @param string $mac
 * @return false|string
 */
function _lookup_ieee_registry(string $file, string $registry_name, string $mac) {
	$mac = mac_canonize($mac, '');
	if ($mac === false) return false;
	$begin = substr($mac, 0, 2).'-'.substr($mac, 2, 2).'-'.substr($mac, 4, 2);
	$f = file_get_contents($file);

	$f = str_replace("\r", '', $f);

	# We are using a positive-lookahead because entries like the MA-M references have a blank line between organization and address
	preg_match_all('@^\s*'.preg_quote($begin, '@').'\s+\(hex\)\s+(\S+)\s+(.*)\n\n\s*(?=[0-9A-F])@ismU', "$f\n\nA", $m, PREG_SET_ORDER);
	foreach ($m as $n) {
		preg_match('@(\S+)\s+\(base 16\)(.*)$@ism', $n[2], $m);

		if (preg_match('@(.+)-(.+)@ism', $m[1], $o)) {
			$z = hexdec(substr($mac, 6, 6));
			$beg = hexdec($o[1]);
			$end = hexdec($o[2]);
			if (($z < $beg) || ($z > $end)) continue;
		} else {
			$beg = 0x000000;
			$end = 0xFFFFFF;
		}

		$x = trim(preg_replace('@^\s+@im', '', $m[2]));

		# "PRIVATE" entries are only marked at the "(hex)" line, but not at the "(base16)" line
		if ($x == '') $x = trim($n[1]);

		$x = explode("\n", $x);

		// The 12 is hardcoded and is valid for MAC-48 and MAC-64!
		// Reason: The length of the prefix is calculated from MAC-48. MAC-64 just extends the vendor-specific part
		// end-beg (= range)  OUI24 0xFFFFFF  len=6    12-6 = 6 nibbles prefix
		//                    OUI28 0xFFFFF   len=5    12-5 = 7 nibbles prefix
		//                    OUI36 0xFFF     len=3    12-3 = 9 nibbles prefix
		$prefix_len = 12-strlen(dechex($end-$beg));

		$out = sprintf("%-32s 0x%s\n", "IEEE $registry_name:", substr($mac, 0, $prefix_len));
		$out .= sprintf("%-32s 0x%s\n", "Vendor-specific part:", substr($mac, $prefix_len));
		$out .= sprintf("%-32s %s\n", "Registrant:", $x[0]);

		foreach ($x as $n => $y) {
			if ($n == 0) continue;
			else if ($n == 1) $out .= sprintf("%-32s %s\n", "Address of registrant:", $y);
			else if ($n >= 2) $out .= sprintf("%-32s %s\n", "", $y);
		}

		return $out;
	}

	return false;
}

/**
 * Try to Decapsulate EUI-64 into MAC-48 or EUI-48
 * @param string $eui64
 * @return false|string If EUI-64 can be converted into EUI-48, returns EUI-48, otherwise returns EUI-64. On invalid input, return false.
 */
function eui64_to_eui48(string $eui64) {
	if (!mac_valid($eui64)) return false;
	$eui64 = mac_canonize($eui64, '');
	if (eui_bits($eui64) == 48) return mac_canonize($eui64);
	if (($eui64[1] != '0') && ($eui64[1] != '4') && ($eui64[1] != '8') && ($eui64[1] != 'C')) return false; // only allow EUI

	if (substr($eui64, 6, 4) == 'FFFF') {
		// EUI-64 to MAC-48
		return mac_canonize(substr($eui64, 0, 6).substr($eui64, 10, 6));
	} else if (substr($eui64, 6, 4) == 'FFFE') {
		if ((hexdec($eui64[1])&2) == 2) {
			// Modified EUI-64 to MAC/EUI-48
			$eui64[1] = dechex(hexdec($eui64[1])&253); // remove bit
			return mac_canonize(substr($eui64, 0, 6).substr($eui64, 10, 6));
		} else {
			// EUI-64 to EUI-48
			return mac_canonize(substr($eui64, 0, 6).substr($eui64, 10, 6));
		}
	} else {
		return mac_canonize($eui64);
	}
}

/**
 * MAC-48 to EUI-64 Encapsulation
 * @param string $mac48 MAC-48 address
 * @return false|string EUI-64 address
 */
function mac48_to_eui64(string $mac48) {
	// Note: MAC-48 is used for network hardware; EUI-48 is used to identify other devices and software.
	//       MAC48-to-EUI64 Encapsulation uses 0xFFFF middle part
	if (!mac_valid($mac48)) return false;
	$mac48 = mac_canonize($mac48, '');
	if (eui_bits($mac48) == 64) return mac_canonize($mac48);
	if (($mac48[1] != '0') && ($mac48[1] != '4') && ($mac48[1] != '8') && ($mac48[1] != 'C')) return false; // only allow EUI

	$eui64 = substr($mac48, 0, 6).'FFFF'.substr($mac48, 6, 6);
	return mac_canonize($eui64);
}

/**
 * EUI-48 to EUI-64 Encapsulation
 * @param string $eui48 EUI-48 address
 * @return false|string EUI-64 address
 */
function eui48_to_eui64(string $eui48) {
	// Note: MAC-48 is used for network hardware; EUI-48 is used to identify other devices and software.
	//       EUI48-to-EUI64 Encapsulation uses 0xFFFF middle part
	if (!mac_valid($eui48)) return false;
	$eui48 = mac_canonize($eui48, '');
	if (eui_bits($eui48) == 64) return mac_canonize($eui48);
	if (($eui48[1] != '0') && ($eui48[1] != '4') && ($eui48[1] != '8') && ($eui48[1] != 'C')) return false; // only allow EUI

	$eui64 = substr($eui48, 0, 6).'FFFE'.substr($eui48, 6, 6);
	return mac_canonize($eui64);
}

/**
 * MAC/EUI-48 to Modified EUI-64 Encapsulation
 * @param string $eui48 MAC-48 or EUI-48 address
 * @return false|string Modified EUI-64 address
 */
function maceui48_to_modeui64(string $eui48) {
	// Note: MAC-48 is used for network hardware; EUI-48 is used to identify other devices and software.
	//       EUI48-to-ModifiedEUI64 Encapsulation uses 0xFFFE middle part (SIC! This was a mistake by IETF, since it should actually be 0xFFFF!)
	if (!mac_valid($eui48)) return false;
	$eui48 = mac_canonize($eui48, '');
	if (eui_bits($eui48) == 64) return mac_canonize($eui48);
	if (($eui48[1] != '0') && ($eui48[1] != '4') && ($eui48[1] != '8') && ($eui48[1] != 'C')) return false; // only allow EUI

	$eui64 = substr($eui48, 0, 6).'FFFE'.substr($eui48, 6, 6);

	$eui64[1] = dechex(hexdec($eui64[1]) | 2); // flip seventh bit

	return mac_canonize($eui64);
}

/**
 * Try to convert IPv6-Link-Local address to MAC-48
 * @param string $ipv6 IPv6-Link-Local address
 * @return false|string MAC-48 (or IPv6 if it was no LinkLocal address, or Modified EUI-64 if it decapsulation failed)
 */
function ipv6linklocal_to_mac48(string $ipv6) {
	// https://stackoverflow.com/questions/12095835/quick-way-of-expanding-ipv6-addresses-with-php (modified)
	$tmp = @inet_pton($ipv6);
	if ($tmp === false) return false;
	$hex = unpack("H*hex", $tmp);
	$ipv6 = substr(preg_replace("/([A-f0-9]{4})/", "$1:", $hex['hex']), 0, -1);

	// Remove "fe80::" to convert IPv6-Link-Local address back to EUI-64
	// see https://support.lenovo.com/de/de/solutions/ht509925-how-to-convert-a-mac-address-into-an-ipv6-link-local-address-eui-64
	$cnt = 0;
	$mac = preg_replace('@^fe80:0000:0000:0000:@i', '', $ipv6, -1, $cnt);
	if ($cnt == 0) return false;

	// Set LAA to UAA again
	$mac_uaa_64 = $mac;
	$mac_uaa_64[1] = dechex(hexdec($mac_uaa_64[1]) & 253);

	$mac_uaa_48 = eui64_to_eui48($mac_uaa_64);
	if (eui_bits($mac_uaa_48) == 48) {
		return $mac_uaa_48; // Output MAC-48 (UAA)
	} else {
		return $mac; // Failed decapsulation; output Modified EUI-64 instead
	}
}

/**
 * Converts MAC-48 or EUI-48 to IPv6-Link-Local (based on Modified EUI-64)
 * @param string $mac
 * @return false|string
 */
function maceui_to_ipv6linklocal(string $mac) {
	if (!mac_valid($mac)) return false;
	if (eui_bits($mac) == 48) {
		$mac = maceui48_to_modeui64($mac);
	}
	$mac = mac_canonize($mac, '');
	$mac = str_pad($mac, 16, '0', STR_PAD_LEFT);
	return strtolower('fe80::'.substr($mac,0, 4).':'.substr($mac,4, 4).':'.substr($mac,8, 4).':'.substr($mac,12, 4));
}

/**
 * @param string $mac
 * @return string
 * @throws Exception
 */
function mac_type(string $mac): string {
	// Format MAC for machine readability
	$mac = mac_canonize($mac, '');

	/*
	 *
	 *  	ZYXM
	 * 0	0000	EUI (OUI)
	 * 1	0001	[Multicast]
	 * 2	0010	AAI
	 * 3	0011	[Multicast]
	 * 4	0100	EUI (OUI)
	 * 5	0101	[Multicast]
	 * 6	0110	Reserved
	 * 7	0111	[Multicast]
	 * 8	1000	EUI (OUI)
	 * 9	1001	[Multicast]
	 * A	1010	ELI (CID)
	 * B	1011	[Multicast]
	 * C	1100	EUI (OUI)
	 * D	1101	[Multicast]
	 * E	1110	SAI
	 * F	1111	[Multicast]
	 *
	 */

	$type = '';
	$tmp = ipv6linklocal_to_mac48($mac);
	if ($tmp !== false) {
		$mac = $tmp;
		$type = 'IPv6-Link-Local';
	}
	if (!mac_valid($mac)) throw new Exception("Invalid MAC address");
	if ($tmp === false) {
		if (($mac[1] == '2') || ($mac[1] == '3')) {
			/*
			 * AAI: Administratively Assigned Identifier
			 * Administrators who wish to assign local MAC addresses in an
			 * arbitrary fashion (for example, randomly) and yet maintain
			 * compatibility with other assignment protocols operating under the
			 * SLAP on the same LAN may assign a local MAC address as AAI.
			 */
			$type = 'AAI-' . eui_bits($mac).' (Administratively Assigned Identifier)';
		} else if (($mac[1] == '6') || ($mac[1] == '7')) {
			/*
			 * Reserved
			 * may be administratively used and assigned in accordance with the
			 * considerations specified for AAI usage, without effect on SLAP
			 * assignments. However, administrators should be cognizant of
			 * possible future specifications… that would render administrative
			 * assignment incompatible with the SLAP.
			 */
			$type = 'Reserved-' . eui_bits($mac);
		} else if (($mac[1] == 'A') || ($mac[1] == 'B')) {
			/*
			 * ELI: Extended Local Identifier
			 * An ELI is based on a 24 bit CID
			 * A CID has ZYXM bits set to 1010 (0b1010 = 0xA)
			 * Since X=1 (U/L=1), the CID cannot be used to form a universal UAA MAC (only a local LAA MAC)
			 */
			$type = 'ELI-' . eui_bits($mac).' (Extended Local Identifier)';
		} else if (($mac[1] == 'E') || ($mac[1] == 'F')) {
			/*
			 * SAI: Standard Assigned Identifier
			 * Specification of the use of the SAI quadrant for SLAP address
			 * assignments is reserved for the standard forthcoming from IEEE
			 * P802.1CQ.
			 * An SAI is assigned by a protocol specified in an IEEE 802 standard.
			 * Multiple protocols for assigning SAI may be specified within various
			 * IEEE 802 standards. Coexistence of such protocols may be supported
			 * by restricting each to assignments within a subspace of SAI space.
			 * In some cases, an SAI assignment protocol may assign the SAI to convey
			 * specific information. Such information may be interpreted by receivers
			 * and bridges that recognize the specific SAI assignment protocol, as
			 * identified by the subspace of the SAI. The functionality of receivers
			 * and bridges that do not recognize the protocol is not affected.
			 */
			$type = 'SAI-' . eui_bits($mac).' (Standard Assigned Identifier)';
		} else {
			/*
			 * Extended Unique Identifier
			 * Based on an OUI-24, OUI-28, or OUI-36
			 */
			if (eui_bits($mac) == 48) {
				// The name "MAC-48" has been deprecated by IEEE
				//$type = 'MAC-48 (network hardware) or EUI-48 (other devices and software)';
				$type = 'EUI-48 (Extended Unique Identifier)';
			} else if (eui_bits($mac) == 64) {
				if (substr($mac, 6, 4) == 'FFFE') {
					if ((hexdec($mac[1]) & 2) == 2) {
						$type = 'EUI-64 (Extended Unique Identifier, MAC/EUI-48 to Modified EUI-64 Encapsulation)';
					} else {
						$type = 'EUI-64 (Extended Unique Identifier, EUI-48 to EUI-64 Encapsulation)';
					}
				} else if (substr($mac, 6, 4) == 'FFFF') {
					$type = 'EUI-64 (Extended Unique Identifier, MAC-48 to EUI-64 Encapsulation)';
				} else {
					$type = 'EUI-64 (Extended Unique Identifier)';
				}
			} else {
				assert(false); /** @phpstan-ignore-line */
			}
		}
	}

	if ((hexdec($mac[1])&1) == 1) {
		// see also https://networkengineering.stackexchange.com/questions/83121/can-eli-aai-sai-addresses-be-multicast

		/* https://standards.ieee.org/wp-content/uploads/import/documents/tutorials/eui.pdf writes:
		 * - The assignee of an OUI or OUI-36 is exclusively authorized to assign group
		 *   MAC addresses, with I/G=1, by extending a modified version of the assigned
		 *   OUI or OUI-36 in which the M bit is set to 1. Such addresses are not EUIs and
		 *   do not globally identify hardware instances, even though U/L=0.
		 * - The assignee of a CID may assign local group MAC addresses by extending a modified version of
		 *   the assigned CID by setting the M bit to 1 (so that I/G=1). The resulting
		 *   extended identifier is an ELI.
		 */

		// TODO: If "Multicast EUI" is not an EUI (as tutorials/eui.pdf states), how should we name it instead?!
		$type = "Multicast $type";
	}

	return $type;
}

/**
 * Prints information about an IPv6-Link-Local address, MAC, EUI, ELI, AAI, or SAI.
 * @param string $mac IPv6-Link-Local address, MAC, EUI, ELI, AAI, or SAI
 * @return void
 * @throws Exception
 */
function decode_mac(string $mac) {

	// TODO: Should we decode Multicast MAC to its IP (see https://ipcisco.com/lesson/multicast-mac-addresses/)?

	echo sprintf("%-32s %s\n", "Input:", $mac);

	// Format MAC for machine readability
	$mac = mac_canonize($mac, '');

	$type = mac_type($mac);
	echo sprintf("%-32s %s\n", "Type:", $type);

	echo "\n";

	$is_eli_unicast = (hexdec($mac[1]) & 0xF) == 0xA;  // ELI = 1010 (unicast)
	$is_eli         = (hexdec($mac[1]) & 0xE) == 0xA;  // ELI = 101x (unicast and multicast)

	$is_eui_unicast = (hexdec($mac[1]) & 0x3) == 0x0;  // EUI = xx00 (unicast)
	$is_eui         = (hexdec($mac[1]) & 0x2) == 0x0;  // EUI = xx0x (unicast and multicast)

	// Show various representations
	if ($is_eli) {
		// Note: There does not seem to exist an algorithm for encapsulating/converting ELI-48 <=> ELI-64
		echo sprintf("%-32s %s\n", "ELI-".eui_bits($mac).":", mac_canonize($mac));
		$mac48 = eui64_to_eui48($mac);
		echo sprintf("%-32s %s\n", "MAC-48 (Local):", (eui_bits($mac48) != 48) ? 'Not available' : $mac48);
	} else if ($is_eui) {
		$eui48 = eui64_to_eui48($mac);
		echo sprintf("%-32s %s\n", "EUI-48 or MAC-48:", (eui_bits($eui48) != 48) ? 'Not available' : $eui48);
		if (eui_bits($mac) == 48) {
			$eui64 = mac48_to_eui64($mac);
			echo sprintf("%-32s %s\n", "EUI-64:", ((eui_bits($eui64) != 64) ? 'Not available' : $eui64).' (MAC-48 to EUI-64 Encapsulation)');
			$eui64 = eui48_to_eui64($mac);
			echo sprintf("%-32s %s\n", "", ((eui_bits($eui64) != 64) ? 'Not available' : $eui64).' (EUI-48 to EUI-64 Encapsulation)');
			$eui64 = maceui48_to_modeui64($mac);
			echo sprintf("%-32s %s\n", "", ((eui_bits($eui64) != 64) ? 'Not available' : $eui64).' (MAC/EUI-48 to Modified EUI-64 Encapsulation)');
			$ipv6 = maceui_to_ipv6linklocal($mac);
			echo sprintf("%-32s %s\n", "IPv6-Link-Local address:", $ipv6);
		} else {
			$eui64 = mac_canonize($mac);
			echo sprintf("%-32s %s\n", "EUI-64:", $eui64);
		}
	}

	// Vergabestelle
	$ul = hexdec($mac[1]) & 2; // Bit #LSB+1 of Byte 1
	$ul_ = ($ul == 0) ? '[0] Universally Administered Address (UAA)' : '[1] Locally Administered Address (LAA)';
	echo sprintf("%-32s %s\n", "Administration type (U/L flag):", $ul_);

	// Empfaengergruppe
	$ig = hexdec($mac[1]) & 1; // Bit #LSB+0 of Byte 1
	$ig_ = ($ig == 0) ? '[0] Unicast (Individual)' : '[1] Multicast (Group)';
	echo sprintf("%-32s %s\n", "Transmission type (I/G flag):", $ig_);

	// Query IEEE registries
	if (count(glob(IEEE_MAC_REGISTRY.DIRECTORY_SEPARATOR.'*.txt')) > 0) {
		$alt_mac = $mac;
		$alt_mac[1] = dechex(hexdec($alt_mac[1])^1); // switch Unicast<=>Multicast in order to find the vendor

		if (is_dir(IEEE_MAC_REGISTRY)) {
			if ($is_eli) {
				// Query the CID registry
				if (
					($x = _lookup_ieee_registry(IEEE_MAC_REGISTRY . DIRECTORY_SEPARATOR . 'cid.txt', 'CID', $mac)) ||
					($x = _lookup_ieee_registry(IEEE_MAC_REGISTRY . DIRECTORY_SEPARATOR . 'cid.txt', 'CID', $alt_mac))
				) {
					echo "\n";
					echo $x;
				} else {
					$registry_name = 'CID';
					echo "\n";
					echo sprintf("%-32s 0x%s\n", "IEEE $registry_name:", substr($mac, 0, 6));
					echo sprintf("%-32s 0x%s\n", "Vendor-specific part:", substr($mac, 6));
					echo sprintf("%-32s %s\n", "Registrant:", "$registry_name not found in database");
				}
			} else if ($is_eui) {
				// Query the OUI registries
				if (
					# The IEEE Registration Authority distinguishes between IABs and OUI-36 values. Both are 36-bit values which may be used to generate EUI-48 values, but IABs may not be used to generate EUI-64 values.[6]
					# Note: The Individual Address Block (IAB) is an inactive registry activity, which has been replaced by the MA-S registry product as of January 1, 2014.
					($x = _lookup_ieee_registry(IEEE_MAC_REGISTRY . DIRECTORY_SEPARATOR . 'iab.txt', 'IAB', $mac)) ||
					($x = _lookup_ieee_registry(IEEE_MAC_REGISTRY . DIRECTORY_SEPARATOR . 'oui36.txt', 'OUI-36 (MA-S)', $mac)) ||
					($x = _lookup_ieee_registry(IEEE_MAC_REGISTRY . DIRECTORY_SEPARATOR . 'mam.txt', '28 bit identifier (MA-M)', $mac)) ||
					($x = _lookup_ieee_registry(IEEE_MAC_REGISTRY . DIRECTORY_SEPARATOR . 'oui.txt', 'OUI (MA-L)', $mac)) ||
					($x = _lookup_ieee_registry(IEEE_MAC_REGISTRY . DIRECTORY_SEPARATOR . 'iab.txt', 'IAB', $alt_mac)) ||
					($x = _lookup_ieee_registry(IEEE_MAC_REGISTRY . DIRECTORY_SEPARATOR . 'oui36.txt', 'OUI-36 (MA-S)', $alt_mac)) ||
					($x = _lookup_ieee_registry(IEEE_MAC_REGISTRY . DIRECTORY_SEPARATOR . 'mam.txt', '28 bit identifier (MA-M)', $alt_mac)) ||
					($x = _lookup_ieee_registry(IEEE_MAC_REGISTRY . DIRECTORY_SEPARATOR . 'oui.txt', 'OUI (MA-L)', $alt_mac))
				) {
					echo "\n";
					echo $x;
				} else {
					$registry_name = 'OUI (MA-L)?';
					echo "\n";
					echo sprintf("%-32s 0x%s\n", "IEEE $registry_name:", substr($mac, 0, 6));
					echo sprintf("%-32s 0x%s\n", "Vendor-specific part:", substr($mac, 6));
					echo sprintf("%-32s %s\n", "Registrant:", "$registry_name not found in database");

					$registry_name = '28 bit identifier (MA-M)?';
					echo "\n";
					echo sprintf("%-32s 0x%s\n", "IEEE $registry_name:", substr($mac, 0, 7));
					echo sprintf("%-32s 0x%s\n", "Vendor-specific part:", substr($mac, 7));
					echo sprintf("%-32s %s\n", "Registrant:", "$registry_name not found in database");

					$registry_name = 'OUI-36 (MA-S)?';
					echo "\n";
					echo sprintf("%-32s 0x%s\n", "IEEE $registry_name:", substr($mac, 0, 9));
					echo sprintf("%-32s 0x%s\n", "Vendor-specific part:", substr($mac, 9));
					echo sprintf("%-32s %s\n", "Registrant:", "$registry_name not found in database");
				}
			}
		}
	}

	$vm = '';
	// === FAQ "Detection rules which don't have their dedicated page yet" ===
	// https://wiki.xenproject.org/wiki/Xen_Networking
	// https://mcpmag.com/articles/2007/11/27/hey-vm-whats-your-hypervisor.aspx
	// https://www.techrepublic.com/blog/data-center/mac-address-scorecard-for-common-virtual-machine-platforms
	if (mac_between($mac, '00:16:3E:00:00:00', '00:16:3E:FF:FF:FF')) $vm = "Red Hat Xen, XenSource, Novell Xen";
	// http://techgenix.com/mac-address-pool-duplication-hyper-v/
	// https://docs.microsoft.com/en-us/system-center/vmm/network-mac?view=sc-vmm-1807
	// https://blogs.technet.microsoft.com/gbanin/2014/08/27/how-to-solve-mac-address-conflict-on-hyper-v/
	if (mac_between($mac, '00:1D:D8:B7:1C:00', '00:1D:D8:F4:1F:FF')) $vm = "Microsoft SCVMM (System Center Virtual Machine Manager)";
	// https://mcpmag.com/articles/2007/11/27/hey-vm-whats-your-hypervisor.aspx
	// https://www.techrepublic.com/blog/data-center/mac-address-scorecard-for-common-virtual-machine-platforms/
	// https://blogs.technet.microsoft.com/medv/2011/01/24/how-to-manage-vm-mac-addresses-with-the-globalimagedata-xml-file-in-med-v-v1/
	if (mac_between($mac, '00:03:FF:00:00:00', '00:03:FF:FF:FF:FF')) $vm = "Microsoft Virtual PC / Virtual Server";
	// https://mcpmag.com/articles/2007/11/27/hey-vm-whats-your-hypervisor.aspx
	if (mac_between($mac, '00:18:51:00:00:00', '00:18:51:FF:FF:FF')) $vm = "SWsoft";
	// https://macaddress.io/statistics/company/17619
	if (mac_between($mac, '58:9C:FC:00:00:00', '58:9C:FC:FF:FF:FF')) $vm = "bhyve by FreebsdF";
	// https://macaddress.io/statistics/company/17388
	if (mac_between($mac, '50:6B:8D:00:00:00', '50:6B:8D:FF:FF:FF')) $vm = "Nutanix AHV";
	// https://www.centos.org/forums/viewtopic.php?t=26739
	if (mac_between($mac, '54:52:00:00:00:00', '54:52:FF:FF:FF:FF')) $vm = "KVM (proxmox)";
	// Self tested (alldatafeeds.com)
	if (mac_between($mac, '96:00:00:00:00:00', '96:00:FF:FF:FF:FF')) $vm = "Hetzner vServer (based on KVM and libvirt)";
	// === FAQ "How to recognise a VMware's virtual machine by its MAC address?" ===
	if (mac_between($mac, '00:50:56:00:00:00', '00:50:56:FF:FF:FF')) $vm = "VMware vSphere, VMware Workstation, VMware ESX Server";
	if (mac_between($mac, '00:50:56:80:00:00', '00:50:56:BF:FF:FF')) $vm = "VMware vSphere managed by vCenter Server";
	if (mac_between($mac, '00:0C:29:00:00:00', '00:0C:29:FF:FF:FF')) $vm = "VMWare Standalone VMware vSphere, VMware Workstation, VMware Horizon";
	if (mac_between($mac, '00:05:69:00:00:00', '00:05:69:FF:FF:FF')) $vm = "VMware ESX, VMware GSX Server";
	if (mac_between($mac, '00:1C:14:00:00:00', '00:1C:14:FF:FF:FF')) $vm = "VMWare";
	// === FAQ "machine by its MAC address?" ===
	if (mac_between($mac, '00:1C:42:00:00:00', '00:1C:42:FF:FF:FF')) $vm = "Parallels Virtual Machine";
	// === FAQ "How to recognise a Docker container by its MAC address?" ===
	if (mac_between($mac, '02:42:00:00:00:00', '02:42:FF:FF:FF:FF')) $vm = "Docker container";
	// === FAQ =How to recognise a Microsoft Hyper-V's virtual machine by its MAC address?" ===
	if (mac_between($mac, '00:15:5D:00:00:00', '00:15:5D:FF:FF:FF')) $vm = "Microsoft Hyper-V";
	// === FAQ "How to recognise an Oracle Virtual machine by its MAC address?" ===
	if (mac_between($mac, '08:00:27:00:00:00', '08:00:27:FF:FF:FF')) $vm = "Oracle VirtualBox 5.2"; // Pcs Systemtechnik GmbH
	if (mac_between($mac, '52:54:00:00:00:00', '52:54:00:FF:FF:FF')) $vm = "Oracle VirtualBox 5.2 + Vagrant"; // 52:54:00 (Exact MAC: 52:54:00:C9:C7:04)
	if (mac_between($mac, '00:21:F6:00:00:00', '00:21:F6:FF:FF:FF')) $vm = "Oracle VirtualBox 3.3";
	if (mac_between($mac, '00:14:4F:00:00:00', '00:14:4F:FF:FF:FF')) $vm = "Oracle VM Server for SPARC";
	if (mac_between($mac, '00:0F:4B:00:00:00', '00:0F:4B:FF:FF:FF')) $vm = "Oracle Virtual Iron 4";

	if ($vm) {
		echo sprintf("%-32s %s\n", "Special use:", "Virtual machine $vm");
	}

	$app = '';

	// === FAQ "Other MAC address applications"
	// http://www.cavebear.com/archive/cavebear/Ethernet/Ethernet.txt
	// https://tools.ietf.org/html/rfc1060
	if (mac_between($mac, '03:00:00:01:00:00', '03:00:40:00:00:00')) $app = 'User-defined (per 802 spec), EtherType is 0x0802';
	if (mac_equals($mac, '01:00:1D:00:00:00')) $app = 'Cabletron PC-OV PC discover (on demand), EtherType is 0x0802';
	if (mac_equals($mac, '01:00:1D:42:00:00')) $app = 'Cabletron PC-OV Bridge discover (on demand), EtherType is 0x0802';
	if (mac_equals($mac, '01:00:1D:52:00:00')) $app = 'Cabletron PC-OV MMAC discover (on demand), EtherType is 0x0802';
	if (mac_between($mac, '01:00:3C:00:00:00' , '01:00:3C:FF:FF:FF')) $app = 'Auspex Systems (Serverguard)';
	if (mac_equals($mac, '01:00:10:00:00:20')) $app = 'Hughes Lan Systems Terminal Server S/W download, EtherType is 0x0802';
	if (mac_equals($mac, '01:00:10:FF:FF:20')) $app = 'Hughes Lan Systems Terminal Server S/W request, EtherType is 0x0802';
	if (mac_equals($mac, '01:00:81:00:00:00')) $app = 'Synoptics Network Management';
	if (mac_equals($mac, '01:00:81:00:00:02')) $app = 'Synoptics Network Management';
	if (mac_equals($mac, '01:00:81:00:01:00')) $app = 'Bay Networks (Synoptics) autodiscovery, EtherType is 0x0802 SNAP type is 0x01A2';
	if (mac_equals($mac, '01:00:81:00:01:01')) $app = 'Bay Networks (Synoptics) autodiscovery, EtherType is 0x0802 SNAP type is 0x01A1';
	if (mac_between($mac, '01:20:25:00:00:00', '01:20:25:7F:FF:FF')) $app = 'Control Technology Inc\'s Industrial Ctrl Proto., EtherType is 0x873A';
	if (mac_equals($mac, '01:80:24:00:00:00')) $app = 'Kalpana Etherswitch every 60 seconds, EtherType is 0x0802';
	if (mac_equals($mac, '01:DD:00:FF:FF:FF')) $app = 'Ungermann-Bass boot-me requests, EtherType is 0x7002';
	if (mac_equals($mac, '01:DD:01:00:00:00')) $app = 'Ungermann-Bass Spanning Tree, EtherType is 0x7005';
	if (mac_equals($mac, '03:00:00:00:00:10')) $app = 'OS/2 1.3 EE + Communications Manager, EtherType is 0x80D5';
	if (mac_equals($mac, '03:00:00:00:00:40')) $app = 'OS/2 1.3 EE + Communications Manager, EtherType is 0x80D5';
	if (mac_equals($mac, '03:00:00:00:01:00')) $app = 'OSI All-IS Multicast, EtherType is 0x0802';
	if (mac_equals($mac, '03:00:00:00:02:00')) $app = 'OSI All-ES Multicast, EtherType is 0x0802';
	if (mac_equals($mac, '03:00:00:80:00:00')) $app = 'Discovery Client, EtherType is 0x0802';
	if (mac_equals($mac, '03:00:FF:FF:FF:FF')) $app = 'All Stations address, EtherType is 0x0802';
	if (mac_between($mac, '09:00:0D:00:00:00', '09:00:0D:FF:FF:FF')) $app = 'ICL Oslan Multicast, EtherType is 0x0802';
	if (mac_equals($mac, '09:00:0D:02:00:00')) $app = 'ICL Oslan Service discover only on boot';
	if (mac_equals($mac, '09:00:0D:02:0A:3C')) $app = 'ICL Oslan Service discover only on boot';
	if (mac_equals($mac, '09:00:0D:02:0A:38')) $app = 'ICL Oslan Service discover only on boot';
	if (mac_equals($mac, '09:00:0D:02:0A:39')) $app = 'ICL Oslan Service discover only on boot';
	if (mac_equals($mac, '09:00:0D:02:FF:FF')) $app = 'ICL Oslan Service discover only on boot';
	if (mac_equals($mac, '09:00:0D:09:00:00')) $app = 'ICL Oslan Service discover as required';
	if (mac_equals($mac, '09:00:1E:00:00:00')) $app = 'Apollo DOMAIN, EtherType is 0x8019';
	if (mac_equals($mac, '09:00:02:04:00:01')) $app = 'Vitalink printer messages, EtherType is 0x8080';
	if (mac_equals($mac, '09:00:02:04:00:02')) $app = 'Vitalink bridge management, EtherType is 0x8080';
	if (mac_equals($mac, '09:00:4C:00:00:0F')) $app = 'BICC Remote bridge adaptive routing (e.g. to Retix), EtherType is 0x0802';
	if (mac_equals($mac, '09:00:4E:00:00:02')) $app = 'Novell IPX, EtherType is 0x8137';
	if (mac_equals($mac, '09:00:6A:00:01:00')) $app = 'TOP NetBIOS';
	if (mac_equals($mac, '09:00:7C:01:00:01')) $app = 'Vitalink DLS Multicast';
	if (mac_equals($mac, '09:00:7C:01:00:03')) $app = 'Vitalink DLS Inlink';
	if (mac_equals($mac, '09:00:7C:01:00:04')) $app = 'Vitalink DLS and non DLS Multicast';
	if (mac_equals($mac, '09:00:7C:02:00:05')) $app = 'Vitalink diagnostics, EtherType is 0x8080';
	if (mac_equals($mac, '09:00:7C:05:00:01')) $app = 'Vitalink gateway, EtherType is 0x8080';
	if (mac_equals($mac, '09:00:7C:05:00:02')) $app = 'Vitalink Network Validation Message';
	if (mac_equals($mac, '09:00:09:00:00:01')) $app = 'HP Probe, EtherType is 0x8005 or 0x0802';
	if (mac_equals($mac, '09:00:09:00:00:04')) $app = 'HP DTC, EtherType is 0x8005';
	if (mac_equals($mac, '09:00:26:01:00:01')) $app = 'Vitalink TransLAN bridge management, EtherType is 0x8038';
	if (mac_equals($mac, '09:00:39:00:70:00')) $app = 'Spider Systems Bridge';
	if (mac_between($mac, '09:00:56:00:00:00', '09:00:56:FE:FF:FF')) $app = 'Stanford reserved';
	if (mac_between($mac, '09:00:56:FF:00:00', '09:00:56:FF:FF:FF')) $app = 'Stanford V Kernel, version 6.0, EtherType is 0x805C';
	if (mac_equals($mac, '09:00:77:00:00:00')) $app = 'Retix Bridge Local Management System, EtherType is 0x0802';
	if (mac_equals($mac, '09:00:77:00:00:01')) $app = 'Retix spanning tree bridges, EtherType is 0x0802';
	if (mac_equals($mac, '09:00:77:00:00:02')) $app = 'Retix Bridge Adaptive routing, EtherType is 0x0802';
	if (mac_equals($mac, '09:00:87:80:FF:FF')) $app = 'Xyplex Terminal Servers, EtherType is 0x0889';
	if (mac_equals($mac, '09:00:87:90:FF:FF')) $app = 'Xyplex Terminal Servers, EtherType is 0x0889';
	if (mac_between($mac, '44:38:39:FF:00:00', '44:38:39:FF:FF:FF')) $app = 'Multi-Chassis Link Aggregation (Cumulus Linux)';
	if (mac_equals($mac, 'FF:FF:00:40:00:01')) $app = 'LANtastic, EtherType is 0x81D6';
	if (mac_equals($mac, 'FF:FF:00:60:00:04')) $app = 'LANtastic, EtherType is 0x81D6';
	if (mac_equals($mac, 'FF:FF:01:E0:00:04')) $app = 'LANtastic';

	// === FAQ "The "CF" series MAC addresses" ===
	// https://www.iana.org/assignments/ppp-numbers/ppp-numbers.xhtml
	// https://tools.ietf.org/html/rfc2153
	// https://tools.ietf.org/html/rfc7042#section-2.3.2
	if (mac_between($mac, 'CF:00:00:00:00:00', 'CF:00:00:FF:FF:FF')) $app = 'Reserved';
	if (mac_equals($mac, 'CF:00:00:00:00:00')) $app = 'Used for Ethernet loopback tests';

	// === FAQ "How to recognise a Broadcast MAC address application?" ===
	// According to https://standards.ieee.org/wp-content/uploads/import/documents/tutorials/eui.pdf FFFFFFFFFFFF can be used as NULL EUI
	if (mac_equals($mac, 'FF:FF:FF:FF:FF:FF')) echo sprintf("%-32s %s\n", "Special use:", "Broadcast messaging or Null-EUI");

	// === FAQ "How to recognise a Virtual Router ID by MAC address?" ===
	// https://tools.ietf.org/html/rfc7042#section-5.1
	// https://tools.ietf.org/html/rfc5798
	if (mac_between($mac, '00:00:5E:00:01:00', '00:00:5E:00:01:FF')) $app = 'IPv4 Virtual Router Redundancy Protocol  (VRRP)';
	if (mac_between($mac, '00:00:5E:00:02:00', '00:00:5E:00:02:FF')) $app = 'IPv6 Virtual Router Redundancy Protocol';

	// === FAQ "How to recognise an IP frame by MAC address?" ===
	// https://tools.ietf.org/html/rfc1060
	// https://en.wikipedia.org/wiki/Multicast_address#cite_note-15
	// https://tools.ietf.org/html/rfc2464
	// https://www.iana.org/go/rfc1112
	// http://www.cavebear.com/archive/cavebear/Ethernet/Ethernet.txt
	if (mac_between($mac, '01:00:5E:00:00:00', '01:00:5E:7F:FF:FF')) $app = 'IPv4 Multicast (EtherType is 0x0800)';
	if (mac_between($mac, '33:33:00:00:00:00', '33:33:FF:FF:FF:FF')) $app = 'IPv6 Multicast. IPv6 neighbor discovery (EtherType is 0x86DD)'; // TODO: Dabei werden die untersten 32 Bit der IPv6-Multicast-Adresse in die MAC-Adresse eingebettet.
	if (mac_between($mac, '00:00:5E:00:52:13', '00:00:5E:00:52:13')) $app = 'Proxy Mobile IPv6';
	if (mac_between($mac, '00:00:5E:FE:C0:00:02:00', '00:00:5E:FE:C0:00:02:FF')) $app = 'IPv4 derived documentation';
	if (mac_between($mac, '00:00:5E:FE:C6:33:64:00', '00:00:5E:FE:C6:33:64:FF')) $app = 'IPv4 derived documentation';
	if (mac_between($mac, '00:00:5E:FE:CB:00:71:00', '00:00:5E:FE:CB:00:71:FF')) $app = 'IPv4 derived documentation';
	if (mac_equals($mac, '00:00:5E:FE:EA:C0:00:02')) $app = 'IPv4 multicast derived documentation';
	if (mac_equals($mac, '00:00:5E:FE:EA:C6:33:64')) $app = 'IPv4 multicast derived documentation';
	if (mac_equals($mac, '00:00:5E:FE:EA:CB:00:71')) $app = 'IPv4 multicast derived documentation';
	if (mac_between($mac, '01:00:5E:FE:C0:00:02:00', '01:00:5E:FE:C0:00:02:FF')) $app = 'IPv4 derived documentation';
	if (mac_between($mac, '01:00:5E:FE:C6:33:64:00', '01:00:5E:FE:C6:33:64:FF')) $app = 'IPv4 derived documentation';
	if (mac_between($mac, '01:00:5E:FE:CB:00:71:00', '01:00:5E:FE:CB:00:71:FF')) $app = 'IPv4 derived documentation';
	if (mac_equals($mac, '01:00:5E:FE:EA:C0:00:02')) $app = 'IPv4 multicast derived documentation';
	if (mac_equals($mac, '01:00:5E:FE:EA:C6:33:64')) $app = 'IPv4 multicast derived documentation';
	if (mac_equals($mac, '01:00:5E:FE:EA:CB:00:71')) $app = 'IPv4 multicast derived documentation';
	if (mac_between($mac, '01:80:C2:00:00:20', '01:80:C2:00:00:2F')) $app = 'Reserved for use by Multiple Registration Protocol (MRP) applications';
	if (mac_between($mac, '02:00:5E:FE:00:00:00:00', '02:00:5E:FE:FF:FF:FF:FF')) $app = 'IPv4 Addr Holders';
	if (mac_equals($mac, '03:00:00:20:00:00')) $app = 'IP multicast address';
	if (mac_equals($mac, 'C0:00:00:04:00:00')) $app = 'IP multicast address';
	if (mac_between($mac, '03:00:5E:FE:00:00:00:00', '03:00:5E:FE:FF:FF:FF:FF')) $app = 'IPv4 Addr Holders';

	// === FAQ "How to recognise a MPLS multicast frame by MAC address?" ===
	// http://www.iana.org/go/rfc5332
	// http://www.iana.org/go/rfc7213
	if (mac_between($mac, '01:00:5E:80:00:00', '01:00:5E:8F:FF:FF')) $app = 'MPLS multicast (EtherType is 0x8847 or 0x8848)';
	if (mac_equals($mac, '01:00:5E:90:00:00')) $app = 'MPLS-TP p2p';

	// === FAQ "How to recognise a Bidirectional Forwarding Detection (BFD) on Link Aggregation Group (LAG) interfaces by MAC address?" ===
	// http://www.iana.org/go/rfc7130
	if (mac_equals($mac, '01:00:5E:90:00:01')) $app = 'Bidirectional Forwarding Detection (BFD) on Link Aggregation Group (LAG) interfaces';

	// === FAQ "How to recognise Token Ring specific functions by MAC address?" ===
	// https://tools.ietf.org/html/rfc1060
	// https://tools.ietf.org/html/rfc1469
	// https://standards.ieee.org/products-services/regauth/grpmac/public.html
	// https://tools.ietf.org/html/rfc2470
	// http://www.cavebear.com/archive/cavebear/Ethernet/Ethernet.txt
	if (mac_equals($mac, '03:00:00:00:00:01')) $app = 'NetBIOS (Token Ring)';
	if (mac_equals($mac, '03:00:00:00:00:02')) $app = 'Locate - Directory Server (Token Ring)';
	if (mac_equals($mac, '03:00:00:00:00:04')) $app = 'Synchronous Bandwidth Manager (Token Ring)';
	if (mac_equals($mac, '03:00:00:00:00:08')) $app = 'Configuration Report Server (Token Ring)';
	if (mac_equals($mac, '03:00:00:00:00:10')) $app = 'Ring Error Monitor (Token Ring)';
	if (mac_equals($mac, '03:00:00:00:00:20')) $app = 'Network Server Heartbeat (Token Ring)';
	if (mac_equals($mac, '03:00:00:00:00:40')) $app = 'Ring Parameter Monitor (Token Ring)';
	if (mac_equals($mac, '03:00:00:00:00:80')) $app = 'Active Monitor (Token Ring)';
	if (mac_equals($mac, '03:00:00:00:04:00')) $app = 'LAN Manager (Token Ring)';
	if (mac_equals($mac, '03:00:00:00:08:00')) $app = 'Ring Wiring Concentrator (Token Ring)';
	if (mac_equals($mac, '03:00:00:00:10:00')) $app = 'LAN Gateway (Token Ring)';
	if (mac_equals($mac, '03:00:00:00:20:00')) $app = 'Ring Authorization Server (Token Ring)';
	if (mac_equals($mac, '03:00:00:00:40:00')) $app = 'IMPL Server (Token Ring)';
	if (mac_equals($mac, '03:00:00:00:80:00')) $app = 'Bridge (Token Ring)';
	if (mac_equals($mac, '03:00:00:20:00:00')) $app = 'Single Token-Ring functional address';
	if (mac_equals($mac, '03:00:00:00:00:08')) $app = 'Configuration Report Server (CRS) MAC Group address';
	if (mac_equals($mac, '03:00:00:00:00:10')) $app = 'Ring Error Monitor (REM) MAC Group address';
	if (mac_equals($mac, '03:00:00:00:00:40')) $app = 'Ring Parameter Server (RPS) MAC group address';
	if (mac_equals($mac, '03:00:00:00:01:00')) $app = 'All Intermediate System Network Entities address';
	if (mac_equals($mac, '03:00:00:00:02:00')) $app = 'All End System Network Entities address, and Lobe Media Test (LMT) MAC group address';
	if (mac_equals($mac, '03:00:00:00:04:00')) $app = 'Generic address for all Manager Stations';
	if (mac_equals($mac, '03:00:00:00:08:00')) $app = 'All CONs SNARES address';
	if (mac_equals($mac, '03:00:00:00:10:00')) $app = 'All CONs End System address';
	if (mac_equals($mac, '03:00:00:00:20:00')) $app = 'Loadable Device Generic address';
	if (mac_equals($mac, '03:00:00:00:40:00')) $app = 'Load Server Generic address';
	if (mac_equals($mac, '03:00:00:40:00:00')) $app = 'Generic address for all Agent Stations';
	if (mac_equals($mac, 'C0:00:00:04:00:00')) $app = 'Single Token-Ring functional address';
	if (mac_equals($mac, '03:00:80:00:00:00')) $app = 'IPv6 multicast over Token Ring: all-Nodes (FF01::1 and FF02::1) and solicited node (FF02:0:0:0:0:1:FFXX:XXXX) addresses';
	if (mac_equals($mac, '03:00:40:00:00:00')) $app = 'IPv6 multicast over Token Ring: all-Routers addresses (FF0X::2)';
	if (mac_equals($mac, '03:00:00:80:00:00')) $app = 'IPv6 multicast over Token Ring: any other multicast address with three least significant bits = 000';
	if (mac_equals($mac, '03:00:00:40:00:00')) $app = 'IPv6 multicast over Token Ring: any other multicast address with three least significant bits = 001';
	if (mac_equals($mac, '03:00:00:20:00:00')) $app = 'IPv6 multicast over Token Ring: any other multicast address with three least significant bits = 010';
	if (mac_equals($mac, '03:00:00:10:00:00')) $app = 'IPv6 multicast over Token Ring: any other multicast address with three least significant bits = 011';
	if (mac_equals($mac, '03:00:00:08:00:00')) $app = 'IPv6 multicast over Token Ring: any other multicast address with three least significant bits = 100';
	if (mac_equals($mac, '03:00:00:04:00:00')) $app = 'IPv6 multicast over Token Ring: any other multicast address with three least significant bits = 101';
	if (mac_equals($mac, '03:00:00:02:00:00')) $app = 'IPv6 multicast over Token Ring: any other multicast address with three least significant bits = 110';
	if (mac_equals($mac, '03:00:00:01:00:00')) $app = 'IPv6 multicast over Token Ring: any other multicast address with three least significant bits = 111';

	// === FAQ "How to recognise an AppleTalk protocols by MAC address?" ===
	// https://tools.ietf.org/html/rfc1060
	// http://www.cavebear.com/archive/cavebear/Ethernet/Ethernet.txt
	if (mac_between($mac, '09:00:07:00:00:00', '09:00:07:00:00:FC')) $app = 'AppleTalk zone multicast addresses (EtherType is 0x0802)';
	if (mac_equals($mac, '09:00:07:FF:FF:FF')) $app = 'AppleTalk broadcast address (EtherType is 0x0802)';

	// === FAQ "How to recognise a TRILL protocols by MAC address?" ===
	// http://www.iana.org/go/rfc7455
	// https://tools.ietf.org/html/draft-ietf-trill-oam-framework-04
	// https://standards.ieee.org/products-services/regauth/grpmac/public.html
	// https://tools.ietf.org/html/rfc7455#appendix-C
	if (mac_between($mac, '00:00:5E:90:01:00', '00:00:5E:90:01:00')) $app = 'TRILL OAM';
	if (mac_equals($mac, '01:00:5E:90:01:00')) $app = 'TRILL OAM';
	if (mac_between($mac, '01:80:C2:00:00:40', '01:80:C2:00:00:4F')) $app = 'Group MAC addresses used by the TRILL protocols';

	// === FAQ "How to recognise an IEEE 802.1X MAC address application?" ===
	if (mac_between($mac, '01:0C:CD:01:00:00', '01:0C:CD:01:01:FF')) $app = 'IEC 61850-8-1 GOOSE Type 1/1A, EtherType is 0x88B8';
	if (mac_between($mac, '01:0C:CD:02:00:00', '01:0C:CD:02:01:FF')) $app = 'GSSE (IEC 61850 8-1), EtherType is 0x88B9';
	if (mac_between($mac, '01:0C:CD:04:00:00', '01:0C:CD:04:01:FF')) $app = 'Multicast sampled values (IEC 61850 8-1), EtherType is 0x88BA';
	if (mac_equals($mac, '01:1B:19:00:00:00')) $app = 'General group address - An 802.1Q VLAN Bridge would forward the frame unchanged.';
	if (mac_equals($mac, '01:1B:19:00:00:00')) $app = 'Precision Time Protocol (PTP) version 2 over Ethernet, EtherType is 0x88F7';
	if (mac_equals($mac, '01:80:C2:00:00:00')) $app = 'Bridge Group address Nearest Customer Bridge group address';
	if (mac_equals($mac, '01:80:C2:00:00:00')) $app = 'Spanning Tree Protocol (for bridges) IEEE 802.1D, EtherType is 0x0802';
	if (mac_equals($mac, '01:80:C2:00:00:00')) $app = 'Link Layer Discovery Protocol, EtherType is 0x88CC';
	if (mac_between($mac, '01:80:C2:00:00:00', '01:80:C2:00:00:0F')) $app = 'The initial bridging/link protocols block';
	if (mac_between($mac, '01:80:C2:00:00:00', '01:80:C2:00:00:0F')) $app = 'IEEE 802.1D MAC Bridge Filtered MAC Group Addresses';
	if (mac_between($mac, '01:80:C2:00:00:00', '01:80:C2:00:00:0F')) $app = 'IEEE Pause, 802.3x';
	if (mac_equals($mac, '01:80:C2:00:00:0A')) $app = 'Reserved for future standardization';
	if (mac_equals($mac, '01:80:C2:00:00:0B')) $app = 'EDE-SS PEP Address';
	if (mac_equals($mac, '01:80:C2:00:00:0C')) $app = 'Reserved for future standardization';
	if (mac_equals($mac, '01:80:C2:00:00:0D')) $app = 'Provider Bridge MVRP address';
	if (mac_equals($mac, '01:80:C2:00:00:0E')) $app = 'Individual LAN Scope group address, It is intended that no IEEE 802.1 relay device will be defined that will forward frames that carry this destination address';
	if (mac_equals($mac, '01:80:C2:00:00:0E')) $app = 'Nearest Bridge group address';
	if (mac_equals($mac, '01:80:C2:00:00:0E')) $app = 'Link Layer Discovery Protocol, EtherType is 0x88CC';
	if (mac_equals($mac, '01:80:C2:00:00:0E')) $app = 'Precision Time Protocol (PTP) version 2 over Ethernet, EtherType is 0x88F7';
	if (mac_equals($mac, '01:80:C2:00:00:01')) $app = 'IEEE MAC-specific Control Protocols group address';
	if (mac_equals($mac, '01:80:C2:00:00:01')) $app = 'Ethernet flow control (Pause frame) IEEE 802.3x, EtherType is 0x8808';
	if (mac_equals($mac, '01:80:C2:00:00:1A')) $app = 'Generic Address for All Agent Stations';
	if (mac_equals($mac, '01:80:C2:00:00:1B')) $app = 'All Multicast Capable End Systems address';
	if (mac_equals($mac, '01:80:C2:00:00:1C')) $app = 'All Multicast Announcements address';
	if (mac_equals($mac, '01:80:C2:00:00:1D')) $app = 'All Multicast Capable Intermediate Systems address';
	if (mac_equals($mac, '01:80:C2:00:00:1E')) $app = 'All DTR Concentrators MAC group address';
	if (mac_equals($mac, '01:80:C2:00:00:1F')) $app = 'EDE-CC PEP Address';
	if (mac_between($mac, '01:80:C2:00:00:01', '01:80:C2:00:00:0F')) $app = '802.1 alternate Spanning multicast, EtherType is 0x0802';
	if (mac_equals($mac, '01:80:C2:00:00:02')) $app = 'Ethernet OAM Protocol IEEE 802.3ah (also known as "slow protocols"), EtherType is 0x8809';
	if (mac_equals($mac, '01:80:C2:00:00:03')) $app = 'Nearest non-TPMR Bridge group address IEEE Std 802.1X PAE address';
	if (mac_equals($mac, '01:80:C2:00:00:03')) $app = 'Link Layer Discovery Protocol, EtherType is 0x88CC';
	if (mac_equals($mac, '01:80:C2:00:00:04')) $app = 'IEEE MAC-specific Control Protocols group address';
	if (mac_equals($mac, '01:80:C2:00:00:05')) $app = 'Reserved for future standardization';
	if (mac_equals($mac, '01:80:C2:00:00:06')) $app = 'Reserved for future standardization';
	if (mac_equals($mac, '01:80:C2:00:00:07')) $app = 'MEF Forum ELMI protocol group address';
	if (mac_equals($mac, '01:80:C2:00:00:08')) $app = 'Provider Bridge group address';
	if (mac_equals($mac, '01:80:C2:00:00:08')) $app = 'Spanning Tree Protocol (for provider bridges) IEEE 802.1ad, EtherType is 0x0802';
	if (mac_equals($mac, '01:80:C2:00:00:09')) $app = 'Reserved for future standardization';
	if (mac_equals($mac, '01:80:C2:00:00:10')) $app = 'All LANs Bridge Management group address (deprecated)';
	if (mac_equals($mac, '01:80:C2:00:00:10')) $app = 'Bridge Management, EtherType is 0x0802';
	if (mac_equals($mac, '01:80:C2:00:00:11')) $app = 'Load Server generic address';
	if (mac_equals($mac, '01:80:C2:00:00:11')) $app = 'Load Server, EtherType is 0x0802';
	if (mac_equals($mac, '01:80:C2:00:00:12')) $app = 'Loadable Device generic address';
	if (mac_equals($mac, '01:80:C2:00:00:12')) $app = 'Loadable Device, EtherType is 0x0802';
	if (mac_equals($mac, '01:80:C2:00:00:13')) $app = 'Transmission of IEEE 1905.1 control packets';
	if (mac_equals($mac, '01:80:C2:00:00:14')) $app = 'All Level 1 Intermediate Systems address';
	if (mac_equals($mac, '01:80:C2:00:00:14')) $app = 'OSI Route level 1 (within area), EtherType is 0x0802';
	if (mac_equals($mac, '01:80:C2:00:00:15')) $app = 'All Level 2 Intermediate Systems address';
	if (mac_equals($mac, '01:80:C2:00:00:15')) $app = 'OSI Route level 2 (between area), EtherType is 0x0802';
	if (mac_equals($mac, '01:80:C2:00:00:16')) $app = 'All CONS End Systems address';
	if (mac_equals($mac, '01:80:C2:00:00:17')) $app = 'All CONS SNARES address';
	if (mac_equals($mac, '01:80:C2:00:00:18')) $app = 'Generic address for All Manager Stations';
	if (mac_equals($mac, '01:80:C2:00:00:19')) $app = 'Groupcast with retries (GCR) MAC group address';
	if (mac_between($mac, '01:80:C2:00:00:20', '01:80:C2:00:00:2F')) $app = 'Reserved for use by Multiple Registration Protocol (MRP) applications';
	if (mac_equals($mac, '01:80:C2:00:00:21')) $app = 'GARP VLAN Registration Protocol (also known as IEEE 802.1q GVRP), EtherType is 0x88f5';
	if (mac_between($mac, '01:80:C2:00:00:30', '01:80:C2:00:00:3F')) $app = 'Destination group MAC addresses for CCM and Linktrace messages';
	if (mac_between($mac, '01:80:C2:00:00:30', '01:80:C2:00:00:3F')) $app = 'Ethernet CFM Protocol IEEE 802.1ag, EtherType is 0x8902';
	if (mac_between($mac, '01:80:C2:00:00:50', '01:80:C2:00:00:FF')) $app = 'Unassigned standard group MAC address';
	if (mac_equals($mac, '01:80:C2:00:01:00')) $app = 'Ring Management Directed Beacon multicast address';
	if (mac_equals($mac, '01:80:C2:00:01:00')) $app = 'FDDI RMT Directed Beacon, EtherType is 0x0802';
	if (mac_between($mac, '01:80:C2:00:01:01', '01:80:C2:00:01:0F')) $app = 'Assigned to ISO/IEC JTC1/SC25 for future use';
	if (mac_equals($mac, '01:80:C2:00:01:10')) $app = 'Status Report Frame Status Report Protocol multicast address';
	if (mac_equals($mac, '01:80:C2:00:01:10')) $app = 'FDDI status report frame, EtherType is 0x0802';
	if (mac_between($mac, '01:80:C2:00:01:11', '01:80:C2:00:01:1F')) $app = 'Assigned to ISO/IEC JTC1/SC25 for future use';
	if (mac_equals($mac, '01:80:C2:00:01:20')) $app = 'All FDDI Concentrator MACs';
	if (mac_between($mac, '01:80:C2:00:01:21', '01:80:C2:00:01:2F')) $app = 'Assigned to ISO/IEC JTC1/SC25 for future use';
	if (mac_equals($mac, '01:80:C2:00:01:30')) $app = 'Synchronous Bandwidth Allocation address';
	if (mac_between($mac, '01:80:C2:00:01:31', '01:80:C2:00:01:FF')) $app = 'Assigned to ISO/IEC JTC1/SC25 for future use';
	if (mac_between($mac, '01:80:C2:00:02:00', '01:80:C2:00:02:FF')) $app = 'Assigned to ETSI for future use';
	if (mac_between($mac, '01:80:C2:00:03:00', '01:80:C2:FF-FF-FF')) $app = 'Unassigned standard group MAC address';
	if (mac_equals($mac, '09:00:4C:00:00:00')) $app = 'BICC 802.1 management, EtherType is 0x0802';
	if (mac_equals($mac, '09:00:4C:00:00:0C')) $app = 'BICC Remote bridge STA 802.1(D) Rev8, EtherType is 0x0802';
	if (mac_equals($mac, '09:00:4C:00:00:02')) $app = 'BICC 802.1 management, EtherType is 0x0802';
	if (mac_equals($mac, '09:00:4C:00:00:06')) $app = 'BICC Local bridge STA 802.1(D) Rev6, EtherType is 0x0802';
	if (mac_between($mac, '33:33:00:00:00:00', '33:33:FF:FF:FF:FF')) $app = 'IPv6 multicast, EtherType is 0x86DD';

	// === FAQ "How to recognise an ISO 9542 ES-IS protocol's MAC address application?" ===
	// https://standards.ieee.org/products-services/regauth/grpmac/public.html
	if (mac_equals($mac, '09:00:2B:00:00:04')) $app = 'All End System Network Entities address';
	if (mac_equals($mac, '09:00:2B:00:00:05')) $app = 'All Intermediate System Network Entities address';

	// === FAQ "How to recognise an IANA MAC address application?" ===
	// https://www.iana.org/assignments/ethernet-numbers/ethernet-numbers.xhtml
	// http://www.iana.org/go/rfc7042
	// https://tools.ietf.org/html/rfc1060
	if (mac_between($mac, '00:00:5E:00-52:14', '00:00:5E:00:52:FF')) $app = 'Unassigned (small allocations)';
	if (mac_between($mac, '00:00:5E:00:00:00', '00:00:5E:00:00:FF')) $app = 'Reserved and require IESG Ratification for assignment';
	if (mac_between($mac, '00:00:5E:00:03:00', '00:00:5E:00:51:FF')) $app = 'Unassigned';
	if (mac_between($mac, '00:00:5E:00:52:00', '00:00:5E:00:52:FF')) $app = 'Is used for very small assignments. Currently, 3 out of these 256 values have been assigned.';
	if (mac_between($mac, '00:00:5E:00:52:00', '00:00:5E:00:52:00')) $app = 'PacketPWEthA';
	if (mac_between($mac, '00:00:5E:00:52:01', '00:00:5E:00:52:01')) $app = 'PacketPWEthB';
	if (mac_between($mac, '00:00:5E:00:52:02', '00:00:5E:00:52:12')) $app = 'Unassigned (small allocations)';
	if (mac_between($mac, '00:00:5E:00:53:00', '00:00:5E:00:53:FF')) $app = 'Assigned for use in documentation';
	if (mac_between($mac, '00:00:5E:00:54:00', '00:00:5E:90:00:FF')) $app = 'Unassigned';
	if (mac_between($mac, '00:00:5E:90:01:01', '00:00:5E:90:01:FF')) $app = 'Unassigned (small allocations requiring both unicast and multicast)';
	if (mac_between($mac, '00:00:5E:EF:10:00:00:00', '00:00:5E:EF:10:00:00:FF')) $app = 'General documentation';
	if (mac_between($mac, '00:00:5E:FF:FE:00:53:00', '00:00:5E:FF:FE:00:53:FF')) $app = 'EUI-48 derived documentation';
	if (mac_between($mac, '01:00:5E:00:00:00', '01:00:5E:7F:FF:FF')) $app = 'DoD Internet Multicast (EtherType is 0x0800)'; // TODO: IPv4-Multicast  (Dabei werden dann die unteren 23 Bit der IP-Multicast-Adresse direkt auf die untersten 23 Bit der MAC-Adresse abgebildet. Der IP-Multicast-Adresse 224.0.0.1 ist somit die Multicast-MAC-Adresse 01-00-5e-00-00-01 fest zugeordnet.)
	if (mac_between($mac, '01:00:5E:80:00:00', '01:00:5E:FF:FF:FF')) $app = 'DoD Internet';
	if (mac_equals($mac, '01:00:5E:90:00:02')) $app = 'AllL1MI-ISs';
	if (mac_equals($mac, '01:00:5E:90:00:03')) $app = 'AllL2MI-ISs';
	if (mac_between($mac, '01:00:5E:90:00:04', '01:00:5E:90:00:FF')) $app = 'Unassigned (small allocations)';
	if (mac_between($mac, '01:00:5E:90:01:01', '01:00:5E:90:01:FF')) $app = 'Unassigned (small allocations requiring both unicast and multicast)';
	if (mac_between($mac, '01:00:5E:90:02:00', '01:00:5E:90:0F:FF')) $app = 'Unassigned';
	if (mac_between($mac, '01:00:5E:90:02:00', '00:00:5E:FF:FF:FF')) $app = 'Unassigned';
	if (mac_between($mac, '01:00:5E:90:10:00', '01:00:5E:90:10:FF')) $app = 'Documentation';
	if (mac_between($mac, '01:00:5E:90:11:00', '01:00:5E:FF:FF:FF')) $app = 'Unassigned';
	if (mac_between($mac, '01:00:5E:EF:10:00:00:00', '01:00:5E:EF:10:00:00:FF')) $app = 'General documentation';
	if (mac_between($mac, '02:00:5E:00:00:00:00:00', '02:00:5E:0F:FF:FF:FF:FF')) $app = 'Reserved';
	if (mac_between($mac, '02:00:5E:10:00:00:00:00', '02:00:5E:10:00:00:00:FF')) $app = 'Documentation';
	if (mac_between($mac, '02:00:5E:10:00:00:01:00', '02:00:5E:EF:FF:FF:FF:FF')) $app = 'Unassigned';
	if (mac_between($mac, '02:00:5E:F0:00:00:00:00', '02:00:5E:FD:FF:FF:FF:FF')) $app = 'Reserved';
	if (mac_between($mac, '02:00:5E:FE:00:00:00:00', '02:00:5E:FE:FF:FF:FF:FF')) $app = 'IPv4 Addr Holders';
	if (mac_between($mac, '02:00:5E:FF:00:00:00:00', '02:00:5E:FF:FD:FF:FF:FF')) $app = 'Reserved';
	if (mac_between($mac, '02:00:5E:FF:FE:00:00:00', '02:00:5E:FF:FE:FF:FF:FF')) $app = 'IANA EUI-48 Holders';
	if (mac_between($mac, '02:00:5E:FF:FF:00:00:00', '02:00:5E:FF:FF:FF:FF:FF')) $app = 'Reserved';
	if (mac_between($mac, '03:00:5E:00:00:00:00:00', '03:00:5E:0F:FF:FF:FF:FF')) $app = 'Reserved';
	if (mac_between($mac, '03:00:5E:10:00:00:00:00', '03:00:5E:10:00:00:00:FF')) $app = 'Documentation';
	if (mac_between($mac, '03:00:5E:10:00:00:01:00', '03:00:5E:EF:FF:FF:FF:FF')) $app = 'Unassigned';
	if (mac_between($mac, '03:00:5E:F0:00:00:00:00', '03:00:5E:FD:FF:FF:FF:FF')) $app = 'Reserved';
	if (mac_between($mac, '03:00:5E:FF:00:00:00:00', '03:00:5E:FF:FD:FF:FF:FF')) $app = 'Reserved';
	if (mac_between($mac, '03:00:5E:FF:FE:00:00:00', '03:00:5E:FF:FE:FF:FF:FF')) $app = 'IANA EUI-48 Holders';
	if (mac_between($mac, '03:00:5E:FF:FF:00:00:00', '03:00:5E:FF:FF:FF:FF:FF')) $app = 'Reserved';

	// === FAQ "How to recognise a Cisco's MAC address application?" ===
	// https://www.cisco.com/c/en/us/support/docs/switches/catalyst-4500-series-switches/13414-103.html
	// https://tools.ietf.org/html/rfc1060
	// https://en.wikipedia.org/wiki/Multicast_address#cite_note-15
	// http://www.cavebear.com/archive/cavebear/Ethernet/Ethernet.txt
	if (mac_equals($mac, '01:00:0C:00:00:00')) $app = 'Inter Switch Link (ISL)';
	if (mac_equals($mac, '01:00:0C:CC:CC:CC')) $app = 'CDP (Cisco Discovery Protocol), VTP (VLAN Trunking Protocol), EtherType is 0x0802';
	if (mac_equals($mac, '01:00:0C:CC:CC:CC')) $app = 'Port Aggregation Protocol (PAgP), SNAP HDLC Protocol Type is 0x0104';
	if (mac_equals($mac, '01:00:0C:CC:CC:CC')) $app = 'Unidirectional Link Detection (UDLD), SNAP HDLC Protocol Type is 0x0111';
	if (mac_equals($mac, '01:00:0C:CC:CC:CC')) $app = 'Dynamic Trunking (DTP), SNAP HDLC Protocol Type is 0x2004';
	if (mac_equals($mac, '01:00:0C:CC:CC:CC')) $app = 'VLAN Trunking (VTP), SNAP HDLC Protocol Type is 0x2003';
	if (mac_equals($mac, '01:00:0C:CC:CC:CD')) $app = 'Cisco Shared Spanning Tree Protocol address, EtherType is 0x0802';
	if (mac_equals($mac, '01:00:0C:CC:CC:CD')) $app = 'Spanning Tree PVSTP+, SNAP HDLC Protocol Type is 0x010B';
	if (mac_equals($mac, '01:00:0C:CD:CD:CD')) $app = 'STP Uplink Fast, SNAP HDLC Protocol Type is 0x200A';
	if (mac_equals($mac, '01:00:0C:CD:CD:CE')) $app = 'VLAN Bridge, SNAP HDLC Protocol Type is 0x010C';
	if (mac_equals($mac, '01:00:0C:DD:DD:DD')) $app = 'CGMP (Cisco Group Management Protocol)';

	// === FAQ "How to recognise an ITU-T's MAC address application?" ===
	// https://www.itu.int/en/ITU-T/studygroups/2017-2020/15/Documents/IEEE-assigned_OUIs-30-06-2017.docx
	if (mac_between($mac, '01:19:A7:00:00:00', '01:19:A7:00:00:FF')) $app = 'R-APS per G.8032';
	if (mac_between($mac, '01:19:A7:52:76:90', '01:19:A7:52:76:9F')) $app = 'Multicast per G.9961';

	// === FAQ "How to recognise Digital Equipment Corporation's MAC address application?" ===
	if (mac_equals($mac, '09:00:2B:00:00:00')) $app = 'DEC MUMPS, EtherType is 0x6009';
	if (mac_equals($mac, '09:00:2B:00:00:0F')) $app = 'DEC Local Area Transport (LAT), EtherType is 0x6004';
	if (mac_equals($mac, '09:00:2B:00:00:01')) $app = 'DEC DSM/DDP, EtherType is 0x8039';
	if (mac_between($mac, '09:00:2B:00:00:10', '09:00:2B:00:00:1F')) $app = 'DEC Experimental';
	if (mac_equals($mac, '09:00:2B:00:00:02')) $app = 'DEC VAXELN, EtherType is 0x803B';
	if (mac_equals($mac, '09:00:2B:00:00:03')) $app = 'DEC Lanbridge Traffic Monitor (LTM), EtherType is 0x8038';
	if (mac_equals($mac, '09:00:2B:00:00:04')) $app = 'DEC MAP End System';
	if (mac_equals($mac, '09:00:2B:00:00:05')) $app = 'DEC MAP Intermediate System';
	if (mac_equals($mac, '09:00:2B:00:00:06')) $app = 'DEC CSMA/CD Encryption, EtherType is 0x803D';
	if (mac_equals($mac, '09:00:2B:00:00:07')) $app = 'DEC NetBios Emulator, EtherType is 0x8040';
	if (mac_equals($mac, '09:00:2B:01:00:00')) $app = 'DEC LanBridge, EtherType is 0x8038';
	if (mac_equals($mac, '09:00:2B:01:00:01')) $app = 'DEC LanBridge, EtherType is 0x8038';
	if (mac_equals($mac, '09:00:2B:02:00:00')) $app = 'DEC DNA Level 2 Routing';
	if (mac_equals($mac, '09:00:2B:02:01:00')) $app = 'DEC DNA Naming Service Advertisement, EtherType is 0x803C';
	if (mac_equals($mac, '09:00:2B:02:01:01')) $app = 'DEC DNA Naming Service Solicitation, EtherType is 0x803C';
	if (mac_equals($mac, '09:00:2B:02:01:02')) $app = 'DEC Distributed Time Service, EtherType is 0x803E';
	if (mac_equals($mac, '09:00:2B:02:01:09')) $app = 'DEC Availability Manager for Distributed Systems DECamds, EtherType is 0x8048';
	if (mac_between($mac, '09:00:2B:03:00:00', '09:00:2B:03:FF:FF')) $app = 'DEC default filtering by bridges';
	if (mac_equals($mac, '09:00:2B:04:00:00')) $app = 'DEC Local Area System Transport (LAST), EtherType is 0x8041';
	if (mac_equals($mac, '09:00:2B:23:00:00')) $app = 'DEC Argonaut Console, EtherType is 0x803A';
	if (mac_equals($mac, 'AB:00:00:01:00:00')) $app = 'DEC Maintenance Operation Protocol (MOP) Dump/Load Assistance, EtherType is 0x6001';
	if (mac_equals($mac, 'AB:00:00:02:00:00')) $app = 'DEC Maintenance Operation Protocol (MOP), EtherType is 0x6002';
	if (mac_equals($mac, 'AB:00:00:03:00:00')) $app = 'DECNET Phase IV end node, EtherType is 0x6003';
	if (mac_equals($mac, 'AB:00:00:04:00:00')) $app = 'DECNET Phase IV Router, EtherType is 0x6003';
	if (mac_between($mac, 'AB:00:00:05:00:00', 'AB:00:03:FF:FF:FF')) $app = 'Reserved DEC';
	if (mac_equals($mac, 'AB:00:03:00:00:00')) $app = 'DEC Local Area Transport (LAT) - old, EtherType is 0x6004';
	if (mac_between($mac, 'AB:00:04:00:00:00', 'AB:00:04:00:FF:FF')) $app = 'Reserved DEC customer private use';
	if (mac_between($mac, 'AB:00:04:01:00:00', 'AB:00:04:01:FF:FF')) $app = 'DEC Local Area VAX Cluster groups System Communication Architecture (SCA), EtherType is 0x6007';

	// https://standards.ieee.org/products-programs/regauth/grpmac/public/
	// TODO: Check for duplicates between these and the ones at the top
	// IEEE Std 802.1D and IEEE Std 802.1Q Reserved Addresses
	if (mac_equals($mac, '01-80-C2-00-00-00')) $app = 'IEEE Std 802.1Q / Bridge Group address, Nearest Customer Bridge group address';
	if (mac_equals($mac, '01-80-C2-00-00-01')) $app = 'IEEE Std 802.1Q / IEEE MAC-specific Control Protocols group address';
	if (mac_equals($mac, '01-80-C2-00-00-02')) $app = 'IEEE Std 802.1Q / IEEE 802.3 Slow_Protocols_Multicast address';
	if (mac_equals($mac, '01-80-C2-00-00-03')) $app = 'IEEE Std 802.1Q / Nearest non-TPMR Bridge group address, IEEE Std 802.1X PAE address';
	if (mac_equals($mac, '01-80-C2-00-00-04')) $app = 'IEEE Std 802.1Q / IEEE MAC-specific Control Protocols group address';
	if (mac_equals($mac, '01-80-C2-00-00-05')) $app = 'IEEE Std 802.1Q / Reserved for future standardization';
	if (mac_equals($mac, '01-80-C2-00-00-06')) $app = 'IEEE Std 802.1Q / Reserved for future standardization';
	if (mac_equals($mac, '01-80-C2-00-00-07')) $app = 'IEEE Std 802.1Q / MEF Forum ELMI protocol group address';
	if (mac_equals($mac, '01-80-C2-00-00-08')) $app = 'IEEE Std 802.1Q / Provider Bridge group address';
	if (mac_equals($mac, '01-80-C2-00-00-09')) $app = 'IEEE Std 802.1Q / Reserved for future standardization';
	if (mac_equals($mac, '01-80-C2-00-00-0A')) $app = 'IEEE Std 802.1Q / Reserved for future standardization';
	if (mac_equals($mac, '01-80-C2-00-00-0B')) $app = 'IEEE Std 802.1Q / EDE-SS PEP Address';
	if (mac_equals($mac, '01-80-C2-00-00-0C')) $app = 'IEEE Std 802.1Q / Reserved for future standardization';
	if (mac_equals($mac, '01-80-C2-00-00-0D')) $app = 'IEEE Std 802.1Q / Provider Bridge MVRP address';
	if (mac_equals($mac, '01-80-C2-00-00-0E')) $app = 'IEEE Std 802.1Q / Individual LAN Scope group address, Nearest Bridge group address';
	if (mac_equals($mac, '01-80-C2-00-00-0F')) $app = 'IEEE Std 802.1Q / Reserved for future standardization';
	// Standard Group MAC Addresses
	if (mac_equals($mac, '01-80-C2-00-00-10')) $app = 'All LANs Bridge Management Group Address (deprecated)';
	if (mac_equals($mac, '01-80-C2-00-00-11')) $app = 'Load Server Generic Address';
	if (mac_equals($mac, '01-80-C2-00-00-12')) $app = 'Loadable Device Generic Address';
	if (mac_equals($mac, '01-80-C2-00-00-13')) $app = 'Transmission of IEEE 1905.1 control packets';
	if (mac_equals($mac, '01-80-C2-00-00-14')) $app = 'All Level 1 Intermediate Systems Address';
	if (mac_equals($mac, '01-80-C2-00-00-15')) $app = 'All Level 2 Intermediate Systems Address';
	if (mac_equals($mac, '01-80-C2-00-00-16')) $app = 'All CONS End Systems Address';
	if (mac_equals($mac, '01-80-C2-00-00-17')) $app = 'All CONS SNARES Address';
	if (mac_equals($mac, '01-80-C2-00-00-18')) $app = 'Generic Address for All Manager Stations';
	if (mac_equals($mac, '01-80-C2-00-00-19')) $app = 'Groupcast with retries (GCR) MAC Group Address';
	if (mac_equals($mac, '01-80-C2-00-00-1A')) $app = 'Generic Address for All Agent Stations';
	if (mac_equals($mac, '01-80-C2-00-00-1B')) $app = 'All Multicast Capable End Systems Address';
	if (mac_equals($mac, '01-80-C2-00-00-1C')) $app = 'All Multicast Announcements Address';
	if (mac_equals($mac, '01-80-C2-00-00-1D')) $app = 'All Multicast Capable Intermediate Systems Address';
	if (mac_equals($mac, '01-80-C2-00-00-1E')) $app = 'All DTR Concentrators MAC Group Address';
	if (mac_equals($mac, '01-80-C2-00-00-1F')) $app = 'EDE-CC PEP Address';
	if (mac_between($mac, '01-80-C2-00-00-20','01-80-C2-00-00-2F')) $app = 'Reserved for use by Multiple Registration Protocol (MRP) applications';
	if (mac_between($mac, '01-80-C2-00-00-30','01-80-C2-00-00-3F')) $app = 'Destination group MAC addresses for CCM and Linktrace messages';
	if (mac_between($mac, '01-80-C2-00-00-40','01-80-C2-00-00-4F')) $app = 'Group MAC addresses used by the TRILL protocols';
	if (mac_between($mac, '01-80-C2-00-00-50','01-80-C2-00-00-FF')) $app = 'unassigned';
	if (mac_equals($mac, '01-80-C2-00-01-00')) $app = 'Ring Management Directed Beacon Multicast Address';
	if (mac_between($mac, '01-80-C2-00-01-01','01-80-C2-00-01-0F')) $app = 'Assigned to ISO/IEC JTC1/SC25 for future use';
	if (mac_equals($mac, '01-80-C2-00-01-10')) $app = 'Status Report Frame Status Report Protocol Multicast Address';
	if (mac_between($mac, '01-80-C2-00-01-11','01-80-C2-00-01-1F')) $app = 'Assigned to ISO/IEC JTC1/SC25 for future use';
	if (mac_equals($mac, '01-80-C2-00-01-20')) $app = 'ISO/IEC 9314-2 All FDDI Concentrator MACs';
	if (mac_between($mac, '01-80-C2-00-01-21','01-80-C2-00-01-2F')) $app = 'Assigned to ISO/IEC JTC1/SC25 for future use';
	if (mac_equals($mac, '01-80-C2-00-01-30')) $app = 'ISO/IEC 9314-6 Synchronous Bandwidth Allocation Address';
	if (mac_between($mac, '01-80-C2-00-01-31','01-80-C2-00-01-FF')) $app = 'Assigned to ISO/IEC JTC1/SC25 for future use';
	if (mac_between($mac, '01-80-C2-00-02-00','01-80-C2-00-02-FF')) $app = 'Assigned to ETSI for future use';
	if (mac_between($mac, '01-80-C2-00-03-00', '01-80-C2-FF-FF-FF')) $app = 'unassigned';
	if (mac_equals($mac, '09-00-2B-00-00-04')) $app = 'ISO 9542 All End System Network Entities Address';
	if (mac_equals($mac, '09-00-2B-00-00-05')) $app = 'ISO 9542 All Intermediate System Network Entities Address';
	// Group MAC Addresses Used in ISO 9542 ES-IS Protocol
	if (mac_equals($mac, '09-00-2B-00-00-04')) $app = 'ISO 9542 All End System Network Entities Address';
	if (mac_equals($mac, '09-00-2B-00-00-05')) $app = 'ISO 9542 All Intermediate System Network Entities Address';
	// Locally Administered Group MAC Addresses Used by IEEE Std 802.5 (IEEE Std 802.5 Functional Addresses)
	if (mac_equals($mac, '03-00-00-00-00-08')) $app = 'Configuration Report Server (CRS) MAC Group Address';
	if (mac_equals($mac, '03-00-00-00-00-10')) $app = 'Ring Error Monitor (REM) MAC Group Address';
	if (mac_equals($mac, '03-00-00-00-00-40')) $app = 'Ring Parameter Server (RPS) MAC Group Address';
	if (mac_equals($mac, '03-00-00-00-01-00')) $app = 'All Intermediate System Network Entities Address';
	if (mac_equals($mac, '03-00-00-00-02-00')) $app = 'All End System Network Entities Address, and Lobe Media Test (LMT) MAC Group Address';
	if (mac_equals($mac, '03-00-00-00-04-00')) $app = 'Generic Address for all Manager Stations';
	if (mac_equals($mac, '03-00-00-00-08-00')) $app = 'All CONs SNARES Address';
	if (mac_equals($mac, '03-00-00-00-10-00')) $app = 'All CONs End System Address';
	if (mac_equals($mac, '03-00-00-00-20-00')) $app = 'Loadable Device Generic Address';
	if (mac_equals($mac, '03-00-00-00-40-00')) $app = 'Load Server Generic Address';
	if (mac_equals($mac, '03-00-00-40-00-00')) $app = 'Generic Address for all Agent Stations';

	if ($app) {
		echo sprintf("%-32s %s\n", "Special use:", $app);
	}

}

/**
 * @param string $mac1
 * @param string $mac2
 * @return bool
 */
function mac_equals(string $mac1, string $mac2): bool {
	$mac1test = eui64_to_eui48($mac1);
	if ($mac1test === false) return false;
	$mac2test = eui64_to_eui48($mac2);
	if ($mac2test === false) return false;

	if (eui_bits($mac1test) != eui_bits($mac2test)) {
		$mac1test = eui48_to_eui64($mac1);
		$mac2test = eui48_to_eui64($mac2);
	}

	return mac_canonize($mac1test) == mac_canonize($mac2test);
}

/**
 * @param string $mac
 * @param string $low
 * @param string $high
 * @return bool
 */
function mac_between(string $mac, string $low, string $high): bool {
	$mactest = eui64_to_eui48($mac);
	if ($mactest === false) return false;
	$lowtest = eui64_to_eui48($low);
	if ($lowtest === false) return false;
	$hightest = eui64_to_eui48($high);
	if ($hightest === false) return false;

	if ((eui_bits($mactest) != eui_bits($lowtest)) || (eui_bits($lowtest) != eui_bits($hightest))) {
		$mactest = eui48_to_eui64($mac);
		if ($mactest === false) return false; // e.g. trying ELI-48 to ELI-64
		$lowtest = eui48_to_eui64($low);
		if ($lowtest === false) return false; // e.g. trying ELI-48 to ELI-64
		$hightest = eui48_to_eui64($high);
		if ($hightest === false) return false; // e.g. trying ELI-48 to ELI-64
	}

	$mactest = strtoupper(preg_replace('@[^0-9A-F]@', '', $mactest));
	$lowtest = strtoupper(preg_replace('@[^0-9A-F]@', '', $lowtest));
	$hightest = strtoupper(preg_replace('@[^0-9A-F]@', '', $hightest));

	$mactest = gmp_init($mactest, 16);
	$lowtest = gmp_init($lowtest, 16);
	$hightest = gmp_init($hightest, 16);

	return (gmp_cmp($mactest, $lowtest) >= 0) && (gmp_cmp($mactest, $hightest) <= 0);
}

/**
 * Gets the current MAC address of the system
 * @return string|false MAC address of the local system
 */
function get_mac_address() {
	static $detected_mac = false;

	if ($detected_mac !== false) { // false NOT null!
		return $detected_mac;
	}

	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
		// Windows
		$cmds = array(
			"ipconfig /all", // faster
			"getmac"
		);
		foreach ($cmds as $cmd) {
			$out = array();
			$ec = -1;
			exec($cmd, $out, $ec);
			if ($ec == 0) {
				$out = implode("\n",$out);
				$m = array();
				if (preg_match("/([0-9a-f]{2}-[0-9a-f]{2}-[0-9a-f]{2}-[0-9a-f]{2}-[0-9a-f]{2}-[0-9a-f]{2})/ismU", $out, $m)) {
					$detected_mac = strtolower($m[1]);
					return $detected_mac;
				}
			}
		}
	} else if (strtoupper(PHP_OS) == 'DARWIN') {
		// Mac OS X
		$cmds = array(
			"networksetup -listallhardwareports 2>/dev/null",
			"netstat -i 2>/dev/null"
		);
		foreach ($cmds as $cmd) {
			$out = array();
			$ec = -1;
			exec($cmd, $out, $ec);
			if ($ec == 0) {
				$out = implode("\n",$out);
				$m = array();
				if (preg_match("/([0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2})/ismU", $out, $m)) {
					$detected_mac = $m[1];
					return $detected_mac;
				}
			}
		}
	} else {
		// Linux
		$addresses = @glob('/sys/class/net/'.'*'.'/address');
		foreach ($addresses as $x) {
			if (!strstr($x,'/lo/')) {
				$detected_mac = trim(file_get_contents($x));
				if (substr(mac_type($detected_mac),0,6) == 'EUI-48') {
					return $detected_mac;
				}
			}
		}
		$cmds = array(
			"netstat -ie 2>/dev/null",
			"ifconfig 2>/dev/null" // only available for root (because it is in sbin)
		);
		foreach ($cmds as $cmd) {
			$out = array();
			$ec = -1;
			exec($cmd, $out, $ec);
			if ($ec == 0) {
				$out = implode("\n",$out);
				$m = array();
				if (preg_match("/([0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2})/ismU", $out, $m)) {
					$detected_mac = $m[1];
					return $detected_mac;
				}
			}
		}
	}

	return $detected_mac;
}
