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

require_once __DIR__ . '/../../../../includes/oidplus.inc.php';

try {
	OIDplus::init(true);

	if (OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_OIDplusPagePublicAttachments', false)) {
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

	VtsBrowserDownload::output_file($local_file);
} catch (Exception $e) {
	echo '<h1>'._L('Error').'</h1><p>'.htmlentities($e->getMessage()).'<p>';
}
