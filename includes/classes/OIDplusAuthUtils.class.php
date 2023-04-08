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

class OIDplusAuthUtils extends OIDplusBaseClass {

	// Useful functions

	/**
	 * @param string $password
	 * @return string
	 * @throws OIDplusException
	 */
	private function raPepperProcessing(string $password): string {
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
			if ($hmac === "") throw new OIDplusException(_L('HMAC failed'));
			return $hmac;
		} else {
			return $password;
		}
	}

	// Content provider

	/**
	 * @return string
	 * @throws OIDplusException
	 */
	public function getAuthMethod(): string {
		$acs = $this->getAuthContentStore();
		if (is_null($acs)) return 'null';
		return get_class($acs);
	}

	/**
	 * @return OIDplusAuthContentStore|null
	 * @throws OIDplusException
	 */
	protected function getAuthContentStore()/*: ?OIDplusAuthContentStore*/ {
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

	/**
	 * @param string $name
	 * @param mixed|null $default
	 * @return mixed
	 * @throws OIDplusException
	 */
	public function getExtendedAttribute(string $name, $default=NULL) {
		$acs = $this->getAuthContentStore();
		if (is_null($acs)) return $default;
		return $acs->getValue($name, $default);
	}

	// RA authentication functions

	/**
	 * @param string $email
	 * @return void
	 * @throws OIDplusException
	 */
	public function raLogin(string $email) {
		$acs = $this->getAuthContentStore();
		if (is_null($acs)) return;
		$acs->raLogin($email);
	}

	/**
	 * @param string $email
	 * @return void
	 * @throws OIDplusException
	 */
	public function raLogout(string $email) {
		$acs = $this->getAuthContentStore();
		if (is_null($acs)) return;
		$acs->raLogout($email);
	}

	/**
	 * @param string $ra_email
	 * @param string $password
	 * @return bool
	 * @throws OIDplusException
	 */
	public function raCheckPassword(string $ra_email, string $password): bool {
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
			if ($plugin->verify($authInfo, $this->raPepperProcessing($password))) return true;
		}

		return false;
	}

	/**
	 * @return int
	 * @throws OIDplusException
	 */
	public function raNumLoggedIn(): int {
		$acs = $this->getAuthContentStore();
		if (is_null($acs)) return 0;
		return $acs->raNumLoggedIn();
	}

	/**
	 * @return OIDplusRA[]
	 * @throws OIDplusException
	 */
	public function loggedInRaList(): array {
		if ($this->forceAllLoggedOut()) {
			return array();
		} else {
			$acs = $this->getAuthContentStore();
			if (is_null($acs)) return array();
			return $acs->loggedInRaList();
		}
	}

	/**
	 * @param string $email
	 * @return bool
	 * @throws OIDplusException
	 */
	public function isRaLoggedIn(string $email): bool {
		$acs = $this->getAuthContentStore();
		if (is_null($acs)) return false;
		return $acs->isRaLoggedIn($email);
	}

	// "High level" function including logging and checking for valid JWT alternations

	/**
	 * @param string $email
	 * @param bool $remember_me
	 * @param string $origin
	 * @return void
	 * @throws OIDplusException
	 */
	public function raLoginEx(string $email, bool $remember_me, string $origin='') {
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

	/**
	 * @param string $email
	 * @return void
	 * @throws OIDplusException
	 */
	public function raLogoutEx(string $email) {
		$loginfo = '';

		$acs = $this->getAuthContentStore();
		if (is_null($acs)) return;
		$acs->raLogoutEx($email, $loginfo);

		OIDplus::logger()->log("[OK]RA($email)!", "RA '$email' logged out ($loginfo)");

		if (($this->raNumLoggedIn() == 0) && (!$this->isAdminLoggedIn())) {
			// Nobody logged in anymore. Destroy session cookie to make GDPR people happy
			$acs->destroySession();
		} else {
			// Get a new token for the remaining users
			$acs->activate();
		}
	}

	// Admin authentication functions

	/**
	 * @return void
	 * @throws OIDplusException
	 */
	public function adminLogin() {
		$acs = $this->getAuthContentStore();
		if (is_null($acs)) return;
		$acs->adminLogin();
	}

	/**
	 * @return void
	 * @throws OIDplusException
	 */
	public function adminLogout() {
		$acs = $this->getAuthContentStore();
		if (is_null($acs)) return;
		$acs->adminLogout();
	}

	/**
	 * @param string $password
	 * @return bool
	 * @throws OIDplusException
	 */
	public function adminCheckPassword(string $password): bool {
		$cfgData = OIDplus::baseConfig()->getValue('ADMIN_PASSWORD', '');
		if (empty($cfgData)) {
			throw new OIDplusException(_L('No admin password set in %1','userdata/baseconfig/config.inc.php'));
		}

		if (!is_array($cfgData)) {
			$passwordDataArray = array($cfgData);
		} else {
			$passwordDataArray = $cfgData; // Multiple Administrator passwords
		}

		foreach ($passwordDataArray as $passwordData) {
			if (str_starts_with($passwordData, '$')) {
				// Version 3: BCrypt (or any other crypt)
				$ok = password_verify($password, $passwordData);
			} else if (strpos($passwordData, '$') !== false) {
				// Version 2: SHA3-512 with salt
				list($salt, $hash) = explode('$', $passwordData, 2);
				$ok = hash_equals(sha3_512($salt.$password, true), base64_decode($hash));
			} else {
				// Version 1: SHA3-512 without salt
				$ok = hash_equals(sha3_512($password, true), base64_decode($passwordData));
			}
			if ($ok) return true;
		}

		return false;
	}

	/**
	 * @return bool
	 * @throws OIDplusException
	 */
	public function isAdminLoggedIn(): bool {
		if ($this->forceAllLoggedOut()) {
			return false;
		} else {
			$acs = $this->getAuthContentStore();
			if (is_null($acs)) return false;
			return $acs->isAdminLoggedIn();
		}
	}

	/**
	 * "High level" function including logging and checking for valid JWT alternations
	 * @param bool $remember_me
	 * @param string $origin
	 * @return void
	 * @throws OIDplusException
	 */
	public function adminLoginEx(bool $remember_me, string $origin='') {
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

	/**
	 * @return void
	 * @throws OIDplusException
	 */
	public function adminLogoutEx() {
		$loginfo = '';

		$acs = $this->getAuthContentStore();
		if (is_null($acs)) return;
		$acs->adminLogoutEx($loginfo);

		if ($this->raNumLoggedIn() == 0) {
			// Nobody here anymore. Destroy the cookie to make GDPR people happy
			$acs->destroySession();
		} else {
			// Get a new token for the remaining users
			$acs->activate();
		}

		OIDplus::logger()->log("[OK]A!", "Admin logged out ($loginfo)");
	}

	// Authentication keys for validating arguments (e.g. sent by mail)

	/**
	 * @param string $data
	 * @return string
	 * @throws OIDplusException
	 */
	public function makeAuthKey(string $data): string {
		return sha3_512_hmac($data, 'authkey:'.OIDplus::baseConfig()->getValue('SERVER_SECRET'), false);
	}

	/**
	 * @param string $data
	 * @param string $auth_key
	 * @return bool
	 * @throws OIDplusException
	 */
	public function validateAuthKey(string $data, string $auth_key): bool {
		return hash_equals($this->makeAuthKey($data), $auth_key);
	}

	// "Veto" functions to force logout state

	/**
	 * @return bool
	 */
	protected function forceAllLoggedOut(): bool {
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

	/**
	 * @return void
	 */
	public function enableCSRF() {
		$this->enable_csrf = true;
	}

	/**
	 * @return void
	 */
	public function disableCSRF() {
		$this->enable_csrf = false;
	}

	/**
	 * @return string
	 * @throws \Random\RandomException
	 */
	public function genCSRFToken(): string {
		return random_bytes_ex(64, false, false);
	}

	/**
	 * @return void
	 * @throws OIDplusException
	 */
	public function checkCSRF() {
		if (!$this->enable_csrf) return;

		$request_token = $_REQUEST['csrf_token'] ?? '';
		$cookie_token = $_COOKIE['csrf_token'] ?? '';

		if (empty($request_token) || empty($cookie_token) || ($request_token !== $cookie_token)) {
			if (OIDplus::baseConfig()->getValue('DEBUG')) {
				throw new OIDplusException(_L('Missing or wrong CSRF Token: Request %1 vs Cookie %2',
					isset($_REQUEST['csrf_token']) ? '"'.$_REQUEST['csrf_token'].'"' : 'NULL',
					$_COOKIE['csrf_token'] ?? 'NULL'
				));
			} else {
				throw new OIDplusException(_L('Missing or wrong "CSRF Token". To fix the issue, try clearing your browser cache and reload the page. If you visited the page via HTTPS before, try HTTPS in case you are currently connected via HTTP.'));
			}
		}
	}

	// Generate RA passwords

	/**
	 * @param string $password
	 * @return OIDplusRAAuthInfo
	 * @throws OIDplusException
	 */
	public function raGeneratePassword(string $password): OIDplusRAAuthInfo {
		$plugin = OIDplus::getDefaultRaAuthPlugin(true);
		return $plugin->generate($this->raPepperProcessing($password));
	}

	// Generate admin password

	/* Nothing here; the admin password will be generated in setup_base.js , purely in the web-browser */

}
