<?php

/*
 * IPv6 functions for PHP
 * Copyright 2012-2020 Daniel Marschall, ViaThinkSoft
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

// TODO: oop, exceptions?
// TODO: variant without gmp ?
// TODO: IPv6 auflösung 'ffff::192.168.69.1' -> 'ffff:0000:0000:0000:0000:0000:c0a8:4501' geht nicht!!!

if (file_exists(__DIR__ . '/gmp_supplement.inc.php')) include_once __DIR__ . '/gmp_supplement.inc.php';

define('GMP_ONE', gmp_init('1'));

// Very small self-test:
/*
function ipv6_selftest() {
	$iv_b = 'c0ff:ee00::';
	$iv_m = 32;
	$r = ipv6_cidr2range($iv_b, $iv_m);
	echo "$iv_b/$iv_m => $r[0] - $r[1]\n";

	$rev = ipv6_range2cidr($r[0], $r[1]);
	$rev = implode("\n", $rev);
	echo "$r[0] - $r[1] => $rev [";
	$ok = $rev == "$iv_b/$iv_m";
	echo $ok ? 'OK' : 'Mismatch';
	echo "]\n";
	echo "In-CIDR-Test: ";
	echo ipv6_in_cidr("$iv_b/$iv_m", "$iv_b/$iv_m") ? 'OK' : 'Fail';
	echo "\n";
}
ipv6_selftest();
*/

$cache_ipv6_cidr2range = array();
function ipv6_cidr2range($baseip_or_cidr, $subnet='') {
	# (C) 2012 ViaThinkSoft
	# Version 1.1
	# This function converts an CIDR notation <baseip>/<subnet> into an IPv6 address block array($low_ip, $high_ip)

	global $cache_ipv6_cidr2range;
	$vvv = $baseip_or_cidr.'|'.$subnet;
	if (isset($cache_ipv6_cidr2range[$vvv])) return $cache_ipv6_cidr2range[$vvv];

	if (strpos($baseip_or_cidr, '/') !== false) {
		$tmp = explode('/', $baseip_or_cidr, 2);
		$baseip_or_cidr = $tmp[0];
		$subnet = $tmp[1];
		unset($tmp);
	}

	if (($subnet < 0) || ($subnet > 128)) {
		$cache_ipv6_cidr2range[$vvv] = false;
		return false;
	}

	$maxint128 = gmp_sub(gmp_pow('2', 128), GMP_ONE); # TODO: GMP_TWO ?
	$netmask = gmp_shiftl($maxint128, 128-$subnet);
	$netmask = gmp_and($netmask, $maxint128); // crop to 128 bit
	$wildcard = gmp_xor($maxint128, $netmask);

	$x = gmp_and(ip2long6($baseip_or_cidr), $netmask);
	$nums = $wildcard;
	$low = long2ip6($x);
	$high = long2ip6(gmp_add($x, $nums));

	$out = array($low, $high);
	$cache_ipv6_cidr2range[$vvv] = $out;
	return $out;
}

$cache_ipv6_range2cidr = array();
function ipv6_range2cidr($baseip, $topip) {
	# (C) 2012 ViaThinkSoft
	# Version 1.0
	# This function converts an IPv6 address block into valid CIDR blocks (There may be multiple blocks!)

	global $cache_ipv6_range2cidr;
	$vvv = $baseip.'|'.$topip;
	if (isset($cache_ipv6_range2cidr[$vvv])) return $cache_ipv6_range2cidr[$vvv];

	$out = array();
	if (ipv6_cmp($baseip, $topip) > 0) {
		$cache_ipv6_range2cidr[$vvv] = false;
		return false;
	}
	while (gmp_cmp(gmp_sub(ip2long6($baseip), GMP_ONE), ip2long6($topip)) != 0) {
		$i = -1;
		do {
			$i++;
			$range = ipv6_cidr2range($baseip, $i);
			$l = $range[0];
			$t = $range[1];
		} while ((ipv6_cmp($l, $baseip) != 0) || (ipv6_cmp($t, $topip) > 0));

		$out[] = "$baseip/$i";
		$baseip = ipv6_add($t, GMP_ONE);
	}

	$cache_ipv6_range2cidr[$vvv] = $out;
	return $out;
}

