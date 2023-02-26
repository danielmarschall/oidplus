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

class OIDplusAuthContentStoreJWT extends OIDplusAuthContentStoreDummy {

	const COOKIE_NAME = 'OIDPLUS_AUTH_JWT';

	const JWT_GENERATOR_AJAX   = 0; // "Automated AJAX" plugin
	const JWT_GENERATOR_LOGIN  = 1; // "Remember me" login method
	const JWT_GENERATOR_MANUAL = 2; // "Manually crafted" JWT tokens

	private static function jwtGetBlacklistConfigKey($gen, $sub) {
		// Note: Needs to be <= 50 characters!
		return 'jwt_blacklist_gen('.$gen.')_sub('.trim(base64_encode(md5($sub,true)),'=').')';
	}

	public static function jwtBlacklist($gen, $sub) {
		$cfg = self::jwtGetBlacklistConfigKey($gen, $sub);
		$bl_time = time()-1;

		$gen_desc = 'Unknown';
		if ($gen === self::JWT_GENERATOR_AJAX)   $gen_desc = 'Automated AJAX calls';
		if ($gen === self::JWT_GENERATOR_LOGIN)  $gen_desc = 'Login ("Remember me")';
		if ($gen === self::JWT_GENERATOR_MANUAL) $gen_desc = 'Manually created';

		OIDplus::config()->prepareConfigKey($cfg, 'Revoke timestamp of all JWT tokens for $sub with generator $gen ($gen_desc)', $bl_time, OIDplusConfig::PROTECTION_HIDDEN, function($value) {});
		OIDplus::config()->setValue($cfg, $bl_time);
	}

	public static function jwtGetBlacklistTime($gen, $sub) {
		$cfg = self::jwtGetBlacklistConfigKey($gen, $sub);
		return OIDplus::config()->getValue($cfg,0);
	}

	private static function jwtSecurityCheck($contentProvider) {
		// Check if the token is intended for us
		if ($contentProvider->getValue('aud','') !== OIDplus::getEditionInfo()['jwtaud']) {
			throw new OIDplusException(_L('Token has wrong audience'));
		}
		$gen = $contentProvider->getValue('oidplus_generator', -1);

		$has_admin = $contentProvider->isAdminLoggedIn();
		$has_ra = $contentProvider->raNumLoggedIn() > 0;

		// Check if the token generator is allowed
		if ($gen === self::JWT_GENERATOR_AJAX) {
			if (($has_admin) && !OIDplus::baseConfig()->getValue('JWT_ALLOW_AJAX_ADMIN', true)) {
				// Generator: plugins/viathinksoft/adminPages/910_automated_ajax_calls/OIDplusPageAdminAutomatedAJAXCalls.class.php
				throw new OIDplusException(_L('The administrator has disabled this feature. (Base configuration setting %1).','JWT_ALLOW_AJAX_ADMIN'));
			}
			if (($has_ra) && !OIDplus::baseConfig()->getValue('JWT_ALLOW_AJAX_USER', true)) {
				// Generator: plugins/viathinksoft/raPages/910_automated_ajax_calls/OIDplusPageRaAutomatedAJAXCalls.class.php
				throw new OIDplusException(_L('The administrator has disabled this feature. (Base configuration setting %1).','JWT_ALLOW_AJAX_USER'));
			}
		}
		else if ($gen === self::JWT_GENERATOR_LOGIN) {
			// Used for feature "Remember me" (use JWT token in a cookie as alternative to PHP session):
			// - No PHP session will be used
			// - Session will not be bound to IP address (therefore, you can switch between mobile/WiFi for example)
			// - No server-side session needed
			if (($has_admin) && !OIDplus::baseConfig()->getValue('JWT_ALLOW_LOGIN_ADMIN', true)) {
				throw new OIDplusException(_L('The administrator has disabled this feature. (Base configuration setting %1).','JWT_ALLOW_LOGIN_ADMIN'));
			}
			if (($has_ra) && !OIDplus::baseConfig()->getValue('JWT_ALLOW_LOGIN_USER', true)) {
				throw new OIDplusException(_L('The administrator has disabled this feature. (Base configuration setting %1).','JWT_ALLOW_LOGIN_USER'));
			}
		}
		else if ($gen === self::JWT_GENERATOR_MANUAL) {
			// Generator 2 are "hand-crafted" tokens
			if (!OIDplus::baseConfig()->getValue('JWT_ALLOW_MANUAL', false)) {
				throw new OIDplusException(_L('The administrator has disabled this feature. (Base configuration setting %1).','JWT_ALLOW_MANUAL'));
			}
		} else {
			throw new OIDplusException(_L('Token generator %1 not recognized',$gen));
		}

		// Make sure that the IAT (issued at time) isn't in a blacklisted timeframe
		// When an user believes that a token was compromised, then they can blacklist the tokens identified by their "iat" ("Issued at") property
		// When a user logs out of a "remember me" session, the JWT token will be blacklisted as well
		// Small side effect: All "remember me" sessions of that user will be revoked then
		$sublist = $contentProvider->loggedInRaList();
		foreach ($sublist as &$sub) {
			$sub = $sub->raEmail();
		}
		if ($has_admin) $sublist[] = 'admin';
		foreach ($sublist as $sub) {
			$bl_time = self::jwtGetBlacklistTime($gen, $sub);
			$iat = $contentProvider->getValue('iat',0);
			if ($iat <= $bl_time) {
				throw new OIDplusException(_L('The JWT token was blacklisted on %1. Please generate a new one',date('d F Y, H:i:s',$bl_time)));
			}
		}

		// Optional feature: Limit the JWT to a specific IP address
		// Currently not used in OIDplus
		$ip = $contentProvider->getValue('ip','');
		if ($ip !== '') {
			if (isset($_SERVER['REMOTE_ADDR']) && ($ip !== $_SERVER['REMOTE_ADDR'])) {
				throw new OIDplusException(_L('Your IP address is not allowed to use this token'));
			}
		}

		// Checks which are dependent on the generator
		if ($gen === self::JWT_GENERATOR_LOGIN) {
			if (!isset($_COOKIE[self::COOKIE_NAME])) {
				throw new OIDplusException(_L('This kind of JWT token can only be used with the %1 request type','COOKIE'));
			}
		}
		if ($gen === self::JWT_GENERATOR_AJAX) {
			if (!isset($_GET[self::COOKIE_NAME]) && !isset($_POST[self::COOKIE_NAME])) {
				throw new OIDplusException(_L('This kind of JWT token can only be used with the %1 request type','GET/POST'));
			}
			if (isset($_SERVER['SCRIPT_FILENAME']) && (strtolower(basename($_SERVER['SCRIPT_FILENAME'])) !== 'ajax.php')) {
				throw new OIDplusException(_L('This kind of JWT token can only be used in ajax.php'));
			}
		}
	}

