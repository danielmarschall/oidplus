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

class OIDplus extends OIDplusBaseClass {
	/**
	 * @var OIDplusPagePlugin[]
	 */
	private static /*OIDplusPagePlugin[]*/ $pagePlugins = array();
	/**
	 * @var OIDplusAuthPlugin[]
	 */
	private static /*OIDplusAuthPlugin[]*/ $authPlugins = array();
	/**
	 * @var OIDplusLoggerPlugin[]
	 */
	private static /*OIDplusLoggerPlugin[]*/ $loggerPlugins = array();
	/**
	 * @var OIDplusObjectTypePlugin[]
	 */
	private static /*OIDplusObjectTypePlugin[]*/ $objectTypePlugins = array();
	/**
	 * @var string[]|OIDplusObject[] Classnames of OIDplusObject classes
	 */
	private static /*string[]*/ $enabledObjectTypes = array();
	/**
	 * @var string[]|OIDplusObject[] Classnames of OIDplusObject classes
	 */
	private static /*string[]*/ $disabledObjectTypes = array();
	/**
	 * @var OIDplusDatabasePlugin[]
	 */
	private static /*OIDplusDatabasePlugin[]*/ $dbPlugins = array();
	/**
	 * @var OIDplusCaptchaPlugin[]
	 */
	private static /*OIDplusCaptchaPlugin[]*/ $captchaPlugins = array();
	/**
	 * @var OIDplusSqlSlangPlugin[]
	 */
	private static /*OIDplusSqlSlangPlugin[]*/ $sqlSlangPlugins = array();
	/**
	 * @var OIDplusLanguagePlugin[]
	 */
	private static /*OIDplusLanguagePlugin[]*/ $languagePlugins = array();
	/**
	 * @var OIDplusDesignPlugin[]
	 */
	private static /*OIDplusDesignPlugin[]*/ $designPlugins = array();

	/**
	 * @var bool
	 */
	protected static $html = true;

	/**
	 * e.g. "../"
	 */
	/*public*/ const PATH_RELATIVE = 1;

	/**
	 * e.g. "http://www.example.com/oidplus/"
	 */
	/*public*/ const PATH_ABSOLUTE = 2;

	/**
	 * e.g. "http://www.example.org/oidplus/" (if baseconfig CANONICAL_SYSTEM_URL is set)
	 */
	/*public*/ const PATH_ABSOLUTE_CANONICAL = 3;

	/**
	 * e.g. "/oidplus/"
	 */
	/*public*/ const PATH_RELATIVE_TO_ROOT = 4;

	/**
	 * e.g. "/oidplus/" (if baseconfig CANONICAL_SYSTEM_URL is set)
	 */
	/*public*/ const PATH_RELATIVE_TO_ROOT_CANONICAL = 5;

