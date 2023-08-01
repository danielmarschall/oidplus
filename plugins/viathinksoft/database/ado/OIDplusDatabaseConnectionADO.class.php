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

class OIDplusDatabaseConnectionADO extends OIDplusDatabaseConnection {
	/**
	 * @var mixed|null
	 */
	private $conn = null;

	/**
	 * @var string|null
	 */
	private $last_error = null; // do the same like MySQL+PDO, just to be equal in the behavior

	/**
	 * @param string $sql
	 * @param array|null $prepared_args
	 * @return OIDplusQueryResultADO
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 * @throws OIDplusSQLException
	 */
	protected function doQueryPrepareEmulation(string $sql, array $prepared_args=null): OIDplusQueryResultADO {
		$sql = str_replace('?', chr(1), $sql);
		foreach ($prepared_args as $arg) {
			$needle = chr(1);
			if (is_bool($arg)) {
				if ($this->slangDetectionDone) {
					$replace = $this->getSlang()->getSQLBool($arg);
				} else {
					$replace = $arg ? '1' : '0';
				}
			} else if (is_int($arg)) {
				$replace = $arg;
			} else if (is_float($arg)) {
				$replace = number_format($arg, 10, '.', '');
			} else if (is_null($arg)) {
				$replace = 'NULL';
			} else {
				// TODO: More types?
				if ($this->slangDetectionDone) {
					$replace = "N'".$this->getSlang()->escapeString($arg)."'";
				} else {
					$replace = "N'".str_replace("'", "''", $arg)."'";
				}
			}
			$pos = strpos($sql, $needle);
			if ($pos !== false) {
				$sql = substr_replace($sql, $replace, $pos, strlen($needle));
			}
		}
		$sql = str_replace(chr(1), '?', $sql);
		return $this->doQuery($sql);
	}

	/**
	 * @var int
	 */
	private $rowsAffected = 0;

	/**
	 * @return int
	 */
	public function rowsAffected(): int {
		return $this->rowsAffected;
	}

	/**
	 * @param string $sql
	 * @param array|null $prepared_args
	 * @return OIDplusQueryResultADO
	 * @throws OIDplusException
	 */
	public function doQuery(string $sql, array $prepared_args=null): OIDplusQueryResult {
		$this->last_error = null;
		if (is_null($prepared_args)) {
			try {
				$fetchableRowsExpected = $this->slangDetectionDone ? $this->getSlang()->fetchableRowsExpected($sql) : str_starts_with(trim(strtolower($sql)),'select');
				if ($fetchableRowsExpected) {
					$res = new \COM("ADODB.Recordset", NULL, 65001/*CP_UTF8*/);

					$res->Open($sql, $this->conn, 3/*adOpenStatic*/, 3/*adLockOptimistic*/);   /** @phpstan-ignore-line */

					$deb = new OIDplusQueryResultADO($res);

					// These two lines are important, otherwise INSERT queries won't have @@ROWCOUNT and stuff...
					// It's probably this an MARS issue (multiple result sets open at the same time),
					// especially because the __destruct() raises an Exception that the dataset is already closed...
					$deb->prefetchAll();
					$res->Close(); /** @phpstan-ignore-line */

					// Important: Do num_rows() after prefetchAll(), because
					// at OLE DB provider for SQL Server, RecordCount is -1 for queries
					// which don't have physical row tables, e.g. "select max(id) as maxid from ###log"
					// If we have prefetched the table, then RecordCount won't be checked;
					// instead, the prefetched array will be counted.
					$this->rowsAffected = $deb->num_rows();

					return $deb;

				} else {
					$this->conn->Execute($sql, $this->rowsAffected);

					// Alternatively:
					//$cmd = new \COM("ADODB.Command", NULL, 65001/*CP_UTF8*/);
					//$cmd->CommandText = $sql;
					//$cmd->CommandType = 1/*adCmdText*/;
					//$cmd->ActiveConnection = $this->conn;
					//$cmd->Execute();

					return new OIDplusQueryResultADO(null);
				}
			} catch (\Exception $e) {
				$this->last_error = $e->getMessage();
				throw new OIDplusSQLException($sql, $this->error());
			}

		} else {
			return $this->doQueryPrepareEmulation($sql, $prepared_args);
		}
	}

	/**
	 * @return string
	 */
	public function error(): string {
		$err = $this->last_error;
		if ($err == null) $err = '';

		$err = html_to_text($err); // The original ADO Exception is HTML

		return vts_utf8_encode($err); // UTF-8 encode, because ADO might output weird stuff ...
	}

	/**
	 * @return void
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	protected function doConnect()/*: void*/ {
		if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
			throw new OIDplusConfigInitializationException(_L('Functionality only available on Windows systems'));
		}

		if (!class_exists('COM')) {
			throw new OIDplusConfigInitializationException(_L('To use %1, please enable the lines "extension=%2" and "extension_dir=ext" in the configuration file %3.',get_class(),'com_dotnet',php_ini_loaded_file() ? php_ini_loaded_file() : 'PHP.ini'));
		}

		// Try connecting to the database

		$conn = new \COM("ADODB.Connection", NULL, 65001/*CP_UTF8*/);

		$connStr = OIDplus::baseConfig()->getValue('ADO_CONNECTION_STRING', 'Provider=MSOLEDBSQL;Data Source=LOCALHOST\SQLEXPRESS;Initial Catalog=oidplus;Integrated Security=SSPI');

		// TODO: Nothing seems to work! Unicode characters entered in SQL Management Studio are not showing up in OIDplus
		//$connStr .=  ";Client_CSet=UTF-8;Server_CSet=Windows-1251";
		//$connStr .=  ";Client_CSet=Windows-1251;Server_CSet=UTF-8";
		//$connStr .=  ";Client_CSet=Windows-1251;Server_CSet=Windows-1251";
		//$connStr .=  ";Client_CSet=UTF-8;Server_CSet=UTF-8";
		//$connStr .= ";CharacterSet=65001";

