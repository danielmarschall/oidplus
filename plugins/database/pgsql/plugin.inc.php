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

class OIDplusDatabasePluginPgSql extends OIDplusDatabasePlugin {
	private $conn = null;
	private $already_prepared = array();
	private $last_error = null; // do the same like MySQL+PDO, just to be equal in the behavior

	public static function getPluginInformation(): array {
		$out = array();
		$out['name'] = 'PostgreSQL';
		$out['author'] = 'ViaThinkSoft';
		$out['version'] = null;
		$out['descriptionHTML'] = null;
		return $out;
	}

	public static function id(): string {
		return "PgSQL";
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
				throw new OIDplusException("'prepared_args' must be either NULL or an ARRAY.");
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
					throw new OIDplusSQLException($sql, 'Cannot prepare statement: '.$this->error());
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
		if (!function_exists('pg_connect')) throw new OIDplusConfigInitializationException('PHP extension "PostgreSQL" not installed');

		// Try connecting to the database
		ob_start();
		$err = '';
		try {
			$host     = OIDplus::baseConfig()->getValue('PGSQL_HOST',     'localhost:5432');
			$username = OIDplus::baseConfig()->getValue('PGSQL_USERNAME', 'postgres');
			$password = OIDplus::baseConfig()->getValue('PGSQL_PASSWORD', '');
			$database = OIDplus::baseConfig()->getValue('PGSQL_DATABASE', 'oidplus');
			list($hostname, $port) = explode(':', "$host:5432");
			$this->conn = pg_connect("host=$hostname user=$username password=$password port=$port dbname=$database");
		} finally {
			# TODO: this does not seem to work?! (at least not for CLI)
			$err = ob_get_contents();
			ob_end_clean();
		}

		if (!$this->conn) {
			throw new OIDplusConfigInitializationException('Connection to the database failed! ' . strip_tags($err));
		}

		$this->already_prepared = array();
		$this->last_error = null;

		try {
			$this->query("SET NAMES 'utf8'");
		} catch (Exception $e) {
		}

		$this->slang = self::getHardcodedSlangById('pgsql');
		if (is_null($this->slang)) {
			throw new OIDplusConfigInitializationException("Slang plugin 'pgsql' is missing");
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

	public function transaction_begin()/*: void*/ {
		if ($this->intransaction) throw new OIDplusException("Nested transactions are not supported by this database plugin.");
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
}

class OIDplusQueryResultPgSql extends OIDplusQueryResult {
	protected $no_resultset;
	protected $res;

	public function __construct($res) {
		$this->no_resultset = is_bool($res);

		if (!$this->no_resultset) {
			$this->res = $res;
		}
	}

	public function __destruct() {
		if ($this->res) {
			pg_free_result($this->res);
		}
	}

	public function containsResultSet(): bool {
		return !$this->no_resultset;
	}

	public function num_rows(): int {
		if ($this->no_resultset) throw new OIDplusException("The query has returned no result set (i.e. it was not a SELECT query)");
		return pg_num_rows($this->res);
	}

	public function fetch_array()/*: ?array*/ {
		if ($this->no_resultset) throw new OIDplusException("The query has returned no result set (i.e. it was not a SELECT query)");
		$ret = pg_fetch_array($this->res, null, PGSQL_ASSOC);
		if ($ret === false) $ret = null;
		if (!is_null($ret)) {
			foreach ($ret as $key => &$value){
				$type = pg_field_type($this->res,pg_field_num($this->res, $key));
				if ($type == 'bool'){
					$value = ($value == 't');
				}
			}
		}
		return $ret;
	}

	public function fetch_object()/*: ?object*/ {
		if ($this->no_resultset) throw new OIDplusException("The query has returned no result set (i.e. it was not a SELECT query)");
		$ret = pg_fetch_object($this->res);
		if ($ret === false) $ret = null;
		if (!is_null($ret)) {
			foreach ($ret as $key => &$value){
				$type = pg_field_type($this->res,pg_field_num($this->res, $key));
				if ($type == 'bool'){
					$value = ($value == 't');
				}
			}
		}
		return $ret;
	}
}
