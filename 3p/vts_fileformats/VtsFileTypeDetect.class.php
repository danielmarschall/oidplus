<?php

/*
 * File Type Detection for PHP
 * Copyright 2020 Daniel Marschall, ViaThinkSoft
 *
 *    Revision 2020-05-17
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

class VtsFileTypeDetect {

	public static function getMimeType($filename) {
	    $mime_types = array();
	    
		include __DIR__ . '/mimetype_lookup.inc.php';

		foreach ($mime_types as $ext => $mime) {
			if (strtoupper(substr($filename, -strlen($ext)-1)) == strtoupper('.'.$ext)) {
				return $mime;
			}
		}

		return false;
	}

	public static function getDescription($file, $filenames=array(__DIR__.'/filetypes.local', __DIR__.'/filetypes.conf')) {
		// TODO: Make it multi-lang

		$inis = array();
		foreach ($filenames as $num => $filename) {
			$inis[$num] = !file_exists($filename) ? array() : parse_ini_file($filename, true, INI_SCANNER_RAW);
			if (!isset($inis[$num]['OidHeader']))     $inis[$num]['OidHeader']     = array();
			if (!isset($inis[$num]['GuidHeader']))    $inis[$num]['GuidHeader']    = array();
			if (!isset($inis[$num]['FileExtension'])) $inis[$num]['FileExtension'] = array();
			if (!isset($inis[$num]['MimeType']))      $inis[$num]['MimeType']      = array();
		}

		if (is_readable($file)) {
			$h = fopen($file, 'r');
			$line = trim(fgets($h, 128));
			if ((substr($line,0,1) == '[') && ($line[strlen($line)-1] == ']')) {
				$line = substr($line, 1, strlen($line)-2);
				foreach ($inis as $ini) {
					if (isset($ini['OidHeader'][$line]))  return $ini['OidHeader'][$line];
				}
				foreach ($inis as $ini) {
					if (isset($ini['GuidHeader'][$line])) return $ini['GuidHeader'][$line];
				}
			}
			fclose($h);
		}

		foreach ($inis as $ini) {
			foreach ($ini['FileExtension'] as $ext => $name) {
				if (strtoupper(substr($file, -strlen($ext)-1)) == strtoupper('.'.$ext)) {
					return $name;
				}
			}
		}

		$mime = false;
		if (function_exists('mime_content_type')) {
			$mime = @mime_content_type($file);
		}
		if (!$mime) {
			$mime = self::getMimeType($file);
		}
		if ($mime) {
			foreach ($inis as $ini) {
				if (isset($ini['MimeType'][$mime]))  return $ini['MimeType'][$mime];
			}
		}

		return $ini['Static']['LngUnknown'];
	}

}
