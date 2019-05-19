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

if (!defined('IN_OIDPLUS')) die();

class OIDplusDataBaseMySQL implements OIDplusDataBase {
	private $mysqli;

	public function query($sql) {
		// $sql = str_replace('???_', OIDPLUS_TABLENAME_PREFIX, $sql);
		return $this->mysqli->query($sql, MYSQLI_STORE_RESULT);
	}
	public function num_rows($res) {
		return $res->num_rows;
	}
	public function fetch_array($res) {
		return $res->fetch_array(MYSQLI_BOTH);
	}
	public function fetch_object($res) {
		return $res->fetch_object("stdClass");
	}
	public function real_escape_string($str) {
		return $this->mysqli->real_escape_string($str);
	}
	public function insert_id() {
		return $this->mysqli->insert_id;
	}
	public function escape_bool($str) {
		return (($str == 'true') || ($str == '1') || ($str == 'On') || ($str == 'on')) ? '1' : '0';
	}
	public function set_charset($charset) {
		return $this->mysqli->set_charset($charset);
	}
	public function error() {
		return !empty($this->mysqli->connect_error) ? $this->mysqli->connect_error : $this->mysqli->error;
	}
	public function __construct() {
		$html = OIDPLUS_HTML_OUTPUT;

		// Try connecting to the database
		$this->mysqli = @new mysqli(OIDPLUS_MYSQL_HOST, OIDPLUS_MYSQL_USERNAME, base64_decode(OIDPLUS_MYSQL_PASSWORD), OIDPLUS_MYSQL_DATABASE, ini_get("mysqli.default_port"), ini_get("mysqli.default_socket"));
		if (!empty($this->mysqli->connect_error) || ($this->mysqli->connect_errno != 0)) {
			if ($html) {
				echo "<h1>Error</h1><p>Database connection failed!</p>";
				if (is_dir(__DIR__.'/../../setup')) {
					echo '<p>If you believe that the login credentials are wrong, please run <a href="setup/">setup</a> again.</p>';
				}
			} else {
				echo "Error: Database connection failed!";
				if (is_dir(__DIR__.'/../../setup')) {
					echo ' If you believe that the login credentials are wrong, please run setup again.';
				}
			}
			die();
		}

		// Check if database tables are existing
		$table_names = array('objects', 'asn1id', 'iri', 'ra', 'config');
		foreach ($table_names as $tablename) {
			if (!$this->query("DESCRIBE `".OIDPLUS_TABLENAME_PREFIX.$tablename."`")) {
				if ($html) {
					echo '<h1>Error</h1><p>Table <b>'.OIDPLUS_TABLENAME_PREFIX.$tablename.'</b> does not exist.</p><p>Please run <a href="setup/">setup</a> again.</p>';
				} else {
					echo 'Error: Table '.OIDPLUS_TABLENAME_PREFIX.$tablename.' does not exist. Please run setup again.';
				}
				die();
			}
		}

		// Do the database table tables need an update?
		// Note: The config setting "database_version" is inserted in setup/sql/...sql, not in the OIDplus core init

		/*
		$res = $this->query("SELECT value FROM `".OIDPLUS_TABLENAME_PREFIX."config` WHERE name = 'database_version'");
		$row = $this->fetch_array($res);
		$version = $row['value'];
		if ($version == 200) {
			// Do stuff to update 200 -> 201
			$version = 201;
			$this->query("UPDATE `".OIDPLUS_TABLENAME_PREFIX."config` SET value = '$version' WHERE name = 'database_version'");
		}
		if ($version == 201) {
			// Do stuff to update 201 -> 202
			$version = 202;
			$this->query("UPDATE `".OIDPLUS_TABLENAME_PREFIX."config` SET value = '$version' WHERE name = 'database_version'");
		}
		*/
	}

	// TODO: better create some kind of Object-Type-API that does the sorting. But this means, the sorting won't be done with SQL
	public function natOrder($fieldname, $maxdepth=100) { // TODO: also "desc" and "asc" support?
		/*
		   CREATE FUNCTION SPLIT_STRING(str VARCHAR(255), delim VARCHAR(12), pos INT)
		   RETURNS VARCHAR(255)
		   RETURN REPLACE(SUBSTRING(SUBSTRING_INDEX(str, delim, pos),
		   LENGTH(SUBSTRING_INDEX(str, delim, pos-1)) + 1), delim, '');
		 */
		$out = array();
		$out[] = "(REPLACE(SUBSTRING(SUBSTRING_INDEX($fieldname, ':', 1),LENGTH(SUBSTRING_INDEX($fieldname, ':', 0)) + 1),':', '')) asc"; // first sort by NS (namespace)
		for ($i=1; $i<=$maxdepth; $i++) {
	//		$out[] = "LENGTH(SPLIT_STRING($fieldname, '.', $i) asc";
	//		$out[] = "SPLIT_STRING($fieldname, '.', $i) asc";

			$out[] = "LENGTH(REPLACE(SUBSTRING(SUBSTRING_INDEX($fieldname, '.', $i),LENGTH(SUBSTRING_INDEX($fieldname, '.', $i-1)) + 1),'.', '')) asc";
			$out[] = "(REPLACE(SUBSTRING(SUBSTRING_INDEX($fieldname, '.', $i),LENGTH(SUBSTRING_INDEX($fieldname, '.', $i-1)) + 1),'.', '')) asc";

		}
		return implode(', ', $out);
	}

}

