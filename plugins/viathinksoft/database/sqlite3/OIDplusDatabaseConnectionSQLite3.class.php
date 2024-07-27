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

namespace ViaThinkSoft\OIDplus\Plugins\viathinksoft\database\sqlite3;

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

class OIDplusDatabaseConnectionSQLite3 extends OIDplusDatabaseConnection {
	/**
	 * @var mixed|null
	 */
	private $conn = null;

	/**
	 * @var array
	 */
	private $prepare_cache = array();

	/**
	 * @var string|null
	 */
	private $last_error = null; // do the same like MySQL+PDO, just to be equal in the behavior

	/**
	 * @param string $sql
	 * @param array|null $prepared_args
	 * @return OIDplusQueryResultSQLite3
	 * @throws OIDplusException
	 */
	public function doQuery(string $sql, ?array $prepared_args=null): OIDplusQueryResult {
		$this->last_error = null;
		if (is_null($prepared_args)) {
			try {
				$res = $this->conn->query($sql);
			} catch (\Exception $e) {
				$res = false;
			}
			if ($res === false) {
				$this->last_error = $this->conn->lastErrorMsg();
				throw new OIDplusSQLException($sql, $this->error());
			} else {
				return new OIDplusQueryResultSQLite3($res);
			}
		} else {
			// convert ? ? ? to :param1 :param2 :param3 ...
			$sql = preg_replace_callback('@\\?@', function($found) {
				static $i = 0;
				$i++;
				return ':param'.$i;
			}, $sql, count($prepared_args), $count);

			if (isset($this->prepare_cache[$sql])) {
				$stmt = $this->prepare_cache[$sql];
			} else {
				try {
					$stmt = $this->conn->prepare($sql);
				} catch (\Exception $e) {
					$stmt = false;
				}
				if ($stmt === false) {
					$this->last_error = $this->conn->lastErrorMsg();
					throw new OIDplusSQLException($sql, _L('Cannot prepare statement').': '.$this->error());
				}
				$this->prepare_cache[$sql] = $stmt;
			}

			$i = 0;
			foreach ($prepared_args as &$value) {
				$i++;
				if ($i > $count) break;
				if (is_bool($value)) $value = $value ? '1' : '0';
				$stmt->bindValue(':param'.$i, $value, SQLITE3_TEXT);
			}
			unset($value);

			try {
				$ps = $stmt->execute();
			} catch (\Exception $e) {
				$ps = false;
			}
			if ($ps === false) {
				$this->last_error = $this->conn->lastErrorMsg();
				throw new OIDplusSQLException($sql, $this->error());
			}
			return new OIDplusQueryResultSQLite3($ps);
		}
	}

	/**
	 * @return int
	 */
	public function doInsertId(): int {
		try {
			// Note: This will always give results even for tables that do not
			// have autoincrements, because SQLite3 assigns an "autoindex" for every table,
			// e.g. the config table. Therefore, our testcase will fail.
			return (int)$this->conn->lastInsertRowID();
			//return (int)$this->query('select last_insert_rowid() as id')->fetch_object()->id;
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
	 */
	protected function doConnect(): void {
		if (!class_exists('SQLite3')) throw new OIDplusConfigInitializationException(_L('PHP extension "%1" not installed','SQLite3'));

		// Try connecting to the database
		try {
			$filename   = OIDplus::baseConfig()->getValue('SQLITE3_FILE', OIDplus::getUserDataDir("database").'oidplus.db');
			$flags      = SQLITE3_OPEN_READWRITE/* | SQLITE3_OPEN_CREATE*/;
			$encryption = OIDplus::baseConfig()->getValue('SQLITE3_ENCRYPTION', '');

			$is_absolute_path = ((substr($filename,0,1) == '/') || (substr($filename,1,1) == ':'));
			if (!$is_absolute_path) {
				// Filename must be absolute path, since OIDplus can be called from several locations (e.g. registration wizard)
				$filename = OIDplus::localpath().$filename;
			}

			$this->conn = new \SQLite3($filename, $flags, $encryption);
		} catch (\Exception $e) {
			throw new OIDplusConfigInitializationException(trim(_L('Connection to the database failed!').' ' . $e->getMessage()));
		}

		$this->conn->createCollation('NATURAL_CMP', 'strnatcmp'); // we need that for natSort()
		$this->conn->enableExceptions(true); // Throw exceptions instead of PHP warnings (preferred as of PHP 8.3)

		$this->prepare_cache = array();
		$this->last_error = null;
	}

	/**
	 * @return void
	 */
	protected function doDisconnect(): void {
		$this->prepare_cache = array();
		$this->conn = null;
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
		return 'datetime()';
	}

	/**
	 * @param bool $mustExist
	 * @return OIDplusSqlSlangPlugin|null
	 * @throws OIDplusConfigInitializationException
	 */
	protected function doGetSlang(bool $mustExist=true): ?OIDplusSqlSlangPlugin {
		$slang = OIDplus::getSqlSlangPlugin('sqlite');
		if (is_null($slang)) {
			throw new OIDplusConfigInitializationException(_L('SQL-Slang plugin "%1" is missing. Please check if it exists in the directory "plugin/sqlSlang". If it is not existing, please recover it from an GIT/SVN snapshot or OIDplus archive file.','sqlite'));
		}
		return $slang;
	}

	/**
	 * @return array
	 */
	public function getExtendedInfo(): array {
		$filename  = OIDplus::baseConfig()->getValue('SQLITE3_FILE', OIDplus::getUserDataDir("database").'oidplus.db');
		return array(
			_L('Filename') => $filename
		);
	}

}
