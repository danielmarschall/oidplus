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

namespace ViaThinkSoft\OIDplus\Plugins\Database\PgSQL;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusQueryResult;
use ViaThinkSoft\OIDplus\Core\OIDplusSqlSlangPlugin;
use ViaThinkSoft\OIDplus\Core\OIDplusConfigInitializationException;
use ViaThinkSoft\OIDplus\Core\OIDplusDatabaseConnection;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusSQLException;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusDatabaseConnectionPgSql extends OIDplusDatabaseConnection {
	/**
	 * @var mixed|null
	 */
	private $conn = null;

	/**
	 * @var array
	 */
	private $already_prepared = array();

	/**
	 * @var string|null
	 */
	private $last_error = null; // do the same like MySQL+PDO, just to be equal in the behavior

	/**
	 * @param string $sql
	 * @param array|null $prepared_args
	 * @return OIDplusQueryResultPgSql
	 * @throws OIDplusException
	 */
	public function doQuery(string $sql, ?array $prepared_args=null): OIDplusQueryResult {
		$this->last_error = null;
		if (is_null($prepared_args)) {
			$res = @pg_query($this->conn, $sql);

			if ($res === false) {
				$this->last_error = pg_last_error($this->conn);
				throw new OIDplusSQLException($sql, $this->error());
			} else {
				return new OIDplusQueryResultPgSql($res);
			}
		} else {
			// convert ? ? ? to $1 $2 $3
			$sql = preg_replace_callback('@\\?@', function($found) {
				static $i = 0; // [NoOidplusContextOk]
				$i++;
				return '$'.$i;
			}, $sql, count($prepared_args));

			$prepare_name = 'OIDplus_ps_'.sha1($sql);
			if (!in_array($prepare_name, $this->already_prepared)) {
				$res = @pg_prepare($this->conn, $prepare_name, $sql);
				if ($res === false) {
					$this->last_error = pg_last_error($this->conn);
					throw new OIDplusSQLException($sql, _L('Cannot prepare statement').': '.$this->error());
				}
				$this->already_prepared[] = $prepare_name;
			}

			foreach ($prepared_args as &$value) {
				if (is_bool($value)) $value = $value ? '1' : '0';
			}
			unset($value);

			$ps = pg_execute($this->conn, $prepare_name, $prepared_args);
			if ($ps === false) {
				$this->last_error = pg_last_error($this->conn);
				throw new OIDplusSQLException($sql, $this->error());
			}
			return new OIDplusQueryResultPgSql($ps);
		}
	}

	/**
	 * @return int
	 */
	public function doInsertId(): int {
		try {
			return (int)$this->query('select lastval() as id')->fetch_object()->id;
		} catch (\Exception $e) {
			return 0;
		}
	}

	/**
	 * @return string
	 */
	public function error(): string {
		$err = $this->last_error;
		if ($err == null) $err = '';
		return $err;
	}

	/**
	 * @return void
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	protected function doConnect(): void {
		if (!function_exists('pg_connect')) throw new OIDplusConfigInitializationException(_L('PHP extension "%1" not installed','PostgreSQL'));

		// Try connecting to the database
		ob_start();
		$err = '';
		try {
			$host     = OIDplus::baseConfig()->getValue('PGSQL_HOST',     'localhost:5432');
			$username = OIDplus::baseConfig()->getValue('PGSQL_USERNAME', 'postgres');
			$password = OIDplus::baseConfig()->getValue('PGSQL_PASSWORD', '');
			$database = OIDplus::baseConfig()->getValue('PGSQL_DATABASE', 'oidplus');
			$socket   = OIDplus::baseConfig()->getValue('PGSQL_SOCKET',   '');
			if ($socket != '') {
				$hostname = $socket;
				$port = '';
			} else {
				list($hostname, $port) = explode(':', "$host:5432");
			}

			$connection_string = array();
			if ($hostname != '') $connection_string[] = "host='".str_replace("'", "\\'", $hostname)."'";
			if ($username != '') $connection_string[] = "user='".str_replace("'", "\\'", $username)."'";
			if ($password != '') $connection_string[] = "password='".str_replace("'", "\\'", $password)."'";
			if ($port     != '') $connection_string[] = "port='".str_replace("'", "\\'", $port)."'";
			if ($database != '') $connection_string[] = "dbname='".str_replace("'", "\\'", $database)."'";

			// We need to use PGSQL_CONNECT_FORCE_NEW because we require two connectoins (for isolated log message queries)
			$this->conn = pg_connect(implode(' ', $connection_string), PGSQL_CONNECT_FORCE_NEW);
		} finally {
			# TODO: this does not seem to work?! (at least not for CLI)
			$err = ob_get_contents();
			ob_end_clean();
		}

		if (!$this->conn) {
			throw new OIDplusConfigInitializationException(trim(_L('Connection to the database failed!').' ' . strip_tags($err)));
		}

		$this->already_prepared = array();
		$this->last_error = null;

		try {
			$this->query("SET NAMES 'utf8'");
		} catch (\Exception $e) {
		}
	}

	/**
	 * @return void
	 */
	protected function doDisconnect(): void {
		$this->already_prepared = array();
		if (!is_null($this->conn)) {
			pg_close($this->conn);
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
		$this->query('begin transaction');
		$this->intransaction = true;
	}

	/**
	 * @return void
	 * @throws OIDplusException
	 */
	public function transaction_commit(): void {
		$this->query('commit');
		$this->intransaction = false;
	}

	/**
	 * @return void
	 * @throws OIDplusException
	 */
	public function transaction_rollback(): void {
		$this->query('rollback');
		$this->intransaction = false;
	}

	/**
	 * @return string
	 */
	public function sqlDate(): string {
		return 'now()';
	}

	/**
	 * @return OIDplusSqlSlangPlugin
	 * @throws OIDplusConfigInitializationException
	 */
	protected function doGetSlang(): OIDplusSqlSlangPlugin {
		$slang = OIDplus::getSqlSlangPlugin('pgsql');
		if (is_null($slang)) {
			throw new OIDplusConfigInitializationException(_L('SQL-Slang plugin "%1" is missing. Please check if it exists in the directory "plugin/sqlSlang". If it is not existing, please recover it from an GIT/SVN snapshot or OIDplus archive file.','pgsql'));
		}
		return $slang;
	}

	/**
	 * @return array
	 */
	public function getExtendedInfo(): array {
		$host     = OIDplus::baseConfig()->getValue('PGSQL_HOST',     'localhost:5432');
		$username = OIDplus::baseConfig()->getValue('PGSQL_USERNAME', 'postgres');
		$password = OIDplus::baseConfig()->getValue('PGSQL_PASSWORD', '');
		$database = OIDplus::baseConfig()->getValue('PGSQL_DATABASE', 'oidplus');
		$socket   = OIDplus::baseConfig()->getValue('PGSQL_SOCKET',   '');
		if ($socket != '') {
			$hostname = $socket;
			$port = '';
		} else {
			list($hostname, $port) = explode(':', "$host:5432");
		}
		return array(
			_L('Hostname') => $hostname,
			_L('Port') => $port,
			_L('Socket') => $socket,
			_L('Database') => $database,
			_L('Username') => $username
		);
	}

}
