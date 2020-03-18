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

if (!defined('IN_OIDPLUS')) die();

class OIDplusAuthPluginSha3SaltedBase64 extends OIDplusAuthPlugin {
	public function verify($authKey, $salt, $check_password) {
		@list($s_authmethod, $s_authkey) = explode('#', $authKey, 2);

		if ($s_authmethod == 'A2') {
			// Default OIDplus 2.0 behavior
			// A2#X with X being sha3{base64}(salt+password)
			$calc_authkey = base64_encode(version_compare(PHP_VERSION, '7.1.0') >= 0 ? hash('sha3-512', $salt.$check_password, true) : bb\Sha3\Sha3::hash($salt.$check_password, 512, true));
		} else {
			// Invalid auth code
			return false;
		}

		return hash_equals($calc_authkey, $s_authkey);
	}
}

OIDplus::registerAuthPlugin(new OIDplusAuthPluginSha3SaltedBase64());
