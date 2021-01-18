<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2021 Daniel Marschall, ViaThinkSoft
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

if (!defined('INSIDE_OIDPLUS')) die();

class OIDplusAuthPluginPhpGenericSaltedHex extends OIDplusAuthPlugin {

	public function verify(OIDplusRAAuthInfo $authInfo, $check_password) {
		$authKey = $authInfo->getAuthKey();
		$salt = $authInfo->getSalt();
		@list($s_authmethod, $s_authkey) = explode('#', $authKey, 2);

		if ($s_authmethod == 'A1a') {
			// This auth method can be used by you if you migrate users from another software solution into OIDplus
			// A1a#hashalgo:X with X being H(salt+password) in hex-notation
			// Attention: With some hash algorithms, prepending the salt makes it vulnerable against length-extension-attacks
			$hashalgo = explode(':', $s_authkey, 2)[0];
			$calc_authkey = $hashalgo.':'.hash($hashalgo, $salt.$check_password);
		} else if ($s_authmethod == 'A1b') {
			// This auth method can be used by you if you migrate users from another software solution into OIDplus
			// A1b#hashalgo:X with X being H(password+salt) in hex-notation
			$hashalgo = explode(':', $s_authkey, 2)[0];
			$calc_authkey = $hashalgo.':'.hash($hashalgo, $check_password.$salt);
		} else if ($s_authmethod == 'A1c') {
			// This auth method can be used by you if you migrate users from another software solution into OIDplus
			// A1c#hashalgo:X with X being H(salt+password+salt) in hex-notation
			$hashalgo = explode(':', $s_authkey, 2)[0];
			$calc_authkey = $hashalgo.':'.hash($hashalgo, $salt.$check_password.$salt);
		} else if ($s_authmethod == 'A1d') {
			// This auth method can be used by you if you migrate users from another software solution into OIDplus
			// A1d#hashalgo:X with X being H_HMAC(password,salt) in hex-notation
			$hashalgo = explode(':', $s_authkey, 2)[0];
			$calc_authkey = $hashalgo.':'.hash_hmac($hashalgo, $check_password, $salt);
		} else {
			// Invalid auth code
			return false;
		}

		return hash_equals($calc_authkey, $s_authkey);
	}

	public function generate($password): OIDplusRAAuthInfo {
		$preferred_hash_algos = array(
		    // sorted by priority
		    //'sha3-512', // this would exceed the 100 byte auth key length
		    //'sha3-384', // this would exceed the 100 byte auth key length
		    'sha3-256',
		    'sha3-224',
		    //'sha512', // this would exceed the 100 byte auth key length
		    'sha512/256',
		    'sha512/224',
		    //'sha384', // this would exceed the 100 byte auth key length
		    'sha256',
		    'sha224',
		    'sha1',
		    'md5'
		);
		$algos = hash_algos();
		$hashalgo = null;
		foreach ($preferred_hash_algos as $a) {
			if (in_array($a, $algos)) {
				$hashalgo = $a;
				break;
			}
		}
		if (is_null($hashalgo)) {
			throw new OIDplusException(_L('No fitting hash algorithm found'));
		}
		$s_salt = bin2hex(OIDplusAuthUtils::getRandomBytes(50)); // DB field ra.salt is limited to 100 chars (= 50 bytes)
		$calc_authkey = 'A1c#'.$hashalgo.':'.hash($hashalgo, $s_salt.$password.$s_salt);

		return new OIDplusRAAuthInfo($s_salt, $calc_authkey);
	}

}
