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

/**
 * This function will be called by OIDplusDatabaseConnection.class.php at method afterConnect().
 * @param OIDplusDatabaseConnection $db is the OIDplusDatabaseConnection class
 * @param string $version is the current version (this script MUST increase the number by 1 when it is done)
 * @throws OIDplusException
 */
function oidplus_dbupdate_204_205(OIDplusDatabaseConnection $db, string &$version) {
    if ($db->transaction_supported()) $db->transaction_begin();

    if ($db->getSlang()->id() == 'mssql') {
	$db->query("alter table ###ra alter column [ra_name] [varchar](100) NULL;");
	$db->query("alter table ###ra alter column [personal_name] [varchar](100) NULL;");
	$db->query("alter table ###ra alter column [organization] [varchar](100) NULL;");
	$db->query("alter table ###ra alter column [office] [varchar](100) NULL;");
	$db->query("alter table ###ra alter column [street] [varchar](100) NULL;");
	$db->query("alter table ###ra alter column [zip_town] [varchar](100) NULL;");
	$db->query("alter table ###ra alter column [country] [varchar](100) NULL;");
	$db->query("alter table ###ra alter column [phone] [varchar](100) NULL;");
	$db->query("alter table ###ra alter column [mobile] [varchar](100) NULL;");
	$db->query("alter table ###ra alter column [fax] [varchar](100) NULL;");
	$db->query("alter table ###ra alter column [salt] [varchar](100) NULL;");
	$db->query("alter table ###ra alter column [authkey] [varchar](100) NULL;");

	$db->query("alter table ###objects alter column [title] [varchar](255) NULL;");
	$db->query("alter table ###objects alter column [description] [text] NULL;");
    }
    else if ($db->getSlang()->id() == 'mysql') {
	$db->query("alter table ###ra modify ra_name varchar(100) NULL;");
	$db->query("alter table ###ra modify personal_name varchar(100) NULL;");
	$db->query("alter table ###ra modify organization varchar(100) NULL;");
	$db->query("alter table ###ra modify office varchar(100) NULL;");
	$db->query("alter table ###ra modify street varchar(100) NULL;");
	$db->query("alter table ###ra modify zip_town varchar(100) NULL;");
	$db->query("alter table ###ra modify country varchar(100) NULL;");
	$db->query("alter table ###ra modify phone varchar(100) NULL;");
	$db->query("alter table ###ra modify mobile varchar(100) NULL;");
	$db->query("alter table ###ra modify fax varchar(100) NULL;");
	$db->query("alter table ###ra modify salt varchar(100) NULL;");
	$db->query("alter table ###ra modify authkey varchar(100) NULL;");

	$db->query("alter table ###objects modify title varchar(255) NULL;");
	$db->query("alter table ###objects modify description text NULL;");
    }
    else if ($db->getSlang()->id() == 'pgsql') {
	$db->query("alter table ###ra alter column ra_name DROP NOT NULL");
	$db->query("alter table ###ra alter column personal_name DROP NOT NULL");
	$db->query("alter table ###ra alter column organization DROP NOT NULL");
	$db->query("alter table ###ra alter column office DROP NOT NULL");
	$db->query("alter table ###ra alter column street DROP NOT NULL");
	$db->query("alter table ###ra alter column zip_town DROP NOT NULL");
	$db->query("alter table ###ra alter column country DROP NOT NULL");
	$db->query("alter table ###ra alter column phone DROP NOT NULL");
	$db->query("alter table ###ra alter column mobile DROP NOT NULL");
	$db->query("alter table ###ra alter column fax DROP NOT NULL");
	$db->query("alter table ###ra alter column salt DROP NOT NULL");
	$db->query("alter table ###ra alter column authkey DROP NOT NULL");

	$db->query("alter table ###objects alter column title DROP NOT NULL");
	$db->query("alter table ###objects alter column description DROP NOT NULL");
    }
    else if ($db->getSlang()->id() == 'sqlite') {
	$db->query("CREATE TABLE `###ra2` (".
	           "  `ra_id` INTEGER PRIMARY KEY AUTOINCREMENT,".
	           "  `email` TEXT NOT NULL UNIQUE,".
	           "  `ra_name` TEXT NULL,".
	           "  `personal_name` TEXT NULL,".
	           "  `organization` TEXT NULL,".
	           "  `office` TEXT NULL,".
	           "  `street` TEXT NULL,".
	           "  `zip_town` TEXT NULL,".
	           "  `country` TEXT NULL,".
	           "  `phone` TEXT NULL,".
	           "  `mobile` TEXT NULL,".
	           "  `fax` TEXT NULL,".
	           "  `privacy` INTEGER NOT NULL DEFAULT 0,".
	           "  `salt` TEXT NULL,".
	           "  `authkey` TEXT NULL,".
	           "  `registered` TEXT,".
	           "  `updated` TEXT,".
	           "  `last_login` datetime".
	           ");");
	$db->query("INSERT INTO ###ra2 SELECT * FROM ###ra;");
	$db->query("DROP TABLE ###ra;");
	$db->query("ALTER TABLE ###ra2 RENAME TO ###ra;");

	$db->query("CREATE TABLE `###objects2` (".
	           "  `id` TEXT NOT NULL,".
	           "  `parent` TEXT DEFAULT NULL REFERENCES `objects`(`id`),".
	           "  `title` TEXT NULL,".
	           "  `description` TEXT NULL,".
	           "  `ra_email` TEXT NULL REFERENCES `###ra`(`email`),".
	           "  `confidential` boolean NOT NULL,".
	           "  `created` TEXT,".
	           "  `updated` TEXT,".
	           "  `comment` TEXT NULL,".
	           "  PRIMARY KEY (`id`)".
	           ");");
	$db->query("INSERT INTO ###objects2 SELECT * FROM ###objects;");
	$db->query("DROP TABLE ###objects;");
	$db->query("ALTER TABLE ###objects2 RENAME TO ###objects;");
    }

    $version = 205;
    $db->query("UPDATE ###config SET value = ? WHERE name = 'database_version'", array($version));

    if ($db->transaction_supported()) $db->transaction_commit();
}
