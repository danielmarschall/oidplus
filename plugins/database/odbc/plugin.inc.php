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

class OIDplusDatabasePluginODBC extends OIDplusDatabasePlugin {
	private $conn;
	private $last_error = null; // do the same like MySQL+PDO, just to be equal in the behavior

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

	public function doQuery(string $sql, /*?array*/ $prepared_args=null): OIDplusQueryResult {
		$this->last_error = null;
		if (is_null($prepared_args)) {
			$res = @odbc_exec($this->conn, $sql);

			if ($res === false) {
				$this->last_error = odbc_errormsg($this->conn);
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
			return OIDplusQueryResultODBC(@odbc_exec($this->conn, $sql));
			*/
			if (!is_array($prepared_args)) {
				throw new OIDplusException("'prepared_args' must be either NULL or an ARRAY.");
			}

			foreach ($prepared_args as &$value) {
				// ODBC/SQLServer has problems converting "true" to the data type "bit"
				// Error "Invalid character value for cast specification"
				if (is_bool($value)) $value = $value ? '1' : '0';
			}

			$ps = @odbc_prepare($this->conn, $sql);
			if (!$ps) {
				$this->last_error = odbc_errormsg($this->conn);
				throw new OIDplusSQLException($sql, 'Cannot prepare statement: '.$this->error());
			}

			if (!@odbc_execute($ps, $prepared_args)) {
				$this->last_error = odbc_errormsg($this->conn);
				throw new OIDplusSQLException($sql, $this->error());
			}
			return new OIDplusQueryResultODBC($ps);
		}
	}

	public function insert_id(): int {
		switch ($this->slang()) {
			case 'mysql':
				$res = $this->query("SELECT LAST_INSERT_ID() AS ID");
				$row = $res->fetch_array();
				return (int)$row['ID'];
			case 'sqlite':
				$res = $this->query("SELECT last_insert_rowid() AS ID");
				$row = $res->fetch_array();
				return (int)$row['ID'];
			case 'pgsql':
				$res = $this->query("SELECT LASTVAL() AS ID");
				$row = $res->fetch_array();
				return (int)$row['ID'];
			case 'mssql':
				// Note: SCOPE_IDENTITY() does not work, does only give 0.
				// $res = $this->query("SELECT SCOPE_IDENTITY() AS ID");
				$res = $this->query("SELECT @@IDENTITY AS ID");
				$row = $res->fetch_array();
				return (int)$row['ID'];
			default:
				throw new OIDplusException("Cannot determine the last inserted ID for your DBMS. The DBMS is probably not supported.");
		}
	}

	public function error(): string {
		$err = $this->last_error;
		if ($err == null) $err = '';
		return $err;
	}

	protected function doConnect(): void {
		if (!function_exists('odbc_connect')) throw new OIDplusConfigInitializationException('PHP extension "ODBC" not installed');

		// Try connecting to the database
		$dsn      = OIDplus::baseConfig()->getValue('ODBC_DSN',      'DRIVER={SQL Server};SERVER=localhost;DATABASE=oidplus;CHARSET=UTF8');
		$username = OIDplus::baseConfig()->getValue('ODBC_USERNAME', '');
		$password = OIDplus::baseConfig()->getValue('ODBC_PASSWORD', '');
		$this->conn = @odbc_connect($dsn, $username, $password);

		if (!$this->conn) {
			$message = odbc_errormsg();
			throw new OIDplusConfigInitializationException('Connection to the database failed! '.$message);
		}

		$this->last_error = null;

		try {
			$this->query("SET NAMES 'utf8'"); // Does most likely NOT work with ODBC. Try adding ";CHARSET=UTF8" (or similar) to the DSN
		} catch (Exception $e) {
		}
	}

	protected function doDisconnect(): void {
		if (!is_null($this->conn)) {
			@odbc_close($this->conn);
			$this->conn = null;
		}
	}

	private $intransaction = false;

	public function transaction_begin(): void {
		if ($this->intransaction) throw new OIDplusException("Nested transactions are not supported by this database plugin.");
		odbc_autocommit($this->conn, false); // begin transaction
		$this->intransaction = true;
	}

	public function transaction_commit(): void {
		odbc_commit($this->conn);
		odbc_autocommit($this->conn, true);
		$this->intransaction = false;
	}

	public function transaction_rollback(): void {
		odbc_rollback($this->conn);
		odbc_autocommit($this->conn, true);
		$this->intransaction = false;
	}
}

class OIDplusQueryResultODBC extends OIDplusQueryResult {
	protected $no_resultset;
	protected $res;

	public function __construct($res) {
		$this->no_resultset = is_bool($res);

		if (!$this->no_resultset) {
			$this->res = $res;
		}
	}

	public function __destruct() {
		// odbc_close_cursor($this->res);
	}

	public function containsResultSet(): bool {
		return !$this->no_resultset;
	}

	public function num_rows(): int {
		if ($this->no_resultset) throw new OIDplusException("The query has returned no result set (i.e. it was not a SELECT query)");
		return odbc_num_rows($this->res);
	}

	public function fetch_array()/*: ?array*/ {
		if ($this->no_resultset) throw new OIDplusException("The query has returned no result set (i.e. it was not a SELECT query)");
		$ret = odbc_fetch_array($this->res);
		if ($ret === false) $ret = null;
		if (!is_null($ret)) {
			// ODBC gives bit(1) as binary, MySQL as integer and PDO as string.
			// We'll do it like MySQL does, even if ODBC is actually more correct.
			foreach ($ret as &$value) {
				if ($value === chr(0)) $value = 0;
				if ($value === chr(1)) $value = 1;
			}
		}
		return $ret;
	}

	public function fetch_object()/*: ?object*/ {
		if ($this->no_resultset) throw new OIDplusException("The query has returned no result set (i.e. it was not a SELECT query)");
		$ret = odbc_fetch_object($this->res);
		if ($ret === false) $ret = null;
		if (!is_null($ret)) {
			// ODBC gives bit(1) as binary, MySQL as integer and PDO as string.
			// We'll do it like MySQL does, even if ODBC is actually more correct.
			foreach ($ret as &$value) {
				if ($value === chr(0)) $value = 0;
				if ($value === chr(1)) $value = 1;
			}
		}
		return $ret;
	}
}
