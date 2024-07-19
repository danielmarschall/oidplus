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

/**
 * Auth content store for JWT tokens (web browser login cookies, Automated AJAX argument, or REST Authorization Bearer)
 */
class OIDplusAuthContentStoreJWT implements OIDplusGetterSetterInterface {

	/**
	 * Cookie name for the JWT auth token
	 */
	const COOKIE_NAME = 'OIDPLUS_AUTH_JWT';

	/**
	 * Token generator; must be one of OIDplusAuthContentStoreJWT::JWT_GENERATOR_*
	 */
	const CLAIM_GENERATOR = 'urn:oid:1.3.6.1.4.1.37476.2.5.2.7.1';

	/**
	 * List of logged-in users
	 */
	const CLAIM_LOGIN_LIST = 'urn:oid:1.3.6.1.4.1.37476.2.5.2.7.2';

	/**
	 * SSH = Server Secret Hash
	 */
	const CLAIM_SSH = 'urn:oid:1.3.6.1.4.1.37476.2.5.2.7.3';

	/**
	 * IP-Adress limit
	 */
	const CLAIM_LIMIT_IP = 'urn:oid:1.3.6.1.4.1.37476.2.5.2.7.4';

	/**
	 * Trace JTI, IP, and UserAgent
	 */
	const CLAIM_TRACE = 'urn:oid:1.3.6.1.4.1.37476.2.5.2.7.5';

	/**
	 * "Automated AJAX" plugin
	 */
	const JWT_GENERATOR_AJAX   = 10;
	/**
	 * "REST API" plugin
	 */
	const JWT_GENERATOR_REST   = 20;
	/**
	 * Web browser login method
	 */
	const JWT_GENERATOR_LOGIN  = 40;
	/**
	 * "Manually crafted" JWT tokens
	 */
	const JWT_GENERATOR_MANUAL = 80;

	/**
	 * @return string
	 */
	public static function getAudIss(): string {
		$oid = OIDplus::getSystemId(true);
		if ($oid !== false) return 'urn:oid:'.$oid;
		$url = OIDplus::webpath(null, OIDplus::PATH_ABSOLUTE_CANONICAL);
		if ($url) return $url;
		return 'http://oidplus.com/';
	}

	/**
	 * @param int $gen OIDplusAuthContentStoreJWT::JWT_GENERATOR_...
	 * @param string $sub
	 * @return string
	 */
	private static function jwtGetBlacklistConfigKey(int $gen, string $sub): string {
		// Note: Needs to be <= 50 characters! If $gen is 2 chars, then the config key is 49 chars long
		return 'jwt_blacklist_gen('.$gen.')_sub('.trim(base64_encode(md5($sub,true)),'=').')';
	}

	/**
	 * @param int $gen
	 */
	private static function generatorName($gen) {
		// Note: The strings are not translated, because the name is used in config keys or logs
		if ($gen === self::JWT_GENERATOR_AJAX)   return 'Automated AJAX calls';
		if ($gen === self::JWT_GENERATOR_REST)   return 'REST API';
		if ($gen === self::JWT_GENERATOR_LOGIN)  return 'Browser login';
		if ($gen === self::JWT_GENERATOR_MANUAL) return 'Manually created';
		return 'Unknown generator';
	}

	/**
	 * @param int $gen OIDplusAuthContentStoreJWT::JWT_GENERATOR_...
	 * @param string $sub
	 * @return void
	 * @throws OIDplusException
	 */
	public static function jwtBlacklist(int $gen, string $sub) {
		$cfg = self::jwtGetBlacklistConfigKey($gen, $sub);
		$bl_time = time()-1;

		$gen_desc = self::generatorName($gen);

		OIDplus::config()->prepareConfigKey($cfg, "Revoke timestamp of all JWT tokens for $sub with generator $gen ($gen_desc)", "$bl_time", OIDplusConfig::PROTECTION_HIDDEN, function($value) {});
		OIDplus::config()->setValue($cfg, $bl_time);
	}

