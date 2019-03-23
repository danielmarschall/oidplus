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

if ($_POST["action"] == "change_ra_password") {
	$handled = true;

	$email = $_POST['email'];

	if (!OIDplus::authUtils()::isRaLoggedIn($email) && !OIDplus::authUtils()::isAdminLoggedIn()) {
		die('Authentification error. Please log in as the RA to update its data.');
	}

	$old_password = $_POST['old_password'];
	$password1 = $_POST['new_password1'];
	$password2 = $_POST['new_password2'];

	if ($password1 !== $password2) {
		die('Passwords are not equal');
	}

	if (strlen($password1) < OIDplus::config()->minRaPasswordLength()) {
		die('New password is too short. Minimum password length: '.OIDplus::config()->minRaPasswordLength());
	}

	$ra = new OIDplusRA($email);
	if (!$ra->checkPassword($old_password)) {
		die('Old password incorrect');
	}
	$ra->change_password($password1);

	echo "OK";
}
