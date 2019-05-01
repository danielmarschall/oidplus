<?php

/*
 * OID-Utilities for PHP
 * Copyright 2011-2019 Daniel Marschall, ViaThinkSoft
 * Version 2019-03-25
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

// All functions in this library are compatible with leading zeroes (not recommended) and leading dots

// TODO: change the function names, so that they have a uniform naming schema, and rename "oid identifier" into "asn.1 alphanumeric identifier"
// TODO: Function for finding a shared ancestor, e.g. oid_shared_ancestor('2.999.1.2.3', '2.999.4.5') == '2.999'

define('OID_DOT_FORBIDDEN', 0);
define('OID_DOT_OPTIONAL',  1);
define('OID_DOT_REQUIRED',  2);

/**
 * Checks if an OID has a valid dot notation.
 * @author  Daniel Marschall, ViaThinkSoft
 * @version 2014-12-09
 * @param   $oid (string)<br />
 *              An OID in dot notation.
 * @param   $allow_leading_zeroes (bool)<br />
 *              true of leading zeroes are allowed or not.
 * @param   $allow_leading_dot (bool)<br />
 *              true of leading dots are allowed or not.
 * @return  (bool) true if the dot notation is valid.
 **/
function oid_valid_dotnotation($oid, $allow_leading_zeroes=true, $allow_leading_dot=false, $min_len=0) {
	$regex = oid_validation_regex($allow_leading_zeroes, $allow_leading_dot, $min_len);

	return preg_match($regex, $oid, $m) ? true : false;
}

/**
 * Returns a full regular expression to validate an OID in dot-notation
 * @author  Daniel Marschall, ViaThinkSoft
 * @version 2014-12-09
 * @param   $allow_leading_zeroes (bool)<br />
 *              true of leading zeroes are allowed or not.
 * @param   $allow_leading_dot (bool)<br />
 *              true of leading dots are allowed or not.
 * @return  (string) The regular expression
 **/
function oid_validation_regex($allow_leading_zeroes=true, $allow_leading_dot=false, $min_len=0) {
	$leading_dot_policy = $allow_leading_dot ? OID_DOT_OPTIONAL : OID_DOT_FORBIDDEN;

	$part_regex = oid_part_regex($min_len, $allow_leading_zeroes, $leading_dot_policy);

	return '@^'.$part_regex.'$@';
}

/**
 * Returns a partial regular expression which matches valid OIDs in dot notation.
 * It can be inserted into regular expressions.
 * @author  Daniel Marschall, ViaThinkSoft
 * @version 2014-12-09
 * @param   $min_len (int)<br />
 *              0="." and greater will be recognized, but not ""<br />
 *              1=".2" and greater will be recognized<br />
 *              2=".2.999" and greater will be recognized (default)<br />
 *              etc.
 * @param   $allow_leading_zeroes (bool)<br />
 *              true:  ".2.0999" will be recognized<br />
 *              false: ".2.0999" won't be recognized (default)
 * @param   $leading_dot_policy (int)<br />
 *              0 (OID_DOT_FORBIDDEN): forbidden<br />
 *              1 (OID_DOT_OPTIONAL) : optional (default)<br />
 *              2 (OID_DOT_REQUIRED) : enforced
 * @return  (string) A regular expression which matches OIDs in dot notation
 **/
function oid_part_regex($min_len=2, $allow_leading_zeroes=false, $leading_dot_policy=OID_DOT_OPTIONAL) {
	switch ($leading_dot_policy) {
		case 0: // forbidden
			$lead_dot = '';
			break;
		case 1: // optional
			$lead_dot = '\\.{0,1}';
			break;
		case 2: // enforced
			$lead_dot = '\\.';
			break;
		default:
			assert(false);
			break;
	}

	$lead_zero            = $allow_leading_zeroes ? '0*' : '';
	$zero_till_thirtynine = '(([0-9])|([1-3][0-9]))'; // second arc is limited to 0..39 if root arc is 0..1
	$singledot_option     = ($min_len == 0) && ($leading_dot_policy != OID_DOT_FORBIDDEN) ? '|\\.' : '';
	$only_root_option     = ($min_len <= 1) ? '|('.$lead_dot.$lead_zero.'[0-2])' : '';

	$regex = '
	(
		(
			(
				('.$lead_dot.$lead_zero.'[0-1])
				\\.'.$lead_zero.$zero_till_thirtynine.'
				(\\.'.$lead_zero.'(0|[1-9][0-9]*)){'.max(0, $min_len-2).',}
			)|(
				('.$lead_dot.$lead_zero.'[2])
				(\\.'.$lead_zero.'(0|[1-9][0-9]*)){'.max(0, $min_len-1).',}
			)
			'.$only_root_option.'
			'.$singledot_option.'
		)
	)';

	// Remove the indentations which are used to maintain this large regular expression in a human friendly way
	$regex = str_replace("\n", '', $regex);
	$regex = str_replace("\r", '', $regex);
	$regex = str_replace("\t", '', $regex);
	$regex = str_replace(' ',  '', $regex);

	return $regex;
}

