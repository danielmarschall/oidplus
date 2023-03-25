<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2023 Daniel Marschall, ViaThinkSoft
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

namespace ViaThinkSoft\OIDplus;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusDatabaseConnectionODBC extends OIDplusDatabaseConnection {
	/**
	 * @var mixed|null
	 */
	private $conn = null;

	/**
	 * @var string|null
	 */
	private $last_error = null; // do the same like MySQL+PDO, just to be equal in the behavior

	/**
	 * @var bool
	 */
	private $transactions_supported = false;

	/**
	 * @return bool|null
	 * @throws OIDplusException
	 */
	protected function forcePrepareEmulation() {
		$mode = OIDplus::baseConfig()->getValue('PREPARED_STATEMENTS_EMULATION', 'auto');
		if ($mode === 'on') return true;
		if ($mode === 'off') return false;

		static $res = null;
		if (is_null($res)) {
			$sql = 'select name from ###config where name = ?';
			$sql = str_replace('###', OIDplus::baseConfig()->getValue('TABLENAME_PREFIX', ''), $sql);
			$res = @odbc_prepare($this->conn, $sql) === false;
		}

		return $res;
	}

	/**
	 * @param string $sql
	 * @param array|null $prepared_args
	 * @return OIDplusQueryResultODBC
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 * @throws OIDplusSQLException
	 */
	protected function doQueryInternalPrepare(string $sql, array $prepared_args=null): OIDplusQueryResultODBC {
				foreach ($prepared_args as &$value) {
					// ODBC/SQLServer has problems converting "true" to the data type "bit"
					// Error "Invalid character value for cast specification"
					if (is_bool($value)) {
						if ($this->slangDetectionDone) {
							$value = $this->getSlang()->getSQLBool($value);
						} else {
							$value = $value ? '1' : '0';
						}
					}
				}

				$ps = @odbc_prepare($this->conn, $sql);
				if (!$ps) {
					// If preparation fails, try the emulation
					// For example, SQL Server ODBC Driver cannot have "?" in a subquery,
					// otherwise you receive the error message
					// "Syntax error or access violation" on odbc_prepare()
					return $this->doQueryPrepareEmulation($sql, $prepared_args);
					/*
					$this->last_error = odbc_errormsg($this->conn);
					throw new OIDplusSQLException($sql, _L('Cannot prepare statement').': '.$this->error());
					*/
				}

				if (!@odbc_execute($ps, $prepared_args)) {
					$this->last_error = odbc_errormsg($this->conn);
					throw new OIDplusSQLException($sql, $this->error());
				}
				return new OIDplusQueryResultODBC($ps);

	}

	/**
	 * @param string $sql
	 * @param array|null $prepared_args
	 * @return OIDplusQueryResultODBC
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 * @throws OIDplusSQLException
	 */
	protected function doQueryPrepareEmulation(string $sql, array $prepared_args=null): OIDplusQueryResultODBC {
				// For some drivers (e.g. Microsoft Access), we need to do this kind of emulation, because odbc_prepare() does not work
				$sql = str_replace('?', chr(1), $sql);
				foreach ($prepared_args as $arg) {
					$needle = chr(1);
					if (is_bool($arg)) {
						if ($this->slangDetectionDone) {
							$replace = $this->getSlang()->getSQLBool($arg);
						} else {
							$replace = $arg ? '1' : '0';
						}
					} else {
						if ($this->slangDetectionDone) {
							$replace = "'".$this->getSlang()->escapeString($arg)."'"; // TODO: types
						} else {
							$replace = "'".str_replace("'", "''", $arg)."'"; // TODO: types
						}
					}
					$pos = strpos($sql, $needle);
					if ($pos !== false) {
						$sql = substr_replace($sql, $replace, $pos, strlen($needle));
					}
				}
				$sql = str_replace(chr(1), '?', $sql);
				$ps = @odbc_exec($this->conn, $sql);
				if (!$ps) {
					$this->last_error = odbc_errormsg($this->conn);
					throw new OIDplusSQLException($sql, _L('Cannot prepare statement').': '.$this->error());
				}
				return new OIDplusQueryResultODBC($ps);
	}

