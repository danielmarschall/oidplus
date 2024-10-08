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

// Using this script (barcode.php) as proxy to the service at metafloor.com has the advantage
// that we are flexible (e.g. if we want to change to another service or create the barcodes
// ourselves) and also allows us to be conform with the GDPR, since the IP address / referrer is
// not transferred to metafloor.com

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusException;

for ($sysdir_depth=4; $sysdir_depth<=7; $sysdir_depth++) {
	// The plugin directory can be in plugins (i=4), userdata_pub/plugins (i=5), or userdata_pub/tenant/.../plugins/ (i=7)
	$candidate = __DIR__. str_repeat('/..', $sysdir_depth) . '/includes/oidplus.inc.php';
	if (file_exists($candidate)) {
		require_once $candidate;
		break;
	}
}

_CheckParamExists($_GET, 'number');

if (OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_1.3.6.1.4.1.37476.2.5.2.4.8.2', false)) {
	throw new OIDplusException(_L('This plugin was disabled by the system administrator!'));
}

error_reporting(0);

const OIDPLUS_BARCODE_MAX_CACHE_AGE = 100*365*24*60*60;;

$number = $_GET['number'];
$number = preg_replace("/[^0-9]/", "", $number);
$number = substr($number, 0, 20);

$cache_file = OIDplus::getUserDataDir("cache") . 'barcode_'.$number.'.png';

if ((file_exists($cache_file)) && (time()-filemtime($cache_file) <= OIDPLUS_BARCODE_MAX_CACHE_AGE)) {

	$out = file_get_contents($cache_file);

} else {

	try {
		$out = url_get_contents('https://bwipjs-api.metafloor.com/?bcid=code128&text='.urlencode($number).'&scale=1&includetext');
	} catch (\Exception $e) {
		http_response_code(500);
		die();
	}

	if (($out === false) || ($out == '')) {
		http_response_code(500);
		die();
	}

	@file_put_contents($cache_file, $out);
}

httpOutWithETag($out, 'image/png', "barcode_$number.png");
