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

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusGui;
use ViaThinkSoft\OIDplus\Core\OIDplusHtmlException;
use ViaThinkSoft\OIDplus\Plugins\viathinksoft\adminPages\n400_oidinfo_export\OIDplusPageAdminOIDInfoExport;

require_once __DIR__ . '/../../../../includes/oidplus.inc.php';

set_exception_handler(array(OIDplusGui::class, 'html_exception_handler'));

header('Content-Type:text/html; charset=UTF-8');

OIDplus::init(true);

if (OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_ViaThinkSoft\OIDplus\Plugins\viathinksoft\adminPages\n400_oidinfo_export\OIDplusPageAdminOIDInfoExport', false)) {
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

list($out_content, $out_type) = OIDplusPageAdminOIDInfoExport::outputXML(isset($_REQUEST['online']) && $_REQUEST['online']);

OIDplus::invoke_shutdown();

if ($out_type) header('Content-Type:'.$out_type);
echo $out_content;
