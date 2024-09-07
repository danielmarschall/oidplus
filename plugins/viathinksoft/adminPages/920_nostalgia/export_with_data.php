<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2024 Daniel Marschall, ViaThinkSoft
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

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusGui;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusHtmlException;

header('Content-Type:text/html; charset=UTF-8');

for ($sysdir_depth=4; $sysdir_depth<=7; $sysdir_depth++) {
	// The plugin directory can be in plugins (i=4), userdata_pub/plugins (i=5), or userdata_pub/tenant/.../plugins/ (i=7)
	$candidate = __DIR__. str_repeat('/..', $sysdir_depth) . '/includes/oidplus.inc.php';
	if (file_exists($candidate)) {
		require_once $candidate;
		break;
	}
}

set_exception_handler(array(OIDplusGui::class, 'html_exception_handler'));

@set_time_limit(0);

OIDplus::init(true);

if (OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_1.3.6.1.4.1.37476.2.5.2.4.3.920', false)) {
	throw new OIDplusException(_L('This plugin was disabled by the system administrator!'));
}

if (!OIDplus::authUtils()->isAdminLoggedIn()) {
	throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')), null, 401);
}

if (!class_exists('ZipArchive')) {
	throw new OIDplusException(_L('The PHP extension "ZipArchive" needs to be installed to create a ZIP archive with an included database. Otherwise, you can just download the plain program without data.'));
}

$tmp_file = OIDplus::getUserDataDir("cache").'oidplus_nostalgia_'.generateRandomString(10).'.zip';

$zip = new ZipArchive();
if ($zip->open($tmp_file, ZipArchive::CREATE)!== true) {
	throw new OIDplusException(_L("Cannot open file %1", $tmp_file));
}

// ---------------------------- RA

// Now check all RAs
$i = 0;
$res = OIDplus::db()->query("select * from ###ra");
$res->naturalSortByField('email');
$ra_dos_ids = [];
while ($row = $res->fetch_object()) {
	$ra_dos_ids[$row->email] = str_pad(strval($i++), 8, '0', STR_PAD_LEFT);
	$ra_name[$row->email] = $row->ra_name;
	$ra_phone[$row->email] = $row->phone;
	$ra_cdat[$row->email] = fix_datetime_for_output($row->registered);
	$ra_udat[$row->email] = fix_datetime_for_output($row->updated);
}

// https://github.com/danielmarschall/oidplus_win311/blob/master/RAFILE.PAS
// https://github.com/danielmarschall/oidplus_win95/blob/master/RAFILE.PAS

const RA_CMD_VERSION          = 'VERS';
const RA_CMD_NAME             = 'NAME';
const RA_CMD_SELF             = 'SELF';
const RA_CMD_SUPR             = 'SUPR';
const RA_CMD_CHLD             = 'CHLD';
const RA_CMD_EMAIL            = 'MAIL';
const RA_CMD_PHONE            = 'PHON';
const RA_CMD_CREATE_DATE      = 'CDAT';
const RA_CMD_UPDATE_DATE      = 'UDAT';

$idxfile = make_line(RA_CMD_VERSION, '2024');
$idxfile .= make_line(RA_CMD_SELF, '00000000');
$idxfile .= make_line(RA_CMD_SUPR, '00000000');

foreach ($ra_dos_ids as $ra_email => $dos_id) {
	$idxfile .= make_line(RA_CMD_CHLD, $dos_id.$ra_email);

	$cont = make_line(RA_CMD_VERSION, '2024');
	$cont .= make_line(RA_CMD_SELF, $dos_id.$ra_email);
	$cont .= make_line(RA_CMD_SUPR, '00000000');
	if ($ra_name[$ra_email]) $cont .= make_line(RA_CMD_NAME, $ra_name[$ra_email]);
	if ($ra_email) $cont .= make_line(RA_CMD_EMAIL, $ra_email);
	if ($ra_phone[$ra_email]) $cont .= make_line(RA_CMD_PHONE, $ra_phone[$ra_email]);
	$cont .= make_line(RA_CMD_CREATE_DATE, $ra_cdat[$ra_email] ?? '1900-01-01');
	$cont .= make_line(RA_CMD_UPDATE_DATE, $ra_udat[$ra_email] ?? '1900-01-01');

	//echo "****$dos_id.RA_\r\n";
	//echo "$cont\r\n";
	$zip->addFromString("$dos_id.RA_", $cont);
}

//echo "****00000000.RA_\r\n";
//echo "$idxfile\r\n";
$zip->addFromString("00000000.RA_", $idxfile);

// ---------------------------- OIDS

$dos_ids = array();
$parent_oids = array();
$i = 0;

// Root node
$dos_ids[''] = str_pad(strval($i++), 8, '0', STR_PAD_LEFT);
$parent_oids[''] = '';
$iri[''] = array();
$asn1[''] = array();
$title[''] = 'OID Root';
$description[''] = 'Exported by OIDplus 2.0';
$cdat[''] = '1900-01-01';
$udat[''] = '1900-01-01';
$ra[''] = '';
$hide[''] = false;

