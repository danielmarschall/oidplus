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

	private function getCookieDomain() {
		$default_domain = ''; // ini_get('session.cookie_domain');
		$domain = OIDplus::baseConfig()->getValue('COOKIE_DOMAIN', $default_domain);
		if ($domain === '(auto)') {
			$tmp = OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE/*_CANONICAL*/);
			if ($tmp === false) return $default_domain;
			$tmp = parse_url($tmp);
			if ($tmp === false) return $default_domain;
			if (!isset($tmp['host'])) return $default_domain;
			$domain = $tmp['host'];
		}
		return $domain;
	}

	private function getCookiePath() {
		$default_path = '/'; // ini_get('session.cookie_path');
		$path = OIDplus::baseConfig()->getValue('COOKIE_PATH', $default_path);
		if ($path === '(auto)') {
			$tmp = OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE/*_CANONICAL*/);
			if ($tmp === false) return $default_path;
			$tmp = parse_url($tmp);
			if ($tmp === false) return $default_path;
			if (!isset($tmp['path'])) return $default_path;
			$path = $tmp['path'];

			// Alternatively:
			//$path = OIDplus::webpath(null,OIDplus::PATH_RELATIVE_TO_ROOT_CANONICAL);
			//if ($path === false) return $default_path;
		}
		return $path;
	}

	public function setcookie($name, $value, $expires=0, $allowJS=false, $samesite=null) {
		$domain = $this->getCookieDomain();
		$path = $this->getCookiePath();
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
