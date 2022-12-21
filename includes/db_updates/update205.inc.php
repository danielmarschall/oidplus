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
 * This function will be called by OIDplusDatabaseConnection.class.php at method afterConnect().
 * @param OIDplusDatabaseConnection $db is the OIDplusDatabaseConnection class
 * @return int new version set
 * @throws \ViaThinkSoft\OIDplus\OIDplusException
 */
function oidplus_dbupdate_205(OIDplusDatabaseConnection $db) {
	// Note: We update to version 1000, because we want to intentionally break older versions of OIDplus
	// if they try to connect to a database that is newer than their own program files. Older versions
	// of OIDplus checked for DB version 200..999 and failed if the version is outside this range.
	// The main reason is that the new version of OIDplus added encrypted private keys,
	// and if an older version of OIDplus would try to connect to such a database,
	// then it would re-generate the keys (and therefore destroy the existing SystemID).
	$version = 1000;
	$db->query("UPDATE ###config SET value = ? WHERE name = 'database_version'", array($version));

	return $version;
}
