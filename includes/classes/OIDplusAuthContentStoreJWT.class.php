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

	public function loadJWT($jwt, $additional_secret='') {
		$key = OIDplus::baseConfig()->getValue('SERVER_SECRET', '').$additional_secret;
		$algo = OIDplus::baseConfig()->getValue('JWK_ALGORITHM', 'HS256');
		$this->content = (array) \Firebase\JWT\JWT::decode($jwt, $key, array($algo));
	}

	public function getJWTToken($lifetime=100*365*24*60*60, $additional_secret='') {
		$key = OIDplus::baseConfig()->getValue('SERVER_SECRET', '').$additional_secret;
		$payload = $this->content;
		$payload["iss"] = "http://oidplus.com";
		$payload["aud"] = "http://oidplus.com";
		$payload["iat"] = time();
		$payload["exp"] = time() + $lifetime;
		$algo = OIDplus::baseConfig()->getValue('JWK_ALGORITHM', 'HS256');
		return \Firebase\JWT\JWT::encode($payload, $key, $algo);
	}

}
