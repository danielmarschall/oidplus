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

abstract class OIDplusDataBasePlugin extends OIDplusPlugin {
	protected $connected = false;
	protected $html = null;

	public abstract static function name(): string; // this is the name that is set to the configuration value OIDPLUS_DATABASE_PLUGIN to identify the database plugin
	public abstract function query($sql, $prepared_args=null): OIDplusQueryResult;
	public abstract function insert_id(): int;
	public abstract function error(): string;
	public abstract function transaction_begin(): void;
	public abstract function transaction_commit(): void;
	public abstract function transaction_rollback(): void;

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

	protected function afterConnect(): void {
		// Check if database tables are existing
		$table_names = array('objects', 'asn1id', 'iri', 'ra', 'config');
		foreach ($table_names as $tablename) {
			try {
				$this->query("select 0 from ".OIDPLUS_TABLENAME_PREFIX.$tablename." where 1=0");
			} catch (Exception $e) {
				if ($this->html) {
					echo '<h1>Error</h1><p>Table <b>'.OIDPLUS_TABLENAME_PREFIX.$tablename.'</b> does not exist.</p>';
					if (is_dir(__DIR__.'/../../setup')) {
						echo '<p>Please run <a href="'.OIDplus::getSystemUrl().'setup/">setup</a> again.</p>';
					}
				} else {
					echo 'Error: Table '.OIDPLUS_TABLENAME_PREFIX.$tablename.' does not exist.';
					if (is_dir(__DIR__.'/../../setup')) {
						echo ' Please run setup again.';
					}
				}
				die();
			}
		}

		// Do the database table tables need an update?
		// Note: The config setting "database_version" is inserted in setup/sql/...sql, not in the OIDplus core init

		$res = $this->query("SELECT value FROM ".OIDPLUS_TABLENAME_PREFIX."config WHERE name = 'database_version'");
		$row = $res->fetch_array();
		$version = $row['value'];
		if ($version == 200) {
			$this->transaction_begin();
			$this->query("ALTER TABLE ".OIDPLUS_TABLENAME_PREFIX."objects ADD comment varchar(255) NULL");
			$version = 201;
			$this->query("UPDATE ".OIDPLUS_TABLENAME_PREFIX."config SET value = '$version' WHERE name = 'database_version'");
			$this->transaction_commit();
		}
	}

	protected function showConnectError($message): void {
		if ($this->html) {
			echo "<h1>Error</h1><p>Database connection failed! (".$this->error().")</p>";
			if (is_dir(__DIR__.'/../../setup')) {
				echo '<p>If you believe that the login credentials are wrong, please run <a href="'.OIDplus::getSystemUrl().'setup/">setup</a> again.</p>';
			}
		} else {
			echo "Error: Database connection failed! (".$this->error().")";
			if (is_dir(__DIR__.'/../../setup')) {
				echo ' If you believe that the login credentials are wrong, please run setup again.';
			}
		}
	}

	public function isConnected(): bool {
		return $this->connected;
	}

	public function init($html = true): void {
		$this->html = $html;
	}
}

