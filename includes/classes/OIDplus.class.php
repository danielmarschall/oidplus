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
	private static /*OIDplusPagePlugin[]*/ $pagePlugins = array();
	private static /*OIDplusAuthPlugin[]*/ $authPlugins = array();
	private static /*OIDplusLoggerPlugin[]*/ $loggerPlugins = array();
	private static /*OIDplusObjectTypePlugin[]*/ $objectTypePlugins = array();
	private static /*string[]*/ $enabledObjectTypes = array();
	private static /*string[]*/ $disabledObjectTypes = array();
	private static /*OIDplusDatabasePlugin[]*/ $dbPlugins = array();
	private static /*OIDplusSqlSlangPlugin[]*/ $sqlSlangPlugins = array();

	protected static $html = true;

	private function __construct() {
	}

	# --- Static classes

	private static $baseConfig = null;
	private static $old_config_format = false;
	public static function baseConfig() {
		$first_init = false;

		if ($first_init = is_null(self::$baseConfig)) {
			self::$baseConfig = new OIDplusBaseConfig();
		}

		if ($first_init) {
			// Include a file containing various size/depth limitations of OIDs
			// It is important to include it before config.inc.php was included,
			// so we can give config.inc.php the chance to override the values.

			include __DIR__ . '/../limits.inc.php';

			// Include config file

			if (file_exists(__DIR__ . '/../config.inc.php')) {
				if (self::$old_config_format) {
					// Note: We may only include it once due to backwards compatibility,
					//       since in version 2.0, the configuration was defined using define() statements
					// Attention: This does mean that a full re-init (e.g. for test cases) is not possible
					//            if a version 2.0 config is used!
					include_once __DIR__ . '/../config.inc.php';
				} else {
					include __DIR__ . '/../config.inc.php';
				}

				if (defined('OIDPLUS_CONFIG_VERSION') && (OIDPLUS_CONFIG_VERSION == 2.0)) {
					self::$old_config_format = true;

					// Backwards compatibility 2.0 => 2.1
					foreach (get_defined_constants(true)['user'] as $name => $value) {
						$name = str_replace('OIDPLUS_', '', $name);
						if ($name == 'SESSION_SECRET') $name = 'SERVER_SECRET';
						if ($name == 'MYSQL_QUERYLOG') $name = 'QUERY_LOGFILE';
						if (($name == 'MYSQL_PASSWORD') || ($name == 'ODBC_PASSWORD') || ($name == 'PDO_PASSWORD') || ($name == 'PGSQL_PASSWORD')) {
							self::$baseConfig->setValue($name, base64_decode($value));
						} else {
							if ($name == 'CONFIG_VERSION') $value = 2.1;
							self::$baseConfig->setValue($name, $value);
						}
					}
				}
			} else {
				if (!is_dir(__DIR__.'/../../setup')) {
					throw new OIDplusConfigInitializationException('File includes/config.inc.php is missing, but setup can\'t be started because its directory missing.');
				} else {
					if (self::$html) {
						header('Location:'.OIDplus::getSystemUrl().'setup/');
						die('Redirecting to setup...');
					} else {
						// This can be displayed in e.g. ajax.php
						throw new OIDplusConfigInitializationException('File includes/config.inc.php is missing. Please run setup again.');
					}
				}
			}

			// Check important config settings

			if (self::$baseConfig->getValue('CONFIG_VERSION') != 2.1) {
				throw new OIDplusConfigInitializationException("The information located in includes/config.inc.php is outdated.");
			}

			if (self::$baseConfig->getValue('SERVER_SECRET', '') === '') {
				throw new OIDplusConfigInitializationException("You must set a value for SERVER_SECRET in includes/config.inc.php for the system to operate secure.");
			}
		}

		return self::$baseConfig;
	}

	private static $config = null;
	public static function config() {
		if ($first_init = is_null(self::$config)) {
			self::$config = new OIDplusConfig();
		}

		if ($first_init) {
			// These are important settings for base functionalities and therefore are not inside plugins
			self::$config->prepareConfigKey('system_title', 'What is the name of your RA?', 'OIDplus 2.0', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
				if (empty($value)) {
					throw new OIDplusException("Please enter a value for the system title.");
				}
			});
			self::$config->prepareConfigKey('admin_email', 'E-Mail address of the system administrator', '', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
				if (!empty($value) && !OIDplus::mailUtils()->validMailAddress($value)) {
					throw new OIDplusException("This is not a correct email address");
				}
			});
			self::$config->prepareConfigKey('global_cc', 'Global CC for all outgoing emails?', '', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
				if (!empty($value) && !OIDplus::mailUtils()->validMailAddress($value)) {
					throw new OIDplusException("This is not a correct email address");
				}
			});
			self::$config->prepareConfigKey('objecttypes_initialized', 'List of object type plugins that were initialized once', '', OIDplusConfig::PROTECTION_READONLY, function($value) {
				// Nothing here yet
			});
			self::$config->prepareConfigKey('objecttypes_enabled', 'Enabled object types and their order, separated with a semicolon (please reload the page so that the change is applied)', '', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
				# TODO: when objecttypes_enabled is changed at the admin control panel, we need to do a reload of the page, so that jsTree will be updated. Is there anything we can do?

				$ary = explode(';',$value);
				$uniq_ary = array_unique($ary);

				if (count($ary) != count($uniq_ary)) {
					throw new OIDplusException("Please check your input. Some object types are double.");
				}

				foreach ($ary as $ot_check) {
					$ns_found = false;
					foreach (OIDplus::getEnabledObjectTypes() as $ot) {
						if ($ot::ns() == $ot_check) {
							$ns_found = true;
							break;
						}
					}
					foreach (OIDplus::getDisabledObjectTypes() as $ot) {
						if ($ot::ns() == $ot_check) {
							$ns_found = true;
							break;
						}
					}
					if (!$ns_found) {
						throw new OIDplusException("Please check your input. Namespace \"$ot_check\" is not found");
					}
				}
			});
			self::$config->prepareConfigKey('oidplus_private_key', 'Private key for this system', '', OIDplusConfig::PROTECTION_HIDDEN, function($value) {
				// Nothing here yet
			});
			self::$config->prepareConfigKey('oidplus_public_key', 'Public key for this system. If you "clone" your system, you must delete this key (e.g. using phpMyAdmin), so that a new one is created.', '', OIDplusConfig::PROTECTION_READONLY, function($value) {
				// Nothing here yet
			});

		}

		return self::$config;
	}

	private static $gui = null;
	public static function gui() {
		if (is_null(self::$gui)) {
			self::$gui = new OIDplusGui();
		}
		return self::$gui;
	}

	private static $authUtils = null;
	public static function authUtils() {
		if (is_null(self::$authUtils)) {
			self::$authUtils = new OIDplusAuthUtils();
		}
		return self::$authUtils;
	}

	private static $mailUtils = null;
	public static function mailUtils() {
		if (is_null(self::$mailUtils)) {
			self::$mailUtils = new OIDplusMailUtils();
		}
		return self::$mailUtils;
	}

	private static $menuUtils = null;
	public static function menuUtils() {
		if (is_null(self::$menuUtils)) {
			self::$menuUtils = new OIDplusMenuUtils();
		}
		return self::$menuUtils;
	}

	private static $logger = null;
	public static function logger() {
		if (is_null(self::$logger)) {
			self::$logger = new OIDplusLogger();
		}
		return self::$logger;
	}

	private static $sesHandler = null;
	public static function sesHandler() {
		if (is_null(self::$sesHandler)) {
			self::$sesHandler = new OIDplusSessionHandler();
		}
		return self::$sesHandler;
	}

	# --- SQL slang plugin

	private static function registerSqlSlangPlugin(OIDplusSqlSlangPlugin $plugin) {
		$name = $plugin::id();
		if ($name === false) return false;

		self::$sqlSlangPlugins[$name] = $plugin;

		return true;
	}

	public static function getSqlSlangPlugins() {
		return self::$sqlSlangPlugins;
	}

	# --- Database plugin

	private static function registerDatabasePlugin(OIDplusDatabasePlugin $plugin) {
		$name = $plugin::id();
		if ($name === false) return false;

		self::$dbPlugins[$name] = $plugin;

		return true;
	}

	public static function getDatabasePlugins() {
		return self::$dbPlugins;
	}

	public static function db() {
		if (OIDplus::baseConfig()->getValue('DATABASE_PLUGIN', '') === '') {
			throw new OIDplusConfigInitializationException("No database plugin selected in config file");
		}
		if (!isset(self::$dbPlugins[OIDplus::baseConfig()->getValue('DATABASE_PLUGIN')])) {
			throw new OIDplusConfigInitializationException("Database plugin '".OIDplus::baseConfig()->getValue('DATABASE_PLUGIN')."' not found");
		}
		$obj = self::$dbPlugins[OIDplus::baseConfig()->getValue('DATABASE_PLUGIN')];
		if (!$obj->isConnected()) $obj->connect();
		return $obj;
	}

	# --- Page plugin

	private static function registerPagePlugin(OIDplusPagePlugin $plugin) {
		self::$pagePlugins[] = $plugin;

		return true;
	}

	public static function getPagePlugins() {
		return self::$pagePlugins;
	}

	# --- Auth plugin

	private static function registerAuthPlugin(OIDplusAuthPlugin $plugin) {
		self::$authPlugins[] = $plugin;
		return true;
	}

	public static function getAuthPlugins() {
		return self::$authPlugins;
	}

	# --- Logger plugin

	private static function registerLoggerPlugin(OIDplusLoggerPlugin $plugin) {
		self::$loggerPlugins[] = $plugin;
		return true;
	}

	public static function getLoggerPlugins() {
		return self::$loggerPlugins;
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

	# --- Plugin handling functions

	public static function getPluginInfo($class_name): array {
		$reflector = new ReflectionClass($class_name);
		$ini = dirname($reflector->getFileName()).'/manifest.ini';
		if (!file_exists($ini)) return array();
		$bry = parse_ini_file($ini, true, INI_SCANNER_TYPED);
		if (!isset($bry['Plugin'])) return array();
		return $bry['Plugin'];
	}

	public static function getAllPluginManifests($pluginFolderMask='*'): array {
		$out = array();
		// Note: glob() will sort by default, so we do not need a page priority attribute.
		//       So you just need to use a numeric plugin directory prefix (padded).
		$ary = glob(__DIR__ . '/../../plugins/'.$pluginFolderMask.'/'.'*'.'/manifest.ini');
		foreach ($ary as $ini) {
			if (!file_exists($ini)) continue;
			$bry = parse_ini_file($ini, true, INI_SCANNER_TYPED);

			$plugintype_folder = basename(dirname(dirname($ini)));
			$pluginname_folder = basename(dirname($ini));

			if (!isset($out[$plugintype_folder])) $out[$plugintype_folder] = array();
			if (!isset($out[$plugintype_folder][$pluginname_folder])) $out[$plugintype_folder][$pluginname_folder] = array();
			$out[$plugintype_folder][$pluginname_folder] = $bry;
		}
		return $out;
	}

	public static function registerAllPlugins($pluginDirName, $expectedPluginClass, $registerCallback): array {
		$out = array();
		$ary = self::getAllPluginManifests($pluginDirName);
		foreach ($ary as $plugintype_folder => $bry) {
			foreach ($bry as $pluginname_folder => $cry) {
				if (!isset($cry['PHP']) || !isset($cry['PHP']['pluginclass'])) {
					throw new OIDplusException("Plugin '$plugintype_folder/$pluginname_folder' is errornous: Plugin class is not defined (manifest.ini section 'PHP', key 'pluginclass'");
				}
				$class_name = $cry['PHP']['pluginclass'];
				if (!is_subclass_of($class_name, $expectedPluginClass)) {
					throw new OIDplusException("Plugin '$plugintype_folder/$pluginname_folder' is errornous: Plugin class '$class_name' is expected to be a subclass of '$expectedPluginClass'");
				}
				$out[] = $class_name;
				if (!is_null($registerCallback)) {
					call_user_func($registerCallback, new $class_name());
				}
			}

		}
		return $out;
	}

	# --- Initialization of OIDplus

	public static function init($html=true) {
		self::$html = $html;

		// Reset internal state, so we can re-init verything if required

		if (self::$old_config_format) {
			// Note: This can only happen in very special cases (e.g. test cases) where you call init() twice
			throw new OIDplusConfigInitializationException('A full re-initialization is not possible if a version 2.0 config file (containing "defines") is used. Please update to a config 2.1 file by running setup again.');
		}

		self::$config = null;
		self::$baseConfig = null;
		self::$gui = null;
		self::$authUtils = null;
		self::$mailUtils = null;
		self::$menuUtils = null;
		self::$logger = null;
		self::$sesHandler = null;
		self::$pagePlugins = array();
		self::$authPlugins = array();
		self::$loggerPlugins = array();
		self::$objectTypePlugins = array();
		self::$enabledObjectTypes = array();
		self::$disabledObjectTypes = array();
		self::$dbPlugins = array();
		self::$sqlSlangPlugins = array();
		self::$system_id_cache = null;
		self::$sslAvailableCache = null;

		// Continue...

		OIDplus::baseConfig(); // this loads the base configuration located in config.inc.php (once!)
		                       // You can do changes to the configuration afterwards using OIDplus::baseConfig()->...

		// Register database types (highest priority)

		// SQL slangs

		self::registerAllPlugins('sql_slang', 'OIDplusSqlSlangPlugin', array('OIDplus','registerSqlSlangPlugin'));
		foreach (OIDplus::getSqlSlangPlugins() as $plugin) {
			$plugin->init($html);
		}

		// Database providers

		self::registerAllPlugins('database', 'OIDplusDatabasePlugin', array('OIDplus','registerDatabasePlugin'));
		foreach (OIDplus::getDatabasePlugins() as $plugin) {
			$plugin->init($html);
		}

		// Do redirect stuff etc.

		self::isSslAvailable(); // This function does automatic redirects

		// Construct the configuration manager

		OIDplus::config(); // During the construction, various system settings are prepared if required

		// Initialize public / private keys

		OIDplus::getPkiStatus(true);

		// Register non-DB plugins

		self::registerAllPlugins('*Pages', 'OIDplusPagePlugin', array('OIDplus','registerPagePlugin'));
		self::registerAllPlugins('auth', 'OIDplusAuthPlugin', array('OIDplus','registerAuthPlugin'));
		self::registerAllPlugins('logger', 'OIDplusLoggerPlugin', array('OIDplus','registerLoggerPlugin'));
		self::registerAllPlugins('objectTypes', 'OIDplusObjectTypePlugin', array('OIDplus','registerObjectTypePlugin'));

		// Initialize non-DB plugins

		foreach (OIDplus::getPagePlugins() as $plugin) {
			$plugin->init($html);
		}
		foreach (OIDplus::getAuthPlugins() as $plugin) {
			$plugin->init($html);
		}
		foreach (OIDplus::getLoggerPlugins() as $plugin) {
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
		$test_dir = str_replace('\\', '/', $test_dir);
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

		$res = str_replace('\\', '/', $res);
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
			$pkey_config = array(
			    "digest_alg" => "sha512",
			    "private_key_bits" => 2048,
			    "private_key_type" => OPENSSL_KEYTYPE_RSA,
			);

			// Create the private and public key
			$res = openssl_pkey_new($pkey_config);

			if (!$res) return false;

			// Extract the private key from $res to $privKey
			openssl_pkey_export($res, $privKey);

			// Extract the public key from $res to $pubKey
			$pubKey = openssl_pkey_get_details($res)["key"];

			// Log
			OIDplus::logger()->log("[INFO]A!", "Generating new SystemID using a new key pair");

			// Save the key pair to database
			OIDplus::config()->setValue('oidplus_private_key', $privKey);
			OIDplus::config()->setValue('oidplus_public_key', $pubKey);

			// Log the new system ID
			if (preg_match('@BEGIN PUBLIC KEY\-+(.+)\-+END PUBLIC KEY@ismU', $pubKey, $m)) {
				$system_id = smallhash(base64_decode($m[1]));
				OIDplus::logger()->log("[INFO]A!", "Your SystemID is now $system_id");
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
			// Try to get the version via SQLite3
			if (class_exists('SQLite3')) {
				try {
					$db = new SQLite3(__DIR__ . '/../../.svn/wc.db');
					$results = $db->query('SELECT MIN(revision) AS rev FROM NODES_BASE');
					while ($row = $results->fetchArray()) {
						return 'svn-'.$row['rev'];
					}
					$db->close();
					$db = null;
				} catch (Exception $e) {
				}
			}
			if (class_exists('PDO')) {
				try {
					$pdo = new PDO('sqlite:' . __DIR__ . '/../../.svn/wc.db');
					$res = $pdo->query('SELECT MIN(revision) AS rev FROM NODES_BASE');
					$row = $res->fetch();
					if ($row !== false) return 'svn-'.$row['rev'];
					$pdo = null;
				} catch (Exception $e) {
				}
			}

			// Try to find out the SVN version using the shell
			// We don't prioritize this method, because a failed shell access will flood the apache error log with STDERR messages
			$output = @shell_exec('svnversion '.escapeshellarg(realpath(__DIR__ . '/../../')));
			if (preg_match('/\d+/', $output, $match)) {
				return 'svn-'.$match[0];
			}

			$output = @shell_exec('svn info '.escapeshellarg(realpath(__DIR__ . '/../../')));
			if (preg_match('/Revision:\s*(\d+)/m', $output, $match)) {
				return 'svn-'.$match[1];
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

		$mode = OIDplus::baseConfig()->getValue('ENFORCE_SSL', 2/*auto*/);

		if ($mode == 0) {
			// No SSL available
			self::$sslAvailableCache = $already_ssl;
			return $already_ssl;
		}

		if ($mode == 1) {
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

		if ($mode == 2) {
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
