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
// Keep this file clean from fancy syntax sugar, otherwise old PHP versions
// will get a compilation error and then they won't see our friendly error message.

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
unset($oidplus_min_version);

// Polyfills/Supplements to implement some missing dependencies if possible

include_once __DIR__ . '/../vendor/danielmarschall/php_utils/openssl_supplement.inc.php';
include_once __DIR__ . '/../vendor/danielmarschall/php_utils/gmp_supplement.inc.php';
include_once __DIR__ . '/../vendor/symfony/polyfill-mbstring/bootstrap.php';
include_once __DIR__ . '/../vendor/danielmarschall/php_utils/simplexml_supplement.inc.php';

// Now check for things like missing PHP libraries (which could not implemented using the polyfills)

require_once __DIR__ . '/oidplus_dependency.inc.php';
$missing_dependencies = oidplus_get_missing_dependencies();
if (count($missing_dependencies) >= 1) {
	// Note that there are no translations _L() because if we get an error at this
	// stage, then we have no language plugins anyways.
	if (PHP_SAPI != 'cli') @http_response_code(500);
	echo '<!DOCTYPE HTML>';
	echo '<html><head><title>'.sprintf('OIDplus error').'</title></head><body>';
	echo '<h1>'.sprintf('OIDplus error').'</h1>';
	echo '<p>'.sprintf('The following PHP extensions need to be installed in order to run OIDplus:').'</p>';
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

require_once __DIR__ . '/functions.inc.php';
require_once __DIR__ . '/../vendor/danielmarschall/php_utils/oid_utils.inc.php';
require_once __DIR__ . '/../vendor/danielmarschall/php_utils/xml_utils.inc.php';
require_once __DIR__ . '/../vendor/danielmarschall/uuid_mac_utils/includes/uuid_utils.inc.php';
require_once __DIR__ . '/../vendor/danielmarschall/php_utils/color_utils.inc.php';
require_once __DIR__ . '/../vendor/danielmarschall/php_utils/ipv4_functions.inc.php';
require_once __DIR__ . '/../vendor/danielmarschall/php_utils/ipv6_functions.inc.php';
require_once __DIR__ . '/../vendor/danielmarschall/php_utils/anti_xss.inc.php';
require_once __DIR__ . '/../vendor/danielmarschall/php_utils/git_utils.inc.php';
require_once __DIR__ . '/../vendor/danielmarschall/php_utils/svn_utils.inc.php';
require_once __DIR__ . '/../vendor/danielmarschall/php_utils/aid_decoder.inc.php';
require_once __DIR__ . '/../vendor/danielmarschall/php_utils/misc_functions.inc.php';

// Load the autoloaders

require_once __DIR__ . '/../vendor/autoload.php';      // Autoloader of "vendor/"
require_once __DIR__ . '/oidplus_autoloader.inc.php';  // Autoloader for all OIDplus base classes and plugins (*.class.php)
