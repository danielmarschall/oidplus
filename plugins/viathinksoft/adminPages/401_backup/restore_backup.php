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

$exp_objects = $_POST['database_backup_import_objects'] ?? false;
$exp_ra = $_POST['database_backup_import_ra'] ?? false;
$exp_config = $_POST['database_backup_import_config'] ?? false;
$exp_log = $_POST['database_backup_import_log'] ?? false;
$exp_pki = $_POST['database_backup_import_pki'] ?? false;
$password = $_POST['database_backup_import_password'] ?? "";

if (!isset($_FILES['userfile'])) {
	throw new OIDplusException(_L('Please choose a file.'));
}

$encoded_data = file_get_contents($_FILES['userfile']['tmp_name']);

if (preg_match('@-----BEGIN OIDPLUS ENCRYPTED DATABASE BACKUP-----(.+)-----END OIDPLUS ENCRYPTED DATABASE BACKUP-----@ismU', $encoded_data, $m)) {
	$encoded_data = $m[1];
	$encoded_data = base64_decode($encoded_data);
	$encoded_data = decrypt_str($encoded_data, $password);
	if (substr($encoded_data,0,4) === 'GZIP') {
		if (!function_exists('gzinflate')) {
			throw new OIDplusException(_L("Cannot decompress backup file because PHP ZLib extension is not installed"));
		}
		$encoded_data = gzinflate(substr($encoded_data,4));
	}
}

OIDplusPageAdminDatabaseBackup::restoreBackup(true, $encoded_data, $exp_objects, $exp_ra, $exp_config, $exp_log, $exp_pki);

OIDplus::invoke_shutdown();
