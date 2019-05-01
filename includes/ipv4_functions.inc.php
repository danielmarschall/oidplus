<?php

/*
 * IPv4 functions for PHP
 * Copyright 2012-2019 Daniel Marschall, ViaThinkSoft
 * Version 2019-03-11
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

// TODO: oop, exceptions?

// Very small self-test:
/*
function ipv4_selftest() {
	$iv_b = ipv4_complete('1.2');
	$iv_m = 20;
	$r = ipv4_cidr2range($iv_b, $iv_m);
	echo "$iv_b/$iv_m => $r[0] - $r[1]\n";

	$rev = ipv4_range2cidr($r[0], $r[1]);
	$rev = implode("\n", $rev);
	echo "$r[0] - $r[1] => $rev [";
	$ok = $rev == "$iv_b/$iv_m";
	echo $ok ? 'OK' : 'Mismatch';
	echo "]\n";
	echo "In-CIDR-Test: ";
	echo ipv4_in_cidr("$iv_b/$iv_m", "$iv_b/$iv_m") ? 'OK' : 'Fail';
	echo "\n";
}
ipv4_selftest();
*/

function ipv4_cidr2range($baseip_or_cidr, $subnet='') {
	# (C) 2012 ViaThinkSoft
	# Version 1.1
	# This function converts an CIDR notation <baseip>/<subnet> into an IPv4 address block array($low_ip, $high_ip)

	if (strpos($baseip_or_cidr, '/') !== false) {
		$tmp = explode('/', $baseip_or_cidr, 2);
		$baseip_or_cidr = $tmp[0];
		$subnet = $tmp[1];
		unset($tmp);
	}

	if (($subnet < 0) || ($subnet > 32)) return false;

	$maxint32 = 0xFFFFFFFF;
	$netmask = $maxint32 << (32-$subnet);
	$netmask = $netmask & $maxint32; // crop to 32 bits
	$wildcard = $maxint32 ^ $netmask; // ~$netmask;

	$x = ipv4_incomplete_ip2long($baseip_or_cidr) & $netmask;
	$nums = $wildcard;
	$low = long2ip($x);
	$high = long2ip($x + $nums);

	return array($low, $high);
}

function ipv4_range2cidr($baseip, $topip, $shortening=false) {
	# (C) 2012 ViaThinkSoft
	# Version 1.0
	# This function converts an IPv4 address block into valid CIDR blocks (There may be multiple blocks!)

	$out = array();
	if (ipv4_cmp($baseip, $topip) > 0) return false;
	while (ipv4_incomplete_ip2long($baseip)-1 != ipv4_incomplete_ip2long($topip)) {
		$i = -1;
		do {
			$i++;
			$range = ipv4_cidr2range($baseip, $i);
			$l = $range[0];
			$t = $range[1];
		} while ((ipv4_cmp($l, $baseip) != 0) || (ipv4_cmp($t, $topip) > 0));

		# Shortening: Stroke ".0" at the end
		if ($shortening) $baseip = ipv4_shortening($baseip);

		$out[] = "$baseip/$i";
		$baseip = ipv4_add($t, 1);
	}
	return $out;
}

function ipv4_shortening($ip) {
	# (C) 2012 ViaThinkSoft
	# Version 1.0

	return preg_replace("|(\\.0{1,3}){0,3}\$|ismU", '', $ip);
}

function ipv4_add($baseip, $num) {
	# (C) 2012 ViaThinkSoft
	# Version 1.0

	return long2ip(ipv4_incomplete_ip2long($baseip) + $num);
}

function ipv4_sub($baseip, $num) {
	# (C) 2012 ViaThinkSoft
	# Version 1.0

	return long2ip(ipv4_incomplete_ip2long($baseip) - $num);
}

function ipv4_cmp($a, $b) {
	# (C) 2012 ViaThinkSoft
	# Version 1.0

	$a = ipv4_incomplete_ip2long($a);
	$b = ipv4_incomplete_ip2long($b);

	if ($a == $b) return  0;
	if ($a  < $b) return -1;
	if ($a  > $b) return  1;
}

function ipv4_in_cidr($haystack, $needle) {
	# (C) 2012 ViaThinkSoft
	# Version 1.1

	$x = explode('/', $haystack);
	$ha = ipv4_cidr2range($x[0], $x[1]);

	$x = explode('/', $needle);
	if (!isset($x[1])) $x[1] = '32'; // single IP
	$ne = ipv4_cidr2range($x[0], $x[1]);

	$ha_low = ipv4_incomplete_ip2long($ha[0]);
	$ha_hig = ipv4_incomplete_ip2long($ha[1]);
	$ne_low = ipv4_incomplete_ip2long($ne[0]);
	$ne_hig = ipv4_incomplete_ip2long($ne[1]);

	# HA:    low[                               ]high
	# NE:            low[             ]high

	return ($ne_low >= $ha_low) && ($ne_hig <= $ha_hig);
}

