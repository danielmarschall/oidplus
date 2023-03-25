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
use ViaThinkSoft\OIDplus\OIDplusException;

/**
 * This function is internally called by oidplus_dbupdate_1002().
 * It changes the auth keys A1*# and A2# to VTS-MCF and A3# to BCrypt-MCF.
 * @param OIDplusDatabaseConnection $db is the OIDplusDatabaseConnection class
 * @throws OIDplusException
 */
function oidplus_dbupdate_1002_migrate_ra_passwords(OIDplusDatabaseConnection $db) {
	$res = $db->query("select * from ###ra ");
	while ($row = $res->fetch_array()) {
		$salt = $row['salt'] ?? '';
		$new_auth_key = vts_crypt_convert_from_old_oidplus($row['authkey'], $salt);
		$email = $row['email'];
		if ($new_auth_key !== $row['authkey']) {
			//echo 'Migrate authkey '.$row['authkey'].' to '.$new_auth_key.' for '.$email.'<br><br>';
			$db->query("update ###ra set authkey = ?, salt = ? where email = ?", array($new_auth_key, '', $email));
		}
	}
}

/**
 * This function converts A1*#, A2#, A3# hashes to Crypt compatible hashes.
 * @param string $authkey is old database field value "authkey"
 * @param string $salt is old database field value "salt"
 * @returns string New authkey field (Crypt compatible hash)
 */
function vts_crypt_convert_from_old_oidplus(string $authkey, string $salt): string {
	if (preg_match('@^A1([abcd])#(.+):(.+)$@', $authkey, $m)) {
		// A1a#hashalgo:X with X being H(salt+password) in hex- or rfc4648-base64-notation
		// A1b#hashalgo:X with X being H(password+salt) in hex- or rfc4648-base64-notation
		// A1c#hashalgo:X with X being H(salt+password+salt) in hex- or rfc4648-base64-notation
		// A1d#hashalgo:X with X being H_HMAC(password,salt) in hex- or rfc4648-base64-notation
		$mode = ''; // avoid PHPstan warning
		if ($m[1] == 'a') $mode = 'sp';
		else if ($m[1] == 'b') $mode = 'ps';
		else if ($m[1] == 'c') $mode = 'sps';
		else if ($m[1] == 'd') $mode = 'hmac';
		else assert(false);
		$algo = $m[2];
		$bin_salt = $salt;
		if (($algo == 'sha3-512') || ($algo == 'sha3-384') || ($algo == 'sha512') || ($algo == 'sha384')) {
			$bin_hash = base64_decode($m[3]);
		} else {
			$bin_hash = hex2bin($m[3]);
		}
		return crypt_modular_format_encode(OID_MCF_VTS_V1, $bin_salt, $bin_hash, array('a'=>$algo,'m'=>$mode));
	} else if (preg_match('@^A2#(.+)$@', $authkey, $m)) {
		// A2#X with X being sha3(salt+password) in rfc4648-base64-notation
		$mode = 'sp';
		$algo = 'sha3-512';
		$bin_salt = $salt;
		$bin_hash = base64_decode($m[1]);
		return crypt_modular_format_encode(OID_MCF_VTS_V1, $bin_salt, $bin_hash, array('a'=>$algo,'m'=>$mode));
	} else if (preg_match('@^A3#(.+)$@', $authkey, $m)) {
		// A3#X with X being bcrypt  [not VTS hash!]
		return $m[1];
	} else {
		// Nothing to convert
		return $authkey;
	}
}

/**
 * This function will be called by OIDplusDatabaseConnection.class.php at method afterConnect().
 * @param OIDplusDatabaseConnection $db is the OIDplusDatabaseConnection class
 * @return int new version set
 * @throws \ViaThinkSoft\OIDplus\OIDplusException
 */
function oidplus_dbupdate_1002(OIDplusDatabaseConnection $db): int {
	if ($db->transaction_supported()) $db->transaction_begin();

	if ($db->getSlang()->id() == 'mssql') {
		$db->query("alter table ###ra alter column [authkey] [varchar](250) NULL;");
		oidplus_dbupdate_1002_migrate_ra_passwords($db);
		try {
			$db->query("alter table ###ra drop column [salt];");
		} catch(Exception $e) {}
	}
	else if ($db->getSlang()->id() == 'mysql') {
		$db->query("alter table ###ra modify authkey varchar(250) NULL;");
		oidplus_dbupdate_1002_migrate_ra_passwords($db);
		try {
			$db->query("alter table ###ra drop column salt;");
		} catch(Exception $e) {}
	}
	else if ($db->getSlang()->id() == 'pgsql') {
		$db->query("alter table ###ra alter column authkey type varchar(250)");
		oidplus_dbupdate_1002_migrate_ra_passwords($db);
		try {
			$db->query("alter table ###ra drop column salt");
		} catch(Exception $e) {}
	}
	else if ($db->getSlang()->id() == 'oracle') {
		$db->query("alter table ###ra modify authkey varchar2(250)");
		oidplus_dbupdate_1002_migrate_ra_passwords($db);
		try {
			$db->query("alter table ###ra set unused column salt");
			$db->query("alter table ###ra set drop unused columns");
		} catch(Exception $e) {}
	}
	else if ($db->getSlang()->id() == 'sqlite') {
		oidplus_dbupdate_1002_migrate_ra_passwords($db);
		try {
			$db->query("alter table ###ra drop column salt");
		} catch(Exception $e) {}
	}
	else if ($db->getSlang()->id() == 'access') {
		oidplus_dbupdate_1002_migrate_ra_passwords($db);
		try {
			$db->query("alter table ###ra drop column salt");
		} catch(Exception $e) {}
	}

	// Auth plugins A1 and A2 have been replaced with A5
	// Note that you cannot use `value` in the where clause on MSSQL, because "text and varchar" are incompatible...
	$res = $db->query("SELECT value from ###config where name = 'default_ra_auth_method'");
	if ($row = $res->fetch_array()) {
		if (($row['value'] == 'A1_phpgeneric_salted_hex') || ($row['value'] == 'A2_sha3_salted_base64')) {
			$db->query("UPDATE ###config SET value = 'A5_vts_mcf' WHERE name = 'default_ra_auth_method'");
		}
	}

	$version = 1002;
	$db->query("UPDATE ###config SET value = ? WHERE name = 'database_version'", array($version));

	if ($db->transaction_supported()) $db->transaction_commit();

	return $version;
}
