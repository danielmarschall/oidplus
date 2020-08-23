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

include_once __DIR__ . '/../includes/oidplus.inc.php';

$out = '';

$out .= 'var DEFAULT_LANGUAGE = '.json_encode(OIDplus::DEFAULT_LANGUAGE).';';

OIDplus::registerAllPlugins('language', 'OIDplusLanguagePlugin', null);
$translation_array = OIDplus::getTranslationArray();
$out .= 'var language_messages = '.json_encode($translation_array).';';

//$tbl_prefix = OIDplus::baseConfig()->getValue('OIDPLUS_TABLENAME_PREFIX','');
//$files[] = 'var language_tblprefix = '.json_encode($tbl_prefix).';';
$out .= 'var language_tblprefix = "<tableprefix>";'; // hide OIDPLUS_TABLENAME_PREFIX from the client

$out .= 'var setupdir = "'.((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"] . dirname($_SERVER['REQUEST_URI'])).'/";';
$out .= 'var rebuild_callbacks = [];';
$out .= 'var rebuild_config_callbacks = [];';
$out .= 'var plugin_combobox_change_callbacks = [];';

$found_db_plugins = 0;
OIDplus::registerAllPlugins('database', 'OIDplusDatabasePlugin', null);
foreach (get_declared_classes() as $c) {
	if (is_subclass_of($c, 'OIDplusDatabasePlugin')) {
		$out .= $c::setupJS();
	}
}

$out .= file_get_contents(__DIR__.'/setup_base.js');

$etag = md5($out);
header("Etag: $etag");
header('Content-MD5: '.$etag); // RFC 2616 clause 14.15
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && (trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag)) {
	header("HTTP/1.1 304 Not Modified");
} else {
	header('Content-Type:application/javascript');
	echo $out;
}
