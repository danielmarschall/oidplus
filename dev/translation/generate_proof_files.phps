#!/usr/bin/env php
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

// Use this script to create files containing all strings of a specific language
// These files can be used for proofing (e.g. using Microsoft Word)

$dir = __DIR__ . '/../../';

// ---

$langs = array();
$tmp = glob($dir.'/plugins/'.'*'.'/language/'.'*'.'/messages.xml');
foreach ($tmp as $tmp2) {
	$tmp3 = explode('/', $tmp2);
	$lang = $tmp3[count($tmp3)-2];
	if ($lang == 'enus') continue; // ignore base lang
	$langs[] = $lang;
}

// ---

$all_strings = array();

$it = new RecursiveDirectoryIterator($dir);
foreach(new RecursiveIteratorIterator($it) as $file) {
	if (strpos(str_replace('\\','/',realpath($file)),'/vendor/') !== false) continue; // ignore third-party-code
	if (strpos(str_replace('\\','/',realpath($file)),'/dev/') !== false) continue; // ignore development utilities
	if ($file->getExtension() == 'php') {
		$cont = file_get_contents($file);
		$cont = str_replace('function _L($str, ...$sprintfArgs) {', '', $cont);
		$strings = get_php_L_strings($cont);
		$all_strings = array_merge($all_strings, $strings);
	}
	if ($file->getExtension() == 'js') {
		$cont = file_get_contents($file);
		$cont = str_replace('function _L()', '', $cont);
		$strings = get_js_L_strings($cont);
		$all_strings = array_merge($all_strings, $strings);
	}
}

$all_strings = array_unique($all_strings);
sort($all_strings);

file_put_contents(__DIR__.'/.proof_enus.txt', implode("\r\n\r\n", $all_strings));
echo "Done: enus\n";

// ---

foreach ($langs as $lang) {
	$all_strings = array();
	$translation_files = glob($dir.'/plugins/'.'*'.'/language/'.$lang.'/messages.xml');
	$translation_file = count($translation_files) > 0 ? $translation_files[0] : null;
	if (file_exists($translation_file)) {
	$xml = simplexml_load_string(file_get_contents($translation_file));
	if (!$xml) {
		echo "STOP: Cannot load $translation_file\n";
		continue;
	}
	foreach ($xml->message as $msg) {
			$dst = trim($msg->target->__toString());
			$all_strings[] = $dst;
		}
	}

	sort($all_strings);

	file_put_contents(__DIR__.'/.proof_'.$lang.'.txt', implode("\r\n\r\n", $all_strings));
	echo "Done: $lang\n";
}

if (count($langs) > 0) {
	echo "All done!\n";
} else {
	echo "Attention: No language plugins found!\n";
}

# ---

function get_js_L_strings($cont) {
	// Works with JavaScript and PHP
	$cont = preg_replace('@/\\*.+\\*/@ismU', '', $cont);
	$cont = str_replace('\\"', chr(1), $cont);
	$cont = str_replace("\\'", chr(2), $cont);
	$cont = str_replace("\\\\", "\\", $cont);
	$m = array();
	preg_match_all('@[^_A-Za-z0-9]_L\\(.*(["\'])(.+)\\1@ismU', $cont, $m);
	foreach ($m[2] as &$x) {
		$x = str_replace(chr(1), '"', $x);
		$x = str_replace(chr(2), "'", $x);
	}
	return $m[2];
}

function get_php_L_strings($cont) {
	// Works only with PHP
	$out = array();
	$tokens = token_get_all($cont);
	$activated = 0;
	foreach ($tokens as $token) {
		if (is_array($token)) {
			if (($token[0] == T_STRING) && ($token[1] == '_L')) {
				$activated = 1;
			} else if (($activated == 1) && ($token[0] == T_CONSTANT_ENCAPSED_STRING)) {
				$tmp = stripcslashes($token[1]);
				$out[] = substr($tmp,1,strlen($tmp)-2);
				$activated = 0;
			}
		}
	}
	return $out;
}
