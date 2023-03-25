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

$dir = __DIR__ . '/../../';

// ---

$all_cont  = "<?php\n";
$all_cont .= "__halt_compiler();\n"; // avoid that the FastPHP code explorer runs crazy
$all_cont .= "?>\n";

$it = new RecursiveDirectoryIterator($dir);
foreach(new RecursiveIteratorIterator($it) as $file) {
	if (strpos(str_replace('\\','/',realpath($file)),'/vendor/') !== false) continue; // ignore third-party-code
	if (strpos(str_replace('\\','/',realpath($file)),'/dev/') !== false) continue; // ignore development utilities
	if (basename($file) == 'ipv4_functions.inc.php') continue; // 3P
	if (basename($file) == 'ipv6_functions.inc.php') continue; // 3P
	if (basename($file) == 'gmp_supplement.inc.php') continue; // 3P
	if (basename($file) == 'xml_utils.inc.php') continue; // 3P
	if (basename($file) == 'uuid_utils.inc.php') continue; // 3P
	if (basename($file) == 'oid_utils.inc.php') continue; // 3P
	if (basename($file) == 'oidinfo_api.inc.php') continue; // 3P
	if (basename($file) == 'anti_xss.inc.php') continue; // 3P
	if ($file->getExtension() == 'js') {

		$cont = file_get_contents($file);

		$ary = explode("\n",$cont);
		$cont = '';
		foreach ($ary as $a) {
			if (stripos($a,'do not translate') === false) {
				$cont .= "$a\n";
			}
		}

		$cont = preg_replace('@_L\\((\'|")(.*)\\1@smU', '_L(...', $cont);

		$ary = explode("\n",$cont);
		$cont = '';
		foreach ($ary as $a) {
			$cont .= wordwrap(trim($a), 80)."\n";
		}

		$all_cont .= "=============== ".realpath($file)." ===============\n";
		$all_cont .= $cont."\n\n";

	}
	if ($file->getExtension() == 'php') {
		$cont = file_get_contents($file);

		$ary = explode("\n",$cont);
		$cont = '';
		foreach ($ary as $a) {
			if (stripos($a,'do not translate') === false) {
				$cont .= "$a\n";
			}
		}

		$cont = phpRemoveComments($cont);

		$cont = str_replace('<li>', '', $cont);
		$cont = str_replace('</li>', '', $cont);
		$cont = str_replace('<ul>', '', $cont);
		$cont = str_replace('</ul>', '', $cont);
		$cont = str_replace('<th>', '', $cont);
		$cont = str_replace('</th>', '', $cont);
		$cont = str_replace('<pre>', '', $cont);
		$cont = str_replace('</pre>', '', $cont);
		$cont = str_replace('</a>', '', $cont);
		$cont = str_replace('<br>', '', $cont);
		$cont = str_replace('<br/>', '', $cont);
		$cont = str_replace('<br />', '', $cont);
		$cont = str_replace('<h1>', '', $cont);
		$cont = str_replace('</h1>', '', $cont);
		$cont = str_replace('<h2>', '', $cont);
		$cont = str_replace('</h2>', '', $cont);
		$cont = str_replace('<h3>', '', $cont);
		$cont = str_replace('</h3>', '', $cont);
		$cont = str_replace('<p>', '', $cont);
		$cont = str_replace('</p>', '', $cont);
		$cont = str_replace('<th width="25%">', '', $cont);
		$cont = str_replace('<th width="50%">', '', $cont);
		$cont = str_replace('</th>', '', $cont);
		$cont = str_replace('<td>', '', $cont);
		$cont = str_replace('</td>', '', $cont);
		$cont = str_replace('<tr>', '', $cont);
		$cont = str_replace('</tr>', '', $cont);
		$cont = str_replace('<b>', '', $cont);
		$cont = str_replace('</b>', '', $cont);
		$cont = str_replace('<i>', '', $cont);
		$cont = str_replace('</i>', '', $cont);
		$cont = str_replace('<u>', '', $cont);
		$cont = str_replace('</u>', '', $cont);
		$cont = preg_replace('@<form.+>@ismU', '', $cont);
		$cont = preg_replace('@<table.+>@ismU', '', $cont);
		$cont = str_replace('</form>', '', $cont);
		$cont = str_replace('</table>', '', $cont);

		$cont = str_replace('\\\\', chr(1), $cont);
		$cont = str_replace('\\"', chr(2), $cont);
		$cont = str_replace("\\'", chr(3), $cont);

		$cont = preg_replace_callback('@("|\').*\\1@ismU', function($treffer) {
			$x = $treffer[0];

			if (strpos($x,'_L(') !== false) {
				echo "Attention: '_L(' inside a string?! at <pre>'.htmlentities($x).'</pre>\n";
			}

			$x = str_replace('(', chr(4), $x);

			return str_replace(')', chr(5), $x);
		}, $cont);

		$cont = preg_replace('@_L\\((\'|")(.*)\\1@smU', '_L(...', $cont);

		$cont = preg_replace('@logger\\(\\)\\->log\\([^\n]*\\)@smU', 'logger()->log(...)', $cont);
		$cont = preg_replace('@config\\(\\)\\->getValue\\([^\n]*\\)@smU', 'config()->getValue(...)', $cont);
		$cont = preg_replace('@config\\(\\)\\->setValue\\([^\n]*\\)@smU', 'config()->setValue(...)', $cont);
		$cont = preg_replace('@prepareConfigKey\\([^\n]*\\)@smU', 'prepareConfigKey(...)', $cont);
		$cont = preg_replace('@baseConfig\\(\\)\\->getValue\\([^\n]*\\)@smU', 'baseConfig()->getValue(...)', $cont);
		$cont = preg_replace('@query\\([^\n]*\\)@smU', 'query(...)', $cont);
		$cont = preg_replace('@query\\([^\n]*\\)@smU', 'query(...)', $cont);
		$cont = preg_replace('@registerAllPlugins\\([^\n]*\\);@smU', 'registerAllPlugins(...)', $cont);

		$cont = preg_replace('@\\[(\'|")([^\n]*)\\1\\]@smU', '[...]', $cont);
		$cont = preg_replace('@header\\([^\n]*\\);@smU', 'header(...)', $cont);
		$cont = preg_replace('@setcookie\\([^\n]*\\);@smU', 'header(...)', $cont);
		$cont = preg_replace('@file_exists\\((\'|")([^\n]*)\\1\\)@smU', 'file_exists(...)', $cont);
		$cont = preg_replace('@is_dir\\((\'|")([^\n]*)\\1\\)@smU', 'is_dir(...)', $cont);
		$cont = preg_replace('@ini_get\\((\'|")([^\n]*)\\1\\)@smU', 'ini_get(...)', $cont);
		$cont = preg_replace('@realpath\\((\'|")([^\n]*)\\1\\)@smU', 'realpath(...)', $cont);

		$cont = preg_replace('@include_once(.+);@ismU', '', $cont);
		$cont = preg_replace('@require_once(.+);@ismU', '', $cont);
		$cont = preg_replace('@==\\s*(\'|").*\\1@ismU', '==...', $cont);
		$cont = preg_replace('@!=\\s*(\'|").*\\1@ismU', '!=...', $cont);

		$cont = str_replace(chr(1), "\\\\", $cont);
		$cont = str_replace(chr(2), '\\"', $cont);
		$cont = str_replace(chr(3), "\\'", $cont);
		$cont = str_replace(chr(4), "(", $cont);
		$cont = str_replace(chr(5), ")", $cont);

		$cont = str_replace("\t", "", $cont);

		$cont = str_replace("''", '', $cont);
		$cont = str_replace('""', '', $cont);

		$cont = str_replace("'.'", '', $cont);
		$cont = str_replace('"."', '', $cont);

		$cont = str_replace("':'", '', $cont);
		$cont = str_replace('":"', '', $cont);

		$cont = str_replace("';'", '', $cont);
		$cont = str_replace('";"', '', $cont);

		$cont = str_replace('"\\n"', '', $cont);

		$cont = str_replace('"\\r\\n"', '', $cont);

		$ary = explode("\n",$cont);
		$cont = '';
		foreach ($ary as $a) {
			$cont .= wordwrap($a, 80)."\n";
		}

		$all_cont .= "=============== ".realpath($file)." ===============\n";
		$all_cont .= $cont."\n\n";
		$all_cont .= "?>\n";
	}
}

$outfile = __DIR__.'/.nontranslated.tmp';
$fastphp = 'C:\\Program Files (x86)\\FastPHP\\FastPHPEditor.exe';

file_put_contents($outfile, $all_cont);

if (file_exists($fastphp)) {
	system('"'.$fastphp.'" "'.$outfile.'"');
	unlink($outfile);
} else {
	echo "Generated file $outfile\n";
}

# ---

/**
 * @param string $fileStr
 * @return string
 */
function phpRemoveComments(string $fileStr): string {

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
