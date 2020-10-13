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

use MatthiasMullie\Minify;

require_once __DIR__ . '/../includes/oidplus.inc.php';
require_once __DIR__ . '/../3p/minify/path-converter/ConverterInterface.php';
require_once __DIR__ . '/../3p/minify/path-converter/Converter.php';
require_once __DIR__ . '/../3p/minify/src/Minify.php';
require_once __DIR__ . '/../3p/minify/src/CSS.php';
require_once __DIR__ . '/../3p/minify/src/Exception.php';

error_reporting(E_ALL);

$out = '';

# ---

$do_minify = OIDplus::baseConfig()->getValue('MINIFY_CSS', true);

function process_file($filename) {
	global $do_minify;

	if (!file_exists($filename)) return;

	$dir = dirname((strpos($filename, __DIR__.'/') === 0) ? substr($filename, strlen(__DIR__.'/')) : $filename);
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
	$out .= process_file(__DIR__ . '/setup_base.css');
}

// Then plugins
OIDplus::registerAllPlugins('database', 'OIDplusDatabasePlugin', array('OIDplus','registerDatabasePlugin'));
$manifests = OIDplus::getAllPluginManifests('database', true);
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

httpOutWithETag($out, 'text/css', 'oidplus_setup.css');
