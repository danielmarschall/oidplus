<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2022 Daniel Marschall, ViaThinkSoft
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

use ViaThinkSoft\OIDplus\OIDplusDatabaseConnection;

/**
 * This function is internally called by oidplus_dbupdate_1002().
 * It changes the auth keys A1*# and A2# to VTS-MCF and A3# to BCrypt-MCF.
 * @param OIDplusDatabaseConnection $db is the OIDplusDatabaseConnection class
 */
function oidplus_dbupdate_1002_migrate_ra_passwords(OIDplusDatabaseConnection $db) {
	$res = $db->query("select * from ###ra ");
	while ($row = $res->fetch_array()) {
		$new_auth_key = vts_crypt_convert_from_old_oidplus($row['authkey'], $row['salt']);
		$email = $row['email'];
		if ($new_auth_key !== $row['authkey']) {
			//echo 'Migrate authkey '.$row['authkey'].' to '.$new_auth_key.' for '.$email.'<br><br>';
			$db->query("update ###ra set authkey = ?, salt = ? where email = ?", array($new_auth_key, '', $email));
		}
	}
}

/**
 * This function will be called by OIDplusDatabaseConnection.class.php at method afterConnect().
 * @param OIDplusDatabaseConnection $db is the OIDplusDatabaseConnection class
 * @return int new version set
 * @throws \ViaThinkSoft\OIDplus\OIDplusException
 */
function oidplus_dbupdate_1002(OIDplusDatabaseConnection $db) {

	if ($db->getSlang()->id() == 'mssql') {
		$db->query("alter table ###ra alter column [authkey] [varchar](250) NULL;");
		oidplus_dbupdate_1002_migrate_ra_passwords($db);
		$db->query("alter table ###ra drop column [salt];");
	}
	else if ($db->getSlang()->id() == 'mysql') {
		$db->query("alter table ###ra modify authkey varchar(250) NULL;");
		oidplus_dbupdate_1002_migrate_ra_passwords($db);
		$db->query("alter table ###ra drop column salt;");
	}
	else if ($db->getSlang()->id() == 'pgsql') {
		$db->query("alter table ###ra alter column authkey type varchar(250)");
		oidplus_dbupdate_1002_migrate_ra_passwords($db);
		$db->query("alter table ###ra drop column salt");
	}
	else if ($db->getSlang()->id() == 'oracle') {
		$db->query("alter table ###ra modify authkey varchar2(250)");
		oidplus_dbupdate_1002_migrate_ra_passwords($db);
		$db->query("alter table ###ra set unused column salt");
		$db->query("alter table ###ra set drop unused columns");
	}
	else if ($db->getSlang()->id() == 'sqlite') {
		oidplus_dbupdate_1002_migrate_ra_passwords($db);
		$db->query("alter table ###ra drop column salt");
	}
	else if ($db->getSlang()->id() == 'access') {
		oidplus_dbupdate_1002_migrate_ra_passwords($db);
		$db->query("alter table ###ra drop column salt");
	}

	// Auth plugins A1 and A2 have been replaced with A5
	$db->query("UPDATE ###config SET value = ? WHERE name = 'default_ra_auth_method' and value = ?", array('A5_vts_mcf', 'A1_phpgeneric_salted_hex'));
	$db->query("UPDATE ###config SET value = ? WHERE name = 'default_ra_auth_method' and value = ?", array('A5_vts_mcf', 'A2_sha3_salted_base64'));

	$version = 1002;
	$db->query("UPDATE ###config SET value = ? WHERE name = 'database_version'", array($version));

	return $version;
}