function ipv6_add($baseip, $num) {
	# (C) 2012 ViaThinkSoft
	# Version 1.0

	return long2ip6(gmp_add(ip2long6($baseip), $num));
}

function ipv6_sub($baseip, $num) {
	# (C) 2012 ViaThinkSoft
	# Version 1.0

	return long2ip6(gmp_sub(ip2long6($baseip), $num));
}

function ipv6_cmp($a, $b) {
	# (C) 2012 ViaThinkSoft
	# Version 1.0

	return gmp_cmp(ip2long6($a), ip2long6($b));
}

$cache_ipv6_in_cidr = array();
function ipv6_in_cidr($haystack, $needle) {
	# (C) 2012 ViaThinkSoft
	# Version 1.1

	global $cache_ipv6_in_cidr;
	$vvv = $haystack.'|'.$needle;
	if (isset($cache_ipv6_in_cidr[$vvv])) return $cache_ipv6_in_cidr[$vvv];

	$x = explode('/', $haystack);
	$ha = ipv6_cidr2range($x[0], $x[1]);

	$x = explode('/', $needle);
	if (!isset($x[1])) $x[1] = 128; // single IP
	$ne = ipv6_cidr2range($x[0], $x[1]);

	$ha_low = ip2long6($ha[0]);
	$ha_hig = ip2long6($ha[1]);
	$ne_low = ip2long6($ne[0]);
	$ne_hig = ip2long6($ne[1]);

	# HA:    low[                               ]high
	# NE:            low[             ]high

	$out = (gmp_cmp($ne_low, $ha_low) >= 0) && (gmp_cmp($ne_hig, $ha_hig) <= 0);
	$cache_ipv6_in_cidr[$vvv] = $out;
	return $out;
}

// IMPORTANT! $cmp_ary[x]=y MUST HAVE x<=y !
function ipv6_merge_address_blocks($data, $debug = false) {
	# (C) 2012-2013 ViaThinkSoft
	# Version 2.2

	if ($debug !== false) $STARTZEIT = time();

	// 1. Convert IPs to numbers

	$cmp_ary = array();
	foreach ($data as $a => &$b) {
		$a = ip2long6($a);
		$b = ip2long6($b);

		$cmp_ary[gmp_strval($a)] = gmp_strval($b);
		unset($a);
		unset($b);
	}

	// 2. Sort array

	ksort($cmp_ary);

	// 3. Merge the blocks in an intelligent way (and remove redundant blocks)

	# Merge overlapping blocks
	#   [          ]
	#           [            ]   ->   [                    ]

	# Merge neighbor blocks
	#   [   ][   ]   ->   [        ]

	# Remove redundant blocks
	#  [          ]   ->   [          ]
	#      [  ]

	$merge_count = 0;
	$redundant_deleted_count = 0;
	$round_count = 0;
	do {
		if ($debug !== false) {
			$LAUFZEIT = time() - $STARTZEIT;
			echo $debug."Merging... $round_count rounds; merged $merge_count blocks; deleted $redundant_deleted_count redundant blocks; time: $LAUFZEIT seconds\r";
		}

		$round_count++;

		$clean = true;

		foreach ($cmp_ary as $a => &$b) {
			foreach ($cmp_ary as $x => &$y) {
				// x in range [a+1..b+1] ?
				if (gmp_cmp(gmp_init($x), gmp_init($a)) <= 0) continue;
				if (gmp_cmp(gmp_init($x), gmp_add(gmp_init($b), GMP_ONE)) > 0) break;

				// Merge
				$clean = false;
				if (gmp_cmp(gmp_init($y), gmp_init($b)) > 0) {
					$merge_count++;
					$b = $y;
					unset($cmp_ary[$x]);
				} else {
					$redundant_deleted_count++;
					unset($cmp_ary[$x]);
				}
			}
		}
	} while (!$clean);

	if ($debug !== false) {
		$LAUFZEIT = time() - $STARTZEIT;
		echo $debug."Merge completed. $round_count rounds; merged $merge_count blocks; deleted $redundant_deleted_count redundant blocks; time: $LAUFZEIT seconds\n";
	}

	// 4. Convert back to IPs

	$out_ary = array();
	foreach ($cmp_ary as $a => &$b) {
		$a = long2ip6(gmp_init($a));
		$b = long2ip6(gmp_init($b));
		$out_ary[$a] = $b;
	}

	return $out_ary;
}

