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

require_once __DIR__ . '/../../../../includes/oidplus.inc.php';

OIDplus::init(true);
set_exception_handler(array('OIDplusGui', 'html_exception_handler'));

if (OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_OIDplusPagePublicWhois', false)) {
	throw new OIDplusException(_L('This plugin was disabled by the system administrator!'));
}

originHeaders();

// Step 0: Get request parameter

if (PHP_SAPI == 'cli') {
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

// Split input into query, authTokens and serverCommands
$tokens = explode('$', $query);
$query = array_shift($tokens);
$authTokens[] = array();
$serverCommands = array();
foreach ($tokens as $token) {
	if (strpos($token,'=') !== false) {
		$tmp = explode('=',$token,2);
		$serverCommands[strtolower($tmp[0])] = $tmp[1];
	} else {
		$authTokens[] = $token;
	}
}

$query = str_replace('oid:.', 'oid:', $query); // allow leading dot

// Step 1: Collect data

$out = array();

$out[] = "query: $query";

$distance = null;
$found = null;

try {
	$obj = OIDplusObject::findFitting($query);
	if (!$obj) $obj = OIDplusObject::parse($query); // in case we didn't find anything fitting, we take it as it is and later use getParent() to find something else
	$query = $obj->nodeId();
} catch (Exception $e) {
	$obj = null;
}

$only_wellknown_ids_found = false;
$continue = false;

if (!$obj) {
	$out[] = "result: Not found"; // DO NOT TRANSLATE!
	$continue = false;
	$res = null;
} else {
	$obj = null;
	$distance = 0;

	$init_query = $query;
	while (true) {
		$res = OIDplus::db()->query("select * from ###objects where id = ?", array($query));
		if ($res->num_rows() > 0) {
			$obj = OIDplusObject::parse($query);
			if ($distance > 0) {
				$out[] = "result: Not found; superior object found"; // DO NOT TRANSLATE!
				$out[] = "distance: $distance"; // DO NOT TRANSLATE
			} else {
				$out[] = "result: Found"; // DO NOT TRANSLATE!
			}
			$continue = true;
			break;
		}

		if (substr($query,0,4) === 'oid:') {
			$query_prev = $query;
			$query = 'oid:'.oid_up(explode(':',$query,2)[1]);
			if ($query == $query_prev) break;
			$distance++;
		} else {
			$obj = OIDplusObject::parse($query)->getParent(); // For objects, we assume that they are parents of each other
			if ($obj) {
				$res = OIDplus::db()->query("select * from ###objects where id = ?", array($obj->nodeId()));
				$distance = $obj->distance($query);
				assert($res->num_rows() > 0);

				$query = $obj->nodeId();
			}
			break;
		}
	}

	if ((substr($query,0,4) === 'oid:') && (!$obj)) {
		$query = $init_query;
		$distance = 0;
		while (true) {
			$res = OIDplus::db()->query("select * from ###asn1id where oid = ? union select * from ###iri where oid = ?", array($query, $query));
			if ($res->num_rows() > 0) {
				$obj = OIDplusObject::parse($query);
				$res = null;
				if ($distance > 0) {
					$out[] = "result: Not found; superior object found"; // DO NOT TRANSLATE!
					$out[] = "distance: $distance"; // DO NOT TRANSLATE
				} else {
					$out[] = "result: Found"; // DO NOT TRANSLATE!
				}
				$only_wellknown_ids_found = true; // Information partially available
				$continue = true;
				break;
			}
			$query_prev = $query;
			$query = 'oid:'.oid_up(explode(':',$query,2)[1]);
			if ($query == $query_prev) break;
			$distance++;
		}
	}

	if (!$obj) {
		$out[] = "result: Not found"; // DO NOT TRANSLATE!
		$continue = false;
	}

	$found = $distance == 0;
}

if ($continue) {
	$out[] = "";
	$out[] = "object: $query"; // DO NOT TRANSLATE!
	if (!allowObjectView($obj, $authTokens)) {
		$out[] = "status: Information unavailable"; // DO NOT TRANSLATE!
		$out[] = "attribute: confidential"; // DO NOT TRANSLATE!
	} else {
		if ($only_wellknown_ids_found) {
			$out[] = "status: Information partially available"; // DO NOT TRANSLATE!
		} else {
			$out[] = "status: Information available"; // DO NOT TRANSLATE!
		}

		$row = $res ? $res->fetch_object() : null;

		if (!is_null($row)) $out[] = 'name: ' . $row->title; // DO NOT TRANSLATE!

		if (!is_null($row)) {
			$cont = $row->description;
			$cont = preg_replace('@<a[^>]+href\s*=\s*["\']([^\'"]+)["\'][^>]*>(.+)<\s*/\s*a\s*>@ismU', '\2 (\1)', $cont);
			$cont = preg_replace('@<br.*>@', "\n", $cont);
			$cont = preg_replace('@\\n+@', "\n", $cont);
			$out[] = 'description: ' . trim(html_entity_decode(strip_tags($cont))); // DO NOT TRANSLATE!
		}

		if (substr($query,0,4) === 'oid:') {
			$out[] = 'asn1-notation: ' . $obj->getAsn1Notation(false); // DO NOT TRANSLATE!
			$out[] = 'iri-notation: ' . $obj->getIriNotation(false); // DO NOT TRANSLATE!

			$res2 = OIDplus::db()->query("select * from ###asn1id where oid = ?", array($obj->nodeId()));
			while ($row2 = $res2->fetch_object()) {
				$out[] = 'identifier: ' . $row2->name; // DO NOT TRANSLATE!
			}

			$res2 = OIDplus::db()->query("select * from ###asn1id where standardized = ? and oid = ?", array(true, $obj->nodeId()));
			while ($row2 = $res2->fetch_object()) {
				$out[] = 'standardized-id: ' . $row2->name; // DO NOT TRANSLATE!
			}

			$res2 = OIDplus::db()->query("select * from ###iri where oid = ?", array($obj->nodeId()));
			while ($row2 = $res2->fetch_object()) {
				$out[] = 'unicode-label: ' . $row2->name; // DO NOT TRANSLATE!
			}

			$res2 = OIDplus::db()->query("select * from ###iri where longarc = ? and oid = ?", array(true, $obj->nodeId()));
			while ($row2 = $res2->fetch_object()) {
				$out[] = 'long-arc: ' . $row2->name; // DO NOT TRANSLATE!
			}
		}

		if ($obj->isConfidential()) { // yes, we use isConfidential() instead of allowObjectView()!
			$out[] = 'attribute: confidential';
		}

		foreach (OIDplus::getPagePlugins() as $plugin) {
			if ($plugin->implementsFeature('1.3.6.1.4.1.37476.2.5.2.3.4')) {
				$plugin->whoisObjectAttributes($obj->nodeId(), $out);
			}
		}

		if (substr($query,0,4) === 'oid:') {
			$sParent = 'oid:'.oid_up(explode(':',$query,2)[1]);

			$objTest = OIDplusObject::parse($sParent);
			if (allowObjectView($objTest, $authTokens)) {
				$out[] = 'parent: ' . $sParent . show_asn1_appendix($sParent); // DO NOT TRANSLATE!
			} else {
				$out[] = 'parent: ' . $sParent; // DO NOT TRANSLATE!
			}
		} else if (!is_null($row) && !empty($row->parent) && (!is_root($row->parent))) {
			$sParent = $row->parent;
			$out[] = 'parent: ' . $row->parent; // DO NOT TRANSLATE!
		}

		$res2 = OIDplus::db()->query("select * from ###objects where parent = ? order by ".OIDplus::db()->natOrder('id'), array($obj->nodeId()));
		while ($row2 = $res2->fetch_object()) {
			$objTest = OIDplusObject::parse($row2->id);
			if (allowObjectView($objTest, $authTokens)) {
				$out[] = 'subordinate: ' . $row2->id . show_asn1_appendix($row2->id); // DO NOT TRANSLATE!
			} else {
				$out[] = 'subordinate: ' . $row2->id; // DO NOT TRANSLATE!
			}
		}

		if (!is_null($row)) {
			if ($row->created) $out[] = 'created: ' . date('Y-m-d H:i:s', strtotime($row->created)); // DO NOT TRANSLATE!
			if ($row->updated) $out[] = 'updated: ' . date('Y-m-d H:i:s', strtotime($row->updated)); // DO NOT TRANSLATE!
		}

		$out[] = '';

		$res2 = OIDplus::db()->query("select * from ###ra where email = ?", array(is_null($row) ? '' : $row->ra_email));
		if ($row2 = $res2->fetch_object()) {
			$out[] = 'ra: '.(!empty($row2->ra_name) ? $row2->ra_name : (!empty($row2->email) ? $row2->email : _L('Unknown'))); // DO NOT TRANSLATE!
			$out[] = 'ra-status: Information available'; // DO NOT TRANSLATE!

			$tmp = array();
			if (!empty($row2->office)) $tmp[] = $row2->office;
			if (!empty($row2->organization)) $tmp[] = $row2->organization;
			$tmp = implode(', ', $tmp);

			$out[] = 'ra-contact-name: ' . $row2->personal_name.(!empty($tmp) ? " ($tmp)" : ''); // DO NOT TRANSLATE!
			if (!allowRAView($row2, $authTokens)) {
				if (!empty($row2->street) || !empty($row2->zip_town) || !empty($row2->country)) {
					$out[] = 'ra-address: '._L('(redacted)'); // DO NOT TRANSLATE!
				}
				$out[] = 'ra-phone: ' . (!empty($row2->phone) ? _L('(redacted)') : ''); // DO NOT TRANSLATE!
				$out[] = 'ra-mobile: ' . (!empty($row2->mobile) ? _L('(redacted)') : ''); // DO NOT TRANSLATE!
				$out[] = 'ra-fax: ' . (!empty($row2->fax) ? _L('(redacted)') : ''); // DO NOT TRANSLATE!
			} else {
				if (!empty($row2->street))   $out[] = 'ra-address: ' . $row2->street; // DO NOT TRANSLATE!
				if (!empty($row2->zip_town)) $out[] = 'ra-address: ' . $row2->zip_town; // DO NOT TRANSLATE!
				if (!empty($row2->country))  $out[] = 'ra-address: ' . $row2->country; // DO NOT TRANSLATE!
				$out[] = 'ra-phone: ' . $row2->phone; // DO NOT TRANSLATE!
				$out[] = 'ra-mobile: ' . $row2->mobile; // DO NOT TRANSLATE!
				$out[] = 'ra-fax: ' . $row2->fax; // DO NOT TRANSLATE!
			}
			$out[] = 'ra-email: ' . $row->ra_email; // DO NOT TRANSLATE!
			foreach (OIDplus::getPagePlugins() as $plugin) {
				if ($plugin->implementsFeature('1.3.6.1.4.1.37476.2.5.2.3.4')) {
					$plugin->whoisRaAttributes($row->ra_email, $out);
				}
			}

			if ($row2->privacy) { // yes, we use row2->privacy() instead of allowRAView()!
				$out[] = 'ra-attribute: confidential';
			}

			if ($row2->registered) $out[] = 'ra-created: ' . date('Y-m-d H:i:s', strtotime($row2->registered)); // DO NOT TRANSLATE!
			if ($row2->updated)    $out[] = 'ra-updated: ' . date('Y-m-d H:i:s', strtotime($row2->updated)); // DO NOT TRANSLATE!
		} else {
			$out[] = 'ra: '.(!is_null($row) && !empty($row->ra_email) ? $row->ra_email : _L('Unknown')); // DO NOT TRANSLATE!
			if (!is_null($row)) {
				foreach (OIDplus::getPagePlugins() as $plugin) {
					if ($plugin->implementsFeature('1.3.6.1.4.1.37476.2.5.2.3.4')) {
						$plugin->whoisRaAttributes($row->ra_email, $out);
					}
				}
			}
			$out[] = "ra-status: Information unavailable"; // DO NOT TRANSLATE!
		}
	}
}

// Step 2: Format output

if (isset($_REQUEST['format'])) {
	$format = $_REQUEST['format'];
} else if (isset($serverCommands['format'])) {
	$format = $serverCommands['format'];
} else {
	$format = 'txt'; // default
}

if (($format != 'txt') && ($format != 'json') && ($format != 'xml')) {
	$format = 'txt'; // default
}

if ($format == 'txt') {
	header('Content-Type:text/plain; charset=UTF-8');

	$longest_key = 0;
	foreach ($out as $line) {
		$longest_key = max($longest_key, strlen(trim(explode(':',$line,2)[0])));
	}

	ob_start();

	//echo '% ' . str_repeat('*', OIDplus::config()->getValue('webwhois_output_format_max_line_length', 80)-2)."\r\n";

	foreach ($out as $line) {
		if (trim($line) == '') {
			echo "\r\n";
			continue;
		}

		$ary = explode(':', $line, 2);

		$key = trim($ary[0]);

		$value = isset($ary[1]) ? trim($ary[1]) : '';
		$value = mb_wordwrap($value, OIDplus::config()->getValue('webwhois_output_format_max_line_length', 80) - $longest_key - strlen(':') - OIDplus::config()->getValue('webwhois_output_format_spacer', 2), "\r\n");
		$value = str_replace("\r\n", "\r\n$key:".str_repeat(' ', $longest_key-strlen($key)) . str_repeat(' ', OIDplus::config()->getValue('webwhois_output_format_spacer', 2)), $value);

		if (!empty($value)) {
			echo $key.':' . str_repeat(' ', $longest_key-strlen($key)) . str_repeat(' ', OIDplus::config()->getValue('webwhois_output_format_spacer', 2)) . $value . "\r\n";
		}
	}

	//echo '% ' . str_repeat('*', OIDplus::config()->getValue('webwhois_output_format_max_line_length', 80)-2)."\r\n";

	$cont = ob_get_contents();
	ob_end_clean();

	echo $cont;

	if (OIDplus::getPkiStatus()) {
		$signature = '';
		if (@openssl_sign($cont, $signature, OIDplus::config()->getValue('oidplus_private_key'))) {
			$signature = base64_encode($signature);
			$signature = mb_wordwrap($signature, OIDplus::config()->getValue('webwhois_output_format_max_line_length', 80) - strlen('% '), "\r\n", true);
			$signature = "% -----BEGIN RSA SIGNATURE-----\r\n".
			             preg_replace('/^/m', '% ', $signature)."\r\n".
			             "% -----END RSA SIGNATURE-----\r\n";
			echo $signature;
		}
	}
}

if ($format == 'json') {
	$ary = array();

	$current_section = array();
	$ary[] = &$current_section;

	foreach ($out as $line) {
		if ($line == '') {
			unset($current_section);
			$current_section = array();
			$ary[] = &$current_section;
		} else {
			list($key,$val) = explode(':', $line, 2);
			$val = trim($val);
			if (!empty($val)) {
				if (!isset($current_section[$key])) {
					$current_section[$key] = $val;
				} elseif (is_array($current_section[$key])) {
					$current_section[$key][] = $val;
				} else {
					$current_section[$key] = array($current_section[$key], $val);
				}
			}
		}
	}
	$ary = array(
		// https://code.visualstudio.com/docs/languages/json#_mapping-in-the-json
		// Note that this syntax is VS Code-specific and not part of the JSON Schema specification.
		//'$schema' => 'https://oidplus.viathinksoft.com/oidplus/plugins/publicPages/100_whois/whois/json_schema.json',
		'$schema' => OIDplus::webpath(__DIR__,true).'/json_schema.json',

		// we need this NAMED root, otherwise PHP will name the sections "0", "1", "2" if the array is not sequencial (e.g. because "signature" is added)
		'whois' => $ary
	);

	if (OIDplus::getPkiStatus()) {
		$cont = json_encode($ary);
		$signature = '';
		if (@openssl_sign($cont, $signature, OIDplus::config()->getValue('oidplus_private_key'))) {
			$signature = base64_encode($signature);
			$ary['signature'] = array('content' => $cont, 'signature' => $signature);
		}
	}

	// Good JSON schema validator here: https://www.jsonschemavalidator.net
	header('Content-Type:application/json; charset=UTF-8');
	echo json_encode($ary);
}

if ($format == 'xml') {
	$xml = '<whois><section>';
	foreach ($out as $line) {
		if ($line == '') {
			$xml .= '</section><section>';
		} else {
			list($key,$val) = explode(':', $line, 2);
			$val = trim($val);
			if (!empty($val)) {
				$xml .= "<$key>".htmlspecialchars($val)."</$key>";
			}
		}
	}
	$xml .= '</section></whois>';

	$xml = preg_replace('@<section><(.+)>(.+)</section>@ismU', '<\\1Section><\\1>\\2</\\1Section>', $xml);

	if (OIDplus::getPkiStatus()) {
		$cont = $xml;
		$signature = '';
		if (@openssl_sign($cont, $signature, OIDplus::config()->getValue('oidplus_private_key'))) {
			$signature = base64_encode($signature);
			$xml .= "<signature><content>".htmlspecialchars($cont)."</content><signature>".htmlspecialchars($signature)."</signature></signature>";
		}
	}

	// Good XSD validator here: https://www.liquid-technologies.com/online-xsd-validator
	header('Content-Type:application/xml; charset=UTF-8');
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes" ?>';
	echo '<root xmlns="urn:oid:1.3.6.1.4.1.37476.2.5.2.5.1.1"';
	echo '      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"';
	//echo '      xsi:schemaLocation="urn:oid:1.3.6.1.4.1.37476.2.5.2.5.1.1 https://oidplus.viathinksoft.com/oidplus/plugins/publicPages/100_whois/whois/xml_schema.xsd">';
	echo '      xsi:schemaLocation="urn:oid:1.3.6.1.4.1.37476.2.5.2.5.1.1 '.OIDplus::webpath(__DIR__,true).'/xml_schema.xsd">';
	echo $xml;
	echo '</root>';
}

# ---

function show_asn1_appendix($id) {
	if (substr($id,0,4) === 'oid:') {
		$appendix_asn1ids = array();
		$res3 = OIDplus::db()->query("select * from ###asn1id where oid = ?", array($id));
		while ($row3 = $res3->fetch_object()) {
			$appendix_asn1ids[] = $row3->name;
		}

		$appendix = implode(', ', $appendix_asn1ids);
		if (!empty($appendix)) $appendix = " ($appendix)";
	} else {
		$appendix = '';
	}
	return $appendix;
}

function is_root($id) {
	return empty(explode(':',$id,2)[1]);
}

function authTokenAccepted($content, $authTokens) {
	foreach ($authTokens as $token) {
		if (OIDplusPagePublicWhois::genWhoisAuthToken($content) == $token) return true;
	}
	return false;
}

function allowObjectView($obj, $authTokens) {
	// Master auth token
	$authToken = trim(OIDplus::config()->getValue('whois_auth_token'));
	if (empty($authToken)) $authToken = false;
	if ($authToken && in_array($authToken, $authTokens)) return true;

	// Per-OID auth tokens
	$curid = $obj->nodeId();
	while (($res = OIDplus::db()->query("select parent, confidential from ###objects where id = ?", array($curid)))->num_rows() > 0) {
		$row = $res->fetch_array();
		// Example: You have an auth Token for 2.999.1.2.3
		// This allows you to view 2.999.1.2.3 and all of its children,
		// as long as they are not confidential (then you need their auth token).
		// 2, 2.999, 2.999.1 and 2.999.1.2 are visible,
		// (because their existence is now obvious).
		if ($row['confidential'] && !authTokenAccepted($curid, $authTokens)) return false;
		$curid = $row['parent'];
	}

	// Allow
	return true;
}

function allowRAView($row, $authTokens) {
	// Master auth token
	$authToken = trim(OIDplus::config()->getValue('whois_auth_token'));
	if (empty($authToken)) $authToken = false;
	if ($authToken && in_array($authToken, $authTokens)) return true;

	// Per-RA auth tokens
	if ($row->privacy && !authTokenAccepted('ra:'.$row->ra_name, $authTokens)) return false;

	// Allow
	return true;
}
