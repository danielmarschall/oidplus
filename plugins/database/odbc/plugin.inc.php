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

class OIDplusDataBaseODBC extends OIDplusDataBase {
	private $odbc;
	private $last_query;
	private $prepare_cache = array();

	public static function name() {
		return "ODBC";
	}

	public function query($sql, $prepared_args=null) {
		$this->last_query = $sql;
		if (is_null($prepared_args)) {
			return @odbc_exec($this->odbc, $sql);
		} else {


			// TEST: Emulate the prepared statement

/*
			foreach ($prepared_args as $arg) {
				$needle = '?';
				$replace = "'$arg'"; // TODO: types
				$pos = strpos($sql, $needle);
				if ($pos !== false) {
					$sql = substr_replace($sql, $replace, $pos, strlen($needle));
				}
			}
			return @odbc_exec($this->odbc, $sql);
*/


			if (!is_array($prepared_args)) {
				throw new Exception("'prepared_args' must be either NULL or an ARRAY.");
			}
			if (isset($this->prepare_cache[$sql])) {
				$ps = $this->prepare_cache[$sql];
			} else {
				$ps = odbc_prepare($this->odbc, $sql);
				if (!$ps) {
					throw new Exception("Cannot prepare statement '$sql'");
				}
				$this->prepare_cache[$sql] = $ps;
			}
			if (!@odbc_execute($ps, $prepared_args)) {
				// Note: Our plugins prepare the configs by trying to insert stuff, which raises a Primary Key exception. So we cannot throw an Exception.
				return false;
			}
			return $ps;

		}
	}
	public function num_rows($res) {
		if (!is_resource($res)) {
			throw new Exception("num_rows called on non object. Last query: ".$this->last_query);
		} else {
			return odbc_num_rows($res);
		}
	}
	public function fetch_array($res) {
		if (!is_resource($res)) {
			throw new Exception("fetch_array called on non object. Last query: ".$this->last_query);
		} else {
			return odbc_fetch_array($res);
		}
	}
	public function fetch_object($res) {
		if (!is_resource($res)) {
			throw new Exception("fetch_object called on non object. Last query: ".$this->last_query);
		} else {
			return odbc_fetch_object($res);
		}
	}
	public function insert_id() {
		$res = $this->query("SELECT LAST_INSERT_ID AS ID"); // MySQL
		if (!$res) $res = $this->query("SELECT @@IDENTITY AS ID"); // MS SQL
		if (!$res) return false;
		$row = $this->fetch_array($res);
		return $row['ID'];
	}
	public function error() {
		return odbc_errormsg($this->odbc);
	}
	public function connect() {
		$html = OIDPLUS_HTML_OUTPUT;

		// Try connecting to the database
		$this->odbc = @odbc_connect(OIDPLUS_ODBC_DSN, OIDPLUS_ODBC_USERNAME, base64_decode(OIDPLUS_ODBC_PASSWORD));

		if (!$this->odbc) {
			if ($html) {
				echo "<h1>Error</h1><p>Database connection failed! (".odbc_errormsg().")</p>";
				if (is_dir(__DIR__.'/../../../setup')) {
					echo '<p>If you believe that the login credentials are wrong, please run <a href="setup/">setup</a> again.</p>';
				}
			} else {
				echo "Error: Database connection failed! (".odbc_errormsg().")";
				if (is_dir(__DIR__.'/../../../setup')) {
					echo ' If you believe that the login credentials are wrong, please run setup again.';
				}
			}
			die();
		}

		$this->query("SET NAMES 'utf8'"); // Does most likely NOT work with ODBC. Try adding ";CHARSET=UTF8" (or similar) to the DSN
		$this->afterConnect($html);
		$this->connected = true;
	}

	private $intransaction = false;

	public function transaction_begin() {
		if ($this->intransaction) throw new Exception("Nested transactions are not supported by this database plugin.");
		odbc_autocommit($this->odbc, true);
		$this->intransaction = true;
	}

	public function transaction_commit() {
		odbc_commit($this->odbc);
		odbc_autocommit($this->odbc, false);
		$this->intransaction = false;
	}

	public function transaction_rollback() {
		odbc_rollback($this->odbc);
		odbc_autocommit($this->odbc, false);
		$this->intransaction = false;
	}
}

OIDplus::registerDatabasePlugin(new OIDplusDataBaseODBC());