	/**
	 * @param int $gen OIDplusAuthContentStoreJWT::JWT_GENERATOR_...
	 * @param string $sub E-Mail-Adress of RA or 'admin'
	 * @return int
	 * @throws OIDplusException
	 */
	public static function jwtGetBlacklistTime(int $gen, string $sub): int {
		$cfg = self::jwtGetBlacklistConfigKey($gen, $sub);
		return (int)OIDplus::config()->getValue($cfg,0);
	}

	/**
	 * We include a hash of the server-secret here (ssh = server-secret-hash), so that the JWT can be invalidated by changing the server-secret
	 * @return string
	 * @throws OIDplusException
	 */
	private static function getSsh(): string {
		$hexadecimal_string = OIDplus::authUtils()->makeSecret(['bb1aebd6-fe6a-11ed-a553-3c4a92df8582']);
		return base64_encode(pack('H*',$hexadecimal_string));
	}

	/**
	 * Do various checks if the token is allowed and not blacklisted
	 * @param OIDplusAuthContentStoreJWT $contentProvider
	 * @param int|null $validGenerators Bitmask which generators to allow (null = allow all)
	 * @return void
	 * @throws OIDplusException
	 */
	private static function jwtSecurityCheck(OIDplusAuthContentStoreJWT $contentProvider, int $validGenerators=null) {
		// Check if the token is intended for us
		// Note 'aud' is mandatory for OIDplus, so we do not check for exists()
		if ($contentProvider->getValue('aud','') !== $contentProvider->getAudIss()) {
			throw new OIDplusException(_L('Token has wrong audience: Given %1 but expected %2.', $contentProvider->getValue('aud',''), $contentProvider->getAudIss()));
		}

		// Note CLAIM_SSH is mandatory for OIDplus, so we do not check for exists()
		if ($contentProvider->getValue(self::CLAIM_SSH, '') !== self::getSsh()) {
			throw new OIDplusException(_L('"Server Secret" was changed; therefore the JWT is not valid anymore'));
		}

		// Note CLAIM_GENERATOR is mandatory for OIDplus, so we do not check for exists()
		$gen = $contentProvider->getValue(self::CLAIM_GENERATOR, -1);

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
		else if ($gen === self::JWT_GENERATOR_REST) {
			if (($has_admin) && !OIDplus::baseConfig()->getValue('JWT_ALLOW_REST_ADMIN', true)) {
				// Generator: plugins/viathinksoft/adminPages/911_rest_api/OIDplusPageAdminRestApi.class.php
				throw new OIDplusException(_L('The administrator has disabled this feature. (Base configuration setting %1).','JWT_ALLOW_REST_ADMIN'));
			}
			if (($has_ra) && !OIDplus::baseConfig()->getValue('JWT_ALLOW_REST_USER', true)) {
				// Generator: plugins/viathinksoft/raPages/911_rest_api/OIDplusPageRaRestApi.class.php
				throw new OIDplusException(_L('The administrator has disabled this feature. (Base configuration setting %1).','JWT_ALLOW_REST_USER'));
			}
		}
		else if ($gen === self::JWT_GENERATOR_LOGIN) {
			// Used for web browser login (use JWT token in a cookie as alternative to PHP session):
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
			// Generator: "hand-crafted" tokens
			if (($has_admin) && !OIDplus::baseConfig()->getValue('JWT_ALLOW_MANUAL_ADMIN', false)) {
				throw new OIDplusException(_L('The administrator has disabled this feature. (Base configuration setting %1).','JWT_ALLOW_MANUAL_ADMIN'));
			}
			if (($has_ra) && !OIDplus::baseConfig()->getValue('JWT_ALLOW_MANUAL_USER', false)) {
				throw new OIDplusException(_L('The administrator has disabled this feature. (Base configuration setting %1).','JWT_ALLOW_MANUAL_USER'));
			}
		} else {
			throw new OIDplusException(_L('Token generator %1 not recognized',$gen));
		}

		// Every token must have and issued timestamp
		$iat = $contentProvider->getValue('iat',null);
		if (is_null($iat)) {
			throw new OIDplusException(_L('The claim "%1" of the JWT token is missing or invalid','iat'));
		}

		// Verify that IAT has a valid value
		// Note: This check is already done in Firebase\JWT. However, we do it again, just to be 100% sure.
		if (($iat-120/*leeway 2min*/) > time()) {
			// Token was created in the future. Something is wrong!
			throw new OIDplusException(_L('JWT Token cannot be verified because the server time is wrong'));
		}

		// Check if token is not yet valid
		// Note: This check is already done in Firebase\JWT. However, we do it again, just to be 100% sure.
		$nbf = $contentProvider->getValue('nbf',null);
		if (!is_null($nbf)) {
			if (time() < $nbf-120/*leeway 2min*/) {
				throw new OIDplusException(_L('Token not valid before %1',date('d F Y, H:i:s',$nbf)));
			}
		}

		// Check if token has expired
		// Note: This check is already done in Firebase\JWT. However, we do it again, just to be 100% sure.
		$exp = $contentProvider->getValue('exp',null);
		if (!is_null($exp)) {
			if (time() > $exp+120/*leeway 2min*/) {
				throw new OIDplusException(_L('Token has expired on %1',date('d F Y, H:i:s',$exp)));
			}
		}

		// Make sure that the IAT (issued at time) isn't in a blacklisted timeframe
		// When an user believes that a token was compromised, then they can blacklist the tokens identified by their "iat" ("Issued at") property
		// When a user logs out of a web browser session, the JWT token will be blacklisted as well
		// Small side effect: All web browser login sessions of that user will be revoked then
		$sublist = $contentProvider->loggedInRaList();
		$usernames = array();
		foreach ($sublist as $sub) {
			$usernames[] = $sub->raEmail();
		}
		if ($has_admin) $usernames[] = 'admin';
		foreach ($usernames as $username) {
			$bl_time = self::jwtGetBlacklistTime($gen, $username);
			if ($iat <= $bl_time) {
				// Token is blacklisted (it was created before the last blacklist time)
				throw new OIDplusException(_L('The JWT token was blacklisted on %1. Please generate a new one',date('d F Y, H:i:s',$bl_time)));
			}
		}

		// Optional feature: Limit the JWT to a specific IP address (used if JWT_FIXED_IP_USER or JWT_FIXED_IP_ADMIN is true)
		$ip = $contentProvider->getValue(self::CLAIM_LIMIT_IP, null);
		if (!is_null($ip)) {
			if ($ip !== OIDplus::getClientIpAddress()) {
				throw new OIDplusException(_L('Your IP address is not allowed to use this token'));
			}
		}

		// Checks if JWT are dependent on the generator
		if (!is_null($validGenerators)) {
			if (($gen & $validGenerators) === 0) {
				throw new OIDplusException(_L('This kind of JWT token (%1) cannot be used in this request type', self::generatorName($gen)));
			}
		}
	}

