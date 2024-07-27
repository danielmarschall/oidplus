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

namespace ViaThinkSoft\OIDplus\Plugins\viathinksoft\database\oci;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusConfigInitializationException;
use ViaThinkSoft\OIDplus\Core\OIDplusQueryResult;
use ViaThinkSoft\OIDplus\Core\OIDplusDatabaseConnection;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusSQLException;
use ViaThinkSoft\OIDplus\Core\OIDplusSqlSlangPlugin;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusDatabaseConnectionOci extends OIDplusDatabaseConnection {
	/**
	 * @var mixed|null
	 */
	private $conn = null;

	/**
	 * @var array|null
	 */
	private $last_error = null; // do the same like MySQL+PDO, just to be equal in the behavior

	/**
	 * @param string $sql
	 * @param array|null $prepared_args
	 * @return OIDplusQueryResultOci
	 * @throws OIDplusException
	 */
	public function doQuery(string $sql, ?array $prepared_args=null): OIDplusQueryResult {
		$this->last_error = null;

		$mode = $this->intransaction ? OCI_NO_AUTO_COMMIT : OCI_COMMIT_ON_SUCCESS;

		if (is_null($prepared_args)) {
			$res = @oci_parse($this->conn, $sql);
			if ($res === false) {
				$this->last_error = oci_error($this->conn);
				throw new OIDplusSQLException($sql, _L('Cannot prepare statement').': '.$this->error());
			} else if (!@oci_execute($res, $mode)) {
				$this->last_error = oci_error($res);
				throw new OIDplusSQLException($sql, $this->error());
			} else {
				return new OIDplusQueryResultOci($res);
			}
			//oci_free_statement($res); // will be done in OIDplusQueryResultOci::__destruct()
		} else {
			// convert ? ? ? to :param1 :param2 :param3
			$count = 0;
			$sql = preg_replace_callback('@\\?@', function($found) {
				static $i = 0;
				$i++;
				return ":param$i";
			}, $sql, count($prepared_args), $count);

			$res = @oci_parse($this->conn, $sql); // TODO: prepare_cache (is this safe?)
			if ($res === false) {
				$this->last_error = oci_error($this->conn);
				throw new OIDplusSQLException($sql, _L('Cannot prepare statement').': '.$this->error());
			}

			$i = 0;
			foreach ($prepared_args as $value) {
				$i++;
				if ($i > $count) break;
				if (is_bool($value)) $value = $value ? 1 : 0;
				$paramname = ":param$i";
				$$paramname = $value; // It is VERY important to clone the variable in this stupid way, because the binding will be done to the memory address of $value !
				if (@oci_bind_by_name($res, $paramname, $$paramname) === false) {
					$this->last_error = oci_error($res);
					throw new OIDplusSQLException($sql, _L('Cannot bind parameter %1 to value %2',$paramname,$$paramname).': '.$this->error());
				}
			}

			if (!@oci_execute($res, $mode)) {
				$this->last_error = oci_error($res);
				throw new OIDplusSQLException($sql, $this->error());
			} else {
				return new OIDplusQueryResultOci($res);
			}
			//oci_free_statement($res); // will be done in OIDplusQueryResultOci::__destruct()

		}
	}

	/**
	 * @return string
	 */
	public function error(): string {
		$err = $this->last_error;
		if (!$err) $err = '';
		/*
		array(4) {
		  ["code"]=>
		  int(1)
		  ["message"]=>
		  string(55) "ORA-00001: unique constraint (HR.SYS_C0012493) violated"
		  ["offset"]=>
		  int(0)
		  ["sqltext"]=>
		  string(118) "insert into config (name, description, value, protected, visible) values (:param1, :param2, :param3, :param4, :param5)"
		}
		*/
		if (isset($err['message'])) return $err['message'];
		return $err;
	}

	/**
	 * @return void
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	protected function doConnect(): void {
		if (!function_exists('oci_connect')) throw new OIDplusConfigInitializationException(_L('PHP extension "%1" not installed','OCI8'));

		// Try connecting to the database
		ob_start();
		$err = '';
		try {
			$conn_str = OIDplus::baseConfig()->getValue('OCI_CONN_STR', 'localhost/XE');
			$username = OIDplus::baseConfig()->getValue('OCI_USERNAME', 'hr');
			$password = OIDplus::baseConfig()->getValue('OCI_PASSWORD', 'oracle');
			$this->conn = oci_connect($username, $password, $conn_str, "AL32UTF8" /*, $session_mode*/);
		} finally {
			$err = ob_get_contents();
			ob_end_clean();

			$tmp = oci_error();
			if ($tmp !== false) {
				$err .= $tmp['message'];
			}
		}

		if (!$this->conn) {
			throw new OIDplusConfigInitializationException(trim(_L('Connection to the database failed!').' ' . strip_tags($err)));
		}

		$this->last_error = null;
	}

	/**
	 * @return void
	 */
	protected function doDisconnect(): void {
		if (!is_null($this->conn)) {
			oci_close($this->conn);
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
		return true;
	}

	/**
	 * @return int
	 */
	public function transaction_level(): int {
		return $this->intransaction ? 1 : 0;
	}

	/**
	 * @return void
	 * @throws OIDplusException
	 */
	public function transaction_begin(): void {
		if ($this->intransaction) throw new OIDplusException(_L('Nested transactions are not supported by this database plugin.'));
		// Later, in oci_execute() we will include OCI_NO_AUTO_COMMIT
		$this->intransaction = true;
	}

	/**
	 * @return void
	 */
	public function transaction_commit(): void {
		oci_commit($this->conn);
		$this->intransaction = false;
	}

	/**
	 * @return void
	 */
	public function transaction_rollback(): void {
		oci_rollback($this->conn);
		$this->intransaction = false;
	}

	/**
	 * @param bool $mustExist
	 * @return OIDplusSqlSlangPlugin|null
	 * @throws OIDplusConfigInitializationException
	 */
	protected function doGetSlang(bool $mustExist=true): ?OIDplusSqlSlangPlugin {
		$slang = OIDplus::getSqlSlangPlugin('oracle');
		if (is_null($slang)) {
			throw new OIDplusConfigInitializationException(_L('SQL-Slang plugin "%1" is missing. Please check if it exists in the directory "plugin/sqlSlang". If it is not existing, please recover it from an GIT/SVN snapshot or OIDplus archive file.','oracle'));
		}
		return $slang;
	}

	/**
	 * @return array
	 */
	public function getExtendedInfo(): array {
		$conn_str = OIDplus::baseConfig()->getValue('OCI_CONN_STR', 'localhost/XE');
		return array(
			_L('Connection String') => $conn_str
		);
	}
}
