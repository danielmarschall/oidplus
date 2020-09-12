#!/usr/bin/php
<?php

/*
 * OIDplus 2.0
 * Copyright 2020 Daniel Marschall, ViaThinkSoft
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

// This script updates the language message files (adding new entries and
// removing entries that are not existing anymore).
// It requires that PHP scripts are using following syntax for translations:
//             _L('hello world',optionalParams) <recommended>
//             _L("hello world",optionalParams)
// and JS files:
//             _L('hello world',optionalParams)
//             _L("hello world",optionalParams) <recommended>


$dir = __DIR__ . '/../../';

// ---

$langs = array();
$tmp = glob($dir.'/plugins/language/*/messages.xml');
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
	if (strpos(str_replace('\\','/',realpath($file)),'/3p/') !== false) continue; // ignore third-party-code
	if (strpos(str_replace('\\','/',realpath($file)),'/dev/') !== false) continue; // ignore development utilities
	if ($file->getExtension() == 'php') {
		$cont = file_get_contents($file);
		$cont = phpRemoveComments($cont);
		$cont = str_replace('function _L($str, ...$sprintfArgs) {', '', $cont);
		$strings = get_php_L_strings($cont);
		$strings_test = get_js_L_strings($cont);

		if (serialize($strings) != serialize($strings_test)) {
			echo "Attention: File ".realpath($file)." ambiguous _L() functions\n";
		}

		$all_strings = array_merge($all_strings, $strings);
	}
	if ($file->getExtension() == 'js') {
		$cont = file_get_contents($file);
		$cont = str_replace('function _L()', '', $cont);
		$strings = get_js_L_strings($cont);
		$all_strings = array_merge($all_strings, $strings);
	}
}

foreach ($all_strings as $str) {
	test_missing_placeholder($str);
}

$all_strings = array_unique($all_strings);
sort($all_strings);

// ---

foreach ($langs as $lang) {
	$translation_array = array();
	$translation_file = $dir.'/plugins/language/'.$lang.'/messages.xml';
	if (file_exists($translation_file)) {
	$xml = simplexml_load_string(file_get_contents($translation_file));
	if (!$xml) {
		echo "STOP: Cannot load $translation_file\n";
		continue;
	}
	foreach ($xml->message as $msg) {
			$src = trim($msg->source->__toString());
			$dst = trim($msg->target->__toString());
			$translation_array[$src] = $dst;
		}
	}

	// ---

	echo "Processing ".realpath($translation_file)." ...\n";

	$stats_total = 0;
	$stats_translated = 0;
	$stats_not_translated = 0;

	$cont = '';
	$cont .= "<?xml version=\"1.0\" encoding=\"utf-8\" standalone=\"yes\"?>\n";
	$cont .= "<translation>\n";
	foreach ($all_strings as $string) {
		$stats_total++;
		$cont .= "	<message>\n";
		$cont .= "		<source><![CDATA[\n";
		$cont .= "		$string\n";
		$cont .= "		]]></source>\n";
		if (isset($translation_array[$string]) && !empty($translation_array[$string])) {
			$stats_translated++;
			if (substr_count($string,'%') != substr_count($translation_array[$string],'%')) {
				echo "\tAttention: Number of %-Replacements differs at translation of message '$string'\n";
			}
			$cont .= "		<target><![CDATA[\n";
			$cont .= "		".$translation_array[$string]."\n";
			$cont .= "		]]></target>\n";
			test_missing_placeholder($translation_array[$string]);
		} else {
			$stats_not_translated++;
			$cont .= "		<target><![CDATA[\n";
			$cont .= "		]]></target><!-- TODO: TRANSLATE -->\n";
		}
		$cont .= "	</message>\n";
	}
	$cont .= "</translation>\n";
	file_put_contents($translation_file, $cont);

	echo "\t$stats_total total messages, $stats_translated already translated (".round($stats_translated/$stats_total*100,2)."%), $stats_not_translated not translated (".round($stats_not_translated/$stats_total*100,2)."%)\n";
	echo "\tDone...";
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

function test_missing_placeholder($test) {
	$max = -1;
	for ($i=99; $i>=1; $i--) {
		if (strpos($test, '%'.$i) !== false) {
			$max = $i;
			break;
		}
	}

	for ($i=1; $i<=$max; $i++) {
		if (strpos($test, '%'.$i) === false) {
			echo "Attention: %$i is missing in string '$test'!\n";
			$max = $i;
			break;
		}
	}

	$test = preg_replace('@%([1-9][0-9]|%)*@ism', '', $test);
	if (strpos($test,'%') !== false) {
		echo "Attention: Wrong percentage sign in '$test'!\n";
	}
}

# ---

function phpRemoveComments($fileStr) {

	// https://stackoverflow.com/questions/503871/best-way-to-automatically-remove-comments-from-php-code

	$newStr  = '';

	$commentTokens = array(T_COMMENT);

	if (defined('T_DOC_COMMENT')) $commentTokens[] = T_DOC_COMMENT; // PHP 5
	if (defined('T_ML_COMMENT'))  $commentTokens[] = T_ML_COMMENT;  // PHP 4

	$tokens = token_get_all($fileStr);

	foreach ($tokens as $token) {
		if (is_array($token)) {
			if (in_array($token[0], $commentTokens)) continue;
			$token = $token[1];
		}
		$newStr .= $token;
	}

	return $newStr;

}
