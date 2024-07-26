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

namespace ViaThinkSoft\OIDplus;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusSqlSlangPluginOracle extends OIDplusSqlSlangPlugin {

	/**
	 * @return string
	 */
	public static function id(): string {
		return 'oracle';
	}

	/**
	 * @return string
	 */
	public function sqlDate(): string {
		return 'SYSDATE';
	}

	/**
	 * @param OIDplusDatabaseConnection $db
	 * @return bool
	 */
	public function detect(OIDplusDatabaseConnection $db): bool {
		try {
			$vers = $db->query("SELECT banner FROM v\$version WHERE banner LIKE 'Oracle%'")->fetch_object();
			if (!$vers) return false;
			return (stripos($vers->banner, 'Oracle') !== false);
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * @var string|null
	 */
	private $last_insert_table = null;

	/**
	 * @param OIDplusDatabaseConnection $db
	 * @return int
	 * @throws OIDplusException
	 */
	public function insert_id(OIDplusDatabaseConnection $db): int {
		if (!$this->last_insert_table) return 0;
		$res = $db->query("select sequence_name from user_tab_identity_cols where table_name = '".strtoupper($this->last_insert_table)."'");
		$row = $res->fetch_array();

		if (!isset($row['sequence_name'])) return 0;
		$res = $db->query("select ".$row['sequence_name'].".currval from dual");
		$row = $res->fetch_array();
		if (!$row) return 0;
		return (int)$row['CURRVAL'];
	}

	/**
	 * @param string $cont
	 * @param string $table
	 * @param string $prefix
	 * @return string
	 */
	public function setupSetTablePrefix(string $cont, string $table, string $prefix): string {
		$table = strtoupper($table);
		$prefix = strtoupper($prefix);
		return str_replace('"'.$table.'"', '"'.$prefix.$table.'"', $cont);
	}

	/**
	 * @param string $database
	 * @return string
	 */
	public function setupCreateDbIfNotExists(string $database): string {
		// TODO! Implement
		return "";
	}

	/**
	 * @param string $database
	 * @return string
	 */
	public function setupUseDatabase(string $database): string {
		// TODO! Implement
		return "";
	}

	/**
	 * @param string $expr1
	 * @param string $expr2
	 * @return string
	 */
	public function isNullFunction(string $expr1, string $expr2): string {
		// Test via "SELECT NVL(null, 'foo') FROM DUAL;"
		return "NVL($expr1, $expr2)";
	}

	/**
	 * @param string $sql
	 * @return string
	 */
	public function filterQuery(string $sql): string {
		$sql = trim($sql);

		// SQL-Queries MUST NOT end with a ";", otherwise error "SQL command not property ended"
		$sql = rtrim($sql, "; \n\r\t\v\x00");
		// SQL/PL-Programs MUST end with a ";"
		if (strtolower(substr($sql,-3)) == 'end') $sql .= ';';

		// "select 1" is not valid. You need to add "from dual"
		if (str_starts_with(trim(strtolower($sql)),'select') && (stripos($sql,'from') === false)) {
			$sql .= ' from dual';
		}

		// Dirty hack!!! We need the name of the last inserted table so that insert_id()
		// works. This is a dirty hack, because the invokation of filterQuery() does
		// not guarantee that the query was actually executed...
		if (preg_match("@insert into (.+) @ismU", $sql, $m)) {
			$this->last_insert_table = $m[1];
		} else {
			$this->last_insert_table = null;
		}

		// Comment is a keyword and cannot be used as column name
		return str_ireplace('comment', '"COMMENT"', $sql);
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
}
