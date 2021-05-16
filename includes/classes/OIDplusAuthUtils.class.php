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

class OIDplusAuthUtils {

	// Useful functions

	public static function getRandomBytes($len) {
		if (function_exists('openssl_random_pseudo_bytes')) {
			$a = openssl_random_pseudo_bytes($len);
			if ($a) return $a;
		}

		if (function_exists('mcrypt_create_iv')) {
			$a = bin2hex(mcrypt_create_iv($len, MCRYPT_DEV_URANDOM));
			if ($a) return $a;
		}

		if (function_exists('random_bytes')) {
			$a = random_bytes($len);
			if ($a) return $a;
		}

		// Fallback to non-secure RNG
		$a = '';
		while (strlen($a) < $len*2) {
			$a .= sha1(uniqid(mt_rand(), true));
		}
		$a = substr($a, 0, $len*2);
		return hex2bin($a);
	}

	// Content provider

	protected function getAuthContentStore() {
		static $contentProvider = null;

		if (is_null($contentProvider)) {
			if (isset($_REQUEST['OIDPLUS_AUTH_JWT'])) {
				$contentProvider = new OIDplusAuthContentStoreJWT();
				$contentProvider->loadJWT($_REQUEST['OIDPLUS_AUTH_JWT']);

				// Check if the token is intended for us
				if ($contentProvider->getValue('aud','') !== "http://oidplus.com") {
					throw new OIDplusException(_L('This JWT token is not valid'));
				}

				// Check if the token generator is allowed
				$gen = $contentProvider->getValue('oidplus_generator', -1);
				$sub = $contentProvider->getValue('sub', '');
				$ok = false;
				if (($gen === 0) && ($sub === 'admin') && OIDplus::baseConfig()->getValue('JWT_ALLOW_AJAX_ADMIN', true)) $ok = true;
				else if (($gen === 0) && ($sub !== 'admin') && OIDplus::baseConfig()->getValue('JWT_ALLOW_AJAX_USER', true)) $ok = true;
				// Reserved for future use (use JWT token in a cookie as alternative to PHP session):
				//else if (($gen === 1) && ($sub === 'admin') && OIDplus::baseConfig()->getValue('JWT_ALLOW_LOGIN_ADMIN', true)) $ok = true;
				//else if (($gen === 1) && ($sub !== 'admin') && OIDplus::baseConfig()->getValue('JWT_ALLOW_LOGIN_USER', true)) $ok = true;
				else if (($gen === 2) && OIDplus::baseConfig()->getValue('JWT_ALLOW_MANUAL', true)) $ok = true;
				if (!$ok) {
					throw new OIDplusException(_L('This JWT token is not valid or the administrator has disabled the functionality.'));
				}

				// Make sure that the IAT (issued at time) isn't in a blacklisted timeframe
				// When an user believes that a token was compromised, then they can define a virtual NBF ("not before") attribute to all of their tokens
				$cfg = 'jwt_nbf_gen('.$gen.')_sub('.base64_encode(md5($sub,true)).')';
				$nbf = OIDplus::config()->getValue($cfg,0);
				$iat = $contentProvider->getValue('iat',0);
				if ($iat <= $nbf) {
					throw new OIDplusException(_L('The JWT token was blacklisted (NBF). Please generate a new one'));
				}
			} else {
				// Normal login via web-browser
				$contentProvider = new OIDplusAuthContentStoreSession();
			}
		}

		return $contentProvider;
	}

	// RA authentication functions

	public function raLogin($email) {
		return $this->getAuthContentStore()->raLogin($email);
	}

	public function raLogout($email) {
		return $this->getAuthContentStore()->raLogout($email);
	}

	public function raCheckPassword($ra_email, $password) {
		$ra = new OIDplusRA($ra_email);

		$authInfo = $ra->getAuthInfo();

		$plugins = OIDplus::getAuthPlugins();
		if (count($plugins) == 0) {
			throw new OIDplusException(_L('No RA authentication plugins found'));
		}
		foreach ($plugins as $plugin) {
			if ($plugin->verify($authInfo, $password)) return true;
		}

		return false;
	}