function ipv6_merge_arrays($data_a, $data_b) {
	# (C) 2012 ViaThinkSoft
	# Version 1.2

	$normalized_data_a = array();
	foreach ($data_a as $from => &$to) {
		$normalized_data_a[ipv6_normalize($from)] = ipv6_normalize($to);
	}

	$normalized_data_b = array();
	foreach ($data_b as $from => &$to) {
		$normalized_data_b[ipv6_normalize($from)] = ipv6_normalize($to);
	}

	$data = array();

	foreach ($normalized_data_a as $from => &$to) {
		if (isset($normalized_data_b[$from])) {
			$data[$from] = ipv6_max($to, $normalized_data_b[$from]);
		} else {
			$data[$from] = $to;
		}
	}

	foreach ($normalized_data_b as $from => &$to) {
		if (!isset($normalized_data_a[$from])) {
			$data[$from] = $to;
		}
	}

	return $data;
}

function ipv6_valid($ip) {
	# (C) 2012 ViaThinkSoft
	# Version 1.0

	return ip2long6($ip) !== false;
}

function ipv6_normalize($ip) {
	# (C) 2012 ViaThinkSoft
	# Version 1.0

	# Example:
	# 2001:0000:0000::1 -> 2001::1

	$long = ip2long6($ip);
	if ($long == -1 || $long === FALSE) return false;
	return long2ip6($long);
}

function ipv6_expand($ip) {
	# (C) 2012 ViaThinkSoft
	# Version 1.0

	# Example:
	# 2001::1 -> 2001:0000:0000:0000:0000:0000:0000:0000

	$long = ip2long6($ip);
	if ($long == -1 || $long === FALSE) return false;
	return long2ip6($long, false);
}

function ipv6_min($ip_a, $ip_b) {
	# (C) 2012 ViaThinkSoft
	# Version 1.0

	if (ipv6_cmp($ip_a, $ip_b) == -1) {
		return $ip_a;
	} else {
		return $ip_b;
	}
}

function ipv6_max($ip_a, $ip_b) {
	# (C) 2012 ViaThinkSoft
	# Version 1.0

	if (ipv6_cmp($ip_a, $ip_b) == 1) {
		return $ip_a;
	} else {
		return $ip_b;
	}
}

function ipv6_ipcount($data) {
	# (C) 2012 ViaThinkSoft
	# Version 1.0

	$cnt = gmp_init('0');

	foreach ($data as $from => &$to) {
		$cnt = gmp_add($cnt, gmp_sub(ip2long6($to), ip2long6($from)));
	}

	return gmp_strval($cnt, 10);
}

function ipv6_read_file($file) {
	# (C) 2012 ViaThinkSoft
	# Version 1.0

	$data = array();

	$lines = file($file);
	foreach ($lines as &$line) {
		$rng = ipv6_line2range($line);
		$data[$rng[0]] = $rng[1];
	}

	return $data;
}

function ipv6_line2range($line) {
	# (C) 2012 ViaThinkSoft
	# Version 1.0

	$line = trim($line);

	if (strpos($line, '/') !== false) {
		$rng = ipv6_cidr2range($line);
	} else {
		$rng = explode('-', $line);
		$rng[0] = trim($rng[0]);
		$rng[1] = trim($rng[1]);
		$rng[0] = ipv6_normalize($rng[0]);
		if (!isset($rng[1])) $rng[1] = $rng[0];
		$rng[1] = ipv6_normalize($rng[1]);
	}

	return $rng;
}

# ---

if (!function_exists('gmp_shiftl')) {
	function gmp_shiftl($x, $n) { // shift left
		// http://www.php.net/manual/en/ref.gmp.php#99788
		return gmp_mul($x, gmp_pow('2', $n));
	}
}

if (!function_exists('gmp_shiftr')) {
	function gmp_shiftr($x, $n) { // shift right
		// http://www.php.net/manual/en/ref.gmp.php#99788
		return gmp_div($x, gmp_pow('2', $n));
	}
}

