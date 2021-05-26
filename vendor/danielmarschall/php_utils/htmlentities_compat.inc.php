<?php

/*
 * HtmlEntities compatibility functions
 * Copyright 2019 Daniel Marschall, ViaThinkSoft
 * Version 2019-11-18
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

# http://www.ufive.unibe.ch/index.php?c=php54entitiesfix&l=de
# This workaround is not required with PHP 5.6+, since htmlentities() now uses the default encoding charset as default parameter value

if (!function_exists('compat_htmlspecialchars')) {
	function compat_htmlspecialchars($string, $ent=ENT_COMPAT, $charset='ISO-8859-1') {
		return htmlspecialchars($string, $ent, $charset);
	}
}

if (!function_exists('compat_htmlentities')) {
	function compat_htmlentities($string, $ent=ENT_COMPAT, $charset='ISO-8859-1') {
		return htmlentities($string, $ent, $charset);
	}
}

if (!function_exists('compat_html_entity_decode')) {
	function compat_html_entity_decode($string, $ent=ENT_COMPAT, $charset='ISO-8859-1') {
		return html_entity_decode($string, $ent, $charset);
	}
}

