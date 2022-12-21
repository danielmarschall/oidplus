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

use ViaThinkSoft\OIDplus\OIDplus;
use ViaThinkSoft\OIDplus\OIDplusGui;
use ViaThinkSoft\OIDplus\OIDplusException;

require_once __DIR__ . '/../../../../includes/oidplus.inc.php';

ob_start(); // allow cookie headers to be sent

header('Content-Type:text/html; charset=UTF-8');

OIDplus::init(true);
set_exception_handler(array(OIDplusGui::class, 'html_exception_handler'));

if (OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_ViaThinkSoft\OIDplus\OIDplusPageAdminOOBE', false)) {
	throw new OIDplusException(_L('This plugin was disabled by the system administrator!'));
}

ob_start();

$step = 1;
$errors_happened = false;
$edits_possible = true;

echo '<!DOCTYPE html>';
echo '<html lang="'.substr(OIDplus::getCurrentLang(),0,2).'">';

echo '<head>';
echo '	<title>'._L('OIDplus Setup').'</title>';
echo '	<meta name="robots" content="noindex">';
echo '	<meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '  <script src="../../../../oidplus.min.js.php"></script>';
echo '  <script src="../../../../polyfill.min.js.php"></script>';
echo '	<link rel="stylesheet" href="../../../../setup/setup.min.css.php">';
echo '	<link rel="shortcut icon" type="image/x-icon" href="../../../../favicon.ico.php">';
echo '</head>';

echo '<body>';

echo '<h1>'._L('OIDplus Setup - Initial Settings').'</h1>';

OIDplus::handleLangArgument();
echo OIDplus::gui()->getLanguageBox(null, false);

echo '<p>'._L('If you can read this, then your database login credentials are correct.').'</p>';

echo '<p>'._L('The following settings need to be configured once.<br>After setup is complete, you can change all these settings through the admin login area, if necessary.').'</p>';

echo '<form method="POST" action="oobe.php">';
echo '<input type="hidden" name="sent" value="1">';

if (OIDplus::getActiveCaptchaPlugin()->isVisible()) echo '<p><u>'._L('Step %1: Solve CAPTCHA',$step++).'</u></p>';
if (isset($_POST['sent'])) {
	try {
		OIDplus::getActiveCaptchaPlugin()->captchaVerify($_POST);
	} catch (\Exception $e) {
		echo '<p><font color="red"><b>'.htmlentities($e->getMessage()).'</b></font></p>';
		$errors_happened = true;
		$edits_possible = false;
	}
}
echo OIDplus::getActiveCaptchaPlugin()->captchaGenerate(_L('Before logging in, please solve the following CAPTCHA'), _L('If the CAPTCHA does not work (e.g. because of wrong keys, please run <a href="%1">setup part 1</a> again or edit %2 manually).',OIDplus::webpath(null,OIDplus::PATH_RELATIVE).'setup/','userdata/baseconfig/config.inc.php'));

echo '<p><u>'._L('Step %1: Authenticate',$step++).'</u></p>';

if (OIDplus::authUtils()->isAdminLoggedIn()) {

	echo '<p><font color="green">You are already logged in as administrator.</font></p>';

} else {

	echo '<p>'._L('Please enter the administrator password you have entered before.').'</p>';

	echo '<p><input type="password" name="admin_password" value=""> (<a href="'.OIDplus::webpath(null,OIDplus::PATH_RELATIVE).'setup/">'._L('Forgot password?').'</a>) ';

	if (isset($_POST['sent'])) {
		if (!OIDplus::authUtils()->adminCheckPassword(isset($_POST['admin_password']) ? $_POST['admin_password'] : '')) {
			$errors_happened = true;
			$edits_possible = false;
			echo '<font color="red"><b>'._L('Wrong password').'</b></font>';
		}
	}

	echo '</p>';
}

#------------------------
$do_edits = isset($_POST['sent']) && $edits_possible;;
#------------------------

# ---

function step_admin_email($step, $do_edits, &$errors_happened) {
	echo '<p><u>'._L('Step %1: Please enter the email address of the system administrator',$step).'</u></p>';
	echo '<input type="text" name="admin_email" value="';

	$msg = '';
	if (isset($_POST['sent'])) {
		echo htmlentities(isset($_POST['admin_email']) ? $_POST['admin_email'] : '');
		if ($do_edits) {
			try {
				OIDplus::config()->setValue('admin_email', isset($_POST['admin_email']) ? $_POST['admin_email'] : '');
			} catch (\Exception $e) {
				$msg = $e->getMessage();
				$errors_happened = true;
			}
		}
	} else {
		echo htmlentities(OIDplus::config()->getValue('admin_email'));
	}

	echo '" size="25"> <font color="red"><b>'.$msg.'</b></font>';
}
step_admin_email($step++, $do_edits, $errors_happened);

# ---

function step_system_title($step, $do_edits, &$errors_happened) {
	echo '<p><u>'._L('Step %1: What title should your Registration Authority / OIDplus instance have?',$step).'</u></p>';
	echo '<input type="text" name="system_title" value="';

	$msg = '';
	if (isset($_POST['sent'])) {
		echo htmlentities(isset($_POST['system_title']) ? $_POST['system_title'] : '');
		if ($do_edits) {
			try {
				OIDplus::config()->setValue('system_title', isset($_POST['system_title']) ? $_POST['system_title'] : '');
			} catch (\Exception $e) {
				$msg = $e->getMessage();
				$errors_happened = true;
			}
		}
	} else {
		echo htmlentities(OIDplus::config()->getValue('system_title'));
	}

	echo '" size="50"> <font color="red"><b>'.$msg.'</b></font>';
}
step_system_title($step++, $do_edits, $errors_happened);

# ---

foreach (OIDplus::getAllPlugins() as $plugin) {
	if ($plugin->implementsFeature('1.3.6.1.4.1.37476.2.5.2.3.1')) {
		$plugin->oobeEntry($step++, $do_edits, $errors_happened);
	}
}

# ---

echo '<p><u>'._L('Step %1: Save settings and start OIDplus',$step).'</u></p>';
echo '<input type="submit" value="'._L('Save and start OIDplus!').'">';
echo '</form>';

$pki_status = OIDplus::getPkiStatus();

if ($pki_status) {

	echo '<p><u>'._L('Your OIDplus system ID (derived from the public key) is:').'</u></p>';

	echo '<b>';
	$sysid_oid = OIDplus::getSystemId(true);
	if (!$sysid_oid) $sysid_oid = _L('Unknown!');
	echo htmlentities($sysid_oid);
	echo '</b>';

	echo '<p><u>'._L('Your public key is:').'</u></p>';

	$val = OIDplus::getSystemPublicKey();
	if ($val) {
		echo '<pre>'.htmlentities($val).'</pre>';
	} else {
		echo '<p>'._L('Private/Public key creation failed').'</p>';
	}

}

echo '<br><br><br>'; // because of iPhone Safari

echo '</body>';

echo '</html>';

$cont = ob_get_contents();
ob_end_clean();

if ($do_edits && !$errors_happened)  {
	OIDplus::config()->setValue('oobe_main_done', '1');
	OIDplus::invoke_shutdown();
	header('Location:../../../../');
} else {
	OIDplus::invoke_shutdown();
	echo $cont;
}
