<?php

/*
 * ViaThinkSoft Modular Crypt Format 1.0 / vts_password_hash() / vts_password_verify()
 * Copyright 2023 Daniel Marschall, ViaThinkSoft
 * Revision 2023-02-28
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
	pbkdf2 = PBKDF2-HMAC (Additional param i= contains the number of iterations)
Like most Crypt-hashes, <salt> and <hash> are Radix64 coded
with alphabet './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' and no padding.
Link to the online specification:
	https://oidplus.viathinksoft.com/oidplus/?goto=oid%3A1.3.6.1.4.1.37476.3.0.1.1
Reference implementation in PHP:
	https://github.com/danielmarschall/php_utils/blob/master/vts_crypt.inc.php

*/

require_once __DIR__ . '/misc_functions.inc.php';

define('OID_MCF_VTS_V1',     '1.3.6.1.4.1.37476.3.0.1.1'); // { iso(1) identified-organization(3) dod(6) internet(1) private(4) enterprise(1) 37476 specifications(3) misc(0) modular-crypt-format(1) vts-crypt-v1(1) }

// Valid algorithms for vts_password_hash():
define('PASSWORD_STD_DES',   'std_des');       // Algorithm from crypt()
define('PASSWORD_EXT_DES',   'ext_des');       // Algorithm from crypt()
define('PASSWORD_MD5',       'md5');           // Algorithm from crypt()
define('PASSWORD_BLOWFISH',  'blowfish');      // Algorithm from crypt()
define('PASSWORD_SHA256',    'sha256');        // Algorithm from crypt()
define('PASSWORD_SHA512',    'sha512');        // Algorithm from crypt()
define('PASSWORD_VTS_MCF1',  OID_MCF_VTS_V1);  // Algorithm from ViaThinkSoft
// Other valid values (already defined in PHP):
// - PASSWORD_DEFAULT
// - PASSWORD_BCRYPT
// - PASSWORD_ARGON2I
// - PASSWORD_ARGON2ID

// --- Part 1: Modular Crypt Format encode/decode