/**
 * Searches all OIDs in $text and outputs them as array.
 * @author  Daniel Marschall, ViaThinkSoft
 * @version 2014-12-09
 * @param   $text (string)<br />
 *              The text to be parsed
 * @param   $min_len (int)<br />
 *              0="." and greater will be recognized, but not ""<br />
 *              1=".2" and greater will be recognized<br />
 *              2=".2.999" and greater will be recognized (default)<br />
 *              etc.
 * @param   $allow_leading_zeroes (bool)<br />
 *              true:  ".2.0999" will be recognized<br />
 *              false: ".2.0999" won't be recognized (default)
 * @param   $leading_dot_policy (int)<br />
 *              0 (OID_DOT_FORBIDDEN): forbidden<br />
 *              1 (OID_DOT_OPTIONAL) : optional (default)<br />
 *              2 (OID_DOT_REQUIRED) : enforced
 * @param   $requires_whitespace_delimiters (bool)<br />
 *              true:  "2.999" will be recognized, as well as " 2.999 " (default)<br />
 *              false: "2.999!" will be reconigzed, as well as "2.999.c" (this might be used in in documentations with templates)
 * @return  (array<string>) An array of OIDs in dot notation
 **/
function parse_oids($text, $min_len=2, $allow_leading_zeroes=false, $leading_dot_policy=OID_DOT_OPTIONAL, $requires_whitespace_delimiters=true) {
	$regex = oid_detection_regex($min_len, $allow_leading_zeroes, $leading_dot_policy, $requires_whitespace_delimiters);

	preg_match_all($regex, $text, $matches);
	return $matches[1];
}

/**
 * Returns a full regular expression for detecting OIDs in dot notation inside a text.
 * @author  Daniel Marschall, ViaThinkSoft
 * @version 2014-12-09
 * @param   $min_len (int)<br />
 *              0="." and greater will be recognized, but not ""<br />
 *              1=".2" and greater will be recognized<br />
 *              2=".2.999" and greater will be recognized (default)<br />
 *              etc.
 * @param   $allow_leading_zeroes (bool)<br />
 *              true:  ".2.0999" will be recognized<br />
 *              false: ".2.0999" won't be recognized (default)
 * @param   $leading_dot_policy (int)<br />
 *              0 (OID_DOT_FORBIDDEN): forbidden<br />
 *              1 (OID_DOT_OPTIONAL) : optional (default)<br />
 *              2 (OID_DOT_REQUIRED) : enforced
 * @param   $requires_whitespace_delimiters (bool)<br />
 *              true:  "2.999" will be recognized, as well as " 2.999 " (default)<br />
 *              false: "2.999!" will be reconigzed, as well as "2.999.c" (this might be used in in documentations with templates)
 * @return  (string) The regular expression
 **/
function oid_detection_regex($min_len=2, $allow_leading_zeroes=false, $leading_dot_policy=OID_DOT_OPTIONAL, $requires_whitespace_delimiters=true) {
	if ($requires_whitespace_delimiters) {
		// A fully qualified regular expression which can be used by preg_match()
		$begin_condition = '(?<=^|\\s)';
		$end_condition   = '(?=\\s|$)';
	} else {
		// A partial expression which can be used inside another regular expression
		$begin_condition = '(?<![\d])';
		$end_condition   = '(?![\d])';
	}

	$part_regex = oid_part_regex($min_len, $allow_leading_zeroes, $leading_dot_policy);

	return '@'.$begin_condition.$part_regex.$end_condition.'@';
}

