<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2022 Daniel Marschall, ViaThinkSoft
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

use ViaThinkSoft\OIDplus\OIDplus;
use ViaThinkSoft\OIDplus\OIDplusGui;

header('Content-Type:text/html; charset=UTF-8');

require_once __DIR__ . '/includes/oidplus.inc.php';

set_exception_handler(array(OIDplusGui::class, 'html_exception_handler'));

ob_start(); // allow cookie headers to be sent

OIDplus::init(true);

$static_node_id = isset($_REQUEST['goto']) ? $_REQUEST['goto'] : 'oidplus:system';

if (isset($_REQUEST['h404'])) {
	$handled = false;
	$plugins = OIDplus::getAllPlugins();
	foreach ($plugins as $plugin) {
		if ($plugin->handle404($_REQUEST['h404'])) $handled = true;
	}
	if (!$handled) {
		header('Location:'.OIDplus::webpath().'?goto='.urlencode('oidplus:err:'.$_REQUEST['h404']));
		die();
	}
}

$static_node_id = OIDplus::prefilterQuery($static_node_id, false);

$static = OIDplus::gui()->generateContentPage($static_node_id);
$page_title_2 = $static['title'];
$static_icon = $static['icon'];
$static_content = $static['text'];

if (!isset($_COOKIE['csrf_token'])) {
	// This is the main CSRF token used for AJAX.
	$token = OIDplus::authUtils()->genCSRFToken();
	OIDplus::cookieUtils()->setcookie('csrf_token', $token, 0, false);
	unset($token);
}

if (!isset($_COOKIE['csrf_token_weak'])) {
	// This CSRF token is created with SameSite=Lax and must be used
	// for OAuth 2.0 redirects or similar purposes.
	$token = OIDplus::authUtils()->genCSRFToken();
	OIDplus::cookieUtils()->setcookie('csrf_token_weak', $token, 0, false, 'Lax');
	unset($token);
}

OIDplus::handleLangArgument();

$page_title_1 = OIDplus::gui()->combine_systemtitle_and_pagetitle(OIDplus::config()->getValue('system_title'), $page_title_2);

$cont = OIDplus::gui()->showMainPage($page_title_1, $page_title_2, $static_icon, $static_content, $extra_head_tags=array(), $static_node_id='');

OIDplus::invoke_shutdown();

echo $cont;
