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

	// RA authentication functions

	public static function raLogin($email) {
		if (strpos($email, '|') !== false) return;

		$ses = OIDplus::sesHandler();
		$list = $ses->getValue('oidplus_logged_in');
		if (is_null($list)) $list = '';

		$ary = ($list == '') ? array() : explode('|', $list);
		if (!in_array($email, $ary)) $ary[] = $email;
		$list = implode('|', $ary);

		$ses->setValue('oidplus_logged_in', $list);
	}

	public static function raLogout($email) {
		$ses = OIDplus::sesHandler();
		$list = $ses->getValue('oidplus_logged_in');
		if (is_null($list)) $list = '';

		$ary = ($list == '') ? array() : explode('|', $list);
		$key = array_search($email, $ary);
		if ($key !== false) unset($ary[$key]);
		$list = implode('|', $ary);

		$ses->setValue('oidplus_logged_in', $list);

		if (($list == '') && (!self::isAdminLoggedIn())) {
			// Nobody logged in anymore. Destroy session cookie to make GDPR people happy
			$ses->destroySession();
		}
	}

	public static function raCheckPassword($ra_email, $password) {
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

	public static function raNumLoggedIn() {
		return count(self::loggedInRaList());
	}

	public static function raLogoutAll() {
		$ses = OIDplus::sesHandler();
		$ses->setValue('oidplus_logged_in', '');
	}

	public static function loggedInRaList() {
		if (self::forceAllLoggedOut()) {
			return array();
		}

		$ses = OIDplus::sesHandler();
		$list = $ses->getValue('oidplus_logged_in');
		if (is_null($list)) $list = '';

		$res = array();
		foreach (array_unique(explode('|',$list)) as $ra_email) {
			if ($ra_email == '') continue;
			$res[] = new OIDplusRA($ra_email);
		}
		return $res;
	}

	public static function isRaLoggedIn($email) {
		foreach (self::loggedInRaList() as $ra) {
			if ($email == $ra->raEmail()) return true;
		}
		return false;
	}

	// Admin authentication functions

	public static function adminLogin() {
		$ses = OIDplus::sesHandler();
		$ses->setValue('oidplus_admin_logged_in', '1');
	}

	public static function adminLogout() {
		$ses = OIDplus::sesHandler();
		$ses->setValue('oidplus_admin_logged_in', '0');

		if (self::raNumLoggedIn() == 0) {
			// Nobody logged in anymore. Destroy session cookie to make GDPR people happy
			$ses->destroySession();
		}
	}

	public static function adminCheckPassword($password) {
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

	public static function isAdminLoggedIn() {
		if (self::forceAllLoggedOut()) {
			return false;
		}
		$ses = OIDplus::sesHandler();
		return $ses->getValue('oidplus_admin_logged_in') == '1';
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
		return bin2hex(OIDplusAuthUtils::getRandomBytes(64));
	}

	public function checkCSRF() {
		if (!$this->enable_csrf) return;
		if (!isset($_REQUEST['csrf_token']) || !isset($_COOKIE['csrf_token']) || ($_REQUEST['csrf_token'] != $_COOKIE['csrf_token'])) {
			throw new Exception(_L('Wrong CSRF Token'));
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
