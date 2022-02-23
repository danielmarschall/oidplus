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

OIDplus::init(true);

if (OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_OIDplusPageAdminOIDInfoExport', false)) {
	throw new OIDplusException(_L('This plugin was disabled by the system administrator!'));
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

$tmp_file = OIDplus::localpath().'userdata/dos_export.zip';

$zip = new ZipArchive();
if ($zip->open($tmp_file, ZipArchive::CREATE)!== true) {
	throw new OIDplusException("cannot open <$tmp_file>");
}

foreach ($dos_ids as $oid => $dos_id) {
	$cont = '';

	$cont .= "VERS2022\r\n";

	$cont .= "SELF$dos_id$oid\r\n";

	$parent_oid = $parent_oids[$oid];
	$parent_id = $dos_ids[$parent_oid];
	$cont .= "SUPR$parent_id$parent_oid\r\n";

	foreach ($parent_oids as $child_oid => $parent_oid) {
		if ($child_oid == '') continue;
		if ($parent_oid == $oid) {
			$child_id = $dos_ids[$child_oid];
			$cont .= "CHLD$child_id$child_oid\r\n";
		}
	}

	$res = OIDplus::db()->query("select * from ###asn1id where oid = 'oid:$oid'");
	while ($row = $res->fetch_object()) {
		$asn1 = $row->name;
		$cont .= "ASN1$asn1\r\n";
	}

	$res = OIDplus::db()->query("select * from ###iri where oid = 'oid:$oid'");
	while ($row = $res->fetch_object()) {
		$iri = $row->name;
		$cont .= "UNIL$iri\r\n";
	}

	if ($oid == '') {
		// TODO: Split in single parent OIDs
		$cont .= "DESCHere, you can find the root OIDs.\r\n";
	} else {
		$res = OIDplus::db()->query("select * from ###objects where id = 'oid:$oid';");
		$row = $res->fetch_object();
		$desc = trim(trim(strip_tags($row->description)));
		$desc = str_replace("\r", "", $desc);
		$desc = str_replace("\n", "  ", $desc);
		$desc_ary1 = explode("\r\n", wordwrap($desc, 80/*TREEVIEW_WIDTH*/, "\r\n", true));
		$desc_ary2 = explode("\r\n", wordwrap($row->title, 80/*TREEVIEW_WIDTH*/, "\r\n", true));
		if (implode('',$desc_ary1) == '') $desc_ary1 = array();
		if (implode('',$desc_ary2) == '') $desc_ary2 = array();
		$desc_ary = array_merge($desc_ary1, $desc_ary2);
		foreach ($desc_ary as $line_idx => $line) {
			if ($line_idx >= 10/*DESCEDIT_LINES*/) break;
			$cont .= "DESC$line\r\n";
		}
	}

	//echo "****$dos_id.OID\r\n";
	//echo "$cont\r\n";

	$zip->addFromString("$dos_id.OID", $cont);
}

$exe_url = 'https://github.com/danielmarschall/oidplus_dos/raw/master/OIDPLUS.EXE';
$exe = @file_get_contents($exe_url);
if ($exe == '') {
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