$cache_ip2long6 = array();
function ip2long6($ipv6) {
	// Source:
	// http://www.netz-guru.de/2009/11/07/php-ipv6-ip2long-und-long2ip-funktionen/
	// Slightly modified

	global $cache_ip2long6;
	if (isset($cache_ip2long6[$ipv6])) return $cache_ip2long6[$ipv6];

	if ($ipv6 == '') $ipv6 = '::';

	if (filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
		$cache_ip2long6[$ipv6] = false;
		return false;
	}

	$ip_n = @inet_pton($ipv6);
	if ($ip_n === false) {
		$cache_ip2long6[$ipv6] = false;
		return false; // modified
	}
	$bytes = 16; // 16 bytes x 8 bit/byte = 128bit
	$ipv6long = '';

	while ($bytes > 0) {
		$bin = sprintf('%08b',(ord($ip_n[$bytes-1])));
		$ipv6long = $bin.$ipv6long;
		$bytes--;
	}

	// $out = gmp_strval(gmp_init($ipv6long, 2), 10);
	$out = gmp_init($ipv6long, 2);
	$cache_ip2long6[$ipv6] = $out;
	return $out;
}

$cache_long2ip6 = array();
function long2ip6($ipv6long, $compress=true) {
	// Source:
	// http://www.netz-guru.de/2009/11/07/php-ipv6-ip2long-und-long2ip-funktionen/
	// Slightly modified

	global $cache_long2ip6;
	$vvv = ($compress ? 'T' : 'F').$ipv6long;
	if (isset($cache_long2ip6[$vvv])) return $cache_long2ip6[$vvv];

	// $bin = gmp_strval(gmp_init($ipv6long, 10), 2);
	$bin = gmp_strval($ipv6long, 2);
	if (strlen($bin) < 128) {
		$pad = 128 - strlen($bin);
		for ($i = 1; $i <= $pad; $i++) {
			$bin = '0'.$bin;
		}
	}

	$bytes = 0;
	$ipv6 = '';
	while ($bytes < 8) { // 16 bytes x 8 bit/byte = 128bit
		$bin_part = substr($bin,($bytes*16),16);
		$part = dechex(bindec($bin_part));
		if (!$compress) {
			$part = str_pad($part, 4, '0', STR_PAD_LEFT);
		}
		$ipv6 .= $part.':';
		$bytes++;
	}

	if ($compress) {
		$out = inet_ntop(inet_pton(substr($ipv6, 0, -1)));
	} else {
		$out = substr($ipv6, 0, strlen($ipv6)-1);
	}
	$cache_long2ip6[$vvv] = $out;
	return $out;
}

# --- New 16,12,12

define('IPV6_BITS', 128);

$global_ipv6_distance = array();
function ipv6_distance($ipOrCIDR_Searchterm, $ipOrCIDR_Candidate) {
	global $global_ipv6_distance;
	$vvv = $ipOrCIDR_Searchterm.'|'.$ipOrCIDR_Candidate;
	if (isset($global_ipv6_distance[$vvv])) return $global_ipv6_distance[$vvv];

	$ary = ipv6_cidr_split($ipOrCIDR_Searchterm);
	$ip = $ary[0];

	if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
		$global_ipv6_distance[$vvv] = false;
		return false;
	}

	$ary = ipv6_cidr_split($ipOrCIDR_Candidate);
	$ip = $ary[0];
	$cidr_bits = $ary[1];
	if ($cidr_bits > IPV6_BITS) {
		$global_ipv6_distance[$vvv] = false;
		return false; // throw new Exception('CIDR bits > '.IPV6_BITS);
	}
	if (!is_numeric($cidr_bits)) return false;

	if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
		$global_ipv6_distance[$vvv] = false;
		return false;
	}

	$x = ipv6_trackdown($ipOrCIDR_Searchterm);

	if (ipv6_in_cidr($x[0], $ip.'/'.$cidr_bits)) {
		$ary = ipv6_cidr_split($x[0]);
		$cidr_bits2 = $ary[1];
		if ($cidr_bits2 > IPV6_BITS) {
			$global_ipv6_distance[$vvv] = false;
			return false; // throw new Exception('CIDR bits > '.IPV6_BITS);
		}
		$out = $cidr_bits2-$cidr_bits;
		$global_ipv6_distance[$vvv] = $out;
		return $out;
	}

	$i = 0;
	$max = false;
	foreach ($x as &$y) {
		if (ipv6_in_cidr($ip.'/'.$cidr_bits, $y)) {
			$max = $i;
		}
		$i++;
	}

	$global_ipv6_distance[$vvv] = $max;
	return $max;
}

