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

abstract class OIDplusDatabaseConnection {
	protected /*bool*/ $connected = false;
	protected /*?bool*/ $html = null;
	protected /*?string*/ $last_query = null;

	protected abstract function doQuery(string $sql, /*?array*/ $prepared_args=null): OIDplusQueryResult;
	public abstract function error(): string;
	public abstract function transaction_begin()/*: void*/;
	public abstract function transaction_commit()/*: void*/;
	public abstract function transaction_rollback()/*: void*/;
	public abstract function transaction_supported(): bool;
	public abstract function transaction_level(): int;
	protected abstract function doConnect()/*: void*/;
	protected abstract function doDisconnect()/*: void*/;

	public function insert_id(): int {
		// This is the "fallback" variant. If your database provider (e.g. PDO) supports
		// a function to detect the last inserted id, please override this
		// function in order to use that specialized function (since it is usually
		// more reliable).
		return $this->getSlang()->insert_id($this);
	}

	public final function query(string $sql, /*?array*/ $prepared_args=null): OIDplusQueryResult {

		$query_logfile = OIDplus::baseConfig()->getValue('QUERY_LOGFILE', '');
		if (!empty($query_logfile)) {
			$ts = explode(" ",microtime());
			$ts = date("Y-m-d H:i:s",$ts[1]).substr((string)$ts[0],1,4);
			static $log_session_id = "";
			if (empty($log_session_id)) {
				$log_session_id = rand(10000,99999);
			}
			$file = isset($_SERVER['REQUEST_URI']) ? ' | '.$_SERVER['REQUEST_URI'] : '';
			file_put_contents($query_logfile, "$ts <$log_session_id$file> $sql\n", FILE_APPEND);
		}

		$this->last_query = $sql;
		$sql = str_replace('###', OIDplus::baseConfig()->getValue('TABLENAME_PREFIX', ''), $sql);
		return $this->doQuery($sql, $prepared_args);
	}

	public final function connect()/*: void*/ {
		if ($this->connected) return;
		$this->beforeConnect();
		$this->doConnect();
		$this->connected = true;
		register_shutdown_function(array($this, 'disconnect'));
		$this->afterConnectMandatory();
		$this->afterConnect();
	}

	public final function disconnect()/*: void*/ {
		if (!$this->connected) return;
		$this->beforeDisconnect();
		$this->doDisconnect();
		$this->connected = false;
		$this->afterDisconnect();
	}

	public function natOrder($fieldname, $order='asc'): string {
		$slang = $this->getSlang();
		if (!is_null($slang)) {
			return $slang->natOrder($fieldname, $order);
		} else {
			$order = strtolower($order);
			if (($order != 'asc') && ($order != 'desc')) {
				throw new OIDplusException("Invalid order '$order' (needs to be 'asc' or 'desc')");
			}

			// For (yet) unsupported DBMS, we do not offer natural sort
			return "$fieldname $order";
		}
	}

	protected function beforeDisconnect()/*: void*/ {}

	protected function afterDisconnect()/*: void*/ {}

	protected function beforeConnect()/*: void*/ {}

	protected function afterConnect()/*: void*/ {}

	private function afterConnectMandatory()/*: void*/ {
		// Check if the config table exists. This is important because the database version is stored in it
		$this->initRequireTables(array('config'));

		// Do the database tables need an update?
		// It is important that we do it immediately after connecting,
		// because the database structure might change and therefore various things might fail.
		// Note: The config setting "database_version" is inserted in setup/sql/...sql, not in the OIDplus core init

		$res = $this->query("SELECT value FROM ###config WHERE name = 'database_version'");
		$row = $res->fetch_array();
		if ($row == null) {
			throw new OIDplusConfigInitializationException('Cannot determine database version (the entry "database_version" inside the table "###config" is probably missing)');
		}
		$version = $row['value'];
		if (!is_numeric($version) || ($version < 200) || ($version > 999)) {
			throw new OIDplusConfigInitializationException('Entry "database_version" inside the table "###config" seems to be wrong (expect number between 200 and 999)');
		}

		while (file_exists($file = OIDplus::basePath().'/includes/db_updates/update'.$version.'.inc.php')) {
			$prev_version = $version;
			include $file; // run update-script
			if ($version != $prev_version+1) {
				// This should usually not happen, since the update-file should increase the version
				// or throw an Exception by itself
				throw new OIDplusException("Database update $prev_version -> ".($prev_version+1)." failed (script reports new version to be $version)");
			}
		}

		// Now that our database is up-to-date, we check if database tables are existing
		// without config table, because it was checked above
		$this->initRequireTables(array('objects', 'asn1id', 'iri', 'ra'/*, 'config'*/));

		// In case an auto-detection of the slang is required (for generic providers like PDO or ODBC),
		// we must not be inside a transaction, because the detection requires intentionally submitting
		// invalid queries to detect the correct DBMS. If we would be inside a transaction, providers like
		// PDO would automatically roll-back. Therefore, we detect the slang right at the beginning,
		// before any transaction is used.
		$this->getSlang();
	}

	private function initRequireTables($tableNames)/*: void*/ {
		$msgs = array();
		foreach ($tableNames as $tableName) {
			$prefix = OIDplus::baseConfig()->getValue('TABLENAME_PREFIX', '');
			if (!$this->tableExists($prefix.$tableName)) {
				$msgs[] = 'Table '.$prefix.$tableName.' is missing!';
			}
		}
		if (count($msgs) > 0) {
			throw new OIDplusConfigInitializationException(implode("\n\n",$msgs));
		}
	}

	public function tableExists($tableName): bool {
		try {
			// Attention: This query could interrupt transactions if Rollback-On-Error is enabled
			$this->query("select 0 from ".$tableName." where 1=0");
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	public function isConnected(): bool {
		return $this->connected;
	}

	public function init($html = true)/*: void*/ {
		$this->html = $html;
	}

	public function sqlDate(): string {
		$slang = $this->getSlang();
		if (!is_null($slang)) {
			return $slang->sqlDate();
		} else {
			return "'" . datetime('Y-m-d H:i:s') . "'";
		}
	}

	private /*?OIDplusSqlSlangPlugin*/ $slangCache = null;
	public function getSlang(bool $mustExist=true)/*: ?OIDplusSqlSlangPlugin*/ {
		if (is_null($this->slangCache)) {
			if (OIDplus::baseConfig()->exists('FORCE_DBMS_SLANG')) {
				$name = OIDplus::baseConfig()->getValue('FORCE_DBMS_SLANG', '');
				$this->slangCache = OIDplus::getSqlSlangPlugin($name);
				if ($mustExist && is_null($this->slangCache)) {
					throw new OIDplusConfigInitializationException("Enforced SQL slang (via setting FORCE_DBMS_SLANG) '$name' does not exist.");
				}
			} else {
				foreach (OIDplus::getSqlSlangPlugins() as $plugin) {
					if ($plugin->detect($this)) {
						$this->slangCache = $plugin;
						break;
					}
				}
				if ($mustExist && is_null($this->slangCache)) {
					throw new OIDplusException("Cannot determine the SQL slang of your DBMS. Your DBMS is probably not supported.");
				}
			}
		}

		return $this->slangCache;
	}
}

