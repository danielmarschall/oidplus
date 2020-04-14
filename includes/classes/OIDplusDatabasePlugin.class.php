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

abstract class OIDplusDatabasePlugin extends OIDplusPlugin {
	protected $connected = false;
	protected $html = null;

	public abstract static function name(): string; // this is the name that is set to the configuration value OIDPLUS_DATABASE_PLUGIN to identify the database plugin
	public abstract function query(string $sql, /*?array*/ $prepared_args=null): OIDplusQueryResult;
	public abstract function insert_id(): int;
	public abstract function error(): string;
	public abstract function transaction_begin(): void;
	public abstract function transaction_commit(): void;
	public abstract function transaction_rollback(): void;
	protected abstract function doConnect(): void;
	protected abstract function doDisconnect(): void;
	
	public final function connect(): void {
		if ($this->connected) return;
		$this->beforeConnect();
		$this->doConnect();
		$this->connected = true;
		register_shutdown_function(array($this, 'disconnect'));
		$this->afterConnect();
	}
	
	public final function disconnect(): void {
		if (!$this->connected) return;
		$this->beforeDisconnect();
		$this->doDisconnect();
		$this->connected = false;
		$this->afterDisconnect();
	}

	public function natOrder($fieldname, $order='asc'): string {
	
		$order = strtolower($order);
		if (($order != 'asc') && ($order != 'desc')) {
			throw new OIDplusException("Invalid order '$order' (needs to be 'asc' or 'desc')");
		}

		$out = array();
		
		if ($this->slang() == 'pgsql') {
			$max_arc_len = OIDPLUS_MAX_OID_ARC_SIZE > 131072 ? 131072 : OIDPLUS_MAX_OID_ARC_SIZE; // Limit of the "numeric()" type
			
			// 1. Sort by namespace (oid, guid, ...)
			$out[] = "SPLIT_PART($fieldname, ':', 1) $order";
			
			// 2. Only if namespace is 'oid:': Sort OID as integer array
			$out[] = "STRING_TO_ARRAY(SPLIT_PART($fieldname, 'oid:', 2), '.')::numeric($max_arc_len)[] $order";
			
			// 3. Otherwise order by ID
			$out[] = "$fieldname $order";
			
		} else if ($this->slang() == 'mysql') {
			$max_arc_len = OIDPLUS_MAX_OID_ARC_SIZE > 65 ? 65 : OIDPLUS_MAX_OID_ARC_SIZE; // Limit of "decimal()" type
			
			// 1. sort by namespace (oid, guid, ...)
			$out[] = "SUBSTRING_INDEX($fieldname,':',1) $order";
			
			// 2. sort by first arc (0,1,2)
			$out[] = "SUBSTRING(SUBSTRING_INDEX($fieldname,'.',1), LENGTH(SUBSTRING_INDEX($fieldname,':',1))+2, $max_arc_len) $order";
			
			for ($i=2; $i<=OIDPLUS_MAX_OID_DEPTH; $i++) {
				// 3. Sort by the rest arcs one by one, not that MySQL can only handle decimal(65), not decimal($max_arc_len)
				$out[] = "cast(SUBSTRING(SUBSTRING_INDEX($fieldname,'.',$i), LENGTH(SUBSTRING_INDEX($fieldname,'.',".($i-1)."))+2, $max_arc_len) as decimal($max_arc_len)) $order";
			}

			// 4. as last resort, sort by the identifier itself, e.g. if the casts above did fail (happens if it is not an OID)			
			$out[] = "$fieldname $order";
			
		} else if ($this->slang() == 'mssql') {
			$max_arc_len = OIDPLUS_MAX_OID_ARC_SIZE;
		
			// 1. sort by namespace (oid, guid, ...)
			$out[] = "SUBSTRING($fieldname,1,CHARINDEX(':',$fieldname)-1) $order";
			
			for ($i=1; $i<=OIDPLUS_MAX_OID_DEPTH; $i++) {
				// 2. Sort by the rest arcs one by one, not that MySQL can only handle decimal(65), not decimal($max_arc_len)
				$out[] = "dbo.getOidArc($fieldname, $max_arc_len, $i) $order";
			}

			// 3. as last resort, sort by the identifier itself, e.g. if the function getOidArc always return 0 (happens if it is not an OID)			
			$out[] = "$fieldname $order";
		} else {
		
			// For (yet) unsupported DBMS, we do not offer natural sort
			$out[] = "$fieldname $order";

		}

		return implode(', ', $out); 
	}