	// Override abstract functions

	public function activate() {
		// Send cookie at the end of the HTTP request, in case there are multiple activate() calls
		OIDplus::register_shutdown_function(array($this,'activateNow'));
	}

	public function activateNow() {
		$token = $this->getJWTToken();
		$exp = $this->getValue('exp',0);
		OIDplus::cookieUtils()->setcookie(self::COOKIE_NAME, $token, $exp, false);
	}

	public function destroySession() {
		OIDplus::cookieUtils()->unsetcookie(self::COOKIE_NAME);
	}

	public function raLogout($email) {
		$gen = $this->getValue('oidplus_generator', -1);
		if ($gen >= 0) self::jwtBlacklist($gen, $email);
		parent::raLogout($email);
	}

	public function raLogoutEx($email, &$loginfo) {
		$this->raLogout($email);
		$loginfo = 'from JWT session';
	}

	public function adminLogout() {
		$gen = $this->getValue('oidplus_generator', -1);
		if ($gen >= 0) self::jwtBlacklist($gen, 'admin');
		parent::adminLogout();
	}

	public function adminLogoutEx(&$loginfo) {
		$this->adminLogout();
		$loginfo = 'from JWT session';
	}

	private static $contentProvider = null;
	public static function getActiveProvider() {
		if (!self::$contentProvider) {
			$jwt = '';
			if (isset($_COOKIE[self::COOKIE_NAME])) $jwt = $_COOKIE[self::COOKIE_NAME];
			if (isset($_POST[self::COOKIE_NAME]))   $jwt = $_POST[self::COOKIE_NAME];
			if (isset($_GET[self::COOKIE_NAME]))    $jwt = $_GET[self::COOKIE_NAME];

			if (!empty($jwt)) {
				$tmp = new OIDplusAuthContentStoreJWT();

				try {
					// Decode the JWT. In this step, the signature as well as EXP/NBF times will be checked
					$tmp->loadJWT($jwt);

					// Do various checks if the token is allowed and not blacklisted
					self::jwtSecurityCheck($tmp);
				} catch (\Exception $e) {
					if (isset($_GET[self::COOKIE_NAME]) || isset($_POST[self::COOKIE_NAME])) {
						// Most likely an AJAX request. We can throw an Exception
						throw new OIDplusException(_L('The JWT token was rejected: %1',$e->getMessage()));
					} else {
						// Most likely an expired Cookie/Login session. We must not throw an Exception, otherwise we will break jsTree
						OIDplus::cookieUtils()->unsetcookie(self::COOKIE_NAME);
						return null;
					}
				}

				self::$contentProvider = $tmp;
			}
		}

		return self::$contentProvider;
	}

