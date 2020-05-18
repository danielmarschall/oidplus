<?php

/*
 * OIDplus 2.0
 * Copyright 2019 Daniel Marschall, ViaThinkSoft
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

require_once __DIR__ . '/../../../includes/oidplus.inc.php';

OIDplus::init(true);

originHeaders();

if (!isset($_REQUEST['filename'])) {
	http_response_code(400);
	throw new Exception("<h1>Error</h1><p>Argument 'filename' is missing<p>");
}
$filename = $_REQUEST['filename'];
if (strpos($filename, '/') !== false) throw new OIDplusException("Illegal file name");
if (strpos($filename, '\\') !== false) throw new OIDplusException("Illegal file name");
if (strpos($filename, '..') !== false) throw new OIDplusException("Illegal file name");
if (strpos($filename, chr(0)) !== false) throw new OIDplusException("Illegal file name");

if (!isset($_REQUEST['id'])) {
	http_response_code(400);
	throw new Exception("<h1>Error</h1><p>Argument 'id' is missing<p>");
}
$id = $_REQUEST['id'];

$uploaddir = OIDplusPagePublicAttachments::getUploadDir($id);
$local_file = $uploaddir.'/'.$filename;
VtsBrowserDownload::output_file($local_file);
