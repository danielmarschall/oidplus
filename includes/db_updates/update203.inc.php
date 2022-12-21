<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2021 Daniel Marschall, ViaThinkSoft
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
function oidplus_dbupdate_203(OIDplusDatabaseConnection $db) {
	if ($db->transaction_supported()) $db->transaction_begin();

	if ($db->getSlang()->id() == 'mssql') {
		$db->query("ALTER TABLE ###log_object ADD severity [int]");
		$db->query("ALTER TABLE ###log_user ADD severity [int]");
	}
	else if ($db->getSlang()->id() == 'mysql') {
		$db->query("ALTER TABLE ###log_object ADD severity int(11)");
		$db->query("ALTER TABLE ###log_user ADD severity int(11)");
	}
	else if ($db->getSlang()->id() == 'pgsql') {
		$db->query("ALTER TABLE ###log_object ADD severity integer");
		$db->query("ALTER TABLE ###log_user ADD severity integer");
	}
	else if ($db->getSlang()->id() == 'sqlite') {
		$db->query("ALTER TABLE ###log_object ADD severity integer");
		$db->query("ALTER TABLE ###log_user ADD severity integer");
	}

	$version = 204;
	$db->query("UPDATE ###config SET value = ? WHERE name = 'database_version'", array($version));

	if ($db->transaction_supported()) $db->transaction_commit();

	return $version;
}
