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

if ($id === 'oidplus:baseasn1') {
	$handled = true;
	$out['title'] = 'Well known ASN.1 IDs';

	if (!OIDplus::authUtils()::isAdminLoggedIn()) {
		$out['text'] .= '<p>You need to <a href="?goto=oidplus:login">log in</a> as administrator.</p>';
	} else {

		$out['text'] = '<p><abbr title="These ID names can only be edited in the database directly (Table '.OIDPLUS_TABLENAME_PREFIX.'asn1id). Usually, there is no need to do this, though.">How to edit these IDs?</abbr></p>';

		$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."asn1id where well_known = 1 order by ".OIDplus::db()->natOrder('oid').", lfd");
		while ($row = OIDplus::db()->fetch_array($res)) {
			$out['text'] .= '<p>'.htmlentities(explode(':',$row['oid'])[1]).' = '.htmlentities($row['name']).'</p>';
		}
	}
}
