<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2024 Daniel Marschall, ViaThinkSoft
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

namespace ViaThinkSoft\OIDplus\Core;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplus extends OIDplusBaseClass {
	/**
	 * @var ?OIDplusContext
	 */
	private static ?OIDplusContext $currentContext = null;

	/**
	 * Gets the current state of the system
	 * @return OIDplusContext
	 */
	public static function getCurrentContext(): OIDplusContext {
		if (is_null(self::$currentContext)) self::$currentContext = new OIDplusContext();
		return self::$currentContext;
	}

	/**
	 * Sets the current state of the system, for example if you want to connect to a different tenant.
	 * @param OIDplusContext $newContext
	 * @return void
	 */
	public static function setCurrentContext(OIDplusContext $newContext): void {
		self::$currentContext = $newContext;
	}

	/**
	 * e.g. "../"
	 */
	public const PATH_RELATIVE = 1;

	/**
	 * e.g. "http://www.example.com/oidplus/"
	 */
	public const PATH_ABSOLUTE = 2;

	/**
	 * e.g. "http://www.example.org/oidplus/" (if baseconfig CANONICAL_SYSTEM_URL is set)
	 */
	public const PATH_ABSOLUTE_CANONICAL = 3;

	/**
	 * e.g. "/oidplus/"
	 */
	public const PATH_RELATIVE_TO_ROOT = 4;

	/**
	 * e.g. "/oidplus/" (if baseconfig CANONICAL_SYSTEM_URL is set)
	 */
	public const PATH_RELATIVE_TO_ROOT_CANONICAL = 5;

	/**
	 * These plugin types can contain HTML code and therefore may
	 * emit (non-setup) CSS/JS code via their manifest.
	 * Note that design plugins may only output CSS, not JS.
	 */
	public const INTERACTIVE_PLUGIN_TYPES = array(
		'publicPages',
		'raPages',
		'adminPages',
		'objectTypes',
		'captcha'
	);

	//const UUID_NAMEBASED_NS_Base64PubKey = 'fd16965c-8bab-11ed-8744-3c4a92df8582';

	/**
	 * Private constructor (Singleton)
	 */
	private function __construct() {
	}

	/**
	 * @return bool
	 * @throws OIDplusException
	 */
	public static function insideSetup(): bool {
		if (PHP_SAPI == 'cli') return false;
		if (!isset($_SERVER['REQUEST_URI'])) return false;
		return (strpos($_SERVER['REQUEST_URI'], self::webpath(null,self::PATH_RELATIVE_TO_ROOT).'setup/') === 0);
	}

	// --- Static classes

	/**
	 * @return OIDplusBaseConfig
	 * @throws OIDplusException
	 * @throws OIDplusConfigInitializationException
	 */
	public static function baseConfig(): OIDplusBaseConfig {
		if ($first_init = is_null(self::getCurrentContext()->baseConfig)) {
			self::getCurrentContext()->baseConfig = new OIDplusBaseConfig();
		}

		if ($first_init) {
			if (self::insideSetup()) return self::getCurrentContext()->baseConfig;
			// noBaseConfig=1 setting: see OIDplusGui.class.php
			if ((basename($_SERVER['SCRIPT_NAME']) === 'oidplus.min.js.php') && isset($_REQUEST['noBaseConfig']) && ($_REQUEST['noBaseConfig'] == '1')) return self::getCurrentContext()->baseConfig;
			if ((basename($_SERVER['SCRIPT_NAME']) === 'oidplus.min.css.php') && isset($_REQUEST['noBaseConfig']) && ($_REQUEST['noBaseConfig'] == '1')) return self::getCurrentContext()->baseConfig;

			// Include a file containing various size/depth limitations of OIDs
			// It is important to include it before userdata/baseconfig/config.inc.php was included,
			// so we can give userdata/baseconfig/config.inc.php the chance to override the values.

			include self::localpath().'includes/oidplus_limits.inc.php';

			// Include config file

			$config_file = self::getUserDataDir("baseconfig") . 'config.inc.php';
			$config_file_old = self::localpath() . 'includes/config.inc.php'; // backwards compatibility

			if (!file_exists($config_file) && file_exists($config_file_old)) {
				$config_file = $config_file_old;
			}

			if (file_exists($config_file)) {
				if (self::getCurrentContext()->oldConfigFormatLoaded) {
					// Note: We may only include it once due to backwards compatibility,
					//       since in version 2.0, the configuration was defined using define() statements
					// Attention: This does mean that a full re-init (e.g. for test cases) is not possible
					//            if a version 2.0 config is used!

					// We need to do this, because define() cannot be undone
					// Note: This can only happen in very special cases (e.g. test cases) where you call init() twice
					throw new OIDplusConfigInitializationException(_L('A full re-initialization is not possible if a version %1 config file (containing "defines") is used. Please update to a config %2 file by running setup again.','2.0','2.1'));
				} else {
					$tmp = file_get_contents($config_file);
					$old_class1 = "ViaThinkSoft\OIDplus\OIDplus";
					$new_class1 = "ViaThinkSoft\OIDplus\Core\OIDplus";
					$uses = "use $new_class1;";
					$old_class2 = "ViaThinkSoft\OIDplus\OIDplusCaptchaPluginRecaptcha";
					$new_class2 = "ViaThinkSoft\OIDplus\Plugins\Captcha\ReCaptcha\OIDplusCaptchaPluginRecaptcha";
					if ((strpos($tmp,$old_class1) !== false) || (strpos($tmp,$old_class2) !== false)) {
						// Migrate from old namespace class names to new namespace class names (OIDplus 2.0.2.x)
						$tmp = str_replace($old_class1, $new_class1, $tmp);
						$tmp = str_replace($old_class2, $new_class2, $tmp);
						if (@file_put_contents($config_file, $tmp) === false) {
							eval('?'.'>'.$tmp);
						} else {
							include $config_file;
						}
					}
					else if ((strpos($tmp,'self::') !== false) && (strpos($tmp,$uses) === false)) {
						// Migrate config file to namespace class names
						// Note: Only config files version 2.1 are affected. Not 2.0 ones

						$tmp = "<?php\r\n\r\n$uses /* Automatically added by migration procedure */\r\n?>$tmp";
						$tmp = str_replace('?><?php', '', $tmp);

						$tmp = str_replace("\$ns\OIDplusCaptchaPluginRecaptcha::", "OIDplusCaptchaPluginRecaptcha::", $tmp);
						$tmp = str_replace("OIDplusCaptchaPluginRecaptcha::", "\$ns\OIDplusCaptchaPluginRecaptcha::", $tmp);

						if (@file_put_contents($config_file, $tmp) === false) {
							eval('?'.'>'.$tmp);
						} else {
							include $config_file;
						}
					} else {
						include $config_file;
					}
				}

				// Backwards compatibility 2.0 => 2.1
				if (defined('OIDPLUS_CONFIG_VERSION') && (OIDPLUS_CONFIG_VERSION == 2.0)) {
					self::getCurrentContext()->oldConfigFormatLoaded = true;
					foreach (get_defined_constants(true)['user'] as $name => $value) {
						$name = str_replace('OIDPLUS_', '', $name);
						if ($name == 'SESSION_SECRET') $name = 'SERVER_SECRET';
						if ($name == 'MYSQL_QUERYLOG') $name = 'QUERY_LOGFILE';
						if ($name == 'CONFIG_VERSION') {
							$value = 2.1;
						} else if (($name == 'MYSQL_PASSWORD') || ($name == 'ODBC_PASSWORD') || ($name == 'PDO_PASSWORD') || ($name == 'PGSQL_PASSWORD')) {
							$value = base64_decode($value);
						}
						self::getCurrentContext()->baseConfig->setValue($name, $value);
					}
				}
			} else {
				if (!is_dir(self::localpath().'setup')) {
					$config_file = substr($config_file, strlen(self::localpath(NULL))); // "censor" the system local path
					throw new OIDplusConfigInitializationException(_L('File %1 is missing, but setup can\'t be started because its directory missing.',$config_file));
				} else {
					if (self::getCurrentContext()->html) {
						if (!self::insideSetup()) {
							header('Location:'.self::webpath(null,self::PATH_RELATIVE).'setup/');
							die(_L('Redirecting to setup...'));
						} else {
							return self::getCurrentContext()->baseConfig;
						}
					} else {
						// This can be displayed in e.g. ajax.php
						$config_file = substr($config_file, strlen(self::localpath(NULL))); // "censor" the system local path
						throw new OIDplusConfigInitializationException(_L('File %1 is missing. Please run setup again.',$config_file));
					}
				}
			}

			// Check important config settings

			if (self::getCurrentContext()->baseConfig->getValue('CONFIG_VERSION') != 2.1) {
				if (strpos($_SERVER['REQUEST_URI']??'', self::webpath(null,self::PATH_RELATIVE).'setup/') !== 0) {
					$config_file = substr($config_file, strlen(self::localpath(NULL))); // "censor" the system local path
					throw new OIDplusConfigInitializationException(_L("The information located in %1 is outdated.",$config_file));
				}
			}

			if (self::getCurrentContext()->baseConfig->getValue('SERVER_SECRET', '') === '') {
				if (strpos($_SERVER['REQUEST_URI']??'', self::webpath(null,self::PATH_RELATIVE).'setup/') !== 0) {
					$config_file = substr($config_file, strlen(self::localpath(NULL))); // "censor" the system local path
					throw new OIDplusConfigInitializationException(_L("You must set a value for SERVER_SECRET in %1 for the system to operate secure.",$config_file));
				}
			}

			foreach (self::getCurrentContext()->baseConfig->getAllKeys() as $key) {
				if (str_starts_with($key, 'DISABLE_PLUGIN_')) {
					if (!oid_valid_dotnotation(substr($key, strlen('DISABLE_PLUGIN_')))) {
						throw new OIDplusConfigInitializationException(_L("File %1 contains an outdated setting %2. It must be a plugin OID instead of PHP class name.",$config_file,$key));
					}
				}
			}
		}

		return self::getCurrentContext()->baseConfig;
	}

