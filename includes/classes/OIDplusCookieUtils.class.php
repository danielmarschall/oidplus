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

class OIDplusCookieUtils extends OIDplusBaseClass {

	public function unsetcookie($name) {
		$this->setcookie($name, '', time()-9999, true);
	}

	public function setcookie($name, $value, $expires=0, $allowJS=false, $samesite=null) {
		// $path = ini_get('session.cookie_path');
		$path = parse_url(OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE))['path'];

		$domain = '';
		$secure = OIDplus::isSSL();
		$httponly = !$allowJS;
		if (is_null($samesite)) {
			$samesite = OIDplus::baseConfig()->getValue('COOKIE_SAMESITE_POLICY', 'Strict');
		}

		if (strnatcmp(phpversion(),'7.3.0') >= 0) {
			$options = array(
				"expires" => $expires,
				"path" => $path,
				"domain" => $domain,
				"secure" => $secure,
				"httponly" => $httponly,
				"samesite" => $samesite
			);
			setcookie($name, $value, $options);
		} else {
			setcookie($name, $value, $expires, $path.'; samesite='.$samesite, $domain, $secure, $httponly);
		}
	}

}
