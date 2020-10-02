<?php

/*
 * OIDplus 2.0
 * Copyright 2019 Daniel Marschall, ViaThinkSoft
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

class OIDplusAuthUtils {

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
		return $ra->checkPassword($password);
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
			list($s_salt, $hash) = explode('$', $passwordData, 2);
		} else {
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
		return uniqid(mt_rand(), true);
	}
	
	public function checkCSRF() {
		if (!$this->enable_csrf) return; 
		if (!isset($_REQUEST['csrf_token']) || !isset($_COOKIE['csrf_token']) || ($_REQUEST['csrf_token'] != $_COOKIE['csrf_token'])) {
			throw new Exception(_L('Wrong CSRF Token'));
		}
	}

	// Generate RA passwords

	public static function raGeneratePassword($password) {
		$s_salt = uniqid(mt_rand(), true);
		$calc_authkey = 'A2#'.base64_encode(sha3_512($s_salt.$password, true));
		return array($s_salt, $calc_authkey);
	}

	// Generate admin password

	/* Nothing here; the admin password will be generated in setup_base.js */

}
