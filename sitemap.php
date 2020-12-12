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

header('Content-Type:text/text; charset=UTF-8');

OIDplus::init(false);

# ---

$non_default_languages = array();
foreach (OIDplus::getAvailableLangs() as $code) {
	if ($code == OIDplus::DEFAULT_LANGUAGE) continue;
	$non_default_languages[] = $code;
}

$out = array();
foreach (OIDplus::getPagePlugins() as $plugin) {
	if (is_subclass_of($plugin, OIDplusPagePluginPublic::class)) {
		$plugin->publicSitemap($out);

	}
}

$out2 = array();
foreach ($out as $o) {
	$out2[] = OIDplus::webpath().'?goto='.urlencode($o);
	foreach ($non_default_languages as $lang) {
		$out2[] = OIDplus::webpath().'?lang='.urlencode($lang).'&goto='.urlencode($o);
	}
}

echo implode("\r\n", $out2);