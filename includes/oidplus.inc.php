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
	// Reasons why we currently require PHP 7.0:
	// - Return values (e.g. "function foo(): array") (added 2020-04-06 at the database classes)
	//   Note: By removing these return values (e.g. removing ": array"), you *might* be
	//   able to run OIDplus with PHP lower than version 7.0 (not tested)
	//
	// Currently we do NOT require 7.1, because some (old-)stable distros are still using PHP 7.0
	// (e.g. Debian 9 which has LTS support till May 2022).
	// Therefore we commented out following features which would require PHP 7.1:
	// - Nullable return values (e.g. "function foo(): ?array")
	// - void return value (e.g. "function foo(): void")
	// - private/protected/public consts
	echo '<!DOCTYPE HTML>';
	echo '<html><head><title>'._L('OIDplus error').'</title></head><body>';
	echo '<h1>'._L('OIDplus error').'</h1>';
	echo '<p>'._L('OIDplus requires at least PHP version %1! You are currently using version %2','7.0',PHP_VERSION).'</p>'."\n";
	echo '</body></html>';
	die();
}

include_once __DIR__ . '/gmp_supplement.inc.php';
include_once __DIR__ . '/../3p/symfony-mbstring-polyfill/bootstrap.php';

include_once __DIR__ . '/../3p/php-jwt/ExpiredException.php';
include_once __DIR__ . '/../3p/php-jwt/BeforeValidException.php';
include_once __DIR__ . '/../3p/php-jwt/SignatureInvalidException.php';
include_once __DIR__ . '/../3p/php-jwt/JWT.php';
include_once __DIR__ . '/../3p/php-jwt/JWK.php';

include_once __DIR__ . '/simplexml_supplement.inc.php';

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
}

require_once __DIR__ . '/../3p/0xbb/Sha3.php';

require_once __DIR__ . '/oid_utils.inc.php';
require_once __DIR__ . '/uuid_utils.inc.php';
require_once __DIR__ . '/color_utils.inc.php';
require_once __DIR__ . '/ipv4_functions.inc.php';
require_once __DIR__ . '/ipv6_functions.inc.php';
require_once __DIR__ . '/anti_xss.inc.php';

include_once __DIR__ . '/../3p/vts_fileformats/VtsFileTypeDetect.class.php';

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
			'sqlSlang'
		);

		$class_files = array();
		foreach ($valid_plugin_folders as $folder) {
			$class_files = array_merge($class_files, glob(__DIR__ . '/../plugins/'.$folder.'/'.'*'.'/'.'*'.'.class.php'));
		}
		$class_files = array_merge($class_files, glob(__DIR__ . '/classes/'.'*'.'.class.php'));

		$class_refs = array();
		foreach ($class_files as $filename) {
			$cn = basename($filename, '.class.php');
			$cn = strtolower($cn);
			if (!isset($class_refs[$cn])) {
				$class_refs[$cn] = $filename;
			}
		}
	}

	$class_name = strtolower($class_name);
	if (isset($class_refs[$class_name])) {
		require $class_refs[$class_name];
		unset($class_refs[$class_name]); // this emulates a "require_once" and is faster
	}
});
