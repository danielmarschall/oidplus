<?php

/*
 * ViaThinkSoft Modular Crypt Format 1.0 / vts_password_hash() / vts_password_verify()
 * Copyright 2023 Daniel Marschall, ViaThinkSoft
 * Revision 2023-02-27
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

The function vts_password_hash() replaces password_hash()
and adds the ViaThinkSoft Modular Crypt Format 1.0 hash as well as
all hashes from password_hash() and crypt().

The function vts_password_verify() replaces password_verify().

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
Like most Crypt-hashes, <salt> and <hash> are Radix64 coded
with alphabet './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' and no padding.
Link to the online specification:
	https://oidplus.viathinksoft.com/oidplus/?goto=oid%3A1.3.6.1.4.1.37476.3.0.1.1
Reference implementation in PHP:
	https://github.com/danielmarschall/php_utils/blob/master/vts_crypt.inc.php

*/

require_once __DIR__ . '/misc_functions.inc.php';

define('OID_MCF_VTS_V1',    '1.3.6.1.4.1.37476.3.0.1.1'); // { iso(1) identified-organization(3) dod(6) internet(1) private(4) enterprise(1) 37476 specifications(3) misc(0) modular-crypt-format(1) vts-crypt-v1(1) }

define('PASSWORD_STD_DES',   'std_des');
define('PASSWORD_EXT_DES',   'ext_des');
define('PASSWORD_MD5',       'md5');
define('PASSWORD_BLOWFISH',  'blowfish');
define('PASSWORD_SHA256',    'sha256');
define('PASSWORD_SHA512',    'sha512');
define('PASSWORD_VTS_MCF1',  OID_MCF_VTS_V1);

define('BASE64_RFC4648_ALPHABET', '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz+/');
define('BASE64_CRYPT_ALPHABET',   './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz');

// --- Part 1: Modular Crypt Format encode/decode

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

// --- Part 2: ViaThinkSoft Modular Crypt Format 1.0

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

// --- Part 3: vts_password_hash() and vts_password_verify()

/** This function extends password_verify() by adding ViaThinkSoft Modular Crypt Format 1.0.
 * @param string $password to be checked
 * @param string $hash Hash created by crypt(), password_hash(), or vts_password_hash().
 * @return bool true if password is valid
 */
function vts_password_verify($password, $hash): bool {
	if (str_starts_with($hash, '$'.PASSWORD_VTS_MCF1.'$')) {

		// Decode the MCF hash parameters
		$data = crypt_modular_format_decode($hash);
		if ($data === false) throw new Exception('Invalid auth key');
		$id = $data['id'];
		$bin_salt = $data['salt'];
		$bin_hash = $data['hash'];
		$params = $data['params'];
		$algo = $params['a'];
		$mode = $params['m'];
		$ver = '1';

		// Create a VTS MCF 1.0 hash based on the parameters of $hash and the password $password
		$calc_authkey_1 = vts_crypt($algo, $password, $bin_salt, $ver, $mode);

		// We rewrite the MCF to make sure that they match (if params) have the wrong order
		$calc_authkey_2 = crypt_modular_format($id, $bin_salt, $bin_hash, $params);

		return hash_equals($calc_authkey_1, $calc_authkey_2);

	} else {
		// password_hash() and crypt() hashes
		return password_verify($password, $hash);
	}
}

/** This function extends password_hash() with the algorithms supported by crypt().
 * It also adds ViaThinkSoft Modular Crypt Format 1.0.
 * The result can be verified using vts_password_verify().
 * @param string $password to be hashed
 * @param mixed $algo algorithm
 * @param array $options options for the hashing algorithm
 * @return string Crypt compatible password hash
 */
