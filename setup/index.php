<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2021 Daniel Marschall, ViaThinkSoft
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

require_once __DIR__ . '/../includes/oidplus.inc.php';

OIDplus::handleLangArgument();

echo '<!DOCTYPE html>';
echo '<html lang="'.substr(OIDplus::getCurrentLang(),0,2).'">';

echo '<head>';
echo '	<title>'._L('OIDplus Setup').'</title>';
echo '	<meta name="robots" content="noindex">';
echo '	<meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '	<link rel="stylesheet" href="setup.min.css.php">';
echo '	<link rel="shortcut icon" type="image/x-icon" href="../favicon.ico.php">';
echo '	<script src="setup.min.js.php" type="text/javascript"></script>';
echo '</head>';

echo '<body>';

echo '<h1>'._L('OIDplus Setup - Configuration File Generator').'</h1>';

echo '<noscript>';
echo '<h2>'._L('Please enable JavaScript in order to use setup!').'</h2>';
echo '</noscript>';

echo '<span id="setupPageContent" style="display:None">';

echo OIDplus::gui()->getLanguageBox(null, false);

echo '<p>'._L('Thank you very much for choosing OIDplus! This setup assistant will help you creating or updating the file <b>%1</b>. Setup does not automatically write to this file. Instead, you need to copy-paste the contents into the file. Once OIDplus setup is finished, you can change the config file by hand, or run this setup assistant again.','userdata/baseconfig/config.inc.php').'</p>';

echo '<h2 id="systemCheckCaption" style="display:none">'._L('System check').'</h2>';
echo '<div id="dirAccessWarning"></div>';

echo '<div id="step1">';
echo '<h2>'._L('Step %1: Enter setup information',1).'</h2>';

echo '<h3>'._L('Administrator password').'</h3>';

echo '<form id="step1_form">';
echo '<p>'._L('Which admin password do you want?').'<br><input id="admin_password" type="password" autocomplete="new-password" onkeypress="rebuild()" onkeyup="rebuild()"> <span id="password_warn"></span></p>';
echo '<p>'._L('Please repeat the password input:').'<br><input id="admin_password2" type="password" autocomplete="new-password" onkeypress="rebuild()" onkeyup="rebuild()"> <span id="password_warn2"></span></p>';

echo '<h3>'._L('Database connectivity').'</h3>';

if (file_exists(__DIR__ . '/../doc/database_connectivity_diagram.png')) {
	echo '<p><a href="../doc/database_connectivity_diagram.png" target="_blank"><img src="../doc/database_connectivity_diagram.png" width="20%" alt="'._L('Database connectivity diagram').'" title="'._L('Database connectivity diagram').'"></a></p>';
}

echo _L('Database plugin').': <select name="db_plugin" onChange="dbplugin_changed()" id="db_plugin">';

OIDplus::registerAllPlugins('database', 'OIDplusDatabasePlugin', array('OIDplus','registerDatabasePlugin'));
foreach (OIDplus::getDatabasePlugins() as $plugin) {
	$selected = $plugin::id() == 'MySQL' ? ' selected="true"' : '';
	echo '<option value="'.htmlentities($plugin::id()).'"'.$selected.'>'.htmlentities($plugin::id()).'</option>';
}

echo '</select>';

echo '<div style="margin-left:50px">';

OIDplus::registerAllPlugins('sqlSlang', 'OIDplusSqlSlangPlugin', array('OIDplus','registerSqlSlangPlugin'));
$sql_slang_selection = array();
foreach (OIDplus::getSqlSlangPlugins() as $plugin) {
	$slang_id = $plugin::id();
	$pluginManifest = $plugin->getManifest();
	$human_friendly_name = empty($pluginManifest->getName()) ? get_class($plugin) : $pluginManifest->getName();
	$sql_slang_selection[] = '<option value="'.$slang_id.'">'.$human_friendly_name.'</option>';
}
$sql_slang_selection = implode("\n", $sql_slang_selection);

$found_db_plugins = 0;
//OIDplus::registerAllPlugins('database', 'OIDplusDatabasePlugin', array('OIDplus','registerDatabasePlugin'));
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

echo '<h3>'._L('ReCAPTCHA').'</h3>';
echo '<p><input id="recaptcha_enabled" type="checkbox" onclick="rebuild()"> <label for="recaptcha_enabled">'._L('reCAPTCHA enabled').'</label> (<a href="https://developers.google.com/recaptcha/intro" target="_blank">'._L('more information and obtain key').'</a>)</p>';
echo '<p>'._L('reCAPTCHA Public key').'<br><input id="recaptcha_public" type="text" onkeypress="rebuild()" onkeyup="rebuild()"></p>';
echo '<p>'._L('reCAPTCHA Private key').'<br><input id="recaptcha_private" type="text" onkeypress="rebuild()" onkeyup="rebuild()"></p>';

echo '<h3>'._L('TLS').'</h3>';
echo '<p>'._L('SSL enforcement').'<br><select name="enforce_ssl" id="enforce_ssl" onclick="rebuild()">';
echo '<option value="0">'._L('No SSL available (don\'t redirect)').'</option>';
echo '<option value="1">'._L('Enforce SSL (always redirect)').'</option>';
echo '<option value="2" selected>'._L('Intelligent SSL detection (redirect if port 443 is open)').'</option>';
echo '</select></p>';
echo '</form>';
echo '</div>';

echo '<div id="step2">';
echo '<h2>'._L('Step %1: Initialize database',2).'</h2>';
echo '<p><font color="red"><b>'._L('If you already have an OIDplus database and just want to rebuild the config file, please skip this step.').'</b></font></p>';
echo '<p>'._L('Otherwise, import one of the following SQL dumps in your database:').'</p>';
echo '<p><ul>';
echo '	<li><a href="struct_empty.sql.php" id="struct_1" target="_blank">'._L('Empty OIDplus database without example data').'</a><span id="struct_cli_1"></span><br><br></li>';
echo '	<li><a href="struct_with_examples.sql.php" id="struct_2" target="_blank">'._L('OIDplus database with example data').'</a><span id="struct_cli_2"></span><br><br></li>';
echo '</ul></p>';
echo '<p><font color="red">'._L('Warning: All data from the previous OIDplus instance will be deleted during the import.<br>If you already have an OIDplus database, skip to Step 3.').'</font></p>';
echo '</div>';

echo '<div id="step3">';
echo '<h2>'._L('Step %1: Save %2 file',3,'userdata/baseconfig/config.inc.php').'</h2>';
echo '<p>'._L('Save following contents into the file <b>%1</b>','userdata/baseconfig/config.inc.php').'</p>';
echo '<code><font color="darkblue"><div id="config"></div></font></code>';
echo '</div>';

echo '<div id="step4">';
echo '<h2>'._L('Step %1: Continue to next step',4).'</h2>';
echo '<p><input type="button" onclick="window.location.href=\'../\'" value="'._L('Continue').'"></p>';
// echo '<p><a href="../">Run the OIDplus system</a></p>';
echo '</div>';

echo '<br><br><br>'; // because of iPhone Safari

echo '</span>';
echo '<script> $("#setupPageContent")[0].style.display = "Block"; </script>';

echo '</body>';
echo '</html>';
