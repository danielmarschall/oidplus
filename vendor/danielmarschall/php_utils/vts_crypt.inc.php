<?php

/*
 * ViaThinkSoft Modular Crypt Format 1.0
 * Revision 2023-02-26
 * Copyright 2023 Daniel Marschall, ViaThinkSoft
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

/*

ViaThinkSoft Modular Crypt Format 1.0 performs a simple hash or HMAC operation.
No key derivation function or iterations are performed.

Format:
	$1.3.6.1.4.1.37476.3.0.1.1$a=<algo>,m=<mode>$<salt>$<hash>

where <algo> is any valid hash algorithm (name scheme of PHP hash_algos() preferred), e.g.
	sha3-512
	sha3-384
	sha3-256
	sha3-224
	sha512
	sha512/256
	sha512/224
	sha384
	sha256
	sha224
	sha1
	md5

Valid <mode> :
	sp = salt + password
	ps = password + salt
	sps = salt + password + salt
	hmac = HMAC (salt is the key)

Link to the online specification:
	https://oidplus.viathinksoft.com/oidplus/?goto=oid%3A1.3.6.1.4.1.37476.3.0.1.1

Reference implementation in PHP:
	https://github.com/danielmarschall/php_utils/blob/master/vts_crypt.inc.php

*/

define('OID_MCF_VTS_V1', '1.3.6.1.4.1.37476.3.0.1.1'); // { iso(1) identified-organization(3) dod(6) internet(1) private(4) enterprise(1) 37476 specifications(3) misc(0) modular-crypt-format(1) vts-crypt-v1(1) }

define('BASE64_RFC4648_ALPHABET', '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz+/=');
define('BASE64_CRYPT_ALPHABET',   './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz ');

function crypt_radix64_encode($str) {
	$x = $str;
	$x = base64_encode($x);
	$x = trim(strtr($x, BASE64_RFC4648_ALPHABET, BASE64_CRYPT_ALPHABET));
	return $x;
}

function crypt_radix64_decode($str) {
	$x = $str;
	$x = trim(strtr($x, BASE64_CRYPT_ALPHABET, BASE64_RFC4648_ALPHABET));
	$x = base64_decode($x);
	return $x;
}

assert(crypt_radix64_decode(crypt_radix64_encode('hallo')));

function crypt_modular_format($id, $bin_salt, $bin_hash, $params=null) {
	// $<id>[$<param>=<value>(,<param>=<value>)*][$<salt>[$<hash>]]
	$out = '$'.$id;
	if (!is_null($params)) {
		$ary_params = array();
		//ksort($params);
		foreach ($params as $name => $value) {
			$ary_params[] = "$name=$value";
		}
		$out .= '$'.implode(',',$ary_params);
	}
	$out .= '$'.crypt_radix64_encode($bin_salt);
	$out .= '$'.crypt_radix64_encode($bin_hash);
	return $out;
}

function crypt_modular_format_decode($mcf) {
	$ary = explode('$', $mcf);

	$dummy = array_shift($ary);
	if ($dummy !== '') return false;

	$dummy = array_shift($ary);
	$id = $dummy;

	$params = array();
	$dummy = array_shift($ary);
	if (strpos($dummy, '=') !== false) {
		$params_ary = explode(',',$dummy);
		foreach ($params_ary as $param) {
			$bry = explode('=', $param, 2);
			if (count($bry) > 1) {
				$params[$bry[0]] = $bry[1];
			}
		}
	} else {
		array_unshift($ary, $dummy);
	}

	if (count($ary) > 1) {
		$dummy = array_shift($ary);
		$bin_salt = crypt_radix64_decode($dummy);
	} else {
		$bin_salt = '';
	}

	$dummy = array_shift($ary);
	$bin_hash = crypt_radix64_decode($dummy);

	return array('id' => $id, 'salt' => $bin_salt, 'hash' => $bin_hash, 'params' => $params);
}