	public function raLoginEx($email, &$loginfo) {
		if (is_null(self::getActiveProvider())) {
			$this->raLogin($email);
			$loginfo = 'into new JWT session';
			self::$contentProvider = $this;
		} else {
			$gen = $this->getValue('oidplus_generator',-1);
			switch ($gen) {
				case OIDplusAuthContentStoreJWT::JWT_GENERATOR_AJAX :
				case OIDplusAuthContentStoreJWT::JWT_GENERATOR_MANUAL :
					throw new OIDplusException(_L('This kind of JWT token cannot be altered. Therefore you cannot do this action.'));
				case OIDplusAuthContentStoreJWT::JWT_GENERATOR_LOGIN :
					if (!OIDplus::baseConfig()->getValue('JWT_ALLOW_LOGIN_USER', true)) {
						throw new OIDplusException(_L('You cannot add this login credential to your existing "remember me" session. You need to log-out first.'));
					}
					break;
				default:
					assert(false); // This cannot happen because jwtSecurityCheck will check for unknown generators
					break;
			}
			$this->raLogin($email);
			$loginfo = 'into existing JWT session';
		}
	}

	public function adminLoginEx(&$loginfo) {
		if (is_null(self::getActiveProvider())) {
			$this->adminLogin();
			$loginfo = 'into new JWT session';
			self::$contentProvider = $this;
		} else {
			$gen = $this->getValue('oidplus_generator',-1);
			switch ($gen) {
				case OIDplusAuthContentStoreJWT::JWT_GENERATOR_AJAX :
				case OIDplusAuthContentStoreJWT::JWT_GENERATOR_MANUAL :
					throw new OIDplusException(_L('This kind of JWT token cannot be altered. Therefore you cannot do this action.'));
				case OIDplusAuthContentStoreJWT::JWT_GENERATOR_LOGIN :
					if (!OIDplus::baseConfig()->getValue('JWT_ALLOW_LOGIN_ADMIN', true)) {
						throw new OIDplusException(_L('You cannot add this login credential to your existing "remember me" session. You need to log-out first.'));
					}
					break;
				default:
					assert(false); // This cannot happen because jwtSecurityCheck will check for unknown generators
					break;
			}
			$this->adminLogin();
			$loginfo = 'into existing JWT session';
		}
	}

	// Individual functions

	public function loadJWT($jwt) {
		\Firebase\JWT\JWT::$leeway = 60; // leeway in seconds
		if (OIDplus::getPkiStatus()) {
			$pubKey = OIDplus::getSystemPublicKey();
			$k = new \Firebase\JWT\Key($pubKey, 'RS256'); // RSA+SHA256 ist hardcoded in getPkiStatus() generation
			$this->content = (array) \Firebase\JWT\JWT::decode($jwt, $k);
		} else {
			$key = OIDplus::baseConfig()->getValue('SERVER_SECRET', '').'/OIDplusAuthContentStoreJWT';
			$key = hash_pbkdf2('sha512', $key, '', 10000, 32/*256bit*/, false);
			$k = new \Firebase\JWT\Key($key, 'HS512'); // HMAC+SHA512 is hardcoded here
			$this->content = (array) \Firebase\JWT\JWT::decode($jwt, $k);
		}
	}

	public function getJWTToken() {
		$payload = $this->content;
		$payload["iss"] = OIDplus::getEditionInfo()['jwtaud'];
		$payload["aud"] = OIDplus::getEditionInfo()['jwtaud'];
		$payload["jti"] = gen_uuid();
		$payload["iat"] = time();

		if (OIDplus::getPkiStatus()) {
			$privKey = OIDplus::getSystemPrivateKey();
			return \Firebase\JWT\JWT::encode($payload, $privKey, 'RS256'); // RSA+SHA256 ist hardcoded in getPkiStatus() generation
		} else {
			$key = OIDplus::baseConfig()->getValue('SERVER_SECRET', '').'/OIDplusAuthContentStoreJWT';
			$key = hash_pbkdf2('sha512', $key, '', 10000, 32/*256bit*/, false);
			return \Firebase\JWT\JWT::encode($payload, $key, 'HS512'); // HMAC+SHA512 is hardcoded here
		}
	}

}