function ipv4_complete($short_form) {
	# (C) 2012 ViaThinkSoft
	# Version 1.0

	$short_form = trim($short_form);
	if ($short_form == '') return '0.0.0.0';
	$c = substr_count($short_form, '.');
	if ($c > 3) return false;
	if ($c == 3) return $short_form;
	$c = substr_count($short_form, '.');
	$short_form .= str_repeat('.0', 3-$c);
	return $short_form;
}

function ipv4_incomplete_ip2long($ip) {
	# (C) 2012-2014 ViaThinkSoft
	# Version 1.2

	# return sprintf('%u', ip2long(ipv4_complete($ip)));
	return sprintf('%u', ip2long(ipv4_normalize($ip)));
}

// IMPORTANT! $cmp_ary[x]=y MUST HAVE x<=y !
function ipv4_merge_address_blocks($data, $debug = false, $shortening = false) {
	# (C) 2012-2013 ViaThinkSoft
	# Version 2.2

	if ($debug !== false) $STARTZEIT = time();

	// 1. Convert IPs to numbers

	$cmp_ary = array();
	foreach ($data as $a => &$b) {
		$a = ipv4_incomplete_ip2long($a);
		$b = ipv4_incomplete_ip2long($b);

		$cmp_ary[$a] = $b;
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
				if ($x<=$a) continue;
				if ($x>$b+1) break;

				// Merge
				$clean = false;
				if ($y>$b) {
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
		$a = long2ip($a);
		$b = long2ip($b);
		if ($shortening) {
			$a = ipv4_shortening($a);
			$b = ipv4_shortening($b);
		}
		$out_ary[$a] = $b;
	}

	return $out_ary;
}

function ipv4_merge_arrays($data_a, $data_b) {
	# (C) 2012 ViaThinkSoft
	# Version 1.2

	$normalized_data_a = array();
	foreach ($data_a as $from => &$to) {
		$normalized_data_a[ipv4_normalize($from)] = ipv4_normalize($to);
	}

	$normalized_data_b = array();
	foreach ($data_b as $from => &$to) {
		$normalized_data_b[ipv4_normalize($from)] = ipv4_normalize($to);
	}

	$data = array();

	foreach ($normalized_data_a as $from => &$to) {
		if (isset($normalized_data_b[$from])) {
			$data[$from] = ipv4_max($to, $normalized_data_b[$from]);
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

function ipv4_valid($ip) {
	# (C) 2012 ViaThinkSoft
	# Version 1.0

	# return ipv4_incomplete_ip2long($ip) !== false;
	return ip2long($ip) !== false;
}

function ipv4_normalize($ip) {
	# (C) 2012-2013 ViaThinkSoft
	# Version 1.1.1

	# Example:
	# 100.010.001.000 -> 100.10.1.0

	$ip = ipv4_complete($ip);
	if (!$ip) return false;

	# ip2long buggy: 001.0.0.0 wird nicht akzeptiert
##	$cry = explode('.', $ip);
##	$cry[0] = preg_replace('@^0+@', '', $cry[0]); if ($cry[0] == '') $cry[0] = '0';
##	$cry[1] = preg_replace('@^0+@', '', $cry[1]); if ($cry[1] == '') $cry[1] = '0';
##	$cry[2] = preg_replace('@^0+@', '', $cry[2]); if ($cry[2] == '') $cry[2] = '0';
##	$cry[3] = preg_replace('@^0+@', '', $cry[3]); if ($cry[3] == '') $cry[3] = '0';
##	$ip = implode('.', $cry);
##	return $ip;

	return preg_replace('@^0{0,2}([0-9]{1,3})\.0{0,2}([0-9]{1,3})\.0{0,2}([0-9]{1,3})\.0{0,2}([0-9]{1,3})$@', '\\1.\\2.\\3.\\4', $ip);
}

function ipv4_expand($ip) {
	# (C) 2012 ViaThinkSoft
	# Version 1.0

	# Example:
	# 100.10.1.0 -> 100.010.001.000

	$ip = ipv4_complete($ip);
	if (!$ip) return false;

	$cry = explode('.', $ip);
	$cry[0] = str_pad($cry[0], 3, '0', STR_PAD_LEFT);
	$cry[1] = str_pad($cry[1], 3, '0', STR_PAD_LEFT);
	$cry[2] = str_pad($cry[2], 3, '0', STR_PAD_LEFT);
	$cry[3] = str_pad($cry[3], 3, '0', STR_PAD_LEFT);
	return implode('.', $cry);
}

function ipv4_min($ip_a, $ip_b) {
	# (C) 2012 ViaThinkSoft
	# Version 1.0

	if (ipv4_cmp($ip_a, $ip_b) == -1) {
		return $ip_a;
	} else {
		return $ip_b;
	}
}

function ipv4_max($ip_a, $ip_b) {
	# (C) 2012 ViaThinkSoft
	# Version 1.0

	if (ipv4_cmp($ip_a, $ip_b) == 1) {
		return $ip_a;
	} else {
		return $ip_b;
	}
}

function ipv4_ipcount($data) {
	# (C) 2012 ViaThinkSoft
	# Version 1.0

	$cnt = 0;

	foreach ($data as $from => &$to) {
		$cnt += ipv4_incomplete_ip2long($to) - ipv4_incomplete_ip2long($from);
	}

	return $cnt;
}

function ipv4_read_file($file) {
	# (C) 2012 ViaThinkSoft
	# Version 1.0

	$data = array();

	$lines = file($file);
	foreach ($lines as &$line) {
		$rng = ipv4_line2range($line);
		$data[$rng[0]] = $rng[1];
	}

	return $data;
}

function ipv4_line2range($line) {
	# (C) 2012 ViaThinkSoft
	# Version 1.0

	$line = trim($line);

	if (strpos($line, '/') !== false) {
		$rng = ipv4_cidr2range($line);
	} else {
		$rng = explode('-', $line);
		$rng[0] = trim($rng[0]);
		$rng[1] = trim($rng[1]);
		$rng[0] = ipv4_normalize($rng[0]);
		if (!isset($rng[1])) $rng[1] = $rng[0];
		$rng[1] = ipv4_normalize($rng[1]);
	}

	return $rng;
}

# --- New 16,12,12

define('IPV4_BITS', 32);

function ipv4_distance($ipOrCIDR_Searchterm, $ipOrCIDR_Candidate) {
	$ary = ipv4_cidr_split($ipOrCIDR_Searchterm);
	$ip = $ary[0];

	if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
		return false;
	}

	$ary = ipv4_cidr_split($ipOrCIDR_Candidate);
	$ip = $ary[0];
	$cidr_bits = $ary[1];
	if ($cidr_bits > IPV4_BITS) return false; // throw new Exception('CIDR bits > '.IPV4_BITS);
	if (!is_numeric($cidr_bits)) return false;

	if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
		return false;
	}

	$x = ipv4_trackdown($ipOrCIDR_Searchterm);

	if (ipv4_in_cidr($x[0], $ip.'/'.$cidr_bits)) {
		$ary = ipv4_cidr_split($x[0]);
		$cidr_bits2 = $ary[1];
		if ($cidr_bits2 > IPV4_BITS) return false; // throw new Exception('CIDR bits > '.IPV4_BITS);
		return $cidr_bits2-$cidr_bits;
	}

	$i = 0;
	$max = false;
	foreach ($x as &$y) {
		if (ipv4_in_cidr($ip.'/'.$cidr_bits, $y)) {
			$max = $i;
		}
		$i++;
	}

	return $max;
}

function ipv4_cidr_split($ipOrCIDR) {
	$ary = explode('/', $ipOrCIDR, 2);
	$cidr_bits = isset($ary[1]) ? $ary[1] : IPV4_BITS;
	if ($cidr_bits > IPV4_BITS) return false; // throw new Exception('CIDR bits > '.IPV4_BITS);
	if (!is_numeric($cidr_bits)) return false;
	$ip = $ary[0];
	return array($ip, $cidr_bits);
}

function ipv4_equals($ipOrCIDRA, $ipOrCIDRB) {
	return ipv4_normalize_range($ipOrCIDRA) == ipv4_normalize_range($ipOrCIDRB);
}

function ipv4_cidr_min_ip($ipOrCIDR) {
	$ary = ipv4_cidr_split($ipOrCIDR);
	$ipOrCIDR  = $ary[0];
	$cidr_bits = $ary[1];
	if ($cidr_bits > IPV4_BITS) return false; // throw new Exception('CIDR bits > '.IPV4_BITS);
	if (!is_numeric($cidr_bits)) return false;

	$m = ip2bin($ipOrCIDR);
	$m = substr($m, 0, $cidr_bits) . str_repeat('0', IPV4_BITS-$cidr_bits);

	return bin2ip($m);
}

function ipv4_cidr_max_ip($ipOrCIDR) {
	$ary = ipv4_cidr_split($ipOrCIDR);
	$ipOrCIDR  = $ary[0];
	$cidr_bits = $ary[1];
	if ($cidr_bits > IPV4_BITS) return false; // throw new Exception('CIDR bits > '.IPV4_BITS);
	if (!is_numeric($cidr_bits)) return false;

	$m = ip2bin($ipOrCIDR);
	$m = substr($m, 0, $cidr_bits) . str_repeat('1', IPV4_BITS-$cidr_bits);

	return bin2ip($m);
}

function ipv4_normalize_range($ipOrCIDR) {
	$ary = ipv4_cidr_split($ipOrCIDR);
	$ipOrCIDR  = $ary[0];
	$cidr_bits = $ary[1];
	if ($cidr_bits > IPV4_BITS) return false; // throw new Exception('CIDR bits > '.IPV4_BITS);
	if (!is_numeric($cidr_bits)) return false;

	$m = ip2bin($ipOrCIDR);
	$m = substr($m, 0, $cidr_bits) . str_repeat('0', IPV4_BITS-$cidr_bits);

	return bin2ip($m) . '/' . $cidr_bits;
}

function ipv4_trackdown($ipOrCIDR) {
	$ary = ipv4_cidr_split($ipOrCIDR);
	$ipOrCIDR  = $ary[0];
	$cidr_bits = $ary[1];
	if ($cidr_bits > IPV4_BITS) return false; // throw new Exception('CIDR bits > '.IPV4_BITS);
	if (!is_numeric($cidr_bits)) return false;

	$out = array();
	$m = ip2bin($ipOrCIDR);

	for ($i=$cidr_bits; $i>=0; $i--) {
		$m = substr($m, 0, $i) . str_repeat('0', IPV4_BITS-$i);
		$out[] = bin2ip($m) . '/' . $i;
	}

	return $out;
}

# ---

if (!function_exists('ip2bin')) {
	function ip2bin($ip) {
		# Source: http://php.net/manual/en/function.ip2long.php#104163
		# modified by VTS

		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
			return base_convert(ip2long($ip), 10, 2);
		}
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
			return false;
		}
		if (($ip_n = inet_pton($ip)) === false) {
			return false;
		}
		$bits = 15; // 16 x 8 bit = 128bit (ipv6)
		$ipbin = ''; # added by vts to avoid warning
		while ($bits >= 0) {
			$bin = sprintf('%08b', (ord($ip_n[$bits])));
			$ipbin = $bin.$ipbin;
			$bits--;
		}
		return $ipbin;
	}
}