function crypt_modular_format_encode($id, $bin_salt, $bin_hash, $params=null) {
	// $<id>[$<param>=<value>(,<param>=<value>)*][$<salt>[$<hash>]]
	$out = '$'.$id;
	if (!is_null($params)) {
		$ary_params = array();
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

function vts_crypt_version($hash) {
	if (str_starts_with($hash, '$'.OID_MCF_VTS_V1.'$')) {
		return '1';
	} else {
		return '0';
	}
}

function vts_crypt_hash($algo, $str_password, $str_salt, $ver='1', $mode='ps', $iterations=0/*default*/) {
	if ($ver == '1') {
		if ($mode == 'sp') {
			$payload = $str_salt.$str_password;
			if (!hash_supported_natively($algo) && str_starts_with($algo, 'sha3-') && method_exists('\bb\Sha3\Sha3', 'hash')) {
				$bits = explode('-',$algo)[1];
				$bin_hash = \bb\Sha3\Sha3::hash($payload, $bits, true);
			} else {
				$bin_hash = hash($algo, $payload, true);
			}
		} else if ($mode == 'ps') {
			$payload = $str_password.$str_salt;
			if (!hash_supported_natively($algo) && str_starts_with($algo, 'sha3-') && method_exists('\bb\Sha3\Sha3', 'hash')) {
				$bits = explode('-',$algo)[1];
				$bin_hash = \bb\Sha3\Sha3::hash($payload, $bits, true);
			} else {
				$bin_hash = hash($algo, $payload, true);
			}
		} else if ($mode == 'sps') {
			$payload = $str_salt.$str_password.$str_salt;
			if (!hash_supported_natively($algo) && str_starts_with($algo, 'sha3-') && method_exists('\bb\Sha3\Sha3', 'hash')) {
				$bits = explode('-',$algo)[1];
				$bin_hash = \bb\Sha3\Sha3::hash($payload, $bits, true);
			} else {
				$bin_hash = hash($algo, $payload, true);
			}
		} else if ($mode == 'hmac') {
			if (!hash_hmac_supported_natively($algo) && str_starts_with($algo, 'sha3-') && method_exists('\bb\Sha3\Sha3', 'hash_hmac')) {
				$bits = explode('-',$algo)[1];
				$bin_hash = \bb\Sha3\Sha3::hash_hmac($str_password, $str_salt, $bits, true);
			} else {
				$bin_hash = hash_hmac($algo, $str_password, $str_salt, true);
			}
		} else if ($mode == 'pbkdf2') {
			if (!hash_pbkdf2_supported_natively($algo) && str_starts_with($algo, 'sha3-') && method_exists('\bb\Sha3\Sha3', 'hash_pbkdf2')) {
				if ($iterations == 0) {
					$iterations = 100; // because the userland implementation is EXTREMELY slow, we must choose a small value, sorry...
				}
				$bits = explode('-',$algo)[1];
				$bin_hash = \bb\Sha3\Sha3::hash_pbkdf2($str_password, $str_salt, $iterations, $bits, 0, true);
			} else {
				if ($iterations == 0) {
					// Recommendations taken from https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html#pbkdf2
					// Note that hash_pbkdf2() implements PBKDF2-HMAC-*
					if      ($algo == 'sha3-512')    $iterations =  100000;
					else if ($algo == 'sha3-384')    $iterations =  100000;
					else if ($algo == 'sha3-256')    $iterations =  100000;
					else if ($algo == 'sha3-224')    $iterations =  100000;
					else if ($algo == 'sha512')      $iterations =  210000; // value by owasp.org cheatcheat (28 February 2023)
					else if ($algo == 'sha512/256')  $iterations =  210000; // value by owasp.org cheatcheat (28 February 2023)
					else if ($algo == 'sha512/224')  $iterations =  210000; // value by owasp.org cheatcheat (28 February 2023)
					else if ($algo == 'sha384')      $iterations =  600000;
					else if ($algo == 'sha256')      $iterations =  600000; // value by owasp.org cheatcheat (28 February 2023)
					else if ($algo == 'sha224')      $iterations =  600000;
					else if ($algo == 'sha1')        $iterations = 1300000; // value by owasp.org cheatcheat (28 February 2023)
					else if ($algo == 'md5')         $iterations = 5000000;
					else                             $iterations =    5000;
				}
				$bin_hash = hash_pbkdf2($algo, $str_password, $str_salt, $iterations, 0, true);
			}
		} else {
			throw new Exception("Invalid VTS crypt version 1 mode. Expect sp, ps, sps, hmac, or pbkdf2.");
		}
		$bin_salt = $str_salt;
		$params = array();
		$params['a'] = $algo;
		$params['m'] = $mode;
		if ($mode == 'pbkdf2') $params['i'] = $iterations;
		return crypt_modular_format_encode(OID_MCF_VTS_V1, $bin_salt, $bin_hash, $params);
	} else {
		throw new Exception("Invalid VTS crypt version, expect 1.");
	}
}

function vts_crypt_verify($password, $hash): bool {
	$ver = vts_crypt_version($hash);
	if ($ver == '1') {
		// Decode the MCF hash parameters
		$data = crypt_modular_format_decode($hash);
		if ($data === false) throw new Exception('Invalid auth key');
		$id = $data['id'];
		$bin_salt = $data['salt'];
		$bin_hash = $data['hash'];
		$params = $data['params'];

		if (!isset($params['a'])) throw new Exception('Param "a" (algo) missing');
		$algo = $params['a'];

		if (!isset($params['m'])) throw new Exception('Param "m" (mode) missing');
		$mode = $params['m'];

		if ($mode == 'pbkdf2') {
			if (!isset($params['i'])) throw new Exception('Param "i" (iterations) missing');
			$iterations = $params['i'];
		} else {
			$iterations = 0;
		}

		// Create a VTS MCF 1.0 hash based on the parameters of $hash and the password $password
		$calc_authkey_1 = vts_crypt_hash($algo, $password, $bin_salt, $ver, $mode, $iterations);

		// We rewrite the MCF to make sure that they match (if params have the wrong order)
		$calc_authkey_2 = crypt_modular_format_encode($id, $bin_salt, $bin_hash, $params);

		return hash_equals($calc_authkey_1, $calc_authkey_2);
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
	if (vts_crypt_version($hash) != '0') {
		// Hash created by vts_password_hash(), or vts_crypt_hash()
		return vts_crypt_verify($password, $hash);
	} else {
		// Hash created by vts_password_hash(), password_hash(), or crypt()
		return password_verify($password, $hash);
	}
}

/** This function extends password_hash() with the algorithms supported by crypt().
 * It also adds vts_crypt_hash() which implements the ViaThinkSoft Modular Crypt Format 1.0.
 * The result can be verified using vts_password_verify().
 * @param string $password to be hashed
 * @param mixed $algo algorithm
 * @param array $options options for the hashing algorithm
 * @return string Crypt style password hash
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
		// Algorithms: PASSWORD_STD_DES
		//             PASSWORD_EXT_DES
		//             PASSWORD_MD5
		//             PASSWORD_BLOWFISH
		//             PASSWORD_SHA256
		//             PASSWORD_SHA512
		$out = crypt($password, $crypt_salt);
		if (strlen($out) < 13) throw new Exception("crypt() failed");
		return $out;
	} else if ($algo === PASSWORD_VTS_MCF1) {
		// Algorithms: PASSWORD_VTS_MCF1
		$ver  = '1';
		$algo = isset($options['algo']) ? $options['algo'] : 'sha3-512';
		$mode = isset($options['mode']) ? $options['mode'] : 'ps';
		$iterations = isset($options['iterations']) ? $options['iterations'] : 0/*default*/;
		$salt_len = isset($options['salt_length']) ? $options['salt_length'] : 50;
		$salt = random_bytes_ex($salt_len, true, true);
		return vts_crypt_hash($algo, $password, $salt, $ver, $mode, $iterations);
	} else {
		// Algorithms: PASSWORD_DEFAULT
		//             PASSWORD_BCRYPT
		//             PASSWORD_ARGON2I
		//             PASSWORD_ARGON2ID
		return password_hash($password, $algo, $options);
	}
}

// --- Part 4: Useful functions required by the crypt-functions

define('BASE64_RFC4648_ALPHABET', '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz+/');
define('BASE64_CRYPT_ALPHABET',   './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz');

function des_compat_salt($salt_len) {
	if ($salt_len <= 0) return '';
	$characters = BASE64_CRYPT_ALPHABET;
	$salt = '';
	$bytes = random_bytes_ex($salt_len, true, true);
	for ($i=0; $i<$salt_len; $i++) {
		$salt .= $characters[ord($bytes[$i]) % strlen($characters)];
	}
	return $salt;
}

function base64_int_encode($num) {
	// https://stackoverflow.com/questions/15534982/which-iteration-rules-apply-on-crypt-using-crypt-ext-des
	$alphabet_raw = BASE64_CRYPT_ALPHABET;
	$alphabet = str_split($alphabet_raw);
	$arr = array();
	$base = sizeof($alphabet);
	while ($num) {
		$rem = $num % $base;
		$num = (int)($num / $base);
		$arr[] = $alphabet[$rem];
	}
	$string = implode($arr);
	return str_pad($string, 4, '.', STR_PAD_RIGHT);
}

function crypt_radix64_encode($str) {
	$x = $str;
	$x = base64_encode($x);
	$x = rtrim($x, '='); // remove padding
	$x = strtr($x, BASE64_RFC4648_ALPHABET, BASE64_CRYPT_ALPHABET);
	return $x;
}

function crypt_radix64_decode($str) {
	$x = $str;
	$x = strtr($x, BASE64_CRYPT_ALPHABET, BASE64_RFC4648_ALPHABET);
	$x = base64_decode($x);
	return $x;
}

function hash_supported_natively($algo) {
	if (version_compare(PHP_VERSION, '5.1.2') >= 0) {
		return in_array($algo, hash_algos());
	} else {
		return false;
	}
}

function hash_hmac_supported_natively($algo): bool {
	if (version_compare(PHP_VERSION, '7.2.0') >= 0) {
		return in_array($algo, hash_hmac_algos());
	} else if (version_compare(PHP_VERSION, '5.1.2') >= 0) {
		return in_array($algo, hash_algos());
	} else {
		return false;
	}
}

function hash_pbkdf2_supported_natively($algo) {
	return hash_supported_natively($algo);
}

// --- Part 5: Selftest

/*
$rnd = random_bytes_ex(50, true, true);
assert(crypt_radix64_decode(crypt_radix64_encode($rnd)) === $rnd);

$password = random_bytes_ex(20, false, true);
assert(vts_password_verify($password,vts_password_hash($password, PASSWORD_STD_DES)));
assert(vts_password_verify($password,vts_password_hash($password, PASSWORD_EXT_DES)));
assert(vts_password_verify($password,vts_password_hash($password, PASSWORD_MD5)));
assert(vts_password_verify($password,vts_password_hash($password, PASSWORD_BLOWFISH)));
assert(vts_password_verify($password,vts_password_hash($password, PASSWORD_SHA256)));
assert(vts_password_verify($password,vts_password_hash($password, PASSWORD_SHA512)));
assert(vts_password_verify($password,$debug = vts_password_hash($password, PASSWORD_VTS_MCF1, array(
	'algo' => 'sha3-512',
	'mode' => 'pbkdf2',
	'iterations' => 5000
))));
echo "$debug\n";
assert(vts_password_verify($password,vts_password_hash($password, PASSWORD_DEFAULT)));
assert(vts_password_verify($password,vts_password_hash($password, PASSWORD_BCRYPT)));
if (defined('PASSWORD_ARGON2I'))
	assert(vts_password_verify($password,vts_password_hash($password, PASSWORD_ARGON2I)));
if (defined('PASSWORD_ARGON2ID'))
	assert(vts_password_verify($password,vts_password_hash($password, PASSWORD_ARGON2ID)));
echo "OK, Password $password\n";
*/
