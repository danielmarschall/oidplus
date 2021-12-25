<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2021 Daniel Marschall, ViaThinkSoft
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

// Before we do ANYTHING, check for dependencies! Do not include anything (except the GMP supplement) yet.

define('INSIDE_OIDPLUS', true);

require_once __DIR__ . '/functions.inc.php'; // Required for _L()

if (version_compare(PHP_VERSION, '7.0.0') < 0) {
	// More information about the required PHP version:
	// doc/developer_notes/php7_compat
	echo '<!DOCTYPE HTML>';
	echo '<html><head><title>'._L('OIDplus error').'</title></head><body>';
	echo '<h1>'._L('OIDplus error').'</h1>';
	echo '<p>'._L('OIDplus requires at least PHP version %1! You are currently using version %2','7.0',PHP_VERSION).'</p>'."\n";
	echo '</body></html>';
	die();
}

require_once __DIR__ . '/../vendor/autoload.php';

include_once __DIR__ . '/../vendor/danielmarschall/php_utils/gmp_supplement.inc.php';
include_once __DIR__ . '/../vendor/symfony/polyfill-mbstring/bootstrap.php';
include_once __DIR__ . '/../vendor/danielmarschall/php_utils/simplexml_supplement.inc.php';

require_once __DIR__ . '/oidplus_dependency.inc.php';

$missing_dependencies = oidplus_get_missing_dependencies();

if (count($missing_dependencies) >= 1) {
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

if (PHP_SAPI != 'cli') {
	// TODO: Plugins should be able to extend CSP
	header('X-Content-Type-Options: nosniff');
	header('X-XSS-Protection: 1; mode=block');
	header("Content-Security-Policy: default-src 'self' blob: https://fonts.gstatic.com https://www.google.com/ https://www.gstatic.com/ https://cdnjs.cloudflare.com/; ".
	       "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com/; ".
	       "img-src blob: data: http: https:; ".
	       "script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: https://www.google.com/ https://www.gstatic.com/ https://cdnjs.cloudflare.com/ https://polyfill.io/; ".
	       "frame-ancestors 'none'; ".
	       "object-src 'none'");
	header('X-Frame-Options: SAMEORIGIN');
	header('Referrer-Policy: no-referrer-when-downgrade');
	header('Cache-control: no-cache');
	header('Cache-control: no-store');
	header('Pragma: no-cache');
	header('Expires: 0');
}

require_once __DIR__ . '/../vendor/danielmarschall/php_utils/oid_utils.inc.php';
require_once __DIR__ . '/../vendor/danielmarschall/php_utils/xml_utils.inc.php';
require_once __DIR__ . '/../vendor/danielmarschall/uuid_mac_utils/includes/uuid_utils.inc.php';
require_once __DIR__ . '/../vendor/danielmarschall/php_utils/color_utils.inc.php';
require_once __DIR__ . '/../vendor/danielmarschall/php_utils/ipv4_functions.inc.php';
require_once __DIR__ . '/../vendor/danielmarschall/php_utils/ipv6_functions.inc.php';
require_once __DIR__ . '/../vendor/danielmarschall/php_utils/anti_xss.inc.php';
include_once __DIR__ . '/../vendor/danielmarschall/php_utils/git_utils.inc.php';
include_once __DIR__ . '/../vendor/danielmarschall/php_utils/svn_utils.inc.php';

// ---

spl_autoload_register(function ($class_name) {
	static $class_refs = null;

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
				$cn = preg_replace('@(\\.class){0,1}\\.php$@', '', $cn);
				if (!empty($namespace)) {
					if (substr($namespace,-1,1) !== '\\') $namespace .= '\\';
					$cn = strtolower($namespace) . $cn;
				}
				if (!isset($class_refs[$cn])) {
					$class_refs[$cn] = $filename;
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
		$func($class_refs, $class_files);
	}

	$class_name = strtolower($class_name);
	if (isset($class_refs[$class_name])) {
		require $class_refs[$class_name];
		unset($class_refs[$class_name]); // this emulates a "require_once" and is faster
	}
});