if (!function_exists('bin2ip')) {
	function bin2ip($bin) {
		# Source: http://php.net/manual/en/function.ip2long.php#104163
		# modified by VTS

		if (strlen($bin) <= 32) { // 32bits (ipv4)
			return long2ip(base_convert($bin, 2, 10));
		}
		if (strlen($bin) != 128) {
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
		return inet_ntop(inet_pton(substr($ipv6, 0, -1)));
	}
}

# --- TEST

/*
assert(ipv4_normalize('100.010.001.000') == '100.10.1.0');
assert(ipv4_normalize('100.010.01.000') == '100.10.1.0');
assert(ipv4_normalize('100.10.001.000') == '100.10.1.0');
assert(ipv4_normalize('1.010.001.000') == '1.10.1.0');
assert(ipv4_normalize('1.10.001.000') == '1.10.1.0');

assert(ipv4_distance('192.168.0.0/16',  '192.168.64.0/18') == -2);
assert(ipv4_distance('192.168.0.0/17',  '192.168.64.0/18') == -1);
assert(ipv4_distance('192.168.64.0/18', '192.168.64.0/18') == 0);
assert(ipv4_distance('192.168.64.0/19', '192.168.64.0/18') == 1);
assert(ipv4_distance('192.168.64.0/20', '192.168.64.0/18') == 2);

assert(ipv4_distance('192.168.69.202/31', '192.168.69.200/31') === false);
assert(ipv4_distance('192.168.69.201/32', '192.168.69.200/32') === false);
assert(ipv4_distance('192.168.69.201',    '192.168.69.200')    === false);
*/

/*
$test = '192.168.69.123';
$x = ipv4_trackdown($test);
foreach ($x as &$cidr) {
	$min = ipv4_cidr_min_ip($cidr);
	$max = ipv4_cidr_max_ip($cidr);
	echo "$cidr ($min - $max)\n";
}
*/




function ipv4_sort($ary) {
	$f = array();
	foreach ($ary as $c) {
		$a = explode('/', $c);
		$ip = $a[0];
		$bits = isset($a[1]) ? $a[1] : 32;

		$d = ip2bin($ip);

		# ord('*') must be smaller than ord('0')
		$d = substr($d, 0, $bits).str_repeat('*', 32-$bits);

		$f[$d] = $c;
	}

	return $f;
}

function ipv4_make_tree($ary) {
	$ary = ipv4_sort($ary);

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
	$out[$sub_begin_ip] = ipv4_make_tree($sub);

	$a = ipv4_make_tree($nonsub);

	$out = array_merge($out, $a);

	return $out;
}

