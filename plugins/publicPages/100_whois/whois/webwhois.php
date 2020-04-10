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

define('OUTPUT_FORMAT_SPACER', 2);
define('OUTPUT_FORMAT_MAX_LINE_LENGTH', 80);

# ---

require_once __DIR__ . '/../../../../includes/oidplus.inc.php';

OIDplus::init(true);

originHeaders();

// Step 0: Get request parameter

if (php_sapi_name() == 'cli') {
	if ($argc != 2) {
		echo "Syntax: $argv[0] <query>\n";
		exit(2);
	}
	$query = $argv[1];
} else {
	if (!isset($_REQUEST['query'])) {
		http_response_code(400);
		die("<h1>Error</h1><p>Argument 'query' is missing<p>");
	}
	$query = $_REQUEST['query'];
}

$authTokens = explode('$', $query);
$query = array_shift($authTokens);

$authToken = trim(OIDplus::config()->getValue('whois_auth_token'));
if (empty($authToken)) $authToken = false;

$show_confidential = $authToken && in_array($authToken, $authTokens);

$query = str_replace('oid:.', 'oid:', $query); // allow leading dot

// Step 1: Collect data

$out = array();

$out[] = "query: $query";

$distance = null;
$found = null;

try {
	$obj = OIDplusObject::findFitting($query);
	if (!$obj) $obj = OIDplusObject::parse($query); // in case we didn't find anything fitting, we take it as it is and later use getParent() to find something else
} catch (Exception $e) {
	$obj = null;
}

if (!$obj) {
	$found = false;
} else {
	$query = $obj->nodeId(); // this may sanitize/canonize identifiers
	$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where id = ?", array($obj->nodeId()));
	if ($res->num_rows() > 0) {
		$found = true;
		$distance = 0;
	} else {
		$found = false;
		$objParent = OIDplusObject::parse($query)->getParent();
		if ($objParent) {
			$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where id = ?", array($objParent->nodeId()));
			$distance = $objParent->distance($query);
			assert($res->num_rows() > 0);

			$query = $objParent->nodeId();
			$obj = $objParent;
		}
	}
}

$continue = null;
if (!$found) {
	if (!is_null($distance)) {
		$out[] = "result: Not found; superior object found";
		$out[] = "distance: $distance";
		$continue = true;
	} else {
		$out[] = "result: Not found";
		$continue = false;
	}
} else {
	$out[] = "result: Found";
	$continue = true;
}

if ($continue) {
	$out[] = "";
	$out[] = "object: $query";
	if ($obj->isConfidential() && !$show_confidential) {
		$out[] = "status: Confidential";
	} else {
		$out[] = "status: Information available";

		$row = $res->fetch_object();
		$obj = OIDplusObject::parse($row->id);

		if (!empty($row->parent) && (!is_root($row->parent))) {
			$out[] = 'parent: ' . $row->parent . show_asn1_appendix($row->parent);
		}
		$out[] = 'name: ' . $row->title;

		$cont = $row->description;
		$cont = preg_replace('@<a[^>]+href\s*=\s*["\']([^\'"]+)["\'][^>]*>(.+)<\s*/\s*a\s*>@ismU', '\2 (\1)', $cont);
		$out[] = 'description: ' . trim(html_entity_decode(strip_tags($cont)));

		if (substr($query,0,4) === 'oid:') {
			$res2 = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."asn1id where oid = ?", array($row->id));
			while ($row2 = $res2->fetch_object()) {
				$out[] = 'identifier: ' . $row2->name;
			}

			$res2 = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."asn1id where standardized = ? and oid = ?", array(true, $row->id));
			while ($row2 = $res2->fetch_object()) {
				$out[] = 'standardized-id: ' . $row2->name;
			}

			$res2 = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."iri where oid = ?", array($row->id));
			while ($row2 = $res2->fetch_object()) {
				$out[] = 'unicode-label: ' . $row2->name;
			}

			$res2 = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."iri where longarc = ? and oid = ?", array(true, $row->id));
			while ($row2 = $res2->fetch_object()) {
				$out[] = 'long-arc: ' . $row2->name;
			}
		}

		$out[] = 'created: ' . $row->created;
		$out[] = 'updated: ' . $row->updated;

		$res2 = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where parent = ? order by ".OIDplus::db()->natOrder('id'), array($row->id));
		if ($res2->num_rows() == 0) {
			// $out[] = 'subordinate: (none)';
		}
		while ($row2 = $res2->fetch_object()) {
			$out[] = 'subordinate: ' . $row2->id . show_asn1_appendix($row2->id);
		}

		$out[] = '';

		$res2 = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = ?", array($row->ra_email));
		if ($row2 = $res2->fetch_object()) {
			$out[] = 'ra: '.(!empty($row2->ra_name) ? $row2->ra_name : $row2->email);
			$out[] = 'ra-status: Information available';
			$out[] = 'ra-name: ' . $row2->ra_name;
			$out[] = 'ra-email: ' . $row->ra_email;
			$out[] = 'ra-personal-name: ' . $row2->personal_name;
			$out[] = 'ra-organization: ' . $row2->organization;
			$out[] = 'ra-office: ' . $row2->office;
			if ($row2->privacy && !$show_confidential) {
				$out[] = 'ra-street: ' . (!empty($row2->street) ? '(redacted)' : '');
				$out[] = 'ra-town: ' . (!empty($row2->zip_town) ? '(redacted)' : '');
				$out[] = 'ra-country: ' . (!empty($row2->country) ? '(redacted)' : '');
				$out[] = 'ra-phone: ' . (!empty($row2->phone) ? '(redacted)' : '');
				$out[] = 'ra-mobile: ' . (!empty($row2->mobile) ? '(redacted)' : '');
				$out[] = 'ra-fax: ' . (!empty($row2->fax) ? '(redacted)' : '');
			} else {
				$out[] = 'ra-street: ' . $row2->street;
				$out[] = 'ra-zip_town: ' . $row2->zip_town;
				$out[] = 'ra-country: ' . $row2->country;
				$out[] = 'ra-phone: ' . $row2->phone;
				$out[] = 'ra-mobile: ' . $row2->mobile;
				$out[] = 'ra-fax: ' . $row2->fax;
			}
			$out[] = 'ra-created: ' . $row2->registered;
			$out[] = 'ra-updated: ' . $row2->updated;
		} else {
			$out[] = 'ra: '.$row->ra_email;
			$out[] = "ra-status: Information unavailable";
		}
	}
}

