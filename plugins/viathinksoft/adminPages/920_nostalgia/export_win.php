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

header('Content-Type:text/html; charset=UTF-8');

require_once __DIR__ . '/../../../../includes/oidplus.inc.php';

set_exception_handler(array('OIDplusGui', 'html_exception_handler'));

ob_start(); // allow cookie headers to be sent

OIDplus::init(true);

if (OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_OIDplusPageAdminNostalgia', false)) {
	throw new OIDplusException(_L('This plugin was disabled by the system administrator!'));
}

if (!OIDplus::authUtils()->isAdminLoggedIn()) {
	throw new OIDplusException(_L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')));
}

if (!class_exists('ZipArchive')) {
	throw new OIDplusException(_L('The PHP extension "ZipArchive" needs to be installed to create a ZIP archive with an included database. Otherwise, you can just download the plain program without data.'));
}

$dos_ids = array();
$parent_oids = array();
$i = 0;
$dos_ids[''] = '00000000';
$parent_oids[''] = '';

$dos_ids[''] = str_pad($i++, 8, '0', STR_PAD_LEFT);
$res = OIDplus::db()->query("select * from ###objects where id like 'oid:%' order by ".OIDplus::db()->natOrder('id'));
while ($row = $res->fetch_object()) {
	$oid = substr($row->id, strlen('oid:'));
	$parent_oid = substr($row->parent, strlen('oid:'));
	$dos_ids[$oid] = str_pad($i++, 8, '0', STR_PAD_LEFT);
	if ($parent_oid == '') {
		$parent_oids[$oid] = '';
	} else {
		$parent_oids[$oid] = $parent_oid;
	}
}

$tmp_file = OIDplus::localpath().'userdata/windows_export.zip';

$zip = new ZipArchive();
if ($zip->open($tmp_file, ZipArchive::CREATE)!== true) {
	throw new OIDplusException("cannot open <$tmp_file>");
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
	$cont .= "delegates=".($i-1)."\n";

	if ($oid != '') {
		$res = OIDplus::db()->query("select * from ###asn1id where oid = 'oid:$oid'");
		$asnids = array();
		while ($row = $res->fetch_object()) {
			$asn1 = $row->name;
			$asnids[] = $asn1;
		}
		$asnids = implode(',', $asnids);
		if ($asnids != '') $cont .= "asn1id=$asnids\r\n";

		/*
		$res = OIDplus::db()->query("select * from ###iri where oid = 'oid:$oid'");
		$iris = array();
		while ($row = $res->fetch_object()) {
			$iri = $row->name;
			$iris[] = $iri;
		}
		$iris = implode(',', $iris);
		if ($iris != '') $cont .= "iri=$iris\r\n";
		*/

		$res = OIDplus::db()->query("select * from ###objects where id = 'oid:$oid';");
		$row = $res->fetch_object();

		if ($row->title != '') $cont .= "description=".$row->title."\r\n";

		if ($row->updated != '') $cont .= "updatedate=".explode(' ',$row->updated)[0]."\r\n";
		if ($row->created != '') $cont .= "createdate=".explode(' ',$row->created)[0]."\r\n";

		$desc = $row->description;
		$desc = strip_tags($desc);
		$desc = trim($desc);
		if ($desc != '') {
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
$exe = @file_get_contents($exe_url);
if ($exe == '') {
	throw new OIDplusException(_L("Cannot download the binary file from GitHub (%1)", $exe_url));
}
$zip->addFromString('OIDPLS32.EXE', $exe);

$exe_url = 'https://github.com/danielmarschall/oidplus_win311/raw/master/OIDPLUS.exe';
$exe = @file_get_contents($exe_url);
if ($exe == '') {
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
