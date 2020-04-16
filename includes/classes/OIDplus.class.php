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

if (!defined('IN_OIDPLUS')) die();

class OIDplus {
	private static /*OIDplusPagePlugin[][]*/ $pagePlugins = array();
	private static /*OIDplusAuthPlugin[][]*/ $authPlugins = array();
	private static /*OIDplusObjectTypePlugin[]*/ $objectTypePlugins = array();
	private static /*string[]*/ $enabledObjectTypes = array();
	private static /*string[]*/ $disabledObjectTypes = array();
	private static /*OIDplusDatabasePlugin[]*/ $dbPlugins = array();

	protected static $html = null;

	private function __construct() {
	}

	# --- Singleton classes

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

	public static function mailUtils() {
		static $mailUtils = null;
		if (is_null($mailUtils)) {
			$mailUtils = new OIDplusMailUtils();
		}
		return $mailUtils;
	}

	public static function menuUtils() {
		static $menuUtils = null;
		if (is_null($menuUtils)) {
			$menuUtils = new OIDplusMenuUtils();
		}
		return $menuUtils;
	}

	public static function logger() {
		static $logger = null;
		if (is_null($logger)) {
			$logger = new OIDplusLogger();
		}
		return $logger;
	}

	public static function sesHandler() {
		static $sesHandler = null;
		if (is_null($sesHandler)) {
			$sesHandler = new OIDplusSessionHandler(OIDPLUS_SESSION_SECRET);
		}
		return $sesHandler;
	}

	# --- Database plugin

	private static function registerDatabasePlugin(OIDplusDatabasePlugin $plugin) {
		$name = $plugin->name();
		if ($name === false) return false;

		self::$dbPlugins[$name] = $plugin;

		return true;
	}

	public static function getDatabasePlugins() {
		return self::$dbPlugins;
	}

	public static function db() {
		if (!isset(self::$dbPlugins[OIDPLUS_DATABASE_PLUGIN])) {
			throw new OIDplusConfigInitializationException("Database plugin '".OIDPLUS_DATABASE_PLUGIN."' not found");
		}
		$obj = self::$dbPlugins[OIDPLUS_DATABASE_PLUGIN];
		if (!$obj->isConnected()) $obj->connect();
		return $obj;
	}

	# --- Page plugin

	private static function registerPagePlugin(OIDplusPagePlugin $plugin) {
		$type = $plugin->type();
		if ($type === false) return false;

		$prio = $plugin->priority();
		if (!is_numeric($prio)) throw new OIDplusException('Errornous plugin "'.get_class($plugin).'": Invalid priority');
		if ($prio <   0) throw new OIDplusException('Errornous plugin "'.get_class($plugin).'": Invalid priority');
		if ($prio > 999) throw new OIDplusException('Errornous plugin "'.get_class($plugin).'": Invalid priority');

		if (!isset(self::$pagePlugins[$type])) self::$pagePlugins[$type] = array();
		self::$pagePlugins[$type][str_pad($prio, 3, '0', STR_PAD_LEFT).get_class($plugin)] = $plugin;

		return true;
	}

	public static function getPagePlugins($type='*') {
		if ($type === '*') {
			$res = array();
			foreach (self::$pagePlugins as $data) {
				$res = array_merge($res, $data);
			}
		} else {
			$types = explode(',', $type);
			foreach ($types as $type) {
				$res = isset(self::$pagePlugins[$type]) ? self::$pagePlugins[$type] : array();
			}
		}
		ksort($res);
		return $res;
	}

	# --- Auth plugin

	private static function registerAuthPlugin(OIDplusAuthPlugin $plugin) {
		self::$authPlugins[] = $plugin;
		return true;
	}

	public static function getAuthPlugins() {
		return self::$authPlugins;
	}

	# --- Object type plugin

	private static function registerObjectTypePlugin(OIDplusObjectTypePlugin $plugin) {
		self::$objectTypePlugins[] = $plugin;

		$ot = $plugin::getObjectTypeClassName();
		self::registerObjectType($ot);

		return true;
	}

