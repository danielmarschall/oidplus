<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2022 Daniel Marschall, ViaThinkSoft
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

// Before we do ANYTHING, check for PHP version and dependencies!
// Do not include anything (except the supplements) yet.

if (version_compare(PHP_VERSION, $oidplus_min_version='7.0.0') < 0) {
	// More information about the required PHP version:
	// doc/developer_notes/php7_compat.txt
	// Note: These strings are not translated, because in case of an incompatible
	// PHP version, we are not able to load language plugins at all.
	if (PHP_SAPI != 'cli') @http_response_code(500);
	echo '<!DOCTYPE HTML>';
	echo '<html><head><title>'.sprintf('OIDplus error').'</title></head><body>';
	echo '<h1>'.sprintf('OIDplus error').'</h1>';
	echo '<p>'.sprintf('OIDplus requires at least PHP version %s! You are currently using version %s',$oidplus_min_version,PHP_VERSION).'</p>'."\n";
	echo '</body></html>';
	die();
}

require_once __DIR__ . '/../vendor/autoload.php';

include_once __DIR__ . '/../vendor/danielmarschall/php_utils/openssl_supplement.inc.php';
include_once __DIR__ . '/../vendor/danielmarschall/php_utils/gmp_supplement.inc.php';
include_once __DIR__ . '/../vendor/symfony/polyfill-mbstring/bootstrap.php';
include_once __DIR__ . '/../vendor/danielmarschall/php_utils/simplexml_supplement.inc.php';

require_once __DIR__ . '/functions.inc.php';

require_once __DIR__ . '/oidplus_dependency.inc.php';
$missing_dependencies = oidplus_get_missing_dependencies();
if (count($missing_dependencies) >= 1) {
	if (PHP_SAPI != 'cli') @http_response_code(500);
	echo '<!DOCTYPE HTML>';
	echo '<html><head><title>'._L('OIDplus error').'</title></head><body>';
	echo '<h1>'._L('OIDplus error').'</h1>';
	echo '<p>'._L('The following PHP extensions need to be installed in order to run OIDplus:').'</p>';
	echo '<ul>';
	foreach ($missing_dependencies as $dependency) {
		echo '<li>'.$dependency.'<br><br></li>';
	}
	echo '</ul>';
	echo '</body></html>';
	die();
}
unset($missing_dependencies);

// Now we can continue!

require_once __DIR__ . '/../vendor/danielmarschall/php_utils/oid_utils.inc.php';
require_once __DIR__ . '/../vendor/danielmarschall/php_utils/xml_utils.inc.php';
require_once __DIR__ . '/../vendor/danielmarschall/uuid_mac_utils/includes/uuid_utils.inc.php';
require_once __DIR__ . '/../vendor/danielmarschall/php_utils/color_utils.inc.php';
require_once __DIR__ . '/../vendor/danielmarschall/php_utils/ipv4_functions.inc.php';
require_once __DIR__ . '/../vendor/danielmarschall/php_utils/ipv6_functions.inc.php';
require_once __DIR__ . '/../vendor/danielmarschall/php_utils/anti_xss.inc.php';
include_once __DIR__ . '/../vendor/danielmarschall/php_utils/git_utils.inc.php';
include_once __DIR__ . '/../vendor/danielmarschall/php_utils/svn_utils.inc.php';
include_once __DIR__ . '/../vendor/danielmarschall/php_utils/aid_decoder.inc.php';
include_once __DIR__ . '/../vendor/danielmarschall/php_utils/misc_functions.inc.php';

// ---

spl_autoload_register(function ($class_name) {
	static $class_refs = null;

	// We only load based on the last element of the class name (ignore namespace)
	// If there are multiple classes matching that name we just include all class files
	$path = explode('\\',$class_name);
	$class_name = end($path);

	if (is_null($class_refs)) {
		$valid_plugin_folders = array(
			'adminPages',
			'auth',
			'database',
			'design',
			'language',
			'logger',
			'objectTypes',
			'publicPages',
			'raPages',
			'sqlSlang',
			'captcha'
		);

		$func = function(&$class_refs, $class_files, $namespace='') {
			foreach ($class_files as $filename) {
				$cn = strtolower(basename($filename));
				$cn = preg_replace('@(\\.class){0,1}\\.phps{0,1}$@', '', $cn);
				if (!empty($namespace)) {
					if (substr($namespace,-1,1) !== '\\') $namespace .= '\\';
					$cn = strtolower($namespace) . $cn;
				}
				if (!isset($class_refs[$cn])) {
					$class_refs[$cn] = array($filename);
				} else {
					$class_refs[$cn][] = $filename;;
				}
			}
		};

		$class_files = array();

		// Global namespace / OIDplus
		// (the last has the highest priority)
		foreach ($valid_plugin_folders as $folder) {
			$class_files = array_merge($class_files, glob(__DIR__ . '/../plugins/'.'*'.'/'.$folder.'/'.'*'.'/'.'*'.'.class.php'));
		}
		$class_files = array_merge($class_files, glob(__DIR__ . '/classes/'.'*'.'.class.php'));
		$class_files = array_merge($class_files, glob(__DIR__ . '/../vendor/danielmarschall/fileformats/'.'*'.'.class.php'));
		$class_files = array_merge($class_files, glob(__DIR__ . '/../vendor/danielmarschall/php_utils/'.'*'.'.class.php'));
		$class_files = array_merge($class_files, glob(__DIR__ . '/../vendor/danielmarschall/oidconverter/php/'.'*'.'.class.phps'));
		$func($class_refs, $class_files);
	}

	$class_name = strtolower($class_name);
	if (isset($class_refs[$class_name])) {
		foreach ($class_refs[$class_name] as $inc) {
			require $inc;
		}
		unset($class_refs[$class_name]); // this emulates a "require_once" and is faster
	}
});