	public function raNumLoggedIn() {
		return $this->getAuthContentStore()->raNumLoggedIn();
	}

	public function raLogoutAll() {
		return $this->getAuthContentStore()->raLogoutAll();
	}

	public function loggedInRaList() {
		if (OIDplus::authUtils()->forceAllLoggedOut()) {
			return array();
		} else {
			return $this->getAuthContentStore()->loggedInRaList();
		}
	}

	public function isRaLoggedIn($email) {
		return $this->getAuthContentStore()->isRaLoggedIn($email);
	}

	// Admin authentication functions

	public function adminLogin() {
		return $this->getAuthContentStore()->adminLogin();
	}

	public function adminLogout() {
		return $this->getAuthContentStore()->adminLogout();
	}

	public function adminCheckPassword($password) {
		$passwordData = OIDplus::baseConfig()->getValue('ADMIN_PASSWORD', '');
		if (empty($passwordData)) {
			throw new OIDplusException(_L('No admin password set in %1','userdata/baseconfig/config.inc.php'));
		}

		if (strpos($passwordData, '$') !== false) {
			if ($passwordData[0] == '$') {
				// Version 3: BCrypt
				return password_verify($password, $passwordData);
			} else {
				// Version 2: SHA3-512 with salt
				list($s_salt, $hash) = explode('$', $passwordData, 2);
			}
		} else {
			// Version 1: SHA3-512 without salt
			$s_salt = '';
			$hash = $passwordData;
		}
		return strcmp(sha3_512($s_salt.$password, true), base64_decode($hash)) === 0;
	}

	public function isAdminLoggedIn() {
		if (OIDplus::authUtils()->forceAllLoggedOut()) {
			return false;
		} else {
			return $this->getAuthContentStore()->isAdminLoggedIn();
		}
	}

	// Authentication keys for validating arguments (e.g. sent by mail)

	public static function makeAuthKey($data) {
		$data = OIDplus::baseConfig()->getValue('SERVER_SECRET') . '/AUTHKEY/' . $data;
		$calc_authkey = sha3_512($data, false);
		return $calc_authkey;
	}

	public static function validateAuthKey($data, $auth_key) {
		return strcmp(self::makeAuthKey($data), $auth_key) === 0;
	}

	// "Veto" functions to force logout state

	public static function forceAllLoggedOut() {
		if (isset($_SERVER['SCRIPT_FILENAME']) && (basename($_SERVER['SCRIPT_FILENAME']) == 'sitemap.php')) {
			// The sitemap may not contain any confidential information,
			// even if the user is logged in, because the admin could
			// accidentally copy-paste the sitemap to a
			// search engine control panel while they are logged in
			return true;
		} else {
			return false;
		}
	}

	// CSRF functions

	private $enable_csrf = true;

	public function enableCSRF() {
		$this->enable_csrf = true;
	}

	public function disableCSRF() {
		$this->enable_csrf = false;
	}

	public function genCSRFToken() {
		return bin2hex(self::getRandomBytes(64));
	}

	public function checkCSRF() {
		if (!$this->enable_csrf) return;
		if (!isset($_REQUEST['csrf_token']) || !isset($_COOKIE['csrf_token']) || ($_REQUEST['csrf_token'] !== $_COOKIE['csrf_token'])) {
			throw new OIDplusException(_L('Wrong CSRF Token'));
		}
	}

	// Generate RA passwords

	public static function raGeneratePassword($password): OIDplusRAAuthInfo {
		$def_method = OIDplus::config()->getValue('default_ra_auth_method');

		$plugins = OIDplus::getAuthPlugins();
		foreach ($plugins as $plugin) {
			if (basename($plugin->getPluginDirectory()) === $def_method) {
				return $plugin->generate($password);
			}
		}
		throw new OIDplusException(_L('Default RA auth method/plugin "%1" not found',$def_method));
	}

	// Generate admin password

	/* Nothing here; the admin password will be generated in setup_base.js */

}