	/**
	 * @param string $sql
	 * @param array|null $prepared_args
	 * @return OIDplusQueryResultODBC
	 * @throws OIDplusException
	 */
	public function doQuery(string $sql, array $prepared_args=null): OIDplusQueryResult {
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
			if (!is_array($prepared_args)) {
				throw new OIDplusException(_L('"prepared_args" must be either NULL or an ARRAY.'));
			}

			if ($this->forcePrepareEmulation()) {
				return $this->doQueryPrepareEmulation($sql, $prepared_args);
			} else {
				return $this->doQueryInternalPrepare($sql, $prepared_args);
			}
		}
	}

	/**
	 * @return string
	 */
	public function error(): string {
		$err = $this->last_error;
		if ($err == null) $err = '';
		return vts_utf8_encode($err); // UTF-8 encode, because ODBC might output weird stuff ...
	}

	/**
	 * @return void
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	protected function doConnect()/*: void*/ {
		if (!function_exists('odbc_connect')) throw new OIDplusConfigInitializationException(_L('PHP extension "%1" not installed','ODBC'));

		// Try connecting to the database
		$dsn      = OIDplus::baseConfig()->getValue('ODBC_DSN',      'DRIVER={SQL Server};SERVER=localhost;DATABASE=oidplus;CHARSET=UTF8');
		$username = OIDplus::baseConfig()->getValue('ODBC_USERNAME', '');
		$password = OIDplus::baseConfig()->getValue('ODBC_PASSWORD', '');
		$this->conn = @odbc_connect($dsn, $username, $password);

		if (!$this->conn) {
			$message = odbc_errormsg();
			throw new OIDplusConfigInitializationException(trim(_L('Connection to the database failed!').' '.$message));
		}

		$this->last_error = null;

		try {
			@odbc_exec($this->conn, "SET NAMES 'utf8'"); // Does most likely NOT work with ODBC. Try adding ";CHARSET=UTF8" (or similar) to the DSN
		} catch (\Exception $e) {
		}

		// We check if the DBMS supports autocommit.
		// Attention: Check it after you have sent a query already, because Microsoft Access doesn't seem to allow
		// changing auto commit once a query was executed ("Attribute cannot be set now SQLState: S1011")
		// Note: For some weird reason we *DO* need to redirect the output to "$dummy", otherwise it won't work!
		$sql = "select name from ###config where 1=0";
		$sql = str_replace('###', OIDplus::baseConfig()->getValue('TABLENAME_PREFIX', ''), $sql);
		$dummy = @odbc_exec($this->conn, $sql);
		$this->transactions_supported = @odbc_autocommit($this->conn, false);
		@odbc_autocommit($this->conn, true);
	}

	/**
	 * @return void
	 */
	protected function doDisconnect()/*: void*/ {
		if (!is_null($this->conn)) {
			@odbc_close($this->conn);
			$this->conn = null;
		}
	}

	/**
	 * @var bool
	 */
	private $intransaction = false;

	/**
	 * @return bool
	 */
	public function transaction_supported(): bool {
		return $this->transactions_supported;
	}

	/**
	 * @return int
	 */
	public function transaction_level(): int {
		if (!$this->transaction_supported()) {
			// TODO?
			return 0;
		}
		return $this->intransaction ? 1 : 0;
	}

	/**
	 * @return void
	 * @throws OIDplusException
	 */
	public function transaction_begin()/*: void*/ {
		if (!$this->transaction_supported()) {
			// TODO?
			return;
		}
		if ($this->intransaction) throw new OIDplusException(_L('Nested transactions are not supported by this database plugin.'));
		odbc_autocommit($this->conn, false); // begin transaction
		$this->intransaction = true;
	}

	/**
	 * @return void
	 */
	public function transaction_commit()/*: void*/ {
		if (!$this->transaction_supported()) {
			// TODO?
			return;
		}
		odbc_commit($this->conn);
		odbc_autocommit($this->conn, true);
		$this->intransaction = false;
	}

	/**
	 * @return void
	 */
	public function transaction_rollback()/*: void*/ {
		if (!$this->transaction_supported()) {
			// TODO?
			return;
		}
		odbc_rollback($this->conn);
		odbc_autocommit($this->conn, true);
		$this->intransaction = false;
	}
}
