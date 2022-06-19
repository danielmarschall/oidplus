<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2022 Daniel Marschall, ViaThinkSoft
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

class OIDplusAuthUtils extends OIDplusBaseClass {

	// Useful functions

	public static function getRandomBytes($len) {
		if (function_exists('openssl_random_pseudo_bytes')) {
			$a = openssl_random_pseudo_bytes($len);
			if ($a) return $a;
		}

		if (function_exists('mcrypt_create_iv')) {
			$a = bin2hex(mcrypt_create_iv($len));
			if ($a) return $a;
		}

		if (function_exists('random_bytes')) {
			$a = random_bytes($len);
			if ($a) return $a;
		}

		// Fallback to non-secure RNG
		$a = '';
		while (strlen($a) < $len*2) {
			$a .= sha1(uniqid((string)mt_rand(), true));
		}
		$a = substr($a, 0, $len*2);
		return hex2bin($a);
	}

	private static function raPepperProcessing(string $password): string {
		// Additional feature: Pepper
		// The pepper is stored inside the base configuration file
		// It prevents that an attacker with SQL write rights can
		// create accounts.
		// ATTENTION!!! If a pepper is used, then the
		// hashes are bound to that pepper. If you change the pepper,
		// then ALL passwords of RAs become INVALID!
		$pepper = OIDplus::baseConfig()->getValue('RA_PASSWORD_PEPPER','');
		if ($pepper !== '') {
			$algo = OIDplus::baseConfig()->getValue('RA_PASSWORD_PEPPER_ALGO','sha512'); // sha512 works with PHP 7.0
			if (strtolower($algo) === 'sha3-512') {
				$hmac = sha3_512_hmac($password, $pepper);
			} else {
				$hmac = hash_hmac($algo, $password, $pepper);
			}
			if ($hmac === false) throw new OIDplusException(_L('HMAC failed'));
			return $hmac;
		} else {
			return $password;
		}
	}

	// Content provider

	public function getAuthMethod() {
		$acs = $this->getAuthContentStore();
		if (is_null($acs)) return 'null';
		return get_class($acs);
	}

	protected function getAuthContentStore() {
		// Logged in via JWT
		$tmp = OIDplusAuthContentStoreJWT::getActiveProvider();
		if ($tmp) return $tmp;

		// Normal login via web-browser
		// Cookie will only be created once content is stored
		$tmp = OIDplusAuthContentStoreSession::getActiveProvider();
		if ($tmp) return $tmp;

		// No active session and no JWT token available. User is not logged in.
		return null;
	}

	public function getExtendedAttribute($name, $default=NULL) {
		$acs = $this->getAuthContentStore();
		if (is_null($acs)) return $default;
		return $acs->getValue($name, $default);
	}

	// RA authentication functions

	public function raLogin($email) {
		$acs = $this->getAuthContentStore();
		if (is_null($acs)) return;
		return $acs->raLogin($email);
	}

	public function raLogout($email) {
		$acs = $this->getAuthContentStore();
		if (is_null($acs)) return;
		return $acs->raLogout($email);
	}

	public function raCheckPassword($ra_email, $password) {
		$ra = new OIDplusRA($ra_email);

		// Get RA info from RA
		$authInfo = $ra->getAuthInfo();
		if (!$authInfo) return false; // user not found

		// Ask plugins if they can verify this hash
		$plugins = OIDplus::getAuthPlugins();
		if (count($plugins) == 0) {
			throw new OIDplusException(_L('No RA authentication plugins found'));
		}
		foreach ($plugins as $plugin) {
			if ($plugin->verify($authInfo, self::raPepperProcessing($password))) return true;
		}

		return false;
	}

	public function raNumLoggedIn() {
		$acs = $this->getAuthContentStore();
		if (is_null($acs)) return 0;
		return $acs->raNumLoggedIn();
	}

	public function loggedInRaList() {
		if ($this->forceAllLoggedOut()) {
			return array();
		} else {
			$acs = $this->getAuthContentStore();
			if (is_null($acs)) return array();
			return $acs->loggedInRaList();
		}
	}

