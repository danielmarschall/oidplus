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

/**
 * This function will be called by OIDplusDatabaseConnection.class.php at method afterConnect().
 * @param OIDplusDatabaseConnection $db is the OIDplusDatabaseConnection class
 * @return int new version set
 * @throws OIDplusException
 */
function oidplus_dbupdate_1001(OIDplusDatabaseConnection $db) {

	// Change collation so that objects like FourCC can be case-sensitive
	if ($db->getSlang()->id() == 'mysql') {
		$db->query("ALTER TABLE ###asn1id     CHANGE `oid`    `oid`    varchar(255) NOT NULL     COLLATE utf8_bin;");
		$db->query("ALTER TABLE ###iri        CHANGE `oid`    `oid`    varchar(255) NOT NULL     COLLATE utf8_bin;");
		$db->query("ALTER TABLE ###objects    CHANGE `id`     `id`     varchar(255) NOT NULL     COLLATE utf8_bin;");
		$db->query("ALTER TABLE ###objects    CHANGE `parent` `parent` varchar(255) DEFAULT NULL COLLATE utf8_bin;");
		$db->query("ALTER TABLE ###log_object CHANGE `object` `object` varchar(255) NOT NULL     COLLATE utf8_bin;");
	} else if ($db->getSlang()->id() == 'mssql') {
		$db->query("ALTER TABLE ###asn1id     ALTER COLUMN [oid]    varchar(255) COLLATE German_PhoneBook_CS_AS NOT NULL;");
		$db->query("ALTER TABLE ###iri        ALTER COLUMN [oid]    varchar(255) COLLATE German_PhoneBook_CS_AS NOT NULL;");
		$db->query("ALTER TABLE ###objects    ALTER COLUMN [id]     varchar(255) COLLATE German_PhoneBook_CS_AS NOT NULL;");
		$db->query("ALTER TABLE ###objects    ALTER COLUMN [parent] varchar(255) COLLATE German_PhoneBook_CS_AS NULL    ;");
		$db->query("ALTER TABLE ###log_object ALTER COLUMN [object] varchar(255) COLLATE German_PhoneBook_CS_AS NOT NULL;");
	} else if ($db->getSlang()->id() == 'oracle') {
		// On the Oracle DeveloperDays 2019 VM, the default behavior is case-sensitive.
		// Let's hope that this is true for all OIDplus environments
		// DM 31.05.2022 : Reproduction on Ubuntu+PostgreSQL also successful. The default is case-sensitive, like we want.
	} else if ($db->getSlang()->id() == 'pgsql') {
		// It looks like PgSQL is case-sensitive by default
		// see https://stackoverflow.com/questions/18807276/how-to-make-my-postgresql-database-use-a-case-insensitive-collation
		// DM 31.05.2022 : Reproduction on Ubuntu+PostgreSQL successful. The default is case-sensitive, like we want.
	} else if ($db->getSlang()->id() == 'access') {
		// TODO: Implement
		// However, this is not important, because Access is not yet correctly implemented anyway
	} else if ($db->getSlang()->id() == 'sqlite') {
		// It looks like SQLite is case-sensitive by default
		// https://stackoverflow.com/questions/973541/how-to-set-sqlite3-to-be-case-insensitive-when-string-comparing
		// DM 05.06.2022 : Reproduction on Ubuntu successful. The default is case-sensitive, like we want.
	} else {
		// This should not happen
	}

	$version = 1001;
	$db->query("UPDATE ###config SET value = ? WHERE name = 'database_version'", array($version));

	return $version;
}
