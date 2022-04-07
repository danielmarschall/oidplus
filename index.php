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

header('Content-Type:text/html; charset=UTF-8');

require_once __DIR__ . '/includes/oidplus.inc.php';

set_exception_handler(array('OIDplusGui', 'html_exception_handler'));

ob_start(); // allow cookie headers to be sent

OIDplus::init(true);

$static_node_id = isset($_REQUEST['goto']) ? $_REQUEST['goto'] : 'oidplus:system';

$static_node_id = OIDplus::prefilterQuery($static_node_id, false);

$static = OIDplus::gui()->generateContentPage($static_node_id);
$static_title = $static['title'];
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

function combine_systemtitle_and_pagetitle($systemtitle, $pagetitle) {
	// Please also change the function in oidplus_base.js
	if ($systemtitle == $pagetitle) {
		return $systemtitle;
	} else {
		return $pagetitle . ' - ' . $systemtitle;
	}
}

// Get theme color (color of title bar)
$design_plugin = OIDplus::getActiveDesignPlugin();
$theme_color = is_null($design_plugin) ? '' : $design_plugin->getThemeColor();

$head_elems = array();
$head_elems[] = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
$head_elems[] = '<meta name="OIDplus-SystemTitle" content="'.htmlentities(OIDplus::config()->getValue('system_title')).'">'; // Do not remove. This meta tag is acessed by oidplus_base.js
if ($theme_color != '') $head_elems[] = '<meta name="theme-color" content="'.htmlentities($theme_color).'">';
$head_elems[] = '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
$head_elems[] = '<title>'.htmlentities(combine_systemtitle_and_pagetitle(OIDplus::config()->getValue('system_title'), $static_title)).'</title>';
$head_elems[] = '<script src="polyfill.min.js.php"></script>';
$head_elems[] = OIDplus::getActiveCaptchaPlugin()->captchaDomHead();
$head_elems[] = '<script src="oidplus.min.js.php"></script>';
$head_elems[] = '<link rel="stylesheet" href="oidplus.min.css.php">';
$head_elems[] = '<link rel="shortcut icon" type="image/x-icon" href="favicon.ico.php">';
$head_elems[] = '<link rel="canonical" href="'.htmlentities(OIDplus::canonicalURL()).'">';

$plugins = OIDplus::getPagePlugins();
foreach ($plugins as $plugin) {
	$plugin->htmlHeaderUpdate($head_elems);
}

// ---

echo "<!DOCTYPE html>\n";

echo "<html lang=\"".substr(OIDplus::getCurrentLang(),0,2)."\">\n";
echo "<head>\n";
echo "\t".implode("\n\t",$head_elems)."\n";
echo "</head>\n";

echo "<body>\n";

echo '<div id="loading" style="display:none">Loading&#8230;</div>';

echo '<div id="frames">';
echo '<div id="content_window" class="borderbox">';

echo '<h1 id="real_title">';
if ($static_icon != '') echo '<img src="'.htmlentities($static_icon).'" width="48" height="48" alt=""> ';
echo htmlentities($static_title).'</h1>';
echo '<div id="real_content">'.$static_content.'</div>';
if ((!isset($_SERVER['REQUEST_METHOD'])) || ($_SERVER['REQUEST_METHOD'] == 'GET')) {
	echo '<br><p><img src="img/share.png" width="15" height="15" alt="'._L('Share').'"> <a href="?goto='.htmlentities($static_node_id).'" id="static_link" class="gray_footer_font">'._L('Static link to this page').'</a>';
	echo '</p>';
}
echo '<br>';

echo '</div>';

echo '<div id="system_title_bar">';

echo '<div id="system_title_menu" onclick="mobileNavButtonClick(this)" onmouseenter="mobileNavButtonHover(this)" onmouseleave="mobileNavButtonHover(this)">';
echo '	<div id="bar1"></div>';
echo '	<div id="bar2"></div>';
echo '	<div id="bar3"></div>';
echo '</div>';

echo '<div id="system_title_text">';
echo '	<a '.OIDplus::gui()->link('oidplus:system').' id="system_title_a">';
echo '		<span id="system_title_logo"></span>';
echo '		<span id="system_title_1">'._L('ViaThinkSoft OIDplus 2.0').'</span><br>';
echo '		<span id="system_title_2">'.htmlentities(OIDplus::config()->getValue('system_title')).'</span>';
echo '	</a>';
echo '</div>';

echo '</div>';

echo OIDplus::gui()->getLanguageBox($static_node_id, true);

echo '<div id="gotobox">';
echo '<input type="text" name="goto" id="gotoedit" value="'.htmlentities($static_node_id).'">';
echo '<input type="button" value="'._L('Go').'" onclick="gotoButtonClicked()" id="gotobutton">';
echo '</div>';

echo '<div id="oidtree" class="borderbox">';
//echo '<noscript>';
//echo '<p><b>'._L('Please enable JavaScript to use all features').'</b></p>';
//echo '</noscript>';
OIDplus::menuUtils()->nonjs_menu();
echo '</div>';

echo '</div>';

echo "\n</body>\n";
echo "</html>\n";

$cont = ob_get_contents();
ob_end_clean();

OIDplus::invoke_shutdown();

$plugins = OIDplus::getPagePlugins();
foreach ($plugins as $plugin) {
	$plugin->htmlPostprocess($cont);
}

echo $cont;
