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

ob_start(); // allow cookie headers to be sent

header('Content-Type:text/html; charset=UTF-8');

if (!function_exists('gmp_init')) {
	// Required for uuid_functions.inc.php and ipv6_functions.inc.php
	die('<h1>OIDplus Installation</h1><p>ERROR: The extension <b>gmp</b> is not installed.</p><p>Install it using <code>sudo aptitude update && sudo aptitude install php-gmp && sudo service apache2 restart</code> on Linux systems.</p>');
}

if (!function_exists('mb_substr')) {
	// Required for includes/classes/OIDplusSessionHandler.class.php, includes/oid_utils.inc.php, 3p/minify/path-converter/Converter.php, 3p/0xbb/Sha3.class.php
	die('<h1>OIDplus Installation</h1><p>ERROR: The extension <b>php-mbstring</b> is not installed.</p><p>Install it using <code>sudo aptitude update && sudo aptitude install php-mbstring && sudo service apache2 restart</code> on Linux systems.</p>');
}

OIDplus::init(true);

$static_node_id = isset($_REQUEST['goto']) ? $_REQUEST['goto'] : 'oidplus:system';
$static = OIDplus::gui()::generateContentPage($static_node_id);
$static_title = $static['title'];
$static_icon = $static['icon'];
$static_content = $static['text'];

function combine_systemtitle_and_pagetitle($systemtitle, $pagetitle) {
	if ($systemtitle == $pagetitle) {
		return $systemtitle;
	} else {
		return $systemtitle . ' - ' . $pagetitle;
	}
}

$sysid_oid = OIDplus::system_id(true);
if (!$sysid_oid) $sysid_oid = 'unknown';
header('X-OIDplus-SystemID:'.$sysid_oid);

$sys_url = OIDplus::system_url();
header('X-OIDplus-SystemURL:'.$sys_url);

$sys_ver = OIDplus::getVersion();
if (!$sys_ver) $sys_ver = 'unknown';
header('X-OIDplus-SystemVersion:'.$sys_ver);

$sys_install_type = OIDplus::getInstallType();
header('X-OIDplus-SystemInstallType:'.$sys_install_type);

$sys_title = OIDplus::config()->systemTitle();
header('X-OIDplus-SystemTitle:'.$sys_title);

if (class_exists('OIDplusPageAdminColors')) {
	$css = 'oidplus.min.css.php?h_shift='.(OIDplus::config()->getValue('color_hue_shift')/360).'&s_shift='.(OIDplus::config()->getValue('color_sat_shift')/100).'&v_shift='.(OIDplus::config()->getValue('color_val_shift')/100);
} else {
	$css = 'oidplus.min.css.php';
}

?><!DOCTYPE html>
<html lang="en">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="OIDplus-SystemID" content="<?php echo htmlentities($sysid_oid); ?>">
	<meta name="OIDplus-SystemURL" content="<?php echo htmlentities($sys_url); ?>">
	<meta name="OIDplus-SystemVersion" content="<?php echo htmlentities($sys_ver); ?>">
	<meta name="OIDplus-SystemInstallType" content="<?php echo htmlentities($sys_install_type); ?>">
	<meta name="OIDplus-SystemTitle" content="<?php echo htmlentities($sys_title); /* Do not remove. This meta tag is acessed via JS */ ?>">
	<meta name="theme-color" content="#A9DCF0">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<title><?php echo combine_systemtitle_and_pagetitle(OIDplus::config()->systemTitle(), $static_title); ?></title>

	<!-- We are using jQuery 2.2.1, because 3.3.1 seems to be incompatible with jsTree (HTML content will not be loaded into jsTree!) TODO: File bug report -->
	<script src="3p/jquery/jquery-2.2.1.min.js"></script>
	<script src="3p/bootstrap/js/bootstrap.min.js"></script>
	<script src="3p/jstree/jstree.min.js"></script>
	<script src='3p/tinymce/tinymce.min.js'></script>
	<script src="3p/jquery-ui/jquery-ui.min.js"></script>
	<script src="3p/layout/jquery.layout.min.js"></script>
	<script src="3p/spamspan/spamspan.js"></script>
	<script src="3p/sha3_js/sha3.js"></script><!-- https://github.com/emn178/js-sha3 -->
	<?php
	if (RECAPTCHA_ENABLED) {
	?>
	<script src="https://www.google.com/recaptcha/api.js"></script>
	<?php
	}
	?>
	<script src="https://polyfill.io/v3/polyfill.min.js?features=fetch%2CURL"></script><!-- For IE... -->
	<script src="oidplus.min.js.php"></script>

	<link rel="stylesheet" href="<?php echo htmlentities($css); ?>">

	<link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico">

	<!-- DM 28 May 2019: Removed CookieConsent temporarily, because it is placed at the beginning of the page and therefore ruins the Google index ... -->
	<!-- We might not need it, because cookies are only set during login, and at the login page we already warn about cookies -->
	<!-- TODO: Bring back? -->
	<!-- <link rel="stylesheet" type="text/css" href="3p/cookieconsent/cookieconsent.min.css">
	<script src="3p/cookieconsent/cookieconsent.min.js"></script>
	<script>
		window.addEventListener("load", function(){
		window.cookieconsent.initialise({
			"palette": {
				"popup": {
					"background": "#edeff5",
					"text": "#838391"
				},
				"button": {
					"background": "#4b81e8"
				}
			},
			"position": "bottom-right"
		})});
	</script> -->
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
				return secure_email($email, $text, 1); // AntiSpam
			}, $static_content);

		echo '<h1 id="real_title">';
		if ($static_icon != '') echo '<img src="'.htmlentities($static_icon).'" width="48" height="48" alt=""> ';
		echo htmlentities($static_title).'</h1>';
		echo '<div id="real_content">'.$static_content.'</div>';
		if ($_SERVER['REQUEST_METHOD'] == 'GET') {
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
			<a <?php echo oidplus_link('oidplus:system'); ?>>
				<span id="system_title_1">ViaThinkSoft OIDplus 2.0</span><br>
				<span id="system_title_2"><?php echo htmlentities(OIDplus::config()->systemTitle()); ?></span>
			</a>
		</div>
	</div>

	<div id="gotobox">
		<input type="text" name="goto" id="gotoedit" value="<?php echo htmlentities($static_node_id); ?>">
		<input type="button" value="Go" onclick="gotoButtonClicked()" id="gotobutton">
	</div>

	<div id="oidtree" class="borderbox">
		<!-- <noscript>
			<p><b>Please enable JavaScript to use all features</b></p>
		</noscript> -->
		<?php OIDplusTree::nonjs_menu(); ?>
	</div>
</div>

</body>
</html>
<?php

$cont = ob_get_contents();
ob_end_clean();

echo $cont;
