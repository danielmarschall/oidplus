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

class OIDplus {
	private static /*OIDplusPagePlugin[][]*/ $pagePlugins = array();
	private static /*OIDplusObject*/ $objectTypes = array();
	private static /*OIDplusObject*/ $disabledObjectTypes = array();

	private function __construct() {
	}

	public static function db() {
		static $database = null;
		if (is_null($database)) {
			$database = new OIDplusDataBaseMySQL();
		}
		return $database;
	}

	public static function config() {
		static $config = null;
		if (is_null($config)) {
			$config = new OIDplusConfig();
		}
		return $config;
	}

	public static function gui() {
		static $gui = null;
		if (is_null($gui)) {
			$gui = new OIDplusGui();
		}
		return $gui;
	}

	public static function authUtils() {
		static $authUtils = null;
		if (is_null($authUtils)) {
			$authUtils = new OIDplusAuthUtils();
		}
		return $authUtils;
	}

	public static function sesHandler() {
		static $sesHandler = null;
		if (is_null($sesHandler)) {
			$sesHandler = new OIDplusSessionHandler(OIDPLUS_SESSION_SECRET);
		}
		return $sesHandler;
	}

	public static function system_url($relative=false) {
		if (!isset($_SERVER["REQUEST_URI"])) return false;

		$test_dir = dirname($_SERVER['SCRIPT_FILENAME']);
		$c = 0;
		while (!file_exists($test_dir.'/oidplus.js')) {
			$test_dir = dirname($test_dir);
			$c++;
			if ($c == 1000) return false;
		}

		$res = dirname($_SERVER['REQUEST_URI'].'xxx');

		for ($i=1; $i<=$c; $i++) {
			$res = dirname($res);
		}

		$res .= '/';

		if (!$relative) {
			$res = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . $res; // TODO: also add port?
		}

		return $res;
	}

	public static function registerPagePlugin(OIDplusPagePlugin $plugin) {
		$type = $plugin->type();
		if ($type === false) return false;

		$prio = $plugin->priority();
		if ($prio === false) return false;

		if (!isset(self::$pagePlugins[$type])) self::$pagePlugins[$type] = array();
		self::$pagePlugins[$type][$prio] = $plugin;

		return true;
	}

	public static function getPagePlugins($type) {
		if ($type == '*') {
			$res = array();
			foreach (self::$pagePlugins as $data) {
				$res = array_merge($res, $data);
			}
		} else {
			$res = isset(self::$pagePlugins[$type]) ? self::$pagePlugins[$type] : array();
		}
		ksort($res);
		return $res;
	}

	public static function registerObjectType($ot) {
		$ns = $ot::ns();

		if (empty($ns)) die("Attention: Empty NS at $ot\n");

		$ns_found = false;
		foreach (OIDplus::getRegisteredObjectTypes() as $test_ot) {
			if ($test_ot::ns() == $ns) {
				$ns_found = true;
				break;
			}
		}
		if ($ns_found) {
			throw new Exception("Attention: Two objectType plugins use the same namespace \"$ns\"!");
		}

		$init = OIDplus::config()->getValue("objecttypes_initialized");
		$init_ary = empty($init) ? array() : explode(';', $init);
		$init_ary = array_map('trim', $init_ary);

		$enabled = OIDplus::config()->getValue("objecttypes_enabled");
		$enabled_ary = empty($enabled) ? array() : explode(';', $enabled);
		$enabled_ary = array_map('trim', $enabled_ary);

		$do_enable = false;
		if (in_array($ns, $enabled_ary)) {
			$do_enable = true;
		} else {
			if (!OIDplus::config()->getValue('registration_done')) {
				$do_enable = $ns == 'oid';
			} else {
				$do_enable = !in_array($ns, $init_ary);
			}
		}

		if ($do_enable) {
			self::$objectTypes[] = $ot;
			usort(self::$objectTypes, function($a, $b) {
				$enabled = OIDplus::config()->getValue("objecttypes_enabled");
				$enabled_ary = explode(';', $enabled);

				$idx_a = array_search($a::ns(), $enabled_ary);
				$idx_b = array_search($b::ns(), $enabled_ary);

			        if ($idx_a == $idx_b) {
			            return 0;
			        }
			        return ($idx_a > $idx_b) ? +1 : -1;
			});
		} else {
			self::$disabledObjectTypes[] = $ot;
		}

		if (!in_array($ns, $init_ary)) {
			// Was never initialized before, so we add it to the list of enabled object types once

			if ($do_enable) {
				$enabled_ary[] = $ns;
				OIDplus::config()->setValue("objecttypes_enabled", implode(';', $enabled_ary));
			}

			$init_ary[] = $ns;
			OIDplus::config()->setValue("objecttypes_initialized", implode(';', $init_ary));
		}
	}

