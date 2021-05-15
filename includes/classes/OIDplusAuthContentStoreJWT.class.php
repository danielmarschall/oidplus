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

class OIDplusAuthContentStoreJWT extends OIDplusAuthContentStoreDummy {

	// Individual functions

	public function loadJWT($jwt) {
		\Firebase\JWT\JWT::$leeway = 60; // leeway in seconds
		if (OIDplus::getPkiStatus()) {
			$pubKey = OIDplus::config()->getValue('oidplus_public_key');
			$this->content = (array) \Firebase\JWT\JWT::decode($jwt, $pubKey, array('RS256', 'RS384', 'RS512'));
		} else {
			$key = OIDplus::baseConfig()->getValue('SERVER_SECRET', '');
			$key = hash_pbkdf2('sha512', $key, '', 10000, 64/*256bit*/, false);
			$this->content = (array) \Firebase\JWT\JWT::decode($jwt, $key, array('HS256', 'HS384', 'HS512'));
		}
	}

	public function getJWTToken($lifetime=100*365*24*60*60) {
		$payload = $this->content;
		$payload["iss"] = "http://oidplus.com";
		$payload["aud"] = "http://oidplus.com";
		$payload["jti"] = gen_uuid();
		$payload["iat"] = time();
		$payload["exp"] = time() + $lifetime;

		if (OIDplus::getPkiStatus()) {
			$privKey = OIDplus::config()->getValue('oidplus_private_key');
			return \Firebase\JWT\JWT::encode($payload, $privKey, 'RS512');
		} else {
			$key = OIDplus::baseConfig()->getValue('SERVER_SECRET', '');
			$key = hash_pbkdf2('sha512', $key, '', 10000, 64/*256bit*/, false);
			return \Firebase\JWT\JWT::encode($payload, $key, 'HS512');
		}
	}

}
