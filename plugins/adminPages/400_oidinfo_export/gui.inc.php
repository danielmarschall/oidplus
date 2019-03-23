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

if ($id === 'oidplus:export') {
	$handled = true;
	$out['title'] = 'Data export';
	$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/adminPages/'.basename(__DIR__).'/icon_big.png' : '';

	if (!OIDplus::authUtils()::isAdminLoggedIn()) {
		$out['icon'] = 'img/error_big.png';
		$out['text'] .= '<p>You need to <a href="?goto=oidplus:login">log in</a> as administrator.</p>';
	} else {
		$out['text'] = '<p>Here you can prepare the data export to <b>oid-info.com</b>.</p>'.
		               '<p><a href="plugins/adminPages/'.basename(__DIR__).'/oidinfo_export.php">Generate XML (all)</a></p>'.
		               '<p><a href="plugins/adminPages/'.basename(__DIR__).'/oidinfo_export.php?online=1">Generate XML (only non-existing)</a></p>'.
		               '<p><a href="http://www.oid-info.com/submit.htm">Upload to oid-info.com</a></p>';
	}
}