	public function isRaLoggedIn($email) {
		$acs = $this->getAuthContentStore();
		if (is_null($acs)) return false;
		return $acs->isRaLoggedIn($email);
	}

	// "High level" function including logging and checking for valid JWT alternations
	public function raLoginEx($email, $remember_me, $origin='') {
		$loginfo = '';
		$acs = $this->getAuthContentStore();
		if (!is_null($acs)) {
			$acs->raLoginEx($email, $loginfo);
			$acs->activate();
		} else {
			if ($remember_me) {
				if (!OIDplus::baseConfig()->getValue('JWT_ALLOW_LOGIN_USER', true)) {
					throw new OIDplusException(_L('The administrator has disabled this feature. (Base configuration setting %1).','JWT_ALLOW_LOGIN_USER'));
				}
				$ttl = OIDplus::baseConfig()->getValue('JWT_TTL_LOGIN_USER', 10*365*24*60*60);
				$authSimulation = new OIDplusAuthContentStoreJWT();
				$authSimulation->raLoginEx($email, $loginfo);
				$authSimulation->setValue('oidplus_generator', OIDplusAuthContentStoreJWT::JWT_GENERATOR_LOGIN);
				$authSimulation->setValue('exp', time()+$ttl); // JWT "exp" attribute
				$authSimulation->activate();
			} else {
				$authSimulation = new OIDplusAuthContentStoreSession();
				$authSimulation->raLoginEx($email, $loginfo);
				$authSimulation->activate();
			}
		}
		$logmsg = "RA '$email' logged in";
		if ($origin != '') $logmsg .= " via $origin";
		if ($loginfo != '') $logmsg .= " ($loginfo)";
		OIDplus::logger()->log("[OK]RA($email)!", $logmsg);
	}

	public function raLogoutEx($email) {
		$loginfo = '';

		$acs = $this->getAuthContentStore();
		if (is_null($acs)) return;
		$res = $acs->raLogoutEx($email, $loginfo);

		OIDplus::logger()->log("[OK]RA($email)!", "RA '$email' logged out ($loginfo)");

		if (($this->raNumLoggedIn() == 0) && (!$this->isAdminLoggedIn())) {
			// Nobody logged in anymore. Destroy session cookie to make GDPR people happy
			$acs->destroySession();
		} else {
			// Get a new token for the remaining users
			$acs->activate();
		}

		return $res;
	}

	// Admin authentication functions

	public function adminLogin() {
		$acs = $this->getAuthContentStore();
		if (is_null($acs)) return;
		return $acs->adminLogin();
	}

	public function adminLogout() {
		$acs = $this->getAuthContentStore();
		if (is_null($acs)) return;
		return $acs->adminLogout();
	}

	public function adminCheckPassword($password) {
		$cfgData = OIDplus::baseConfig()->getValue('ADMIN_PASSWORD', '');
		if (empty($cfgData)) {
			throw new OIDplusException(_L('No admin password set in %1','userdata/baseconfig/config.inc.php'));
		}

		if (!is_array($cfgData)) {
			$passwordDataArray = array($cfgData);
		} else {
			$passwordDataArray = $cfgData;
		}

		foreach ($passwordDataArray as $passwordData) {
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

			if (hash_equals(sha3_512($s_salt.$password, true), base64_decode($hash))) return true;
		}

		return false;
	}

	public function isAdminLoggedIn() {
		if ($this->forceAllLoggedOut()) {
			return false;
		} else {
			$acs = $this->getAuthContentStore();
			if (is_null($acs)) return false;
			return $acs->isAdminLoggedIn();
		}
	}