/**
 * Returns the parent of an OID in dot notation or the OID itself, if it is the root.<br />
 * Leading dots and leading zeroes are tolerated.
 * @author  Daniel Marschall, ViaThinkSoft
 * @version 2014-12-16
 * @param   $oid (string)<br />
 *              An OID in dot notation.
 * @return  (string) The parent OID in dot notation.
 **/
function oid_up($oid) {
	$oid = sanitizeOID($oid, 'auto');
	if ($oid === false) return false;

	$p = strrpos($oid, '.');
	if ($p === false) return $oid;
	if ($p == 0) return '.';

	return substr($oid, 0, $p);
}

/**
 * Outputs the depth of an OID.
 * @author  Daniel Marschall, ViaThinkSoft
 * @version 2014-12-09
 * @param   $oid (string) An OID in dot notation (with or without leading dot)
 * @return  (int) The depth of the OID, e.g. 2.999 and .2.999 has the length 2.
 **/
function oid_len($oid) {
	if ($oid == '') return 0;
	if ($oid[0] == '.') $oid = substr($oid, 1);
	return substr_count($oid, '.')+1;
}
function oid_depth($oid) {
	return oid_len($oid);
}

/**
 * Lists all parents of an OID.
 * This function tolerates leading dots. The parent of '.' stays '.'.
 * The OID will not be checked for validity!
 * @author  Daniel Marschall, ViaThinkSoft
 * @version 2014-12-17
 * @param   $oid (string)<br />
 *              An OID in dot notation.
 * @return  (array<string>) An array with all parent OIDs.
 **/
function oid_parents($oid) {
	$parents = array();

	while (oid_len($oid) > 1) {
		$oid = oid_up($oid);
		$parents[] = $oid;
	}

	if (substr($oid, 0, 1) == '.') $parents[] = '.';

	return $parents;
}

/*
assert(oid_parents('.1.2.999') == array('.1.2', '.1', '.'));
assert(oid_parents('1.2.999') == array('1.2', '1'));
assert(oid_parents('.') == array('.'));
assert(oid_parents('') == array());
*/

/**
 * Sorts an array containing OIDs in dot notation.
 * @author  Daniel Marschall, ViaThinkSoft
 * @version 2014-12-09
 * @param   $ary (array<string>)<br />
 *              An array of OIDs in dot notation.<br />
 *              This array will be changed by this method.
 * @param   $output_with_leading_dot (bool)<br />
 *              true: The array will be normalized to OIDs with a leading dot.
 *              false: The array will be normalized to OIDs without a leading dot. (default)
 * @return  Nothing
 **/
function oidSort(&$ary, $output_with_leading_dot=false) {
	$out = array();

	$none = $output_with_leading_dot ? '.' : '';

	$d = array();
	foreach ($ary as &$oid) {
		if (($oid == '') || ($oid == '.')) {
			$out[] = $none;
		} else {
			$oid = sanitizeOID($oid, 'auto'); // strike leading zeroes
			$bry = explode('.', $oid, 2);
			$firstarc = $bry[0];
			$rest     = (isset($bry[1])) ? $bry[1] : '';
			$d[$firstarc][] = $rest;
		}
	}
	unset($oid);
	ksort($d);

	foreach ($d as $firstarc => &$data) {
		oidSort($data);
		foreach ($data as &$rest) {
			$out[] = ($output_with_leading_dot ? '.' : '')."$firstarc" . (($rest != $none) ? ".$rest" : '');
		}
	}
	unset($data);

	$ary = $out;
}

/**
 * Removes leading zeroes from an OID in dot notation.
 * @author  Daniel Marschall, ViaThinkSoft
 * @version 2015-08-17
 * @param   $oid (string)<br />
 *              An OID in dot notation.
 * @param   $leading_dot (bool)<br />
 *              true: The OID is valid, if it contains a leading dot.<br />
 *              false (default): The OID is valid, if it does not contain a leading dot.
 *              'auto: Allow both
 * @return  (mixed) The OID without leading dots, or <code>false</code> if the OID is syntactically wrong.
 **/