	/**
	 * @return OIDplusConfig
	 * @throws OIDplusException
	 */
	public static function config(): OIDplusConfig {
		if ($first_init = is_null(self::getCurrentContext()->config)) {
			self::getCurrentContext()->config = new OIDplusConfig();
		}

		if ($first_init) {
			// These are important settings for base functionalities and therefore are not inside plugins
			self::getCurrentContext()->config->prepareConfigKey('system_title', 'What is the name of your RA?', 'OIDplus 2.0', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
				if (empty($value)) {
					throw new OIDplusException(_L('Please enter a value for the system title.'));
				}
			});
			self::getCurrentContext()->config->prepareConfigKey('admin_email', 'E-Mail address of the system administrator', '', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
				if (!empty($value) && !self::mailUtils()->validMailAddress($value)) {
					throw new OIDplusException(_L('This is not a correct email address'));
				}
			});
			self::getCurrentContext()->config->prepareConfigKey('global_cc', 'Global CC for all outgoing emails?', '', OIDplusConfig::PROTECTION_EDITABLE, function(&$value) {
				$value = trim($value);
				if ($value === '') return;
				$addrs = explode(';', $value);
				foreach ($addrs as $addr) {
					$addr = trim($addr);
					if (!empty($addr) && !self::mailUtils()->validMailAddress($addr)) {
						throw new OIDplusException(_L('%1 is not a correct email address',$addr));
					}
				}
			});
			self::getCurrentContext()->config->prepareConfigKey('global_bcc', 'Global BCC for all outgoing emails?', '', OIDplusConfig::PROTECTION_EDITABLE, function(&$value) {
				$value = trim($value);
				if ($value === '') return;
				$addrs = explode(';', $value);
				foreach ($addrs as $addr) {
					$addr = trim($addr);
					if (!empty($addr) && !self::mailUtils()->validMailAddress($addr)) {
						throw new OIDplusException(_L('%1 is not a correct email address',$addr));
					}
				}
			});
			self::getCurrentContext()->config->prepareConfigKey('objecttypes_initialized', 'List of object type plugins that were initialized once', '', OIDplusConfig::PROTECTION_READONLY, function($value) {
				// Nothing here yet
			});
			self::getCurrentContext()->config->prepareConfigKey('objecttypes_enabled', 'Enabled object types and their order, separated with a semicolon (please reload the page so that the change is applied)', '', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
				// TODO: when objecttypes_enabled is changed at the admin control panel, we need to do a reload of the page, so that jsTree will be updated. Is there anything we can do?

				$ary = explode(';',$value);
				$uniq_ary = array_unique($ary);

				if (count($ary) != count($uniq_ary)) {
					throw new OIDplusException(_L('Please check your input. Some object types are double.'));
				}

				foreach ($ary as $ot_check) {
					$ns_found = false;
					foreach (self::getEnabledObjectTypes() as $ot) {
						if ($ot::ns() == $ot_check) {
							$ns_found = true;
							break;
						}
					}
					foreach (self::getDisabledObjectTypes() as $ot) {
						if ($ot::ns() == $ot_check) {
							$ns_found = true;
							break;
						}
					}
					if (!$ns_found) {
						throw new OIDplusException(_L('Please check your input. Namespace "%1" is not found',$ot_check));
					}
				}
			});
			self::getCurrentContext()->config->prepareConfigKey('oidplus_private_key', 'Private key for this system', '', OIDplusConfig::PROTECTION_HIDDEN, function($value) {
				// Nothing here yet
			});
			self::getCurrentContext()->config->prepareConfigKey('oidplus_public_key', 'Public key for this system. If you "clone" your system, you must delete this key (e.g. using phpMyAdmin), so that a new one is created.', '', OIDplusConfig::PROTECTION_READONLY, function($value) {
				// Nothing here yet
			});
			self::getCurrentContext()->config->prepareConfigKey('last_known_system_url', 'Last known System URL', '', OIDplusConfig::PROTECTION_HIDDEN, function($value) {
				// Nothing here yet
			});
			self::getCurrentContext()->config->prepareConfigKey('last_known_version', 'Last known OIDplus Version', '', OIDplusConfig::PROTECTION_HIDDEN, function($value) {
				// Nothing here yet
			});
			self::getCurrentContext()->config->prepareConfigKey('default_ra_auth_method', 'Default auth method used for generating password of RAs (must exist in [userdata_pub/]plugins/[vendorname]/auth/)? Empty = OIDplus decides.', '', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
				if (trim($value) === '') return; // OIDplus decides

				$good = true;
				if (strpos($value,'/') !== false) $good = false;
				if (strpos($value,'\\') !== false) $good = false;
				if (strpos($value,'..') !== false) $good = false;
				if (!$good) {
					throw new OIDplusException(_L('Invalid auth plugin name. It is usually the folder name, without path, e.g. "%1"', 'A4_argon2'));
				}

				self::checkRaAuthPluginAvailable($value, true);
			});
		}

		return self::getCurrentContext()->config;
	}

	/**
	 * @return OIDplusGui
	 */
	public static function gui(): OIDplusGui {
		if (is_null(self::getCurrentContext()->gui)) {
			self::getCurrentContext()->gui = new OIDplusGui();
		}
		return self::getCurrentContext()->gui;
	}

	/**
	 * @return OIDplusAuthUtils
	 */
	public static function authUtils(): OIDplusAuthUtils {
		if (is_null(self::getCurrentContext()->authUtils)) {
			self::getCurrentContext()->authUtils = new OIDplusAuthUtils();
		}
		return self::getCurrentContext()->authUtils;
	}

	/**
	 * @return OIDplusMailUtils
	 */
	public static function mailUtils(): OIDplusMailUtils {
		if (is_null(self::getCurrentContext()->mailUtils)) {
			self::getCurrentContext()->mailUtils = new OIDplusMailUtils();
		}
		return self::getCurrentContext()->mailUtils;
	}

	/**
	 * @return OIDplusCookieUtils
	 */
	public static function cookieUtils(): OIDplusCookieUtils {
		if (is_null(self::getCurrentContext()->cookieUtils)) {
			self::getCurrentContext()->cookieUtils = new OIDplusCookieUtils();
		}
		return self::getCurrentContext()->cookieUtils;
	}

	/**
	 * @return OIDplusMenuUtils
	 */
	public static function menuUtils(): OIDplusMenuUtils {
		if (is_null(self::getCurrentContext()->menuUtils)) {
			self::getCurrentContext()->menuUtils = new OIDplusMenuUtils();
		}
		return self::getCurrentContext()->menuUtils;
	}

	/**
	 * @return OIDplusLogger
	 */
	public static function logger(): OIDplusLogger {
		if (is_null(self::getCurrentContext()->logger)) {
			self::getCurrentContext()->logger = new OIDplusLogger();
		}
		return self::getCurrentContext()->logger;
	}

	// --- SQL slang plugin

	/**
	 * @param OIDplusSqlSlangPlugin $plugin
	 * @return void
	 * @throws OIDplusException
	 */
	private static function registerSqlSlangPlugin(OIDplusSqlSlangPlugin $plugin): void {
		$name = $plugin::id();

		if ($name === '') {
			throw new OIDplusException(_L('Plugin %1 cannot be registered because it does not return a valid ID', $plugin->getPluginDirectory()));
		}

		if (isset(self::getCurrentContext()->sqlSlangPlugins[$name])) {
			$plugintype_hf = _L('SQL slang');
			throw new OIDplusException(_L('Multiple %1 plugins use the ID %2', $plugintype_hf, $name));
		}

		self::getCurrentContext()->sqlSlangPlugins[$name] = $plugin;
	}

	/**
	 * @return OIDplusSqlSlangPlugin[]
	 */
	public static function getSqlSlangPlugins(): array {
		return self::getCurrentContext()->sqlSlangPlugins;
	}

	/**
	 * @param string $id
	 * @return OIDplusSqlSlangPlugin|null
	 */
	public static function getSqlSlangPlugin(string $id): ?OIDplusSqlSlangPlugin {
		return self::getCurrentContext()->sqlSlangPlugins[$id] ?? null;
	}

	// --- Database plugin

	/**
	 * @param OIDplusDatabasePlugin $plugin
	 * @return void
	 * @throws OIDplusException
	 */
	private static function registerDatabasePlugin(OIDplusDatabasePlugin $plugin): void {
		$name = $plugin::id();

		if ($name === '') {
			throw new OIDplusException(_L('Plugin %1 cannot be registered because it does not return a valid ID', $plugin->getPluginDirectory()));
		}

		if (isset(self::getCurrentContext()->dbPlugins[$name])) {
			$plugintype_hf = _L('Database');
			throw new OIDplusException(_L('Multiple %1 plugins use the ID %2', $plugintype_hf, $name));
		}

		self::getCurrentContext()->dbPlugins[$name] = $plugin;
	}

	/**
	 * @return OIDplusDatabasePlugin[]
	 */
	public static function getDatabasePlugins(): array {
		return self::getCurrentContext()->dbPlugins;
	}

	/**
	 * @return OIDplusDatabasePlugin
	 * @throws OIDplusException
	 * @throws OIDplusConfigInitializationException
	 */
	public static function getActiveDatabasePlugin(): OIDplusDatabasePlugin {
		$db_plugin_name = self::baseConfig()->getValue('DATABASE_PLUGIN','');
		if ($db_plugin_name === '') {
			throw new OIDplusConfigInitializationException(_L('No database plugin selected in config file'));
		}
		foreach (self::getCurrentContext()->dbPlugins as $name => $plugin) {
			if (strtolower($name) == strtolower($db_plugin_name)) {
				return $plugin;
			}
		}
		throw new OIDplusConfigInitializationException(_L('Database plugin "%1" not found',$db_plugin_name));
	}

	/**
	 * @return OIDplusDatabaseConnection
	 * @throws OIDplusException
	 * @throws OIDplusConfigInitializationException
	 */
	public static function db(): OIDplusDatabaseConnection {
		if (is_null(self::getCurrentContext()->dbMainSession)) {
			self::getCurrentContext()->dbMainSession = self::getActiveDatabasePlugin()->newConnection();
		}
		if (!self::getCurrentContext()->dbMainSession->isConnected()) self::getCurrentContext()->dbMainSession->connect();
		return self::getCurrentContext()->dbMainSession;
	}

	/**
	 * @return OIDplusDatabaseConnection
	 * @throws OIDplusException
	 * @throws OIDplusConfigInitializationException
	 */
	public static function dbIsolated(): OIDplusDatabaseConnection {
		if (is_null(self::getCurrentContext()->dbIsolatedSession)) {
			self::getCurrentContext()->dbIsolatedSession = self::getActiveDatabasePlugin()->newConnection();
		}
		if (!self::getCurrentContext()->dbIsolatedSession->isConnected()) self::getCurrentContext()->dbIsolatedSession->connect();
		return self::getCurrentContext()->dbIsolatedSession;
	}

	// --- CAPTCHA plugin

	/**
	 * @param OIDplusCaptchaPlugin $plugin
	 * @return void
	 * @throws OIDplusException
	 */
	private static function registerCaptchaPlugin(OIDplusCaptchaPlugin $plugin): void {
		$name = $plugin::id();

		if ($name === '') {
			throw new OIDplusException(_L('Plugin %1 cannot be registered because it does not return a valid ID', $plugin->getPluginDirectory()));
		}

		if (isset(self::getCurrentContext()->captchaPlugins[$name])) {
			$plugintype_hf = _L('CAPTCHA');
			throw new OIDplusException(_L('Multiple %1 plugins use the ID %2', $plugintype_hf, $name));
		}

		self::getCurrentContext()->captchaPlugins[$name] = $plugin;
	}

	/**
	 * @return OIDplusCaptchaPlugin[]
	 */
	public static function getCaptchaPlugins(): array {
		return self::getCurrentContext()->captchaPlugins;
	}

	/**
	 * @return string
	 * @throws OIDplusException
	 * @throws OIDplusConfigInitializationException
	 */
	public static function getActiveCaptchaPluginId(): string {
		$captcha_plugin_name = self::baseConfig()->getValue('CAPTCHA_PLUGIN', '');

		if (self::baseConfig()->getValue('RECAPTCHA_ENABLED', false) && ($captcha_plugin_name === '')) {
			// Legacy config file support!
			$captcha_plugin_name = 'reCAPTCHA';
		}

		if ($captcha_plugin_name === '') $captcha_plugin_name = 'None'; // the "None" plugin is a must-have!

		return $captcha_plugin_name;
	}

	/**
	 * @return OIDplusCaptchaPlugin
	 * @throws OIDplusException
	 * @throws OIDplusConfigInitializationException
	 */
	public static function getActiveCaptchaPlugin(): OIDplusCaptchaPlugin {
		$captcha_plugin_name = self::getActiveCaptchaPluginId();
		foreach (self::getCurrentContext()->captchaPlugins as $name => $plugin) {
			if (strtolower($name) == strtolower($captcha_plugin_name)) {
				return $plugin;
			}
		}
		throw new OIDplusConfigInitializationException(_L('CAPTCHA plugin "%1" not found',$captcha_plugin_name));
	}

	// --- Page plugin

	/**
	 * @param OIDplusPagePlugin $plugin
	 * @return void
	 */
	private static function registerPagePlugin(OIDplusPagePlugin $plugin): void {
		self::getCurrentContext()->pagePlugins[] = $plugin;
	}

	/**
	 * @return OIDplusPagePlugin[]
	 */
	public static function getPagePlugins(): array {
		return self::getCurrentContext()->pagePlugins;
	}

	// --- Auth plugin

	/**
	 * @param string $id
	 * @return OIDplusAuthPlugin|null
	 */
	public static function getAuthPluginById(string $id): ?OIDplusAuthPlugin {
		$plugins = self::getAuthPlugins();
		foreach ($plugins as $plugin) {
			if ($plugin->id() == $id) {
				return $plugin;
			}
		}
		return null;
	}

	/**
	 * @param string $plugin_id
	 * @param bool $must_hash
	 * @return void
	 * @throws OIDplusException
	 */
	private static function checkRaAuthPluginAvailable(string $plugin_id, bool $must_hash): void {
		$plugin = self::getAuthPluginById($plugin_id);
		if (is_null($plugin)) {
			throw new OIDplusException(_L('The auth plugin "%1" does not exist in plugin directory %2',$plugin_id,'[userdata_pub/]plugins/[vendorname]/auth/'));
		}

		$reason = '';
		if (!$plugin->availableForVerify($reason)) {
			throw new OIDplusException(trim(_L('The auth plugin "%1" is not available for password verification on this system.',$plugin_id).' '.$reason));
		}
		if ($must_hash && !$plugin->availableForHash($reason)) {
			throw new OIDplusException(trim(_L('The auth plugin "%1" is not available for hashing on this system.',$plugin_id).' '.$reason));
		}
	}

	/**
	 * @param bool $must_hash
	 * @return OIDplusAuthPlugin
	 * @throws OIDplusException
	 */
	public static function getDefaultRaAuthPlugin(bool $must_hash): OIDplusAuthPlugin {
		// 1. Priority: Use the auth plugin the user prefers
		$def_plugin_id = self::config()->getValue('default_ra_auth_method');
		if (trim($def_plugin_id) !== '') {
			self::checkRaAuthPluginAvailable($def_plugin_id, $must_hash);
			$tmp = self::getAuthPluginById($def_plugin_id);
			if ($tmp) return $tmp;
		}

		// 2. Priority: If empty (i.e. OIDplus may decide), choose the best ViaThinkSoft plugin that is supported on this system
		$preferred_auth_plugins = array(
			// Sorted by preference
			'A4_argon2',  // usually Salted Argon2id
			'A3_bcrypt',  // usually Salted BCrypt
			'A5_vts_mcf', // usually SHA3-512-HMAC
			'A6_crypt'    // usually Salted SHA512 with 5000 rounds
		);
		foreach ($preferred_auth_plugins as $plugin_id) {
			$plugin = self::getAuthPluginById($plugin_id);
			if (is_null($plugin)) continue;

			$reason = '';
			if (!$plugin->availableForHash($reason)) continue;
			if ($must_hash && !$plugin->availableForVerify($reason)) continue;
			return $plugin;
		}

		// 3. Priority: If nothing found, take the first found plugin
		$plugins = self::getAuthPlugins();
		foreach ($plugins as $plugin) {
			$reason = '';
			if (!$plugin->availableForHash($reason)) continue;
			if ($must_hash && !$plugin->availableForVerify($reason)) continue;
			return $plugin;
		}

		// 4. Priority: We must deny the creation of the password because we have no auth plugin!
		throw new OIDplusException(_L('Could not find a fitting auth plugin!'));
	}

	/**
	 * @param OIDplusAuthPlugin $plugin
	 * @return void
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	private static function registerAuthPlugin(OIDplusAuthPlugin $plugin): void {
		$reason = '';
		if (self::baseConfig()->getValue('DEBUG') && $plugin->availableForHash($reason) && $plugin->availableForVerify($reason)) {
			$password = generateRandomString(25);

			try {
				$authInfo = $plugin->generate($password);
			} catch (\Exception $e) {
				// This can happen when the AuthKey is too long for the database field
				// Note: The constructor and setters of OIDplusRAAuthInfo() already check for length and null/false values.
				throw new OIDplusException(_L('Auth plugin "%1" is erroneous: %2',basename($plugin->getPluginDirectory()),$e->getMessage()));
			}

			$authInfo_AuthKeyDiff = clone $authInfo;
			$authInfo_AuthKeyDiff->setAuthKey(strrev($authInfo_AuthKeyDiff->getAuthKey()));

			if ((!$plugin->verify($authInfo,$password)) ||
				($plugin->verify($authInfo_AuthKeyDiff,$password)) ||
				($plugin->verify($authInfo,$password.'x'))) {
				throw new OIDplusException(_L('Auth plugin "%1" is erroneous: Generate/Verify self-test failed',basename($plugin->getPluginDirectory())));
			}
		}

		self::getCurrentContext()->authPlugins[] = $plugin;
	}

	/**
	 * @return OIDplusAuthPlugin[]
	 */
	public static function getAuthPlugins(): array {
		return self::getCurrentContext()->authPlugins;
	}

	// --- Language plugin

	/**
	 * @param OIDplusLanguagePlugin $plugin
	 * @return void
	 */
	private static function registerLanguagePlugin(OIDplusLanguagePlugin $plugin): void {
		self::getCurrentContext()->languagePlugins[] = $plugin;
	}

	/**
	 * @return OIDplusLanguagePlugin[]
	 */
	public static function getLanguagePlugins(): array {
		return self::getCurrentContext()->languagePlugins;
	}

	// --- Design plugin

	/**
	 * @param OIDplusDesignPlugin $plugin
	 * @return void
	 */
	private static function registerDesignPlugin(OIDplusDesignPlugin $plugin): void {
		self::getCurrentContext()->designPlugins[] = $plugin;
	}

	/**
	 * @return OIDplusDesignPlugin[]
	 */
	public static function getDesignPlugins(): array {
		return self::getCurrentContext()->designPlugins;
	}

	/**
	 * @return OIDplusDesignPlugin|null
	 * @throws OIDplusException
	 */
	public static function getActiveDesignPlugin(): ?OIDplusDesignPlugin {
		$plugins = self::getDesignPlugins();
		foreach ($plugins as $plugin) {
			if ($plugin->id() == self::config()->getValue('design','default')) {
				return $plugin;
			}
		}
		return null;
	}

	// --- Logger plugin

	/**
	 * @param OIDplusLoggerPlugin $plugin
	 * @return void
	 */
	private static function registerLoggerPlugin(OIDplusLoggerPlugin $plugin): void {
		self::getCurrentContext()->loggerPlugins[] = $plugin;
	}

	/**
	 * @return OIDplusLoggerPlugin[]
	 */
	public static function getLoggerPlugins(): array {
		return self::getCurrentContext()->loggerPlugins;
	}

	// --- Object type plugin

	/**
	 * @param OIDplusObjectTypePlugin $plugin
	 * @return void
	 * @throws OIDplusException
	 */
	private static function registerObjectTypePlugin(OIDplusObjectTypePlugin $plugin): void {
		self::getCurrentContext()->objectTypePlugins[] = $plugin;

		if (self::baseConfig()->getValue('DEBUG')) {
			// Avoid a namespace hash conflict of the OIDplus Information Object Custom UUIDs
			// see here https://github.com/danielmarschall/oidplus/blob/master/doc/oidplus_custom_guid.md
			if (!str_starts_with($plugin->getManifest()->getOid(), '1.3.6.1.4.1.37476.2.5.2.4.8.')) {
				$coll = [];
				for ($i = 1; $i <= 185; $i++) {
					// No conflict between ViaThinkSoft OIDs .1 till .185
					$block4 = dechex(hexdec(substr(sha1('1.3.6.1.4.1.37476.2.5.2.4.8.'.$i), -4)) & 0x3FFF | 0x8000);
					$coll[] = $block4;
				}
				for ($i=0; $i<=0xF; $i++) {
					// 0x8000 - 0x800F are used by the system
					$coll[] = dechex(0x8000 + $i);
				}
				$block4 = dechex(hexdec(substr(sha1($plugin->getManifest()->getOid()), -4)) & 0x3FFF | 0x8000);
				if (in_array($block4, $coll)) {
					throw new OIDplusException(_L("A third-party vendor object type plugin with OID %1 has a hash-conflict with a ViaThinkSoft plugin. Please recommend to the developer to pick a different OID for their plugin. More information here: %2",$plugin->getManifest()->getOid(),'https://github.com/danielmarschall/oidplus/blob/master/doc/oidplus_custom_guid.md'));
				}
			}
		}

		$ot = $plugin::getObjectTypeClassName();
		self::registerObjectType($ot);
	}

	/**
	 * @param string|OIDplusObject $ot Object type class name (OIDplusObject)
	 * @return void
	 * @throws OIDplusException
	 */
	private static function registerObjectType(/*string|OIDplusObject*/ $ot): void {
		$ns = $ot::ns();
		if (empty($ns)) throw new OIDplusException(_L('ObjectType plugin %1 is erroneous: Namespace must not be empty',$ot));

		// Currently, we must enforce that namespaces in objectType plugins are lowercase, because prefilterQuery() makes all namespaces lowercase and the DBMS should be case-sensitive
		if ($ns != strtolower($ns)) throw new OIDplusException(_L('ObjectType plugin %1 is erroneous: Namespace %2 must be lower-case',$ot,$ns));

		$root = $ot::root();
		if (!str_starts_with($root,$ns.':')) throw new OIDplusException(_L('ObjectType plugin %1 is erroneous: Root node (%2) is in wrong namespace (needs starts with %3)!',$ot,$root,$ns.':'));

		$ns_found = false;
		foreach (array_merge(self::getEnabledObjectTypes(), self::getDisabledObjectTypes()) as $test_ot) {
			if ($test_ot::ns() == $ns) {
				$ns_found = true;
				break;
			}
		}
		if ($ns_found) {
			throw new OIDplusException(_L('Attention: Two objectType plugins use the same namespace "%1"!',$ns));
		}

		$init = self::config()->getValue("objecttypes_initialized");
		$init_ary = empty($init) ? array() : explode(';', $init);
		$init_ary = array_map('trim', $init_ary);

		$enabled = self::config()->getValue("objecttypes_enabled");
		$enabled_ary = empty($enabled) ? array() : explode(';', $enabled);
		$enabled_ary = array_map('trim', $enabled_ary);

		if (in_array($ns, $enabled_ary)) {
			// If it is in the list of enabled object types, it is enabled (obviously)
			$do_enable = true;
		} else {
			if (!self::config()->getValue('oobe_objects_done')) {
				// If the OOBE wizard is NOT done, then just enable the "oid" object type by default
				$do_enable = $ns == 'oid';
			} else {
				// If the OOBE wizard was done (once), then
				// we will enable all object types which were never initialized
				// (i.e. a plugin folder was freshly added)
				$do_enable = !in_array($ns, $init_ary);
			}
		}

		if ($do_enable) {
			self::getCurrentContext()->enabledObjectTypes[] = $ot;
			usort(self::getCurrentContext()->enabledObjectTypes, function($a, $b) {
				$enabled = self::config()->getValue("objecttypes_enabled");
				$enabled_ary = explode(';', $enabled);

				$idx_a = array_search($a::ns(), $enabled_ary);
				$idx_b = array_search($b::ns(), $enabled_ary);

				if ($idx_a == $idx_b) return 0;
				return ($idx_a > $idx_b) ? +1 : -1;
			});
		} else {
			self::getCurrentContext()->disabledObjectTypes[] = $ot;
		}

		if (!in_array($ns, $init_ary)) {
			// Was never initialized before, so we add it to the list of enabled object types once

			if ($do_enable) {
				$enabled_ary[] = $ns;
				// Important: Don't validate the input, because the other object types might not be initialized yet! So use setValueNoCallback() instead setValue().
				self::config()->setValueNoCallback("objecttypes_enabled", implode(';', $enabled_ary));
			}

			$init_ary[] = $ns;
			self::config()->setValue("objecttypes_initialized", implode(';', $init_ary));
		}
	}

	/**
	 * @return OIDplusObjectTypePlugin[]
	 */
	public static function getObjectTypePlugins(): array {
		return self::getCurrentContext()->objectTypePlugins;
	}

	/**
	 * @return OIDplusObjectTypePlugin[]
	 */
	public static function getObjectTypePluginsEnabled(): array {
		$res = array();
		foreach (self::getCurrentContext()->objectTypePlugins as $plugin) {
			$ot = $plugin::getObjectTypeClassName();
			if (in_array($ot, self::getCurrentContext()->enabledObjectTypes)) $res[] = $plugin;
		}
		return $res;
	}

	/**
	 * @return OIDplusObjectTypePlugin[]
	 */
	public static function getObjectTypePluginsDisabled(): array {
		$res = array();
		foreach (self::getCurrentContext()->objectTypePlugins as $plugin) {
			$ot = $plugin::getObjectTypeClassName();
			if (in_array($ot, self::getCurrentContext()->disabledObjectTypes)) $res[] = $plugin;
		}
		return $res;
	}

	/**
	 * @return string[]|OIDplusObject[] Classname of a OIDplusObject class
	 */
	public static function getEnabledObjectTypes(): array {
		return self::getCurrentContext()->enabledObjectTypes;
	}

	/**
	 * @return string[]|OIDplusObject[] Classname of a OIDplusObject class
	 */
	public static function getDisabledObjectTypes(): array {
		return self::getCurrentContext()->disabledObjectTypes;
	}

	// --- Plugin handling functions

	/**
	 * @return OIDplusPlugin[]
	 */
	public static function getAllPlugins(): array {
		// TODO: such methods must throw an exception if self::init() was not called previously!
		$res = array();
		$res = array_merge($res, self::getCurrentContext()->pagePlugins);
		$res = array_merge($res, self::getCurrentContext()->authPlugins);
		$res = array_merge($res, self::getCurrentContext()->loggerPlugins);
		$res = array_merge($res, self::getCurrentContext()->objectTypePlugins);
		$res = array_merge($res, self::getCurrentContext()->dbPlugins);
		$res = array_merge($res, self::getCurrentContext()->captchaPlugins);
		$res = array_merge($res, self::getCurrentContext()->sqlSlangPlugins);
		$res = array_merge($res, self::getCurrentContext()->languagePlugins);
		return array_merge($res, self::getCurrentContext()->designPlugins);
	}

	/**
	 * @param string $oid
	 * @return OIDplusPlugin|null
	 */
	public static function getPluginByOid(string $oid): ?OIDplusPlugin {
		$plugins = self::getAllPlugins();
		foreach ($plugins as $plugin) {
			if (oid_dotnotation_equal($plugin->getManifest()->getOid(), $oid)) {
				return $plugin;
			}
		}
		return null;
	}

	/**
	 * @param string $classname
	 * @return OIDplusPlugin|null
	 */
	public static function getPluginByClassName(string $classname): ?OIDplusPlugin {
		$plugins = self::getAllPlugins();
		foreach ($plugins as $plugin) {
			if (get_class($plugin) === $classname) {
				return $plugin;
			}
		}
		return null;
	}

	/**
	 * Checks if the plugin is disabled
	 * @param string $oid OID of the plugin to be checked
	 * @return bool true if plugin is enabled, false if plugin is disabled
	 * @throws OIDplusException if the class name or config file (disabled setting) does not contain a namespace
	 */
	private static function pluginIsDisabled(string $oid): bool {
		return self::baseConfig()->getValue('DISABLE_PLUGIN_'.$oid, false);
	}

	/**
	 * @param string $pluginFolderMasks
	 * @param bool $flat
	 * @return OIDplusPluginManifest[]|array<string,array<string,OIDplusPluginManifest>>
	 * @throws OIDplusException
	 */
	public static function getAllPluginManifests(string $pluginFolderMasks='*', bool $flat=true): array {
		$out = array();
		// Note: glob() will sort by default, so we do not need a page priority attribute.
		//       So you just need to use a numeric plugin directory prefix (padded).
		$ary = array();
		foreach (explode(',',$pluginFolderMasks) as $pluginFolderMask) {
			$ary = array_merge($ary,
				glob(self::localpath().'plugins/'.'*'.'/'.$pluginFolderMask.'/'.'*'.'/manifest.json'),
				glob(self::getUserDataDir("plugins", true).'*'.'/'.$pluginFolderMask.'/'.'*'.'/manifest.json')
			);
		}

		// Sort the plugins by their type and name, as if they would be in a single vendor-folder!
		uasort($ary, function($a,$b) { /* @phpstan-ignore-line */ // Weird message which I don't understand: Parameter #1 $array of function uasort contains unresolvable type.
			if ($a == $b) return 0;

			$a = str_replace('\\', '/', $a);
			$ary = explode('/',$a);
			$bry = explode('/',$b);

			// First sort by type (publicPage, auth, database, language, ...)
			$a_type = $ary[count($ary)-1-2];
			$b_type = $bry[count($bry)-1-2];
			if ($a_type < $b_type) return -1;
			if ($a_type > $b_type) return 1;

			// Then sort by name (090_login, 100_whois, etc.)
			$a_name = $ary[count($ary)-1-1];
			$b_name = $bry[count($bry)-1-1];
			if ($a_name < $b_name) return -1;
			if ($a_name > $b_name) return 1;

			// If it is still equal, then finally sort by vendorname
			$a_vendor = $ary[count($ary)-1-3];
			$b_vendor = $bry[count($bry)-1-3];
			if ($a_vendor < $b_vendor) return -1;
			if ($a_vendor > $b_vendor) return 1;
			return 0;
		});

		foreach ($ary as $ini) {
			if (!file_exists($ini)) continue;

			$manifest = new OIDplusPluginManifest();
			$manifest->loadManifest($ini);

			if (self::pluginIsDisabled($manifest->getOid())) continue;

			if ($flat) {
				$out[] = $manifest;
			} else {
				$vendor_folder = basename(dirname($ini, 3));
				$plugintype_folder = basename(dirname($ini, 2));
				$pluginname_folder = basename(dirname($ini));

				if (!isset($out[$plugintype_folder])) $out[$plugintype_folder] = array();
				if (!isset($out[$plugintype_folder][$vendor_folder])) $out[$plugintype_folder][$vendor_folder] = array();
				$out[$plugintype_folder][$vendor_folder][$pluginname_folder] = $manifest;
			}
		}
		return $out;
	}

	/**
	 * @param string|array $pluginDirName
	 * @param string $expectedPluginClass
	 * @param callable|null $registerCallback
	 * @return string[]
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 * @throws \ReflectionException
	 */
	public static function registerAllPlugins(/*string|array*/ $pluginDirName, string $expectedPluginClass, ?callable $registerCallback=null): array {
		$out = array();
		if (is_array($pluginDirName)) {
			$ary = array();
			foreach ($pluginDirName as $pluginDirName_) {
				$ary = array_merge($ary, self::getAllPluginManifests($pluginDirName_, false));
			}
		} else {
			$ary = self::getAllPluginManifests($pluginDirName, false); // note: does filter disabled plugins
		}
		$known_plugin_oids = array();
		foreach ($ary as $plugintype_folder => $bry) {
			foreach ($bry as $vendor_folder => $cry) {
				foreach ($cry as $pluginname_folder => $manifest) { /* @phpstan-ignore-line */
					assert($manifest instanceof OIDplusPluginManifest);
					$php_namespace = $manifest->getPhpNamespace();
					$php_class_name = $manifest->getPhpMainClass();
					$fq_classname = $php_namespace . $php_class_name;
					$full_plugin_dir = dirname($manifest->getManifestFile());
					$full_plugin_dir_rel = substr($full_plugin_dir, strlen(self::localpath()));

					// Before we load the plugin, we want to make some checks to confirm
					// that the plugin is working correctly.

					if (!$php_namespace || str_starts_with($php_namespace, "\\") || !str_ends_with($php_namespace, "\\")) {
						throw new OIDplusException(_L('Plugin "%1" is erroneous', $vendor_folder . '/' . $plugintype_folder . '/' . $pluginname_folder) . ': ' . _L('Manifest does not declare a PHP namespace include trailing backslash but no beginning backslash'));
					}
					if (!$php_class_name || str_contains($php_class_name, "\\")) {
						throw new OIDplusException(_L('Plugin "%1" is erroneous', $vendor_folder . '/' . $plugintype_folder . '/' . $pluginname_folder) . ': ' . _L('Manifest does not declare a PHP main class (without namespace)'));
					}

					// Do some basic checks on the plugin PHP main class
					if (!class_exists($fq_classname)) {
						throw new OIDplusException(_L('Plugin "%1" is erroneous', $vendor_folder . '/' . $plugintype_folder . '/' . $pluginname_folder) . ': ' . _L('Manifest declares PHP main class as "%1", but it could not be found', $fq_classname));
					}
					if (!is_subclass_of($fq_classname, $expectedPluginClass)) { /* @phpstan-ignore-line */ // Weird message which I don't understand: Call to function is_subclass_of() with class-string and string will always evaluate to true.
						throw new OIDplusException(_L('Plugin "%1" is erroneous', $vendor_folder . '/' . $plugintype_folder . '/' . $pluginname_folder) . ': ' . _L('Plugin main class "%1" is expected to be a subclass of "%2"', $fq_classname, $expectedPluginClass));
					}
					if (($fq_classname != $manifest->getTypeClass()) && (!is_subclass_of($fq_classname, $manifest->getTypeClass()))) { /* @phpstan-ignore-line */ // Weird message which I don't understand: Call to function is_subclass_of() with class-string and string will always evaluate to true.
						throw new OIDplusException(_L('Plugin "%1" is erroneous', $vendor_folder . '/' . $plugintype_folder . '/' . $pluginname_folder) . ': ' . _L('Plugin main class "%1" is expected to be a subclass of "%2", according to type declared in manifest', $fq_classname, $manifest->getTypeClass()));
					}
					if (($manifest->getTypeClass() != $expectedPluginClass) && (!is_subclass_of($manifest->getTypeClass(), $expectedPluginClass))) {
						throw new OIDplusException(_L('Plugin "%1" is erroneous', $vendor_folder . '/' . $plugintype_folder . '/' . $pluginname_folder) . ': ' . _L('Class declared in manifest is "%1" does not fit expected class for this plugin type "%2"', $manifest->getTypeClass(), $expectedPluginClass));
					}

					// Do some basic checks on the plugin OID
					$plugin_oid = $manifest->getOid();
					if (!$plugin_oid) {
						throw new OIDplusException(_L('Plugin "%1" is erroneous', $vendor_folder . '/' . $plugintype_folder . '/' . $pluginname_folder) . ': ' . _L('Does not have an OID'));
					}
					if (!oid_valid_dotnotation($plugin_oid, false, false, 2)) {
						throw new OIDplusException(_L('Plugin "%1" is erroneous', $vendor_folder . '/' . $plugintype_folder . '/' . $pluginname_folder) . ': ' . _L('Plugin OID "%1" is invalid (needs to be valid dot-notation)', $plugin_oid));
					}
					if (isset($known_plugin_oids[$plugin_oid])) {
						throw new OIDplusException(_L('Plugin "%1" is erroneous', $vendor_folder . '/' . $plugintype_folder . '/' . $pluginname_folder) . ': ' . _L('The OID "%1" is already used by the plugin "%2"', $plugin_oid, $known_plugin_oids[$plugin_oid]));
					}

					// Additional check: Are third-party plugins using ViaThinkSoft plugin folders, OIDs or class namespaces?
					$dir_is_viathinksoft =
						   str_starts_with($full_plugin_dir_rel, 'plugins/viathinksoft/')
						|| str_starts_with(strtolower($full_plugin_dir_rel), strtolower('plugins\\viathinksoft\\'))
						// TODO: hier auch tenant dir berücksichtigen! self::getUserDataDir("plugins", true)
						|| str_starts_with($full_plugin_dir_rel, 'userdata_pub/plugins/viathinksoft/')
						|| str_starts_with(strtolower($full_plugin_dir_rel), strtolower('userdata_pub\\plugins\\viathinksoft\\'));
					$oid_is_viathinksoft = str_starts_with($plugin_oid, '1.3.6.1.4.1.37476.2.5.2.4.'); // { iso(1) identified-organization(3) dod(6) internet(1) private(4) enterprise(1) 37476 products(2) oidplus(5) v2(2) plugins(4) }
					$class_is_viathinksoft = str_starts_with($fq_classname, 'ViaThinkSoft\\');
					if ($oid_is_viathinksoft != $class_is_viathinksoft) {
						throw new OIDplusException(_L('Plugin "%1" is erroneous', $full_plugin_dir_rel) . ': ' . _L('Third-party plugins must not use the ViaThinkSoft PHP namespace. Please use your own vendor namespace.'));
					}
					$plugin_is_viathinksoft = $oid_is_viathinksoft && $class_is_viathinksoft;
					if ($dir_is_viathinksoft != $plugin_is_viathinksoft) {
						throw new OIDplusException(_L('Plugin "%1" is misplaced', $full_plugin_dir_rel) . ': ' . _L('The plugin is in the wrong folder. The folder %1 can only be used by official ViaThinkSoft plugins', '[userdata_pub/]plugins/viathinksoft/'));
					}

					// Additional check: does the plugin define JS/CSS although it is not an interactive plugin type?
					$has_js = $manifest->getJSFiles();
					$has_css = $manifest->getCSSFiles();
					$is_interactive = in_array(basename($plugintype_folder), self::INTERACTIVE_PLUGIN_TYPES);
					$is_design = basename($plugintype_folder) === 'design';
					if (!$is_interactive && $has_js) {
						throw new OIDplusException(_L('Plugin "%1" is erroneous', $vendor_folder . '/' . $plugintype_folder . '/' . $pluginname_folder) . ': ' . _L('%1 files are included in the manifest XML, but this plugin type does not allow such files.', 'JavaScript'));
					}
					if (!$is_interactive && !$is_design && $has_css) {
						throw new OIDplusException(_L('Plugin "%1" is erroneous', $vendor_folder . '/' . $plugintype_folder . '/' . $pluginname_folder) . ': ' . _L('%1 files are included in the manifest XML, but this plugin type does not allow such files.', 'CSS'));
					}

					// Additional check: Check "Setup CSS" and "Setup JS" (Allowed for plugin types: database, captcha)
					$has_js_setup = $manifest->getJSFilesSetup();
					$has_css_setup = $manifest->getCSSFilesSetup();
					$is_database = basename($plugintype_folder) === 'database';
					$is_captcha = basename($plugintype_folder) === 'captcha';
					if (!$is_database && !$is_captcha && $has_js_setup) {
						throw new OIDplusException(_L('Plugin "%1" is erroneous', $vendor_folder . '/' . $plugintype_folder . '/' . $pluginname_folder) . ': ' . _L('%1 files are included in the manifest XML, but this plugin type does not allow such files.', 'Setup JavaScript'));
					}
					if (!$is_database && !$is_captcha && $has_css_setup) {
						throw new OIDplusException(_L('Plugin "%1" is erroneous', $vendor_folder . '/' . $plugintype_folder . '/' . $pluginname_folder) . ': ' . _L('%1 files are included in the manifest XML, but this plugin type does not allow such files.', 'Setup CSS'));
					}

					// Additional check: Are all CSS/JS files there?
					$tmp = $manifest->getManifestLinkedFiles();
					foreach ($tmp as $file) {
						if (!file_exists($file)) {
							throw new OIDplusException(_L('Plugin "%1" is erroneous', $vendor_folder . '/' . $plugintype_folder . '/' . $pluginname_folder) . ': ' . _L('File %1 was defined in manifest, but it is not existing', $file));
						}
					}

					// For the next check, we need an instance of the object
					$obj = new $fq_classname();

					// Now we can continue
					$known_plugin_oids[$plugin_oid] = $vendor_folder . '/' . $plugintype_folder . '/' . $pluginname_folder;
					$out[] = $fq_classname;
					if (!is_null($registerCallback)) {
						call_user_func($registerCallback, $obj);

						// Alternative approaches:
						//$registerCallback[0]::{$registerCallback[1]}($obj);
						// or:
						//forward_static_call($registerCallback, $obj);
					}
				}
			}
		}
		return $out;
	}

	// --- Initialization of OIDplus

	/**
	 * @param bool $html
	 * @param bool $keepBaseConfig
	 * @return void
	 * @throws OIDplusConfigInitializationException|OIDplusException|\ReflectionException
	 */
	public static function init(bool $html=true, bool $keepBaseConfig=true): void {

		// For test cases we need to be able to control base config and setting values manually, so $keepBaseConfig needs to be true
		$bakBaseConfig = $keepBaseConfig ? self::getCurrentContext()->baseConfig : null;

		// Makes sure that forceTenantSubDirName() can be used before init()
		$bakForcedTenantSubDirName = self::getCurrentContext()->forcedTenantSubDirName;

		// Reset internal state, so we can re-init verything if required
		self::setCurrentContext(new OIDplusContext());
		self::getCurrentContext()->baseConfig = $bakBaseConfig;
		self::getCurrentContext()->forcedTenantSubDirName = $bakForcedTenantSubDirName;

		// TODO: instead of having parameter $html=true|false, wouldn't it be better to have $type=html|css|js ?
		self::getCurrentContext()->html = $html;

		// Continue...

		self::baseConfig(); // this loads the base configuration located in userdata/baseconfig/config.inc.php (once!)
		// You can do changes to the configuration afterwards using self::baseConfig()->...

		// Register database types (highest priority)

		// SQL slangs

		self::registerAllPlugins('sqlSlang', OIDplusSqlSlangPlugin::class, array(self::class,'registerSqlSlangPlugin'));
		foreach (self::getSqlSlangPlugins() as $plugin) {
			$plugin->init($html);
		}

		// Database providers

		self::registerAllPlugins('database', OIDplusDatabasePlugin::class, array(self::class,'registerDatabasePlugin'));
		foreach (self::getDatabasePlugins() as $plugin) {
			$plugin->init($html);
		}

		// Do redirect stuff etc.

		self::isSslAvailable(); // This function does automatic redirects

		// Construct the configuration manager

		self::config(); // During the construction, various system settings are prepared if required

		// Initialize public / private keys

		self::getPkiStatus(true);

		// Register non-DB plugins

		self::registerAllPlugins(array('publicPages', 'raPages', 'adminPages'), OIDplusPagePlugin::class, array(self::class,'registerPagePlugin'));
		self::registerAllPlugins('auth', OIDplusAuthPlugin::class, array(self::class,'registerAuthPlugin'));
		self::registerAllPlugins('logger', OIDplusLoggerPlugin::class, array(self::class,'registerLoggerPlugin'));
		self::logger()->reLogMissing(); // Some previous plugins might have tried to log. Repeat that now.
		self::registerAllPlugins('objectTypes', OIDplusObjectTypePlugin::class, array(self::class,'registerObjectTypePlugin'));
		self::registerAllPlugins('language', OIDplusLanguagePlugin::class, array(self::class,'registerLanguagePlugin'));
		self::registerAllPlugins('design', OIDplusDesignPlugin::class, array(self::class,'registerDesignPlugin'));
		self::registerAllPlugins('captcha', OIDplusCaptchaPlugin::class, array(self::class,'registerCaptchaPlugin'));

		// Initialize non-DB plugins

		foreach (self::getPagePlugins() as $plugin) {
			$plugin->init($html);
		}
		foreach (self::getAuthPlugins() as $plugin) {
			$plugin->init($html);
		}
		foreach (self::getLoggerPlugins() as $plugin) {
			$plugin->init($html);
		}
		foreach (self::getObjectTypePluginsEnabled() as $plugin) {
			$plugin->init($html);
		}
		foreach (self::getLanguagePlugins() as $plugin) {
			$plugin->init($html);
		}
		foreach (self::getDesignPlugins() as $plugin) {
			$plugin->init($html);
		}
		foreach (self::getCaptchaPlugins() as $plugin) {
			$plugin->init($html);
		}

		if (PHP_SAPI != 'cli') {

			// Prepare some security related response headers (default values)

			$content_language =
				strtolower(substr(self::getCurrentLang(),0,2)) . '-' .
				strtoupper(substr(self::getCurrentLang(),2,2)); // e.g. 'en-US'

			$http_headers = array(
				"X-Content-Type-Options" => "nosniff",
				"X-XSS-Protection" => "1; mode=block",
				"X-Frame-Options" => "SAMEORIGIN",
				"Referrer-Policy" => array(
					"no-referrer-when-downgrade"
				),
				"Cache-Control" => array(
					"no-cache",
					"no-store",
					"must-revalidate"
				),
				"Pragma" => "no-cache",
				"Content-Language" => $content_language,
				"Expires" => "0",
				"Content-Security-Policy" => array(
					// see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy

					// --- Fetch directives ---
					"child-src" => array(
						"'self'",
						"blob:"
					),
					"connect-src" => array(
						"'self'",
						"blob:"
					),
					"default-src" => array(
						"'self'",
						"blob:",
						"https://cdnjs.cloudflare.com/"
					),
					"font-src" => array(
						"'self'",
						"blob:"
					),
					"frame-src" => array(
						"'self'",
						"blob:"
					),
					"img-src" => array(
						"blob:",
						"data:",
						"http:",
						"https:"
					),
					"manifest-src" => array(
						"'self'",
						"blob:"
					),
					"media-src" => array(
						"'self'",
						"blob:"
					),
					"object-src" => array(
						"'none'"
					),
					"script-src" => array(
						"'self'",
						"'unsafe-inline'",
						"'unsafe-eval'",
						"blob:",
						"https://cdnjs.cloudflare.com/",
						"https://polyfill.io/"
					),
					// script-src-elem not used
					// script-src-attr not used
					"style-src" => array(
						"'self'",
						"'unsafe-inline'",
						"https://cdnjs.cloudflare.com/"
					),
					// style-src-elem not used
					// style-src-attr not used
					"worker-src" => array(
						"'self'",
						"blob:"
					),

					// --- Navigation directives ---
					"frame-ancestors" => array(
						"'none'"
					),
				)
			);

			// Give plugins the opportunity to manipulate/extend the headers

			foreach (self::getSqlSlangPlugins() as $plugin) {
				$plugin->httpHeaderCheck($http_headers);
			}
			//foreach (self::getDatabasePlugins() as $plugin) {
			if ($plugin = self::getActiveDatabasePlugin()) {
				$plugin->httpHeaderCheck($http_headers);
			}
			foreach (self::getPagePlugins() as $plugin) {
				$plugin->httpHeaderCheck($http_headers);
			}
			foreach (self::getAuthPlugins() as $plugin) {
				$plugin->httpHeaderCheck($http_headers);
			}
			foreach (self::getLoggerPlugins() as $plugin) {
				$plugin->httpHeaderCheck($http_headers);
			}
			foreach (self::getObjectTypePlugins() as $plugin) {
				$plugin->httpHeaderCheck($http_headers);
			}
			foreach (self::getLanguagePlugins() as $plugin) {
				$plugin->httpHeaderCheck($http_headers);
			}
			foreach (self::getDesignPlugins() as $plugin) {
				$plugin->httpHeaderCheck($http_headers);
			}
			//foreach (self::getCaptchaPlugins() as $plugin) {
			if ($plugin = self::getActiveCaptchaPlugin()) {
				$plugin->httpHeaderCheck($http_headers);
			}

			// Prepare to send the headers to the client
			// The headers are sent automatically when the first output comes or the script ends

			foreach ($http_headers as $name => $val) {

				// Plugins can remove standard OIDplus headers by setting the value to null.
				if (is_null($val)) continue; /* @phpstan-ignore-line */

				// Some headers can be written as arrays to make it easier for plugin authors
				// to manipulate/extend the contents.
				if (is_array($val)) { /* @phpstan-ignore-line */
					if ((strtolower($name) == 'cache-control') ||
						(strtolower($name) == 'referrer-policy'))
					{
						if (count($val) == 0) continue;
						$val = implode(', ', $val);
					} else if (strtolower($name) == 'content-security-policy') {
						if (count($val) == 0) continue;
						foreach ($val as $tmp1 => &$tmp2) {
							$tmp2 = array_unique($tmp2);
							$tmp2 = $tmp1.' '.implode(' ', $tmp2);
						}
						$val = implode('; ', $val);
					} else {
						throw new OIDplusException(_L('HTTP header "%1" cannot be written as array. A newly installed plugin is probably misusing the method "%2".',$name,'httpHeaderCheck'));
					}
				}

				if (is_string($val)) { /* @phpstan-ignore-line */
					@header("$name: $val");
				}
			}

		} // endif (PHP_SAPI != 'cli')

		// Initialize other stuff (i.e. things which require the logger!)

		self::recognizeSystemUrl(); // Make sure "last_known_system_url" is set
		self::recognizeVersion(); // Make sure "last_known_version" is set and a log entry is created
	}

	// --- System URL, System ID, PKI, and other functions

	/**
	 * @return void
	 */
	private static function recognizeSystemUrl(): void {
		try {
			$url = self::webpath(null,self::PATH_ABSOLUTE_CANONICAL);
			self::config()->setValue('last_known_system_url', $url);
		} catch (\Exception $e) {
		}
	}

	/**
	 * @return false|int
	 */
	private static function getExecutingScriptPathDepth()/*: false|int*/ {
		if (PHP_SAPI == 'cli') {
			global $argv;
			$test_dir = dirname(realpath($argv[0]));
		} else {
			if (!isset($_SERVER["SCRIPT_FILENAME"])) return false;
			$test_dir = dirname($_SERVER['SCRIPT_FILENAME']);
		}
		$test_dir = str_replace('\\', '/', $test_dir);
		$steps_up = 0;
		while (!file_exists($test_dir.'/oidplus.min.css.php')) { // We just assume that only the OIDplus base directory contains "oidplus.min.css.php" and not any subordinate directory!
			$test_dir = dirname($test_dir);
			$steps_up++;
			if ($steps_up == 1000) return false; // to make sure there will never be an infinite loop
		}
		return $steps_up;
	}

	/**
	 * @return bool
	 */
	public static function isSSL(): bool {
		return isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on');
	}

	/**
	 * Returns the URL of the system.
	 * @param int $mode If true or self::PATH_RELATIVE, the returning path is relative to the currently executed
	 *                  PHP script (i.e. index.php , not the plugin PHP script!). False or self::PATH_ABSOLUTE is
	 *                  results in an absolute URL. self::PATH_ABSOLUTE_CANONICAL is an absolute URL,
	 *                  but a canonical path (set by base config setting CANONICAL_SYSTEM_URL) is preferred.
	 * @return string|false The URL, with guaranteed trailing path delimiter for directories
	 * @throws OIDplusException
	 */
	private static function getSystemUrl(int $mode)/*: string|false*/ {
		if ($mode === self::PATH_RELATIVE) {
			$steps_up = self::getExecutingScriptPathDepth();
			if ($steps_up === false) {
				return false;
			} else {
				return str_repeat('../', $steps_up);
			}
		} else {
			if ($mode === self::PATH_ABSOLUTE_CANONICAL) {
				$tmp = self::baseConfig()->getValue('CANONICAL_SYSTEM_URL', '');
				if ($tmp) {
					return rtrim($tmp,'/').'/';
				}
			}

			if (PHP_SAPI == 'cli') {
				try {
					return self::config()->getValue('last_known_system_url', false);
				} catch (\Exception $e) {
					return false;
				}
			} else {
				// First, try to find out how many levels we need to go up
				$steps_up = self::getExecutingScriptPathDepth();

				// Then go up these amount of levels, based on SCRIPT_NAME/argv[0]
				$res = dirname($_SERVER['SCRIPT_NAME'].'index.php'); // This fake 'index.php' ensures that SCRIPT_NAME does not end with '/', which would make dirname() fail
				for ($i=0; $i<$steps_up; $i++) {
					$res = dirname($res);
				}
				$res = str_replace('\\', '/', $res);
				if ($res == '/') $res = '';

				// Add protocol and hostname
				$is_ssl = self::isSSL();
				$protocol = $is_ssl ? 'https' : 'http'; // do not translate
				$host = $_SERVER['HTTP_HOST']; // includes port if it is not 80/443

				return $protocol.'://'.$host.$res.'/';
			}
		}
	}

	/**
	 * @param string $pubKey
	 * @return false|string
	 */
	private static function pubKeyToRaw(string $pubKey)/*: false|string*/ {
		$m = array();
		if (preg_match('@BEGIN PUBLIC KEY\\-+([^\\-]+)\\-+END PUBLIC KEY@imU', $pubKey, $m)) {
			return base64_decode($m[1], false);
		}
		return false;
	}

	/**
	 * @param string $pubKey
	 * @return int|null
	 */
	private static function getSystemIdFromPubKey(string $pubKey): ?int {
		$rawData = self::pubKeyToRaw($pubKey);
		if ($rawData === false) return null;
		return smallhash($rawData);
	}

	/**
	 * @param string $pubKey
	 * @return false|string
	 */
	private static function getSystemGuidFromPubKey(string $pubKey)/*: false|int*/ {
		/*
		$rawData = self::pubKeyToRaw($pubKey);
		if ($rawData === false) return false;
		$normalizedBase64 = base64_encode($rawData);
		return gen_uuid_sha1_namebased(self::UUID_NAMEBASED_NS_Base64PubKey, $normalizedBase64);
		*/

		// https://github.com/danielmarschall/oidplus/blob/master/doc/oidplus_custom_guid.md
		$sysid = self::getSystemId(false);
		$sysid_int = $sysid ? $sysid : 0;
		return gen_uuid_v8(
			dechex($sysid_int),
			dechex(0), // Creation time of the system unknown
			dechex(0), // Reserved
			dechex(0), // 0=System, otherwise Object Namespace
			sha1('') // Objectname, empty string for system UUID
		);
	}

	/**
	 * @param bool $oid
	 * @return false|string
	 * @throws OIDplusException
	 */
	public static function getSystemId(bool $oid=false)/*: false|string*/ {
		if (!is_null(self::getCurrentContext()->system_id_cache)) {
			$out = self::getCurrentContext()->system_id_cache;
		} else {
			$out = false;
			if (self::getPkiStatus(true)) {
				$pubKey = self::getSystemPublicKey();
				$out = self::getSystemIdFromPubKey($pubKey);
			}
			self::getCurrentContext()->system_id_cache = $out;
		}
		if (!$out) return false;
		return ($oid ? '1.3.6.1.4.1.37476.30.9.' : '').$out;
	}

	/**
	 * @return false|string
	 * @throws OIDplusException
	 */
	public static function getSystemGuid()/*: false|string*/ {
		if (!is_null(self::getCurrentContext()->system_guid_cache)) {
			$out = self::getCurrentContext()->system_guid_cache;
		} else {
			$out = false;

			if (self::getPkiStatus(true)) {
				$pubKey = self::getSystemPublicKey();
				$out = self::getSystemGuidFromPubKey($pubKey);
			}
			self::getCurrentContext()->system_guid_cache = $out;
		}
		if (!$out) return false;
		return $out;
	}

	/**
	 * @return array|string
	 */
	public static function getOpenSslCnf()/*: array|string*/ {
		// The following functions need a config file, otherway they don't work
		// - openssl_csr_new
		// - openssl_csr_sign
		// - openssl_pkey_export
		// - openssl_pkey_export_to_file
		// - openssl_pkey_new
		$tmp = @getenv('OPENSSL_CONF');
		if ($tmp && file_exists($tmp)) return $tmp;

		// OpenSSL in XAMPP does not work OOBE, since the OPENSSL_CONF is
		// C:/xampp/apache/bin/openssl.cnf and not C:/xampp/apache/conf/openssl.cnf
		// Bug reports are more than 10 years old and nobody cares...
		// Use our own config file
		return __DIR__.'/../../vendor/phpseclib/phpseclib/phpseclib/openssl.cnf';
	}

	/**
	 * @param string $pubKey
	 * @return string
	 */
	private static function getPrivKeyPassphraseFilename(string $pubKey): string {
		$systemid = self::getSystemIdFromPubKey($pubKey);

		$oldfile1 = self::localpath() . 'userdata/privkey_secret.php'; // backwards compatibility
		$oldfile2 = realpath(self::getUserDataDir("secret").'../').'/privkey_secret.php'; // when userdata is copied to a new tenant
		$oldfile3 = self::getUserDataDir("secret") . 'privkey_secret.php';
		$newfile = self::getUserDataDir("secret") . 'privkey_secret_'.$systemid.'.php';

		$file_to_choose = $newfile;
		if (file_exists($oldfile3) && !file_exists($newfile)) {
			@rename($oldfile3, $newfile);
			if (!file_exists($newfile)) $file_to_choose = $oldfile3;
		}
		else if (file_exists($oldfile2) && !file_exists($newfile)) {
			@rename($oldfile2, $newfile);
			if (!file_exists($newfile)) $file_to_choose = $oldfile2;
		}
		else if (file_exists($oldfile1) && !file_exists($newfile)) {
			@rename($oldfile1, $newfile);
			if (!file_exists($newfile)) $file_to_choose = $oldfile1;
		}
		return $file_to_choose;
	}

	/**
	 * @param string $pubKey
	 * @return void
	 */
	private static function tryCreatePrivKeyPassphrase(string $pubKey): void {
		$file = self::getPrivKeyPassphraseFilename($pubKey);

		$passphrase = generateRandomString(64);
		$cont = "<?php\n";
		$cont .= "// ATTENTION! This file was automatically generated by OIDplus to encrypt the private key\n";
		$cont .= "// that is located in your database configuration table. DO NOT ALTER OR DELETE THIS FILE,\n";
		$cont .= "// otherwise you will lose your OIDplus System-ID and all services connected with it!\n";
		$cont .= "// If multiple systems access the same database, then this file must be synchronous\n";
		$cont .= "// between all systems, otherwise you will lose your system ID, too!\n";
		$cont .= "\$passphrase = '$passphrase';\n";
		$cont .= "// End of file\n";

		@file_put_contents($file, $cont);
	}

	/**
	 * @param string $pubKey
	 * @return string|false
	 */
	private static function getPrivKeyPassphrase(string $pubKey)/*: false|string*/ {
		$file = self::getPrivKeyPassphraseFilename($pubKey);
		if (!file_exists($file)) return false;
		$cont = file_get_contents($file);
		$m = array();
		if (!preg_match("@'(.+)'@isU", $cont, $m)) return false;
		return $m[1];
	}

	/**
	 * Returns the private key of the system
	 * @param bool $auto_decrypt Try to decrypt the key in case it is encrypted.
	 * @return string|false
	 * @throws OIDplusException
	 */
	public static function getSystemPrivateKey(bool $auto_decrypt=true)/*: false|string*/ {
		$pubKey = self::config()->getValue('oidplus_public_key');
		if ($pubKey == '') return false;

		$privKey = self::config()->getValue('oidplus_private_key');
		if ($privKey == '') return false;

		if (is_privatekey_encrypted($privKey)) {
			if (!$auto_decrypt) {
				return false;
			}

			$passphrase = self::getPrivKeyPassphrase($pubKey);
			if ($passphrase === false) {
				return false;
			}

			$privKey = decrypt_private_key($privKey, $passphrase);
			if (($privKey === false) || is_privatekey_encrypted($privKey)) {
				// This can happen if the key file has vanished or decryption failed because of another reason
				return false;
			}
		}

		return $privKey;
	}

	/**
	 * @return string|false
	 * @throws OIDplusException
	 */
	public static function getSystemPublicKey()/*: false|string*/ {
		$pubKey = self::config()->getValue('oidplus_public_key');
		if ($pubKey == '') return false;
		return $pubKey;
	}

	/**
	 * @param bool $try_generate
	 * @return bool
	 * @throws OIDplusException
	 */
	public static function getPkiStatus(bool $try_generate=false): bool {
		if (!function_exists('openssl_pkey_new')) return false;

		if (basename($_SERVER['SCRIPT_NAME']) == 'test_database_plugins.php') return false; // database switching will destroy keys because of the secret file

		if ($try_generate) {
			// For debug purposes: Invalidate current key once:
			//self::config()->setValue('oidplus_private_key', '');

			$privKey = self::getSystemPrivateKey();
			$pubKey = self::getSystemPublicKey();
			if (!verify_private_public_key($privKey, $pubKey)) {

				if ($pubKey) {
					throw new OIDplusException(_L("The Private/Public key-pair is broken or cannot be decrypted! Please check if the file %1 is existing and OK! In case this file is missing or corrupted and cannot be recovered, you need to reset your private/public key-pair. Do so by removing the settings %2 and %3 in the OIDplus configuration table in the database. This will invoke a regeneration of the private/public key-pair (you will receive a new system ID).", self::getPrivKeyPassphraseFilename($pubKey), 'oidplus_public_key', 'oidplus_private_key'));
					//self::logger()->log("V2:[CRIT]A", "The private/public key-pair is broken. A new key-pair will now be generated for your system. Your System-ID will change.");
				}

				$pkey_config = array(
					"digest_alg" => "sha512",
					"private_key_bits" => defined('OPENSSL_SUPPLEMENT') ? 1024 : 2048, // openssl_supplement.inc.php is based on phpseclib, which is very slow. So we use 1024 bits instead of 2048 bits
					"private_key_type" => OPENSSL_KEYTYPE_RSA,
					"config" => self::getOpenSslCnf()
				);

				// Create the private and public key
				$res = openssl_pkey_new($pkey_config);
				if ($res === false) return false;

				// Extract the private key from $res to $privKey
				if (openssl_pkey_export($res, $privKey, null, $pkey_config) === false) return false;

				// Extract the public key from $res to $pubKey
				$tmp = openssl_pkey_get_details($res);
				if ($tmp === false) return false;
				$pubKey = $tmp["key"];

				// encrypt new keys using a passphrase stored in a secret file
				self::tryCreatePrivKeyPassphrase($pubKey); // *try* (re)generate this file
				$passphrase = self::getPrivKeyPassphrase($pubKey);
				if ($passphrase !== false) {
					$privKey = encrypt_private_key($privKey, $passphrase);
				}

				// Calculate the system ID from the public key
				$system_id = self::getSystemIdFromPubKey($pubKey);
				if ($system_id !== null) {
					// Save the key pair to database
					self::config()->setValue('oidplus_private_key', $privKey);
					self::config()->setValue('oidplus_public_key', $pubKey);

					// Log the new system ID
					self::logger()->log("V2:[INFO]A", "A new private/public key-pair for your system had been generated. Your SystemID is now %1", $system_id);
				}
			} else {
				$passphrase = self::getPrivKeyPassphrase($pubKey);
				$rawPrivKey = self::config()->getValue('oidplus_private_key');
				if (($passphrase === false) || !is_privatekey_encrypted($rawPrivKey)) {
					// Upgrade to new encrypted keys
					self::tryCreatePrivKeyPassphrase($pubKey); // *try* generate this file
					$passphrase = self::getPrivKeyPassphrase($pubKey);
					if ($passphrase !== false) {
						$privKey = encrypt_private_key($privKey, $passphrase);
						self::logger()->log("V2:[INFO]A", "The private/public key-pair has been upgraded to an encrypted key-pair. The key is saved in %1", self::getPrivKeyPassphraseFilename($pubKey));
						self::config()->setValue('oidplus_private_key', $privKey);
					}
				}
			}
		}

		$privKey = self::getSystemPrivateKey();
		$pubKey = self::getSystemPublicKey();
		return verify_private_public_key($privKey, $pubKey);
	}

	/**
	 * @return string
	 */
	public static function getInstallType(): string {
		$counter = 0;

		if ($svn_dir_exists = (self::findSvnFolder() !== false)) {
			$counter++;
		}
		if ($git_dir_exists = (self::findGitFolder() !== false)) {
			$counter++;
		}

		if ($counter > 1) {
			return 'ambigous'; // do not translate
		}
		else if ($svn_dir_exists) {
			return 'svn-wc'; // do not translate
		}
		else if ($git_dir_exists) {
			return 'git-wc'; // do not translate
		}
		else {
			return 'manual'; // do not translate
		}
	}

	/**
	 * @return void
	 */
	private static function recognizeVersion(): void {
		try {
			if ($ver_now = self::getVersion()) {
				$ver_prev = self::config()->getValue("last_known_version");
				if (($ver_prev) && ($ver_now != $ver_prev)) {
					// TODO: Problem: When the system was updated using SVN or GIT in the console, then the IP address of the next random visitor of the website is logged!
					//       Idea: Maybe we should extend the mask code with some kind of magic constant "[NO_IP]", so that no IP is logged for that event?
					self::logger()->log("V2:[INFO]A", "Detected system version change from '%1' to '%2'", $ver_prev, $ver_now);

					// Just to be sure, recanonize objects (we don't do it at every page visit due to performance reasons)
					self::recanonizeObjects();
				}
				self::config()->setValue("last_known_version", $ver_now);
			}
		} catch (\Exception $e) {
		}
	}

	/**
	 * @param string|array $infoFile Path or content of a changelog.json.php file (It must be in its source code form!)
	 * @param bool $allow_dev_version If set to false, then versions ending with "-dev" will be ignored
	 * @return false|string
	 */
	public static function getVersion(/*string|array*/ $infoFile = __DIR__.'/../../changelog.json.php', bool $allow_dev_version=true)/*: false|string*/ {
		if (is_array($infoFile)) {
			$json = $infoFile;
		} else {
			if (strlen($infoFile) > 255) {
				$cont = $infoFile;
			} else {
				if ((stripos($infoFile,'http://')===0) || (stripos($infoFile,'https://')===0)) {
					$cont = @url_get_contents($infoFile);
				} else {
					$cont = @file_get_contents($infoFile);
				}
			}
			if ($cont === false) return false;
			$json = @json_decode($cont, true);
			if ($json === null) return false;
		}
		$latest_version = false;
		foreach ($json as $v) {
			if (isset($v['version'])) {
				if (!$allow_dev_version && str_ends_with($v['version'],'-dev')) continue;
				$latest_version = $v['version'];
				break; // the first item is the latest version
			}
		}
		return $latest_version;

		/*

		$installType = self::getInstallType();

		if ($installType === 'svn-wc') {
			if (is_dir($svn_dir = self::findSvnFolder())) {
				$ver = get_svn_revision($svn_dir);
				if ($ver)
					return ($cachedVersion[$infoFile] = '2.0.0.'.$ver);
			}
		}

		if ($installType === 'git-wc') {
			$ver = self::getGitsvnRevision();
			if ($ver)
				return ($cachedVersion[$infoFile] = '2.0.0.'.$ver);
		}

		*/
	}

	const ENFORCE_SSL_NO   = 0;
	const ENFORCE_SSL_YES  = 1;
	const ENFORCE_SSL_AUTO = 2;

	/**
	 * @return bool
	 * @throws OIDplusException
	 * @throws OIDplusConfigInitializationException
	 */
	public static function isSslAvailable(): bool {
		if (!is_null(self::getCurrentContext()->sslAvailableCache)) return self::getCurrentContext()->sslAvailableCache;

		if (PHP_SAPI == 'cli') {
			self::getCurrentContext()->sslAvailableCache = false;
			return false;
		}

		$timeout = 2; // TODO: make configurable
		$already_ssl = self::isSSL();
		$ssl_port = 443; // TODO: make configurable
		$host_with_port = $_SERVER['HTTP_HOST'];
		$host_no_port = explode(':',$host_with_port)[0];
		$host_ssl = $host_no_port . ($ssl_port != 443 ? ':'.$ssl_port : ''); /* @phpstan-ignore-line */

		if ($already_ssl) {
			self::cookieUtils()->setcookie('SSL_CHECK', '1', 0, true/*allowJS*/, null/*samesite*/, true/*forceInsecure*/);
			self::getCurrentContext()->sslAvailableCache = true;
			return true;
		} else {
			if (isset($_COOKIE['SSL_CHECK']) && ($_COOKIE['SSL_CHECK'] == '1')) {
				// The cookie "SSL_CHECK" is set once a website was loaded with HTTPS.
				// It forces subsequent HTTP calls to redirect to HTTPS (like HSTS).
				// The reason is the following problem:
				// If you open the page with HTTPS first, then the CSRF token cookies will get the "secure" flag
				// If you open the page then with HTTP, the HTTP cannot access the secure CSRF cookies,
				// Chrome will then block "Set-Cookie" since the HTTP cookie would overwrite the HTTPS cookie.
				// So we MUST redirect, even if the Mode is ENFORCE_SSL_NO.
				// Note: SSL_CHECK is NOT a replacement for HSTS! You should use HSTS,
				//       because on there your browser ensures that HTTPS is called, before the server
				//       is even contacted (and therefore, no HTTP connection can be hacked).
				$mode = self::ENFORCE_SSL_YES;
			} else {
				$mode = self::baseConfig()->getValue('ENFORCE_SSL', self::ENFORCE_SSL_AUTO);
			}

			if ($mode == self::ENFORCE_SSL_NO) {
				// No SSL available
				self::getCurrentContext()->sslAvailableCache = false;
				return false;
			} else if (($mode == self::ENFORCE_SSL_YES) && isset($_SERVER['REQUEST_URI'])) {
				// Force SSL
				$location = 'https://' . $host_ssl . $_SERVER['REQUEST_URI'];
				header('Location:'.$location);
				die(_L('Redirecting to HTTPS...'));
			} else if ($mode == self::ENFORCE_SSL_AUTO) {
				// Automatic SSL detection
				if (isset($_COOKIE['SSL_CHECK'])) {
					// We already had the HTTPS detection done before.
					if ($_COOKIE['SSL_CHECK'] == '1') {
						// HTTPS was detected before, but we are HTTP. Redirect now
						$location = 'https://' . $host_ssl . $_SERVER['REQUEST_URI'];
						header('Location:'.$location);
						die(_L('Redirecting to HTTPS...'));
					} else {
						// No HTTPS available. Do nothing.
						self::getCurrentContext()->sslAvailableCache = false;
						return false;
					}
				} else if (isset($_SERVER['REQUEST_URI'])) {
					// This is our first check (or the browser didn't accept the SSL_CHECK cookie)
					$errno = -1;
					$errstr = '';
					if (@fsockopen($host_no_port, $ssl_port, $errno, $errstr, $timeout)) {
						// HTTPS detected. Redirect now, and remember that we had detected HTTPS
						self::cookieUtils()->setcookie('SSL_CHECK', '1', 0, true/*allowJS*/, null/*samesite*/, true/*forceInsecure*/);
						$location = 'https://' . $host_ssl . $_SERVER['REQUEST_URI'];
						header('Location:'.$location);
						die(_L('Redirecting to HTTPS...'));
					} else {
						// No HTTPS detected. Do nothing, and next time, don't try to detect HTTPS again.
						self::cookieUtils()->setcookie('SSL_CHECK', '0', 0, true/*allowJS*/, null/*samesite*/, true/*forceInsecure*/);
						self::getCurrentContext()->sslAvailableCache = false;
						return false;
					}
				} else {
					return false;
				}
			} else {
				assert(false);
				return false;
			}
		}
	}

	/**
	* Returns true if this is a tenant (data in userdata/tenant/.../),
	* and false if it is the base system (data in userdata)
	* @return bool true for tenants
	*/
	public static function isTenant(): bool {
		$localdir = self::localpath(); // contains trailing dir separator
		$priv_or_pub = "userdata".DIRECTORY_SEPARATOR;
		$tenant_dir = "tenant".DIRECTORY_SEPARATOR.self::tenantSubDirName().DIRECTORY_SEPARATOR;
		return is_dir($localdir.$priv_or_pub.$tenant_dir);
	}

	/**
	* Overrides the tenant subdir detection. Only used for cron.sh and should not be used otherwise,
	* since the detection of the tenant subdir is made out of the hostname and directory.
	* @param string $name The name of the subdirectory inside userdata/tenant/ )
	* @return void
	*/
	public static function forceTenantSubDirName(string $name): void {
		self::getCurrentContext()->forcedTenantSubDirName = $name;
	}

	/**
	* @return string The default tenant subdir name, determined by hostname and directory.
	*/
	private static function tenantSubDirName(): string {
		// Important for cron.sh
		if (!is_null(self::getCurrentContext()->forcedTenantSubDirName)) return self::getCurrentContext()->forcedTenantSubDirName;

		// CLI cannot use tenants
		if (!isset($_SERVER['HTTP_HOST'])) return 'NOT_AVAILABLE';

		// Example: https://hosted.oidplus.com/r74n_tenant/
		// becomes: hosted.oidplus.com__r74n_tenant
		// If you want to work with directories (instead of domain names), you
		// can symlink the directories towards one OIDplus base installation.
		$test = self::webpath(null, self::PATH_ABSOLUTE); // NOT canonical, otherwise it will be different before and after loading baseconfig
		if (!$test) return 'NOT_AVAILABLE';
		$test = explode('://', $test, 2)[1];
		$test = rtrim($test, '/');
		$test = preg_replace('@^www\\.@ismU', '', $test);
		$test = str_replace('/', '__', $test);
		return $test;
	}

	/**
	* @param string $subdir The desired subdir inside userdata, userdata_pub, userdata/tenant/..., userdata_pub/tenant/...
	* @param bool $public Use userdata_pub if true, otherwise userdata
	* @return string the local path
	*/
	public static function getUserDataDir(string $subdir, bool $public=false): string {
		$localdir = self::localpath(); // contains trailing dir separator
		$priv_or_pub = $public ? "userdata_pub".DIRECTORY_SEPARATOR : "userdata".DIRECTORY_SEPARATOR;
		$tenant_dir = "tenant".DIRECTORY_SEPARATOR.self::tenantSubDirName().DIRECTORY_SEPARATOR;
		if (is_dir($localdir.$priv_or_pub.$tenant_dir)) {
			// This is a tenancy-enabled system. Therefore, create tenancy-dependant dir by default
			// Tenancy dependant dir `userdata/tenant/<tenantSubDirName>/$subdir` existing? Then use this.
			$candidate1 = $localdir.$priv_or_pub.$tenant_dir.$subdir;
			if (is_dir($candidate1)) return $candidate1.DIRECTORY_SEPARATOR;
			@mkdir($candidate1);
			return $candidate1.DIRECTORY_SEPARATOR;
		} else {
			// This is a non-tenancy-enabled system. Therefore, create a general dir by default
			// General dir `userdata/$subdir` existing? Then use this.
			$candidate2 = $localdir.$priv_or_pub.$subdir;
			if (is_dir($candidate2)) return $candidate2.DIRECTORY_SEPARATOR;
			@mkdir($candidate2);
			return $candidate2.DIRECTORY_SEPARATOR;
		}
	}

	/**
	 * Gets a local path pointing to a resource
	 * @param string|null $target Target resource (file or directory must exist), or null to get the OIDplus base directory
	 * @param bool $relative If true, the returning path is relative to the currently executed PHP file (not the CLI working directory)
	 * @return string|false The local path, with guaranteed trailing path delimiter for directories
	 */
	public static function localpath(?string $target=null, bool $relative=false)/*: false|string*/ {
		if (is_null($target)) {
			$target = __DIR__.'/../../';
		}

		if ($relative) {
			// First, try to find out how many levels we need to go up
			$steps_up = self::getExecutingScriptPathDepth();
			if ($steps_up === false) return false;

			// Virtually go back from the executing PHP script to the OIDplus base path
			$res = str_repeat('../',$steps_up);

			// Then go to the desired location
			$basedir = realpath(__DIR__.'/../../');
			$target = realpath($target);
			if ($target === false) return false;
			$res .= substr($target, strlen($basedir)+1);
			$res = rtrim($res,'/'); // avoid '..//' for localpath(null,true)
		} else {
			$res = realpath($target);
		}

		if (is_dir($target)) $res .= '/';

		return str_replace('/', DIRECTORY_SEPARATOR, $res);
	}

	/**
	 * Gets a URL pointing to a resource
	 * @param string|null $target Target resource (file or directory must exist), or null to get the OIDplus base directory
	 * @param int|bool $mode If true or self::PATH_RELATIVE, the returning path is relative to the currently executed
	 *                          PHP script (i.e. index.php , not the plugin PHP script!). False or self::PATH_ABSOLUTE is
	 *                          results in an absolute URL. self::PATH_ABSOLUTE_CANONICAL is an absolute URL,
	 *                          but a canonical path (set by base config setting CANONICAL_SYSTEM_URL) is preferred.
	 * @return string|false The URL, with guaranteed trailing path delimiter for directories
	 * @throws OIDplusException
	 */
	public static function webpath(?string $target=null, /*int|bool*/ $mode=self::PATH_ABSOLUTE_CANONICAL)/*: false|string*/ {
		// backwards compatibility
		if ($mode === true) $mode = self::PATH_RELATIVE;
		if ($mode === false) $mode = self::PATH_ABSOLUTE;

		if ($mode == self::PATH_RELATIVE_TO_ROOT) {
			$tmp = self::webpath($target,self::PATH_ABSOLUTE);
			if ($tmp === false) return false;
			$tmp = parse_url($tmp);
			if ($tmp === false) return false;
			if (!isset($tmp['path'])) return false;
			return $tmp['path'];
		} else if ($mode == self::PATH_RELATIVE_TO_ROOT_CANONICAL) {
			$tmp = self::webpath($target,self::PATH_ABSOLUTE_CANONICAL);
			if ($tmp === false) return false;
			$tmp = parse_url($tmp);
			if ($tmp === false) return false;
			if (!isset($tmp['path'])) return false;
			return $tmp['path'];
		} else {
			$res = self::getSystemUrl($mode); // Note: already contains a trailing path delimiter
			if ($res === false) return false;
			if (!is_null($target)) {
				$basedir = realpath(__DIR__.'/../../');
				$target = realpath($target);
				if ($target === false) return false;
				if (!str_starts_with(str_replace(DIRECTORY_SEPARATOR,'/',$target).'/', str_replace(DIRECTORY_SEPARATOR,'/',$basedir).'/')) {
					// $target must be inside $basedir, otherwise it does not work!
					return false;
				}
				$tmp = substr($target, strlen($basedir)+1);
				$res .= str_replace(DIRECTORY_SEPARATOR,'/',$tmp); // replace OS specific path delimiters introduced by realpath()
				if (is_dir($target)) $res .= '/';
			}
			return $res;
		}
	}

	/**
	 * Note: canonicalURL() is different than webpath(),
	 * because it does additional things like re-ordering of arguments
	 * @param string|null $goto
	 * @return false|string
	 * @throws OIDplusException
	 */
	public static function canonicalURL(?string $goto=null)/*: false|string*/ {
		// First part: OIDplus system URL (or canonical system URL)
		$sysurl = self::getSystemUrl(self::PATH_ABSOLUTE_CANONICAL);

		// Second part: Directory
		$basedir = realpath(__DIR__.'/../../');
		$target = realpath('.');
		if ($target === false) return false;
		$tmp = substr($target, strlen($basedir)+1);
		$res = str_replace(DIRECTORY_SEPARATOR,'/',$tmp); // replace OS specific path delimiters introduced by realpath()
		if (is_dir($target) && ($res != '')) $res .= '/';

		// Third part: File name
		$tmp = $_SERVER['SCRIPT_NAME'];
		$tmp = preg_replace('@index\\.php$@ismU', '', $tmp);
		$tmp = explode('/',$tmp);
		$tmp = end($tmp);

		// Fourth part: Query string (ordered)
		$url = [];
		parse_str($_SERVER['QUERY_STRING']??'', $url);
		if ($goto !== null) $url['goto'] = $goto;
		ksort($url);
		$tmp2 = http_build_query($url);
		if ($tmp2 != '') $tmp2 = '?'.$tmp2;

		return $sysurl.$res.$tmp.$tmp2;
	}

	/**
	 * @param callable $func
	 * @return void
	 */
	public static function register_shutdown_function(callable $func): void {
		self::getCurrentContext()->shutdown_functions[] = $func;
	}

	/**
	 * @return void
	 */
	public static function invoke_shutdown(): void {
		foreach (self::getCurrentContext()->shutdown_functions as $func) {
			$func();
		}
	}

	/**
	 * @return string[]
	 * @throws OIDplusException
	 */
	public static function getAvailableLangs(): array {
		$langs = array();
		foreach (self::getAllPluginManifests('language') as $pluginManifest) {
			$code = $pluginManifest->getLanguageCode();
			$langs[] = $code;
		}
		return $langs;
	}

	/**
	 * @return string
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	public static function getDefaultLang(): string {
		$lang = self::baseConfig()->getValue('DEFAULT_LANGUAGE', 'enus');

		if (!in_array($lang,self::getAvailableLangs())) {
			static $anti_deadlock = false; // [NoOidplusContextOk] avoid endless loop inside OIDplusConfigInitializationException. this does not need to be in OIDplus::getCurrentContext(), because it is only used here and does not store information acreoss multiple parts of the program
			if ($anti_deadlock) return 'enus';
			$anti_deadlock = true;
			try {
				throw new OIDplusConfigInitializationException(_L('DEFAULT_LANGUAGE points to an invalid language plugin. (Consider setting to "enus" = "English USA".)'));
			} finally {
				$anti_deadlock = false;
			}
		}

		return $lang;
	}

	/**
	 * @return false|string
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	public static function getCurrentLang()/*: false|string*/ {

		if (isset($_SERVER['REQUEST_URI'])) {
			$rel_url = substr($_SERVER['REQUEST_URI'], strlen(self::webpath(null, self::PATH_RELATIVE_TO_ROOT)));
			if (str_starts_with($rel_url, 'rest/')) { // <== TODO: Find a way how to move this into the plugin, since REST does not belong to the core. (Maybe some kind of "stateless mode" that is enabled by the REST plugin)
				return self::getDefaultLang();
			}
		}

		if (isset($_GET['lang'])) {
			$lang = $_GET['lang'];
		} else if (isset($_POST['lang'])) {
			$lang = $_POST['lang'];
		} else if (isset($_COOKIE['LANGUAGE'])) {
			$lang = $_COOKIE['LANGUAGE'];
		} else {
			$lang = self::getDefaultLang();
		}
		return substr(preg_replace('@[^a-z]@imU', '', $lang),0,4); // sanitize
	}

	/**
	 * @return void
	 * @throws OIDplusException
	 */
	public static function handleLangArgument(): void {
		if (isset($_GET['lang'])) {
			// The "?lang=" argument is only for NoScript-Browsers/SearchEngines
			// In case someone who has JavaScript clicks a ?lang= link, they should get
			// the page in that language, but the cookie must be set, otherwise
			// the menu and other stuff would be in their cookie-based-language and not the
			// argument-based-language.
			self::cookieUtils()->setcookie('LANGUAGE', $_GET['lang'], 0, true/*HttpOnly off, because JavaScript also needs translation*/);
		} else if (isset($_POST['lang'])) {
			self::cookieUtils()->setcookie('LANGUAGE', $_POST['lang'], 0, true/*HttpOnly off, because JavaScript also needs translation*/);
		}
	}

	/**
	 * @param string $translation_file
	 * @return array
	 */
	private static function getTranslationFileContents(string $translation_file): array {
		// First, try the cache
		$cache_file = self::getUserDataDir("cache").'translation_'.md5($translation_file).'.ser';
		if (file_exists($cache_file) && (filemtime($cache_file) == filemtime($translation_file))) {
			$cac = @unserialize(file_get_contents($cache_file));
			if ($cac) return $cac;
		}

		// If not successful, then load the XML file
		$xml = @simplexml_load_string(file_get_contents($translation_file));
		if (!$xml) return array(); // if there is an UTF-8 or parsing error, don't output any errors, otherwise the JavaScript is corrupt and the page won't render correctly
		$cac = array();
		foreach ($xml->message as $msg) {
			$src = trim($msg->source->__toString());
			$dst = trim($msg->target->__toString());
			$cac[$src] = $dst;
		}
		@file_put_contents($cache_file,serialize($cac));
		@touch($cache_file,filemtime($translation_file));
		return $cac;
	}

	/**
	 * @param string $requested_lang
	 * @return array
	 * @throws OIDplusException
	 */
	public static function getTranslationArray(string $requested_lang='*'): array {
		foreach (self::getAllPluginManifests('language') as $pluginManifest) {
			$lang = $pluginManifest->getLanguageCode();
			if (strpos($lang,'/') !== false) continue; // just to be sure
			if (strpos($lang,'\\') !== false) continue; // just to be sure
			if (strpos($lang,'..') !== false) continue; // just to be sure

			if (($requested_lang != '*') && ($lang != $requested_lang)) continue;

			if (!isset(self::getCurrentContext()->translationArray[$lang])) {
				self::getCurrentContext()->translationArray[$lang] = array();

				$wildcard = $pluginManifest->getLanguageMessages();
				if (strpos($wildcard,'/') !== false) continue; // just to be sure
				if (strpos($wildcard,'\\') !== false) continue; // just to be sure
				if (strpos($wildcard,'..') !== false) continue; // just to be sure

				$translation_files = array_merge(
					glob(__DIR__.'/../../plugins/'.'*'.'/language/'.$lang.'/'.$wildcard),
					glob(self::getUserDataDir("plugins", true).'*'.'/language/'.$lang.'/'.$wildcard)
				);
				sort($translation_files); /* @phpstan-ignore-line */ // Weird message which I don't understand: Parameter #1 $array of function sort contains unresolvable type.
				foreach ($translation_files as $translation_file) {
					if (!file_exists($translation_file)) continue;
					$cac = self::getTranslationFileContents($translation_file);
					foreach ($cac as $src => $dst) {
						self::getCurrentContext()->translationArray[$lang][$src] = $dst;
					}
				}
			}
		}
		return self::getCurrentContext()->translationArray;
	}

	/**
	 * @return mixed
	 */
	public static function getEditionInfo() {
		return @parse_ini_file(__DIR__.'/../edition.ini', true)['Edition'];
	}

	/**
	 * @return false|string The git path, with guaranteed trailing path delimiter for directories
	 */
	public static function findGitFolder()/*: false|string*/ {
		$dir = rtrim(self::localpath(), DIRECTORY_SEPARATOR);

		// Git command line saves git information in folder ".git"
		if (is_dir($res = $dir.'/.git')) {
			return str_replace('/', DIRECTORY_SEPARATOR, $res.'/');
		}

		// Plesk git saves git information in folder "../../../git/oidplus/" (or similar)
		$i = 0;
		do {
			if (is_dir($dir.'/git')) {
				$confs = @glob($dir.'/git/'.'*'.'/config');
				if ($confs) foreach ($confs as $conf) {
					$cont = file_get_contents($conf);
					if (isset(self::getEditionInfo()['gitrepo']) && (self::getEditionInfo()['gitrepo'] != '') && (strpos($cont, self::getEditionInfo()['gitrepo']) !== false)) {
						$res = dirname($conf);
						return str_replace('/', DIRECTORY_SEPARATOR, $res.'/');
					}
				}
			}
			$i++;
		} while (($i<100) && ($dir != ($new_dir = @realpath($dir.'/../'))) && ($dir = $new_dir));

		return false;
	}

	/**
	 * @return false|string The SVN path, with guaranteed trailing path delimiter for directories
	 */
	public static function findSvnFolder()/*: false|string*/ {
		$dir = rtrim(self::localpath(), DIRECTORY_SEPARATOR);

		if (is_dir($res = $dir.'/.svn')) {
			return str_replace('/', DIRECTORY_SEPARATOR, $res.'/');
		}

		// in case we checked out the root instead of the "trunk"
		if (is_dir($res = $dir.'/../.svn')) {
			return str_replace('/', DIRECTORY_SEPARATOR, $res.'/');
		}

		return false;
	}

	/**
	 * @return false|string
	 */
	public static function getGitsvnRevision()/*: false|string*/ {
		try {
			$git_dir = self::findGitFolder();
			if ($git_dir === false) return false;

			// git_get_latest_commit_message() tries command line and binary parsing
			// requires vendor/danielmarschall/php_utils/git_utils.inc.php
			$commit_msg = git_get_latest_commit_message($git_dir);
		} catch (\Exception $e) {
			return false;
		}

		$m = array();
		if (preg_match('%git-svn-id: (.+)@(\\d+) %ismU', $commit_msg, $m)) {
			return $m[2];
		} else {
			return false;
		}
	}

	/**
	 * @param string $static_node_id
	 * @param bool $throw_exception
	 * @return string
	 */
	public static function prefilterQuery(string $static_node_id, bool $throw_exception): string {
		$static_node_id = trim($static_node_id);

		// Let namespace be case-insensitive
		// Note: The query might not contain a namespace. It might be a single OID
		//       or MAC address, and the plugins need to detect it any add a namespace
		//       in the prefiltering. But if we have a namespace, we should fix the
		//       case, so that plugins don't have a problem if they check the namespace
		//       using str_starts_with().
		if (substr_count($static_node_id, ':') === 1) {
			$ary = explode(':', $static_node_id, 2);
			$ary[0] = strtolower($ary[0]);
			$static_node_id = implode(':', $ary);
		}

		// Ask plugins if they want to change the node id
		foreach (self::getObjectTypePluginsEnabled() as $plugin) {
			// Convert URN to OIDplus (e.g. urn:uuid: becomes guid:)
			$urn_nss = $plugin->getObjectTypeClassName()::urnNsOrDefault();
			foreach ($urn_nss as $urn_ns) {
				// Convert URN to OID+
				// Replace 'urn:uuid:' with 'guid:' and 'urn:oid:' with 'oid:'
				$static_node_id = str_replace('urn:'.$urn_ns.':', $plugin->getObjectTypeClassName()::ns().':', $static_node_id);
			}

			// Also ask the plugin to do prefiltering
			$static_node_id = $plugin->prefilterQuery($static_node_id, $throw_exception);
		}

		// Let namespace be case-insensitive
		// At this point, plugins should have already added the namespace during the prefiltering,
		// so, now we make sure that the namespace is really lowercase
		if (substr_count($static_node_id, ':') === 1) {
			$ary = explode(':', $static_node_id, 2);
			$ary[0] = strtolower($ary[0]);
			$static_node_id = implode(':', $ary);
		}

		return $static_node_id;
	}

	/**
	 * @return bool
	 */
	public static function isCronjob(): bool {
		return explode('.',basename($_SERVER['SCRIPT_NAME']))[0] === 'cron';
	}

	/**
	 * Since OIDplus svn-184, entries in the database need to have a canonical ID
	 * If the ID is not canonical (e.g. GUIDs missing hyphens), the object cannot be opened in OIDplus
	 * This script re-canonizes the object IDs if required.
	 * In SVN Rev 856, the canonization for GUID, IPv4 and IPv6 have changed, requiring another
	 * re-canonization
	 * @return void
	 * @throws OIDplusException
	 */
	private static function recanonizeObjects(): void {
		$res = self::db()->query("select id from ###objects");
		while ($row = $res->fetch_array()) {
			$ida = $row['id'];
			$obj = OIDplusObject::parse($ida);
			if (!$obj) continue;
			$idb = $obj->nodeId();
			if (($idb) && ($ida != $idb)) {
				if (self::db()->transaction_supported()) self::db()->transaction_begin();
				try {
					self::db()->query("update ###objects set id = ? where id = ?", array($idb, $ida));
					self::db()->query("update ###asn1id set oid = ? where oid = ?", array($idb, $ida));
					self::db()->query("update ###iri set oid = ? where oid = ?", array($idb, $ida));
					self::db()->query("update ###log_object set object = ? where object = ?", array($idb, $ida));
					self::logger()->log("V2:[INFO]A", "Object name '%1' has been changed to '%2' during re-canonization", $ida, $idb);
					if (self::db()->transaction_supported()) self::db()->transaction_commit();
				} catch (\Exception $e) {
					if (self::db()->transaction_supported()) self::db()->transaction_rollback();
					throw $e;
				}
				OIDplusObject::resetObjectInformationCache();
			}
		}
	}

	/**
	 * Tries to determine the IP address of the website visitor
	 * @return string|false
	 */
	public static function getClientIpAddress()/*: false|string*/ {
		$direct_connection = $_SERVER['REMOTE_ADDR'] ?? false;
		if ($direct_connection === false) return false;

		$trusted_proxies = self::baseConfig()->getValue('XFF_TRUSTED_PROXIES', []);
		if (in_array($direct_connection, $trusted_proxies)) {
			return $_SERVER['HTTP_X_FORWARDED_FOR'] ?: $direct_connection;
		} else {
			return $direct_connection;
		}
	}

}
