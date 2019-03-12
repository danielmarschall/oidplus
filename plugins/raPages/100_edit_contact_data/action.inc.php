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

if ($_POST["action"] == "change_ra_data") {
	$handled = true;

	$error = false;
	$email = $_POST['email'];

	if (!OIDplus::authUtils()::isRaLoggedIn($email) && !OIDplus::authUtils()::isAdminLoggedIn()) {
		die('Authentification error. Please log in as the RA to update its data.');
	}

	if (!OIDplus::db()->query("UPDATE ".OIDPLUS_TABLENAME_PREFIX."ra ".
		"SET ".
		"updated = now(), ".
		"ra_name = '".OIDplus::db()->real_escape_string($_POST['ra_name'])."', ".
		"organization = '".OIDplus::db()->real_escape_string($_POST['organization'])."', ".
		"office = '".OIDplus::db()->real_escape_string($_POST['office'])."', ".
		"personal_name = '".OIDplus::db()->real_escape_string($_POST['personal_name'])."', ".
		"privacy = ".OIDplus::db()->escape_bool($_POST['privacy']).", ".
		"street = '".OIDplus::db()->real_escape_string($_POST['street'])."', ".
		"zip_town = '".OIDplus::db()->real_escape_string($_POST['zip_town'])."', ".
		"country = '".OIDplus::db()->real_escape_string($_POST['country'])."', ".
		"phone = '".OIDplus::db()->real_escape_string($_POST['phone'])."', ".
		"mobile = '".OIDplus::db()->real_escape_string($_POST['mobile'])."', ".
		"fax = '".OIDplus::db()->real_escape_string($_POST['fax'])."' ".
		"WHERE email = '".OIDplus::db()->real_escape_string($email)."'"))
	{
		die(OIDplus::db()->error());
	}

	if (!$error) echo "OK";
}
