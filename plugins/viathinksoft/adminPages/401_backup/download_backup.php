<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2023 Daniel Marschall, ViaThinkSoft
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
use ViaThinkSoft\OIDplus\OIDplusException;
use ViaThinkSoft\OIDplus\OIDplusGui;
use ViaThinkSoft\OIDplus\OIDplusHtmlException;
use ViaThinkSoft\OIDplus\OIDplusPageAdminDatabaseBackup;

require_once __DIR__ . '/../../../../includes/oidplus.inc.php';

set_exception_handler(array(OIDplusGui::class, 'html_exception_handler'));

@set_time_limit(0);

header('Content-Type:text/html; charset=UTF-8');

ob_start();

OIDplus::init(true);

if (OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_ViaThinkSoft\OIDplus\OIDplusPageAdminDatabaseBackup', false)) {
	throw new OIDplusException(_L('This plugin was disabled by the system administrator!'));
}

# ---

if (!OIDplus::authUtils()->isAdminLoggedIn()) {
	if (PHP_SAPI == 'cli') {
		// echo "You need to log in as administrator.\n";
		// die();
	} else {
		throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as administrator.','href="'.OIDplus::webpath(null,OIDplus::PATH_RELATIVE).'?goto=oidplus%3Alogin%24admin"'), null, 401);
	}
}

$exp_objects = oidplus_is_true($_POST['database_backup_export_objects'] ?? false);
$exp_ra = oidplus_is_true($_POST['database_backup_export_ra'] ?? false);
$exp_config = oidplus_is_true($_POST['database_backup_export_config'] ?? false);
$exp_log = oidplus_is_true($_POST['database_backup_export_log'] ?? false);
$exp_pki = oidplus_is_true($_POST['database_backup_export_pki'] ?? false);
$encrypt = oidplus_is_true($_POST['database_backup_export_encrypt'] ?? false);
$password1 = $_POST['database_backup_export_password1'] ?? "";
$password2 = $_POST['database_backup_export_password2'] ?? "";

if ($encrypt) {
	if ($password1 !== $password2) {
		throw new OIDplusException(_L("Passwords do not match"));
	}
}

$encoded_data = OIDplusPageAdminDatabaseBackup::createBackup(false, $exp_objects, $exp_ra, $exp_config, $exp_log, $exp_pki);

if ($encrypt) {
	$encoded_data = json_encode(json_decode($encoded_data),JSON_UNESCAPED_SLASHES); // remove pretty-print to save space
	if (function_exists('gzdeflate')) {
		$encoded_data_gz = @gzdeflate($encoded_data, 9);
		if ($encoded_data_gz) $encoded_data = 'GZIP'.$encoded_data_gz;
	}

	$encoded_data = chunk_split(base64_encode(encrypt_str($encoded_data, $password1)));
	$encoded_data =
		"-----BEGIN OIDPLUS ENCRYPTED DATABASE BACKUP-----\r\n".
		htmlentities($encoded_data).
		"-----END OIDPLUS ENCRYPTED DATABASE BACKUP-----\r\n";
}

$title = OIDplus::config()->getValue('system_title', 'oidplus');

$sysid = OIDplus::getSystemId();

OIDplus::invoke_shutdown();

$cont = ob_get_contents();
ob_end_clean();

if ($cont) {
	// There was some kind of unexpected output. We must not download the file in this case
	die($cont);
}

$filename = preg_replace('@[^a-z0-9]@', '-', strtolower($title)).($sysid ? '-'.$sysid : '').'-backup-' . date('Y-m-d-H-i-s');
if ($encrypt) {
	$filename .= '-encrypted.bak';
	header('Content-Type: application/octet-stream');
} else {
	$filename .= '.json';
	header('Content-Type: application/json');
}

if (function_exists('gzencode')) {
	$tmp = @gzencode($encoded_data, 9);
	if ($tmp) {
		$encoded_data = $tmp;
		$filename .= '.gz';
		header('Content-Type: application/x-gzip');
	}
}

header('Content-Disposition: attachment; filename='.$filename);
header('Content-Length: '.strlen($encoded_data));
echo $encoded_data;
