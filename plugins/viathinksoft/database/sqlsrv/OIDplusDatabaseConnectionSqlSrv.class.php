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

namespace ViaThinkSoft\OIDplus\Plugins\viathinksoft\database\sqlsrv;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusQueryResult;
use ViaThinkSoft\OIDplus\Core\OIDplusConfigInitializationException;
use ViaThinkSoft\OIDplus\Core\OIDplusDatabaseConnection;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusSQLException;
use ViaThinkSoft\OIDplus\Core\OIDplusSqlSlangPlugin;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusDatabaseConnectionSqlSrv extends OIDplusDatabaseConnection {

	/**
	 * @var mixed|null
	 */
	private $conn = null;

	/**
	 * @var string|null
	 */
	private $last_error = null;

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
	 * @return OIDplusQueryResultSqlSrv
	 * @throws OIDplusException
	 */
	public function doQuery(string $sql, ?array $prepared_args=null): OIDplusQueryResult {
		$this->last_error = null;
		try {
			$res = sqlsrv_query($this->conn, $sql, $prepared_args,
				array(
					// SQLSRV_CURSOR_FORWARD ('forward', default)
					// Lets you move one row at a time starting at the first row of the result set until you reach the end of the result set.
					// => Does not work with sqlsrv_num_rows();

					// SQLSRV_CURSOR_STATIC ('static')
					// Lets you access rows in any order but will not reflect changes in the database.
					// => Does not work with transaction rollback?! (Testcase failed)

					// SQLSRV_CURSOR_DYNAMIC
					// Lets you access rows in any order and will reflect changes in the database.
					// => Does not work with sqlsrv_num_rows();

					// SQLSRV_CURSOR_KEYSET ('keyset')
					// Lets you access rows in any order. However, a keyset cursor does not update the row count if a row is deleted from the table (a deleted row is returned with no values).
					// => Does not work with transaction rollback?! (Testcase failed)

					// SQLSRV_CURSOR_CLIENT_BUFFERED ('buffered')
					// Lets you access rows in any order. Creates a client-side cursor query.
					// => Seems to work fine

					'Scrollable' => SQLSRV_CURSOR_CLIENT_BUFFERED
				)
			);
		} catch (\Exception $e) {
			$this->last_error = $e->getMessage();
			throw new OIDplusSQLException($sql, $e->getMessage());
		}

		if ($res === false) {
			$this->last_error = print_r(sqlsrv_errors(), true);
			throw new OIDplusSQLException($sql, $this->error());
		} else {
			if (str_starts_with(trim(strtolower($sql)),'select')) { // Note: Please do not call $this->getSlang()->fetchableRowsExpected($sql)
				$this->rowsAffected = sqlsrv_num_rows($res);
			} else {
				$this->rowsAffected = sqlsrv_rows_affected($res);
			}
			return new OIDplusQueryResultSqlSrv($res);
		}
	}


	/**
	 * @return string
	 */
	public function error(): string {
		$err = $this->last_error;
		if ($err === null) $err = '';
		return $err;
	}

	/**
	 * @return string
	 */
	private static function get_sqlsrv_dll_name(): string {
		ob_start();
		phpinfo(INFO_GENERAL);
		$x = ob_get_contents();
		ob_end_clean();

		$architecture =
			preg_match('@Architecture.+(x86|x64)@', $x, $m) ? $m[1] : '*';

		$threadsafety =
			preg_match('@Thread Safety.+(enabled|disabled)@', $x, $m)
			? ($m[1] == 'enabled' ? 'ts' : 'nts') : '*';

		$m = explode('.',phpversion());
		$version = $m[0].$m[1];

		// e.g. php_sqlsrv_82_ts_x64.dll
		return "php_sqlsrv_{$version}_{$threadsafety}_{$architecture}.dll";
	}

	/**
	 * @return void
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	protected function doConnect(): void {
		// Download here: https://learn.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server?view=sql-server-ver16
		if (!function_exists('sqlsrv_connect')) throw new OIDplusException(_L('PHP extension "%1" not installed',self::get_sqlsrv_dll_name()));

		// Try connecting to the database
		$servername = OIDplus::baseConfig()->getValue('SQLSRV_SERVER',   'localhost\oidplus');
		$username   = OIDplus::baseConfig()->getValue('SQLSRV_USERNAME', '');
		$password   = OIDplus::baseConfig()->getValue('SQLSRV_PASSWORD', '');
		$database   = OIDplus::baseConfig()->getValue('SQLSRV_DATABASE', 'oidplus');
		$options    = OIDplus::baseConfig()->getValue('SQLSRV_OPTIONS',  array());

		if (!isset($options['Database'])) $options['Database'] = $database;
		if (!isset($options['CharacterSet'])) $options['CharacterSet'] = 'UTF-8';
		if ($username != '') {
			if (!isset($options['UID'])) $options['UID'] = $username;
			if (!isset($options['PWD'])) $options['PWD'] = $password;
		}

		$this->conn = @sqlsrv_connect($servername, $options);

		if (!$this->conn) {
			$message = print_r(sqlsrv_errors(), true);
			throw new OIDplusConfigInitializationException(trim(_L('Connection to the database failed!').' '.$message));
		}

		$this->last_error = null;
	}

	/**
	 * @return void
	 */
	protected function doDisconnect(): void {
		if (!is_null($this->conn)) {
			sqlsrv_close($this->conn);
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
		if (sqlsrv_begin_transaction($this->conn)) $this->intransaction = true;
	}

	/**
	 * @return void
	 */
	public function transaction_commit(): void {
		if (sqlsrv_commit($this->conn)) $this->intransaction = false;
	}

	/**
	 * @return void
	 */
	public function transaction_rollback(): void {
		if (sqlsrv_rollback($this->conn)) $this->intransaction = false;
	}

	/**
	 * @param bool $mustExist
	 * @return OIDplusSqlSlangPlugin|null
	 * @throws OIDplusConfigInitializationException
	 */
	protected function doGetSlang(bool $mustExist=true): ?OIDplusSqlSlangPlugin {
		$slang = OIDplus::getSqlSlangPlugin('mssql');
		if (is_null($slang)) {
			throw new OIDplusConfigInitializationException(_L('SQL-Slang plugin "%1" is missing. Please check if it exists in the directory "plugin/sqlSlang". If it is not existing, please recover it from an GIT/SVN snapshot or OIDplus archive file.','mssql'));
		}
		return $slang;
	}

	/**
	 * @return array
	 */
	public function getExtendedInfo(): array {
		$servername = OIDplus::baseConfig()->getValue('SQLSRV_SERVER',   'localhost\oidplus');
		$username   = OIDplus::baseConfig()->getValue('SQLSRV_USERNAME', '');
		$password   = OIDplus::baseConfig()->getValue('SQLSRV_PASSWORD', '');
		$database   = OIDplus::baseConfig()->getValue('SQLSRV_DATABASE', 'oidplus');
		$options    = OIDplus::baseConfig()->getValue('SQLSRV_OPTIONS',  array());

		$ary_info = array(
			_L('Hostname') => $servername,
			_L('Username') => $username,
			_L('Password') => $password != '' ? '('._L('redacted').')' : '',
			_L('Database') => $database
		);
		foreach ($options as $name => $val) {
			$ary_info[_L('Option %1',$name)] = '"'.$val.'"';
		}
		return $ary_info;
	}
}
