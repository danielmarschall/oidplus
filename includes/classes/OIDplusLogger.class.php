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

if (!defined('IN_OIDPLUS')) die();

class OIDplusLogger {

	public function log($maskcodes, $event) {

		$users = array();
		$objects = array();

		/*
		\\ras3\daten\htdocs\oidplus_dev\ajax.php(116): OIDplus::logger()->log("RA($email)?/A?", "RA '$email' deleted");
		\\ras3\daten\htdocs\oidplus_dev\ajax.php(136): OIDplus::logger()->log("OID($id)+SUPOIDRA($id)?/A?", "Object '$id' (recursively) deleted");
		\\ras3\daten\htdocs\oidplus_dev\ajax.php(186): OIDplus::logger()->log("OID($id)+SUPOIDRA($id)?/A?", "RA of object '$id' changed from '$current_ra' to '$new_ra'");
		\\ras3\daten\htdocs\oidplus_dev\ajax.php(187): OIDplus::logger()->log("RA($current_ra)!", "Lost ownership of object '$id' due to RA transfer of superior RA / admin.");
		\\ras3\daten\htdocs\oidplus_dev\ajax.php(188): OIDplus::logger()->log("RA($new_ra)!", "Gained ownership of object '$id' due to RA transfer of superior RA / admin.");
		\\ras3\daten\htdocs\oidplus_dev\ajax.php(192): OIDplus::logger()->log("OID($id)+SUPOIDRA($id)?/A?", "Identifiers/Confidential flag of object '$id' updated"); // TODO: Check if they were ACTUALLY updated!
		\\ras3\daten\htdocs\oidplus_dev\ajax.php(235): OIDplus::logger()->log("OID($id)+SUPOIDRA($id)?/A?", "Title/Description of object '$id' updated");
		\\ras3\daten\htdocs\oidplus_dev\ajax.php(273): OIDplus::logger()->log("OID($parent)+OIDRA($parent)?/A?", "Created child object '$id'");
		\\ras3\daten\htdocs\oidplus_dev\ajax.php(274): OIDplus::logger()->log("OID($id)+SUPOIDRA($id)?/A?",      "OIDP/A", "Object '$id' created, given to RA '".(empty($ra_email) ? '(undefined)' : $ra_email)."'");
		\\ras3\daten\htdocs\oidplus_dev\plugins\publicPages\200_viathinksoft_freeoid\plugin.inc.php(60): OIDplus::logger()->log("OID($root_oid)+RA($email)!", "Requested a free OID for email '$email' to be placed into root '$root_oid'");
		\\ras3\daten\htdocs\oidplus_dev\plugins\publicPages\200_viathinksoft_freeoid\plugin.inc.php(120): OIDplus::logger()->log("OID($root_oid)+OIDRA($root_oid)!", "Child OID '$new_oid' added automatically by '$email' (RA Name: '$ra_name')");
		\\ras3\daten\htdocs\oidplus_dev\plugins\publicPages\200_viathinksoft_freeoid\plugin.inc.php(121): OIDplus::logger()->log("OID($new_oid)+RA($email)!",        "Free OID '$new_oid' activated (RA Name: '$ra_name')");
		\\ras3\daten\htdocs\oidplus_dev\plugins\publicPages\091_forgot_password\plugin.inc.php(51): OIDplus::logger()->log("RA($email)!", "A new password for '$email' was requested (forgot password)");
		\\ras3\daten\htdocs\oidplus_dev\plugins\publicPages\091_forgot_password\plugin.inc.php(89): OIDplus::logger()->log("RA($email)!", "RA '$email' has reset his password (forgot passwort)");
		\\ras3\daten\htdocs\oidplus_dev\plugins\publicPages\090_login\plugin.inc.php(50): OIDplus::logger()->log("RA(".$_POST['email'].")!", "RA '".$_POST['email']."' logged in");
		\\ras3\daten\htdocs\oidplus_dev\plugins\publicPages\090_login\plugin.inc.php(65): OIDplus::logger()->log("RA(".$_POST['email'].")!", "RA '".$_POST['email']."' logged out");
		\\ras3\daten\htdocs\oidplus_dev\plugins\publicPages\090_login\plugin.inc.php(86): OIDplus::logger()->log("A!", "Admin logged in");
		\\ras3\daten\htdocs\oidplus_dev\plugins\publicPages\090_login\plugin.inc.php(95): OIDplus::logger()->log("A!", "Admin logged out");
		\\ras3\daten\htdocs\oidplus_dev\plugins\publicPages\092_invite\plugin.inc.php(53): OIDplus::logger()->log("RA($email)!", "RA '$email' has been invited");
		\\ras3\daten\htdocs\oidplus_dev\plugins\publicPages\092_invite\plugin.inc.php(91): OIDplus::logger()->log("RA($email)!", "RA '$email' has been registered due to invitation");
		\\ras3\daten\htdocs\oidplus_dev\plugins\raPages\102_change_email\plugin.inc.php(60): OIDplus::logger()->log("RA($old_email)!+RA($new_email)!", "Requested email change from '$old_email' to '$new_email'");
		\\ras3\daten\htdocs\oidplus_dev\plugins\raPages\102_change_email\plugin.inc.php(123): OIDplus::logger()->log("RA($old_email)!", "Changed email address from '$old_email' to '$new_email'");
		\\ras3\daten\htdocs\oidplus_dev\plugins\raPages\102_change_email\plugin.inc.php(124): OIDplus::logger()->log("RA($new_email)!", "RA '$old_email' has changed its email address to '$new_email'");
		\\ras3\daten\htdocs\oidplus_dev\plugins\raPages\101_change_password\plugin.inc.php(62): OIDplus::logger()->log("RA($email)?/A?", "Password of RA '$email' changed");
		\\ras3\daten\htdocs\oidplus_dev\plugins\raPages\100_edit_contact_data\plugin.inc.php(46): OIDplus::logger()->log("RA($email)?/A?", "Changed RA '$email' contact data/details");
		\\ras3\daten\htdocs\oidplus_dev\plugins\adminPages\110_system_config\plugin.inc.php(49): OIDplus::logger()->log("A?", "Changed system config setting '$name' to '$value'");
		*/

		$maskcodes = str_replace('/', '+', $maskcodes);
		$maskcodes = explode('+', $maskcodes);
		foreach ($maskcodes as $maskcode) {
			// OID(x)	Save log entry into the logbook of: Object "x"
			if (preg_match('@^OID\((.+)\)$@ismU', $maskcode, $m)) {
				$object_id = $m[1];
				$objects[] = $object_id;
			}

			// OIDRA(x)?	Save log entry into the logbook of: Logged in RA of object "x"
			// Replace ? by ! if the entity does not need to be logged in
			else if (preg_match('@^OIDRA\((.+)\)([\?\!])$@ismU', $maskcode, $m)) {
				$object_id         = $m[1];
				$ra_need_login     = $m[2];
				$obj = OIDplusObject::parse($object_id);
				if ($ra_need_login) {
					foreach (OIDplus::authUtils()->loggedInRaList() as $ra) {
						if ($obj->hasWriteRights($ra)) $users[] = $ra->raEmail();
					}
				} else {
					// $users[] = $obj->getRa()->raEmail();
					foreach (OIDplusRA::getAllRAs() as $ra) {
						if ($obj->hasWriteRights($ra)) $users[] = $ra->raEmail();
					}
				}
			}

			// SUPOIDRA(x)?	Save log entry into the logbook of: Logged in RA that owns the superior object of "x"
			// Replace ? by ! if the entity does not need to be logged in
			else if (preg_match('@^SUPOIDRA\((.+)\)([\?\!])$@ismU', $maskcode, $m)) {
				$object_id         = $m[1];
				$ra_need_login     = $m[2];
				$obj = OIDplusObject::parse($object_id);
				if ($ra_need_login) {
					foreach (OIDplus::authUtils()->loggedInRaList() as $ra) {
						if ($obj->hasParentalWriteRights($ra)) $users[] = $ra->raEmail();
					}
				} else {
					// $users[] = $obj->getParent()->getRa()->raEmail();
					foreach (OIDplusRA::getAllRAs() as $ra) {
						if ($obj->hasParentalWriteRights($ra)) $users[] = $ra->raEmail();
					}
				}
			}

			// RA(x)?	Save log entry into the logbook of: Logged in RA "x"
			// Replace ? by ! if the entity does not need to be logged in
			else if (preg_match('@^RA\((.+)\)([\?\!])$@ismU', $maskcode, $m)) {
				$ra_email          = $m[1];
				$ra_need_login     = $m[2];
				if ($ra_need_login && OIDplus::authUtils()->isRaLoggedIn($ra_email)) {
					$users[] = $ra_email;
				} else if (!$ra_need_login) {
					$users[] = $ra_email;
				}
			}

			// A?	Save log entry into the logbook of: A logged in admin
			// Replace ? by ! if the entity does not need to be logged in
			else if (preg_match('@^A([\?\!])$@ismU', $maskcode, $m)) {
				$admin_need_login = $m[1];
				if ($admin_need_login && OIDplus::authUtils()->isAdminLoggedIn()) {
					$users[] = 'admin';
				} else if (!$admin_need_login) {
					$users[] = 'admin';
				}
			}

			// Unexpected
			else {
				throw new Exception("Unexpected logger mask code '$maskcode'");
			}

			// TODO: Log to database
			$users = implode(';', $users);
			if ($users == '') $users = '-';
			$objects = implode(';', $objects);
			if ($objects == '') $objects = '-';
			file_put_contents(__DIR__ . '/../../logtest.log', "$objects / $users / $event\n", FILE_APPEND);
		}
	}
}
