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

namespace ViaThinkSoft\OIDplus\Plugins\SqlSlang\PgSQL;

use ViaThinkSoft\OIDplus\Core\OIDplusDatabaseConnection;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusSqlSlangPlugin;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusSqlSlangPluginPgSQL extends OIDplusSqlSlangPlugin {

	/**
	 * @return string
	 */
	public static function id(): string {
		return 'pgsql';
	}

	/**
	 * @return string
	 */
	public function sqlDate(): string {
		return 'now()';
	}

	/**
	 * @param OIDplusDatabaseConnection $db
	 * @return bool
	 */
	public function detect(OIDplusDatabaseConnection $db): bool {
		try {
			$vers = $db->query("select version() as dbms_version")->fetch_object();
			if (!$vers) return false;
			return stripos($vers->dbms_version, 'PostgreSQL') !== false;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * @param OIDplusDatabaseConnection $db
	 * @return string
	 */
	public function getDbmsVersion(OIDplusDatabaseConnection $db): string {
		$sql = "SELECT version() as VERSION;";
		return $db->getScalar($sql);
	}

	/**
	 * @param OIDplusDatabaseConnection $db
	 * @return int
	 * @throws OIDplusException
	 */
	public function insert_id(OIDplusDatabaseConnection $db): int {
		$res = $db->query("SELECT LASTVAL() AS ID");
		$row = $res->fetch_array();
		if (!$row) return 0;
		return (int)$row['ID'];
	}

	/**
	 * @param string $cont
	 * @param string $table
	 * @param string $prefix
	 * @return string
	 */
	public function setupSetTablePrefix(string $cont, string $table, string $prefix): string {
		$cont = str_replace('"'.$table.'"', '"'.$prefix.$table.'"', $cont);
		return str_replace('"index_'.$table, '"index_'.$prefix.$table, $cont);
	}

	/**
	 * @param string $database
	 * @return string
	 */
	public function setupCreateDbIfNotExists(string $database): string {
		return "--CREATE ROLE your_username WITH LOGIN PASSWORD 'your_password';\n".
		       "\n".
		       "--CREATE DATABASE $database WITH\n".
		       "--    OWNER = your_username\n".
		       "--    TEMPLATE = template0\n".
		       "--    ENCODING 'UTF8'\n".
		       "--    TABLESPACE = pg_default\n".
		       "--    LC_COLLATE = 'C'\n".
		       "--    LC_CTYPE = 'C'\n".
		       "--    CONNECTION LIMIT = -1;\n".
		       "\n".
		       "--GRANT ALL PRIVILEGES ON DATABASE $database TO your_username;\n\n";
	}

	/**
	 * @param string $database
	 * @return string
	 */
	public function setupUseDatabase(string $database): string {
		return "-- \connect $database;\n\n";
	}

	/**
	 * @param string $expr1
	 * @param string $expr2
	 * @return string
	 */
	public function isNullFunction(string $expr1, string $expr2): string {
		return "coalesce($expr1, $expr2)";
	}

	/**
	 * @param string $sql
	 * @return string
	 */
	public function filterQuery(string $sql): string {
		return $sql;
	}

	/**
	 * @param bool $bool
	 * @return string
	 */
	public function getSQLBool(bool $bool): string {
		return $bool ? 'true' : 'false';
	}

	/**
	 * @param string $str
	 * @return string
	 */
	public function escapeString(string $str): string {
		return str_replace("'", "''", $str);
	}

	/**
	 * @param string $sql
	 * @return string
	 */
	public function lowerCase(string $sql): string {
		return "lower($sql)";
	}

	/**
	 * @param string $sql
	 * @return string
	 */
	public function upperCase(string $sql): string {
		return "upper($sql)";
	}

	/**
	 * @param OIDplusDatabaseConnection $db
	 * @param string $tableName
	 * @return bool
	 */
	public function tableExists(OIDplusDatabaseConnection $db, string $tableName): bool {
		return $db->getScalar("SELECT COUNT(*) FROM pg_catalog.pg_tables WHERE schemaname = 'public' AND tablename = '".$tableName."';") >= 1;
	}
}
