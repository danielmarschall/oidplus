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

class OIDplusAuthPluginPhpGenericSaltedHex extends OIDplusAuthPlugin {

	public function verify($authKey, $salt, $check_password) {
		@list($s_authmethod, $s_authkey) = explode('#', $authKey, 2);

		if ($s_authmethod == 'A1a') {
			// This auth method can be used by you if you migrate users from another software solution into OIDplus
			// A1a#hashalgo:X with X being hashalgo{hex}(salt+password)
			$hashalgo = explode(':', $s_authkey, 2)[0];
			$calc_authkey = $hashalgo.':'.hash($hashalgo, $salt.$check_password);
		} else if ($s_authmethod == 'A1b') {
			// This auth method can be used by you if you migrate users from another software solution into OIDplus
			// A1b#hashalgo:X with X being hashalgo{hex}(password+salt)
			$hashalgo = explode(':', $s_authkey, 2)[0];
			$calc_authkey = $hashalgo.':'.hash($hashalgo, $check_password.$salt);
		} else if ($s_authmethod == 'A1c') {
			// This auth method can be used by you if you migrate users from another software solution into OIDplus
			// A1c#hashalgo:X with X being hashalgo{hex}(salt+password+salt)
			$hashalgo = explode(':', $s_authkey, 2)[0];
			$calc_authkey = $hashalgo.':'.hash($hashalgo, $salt.$check_password.$salt);
		} else {
			// Invalid auth code
			return false;
		}

		return hash_equals($calc_authkey, $s_authkey);
	}

	public function generate($password) {
		$hashalgo = 'sha1';
		$s_salt = uniqid(mt_rand(), true);
		$calc_authkey = 'A1a#'.$hashalgo.':'.hash($hashalgo, $s_salt.$password);
		return array($s_salt, $calc_authkey);
	}

}
