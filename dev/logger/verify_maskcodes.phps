#!/usr/bin/env php
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

$dir = __DIR__ . '/../../';

define('INSIDE_OIDPLUS',true);
include_once $dir.'vendor/danielmarschall/php_utils/misc_functions.inc.php'; // PHP 7.x compat functions
require_once $dir.'includes/classes/OIDplusBaseClass.class.php';
require_once $dir.'includes/classes/OIDplusLogger.class.php';

const VERBOSE = false;

use ViaThinkSoft\OIDplus\OIDplusLogger;

// ---

$errors_found = false;

$cntfiles = 0;
$cntcodes = 0;
$it = new RecursiveDirectoryIterator($dir);
$it->setFlags(FilesystemIterator::SKIP_DOTS); // DOES NOT WORK! Folders with . prefix still get evaluated!
foreach(new RecursiveIteratorIterator($it) as $file) {
	if ((strpos(str_replace('\\','/',realpath($file)),'/vendor/') !== false) && (strpos(str_replace('\\','/',realpath($file)),'/vendor/danielmarschall/') === false)) continue; // ignore third-party-code
	if (strpos(str_replace('\\','/',realpath($file)),'/dev/') !== false) continue; // ignore development utilities

	if (preg_match('@[/\\\\]\\.[^\\.]@',$file,$m)) continue; // Alternative to SKIP_DOTS

	if ($file->getExtension() == 'php') {
		$cont = file_get_contents($file);
		$cont = phpRemoveComments($cont);

		$cntfiles++;

		preg_match_all('@OIDplus::logger\(\)\->log\(\s*(["\'])([^"\']+)(["\'])@', $cont, $m);
		foreach ($m[2] as $str) {
			$cntcodes++;
			if (OIDplusLogger::parse_maskcode($str) === false) {
				$file = substr($file, strlen($dir));
				echo "Invalid maskcode '$str' in file '$file'\n";
				$errors_found = true;
			} else {
				if (VERBOSE) echo 'Valid: '.$str."\n";
			}
		}
	}
}
echo "Done. Checked $cntcodes mask codes in $cntfiles files.\n";

exit($errors_found ? 1 : 0);

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
