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

class OIDplusAuthPluginBCrypt extends OIDplusAuthPlugin {

	public function init($html=true) {
		OIDplus::config()->prepareConfigKey('ra_bcrypt_cost', 'How complex should the BCrypt hash of RA passwords be? (Only for plugin A3_bcrypt)', 10, OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (empty($value) || !is_int($value) || ($value<4)) {
				throw new OIDplusException(_L('Invalid value for "cost".'));
			}
		});
	}

	public function verify(OIDplusRAAuthInfo $authInfo, $check_password) {
		$authKey = $authInfo->getAuthKey();
		$salt = $authInfo->getSalt();
		@list($s_authmethod, $s_authkey) = explode('#', $authKey, 2);

		if ($s_authmethod == 'A3') {
			// A3#$2a$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy
			//    \__/\/ \____________________/\_____________________________/
			//    Alg Cost   Salt (128 bit)             Hash
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
		$cost = OIDplus::config()->getValue('ra_bcrypt_cost', 10);
		$calc_authkey = 'A3#'.password_hash($password, PASSWORD_BCRYPT, array("cost" => $cost));
		return new OIDplusRAAuthInfo($s_salt, $calc_authkey);
	}

}