$oid_sanitize_cache = array();
function sanitizeOID($oid, $leading_dot=false) {
	if ($leading_dot) $leading_dot = substr($oid,0,1) == '.';

	// We are using a cache, since this function is used very often by OID+
	global $oid_sanitize_cache;
	$v = ($leading_dot ? 'T' : 'F').$oid;
	if (isset($oid_sanitize_cache[$v])) return $oid_sanitize_cache[$v];

	if ($leading_dot) {
		if ($oid == '.') return '';
	} else {
		if ($oid == '') return '';
	}

	$out = '';
	$ary = explode('.', $oid);
	foreach ($ary as $n => &$a) {
		if (($leading_dot) && ($n == 0)) {
			if ($a != '') return false;
			continue;
		}

		if (!ctype_digit($a)) return false; // does contain something other than digits

		// strike leading zeroes
		$a = preg_replace("@^0+@", '', $a);
		if ($a == '') $a = 0;

		if (($leading_dot) || ($n != 0)) $out .= '.';
		$out .= $a;
	}
	unset($a);
	unset($ary);

	$oid_sanitize_cache[$v] = $out;
	return $out;
}

/**
 * Shows the top arc of an OID.
 * This function tolerates leading dots.
 * @author  Daniel Marschall, ViaThinkSoft
 * @version 2014-12-16
 * @param   $oid (string)<br />
 *              An OID in dot notation.
 * @return  (mixed) The top arc of the OID or empty string if it is already the root ('.')
 **/
function oid_toparc($oid) {
	$leadingdot = substr($oid,0,1) == '.';

	$oid = sanitizeOID($oid, $leadingdot);
	if ($oid === false) return false;

	if (!$leadingdot) $oid = '.'.$oid;

	$p = strrpos($oid, '.');
	if ($p === false) return false;
	$r = substr($oid, $p+1);

	if ($leadingdot) {
	#	if ($r == '') return '.';
		return $r;
	} else {
		return substr($r, 1);
	}
}

/**
 * Calculates the distance between two OIDs.
 * This function tolerates leading dots and leading zeroes.
 * @author  Daniel Marschall, ViaThinkSoft
 * @version 2014-12-20
 * @param   $a (string)<br />
 *              An OID.
 * @param   $b (string)<br />
 *              An OID.
 * @return  (string) false if both OIDs do not have a child-parent or parent-child relation, e.g. oid_distance('2.999.1.2.3', '2.999.4.5') = false, or if one of the OIDs is syntactially invalid<br />
 *              >0 if $a is more specific than $b , e.g. oid_distance('2.999.1.2', '2.999') = 2<br />
 *              <0 if $a is more common than $b , e.g. oid_distance('2.999', '2.999.1.2') = -2
 **/
function oid_distance($a, $b) {
	if (substr($a,0,1) == '.') $a = substr($a,1);
	if (substr($b,0,1) == '.') $b = substr($b,1);

	$a = sanitizeOID($a, false);
	if ($a === false) return false;
	$b = sanitizeOID($b, false);
	if ($b === false) return false;

	$ary = explode('.', $a);
	$bry = explode('.', $b);

	$min_len = min(count($ary), count($bry));

	for ($i=0; $i<$min_len; $i++) {
		if ($ary[$i] != $bry[$i]) return false;
	}

	return count($ary) - count($bry);
}

/*
assert(oid_distance('2.999.1.2.3', '2.999.4.5') === false);
assert(oid_distance('2.999.1.2', '2.999') === 2);
assert(oid_distance('2.999', '2.999.1.2') === -2);
*/

/**
 * Adds a leading dot to an OID.
 * Leading zeroes are tolerated.
 * @author  Daniel Marschall, ViaThinkSoft
 * @version 2014-12-20
 * @param   $oid (string)<br />
 *              An OID.
 * @return  (string) The OID with a leading dot or false if the OID is syntactially wrong.
 **/
function oid_add_leading_dot($oid) {
	$oid = sanitizeOID($oid, 'auto');
	if ($oid === false) return false;

	if ($oid[0] != '.') $oid = '.'.$oid;
	return $oid;
}

/**
 * Removes a leading dot to an OID.
 * Leading zeroes are tolerated.
 * @author  Daniel Marschall, ViaThinkSoft
 * @version 2014-12-20
 * @param   $oid (string)<br />
 *              An OID.
 * @return  (string) The OID without a leading dot or false if the OID is syntactially wrong.
 **/