	public static function getRegisteredObjectTypes() {
		return self::$objectTypes;
	}

	public static function getDisabledObjectTypes() {
		return self::$disabledObjectTypes;
	}

	public static function system_id($oid=false) {
		if (!self::pkiStatus(true)) return false;
		$pubKey = OIDplus::config()->getValue('oidplus_public_key');
		if (preg_match('@BEGIN PUBLIC KEY\-+(.+)\-+END PUBLIC KEY@ismU', $pubKey, $m)) {
			return ($oid ? '1.3.6.1.4.1.37476.30.9.' : '').smallhash(base64_decode($m[1]));
		}
		return false;
	}

	public static function pkiStatus($try_generate=true) {
		if (!function_exists('openssl_pkey_new')) return false;

		$privKey = OIDplus::config()->getValue('oidplus_private_key');
		$pubKey = OIDplus::config()->getValue('oidplus_public_key');

		if ($try_generate && !verify_private_public_key($privKey, $pubKey)) {
			$config = array(
			    "digest_alg" => "sha512",
			    "private_key_bits" => 2048,
			    "private_key_type" => OPENSSL_KEYTYPE_RSA,
			);

			// Create the private and public key
			$res = openssl_pkey_new($config);

			// Extract the private key from $res to $privKey
			openssl_pkey_export($res, $privKey);

			OIDplus::config()->setValue('oidplus_private_key', $privKey);

			// Extract the public key from $res to $pubKey
			$pubKey = openssl_pkey_get_details($res);
			$pubKey = $pubKey["key"];

			OIDplus::config()->setValue('oidplus_public_key', $pubKey);
		}

		return verify_private_public_key($privKey, $pubKey);
	}

	public static function init($html=true) {
		define('OIDPLUS_HTML_OUTPUT', $html);

		// Include config file

		if (file_exists(__DIR__ . '/../config.inc.php')) {
			include_once __DIR__ . '/../config.inc.php';
		} else {
			if ($html) {
				if (!is_dir('setup')) {
					echo 'Error: Setup directory missing.';
				} else {
					header('Location:setup/');
				}
			} else {
				echo 'Error: Setup directory missing!';
			}
			die();
		}

		// Auto-fill non-existing config values

		if (!defined('OIDPLUS_CONFIG_VERSION'))   define('OIDPLUS_CONFIG_VERSION',   0.0);
		if (!defined('OIDPLUS_ADMIN_PASSWORD'))   define('OIDPLUS_ADMIN_PASSWORD',   '');
		if (!defined('OIDPLUS_MYSQL_HOST'))       define('OIDPLUS_MYSQL_HOST',       'localhost');
		if (!defined('OIDPLUS_MYSQL_USERNAME'))   define('OIDPLUS_MYSQL_USERNAME',   'root');
		if (!defined('OIDPLUS_MYSQL_PASSWORD'))   define('OIDPLUS_MYSQL_PASSWORD',   '');
		if (!defined('OIDPLUS_MYSQL_DATABASE'))   define('OIDPLUS_MYSQL_DATABASE',   'oidplus');
		if (!defined('OIDPLUS_TABLENAME_PREFIX')) define('OIDPLUS_TABLENAME_PREFIX', '');
		if (!defined('OIDPLUS_SESSION_SECRET'))   define('OIDPLUS_SESSION_SECRET',   '');
		if (!defined('RECAPTCHA_ENABLED'))        define('RECAPTCHA_ENABLED',        false);
		if (!defined('RECAPTCHA_PUBLIC'))         define('RECAPTCHA_PUBLIC',         '');
		if (!defined('RECAPTCHA_PRIVATE'))        define('RECAPTCHA_PRIVATE',        '');
		if (!defined('OIDPLUS_ENFORCE_SSL'))      define('OIDPLUS_ENFORCE_SSL',      2 /* Auto */);

		// Check version of the config file

		if (OIDPLUS_CONFIG_VERSION != 2.0) {
			if ($html) {
				echo '<h1>Error</h1><p>The information located in <b>includes/config.inc.php</b> is outdated.</p><p>Please run <a href="setup/">setup</a> again.</p>';
			} else {
				echo 'The information located in includes/config.inc.php is outdated. Please run setup again.';
			}
			die();
		}

		// Do redirect stuff etc.

		define('OIDPLUS_SSL_AVAILABLE', self::isSslAvailable());

		// System config settings

		OIDplus::config()->prepareConfigKey('objecttypes_initialized', 'List of object type plugins that were initialized once', '', 1, 1);
		OIDplus::config()->prepareConfigKey('objecttypes_enabled', 'Enabled object types and their order, separated with a semicolon (please reload the page so that the change is applied)', '', 0, 1);

		OIDplus::config()->prepareConfigKey('oidplus_private_key', 'Private key for this system', '', 1, 0);
		OIDplus::config()->prepareConfigKey('oidplus_public_key', 'Public key for this system. If you "clone" your system, you must delete this key (e.g. using phpMyAdmin), so that a new one is created.', '', 1, 1);

		// Initialize public / private keys

		OIDplus::pkiStatus(true);

		// Register plugins

		$ary = glob(__DIR__ . '/../../plugins/system/'.'*'.'/plugin.inc.php');
		foreach ($ary as $a) include $a;
		$ary = glob(__DIR__ . '/../../plugins/publicPages/'.'*'.'/plugin.inc.php');
		foreach ($ary as $a) include $a;
		$ary = glob(__DIR__ . '/../../plugins/raPages/'.'*'.'/plugin.inc.php');
		foreach ($ary as $a) include $a;
		$ary = glob(__DIR__ . '/../../plugins/adminPages/'.'*'.'/plugin.inc.php');
		foreach ($ary as $a) include $a;
		$ary = glob(__DIR__ . '/../../plugins/objectTypes/'.'*'.'/*.class.php');
		foreach ($ary as $a) include $a;

		// Initialize plugins

		foreach (OIDplus::getPagePlugins('*') as $plugin) {
			$plugin->init($html);
		}
	}

