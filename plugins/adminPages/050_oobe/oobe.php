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

require_once __DIR__ . '/../../../includes/oidplus.inc.php';

ob_start(); // allow cookie headers to be sent

header('Content-Type:text/html; charset=UTF-8');

OIDplus::init(true);

ob_start();

$step = 1;
$errors_happened = false;
$edits_possible = true;

?><!DOCTYPE html>
<html lang="en">

<head>
	<title>OIDplus Setup</title>
	<meta name="robots" content="noindex">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="../../../setup/setup.css">
	<?php
	if (OIDplus::baseConfig()->getValue('RECAPTCHA_ENABLED', false)) {
	?>
	<script src="https://www.google.com/recaptcha/api.js"></script>
	<?php
	}
	?>
</head>

<body>

<h1>OIDplus Setup - Initial Settings</h1>

<p>If you can read this, then your database login credentials are correct.</p>

<p>The following settings need to be configured once.<br>
After setup is complete, you can change all these settings
through the admin login area, if necessary.</p>

<form method="POST" action="oobe.php">
<input type="hidden" name="sent" value="1">

<?php
if (OIDplus::baseConfig()->getValue('RECAPTCHA_ENABLED', false)) {
	echo '<p><u>Step '.($step++).': Solve CAPTCHA</u></p>';
	echo '<noscript>';
	echo '<p><font color="red">You need to enable JavaScript to solve the CAPTCHA.</font></p>';
	echo '</noscript>';
	echo '<script> grecaptcha.render(document.getElementById("g-recaptcha"), { "sitekey" : "'.OIDplus::baseConfig()->getValue('RECAPTCHA_PUBLIC', '').'" }); </script>';
	echo '<p>Before logging in, please solve the following CAPTCHA</p>';
	echo '<p>If the CAPTCHA does not work (e.g. because of wrong keys, please run <a href="<?php echo OIDplus::getSystemUrl(); ?>setup/">setup part 1</a> again or edit includes/config.inc.php).</p>';
	echo '<div id="g-recaptcha" class="g-recaptcha" data-sitekey="'.OIDplus::baseConfig()->getValue('RECAPTCHA_PUBLIC', '').'"></div>';

	if (isset($_REQUEST['sent'])) {
		$secret=OIDplus::baseConfig()->getValue('RECAPTCHA_PRIVATE', '');
		$response=$_POST["g-recaptcha-response"];
		$verify=file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret}&response={$response}");
		$captcha_success=json_decode($verify);
		if ($captcha_success->success==false) {
			echo '<p><font color="red"><b>CAPTCHA not sucessfully verified</b></font></p>';
			$errors_happened = true;
			$edits_possible = false;
		}
	}
}
?>

<p><u>Step <?php echo $step++; ?>: Authenticate</u></p>

<p>Please enter the administrator password you have entered before.</p>

<p><input type="password" name="admin_password" value=""> (<a href="<?php echo OIDplus::getSystemUrl(); ?>setup/">Forgot?</a>) <?php

if (isset($_REQUEST['sent'])) {
	if (!OIDplusAuthUtils::adminCheckPassword($_REQUEST['admin_password'])) {
		$errors_happened = true;
		$edits_possible = false;
		echo '<font color="red"><b>Wrong password</b></font>';
	}
}

?></p>

<?php
#------------------------
$do_edits = isset($_REQUEST['sent']) && $edits_possible;;
#------------------------

# ---

function step_admin_email($step, $do_edits, &$errors_happened) {
	echo "<p><u>Step $step: Please enter the email address of the system administrator</u></p>";
	echo '<input type="text" name="admin_email" value="';

	$msg = '';
	if (isset($_REQUEST['sent'])) {
		echo htmlentities($_REQUEST['admin_email']);
		if ($do_edits) {
			try {
				OIDplus::config()->setValue('admin_email', $_REQUEST['admin_email']);
			} catch (Exception $e) {
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
	echo "<p><u>Step $step: What title should your Registration Authority / OIDplus instance have?</u></p>";
	echo '<input type="text" name="system_title" value="';

	$msg = '';
	if (isset($_REQUEST['sent'])) {
		echo htmlentities($_REQUEST['system_title']);
		if ($do_edits) {
			try {
				OIDplus::config()->setValue('system_title', $_REQUEST['system_title']);
			} catch (Exception $e) {
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

foreach (OIDplus::getPagePlugins() as $plugin) {
	if ($plugin->implementsFeature('1.3.6.1.4.1.37476.2.5.2.3.1')) {
		$plugin->oobeEntry($step++, $do_edits, $errors_happened);
	}
}

# ---

echo '<p><u>Submit</u></p>';
echo '<input type="submit" value="Save and start OIDplus!">';
echo '</form>';

$pki_status = OIDplus::getPkiStatus();

if ($pki_status) {

echo '<p><u>Your OIDplus system ID (derived from the public key) is:</u></p>';

echo '<b>';
$sysid_oid = OIDplus::getSystemId(true);
if (!$sysid_oid) $sysid_oid = 'unknown';
echo htmlentities($sysid_oid);
echo '</b>';

echo '<p><u>Your public key is</u></p>';

$val = OIDplus::config()->getValue('oidplus_public_key');
if ($val) {
	echo '<pre>'.htmlentities($val).'</pre>';
} else {
	echo '<p>Private/Public key creation failed</p>';
}

}

echo '</body>';

echo '</html>';

$cont = ob_get_contents();
ob_end_clean();

if ($do_edits && !$errors_happened)  {
	OIDplus::config()->setValue('reg_wizard_done', '1');
	header('Location:../../../');
} else {
	echo $cont;
}