function oid_remove_leading_dot($oid) {
	$oid = sanitizeOID($oid, 'auto');
	if ($oid === false) return false;

	if (substr($oid,0,1) == '.') $oid = substr($oid, 1);
	return $oid;
}


# === OID-IRI NOTATION FUNCTIONS ===

if (!function_exists('mb_ord')) {
	# http://stackoverflow.com/a/24755772/3544341
	function mb_ord($char, $encoding = 'UTF-8') {
		if ($encoding === 'UCS-4BE') {
			list(, $ord) = (strlen($char) === 4) ? @unpack('N', $char) : @unpack('n', $char);
			return $ord;
		} else {
			return mb_ord(mb_convert_encoding($char, 'UCS-4BE', $encoding), 'UCS-4BE');
		}
	}
}

function iri_char_valid($c, $firstchar, $lastchar) {
	// see Rec. ITU-T X.660, clause 7.5

	if (($firstchar || $lastchar) && ($c == '-')) return false;

	if ($c == '-') return true;
	if ($c == '.') return true;
	if ($c == '_') return true;
	if ($c == '~') return true;
	if (($c >= '0') && ($c <= '9') && (!$firstchar)) return true;
	if (($c >= 'A') && ($c <= 'Z')) return true;
	if (($c >= 'a') && ($c <= 'z')) return true;

	$v = mb_ord($c);

	if (($v >= 0x000000A0) && ($v <= 0x0000DFFE)) return true;
	if (($v >= 0x0000F900) && ($v <= 0x0000FDCF)) return true;
	if (($v >= 0x0000FDF0) && ($v <= 0x0000FFEF)) return true;
	if (($v >= 0x00010000) && ($v <= 0x0001FFFD)) return true;
	if (($v >= 0x00020000) && ($v <= 0x0002FFFD)) return true;
	if (($v >= 0x00030000) && ($v <= 0x0003FFFD)) return true;
	if (($v >= 0x00040000) && ($v <= 0x0004FFFD)) return true;
	if (($v >= 0x00050000) && ($v <= 0x0005FFFD)) return true;
	if (($v >= 0x00060000) && ($v <= 0x0006FFFD)) return true;
	if (($v >= 0x00070000) && ($v <= 0x0007FFFD)) return true;
	if (($v >= 0x00080000) && ($v <= 0x0008FFFD)) return true;
	if (($v >= 0x00090000) && ($v <= 0x0009FFFD)) return true;
	if (($v >= 0x000A0000) && ($v <= 0x000AFFFD)) return true;
	if (($v >= 0x000B0000) && ($v <= 0x000BFFFD)) return true;
	if (($v >= 0x000C0000) && ($v <= 0x000CFFFD)) return true;
	if (($v >= 0x000D0000) && ($v <= 0x000DFFFD)) return true;
	if (($v >= 0x000E1000) && ($v <= 0x000EFFFD)) return true;

	// Note: Rec. ITU-T X.660, clause 7.5.3 would also forbid ranges which are marked in ISO/IEC 10646 as "(This position shall not be used)"
	// But tool implementers should be tolerate them, since these limitations can be removed in future.

	return false;
}

function iri_arc_valid($arc, $allow_numeric=true) {
	if ($arc == '') return false;

	if ($allow_numeric && preg_match('@^(\\d+)$@', $arc, $m)) return true; # numeric arc

	// Question: Should we strip RTL/LTR characters?

	if (mb_substr($arc, 2, 2) == '--') return false; // see Rec. ITU-T X.660, clause 7.5.4

	$array = array();
	preg_match_all('/./u', $arc, $array, PREG_SET_ORDER);
	$len = count($array);
	foreach ($array as $i => $char) {
		if (!iri_char_valid($char[0], $i==0, $i==$len-1)) return false;
	}

	return true;
}

/**
 * Checks if an IRI identifier is valid or not.
 * @author  Daniel Marschall, ViaThinkSoft
 * @version 2014-12-17
 * @param   $iri (string)<br />
 *              An OID in OID-IRI notation, e.g. /Example/test
 * @return  (bool) true if the IRI identifier is valid.
 **/
function iri_valid($iri) {
	if ($iri == '/') return true; // OK?

	if (substr($iri, 0, 1) != '/') return false;

	$ary = explode('/', $iri);
	array_shift($ary);
	foreach ($ary as $a) {
		if (!iri_arc_valid($a)) return false;
	}

	return true;
}