	protected function beforeDisconnect(): void {}

	protected function afterDisconnect(): void {}

	protected function beforeConnect(): void {}

	protected function afterConnect(): void {
		// Check if the config table exists. This is important because the database version is stored in it
		$this->initRequireTables(array('config'));

		// Do the database tables need an update?
		// It is important that we do it immediately after connecting,
		// because the database structure might change and therefore various things might fail.
		// Note: The config setting "database_version" is inserted in setup/sql/...sql, not in the OIDplus core init

		$res = $this->query("SELECT value FROM ".OIDPLUS_TABLENAME_PREFIX."config WHERE name = 'database_version'");
		$row = $res->fetch_array();		
		if ($row == null) {
			throw new OIDplusConfigInitializationException('Cannot determine database version (the entry "database_version" inside the table "'.OIDPLUS_TABLENAME_PREFIX.'config" is probably missing)');
		}		
		$version = $row['value'];
		if (!is_numeric($version) || ($version < 200) || ($version > 999)) {
			throw new OIDplusConfigInitializationException('Entry "database_version" inside the table "'.OIDPLUS_TABLENAME_PREFIX.'config" seems to be wrong (expect number between 200 and 999)');
		}		
		while (file_exists($file = __DIR__."/../db_updates/update$version.inc.php")) {
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
	}
	
	private function initRequireTables($tableNames) {
		$msgs = array();
		foreach ($tableNames as $tableName) {
			if (!$this->tableExists(OIDPLUS_TABLENAME_PREFIX.$tableName)) {
				$msgs[] = 'Table '.OIDPLUS_TABLENAME_PREFIX.$tableName.' is missing!';
			}
		}
		if (count($msgs) > 0) {
			throw new OIDplusConfigInitializationException(implode("\n\n",$msgs));
		}
	}
	
	public function tableExists($tableName) {
		try {
			$this->query("select 0 from ".$tableName." where 1=0");
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	public function isConnected(): bool {
		return $this->connected;
	}

	public function init($html = true): void {
		$this->html = $html;
	}

	public function slang(): string {
		// The constant OIDPLUS_DBMS_SLANG is used as cache.
		// You can also put it in your config.inc.php if you want to enforce a slang to be used

		if (defined('OIDPLUS_DBMS_SLANG')) {
			return OIDPLUS_DBMS_SLANG;
		} else {
			try {
				// MySQL, MariaDB and PostgreSQL
				$vers = $this->query("select version() as dbms_version")->fetch_object()->dbms_version;
				$vers = strtolower($vers);
			} catch (Exception $e) {
				try {
					// Microsoft SQL Server
					$vers = $this->query("select @@version as dbms_version")->fetch_object()->dbms_version;
					$vers = strtolower($vers);
				} catch (Exception $e) {
					throw new OIDplusException("Cannot determine the slang of your DBMS (function 'version()' could not be called). Your DBMS is probably not supported.");
				}
			}

			$slang = null;
			if (strpos($vers, 'postgresql')           !== false) $slang = 'pgsql';
			if (strpos($vers, 'mysql')                !== false) $slang = 'mysql';
			if (strpos($vers, 'mariadb')              !== false) $slang = 'mysql';
			if (strpos($vers, 'microsoft sql server') !== false) $slang = 'mssql';
			if (!is_null($slang)) {
				define('OIDPLUS_DBMS_SLANG', $slang);
				return $slang;
			} else {
				throw new OIDplusException("Cannot determine the slang of your DBMS (we don't know what to do with the DBMS '$vers'). Your DBMS is probably not supported.");
			}
		}
	}
}

