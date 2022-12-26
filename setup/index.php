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
use ViaThinkSoft\OIDplus\OIDplusDatabasePlugin;
use ViaThinkSoft\OIDplus\OIDplusSqlSlangPlugin;
use ViaThinkSoft\OIDplus\OIDplusCaptchaPlugin;

require_once __DIR__ . '/../includes/oidplus.inc.php';

define('BASECONFIG_FILE_OLD', 'includes/config.inc.php');
define('BASECONFIG_FILE_NEW', 'userdata/baseconfig/config.inc.php');
$existing_config = '';
if (file_exists(__DIR__.'/../'.BASECONFIG_FILE_OLD)) $existing_config = BASECONFIG_FILE_OLD;
if (file_exists(__DIR__.'/../'.BASECONFIG_FILE_NEW)) $existing_config = BASECONFIG_FILE_NEW;
$already_setup = $existing_config != '';

OIDplus::handleLangArgument();

ob_start();

echo '<noscript>';
echo '<h2>'._L('Please enable JavaScript in order to use setup!').'</h2>';
echo '</noscript>';

echo '<span id="setupPageContent" style="display:None">';

echo '<p>';
if ($already_setup) {
	echo _L('This assistant will help you updating the file <b>%1</b>.',$existing_config);
} else {
	echo _L('Thank you very much for choosing OIDplus!');
	echo ' ';
	echo _L('This setup assistant will help you creating the file <b>%1</b>.',$existing_config);
}
echo ' ';
echo _L('This assistant does not automatically write to this file. Instead, you need to copy-paste the contents into the file.');
echo ' ';
if ($already_setup) {
	echo _L('Later, you can change the config file by hand, or run this assistant again.');
} else {
	echo _L('Once OIDplus setup is finished, you can change the config file by hand, or run this setup assistant again.');
}
echo '</p>';

echo '<h2 id="systemCheckCaption" style="display:none">'._L('System check').'</h2>';
echo '<div id="dirAccessWarning"></div>';

echo '<div id="step1">';
echo '<h2>'._L('Step %1: Enter setup information',1).'</h2>';

// ----------------------------------------

echo '<h3>'._L('Administrator password').'</h3>';

echo '<form id="step1_form">';
echo '<p>'._L('Which admin password do you want?').'<br><input id="admin_password" type="password" autocomplete="new-password" onkeypress="rebuild()" onkeyup="rebuild()"> <span id="password_warn"></span></p>';
echo '<p>'._L('Please repeat the password input:').'<br><input id="admin_password2" type="password" autocomplete="new-password" onkeypress="rebuild()" onkeyup="rebuild()"> <span id="password_warn2"></span></p>';

// ----------------------------------------

echo '<h3>'._L('Database connectivity').'</h3>';

if (file_exists(__DIR__ . '/../doc/database_connectivity_diagram.png')) {
	echo '<p><a href="../doc/database_connectivity_diagram.png" target="_blank"><img src="../doc/database_connectivity_diagram.png" width="20%" alt="'._L('Database connectivity diagram').'" title="'._L('Database connectivity diagram').'"></a></p>';
}

echo _L('Database plugin').': <select name="db_plugin" onChange="dbplugin_changed()" id="db_plugin">';

OIDplus::registerAllPlugins('database', OIDplusDatabasePlugin::class, array(OIDplus::class,'registerDatabasePlugin'));
foreach (OIDplus::getDatabasePlugins() as $plugin) {
	$selected = $plugin::id() == 'MySQL' ? ' selected="true"' : '';
	echo '<option value="'.htmlentities($plugin::id()).'"'.$selected.'>'.htmlentities($plugin::id()).'</option>';
}

echo '</select>';

echo '<div style="margin-left:50px">';

OIDplus::registerAllPlugins('sqlSlang', OIDplusSqlSlangPlugin::class, array(OIDplus::class,'registerSqlSlangPlugin'));
$sql_slang_selection = array();
foreach (OIDplus::getSqlSlangPlugins() as $plugin) {
	$slang_id = $plugin::id();
	$pluginManifest = $plugin->getManifest();
	$human_friendly_name = empty($pluginManifest->getName()) ? get_class($plugin) : $pluginManifest->getName();
	$sql_slang_selection[] = '<option value="'.$slang_id.'">'.$human_friendly_name.'</option>';
}
$sql_slang_selection = implode("\n", $sql_slang_selection);

$found_db_plugins = 0;
//OIDplus::registerAllPlugins('database', OIDplusDatabasePlugin::class, array(OIDplus::class,'registerDatabasePlugin'));
foreach (OIDplus::getDatabasePlugins() as $plugin) {
	$found_db_plugins++;
	$cont = $plugin->setupHTML();
	$cont = str_replace('<!-- %SQL_SLANG_SELECTION% -->', $sql_slang_selection, $cont);
	echo $cont;
}

if ($found_db_plugins == 0) {
	echo '<p><font color="red">'._L('ERROR: No database plugins were found! You CANNOT use OIDplus without database connection.').'</font></p>';
}

echo '</div>';

echo '<p>'._L('Table name prefix (e.g. <b>oidplus_</b>)').':<br><input id="tablename_prefix" type="text" value="oidplus_" onkeypress="rebuild()" onkeyup="rebuild()"></p>';

// ----------------------------------------

echo '<h3>'._L('CAPTCHA').'</h3>';

// TODO: Add a small explanation here, in case somebody does not know what CAPTCHA is

echo _L('CAPTCHA plugin').': <select name="captcha_plugin" onChange="captchaplugin_changed()" id="captcha_plugin">';