/*
assert(iri_arc_valid('ABCDEF'));
assert(!iri_arc_valid('-ABCDEF'));
assert(!iri_arc_valid('ABCDEF-'));
assert(!iri_arc_valid(' ABCDEF'));
assert(!iri_arc_valid('2 ABCDEF'));
assert(!iri_arc_valid(''));

assert(!iri_valid(''));
assert(iri_valid('/'));
assert(iri_valid('/hello/world'));
assert(iri_valid('/123/world'));
assert(!iri_valid('/hello/0world'));
assert(!iri_valid('/hello/xo--test'));
assert(!iri_valid('/hello/-super-/sd'));
*/

/**
 * Returns an associative array in the form 'ASN.1' => '/2/1' .
 * @author  Daniel Marschall, ViaThinkSoft
 * @version 2018-01-05
 * @see http://itu.int/go/X660
 * @return  (array) An associative array in the form 'ASN.1' => '/2/1' .
 **/
function iri_get_long_arcs() {
	$iri_long_arcs = array();
	$iri_long_arcs['ASN.1'] = '/2/1';
	$iri_long_arcs['Country'] = '/2/16';
	$iri_long_arcs['International-Organizations'] = '/2/23';
	$iri_long_arcs['UUID'] = '/2/25';
	$iri_long_arcs['Tag-Based'] = '/2/27';
	$iri_long_arcs['BIP'] = '/2/41';
	$iri_long_arcs['Telebiometrics'] = '/2/42';
	$iri_long_arcs['Cybersecurity'] = '/2/48';
	$iri_long_arcs['Alerting'] = '/2/49';
	$iri_long_arcs['OIDResolutionSystem'] = '/2/50';
	$iri_long_arcs['GS1'] = '/2/51';
	$iri_long_arcs['Example'] = '/2/999'; // English
	$iri_long_arcs['Exemple'] = '/2/999'; // French
	$iri_long_arcs['Ejemplo'] = '/2/999'; // Spanish
	$iri_long_arcs["\u{0627}\u{0644}\u{0645}\u{062B}\u{0627}\u{0644}"] = '/2/999'; // Arabic
	$iri_long_arcs["\u{8303}\u{4F8B}"] = '/2/999'; // Chinese
	$iri_long_arcs["\u{041F}\u{0440}\u{0438}\u{043C}\u{0435}\u{0440}"] = '/2/999'; // Russian
	$iri_long_arcs["\u{C608}\u{C81C}"] = '/2/999'; // Korean
	$iri_long_arcs["\u{4F8B}"] = '/2/999'; // Japanese
	$iri_long_arcs['Beispiel'] = '/2/999'; // German
	return $iri_long_arcs;
}

/**
 * Tries to shorten/simplify an IRI by applying "long arcs", e.g. /2/999/123 -> /Example/123 .
 * @author  Daniel Marschall, ViaThinkSoft
 * @version 2014-12-28
 * @param   $iri (string)<br />
 *              An OID in OID-IRI notation, e.g. /Example/test
 * @return  (string) The modified IRI.
 **/
function iri_add_longarcs($iri) {
	$iri_long_arcs = iri_get_long_arcs();

	// TODO: $iri valid?

	$ary = explode('/', $iri);

	$ary_number_iri = $ary;
	if ($ary_number_iri[1] == 'Joint-ISO-ITU-T') $ary_number_iri[1] = '2';
	/*
	if ($ary_number_iri[1] == '2') {
		// TODO: /2/Example -> /2/999 -> /Example
	} else {
		// Currently, only long arcs inside .2 are defined
		// return $iri;
	}
	*/
	$number_iri = implode('/', $ary_number_iri);

	foreach ($iri_long_arcs as $cur_longarc => $cur_iri) {
		// TODO: $cur_iri valid?

		if (strpos($number_iri.'/', $cur_iri.'/') === 0) {
			$cnt = substr_count($cur_iri, '/');
			for ($i=1; $i<$cnt; $i++) {
				array_shift($ary);
			}
			$ary[0] = '';
			$ary[1] = $cur_longarc;
			$iri = implode('/', $ary);
			break;
		}
	}

	return $iri;
}

# === FUNCTIONS FOR OIDS IN ASN.1 NOTATION ===

