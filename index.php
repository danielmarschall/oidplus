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

$add_css_args = array();
if (class_exists('OIDplusPageAdminColors')) {
	// Usually, such things would be done using "features" (implementsFeature),
	// but there are following reasons why we DON'T do it:
	// 1. Just having a "CSS URL parameter feature" would change the URL parameter,
	//    but it would not affect the custom code in oidplus.min.css.php
	// 2. The JS function OIDplusPageAdminColors.test_color_theme() has an hardcoded set of parameters
	//    and does not follow the arguments that might be set by other plugins.
	$add_css_args[] = 'theme='.urlencode(OIDplus::config()->getValue('design'));
	$add_css_args[] = 'invert='.urlencode(OIDplus::config()->getValue('color_invert'));
	$add_css_args[] = 'h_shift='.urlencode(number_format(OIDplus::config()->getValue('color_hue_shift')/360,5,'.',''));
	$add_css_args[] = 's_shift='.urlencode(number_format(OIDplus::config()->getValue('color_sat_shift')/100,5,'.',''));
	$add_css_args[] = 'v_shift='.urlencode(number_format(OIDplus::config()->getValue('color_val_shift')/100,5,'.',''));
}
$add_css_args = count($add_css_args) > 0 ? '?'.implode('&',$add_css_args) : '';

// Get theme color (color of title bar)
$theme_color = '';
$plugins = OIDplus::getDesignPlugins();
foreach ($plugins as $plugin) {
	if ((basename($plugin->getPluginDirectory())) == OIDplus::config()->getValue('design','default')) {
		$theme_color = $plugin->getThemeColor();
		if (($theme_color != '') && class_exists('OIDplusPageAdminColors')) {
			$hs = OIDplus::config()->getValue('color_hue_shift',0)/360;
			$ss = OIDplus::config()->getValue('color_sat_shift',0)/100;
			$vs = OIDplus::config()->getValue('color_val_shift',0)/100;
			$theme_color = changeHueOfCSS($theme_color, $hs, $ss, $vs); // "changeHueOfCSS" can also change a single color value if it has the form #xxyyzz or #xyz
		}
	}
}

?><!DOCTYPE html>
<html lang="<?php echo substr(OIDplus::getCurrentLang(),0,2); ?>">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="OIDplus-SystemTitle" content="<?php echo htmlentities(OIDplus::config()->getValue('system_title')); /* Do not remove. This meta tag is acessed by oidplus_base.js */ ?>">
	<?php
	if ($theme_color != '') echo '<meta name="theme-color" content="'.$theme_color.'">';
	?>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<title><?php echo htmlentities(combine_systemtitle_and_pagetitle(OIDplus::config()->getValue('system_title'), $static_title)); ?></title>

	<script src="polyfill.min.js.php"></script>
	<?php
	echo OIDplus::getActiveCaptchaPlugin()->captchaDomHead();
	?>
	<script src="oidplus.min.js.php"></script>

	<link rel="stylesheet" href="oidplus.min.css.php<?php echo htmlentities($add_css_args); ?>">
	<link rel="shortcut icon" type="image/x-icon" href="favicon.ico.php">
</head>

<body>

<div id="loading" style="display:none">Loading&#8230;</div>

<div id="frames">
	<div id="content_window" class="borderbox">
		<?php
		$static_content = preg_replace_callback(
			'|<a\s([^>]*)href="mailto:([^"]+)"([^>]*)>([^<]*)</a>|ismU',
			function ($treffer) {
				$email = $treffer[2];
				$text = $treffer[4];
				return OIDplus::mailUtils()->secureEmailAddress($email, $text, 1); // AntiSpam
			}, $static_content);

		echo '<h1 id="real_title">';
		if ($static_icon != '') echo '<img src="'.htmlentities($static_icon).'" width="48" height="48" alt=""> ';
		echo htmlentities($static_title).'</h1>';
		echo '<div id="real_content">'.$static_content.'</div>';
		if ((!isset($_SERVER['REQUEST_METHOD'])) || ($_SERVER['REQUEST_METHOD'] == 'GET')) {
			echo '<br><p><img src="img/share.png" width="15" height="15" alt="'._L('Share').'"> <a href="?goto='.htmlentities($static_node_id).'" id="static_link" class="gray_footer_font">'._L('Static link to this page').'</a>';
			echo '</p>';
		}
		echo '<br>';
		?>
	</div>

	<div id="system_title_bar">
		<?php
		echo '<div id="system_title_menu" onclick="mobileNavButtonClick(this)" onmouseenter="mobileNavButtonHover(this)" onmouseleave="mobileNavButtonHover(this)">';
		echo '	<div id="bar1"></div>';
		echo '	<div id="bar2"></div>';
		echo '	<div id="bar3"></div>';
		echo '</div>';
		echo '';
		echo '<div id="system_title_text">';
		echo '	<a '.OIDplus::gui()->link('oidplus:system').' id="system_title_a">';
		echo '		<span id="system_title_logo"></span>';
		echo '		<span id="system_title_1">'._L('ViaThinkSoft OIDplus 2.0').'</span><br>';
		echo '		<span id="system_title_2">'.htmlentities(OIDplus::config()->getValue('system_title')).'</span>';
		echo '	</a>';
		echo '</div>';
		?>
	</div>

	<?php
	echo OIDplus::gui()->getLanguageBox($static_node_id, true);
	?>

	<div id="gotobox">
		<?php
		echo '<input type="text" name="goto" id="gotoedit" value="'.htmlentities($static_node_id).'">';
		echo '<input type="button" value="'._L('Go').'" onclick="gotoButtonClicked()" id="gotobutton">';
		?>
	</div>

	<div id="oidtree" class="borderbox">
		<?php
		//echo '<noscript>';
		//echo '<p><b>'._L('Please enable JavaScript to use all features').'</b></p>';
		//echo '</noscript>';
		OIDplus::menuUtils()->nonjs_menu();
		?>
	</div>
</div>

</body>
</html>
<?php

$cont = ob_get_contents();
ob_end_clean();

OIDplus::invoke_shutdown();

echo $cont;
