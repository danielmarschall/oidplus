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

require_once __DIR__ . '/../../../includes/oidplus.inc.php';

header('Content-Type:text/html; charset=UTF-8');

OIDplus::init(true);

# ---

if (!OIDplus::authUtils()::isAdminLoggedIn()) {
	if (PHP_SAPI == 'cli') {
		#echo "You need to log in as administrator.\n";
		#die();
	} else {
		echo '<p>'._L('You need to <a %1>log in</a> as administrator.','href="'.OIDplus::webpath().'?goto=oidplus:login"').'</p>';
		die();
	}
}

header('Content-Type:text/xml');

OIDplusPageAdminOIDInfoExport::outputXML(isset($_GET['online']) && $_GET['online']);