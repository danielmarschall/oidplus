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

require_once __DIR__ . '/3p/minify/path-converter/ConverterInterface.php';
require_once __DIR__ . '/3p/minify/path-converter/Converter.php';
require_once __DIR__ . '/3p/minify/src/Minify.php';
require_once __DIR__ . '/3p/minify/src/CSS.php';
require_once __DIR__ . '/3p/minify/src/Exception.php';

error_reporting(E_ALL);

$minifier = new Minify\CSS(__DIR__ . '/oidplus.css');

$ary = glob(__DIR__ . '/plugins/publicPages/'.'*'.'/style.css');
sort($ary);
foreach ($ary as $a) $minifier->add($a);

$ary = glob(__DIR__ . '/plugins/adminPages/'.'*'.'/style.css');
sort($ary);
foreach ($ary as $a) $minifier->add($a);

$ary = glob(__DIR__ . '/plugins/raPages/'.'*'.'/style.css');
sort($ary);
foreach ($ary as $a) $minifier->add($a);

$out = $minifier->minify();
$etag = md5($out);
header("Etag: $etag");
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && (trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag)) {
	header("HTTP/1.1 304 Not Modified");
} else {
	header('Content-Type:text/css');
	echo $out;
}
