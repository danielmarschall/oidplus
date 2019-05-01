<?php

/*
 * XML Encoding Utilities
 * Copyright 2011-2019 Daniel Marschall, ViaThinkSoft
 * Version 1.7
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

// http://www.viathinksoft.de/?page=codelib&showid=89

// Unicode-proof htmlentities.
// Returns 'normal' chars as chars and weirdos as numeric html entites.
// Source: http://www.php.net/manual/en/function.htmlentities.php#107985 ; modified
// Modified by Daniel Marschall, ViaThinkSoft
function htmlentities_numeric($str, $allow_html=false, $encode_linebreaks=false) {
	// Convert $str to UTF-8 if it is not already
	if (mb_detect_encoding($str, "auto", true) != 'UTF-8') {
#		$str = mb_convert_encoding($str, 'UTF-8', 'Windows-1252');
#		$str = mb_convert_encoding($str, 'UTF-8', 'auto');
		$str = mb_convert_encoding($str, 'UTF-8');
	}

	// get rid of existing entities else double-escape
// DM 24.08.2016 Auskommentiert wegen oid+ xml export
//	$str = html_entity_decode(stripslashes($str),ENT_QUOTES,'UTF-8');

	$ar = preg_split('/(?<!^)(?!$)/u', $str);  // return array of every multi-byte character
	$str2 = '';
	foreach ($ar as $c) {
		$o = ord($c);
		if (
			(strlen($c) > 1)      || /* multi-byte [unicode] */
			($o < 32 || $o > 126) || /* <- control / latin weirdos -> */
			($o > 33 && $o < 40)  || /* quotes + ampersand */
			($o > 59 && $o < 63)     /* html */
		) {
			// convert to numeric entity
			$c = mb_encode_numericentity($c, array(0x0, 0xffff, 0, 0xffff), 'UTF-8');

			if ($allow_html) {
				if ($c == '&#60;') $c = '<';
				if ($c == '&#62;') $c = '>';
				if ($c == '&#61;') $c = '=';
				if ($c == '&#34;') $c = '"';
				if ($c == '&#39;') $c = '\'';
				if ($c == '&#38;') $c = '&'; // DM 24.08.2016 Re-Aktiviert wegen oid+ xml export
			}

			if (!$encode_linebreaks) {
				if ($allow_html) {
					if ($c == "&#10;") $c = "<br />";
					if ($c == "&#13;") $c = "<br />";
				} else {
					if ($c == "&#10;") $c = "\n";
					if ($c == "&#13;") $c = "\r";
				}
			}
		}
		$str2 .= $c;
	}
	return $str2;
}

function ordUTF8($c, $index = 0, &$bytes = null) {
	// http://de.php.net/manual/en/function.ord.php#78032

	$len = strlen($c);
	$bytes = 0;

	if ($index >= $len) {
		return false;
	}

	$h = ord($c{$index});

	if ($h <= 0x7F) {
		$bytes = 1;
		return $h;
	} else if ($h < 0xC2) {
		return false;
	} else if ($h <= 0xDF && $index < $len - 1) {
		$bytes = 2;
		return ($h & 0x1F) <<  6 | (ord($c{$index + 1}) & 0x3F);
	} else if ($h <= 0xEF && $index < $len - 2) {
		$bytes = 3;
		return ($h & 0x0F) << 12 | (ord($c{$index + 1}) & 0x3F) << 6
			| (ord($c{$index + 2}) & 0x3F);
	} else if ($h <= 0xF4 && $index < $len - 3) {
		$bytes = 4;
		return ($h & 0x0F) << 18 | (ord($c{$index + 1}) & 0x3F) << 12
			| (ord($c{$index + 2}) & 0x3F) << 6
			| (ord($c{$index + 3}) & 0x3F);
	} else {
		return false;
	}
}

function utf16_to_utf8($str) {
	// http://betamode.de/2008/09/08/php-utf-16-zu-utf-8-konvertieren/
	// http://www.moddular.org/log/utf16-to-utf8

	$c0 = ord($str[0]);
	$c1 = ord($str[1]);
	if ($c0 == 0xFE && $c1 == 0xFF) {
		$be = true;
	} else if ($c0 == 0xFF && $c1 == 0xFE) {
		$be = false;
	} else {
		return $str;
	}
	$str = substr($str, 2);
	$len = strlen($str);
	$dec = '';
	for ($i = 0; $i < $len; $i += 2) {
		$c = ($be) ? ord($str[$i]) << 8 | ord($str[$i + 1]) :
			ord($str[$i + 1]) << 8 | ord($str[$i]);
		if ($c >= 0x0001 && $c <= 0x007F) {
			$dec .= chr($c);
		} else if ($c > 0x07FF) {
			$dec .= chr(0xE0 | (($c >> 12) & 0x0F));
			$dec .= chr(0x80 | (($c >> 6) & 0x3F));
			$dec .= chr(0x80 | (($c >> 0) & 0x3F));
		} else {
			$dec .= chr(0xC0 | (($c >> 6) & 0x1F));
			$dec .= chr(0x80 | (($c >> 0) & 0x3F));
		}
	}
	return $dec;
}

function html_named_to_numeric_entities($str) {
	if (!function_exists('decodeNamedEntities')) {
		function decodeNamedEntities($string) {
			// https://stackoverflow.com/questions/20406599/how-to-encode-for-entity-igrave-not-defined-error-in-xml-feed
			static $entities = NULL;
			if (NULL === $entities) {
				$entities = array_flip(
					array_diff(
						get_html_translation_table(HTML_ENTITIES, ENT_COMPAT | ENT_HTML401, 'UTF-8'),
						get_html_translation_table(HTML_ENTITIES, ENT_COMPAT | ENT_XML1, 'UTF-8')
					)
				);
			}
			return str_replace(array_keys($entities), $entities, $string);
		}
	}

	if (!function_exists('mb_convert_encoding')) {
		// https://riptutorial.com/php/example/15633/converting-unicode-characters-to-their-numeric-value-and-or-html-entities-using-php
		function mb_convert_encoding($str, $to_encoding, $from_encoding = NULL) {
			return iconv(($from_encoding === NULL) ? mb_internal_encoding() : $from_encoding, $to_encoding, $str);
		}
	}

	if (!function_exists('mb_ord')) {
		// https://riptutorial.com/php/example/15633/converting-unicode-characters-to-their-numeric-value-and-or-html-entities-using-php
		function mb_ord($char, $encoding = 'UTF-8') {
			if ($encoding === 'UCS-4BE') {
				list(, $ord) = (strlen($char) === 4) ? @unpack('N', $char) : @unpack('n', $char);
				return $ord;
			} else {
				return mb_ord(mb_convert_encoding($char, 'UCS-4BE', $encoding), 'UCS-4BE');
			}
		}
	}

	if (!function_exists('mb_htmlentities')) {
		// https://riptutorial.com/php/example/15633/converting-unicode-characters-to-their-numeric-value-and-or-html-entities-using-php
		function mb_htmlentities($string, $hex = true, $encoding = 'UTF-8') {
			return preg_replace_callback('/[\x{80}-\x{10FFFF}]/u', function ($match) use ($hex) {
				return sprintf($hex ? '&#x%X;' : '&#%d;', mb_ord($match[0]));
			}, $string);
		}
	}

	if (!mb_detect_encoding($str, 'UTF-8', true)) $str = utf8_encode($str);
	return mb_htmlentities(decodeNamedEntities($str));
}
