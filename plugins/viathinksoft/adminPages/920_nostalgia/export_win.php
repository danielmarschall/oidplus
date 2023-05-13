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
$created[''] = '';
$updated[''] = '';

// Now check all OIDs
$res = OIDplus::db()->query("select * from ###objects where id like 'oid:%'");
$res->naturalSortByField('id');
while ($row = $res->fetch_object()) {
	$oid = substr($row->id, strlen('oid:'));
	$parent_oid = substr($row->parent, strlen('oid:'));

	$dos_ids[$oid] = str_pad(strval($i++), 8, '0', STR_PAD_LEFT);
	fill_asn1($oid, $asn1);
	//fill_iri($oid, $iri);
	$title[$oid] = vts_utf8_decode($row->title);
	$description[$oid] = vts_utf8_decode($row->description);
	$created[$oid] = $row->created;
	$updated[$oid] = $row->updated;

	if ((oid_len($oid) > 1) && ($parent_oid == '')) {
		do {
			$real_parent = oid_len($oid) > 1 ? oid_up($oid) : '';
			$parent_oids[$oid] = $real_parent;

			if (isset($dos_ids[$real_parent])) break; // did we already handle this parent node?

			$dos_ids[$real_parent] = str_pad(strval($i++), 8, '0', STR_PAD_LEFT);
			fill_asn1($real_parent, $asn1); // well-known OIDs?
			//fill_iri($real_parent, $iri); // well-known OIDs?
			$title[$real_parent] = '';
			$description[$real_parent] = '';
			$created[$real_parent] = '';
			$updated[$real_parent] = '';
			$res2 = OIDplus::db()->query("select * from ###objects where id = 'oid:$real_parent'");
			while ($row2 = $res2->fetch_object()) {
				$title[$real_parent] = vts_utf8_decode($row2->title);
				$description[$real_parent] = vts_utf8_decode($row2->description);
				$created[$real_parent] = $row2->created;
				$updated[$real_parent] = $row2->updated;
			}

			// next
			if ($real_parent == '') break;
			$oid = $real_parent;
		} while (true);
	} else {
		$parent_oids[$oid] = $parent_oid;
	}
}

$tmp_file = OIDplus::localpath().'userdata/windows_export.zip';

$zip = new ZipArchive();
if ($zip->open($tmp_file, ZipArchive::CREATE)!== true) {
	throw new OIDplusException(_L("Cannot open file %1", $tmp_file));
}

$cont = '';

foreach ($dos_ids as $oid => $dos_id) {
	$cont .= "[OID:$oid]\r\n";

	$i = 1;
	foreach ($parent_oids as $child_oid => $parent_oid) {
		if ($child_oid == '') continue;
		if ($parent_oid == $oid) {
			$cont .= "delegate$i=OID:$child_oid\r\n";
			$i++;
		}
	}
	$cont .= "delegates=".($i-1)."\r\n";

	if ($oid != '') {
		$asnids = array();
		foreach ($asn1[$oid] as $name) {
			$asnids[] = $name;
		}
		$asnids = implode(',', $asnids);
		if ($asnids != '') $cont .= "asn1id=$asnids\r\n";

		/*
		$iris = array();
		foreach ($iri[$oid] as $name) {
			$iris[] = $name;
		}
		$iris = implode(',', $iris);
		if ($iris != '') $cont .= "iri=$iris\r\n";
		*/

		if ($title[$oid] != '') $cont .= "description=".$title[$oid]."\r\n";

		if ($updated[$oid] != '') $cont .= "updatedate=".explode(' ',$updated[$oid])[0]."\r\n";
		if ($created[$oid] != '') $cont .= "createdate=".explode(' ',$created[$oid])[0]."\r\n";

		$desc = handleDesc_win($description[$oid]);
		if (trim($desc) != '') {
			$cont .= "information=$dos_id.TXT\r\n";
			$zip->addFromString("DB//$dos_id.TXT", $desc);
		}
	}
}

//echo '<pre>'.$cont.'</pre>';
//die();

$settings = array();
$settings[] = '[SETTINGS]';
$settings[] = 'DATA=DB\\';
$zip->addFromString("OIDPLUS.INI", implode("\r\n",$settings)."\r\n");

$zip->addFromString('DB//OID.INI', $cont);

$exe_url = 'https://github.com/danielmarschall/oidplus_win95/raw/master/OIDPLUS.exe';
$exe = url_get_contents($exe_url);
if ($exe === false) {
	throw new OIDplusException(_L("Cannot download the binary file from GitHub (%1)", $exe_url));
}
$zip->addFromString('OIDPLS32.EXE', $exe);

$exe_url = 'https://github.com/danielmarschall/oidplus_win311/raw/master/OIDPLUS.exe';
$exe = url_get_contents($exe_url);
if ($exe === false) {
	throw new OIDplusException(_L("Cannot download the binary file from GitHub (%1)", $exe_url));
}
$zip->addFromString('OIDPLS16.EXE', $exe);

$zip->close();

if (!headers_sent()) {
	header('Content-Type: application/zip');
	header('Content-Disposition: attachment; filename=oidplus_windows.zip');
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

/*
function fill_iri($oid, &$iri) {
	if (!isset($iri[$oid])) $iri[$oid] = array();
	$res = OIDplus::db()->query("select * from ###iri where oid = 'oid:$oid'");
	while ($row = $res->fetch_object()) {
		$iri[$oid][] = $row->name;
	}
}
*/

/**
 * @param string $desc
 * @return string
 */
function handleDesc_win(string $desc): string {
	$desc = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $desc); // br2nl
	$desc = strip_tags($desc);
	$desc = str_replace('&nbsp;', ' ', $desc);
	$desc = html_entity_decode($desc);
	$desc = str_replace("\r", "", $desc);
	$desc = str_replace("\n", "\r\n", $desc);
	return trim($desc)."\r\n";
}
