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

abstract class OIDplusDataBase {
	protected $connected = false;

	public abstract static function name();
	public abstract function query($sql, $prepared_args=null);
	public abstract function num_rows($res);
	public abstract function fetch_array($res);
	public abstract function fetch_object($res);
	public abstract function insert_id();
	public abstract function error();
	public abstract function transaction_begin();
	public abstract function transaction_commit();
	public abstract function transaction_rollback();

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

	protected function afterConnect($html) {
		// Check if database tables are existing
		$table_names = array('objects', 'asn1id', 'iri', 'ra', 'config');
		foreach ($table_names as $tablename) {
			if (!$this->query("DESCRIBE `".OIDPLUS_TABLENAME_PREFIX.$tablename."`")) {
				if ($html) {
					echo '<h1>Error</h1><p>Table <b>'.OIDPLUS_TABLENAME_PREFIX.$tablename.'</b> does not exist.</p>';
					if (is_dir(__DIR__.'/../../../setup')) {
						echo '<p>Please run <a href="setup/">setup</a> again.</p>';
					}
				} else {
					echo 'Error: Table '.OIDPLUS_TABLENAME_PREFIX.$tablename.' does not exist.';
					if (is_dir(__DIR__.'/../../../setup')) {
						echo ' Please run setup again.';
					}
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

	public function isConnected() {
		return $this->connected;
	}
}

