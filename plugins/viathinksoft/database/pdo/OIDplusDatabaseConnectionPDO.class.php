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

class OIDplusDatabaseConnectionPDO extends OIDplusDatabaseConnection {
	/**
	 * @var mixed|null
	 */
	private $conn = null;

	/**
	 * @var string|null
	 */
	private $last_error = null; // we need that because PDO divides prepared statement errors and normal query errors, but we have only one "error()" method

	/**
	 * @var bool
	 */
	private $transactions_supported = false;

	/**
	 * @param string $sql
	 * @param array|null $prepared_args
	 * @return OIDplusQueryResultPDO
	 * @throws OIDplusException
	 */
	public function doQuery(string $sql, array $prepared_args=null): OIDplusQueryResult {
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
				if (!$this->last_error) $this->last_error = _L("Error")." ".$this->conn->errorInfo()[0]; // if no message is available, only show the error-code
				throw new OIDplusSQLException($sql, _L('Cannot prepare statement').': '.$this->error());
			}

			if (!@$ps->execute($prepared_args)) {
				$this->last_error = $ps->errorInfo()[2];
				if (!$this->last_error) $this->last_error = _L("Error")." ".$ps->errorInfo()[0]; // if no message is available, only show the error-code
				// TODO:
				// On my test machine with PDO + mysql on XAMPP with PHP 8.2.0, there are two problems with the following code:
				//        $db->query("SELECT * from NONEXISTING", array(''));  // note that there is an additional argument, which is wrong!
				//        $db->error()
				// 1. $ps->errorInfo() is ['HY093', null, null].
				//    The actual error message "Invalid parameter number: number of bound variables does not match number of tokens" is not shown via errorInfo()
				//    => For now, as workaround, we just show the error message "HY093", if there is no driver specific error text available.
				//       However, this means that the test-case will fail, because the table name cannot be found in the error message?!
				// 2. The error "Invalid parameter number: number of bound variables does not match number of tokens" is SHOWN as PHP-warning
				//    It seems like PDO::ERRMODE_SILENT is ignored?! The bug is 11 years old: https://bugs.php.net/bug.php?id=63812
				//    => For now, as workaround, we added "@" in front of $ps->execute ...
				//
				// The following code works fine:
				//        $db->query("SELECT * from NONEXISTING", array());  // note that there the number of arguments is now correct
				//        $db->error()
				// 1. $ps->errorInfo() is ['42S02', '1146', "Table 'oidplus.NONEXISTING' doesn't exist"].
				//    => That's correct!
				// 2. $ps->execute() does not show a warning (if "@" is removed)
				//    => That's correct!

				throw new OIDplusSQLException($sql, $this->error());
			}
			return new OIDplusQueryResultPDO($ps);
		}
	}

	/**
	 * @return int
	 * @throws OIDplusException
	 */
	public function doInsertId(): int {
		try {
			$out = @($this->conn->lastInsertId());
			if ($out === false) return parent::doInsertId(); // fallback method that uses the SQL slang
			return $out;
		} catch (\Exception $e) {
			return parent::doInsertId(); // fallback method that uses the SQL slang
		}
	}

	/**
	 * @return string
	 */
	public function error(): string {
		$err = $this->last_error;
		if ($err == null) $err = '';
		return vts_utf8_encode($err);
	}

	/**
	 * @return void
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	protected function doConnect()/*: void*/ {
		if (!class_exists('PDO')) throw new OIDplusConfigInitializationException(_L('PHP extension "%1" not installed','PDO'));

		try {
			$options = [
			    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_SILENT,
			    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
			    \PDO::ATTR_EMULATE_PREPARES   => true,
			];

			// Try connecting to the database
			$dsn      = OIDplus::baseConfig()->getValue('PDO_DSN',      'mysql:host=localhost;dbname=oidplus;charset=utf8mb4');
			$username = OIDplus::baseConfig()->getValue('PDO_USERNAME', 'root');
			$password = OIDplus::baseConfig()->getValue('PDO_PASSWORD', '');

			// Try to extend DSN with charset
			// Note: For MySQL, must be utf8 or utf8, and not UTF-8
			if (stripos($dsn,"charset=") === false) {
				try {
					$this->conn = new \PDO("$dsn;charset=utf8mb4", $username, $password, $options);
				} catch (\Exception $e1) {
					try {
						$this->conn = new \PDO("$dsn;charset=utf8", $username, $password, $options);
					} catch (\Exception $e2) {
						try {
							$this->conn = new \PDO("$dsn;charset=UTF-8", $username, $password, $options);
						} catch (\Exception $e3) {
							$this->conn = new \PDO($dsn, $username, $password, $options);
						}
					}
				}
			} else {
				$this->conn = new \PDO($dsn, $username, $password, $options);
			}
		} catch (\PDOException $e) {
			$message = $e->getMessage();
			$message = vts_utf8_encode($message); // Make UTF-8 if it is NOT already UTF-8. Important for German Microsoft Access.
			throw new OIDplusConfigInitializationException(trim(_L('Connection to the database failed!').' '.$message));
		}

		$this->last_error = null;

		try {
			@$this->conn->exec("SET NAMES 'utf-8'");
		} catch (\Exception $e) {
		}

		try {
			@$this->conn->exec("SET CHARACTER SET 'utf-8'");
		} catch (\Exception $e) {
		}

		try {
			@$this->conn->exec("SET NAMES 'utf8mb4'");
		} catch (\Exception $e) {
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
		} catch (\Exception $e) {
			$this->transactions_supported = false;
		}
	}

	/**
	 * @return void
	 */
	protected function doDisconnect()/*: void*/ {
		$this->conn = null; // the connection will be closed by removing the reference
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
		$this->conn->beginTransaction();
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
		$this->conn->commit();
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
		$this->conn->rollBack();
		$this->intransaction = false;
	}

}
