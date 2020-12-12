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

class OIDplusDatabaseConnectionSQLite3 extends OIDplusDatabaseConnection {
	private $conn = null;
	private $prepare_cache = array();
	private $last_error = null; // do the same like MySQL+PDO, just to be equal in the behavior

	public static function getPlugin(): OIDplusDatabasePlugin {
		return new OIDplusDatabasePluginSQLite3();
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
				throw new OIDplusException(_L('"prepared_args" must be either NULL or an ARRAY.'));
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
					throw new OIDplusSQLException($sql, _L('Cannot prepare statement').': '.$this->error());
				}
				$this->prepare_cache[$sql] = $stmt;
			}

			if ($stmt->paramCount() != count($prepared_args)) {
				throw new OIDplusException(_L('Prepared argument list size not matching number of prepared statement arguments'));
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
		if (!class_exists('SQLite3')) throw new OIDplusConfigInitializationException(_L('PHP extension "%1" not installed','SQLite3'));

		// Try connecting to the database
		try {
			$filename   = OIDplus::baseConfig()->getValue('SQLITE3_FILE', 'userdata/database/oidplus.db');
			$flags      = SQLITE3_OPEN_READWRITE/* | SQLITE3_OPEN_CREATE*/;
			$encryption = OIDplus::baseConfig()->getValue('SQLITE3_ENCRYPTION', '');

			$is_absolute_path = ((substr($filename,0,1) == '/') || (substr($filename,1,1) == ':'));
			if (!$is_absolute_path) {
				// Filename must be absolute path, since OIDplus can be called from several locations (e.g. registration wizard)
				$filename = OIDplus::localpath().$filename;
			}

			$this->conn = new SQLite3($filename, $flags, $encryption);
		} catch (Exception $e) {
			throw new OIDplusConfigInitializationException(_L('Connection to the database failed!').' ' . $e->getMessage());
		}

		$this->conn->createCollation('NATURAL_CMP', 'strnatcmp'); // we need that for natSort()
		$this->conn->enableExceptions(true); // Throw exceptions instead of PHP warnings

		$this->prepare_cache = array();
		$this->last_error = null;
	}

	protected function doDisconnect()/*: void*/ {
		$this->prepare_cache = array();
		$this->conn = null;
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
		return 'datetime()';
	}

	public function natOrder($fieldname, $order='asc'): string {

		// This collation is defined in the database plugin using SQLite3::createCollation()
		return "$fieldname COLLATE NATURAL_CMP $order";

	}

	public function getSlang(bool $mustExist=true)/*: ?OIDplusSqlSlangPlugin*/ {
		$slang = OIDplus::getSqlSlangPlugin('sqlite');
		if (is_null($slang)) {
			throw new OIDplusConfigInitializationException(_L('SQL-Slang plugin "%1" is missing. Please check if it exists in the directory "plugin/sqlSlang". If it is not existing, please recover it from an SVN snapshot or OIDplus ZIP file.','sqlite'));
		}
		return $slang;
	}
}