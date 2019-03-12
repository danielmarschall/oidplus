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

function freeoid_max_id() {
	$res = OIDplus::db()->query("select id from ".OIDPLUS_TABLENAME_PREFIX."objects where id like 'oid:1.3.6.1.4.1.37476.9000.%' order by ".OIDplus::db()->natOrder('id'));
	$highest_id = 0;
	while ($row = OIDplus::db()->fetch_array($res)) {
		$highest_id = explode('.',$row['id'])[8];
	}
	return $highest_id;
}