/**
 * Checks if an ASN.1 identifier is valid.
 * @author  Daniel Marschall, ViaThinkSoft
 * @version 2014-12-09
 * @param   $id (string)<br />
 *              An ASN.1 identifier, e.g. "example". Not "example(99)" or "99" and not a path like "{ 2 999 }"
 *              Note: Use asn1_path_valid() for validating a whole ASN.1 notation path.
 * @return  (bool) true, if the identifier is valid: It begins with an lowercase letter and contains only 0-9, a-z, A-Z and "-"
 **/
# TODO: umbenennen in asn1_alpha_id_valid
function oid_id_is_valid($id) {
	return preg_match('/^([a-z][a-zA-Z0-9-]*)$/', $id);
}

/**
 * Checks if the ASN.1 notation of an OID is valid.
 * This function does not tolerate leading zeros.
 * This function will fail (return false) if there are unresolved symbols, e.g. {iso test} is not valid while { iso 123 } is valid.
 * @author  Daniel Marschall, ViaThinkSoft
 * @version 2014-12-17
 * @param   $asn (string)<br />
 *              An OID in ASN.1 notation.
 * @return  (bools) true if the identifier is valid.
 **/
function asn1_path_valid($asn1) {
	return asn1_to_dot($asn1) != false;
}

/**
 * Returns an array of standardized ASN.1 alphanumeric identifiers which do not require a numeric identifier, e.g. { 2 example }
 * The array has the form '0.0.a' -> '0.0.1'
 * @author  Daniel Marschall, ViaThinkSoft
 * @version 2019-03-25
 * @see http://www.oid-info.com/name-forms.htm
 * @return  (array) Associative array of standardized ASN.1 alphanumeric identifiers
 **/
function asn1_get_standardized_array() {

	// Taken from oid-info.com
	// http://www.oid-info.com/name-forms.htm
	$standardized = array();
	$standardized['itu-t'] = '0';
	$standardized['ccitt'] = '0';
	$standardized['iso'] = '1';
	$standardized['joint-iso-itu-t'] = '2';
	$standardized['joint-iso-ccitt'] = '2';
	$standardized['0.recommendation'] = '0.0';
	$standardized['0.0.a'] = '0.0.1';
	$standardized['0.0.b'] = '0.0.2';
	$standardized['0.0.c'] = '0.0.3';
	$standardized['0.0.d'] = '0.0.4';
	$standardized['0.0.e'] = '0.0.5';
	$standardized['0.0.f'] = '0.0.6';
	$standardized['0.0.g'] = '0.0.7';
	$standardized['0.0.h'] = '0.0.8';
	$standardized['0.0.i'] = '0.0.9';
	$standardized['0.0.j'] = '0.0.10';
	$standardized['0.0.k'] = '0.0.11';
	$standardized['0.0.l'] = '0.0.12';
	$standardized['0.0.m'] = '0.0.13';
	$standardized['0.0.n'] = '0.0.14';
	$standardized['0.0.o'] = '0.0.15';
	$standardized['0.0.p'] = '0.0.16';
	$standardized['0.0.q'] = '0.0.17';
	$standardized['0.0.r'] = '0.0.18';
	$standardized['0.0.s'] = '0.0.19';
	$standardized['0.0.t'] = '0.0.20';
	$standardized['0.0.u'] = '0.0.21';
	$standardized['0.0.v'] = '0.0.22';
	$standardized['0.0.w'] = '0.0.23'; // actually, this OID does not exist
	$standardized['0.0.x'] = '0.0.24';
	$standardized['0.0.y'] = '0.0.25';
	$standardized['0.0.z'] = '0.0.26';
	$standardized['0.question'] = '0.1';
	$standardized['0.administration'] = '0.2';
	$standardized['0.network-operator'] = '0.3';
	$standardized['0.identified-organization'] = '0.4';
	$standardized['1.standard'] = '1.0';
	$standardized['1.registration-authority'] = '1.1';
	$standardized['1.member-body'] = '1.2';
	$standardized['1.identified-organization'] = '1.3';
	return $standardized;
}