// Step 2: Format output

ob_start();

$format = isset($_REQUEST['format']) ? $_REQUEST['format'] : 'txt';

if ($format == 'txt') {
	header('Content-Type:text/plain; charset=UTF-8');

	$longest_key = 0;
	foreach ($out as $line) {
		$longest_key = max($longest_key, strlen(trim(explode(':',$line,2)[0])));
	}

	echo '% ' . str_repeat('*', OUTPUT_FORMAT_MAX_LINE_LENGTH-2)."\n";

	foreach ($out as $line) {
		if (trim($line) == '') {
			echo "\n";
			continue;
		}

		$ary = explode(':', $line, 2);

		$key = trim($ary[0]);

		$value = trim($ary[1]);
		$value = wordwrap($value, OUTPUT_FORMAT_MAX_LINE_LENGTH - $longest_key - strlen(':') - OUTPUT_FORMAT_SPACER);
		$value = str_replace("\n", "\n$key:".str_repeat(' ', $longest_key-strlen($key)) . str_repeat(' ', OUTPUT_FORMAT_SPACER), $value);

		echo $key.':' . str_repeat(' ', $longest_key-strlen($key)) . str_repeat(' ', OUTPUT_FORMAT_SPACER) . (!empty($value) ? $value : '.') . "\n";
	}

	echo '% ' . str_repeat('*', OUTPUT_FORMAT_MAX_LINE_LENGTH-2)."\n";

	$cont = ob_get_contents();
	ob_end_clean();

	echo $cont;

	if (OIDplus::getPkiStatus(true)) {
		$signature = '';
		if (@openssl_sign($cont, $signature, OIDplus::config()->getValue('oidplus_private_key'))) {
			$signature = base64_encode($signature);
			$signature = wordwrap($signature, 80, "\n", true);

			$signature = "-----BEGIN RSA SIGNATURE-----\n".
		                     "$signature\n".
			             "-----END RSA SIGNATURE-----\n";
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
			if (!isset($current_section[$key])) {
				$current_section[$key] = $val;
			} elseif (is_array($current_section[$key])) {
				$current_section[$key][] = $val;
			} else {
				$current_section[$key] = array($current_section[$key], $val);
			}
		}
	}
	$ary = array('whois' => $ary); // we need this named root, otherwise PHP will name the sections "0", "1", "2" if the array is not sequencial (e.g. because "signature" is added)

	if (OIDplus::getPkiStatus(true)) {
		$cont = json_encode($ary);
		$signature = '';
		if (@openssl_sign($cont, $signature, OIDplus::config()->getValue('oidplus_private_key'))) {
			$signature = base64_encode($signature);
			$ary['signature'] = array('content' => $cont, 'signature' => $signature);
		}
	}
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
			$xml .= "<$key>".htmlspecialchars($val)."</$key>";
		}
	}
	$xml .= '</section></whois>';

	if (OIDplus::getPkiStatus(true)) {
		$cont = $xml;
		$signature = '';
		if (@openssl_sign($cont, $signature, OIDplus::config()->getValue('oidplus_private_key'))) {
			$signature = base64_encode($signature);
			$xml .= "<signature><content>".htmlspecialchars($cont)."</content><signature>".htmlspecialchars($signature)."</signature></signature>";
		}
	}

	header('Content-Type:application/xml; charset=UTF-8');
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes" ?>';
	echo '<root>'.$xml.'</root>';
}

# ---

function show_asn1_appendix($id) {
	if (substr($id,0,4) === 'oid:') {
		$appendix_asn1ids = array();
		$res3 = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."asn1id where oid = ?", array($id));
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
