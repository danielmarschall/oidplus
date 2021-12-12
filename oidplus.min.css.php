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

require_once __DIR__ . '/includes/oidplus.inc.php';

error_reporting(OIDplus::baseConfig()->getValue('DEBUG') ? E_ALL : 0);
@ini_set('display_errors', OIDplus::baseConfig()->getValue('DEBUG') ? '1' : '0');

$out = '';

# ---

$do_minify = OIDplus::baseConfig()->getValue('MINIFY_CSS', true);

function process_file($filename) {
	global $do_minify;

	$filename_min = preg_replace('/\.[^.]+$/', '.min.css', $filename);
	$filename_full = $filename;

	if ($do_minify) {
		if (file_exists($filename_min)) {
			// There is a file which is already minified
			$filename = $filename_min;
			$cont = file_get_contents($filename);
		} else if (file_exists($filename_full)) {
			// Otherwise, we minify it ourself
			$filename = $filename_full;
			$minifier = new Minify\CSS($filename);
			$cont = $minifier->minify();
		} else {
			return;
		}
	} else {
		if (file_exists($filename_full)) {
			$filename = $filename_full;
			$cont = file_get_contents($filename);
		} else if (file_exists($filename_min)) {
			$filename = $filename_min;
			$cont = file_get_contents($filename);
		} else {
			return;
		}
	}

	$thisdir = __DIR__;
	$thisdir = str_replace('\\', '/', $thisdir); // change Windows Backslashes into Web-Slashes
	$filename = str_replace('\\', '/', $filename); // change Windows Backslashes into Web-Slashes
	$dir = dirname((strpos($filename, $thisdir.'/') === 0) ? substr($filename, strlen($thisdir.'/')) : $filename);

	$cont = preg_replace('@url\\(\s+@ism', 'url(', $cont);
	$cont = str_ireplace('url("data:', 'url###("data:', $cont);
	$cont = str_ireplace('url("', 'url###("'.$dir.'/', $cont);
	$cont = str_ireplace("url('data:", "url###('data:", $cont);
	$cont = str_ireplace("url('", "url###('".$dir.'/', $cont);
	$cont = str_ireplace("url(data:", "url###(data:", $cont);
	$cont = str_ireplace("url(", "url###(".$dir.'/', $cont);
	$cont = str_ireplace("url###(", "url(", $cont);
	return $cont."\n\n";
}

# ---

// Third-party products
$out .= process_file(__DIR__ . '/vendor/vakata/jstree/dist/themes/default/style.css');
$out .= process_file(__DIR__ . '/vendor/components/jqueryui/themes/base/jquery-ui.css');
$out .= process_file(__DIR__ . '/vendor/twbs/bootstrap/dist/css/bootstrap.css');
$out .= process_file(__DIR__ . '/vendor/gedmarc/layout/dist/layout-default.css');
$out .= process_file(__DIR__ . '/includes/loading.css');

// Find out base CSS
if (isset($_REQUEST['theme'])) {
	$theme = $_REQUEST['theme'];
	if (strpos($theme,'/') !== false) $theme = 'default';
	if (strpos($theme,'\\') !== false) $theme = 'default';
	if (strpos($theme,'..') !== false) $theme = 'default';
} else {
	$theme = 'default';
}

if (file_exists(__DIR__ . '/userdata/styles/oidplus_base.css')) {
	// There is a user defined CSS (not recommended, use design plugins instead!)
	$out .= process_file(__DIR__ . '/userdata/styles/oidplus_base.css');
} else {
	// Use CSS of the design plugin
	OIDplus::registerAllPlugins('design', 'OIDplusDesignPlugin', array('OIDplus','registerDesignPlugin'));
	$plugins = OIDplus::getDesignPlugins();
	foreach ($plugins as $plugin) {
		if ((basename($plugin->getPluginDirectory())) == $theme) {
			$manifest = $plugin->getManifest();
			foreach ($manifest->getCSSFiles() as $css_file) {
				$out .= process_file($css_file);
			}
		}
	}
}

// Then plugins
$manifests = OIDplus::getAllPluginManifests('*Pages,objectTypes', true); // due to interface gridGeneratorLinks (1.3.6.1.4.1.37476.2.5.2.3.6) this plugin type can also have CSS
foreach ($manifests as $manifest) {
	foreach ($manifest->getCSSFiles() as $css_file) {
		$out .= process_file($css_file);
	}
}

// Now user-defined (additional) definitions
if (file_exists(__DIR__ . '/userdata/styles/oidplus_add.css')) {
	$out .= process_file(__DIR__ . '/userdata/styles/oidplus_add.css');
}

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

if (OIDplus::baseConfig()->getValue('DEBUG')) {
	// In debug mode, we might get PHP error messages (see "error_reporting" above),
	// so it would be severe if we would allow ETAG! (since $out does not contain the PHP error messages!)
	header('Content-Type:text/css');
	echo $out;
} else {
	httpOutWithETag($out, 'text/css', 'oidplus.css');
}
