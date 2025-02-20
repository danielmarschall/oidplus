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

namespace ViaThinkSoft\OIDplus\Plugins\SqlSlang\MySQL;

use ViaThinkSoft\OIDplus\Core\OIDplusDatabaseConnection;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusSqlSlangPlugin;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusSqlSlangPluginMySQL extends OIDplusSqlSlangPlugin {

	/**
	 * @return string
	 */
	public static function id(): string {
		return 'mysql';
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
			return (stripos($vers->dbms_version, 'MySQL') !== false) || (stripos($vers->dbms_version, 'MariaDB') !== false);
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * @param OIDplusDatabaseConnection $db
	 * @return string
	 */
	public function getDbmsVersion(OIDplusDatabaseConnection $db): string {
		$sql = "SELECT VERSION() as VERSION;";
		return $db->getScalar($sql);
	}

	/**
	 * @param OIDplusDatabaseConnection $db
	 * @return int
	 * @throws OIDplusException
	 */
	public function insert_id(OIDplusDatabaseConnection $db): int {
		$res = $db->query("SELECT LAST_INSERT_ID() AS ID");
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
		return str_replace('`'.$table.'`', '`'.$prefix.$table.'`', $cont);
	}

	/**
	 * @param string $database
	 * @return string
	 */
	public function setupCreateDbIfNotExists(string $database): string {
		return "CREATE DATABASE IF NOT EXISTS `$database`;\n\n";
	}

	/**
	 * @param string $database
	 * @return string
	 */
	public function setupUseDatabase(string $database): string {
		return "USE `$database`;\n\n";
	}

	/**
	 * @param string $expr1
	 * @param string $expr2
	 * @return string
	 */
	public function isNullFunction(string $expr1, string $expr2): string {
		return "ifnull($expr1, $expr2)";
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
		return $bool ? '1' : '0';
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
		return $db->getScalar("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '".$tableName."' and TABLE_SCHEMA = database();") >= 1;
	}
}