/**
 * Converts an OID in ASN.1 notation into an OID in dot notation and tries to resolve well-known identifiers.<br />
 * e.g. {joint-iso-itu-t(2) example(999) 1 2 3} --> 2.999.1.2.3<br />
 * e.g. {iso 3} --> 1.3
 * This function does not tolerate leading zeros.
 * This function will fail (return false) if there are unresolved symbols, e.g. {iso test} will not be resolved to 1.test
 * @author  Daniel Marschall, ViaThinkSoft
 * @version 2014-12-17
 * @param   $asn (string)<br />
 *              An OID in ASN.1 notation.
 * @return  (string) An OID in dot notation without leading dot or false if the path is invalid.
 **/
function asn1_to_dot($asn) {
	$standardized = asn1_get_standardized_array();

	// Clean up
	$asn = preg_replace('@^\\{(.+)\\}$@', '\\1', $asn, -1, $count);
	if ($count == 0) return false; // { and } are required. The asn.1 path will NOT be trimmed by this function

	// If identifier is set, apply it (no check if it overrides a standardized identifier)
	$asn = preg_replace('|\s*([a-z][a-zA-Z0-9-]*)\s*\((\d+)\)|', ' \\2', $asn);
	$asn = trim($asn);

	// Set dots
	$asn = preg_replace('|\s+|', '.', $asn);

	// Apply standardized identifiers (case sensitive)
	$asn .= '.';
	foreach ($standardized as $s => $r) {
		$asn = preg_replace("|^$s|", $r, $asn);
	}
	$asn = substr($asn, 0, strlen($asn)-1);

	// Check if all numbers are OK
	// -> every arc must be resolved
	// -> numeric arcs must not have a leading zero
	// -> invalid stuff will be recognized, e.g. a "(1)" without an identifier in front of it
	$ary = explode('.', $asn);
	foreach ($ary as $a) {
		if (!preg_match('@^(0|([1-9]\\d*))$@', $a, $m)) return false;
	}

	return $asn;
}

/*
assert(asn1_to_dot('{2 999 (1)}') == false);
assert(asn1_to_dot('{2 999 test}') == false);
assert(asn1_to_dot('{2 999 1}') == '2.999.1');
assert(asn1_to_dot(' {2 999 1} ') == false);
assert(asn1_to_dot('2 999 1') == false);
assert(asn1_to_dot('{2 999 01}') == false);
assert(asn1_to_dot('{  0   question 123  }') == '0.1.123');
assert(asn1_to_dot('{  iso  }') == '1');
assert(asn1_to_dot('{  iso(1)  }') == '1');
assert(asn1_to_dot('{  iso(2)  }') == '2');
assert(asn1_to_dot('{  iso 3 }') == '1.3');
*/

/**
 * "Soft corrects" an invalid ASN.1 identifier.<br />
 * Attention, by "soft correcting" the ID, it is not authoritative anymore, and might not be able to be resolved by ORS.
 * @author  Daniel Marschall, ViaThinkSoft
 * @version 2014-12-09
 * @param   $id (string)<br />
 *              An ASN.1 identifier.
 * @param   $append_id_prefix (bool)<br />
 *              true (default): If the identifier doesn't start with a-Z, the problem will be solved by prepending "id-" to the identifier.<br />
 *              false: If the identifier doesn't start with a-Z, then the problem cannot be solved (method returns empty string).
 * @return  (string) The "soft corrected" ASN.1 identifier.<br />
 *              Invalid characters will be removed.<br />
 *              Uncorrectable start elements (0-9 or "-") will be either removed or solved by prepending "id-" (see <code>$append_id_prefix</code>)<br />
 *              If the identifier begins with an upper case letter, the letter will be converted into lower case.
 **/
function oid_soft_correct_id($id, $append_id_prefix = true) {
	// Convert "_" to "-"
	$id = str_replace('_', '-', $id);

	// Remove invalid characters
	$id = preg_replace('/[^a-zA-Z0-9-]+/', '', $id);

	// Remove uncorrectable start elements (0-9 or "-")
	if ($append_id_prefix) {
		$id = preg_replace('/^([^a-zA-Z]+)/', 'id-$1', $id);
	} else {
		$id = preg_replace('/^([^a-zA-Z]+)/', '', $id);
	}

	// "Correct" upper case beginning letter by converting it to lower case
	if (preg_match('/^[A-Z]/', $id)) {
		$id = strtolower($id[0]) . substr($id, 1);
	}

	return $id;
}

