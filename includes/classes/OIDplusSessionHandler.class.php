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

class OIDplusSessionHandler extends OIDplusBaseClass implements OIDplusGetterSetterInterface {

	/**
	 * @var string|null
	 */
	private $secret = '';

	/**
	 * @var int|null
	 */
	protected $sessionLifetime = 0;

	/**
	 * @throws OIDplusException
	 */
	public function __construct() {
		$this->sessionLifetime = OIDplus::baseConfig()->getValue('SESSION_LIFETIME', 30*60);
		$this->secret = OIDplus::baseConfig()->getValue('SERVER_SECRET');

		// **PREVENTING SESSION HIJACKING**
		// Prevents javascript XSS attacks aimed to steal the session ID
		@ini_set('session.cookie_httponly', '1');

		// **PREVENTING SESSION FIXATION**
		// Session ID cannot be passed through URLs
		@ini_set('session.use_only_cookies', '1');

		@ini_set('session.use_trans_sid', '0');

		// Uses a secure connection (HTTPS) if possible
		@ini_set('session.cookie_secure', OIDplus::isSslAvailable() ? '1' : '0');

		$path = OIDplus::webpath(null,OIDplus::PATH_RELATIVE);
		if (empty($path)) $path = '/';
		@ini_set('session.cookie_path', $path);

		@ini_set('session.cookie_samesite', OIDplus::baseConfig()->getValue('COOKIE_SAMESITE_POLICY', 'Strict'));

		@ini_set('session.use_strict_mode', '1');

		@ini_set('session.gc_maxlifetime', $this->sessionLifetime);
	}

	/**
	 * @return void
	 * @throws OIDplusException
	 */
	protected function sessionSafeStart() {
		if (!isset($_SESSION)) {
			// TODO: session_name() makes some problems. Leave it away for now.
			//session_name('OIDplus_SESHDLR');
			if (!session_start()) {
				throw new OIDplusException(_L('Session could not be started'));
			}
		}

		if (!isset($_SESSION['ip'])) {
			if (!isset($_SERVER['REMOTE_ADDR'])) return;

			// Remember the IP address of the user
			$_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
		} else {
			if ($_SERVER['REMOTE_ADDR'] != $_SESSION['ip']) {
				// Was the session hijacked?! Get out of here!

				// We don't use $this->destroySession(), because this calls sessionSafeStart() again
				$_SESSION = array();
				session_destroy();
				session_write_close();
				OIDplus::cookieUtils()->unsetcookie(session_name()); // remove cookie, so GDPR people are happy
			}
		}
	}

	/**
	 * @return void
	 */
	function __destruct() {
		session_write_close();
	}

	private $cacheSetValues = array(); // Important if you do a setValue() followed by an getValue()

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 * @throws OIDplusException
	 */
	public function setValue(string $name, $value) {
		$enc_data = self::encrypt($value, $this->secret);

		$this->cacheSetValues[$name] = $enc_data;

		$this->sessionSafeStart();
		OIDplus::cookieUtils()->setcookie(session_name(),session_id(),time()+$this->sessionLifetime);

		$_SESSION[$name] = $enc_data;
	}

	/**
	 * @param string $name
	 * @param mixed|null $default
	 * @return mixed|null
	 * @throws OIDplusException
	 */
	public function getValue(string $name, $default = NULL) {
		if (isset($this->cacheSetValues[$name])) return self::decrypt($this->cacheSetValues[$name], $this->secret);

		if (!$this->isActive()) return $default; // GDPR: Only start a session when we really need one
		$this->sessionSafeStart();
		OIDplus::cookieUtils()->setcookie(session_name(),session_id(),time()+$this->sessionLifetime);

		if (!isset($_SESSION[$name])) return $default;
		return self::decrypt($_SESSION[$name], $this->secret);
	}

	/**
	 * @param string $name
	 * @return bool
	 * @throws OIDplusException
	 */
	public function exists(string $name): bool {
		if (isset($this->cacheSetValues[$name])) return true;

		if (!$this->isActive()) return false; // GDPR: Only start a session when we really need one
		$this->sessionSafeStart();
		OIDplus::cookieUtils()->setcookie(session_name(),session_id(),time()+$this->sessionLifetime);

		return isset($_SESSION[$name]);
	}

	/**
	 * @param string $name
	 * @return void
	 * @throws OIDplusException
	 */
	public function delete(string $name) {
		if (isset($this->cacheSetValues[$name])) unset($this->cacheSetValues[$name]);

		if (!$this->isActive()) return; // GDPR: Only start a session when we really need one
		$this->sessionSafeStart();
		OIDplus::cookieUtils()->setcookie(session_name(),session_id(),time()+$this->sessionLifetime);

		unset($_SESSION[$name]);
	}

	/**
	 * @return void
	 * @throws OIDplusException
	 */
	public function destroySession() {
		if (!$this->isActive()) return;

		$this->sessionSafeStart();
		OIDplus::cookieUtils()->setcookie(session_name(),session_id(),time()+$this->sessionLifetime);

		$_SESSION = array();
		session_destroy();
		session_write_close();
		OIDplus::cookieUtils()->unsetcookie(session_name()); // remove cookie, so GDPR people are happy
	}

	/**
	 * @return bool
	 */
	public function isActive(): bool {
		return isset($_COOKIE[session_name()]);
	}

	/**
	 * @param string $data
	 * @param string $key
	 * @return string
	 * @throws \Exception
	 */
	protected static function encrypt(string $data, string $key): string {
		if (function_exists('openssl_encrypt')) {
			$iv = random_bytes(16); // AES block size in CBC mode
			// Encryption
			$ciphertext = openssl_encrypt(
				$data,
				'AES-256-CBC',
				hash_pbkdf2('sha512', $key, '', 10000, 32/*256bit*/, true),
				OPENSSL_RAW_DATA,
				$iv
			);
			// Authentication
			$hmac = sha3_512_hmac($iv . $ciphertext, $key, true);
			return $hmac . $iv . $ciphertext;
		} else {
			// When OpenSSL is not available, then we just do a HMAC
			$hmac = sha3_512_hmac($data, $key, true);
			return $hmac . $data;
		}
	}

	/**
	 * @param string $data
	 * @param string $key
	 * @return string
	 * @throws OIDplusException
	 */
	protected static function decrypt(string $data, string $key): string {
		if (function_exists('openssl_decrypt')) {
			$hmac       = mb_substr($data, 0, 64, '8bit');
			$iv         = mb_substr($data, 64, 16, '8bit');
			$ciphertext = mb_substr($data, 80, null, '8bit');
			// Authentication
			$hmacNew = sha3_512_hmac($iv . $ciphertext, $key, true);
			if (!hash_equals($hmac, $hmacNew)) {
				throw new OIDplusException(_L('Authentication failed'));
			}
			// Decryption
			$cleartext = openssl_decrypt(
				$ciphertext,
				'AES-256-CBC',
				hash_pbkdf2('sha512', $key, '', 10000, 32/*256bit*/, true),
				OPENSSL_RAW_DATA,
				$iv
			);
			if ($cleartext === false) {
				throw new OIDplusException(_L('Decryption failed'));
			}
			return $cleartext;
		} else {
			// When OpenSSL is not available, then we just do a HMAC
			$hmac       = mb_substr($data, 0, 64, '8bit');
			$cleartext  = mb_substr($data, 64, null, '8bit');
			$hmacNew    = sha3_512_hmac($cleartext, $key, true);
			if (!hash_equals($hmac, $hmacNew)) {
				throw new OIDplusException(_L('Authentication failed'));
			}
			return $cleartext;
		}
	}
}