	public static function getVersion() {
		$status = @shell_exec('svnversion '.realpath(__FILE__));
		if (preg_match('/\d+/', $status, $match)) {
			return 'svn-'.$match[0];
		} else {
			return false;
		}
	}

	private static function isSslAvailable() {
		$timeout = 2;
		$already_ssl = isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == "on");
		$ssl_port = 443;
		$cookie_path = OIDplus::system_url(true);
		if (empty($cookie_path)) $cookie_path = '/';

		if (php_sapi_name() == 'cli') return false;

		if (OIDPLUS_ENFORCE_SSL == 0) {
			// No SSL available
			return $already_ssl;
		}

		if (OIDPLUS_ENFORCE_SSL == 1) {
			// Force SSL
			if ($already_ssl) {
				return true;
			} else {
				$location = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				header('Location:'.$location);
				die('Redirect to HTTPS');
				return true;
			}
		}

		if (OIDPLUS_ENFORCE_SSL == 2) {
			// Automatic SSL detection

			if ($already_ssl) {
				// we are already on HTTPS
				setcookie('SSL_CHECK', '1', 0, $cookie_path, '', false, true);
				return true;
			} else {
				if (isset($_COOKIE['SSL_CHECK'])) {
					// We already had the HTTPS detection done before.
					if ($_COOKIE['SSL_CHECK']) {
						// HTTPS was detected before, but we are HTTP. Redirect now
						$location = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
						header('Location:'.$location);
						die('Redirect to HTTPS');
						return true;
					} else {
						// No HTTPS available. Do nothing.
						return false;
					}
				} else {
					// This is our first check (or the browser didn't accept the SSL_CHECK cookie)
					if (@fsockopen($_SERVER['HTTP_HOST'], $ssl_port, $errno, $errstr, $timeout)) {
						// HTTPS detected. Redirect now, and remember that we had detected HTTPS
						setcookie('SSL_CHECK', '1', 0, $cookie_path, '', false, true);
						$location = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
						header('Location:'.$location);
						die('Redirect to HTTPS');
						return true;
					} else {
						// No HTTPS detected. Do nothing, and next time, don't try to detect HTTPS again.
						setcookie('SSL_CHECK', '0', 0, $cookie_path, '', false, true);
						return false;
					}
				}
			}
		}
	}
}
