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

class OIDplusDatabasePluginODBC extends OIDplusDatabasePlugin {
	private $conn;
	private $last_error = null; // do the same like MySQL+PDO, just to be equal in the behavior

	public static function id(): string {
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

	public function error(): string {
		$err = $this->last_error;
		if ($err == null) $err = '';
		return $err;
	}

	protected function doConnect()/*: void*/ {
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

	protected function doDisconnect()/*: void*/ {
		if (!is_null($this->conn)) {
			@odbc_close($this->conn);
			$this->conn = null;
		}
	}

	private $intransaction = false;

	public function transaction_begin()/*: void*/ {
		if ($this->intransaction) throw new OIDplusException("Nested transactions are not supported by this database plugin.");
		odbc_autocommit($this->conn, false); // begin transaction
		$this->intransaction = true;
	}

	public function transaction_commit()/*: void*/ {
		odbc_commit($this->conn);
		odbc_autocommit($this->conn, true);
		$this->intransaction = false;
	}

	public function transaction_rollback()/*: void*/ {
		odbc_rollback($this->conn);
		odbc_autocommit($this->conn, true);
		$this->intransaction = false;
	}
}
