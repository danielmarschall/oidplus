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

OIDplus::init(false);

OIDplus::db()->set_charset("UTF8");
OIDplus::db()->query("SET NAMES 'utf8'");

header('Content-Type:text/plain; charset=utf-8');

try {
	if (isset($_POST["action"])) {

		$handled = false;

		// === Plugins ===

		$ary = glob(__DIR__ . '/plugins/publicPages/'.'*'.'/action.inc.php');
		sort($ary);
		foreach ($ary as $a) include $a;

		$ary = glob(__DIR__ . '/plugins/adminPages/'.'*'.'/action.inc.php');
		sort($ary);
		foreach ($ary as $a) include $a;

		$ary = glob(__DIR__ . '/plugins/raPages/'.'*'.'/action.inc.php');
		sort($ary);
		foreach ($ary as $a) include $a;

		// === INVITATION ===

		if ($_POST["action"] == "invite_ra") {
			$handled = true;
			$email = $_POST['email'];

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
			$activate_url = OIDplus::system_url() . '?goto='.urlencode('oidplus:activate_ra$'.$email.'$'.$timestamp.'$'.OIDplus::authUtils()::makeAuthKey('activate_ra;'.$email.';'.$timestamp));

			$message = OIDplus::gui()::getInvitationText($_POST['email']);
			$message = str_replace('{{ACTIVATE_URL}}', $activate_url, $message);

			my_mail($email, OIDplus::config()->systemTitle().' - Invitation', $message, OIDplus::config()->globalCC());

			die("OK");
		}

		if ($_POST["action"] == "activate_ra") {
			$handled = true;

			$password1 = $_POST['password1'];
			$password2 = $_POST['password2'];
			$email = $_POST['email'];
			$auth = $_POST['auth'];
			$timestamp = $_POST['timestamp'];

			if (!OIDplus::authUtils()::validateAuthKey('activate_ra;'.$email.';'.$timestamp, $auth)) {
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

			$ra = new OIDplusRA($email);
			$ra->register_ra($password1);

			die('OK');
		}

		// === FORGOT PASSWORD ===

		if ($_POST["action"] == "forgot_password") {
			$handled = true;

			$email = $_POST['email'];

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
			$activate_url = OIDplus::system_url() . '?goto='.urlencode('oidplus:reset_password$'.$email.'$'.$timestamp.'$'.OIDplus::authUtils()::makeAuthKey('reset_password;'.$email.';'.$timestamp));

			$message = OIDplus::gui()::getForgotPasswordText($_POST['email']);
			$message = str_replace('{{ACTIVATE_URL}}', $activate_url, $message);

			my_mail($email, OIDplus::config()->systemTitle().' - Password reset request', $message, OIDplus::config()->globalCC());

			die("OK");
		}

		if ($_POST["action"] == "reset_password") {
			$handled = true;

			$password1 = $_POST['password1'];
			$password2 = $_POST['password2'];
			$email = $_POST['email'];
			$auth = $_POST['auth'];
			$timestamp = $_POST['timestamp'];

			if (!OIDplus::authUtils()::validateAuthKey('reset_password;'.$email.';'.$timestamp, $auth)) {
				die('Invalid auth key');
			}

			if ((OIDplus::config()->maxPasswordResetTime() > 0) && (time()-$timestamp > OIDplus::config()->maxPasswordResetTime())) {
				die('Invitation expired!');
			}

			if ($password1 !== $password2) {
				die('Passwords are not equal');
			}

			if (strlen($password1) < OIDplus::config()->minRaPasswordLength()) {
				die('Password is too short. Minimum password length: '.OIDplus::config()->minRaPasswordLength());
			}

			$ra = new OIDplusRA($email);
			$ra->change_password($password1);

			die('OK');
		}

		// === Admin / RA actions ===

		if ($_POST["action"] == "delete_ra") {
			$handled = true;

			$email = $_POST['email'];

			$ra_logged_in = OIDplus::authUtils()::isRaLoggedIn($email);

			if (!OIDplus::authUtils()::isAdminLoggedIn() && !$ra_logged_in) {
				die('You need to log in as administrator');
			}

			if ($ra_logged_in) OIDplus::authUtils()::raLogout($email);

			$ra = new OIDplusRA($email);
			$ra->delete();

			die('OK');
		}

		// === OID CRUD ===

		if ($_POST["action"] == "Delete") {
			$handled = true;

			$id = $_POST['id'];
			$obj = OIDplusObject::parse($_POST['id']);

			// Prüfen ob zugelassen
			if (!$obj->userHasParentalWriteRights()) die('Authentification error. Please log in as the superior RA to delete this OID.');

			// Delete object
			OIDplus::db()->query("delete from ".OIDPLUS_TABLENAME_PREFIX."objects where id = '".OIDplus::db()->real_escape_string($id)."'");

			// Delete orphan stuff
			$test = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where parent <> 'oid:' and parent like 'oid:%' and parent not in (select id from ".OIDPLUS_TABLENAME_PREFIX."objects where id like 'oid:%');");
			if (OIDplus::db()->num_rows($test) > 0) {
				OIDplus::db()->query("delete from ".OIDPLUS_TABLENAME_PREFIX."objects where parent <> 'oid:' and parent like 'oid:%' and parent not in (select id from ".OIDPLUS_TABLENAME_PREFIX."objects where id like 'oid:%');");
			}
			OIDplus::db()->query("delete from ".OIDPLUS_TABLENAME_PREFIX."asn1id where well_known <> 1 and oid not in (select id from ".OIDPLUS_TABLENAME_PREFIX."objects where id like 'oid:%');");
			OIDplus::db()->query("delete from ".OIDPLUS_TABLENAME_PREFIX."iri    where well_known <> 1 and oid not in (select id from ".OIDPLUS_TABLENAME_PREFIX."objects where id like 'oid:%');");

			echo "OK";
		}
		if ($_POST["action"] == "Update") {
			$handled = true;

			// Es wird validiert: ra email, asn1 ids, iri ids

			$id = $_POST['id'];
			$obj = OIDplusObject::parse($_POST['id']);

			// Validate RA email address
			$new_ra = $_POST['ra_email'];
			if (!empty($new_ra) && !oiddb_valid_email($new_ra)) {
				die('Invalid RA email address');
			}

			// Prüfen ob zugelassen
			if (!$obj->userHasParentalWriteRights()) die('Authentification error. Please log in as the superior RA to update this OID.');

			// RA ändern (rekursiv)
			$res = OIDplus::db()->query("select ra_email from ".OIDPLUS_TABLENAME_PREFIX."objects where id = '".OIDplus::db()->real_escape_string($id)."'");
			$row = OIDplus::db()->fetch_array($res);
			$current_ra = $row['ra_email'];

			if ($new_ra != $current_ra) _ra_change_rec($id, $current_ra, $new_ra); // Inherited RAs rekursiv mitändern

			// Replace ASN.1 und IRI IDs
			if ($obj::ns() == 'oid') {
				$oid = $obj;

				$ids = ($_POST['iris'] == '') ? array() : explode(',',$_POST['iris']);
				$ids = array_map('trim',$ids);
				$oid->replaceIris($ids);

				$ids = ($_POST['asn1ids'] == '') ? array() : explode(',',$_POST['asn1ids']);
				$ids = array_map('trim',$ids);
				$oid->replaceAsn1Ids($ids);
			}

			$confidential = $_POST['confidential'] == 'true' ? '1' : '0';
			if (!OIDplus::db()->query("UPDATE ".OIDPLUS_TABLENAME_PREFIX."objects SET confidential = ".OIDplus::db()->real_escape_string($confidential).", updated = now() WHERE id = '".OIDplus::db()->real_escape_string($id)."'")) {
				die('Error at setting confidential flag:' . OIDplus::db()->error());
			}

			echo "OK";

			if (!empty($new_ra)) {
				$res = OIDplus::db()->query("select ra_name from ".OIDPLUS_TABLENAME_PREFIX."ra where email = '".OIDplus::db()->real_escape_string($new_ra)."'");
				if (OIDplus::db()->num_rows($res) == 0) echo " (RaNotInDatabase)"; // do not change
			}
		}
		if ($_POST["action"] == "Update2") {
			$handled = true;

			$id = $_POST['id'];
			$obj = OIDplusObject::parse($_POST['id']);

			// Prüfen ob zugelassen
			if (!$obj->userHasWriteRights()) die('Authentification error. Please log in as the RA to update this OID.');

			if (!OIDplus::db()->query("UPDATE ".OIDPLUS_TABLENAME_PREFIX."objects SET title = '".OIDplus::db()->real_escape_string($_POST['title'])."', description = '".OIDplus::db()->real_escape_string($_POST['description'])."', updated = now() WHERE id = '".OIDplus::db()->real_escape_string($id)."'")) {
				die(OIDplus::db()->error());
			}

			echo "OK";
		}
		if ($_POST["action"] == "Insert") {
			$handled = true;

			// Es wird validiert: ID, ra email, asn1 ids, iri ids

			// Check if you have write rights on the parent (to create a new object)
			$objParent = OIDplusObject::parse($_POST['parent']);
			if (!$objParent->userHasWriteRights()) die('Authentification error. Please log in as the correct RA to insert an OID at this arc.');

			// Check if the ID is valid
			if ($_POST['id'] == '') die('ID may not be empty');

			// Absoluten OID namen bestimmen
			// Note: At addString() and parse(), the syntax of the ID will be checked
			$id = $objParent->addString($_POST['id']);
			$obj = OIDplusObject::parse($id);

			// Superior RA Änderung durchführen
			$parent = $_POST['parent'];
			$ra_email = $_POST['ra_email'];
			if (!empty($ra_email) && !oiddb_valid_email($ra_email)) {
				die('Invalid RA email address');
			}
			$confidential = $_POST['confidential'] == 'true' ? '1' : '0';
			if (!OIDplus::db()->query("INSERT INTO ".OIDPLUS_TABLENAME_PREFIX."objects (id, parent, ra_email, confidential, created) VALUES ('".OIDplus::db()->real_escape_string($id)."', '".OIDplus::db()->real_escape_string($parent)."', '".OIDplus::db()->real_escape_string($ra_email)."', ".OIDplus::db()->real_escape_string($confidential).", now())")) {
				die(OIDplus::db()->error());
			}

			// Set ASN.1 und IRI IDs
			if ($obj::ns() == 'oid') {
				$oid = $obj;

				$ids = ($_POST['iris'] == '') ? array() : explode(',',$_POST['iris']);
				$ids = array_map('trim',$ids);
				$oid->replaceIris($ids);

				$ids = ($_POST['asn1ids'] == '') ? array() : explode(',',$_POST['asn1ids']);
				$ids = array_map('trim',$ids);
				$oid->replaceAsn1Ids($ids);
			}

			echo "OK";

			if (!empty($ra_email)) {
				$res = OIDplus::db()->query("select ra_name from ".OIDPLUS_TABLENAME_PREFIX."ra where email = '".OIDplus::db()->real_escape_string($ra_email)."'");
				if (OIDplus::db()->num_rows($res) == 0) echo " (RaNotInDatabase)"; // do not change
			}
		}

		// === RA LOGIN/LOGOUT ===

		if ($_POST["action"] == "ra_login") {
			$handled = true;

			$ra = new OIDplusRA($_POST['email']);

			if (RECAPTCHA_ENABLED) {
				$secret=RECAPTCHA_PRIVATE;
				$response=$_POST["captcha"];
				$verify=file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret}&response={$response}");
				$captcha_success=json_decode($verify);
				if ($captcha_success->success==false) {
					die('Captcha wrong');
				}
			}

			if ($ra->checkPassword($_POST['password'])) {
				OIDplus::authUtils()::raLogin($_POST['email']);

				if (!OIDplus::db()->query("UPDATE ".OIDPLUS_TABLENAME_PREFIX."ra set last_login = now() where email = '".OIDplus::db()->real_escape_string($_POST['email'])."'")) {
					die(OIDplus::db()->error());
				}

				echo "OK";
			} else {
				echo "Wrong password";
			}
		}
		if ($_POST["action"] == "ra_logout") {
			$handled = true;
			OIDplus::authUtils()::raLogout($_POST['email']);
			echo "OK";
		}

		// === ADMIN LOGIN/LOGOUT ===

		if ($_POST["action"] == "admin_login") {
			$handled = true;

			if (RECAPTCHA_ENABLED) {
				$secret=RECAPTCHA_PRIVATE;
				$response=$_POST["captcha"];
				$verify=file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret}&response={$response}");
				$captcha_success=json_decode($verify);
				if ($captcha_success->success==false) {
					die('Captcha wrong');
				}
			}

			if (OIDplus::authUtils()::adminCheckPassword($_POST['password'])) {
				OIDplus::authUtils()::adminLogin();
				echo "OK";
			} else {
				echo "Wrong password";
			}
		}
		if ($_POST["action"] == "admin_logout") {
			$handled = true;
			OIDplus::authUtils()::adminLogout();
			echo "OK";
		}

		// === Not found ===

		if (!$handled) {
			die('Invalid action ID');
		}
	}
} catch (Exception $e) {
	echo $e->getMessage();
}

# ---

function _ra_change_rec($id, $old_ra, $new_ra) {
	OIDplus::db()->query("update ".OIDPLUS_TABLENAME_PREFIX."objects set ra_email = '".OIDplus::db()->real_escape_string($new_ra)."', updated = now() where id = '".OIDplus::db()->real_escape_string($id)."' and ifnull(ra_email,'') = '".OIDplus::db()->real_escape_string($old_ra)."'");

	$res = OIDplus::db()->query("select id from ".OIDPLUS_TABLENAME_PREFIX."objects where parent = '".OIDplus::db()->real_escape_string($id)."' and ifnull(ra_email,'') = '".OIDplus::db()->real_escape_string($old_ra)."'");
	while ($row = OIDplus::db()->fetch_array($res)) {
		_ra_change_rec($row['id'], $old_ra, $new_ra);
	}
}
