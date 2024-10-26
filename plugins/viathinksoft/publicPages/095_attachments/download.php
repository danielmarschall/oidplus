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
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusGui;
use ViaThinkSoft\OIDplus\Plugins\PublicPages\Attachments\OIDplusPagePublicAttachments;
use ViaThinkSoft\OIDplus\Plugins\PublicPages\Attachments\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_11;

for ($sysdir_depth=4; $sysdir_depth<=7; $sysdir_depth++) {
	// The plugin directory can be in plugins (i=4), userdata_pub/plugins (i=5), or userdata_pub/tenant/.../plugins/ (i=7)
	$candidate = __DIR__. str_repeat('/..', $sysdir_depth) . '/includes/oidplus.inc.php';
	if (file_exists($candidate)) {
		require_once $candidate;
		break;
	}
}

try {
	set_exception_handler(array(OIDplusGui::class, 'html_exception_handler'));

	OIDplus::init(true);

	if (OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_1.3.6.1.4.1.37476.2.5.2.4.1.95', false)) {
		throw new OIDplusException(_L('This plugin was disabled by the system administrator!'));
	}

	originHeaders();

	if (!isset($_REQUEST['filename'])) {
		http_response_code(400);
		throw new OIDplusException(_L('Argument "%1" is missing','filename'));
	}
	$filename = $_REQUEST['filename'];
	if (strpos($filename, '/') !== false) throw new OIDplusException(_L('Illegal file name'));
	if (strpos($filename, '\\') !== false) throw new OIDplusException(_L('Illegal file name'));
	if (strpos($filename, '..') !== false) throw new OIDplusException(_L('Illegal file name'));
	if (strpos($filename, chr(0)) !== false) throw new OIDplusException(_L('Illegal file name'));

	if (!isset($_REQUEST['id'])) {
		http_response_code(400);
		throw new OIDplusException(_L('Argument "%1" is missing','id'));
	}
	$id = $_REQUEST['id'];

	$uploaddir = OIDplusPagePublicAttachments::getUploadDir($id);
	$local_file = $uploaddir.'/'.$filename;

	if (!file_exists($local_file)) {
		http_response_code(404);
		throw new OIDplusException(_L('The file does not exist'));
	}

	foreach (OIDplus::getAllPlugins() as $plugin) {
		if ($plugin instanceof INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_11) {
			$plugin->beforeAttachmentDownload($id, $filename);
		}
	}

	OIDplus::invoke_shutdown();

	VtsBrowserDownload::output_file($local_file);
} catch (\Exception $e) {
	$htmlmsg = $e instanceof OIDplusException ? $e->getHtmlMessage() : htmlentities($e->getMessage());
	echo '<h1>'._L('Error').'</h1><p>'.$htmlmsg.'<p>';
}