	// Override abstract functions

	/**
	 * @var array
	 */
	protected $content = array();

	/**
	 * @param string $name
	 * @param mixed|null $default
	 * @return mixed|null
	 */
	public function getValue(string $name, $default = NULL) {
		return $this->content[$name] ?? $default;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function setValue(string $name, $value) {
		$this->content[$name] = $value;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function exists(string $name): bool {
		return isset($this->content[$name]);
	}

	/**
	 * @param string $name
	 * @return void
	 */
	public function delete(string $name) {
		unset($this->content[$name]);
	}

	/**
	 * @return void
	 */
	public function activate() {
		// Send cookie at the end of the HTTP request, in case there are multiple activate() calls
		OIDplus::register_shutdown_function(array($this,'activateNow'));
	}

	/**
	 * @return void
	 * @throws OIDplusException
	 */
	public function activateNow() {
		$token = $this->getJWTToken();
		$exp = $this->getValue('exp',0);
		OIDplus::cookieUtils()->setcookie(self::COOKIE_NAME, $token, $exp, false);
	}

	/**
	 * @return void
	 * @throws OIDplusException
	 */
	public function destroySession() {
		OIDplus::cookieUtils()->unsetcookie(self::COOKIE_NAME);
	}

	/**
	 * @param string[] $ra RAs
	 * @param bool $admin Admin yes or no?
	 * @param int $gen Generator
	 * @param bool $limit_ip Limit IP to the current IP address?
	 * @param int $ttl How many seconds valid?
	 * @return string JWT token
	 */
	public static function craftJWT(array $ra, bool $admin, int $gen, bool $limit_ip=false, int $ttl=10*365*24*60*60): string {
		$authSimulation = new OIDplusAuthContentStoreJWT();
		foreach ($ra as $username) {
			if ($username == 'admin') continue;
			$authSimulation->raLogin($username);
		}
		if ($admin) $authSimulation->adminLogin();
		$authSimulation->setValue(OIDplusAuthContentStoreJWT::CLAIM_GENERATOR, $gen);
		$authSimulation->setValue('exp', time()+$ttl);
		if ($limit_ip) {
			$cur_ip = OIDplus::getClientIpAddress();
			if ($cur_ip !== false) {
				$authSimulation->setValue(self::CLAIM_LIMIT_IP, $cur_ip);
			}
		}
		return $authSimulation->getJWTToken();
	}

	// RA authentication functions (low-level)

	/**
	 * @param string $email
	 * @return void
	 */
	public function raLogin(string $email) {
		if ($email == 'admin') return;

		$list = $this->getValue(self::CLAIM_LOGIN_LIST, null);
		if (is_null($list)) $list = [];
		if (!in_array($email, $list)) $list[] = $email;
		$this->setValue(self::CLAIM_LOGIN_LIST, $list);
	}

	/**
	 * @return int
	 */
	public function raNumLoggedIn(): int {
		return count($this->loggedInRaList());
	}

	/**
	 * @return OIDplusRA[]
	 */
	public function loggedInRaList(): array {
		$list = $this->getValue(self::CLAIM_LOGIN_LIST, null);
		if (is_null($list)) $list = [];

		$res = array();
		foreach (array_unique($list) as $username) {
			if ($username == '') continue; // should not happen
			if ($username == 'admin') continue;
			$res[] = new OIDplusRA($username);
		}
		return $res;
	}

	/**
	 * @param string $email
	 * @return bool
	 */
	public function isRaLoggedIn(string $email): bool {
		foreach ($this->loggedInRaList() as $ra) {
			if ($email == $ra->raEmail()) return true;
		}
		return false;
	}

	/**
	 * @param string $email
	 * @return void
	 * @throws OIDplusException
	 */
	public function raLogout(string $email) {
		if ($email == 'admin') return;

		$gen = $this->getValue(self::CLAIM_GENERATOR, -1);
		if ($gen >= 0) self::jwtBlacklist($gen, $email);

		$list = $this->getValue(self::CLAIM_LOGIN_LIST, null);
		if (is_null($list)) $list = [];
		$key = array_search($email, $list);
		if ($key !== false) unset($list[$key]);
		$this->setValue(self::CLAIM_LOGIN_LIST, $list);
	}

	/**
	 * @param string $email
	 * @param string $loginfo
	 * @return void
	 * @throws OIDplusException
	 */
	public function raLogoutEx(string $email, string &$loginfo) {
		$this->raLogout($email);
		$loginfo = 'from JWT session';
	}

	// Admin authentication functions (low-level)

	/**
	 * @return void
	 */
	public function adminLogin() {
		$list = $this->getValue(self::CLAIM_LOGIN_LIST, null);
		if (is_null($list)) $list = [];
		if (!in_array('admin', $list)) $list[] = 'admin';
		$this->setValue(self::CLAIM_LOGIN_LIST, $list);
	}

	/**
	 * @return bool
	 */
	public function isAdminLoggedIn(): bool {
		$list = $this->getValue(self::CLAIM_LOGIN_LIST, null);
		if (is_null($list)) $list = [];
		return in_array('admin', $list);
	}

	/**
	 * @return void
	 * @throws OIDplusException
	 */
	public function adminLogout() {
		$gen = $this->getValue(self::CLAIM_GENERATOR, -1);
		if ($gen >= 0) self::jwtBlacklist($gen, 'admin');

		$list = $this->getValue(self::CLAIM_LOGIN_LIST, null);
		if (is_null($list)) $list = [];
		$key = array_search('admin', $list);
		if ($key !== false) unset($list[$key]);
		$this->setValue(self::CLAIM_LOGIN_LIST, $list);
	}

	/**
	 * @param string $loginfo
	 * @return void
	 * @throws OIDplusException
	 */
	public function adminLogoutEx(string &$loginfo) {
		$this->adminLogout();
		$loginfo = 'from JWT session';
	}

	private static $contentProvider = null;

	/**
	 * @return OIDplusAuthContentStoreJWT|null
	 * @throws OIDplusException
	 */
	public static function getActiveProvider()/*: ?OIDplusAuthContentStoreJWT*/ {
		if (!self::$contentProvider) {

			$tmp = null;
			$silent_error = false;

			try {

				if (isset($_SERVER['REQUEST_URI'])) {
					$rel_url = substr($_SERVER['REQUEST_URI'], strlen(OIDplus::webpath(null, OIDplus::PATH_RELATIVE_TO_ROOT)));
					$only_use_bearer = str_starts_with($rel_url, 'rest/'); // <== TODO: Find a way how to move this into the plugin, since REST does not belong to the core.
				} else {
					$only_use_bearer = false;
				}

				if ($only_use_bearer) {

					// REST may only use Bearer Authorization
					$bearer = getBearerToken();
					if (!is_null($bearer)) {
						$silent_error = false;
						$tmp = new OIDplusAuthContentStoreJWT();
						$tmp->loadJWT($bearer);
						self::jwtSecurityCheck($tmp, self::JWT_GENERATOR_REST | self::JWT_GENERATOR_MANUAL);
					}

				} else {

					// A web-visitor (HTML and AJAX, but not REST) can use a JWT Cookie
					if (isset($_COOKIE[self::COOKIE_NAME])) {
						$silent_error = true;
						$tmp = new OIDplusAuthContentStoreJWT();
						$tmp->loadJWT($_COOKIE[self::COOKIE_NAME]);
						self::jwtSecurityCheck($tmp, self::JWT_GENERATOR_LOGIN | self::JWT_GENERATOR_MANUAL);
					}

					// AJAX may additionally use GET/POST automated AJAX (in addition to the normal web browser login Cookie)
					if (isset($_SERVER['SCRIPT_FILENAME']) && (strtolower(basename($_SERVER['SCRIPT_FILENAME'])) !== 'ajax.php')) {
						if (isset($_POST[self::COOKIE_NAME])) {
							$silent_error = false;
							$tmp = new OIDplusAuthContentStoreJWT();
							$tmp->loadJWT($_POST[self::COOKIE_NAME]);
							self::jwtSecurityCheck($tmp, self::JWT_GENERATOR_AJAX | self::JWT_GENERATOR_MANUAL);
						}
						if (isset($_GET[self::COOKIE_NAME])) {
							$silent_error = false;
							$tmp = new OIDplusAuthContentStoreJWT();
							$tmp->loadJWT($_GET[self::COOKIE_NAME]);
							self::jwtSecurityCheck($tmp, self::JWT_GENERATOR_AJAX | self::JWT_GENERATOR_MANUAL);
						}
					}

				}

			} catch (\Exception $e) {
				if (!$silent_error || OIDplus::baseConfig()->getValue('DEBUG',false)) {
					// Most likely an AJAX request. We can throw an Exception
					if (OIDplus::baseConfig()->getValue('DEBUG',false)) {
						OIDplus::cookieUtils()->unsetcookie(self::COOKIE_NAME);
					}
					throw new OIDplusException(_L('The JWT token was rejected: %1',$e->getMessage()));
				} else {
					// Most likely an expired Cookie/Login session. We must not throw an Exception, otherwise we will break jsTree
					OIDplus::cookieUtils()->unsetcookie(self::COOKIE_NAME);
					return null;
				}
			}

			self::$contentProvider = $tmp;
		}

		return self::$contentProvider;
	}

	/**
	 * @param string $email
	 * @param string $loginfo
	 * @return void
	 * @throws OIDplusException
	 */
	public function raLoginEx(string $email, string &$loginfo) {
		if (is_null(self::getActiveProvider())) {
			$loginfo = 'into new JWT session';
			self::$contentProvider = $this;
		} else {
			$gen = $this->getValue(self::CLAIM_GENERATOR,-1);
			switch ($gen) {
				case OIDplusAuthContentStoreJWT::JWT_GENERATOR_AJAX :
				case OIDplusAuthContentStoreJWT::JWT_GENERATOR_REST :
				case OIDplusAuthContentStoreJWT::JWT_GENERATOR_MANUAL :
					throw new OIDplusException(_L('This kind of JWT token cannot be altered. Therefore you cannot do this action.'));
				case OIDplusAuthContentStoreJWT::JWT_GENERATOR_LOGIN :
					if (!OIDplus::baseConfig()->getValue('JWT_ALLOW_LOGIN_USER', true)) {
						throw new OIDplusException(_L('The administrator has disabled this feature. (Base configuration setting %1).','JWT_ALLOW_LOGIN_USER'));
					}
					break;
				default:
					assert(false); // This cannot happen because jwtSecurityCheck will check for unknown generators
					break;
			}
			$loginfo = 'into existing JWT session';
		}
		$this->raLogin($email);
		$ttl = OIDplus::baseConfig()->getValue('JWT_TTL_LOGIN_USER', 30*24*60*60);
		$this->setValue('exp', time()+$ttl); // JWT "exp" attribute
		if (OIDplus::baseConfig()->getValue('JWT_FIXED_IP_USER', false)) {
			$cur_ip = OIDplus::getClientIpAddress();
			if ($cur_ip !== false) {
				$this->setValue(self::CLAIM_LIMIT_IP, $cur_ip);
			}
		}
	}

	/**
	 * @param string $loginfo
	 * @return void
	 * @throws OIDplusException
	 */
	public function adminLoginEx(string &$loginfo) {
		if (is_null(self::getActiveProvider())) {
			$loginfo = 'into new JWT session';
			self::$contentProvider = $this;
		} else {
			$gen = $this->getValue(self::CLAIM_GENERATOR,-1);
			switch ($gen) {
				case OIDplusAuthContentStoreJWT::JWT_GENERATOR_AJAX :
				case OIDplusAuthContentStoreJWT::JWT_GENERATOR_REST :
				case OIDplusAuthContentStoreJWT::JWT_GENERATOR_MANUAL :
					throw new OIDplusException(_L('This kind of JWT token cannot be altered. Therefore you cannot do this action.'));
				case OIDplusAuthContentStoreJWT::JWT_GENERATOR_LOGIN :
					if (!OIDplus::baseConfig()->getValue('JWT_ALLOW_LOGIN_ADMIN', true)) {
						throw new OIDplusException(_L('The administrator has disabled this feature. (Base configuration setting %1).','JWT_ALLOW_LOGIN_ADMIN'));
					}
					break;
				default:
					assert(false); // This cannot happen because jwtSecurityCheck will check for unknown generators
					break;
			}
			$loginfo = 'into existing JWT session';
		}
		$this->adminLogin();
		$ttl = OIDplus::baseConfig()->getValue('JWT_TTL_LOGIN_ADMIN', 30*24*60*60);
		$this->setValue('exp', time()+$ttl); // JWT "exp" attribute
		if (OIDplus::baseConfig()->getValue('JWT_FIXED_IP_ADMIN', false)) {
			$cur_ip = OIDplus::getClientIpAddress();
			if ($cur_ip !== false) {
				$this->setValue(self::CLAIM_LIMIT_IP, $cur_ip);
			}
		}
	}

	// Individual functions

	/**
	 * Decode the JWT. In this step, the signature as well as EXP/NBF times will be checked
	 * @param string $jwt
	 * @return void
	 * @throws OIDplusException
	 */
	public function loadJWT(string $jwt) {
		\Firebase\JWT\JWT::$leeway = 60; // leeway in seconds
		$cls_content = null;
		if (OIDplus::getPkiStatus()) {
			$pubKey = OIDplus::getSystemPublicKey();
			$k = new \Firebase\JWT\Key($pubKey, 'RS256'); // RSA+SHA256 is hardcoded in getPkiStatus() generation
			$cls_content = \Firebase\JWT\JWT::decode($jwt, $k);
		} else {
			$key = OIDplus::authUtils()->makeSecret(['0be35e52-f4ef-11ed-b67e-3c4a92df8582']);
			$key = hash_pbkdf2('sha512', $key, '', 10000, 32/*256bit*/, false);
			$k = new \Firebase\JWT\Key($key, 'HS512'); // HMAC+SHA512 is hardcoded here
			$cls_content = \Firebase\JWT\JWT::decode($jwt, $k);
		}
		$this->content = json_decode(json_encode($cls_content), true); // convert stdClass to array
	}

	/**
	 * @return string
	 * @throws OIDplusException
	 */
	public function getJWTToken(): string {
		$payload = $this->content;
		$payload[self::CLAIM_SSH] = self::getSsh(); // SSH = Server Secret Hash
		// see also https://www.iana.org/assignments/jwt/jwt.xhtml#claims for some generic claims
		if (!isset($payload["iss"])) $payload["iss"] = $this->getAudIss();
		if (!isset($payload["aud"])) $payload["aud"] = $this->getAudIss();
		$payload["jti"] = gen_uuid(); // always set/renew it; therefore not checking isset()
		$payload["iat"] = time(); // always set/renew it; therefore not checking isset()
		if (!isset($payload["nbf"])) $payload["nbf"] = time();
		if (!isset($payload["exp"])) $payload["exp"] = time()+3600/*1h*/;

		$cur_ip = OIDplus::getClientIpAddress();
		if (!isset($payload[self::CLAIM_TRACE])) {
			// "Trace" can be used for later updates
			// For example, if the IP changes "too much" (different country, different AS, etc.)
			// Or revoke all tokens from a single login flow (sequence 1, 2, 3, ...)
			$payload[self::CLAIM_TRACE] = array();
			$payload[self::CLAIM_TRACE]['iat_1st'] = $payload["iat"];
			$payload[self::CLAIM_TRACE]['jti_1st'] = $payload["jti"];
			$payload[self::CLAIM_TRACE]['seq'] = 1;
			if ($cur_ip !== false) $payload[self::CLAIM_TRACE]['ip'] = $cur_ip;
			$payload[self::CLAIM_TRACE]['ip_1st'] = $payload[self::CLAIM_TRACE]['ip'];
			$payload[self::CLAIM_TRACE]['ua'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
			$payload[self::CLAIM_TRACE]['ua_1st'] = $payload[self::CLAIM_TRACE]['ua'];
		} else {
			assert(is_numeric($payload[self::CLAIM_TRACE]['seq']));
			$payload[self::CLAIM_TRACE]['seq']++;
			if ($cur_ip !== false) $payload[self::CLAIM_TRACE]['ip'] = $cur_ip;
			$payload[self::CLAIM_TRACE]['ua'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
		}

		uksort($payload, "strnatcmp"); // this is natsort on the key. Just to make the JWT look nicer.

		if (OIDplus::getPkiStatus()) {
			$privKey = OIDplus::getSystemPrivateKey();
			return \Firebase\JWT\JWT::encode($payload, $privKey, 'RS256'); // RSA+SHA256 is hardcoded in getPkiStatus() generation
		} else {
			$key = OIDplus::authUtils()->makeSecret(['0be35e52-f4ef-11ed-b67e-3c4a92df8582']);
			$key = hash_pbkdf2('sha512', $key, '', 10000, 32/*256bit*/, false);
			return \Firebase\JWT\JWT::encode($payload, $key, 'HS512'); // HMAC+SHA512 is hardcoded here
		}
	}

}
