<?php

/*
 * ViaThinkSoft Modular Crypt Format 1.0 and vts_password_*() functions
 * Copyright 2023 Daniel Marschall, ViaThinkSoft
 * Revision 2023-03-03
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
	$1.3.6.1.4.1.37476.3.0.1.1$a=<algo>,m=<mode>[,i=<iterations>]$<salt>$<hash>
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
Not supported are these hashes (because they have a special salt-handling and output their own crypt format):
	bcrypt [Standardized crypt identifier 2, 2a, 2x, 2y]
	argon2i [Crypt identifier argon2i, not standardized]
	argon2id [Crypt identifier argon2i, not standardized]
Valid <mode> :
	sp = salt + password
	ps = password + salt
	sps = salt + password + salt
	hmac = HMAC (salt is the key)
	pbkdf2 = PBKDF2-HMAC (Additional param i= contains the number of iterations)
<iterations> can be omitted if 0. It is required for mode=pbkdf2. For sp/ps/sps/hmac, it is optional.
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
define('PASSWORD_STD_DES',   'std-des');       // Algorithm from crypt()
define('PASSWORD_EXT_DES',   'ext-des');       // Algorithm from crypt()
define('PASSWORD_MD5',       'md5');           // Algorithm from crypt()
define('PASSWORD_BLOWFISH',  'blowfish');      // Algorithm from crypt()
define('PASSWORD_SHA256',    'sha256');        // Algorithm from crypt()
define('PASSWORD_SHA512',    'sha512');        // Algorithm from crypt()
define('PASSWORD_VTS_MCF1',  OID_MCF_VTS_V1);  // Algorithm by ViaThinkSoft
// Other valid values (already defined in PHP):
// - PASSWORD_DEFAULT
// - PASSWORD_BCRYPT
// - PASSWORD_ARGON2I
// - PASSWORD_ARGON2ID

define('PASSWORD_VTS_MCF1_MODE_SP',             'sp');     // Salt+Password
define('PASSWORD_VTS_MCF1_MODE_PS',             'ps');     // Password+Salt
define('PASSWORD_VTS_MCF1_MODE_SPS',            'sps');    // Salt+Password+Salt
define('PASSWORD_VTS_MCF1_MODE_HMAC',           'hmac');   // HMAC
define('PASSWORD_VTS_MCF1_MODE_PBKDF2',         'pbkdf2'); // PBKDF2-HMAC

define('PASSWORD_EXT_DES_DEFAULT_ITERATIONS',   725);
define('PASSWORD_BLOWFISH_DEFAULT_COST',        10);
define('PASSWORD_SHA256_DEFAULT_ROUNDS',        5000);
define('PASSWORD_SHA512_DEFAULT_ROUNDS',        5000);
define('PASSWORD_VTS_MCF1_DEFAULT_ALGO',        'sha3-512'); // any value in hash_algos(), NOT vts_hash_algos()
define('PASSWORD_VTS_MCF1_DEFAULT_MODE',        PASSWORD_VTS_MCF1_MODE_PS);
define('PASSWORD_VTS_MCF1_DEFAULT_ITERATIONS',  0); // For PBKDF2, iterations=0 means: Default, depending on the algo

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

	return array('id'     => $id,
	             'salt'   => $bin_salt,
	             'hash'   => $bin_hash,
	             'params' => $params);
}

// --- Part 2: ViaThinkSoft Modular Crypt Format 1.0

function vts_crypt_version($hash) {
	if (str_starts_with($hash, '$'.OID_MCF_VTS_V1.'$')) {
		return '1';
	} else {
		return '0';
	}
}

function vts_crypt_hash($algo, $str_password, $str_salt, $ver='1', $mode=PASSWORD_VTS_MCF1_DEFAULT_MODE, $iterations=PASSWORD_VTS_MCF1_DEFAULT_ITERATIONS) {
	if ($ver == '1') {
		if ($mode == PASSWORD_VTS_MCF1_MODE_SP) {
			$bin_hash = hash_ex($algo, $str_salt.$str_password, true);
			for ($i=0; $i<$iterations; $i++) {
				$bin_hash = hash_ex($algo, $str_salt.$bin_hash.$i, true);
			}
		} else if ($mode == PASSWORD_VTS_MCF1_MODE_PS) {
			$bin_hash = hash_ex($algo, $str_password.$str_salt, true);
			for ($i=0; $i<$iterations; $i++) {
				$bin_hash = hash_ex($algo, $bin_hash.$i.$str_salt, true);
			}
		} else if ($mode == PASSWORD_VTS_MCF1_MODE_SPS) {
			$bin_hash = hash_ex($algo, $str_salt.$str_password.$str_salt, true);
			for ($i=0; $i<$iterations; $i++) {
				$bin_hash = hash_ex($algo, $str_salt.$bin_hash.$i.$str_salt, true);
			}
		} else if ($mode == PASSWORD_VTS_MCF1_MODE_HMAC) {
			$bin_hash = hash_hmac_ex($algo, $str_password, $str_salt, true);
			for ($i=0; $i<$iterations; $i++) {
				// https://security.stackexchange.com/questions/149299/rounds-in-a-hashing-function
				$bin_hash = hash_hmac_ex($algo, $str_password, $bin_hash.$i, true);
			}
		} else if ($mode == PASSWORD_VTS_MCF1_MODE_PBKDF2) {
			// Note: If $iterations=0, then hash_pbkdf2_ex() will correct it to the best value depending on $algo, see _vts_password_default_iterations().
			$bin_hash = hash_pbkdf2_ex($algo, $str_password, $str_salt, $iterations, 0, true);
		} else {
			throw new Exception("Invalid VTS crypt version 1 mode. Expect sp, ps, sps, hmac, or pbkdf2.");
		}
		$bin_salt = $str_salt;
		$params = array();
		$params['a'] = $algo;
		$params['m'] = $mode;
		if ($iterations != 0) $params['i'] = $iterations; // i can be omitted if it is 0.
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

		if ($mode == PASSWORD_VTS_MCF1_MODE_PBKDF2) {
			if (!isset($params['i'])) throw new Exception('Param "i" (iterations) missing');
			$iterations = $params['i'];
		} else {
			$iterations = isset($params['i']) ? $params['i'] : 0;
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

// --- Part 3: Replacement of vts_password_*() functions

/**
 * This function replaces password_algos() by extending it with
 * password hashes that are implemented in vts_password_hash().
 * @return array of hashes that can be used in vts_password_hash().
 */
