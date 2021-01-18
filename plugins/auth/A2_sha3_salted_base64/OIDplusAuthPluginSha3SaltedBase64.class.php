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

class OIDplusAuthPluginSha3SaltedBase64 extends OIDplusAuthPlugin {

	public function verify(OIDplusRAAuthInfo $authInfo, $check_password) {
		$authKey = $authInfo->getAuthKey();
		$salt = $authInfo->getSalt();
		@list($s_authmethod, $s_authkey) = explode('#', $authKey, 2);

		if ($s_authmethod == 'A2') {
			// A2#X with X being sha3(salt+password) in base64-notation
			$calc_authkey = base64_encode(sha3_512($salt.$check_password, true));
		} else {
			// Invalid auth code
			return false;
		}

		return hash_equals($calc_authkey, $s_authkey);
	}

	public function generate($password): OIDplusRAAuthInfo {
		$s_salt = bin2hex(OIDplusAuthUtils::getRandomBytes(50)); // DB field ra.salt is limited to 100 chars (= 50 bytes)
		$calc_authkey = 'A2#'.base64_encode(sha3_512($s_salt.$password, true));
		return new OIDplusRAAuthInfo($s_salt, $calc_authkey);
	}

}
