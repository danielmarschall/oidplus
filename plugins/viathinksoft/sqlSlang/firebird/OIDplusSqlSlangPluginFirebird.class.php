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

class OIDplusSqlSlangPluginFirebird extends OIDplusSqlSlangPlugin {

	/**
	 * @return string
	 */
	public static function id(): string {
		return 'firebird';
	}

	/**
	 * @return string
	 */
	public function sqlDate(): string {
		// Firebird 3 :  LOCALTIMESTAMP == current_timestamp
		// Firebird 4 :  LOCALTIMESTAMP is without timezone and
		//               current_timestamp is with timezone.
		// PDO seems to have big problems with the "time stamp with time zone"
		// data type, since the plugin "adminPages => Systeminfo" shows an
		// empty string for "select current_timestamp from ###config".
		// Passing current_timestamp into a "insert into" query works however...
		// For now, we use LOCALTIMESTAMP. It does not seem to make a difference
		//return 'current_timestamp';
		return 'LOCALTIMESTAMP';
	}

	/**
	 * @param OIDplusDatabaseConnection $db
	 * @return bool
	 */
	public function detect(OIDplusDatabaseConnection $db): bool {
		try {
			return $db->query('select * from rdb$character_sets;')->num_rows() > 0;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * @var mixed
	 */
	private $last_insert_id = null;

	/**
	 * @param OIDplusDatabaseConnection $db
	 * @return int
	 * @throws OIDplusException
	 */
	public function insert_id(OIDplusDatabaseConnection $db): int {
		if (!is_numeric($this->last_insert_id)) return -1;
		return $this->last_insert_id ?? -1;
	}

	/**
	 * This gives the SQL slang plugin the chance to review the result before it is passed to the application.
	 * @param OIDplusQueryResult $res
	 * @param string $sql
	 * @param array|null $prepared_args
	 * @return void
	 */
	public function reviewResult(OIDplusQueryResult $res, string $sql, array $prepared_args=null) {
		if (str_starts_with(trim(strtolower($sql)),'insert')) {
			$this->last_insert_id = $res->fetch_array()["id"];
		} else {
			$this->last_insert_id = null;
		}
	}

	/**
	 * @param string $sql
	 * @return bool
	 */
	public function fetchableRowsExpected(string $sql): bool {
		return str_starts_with(trim(strtolower($sql)),'select') || str_starts_with(trim(strtolower($sql)),'insert');
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
		return "COALESCE($expr1, $expr2)";
	}

	/**
	 * @param string $sql
	 * @return string
	 */
	public function filterQuery(string $sql): string {
		// Make sure that the query does not end with ";", otherwise we cannot append stuff to it
		$sql = trim($sql);
		$sql = rtrim($sql, "; \n\r\t\v\x00");

		// Rewrite INSERT queries, so we can extract the ID in reviewResult()
		if (str_starts_with(trim(strtolower($sql)),'insert')) {
			$sql .= " returning id";
		}

		// "select 1" is not valid. You need to add "from RDB$DATABASE"
		if (str_starts_with(trim(strtolower($sql)),'select') && (stripos($sql,'from') === false)) {
			$sql .= ' from RDB$DATABASE';
		}

		// Value is a keyword and cannot be used as column name
		$sql = str_ireplace('value', '"VALUE"', $sql);
		$sql = str_ireplace('"VALUE"s', 'values', $sql);

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
}