	// "High level" function including logging and checking for valid JWT alternations
	public function adminLoginEx($remember_me, $origin='') {
		$loginfo = '';
		$acs = $this->getAuthContentStore();
		if (!is_null($acs)) {
			$acs->adminLoginEx($loginfo);
			$acs->activate();
		} else {
			if ($remember_me) {
				if (!OIDplus::baseConfig()->getValue('JWT_ALLOW_LOGIN_ADMIN', true)) {
					throw new OIDplusException(_L('The administrator has disabled this feature. (Base configuration setting %1).','JWT_ALLOW_LOGIN_ADMIN'));
				}
				$ttl = OIDplus::baseConfig()->getValue('JWT_TTL_LOGIN_ADMIN', 10*365*24*60*60);
				$authSimulation = new OIDplusAuthContentStoreJWT();
				$authSimulation->adminLoginEx($loginfo);
				$authSimulation->setValue('oidplus_generator', OIDplusAuthContentStoreJWT::JWT_GENERATOR_LOGIN);
				$authSimulation->setValue('exp', time()+$ttl); // JWT "exp" attribute
				$authSimulation->activate();
			} else {
				$authSimulation = new OIDplusAuthContentStoreSession();
				$authSimulation->adminLoginEx($loginfo);
				$authSimulation->activate();
			}
		}
		$logmsg = "Admin logged in";
		if ($origin != '') $logmsg .= " via $origin";
		if ($loginfo != '') $logmsg .= " ($loginfo)";
		OIDplus::logger()->log("[OK]A!", $logmsg);
	}

	public function adminLogoutEx() {
		$loginfo = '';

		$acs = $this->getAuthContentStore();
		if (is_null($acs)) return;
		$res = $acs->adminLogoutEx($loginfo);

		if ($this->raNumLoggedIn() == 0) {
			// Nobody here anymore. Destroy the cookie to make GDPR people happy
			$acs->destroySession();
		} else {
			// Get a new token for the remaining users
			$acs->activate();
		}

		OIDplus::logger()->log("[OK]A!", "Admin logged out ($loginfo)");
		return $res;
	}

	// Authentication keys for validating arguments (e.g. sent by mail)

	public static function makeAuthKey($data) {
		return sha3_512_hmac($data, 'authkey:'.OIDplus::baseConfig()->getValue('SERVER_SECRET'), false);
	}

	public static function validateAuthKey($data, $auth_key) {
		return hash_equals(self::makeAuthKey($data), $auth_key);
	}

	// "Veto" functions to force logout state

	protected function forceAllLoggedOut() {
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

		$request_token = isset($_REQUEST['csrf_token']) ? $_REQUEST['csrf_token'] : '';
		$cookie_token = isset($_COOKIE['csrf_token']) ? $_COOKIE['csrf_token'] : '';

		if (empty($request_token) || empty($cookie_token) || ($request_token !== $cookie_token)) {
			if (OIDplus::baseConfig()->getValue('DEBUG')) {
				throw new OIDplusException(_L('Missing or wrong CSRF Token: Request %1 vs Cookie %2',
					isset($_REQUEST['csrf_token']) ? '"'.$_REQUEST['csrf_token'].'"' : 'NULL',
					isset($_COOKIE['csrf_token']) ? $_COOKIE['csrf_token'] : 'NULL'
				));
			} else {
				throw new OIDplusException(_L('Missing or wrong "CSRF Token". To fix the issue, try clearing your browser cache and reload the page. If you visited the page via HTTPS before, try HTTPS in case you are currently connected via HTTP.'));
			}
		}
	}

	// Generate RA passwords

	public static function raGeneratePassword($password): OIDplusRAAuthInfo {
		$def_method = OIDplus::config()->getValue('default_ra_auth_method');

		$plugins = OIDplus::getAuthPlugins();
		foreach ($plugins as $plugin) {
			if (basename($plugin->getPluginDirectory()) === $def_method) {
				return $plugin->generate(self::raPepperProcessing($password));
			}
		}
		throw new OIDplusException(_L('Default RA auth method/plugin "%1" not found',$def_method));
	}

	// Generate admin password

	/* Nothing here; the admin password will be generated in setup_base.js , purely in the web-browser */

}
