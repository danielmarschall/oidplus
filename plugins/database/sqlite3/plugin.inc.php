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

class OIDplusDatabasePluginSQLite3 extends OIDplusDatabasePlugin {
	private $conn = null;
	private $prepare_cache = array();
	private $last_error = null; // do the same like MySQL+PDO, just to be equal in the behavior

	public static function getPluginInformation(): array {
		$out = array();
		$out['name'] = 'SQLite3';
		$out['author'] = 'ViaThinkSoft';
		$out['version'] = null;
		$out['descriptionHTML'] = null;
		return $out;
	}

	public static function name(): string {
		return "SQLite3";
	}

	public function doQuery(string $sql, /*?array*/ $prepared_args=null): OIDplusQueryResult {
		$this->last_error = null;
		if (is_null($prepared_args)) {
			try {
				$res = $this->conn->query($sql);
			} catch (Exception $e) {
				$res = false;
			}
			if ($res === false) {
				$this->last_error = $this->conn->lastErrorMsg();
				throw new OIDplusSQLException($sql, $this->error());
			} else {
				return new OIDplusQueryResultSQLite3($res);
			}
		} else {
			if (!is_array($prepared_args)) {
				throw new OIDplusException("'prepared_args' must be either NULL or an ARRAY.");
			}

			// convert ? ? ? to :param1 :param2 :param3 ...
			$sql = preg_replace_callback('@\\?@', function($found) {
				static $i = 0;
				$i++;
				return ':param'.$i;
			}, $sql);

			if (isset($this->prepare_cache[$sql])) {
				$stmt = $this->prepare_cache[$sql];
			} else {
				try {
					$stmt = $this->conn->prepare($sql);
				} catch (Exception $e) {
					$stmt = false;
				}
				if ($stmt === false) {
					$this->last_error = $this->conn->lastErrorMsg();
					throw new OIDplusSQLException($sql, 'Cannot prepare statement: '.$this->error());
				}
				$this->prepare_cache[$sql] = $stmt;
			}

			if ($stmt->paramCount() != count($prepared_args)) {
				throw new OIDplusException('Prepared argument list size not matching number of prepared statement arguments');
			}
			$i = 1;
			foreach ($prepared_args as &$value) {
				if (is_bool($value)) $value = $value ? '1' : '0';
				$stmt->bindValue(':param'.$i, $value, SQLITE3_TEXT);
				$i++;
			}

			try {
				$ps = $stmt->execute();
			} catch (Exception $e) {
				$ps = false;
			}
			if ($ps === false) {
				$this->last_error = $this->conn->lastErrorMsg();
				throw new OIDplusSQLException($sql, $this->error());
			}
			return new OIDplusQueryResultSQLite3($ps);
		}
	}

	public function insert_id(): int {
		try {
			// Note: This will always give results even for tables that do not
			// have autoincrements, because SQLite3 assigns an "autoindex" for every table,
			// e.g. the config table. Therefore, our testcase will fail.
			return (int)$this->conn->lastInsertRowID();
			//return (int)$this->query('select last_insert_rowid() as id')->fetch_object()->id;
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
		if (!class_exists('SQLite3')) throw new OIDplusConfigInitializationException('PHP extension "SQLite3" not installed');

		// Try connecting to the database
		try {
			$filename   = OIDplus::baseConfig()->getValue('SQLITE3_FILE', 'oidplus_sqlite3.db');
			$flags      = SQLITE3_OPEN_READWRITE/* | SQLITE3_OPEN_CREATE*/;
			$encryption = OIDplus::baseConfig()->getValue('SQLITE3_ENCRYPTION', '');

			$is_absolute_path = ((substr($filename,0,1) == '/') || (substr($filename,1,1) == ':'));
			if (!$is_absolute_path) {
				// Filename must be absolute path, since OIDplus can be called from several locations (e.g. registration wizard)
				$filename = __DIR__ . '/../../../' . $filename;
			}

			$this->conn = new SQLite3($filename, $flags, $encryption);
		} catch (Exception $e) {
			throw new OIDplusConfigInitializationException('Connection to the database failed! ' . $e->getMessage());
		}

		$this->conn->createCollation('NATURAL_CMP', 'strnatcmp'); // we need that for natSort()
		$this->conn->enableExceptions(true); // Throw exceptions instead of PHP warnings

		$this->prepare_cache = array();
		$this->last_error = null;

		$this->slang = self::getHardcodedSlangById('sqlite');
		if (is_null($this->slang)) {
			throw new OIDplusConfigInitializationException("Slang plugin 'sqlite' is missing");
		}
	}

	protected function doDisconnect()/*: void*/ {
		$this->prepare_cache = array();
		$this->conn = null;
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
		return 'datetime()';
	}

	public function natOrder($fieldname, $order='asc'): string {

		// This collation is defined in the database plugin using SQLite3::createCollation()
		return "$fieldname COLLATE NATURAL_CMP $order";

	}
}

class OIDplusQueryResultSQLite3 extends OIDplusQueryResult {
	protected $no_resultset;
	protected $res;
	protected $all_results = array();
	protected $cursor = 0;

	public function __construct($res) {
		if (is_bool($res) || ($res->numColumns() == 0)) {
			// Why do qe need to check numColumns() ?
			// We need to do this because SQLite3::query() will always
			// return a result, even for Non-SELECT queries.
			// If you call fetchArray(), the query (e.g. INSERT)
			// will be executed again.
			$this->no_resultset = true;
			return;
		}

		if (!$this->no_resultset) {
			$this->res = $res;
			while ($row = $this->res->fetchArray(SQLITE3_ASSOC)) {
				// we need that because there is no numRows() function!
				$this->all_results[] = $row;
			}
		}
	}

	public function __destruct() {
		$this->all_results = array();
		if (!is_null($this->res)) {
			$this->res->finalize();
			$this->res = null;
		}
	}

	public function containsResultSet(): bool {
		return !$this->no_resultset;
	}

	public function num_rows(): int {
		if ($this->no_resultset) throw new OIDplusException("The query has returned no result set (i.e. it was not a SELECT query)");
		return count($this->all_results);
	}

	public function fetch_array()/*: ?array*/ {
		if ($this->no_resultset) throw new OIDplusException("The query has returned no result set (i.e. it was not a SELECT query)");

		//$ret = $this->res->fetchArray(SQLITE3_ASSOC);
		$cursor = $this->cursor;
		if (!isset($this->all_results[$cursor])) return null;
		$ret = $this->all_results[$cursor];
		$cursor++;
		$this->cursor = $cursor;

		if ($ret === false) $ret = null;
		return $ret;
	}

	public function fetch_object()/*: ?object*/ {
		if ($this->no_resultset) throw new OIDplusException("The query has returned no result set (i.e. it was not a SELECT query)");

		$ary = $this->fetch_array();
		if (!$ary) return null;

		$obj = new stdClass;
		foreach ($ary as $name => $val) {
			$obj->$name = $val;
		}
		return $obj;
	}
}
