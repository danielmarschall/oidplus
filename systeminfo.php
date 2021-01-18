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

require_once __DIR__ . '/includes/oidplus.inc.php';

header('Content-Type:application/json; charset=UTF-8');

OIDplus::init(false);

# ---

$out = array();

$sysid_oid = OIDplus::getSystemId(true);
if (!$sysid_oid) $sysid_oid = 'unknown'; // do not translate
$out['SystemID'] = $sysid_oid;

$pubKey = OIDplus::config()->getValue('oidplus_public_key');
if (!$pubKey) $pubKey = 'unknown'; // do not translate
$out['PublicKey'] = $pubKey;

// Commented out due to security/privacy reasons
/*
$sys_url = OIDplus::webpath();
$out['SystemURL'] = $sys_url;

$sys_ver = OIDplus::getVersion();
if (!$sys_ver) $sys_ver = 'unknown'; // do not translate
$out['SystemVersion'] = $sys_ver;

$sys_install_type = OIDplus::getInstallType();
$out['SystemInstallType'] = $sys_install_type;

$sys_title = OIDplus::config()->getValue('system_title');
$out['SystemTitle'] = $sys_title;

$admin_email = OIDplus::config()->getValue('admin_email');
$out['AdminEMail'] = $admin_email;
*/

echo json_encode($out);

