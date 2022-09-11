<?php

/*
 * OIDplus 2.0 RDAP
 * Copyright 2019 - 2022 Daniel Marschall, ViaThinkSoft
 * Authors               Daniel Marschall, ViaThinkSoft
 *                       Till Wehowski, Frdlweb
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

require_once __DIR__ . '/../../../../../includes/oidplus.inc.php';



OIDplus::init(true);
set_exception_handler(array('OIDplusGui', 'html_exception_handler'));

if (OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_OIDplusPagePublicRdap', false)) {
	throw new OIDplusException(_L('This plugin was disabled by the system administrator!'));
}

originHeaders();

if (\PHP_SAPI == 'cli') {
	if ($_SERVER['argc'] != 2) {
		echo _L('Syntax').': '.$_SERVER['argv'][0].' <query>'."\n";
		exit(2);
	}
	$query = $_SERVER['argv'][1];
} else {
	if (!isset($_REQUEST['query'])) {
		http_response_code(400);
		die('<h1>'._L('Error').'</h1><p>'._L('Argument "%1" is missing','query').'<p>');
	}
	$query = $_REQUEST['query'];
}

$x = new OIDplusRDAP();
list($out_content, $out_type) = $x->rdapQuery($query);
if ($out_type) header('Content-Type:'.$out_type);
echo $out_content;
