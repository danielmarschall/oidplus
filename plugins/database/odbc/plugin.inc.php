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

class OIDplusDataBasePluginODBC extends OIDplusDataBasePlugin {
	private $odbc;
	private $last_query;
	private $prepare_cache = array();

	public static function getPluginInformation(): array {
		$out = array();
		$out['name'] = 'ODBC';
		$out['author'] = 'ViaThinkSoft';
		$out['version'] = null;
		$out['descriptionHTML'] = null;
		return $out;
	}

	public static function name(): string {
		return "ODBC";
	}

	public function query($sql, $prepared_args=null): OIDplusQueryResult {
		$this->last_query = $sql;
		if (is_null($prepared_args)) {
			$res = @odbc_exec($this->odbc, $sql);

			if ($res === false) {
				throw new OIDplusSQLException($sql, $this->error());
			} else {
				return new OIDplusQueryResultODBC($res);
			}
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
			return OIDplusQueryResultODBC(@odbc_exec($this->odbc, $sql));
			*/

			if (!is_array($prepared_args)) {
				throw new Exception("'prepared_args' must be either NULL or an ARRAY.");
			}
			if (isset($this->prepare_cache[$sql])) {
				$ps = $this->prepare_cache[$sql];
			} else {
				$ps = odbc_prepare($this->odbc, $sql);
				if (!$ps) {
					throw new OIDplusSQLException($sql, 'Cannot prepare statement');
				}
				$this->prepare_cache[$sql] = $ps;
			}
			if (!@odbc_execute($ps, $prepared_args)) {
				throw new OIDplusSQLException($sql, $this->error());
			}
			return new OIDplusQueryResultODBC($ps);
		}
	}

	public function insert_id(): int {
		try {
			$res = $this->query("SELECT LAST_INSERT_ID AS ID"); // MySQL
		} catch (Exception $e) {
			$res = null;
		}

		try {
			if (!$res) $res = $this->query("SELECT @@IDENTITY AS ID"); // MS SQL
		} catch (Exception $e) {
			$res = null;
		}

		if (!$res) return 0;

		$row = $res->fetch_array();
		return (int)$row['ID'];
	}

	public function error(): string {
		return odbc_errormsg($this->odbc);
	}

	private $html = null;
	public function init($html = true): void {
		$this->html = $html;
	}

	public function connect(): void {
		// Try connecting to the database
		$this->odbc = @odbc_connect(OIDPLUS_ODBC_DSN, OIDPLUS_ODBC_USERNAME, base64_decode(OIDPLUS_ODBC_PASSWORD));

		if (!$this->odbc) {
			if ($this->html) {
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

		try {
			$this->query("SET NAMES 'utf8'"); // Does most likely NOT work with ODBC. Try adding ";CHARSET=UTF8" (or similar) to the DSN
		} catch (Exception $e) {
		}
		$this->afterConnect($this->html);
		$this->connected = true;
	}

	private $intransaction = false;

	public function transaction_begin(): void {
		if ($this->intransaction) throw new Exception("Nested transactions are not supported by this database plugin.");
		odbc_autocommit($this->odbc, true);
		$this->intransaction = true;
	}

	public function transaction_commit(): void {
		odbc_commit($this->odbc);
		odbc_autocommit($this->odbc, false);
		$this->intransaction = false;
	}

	public function transaction_rollback(): void {
		odbc_rollback($this->odbc);
		odbc_autocommit($this->odbc, false);
		$this->intransaction = false;
	}
}

class OIDplusQueryResultODBC extends OIDplusQueryResult {
	protected $no_resultset;
	protected $res;

	public function __construct($res) {
		$this->no_resultset = $res === false;
		$this->res = $res;
	}

	public function containsResultSet(): bool {
		return !$this->no_resultset;
	}

	public function num_rows(): int {
		if ($this->no_resultset) throw new Exception("The query has returned no result set (i.e. it was not a SELECT query)");
		return odbc_num_rows($this->res);
	}

	public function fetch_array()/*: ?array*/ {
		if ($this->no_resultset) throw new Exception("The query has returned no result set (i.e. it was not a SELECT query)");
		$ret = odbc_fetch_array($this->res);
		if ($ret === false) $ret = null;
		return $ret;
	}

	public function fetch_object()/*: ?object*/ {
		if ($this->no_resultset) throw new Exception("The query has returned no result set (i.e. it was not a SELECT query)");
		$ret = odbc_fetch_object($this->res);
		if ($ret === false) $ret = null;
		return $ret;
	}
}
