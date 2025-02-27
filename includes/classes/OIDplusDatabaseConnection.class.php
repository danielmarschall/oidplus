<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2025 Daniel Marschall, ViaThinkSoft
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

namespace ViaThinkSoft\OIDplus\Core;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

abstract class OIDplusDatabaseConnection extends OIDplusBaseClass {
	/**
	 * @var bool
	 */
	protected bool $connected = false;

	/**
	 * @var bool|null
	 */
	protected ?bool $html = null;

	/**
	 * @var string|null
	 */
	protected ?string $last_query = null;

	/**
	 * @var bool
	 */
	protected bool $slangDetectionDone = false;

	/**
	 * @var OIDplusSqlSlangPlugin
	 */
	private ?OIDplusSqlSlangPlugin $slangCache = null;

	/**
	 * @param string $sql
	 * @param array|null $prepared_args
	 * @return OIDplusQueryResult
	 * @throws OIDplusException
	 */
	protected abstract function doQuery(string $sql, ?array $prepared_args=null): OIDplusQueryResult;

	/**
	 * @return string
	 */
	public abstract function error(): string;

	/**
	 * @return void
	 */
	public abstract function transaction_begin(): void;

	/**
	 * @return void
	 */
	public abstract function transaction_commit(): void;

	/**
	 * @return void
	 */
	public abstract function transaction_rollback(): void;

	/**
	 * @return bool
	 */
	public abstract function transaction_supported(): bool;

	/**
	 * @return int
	 */
	public abstract function transaction_level(): int;

	/**
	 * @return void
	 */
	protected abstract function doConnect(): void;

	/**
	 * @return void
	 */
	protected abstract function doDisconnect(): void;

	/**
	 * @return OIDplusDatabasePlugin|null
	 */
	public function getPlugin(): ?OIDplusDatabasePlugin {
		$plugins = OIDplus::getDatabasePlugins();
		foreach ($plugins as $plugin) {
			if (get_class($this) == get_class($plugin::newConnection())) {
				return $plugin;
			}
		}
		return null;
	}

	/**
	 * @return int
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	protected function doInsertId(): int {
		// This is the "fallback" variant. If your database provider (e.g. PDO) supports
		// a function to detect the last inserted id, please override this
		// function in order to use that specialized function (since it is usually
		// more reliable).
		return $this->getSlang()->insert_id($this);
	}

	/**
	 * @return int
	 * @throws OIDplusException
	 */
	public final function insert_id(): int {
		// DM 04 Apr 2023: Added, because MSSQL's @@IDENTITY, PgSQL, and SQLite3 does not reset after
		// a Non-Insert query (this is a test case in dev/test_database_plugins).
		// Note that the INSERT could be hidden inside a Stored Procedure; we don't support (or need) that yet.
		if (!str_starts_with(trim(strtolower($this->last_query)),'insert')) return 0;

		return $this->doInsertId(); // doInsertId() can be overridden, but insert_id() must not be overridden
	}

	/**
	 * Get the rows affected, for either SELECT, INSERT, DELETE, UPDATE
	 * @return int
	 */
	public function rowsAffected(): int {
		return -1; // -1 means not implemented
	}

	/**
	 * @param string $sql
	 * @return array[]
	 * @throws OIDplusException
	 */
	public final function getTable(string $sql): array {
		$out = array();
		$res = $this->query($sql);
		while ($row = $res->fetch_array()) {
			$out[] = /*yield*/ $row;
		}
		return $out;
	}

	/**
	 * @param string $sql
	 * @return mixed|null
	 * @throws OIDplusException
	 */
	public final function getScalar(string $sql) {
		$res = $this->query($sql);
		$row = $res->fetch_array();
		return $row ? reset($row) : null;
	}

	/**
	 * @param string $sql
	 * @param array|null $prepared_args
	 * @return OIDplusQueryResult
	 * @throws OIDplusException
	 */
	public final function query(string $sql, ?array $prepared_args=null): OIDplusQueryResult {

		$query_logfile = OIDplus::baseConfig()->getValue('QUERY_LOGFILE', '');
		if (!empty($query_logfile)) {
			$start = microtime(true);
		}

		$this->last_query = $sql;
		$sql = str_replace('###', OIDplus::baseConfig()->getValue('TABLENAME_PREFIX', ''), $sql);

		if ($this->slangDetectionDone) {
			$sql = $this->getSlang()->filterQuery($sql);
		}

		$res = $this->doQuery($sql, $prepared_args);

		if ($this->slangDetectionDone) {
			$this->getSlang()->reviewResult($res, $sql, $prepared_args);
		}

		if (!empty($query_logfile)) {
			$end = microtime(true);
			$ts = explode(" ",microtime());
			$ts = date("Y-m-d H:i:s",intval($ts[1])).substr((string)$ts[0],1,4);
			if (is_null(OIDplus::getCurrentContext()->dbLogSessionId)) {
				OIDplus::getCurrentContext()->dbLogSessionId = rand(10000,99999);
			}
			$file = isset($_SERVER['REQUEST_URI']) ? ' | '.$_SERVER['REQUEST_URI'] : '';
			// file_put_contents($query_logfile, "$ts <".OIDplus::getCurrentContext()->dbLogSessionId."$file> [".($end-$start)." sec] $sql ".print_r($prepared_args,true)."\n", FILE_APPEND);
			file_put_contents($query_logfile, "$ts <".OIDplus::getCurrentContext()->dbLogSessionId."$file> [".($end-$start)." sec] $sql\n", FILE_APPEND);
		}

		return $res;
	}