OIDplus::registerAllPlugins('captcha', OIDplusCaptchaPlugin::class, array(OIDplus::class,'registerCaptchaPlugin'));
foreach (OIDplus::getCaptchaPlugins() as $plugin) {
	$selected = strtolower($plugin::id()) === strtolower('None') ? ' selected="true"' : ''; // select "None" by default
	echo '<option value="'.htmlentities($plugin::id()).'"'.$selected.'>'.htmlentities($plugin::id()).'</option>';
}

echo '</select>';

echo '<div style="margin-left:50px">';

$found_captcha_plugins = 0;
foreach (OIDplus::getCaptchaPlugins() as $plugin) {
	$found_captcha_plugins++;
	$cont = $plugin->setupHTML();
	echo $cont;
}

if ($found_captcha_plugins == 0) {
	echo '<p><font color="red">'._L('ERROR: No CAPTCHA plugins were found! You CANNOT use OIDplus without the "%1" CAPTCHA plugin.','None').'</font></p>';
}

echo '</div>';

// ----------------------------------------

$is_ssl = OIDplus::isSSL();
echo '<h3>'._L('Secure connection (HTTPS)').'</h3>';
echo '<p>'._L('Enforcement of a secure connection:').'<br><select name="enforce_ssl" id="enforce_ssl" onchange="rebuild()">';
echo '<option value="OIDplus::ENFORCE_SSL_NO">'._L('No SSL available (don\'t redirect)').'</option>';
echo '<option value="OIDplus::ENFORCE_SSL_YES"'.($is_ssl ? ' selected' : '').'>'._L('Enforce SSL (always redirect)').'</option>';
echo '<option value="OIDplus::ENFORCE_SSL_AUTO"'.(!$is_ssl ? ' selected' : '').'>'._L('Intelligent SSL detection (redirect if port 443 is open)').'</option>';
echo '</select></p>';

// ----------------------------------------

echo '<h3>'._L('Public URL of this system (Canonical URL)').'</h3>';

echo '<p><input id="canonical_url" type="text" value="'.htmlentities(OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL)).'" onkeypress="rebuild()" onkeyup="rebuild()" style="width:550px"></p>';

// ----------------------------------------

echo '</form>';
echo '</div>';

echo '<div id="step2">';
echo '<h2>'._L('Step %1: Initialize database',2).'</h2>';
if ($already_setup) {
	echo '<p><input type="checkbox" id="step2_enable"> <label for="step2_enable"><font color="red">'._L('Re-Install database (all data will be deleted)').'</font></label></p>';
}
echo '<div id="step2_inner">';
echo '<p>'._L('Please import one of the following SQL dumps in your database:').'</p>';
echo '<p><ul>';
echo '	<li><a href="struct_empty.sql.php" id="struct_1" target="_blank">'._L('Empty OIDplus database without example data').'</a><span id="struct_cli_1"></span><br><br></li>';
echo '	<li><a href="struct_with_examples.sql.php" id="struct_2" target="_blank">'._L('OIDplus database with example data').'</a><span id="struct_cli_2"></span><br><br></li>';
echo '</ul></p>';
echo '<p><font color="red">'._L('Warning: Existing OIDplus data will be deleted during the initialization of the database.').'</font></p>';
echo '</div>';
if ($already_setup) {
	echo '<script>';
	echo '$("#step2_enable").click(function() {';
	echo '    if ($(this).is(":checked")) {';
	echo '        $("#step2_inner").show();';
	echo '    } else {';
	echo '        $("#step2_inner").hide();';
	echo '    }';
	echo '});';
	echo '$("#step2_inner").hide();';
	echo '</script>';
}
echo '</div>';

// ----------------------------------------

echo '<div id="step3">';
echo '<h2>'._L('Step %1: Save %2 file',3,$existing_config).'</h2>';
echo '<p>'._L('Save following contents into the file <b>%1</b>',$existing_config).'</p>';
echo '<code><font color="darkblue"><div id="config"></div></font></code>';
echo '<p><input type="button" value="'._L('Copy to clipboard').'" onClick="copyToClipboard(config)"></p>';
echo '</div>';

// ----------------------------------------

echo '<div id="step4">';
echo '<h2>'._L('Step %1: After you have updated the file...',4).'</h2>';
if (is_dir(__DIR__.'/../plugins/viathinksoft/adminPages/050_oobe/')) {
	if ($already_setup) {
		echo '<p><input type="button" onclick="window.location.href=\'../plugins/viathinksoft/adminPages/050_oobe/oobe.php\'" value="'._L('Check connection and modify initial settings').'"></p>';
		echo '<p><input type="button" onclick="window.location.href=\'../\'" value="'._L('Check connection and start OIDplus').'"></p>';
	} else {
		echo '<p><input type="button" onclick="window.location.href=\'../plugins/viathinksoft/adminPages/050_oobe/oobe.php\'" value="'._L('Check connection and go to initial settings').'"></p>';
	}
} else {
	echo '<p><input type="button" onclick="window.location.href=\'../\'" value="'._L('Check connection and start OIDplus').'"></p>';
}
echo '</div>';

echo '<br><br>'; // because of iPhone Safari

echo '</span>';
echo '<script> $("#setupPageContent")[0].style.display = "Block"; </script>';

# ---

$page_title_1 = _L('OIDplus Setup');
$page_title_2 = _L('Configuration File Generator');
$static_icon = 'img/main_icon.png';
$static_content = ob_get_contents();
$extra_head_tags = array();
$extra_head_tags[] = '<meta name="robots" content="noindex">';
$extra_head_tags[] = '<link rel="stylesheet" href="setup.min.css.php">';
$extra_head_tags[] = '<script src="setup.min.js.php" type="text/javascript"></script>';
ob_end_clean();

$cont = OIDplus::gui()->showSimplePage($page_title_1, $page_title_2, $static_icon, $static_content, $extra_head_tags);

OIDplus::invoke_shutdown();

echo $cont;
