<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2021 Daniel Marschall, ViaThinkSoft
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

if (!defined('INSIDE_OIDPLUS')) die();

class OIDplusDatabaseConnectionPDO extends OIDplusDatabaseConnection {
	private $conn = null;
	private $last_error = null; // we need that because PDO divides prepared statement errors and normal query errors, but we have only one "error()" method
	private $transactions_supported = false;

	public function doQuery(string $sql, /*?array*/ $prepared_args=null): OIDplusQueryResult {
		$this->last_error = null;
		if (is_null($prepared_args)) {
			$res = $this->conn->query($sql);

			if ($res === false) {
				$this->last_error = $this->conn->errorInfo()[2];
				throw new OIDplusSQLException($sql, $this->error());
			} else {
				return new OIDplusQueryResultPDO($res);
			}
		} else {
			if (!is_array($prepared_args)) {
				throw new OIDplusException(_L('"prepared_args" must be either NULL or an ARRAY.'));
			}

			foreach ($prepared_args as &$value) {
				// We need to manually convert booleans into strings, because there is a
				// 14 year old bug that hasn't been adressed by the PDO developers:
				// https://bugs.php.net/bug.php?id=57157
				if (is_bool($value)) {
					if ($this->slangDetectionDone) {
						$value = $this->getSlang()->getSQLBool($value);
					} else {
						// This works for everything except Microsoft Access (which needs -1 and 0)
						// Note: We are using '1' and '0' instead of 'true' and 'false' because MySQL converts boolean to tinyint(1)
						$value = $value ? '1' : '0';
					}
				}
			}

			$ps = $this->conn->prepare($sql);
			if (!$ps) {
				$this->last_error = $this->conn->errorInfo()[2];
				throw new OIDplusSQLException($sql, _L('Cannot prepare statement').': '.$this->error());
			}

			if (!$ps->execute($prepared_args)) {
				$this->last_error = $ps->errorInfo()[2];
				throw new OIDplusSQLException($sql, $this->error());
			}
			return new OIDplusQueryResultPDO($ps);
		}
	}

	public function insert_id(): int {
		try {
			$out = @($this->conn->lastInsertId());
			if ($out === false) return parent::insert_id(); // fallback method that uses the SQL slang
			return $out;
		} catch (Exception $e) {
			return parent::insert_id(); // fallback method that uses the SQL slang
		}
	}

	public function error(): string {
		$err = $this->last_error;
		if ($err == null) $err = '';
		return $err;
	}

	protected function doConnect()/*: void*/ {
		if (!class_exists('PDO')) throw new OIDplusConfigInitializationException(_L('PHP extension "%1" not installed','PDO'));

		try {
			$options = [
			    PDO::ATTR_ERRMODE            => PDO::ERRMODE_SILENT,
			    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			    PDO::ATTR_EMULATE_PREPARES   => true,
			];

			// Try connecting to the database
			$dsn      = OIDplus::baseConfig()->getValue('PDO_DSN',      'mysql:host=localhost;dbname=oidplus;charset=UTF8');
			$username = OIDplus::baseConfig()->getValue('PDO_USERNAME', 'root');
			$password = OIDplus::baseConfig()->getValue('PDO_PASSWORD', '');

			if (stripos($dsn,"charset=") === false) $dsn = "$dsn;charset=UTF8";

			$this->conn = new PDO($dsn, $username, $password, $options);
		} catch (PDOException $e) {
			$message = $e->getMessage();
			throw new OIDplusConfigInitializationException(_L('Connection to the database failed!').' '.$message);
		}

		$this->last_error = null;

		try {
			@$this->conn->exec("SET NAMES 'utf8'");
		} catch (Exception $e) {
		}

		// We check if the DBMS supports autocommit.
		// Attention: Check it after you have sent a query already, because Microsoft Access doesn't seem to allow
		// changing auto commit once a query was executed ("Attribute cannot be set now SQLState: S1011")
		// Note: For some weird reason we *DO* need to redirect the output to "$dummy", otherwise it won't work!
		$sql = "select name from ###config where 1=0";
		$sql = str_replace('###', OIDplus::baseConfig()->getValue('TABLENAME_PREFIX', ''), $sql);
		$dummy = $this->conn->query($sql);
		try {
			$this->conn->beginTransaction();
			$this->conn->rollBack();
			$this->transactions_supported = true;
		} catch (Exception $e) {
			$this->transactions_supported = false;
		}
	}

	protected function doDisconnect()/*: void*/ {
		$this->conn = null; // the connection will be closed by removing the reference
	}

	private $intransaction = false;

	public function transaction_supported(): bool {
		return $this->transactions_supported;
	}

	public function transaction_level(): int {
		if (!$this->transaction_supported()) {
			// TODO?
			return 0;
		}
		return $this->intransaction ? 1 : 0;
	}

	public function transaction_begin()/*: void*/ {
		if (!$this->transaction_supported()) {
			// TODO?
			return;
		}
		if ($this->intransaction) throw new OIDplusException(_L('Nested transactions are not supported by this database plugin.'));
		$this->conn->beginTransaction();
		$this->intransaction = true;
	}

	public function transaction_commit()/*: void*/ {
		if (!$this->transaction_supported()) {
			// TODO?
			return;
		}
		$this->conn->commit();
		$this->intransaction = false;
	}

	public function transaction_rollback()/*: void*/ {
		if (!$this->transaction_supported()) {
			// TODO?
			return;
		}
		$this->conn->rollBack();
		$this->intransaction = false;
	}

}
