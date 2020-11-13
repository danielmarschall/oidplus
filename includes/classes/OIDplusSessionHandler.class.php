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

class OIDplusSessionHandler {

	private $secret = '';
	protected $sessionLifetime = '';
	public $simulate = false;

	public function __construct() {
		$this->sessionLifetime = OIDplus::baseConfig()->getValue('SESSION_LIFETIME', 30*60);
		$this->secret = OIDplus::baseConfig()->getValue('SERVER_SECRET');

		// **PREVENTING SESSION HIJACKING**
		// Prevents javascript XSS attacks aimed to steal the session ID
		@ini_set('session.cookie_httponly', 1);

		// **PREVENTING SESSION FIXATION**
		// Session ID cannot be passed through URLs
		@ini_set('session.use_only_cookies', 1);

		@ini_set('session.use_trans_sid', 0);

		// Uses a secure connection (HTTPS) if possible
		@ini_set('session.cookie_secure', OIDplus::isSslAvailable());

		$path = OIDplus::getSystemUrl(true);
		if (!empty($path)) {
			@ini_set('session.cookie_path', $path);
		}

		@ini_set('session.cookie_samesite', 'Lax');

		@ini_set('session.use_strict_mode', 1);

		@ini_set('session.gc_maxlifetime', $this->sessionLifetime);
	}

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
				setcookie(session_name(), "", time()-3600, ini_get('session.cookie_path')); // remove cookie, so GDPR people are happy
			}
		}
	}

	function __destruct() {
		session_write_close();
	}

	private $cacheSetValues = array(); // Important if you do a setValue() followed by an getValue()

	public function setValue($name, $value) {
		$this->cacheSetValues[$name] = self::encrypt($value, $this->secret);
		if ($this->simulate) return;

		$this->sessionSafeStart();
		setcookie(session_name(),session_id(),time()+$this->sessionLifetime, ini_get('session.cookie_path'));

		$_SESSION[$name] = self::encrypt($value, $this->secret);
	}

	public function getValue($name) {
		if (isset($this->cacheSetValues[$name])) return self::decrypt($this->cacheSetValues[$name], $this->secret);
		if ($this->simulate) return null;

		if (!isset($_COOKIE[session_name()])) return null; // GDPR: Only start a session when we really need one
		$this->sessionSafeStart();
		setcookie(session_name(),session_id(),time()+$this->sessionLifetime, ini_get('session.cookie_path'));

		if (!isset($_SESSION[$name])) return null;
		return self::decrypt($_SESSION[$name], $this->secret);
	}

	public function destroySession() {
		if (!isset($_COOKIE[session_name()])) return;

		$this->sessionSafeStart();
		setcookie(session_name(),session_id(),time()+$this->sessionLifetime, ini_get('session.cookie_path'));

		$_SESSION = array();
		session_destroy();
		session_write_close();
		setcookie(session_name(), "", time()-3600, ini_get('session.cookie_path')); // remove cookie, so GDPR people are happy
	}

	public function exists($name) {
		return isset($_SESSION[$name]);
	}

	protected static function encrypt($data, $key) {
		if (function_exists('openssl_encrypt')) {
			$iv = random_bytes(16); // AES block size in CBC mode
			// Encryption
			$ciphertext = openssl_encrypt(
				$data,
				'AES-256-CBC',
				mb_substr($key, 0, 32, '8bit'),
				OPENSSL_RAW_DATA,
				$iv
			);
			// Authentication
			$hmac = hash_hmac(
				'SHA256',
				$iv . $ciphertext,
				mb_substr($key, 32, null, '8bit'),
				true
			);
			return $hmac . $iv . $ciphertext;
		} else {
			// When OpenSSL is not available, then we just do a HMAC
			$hmac = hash_hmac(
				'SHA256',
				$data,
				mb_substr($key, 32, null, '8bit'),
				true
			);
			return $hmac . $data;
		}
	}

	protected static function decrypt($data, $key) {
		if (function_exists('openssl_decrypt')) {
			$hmac       = mb_substr($data, 0, 32, '8bit');
			$iv         = mb_substr($data, 32, 16, '8bit');
			$ciphertext = mb_substr($data, 48, null, '8bit');
			// Authentication
			$hmacNew = hash_hmac(
				'SHA256',
				$iv . $ciphertext,
				mb_substr($key, 32, null, '8bit'),
				true
			);
			if (!hash_equals($hmac, $hmacNew)) {
				throw new OIDplusException(_L('Authentication failed'));
			}
			// Decryption
			return openssl_decrypt(
				$ciphertext,
				'AES-256-CBC',
				mb_substr($key, 0, 32, '8bit'),
				OPENSSL_RAW_DATA,
				$iv
			);
		} else {
			// When OpenSSL is not available, then we just do a HMAC
			$hmac       = mb_substr($data, 0, 32, '8bit');
			$cleartext  = mb_substr($data, 32, null, '8bit');
			$hmacNew = hash_hmac(
				'SHA256',
				$cleartext,
				mb_substr($key, 32, null, '8bit'),
				true
			);
			if (!hash_equals($hmac, $hmacNew)) {
				throw new OIDplusException(_L('Authentication failed'));
			}
			return $cleartext;
		}
	}
}
