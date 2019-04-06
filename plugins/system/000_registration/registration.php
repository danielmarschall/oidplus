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

OIDplus::db()->set_charset("UTF8");
OIDplus::db()->query("SET NAMES 'utf8'");

ob_start();

?><!DOCTYPE html>
<html lang="en">

<head>
	<title>OIDplus Setup</title>
	<link rel="stylesheet" href="../../../setup/setup.css">
</head>

<body>

<h1>OIDplus Setup</h1>

<p>Your database settings are correct.</p>

<p>The following settings need to be configured once.<br>
After setup is complete, you can change all these settings if required.</p>

<form method="POST" action="registration.php">
<input type="hidden" name="sent" value="1">

<p><u>Step 1: Authentificate</u></p>

<p>Please enter the administrator password you have entered before.</p>

<p><input type="password" name="admin_password" value=""> (<a href="<?php echo OIDplus::system_url(); ?>setup/">Forgot?</a>) <?php

$do_edits = false;
if (isset($_REQUEST['sent'])) {
	if (OIDplusAuthUtils::adminCheckPassword($_REQUEST['admin_password'])) {
		$do_edits = true;
	} else {
		$do_edits = false;
		echo '<font color="red"><b>Wrong password</b></font>';
	}
} else {
	$do_edits = false;
}

?></p>

<p><u>Step 2: Enable/Disable object type plugins</u></p>

<p>Which object types do you want to manage using OIDplus?</p>

<?php

$enabled_ary = array();

foreach (OIDplus::getRegisteredObjectTypes() as $ot) {
	echo '<input type="checkbox" name="enable_ot_'.$ot::ns().'" id="enable_ot_'.$ot::ns().'"';
	if (isset($_REQUEST['sent'])) {
	        if (isset($_REQUEST['enable_ot_'.$ot::ns()])) {
			echo ' checked';
			$enabled_ary[] = $ot::ns();
		}
	} else {
	        echo ' checked';
	}
	echo '> <label for="enable_ot_'.$ot::ns().'">'.htmlentities($ot::objectTypeTitle()).'</label><br>';
}

foreach (OIDplus::getDisabledObjectTypes() as $ot) {
	echo '<input type="checkbox" name="enable_ot_'.$ot::ns().'" id="enable_ot_'.$ot::ns().'"';
	if (isset($_REQUEST['sent'])) {
	        if (isset($_REQUEST['enable_ot_'.$ot::ns()])) {
			echo ' checked';
			$enabled_ary[] = $ot::ns();
		}
	} else {
	        echo ''; // <-- difference
	}
	echo '> <label for="enable_ot_'.$ot::ns().'">'.htmlentities($ot::objectTypeTitle()).'</label><br>';
}

if ($do_edits) {
	OIDplus::config()->setValue('objecttypes_enabled', implode(';', $enabled_ary));
}

?>

<p><u>Step 3: Automatic Publishing</u></p>

<?php

if (!function_exists('openssl_sign')) {
	'<p>OpenSSL plugin is missing in PHP. You cannot register your OIDplus instance.</p>';
} else {

if (OIDplus::config()->exists('oidinfo_export_protected')) {

?>

<input type="checkbox" name="register_oidinfo" id="register_oidinfo" <?php
if (isset($_REQUEST['sent'])) {
	if (isset($_REQUEST['register_oidinfo'])) echo ' checked';
} else {
	if (!OIDplus::config()->getValue('oidinfo_export_protected') || !OIDplus::config()->getValue('registration_done')) {
		echo ' checked';
	} else {
		echo '';
	}
}
if ($do_edits) {
	if (isset($_REQUEST['register_oidinfo'])) {
		OIDplus::config()->setValue('oidinfo_export_protected', '0');
	} else {
		OIDplus::config()->setValue('oidinfo_export_protected', '1');
	}
}
?>> <label for="register_oidinfo">Would you like to enable the automatic transfer of the Object Identifiers you create on this system to the
OID Repository <a href="http://oid-info.com/" target="_blank">oid-info.com</a>?</label><br>
<i>Privacy information:</i>
You can always disable the automatic transmission in the admin control panel of OIDplus,<br>
and you can demand the deletion of already published object identifiers by writing an email
to the <a href="mailto:admin@oid-info.com">OID Repository Webmaster</a>.<br>
Please mention your system ID below.</p>

<?php
}
?>

<input type="checkbox" name="register_viathinksoft" id="register_viathinksoft" <?php
if (isset($_REQUEST['sent'])) {
        if (isset($_REQUEST['register_viathinksoft'])) echo ' checked';
} else {
	if (OIDplus::config()->getValue('reg_enabled') || !OIDplus::config()->getValue('registration_done')) {
		echo ' checked';
	} else {
		echo '';
	}
}
if ($do_edits) {
	if (isset($_REQUEST['register_viathinksoft'])) {
		OIDplus::config()->setValue('reg_enabled', '1');
	} else {
		OIDplus::config()->setValue('reg_enabled', '0');
	}
}
?>> <label for="register_viathinksoft">Would you like to register your system to the ViaThinkSoft directory?</label><br>
This means that the URL of your OIDplus system together with its public key (see below) is published to the <a href="https://oidplus.viathinksoft.com/oidplus/?goto=oid:1.3.6.1.4.1.37476.30.9" target="_blank">OIDplus instance directory</a> and <a href="http://www.oid-info.com/get/1.3.6.1.4.1.37476.30.9">oid-info.com</a>.<br>
The publication of your public key allows users to verify integrity of your data. The registration is also the requirement<br>
for the automatic XML export of Object Identifiers to oid-info.com.<br>
<i>Privacy information:</i>
You can always revoke this permission as well as demand the deletion of<br>
the public directory entries by writing an email to <a href="mailto:info@daniel-marschall.de">ViaThinkSoft</a> (for the directory entry at ViaThinkSoft) and<br>
the <a href="mailto:admin@oid-info.com">OID Repository Webmaster</a> (for the removal of the directory entry at oid-info.com).
Please mention your system ID below.</p>

<?php

}

?>

<p><u>Submit</u></p>

<input type="submit" value="Save and start OIDplus!">

</form>

<?php

if (function_exists('openssl_sign')) {

?>

<p><u>Your OIDplus system ID (derived from the public key) is:</u></p>

1.3.6.1.4.1.37476.30.9.<b><?php
echo htmlentities(OIDplus::system_id());
?></b>

<p><u>Your public key is</u></p>

<?php

echo '<pre>'.htmlentities(OIDplus::config()->getValue('oidplus_public_key')).'</pre>';

}

?>

</body>

</html>

<?php

$cont = ob_get_contents();
ob_end_clean();

if ($do_edits) {
	OIDplus::config()->setValue('registration_done', '1');
	header('Location:../../../');
} else {
	echo $cont;
}
