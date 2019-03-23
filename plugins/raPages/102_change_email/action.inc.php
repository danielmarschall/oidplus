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

if ($_POST["action"] == "change_ra_email") {
	$handled = true;

	$old_email = $_POST['old_email'];
	$new_email = $_POST['new_email'];

	if (!OIDplus::authUtils()::isRaLoggedIn($old_email) && !OIDplus::authUtils()::isAdminLoggedIn()) {
		die('Authentification error. Please log in as the RA to update its email address.');
	}

	if (!oiddb_valid_email($new_email)) {
		die('eMail address is invalid.');
	}

	$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = '".OIDplus::db()->real_escape_string($old_email)."'");
	if (OIDplus::db()->num_rows($res) == 0) {
		die('eMail address does not exist anymore. It was probably already changed.');
	}

	$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = '".OIDplus::db()->real_escape_string($new_email)."'");
	if (OIDplus::db()->num_rows($res) > 0) {
		die('eMail address is already used by another RA. To merge accounts, please contact the superior RA of your objects and request an owner change of your objects.');
	}

	$timestamp = time();
	$activate_url = OIDplus::system_url() . '?goto='.urlencode('oidplus:activate_new_ra_email$'.$old_email.'$'.$new_email.'$'.$timestamp.'$'.OIDplus::authUtils()::makeAuthKey('activate_new_ra_email;'.$old_email.';'.$new_email.';'.$timestamp));

	$message = file_get_contents(__DIR__ . '/change_request_email.tpl');
	$message = str_replace('{{SYSTEM_URL}}', OIDplus::system_url(), $message);
	$message = str_replace('{{SYSTEM_TITLE}}', OIDplus::config()->systemTitle(), $message);
	$message = str_replace('{{ADMIN_EMAIL}}', OIDPLUS_ADMIN_EMAIL, $message);
	$message = str_replace('{{OLD_EMAIL}}', $old_email, $message);
	$message = str_replace('{{NEW_EMAIL}}', $new_email, $message);
	$message = str_replace('{{ACTIVATE_URL}}', $activate_url, $message);
	my_mail($new_email, OIDplus::config()->systemTitle().' - Change email request', $message);

	die('OK');
} else if ($_POST["action"] == "activate_new_ra_email") {
	$handled = true;

	$old_email = $_POST['old_email'];
	$new_email = $_POST['new_email'];
	$password = $_POST['password'];

	$auth = $_POST['auth'];
	$timestamp = $_POST['timestamp'];

	if (!OIDplus::authUtils()::validateAuthKey('activate_new_ra_email;'.$old_email.';'.$new_email.';'.$timestamp, $auth)) {
		die('Invalid auth key');
	}

	if ((OIDplus::config()->maxEmailChangeTime() > 0) && (time()-$timestamp > OIDplus::config()->maxEmailChangeTime())) {
		die('Activation link expired!');
	}

	$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = '".OIDplus::db()->real_escape_string($old_email)."'");
	if (OIDplus::db()->num_rows($res) == 0) {
		die('eMail address does not exist anymore. It was probably already changed.');
	}

	$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = '".OIDplus::db()->real_escape_string($new_email)."'");
	if (OIDplus::db()->num_rows($res) > 0) {
		die('eMail address is already used by another RA. To merge accounts, please contact the superior RA of your objects and request an owner change of your objects.');
	}

	$ra = new OIDplusRA($old_email);
	if (!$ra->checkPassword($password)) {
		die('Wrong password');
	}

	$ra->change_email($new_email);

	if (!OIDplus::db()->query("update ".OIDPLUS_TABLENAME_PREFIX."objects set ra_email = '".OIDplus::db()->real_escape_string($new_email)."' where ra_email = '".OIDplus::db()->real_escape_string($old_email)."'")) {
		throw new Exception(OIDplus::db()->error());
	}

	OIDplus::authUtils()->raLogout($old_email);
	OIDplus::authUtils()->raLogin($new_email);

	$message = file_get_contents(__DIR__ . '/email_change_confirmation.tpl');
	$message = str_replace('{{SYSTEM_URL}}', OIDplus::system_url(), $message);
	$message = str_replace('{{SYSTEM_TITLE}}', OIDplus::config()->systemTitle(), $message);
	$message = str_replace('{{ADMIN_EMAIL}}', OIDPLUS_ADMIN_EMAIL, $message);
	$message = str_replace('{{OLD_EMAIL}}', $old_email, $message);
	$message = str_replace('{{NEW_EMAIL}}', $new_email, $message);
	my_mail($old_email, OIDplus::config()->systemTitle().' - eMail address changed', $message);

	die('OK');
}
