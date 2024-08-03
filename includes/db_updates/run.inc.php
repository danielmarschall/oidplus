<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2023 Daniel Marschall, ViaThinkSoft
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

use ViaThinkSoft\OIDplus\Core\OIDplusDatabaseConnection;
use ViaThinkSoft\OIDplus\Core\OIDplusConfigInitializationException;
use ViaThinkSoft\OIDplus\Core\OIDplusException;

/**
 * This function will be called by OIDplusDatabaseConnection.class.php at method afterConnect().
 * @param OIDplusDatabaseConnection $db is the OIDplusDatabaseConnection class
 * @throws \ViaThinkSoft\OIDplus\Core\OIDplusException
 */
function oidplus_dbupdate(OIDplusDatabaseConnection $db) {
	// Detect database version
	// Also included a non-ntext field in the query, see https://bugs.php.net/bug.php?id=72503
	$res = $db->query("SELECT name, value FROM ###config WHERE name = 'database_version'");
	$row = $res->fetch_array();
	if ($row == null) {
		// Note: The config setting "database_version" is inserted in plugins/*/sqlSlang/*/sql/struct.sql, not in the OIDplus core init
		throw new OIDplusConfigInitializationException(_L('Cannot determine database version (the entry "database_version" inside the table "###config" is probably missing)'));
	}
	$version = $row['value'];
	if (!is_numeric($version)) {
		throw new OIDplusConfigInitializationException(_L('Entry "database_version" inside the table "###config" seems to be wrong (needs to be a number)'));
	}

	// Upgrade from old versions
	try {
		if ($version == 200) {
			// Update 200 => 201
			require_once __DIR__.'/update200.inc.php';
			$version = oidplus_dbupdate_200($db);
		}
		if ($version == 201) {
			// Update 201 => 202
			require_once __DIR__.'/update201.inc.php';
			$version = oidplus_dbupdate_201($db);
		}
		if ($version == 202) {
			// Update 202 => 203
			require_once __DIR__.'/update202.inc.php';
			$version = oidplus_dbupdate_202($db);
		}
		if ($version == 203) {
			// Update 203 => 204
			require_once __DIR__.'/update203.inc.php';
			$version = oidplus_dbupdate_203($db);
		}
		if ($version == 204) {
			// Update 204 => 205
			require_once __DIR__.'/update204.inc.php';
			$version = oidplus_dbupdate_204($db);
		}
		if ($version == 205) {
			// Update 205 => 1000
			require_once __DIR__.'/update205.inc.php';
			$version = oidplus_dbupdate_205($db);
		}
		if ($version == 1000) {
			// Update 1000 => 1001
			require_once __DIR__.'/update1001.inc.php';
			$version = oidplus_dbupdate_1001($db);
		}
		if ($version == 1001) {
			// Update 1001 => 1002
			require_once __DIR__.'/update1002.inc.php';
			$version = oidplus_dbupdate_1002($db);
		}
	} catch (\Exception $e) {
		throw new OIDplusException(_L('Database update from version %1 failed: %2',$version,$e->getMessage()));
	}

	// Don't allow if the database version if newer than we expect
	if ($version > 1002) {
		throw new OIDplusException(_L('The version of the database is newer than the program version. Please upgrade your program version.'));
	}
}