		try {
			if (stripos($connStr, "charset=") === false) {
				// Try to extend DSN with charset
				// Note: For MySQL, must be utf8 or utf8, and not UTF-8
				try {
					/** @phpstan-ignore-next-line */
					$conn->Open("$connStr;charset=utf8mb4");
					$this->conn = $conn;
				} catch (\Exception $e1) {
					try {
						/** @phpstan-ignore-next-line */
						$conn->Open("$connStr;charset=utf8");
						$this->conn = $conn;
					} catch (\Exception $e2) {
						try {
							/** @phpstan-ignore-next-line */
							$conn->Open("$connStr;charset=UTF-8");
							$this->conn = $conn;
						} catch (\Exception $e3) {
							/** @phpstan-ignore-next-line */
							$conn->Open($connStr);
							$this->conn = $conn;
						}
					}
				}
			} else {
				/** @phpstan-ignore-next-line */
				$conn->Open($connStr);
				$this->conn = $conn;
			}
		} catch (\Exception $e) {
			$message = $e->getMessage();
			$message = vts_utf8_encode($message); // Make UTF-8 if it is NOT already UTF-8. Important for German Microsoft Access.
			throw new OIDplusConfigInitializationException(trim(_L('Connection to the database failed!').' '.$message));
		}

		$this->last_error = null;

		try {
			/** @phpstan-ignore-next-line */
			$this->conn->Execute("SET NAMES 'UTF-8'"); // Does most likely NOT work with ADO. Try adding ";CHARSET=UTF8" (or similar) to the DSN
		} catch (\Exception $e) {
		}

		try {
			/** @phpstan-ignore-next-line */
			$this->conn->Execute("SET CHARACTER SET 'UTF-8'"); // Does most likely NOT work with ADO. Try adding ";CHARSET=UTF8" (or similar) to the DSN
		} catch (\Exception $e) {
		}

		try {
			/** @phpstan-ignore-next-line */
			$this->conn->Execute("SET NAMES 'utf8mb4'"); // Does most likely NOT work with ADO. Try adding ";CHARSET=UTF8" (or similar) to the DSN
		} catch (\Exception $e) {
		}
	}

	/**
	 * @return void
	 */
	protected function doDisconnect()/*: void*/ {
		if (!is_null($this->conn)) {
			if ($this->conn->State != 0) $this->conn->Close();
			$this->conn = null;
		}
	}

	/**
	 * @return array
	 */
	private function connectionProperties(): array {
		$ary = array();
		for ($i=0; $i<$this->conn->Properties->Count; $i++) {
			$ary[$this->conn->Properties->Item($i)->Name] = $this->conn->Properties->Item($i)->Value;
		}
		return $ary;
	}

	/**
	 * @var int
	 */
	private $trans_level = 0;

	/**
	 * @return bool
	 */
	public function transaction_supported(): bool {
		// DBPROPVAL_TC_NONE 0 TAs werden nicht unterstützt
		// DBPROPVAL_TC_DML 1 TAs können nur DML ausführen. DDLs verursachen Fehler.
		// DBPROPVAL_TC_DDL_COMMIT 2 TAs können nur DML ausführen. DDLs bewirken einen COMMIT.
		// DBPROPVAL_TC_DDL_IGNORE 4 TAs können nur DML statements enthalten. DDL statements werden ignoriert.
		// DBPROPVAL_TC_ALL 8 TAs werden vollständig unterstützt.
		// DBPROPVAL_TC_DDL_LOCK 16 TAs können DML+DDL statements sein. Tabellen oder Indices erhalten bei Modifikation aber eine Lock für die Dauer der TA.
		$props = $this->connectionProperties();
		return $props['Transaction DDL'] >= 8;
	}

	/**
	 * @return int
	 */
	public function transaction_level(): int {
		if (!$this->transaction_supported()) {
			// TODO?
			return 0;
		}
		return $this->trans_level;
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
		if ($this->trans_level > 0) throw new OIDplusException(_L('Nested transactions are not supported by this database plugin.'));
		$this->trans_level = $this->conn->BeginTrans();
	}

	/**
	 * @return void
	 */
	public function transaction_commit()/*: void*/ {
		if (!$this->transaction_supported()) {
			// TODO?
			return;
		}
		$this->conn->CommitTrans();
		$this->trans_level--;
	}

	/**
	 * @return void
	 */
	public function transaction_rollback()/*: void*/ {
		if (!$this->transaction_supported()) {
			// TODO?
			return;
		}
		$this->conn->RollbackTrans();
		$this->trans_level--;
	}

	/**
	 * @return array
	 */
	public function getExtendedInfo(): array {
		$props = $this->connectionProperties();
		if (isset($props['Provider Name'])) {
			// https://learn.microsoft.com/en-us/sql/connect/oledb/oledb-driver-for-sql-server?view=sql-server-ver16
			if (strtoupper($props['Provider Name']) == 'SQLOLEDB.DLL') {
				$props['OLE DB for SQL Server Provider Generation'] = _L('Generation %1', 1);
			} else if (strtoupper($props['Provider Name']) == 'SQLNCLI11.DLL') {
				$props['OLE DB for SQL Server Provider Generation'] = _L('Generation %1', 2);
			} else if (strtoupper($props['Provider Name']) == 'MSOLEDBSQL.DLL') {
				$props['OLE DB for SQL Server Provider Generation'] = _L('Generation %1', 3);
			}
		}
		if (isset($props['Password'])) $props['Password'] = '['._L('redacted').']';
		return $props;
	}

}
