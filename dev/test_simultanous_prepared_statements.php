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
}

echo '<h1>OIDplus Database plugin testcases</h1>';

# Test MySQL
include __DIR__ . '/../plugins/database/mysqli/plugin.inc.php';
$db = new OIDplusDatabasePluginMySQLi();
if (function_exists('mysqli_fetch_all')) {
	OIDplus::baseConfig()->setValue('MYSQL_FORCE_MYSQLND_SUPPLEMENT', false);
	echo "[With MySQLnd support ] ";
	dotest($db);
	OIDplus::baseConfig()->setValue('MYSQL_FORCE_MYSQLND_SUPPLEMENT', true);
}
echo "[Without MySQLnd support ] ";
dotest($db);

# Test PDO
include __DIR__ . '/../plugins/database/pdo/plugin.inc.php';
$db = new OIDplusDatabasePluginPDO();
dotest($db);

# Test ODBC
include __DIR__ . '/../plugins/database/odbc/plugin.inc.php';
$db = new OIDplusDatabasePluginODBC();
dotest($db);

# Test PgSQL
include __DIR__ . '/../plugins/database/pgsql/plugin.inc.php';
$db = new OIDplusDatabasePluginPgSQL();
dotest($db);

# ---

function dotest($db) {
	echo "Database: " . $db->name()."<br>";
	try {
		$db->connect();
	} catch (Exception $e) {
		echo "Connection <font color=\"red\">FAILED</font> (check config.inc.php): ".$e->getMessage()."<br><br>";
		return;
	}
	$db->query("delete from ###objects where id like 'test:%'");
	$db->query("insert into ###objects (id, parent, title, description, confidential) values ('test:1.1', 'test:1', '', '', '0')");
	$db->query("insert into ###objects (id, parent, title, description, confidential) values ('test:1.2', 'test:1', '', '', '0')");
	try {
		$res = $db->query("select id from ###objects where parent = ? order by id", array('test:1'));
		
		$num_rows = $res->num_rows();
		echo "Num rows: " . ($num_rows===2 ? 'PASSED' : '<font color="red">FAILED</font>')."<br>";
		
		$passed = false;
		while ($row = $res->fetch_array()) {
			$res2 = $db->query("select id from ###objects where parent = ? order by id", array($row['id']));
			while ($row2 = $res2->fetch_array()) {
			}
			if ($row['id'] == 'test:1.2') {
				$passed = true;
			}
		}
		echo "Simultanous prepared statements: ".($passed ? 'PASSED' : '<font color="red">FAILED</font>')."<br>";
		
		try {
			$db->query("ABCDEF");
			echo "Exception for DirectQuery: <font color=\"red\">FAILED</font>, no Exception thrown<br>";
		} catch (Exception $e) {
			if (strpos($e->getMessage(), 'ABCDEF') !== false) {
				echo "Exception for DirectQuery: PASSED<br>";
			} else {
				echo "Exception for DirectQuery: <font color=\"red\">FAILED</font>, does probably not contain DBMS error string<br>";
			}
		}

		try {
			$db->query("FEDCBA", array(''));
			echo "Exception for PreparedQuery: <font color=\"red\">FAILED</font>, no Exception thrown<br>";
		} catch (Exception $e) {
			if (strpos($e->getMessage(), 'FEDCBA') !== false) {
				echo "Exception for PreparedQuery: PASSED<br>";
			} else {
				echo "Exception for PreparedQuery: <font color=\"red\">FAILED</font>, does probably not contain DBMS error string<br>";
			}
		}
	} finally {
		$db->query("delete from ###objects where id like 'test:%'");
	}
	$db->disconnect();
	echo "<br>";
}