// Now check all OIDs
$res = OIDplus::db()->query("select * from ###objects where id not like 'oid:1.3.6.1.4.1.37476.1.2.3.1.%' and id like 'oid:%'");
$res->naturalSortByField('id');
while ($row = $res->fetch_object()) {
	$oid = substr($row->id, strlen('oid:'));
	$parent_oid = substr($row->parent, strlen('oid:'));

	$dos_ids[$oid] = str_pad(strval($i++), 8, '0', STR_PAD_LEFT);
	fill_asn1($oid, $asn1);
	fill_iri($oid, $iri);
	$title[$oid] = vts_utf8_decode($row->title);
	$description[$oid] = vts_utf8_decode($row->description);
	$cdat[$oid] = fix_datetime_for_output($row->created);
	$udat[$oid] = fix_datetime_for_output($row->updated);
	$ra[$oid] = $row->ra_email;
	$hide[$oid] = $row->confidential;

	if ((oid_len($oid) > 1) && ($parent_oid == '')) {
		do {
			$real_parent = oid_len($oid) > 1 ? oid_up($oid) : '';
			$parent_oids[$oid] = $real_parent;

			if (isset($dos_ids[$real_parent])) break; // did we already handle this parent node?

			$dos_ids[$real_parent] = str_pad(strval($i++), 8, '0', STR_PAD_LEFT);
			fill_asn1($real_parent, $asn1); // well-known OIDs?
			fill_iri($real_parent, $iri); // well-known OIDs?
			$title[$real_parent] = '';
			$description[$real_parent] = '';
			$cdat[$real_parent] = null;
			$udat[$real_parent] = null;
			$ra[$real_parent] = '';
			$hide[$real_parent] = false;

			$res2 = OIDplus::db()->query("select * from ###objects where id not like 'oid:1.3.6.1.4.1.37476.1.2.3.1.%' and id = ?", ["oid:$real_parent"]);
			while ($row2 = $res2->fetch_object()) {
				$title[$real_parent] = vts_utf8_decode($row2->title);
				$description[$real_parent] = vts_utf8_decode($row2->description);
				$cdat[$real_parent] = fix_datetime_for_output($row2->created);
				$udat[$real_parent] = fix_datetime_for_output($row2->updated);
				$ra[$real_parent] = $row2->ra_email;
				$hide[$real_parent] = $row2->confidential;
			}

			// next
			if ($real_parent == '') break;
			$oid = $real_parent;
		} while (true);
	} else {
		$parent_oids[$oid] = $parent_oid;
	}
}

// https://github.com/danielmarschall/oidplus_dos/blob/master/OIDFILE.PAS
// https://github.com/danielmarschall/oidplus_win311/blob/master/OIDFILE.PAS
// https://github.com/danielmarschall/oidplus_win95/blob/master/OIDFILE.PAS
const OID_CMD_VERSION         = 'VERS';
const OID_CMD_OWN_ID          = 'SELF';
const OID_CMD_PARENT          = 'SUPR';
const OID_CMD_CHILD           = 'CHLD';
const OID_CMD_ASN1_IDENTIFIER = 'ASN1';
const OID_CMD_UNICODE_LABEL   = 'UNIL';
const OID_CMD_DESCRIPTION     = 'DESC';
const OID_CMD_CREATE_DATE     = 'CDAT';
const OID_CMD_UPDATE_DATE     = 'UDAT';
const OID_CMD_DRAFT           = 'DRFT';
const OID_CMD_HIDE            = 'HIDE';
const OID_CMD_RA              = 'RA__';

