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

header('Content-Type:text/html; charset=UTF-8');

require_once __DIR__ . '/includes/oidplus.inc.php';

set_exception_handler('html_exception_handler');
function html_exception_handler($exception) {
	if ($exception instanceof OIDplusConfigInitializationException) {
		echo "<h1>OIDplus initialization error</h1>";
		echo '<p>'.htmlentities($exception->getMessage(), ENT_SUBSTITUTE).'</p>';
		echo '<p>Please check <b>userdata/baseconfig/config.inc.php</b>';
		if (is_dir(__DIR__ . '/setup')) {
			echo ' or run <a href="'.OIDplus::getSystemUrl().'setup/">setup</a> again';
		}
		echo '</p>';
	} else {
		echo "<h1>OIDplus error</h1>";
		// ENT_SUBSTITUTE because ODBC drivers might return ANSI instead of UTF-8 stuff
		echo '<p>'.htmlentities($exception->getMessage(), ENT_SUBSTITUTE).'</p>';
		echo '<p><b>Technical information about the problem:</b></p>';
		echo '<pre>';
		echo get_class($exception)."\n";
		echo 'at '.$exception->getFile().'('.$exception->getLine().")\n";
		echo "Stacktrace:\n";
		echo $exception->getTraceAsString();
		echo '</pre>';
	}
}

ob_start(); // allow cookie headers to be sent

OIDplus::init(true);

$static_node_id = isset($_REQUEST['goto']) ? $_REQUEST['goto'] : 'oidplus:system';
$static = OIDplus::gui()::generateContentPage($static_node_id);
$static_title = $static['title'];
$static_icon = $static['icon'];
$static_content = $static['text'];

function combine_systemtitle_and_pagetitle($systemtitle, $pagetitle) {
	// Please also change the function in oidplus_base.js
	if ($systemtitle == $pagetitle) {
		return $systemtitle;
	} else {
		return $pagetitle . ' - ' . $systemtitle;
	}
}

$sysid_oid = OIDplus::getSystemId(true);
if (!$sysid_oid) $sysid_oid = 'unknown';
header('X-OIDplus-SystemID:'.$sysid_oid);

$sys_url = OIDplus::getSystemUrl();
header('X-OIDplus-SystemURL:'.$sys_url);

$sys_ver = OIDplus::getVersion();
if (!$sys_ver) $sys_ver = 'unknown';
header('X-OIDplus-SystemVersion:'.$sys_ver);

$sys_install_type = OIDplus::getInstallType();
header('X-OIDplus-SystemInstallType:'.$sys_install_type);

$sys_title = OIDplus::config()->getValue('system_title');
header('X-OIDplus-SystemTitle:'.$sys_title);

if (class_exists('OIDplusPageAdminColors')) {
	$css = 'oidplus.min.css.php?invert='.(OIDplus::config()->getValue('color_invert')).'&h_shift='.(OIDplus::config()->getValue('color_hue_shift')/360).'&s_shift='.(OIDplus::config()->getValue('color_sat_shift')/100).'&v_shift='.(OIDplus::config()->getValue('color_val_shift')/100);
} else {
	$css = 'oidplus.min.css.php';
}

$js = 'oidplus.min.js.php';

?><!DOCTYPE html>
<html lang="en">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="OIDplus-SystemID" content="<?php echo htmlentities($sysid_oid); ?>">
	<meta name="OIDplus-SystemURL" content="<?php echo htmlentities($sys_url); ?>">
	<meta name="OIDplus-SystemVersion" content="<?php echo htmlentities($sys_ver); ?>">
	<meta name="OIDplus-SystemInstallType" content="<?php echo htmlentities($sys_install_type); ?>">
	<meta name="OIDplus-SystemTitle" content="<?php echo htmlentities($sys_title); /* Do not remove. This meta tag is acessed by oidplus_base.js */ ?>">
	<meta name="theme-color" content="#A9DCF0">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<title><?php echo combine_systemtitle_and_pagetitle(OIDplus::config()->getValue('system_title'), $static_title); ?></title>

	<script src="<?php echo htmlentities($js); ?>"></script>

	<link rel="stylesheet" href="<?php echo htmlentities($css); ?>">
	<link rel="shortcut icon" type="image/x-icon" href="favicon.ico.php">
</head>

<body>

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
			echo '<br><p><img src="img/share.png" width="15" height="15" alt="Share"> <a href="?goto='.htmlentities($static_node_id).'" id="static_link" class="gray_footer_font">Static link to this page</a>';
			echo '</p>';
		}
		echo '<br>';
		?>
	</div>

	<div id="system_title_bar">
		<div id="system_title_menu" onclick="mobileNavButtonClick(this)" onmouseenter="mobileNavButtonHover(this)" onmouseleave="mobileNavButtonHover(this)">
			<div id="bar1"></div>
			<div id="bar2"></div>
			<div id="bar3"></div>
		</div>

		<div id="system_title_text">
			<a <?php echo OIDplus::gui()->link('oidplus:system'); ?>>
				<span id="system_title_1">ViaThinkSoft OIDplus 2.0</span><br>
				<span id="system_title_2"><?php echo htmlentities(OIDplus::config()->getValue('system_title')); ?></span>
			</a>
		</div>
	</div>

	<div id="languageBox">
		<?php

		foreach (OIDplus::getAllPluginManifests('language') as $pluginManifest) {
			$xmldata = $pluginManifest->getRawXml();
			$flag = $xmldata->language->flag->__toString();
			$code = $xmldata->language->code->__toString();
			if ($code == OIDplus::getCurrentLang()) {
				$class = 'lng_flag';
			} else {
				$class = 'lng_flag picture_grayout';
			}
			echo '<img src="plugins/language/'.$code.'/'.$flag.'" alt="'.$pluginManifest->getName().'" title="'.$pluginManifest->getName().'" class="'.$class.'" id="lng_flag_'.$code.'" height="20" onclick="setLanguage(\''.$code.'\')"> ';
		}

		?>
	</div>

	<div id="gotobox">
		<input type="text" name="goto" id="gotoedit" value="<?php echo htmlentities($static_node_id); ?>">
		<input type="button" value="Go" onclick="gotoButtonClicked()" id="gotobutton">
	</div>

	<div id="oidtree" class="borderbox">
		<!-- <noscript>
			<p><b>Please enable JavaScript to use all features</b></p>
		</noscript> -->
		<?php OIDplus::menuUtils()->nonjs_menu(); ?>
	</div>
</div>

</body>
</html>
<?php

$cont = ob_get_contents();
ob_end_clean();

echo $cont;
