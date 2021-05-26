<?php

/*
 * MAC utils for PHP
 * Copyright 2017 Daniel Marschall, ViaThinkSoft
 * Version 19 August 2017
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

define('IEEE_MAC_REGISTRY', __DIR__ . '/../web-data');

function mac_valid($mac) {
	$mac = str_replace(array('-', ':'), '', $mac);
	$mac = strtoupper($mac);

	if (strlen($mac) != 12) return false;

	$mac = preg_replace('@[0-9A-F]@', '', $mac);

	return ($mac == '');
}

function _lookup_ieee_registry($file, $oui_name, $mac) {
	$begin = substr($mac, 0, 2).'-'.substr($mac, 2, 2).'-'.substr($mac, 4, 2);
	$f = file_get_contents($file);

	$f = str_replace("\r", '', $f);

	# We are using a positive-lookahead because entries like the MA-M references have a blank line between organization and address
	preg_match_all('@^\s*'.preg_quote($begin, '@').'\s+\(hex\)\s+(\S+)\s+(.*)\n\n\s*(?=[0-9A-F])@ismU', "$f\n\nA", $m, PREG_SET_ORDER);
	foreach ($m as $n) {
		preg_match('@(\S+)\s+\(base 16\)(.*)$@ism', $n[2], $m);

		if (preg_match('@(.+)\-(.+)@ism', $m[1], $o)) {
			$z = hexdec(substr($mac, 6, 6));
			$beg = hexdec($o[1]);
			$end = hexdec($o[2]);
			if (($z < $beg) || ($z > $end)) continue;
		} else {
			$beg = 0x000000;
			$end = 0xFFFFFF;
		}

		$x = trim(preg_replace('@^\s+@ism', '', $m[2]));

		# "PRIVATE" entries are only marked at the "(hex)" line, but not at the "(base16)" line
		if ($x == '') $x = trim($n[1]);

		$x = explode("\n", $x);

		$ra_len = strlen(dechex($end-$beg));

		$out = '';
		$out .= sprintf("%-24s 0x%s\n", "IEEE $oui_name part:", substr($mac, 0, 12-$ra_len));
		$out .= sprintf("%-24s 0x%s\n", "NIC specific part:", substr($mac, 12-$ra_len));
		$out .= sprintf("%-24s %s\n", "Registrant:", $x[0]);
		foreach ($x as $n => $y) {
			if ($n == 0) continue;
			else if ($n == 1) $out .= sprintf("%-24s %s\n", "Address of registrant:", $y);
			else if ($n >= 2) $out .= sprintf("%-24s %s\n", "", $y);
		}

		// TODO: also print the date of last update of the OUI files

		return $out;
	}

	return false;
}

function decode_mac($mac) {
	if (!mac_valid($mac)) return false;

	// Format MAC
	$mac = strtoupper($mac);
	$mac = preg_replace('@[^0-9A-F]@', '', $mac);
	if (strlen($mac) != 12) {
		# echo "Invalid MAC address\n";
		return false;
	}
	$mac_ = preg_replace('@^(..)(..)(..)(..)(..)(..)$@', '\\1-\\2-\\3-\\4-\\5-\\6', $mac);
	echo sprintf("%-24s %s\n", "MAC address:", $mac_);

	// Empfaengergruppe
	$ig = hexdec($mac[1]) & 1; // Bit #LSB+0 of Byte 1
	$ig_ = ($ig == 0) ? '[0] Individual' : '[1] Group';
	echo sprintf("%-24s %s\n", "I/G flag:", $ig_);

	// Vergabestelle
	$ul = hexdec($mac[1]) & 2; // Bit #LSB+1 of Byte 1
	$ul_ = ($ul == 0) ? '[0] Universally Administered Address (UAA)' : '[1] Locally Administered Address (LAA)';
	echo sprintf("%-24s %s\n", "U/L flag:", $ul_);

	// Query IEEE registries
	// TODO: gilt OUI nur bei Individual UAA?
	if (
		($x = _lookup_ieee_registry(IEEE_MAC_REGISTRY.'/mam.txt', 'OUI-28 (MA-M)', $mac)) ||
		($x = _lookup_ieee_registry(IEEE_MAC_REGISTRY.'/oui36.txt', 'OUI-36 (MA-S)', $mac)) ||
		# The IEEE Registration Authority distinguishes between IABs and OUI-36 values. Both are 36-bit values which may be used to generate EUI-48 values, but IABs may not be used to generate EUI-64 values.[6]
		# Note: The Individual Address Block (IAB) is an inactive registry activity, which has been replaced by the MA-S registry product as of January 1, 2014.
		($x = _lookup_ieee_registry(IEEE_MAC_REGISTRY.'/iab.txt', 'IAB', $mac))
           ) {
		return $x;
	} else {
		return _lookup_ieee_registry(IEEE_MAC_REGISTRY.'/oui.txt', 'OUI-24 (MA-L)', $mac);
	}

	// TODO
	// FF-FF-FF-FF-FF-FF = Broadcast-Adresse

	// TODO
	// IP-Multicast
	// 01-00-5E-00-00-00 bis 01-00-5E-7F-FF-FF (unterste 23 bit der MAC = unterste 23 Bit der IP) ...
	//	224.0.0.1 -> 01-00-5e-00-00-01
	//	erste 4 Bits durch Class D konvention belegt. 5 bits sind unbekannt

	// TODO: VRRP
	// 00-00-5E-00-01-ID
}
