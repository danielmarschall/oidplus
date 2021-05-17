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

	// JWT handling

	const JWT_GENERATOR_AJAX   = 0;
	const JWT_GENERATOR_LOGIN  = 1;
	const JWT_GENERATOR_MANUAL = 2;

	private function jwtGetBlacklistConfigKey($gen, $sub) {
		// Note: Needs to be <= 50 characters!
		return 'jwt_blacklist_gen('.$gen.')_sub('.trim(base64_encode(md5($sub,true)),'=').')';
	}

	public function jwtBlacklist($gen, $sub) {
		$cfg = $this->jwtGetBlacklistConfigKey($gen, $sub);
		$bl_time = time()-1;

		$gen_desc = 'Unknown';
		if ($gen === self::JWT_GENERATOR_AJAX)   $gen_desc = 'Automated AJAX calls';
		if ($gen === self::JWT_GENERATOR_LOGIN)  $gen_desc = 'Login';
		if ($gen === self::JWT_GENERATOR_MANUAL) $gen_desc = 'Manually created';

		OIDplus::config()->prepareConfigKey($cfg, 'Revoke timestamp of all JWT tokens for $sub with generator $gen ($gen_desc)', $bl_time, OIDplusConfig::PROTECTION_HIDDEN, function($value) {});
		OIDplus::config()->setValue($cfg, $bl_time);
	}

	public function jwtGetBlacklistTime($gen, $sub) {
		$cfg = $this->jwtGetBlacklistConfigKey($gen, $sub);
		return OIDplus::config()->getValue($cfg,0);
	}

	protected function jwtSecurityCheck($contentProvider) {
		// Check if the token is intended for us
		if ($contentProvider->getValue('aud','') !== "http://oidplus.com") {
			throw new OIDplusException(_L('Token has wrong audience'));
		}
		$gen = $contentProvider->getValue('oidplus_generator', -1);
		$sub = $contentProvider->getValue('sub', '');

		// Check if the token generator is allowed
		if ($gen === self::JWT_GENERATOR_AJAX) {
			if (($sub === 'admin') && !OIDplus::baseConfig()->getValue('JWT_ALLOW_AJAX_ADMIN', true)) {
				// Generator: plugins/adminPages/910_automated_ajax_calls/OIDplusPageAdminAutomatedAJAXCalls.class.php
				throw new OIDplusException(_L('The administrator has disabled this feature. (Base configuration setting %1).','JWT_ALLOW_AJAX_ADMIN'));
			}
			else if (($sub !== 'admin') && !OIDplus::baseConfig()->getValue('JWT_ALLOW_AJAX_USER', true)) {
				// Generator: plugins/raPages/910_automated_ajax_calls/OIDplusPageRaAutomatedAJAXCalls.class.php
				throw new OIDplusException(_L('The administrator has disabled this feature. (Base configuration setting %1).','JWT_ALLOW_AJAX_USER'));
			}
		}
		else if ($gen === self::JWT_GENERATOR_LOGIN) {
			// Used for feature "stay logged in" (use JWT token in a cookie as alternative to PHP session):
			// - No PHP session will be used
			// - Session will not be bound to IP address (therefore, you can switch between mobile/WiFi for example)
			// - No server-side session needed
			if (($sub === 'admin') && !OIDplus::baseConfig()->getValue('JWT_ALLOW_LOGIN_ADMIN', true)) {
				throw new OIDplusException(_L('The administrator has disabled this feature. (Base configuration setting %1).','JWT_ALLOW_LOGIN_ADMIN'));
			}
			else if (($sub !== 'admin') && !OIDplus::baseConfig()->getValue('JWT_ALLOW_LOGIN_USER', true)) {
				throw new OIDplusException(_L('The administrator has disabled this feature. (Base configuration setting %1).','JWT_ALLOW_LOGIN_USER'));
			}
		}
		else if ($gen === self::JWT_GENERATOR_MANUAL) {
			// Generator 2 are "hand-crafted" tokens
			if (!OIDplus::baseConfig()->getValue('JWT_ALLOW_MANUAL', true)) {
				throw new OIDplusException(_L('The administrator has disabled this feature. (Base configuration setting %1).','JWT_ALLOW_MANUAL'));
			}
		} else {
			throw new OIDplusException(_L('Token generator %1 not recognized',$gen));
		}

		// Make sure that the IAT (issued at time) isn't in a blacklisted timeframe
		// When an user believes that a token was compromised, then they can blacklist the tokens identified by their "iat" ("Issued at") property
		$bl_time = $this->jwtGetBlacklistTime($gen, $sub);
		$iat = $contentProvider->getValue('iat',0);
		if ($iat <= $bl_time) {
			throw new OIDplusException(_L('The JWT token was blacklisted on %1. Please generate a new one',date('d F Y, H:i:s',$bl_time)));
		}

		// Optional feature: Limit the JWT to a specific IP address
		// This could become handy if JWTs are used instead of Login sessions,
		// and you want to avoid session/JWT hijacking
		$ip = $contentProvider->getValue('ip','');
		if ($ip !== '') {
			if (isset($_SERVER['REMOTE_ADDR']) && ($ip !== $_SERVER['REMOTE_ADDR'])) {
				throw new OIDplusException(_L('Your IP address is not allowed to use this token'));
			}
		}

		// Checks which are dependent on the generator
		if ($gen === self::JWT_GENERATOR_LOGIN) {
			if (!isset($_COOKIE['OIDPLUS_AUTH_JWT'])) {
				throw new OIDplusException(_L('This kind of JWT token can only be used with the %1 request type','COOKIE'));
			}
		}
		if ($gen === self::JWT_GENERATOR_AJAX) {
			if (!isset($_GET['OIDPLUS_AUTH_JWT']) && !isset($_POST['OIDPLUS_AUTH_JWT'])) {
				throw new OIDplusException(_L('This kind of JWT token can only be used with the %1 request type','GET/POST'));
			}
			if (isset($_SERVER['SCRIPT_FILENAME']) && (strtolower(basename($_SERVER['SCRIPT_FILENAME'])) !== 'ajax.php')) {
				throw new OIDplusException(_L('This kind of JWT token can only be used in ajax.php'));
			}
		}
	}

	// Content provider

	protected function getAuthContentStore() {
		static $contentProvider = null;

		if (is_null($contentProvider)) {
			$jwt = '';
			if (isset($_COOKIE['OIDPLUS_AUTH_JWT'])) $jwt = $_COOKIE['OIDPLUS_AUTH_JWT'];
			if (isset($_POST['OIDPLUS_AUTH_JWT']))   $jwt = $_POST['OIDPLUS_AUTH_JWT'];
			if (isset($_GET['OIDPLUS_AUTH_JWT']))    $jwt = $_GET['OIDPLUS_AUTH_JWT'];

			if (!empty($jwt)) {
				$contentProvider = new OIDplusAuthContentStoreJWT();

				try {
					// Decode the JWT. In this step, the signature as well as EXP/NBF times will be checked
					$contentProvider->loadJWT($jwt);

					// Do various checks if the token is allowed and not blacklisted
					$this->jwtSecurityCheck($contentProvider);
				} catch (Exception $e) {
					if (isset($_GET['OIDPLUS_AUTH_JWT']) || isset($_POST['OIDPLUS_AUTH_JWT'])) {
						// Most likely an AJAX request. We can throw an Exception
						$contentProvider = null;
						throw new OIDplusException(_L('The JWT token was rejected: %1',$e->getMessage()));
					} else {
						// Most likely an expired Cookie/Login session. We must not throw an Exception, otherwise we will break jsTree
						$contentProvider = new OIDplusAuthContentStoreSession();
						OIDplus::cookieUtils()->unsetcookie('OIDPLUS_AUTH_JWT');
					}
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