	private static function registerObjectType($ot) {
		$ns = $ot::ns();

		if (empty($ns)) throw new OIDplusException("Attention: Empty NS at $ot\n");

		$ns_found = false;
		foreach (array_merge(OIDplus::getEnabledObjectTypes(), OIDplus::getDisabledObjectTypes()) as $test_ot) {
			if ($test_ot::ns() == $ns) {
				$ns_found = true;
				break;
			}
		}
		if ($ns_found) {
			throw new OIDplusException("Attention: Two objectType plugins use the same namespace \"$ns\"!");
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
			self::$enabledObjectTypes[] = $ot;
			usort(self::$enabledObjectTypes, function($a, $b) {
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

	public static function getObjectTypePlugins() {
		return self::$objectTypePlugins;
	}

	public static function getObjectTypePluginsEnabled() {
		$res = array();
		foreach (self::$objectTypePlugins as $plugin) {
			$ot = $plugin::getObjectTypeClassName();
			if (in_array($ot, self::$enabledObjectTypes)) $res[] = $plugin;
		}
		return $res;
	}

	public static function getObjectTypePluginsDisabled() {
		$res = array();
		foreach (self::$objectTypePlugins as $plugin) {
			$ot = $plugin::getObjectTypeClassName();
			if (in_array($ot, self::$disabledObjectTypes)) $res[] = $plugin;
		}
		return $res;
	}

	public static function getEnabledObjectTypes() {
		return self::$enabledObjectTypes;
	}

	public static function getDisabledObjectTypes() {
		return self::$disabledObjectTypes;
	}

	# --- Initialization of OIDplus

	public static function init($html=true) {
		self::$html = $html;

		// Include config file

		if (file_exists(__DIR__ . '/../config.inc.php')) {
			include_once __DIR__ . '/../config.inc.php';
		} else {
			if (!is_dir(__DIR__.'/../../setup')) {
				throw new OIDplusConfigInitializationException('File includes/config.inc.php is missing, but setup can\'t be started because its directory missing.');
			} else {
				if ($html) {
					header('Location:'.OIDplus::getSystemUrl().'setup/');
					die('Redirecting to setup...');
				} else {
					// This can be displayed in e.g. ajax.php
					throw new OIDplusConfigInitializationException('File includes/config.inc.php is missing. Please run setup again.');
				}
			}
		}

		// Auto-fill non-existing config values, so that there won't be any PHP errors
		// if something would be missing in config.inc.php (which should not happen!)

		if (!defined('OIDPLUS_CONFIG_VERSION'))   define('OIDPLUS_CONFIG_VERSION',   0.0);
		if (!defined('OIDPLUS_ADMIN_PASSWORD'))   define('OIDPLUS_ADMIN_PASSWORD',   '');
		if (!defined('OIDPLUS_DATABASE_PLUGIN'))  define('OIDPLUS_DATABASE_PLUGIN',  'MySQL');
		if (!defined('OIDPLUS_MYSQL_HOST'))       define('OIDPLUS_MYSQL_HOST',       'localhost');
		if (!defined('OIDPLUS_MYSQL_USERNAME'))   define('OIDPLUS_MYSQL_USERNAME',   'root');
		if (!defined('OIDPLUS_MYSQL_PASSWORD'))   define('OIDPLUS_MYSQL_PASSWORD',   ''); // base64 encoded
		if (!defined('OIDPLUS_MYSQL_DATABASE'))   define('OIDPLUS_MYSQL_DATABASE',   'oidplus');
		if (!defined('OIDPLUS_TABLENAME_PREFIX')) define('OIDPLUS_TABLENAME_PREFIX', '');
		if (!defined('OIDPLUS_SESSION_SECRET'))   define('OIDPLUS_SESSION_SECRET',   '');
		if (!defined('RECAPTCHA_ENABLED'))        define('RECAPTCHA_ENABLED',        false);
		if (!defined('RECAPTCHA_PUBLIC'))         define('RECAPTCHA_PUBLIC',         '');
		if (!defined('RECAPTCHA_PRIVATE'))        define('RECAPTCHA_PRIVATE',        '');
		if (!defined('OIDPLUS_ENFORCE_SSL'))      define('OIDPLUS_ENFORCE_SSL',      2 /* Auto */);

		// Now include a file containing various size/depth limitations of OIDs
		// It is important to include it after config.inc.php was included,
		// so we can give config.inc.php the chance to override the values
		// by defining the constants first.

		include_once __DIR__ . '/../limits.inc.php';

		// Check version of the config file

		if (OIDPLUS_CONFIG_VERSION != 2.0) {
			throw new OIDplusConfigInitializationException("The information located in includes/config.inc.php is outdated.");
		}

		// Register database types (highest priority)

		$ary = glob(__DIR__ . '/../../plugins/database/'.'*'.'/plugin.inc.php');
		foreach ($ary as $a) include $a;

		foreach (get_declared_classes() as $c) {
			if (is_subclass_of($c, 'OIDplusDatabasePlugin')) {
				self::registerDatabasePlugin(new $c());
			}
		}

		foreach (OIDplus::getDatabasePlugins() as $plugin) {
			$plugin->init($html);
		}

		// Do redirect stuff etc.

		self::isSslAvailable(); // This function does automatic redirects

		// Construct the configuration manager once
		// During the construction, various system settings are prepared if required

		OIDplus::config();

		// Initialize public / private keys

		OIDplus::getPkiStatus(true);

		// Register non-DB plugins

		$ary = glob(__DIR__ . '/../../plugins/objectTypes/'.'*'.'/plugin.inc.php');
		foreach ($ary as $a) include $a;

		$ary = glob(__DIR__ . '/../../plugins/*Pages/'.'*'.'/plugin.inc.php');
		foreach ($ary as $a) include $a;

		$ary = glob(__DIR__ . '/../../plugins/auth/'.'*'.'/plugin.inc.php');
		foreach ($ary as $a) include $a;

		foreach (get_declared_classes() as $c) {
			if (!(new ReflectionClass($c))->isAbstract()) {
				if (is_subclass_of($c, 'OIDplusPagePlugin')) {
						self::registerPagePlugin(new $c());
				}
				if (is_subclass_of($c, 'OIDplusAuthPlugin')) {
					self::registerAuthPlugin(new $c());
				}
				if (is_subclass_of($c, 'OIDplusObjectTypePlugin')) {
					self::registerObjectTypePlugin(new $c());
				}
			}
		}

		// Initialize non-DB plugins

		foreach (OIDplus::getPagePlugins('*') as $plugin) {
			$plugin->init($html);
		}
		foreach (OIDplus::getAuthPlugins() as $plugin) {
			$plugin->init($html);
		}
		foreach (OIDplus::getObjectTypePlugins() as $plugin) {
			$plugin->init($html);
		}
	}

	# --- System URL, System ID, PKI, and other functions

	public static function getSystemUrl($relative=false) {
		if (!isset($_SERVER["SCRIPT_NAME"])) return false;

		$test_dir = dirname($_SERVER['SCRIPT_FILENAME']);
		$c = 0;
		while (!file_exists($test_dir.'/oidplus_base.js')) {
			$test_dir = dirname($test_dir);
			$c++;
			if ($c == 1000) return false;
		}

		$res = dirname($_SERVER['SCRIPT_NAME'].'xxx');

		for ($i=1; $i<=$c; $i++) {
			$res = dirname($res);
		}

		if ($res == '/') $res = '';
		$res .= '/';

		if (!$relative) {
			$is_ssl = isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on');
			$protocol = $is_ssl ? 'https' : 'http';
			$host = $_SERVER['HTTP_HOST'];
			$port = $_SERVER['SERVER_PORT'];
			if ($is_ssl && ($port != 443)) {
				$port_add = ":$port";
			} else if (!$is_ssl && ($port != 80)) {
				$port_add = ":$port";
			} else {
				$port_add = "";
			}
			$res = $protocol.'://'.$host.$port_add.$res;
		}

		return $res;
	}

	private static $system_id_cache = null;
	public static function getSystemId($oid=false) {
		if (!is_null(self::$system_id_cache)) {
			$out = self::$system_id_cache;
		} else {
			$out = false;

			if (self::getPkiStatus(true)) {
				$pubKey = OIDplus::config()->getValue('oidplus_public_key');
				if (preg_match('@BEGIN PUBLIC KEY\-+(.+)\-+END PUBLIC KEY@ismU', $pubKey, $m)) {
					$out = smallhash(base64_decode($m[1]));
				}
			}
			self::$system_id_cache = $out;
		}
		return ($out ? '1.3.6.1.4.1.37476.30.9.' : '').$out;
	}

	public static function getPkiStatus($try_generate=true) {
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

			if (!$res) return false;

			// Extract the private key from $res to $privKey
			openssl_pkey_export($res, $privKey);

			// Extract the public key from $res to $pubKey
			$pubKey = openssl_pkey_get_details($res)["key"];

			// Log
			OIDplus::logger()->log("A!", "Generating new SystemID using a new key pair");

			// Save the key pair to database
			OIDplus::config()->setValue('oidplus_private_key', $privKey);
			OIDplus::config()->setValue('oidplus_public_key', $pubKey);

			// Log the new system ID
			if (preg_match('@BEGIN PUBLIC KEY\-+(.+)\-+END PUBLIC KEY@ismU', $pubKey, $m)) {
				$system_id = smallhash(base64_decode($m[1]));
				OIDplus::logger()->log("A!", "Your SystemID is now $system_id");
			}
		}

		return verify_private_public_key($privKey, $pubKey);
	}

	public static function getInstallType() {
		if (!file_exists(__DIR__ . '/../../oidplus_version.txt') && !is_dir(__DIR__ . '/../../.svn')) {
			return 'unknown';
		}
		if (file_exists(__DIR__ . '/../../oidplus_version.txt') && is_dir(__DIR__ . '/../../.svn')) {
			return 'ambigous';
		}
		if (is_dir(__DIR__ . '/../../.svn')) {
			return 'svn-wc';
		}
		if (file_exists(__DIR__ . '/../../oidplus_version.txt')) {
			return 'svn-snapshot';
		}
	}

	public static function getVersion() {
		if (file_exists(__DIR__ . '/../../oidplus_version.txt') && is_dir(__DIR__ . '/../../.svn')) {
			return false; // version is ambigous
		}

		if (is_dir(__DIR__ . '/../../.svn')) {
			// Try to find out the SVN version using the shell
			// TODO: das müllt die log files voll!
			$status = @shell_exec('svnversion '.realpath(__FILE__));
			if (preg_match('/\d+/', $status, $match)) {
				return 'svn-'.$match[0];
			}

			// If that failed, try to get the version via SQLite3
			if (class_exists('SQLite3')) {
				$db = new SQLite3(__DIR__ . '/../../.svn/wc.db');
				$results = $db->query('SELECT MIN(revision) AS rev FROM NODES_BASE');
				while ($row = $results->fetchArray()) {
					return 'svn-'.$row['rev'];
				}
			}
		}

		if (file_exists(__DIR__ . '/../../oidplus_version.txt')) {
			$cont = file_get_contents(__DIR__ . '/../../oidplus_version.txt');
			if (preg_match('@Revision (\d+)@', $cont, $m))
				return 'svn-'.$m[1];
		}

		return false;
	}

	private static $sslAvailableCache = null;
	public static function isSslAvailable() {
		if (!is_null(self::$sslAvailableCache)) return self::$sslAvailableCache;

		if (php_sapi_name() == 'cli') {
			self::$sslAvailableCache = false;
			return false;
		}

		$timeout = 2;
		$already_ssl = isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == "on");
		$ssl_port = 443;
		$cookie_path = OIDplus::getSystemUrl(true);
		if (empty($cookie_path)) $cookie_path = '/';

		if (OIDPLUS_ENFORCE_SSL == 0) {
			// No SSL available
			self::$sslAvailableCache = $already_ssl;
			return $already_ssl;
		}

		if (OIDPLUS_ENFORCE_SSL == 1) {
			// Force SSL
			if ($already_ssl) {
				self::$sslAvailableCache = true;
				return true;
			} else {
				$location = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				header('Location:'.$location);
				die('Redirecting to HTTPS...');
				self::$sslAvailableCache = true;
				return true;
			}
		}

		if (OIDPLUS_ENFORCE_SSL == 2) {
			// Automatic SSL detection

			if ($already_ssl) {
				// we are already on HTTPS
				setcookie('SSL_CHECK', '1', 0, $cookie_path, '', false, true);
				self::$sslAvailableCache = true;
				return true;
			} else {
				if (isset($_COOKIE['SSL_CHECK'])) {
					// We already had the HTTPS detection done before.
					if ($_COOKIE['SSL_CHECK']) {
						// HTTPS was detected before, but we are HTTP. Redirect now
						$location = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
						header('Location:'.$location);
						die('Redirecting to HTTPS...');
						self::$sslAvailableCache = true;
						return true;
					} else {
						// No HTTPS available. Do nothing.
						self::$sslAvailableCache = false;
						return false;
					}
				} else {
					// This is our first check (or the browser didn't accept the SSL_CHECK cookie)
					if (@fsockopen($_SERVER['HTTP_HOST'], $ssl_port, $errno, $errstr, $timeout)) {
						// HTTPS detected. Redirect now, and remember that we had detected HTTPS
						setcookie('SSL_CHECK', '1', 0, $cookie_path, '', false, true);
						$location = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
						header('Location:'.$location);
						die('Redirecting to HTTPS...');
						self::$sslAvailableCache = true;
						return true;
					} else {
						// No HTTPS detected. Do nothing, and next time, don't try to detect HTTPS again.
						setcookie('SSL_CHECK', '0', 0, $cookie_path, '', false, true);
						self::$sslAvailableCache = false;
						return false;
					}
				}
			}
		}
	}

	public static function webpath($target) {
		$dir = __DIR__;
		$dir = dirname($dir);
		$dir = dirname($dir);
		$target = substr($target, strlen($dir)+1, strlen($target)-strlen($dir)-1);
		if ($target != '') {
			$target = str_replace('\\','/',$target).'/';
		}
		return $target;
	}
}
