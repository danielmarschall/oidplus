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

abstract class OIDplusDataBasePlugin extends OIDplusPlugin {
	protected $connected = false;
	protected $html = null;

	public abstract static function name(): string; // this is the name that is set to the configuration value OIDPLUS_DATABASE_PLUGIN to identify the database plugin
	public abstract function query(string $sql, /*?array*/ $prepared_args=null): OIDplusQueryResult;
	public abstract function insert_id(): int;
	public abstract function error(): string;
	public abstract function transaction_begin(): void;
	public abstract function transaction_commit(): void;
	public abstract function transaction_rollback(): void;

	public function natOrder($fieldname, $order='asc'): string {
	
		$order = strtolower($order);
		if (($order != 'asc') && ($order != 'desc')) {
			throw new Exception("Invalid order '$order' (needs to be 'asc' or 'desc')");
		}

		$maxdepth = 100; // adjust this if you have performance issues (only MySQL)
		$max_arc_len = 1000;
		
		$out = array();
		
		if ($this->slang() == 'pgsql') {
			
			// 1. Sort by namespace (oid, guid, ...)
			$out[] = "SPLIT_PART($fieldname, ':', 1) $order";
			
			// 2. Only if namespace is 'oid:': Sort OID as integer array
			$out[] = "STRING_TO_ARRAY(SPLIT_PART($fieldname, 'oid:', 2), '.')::numeric($max_arc_len)[] $order";
			
			// 3. Otherwise order by ID
			$out[] = "$fieldname $order";
			
		} else if ($this->slang() == 'mysql') {
			
			// 1. sort by namespace (oid, guid, ...)
			$out[] = "SUBSTRING_INDEX($fieldname,':',1) $order";
			
			// 2. sort by first arc (0,1,2)
			$out[] = "SUBSTRING(SUBSTRING_INDEX($fieldname,'.',1), LENGTH(SUBSTRING_INDEX($fieldname,':',1))+2, $max_arc_len) $order";
			
			for ($i=2; $i<=$maxdepth; $i++) {
				// 3. Sort by the rest arcs one by one, not that MySQL can only handle decimal(65), not decimal($max_arc_len)
				$out[] = "cast(SUBSTRING(SUBSTRING_INDEX($fieldname,'.',$i), LENGTH(SUBSTRING_INDEX($fieldname,'.',".($i-1)."))+2, $max_arc_len) as decimal(65)) $order";
			}

			// 4. as last resort, sort by the identifier itself, e.g. if the casts above did fail (happens if it is not an OID)			
			$out[] = "$fieldname $order";
			
		} else {
		
			// TODO: Implement natural order for Microsoft SQL Server

			// For (yet) unsupported DBMS, we do not offer natural sort
			$out[] = "$fieldname $order";

		}

		return implode(', ', $out); 
	}