foreach ($dos_ids as $oid => $dos_id) {
	$cont = make_line(OID_CMD_VERSION, '2022');

	$cont .= make_line(OID_CMD_OWN_ID, $dos_id.$oid);

	$parent_oid = $parent_oids[$oid];
	$parent_id = $dos_ids[$parent_oid];
	$cont .= make_line(OID_CMD_PARENT, $parent_id.$parent_oid);

	foreach ($parent_oids as $child_oid => $parent_oid) {
		if ($child_oid == '') continue;
		if ($parent_oid == $oid) {
			$child_id = $dos_ids[$child_oid];
			$cont .= make_line(OID_CMD_CHILD, $child_id.$child_oid);
		}
	}

	foreach ($asn1[$oid] as $name) {
		$cont .= make_line(OID_CMD_ASN1_IDENTIFIER, $name);
	}

	foreach ($iri[$oid] as $name) {
		$cont .= make_line(OID_CMD_UNICODE_LABEL, $name);
	}

	$desc_ary1 = handleDesc_dos($title[$oid]);
	$desc_ary2 = handleDesc_dos($description[$oid]);
	$desc_ary = array_merge($desc_ary1, $desc_ary2);
	$prev_line = '';
	foreach ($desc_ary as $line_idx => $line) {
		if ($line == $prev_line) continue;
		//if ($line_idx >= 10/*DESCEDIT_LINES*/) break;
		$cont .= make_line(OID_CMD_DESCRIPTION, $line);
		$prev_line = $line;
	}

	$cont .= make_line(OID_CMD_CREATE_DATE, $cdat[$oid] ?? '1900-01-01');

	$cont .= make_line(OID_CMD_UPDATE_DATE, $udat[$oid] ?? '1900-01-01');

	$cont .= make_line(OID_CMD_DRAFT, (str_contains($title[$oid],'(Draft)')||str_contains($title[$oid],'[Draft]')) ? '1' : '0');

	$cont .= make_line(OID_CMD_RA, $ra[$oid]);

	$cont .= make_line(OID_CMD_HIDE, $hide[$oid] ? '1' : '0');

	//echo "****$dos_id.OID\r\n";
	//echo "$cont\r\n";
	$zip->addFromString("$dos_id.OID", $cont);
}

// ---------------------------- EXE

$files_to_download = [
	"https://github.com/danielmarschall/oidplus_nostalgia/raw/master/DOS/OIDPLUS.EXE" => "OIDDBDOS.EXE",
	"https://github.com/danielmarschall/oidplus_nostalgia/raw/master/Win311/OIDPLUS.EXE" => "OIDDB_16.EXE",
	"https://github.com/danielmarschall/oidplus_nostalgia/raw/master/Win95/OIDPLUS.EXE" => "OIDDB_32.EXE",
	"https://github.com/danielmarschall/oidplus_nostalgia/raw/master/Win64/OIDPLUS.EXE" => "OIDDB_64.EXE"
];

foreach ($files_to_download as $exe_url => $new_name) {
	$exe_cont = url_get_contents($exe_url);
	if ($exe_cont === false) {
		throw new OIDplusException(_L("Cannot download the binary file from GitHub (%1)", $exe_url));
	}
	$zip->addFromString($new_name, $exe_cont);
}

// ---------------------------- Done

$zip->close();

if (!headers_sent()) {
	header('Content-Type: application/zip');
	header('Content-Disposition: attachment; filename=oidplus_nostalgia.zip');
	readfile($tmp_file);
}

@unlink($tmp_file);

OIDplus::invoke_shutdown();

# ---

/**
 * @param string $oid
 * @param array $asn1
 * @return void
 * @throws OIDplusException
 */
function fill_asn1(string $oid, array &$asn1): void {
	if (!isset($asn1[$oid])) $asn1[$oid] = array();
	$res = OIDplus::db()->query("select * from ###asn1id where oid = ?", ["oid:$oid"]);
	while ($row = $res->fetch_object()) {
		$asn1[$oid][] = $row->name;
	}
}

/**
 * @param string $oid
 * @param array $iri
 * @return void
 * @throws OIDplusException
 */
function fill_iri(string $oid, array &$iri): void {
	if (!isset($iri[$oid])) $iri[$oid] = array();
	$res = OIDplus::db()->query("select * from ###iri where oid = ?", ["oid:$oid"]);
	while ($row = $res->fetch_object()) {
		$iri[$oid][] = $row->name;
	}
}

/**
 * @param string $desc
 * @return array
 */
function handleDesc_dos(string $desc): array {
	$desc = preg_replace('/<br(\s*)?\/?>/i', "\n", $desc); // br2nl
	$desc = strip_tags($desc);
	$desc = str_replace('&nbsp;', ' ', $desc);
	$desc = html_entity_decode($desc);
	$desc = str_replace("\r", "", $desc);
	$desc = str_replace("\n", "  ", $desc);
	$desc = str_replace("\t", "  ", $desc);
	$desc = trim($desc);
	$desc_ary = explode("\r\n", wordwrap($desc, 75, "\r\n", true));
	if (implode('',$desc_ary) == '') $desc_ary = array();
	return $desc_ary;
}

/**
 * @param string|null $datetime
 * @return string|null
 */
function fix_datetime_for_output(?string $datetime): ?string {
	if ($datetime === "0000-00-00") $datetime = null; // MySQL might use this as default instead of NULL... But SQL Server cannot read this.

	if (is_string($datetime) && (substr($datetime,4,1) !== '-')) {
		// Let's hope PHP can convert the database language specific string to ymd
		$time = @strtotime($datetime);
		if ($time) {
			$date = date('Y-m-d', $time);
			if ($date) {
				$datetime = $date;
			}
		}
	}

	return $datetime ? explode(' ', $datetime)[0] : null; // only date, not time
}

/**
 * @param string $command
 * @param string $data
 * @return string
 */
function make_line(string $command, string $data): string {
	return $command.$data."\r\n";
}