function vts_password_algos() {
	$hashes = password_algos();
	$hashes[] = PASSWORD_STD_DES;   // Algorithm from crypt()
	$hashes[] = PASSWORD_EXT_DES;   // Algorithm from crypt()
	$hashes[] = PASSWORD_MD5;       // Algorithm from crypt()
	$hashes[] = PASSWORD_BLOWFISH;  // Algorithm from crypt()
	$hashes[] = PASSWORD_SHA256;    // Algorithm from crypt()
	$hashes[] = PASSWORD_SHA512;    // Algorithm from crypt()
	$hashes[] = PASSWORD_VTS_MCF1;  // Algorithm by ViaThinkSoft
	return $hashes;
}

/** vts_password_get_info() is the same as password_get_info(),
 * but it adds the crypt() and ViaThinkSoft MCF 1.0 algos which can be
 * produced by vts_password_hash()
 * @param string $hash Hash created by vts_password_hash(), password_hash(), or crypt().
 * @return array Same output like password_get_info().
 */
function vts_password_get_info($hash) {
	if (vts_crypt_version($hash) == '1') {
		// OID_MCF_VTS_V1
		$mcf = crypt_modular_format_decode($hash);

		//$options['salt_length'] = strlen($mcf['salt']);  // Note: salt_length is not an MCF option! It's just a hint for vts_password_hash()

		if (!isset($mcf['params']['a'])) throw new Exception('Param "a" (algo) missing');
		$options['algo'] = $mcf['params']['a'];

		if (!isset($mcf['params']['m'])) throw new Exception('Param "m" (mode) missing');
		$options['mode'] = $mcf['params']['m'];

		if ($options['mode'] == PASSWORD_VTS_MCF1_MODE_PBKDF2) {
			if (!isset($mcf['params']['i'])) throw new Exception('Param "i" (iterations) missing');
			$options['iterations'] = (int)$mcf['params']['i'];
		} else {
			$options['iterations'] = isset($mcf['params']['i']) ? (int)$mcf['params']['i'] : 0;
		}

		return array(
			"algo" => PASSWORD_VTS_MCF1,
			"algoName" => "vts-mcf-v1",
			"options" => $options
		);
	} else if (!str_starts_with($hash, '$') && (strlen($hash) == 13)) {
		// PASSWORD_STD_DES
		return array(
			"algo" => PASSWORD_STD_DES,
			"algoName" => "std-des",
			"options" => array(
				// None
			)
		);
	} else if (str_starts_with($hash, '_') && (strlen($hash) == 20)) {
		// PASSWORD_EXT_DES
		return array(
			"algo" => PASSWORD_EXT_DES,
			"algoName" => "ext-des",
			"options" => array(
				"iterations" => (int)base64_int_decode(substr($hash,1,4))
			)
		);
	} else if (str_starts_with($hash, '$1$')) {
		// PASSWORD_MD5
		return array(
			"algo" => PASSWORD_MD5,
			"algoName" => "md5",
			"options" => array(
				// None
			)
		);
	} else if (str_starts_with($hash, '$2$')  || str_starts_with($hash, '$2a$') ||
	           str_starts_with($hash, '$2x$') || str_starts_with($hash, '$2y$')) {
		// PASSWORD_BLOWFISH
		return array(
			"algo" => PASSWORD_BLOWFISH,
			"algoName" => "blowfish",
			"options" => array(
				"cost" => (int)ltrim(explode('$',$hash)[2],'0')
			)
		);
	} else if (str_starts_with($hash, '$5$')) {
		// PASSWORD_SHA256
		return array(
			"algo" => PASSWORD_SHA256,
			"algoName" => "sha256",
			"options" => array(
				'rounds' => (int)str_replace('rounds=','',explode('$',$hash)[2])
			)
		);
	} else if (str_starts_with($hash, '$6$')) {
		// PASSWORD_SHA512
		return array(
			"algo" => PASSWORD_SHA512,
			"algoName" => "sha512",
			"options" => array(
				'rounds' => (int)str_replace('rounds=','',explode('$',$hash)[2])
			)
		);
	} else {
		// PASSWORD_DEFAULT
		// PASSWORD_BCRYPT
		// PASSWORD_ARGON2I
		// PASSWORD_ARGON2ID
		return password_get_info($hash);
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
	$options = vts_password_fill_default_options($algo, $options);

	$crypt_salt = null;
	if (($algo === PASSWORD_STD_DES) && defined('CRYPT_STD_DES')) {
		// Standard DES-based hash with a two character salt from the alphabet "./0-9A-Za-z". Using invalid characters in the salt will cause crypt() to fail.
		$crypt_salt = des_compat_salt(2);
	} else if (($algo === PASSWORD_EXT_DES) && defined('CRYPT_EXT_DES')) {
		// Extended DES-based hash. The "salt" is a 9-character string consisting of an underscore followed by 4 characters of iteration count and 4 characters of salt. Each of these 4-character strings encode 24 bits, least significant character first. The values 0 to 63 are encoded as ./0-9A-Za-z. Using invalid characters in the salt will cause crypt() to fail.
		$iterations = $options['iterations'];
		$crypt_salt = '_' . base64_int_encode($iterations,4) . des_compat_salt(4);
	} else if (($algo === PASSWORD_MD5) && defined('CRYPT_MD5')) {
		// MD5 hashing with a twelve character salt starting with $1$
		$crypt_salt = '$1$'.des_compat_salt(12).'$';
	} else if (($algo === PASSWORD_BLOWFISH) && defined('CRYPT_BLOWFISH')) {
		// Blowfish hashing with a salt as follows: "$2a$", "$2x$" or "$2y$", a two digit cost parameter, "$", and 22 characters from the alphabet "./0-9A-Za-z". Using characters outside of this range in the salt will cause crypt() to return a zero-length string. The two digit cost parameter is the base-2 logarithm of the iteration count for the underlying Blowfish-based hashing algorithm and must be in range 04-31, values outside this range will cause crypt() to fail. "$2x$" hashes are potentially weak; "$2a$" hashes are compatible and mitigate this weakness. For new hashes, "$2y$" should be used.
		$algo = '$2y$'; // most secure
		$cost = $options['cost'];
		$crypt_salt = $algo.str_pad($cost,2,'0',STR_PAD_LEFT).'$'.des_compat_salt(22).'$';
	} else if (($algo === PASSWORD_SHA256) && defined('CRYPT_SHA256')) {
		// SHA-256 hash with a sixteen character salt prefixed with $5$. If the salt string starts with 'rounds=<N>$', the numeric value of N is used to indicate how many times the hashing loop should be executed, much like the cost parameter on Blowfish. The default number of rounds is 5000, there is a minimum of 1000 and a maximum of 999,999,999. Any selection of N outside this range will be truncated to the nearest limit.
		$algo = '$5$';
		$rounds = $options['rounds'];
		$crypt_salt = $algo.'rounds='.$rounds.'$'.des_compat_salt(16).'$';
	} else if (($algo === PASSWORD_SHA512) && defined('CRYPT_SHA512')) {
		// SHA-512 hash with a sixteen character salt prefixed with $6$. If the salt string starts with 'rounds=<N>$', the numeric value of N is used to indicate how many times the hashing loop should be executed, much like the cost parameter on Blowfish. The default number of rounds is 5000, there is a minimum of 1000 and a maximum of 999,999,999. Any selection of N outside this range will be truncated to the nearest limit.
		$algo = '$6$';
		$rounds = $options['rounds'];
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
		$algo = $options['algo'];
		$mode = $options['mode'];
		$iterations = $options['iterations'];
		$salt_len = isset($options['salt_length']) ? $options['salt_length'] : 32; // Note: salt_length is not an MCF option! It's just a hint for vts_password_hash()
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

/** This function replaces password_needs_rehash() by adding additional algorithms
 * supported by vts_password_hash().
 * @param string $hash The current hash
 * @param string|int|null $algo Desired new default algo
 * @param array $options Desired new default options
 * @return bool True if algo or options of the current hash don't match the current desired values ($algo and $options), otherwise false.
 */
function vts_password_needs_rehash($hash, $algo, $options=array()) {
	$options = vts_password_fill_default_options($algo, $options);

	$info = vts_password_get_info($hash);
	$algo2 = $info['algo'];
	$options2 = $info['options'];

	// Check if algorithm matches
	if ($algo !== $algo2) return true;

	if (vts_crypt_version($hash) == '1') {
		if (isset($options['salt_length'])) {
			// For VTS MCF 1.0, salt_length is a valid option for vts_password_hash(),
			// but it is not a valid option inside the MCF options
			// and it is not a valid option for vts_password_get_info().
			unset($options['salt_length']);
		}

		// For PBKDF2, iterations=0 means: Default, depending on the algo
		if (($options['iterations'] == 0/*default*/) && ($options2['mode'] == PASSWORD_VTS_MCF1_MODE_PBKDF2)) {
			$algo = $options2['algo'];
			$userland = !hash_pbkdf2_supported_natively($algo) && str_starts_with($algo, 'sha3-') && method_exists('\bb\Sha3\Sha3', 'hash_pbkdf2');
			$options['iterations'] = _vts_password_default_iterations($algo, $userland);
		}
	}

	// Check if options match
	if (count($options) !== count($options2)) return true;
	foreach ($options as $name => $val) {
		if ($options2[$name] != $val) return true;
	}
	return false;
}

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

// --- Part 4: Functions which include a fallback to a pure-PHP sha3 implementation (requires https://github.com/danielmarschall/php-sha3 )

function hash_ex($algo, $data, $binary=false, $options=array()) {
	if (!hash_supported_natively($algo) && str_starts_with($algo, 'sha3-') && method_exists('\bb\Sha3\Sha3', 'hash')) {
		$bits = (int)explode('-',$algo)[1];
		$hash = \bb\Sha3\Sha3::hash($data, $bits, $binary);
	} else {
		$hash = hash($algo, $data, $binary);
	}
	return $hash;
}

function hash_hmac_ex($algo, $data, $key, $binary=false) {
	if (!hash_hmac_supported_natively($algo) && str_starts_with($algo, 'sha3-') && method_exists('\bb\Sha3\Sha3', 'hash_hmac')) {
		$bits = (int)explode('-',$algo)[1];
		$hash = \bb\Sha3\Sha3::hash_hmac($data, $key, $bits, $binary);
	} else {
		$hash = hash_hmac($algo, $data, $key, $binary);
	}
	return $hash;
}

function hash_pbkdf2_ex($algo, $password, $salt, &$iterations=0, $length=0, $binary=false) {
	if (!hash_pbkdf2_supported_natively($algo) && str_starts_with($algo, 'sha3-') && method_exists('\bb\Sha3\Sha3', 'hash_pbkdf2')) {
		if ($iterations == 0/*default*/) {
			$iterations = _vts_password_default_iterations($algo, true);
		}
		$bits = (int)explode('-',$algo)[1];
		$hash = \bb\Sha3\Sha3::hash_pbkdf2($password, $salt, $iterations, $bits, $length, $binary);
	} else {
		if ($iterations == 0/*default*/) {
			$iterations = _vts_password_default_iterations($algo, false);
		}
		$hash = hash_pbkdf2($algo, $password, $salt, $iterations, $length, $binary);
	}
	return $hash;
}

// --- Part 5: Useful functions required by the crypt-functions

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

function base64_int_encode($num, $len) {
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
	return str_pad($string, $len, '.', STR_PAD_RIGHT);
}

function base64_int_decode($base64) {
	$num = 0;
	for ($i=strlen($base64)-1;$i>=0;$i--) {
		$num += strpos(BASE64_CRYPT_ALPHABET, $base64[$i])*pow(strlen(BASE64_CRYPT_ALPHABET),$i);
	}
	return $num;
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

function vts_password_fill_default_options($algo, $options) {
	if ($algo === PASSWORD_STD_DES) {
		// No options
	} else if ($algo === PASSWORD_EXT_DES) {
		if (!isset($options['iterations'])) {
			$options['iterations'] = PASSWORD_EXT_DES_DEFAULT_ITERATIONS;
		}
	} else if ($algo === PASSWORD_MD5) {
		// No options
	} else if ($algo === PASSWORD_BLOWFISH) {
		if (!isset($options['cost'])) {
			$options['cost'] = PASSWORD_BLOWFISH_DEFAULT_COST;
		}
	} else if ($algo === PASSWORD_SHA256) {
		if (!isset($options['rounds'])) {
			$options['rounds'] = PASSWORD_SHA256_DEFAULT_ROUNDS;
		}
	} else if ($algo === PASSWORD_SHA512) {
		if (!isset($options['rounds'])) {
			$options['rounds'] = PASSWORD_SHA512_DEFAULT_ROUNDS;
		}
	} else if ($algo === PASSWORD_VTS_MCF1) {
		if (!isset($options['algo'])) {
			$options['algo'] = PASSWORD_VTS_MCF1_DEFAULT_ALGO;
		}
		if (!isset($options['mode'])) {
			$options['mode'] = PASSWORD_VTS_MCF1_DEFAULT_MODE;
		}
		if ($options['mode'] == PASSWORD_VTS_MCF1_MODE_PBKDF2) {
			if (!isset($options['iterations'])) {
				$options['iterations'] = PASSWORD_VTS_MCF1_DEFAULT_ITERATIONS;
			}
		} else {
			$options['iterations'] = isset($options['iterations']) ? $options['iterations'] : 0;
		}
	}
	return $options;
}

function _vts_password_default_iterations($algo, $userland) {
	if ($userland) {
		return 100; // because the userland implementation is EXTREMELY slow, we must choose a small value, sorry...
	} else {
		// Recommendations taken from https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html#pbkdf2
		// Note that hash_pbkdf2() implements PBKDF2-HMAC-*
		if      ($algo == 'sha3-512')    return  100000;
		else if ($algo == 'sha3-384')    return  100000;
		else if ($algo == 'sha3-256')    return  100000;
		else if ($algo == 'sha3-224')    return  100000;
		else if ($algo == 'sha512')      return  210000; // value by owasp.org cheatcheat (28 February 2023)
		else if ($algo == 'sha512/256')  return  210000; // value by owasp.org cheatcheat (28 February 2023)
		else if ($algo == 'sha512/224')  return  210000; // value by owasp.org cheatcheat (28 February 2023)
		else if ($algo == 'sha384')      return  600000;
		else if ($algo == 'sha256')      return  600000; // value by owasp.org cheatcheat (28 February 2023)
		else if ($algo == 'sha224')      return  600000;
		else if ($algo == 'sha1')        return 1300000; // value by owasp.org cheatcheat (28 February 2023)
		else if ($algo == 'md5')         return 5000000;
		else                             return    5000;
	}
}

// --- Part 6: Selftest

/*
for ($i=0; $i<9999; $i++) {
	assert($i===base64_int_decode(base64_int_encode($i,4)));
}

$rnd = random_bytes_ex(50, true, true);
assert(crypt_radix64_decode(crypt_radix64_encode($rnd)) === $rnd);

$password = random_bytes_ex(20, false, true);

assert(vts_password_verify($password,$dummy = vts_password_hash($password, PASSWORD_STD_DES)));
//echo "'$dummy' ".strlen($dummy)."\n";
//var_dump(vts_password_get_info($dummy));

assert(vts_password_verify($password,$dummy = vts_password_hash($password, PASSWORD_EXT_DES)));
//echo "'$dummy' ".strlen($dummy)."\n";
//var_dump(vts_password_get_info($dummy));

assert(vts_password_verify($password,$dummy = vts_password_hash($password, PASSWORD_MD5)));
//echo "'$dummy' ".strlen($dummy)."\n";
//var_dump(vts_password_get_info($dummy));

assert(vts_password_verify($password,$dummy = vts_password_hash($password, PASSWORD_BLOWFISH)));
//echo "'$dummy' ".strlen($dummy)."\n";
//var_dump(vts_password_get_info($dummy));

assert(vts_password_verify($password,$dummy = vts_password_hash($password, PASSWORD_SHA256)));
//echo "'$dummy' ".strlen($dummy)."\n";
//var_dump(vts_password_get_info($dummy));

assert(vts_password_verify($password,$dummy = vts_password_hash($password, PASSWORD_SHA512)));
//echo "'$dummy' ".strlen($dummy)."\n";
//var_dump(vts_password_get_info($dummy));

assert(vts_password_verify($password,$dummy = vts_password_hash($password, PASSWORD_VTS_MCF1, array(
	'algo' => 'sha3-512',
	'mode' => 'pbkdf2',
	'iterations' => 0
))));
//echo "'$dummy' ".strlen($dummy)."\n";
//var_dump(vts_password_get_info($dummy));
assert(false===vts_password_needs_rehash($dummy,PASSWORD_VTS_MCF1,array(
	'salt_length' => 51,
	'algo' => 'sha3-512',
	'mode' => 'pbkdf2',
	'iterations' => 0
)));
assert(true===vts_password_needs_rehash($dummy,PASSWORD_VTS_MCF1,array(
	'salt_length' => 50,
	'algo' => 'sha3-256',
	'mode' => 'pbkdf2',
	'iterations' => 0
)));

assert(vts_password_verify($password,$dummy = vts_password_hash($password, PASSWORD_VTS_MCF1, array(
	'algo' => 'sha3-512',
	'mode' => 'sps',
	'iterations' => 2
))));
//echo "'$dummy' ".strlen($dummy)."\n";
//var_dump(vts_password_get_info($dummy));
assert(false===vts_password_needs_rehash($dummy,PASSWORD_VTS_MCF1,array(
	'salt_length' => 51,
	'algo' => 'sha3-512',
	'mode' => 'sps',
	'iterations' => 2
)));
assert(true===vts_password_needs_rehash($dummy,PASSWORD_VTS_MCF1,array(
	'salt_length' => 50,
	'algo' => 'sha3-256',
	'mode' => 'sps',
	'iterations' => 2
)));

assert(vts_password_verify($password,$dummy = vts_password_hash($password, PASSWORD_VTS_MCF1, array(
	'algo' => 'sha3-512',
	'mode' => 'hmac',
	'iterations' => 2
))));
//echo "'$dummy' ".strlen($dummy)."\n";
//var_dump(vts_password_get_info($dummy));
assert(false===vts_password_needs_rehash($dummy,PASSWORD_VTS_MCF1,array(
	'salt_length' => 51,
	'algo' => 'sha3-512',
	'mode' => 'hmac',
	'iterations' => 2
)));
assert(true===vts_password_needs_rehash($dummy,PASSWORD_VTS_MCF1,array(
	'salt_length' => 50,
	'algo' => 'sha3-256',
	'mode' => 'hmac',
	'iterations' => 2
)));
*/
