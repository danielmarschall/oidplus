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

require_once __DIR__ . '/../includes/oidplus.inc.php';

OIDplus::init(true);

OIDplus::db()->set_charset("UTF8");
OIDplus::db()->query("SET NAMES 'utf8'");

header('Content-Type:text/plain; charset=UTF-8');

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
	        echo "Argument 'query' is missing\n";
		die();
	}
	$query = $_REQUEST['query'];
}

$query = str_replace('oid:.', 'oid:', $query); // backwards compatibility: allow leading dot

// Step 1: Collect data

$out = array();

$out[] = "object: $query";

$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where id = '".OIDplus::db()->real_escape_string($query)."'");
if ($row = OIDplus::db()->fetch_object($res)) {
	$obj = OIDplusObject::parse($row->id);
	if ($obj->isConfidential()) {
		$out[] = 'status: Found, confidential';
	} else {
		$out[] = "status: Found";
		if (!empty($row->parent) && (!is_root($row->parent))) {
			$out[] = 'parent: ' . $row->parent . show_asn1_appendix($row->parent);
		}
		$out[] = 'title: ' . $row->title;
		$out[] = 'description: ' . trim(html_entity_decode(strip_tags($row->description)));

		if (substr($query,0,4) === 'oid:') {
			$res2 = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."asn1id where oid = '".OIDplus::db()->real_escape_string($row->id)."'");
			while ($row2 = OIDplus::db()->fetch_object($res2)) {
				$out[] = 'identifier: ' . $row2->name;
			}

			$res2 = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."iri where oid = '".OIDplus::db()->real_escape_string($row->id)."'");
			while ($row2 = OIDplus::db()->fetch_object($res2)) {
				$out[] = 'unicode-label: ' . $row2->name;
			}
		}

		$out[] = 'created: ' . $row->created;
		$out[] = 'updated: ' . $row->updated;

		$out[] = '';

		$res2 = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where parent = '".OIDplus::db()->real_escape_string($row->id)."'");
		if (OIDplus::db()->num_rows($res2) == 0) {
			$out[] = 'subordinate: (none)';
		}
		while ($row2 = OIDplus::db()->fetch_object($res2)) {
			$out[] = 'subordinate: ' . $row2->id . show_asn1_appendix($row2->id);
		}

		$out[] = '';
		$out[] = 'ra-email: ' . $row->ra_email;
		$res2 = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = '".OIDplus::db()->real_escape_string($row->ra_email)."'");
		if ($row2 = OIDplus::db()->fetch_object($res2)) {
			$out[] = 'ra-status: Registered';
			$out[] = 'ra-name: ' . $row2->ra_name;
			$out[] = 'ra-personal_name: ' . $row2->personal_name;
			$out[] = 'ra-organization: ' . $row2->organization;
			$out[] = 'ra-office: ' . $row2->office;
			if ($row2->privacy) {
				$out[] = 'ra-street: ' . (!empty($row2->street) ? '(redacted)' : '');
				$out[] = 'ra-zip_town: ' . (!empty($row2->zip_town) ? '(redacted)' : '');
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
			$out[] = 'ra-registered: ' . $row2->registered;
			$out[] = 'ra-updated: ' . $row2->updated;
		} else {
			$out[] = "ra-status: Not registered";
		}
	}
} else {
	$out[] = "status: Not found";
}

// Step 2: Format output

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
	$value = wordwrap($value, OUTPUT_FORMAT_MAX_LINE_LENGTH - (OUTPUT_FORMAT_SPACER+1) - $longest_key);
	$value = str_replace("\n", "\n".str_repeat(' ', $longest_key+OUTPUT_FORMAT_SPACER+1), $value);

	echo $key.':' . str_repeat(' ', $longest_key-strlen($key)) . str_repeat(' ', OUTPUT_FORMAT_SPACER) . (!empty($value) ? $value : '.') . "\n";
}

echo '% ' . str_repeat('*', OUTPUT_FORMAT_MAX_LINE_LENGTH-2)."\n";

# ---

function show_asn1_appendix($id) {
	if (substr($id,0,4) === 'oid:') {
		$appendix_asn1ids = array();
		$res3 = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."asn1id where oid = '".OIDplus::db()->real_escape_string($id)."'");
		while ($row3 = OIDplus::db()->fetch_object($res3)) {
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