function vts_password_hash($password, $algo, $options=array()): string {
	$crypt_salt = null;
	if (($algo === PASSWORD_STD_DES) && defined('CRYPT_STD_DES')) {
		// Standard DES-based hash with a two character salt from the alphabet "./0-9A-Za-z". Using invalid characters in the salt will cause crypt() to fail.
		$crypt_salt = des_compat_salt(2);
	} else if (($algo === PASSWORD_EXT_DES) && defined('CRYPT_EXT_DES')) {
		// Extended DES-based hash. The "salt" is a 9-character string consisting of an underscore followed by 4 characters of iteration count and 4 characters of salt. Each of these 4-character strings encode 24 bits, least significant character first. The values 0 to 63 are encoded as ./0-9A-Za-z. Using invalid characters in the salt will cause crypt() to fail.
		$iterations = isset($options['iterations']) ? $options['iterations'] : 725;
		$crypt_salt = '_' . base64_int_encode($iterations) . des_compat_salt(4);
	} else if (($algo === PASSWORD_MD5) && defined('CRYPT_MD5')) {
		// MD5 hashing with a twelve character salt starting with $1$
		$crypt_salt = '$1$'.des_compat_salt(12).'$';
	} else if (($algo === PASSWORD_BLOWFISH) && defined('CRYPT_BLOWFISH')) {
		// Blowfish hashing with a salt as follows: "$2a$", "$2x$" or "$2y$", a two digit cost parameter, "$", and 22 characters from the alphabet "./0-9A-Za-z". Using characters outside of this range in the salt will cause crypt() to return a zero-length string. The two digit cost parameter is the base-2 logarithm of the iteration count for the underlying Blowfish-based hashing algorithm and must be in range 04-31, values outside this range will cause crypt() to fail. "$2x$" hashes are potentially weak; "$2a$" hashes are compatible and mitigate this weakness. For new hashes, "$2y$" should be used.
		$algo = '$2y$'; // most secure
		$cost = isset($options['cost']) ? $options['cost'] : 10;
		$crypt_salt = $algo.str_pad($cost,2,'0',STR_PAD_LEFT).'$'.des_compat_salt(22).'$';
	} else if (($algo === PASSWORD_SHA256) && defined('CRYPT_SHA256')) {
		// SHA-256 hash with a sixteen character salt prefixed with $5$. If the salt string starts with 'rounds=<N>$', the numeric value of N is used to indicate how many times the hashing loop should be executed, much like the cost parameter on Blowfish. The default number of rounds is 5000, there is a minimum of 1000 and a maximum of 999,999,999. Any selection of N outside this range will be truncated to the nearest limit.
		$algo = '$5$';
		$rounds = isset($options['rounds']) ? $options['rounds'] : 5000;
		$crypt_salt = $algo.'rounds='.$rounds.'$'.des_compat_salt(16).'$';
	} else if (($algo === PASSWORD_SHA512) && defined('CRYPT_SHA512')) {
		// SHA-512 hash with a sixteen character salt prefixed with $6$. If the salt string starts with 'rounds=<N>$', the numeric value of N is used to indicate how many times the hashing loop should be executed, much like the cost parameter on Blowfish. The default number of rounds is 5000, there is a minimum of 1000 and a maximum of 999,999,999. Any selection of N outside this range will be truncated to the nearest limit.
		$algo = '$6$';
		$rounds = isset($options['rounds']) ? $options['rounds'] : 5000;
		$crypt_salt = $algo.'rounds='.$rounds.'$'.des_compat_salt(16).'$';
	}

	if (!is_null($crypt_salt)) {
		$out = crypt($password, $crypt_salt);
		if (strlen($out) < 13) throw new Exception("crypt() failed");
		return $out;
	} else if ($algo === PASSWORD_VTS_MCF1) {
		$ver  = '1';
		$algo = isset($options['algo']) ? $options['algo'] : 'sha3-512';
		$mode = isset($options['mode']) ? $options['mode'] : 'ps';
		$salt_len = isset($options['salt_length']) ? $options['salt_length'] : 50;
		$salt = random_bytes_ex($salt_len, true, true);
		return vts_crypt($algo, $password, $salt, $ver, $mode);
	} else {
		// $algo === PASSWORD_DEFAULT
		// $algo === PASSWORD_BCRYPT
		// $algo === PASSWORD_ARGON2I
		// $algo === PASSWORD_ARGON2ID
		return password_hash($password, $algo, $options);
	}
}

// --- Part 4: Useful functions required by the above functions

function des_compat_salt($salt_len) {
	if ($salt_len <= 0) return '';
	$characters = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
	$salt = '';
	$bytes = random_bytes_ex($salt_len, true, true);
	for ($i=0; $i<$salt_len; $i++) {
		$salt .= $characters[ord($bytes[$i]) % strlen($characters)];
	}
	return $salt;
}

function base64_int_encode($num) {
	// https://stackoverflow.com/questions/15534982/which-iteration-rules-apply-on-crypt-using-crypt-ext-des
	$alphabet_raw='./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
	$alphabet=str_split($alphabet_raw);
	$arr=array();
	$base=sizeof($alphabet);
	while($num) {
		$rem=$num % $base;
		$num=(int)($num / $base);
		$arr[]=$alphabet[$rem];
	}
	$string=implode($arr);
	return str_pad($string, 4, '.', STR_PAD_RIGHT);
}

function crypt_radix64_encode($str) {
	$x = $str;
	$x = base64_encode($x);
	$x = rtrim($x, '=');
	$x = strtr($x, BASE64_RFC4648_ALPHABET, BASE64_CRYPT_ALPHABET);
	return $x;
}

function crypt_radix64_decode($str) {
	$x = $str;
	$x = strtr($x, BASE64_CRYPT_ALPHABET, BASE64_RFC4648_ALPHABET);
	$x = base64_decode($x);
	return $x;
}

// --- Part 5: Selftest

/*
assert(crypt_radix64_decode(crypt_radix64_encode('test123')) === 'test123');

assert(vts_password_Verify('test123',vts_password_hash('test123', PASSWORD_STD_DES)));
assert(vts_password_Verify('test123',vts_password_hash('test123', PASSWORD_EXT_DES)));
assert(vts_password_Verify('test123',vts_password_hash('test123', PASSWORD_MD5)));
assert(vts_password_Verify('test123',vts_password_hash('test123', PASSWORD_BLOWFISH)));
assert(vts_password_Verify('test123',vts_password_hash('test123', PASSWORD_SHA256)));
assert(vts_password_Verify('test123',vts_password_hash('test123', PASSWORD_SHA512)));
assert(vts_password_Verify('test123',vts_password_hash('test123', PASSWORD_VTS_MCF1)));
assert(vts_password_Verify('test123',vts_password_hash('test123', PASSWORD_DEFAULT)));
assert(vts_password_Verify('test123',vts_password_hash('test123', PASSWORD_BCRYPT)));
assert(vts_password_Verify('test123',vts_password_hash('test123', PASSWORD_ARGON2I)));
assert(vts_password_Verify('test123',vts_password_hash('test123', PASSWORD_ARGON2ID)));
*/

