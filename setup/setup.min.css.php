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

use MatthiasMullie\Minify;

require_once __DIR__ . '/../includes/oidplus.inc.php';

error_reporting(OIDplus::baseConfig()->getValue('DEBUG') ? E_ALL : 0);
@ini_set('display_errors', OIDplus::baseConfig()->getValue('DEBUG') ? '1' : '0');

$out = '';

# ---

$do_minify = OIDplus::baseConfig()->getValue('MINIFY_CSS', true);

function process_file($filename) {
	global $do_minify;

	if (!file_exists($filename)) return;

	$thisdir = __DIR__;
	$thisdir = str_replace('\\', '/', $thisdir); // change Windows Backslashes into Web-Slashes
	$filename = str_replace('\\', '/', $filename); // change Windows Backslashes into Web-Slashes
	$dir = dirname((strpos($filename, $thisdir.'/') === 0) ? substr($filename, strlen($thisdir.'/')) : $filename);

	if ($do_minify) {
		$minifier = new Minify\CSS($filename);
		$cont = $minifier->minify();
		$cont = str_ireplace("url(data:", "url###(data:", $cont);
		$cont = str_ireplace("url(", "url(".$dir.'/', $cont);
	} else {
		$cont = file_get_contents($filename);
		$cont = str_ireplace('url("data:', 'url###("data:', $cont);
		$cont = str_ireplace('url("', 'url("'.$dir.'/', $cont);
		$cont = str_ireplace("url('data:", "url###('data:", $cont);
		$cont = str_ireplace("url('", "url('".$dir.'/', $cont);
	}
	$cont = str_ireplace("url###(", "url(", $cont);
	return $cont."\n\n";
}

# ---

// Third-party products
// (None)

// OIDplus basic definitions
if (file_exists(__DIR__ . '/../userdata/styles/setup_base.css')) {
	$out .= process_file(__DIR__ . '/../userdata/styles/setup_base.css');
} else {
	$out .= process_file(__DIR__ . '/includes/setup_base.css');
}

// Then plugins
$manifests = OIDplus::getAllPluginManifests('database,captcha', true);
foreach ($manifests as $manifest) {
	foreach ($manifest->getCSSFilesSetup() as $css_file) {
		$out .= process_file($css_file);
	}
}

// Now user-defined definitions
if (file_exists(__DIR__ . '/userdata/styles/setup_add.css')) {
	$out .= process_file(__DIR__ . '/userdata/styles/setup_add.css');
}

# ---

if (OIDplus::baseConfig()->getValue('DEBUG')) {
	// In debug mode, we might get PHP error messages (see "error_reporting" above),
	// so it would be severe if we would allow ETAG! (since $out does not contain the PHP error messages!)
	header('Content-Type:text/css');
	echo $out;
} else {
	httpOutWithETag($out, 'text/css', 'oidplus_setup.css');
}
