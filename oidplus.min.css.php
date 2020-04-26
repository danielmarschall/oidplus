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

require_once __DIR__ . '/includes/oidplus.inc.php';
require_once __DIR__ . '/3p/minify/path-converter/ConverterInterface.php';
require_once __DIR__ . '/3p/minify/path-converter/Converter.php';
require_once __DIR__ . '/3p/minify/src/Minify.php';
require_once __DIR__ . '/3p/minify/src/CSS.php';
require_once __DIR__ . '/3p/minify/src/Exception.php';

error_reporting(E_ALL);

$out = '';

# ---

function process_file($filename) {
	$dir = dirname((strpos($filename, __DIR__.'/') === 0) ? substr($filename, strlen(__DIR__.'/')) : $filename);
	if (OIDplus::baseConfig()->getValue('MINIFY_CSS', true)) {
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

$out .= process_file(__DIR__ . '/oidplus_base.css');

$ary = OIDplus::getAllPluginManifests('*Pages');
foreach ($ary as $plugintype_folder => $bry) {
	foreach ($bry as $pluginname_folder => $cry) {
		if (!isset($cry['CSS'])) continue;
		foreach ($cry['CSS'] as $dry_name => $dry) {
			if ($dry_name != 'file') continue;
			foreach ($dry as $css_file) {
				$out .= process_file(__DIR__ . '/plugins/'.$plugintype_folder.'/'.$pluginname_folder.'/'.$css_file);
			}
		}
	}
}

$out .= process_file(__DIR__ . '/3p/jstree/themes/default/style.css');
$out .= process_file(__DIR__ . '/3p/jquery-ui/jquery-ui.css');
$out .= process_file(__DIR__ . '/3p/bootstrap/css/bootstrap.css');

# ---

$inv = isset($_REQUEST['invert']) ? $_REQUEST['invert'] : 0;
if ($inv != 0) {
	$out = invertColorsOfCSS($out);
}

$hs = isset($_REQUEST['h_shift']) ? $_REQUEST['h_shift'] : 0;
$ss = isset($_REQUEST['s_shift']) ? $_REQUEST['s_shift'] : 0;
$vs = isset($_REQUEST['v_shift']) ? $_REQUEST['v_shift'] : 0;
if (($hs != 0) ||($ss != 0) || ($vs != 0)) {
	$out = changeHueOfCSS($out, $hs, $ss, $vs);
}

# ---

$etag = md5($out);
header("Etag: $etag");
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && (trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag)) {
	header("HTTP/1.1 304 Not Modified");
} else {
	header('Content-Type:text/css');
	echo $out;
}
