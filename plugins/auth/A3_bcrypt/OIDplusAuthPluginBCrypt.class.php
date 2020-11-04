<?php

/*
 * OIDplus 2.0
 * Copyright 2020 Daniel Marschall, ViaThinkSoft
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

class OIDplusAuthPluginBCrypt extends OIDplusAuthPlugin {

	public function verify(OIDplusRAAuthInfo $authInfo, $check_password) {
		@list($s_authmethod, $s_authkey) = explode('#', $authKey, 2);

		$authKey = $authInfo->getAuthKey();
		$salt = $authInfo->getSalt();

		if ($s_authmethod == 'A3') {
			// A3#$2a$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy
			//    \__/\/ \____________________/\_____________________________/
			//     Alg Cost      Salt                        Hash
			if ($salt != '') {
				throw new OIDplusException(_L('This function does not accept a salt'));
			}
			return password_verify($check_password, $s_authkey);
		} else {
			// Invalid auth code
			return false;
		}
	}

	public function generate($password): OIDplusRAAuthInfo {
		$s_salt = ''; // BCrypt automatically generates a salt
		$calc_authkey = 'A3#'.password_hash($password, PASSWORD_BCRYPT);
		return array($s_salt, $calc_authkey);
	}

}
