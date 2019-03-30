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
	private static /*OIDplusDataBase*/ $database;
	private static /*OIDplusConfig*/ $config;
	private static /*OIDplusPagePlugin[][]*/ $pagePlugins = array();
	private static /*OIDplusObject*/ $objectTypes = array();

	private function __construct() {
	}

	public static function db() {
		if (is_null(self::$database)) {
			self::$database = new OIDplusDataBaseMySQL();
		}
		return self::$database;
	}

	public static function config() {
		if (is_null(self::$config)) {
			self::$config = new OIDplusConfig();
		}
		return self::$config;
	}

	public static function gui() {
		return new OIDplusGui();
	}

	public static function authUtils() {
		return new OIDplusAuthUtils();
	}

	public static function system_url() {
		return dirname($actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]").'/';
	}

	public static function sesHandler() {
		return new OIDplusSessionHandler(OIDPLUS_SESSION_SECRET);
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
			$res = self::$pagePlugins[$type];
		}
		ksort($res);
		return $res;
	}

	public static function registerObjectType($ot) {
		self::$objectTypes[] = $ot;
	}

	public static function getRegisteredObjectTypes() {
		return self::$objectTypes;
	}

	public static function init($html=true) {
		define('OIDPLUS_HTML_OUTPUT', $html);

		// Include config file
		if (file_exists(__DIR__ . '/../config.inc.php')) {
			include_once __DIR__ . '/../config.inc.php';
		} else {
			if ($html) {
				if (!is_dir(__DIR__.'/../setup')) {
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
		if (!defined('OIDPLUS_ADMIN_EMAIL'))      define('OIDPLUS_ADMIN_EMAIL',      '');
		if (!defined('OIDPLUS_MYSQL_HOST'))       define('OIDPLUS_MYSQL_HOST',       'localhost');
		if (!defined('OIDPLUS_MYSQL_USERNAME'))   define('OIDPLUS_MYSQL_USERNAME',   'root');
		if (!defined('OIDPLUS_MYSQL_PASSWORD'))   define('OIDPLUS_MYSQL_PASSWORD',   '');
		if (!defined('OIDPLUS_MYSQL_DATABASE'))   define('OIDPLUS_MYSQL_DATABASE',   'oidplus');
		if (!defined('OIDPLUS_TABLENAME_PREFIX')) define('OIDPLUS_TABLENAME_PREFIX', '');
		if (!defined('OIDPLUS_SESSION_SECRET'))   define('OIDPLUS_SESSION_SECRET',   '');
		if (!defined('RECAPTCHA_ENABLED'))        define('RECAPTCHA_ENABLED',        false);
		if (!defined('RECAPTCHA_PUBLIC'))         define('RECAPTCHA_PUBLIC',         '');
		if (!defined('RECAPTCHA_PRIVATE'))        define('RECAPTCHA_PRIVATE',        '');

		// Check version of the config file
		if (OIDPLUS_CONFIG_VERSION != 0.1) {
			if ($html) {
				echo '<h1>Error</h1><p>The information located in <b>includes/config.inc.php</b> is outdated.</p><p>Please run <a href="setup/">setup</a> again.</p>';
			} else {
				echo 'The information located in includes/config.inc.php is outdated. Please run setup again.';
			}
			die();
		}

		// Do redirect stuff etc.
		define('OIDPLUS_SSL_AVAILABLE', self::isSslAvailable());

		// Register plugins
		$ary = glob(__DIR__ . '/../../plugins/publicPages/'.'*'.'/plugin.inc.php');
		foreach ($ary as $a) include $a;
		$ary = glob(__DIR__ . '/../../plugins/raPages/'.'*'.'/plugin.inc.php');
		foreach ($ary as $a) include $a;
		$ary = glob(__DIR__ . '/../../plugins/adminPages/'.'*'.'/plugin.inc.php');
		foreach ($ary as $a) include $a;
	}

	private static function isSslAvailable() {
		$timeout = 2;

		if (php_sapi_name() == 'cli') return false;

		if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == "on")) {
			// we are already on HTTPS
			setcookie('SSL_CHECK', '1', 0, '', '', false, true);
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
				if (@fsockopen($_SERVER['HTTP_HOST'], 443, $errno, $errstr, $timeout)) {
					// HTTPS detected. Redirect now, and remember that we had detected HTTPS
					setcookie('SSL_CHECK', '1', 0, '', '', false, true);
					$location = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
					header('Location:'.$location);
					die('Redirect to HTTPS');
					return true;
				} else {
					// No HTTPS detected. Do nothing, and next time, don't try to detect HTTPS again.
					setcookie('SSL_CHECK', '0', 0, '', '', false, true);
					return false;
				}
			}
		}
	}
}
