<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2022 Daniel Marschall, ViaThinkSoft
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

use ViaThinkSoft\OIDplus\OIDplus;
use ViaThinkSoft\OIDplus\OIDplusGui;
use ViaThinkSoft\OIDplus\OIDplusException;
use ViaThinkSoft\OIDplus\OIDplusHtmlException;

header('Content-Type:text/html; charset=UTF-8');

require_once __DIR__ . '/../../../../includes/oidplus.inc.php';

set_exception_handler(array(OIDplusGui::class, 'html_exception_handler'));

@set_time_limit(0);

OIDplus::init(true);

if (OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_ViaThinkSoft\OIDplus\OIDplusPageAdminNostalgia', false)) {
	throw new OIDplusException(_L('This plugin was disabled by the system administrator!'));
}

if (!OIDplus::authUtils()->isAdminLoggedIn()) {
	throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')), null, 401);
}

if (!class_exists('ZipArchive')) {
	throw new OIDplusException(_L('The PHP extension "ZipArchive" needs to be installed to create a ZIP archive with an included database. Otherwise, you can just download the plain program without data.'));
}

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

// Now check all OIDs
$res = OIDplus::db()->query("select * from ###objects where id like 'oid:%'");
$res->naturalSortByField('id');
while ($row = $res->fetch_object()) {
	$oid = substr($row->id, strlen('oid:'));
	$parent_oid = substr($row->parent, strlen('oid:'));

	$dos_ids[$oid] = str_pad(strval($i++), 8, '0', STR_PAD_LEFT);
	fill_asn1($oid, $asn1);
	fill_iri($oid, $iri);
	$title[$oid] = vts_utf8_decode($row->title);
	$description[$oid] = vts_utf8_decode($row->description);

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
			$res2 = OIDplus::db()->query("select * from ###objects where id = 'oid:$real_parent'");
			while ($row2 = $res2->fetch_object()) {
				$title[$real_parent] = vts_utf8_decode($row2->title);
				$description[$real_parent] = vts_utf8_decode($row2->description);
			}

			// next
			if ($real_parent == '') break;
			$oid = $real_parent;
		} while (true);
	} else {
		$parent_oids[$oid] = $parent_oid;
	}
}

$tmp_file = OIDplus::localpath().'userdata/dos_export.zip';

$zip = new ZipArchive();
if ($zip->open($tmp_file, ZipArchive::CREATE)!== true) {
	throw new OIDplusException(_L("Cannot open file %1", $tmp_file));
}

/**
 * @param string $command
 * @param string $data
 * @return string
 */
function make_line(string $command, string $data): string {
	return $command.$data."\r\n";
}

// https://github.com/danielmarschall/oidplus_dos/blob/master/OIDFILE.PAS
const CMD_VERSION         = 'VERS';
const CMD_OWN_ID          = 'SELF';
const CMD_PARENT          = 'SUPR';
const CMD_CHILD           = 'CHLD';
const CMD_ASN1_IDENTIFIER = 'ASN1';
const CMD_UNICODE_LABEL   = 'UNIL';
const CMD_DESCRIPTION     = 'DESC';

foreach ($dos_ids as $oid => $dos_id) {
	$cont = make_line(CMD_VERSION, '2022');

	$cont .= make_line(CMD_OWN_ID, $dos_id.$oid);

	$parent_oid = $parent_oids[$oid];
	$parent_id = $dos_ids[$parent_oid];
	$cont .= make_line(CMD_PARENT, $parent_id.$parent_oid);

	foreach ($parent_oids as $child_oid => $parent_oid) {
		if ($child_oid == '') continue;
		if ($parent_oid == $oid) {
			$child_id = $dos_ids[$child_oid];
			$cont .= make_line(CMD_CHILD, $child_id.$child_oid);
		}
	}

	foreach ($asn1[$oid] as $name) {
		$cont .= make_line(CMD_ASN1_IDENTIFIER, $name);
	}

	foreach ($iri[$oid] as $name) {
		$cont .= make_line(CMD_UNICODE_LABEL, $name);
	}

	$desc_ary1 = handleDesc_dos($title[$oid]);
	$desc_ary2 = handleDesc_dos($description[$oid]);
	$desc_ary = array_merge($desc_ary1, $desc_ary2);
	$prev_line = '';
	foreach ($desc_ary as $line_idx => $line) {
		if ($line == $prev_line) continue;
		if ($line_idx >= 10/*DESCEDIT_LINES*/) break;
		$cont .= make_line(CMD_DESCRIPTION, $line);
		$prev_line = $line;
	}

	//echo "****$dos_id.OID\r\n";
	//echo "$cont\r\n";

	$zip->addFromString("$dos_id.OID", $cont);
}

$exe_url = 'https://github.com/danielmarschall/oidplus_dos/raw/master/OIDPLUS.EXE';
$exe = url_get_contents($exe_url);
if ($exe === false) {
	throw new OIDplusException(_L("Cannot download the binary file from GitHub (%1)", $exe_url));
}
$zip->addFromString('OIDPLUS.EXE', $exe);

$zip->close();

if (!headers_sent()) {
	header('Content-Type: application/zip');
	header('Content-Disposition: attachment; filename=oidplus_dos.zip');
	readfile($tmp_file);
}

unlink($tmp_file);

OIDplus::invoke_shutdown();

# ---

/**
 * @param string $oid
 * @param array $asn1
 * @return void
 * @throws OIDplusException
 */
function fill_asn1(string $oid, array &$asn1) {
	if (!isset($asn1[$oid])) $asn1[$oid] = array();
	$res = OIDplus::db()->query("select * from ###asn1id where oid = 'oid:$oid'");
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
function fill_iri(string $oid, array &$iri) {
	if (!isset($iri[$oid])) $iri[$oid] = array();
	$res = OIDplus::db()->query("select * from ###iri where oid = 'oid:$oid'");
	while ($row = $res->fetch_object()) {
		$iri[$oid][] = $row->name;
	}
}

/**
 * @param string $desc
 * @return array
 */
function handleDesc_dos(string $desc): array {
	$desc = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $desc); // br2nl
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
