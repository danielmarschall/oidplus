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

class OIDplusDatabaseConnectionPgSql extends OIDplusDatabaseConnection {
	private $conn = null;
	private $already_prepared = array();
	private $last_error = null; // do the same like MySQL+PDO, just to be equal in the behavior

	public static function getPlugin(): OIDplusDatabasePlugin {
		return new OIDplusDatabasePluginPgSql();
	}

	public function doQuery(string $sql, /*?array*/ $prepared_args=null): OIDplusQueryResult {
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
			if (!is_array($prepared_args)) {
				throw new OIDplusException(_L('"prepared_args" must be either NULL or an ARRAY.'));
			}

			// convert ? ? ? to $1 $2 $3
			$sql = preg_replace_callback('@\\?@', function($found) {
				static $i = 0;
				$i++;
				return '$'.$i;
			}, $sql);

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

			$ps = pg_execute($this->conn, $prepare_name, $prepared_args);
			if ($ps === false) {
				$this->last_error = pg_last_error($this->conn);
				throw new OIDplusSQLException($sql, $this->error());
			}
			return new OIDplusQueryResultPgSql($ps);
		}
	}

	public function insert_id(): int {
		try {
			return (int)$this->query('select lastval() as id')->fetch_object()->id;
		} catch (Exception $e) {
			return 0;
		}
	}

	public function error(): string {
		$err = $this->last_error;
		if ($err == null) $err = '';
		return $err;
	}

	protected function doConnect()/*: void*/ {
		if (!function_exists('pg_connect')) throw new OIDplusConfigInitializationException(_L('PHP extension "%1" not installed','PostgreSQL'));

		// Try connecting to the database
		ob_start();
		$err = '';
		try {
			$host     = OIDplus::baseConfig()->getValue('PGSQL_HOST',     'localhost:5432');
			$username = OIDplus::baseConfig()->getValue('PGSQL_USERNAME', 'postgres');
			$password = OIDplus::baseConfig()->getValue('PGSQL_PASSWORD', '');
			$database = OIDplus::baseConfig()->getValue('PGSQL_DATABASE', 'oidplus');
			list($hostname, $port) = explode(':', "$host:5432");
			// We need to use PGSQL_CONNECT_FORCE_NEW because we require two connectoins (for isolated log message queries)
			$this->conn = pg_connect("host=$hostname user=$username password=$password port=$port dbname=$database", PGSQL_CONNECT_FORCE_NEW);
		} finally {
			# TODO: this does not seem to work?! (at least not for CLI)
			$err = ob_get_contents();
			ob_end_clean();
		}

		if (!$this->conn) {
			throw new OIDplusConfigInitializationException(_L('Connection to the database failed!').' ' . strip_tags($err));
		}

		$this->already_prepared = array();
		$this->last_error = null;

		try {
			$this->query("SET NAMES 'utf8'");
		} catch (Exception $e) {
		}
	}

	protected function doDisconnect()/*: void*/ {
		$this->already_prepared = array();
		if (!is_null($this->conn)) {
			pg_close($this->conn);
			$this->conn = null;
		}
	}

	private $intransaction = false;

	public function transaction_supported(): bool {
		return true;
	}

	public function transaction_level(): int {
		return $this->intransaction ? 1 : 0;
	}

	public function transaction_begin()/*: void*/ {
		if ($this->intransaction) throw new OIDplusException(_L('Nested transactions are not supported by this database plugin.'));
		$this->query('begin transaction');
		$this->intransaction = true;
	}

	public function transaction_commit()/*: void*/ {
		$this->query('commit');
		$this->intransaction = false;
	}

	public function transaction_rollback()/*: void*/ {
		$this->query('rollback');
		$this->intransaction = false;
	}

	public function sqlDate(): string {
		return 'now()';
	}

	protected function doGetSlang(bool $mustExist=true)/*: ?OIDplusSqlSlangPlugin*/ {
		$slang = OIDplus::getSqlSlangPlugin('pgsql');
		if (is_null($slang)) {
			throw new OIDplusConfigInitializationException(_L('SQL-Slang plugin "%1" is missing. Please check if it exists in the directory "plugin/sqlSlang". If it is not existing, please recover it from an SVN snapshot or OIDplus TAR.GZ file.','pgsql'));
		}
		return $slang;
	}
}