function ipv6_cidr_split($ipOrCIDR) {
	$ary = explode('/', $ipOrCIDR, 2);
	$cidr_bits = isset($ary[1]) ? $ary[1] : IPV6_BITS;
	if ($cidr_bits > IPV6_BITS) return false; // throw new Exception('CIDR bits > '.IPV6_BITS);
	if (!is_numeric($cidr_bits)) return false;
	$ip = $ary[0];
	return array($ip, $cidr_bits);
}

function ipv6_equals($ipOrCIDRA, $ipOrCIDRB) {
	return ipv6_normalize_range($ipOrCIDRA) == ipv6_normalize_range($ipOrCIDRB);
}

function ipv6_cidr_min_ip($ipOrCIDR) {
	$ary = ipv6_cidr_split($ipOrCIDR);
	$ipOrCIDR  = $ary[0];
	$cidr_bits = $ary[1];
	if ($cidr_bits > IPV6_BITS) return false; // throw new Exception('CIDR bits > '.IPV6_BITS);
	if (!is_numeric($cidr_bits)) return false;

	$m = ip2bin($ipOrCIDR);
	$m = substr($m, 0, $cidr_bits) . str_repeat('0', IPV6_BITS-$cidr_bits);

	return bin2ip($m);
}

function ipv6_cidr_max_ip($ipOrCIDR) {
	$ary = ipv6_cidr_split($ipOrCIDR);
	$ipOrCIDR  = $ary[0];
	$cidr_bits = $ary[1];
	if ($cidr_bits > IPV6_BITS) return false; // throw new Exception('CIDR bits > '.IPV6_BITS);
	if (!is_numeric($cidr_bits)) return false;

	$m = ip2bin($ipOrCIDR);
	$m = substr($m, 0, $cidr_bits) . str_repeat('1', IPV6_BITS-$cidr_bits);

	return bin2ip($m);
}

function ipv6_normalize_range($ipOrCIDR) {
	#     2001:1800::1/21
	# --> 2001:1800::/21

	#     2001:1af8:4100:a061:0001::1337
	# --> 2001:1af8:4100:a061:1::1337/128

	$ary = ipv6_cidr_split($ipOrCIDR);
	$ipOrCIDR  = $ary[0];
	$cidr_bits = $ary[1];
	if ($cidr_bits > IPV6_BITS) return false; // throw new Exception('CIDR bits > '.IPV6_BITS);
	if (!is_numeric($cidr_bits)) return false;

	$m = ip2bin($ipOrCIDR);
	$m = substr($m, 0, $cidr_bits) . str_repeat('0', IPV6_BITS-$cidr_bits);

	return bin2ip($m) . '/' . $cidr_bits;
}

function ipv6_trackdown($ipOrCIDR) {
	$ary = ipv6_cidr_split($ipOrCIDR);
	$ipOrCIDR  = $ary[0];
	$cidr_bits = $ary[1];
	if ($cidr_bits > IPV6_BITS) return false; // throw new Exception('CIDR bits > '.IPV6_BITS);
	if (!is_numeric($cidr_bits)) return false;

	$out = array();
	$m = ip2bin($ipOrCIDR);
	for ($i=$cidr_bits; $i>=0; $i--) {
		$m = substr($m, 0, $i) . str_repeat('0', IPV6_BITS-$i);
		$out[] = bin2ip($m) . '/' . $i;
	}

	return $out;
}

function ipv6_sort($ary) {
	$f = array();
	foreach ($ary as $c) {
		$a = explode('/', $c);
		$ip = $a[0];
		$bits = isset($a[1]) ? $a[1] : 128;

		$d = ip2bin($ip);

		# ord('*') must be smaller than ord('0')
		$d = substr($d, 0, $bits).str_repeat('*', 128-$bits);

		$f[$d] = $c;
	}

	return $f;
}

