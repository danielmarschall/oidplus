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
	private static /*OIDplusLanguagePlugin[]*/ $languagePlugins = array();
	private static /*OIDplusDesignPlugin[]*/ $designPlugins = array();

	protected static $html = true;

	/*public*/ const DEFAULT_LANGUAGE = 'enus'; // the language of the source code

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
			// It is important to include it before userdata/baseconfig/config.inc.php was included,
			// so we can give userdata/baseconfig/config.inc.php the chance to override the values.

			include OIDplus::basePath().'/includes/oidplus_limits.inc.php';

			// Include config file

			$config_file = OIDplus::basePath() . '/userdata/baseconfig/config.inc.php';
			$config_file_old = OIDplus::basePath() . '/includes/config.inc.php'; // backwards compatibility

			if (!file_exists($config_file) && file_exists($config_file_old)) {
				$config_file = $config_file_old;
			}

			if (file_exists($config_file)) {
				if (self::$old_config_format) {
					// Note: We may only include it once due to backwards compatibility,
					//       since in version 2.0, the configuration was defined using define() statements
					// Attention: This does mean that a full re-init (e.g. for test cases) is not possible
					//            if a version 2.0 config is used!
					include_once $config_file;
				} else {
					include $config_file;
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
				if (!is_dir(OIDplus::basePath().'/setup')) {
					throw new OIDplusConfigInitializationException(_L('File %1 is missing, but setup can\'t be started because its directory missing.','userdata/baseconfig/config.inc.php'));
				} else {
					if (self::$html) {
						if (strpos($_SERVER['REQUEST_URI'], OIDplus::getSystemUrl(true).'setup/') !== 0) {
							header('Location:'.OIDplus::getSystemUrl().'setup/');
							die(_L('Redirecting to setup...'));
						} else {
							return self::$baseConfig;
						}
					} else {
						// This can be displayed in e.g. ajax.php
						throw new OIDplusConfigInitializationException(_L('File %1 is missing. Please run setup again.','userdata/baseconfig/config.inc.php'));
					}
				}
			}

			// Check important config settings

			if (self::$baseConfig->getValue('CONFIG_VERSION') != 2.1) {
				throw new OIDplusConfigInitializationException(_L("The information located in %1 is outdated.",$config_file));
			}

			if (self::$baseConfig->getValue('SERVER_SECRET', '') === '') {
				throw new OIDplusConfigInitializationException(_L("You must set a value for SERVER_SECRET in %1 for the system to operate secure.",$config_file));
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
					throw new OIDplusException(_L('Please enter a value for the system title.'));
				}
			});
			self::$config->prepareConfigKey('admin_email', 'E-Mail address of the system administrator', '', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
				if (!empty($value) && !OIDplus::mailUtils()->validMailAddress($value)) {
					throw new OIDplusException(_L('This is not a correct email address'));
				}
			});
			self::$config->prepareConfigKey('global_cc', 'Global CC for all outgoing emails?', '', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
				if (!empty($value) && !OIDplus::mailUtils()->validMailAddress($value)) {
					throw new OIDplusException(_L('This is not a correct email address'));
				}
			});
			self::$config->prepareConfigKey('design', 'Which design to use (must exist in plugins/design/)?', 'default', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
				$good = true;
				if (strpos($value,'/') !== false) $good = false;
				if (strpos($value,'\\') !== false) $good = false;
				if (strpos($value,'..') !== false) $good = false;
				if (!$good) {
					throw new OIDplusException(_L('Invalid design folder name. Do only enter a folder name, not an absolute or relative path'));
				}

				if (!is_dir(__DIR__.'/../../plugins/design/'.$value)) {
					throw new OIDplusException(_L('The design "%1" does not exist in plugin directory %2',$value,'plugins/design/'));
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

		if (isset(self::$sqlSlangPlugins[$name])) {
			$plugintype_hf = _L('SQL-Slang');
			throw new OIDplusException('Multiple %1 plugins use the ID %2', $plugintype_hf, $name);
		}

		self::$sqlSlangPlugins[$name] = $plugin;

		return true;
	}

	public static function getSqlSlangPlugins() {
		return self::$sqlSlangPlugins;
	}

	public static function getSqlSlangPlugin($id)/*: ?OIDplusSqlSlangPlugin*/ {
		if (isset(self::$sqlSlangPlugins[$id])) {
			return self::$sqlSlangPlugins[$id];
		} else {
			return null;
		}
	}

	# --- Database plugin

	private static function registerDatabasePlugin(OIDplusDatabasePlugin $plugin) {
		$name = $plugin::id();
		if ($name === false) return false;

		if (isset(self::$dbPlugins[$name])) {
			$plugintype_hf = _L('Database');
			throw new OIDplusException('Multiple %1 plugins use the ID %2', $plugintype_hf, $name);
		}

		self::$dbPlugins[$name] = $plugin;

		return true;
	}

	public static function getDatabasePlugins() {
		return self::$dbPlugins;
	}

	public static function getActiveDatabasePlugin() {
		if (OIDplus::baseConfig()->getValue('DATABASE_PLUGIN', '') === '') {
			throw new OIDplusConfigInitializationException(_L('No database plugin selected in config file'));
		}
		if (!isset(self::$dbPlugins[OIDplus::baseConfig()->getValue('DATABASE_PLUGIN')])) {
			$db_plugin_name = OIDplus::baseConfig()->getValue('DATABASE_PLUGIN');
			throw new OIDplusConfigInitializationException(_L('Database plugin "%1" not found',$db_plugin_name));
		}
		return self::$dbPlugins[OIDplus::baseConfig()->getValue('DATABASE_PLUGIN')];
	}

	private static $dbMainSession = null;
	public static function db() {
		if (is_null(self::$dbMainSession)) {
			self::$dbMainSession = self::getActiveDatabasePlugin()->newConnection();
		}
		if (!self::$dbMainSession->isConnected()) self::$dbMainSession->connect();
		return self::$dbMainSession;
	}

	private static $dbIsolatedSession = null;
	public static function dbIsolated() {
		if (is_null(self::$dbIsolatedSession)) {
			self::$dbIsolatedSession = self::getActiveDatabasePlugin()->newConnection();
		}
		if (!self::$dbIsolatedSession->isConnected()) self::$dbIsolatedSession->connect();
		return self::$dbIsolatedSession;
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

	# --- Language plugin

	private static function registerLanguagePlugin(OIDplusLanguagePlugin $plugin) {
		self::$languagePlugins[] = $plugin;
		return true;
	}

	public static function getLanguagePlugins() {
		return self::$languagePlugins;
	}

	# --- Design plugin

	private static function registerDesignPlugin(OIDplusDesignPlugin $plugin) {
		self::$designPlugins[] = $plugin;
		return true;
	}

	public static function getDesignPlugins() {
		return self::$designPlugins;
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

		if (empty($ns)) throw new OIDplusException(_L('Attention: Empty NS at %1',$ot));

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

		$do_enable = false;
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

	public static function getAllPlugins()/*: array*/ {
		$res = array();
		$res = array_merge($res, self::$pagePlugins);
		$res = array_merge($res, self::$authPlugins);
		$res = array_merge($res, self::$loggerPlugins);
		$res = array_merge($res, self::$objectTypePlugins);
		$res = array_merge($res, self::$dbPlugins);
		$res = array_merge($res, self::$sqlSlangPlugins);
		$res = array_merge($res, self::$languagePlugins);
		$res = array_merge($res, self::$designPlugins);
		return $res;
	}

	public static function getPluginByOid($oid)/*: ?OIDplusPlugin*/ {
		$plugins = self::getAllPlugins();
		foreach ($plugins as $plugin) {
			if (oid_dotnotation_equal($plugin->getManifest()->getOid(), $oid)) {
				return $plugin;
			}
		}
		return null;
	}

	public static function getPluginByClassName($classname)/*: ?OIDplusPlugin*/ {
		$plugins = self::getAllPlugins();
		foreach ($plugins as $plugin) {
			if (get_class($plugin) === $classname) {
				return $plugin;
			}
		}
		return null;
	}

	public static function getAllPluginManifests($pluginFolderMask='*', $flat=true): array {
		$out = array();
		// Note: glob() will sort by default, so we do not need a page priority attribute.
		//       So you just need to use a numeric plugin directory prefix (padded).
		$ary = glob(OIDplus::basePath().'/plugins/'.$pluginFolderMask.'/'.'*'.'/manifest.xml');
		sort($ary);
		foreach ($ary as $ini) {
			if (!file_exists($ini)) continue;

			$manifest = new OIDplusPluginManifest();
			$manifest->loadManifest($ini);

			if ($flat) {
				$out[] = $manifest;
			} else {
				$plugintype_folder = basename(dirname(dirname($ini)));
				$pluginname_folder = basename(dirname($ini));

				if (!isset($out[$plugintype_folder])) $out[$plugintype_folder] = array();
				if (!isset($out[$plugintype_folder][$pluginname_folder])) $out[$plugintype_folder][$pluginname_folder] = array();
				$out[$plugintype_folder][$pluginname_folder] = $manifest;
			}
		}
		return $out;
	}

	public static function registerAllPlugins($pluginDirName, $expectedPluginClass, $registerCallback): array {
		$out = array();
		$ary = self::getAllPluginManifests($pluginDirName, false);
		$known_plugin_oids = array();
		$fake_feature = uuid_to_oid(gen_uuid());
		foreach ($ary as $plugintype_folder => $bry) {
			foreach ($bry as $pluginname_folder => $manifest) {
				$class_name = $manifest->getPhpMainClass();

				// Before we load the plugin, we want to make some checks to confirm
				// that the plugin is working correctly.

				if (!$class_name) {
					throw new OIDplusException(_L('Plugin "%1/%2" is erroneous',$plugintype_folder,$pluginname_folder).': '._L('Manifest does not declare a PHP main class'));
				}
				if (OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_'.$class_name, false)) {
					continue;
				}
				if (!class_exists($class_name)) {
					throw new OIDplusException(_L('Plugin "%1/%2" is erroneous',$plugintype_folder,$pluginname_folder).': '._L('Manifest declares PHP main class as "%1", but it could not be found',$class_name));
				}
				if (!is_subclass_of($class_name, $expectedPluginClass)) {
					throw new OIDplusException(_L('Plugin "%1/%2" is erroneous',$plugintype_folder,$pluginname_folder).': '._L('Plugin main class "%1" is expected to be a subclass of "%2"',$class_name,$expectedPluginClass));
				}
				if (($class_name!=$manifest->getTypeClass()) && (!is_subclass_of($class_name,$manifest->getTypeClass()))) {
					throw new OIDplusException(_L('Plugin "%1/%2" is erroneous',$plugintype_folder,$pluginname_folder).': '._L('Plugin main class "%1" is expected to be a subclass of "%2", according to type declared in manifest',$class_name,$manifest->getTypeClass()));
				}
				if (($manifest->getTypeClass()!=$expectedPluginClass) && (!is_subclass_of($manifest->getTypeClass(),$expectedPluginClass))) {
					throw new OIDplusException(_L('Plugin "%1/%2" is erroneous',$plugintype_folder,$pluginname_folder).': '._L('Class declared in manifest is "%1" does not fit expected class for this plugin type "%2"',$manifest->getTypeClass(),$expectedPluginClass));
				}

				$plugin_oid = $manifest->getOid();
				if (!$plugin_oid) {
					throw new OIDplusException(_L('Plugin "%1/%2" is erroneous',$plugintype_folder,$pluginname_folder).': '._L('Does not have an OID'));
				}
				if (!oid_valid_dotnotation($plugin_oid, false, false, 2)) {
					throw new OIDplusException(_L('Plugin "%1/%2" is erroneous',$plugintype_folder,$pluginname_folder).': '._L('Plugin OID "%1" is invalid (needs to be valid dot-notation)',$plugin_oid));
				}
				if (isset($known_plugin_oids[$plugin_oid])) {
					throw new OIDplusException(_L('Plugin "%1/%2" is erroneous',$plugintype_folder,$pluginname_folder).': '._L('The OID "%1" is already used by the plugin "%2"',$plugin_oid,$known_plugin_oids[$plugin_oid]));
				} else {
					$known_plugin_oids[$plugin_oid] = $plugintype_folder.'/'.$pluginname_folder;
				}

				$obj = new $class_name();
				if ($obj->implementsFeature($fake_feature)) {
					// see https://devblogs.microsoft.com/oldnewthing/20040211-00/?p=40663
					throw new OIDplusException(_L('Plugin "%1/%2" is erroneous',$plugintype_folder,$pluginname_folder).': '._L('implementsFeature() always returns true'));
				}

				// TODO: Maybe as additional plugin-test, we should also check if plugins are allowed to define CSS/JS (since only page plugins may have them!)
				$tmp = array_merge(
					$manifest->getJSFiles(),
					$manifest->getCSSFiles(),
					$manifest->getJSFilesSetup(),
					$manifest->getCSSFilesSetup()
				);
				foreach ($tmp as $file) {
					if (!file_exists($file)) {
						throw new OIDplusException(_L('Plugin "%1/%2" is erroneous',$plugintype_folder,$pluginname_folder).': '._L('File %1 was defined in manifest, but it is not existing',$file));
					}
				}

				// Now we can continue

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
		return $out;
	}

	# --- Initialization of OIDplus

	public static function init($html=true, $keepBaseConfig=true) {
		self::$html = $html;

		// Reset internal state, so we can re-init verything if required

		if (self::$old_config_format) {
			// Note: This can only happen in very special cases (e.g. test cases) where you call init() twice
			throw new OIDplusConfigInitializationException(_L('A full re-initialization is not possible if a version 2.0 config file (containing "defines") is used. Please update to a config 2.1 file by running setup again.'));
		}

		self::$config = null;
		if (!$keepBaseConfig) self::$baseConfig = null;  // for test cases we need to be able to control base config and setting values manually, so $keepBaseConfig needs to be true
		self::$gui = null;
		self::$authUtils = null;
		self::$mailUtils = null;
		self::$menuUtils = null;
		self::$logger = null;
		self::$sesHandler = null;
		self::$dbMainSession = null;
		self::$dbIsolatedSession = null;
		self::$pagePlugins = array();
		self::$authPlugins = array();
		self::$loggerPlugins = array();
		self::$objectTypePlugins = array();
		self::$enabledObjectTypes = array();
		self::$disabledObjectTypes = array();
		self::$dbPlugins = array();
		self::$sqlSlangPlugins = array();
		self::$languagePlugins = array();
		self::$designPlugins = array();
		self::$system_id_cache = null;
		self::$sslAvailableCache = null;

		// Continue...

		OIDplus::baseConfig(); // this loads the base configuration located in userdata/baseconfig/config.inc.php (once!)
		                       // You can do changes to the configuration afterwards using OIDplus::baseConfig()->...

		// Register database types (highest priority)

		// SQL slangs

		self::registerAllPlugins('sqlSlang', 'OIDplusSqlSlangPlugin', array('OIDplus','registerSqlSlangPlugin'));
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
		self::registerAllPlugins('language', 'OIDplusLanguagePlugin', array('OIDplus','registerLanguagePlugin'));
		self::registerAllPlugins('design', 'OIDplusDesignPlugin', array('OIDplus','registerDesignPlugin'));

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

		// Initialize other stuff (i.e. things which require the logger!)

		OIDplus::recognizeSystemUrl(); // Make sure "last_known_system_url" is set
		OIDplus::recognizeVersion(); // Make sure "last_known_version" is set and a log entry is created
	}

	# --- System URL, System ID, PKI, and other functions

	public static function basePath() {
		return realpath(__DIR__ . '/../../');
	}

	private static function recognizeSystemUrl() {
		try {
			$url = OIDplus::getSystemUrl();
			OIDplus::config()->setValue('last_known_system_url', $url);
		} catch (Exception $e) {
		}
	}

	public static function getSystemUrl($relative=false) {
		if (!$relative) {
			$res = OIDplus::baseConfig()->getValue('EXPLICIT_ABSOLUTE_SYSTEM_URL', '');
			if ($res !== '') {
				return $res;
			}
		}

		if (!isset($_SERVER["SCRIPT_NAME"])) return false;

		$test_dir = dirname($_SERVER['SCRIPT_FILENAME']);
		$test_dir = str_replace('\\', '/', $test_dir);
		$c = 0;
		// We just assume that only the OIDplus base directory contains "oidplus.min.css.php" and not any subsequent directory!
		while (!file_exists($test_dir.'/oidplus.min.css.php')) {
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
			if (php_sapi_name() == 'cli') {
				try {
					return OIDplus::config()->getValue('last_known_system_url', false);
				} catch (Exception $e) {
					return false;
				}
			}

			$is_ssl = isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on');
			$protocol = $is_ssl ? 'https' : 'http'; // do not translate
			$host = $_SERVER['HTTP_HOST']; // includes port if it is not 80/443
			$res = $protocol.'://'.$host.$res;
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
				$m = array();
				if (preg_match('@BEGIN PUBLIC KEY\-+(.+)\-+END PUBLIC KEY@ismU', $pubKey, $m)) {
					$out = smallhash(base64_decode($m[1]));
				}
			}
			self::$system_id_cache = $out;
		}
		if (!$out) return false;
		return ($oid ? '1.3.6.1.4.1.37476.30.9.' : '').$out;
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
			$m = array();
			if (preg_match('@BEGIN PUBLIC KEY\-+(.+)\-+END PUBLIC KEY@ismU', $pubKey, $m)) {
				$system_id = smallhash(base64_decode($m[1]));
				OIDplus::logger()->log("[INFO]A!", "Your SystemID is now $system_id");
			}
		}

		return verify_private_public_key($privKey, $pubKey);
	}

	public static function getInstallType() {
		if (!file_exists(OIDplus::basePath().'/oidplus_version.txt') && !is_dir(OIDplus::basePath().'/.svn')) {
			return 'unknown'; // do not translate
		}
		if (file_exists(OIDplus::basePath().'/oidplus_version.txt') && is_dir(OIDplus::basePath().'/.svn')) {
			return 'ambigous'; // do not translate
		}
		if (is_dir(OIDplus::basePath().'/.svn')) {
			return 'svn-wc'; // do not translate
		}
		if (file_exists(OIDplus::basePath().'/oidplus_version.txt')) {
			return 'svn-snapshot'; // do not translate
		}
	}

	private static function recognizeVersion() {
		try {
			$ver_prev = OIDplus::config()->getValue("last_known_version");
			$ver_now = OIDplus::getVersion();
			if (($ver_now != '') && ($ver_prev != '') && ($ver_now != $ver_prev)) {
				OIDplus::logger()->log("[INFO]A!", "System version changed from '$ver_prev' to '$ver_now'");
			}
			OIDplus::config()->setValue("last_known_version", $ver_now);
		} catch (Exception $e) {
		}
	}

	public static function getVersion() {
		static $cachedVersion = null;
		if (!is_null($cachedVersion)) {
			return $cachedVersion;
		}

		if (file_exists(OIDplus::basePath().'/oidplus_version.txt') && is_dir(OIDplus::basePath().'/.svn')) {
			return ($cachedVersion = false); // version is ambiguous
		}

		if (is_dir(OIDplus::basePath().'/.svn')) {
			// Try to get the version via SQLite3
			if (class_exists('SQLite3')) {
				try {
					$db = new SQLite3(OIDplus::basePath().'/.svn/wc.db');
					$results = $db->query('SELECT MIN(revision) AS rev FROM NODES_BASE');
					while ($row = $results->fetchArray()) {
						return ($cachedVersion = 'svn-'.$row['rev']); // do not translate
					}
					$db->close();
					$db = null;
				} catch (Exception $e) {
				}
			}
			if (class_exists('PDO')) {
				try {
					$pdo = new PDO('sqlite:' . OIDplus::basePath().'/.svn/wc.db');
					$res = $pdo->query('SELECT MIN(revision) AS rev FROM NODES_BASE');
					$row = $res->fetch();
					if ($row !== false) {
						return ($cachedVersion = 'svn-'.$row['rev']); // do not translate
					}
					$pdo = null;
				} catch (Exception $e) {
				}
			}

			// Try to find out the SVN version using the shell
			// We don't prioritize this method, because a failed shell access will flood the apache error log with STDERR messages
			$output = @shell_exec('svnversion '.escapeshellarg(OIDplus::basePath()));
			$match = array();
			if (preg_match('/\d+/', $output, $match)) {
				return ($cachedVersion = 'svn-'.$match[0]); // do not translate
			}

			$output = @shell_exec('svn info '.escapeshellarg(OIDplus::basePath()));
			if (preg_match('/Revision:\s*(\d+)/m', $output, $match)) { // do not translate
				return ($cachedVersion = 'svn-'.$match[1]); // do not translate
			}
		}

		if (file_exists(OIDplus::basePath().'/oidplus_version.txt')) {
			$cont = file_get_contents(OIDplus::basePath().'/oidplus_version.txt');
			$m = array();
			if (preg_match('@Revision (\d+)@', $cont, $m)) // do not translate
				return ($cachedVersion = 'svn-'.$m[1]); // do not translate
		}

		return ($cachedVersion = false);
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
				die(_L('Redirecting to HTTPS...'));
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
						die(_L('Redirecting to HTTPS...'));
						self::$sslAvailableCache = true;
						return true;
					} else {
						// No HTTPS available. Do nothing.
						self::$sslAvailableCache = false;
						return false;
					}
				} else {
					// This is our first check (or the browser didn't accept the SSL_CHECK cookie)
					$errno = -1;
					$errstr = '';
					if (@fsockopen($_SERVER['HTTP_HOST'], $ssl_port, $errno, $errstr, $timeout)) {
						// HTTPS detected. Redirect now, and remember that we had detected HTTPS
						setcookie('SSL_CHECK', '1', 0, $cookie_path, '', false, true);
						$location = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
						header('Location:'.$location);
						die(_L('Redirecting to HTTPS...'));
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

	public static function getAvailableLangs() {
		$langs = array();
		foreach (OIDplus::getAllPluginManifests('language') as $pluginManifest) {
			$code = $pluginManifest->getLanguageCode();
			$langs[] = $code;
		}
		return $langs;
	}

	public static function getCurrentLang() {
		if (isset($_GET['lang'])) {
			$lang = $_GET['lang'];
		} else if (isset($_POST['lang'])) {
			$lang = $_POST['lang'];
		} else if (isset($_COOKIE['LANGUAGE'])) {
			$lang = $_COOKIE['LANGUAGE'];
		} else {
			$lang = self::DEFAULT_LANGUAGE;
		}
		$lang = substr(preg_replace('@[^a-z]@ismU', '', $lang),0,4); // sanitize
		return $lang;
	}

	public static function handleLangArgument() {
		if (isset($_GET['lang'])) {
			// The "?lang=" argument is only for NoScript-Browsers/SearchEngines
			// In case someone who has JavaScript clicks a ?lang= link, they should get
			// the page in that language, but the cookie must be set, otherwise
			// the menu and other stuff would be in their cookie-based-language and not the
			// argument-based-language.
			$cookie_path = OIDplus::getSystemUrl(true);
			if (empty($cookie_path)) $cookie_path = '/';
			setcookie('LANGUAGE', $_GET['lang'], 0, $cookie_path, '', false, false/*HttpOnly off, because JavaScript also needs translation*/);
		} else if (isset($_POST['lang'])) {
			$cookie_path = OIDplus::getSystemUrl(true);
			if (empty($cookie_path)) $cookie_path = '/';
			setcookie('LANGUAGE', $_POST['lang'], 0, $cookie_path, '', false, false/*HttpOnly off, because JavaScript also needs translation*/);
		}
	}

	public static function getTranslationArray() {
		$translation_array = array();
		foreach (OIDplus::getAllPluginManifests('language') as $pluginManifest) {
			$lang = $pluginManifest->getLanguageCode();
			$translation_array[$lang] = array();
			if (strpos($lang,'/') !== false) continue; // just to be sure
			if (strpos($lang,'\\') !== false) continue; // just to be sure
			if (strpos($lang,'..') !== false) continue; // just to be sure

			$wildcard = $pluginManifest->getLanguageMessages();
			if (strpos($wildcard,'/') !== false) continue; // just to be sure
			if (strpos($wildcard,'\\') !== false) continue; // just to be sure
			if (strpos($wildcard,'..') !== false) continue; // just to be sure

			$translation_files = glob(__DIR__.'/../../plugins/language/'.$lang.'/'.$wildcard);
			sort($translation_files);
			foreach ($translation_files as $translation_file) {
				if (!file_exists($translation_file)) continue;
				$xml = @simplexml_load_string(file_get_contents($translation_file));
				if (!$xml) continue; // if there is an UTF-8 or parsing error, don't output any errors, otherwise the JavaScript is corrupt and the page won't render correctly
				foreach ($xml->message as $msg) {
					$src = trim($msg->source->__toString());
					$dst = trim($msg->target->__toString());
					$translation_array[$lang][$src] = $dst;
				}
			}
		}
		return $translation_array;
	}

}