	/**
	 * These plugin types can contain HTML code and therefore may
	 * emit (non-setup) CSS/JS code via their manifest.
	 * Note that design plugins may only output CSS, not JS.
	 */
	/*public*/ const INTERACTIVE_PLUGIN_TYPES = array(
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
	private static function insideSetup(): bool {
		if (PHP_SAPI == 'cli') return false;
		if (!isset($_SERVER['REQUEST_URI'])) return false;
		return (strpos($_SERVER['REQUEST_URI'], OIDplus::webpath(null,OIDplus::PATH_RELATIVE_TO_ROOT).'setup/') === 0);
	}

	// --- Static classes

	private static $baseConfig = null;
	private static $oldConfigFormatLoaded = false;

	/**
	 * @return OIDplusBaseConfig
	 * @throws OIDplusException, OIDplusConfigInitializationException
	 */
	public static function baseConfig(): OIDplusBaseConfig {
		if ($first_init = is_null(self::$baseConfig)) {
			self::$baseConfig = new OIDplusBaseConfig();
		}

		if ($first_init) {
			if (self::insideSetup()) return self::$baseConfig;
			if ((basename($_SERVER['SCRIPT_NAME']) === 'oidplus.min.js.php') && isset($_REQUEST['noBaseConfig']) && ($_REQUEST['noBaseConfig'] == '1')) return self::$baseConfig;
			if ((basename($_SERVER['SCRIPT_NAME']) === 'oidplus.min.css.php') && isset($_REQUEST['noBaseConfig']) && ($_REQUEST['noBaseConfig'] == '1')) return self::$baseConfig;

			// Include a file containing various size/depth limitations of OIDs
			// It is important to include it before userdata/baseconfig/config.inc.php was included,
			// so we can give userdata/baseconfig/config.inc.php the chance to override the values.

			include OIDplus::localpath().'includes/oidplus_limits.inc.php';

			// Include config file

			$config_file = OIDplus::localpath() . 'userdata/baseconfig/config.inc.php';
			$config_file_old = OIDplus::localpath() . 'includes/config.inc.php'; // backwards compatibility

			if (!file_exists($config_file) && file_exists($config_file_old)) {
				$config_file = $config_file_old;
			}

			if (file_exists($config_file)) {
				if (self::$oldConfigFormatLoaded) {
					// Note: We may only include it once due to backwards compatibility,
					//       since in version 2.0, the configuration was defined using define() statements
					// Attention: This does mean that a full re-init (e.g. for test cases) is not possible
					//            if a version 2.0 config is used!

					// We need to do this, because define() cannot be undone
					// Note: This can only happen in very special cases (e.g. test cases) where you call init() twice
					throw new OIDplusConfigInitializationException(_L('A full re-initialization is not possible if a version 2.0 config file (containing "defines") is used. Please update to a config 2.1 file by running setup again.'));
				} else {
					$tmp = file_get_contents($config_file);
					$ns = "ViaThinkSoft\OIDplus\OIDplus";
					$uses = "use $ns;";
					if ((strpos($tmp,'OIDplus::') !== false) && (strpos($tmp,$uses) === false)) {
						// Migrate config file to namespace class names
						// Note: Only config files version 2.1 are affected. Not 2.0 ones

						$tmp = "<?php\r\n\r\n$uses /* Automatically added by migration procedure */\r\n?>$tmp";
						$tmp = str_replace('?><?php', '', $tmp);

						$tmp = str_replace("\$ns\OIDplusCaptchaPluginRecaptcha::", "OIDplusCaptchaPluginRecaptcha::", $tmp);
						$tmp = str_replace("OIDplusCaptchaPluginRecaptcha::", "\$ns\OIDplusCaptchaPluginRecaptcha::", $tmp);

						$tmp = str_replace('DISABLE_PLUGIN_OIDplusPagePublicRdap',
							'DISABLE_PLUGIN_Frdlweb\OIDplus\OIDplusPagePublicRdap', $tmp);
						$tmp = str_replace('DISABLE_PLUGIN_OIDplusPagePublicAltIds',
							'DISABLE_PLUGIN_Frdlweb\OIDplus\OIDplusPagePublicAltIds', $tmp);
						$tmp = str_replace('DISABLE_PLUGIN_OIDplusPagePublicUITweaks',
							'DISABLE_PLUGIN_TushevOrg\OIDplus\OIDplusPagePublicUITweaks', $tmp);
						$tmp = str_replace('DISABLE_PLUGIN_OIDplus',
							'DISABLE_PLUGIN_ViaThinkSoft\OIDplus\OIDplus', $tmp);

						if (@file_put_contents($config_file, $tmp) === false) {
							eval('?>'.$tmp);
						} else {
							include $config_file;
						}
					} else {
						include $config_file;
					}
				}

				// Backwards compatibility 2.0 => 2.1
				if (defined('OIDPLUS_CONFIG_VERSION') && (OIDPLUS_CONFIG_VERSION == 2.0)) {
					self::$oldConfigFormatLoaded = true;
					foreach (get_defined_constants(true)['user'] as $name => $value) {
						$name = str_replace('OIDPLUS_', '', $name);
						if ($name == 'SESSION_SECRET') $name = 'SERVER_SECRET';
						if ($name == 'MYSQL_QUERYLOG') $name = 'QUERY_LOGFILE';
						$name = str_replace('DISABLE_PLUGIN_OIDplusPagePublicRdap',
							'DISABLE_PLUGIN_Frdlweb\OIDplus\OIDplusPagePublicRdap', $name);
						$name = str_replace('DISABLE_PLUGIN_OIDplusPagePublicAltIds',
							'DISABLE_PLUGIN_Frdlweb\OIDplus\OIDplusPagePublicAltIds', $name);
						$name = str_replace('DISABLE_PLUGIN_OIDplusPagePublicUITweaks',
							'DISABLE_PLUGIN_TushevOrg\OIDplus\OIDplusPagePublicUITweaks', $name);
						$name = str_replace('DISABLE_PLUGIN_OIDplus',
							'DISABLE_PLUGIN_ViaThinkSoft\OIDplus\OIDplus', $name);
						if ($name == 'CONFIG_VERSION') {
							$value = 2.1;
						} else if (($name == 'MYSQL_PASSWORD') || ($name == 'ODBC_PASSWORD') || ($name == 'PDO_PASSWORD') || ($name == 'PGSQL_PASSWORD')) {
							$value = base64_decode($value);
						}
						self::$baseConfig->setValue($name, $value);
					}
				}
			} else {
				if (!is_dir(OIDplus::localpath().'setup')) {
					throw new OIDplusConfigInitializationException(_L('File %1 is missing, but setup can\'t be started because its directory missing.',$config_file));
				} else {
					if (self::$html) {
						if (!self::insideSetup()) {
							header('Location:'.OIDplus::webpath(null,OIDplus::PATH_RELATIVE).'setup/');
							die(_L('Redirecting to setup...'));
						} else {
							return self::$baseConfig;
						}
					} else {
						// This can be displayed in e.g. ajax.php
						throw new OIDplusConfigInitializationException(_L('File %1 is missing. Please run setup again.',$config_file));
					}
				}
			}

			// Check important config settings

			if (self::$baseConfig->getValue('CONFIG_VERSION') != 2.1) {
				if (strpos($_SERVER['REQUEST_URI'], OIDplus::webpath(null,OIDplus::PATH_RELATIVE).'setup/') !== 0) {
					throw new OIDplusConfigInitializationException(_L("The information located in %1 is outdated.",realpath($config_file)));
				}
			}

			if (self::$baseConfig->getValue('SERVER_SECRET', '') === '') {
				if (strpos($_SERVER['REQUEST_URI'], OIDplus::webpath(null,OIDplus::PATH_RELATIVE).'setup/') !== 0) {
					throw new OIDplusConfigInitializationException(_L("You must set a value for SERVER_SECRET in %1 for the system to operate secure.",realpath($config_file)));
				}
			}
		}

		return self::$baseConfig;
	}

	private static $config = null;

	/**
	 * @return OIDplusConfig
	 * @throws OIDplusException
	 */
	public static function config(): OIDplusConfig {
		if ($first_init = is_null(self::$config)) {
			self::$config = new OIDplusConfig();
		}

		if ($first_init) {
			// These are important settings for base functionalities and therefore are not inside plugins
			self::$config->prepareConfigKey('system_title', 'What is the name of your RA?', 'OIDplus 2.0', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
				if (empty($value)) {
					throw new OIDplusException(_L('Please enter a value for the system title.'));
				}
			});
			self::$config->prepareConfigKey('admin_email', 'E-Mail address of the system administrator', '', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
				if (!empty($value) && !OIDplus::mailUtils()->validMailAddress($value)) {
					throw new OIDplusException(_L('This is not a correct email address'));
				}
			});
			self::$config->prepareConfigKey('global_cc', 'Global CC for all outgoing emails?', '', OIDplusConfig::PROTECTION_EDITABLE, function(&$value) {
				$value = trim($value);
				if ($value === '') return;
				$addrs = explode(';', $value);
				foreach ($addrs as $addr) {
					$addr = trim($addr);
					if (!empty($addr) && !OIDplus::mailUtils()->validMailAddress($addr)) {
						throw new OIDplusException(_L('%1 is not a correct email address',$addr));
					}
				}
			});
			self::$config->prepareConfigKey('global_bcc', 'Global BCC for all outgoing emails?', '', OIDplusConfig::PROTECTION_EDITABLE, function(&$value) {
				$value = trim($value);
				if ($value === '') return;
				$addrs = explode(';', $value);
				foreach ($addrs as $addr) {
					$addr = trim($addr);
					if (!empty($addr) && !OIDplus::mailUtils()->validMailAddress($addr)) {
						throw new OIDplusException(_L('%1 is not a correct email address',$addr));
					}
				}
			});
			self::$config->prepareConfigKey('objecttypes_initialized', 'List of object type plugins that were initialized once', '', OIDplusConfig::PROTECTION_READONLY, function($value) {
				// Nothing here yet
			});
			self::$config->prepareConfigKey('objecttypes_enabled', 'Enabled object types and their order, separated with a semicolon (please reload the page so that the change is applied)', '', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
				// TODO: when objecttypes_enabled is changed at the admin control panel, we need to do a reload of the page, so that jsTree will be updated. Is there anything we can do?

				$ary = explode(';',$value);
				$uniq_ary = array_unique($ary);

				if (count($ary) != count($uniq_ary)) {
					throw new OIDplusException(_L('Please check your input. Some object types are double.'));
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
						throw new OIDplusException(_L('Please check your input. Namespace "%1" is not found',$ot_check));
					}
				}
			});
			self::$config->prepareConfigKey('oidplus_private_key', 'Private key for this system', '', OIDplusConfig::PROTECTION_HIDDEN, function($value) {
				// Nothing here yet
			});
			self::$config->prepareConfigKey('oidplus_public_key', 'Public key for this system. If you "clone" your system, you must delete this key (e.g. using phpMyAdmin), so that a new one is created.', '', OIDplusConfig::PROTECTION_READONLY, function($value) {
				// Nothing here yet
			});
			self::$config->prepareConfigKey('last_known_system_url', 'Last known System URL', '', OIDplusConfig::PROTECTION_HIDDEN, function($value) {
				// Nothing here yet
			});
			self::$config->prepareConfigKey('last_known_version', 'Last known OIDplus Version', '', OIDplusConfig::PROTECTION_HIDDEN, function($value) {
				// Nothing here yet
			});
			self::$config->prepareConfigKey('default_ra_auth_method', 'Default auth method used for generating password of RAs (must exist in plugins/[vendorname]/auth/)? Empty = OIDplus decides.', '', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
				if (trim($value) === '') return; // OIDplus decides

				$good = true;
				if (strpos($value,'/') !== false) $good = false;
				if (strpos($value,'\\') !== false) $good = false;
				if (strpos($value,'..') !== false) $good = false;
				if (!$good) {
					throw new OIDplusException(_L('Invalid auth plugin name. It is usually the folder name, without path, e.g. "%1"', 'A4_argon2'));
				}

				OIDplus::checkRaAuthPluginAvailable($value, true);
			});
		}

		return self::$config;
	}

	private static $gui = null;

	/**
	 * @return OIDplusGui
	 */
	public static function gui(): OIDplusGui {
		if (is_null(self::$gui)) {
			self::$gui = new OIDplusGui();
		}
		return self::$gui;
	}

	private static $authUtils = null;

	/**
	 * @return OIDplusAuthUtils
	 */
	public static function authUtils(): OIDplusAuthUtils {
		if (is_null(self::$authUtils)) {
			self::$authUtils = new OIDplusAuthUtils();
		}
		return self::$authUtils;
	}

	private static $mailUtils = null;

	/**
	 * @return OIDplusMailUtils
	 */
	public static function mailUtils(): OIDplusMailUtils {
		if (is_null(self::$mailUtils)) {
			self::$mailUtils = new OIDplusMailUtils();
		}
		return self::$mailUtils;
	}

	private static $cookieUtils = null;

	/**
	 * @return OIDplusCookieUtils
	 */
	public static function cookieUtils(): OIDplusCookieUtils {
		if (is_null(self::$cookieUtils)) {
			self::$cookieUtils = new OIDplusCookieUtils();
		}
		return self::$cookieUtils;
	}

	private static $menuUtils = null;

	/**
	 * @return OIDplusMenuUtils
	 */
	public static function menuUtils(): OIDplusMenuUtils {
		if (is_null(self::$menuUtils)) {
			self::$menuUtils = new OIDplusMenuUtils();
		}
		return self::$menuUtils;
	}

	private static $logger = null;

	/**
	 * @return OIDplusLogger
	 */
	public static function logger(): OIDplusLogger {
		if (is_null(self::$logger)) {
			self::$logger = new OIDplusLogger();
		}
		return self::$logger;
	}

	// --- SQL slang plugin

	/**
	 * @param OIDplusSqlSlangPlugin $plugin
	 * @return void
	 * @throws OIDplusException
	 */
	private static function registerSqlSlangPlugin(OIDplusSqlSlangPlugin $plugin) {
		$name = $plugin::id();

		if ($name === '') {
			throw new OIDplusException(_L('Plugin %1 cannot be registered because it does not return a valid ID', $plugin->getPluginDirectory()));
		}

		if (isset(self::$sqlSlangPlugins[$name])) {
			$plugintype_hf = _L('SQL slang');
			throw new OIDplusException(_L('Multiple %1 plugins use the ID %2', $plugintype_hf, $name));
		}

		self::$sqlSlangPlugins[$name] = $plugin;
	}

	/**
	 * @return OIDplusSqlSlangPlugin[]
	 */
	public static function getSqlSlangPlugins(): array {
		return self::$sqlSlangPlugins;
	}

	/**
	 * @param string $id
	 * @return OIDplusSqlSlangPlugin|null
	 */
	public static function getSqlSlangPlugin(string $id)/*: ?OIDplusSqlSlangPlugin*/ {
		return self::$sqlSlangPlugins[$id] ?? null;
	}

	// --- Database plugin

	/**
	 * @param OIDplusDatabasePlugin $plugin
	 * @return void
	 * @throws OIDplusException
	 */
	private static function registerDatabasePlugin(OIDplusDatabasePlugin $plugin) {
		$name = $plugin::id();

		if ($name === '') {
			throw new OIDplusException(_L('Plugin %1 cannot be registered because it does not return a valid ID', $plugin->getPluginDirectory()));
		}

		if (isset(self::$dbPlugins[$name])) {
			$plugintype_hf = _L('Database');
			throw new OIDplusException(_L('Multiple %1 plugins use the ID %2', $plugintype_hf, $name));
		}

		self::$dbPlugins[$name] = $plugin;
	}

	/**
	 * @return OIDplusDatabasePlugin[]
	 */
	public static function getDatabasePlugins(): array {
		return self::$dbPlugins;
	}

	/**
	 * @return OIDplusDatabasePlugin
	 * @throws OIDplusException, OIDplusConfigInitializationException
	 */
	public static function getActiveDatabasePlugin(): OIDplusDatabasePlugin {
		$db_plugin_name = OIDplus::baseConfig()->getValue('DATABASE_PLUGIN','');
		if ($db_plugin_name === '') {
			throw new OIDplusConfigInitializationException(_L('No database plugin selected in config file'));
		}
		foreach (self::$dbPlugins as $name => $plugin) {
			if (strtolower($name) == strtolower($db_plugin_name)) {
				return $plugin;
			}
		}
		throw new OIDplusConfigInitializationException(_L('Database plugin "%1" not found',$db_plugin_name));
	}

	/**
	 * @var OIDplusDatabaseConnection|null
	 */
	private static $dbMainSession = null;

	/**
	 * @return OIDplusDatabaseConnection
	 * @throws OIDplusException, OIDplusConfigInitializationException
	 */
	public static function db(): OIDplusDatabaseConnection {
		if (is_null(self::$dbMainSession)) {
			self::$dbMainSession = self::getActiveDatabasePlugin()->newConnection();
		}
		if (!self::$dbMainSession->isConnected()) self::$dbMainSession->connect();
		return self::$dbMainSession;
	}

	/**
	 * @var OIDplusDatabaseConnection|null
	 */
	private static $dbIsolatedSession = null;

	/**
	 * @return OIDplusDatabaseConnection
	 * @throws OIDplusException, OIDplusConfigInitializationException
	 */
	public static function dbIsolated(): OIDplusDatabaseConnection {
		if (is_null(self::$dbIsolatedSession)) {
			self::$dbIsolatedSession = self::getActiveDatabasePlugin()->newConnection();
		}
		if (!self::$dbIsolatedSession->isConnected()) self::$dbIsolatedSession->connect();
		return self::$dbIsolatedSession;
	}

	// --- CAPTCHA plugin

	/**
	 * @param OIDplusCaptchaPlugin $plugin
	 * @return void
	 * @throws OIDplusException
	 */
	private static function registerCaptchaPlugin(OIDplusCaptchaPlugin $plugin) {
		$name = $plugin::id();

		if ($name === '') {
			throw new OIDplusException(_L('Plugin %1 cannot be registered because it does not return a valid ID', $plugin->getPluginDirectory()));
		}

		if (isset(self::$captchaPlugins[$name])) {
			$plugintype_hf = _L('CAPTCHA');
			throw new OIDplusException(_L('Multiple %1 plugins use the ID %2', $plugintype_hf, $name));
		}

		self::$captchaPlugins[$name] = $plugin;
	}

	/**
	 * @return OIDplusCaptchaPlugin[]
	 */
	public static function getCaptchaPlugins(): array {
		return self::$captchaPlugins;
	}

	/**
	 * @return string
	 * @throws OIDplusException, OIDplusConfigInitializationException
	 */
	public static function getActiveCaptchaPluginId(): string {
		$captcha_plugin_name = OIDplus::baseConfig()->getValue('CAPTCHA_PLUGIN', '');

		if (OIDplus::baseConfig()->getValue('RECAPTCHA_ENABLED', false) && ($captcha_plugin_name === '')) {
			// Legacy config file support!
			$captcha_plugin_name = 'reCAPTCHA';
		}

		if ($captcha_plugin_name === '') $captcha_plugin_name = 'None'; // the "None" plugin is a must-have!

		return $captcha_plugin_name;
	}

	/**
	 * @return OIDplusCaptchaPlugin
	 * @throws OIDplusException, OIDplusConfigInitializationException
	 */
	public static function getActiveCaptchaPlugin(): OIDplusCaptchaPlugin {
		$captcha_plugin_name = OIDplus::getActiveCaptchaPluginId();
		foreach (self::$captchaPlugins as $name => $plugin) {
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
	private static function registerPagePlugin(OIDplusPagePlugin $plugin) {
		self::$pagePlugins[] = $plugin;
	}

	/**
	 * @return OIDplusPagePlugin[]
	 */
	public static function getPagePlugins(): array {
		return self::$pagePlugins;
	}

	// --- Auth plugin

	/**
	 * @param string $id
	 * @return OIDplusAuthPlugin|null
	 */
	public static function getAuthPluginById(string $id)/*: ?OIDplusAuthPlugin*/ {
		$plugins = OIDplus::getAuthPlugins();
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
	private static function checkRaAuthPluginAvailable(string $plugin_id, bool $must_hash) {
		// if (!wildcard_is_dir(OIDplus::localpath().'plugins/'.'*'.'/auth/'.$plugin_foldername)) {
		$plugin = OIDplus::getAuthPluginById($plugin_id);
		if (is_null($plugin)) {
			throw new OIDplusException(_L('The auth plugin "%1" does not exist in plugin directory %2',$plugin_id,'plugins/[vendorname]/auth/'));
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
	 * @return OIDplusAuthPlugin|null
	 * @throws OIDplusException
	 */
	public static function getDefaultRaAuthPlugin(bool $must_hash)/*: OIDplusAuthPlugin*/ {
		// 1. Priority: Use the auth plugin the user prefers
		$def_plugin_id = OIDplus::config()->getValue('default_ra_auth_method');
		if (trim($def_plugin_id) !== '') {
			OIDplus::checkRaAuthPluginAvailable($def_plugin_id, $must_hash);
			return OIDplus::getAuthPluginById($def_plugin_id);
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
			$plugin = OIDplus::getAuthPluginById($plugin_id);
			if (is_null($plugin)) continue;

			$reason = '';
			if (!$plugin->availableForHash($reason)) continue;
			if ($must_hash && !$plugin->availableForVerify($reason)) continue;
			return $plugin;
		}

		// 3. Priority: If nothing found, take the first found plugin
		$plugins = OIDplus::getAuthPlugins();
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
	private static function registerAuthPlugin(OIDplusAuthPlugin $plugin) {
		$reason = '';
		if (OIDplus::baseConfig()->getValue('DEBUG') && $plugin->availableForHash($reason) && $plugin->availableForVerify($reason)) {
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

		self::$authPlugins[] = $plugin;
	}

	/**
	 * @return OIDplusAuthPlugin[]
	 */
	public static function getAuthPlugins(): array {
		return self::$authPlugins;
	}

	// --- Language plugin

	/**
	 * @param OIDplusLanguagePlugin $plugin
	 * @return void
	 */
	private static function registerLanguagePlugin(OIDplusLanguagePlugin $plugin) {
		self::$languagePlugins[] = $plugin;
	}

	/**
	 * @return OIDplusLanguagePlugin[]
	 */
	public static function getLanguagePlugins(): array {
		return self::$languagePlugins;
	}

	// --- Design plugin

	/**
	 * @param OIDplusDesignPlugin $plugin
	 * @return void
	 */
	private static function registerDesignPlugin(OIDplusDesignPlugin $plugin) {
		self::$designPlugins[] = $plugin;
	}

	/**
	 * @return OIDplusDesignPlugin[]
	 */
	public static function getDesignPlugins(): array {
		return self::$designPlugins;
	}

	/**
	 * @return OIDplusDesignPlugin|null
	 * @throws OIDplusException
	 */
	public static function getActiveDesignPlugin()/*: ?OIDplusDesignPlugin*/ {
		$plugins = OIDplus::getDesignPlugins();
		foreach ($plugins as $plugin) {
			if ($plugin->id() == OIDplus::config()->getValue('design','default')) {
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
	private static function registerLoggerPlugin(OIDplusLoggerPlugin $plugin) {
		self::$loggerPlugins[] = $plugin;
	}

	/**
	 * @return OIDplusLoggerPlugin[]
	 */
	public static function getLoggerPlugins(): array {
		return self::$loggerPlugins;
	}

	// --- Object type plugin

	/**
	 * @param OIDplusObjectTypePlugin $plugin
	 * @return void
	 * @throws OIDplusException
	 */
	private static function registerObjectTypePlugin(OIDplusObjectTypePlugin $plugin) {
		self::$objectTypePlugins[] = $plugin;

		if (OIDplus::baseConfig()->getValue('DEBUG')) {
			// Avoid a namespace hash conflict of the OIDplus Information Object Custom UUIDs
			// see here https://github.com/danielmarschall/oidplus/blob/master/doc/oidplus_custom_guid.md
			if (!str_starts_with($plugin->getManifest()->getOid(), '1.3.6.1.4.1.37476.2.5.2.4.8.')) {
				$coll = [];
				for ($i = 1; $i <= 185; $i++) {
					$block4 = dechex(hexdec(substr(sha1('1.3.6.1.4.1.37476.2.5.2.4.8.'.$i), -4)) & 0x3FFF | 0x8000);
					$coll[] = $block4;
				}
				$coll[] = dechex(0x8000); // System UUID
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
	private static function registerObjectType($ot) {
		$ns = $ot::ns();
		if (empty($ns)) throw new OIDplusException(_L('ObjectType plugin %1 is erroneous: Namespace must not be empty',$ot));

		// Currently, we must enforce that namespaces in objectType plugins are lowercase, because prefilterQuery() makes all namespaces lowercase and the DBMS should be case-sensitive
		if ($ns != strtolower($ns)) throw new OIDplusException(_L('ObjectType plugin %1 is erroneous: Namespace %2 must be lower-case',$ot,$ns));

		$root = $ot::root();
		if (!str_starts_with($root,$ns.':')) throw new OIDplusException(_L('ObjectType plugin %1 is erroneous: Root node (%2) is in wrong namespace (needs starts with %3)!',$ot,$root,$ns.':'));

		$ns_found = false;
		foreach (array_merge(OIDplus::getEnabledObjectTypes(), OIDplus::getDisabledObjectTypes()) as $test_ot) {
			if ($test_ot::ns() == $ns) {
				$ns_found = true;
				break;
			}
		}
		if ($ns_found) {
			throw new OIDplusException(_L('Attention: Two objectType plugins use the same namespace "%1"!',$ns));
		}

		$init = OIDplus::config()->getValue("objecttypes_initialized");
		$init_ary = empty($init) ? array() : explode(';', $init);
		$init_ary = array_map('trim', $init_ary);

		$enabled = OIDplus::config()->getValue("objecttypes_enabled");
		$enabled_ary = empty($enabled) ? array() : explode(';', $enabled);
		$enabled_ary = array_map('trim', $enabled_ary);

		if (in_array($ns, $enabled_ary)) {
			// If it is in the list of enabled object types, it is enabled (obviously)
			$do_enable = true;
		} else {
			if (!OIDplus::config()->getValue('oobe_objects_done')) {
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
			self::$enabledObjectTypes[] = $ot;
			usort(self::$enabledObjectTypes, function($a, $b) {
				$enabled = OIDplus::config()->getValue("objecttypes_enabled");
				$enabled_ary = explode(';', $enabled);

				$idx_a = array_search($a::ns(), $enabled_ary);
				$idx_b = array_search($b::ns(), $enabled_ary);

				if ($idx_a == $idx_b) return 0;
				return ($idx_a > $idx_b) ? +1 : -1;
			});
		} else {
			self::$disabledObjectTypes[] = $ot;
		}

		if (!in_array($ns, $init_ary)) {
			// Was never initialized before, so we add it to the list of enabled object types once

			if ($do_enable) {
				$enabled_ary[] = $ns;
				// Important: Don't validate the input, because the other object types might not be initialized yet! So use setValueNoCallback() instead setValue().
				OIDplus::config()->setValueNoCallback("objecttypes_enabled", implode(';', $enabled_ary));
			}

			$init_ary[] = $ns;
			OIDplus::config()->setValue("objecttypes_initialized", implode(';', $init_ary));
		}
	}

	/**
	 * @return OIDplusObjectTypePlugin[]
	 */
	public static function getObjectTypePlugins(): array {
		return self::$objectTypePlugins;
	}

	/**
	 * @return OIDplusObjectTypePlugin[]
	 */
	public static function getObjectTypePluginsEnabled(): array {
		$res = array();
		foreach (self::$objectTypePlugins as $plugin) {
			$ot = $plugin::getObjectTypeClassName();
			if (in_array($ot, self::$enabledObjectTypes)) $res[] = $plugin;
		}
		return $res;
	}

	/**
	 * @return OIDplusObjectTypePlugin[]
	 */
	public static function getObjectTypePluginsDisabled(): array {
		$res = array();
		foreach (self::$objectTypePlugins as $plugin) {
			$ot = $plugin::getObjectTypeClassName();
			if (in_array($ot, self::$disabledObjectTypes)) $res[] = $plugin;
		}
		return $res;
	}

	/**
	 * @return string[]|OIDplusObject[] Classname of a OIDplusObject class
	 */
	public static function getEnabledObjectTypes(): array {
		return self::$enabledObjectTypes;
	}

	/**
	 * @return string[]|OIDplusObject[] Classname of a OIDplusObject class
	 */
	public static function getDisabledObjectTypes(): array {
		return self::$disabledObjectTypes;
	}

	// --- Plugin handling functions

	/**
	 * @return OIDplusPlugin[]
	 */
	public static function getAllPlugins(): array {
		$res = array();
		$res = array_merge($res, self::$pagePlugins);
		$res = array_merge($res, self::$authPlugins);
		$res = array_merge($res, self::$loggerPlugins);
		$res = array_merge($res, self::$objectTypePlugins);
		$res = array_merge($res, self::$dbPlugins);
		$res = array_merge($res, self::$captchaPlugins);
		$res = array_merge($res, self::$sqlSlangPlugins);
		$res = array_merge($res, self::$languagePlugins);
		return array_merge($res, self::$designPlugins);
	}

	/**
	 * @param string $oid
	 * @return OIDplusPlugin|null
	 */
	public static function getPluginByOid(string $oid)/*: ?OIDplusPlugin*/ {
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
	public static function getPluginByClassName(string $classname)/*: ?OIDplusPlugin*/ {
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
	 * @return bool true if plugin is enabled, false if plugin is disabled
	 * @throws OIDplusException if the class name or config file (disabled setting) does not contain a namespace
	 */
	private static function pluginCheckDisabled($class_name): bool {
		$path = explode('\\', $class_name);

		if (count($path) == 1) {
			throw new OIDplusException(_L('Plugin "%1" is erroneous',$class_name).': '._L('The plugin uses no namespaces. The new version of OIDplus requires plugin class files to be in a namespace. Please notify your plugin author and ask for an update.'));
		}

		$class_end = end($path);
		if (OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_'.$class_end, false)) {
			throw new OIDplusConfigInitializationException(_L('Your base configuration file is outdated. Please change "%1" to "%2".','DISABLE_PLUGIN_'.$class_end,'DISABLE_PLUGIN_'.$class_name));
		}

		if (OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_'.$class_name, false)) {
			return false;
		}

		return true;
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
			$ary = array_merge($ary,glob(OIDplus::localpath().'plugins/'.'*'.'/'.$pluginFolderMask.'/'.'*'.'/manifest.xml'));
		}

		// Sort the plugins by their type and name, as if they would be in a single vendor-folder!
		uasort($ary, function($a,$b) {
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

			$class_name = $manifest->getPhpMainClass();
			if ($class_name) if (!self::pluginCheckDisabled($class_name)) continue;

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
	public static function registerAllPlugins($pluginDirName, string $expectedPluginClass, callable $registerCallback=null): array {
		$out = array();
		if (is_array($pluginDirName)) {
			$ary = array();
			foreach ($pluginDirName as $pluginDirName_) {
				$ary = array_merge($ary, self::getAllPluginManifests($pluginDirName_, false));
			}
		} else {
			$ary = self::getAllPluginManifests($pluginDirName, false);
		}
		$known_plugin_oids = array();
		$known_main_classes_no_namespace = array();
		foreach ($ary as $plugintype_folder => $bry) {
			foreach ($bry as $vendor_folder => $cry) {
				foreach ($cry as $pluginname_folder => $manifest) {
					$class_name = $manifest->getPhpMainClass();

					// Before we load the plugin, we want to make some checks to confirm
					// that the plugin is working correctly.

					if (!$class_name) {
						throw new OIDplusException(_L('Plugin "%1" is erroneous', $vendor_folder . '/' . $plugintype_folder . '/' . $pluginname_folder) . ': ' . _L('Manifest does not declare a PHP main class'));
					}
					if (!self::pluginCheckDisabled($class_name)) {
						continue; // Plugin is disabled
					}

					// The auto-loader of OIDplus currently does not accept PHP namespaces.
					// Reason: The autoloader detects the classes inside plugins/*/*/*/*.class.php, but it cannot know
					//         which namespace these files have, because their folder names do not reveal the namespace.
					//         So it just ignores the namespace and loads all classes with the same name.
					// TODO: Think about a solution; There was a discussion here https://github.com/frdl/frdl-oidplus-plugin-type-pen/issues/1
					$tmp = explode('\\',$class_name);
					$class_name_no_namespace = end($tmp);
					if (in_array($class_name_no_namespace, $known_main_classes_no_namespace)) {
						// Removed check for now, since everything should work correctly
						// throw new OIDplusException(_L('More than one plugin has the PHP class name "%1". This is currently no supported, not even if they are in different namespaces.', $class_name_no_namespace));
					}
					$known_main_classes_no_namespace[] = $class_name_no_namespace;

					// Do some basic checks on the plugin PHP main class
					if (!class_exists($class_name)) {
						throw new OIDplusException(_L('Plugin "%1" is erroneous', $vendor_folder . '/' . $plugintype_folder . '/' . $pluginname_folder) . ': ' . _L('Manifest declares PHP main class as "%1", but it could not be found', $class_name));
					}
					if (!is_subclass_of($class_name, $expectedPluginClass)) {
						throw new OIDplusException(_L('Plugin "%1" is erroneous', $vendor_folder . '/' . $plugintype_folder . '/' . $pluginname_folder) . ': ' . _L('Plugin main class "%1" is expected to be a subclass of "%2"', $class_name, $expectedPluginClass));
					}
					if (($class_name != $manifest->getTypeClass()) && (!is_subclass_of($class_name, $manifest->getTypeClass()))) {
						throw new OIDplusException(_L('Plugin "%1" is erroneous', $vendor_folder . '/' . $plugintype_folder . '/' . $pluginname_folder) . ': ' . _L('Plugin main class "%1" is expected to be a subclass of "%2", according to type declared in manifest', $class_name, $manifest->getTypeClass()));
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
					$full_plugin_dir = dirname($manifest->getManifestFile());
					$full_plugin_dir = substr($full_plugin_dir, strlen(OIDplus::localpath()));
					$dir_is_viathinksoft = str_starts_with($full_plugin_dir, 'plugins/viathinksoft/') || str_starts_with($full_plugin_dir, 'plugins\\viathinksoft\\');
					$oid_is_viathinksoft = str_starts_with($plugin_oid, '1.3.6.1.4.1.37476.2.5.2.4.'); // { iso(1) identified-organization(3) dod(6) internet(1) private(4) enterprise(1) 37476 products(2) oidplus(5) v2(2) plugins(4) }
					$class_is_viathinksoft = str_starts_with($class_name, 'ViaThinkSoft\\');
					if ($oid_is_viathinksoft != $class_is_viathinksoft) {
						throw new OIDplusException(_L('Plugin "%1" is erroneous', $vendor_folder . '/' . $plugintype_folder . '/' . $pluginname_folder) . ': ' . _L('Third-party plugins must not use the ViaThinkSoft PHP namespace. Please use your own vendor namespace.'));
					}
					$plugin_is_viathinksoft = $oid_is_viathinksoft && $class_is_viathinksoft;
					if ($dir_is_viathinksoft != $plugin_is_viathinksoft) {
						throw new OIDplusException(_L('Plugin "%1" is misplaced', $vendor_folder . '/' . $plugintype_folder . '/' . $pluginname_folder) . ': ' . _L('The plugin is in the wrong folder. The folder %1 can only be used by official ViaThinkSoft plugins', 'plugins/viathinksoft/'));
					}

					// Additional check: does the plugin define JS/CSS although it is not an interactive plugin type?
					$has_js = $manifest->getJSFiles();
					$has_css = $manifest->getCSSFiles();
					$is_interactive = in_array(basename($plugintype_folder), OIDplus::INTERACTIVE_PLUGIN_TYPES);
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
					$obj = new $class_name();

					// Now we can continue
					$known_plugin_oids[$plugin_oid] = $vendor_folder . '/' . $plugintype_folder . '/' . $pluginname_folder;
					$out[] = $class_name;
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
	public static function init(bool $html=true, bool $keepBaseConfig=true) {
		self::$html = $html;

		// Reset internal state, so we can re-init verything if required

		self::$config = null;
		if (!$keepBaseConfig) self::$baseConfig = null;  // for test cases we need to be able to control base config and setting values manually, so $keepBaseConfig needs to be true
		self::$gui = null;
		self::$authUtils = null;
		self::$mailUtils = null;
		self::$menuUtils = null;
		self::$logger = null;
		self::$dbMainSession = null;
		self::$dbIsolatedSession = null;
		self::$pagePlugins = array();
		self::$authPlugins = array();
		self::$loggerPlugins = array();
		self::$objectTypePlugins = array();
		self::$enabledObjectTypes = array();
		self::$disabledObjectTypes = array();
		self::$dbPlugins = array();
		self::$captchaPlugins = array();
		self::$sqlSlangPlugins = array();
		self::$languagePlugins = array();
		self::$designPlugins = array();
		self::$system_id_cache = null;
		self::$sslAvailableCache = null;
		self::$translationArray = array();

		// Continue...

		OIDplus::baseConfig(); // this loads the base configuration located in userdata/baseconfig/config.inc.php (once!)
		// You can do changes to the configuration afterwards using OIDplus::baseConfig()->...

		// Register database types (highest priority)

		// SQL slangs

		self::registerAllPlugins('sqlSlang', OIDplusSqlSlangPlugin::class, array(OIDplus::class,'registerSqlSlangPlugin'));
		foreach (OIDplus::getSqlSlangPlugins() as $plugin) {
			$plugin->init($html);
		}

		// Database providers

		self::registerAllPlugins('database', OIDplusDatabasePlugin::class, array(OIDplus::class,'registerDatabasePlugin'));
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

		self::registerAllPlugins(array('publicPages', 'raPages', 'adminPages'), OIDplusPagePlugin::class, array(OIDplus::class,'registerPagePlugin'));
		self::registerAllPlugins('auth', OIDplusAuthPlugin::class, array(OIDplus::class,'registerAuthPlugin'));
		self::registerAllPlugins('logger', OIDplusLoggerPlugin::class, array(OIDplus::class,'registerLoggerPlugin'));
		self::logger()->reLogMissing(); // Some previous plugins might have tried to log. Repeat that now.
		self::registerAllPlugins('objectTypes', OIDplusObjectTypePlugin::class, array(OIDplus::class,'registerObjectTypePlugin'));
		self::registerAllPlugins('language', OIDplusLanguagePlugin::class, array(OIDplus::class,'registerLanguagePlugin'));
		self::registerAllPlugins('design', OIDplusDesignPlugin::class, array(OIDplus::class,'registerDesignPlugin'));
		self::registerAllPlugins('captcha', OIDplusCaptchaPlugin::class, array(OIDplus::class,'registerCaptchaPlugin'));

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
		foreach (OIDplus::getLanguagePlugins() as $plugin) {
			$plugin->init($html);
		}
		foreach (OIDplus::getDesignPlugins() as $plugin) {
			$plugin->init($html);
		}
		foreach (OIDplus::getCaptchaPlugins() as $plugin) {
			$plugin->init($html);
		}

		if (PHP_SAPI != 'cli') {

			// Prepare some security related response headers (default values)

			$content_language =
				strtolower(substr(OIDplus::getCurrentLang(),0,2)) . '-' .
				strtoupper(substr(OIDplus::getCurrentLang(),2,2)); // e.g. 'en-US'

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

			foreach (OIDplus::getSqlSlangPlugins() as $plugin) {
				$plugin->httpHeaderCheck($http_headers);
			}
			//foreach (OIDplus::getDatabasePlugins() as $plugin) {
			if ($plugin = OIDplus::getActiveDatabasePlugin()) {
				$plugin->httpHeaderCheck($http_headers);
			}
			foreach (OIDplus::getPagePlugins() as $plugin) {
				$plugin->httpHeaderCheck($http_headers);
			}
			foreach (OIDplus::getAuthPlugins() as $plugin) {
				$plugin->httpHeaderCheck($http_headers);
			}
			foreach (OIDplus::getLoggerPlugins() as $plugin) {
				$plugin->httpHeaderCheck($http_headers);
			}
			foreach (OIDplus::getObjectTypePlugins() as $plugin) {
				$plugin->httpHeaderCheck($http_headers);
			}
			foreach (OIDplus::getLanguagePlugins() as $plugin) {
				$plugin->httpHeaderCheck($http_headers);
			}
			foreach (OIDplus::getDesignPlugins() as $plugin) {
				$plugin->httpHeaderCheck($http_headers);
			}
			//foreach (OIDplus::getCaptchaPlugins() as $plugin) {
			if ($plugin = OIDplus::getActiveCaptchaPlugin()) {
				$plugin->httpHeaderCheck($http_headers);
			}

			// Prepare to send the headers to the client
			// The headers are sent automatically when the first output comes or the script ends

			foreach ($http_headers as $name => $val) {

				// Plugins can remove standard OIDplus headers by setting the value to null.
				if (is_null($val)) continue;

				// Some headers can be written as arrays to make it easier for plugin authors
				// to manipulate/extend the contents.
				if (is_array($val)) {
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

				if (is_string($val)) {
					@header("$name: $val");
				}
			}

		} // endif (PHP_SAPI != 'cli')

		// Initialize other stuff (i.e. things which require the logger!)

		OIDplus::recognizeSystemUrl(); // Make sure "last_known_system_url" is set
		OIDplus::recognizeVersion(); // Make sure "last_known_version" is set and a log entry is created
	}

	// --- System URL, System ID, PKI, and other functions

	/**
	 * @return void
	 */
	private static function recognizeSystemUrl() {
		try {
			$url = OIDplus::webpath(null,self::PATH_ABSOLUTE_CANONICAL);
			OIDplus::config()->setValue('last_known_system_url', $url);
		} catch (\Exception $e) {
		}
	}

	/**
	 * @return false|int
	 */
	private static function getExecutingScriptPathDepth() {
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
	 * @param int $mode If true or OIDplus::PATH_RELATIVE, the returning path is relative to the currently executed
	 *                  PHP script (i.e. index.php , not the plugin PHP script!). False or OIDplus::PATH_ABSOLUTE is
	 *                  results in an absolute URL. OIDplus::PATH_ABSOLUTE_CANONICAL is an absolute URL,
	 *                  but a canonical path (set by base config setting CANONICAL_SYSTEM_URL) is preferred.
	 * @return string|false The URL, with guaranteed trailing path delimiter for directories
	 * @throws OIDplusException
	 */
	private static function getSystemUrl(int $mode) {
		if ($mode === self::PATH_RELATIVE) {
			$steps_up = self::getExecutingScriptPathDepth();
			if ($steps_up === false) {
				return false;
			} else {
				return str_repeat('../', $steps_up);
			}
		} else {
			if ($mode === self::PATH_ABSOLUTE_CANONICAL) {
				$tmp = OIDplus::baseConfig()->getValue('CANONICAL_SYSTEM_URL', '');
				if ($tmp) {
					return rtrim($tmp,'/').'/';
				}
			}

			if (PHP_SAPI == 'cli') {
				try {
					return OIDplus::config()->getValue('last_known_system_url', false);
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
	private static function pubKeyToRaw(string $pubKey) {
		$m = array();
		if (preg_match('@BEGIN PUBLIC KEY\\-+([^\\-]+)\\-+END PUBLIC KEY@imU', $pubKey, $m)) {
			return base64_decode($m[1], false);
		}
		return false;
	}

	/**
	 * @param string $pubKey
	 * @return false|int
	 */
	private static function getSystemIdFromPubKey(string $pubKey) {
		$rawData = self::pubKeyToRaw($pubKey);
		if ($rawData === false) return false;
		return smallhash($rawData);
	}

	/**
	 * @param string $pubKey
	 * @return false|string
	 */
	private static function getSystemGuidFromPubKey(string $pubKey) {
		/*
		$rawData = self::pubKeyToRaw($pubKey);
		if ($rawData === false) return false;
		$normalizedBase64 = base64_encode($rawData);
		return gen_uuid_sha1_namebased(self::UUID_NAMEBASED_NS_Base64PubKey, $normalizedBase64);
		*/

		// https://github.com/danielmarschall/oidplus/blob/master/doc/oidplus_custom_guid.md
		$sysid = OIDplus::getSystemId(false);
		$sysid_int = $sysid ? $sysid : 0;
		return gen_uuid_v8(
			dechex($sysid_int),
			dechex(0), // Creation time of the system unknown
			dechex(0), // Reserved
			dechex(0), // 0=System, otherwise Object Namespace
			sha1('') // Objectname, empty string for system UUID
		);
	}

	private static $system_id_cache = null;

	/**
	 * @param bool $oid
	 * @return false|string
	 * @throws OIDplusException
	 */
	public static function getSystemId(bool $oid=false) {
		if (!is_null(self::$system_id_cache)) {
			$out = self::$system_id_cache;
		} else {
			$out = false;

			if (self::getPkiStatus(true)) {
				$pubKey = OIDplus::getSystemPublicKey();
				$out = self::getSystemIdFromPubKey($pubKey);
			}
			self::$system_id_cache = $out;
		}
		if (!$out) return false;
		return ($oid ? '1.3.6.1.4.1.37476.30.9.' : '').$out;
	}

	private static $system_guid_cache = null;

	/**
	 * @return false|string
	 * @throws OIDplusException
	 */
	public static function getSystemGuid() {
		if (!is_null(self::$system_guid_cache)) {
			$out = self::$system_guid_cache;
		} else {
			$out = false;

			if (self::getPkiStatus(true)) {
				$pubKey = OIDplus::getSystemPublicKey();
				$out = self::getSystemGuidFromPubKey($pubKey);
			}
			self::$system_guid_cache = $out;
		}
		if (!$out) return false;
		return $out;
	}

	/**
	 * @return array|string
	 */
	public static function getOpenSslCnf() {
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
	 * @return string
	 */
	private static function getPrivKeyPassphraseFilename(): string {
		return OIDplus::localpath() . 'userdata/privkey_secret.php';
	}

	/**
	 * @return void
	 */
	private static function tryCreatePrivKeyPassphrase() {
		$file = self::getPrivKeyPassphraseFilename();

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
	 * @return string|false
	 */
	private static function getPrivKeyPassphrase() {
		$file = self::getPrivKeyPassphraseFilename();
		if (!file_exists($file)) return false;
		$cont = file_get_contents($file);
		$m = array();
		if (!preg_match("@'(.+)'@isU", $cont, $m)) return false;
		return $m[1];
	}

	/**
	 * @return string|false
	 * @throws OIDplusException
	 */
	public static function getSystemPrivateKey() {
		$privKey = OIDplus::config()->getValue('oidplus_private_key');
		if ($privKey == '') return false;

		$passphrase = self::getPrivKeyPassphrase();
		if ($passphrase !== false) {
			$privKey = decrypt_private_key($privKey, $passphrase);
		}

		if (is_privatekey_encrypted($privKey)) {
			// This can happen if the key file has vanished
			return false;
		}

		return $privKey;
	}

	/**
	 * @return string|false
	 * @throws OIDplusException
	 */
	public static function getSystemPublicKey() {
		$pubKey = OIDplus::config()->getValue('oidplus_public_key');
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
			//OIDplus::config()->setValue('oidplus_private_key', '');

			$privKey = OIDplus::getSystemPrivateKey();
			$pubKey = OIDplus::getSystemPublicKey();
			if (!verify_private_public_key($privKey, $pubKey)) {
				if ($pubKey) {
					OIDplus::logger()->log("V2:[WARN]A", "The private/public key-pair is broken. A new key-pair will now be generated for your system. Your System-ID will change.");
				}

				$pkey_config = array(
					"digest_alg" => "sha512",
					"private_key_bits" => defined('OPENSSL_SUPPLEMENT') ? 1024 : 2048, // openssl_supplement.inc.php is based on phpseclib, which is very slow. So we use 1024 bits instead of 2048 bits
					"private_key_type" => OPENSSL_KEYTYPE_RSA,
					"config" => OIDplus::getOpenSslCnf()
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
				self::tryCreatePrivKeyPassphrase(); // *try* (re)generate this file
				$passphrase = self::getPrivKeyPassphrase();
				if ($passphrase !== false) {
					$privKey = encrypt_private_key($privKey, $passphrase);
				}

				// Calculate the system ID from the public key
				$system_id = self::getSystemIdFromPubKey($pubKey);
				if ($system_id !== false) {
					// Save the key pair to database
					OIDplus::config()->setValue('oidplus_private_key', $privKey);
					OIDplus::config()->setValue('oidplus_public_key', $pubKey);

					// Log the new system ID
					OIDplus::logger()->log("V2:[INFO]A", "A new private/public key-pair for your system had been generated. Your SystemID is now %1", $system_id);
				}
			} else {
				$passphrase = self::getPrivKeyPassphrase();
				$rawPrivKey = OIDplus::config()->getValue('oidplus_private_key');
				if (($passphrase === false) || !is_privatekey_encrypted($rawPrivKey)) {
					// Upgrade to new encrypted keys
					self::tryCreatePrivKeyPassphrase(); // *try* generate this file
					$passphrase = self::getPrivKeyPassphrase();
					if ($passphrase !== false) {
						$privKey = encrypt_private_key($privKey, $passphrase);
						OIDplus::logger()->log("V2:[INFO]A", "The private/public key-pair has been upgraded to an encrypted key-pair. The key is saved in %1", self::getPrivKeyPassphraseFilename());
						OIDplus::config()->setValue('oidplus_private_key', $privKey);
					}
				}
			}
		}

		$privKey = OIDplus::getSystemPrivateKey();
		$pubKey = OIDplus::getSystemPublicKey();
		return verify_private_public_key($privKey, $pubKey);
	}

	/**
	 * @return string|void
	 */
	public static function getInstallType() {
		$counter = 0;

		if ($new_version_file_exists = file_exists(OIDplus::localpath().'.version.php')) {
			$counter++;
		}
		if ($old_version_file_exists = file_exists(OIDplus::localpath().'oidplus_version.txt')) {
			$counter++;
		}
		$version_file_exists = $old_version_file_exists | $new_version_file_exists;

		if ($svn_dir_exists = (OIDplus::findSvnFolder() !== false)) {
			$counter++;
		}
		if ($git_dir_exists = (OIDplus::findGitFolder() !== false)) {
			$counter++;
		}

		if ($counter === 0) {
			return 'unknown'; // do not translate
		}
		else if ($counter > 1) {
			return 'ambigous'; // do not translate
		}
		else if ($svn_dir_exists) {
			return 'svn-wc'; // do not translate
		}
		else if ($git_dir_exists) {
			return 'git-wc'; // do not translate
		}
		else if ($version_file_exists) {
			return 'svn-snapshot'; // do not translate
		}
	}

	/**
	 * @return void
	 */
	private static function recognizeVersion() {
		try {
			if ($ver_now = OIDplus::getVersion()) {
				$ver_prev = OIDplus::config()->getValue("last_known_version");
				if (($ver_prev) && ($ver_now != $ver_prev)) {
					// TODO: Problem: When the system was updated using SVN or GIT in the console, then the IP address of the next random visitor of the website is logged!
					//       Idea: Maybe we should extend the mask code with some kind of magic constant "[NO_IP]", so that no IP is logged for that event?
					OIDplus::logger()->log("V2:[INFO]A", "Detected system version change from '%1' to '%2'", $ver_prev, $ver_now);

					// Just to be sure, recanonize objects (we don't do it at every page visit due to performance reasons)
					self::recanonizeObjects();
				}
				OIDplus::config()->setValue("last_known_version", $ver_now);
			}
		} catch (\Exception $e) {
		}
	}

	/**
	 * @return false|string
	 */
	public static function getVersion() {
		static $cachedVersion = null;
		if (!is_null($cachedVersion)) {
			return $cachedVersion;
		}

		$installType = OIDplus::getInstallType();

		if ($installType === 'svn-wc') {
			if (is_dir($svn_dir = OIDplus::findSvnFolder())) {
				$ver = get_svn_revision($svn_dir);
				if ($ver)
					return ($cachedVersion = 'svn-'.$ver);
			}
		}

		if ($installType === 'git-wc') {
			$ver = OIDplus::getGitsvnRevision();
			if ($ver)
				return ($cachedVersion = 'svn-'.$ver);
		}

		if ($installType === 'svn-snapshot') {
			$cont = '';
			if (file_exists($filename = OIDplus::localpath().'oidplus_version.txt'))
				$cont = file_get_contents($filename);
			if (file_exists($filename = OIDplus::localpath().'.version.php'))
				$cont = file_get_contents($filename);
			$m = array();
			if (preg_match('@Revision (\d+)@', $cont, $m)) // do not translate
				return ($cachedVersion = 'svn-'.$m[1]); // do not translate
		}

		return ($cachedVersion = false); // version ambigous or unknown
	}

	const ENFORCE_SSL_NO   = 0;
	const ENFORCE_SSL_YES  = 1;
	const ENFORCE_SSL_AUTO = 2;

	/**
	 * @var bool|null
	 */
	private static $sslAvailableCache = null;

	/**
	 * @return bool
	 * @throws OIDplusException, OIDplusConfigInitializationException
	 */
	public static function isSslAvailable(): bool {
		if (!is_null(self::$sslAvailableCache)) return self::$sslAvailableCache;

		if (PHP_SAPI == 'cli') {
			self::$sslAvailableCache = false;
			return false;
		}

		$timeout = 2;
		$already_ssl = self::isSSL();
		$ssl_port = 443;
		$host_with_port = $_SERVER['HTTP_HOST'];
		$host_no_port = explode(':',$host_with_port)[0];
		$host_ssl = $host_no_port . ($ssl_port != 443 ? ':'.$ssl_port : '');

		if ($already_ssl) {
			OIDplus::cookieUtils()->setcookie('SSL_CHECK', '1', 0, true/*allowJS*/, null/*samesite*/, true/*forceInsecure*/);
			self::$sslAvailableCache = true;
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
				$mode = OIDplus::ENFORCE_SSL_YES;
			} else {
				$mode = OIDplus::baseConfig()->getValue('ENFORCE_SSL', OIDplus::ENFORCE_SSL_AUTO);
			}

			if ($mode == OIDplus::ENFORCE_SSL_NO) {
				// No SSL available
				self::$sslAvailableCache = false;
				return false;
			} else if ($mode == OIDplus::ENFORCE_SSL_YES) {
				// Force SSL
				$location = 'https://' . $host_ssl . $_SERVER['REQUEST_URI'];
				header('Location:'.$location);
				die(_L('Redirecting to HTTPS...'));
			} else if ($mode == OIDplus::ENFORCE_SSL_AUTO) {
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
						self::$sslAvailableCache = false;
						return false;
					}
				} else {
					// This is our first check (or the browser didn't accept the SSL_CHECK cookie)
					$errno = -1;
					$errstr = '';
					if (@fsockopen($host_no_port, $ssl_port, $errno, $errstr, $timeout)) {
						// HTTPS detected. Redirect now, and remember that we had detected HTTPS
						OIDplus::cookieUtils()->setcookie('SSL_CHECK', '1', 0, true/*allowJS*/, null/*samesite*/, true/*forceInsecure*/);
						$location = 'https://' . $host_ssl . $_SERVER['REQUEST_URI'];
						header('Location:'.$location);
						die(_L('Redirecting to HTTPS...'));
					} else {
						// No HTTPS detected. Do nothing, and next time, don't try to detect HTTPS again.
						OIDplus::cookieUtils()->setcookie('SSL_CHECK', '0', 0, true/*allowJS*/, null/*samesite*/, true/*forceInsecure*/);
						self::$sslAvailableCache = false;
						return false;
					}
				}
			} else {
				assert(false);
				return false;
			}
		}
	}

	/**
	 * Gets a local path pointing to a resource
	 * @param string|null $target Target resource (file or directory must exist), or null to get the OIDplus base directory
	 * @param bool $relative If true, the returning path is relative to the currently executed PHP file (not the CLI working directory)
	 * @return string|false The local path, with guaranteed trailing path delimiter for directories
	 */
	public static function localpath(string $target=null, bool $relative=false) {
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
	 * @param int|bool $mode If true or OIDplus::PATH_RELATIVE, the returning path is relative to the currently executed
	 *                          PHP script (i.e. index.php , not the plugin PHP script!). False or OIDplus::PATH_ABSOLUTE is
	 *                          results in an absolute URL. OIDplus::PATH_ABSOLUTE_CANONICAL is an absolute URL,
	 *                          but a canonical path (set by base config setting CANONICAL_SYSTEM_URL) is preferred.
	 * @return string|false The URL, with guaranteed trailing path delimiter for directories
	 * @throws OIDplusException
	 */
	public static function webpath(string $target=null, $mode=self::PATH_ABSOLUTE_CANONICAL) {
		// backwards compatibility
		if ($mode === true) $mode = self::PATH_RELATIVE;
		if ($mode === false) $mode = self::PATH_ABSOLUTE;

		if ($mode == OIDplus::PATH_RELATIVE_TO_ROOT) {
			$tmp = OIDplus::webpath($target,OIDplus::PATH_ABSOLUTE);
			if ($tmp === false) return false;
			$tmp = parse_url($tmp);
			if ($tmp === false) return false;
			if (!isset($tmp['path'])) return false;
			return $tmp['path'];
		}

		if ($mode == OIDplus::PATH_RELATIVE_TO_ROOT_CANONICAL) {
			$tmp = OIDplus::webpath($target,OIDplus::PATH_ABSOLUTE_CANONICAL);
			if ($tmp === false) return false;
			$tmp = parse_url($tmp);
			if ($tmp === false) return false;
			if (!isset($tmp['path'])) return false;
			return $tmp['path'];
		}

		$res = self::getSystemUrl($mode); // Note: already contains a trailing path delimiter
		if ($res === false) return false;

		if (!is_null($target)) {
			$basedir = realpath(__DIR__.'/../../');
			$target = realpath($target);
			if ($target === false) return false;
			$tmp = substr($target, strlen($basedir)+1);
			$res .= str_replace(DIRECTORY_SEPARATOR,'/',$tmp); // replace OS specific path delimiters introduced by realpath()
			if (is_dir($target)) $res .= '/';
		}

		return $res;
	}

	/**
	 * Note: canonicalURL() is different than webpath(),
	 * because it does additional things like re-ordering of arguments
	 * @param string|null $goto
	 * @return false|string
	 * @throws OIDplusException
	 */
	public static function canonicalURL(string $goto=null) {
		// First part: OIDplus system URL (or canonical system URL)
		$sysurl = OIDplus::getSystemUrl(self::PATH_ABSOLUTE_CANONICAL);

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
		parse_str($_SERVER['QUERY_STRING'], $url);
		if ($goto !== null) $url['goto'] = $goto;
		ksort($url);
		$tmp2 = http_build_query($url);
		if ($tmp2 != '') $tmp2 = '?'.$tmp2;

		return $sysurl.$res.$tmp.$tmp2;
	}

	private static $shutdown_functions = array();

	/**
	 * @param callable $func
	 * @return void
	 */
	public static function register_shutdown_function(callable $func) {
		self::$shutdown_functions[] = $func;
	}

	/**
	 * @return void
	 */
	public static function invoke_shutdown() {
		foreach (self::$shutdown_functions as $func) {
			$func();
		}
	}

	/**
	 * @return string[]
	 * @throws OIDplusException
	 */
	public static function getAvailableLangs(): array {
		$langs = array();
		foreach (OIDplus::getAllPluginManifests('language') as $pluginManifest) {
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
		static $thrownOnce = false; // avoid endless loop inside OIDplusConfigInitializationException

		$lang = self::baseConfig()->getValue('DEFAULT_LANGUAGE', 'enus');

		if (!in_array($lang,self::getAvailableLangs())) {
			if (!$thrownOnce) {
				$thrownOnce = true;
				throw new OIDplusConfigInitializationException(_L('DEFAULT_LANGUAGE points to an invalid language plugin. (Consider setting to "enus" = "English USA".)'));
			} else {
				return 'enus';
			}
		}

		return $lang;
	}

	/**
	 * @return false|string
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	public static function getCurrentLang() {

		$rel_url = substr($_SERVER['REQUEST_URI'], strlen(OIDplus::webpath(null, OIDplus::PATH_RELATIVE_TO_ROOT)));
		if (str_starts_with($rel_url, 'rest/')) { // <== TODO: Find a way how to move this into the plugin, since REST does not belong to the core. (Maybe some kind of "stateless mode" that is enabled by the REST plugin)
			return self::getDefaultLang();
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
	public static function handleLangArgument() {
		if (isset($_GET['lang'])) {
			// The "?lang=" argument is only for NoScript-Browsers/SearchEngines
			// In case someone who has JavaScript clicks a ?lang= link, they should get
			// the page in that language, but the cookie must be set, otherwise
			// the menu and other stuff would be in their cookie-based-language and not the
			// argument-based-language.
			OIDplus::cookieUtils()->setcookie('LANGUAGE', $_GET['lang'], 0, true/*HttpOnly off, because JavaScript also needs translation*/);
		} else if (isset($_POST['lang'])) {
			OIDplus::cookieUtils()->setcookie('LANGUAGE', $_POST['lang'], 0, true/*HttpOnly off, because JavaScript also needs translation*/);
		}
	}

	private static $translationArray = array();

	/**
	 * @param string $translation_file
	 * @return array
	 */
	protected static function getTranslationFileContents(string $translation_file): array {
		// First, try the cache
		$cache_file = __DIR__ . '/../../userdata/cache/translation_'.md5($translation_file).'.ser';
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
		foreach (OIDplus::getAllPluginManifests('language') as $pluginManifest) {
			$lang = $pluginManifest->getLanguageCode();
			if (strpos($lang,'/') !== false) continue; // just to be sure
			if (strpos($lang,'\\') !== false) continue; // just to be sure
			if (strpos($lang,'..') !== false) continue; // just to be sure

			if (($requested_lang != '*') && ($lang != $requested_lang)) continue;

			if (!isset(self::$translationArray[$lang])) {
				self::$translationArray[$lang] = array();

				$wildcard = $pluginManifest->getLanguageMessages();
				if (strpos($wildcard,'/') !== false) continue; // just to be sure
				if (strpos($wildcard,'\\') !== false) continue; // just to be sure
				if (strpos($wildcard,'..') !== false) continue; // just to be sure

				$translation_files = glob(__DIR__.'/../../plugins/'.'*'.'/language/'.$lang.'/'.$wildcard);
				sort($translation_files);
				foreach ($translation_files as $translation_file) {
					if (!file_exists($translation_file)) continue;
					$cac = self::getTranslationFileContents($translation_file);
					foreach ($cac as $src => $dst) {
						self::$translationArray[$lang][$src] = $dst;
					}
				}
			}
		}
		return self::$translationArray;
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
	public static function findGitFolder() {
		$dir = rtrim(OIDplus::localpath(), DIRECTORY_SEPARATOR);

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
					if (isset(OIDplus::getEditionInfo()['gitrepo']) && (OIDplus::getEditionInfo()['gitrepo'] != '') && (strpos($cont, OIDplus::getEditionInfo()['gitrepo']) !== false)) {
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
	public static function findSvnFolder() {
		$dir = rtrim(OIDplus::localpath(), DIRECTORY_SEPARATOR);

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
	public static function getGitsvnRevision() {
		try {
			$git_dir = OIDplus::findGitFolder();
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
		foreach (OIDplus::getObjectTypePluginsEnabled() as $plugin) {
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
	private static function recanonizeObjects() {
		$res = OIDplus::db()->query("select id from ###objects");
		while ($row = $res->fetch_array()) {
			$ida = $row['id'];
			$obj = OIDplusObject::parse($ida);
			if (!$obj) continue;
			$idb = $obj->nodeId();
			if (($idb) && ($ida != $idb)) {
				if (OIDplus::db()->transaction_supported()) OIDplus::db()->transaction_begin();
				try {
					OIDplus::db()->query("update ###objects set id = ? where id = ?", array($idb, $ida));
					OIDplus::db()->query("update ###asn1id set oid = ? where oid = ?", array($idb, $ida));
					OIDplus::db()->query("update ###iri set oid = ? where oid = ?", array($idb, $ida));
					OIDplus::db()->query("update ###log_object set object = ? where object = ?", array($idb, $ida));
					OIDplus::logger()->log("V2:[INFO]A", "Object name '%1' has been changed to '%2' during re-canonization", $ida, $idb);
					if (OIDplus::db()->transaction_supported()) OIDplus::db()->transaction_commit();
				} catch (\Exception $e) {
					if (OIDplus::db()->transaction_supported()) OIDplus::db()->transaction_rollback();
					throw $e;
				}
				OIDplusObject::resetObjectInformationCache();
			}
		}
	}

}
