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

require_once __DIR__ . '/includes/oidplus.inc.php';

header('Content-Type:text/html; charset=UTF-8');

OIDplus::init(true);

OIDplus::db()->set_charset("UTF8");
OIDplus::db()->query("SET NAMES 'utf8'");

# ---

header('Content-Type:text/plain');

$nonConfidential = OIDplusObject::getAllNonConfidential();

$json = array();
foreach (OIDplus::getPagePlugins('public') as $plugin) {
	if (get_class($plugin) == 'OIDplusPagePublicObjects') {
		foreach ($nonConfidential as $id) {
			echo OIDplus::system_url().'?goto='.urlencode($id)."\n";
		}
	} else {
		$plugin->tree($json, null, true, null);
	}
}
_rec($json);

# ---

function _rec($json) {
	foreach ($json as $x) {
		if (isset($x['id']) && $x['id']) {
			echo OIDplus::system_url().'?goto='.urlencode($x['id'])."\n";
		}
		if (isset($x['children'])) {
			_rec($x['children']);
		}
	}
}

