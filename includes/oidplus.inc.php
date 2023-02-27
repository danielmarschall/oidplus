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

// Before we do ANYTHING, check for PHP version and dependencies!
// Do not include anything (except the supplements) yet.
// Keep this file clean from fancy syntax sugar, otherwise old PHP versions
// will get a compilation error and then they won't see our friendly error message.
// More information about the required PHP version:  doc/developer_notes/php7_compat.txt

define('INSIDE_OIDPLUS', true);

if (version_compare(PHP_VERSION, $oidplus_min_version='7.0.0') < 0) {
	// Note: These strings are not translated, because in case of an incompatible
	// PHP version, we are not able to load language plugins at all.
	$message = '<p>'.sprintf('OIDplus requires at least PHP version %s!<br>You are currently using version %s',$oidplus_min_version,PHP_VERSION).'</p>';
	oidplus_dependency_panic($message);
}
unset($oidplus_min_version);

// We need the autoloader now, otherwise openssl_supplement won't work (it needs to check if phpseclib is existing)
// Autoloader of "vendor/" (PSR-4 *.php)

require_once __DIR__ . '/../vendor/autoload.php';

// Polyfills/Supplements to implement some missing dependencies if possible

include_once __DIR__ . '/../vendor/danielmarschall/php_utils/openssl_supplement.inc.php';
include_once __DIR__ . '/../vendor/danielmarschall/php_utils/gmp_supplement.inc.php';
include_once __DIR__ . '/../vendor/symfony/polyfill-mbstring/bootstrap.php';
include_once __DIR__ . '/../vendor/danielmarschall/php_utils/simplexml_supplement.inc.php';

// Now check for things like missing PHP libraries (which could not be implemented using the polyfills)

require_once __DIR__ . '/oidplus_dependency.inc.php';
$missing_dependencies = oidplus_get_missing_dependencies();
if (count($missing_dependencies) >= 1) {
	// Note that there are no translations _L() because if we get an error at this
	// stage, then we have no language plugins anyways.
	$message  = '<p>'.sprintf('The following PHP extensions need to be installed in order to run OIDplus:').'</p>';
	$message .= '<p><ul>';
	foreach ($missing_dependencies as $dependency) {
		$message .= '<li>'.$dependency.'<br><br></li>';
	}
	$message .= '</ul></p>';
	oidplus_dependency_panic($message);
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
require_once __DIR__ . '/../vendor/danielmarschall/php_utils/vts_crypt.inc.php';
require_once __DIR__ . '/../vendor/danielmarschall/php_utils/password_hash_ex.inc.php';

// Autoloader for all OIDplus base classes and plugins (*.class.php)

require_once __DIR__ . '/oidplus_autoloader.inc.php';

// Functions

function oidplus_dependency_panic($message)/*: never*/ {
	$title = sprintf('OIDplus startup error');
	if (PHP_SAPI === 'cli') {
		$message = str_replace('<li>', "- ", $message);
		$message = str_replace('<br>', "\n", $message);
		$message = str_replace('</p>', "\n\n", $message);
		$message = trim(strip_tags($message));
		fprintf(STDERR, "$title\n\n$message\n\n");
		exit(1);
	} else {
		@http_response_code(500);
		echo '<!DOCTYPE HTML>';
		echo '<html><head><title>'.$title.'</title></head><body>';
		echo '<h1>'.$title.'</h1>';
		echo $message;
		echo '</body></html>';
		die();
	}
}