	/**
	 * @return void
	 * @throws OIDplusException
	 */
	public final function connect(): void {
		if ($this->connected) return;

		$bcKeys = OIDplus::baseConfig()->getAllKeys();
		foreach ($bcKeys as $bkKey) {
			$val = OIDplus::baseConfig()->getValue($bkKey, '');
			if (is_string($val) && preg_match('@(database[/\\\\]oidplus_(empty|example)\\.(db|db3|sqlite|sqlite3|mdb|accdb))@i', $val, $m)) {
				throw new OIDplusConfigInitializationException(_L('It looks like you are trying to use the template database file %1 in your base configuration. Since this file gets overridden by software updates, you must copy the template file and use this copy instead.', $m[1]));
			}
		}

		$this->beforeConnect();
		$this->doConnect();
		$this->connected = true;
		OIDplus::register_shutdown_function(array($this, 'disconnect'));
		$this->afterConnectMandatory();
		$this->afterConnect();
	}

	/**
	 * @return void
	 */
	public final function disconnect(): void {
		if (!$this->connected) return;
		$this->beforeDisconnect();
		$this->doDisconnect();
		$this->connected = false;
		$this->afterDisconnect();
	}

	/**
	 * @return void
	 */
	protected function beforeDisconnect(): void {}

	/**
	 * @return void
	 */
	protected function afterDisconnect(): void {}

	/**
	 * @return void
	 */
	protected function beforeConnect(): void {}

	/**
	 * @return void
	 */
	protected function afterConnect(): void {}

	/**
	 * @return void
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	private function afterConnectMandatory(): void {
		// In case an auto-detection of the slang is required (for generic providers like PDO or ODBC),
		// we must not be inside a transaction, because the detection requires intentionally submitting
		// invalid queries to detect the correct DBMS. If we would be inside a transaction, providers like
		// PDO would automatically roll-back. Therefore, we detect the slang right at the beginning,
		// before any transaction is used.
		$this->getSlang();

		// Check if the config table exists. This is important because the database version is stored in it
		$this->initRequireTables(array('config'));

		// Do the database tables need an update?
		// It is important that we do it immediately after connecting,
		// because the database structure might change and therefore various things might fail.
		require_once __DIR__.'/../db_updates/run.inc.php';
		oidplus_dbupdate($this);

		// Now that our database is up-to-date, we check if database tables are existing
		// without config table, because it was checked above
		$this->initRequireTables(array('objects', 'asn1id', 'iri', 'ra'/*, 'config'*/));
	}

	/**
	 * @param string[] $tableNames
	 * @return void
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	private function initRequireTables(array $tableNames): void {
		$msgs = array();

		// Check for a general database error like a locked file DBMS
		// which would raise a false warning "Table oidplus_config missing"
		// if we wouldn't do this fake query first.
		$this->query("select 0");

		$prefix = OIDplus::baseConfig()->getValue('TABLENAME_PREFIX', '');
		foreach ($tableNames as $tableName) {
			if (!$this->tableExists($prefix.$tableName)) {
				$msgs[] = _L('Table %1 is missing!',$prefix.$tableName);
			}
		}
		if (count($msgs) > 0) {
			throw new OIDplusConfigInitializationException(implode("\n\n",$msgs));
		}
	}

	/**
	 * @param string $tableName
	 * @return bool
	 */
	public function tableExists(string $tableName): bool {
		return $this->getSlang()->tableExists($this, $tableName);
	}

	/**
	 * @return bool
	 */
	public function isConnected(): bool {
		return $this->connected;
	}

	/**
	 * @param bool $html
	 * @return void
	 */
	public function init(bool $html=true): void {
		$this->html = $html;
	}

	/**
	 * @return string
	 * @throws OIDplusException
	 */
	public function sqlDate(): string {
		return $this->getSlang()->sqlDate();
	}

	/**
	 * @return OIDplusSqlSlangPlugin
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	protected function doGetSlang(): OIDplusSqlSlangPlugin {
		$res = null;

		if (OIDplus::baseConfig()->exists('FORCE_DBMS_SLANG')) {
			$name = OIDplus::baseConfig()->getValue('FORCE_DBMS_SLANG', '');
			$res = OIDplus::getSqlSlangPlugin($name);
			if (is_null($res)) {
				throw new OIDplusConfigInitializationException(_L('Enforced SQL slang (via setting FORCE_DBMS_SLANG) "%1" does not exist.',$name));
			}
		} else {
			foreach (OIDplus::getSqlSlangPlugins() as $plugin) {
				if ($plugin->detect($this)) {
					if (OIDplus::baseConfig()->getValue('DEBUG') && !is_null($res)) {

						$detected = array();
						foreach (OIDplus::getSqlSlangPlugins() as $plugin) {
							if ($plugin->detect($this)) {
								$detected[] = get_class($plugin);
							}
						}

						throw new OIDplusException(_L('DB-Slang detection failed: Multiple slangs were detected (%1). Use base config setting FORCE_DBMS_SLANG to define one.', implode(', ',$detected)));
					}

					$res = $plugin;

					if (!OIDplus::baseConfig()->getValue('DEBUG')) {
						break;
					}
				}
			}
			if (is_null($res)) {
				throw new OIDplusException(_L('Cannot determine the SQL slang of your DBMS. Your DBMS is probably not supported.'));
			}
		}

		return $res;
	}

	/**
	 * @return OIDplusSqlSlangPlugin
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	public final function getSlang(): OIDplusSqlSlangPlugin {
		if ($this->slangDetectionDone) {
			return $this->slangCache;
		}

		$this->slangCache = $this->doGetSlang();
		$this->slangDetectionDone = true;
		return $this->slangCache;
	}

	/**
	 * @return array
	 */
	public function getExtendedInfo(): array {
		return array();
	}
}