function vts_crypt($algo, $str_password, $str_salt, $ver='1', $mode='ps') {
	if ($ver == '1') {
		if ($mode == 'sp') {
			$payload = $str_salt.$str_password;
			if (($algo === 'sha3-512') && !in_array($algo, hash_algos()) && function_exists('sha3_512')) {
				$bin_hash = sha3_512($payload, true);
			} else {
				$bin_hash = hash($algo, $payload, true);
			}
		} else if ($mode == 'ps') {
			$payload = $str_password.$str_salt;
			if (($algo === 'sha3-512') && !in_array($algo, hash_algos()) && function_exists('sha3_512')) {
				$bin_hash = sha3_512($payload, true);
			} else {
				$bin_hash = hash($algo, $payload, true);
			}
		} else if ($mode == 'sps') {
			$payload = $str_salt.$str_password.$str_salt;
			if (($algo === 'sha3-512') && !in_array($algo, hash_algos()) && function_exists('sha3_512')) {
				$bin_hash = sha3_512($payload, true);
			} else {
				$bin_hash = hash($algo, $payload, true);
			}
		} else if ($mode == 'hmac') {
			// Note: Actually, we should use hash_hmac_algos(), but this requires PHP 7.2, and we would like to stay compatible with PHP 7.0 for now
			if (($algo === 'sha3-512') && !in_array($algo, hash_algos()) && function_exists('sha3_512_hmac')) {
				$bin_hash = sha3_512_hmac($str_password, $str_salt, true);
			} else {
				$bin_hash = hash_hmac($algo, $str_password, $str_salt, true);
			}
		} else {
			throw new Exception("Invalid VTS crypt version 1 mode. Expect sp, ps, sps, or hmac.");
		}
		$bin_salt = $str_salt;
		return crypt_modular_format(OID_MCF_VTS_V1, $bin_salt, $bin_hash, array('a'=>$algo,'m'=>$mode));
	} else {
		throw new Exception("Invalid VTS crypt version, expect 1.");
	}
}

function vts_crypt_convert_from_old_oidplus($authkey, $salt) {
	if (preg_match('@^A1([abcd])#(.+):(.+)$@', $authkey, $m)) {
		// A1a#hashalgo:X with X being H(salt+password) in hex- or rfc4648-base64-notation
		// A1b#hashalgo:X with X being H(password+salt) in hex- or rfc4648-base64-notation
		// A1c#hashalgo:X with X being H(salt+password+salt) in hex- or rfc4648-base64-notation
		// A1d#hashalgo:X with X being H_HMAC(password,salt) in hex- or rfc4648-base64-notation
		$mode = ''; // avoid PHPstan warning
		if ($m[1] == 'a') $mode = 'sp';
		else if ($m[1] == 'b') $mode = 'ps';
		else if ($m[1] == 'c') $mode = 'sps';
		else if ($m[1] == 'd') $mode = 'hmac';
		else assert(false);
		$algo = $m[2];
		$bin_salt = $salt;
		if (($algo == 'sha3-512') || ($algo == 'sha3-384') || ($algo == 'sha512') || ($algo == 'sha384')) {
			$bin_hash = base64_decode($m[3]);
		} else {
			$bin_hash = hex2bin($m[3]);
		}
		return crypt_modular_format(OID_MCF_VTS_V1, $bin_salt, $bin_hash, array('a'=>$algo,'m'=>$mode));
	} else if (preg_match('@^A2#(.+)$@', $authkey, $m)) {
		// A2#X with X being sha3(salt+password) in rfc4648-base64-notation
		$mode = 'sp';
		$algo = 'sha3-512';
		$bin_salt = $salt;
		$bin_hash = base64_decode($m[1]);
		return crypt_modular_format(OID_MCF_VTS_V1, $bin_salt, $bin_hash, array('a'=>$algo,'m'=>$mode));
	} else if (preg_match('@^A3#(.+)$@', $authkey, $m)) {
		// A3#X with X being bcrypt  [not VTS hash!]
		return $m[1];
	} else {
		// Nothing to convert
		return $authkey;
	}
}
