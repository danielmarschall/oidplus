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

// This script is used to detect problems with your database plugin

require_once __DIR__ . '/../includes/oidplus.inc.php';

if (file_exists(__DIR__ . '/../includes/config.inc.php')) {
	require_once __DIR__ . '/../includes/config.inc.php';
	if (!defined('OIDPLUS_TABLENAME_PREFIX')) define('OIDPLUS_TABLENAME_PREFIX', 'oidplus_');
}

# Test MySQL
# TODO: with and without mysqlnd
include __DIR__ . '/../plugins/database/mysqli/plugin.inc.php';
$db = new OIDplusDatabasePluginMySQLi();
dotest($db, OIDPLUS_TABLENAME_PREFIX);

# Test PDO
include __DIR__ . '/../plugins/database/pdo/plugin.inc.php';
$db = new OIDplusDatabasePluginPDO();
dotest($db, OIDPLUS_TABLENAME_PREFIX);

# Test ODBC
include __DIR__ . '/../plugins/database/odbc/plugin.inc.php';
$db = new OIDplusDatabasePluginODBC();
dotest($db, OIDPLUS_TABLENAME_PREFIX);

# Test PgSQL
include __DIR__ . '/../plugins/database/pgsql/plugin.inc.php';
$db = new OIDplusDatabasePluginPgSQL();
dotest($db, OIDPLUS_TABLENAME_PREFIX);

# ---

function dotest($db, $prefix) {
	echo "DATABASE: " . $db->name()."<br>";
	try {
		$db->connect();
	} catch (Exception $e) {
		echo "Connection FAILED (check config.inc.php): ".$e->getMessage()."<br><br>";
		return;
	}
	$db->query("delete from ".$prefix."objects where id like 'test:%'");
	$db->query("insert into ".$prefix."objects (id, parent, title, description, confidential) values ('test:1.1', 'test:1', '', '', '0')");
	$db->query("insert into ".$prefix."objects (id, parent, title, description, confidential) values ('test:1.2', 'test:1', '', '', '0')");
	try {
		$res = $db->query("select id from ".$prefix."objects where parent = ? order by id", array('test:1'));
		assert($res->num_rows() == 2);
		$passed = false;
		while ($row = $res->fetch_array()) {
			$res2 = $db->query("select id from ".$prefix."objects where parent = ? order by id", array($row['id']));
			while ($row2 = $res2->fetch_array()) {
			}
			if ($row['id'] == 'test:1.2') {
				$passed = true;
			}
		}
		echo "SIMULTANOUS PREPARED STATEMENTS: ".($passed ? 'PASSED' : 'FAILED')."<br>";
	} finally {
		$db->query("delete from ".$prefix."objects where id like 'test:%'");
	}
	$db->disconnect();
	echo "<br>";
}


