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

include_once __DIR__ . '/functions.inc.php';

if (isset($_SERVER['SERVER_NAME']) && (($_SERVER['SERVER_NAME'] == 'oidplus.viathinksoft.com'))) {

	if ($_POST["action"] == "com.viathinksoft.freeoid.request_freeoid") {
		$handled = true;
		$email = $_POST['email'];

		$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = '".OIDplus::db()->real_escape_string($email)."'");
		if (OIDplus::db()->num_rows($res) > 0) {
			die('This email address already exists.'); // TODO: actually, the person might have something else (like a DOI) and want to have a FreeOID
		}

		if (!oiddb_valid_email($email)) {
			die('Invalid email address');
		}

		if (RECAPTCHA_ENABLED) {
			$secret=RECAPTCHA_PRIVATE;
			$response=$_POST["captcha"];
			$verify=file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret}&response={$response}");
			$captcha_success=json_decode($verify);
			if ($captcha_success->success==false) {
				die('Captcha wrong');
			}
		}

		$timestamp = time();
		$activate_url = OIDplus::system_url() . '?goto='.urlencode('oidplus:com.viathinksoft.freeoid.activate_freeoid$'.$email.'$'.$timestamp.'$'.OIDplus::authUtils()::makeAuthKey('com.viathinksoft.freeoid.activate_freeoid;'.$email.';'.$timestamp));

		$message = file_get_contents(__DIR__ . '/request_msg.tpl');

		// Resolve stuff
		$message = str_replace('{{SYSTEM_URL}}', OIDplus::system_url(), $message);
		$message = str_replace('{{ADMIN_EMAIL}}', OIDPLUS_ADMIN_EMAIL, $message);

		$message = str_replace('{{ACTIVATE_URL}}', $activate_url, $message);

                my_mail($email, OIDplus::config()->systemTitle().' - Free OID request', $message, 'daniel-marschall@viathinksoft.de');

		die("OK");
	}

	if ($_POST["action"] == "com.viathinksoft.freeoid.activate_freeoid") {
		$handled = true;

		$password1 = $_POST['password1'];
		$password2 = $_POST['password2'];
		$email = $_POST['email'];

		$ra_name = $_POST['ra_name'];
		$url = $_POST['url'];
		$title = $_POST['title'];

		$auth = $_POST['auth'];
		$timestamp = $_POST['timestamp'];

		if (!OIDplus::authUtils()::validateAuthKey('com.viathinksoft.freeoid.activate_freeoid;'.$email.';'.$timestamp, $auth)) {
			die('Invalid auth key');
		}

		if ((OIDplus::config()->maxInviteTime() > 0) && (time()-$timestamp > OIDplus::config()->maxInviteTime())) {
			die('Invitation expired!');
		}

		if ($password1 !== $password2) {
			die('Passwords are not equal');
		}

		if (strlen($password1) < OIDplus::config()->minRaPasswordLength()) {
			die('Password is too short. Minimum password length: '.OIDplus::config()->minRaPasswordLength());
		}

		if (empty($ra_name)) {
			die('Please enter your personal name or the name of your group.');
		}

		// 1. step: Add the RA to the database

		$ra = new OIDplusRA($email);
		$ra->register_ra($password1);
		$ra->setRaName($ra_name);

		// 2. step: Add the new OID to the database

		$new_oid = '1.3.6.1.4.1.37476.9000.'.(freeoid_max_id()+1);

		if ((!empty($url)) && (substr($url, 0, 4) != 'http')) $url = 'http://'.$url;

		$description = '<p>'.htmlentities($ra_name).'</p>';
		if (!empty($url)) {
			$description .= '<p>More information at <a href="'.htmlentities($url).'">'.htmlentities($url).'</a></p>';
		}

		if (empty($title)) $title = $ra_name;

		if (!OIDplus::db()->query("insert into ".OIDPLUS_TABLENAME_PREFIX."objects (id, ra_email, parent, title, description, confidential, created) values ('".OIDplus::db()->real_escape_string('oid:'.$new_oid)."', '".OIDplus::db()->real_escape_string($email)."', 'oid:1.3.6.1.4.1.37476.9000', '".OIDplus::db()->real_escape_string($title)."', '".OIDplus::db()->real_escape_string($description)."', 0, now())")) {
			$ra->delete();
			die(OIDplus::db()->error());
		}

		// Send delegation report email

                $message  = "OID delegation report\n";
                $message .= "\n";
                $message .= "OID: ".$new_oid."\n";;
                $message .= "\n";
                $message .= "RA Name: $ra_name\n";
                $message .= "RA eMail: $email\n";
                $message .= "URL for more information: $url\n";
                $message .= "OID Name: $title\n";
                $message .= "\n";
                $message .= "More details: ".OIDplus::system_url()."?goto=oid:$new_oid\n";

                my_mail($email, OIDplus::config()->systemTitle()." - OID $new_oid registered", $message, OIDplus::config()->globalCC(), 'admin@oid-info.com');

		die('OK');
	}

}

