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

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusCookieUtils extends OIDplusBaseClass {

	/**
	 * @param string $name
	 * @return void
	 * @throws OIDplusException
	 */
	public function unsetcookie(string $name) {
		$this->setcookie($name, '', time()-9999, true);
	}

	/**
	 * @return string
	 * @throws OIDplusException
	 */
	private function getCookieDomain(): string {
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

	/**
	 * @return string
	 * @throws OIDplusException
	 */
	private function getCookiePath(): string {
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

	// TODO: There are several PHPSESSID cookies set. That's not very nice. We should collect the cookies and then at script ending only send the last definition one time.
	/**
	 * @param string $name
	 * @param string $value
	 * @param int $expires
	 * @param bool $allowJS
	 * @param string|null $samesite
	 * @param bool $forceInsecure
	 * @return void
	 * @throws OIDplusException
	 */
	public function setcookie(string $name, string $value, int $expires=0, bool $allowJS=false, string $samesite=null, bool $forceInsecure=false) {
		$domain = $this->getCookieDomain();
		$path = $this->getCookiePath();
		$secure = !$forceInsecure && OIDplus::isSSL();
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