function ipv6_make_tree($ary) {
	$ary = ipv6_sort($ary);

	if (count($ary) == 0) return array();

	$sub_begin = '';
	$sub_begin_ip = '';
	foreach ($ary as $n => $d) {
		$sub_begin = substr($n, 0, strpos($n, '*'));
		$sub_begin_ip = $d;
		unset($ary[$n]);
		break;
	}

	$sub = array();
	$nonsub = array();
	foreach ($ary as $n => $d) {
		if (substr($n, 0, strlen($sub_begin)) == $sub_begin) {
			$sub[$n] = $d;
		} else {
			$nonsub[$n] = $d;
		}
	}

	$out = array();
	$out[$sub_begin_ip] = ipv6_make_tree($sub);

	$a = ipv6_make_tree($nonsub);

	$out = array_merge($out, $a);

	return $out;
}

# ---

if (!function_exists('ip2bin')) {
	$cache_ip2bin = array();
	function ip2bin($ip) {
		# Source: http://php.net/manual/en/function.ip2long.php#104163
		# modified by VTS

		global $cache_ip2bin;
		if (isset($cache_ip2bin[$ip])) return $cache_ip2bin[$ip];

		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
			$out = base_convert(ip2long($ip), 10, 2);
			$cache_ip2bin[$ip] = $out;
			return $out;
		}
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
			$cache_ip2bin[$ip] = false;
			return false;
		}
		if (($ip_n = inet_pton($ip)) === false) {
			$cache_ip2bin[$ip] = false;
			return false;
		}
		$bits = 15; // 16 x 8 bit = 128bit (ipv6)
		$ipbin = ''; # added by vts to avoid warning
		while ($bits >= 0) {
			$bin = sprintf('%08b', (ord($ip_n[$bits])));
			$ipbin = $bin.$ipbin;
			$bits--;
		}

		$cache_ip2bin[$ip] = $ipbin;
		return $ipbin;
	}
}

if (!function_exists('bin2ip')) {
	$cache_bin2ip = array();
	function bin2ip($bin) {
		# Source: http://php.net/manual/en/function.ip2long.php#104163
		# modified by VTS

		global $cache_bin2ip;
		if (isset($cache_bin2ip[$bin])) return $cache_bin2ip[$bin];

		if (strlen($bin) <= 32) { // 32bits (ipv4)
			$out = long2ip(base_convert($bin, 2, 10));
			$cache_bin2ip[$bin] = $out;
			return $out;
		}
		if (strlen($bin) != 128) {
			$cache_bin2ip[$bin] = false;
			return false;
		}
		$pad = 128 - strlen($bin);
		for ($i = 1; $i <= $pad; $i++) {
			$bin = '0'.$bin;
		}
		$bits = 0;
		$ipv6 = ''; # added by vts to avoid warning
		while ($bits <= 7) {
			$bin_part = substr($bin,($bits*16),16);
			$ipv6 .= dechex(bindec($bin_part)) . ':';
			$bits++;
		}

		$out = inet_ntop(inet_pton(substr($ipv6, 0, -1)));
		$cache_bin2ip[$bin] = $out;
		return $out;
	}
}

# --- TEST

/*
assert(ipv6_normalize('2001:0000:0000::1') == '2001::1');

assert(ipv6_distance('2001:1ae0::/27', '2001:1af8::/29') == -2);
assert(ipv6_distance('2001:1af0::/28', '2001:1af8::/29') == -1);
assert(ipv6_distance('2001:1af8::/29', '2001:1af8::/29') == 0);
assert(ipv6_distance('2001:1af8::/30', '2001:1af8::/29') == 1);
assert(ipv6_distance('2001:1af8::/31', '2001:1af8::/29') == 2);

assert(ipv6_distance('2001:1af8:4100:a061:0001::1336/127', '2001:1af8:4100:a061:0001::1335/127') === false);
assert(ipv6_distance('2001:1af8:4100:a061:0001::1336/128', '2001:1af8:4100:a061:0001::1337/128') === false);
assert(ipv6_distance('2001:1af8:4100:a061:0001::1336',     '2001:1af8:4100:a061:0001::1337')     === false);
*/

/*
$test = '2001:1af8:4100:a061:0001::1337';
$x = ipv6_trackdown($test);
foreach ($x as &$cidr) {
	$min = ipv6_cidr_min_ip($cidr);
	$max = ipv6_cidr_max_ip($cidr);
	echo "$cidr ($min - $max)\n";
}
*/