	protected function afterConnect(): void {
		// Check if database tables are existing
		$table_names = array('objects', 'asn1id', 'iri', 'ra', 'config');
		foreach ($table_names as $tablename) {
			try {
				$this->query("select 0 from ".OIDPLUS_TABLENAME_PREFIX.$tablename." where 1=0");
			} catch (Exception $e) {
				if ($this->html) {
					echo '<h1>Error</h1><p>Table <b>'.OIDPLUS_TABLENAME_PREFIX.$tablename.'</b> does not exist.</p>';
					if (is_dir(__DIR__.'/../../setup')) {
						echo '<p>Please run <a href="'.OIDplus::getSystemUrl().'setup/">setup</a> again.</p>';
					}
				} else {
					echo 'Error: Table '.OIDPLUS_TABLENAME_PREFIX.$tablename.' does not exist.';
					if (is_dir(__DIR__.'/../../setup')) {
						echo ' Please run setup again.';
					}
				}
				die();
			}
		}

		// Do the database table tables need an update?
		// Note: The config setting "database_version" is inserted in setup/sql/...sql, not in the OIDplus core init

		$res = $this->query("SELECT value FROM ".OIDPLUS_TABLENAME_PREFIX."config WHERE name = 'database_version'");
		$row = $res->fetch_array();
		
		if ($row == null) {
			if ($this->html) {
				echo '<h1>Error</h1><p>Cannot determine database version (config table).</p>';
				if (is_dir(__DIR__.'/../../setup')) {
					echo '<p>Please run <a href="'.OIDplus::getSystemUrl().'setup/">setup</a> again.</p>';
				}
			} else {
				echo 'Error: Cannot determine database version (config table).';
				if (is_dir(__DIR__.'/../../setup')) {
					echo ' Please run setup again.';
				}
			}
			die();
		}
		
		$version = $row['value'];
		if ($version == 200) {
			$this->transaction_begin();
			$this->query("ALTER TABLE ".OIDPLUS_TABLENAME_PREFIX."objects ADD comment varchar(255) NULL");
			$version = 201;
			$this->query("UPDATE ".OIDPLUS_TABLENAME_PREFIX."config SET value = ? WHERE name = 'database_version'", array($version));
			$this->transaction_commit();
		}
		if ($version == 201) {
			// Change bit(1) types to boolean/tinyint(1)
			$this->transaction_begin();
			if ($this->slang() == 'pgsql') {
				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."config  alter protected    drop default");
				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."config  alter protected    type boolean using get_bit(protected   ,0)::boolean");
				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."config  alter protected    set default false");
				
				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."config  alter visible      drop default");
				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."config  alter visible      type boolean using get_bit(visible     ,0)::boolean");
				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."config  alter visible      set default false");

				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."asn1id  alter standardized drop default");
				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."asn1id  alter standardized type boolean using get_bit(standardized,0)::boolean;");
				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."asn1id  alter standardized set default false");

				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."asn1id  alter well_known   drop default");
				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."asn1id  alter well_known   type boolean using get_bit(well_known  ,0)::boolean;");
				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."asn1id  alter well_known   set default false");

				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."iri     alter longarc      drop default");
				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."iri     alter longarc      type boolean using get_bit(longarc     ,0)::boolean;");
				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."iri     alter longarc      set default false");

				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."iri     alter well_known   drop default");
				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."iri     alter well_known   type boolean using get_bit(well_known  ,0)::boolean;");
				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."iri     alter well_known   set default false");

				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."objects alter confidential type boolean using get_bit(confidential,0)::boolean;");

				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."ra      alter privacy      drop default");
				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."ra      alter privacy      type boolean using get_bit(privacy     ,0)::boolean;");
				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."ra      alter privacy      set default false");
			} else if ($this->slang() == 'mysql') {
				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."config  modify protected    boolean;");
				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."config  modify visible      boolean;");
				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."asn1id  modify standardized boolean;");
				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."asn1id  modify well_known   boolean;");
				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."iri     modify longarc      boolean;");
				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."iri     modify well_known   boolean;");
				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."objects modify confidential boolean;");
				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."ra      modify privacy      boolean;");
			}
			
			// Rename log_user.user to log_user.username, since user is a keyword in PostgreSQL and MSSQL
			if ($this->slang() == 'pgsql') {
				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."log_user rename column \"user\" to \"username\";");
			} else if ($this->slang() == 'mysql') {
				$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."log_user change `user` `username` varchar(255) NOT NULL;");
			}
  
			$version = 202;
			$this->query("UPDATE ".OIDPLUS_TABLENAME_PREFIX."config SET value = ? WHERE name = 'database_version'", array($version));
			$this->transaction_commit();
		}
	}

	protected function showConnectError($message): void {
		if ($this->html) {
			echo "<h1>Error</h1><p>Database connection failed! (".$message.")</p>";
			if (is_dir(__DIR__.'/../../setup')) {
				echo '<p>If you believe that the login credentials are wrong, please run <a href="'.OIDplus::getSystemUrl().'setup/">setup</a> again.</p>';
			}
		} else {
			echo "Error: Database connection failed! (".$message.")";
			if (is_dir(__DIR__.'/../../setup')) {
				echo ' If you believe that the login credentials are wrong, please run setup again.';
			}
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
				$vers = $this->query("select version() as dbms_version;")->fetch_object()->dbms_version;
				$vers = strtolower($vers);
			} catch (Exception $e) {
				try {
					// Microsoft SQL Server
					$vers = $this->query("select @@version as dbms_version;")->fetch_object()->dbms_version;
					$vers = strtolower($vers);
				} catch (Exception $e) {
					throw new Exception("Cannot determine the slang of your DBMS (function 'version()' could not be called). Your DBMS is probably not supported.");
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
				throw new Exception("Cannot determine the slang of your DBMS (we don't know what to do with the DBMS '$vers'). Your DBMS is probably not supported.");
			}
		}
	}
}

