<?php

/*
 * password_hash_ex() function which integrates crypt() algorithms in password_hash().
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

define('PASSWORD_STD_DES',  'std_des');
define('PASSWORD_EXT_DES',  'ext_des');
define('PASSWORD_MD5',      'md5');
define('PASSWORD_BLOWFISH', 'blowfish');
define('PASSWORD_SHA256',   'sha256');
define('PASSWORD_SHA512',   'sha512');

require_once __DIR__ . '/misc_functions.inc.php';

function des_compat_salt($salt_len) {
	if ($salt_len <= 0) return '';
	$characters = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
	$salt = '';
	$bytes = random_bytes_ex($salt_len);
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

/** This function extends password_hash() with the algorithms supported by crypt().
 * The result can be verified using password_verify().
 * @param string $password to be hashed
 * @param mixed $algo algorithm
 * @param array $options options for the hashing algorithm
 * @return string Crypt compatible password hash
 */
function password_hash_ex($password, $algo, $options=array()) {
	$salt = null;
	if (($algo === PASSWORD_STD_DES) && defined('CRYPT_STD_DES')) {
		// Standard DES-based hash with a two character salt from the alphabet "./0-9A-Za-z". Using invalid characters in the salt will cause crypt() to fail.
		$salt = des_compat_salt(2);
	} else if (($algo === PASSWORD_EXT_DES) && defined('CRYPT_EXT_DES')) {
		// Extended DES-based hash. The "salt" is a 9-character string consisting of an underscore followed by 4 characters of iteration count and 4 characters of salt. Each of these 4-character strings encode 24 bits, least significant character first. The values 0 to 63 are encoded as ./0-9A-Za-z. Using invalid characters in the salt will cause crypt() to fail.
		$iterations = isset($options['iterations']) ? $options['iterations'] : 725;
		$salt = '_' . base64_int_encode($iterations) . des_compat_salt(4);
	} else if (($algo === PASSWORD_MD5) && defined('CRYPT_MD5')) {
		// MD5 hashing with a twelve character salt starting with $1$
		$salt = '$1$'.des_compat_salt(12).'$';
	} else if (($algo === PASSWORD_BLOWFISH) && defined('CRYPT_BLOWFISH')) {
		// Blowfish hashing with a salt as follows: "$2a$", "$2x$" or "$2y$", a two digit cost parameter, "$", and 22 characters from the alphabet "./0-9A-Za-z". Using characters outside of this range in the salt will cause crypt() to return a zero-length string. The two digit cost parameter is the base-2 logarithm of the iteration count for the underlying Blowfish-based hashing algorithm and must be in range 04-31, values outside this range will cause crypt() to fail. "$2x$" hashes are potentially weak; "$2a$" hashes are compatible and mitigate this weakness. For new hashes, "$2y$" should be used.
		$algo = '$2y$'; // most secure
		$cost = isset($options['cost']) ? $options['cost'] : 10;
		$salt = $algo.str_pad($cost,2,'0',STR_PAD_LEFT).'$'.des_compat_salt(22).'$';
	} else if (($algo === PASSWORD_SHA256) && defined('CRYPT_SHA256')) {
		// SHA-256 hash with a sixteen character salt prefixed with $5$. If the salt string starts with 'rounds=<N>$', the numeric value of N is used to indicate how many times the hashing loop should be executed, much like the cost parameter on Blowfish. The default number of rounds is 5000, there is a minimum of 1000 and a maximum of 999,999,999. Any selection of N outside this range will be truncated to the nearest limit.
		$algo = '$5$';
		$rounds = isset($options['rounds']) ? $options['rounds'] : 5000;
		$salt = $algo.'rounds='.$rounds.'$'.des_compat_salt(16).'$';
	} else if (($algo === PASSWORD_SHA512) && defined('CRYPT_SHA512')) {
		// SHA-512 hash with a sixteen character salt prefixed with $6$. If the salt string starts with 'rounds=<N>$', the numeric value of N is used to indicate how many times the hashing loop should be executed, much like the cost parameter on Blowfish. The default number of rounds is 5000, there is a minimum of 1000 and a maximum of 999,999,999. Any selection of N outside this range will be truncated to the nearest limit.
		$algo = '$6$';
		$rounds = isset($options['rounds']) ? $options['rounds'] : 5000;
		$salt = $algo.'rounds='.$rounds.'$'.des_compat_salt(16).'$';
	}

	if (!is_null($salt)) {
		$out = crypt($password, $salt);
		if (strlen($out) < 13) throw new Exception("crypt() failed");
		return $out;
	} else {
		// $algo === PASSWORD_DEFAULT
		// $algo === PASSWORD_BCRYPT
		// $algo === PASSWORD_ARGON2I
		// $algo === PASSWORD_ARGON2ID
		return password_hash($password, $algo, $options);
	}
}

/*
assert(password_verify('test123',password_hash_ex('test123', PASSWORD_STD_DES)));
assert(password_verify('test123',password_hash_ex('test123', PASSWORD_EXT_DES)));
assert(password_verify('test123',password_hash_ex('test123', PASSWORD_MD5)));
assert(password_verify('test123',password_hash_ex('test123', PASSWORD_BLOWFISH)));
assert(password_verify('test123',password_hash_ex('test123', PASSWORD_SHA256)));
assert(password_verify('test123',password_hash_ex('test123', PASSWORD_SHA512)));
assert(password_verify('test123',password_hash_ex('test123', PASSWORD_DEFAULT)));
assert(password_verify('test123',password_hash_ex('test123', PASSWORD_BCRYPT)));
assert(password_verify('test123',password_hash_ex('test123', PASSWORD_ARGON2I)));
assert(password_verify('test123',password_hash_ex('test123', PASSWORD_ARGON2ID)));
*/
