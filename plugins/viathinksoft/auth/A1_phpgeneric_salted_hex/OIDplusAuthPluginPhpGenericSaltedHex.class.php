<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2023 Daniel Marschall, ViaThinkSoft
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

namespace ViaThinkSoft\OIDplus;

class OIDplusAuthPluginPhpGenericSaltedHex extends OIDplusAuthPlugin {

	private function getBinaryHash($s_authmethod, $hashalgo, $salt, $check_password) {
		if ($s_authmethod == 'A1a') {
			// This auth method can be used by you if you migrate users from another software solution into OIDplus
			// A1a#hashalgo:X with X being H(salt+password) in hex- or base64-notation
			// Attention: With some hash algorithms, prepending the salt makes it vulnerable against length-extension-attacks
			return hash($hashalgo, $salt.$check_password, true);
		} else if ($s_authmethod == 'A1b') {
			// This auth method can be used by you if you migrate users from another software solution into OIDplus
			// A1b#hashalgo:X with X being H(password+salt) in hex- or base64-notation
			return hash($hashalgo, $check_password.$salt, true);
		} else if ($s_authmethod == 'A1c') {
			// This auth method can be used by you if you migrate users from another software solution into OIDplus
			// A1c#hashalgo:X with X being H(salt+password+salt) in hex- or base64-notation
			return hash($hashalgo, $salt.$check_password.$salt, true);
		} else if ($s_authmethod == 'A1d') {
			// This auth method can be used by you if you migrate users from another software solution into OIDplus
			// A1d#hashalgo:X with X being H_HMAC(password,salt) in hex- or base64-notation
			return hash_hmac($hashalgo, $check_password, $salt, true);
		} else {
			// Invalid auth code
			return false;
		}
	}

	public function verify(OIDplusRAAuthInfo $authInfo, $check_password) {
		$authKey = $authInfo->getAuthKey();
		$salt = $authInfo->getSalt();
		@list($s_authmethod, $s_authkey) = explode('#', $authKey, 2);

		$hashalgo = explode(':', $s_authkey, 2)[0];

		$bindata = $this->getBinaryHash($s_authmethod, $hashalgo, $salt, $check_password);
		if ($bindata === false) return false;

		return hash_equals($s_authkey, $hashalgo.':'.strtolower(bin2hex($bindata)))
		    || hash_equals($s_authkey, $hashalgo.':'.base64_encode($bindata))
		    || hash_equals($s_authkey, $hashalgo.':'.rtrim(base64_encode($bindata),'='));
	}

	public function generate($password): OIDplusRAAuthInfo {
		$preferred_hash_algos = array(
		    // sorted by priority
		    'sha3-512',
		    'sha3-384',
		    'sha3-256',
		    'sha3-224',
		    'sha512',
		    'sha512/256',
		    'sha512/224',
		    'sha384',
		    'sha256',
		    'sha224',
		    'sha1',
		    'md5'
		);

		$s_authmethod = 'A1c';

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
		$salt = bin2hex(OIDplus::authUtils()->getRandomBytes(50)); // DB field ra.salt is limited to 100 chars (= 50 bytes)

		$bindata = $this->getBinaryHash($s_authmethod, $hashalgo, $salt, $password);
		if ($bindata === false) throw new OIDplusException(_L('Invalid hash auth method'));

		$calc_authkey = $s_authmethod.'#'.$hashalgo.':'.strtolower(bin2hex($bindata));

		if (strlen($calc_authkey) > 100) {
			// Since our database field is limited to 100 bytes, use base64 instead of hex
			$calc_authkey = $s_authmethod.'#'.$hashalgo.':'.base64_encode($bindata);
			$calc_authkey = rtrim($calc_authkey,'=');
			/*
			[Base64] sha3-512   authkey length in hex is 141 and length in base64 is 99
			[Base64] sha3-384   authkey length in hex is 109 and length in base64 is 77
			[Hex]    sha3-256   authkey length in hex is  77 and length in base64 is 56
			[Hex]    sha3-224   authkey length in hex is  69 and length in base64 is 51
			[Base64] sha512     authkey length in hex is 139 and length in base64 is 97
			[Hex]    sha512/256 authkey length in hex is  79 and length in base64 is 58
			[Hex]    sha512/224 authkey length in hex is  71 and length in base64 is 53
			[Base64] sha384     authkey length in hex is 107 and length in base64 is 75
			[Hex]    sha256     authkey length in hex is  75 and length in base64 is 54
			[Hex]    sha224     authkey length in hex is  67 and length in base64 is 49
			[Hex]    sha1       authkey length in hex is  49 and length in base64 is 36
			[Hex]    md5        authkey length in hex is  40 and length in base64 is 30
			*/
		}

		return new OIDplusRAAuthInfo($salt, $calc_authkey);
	}

}
