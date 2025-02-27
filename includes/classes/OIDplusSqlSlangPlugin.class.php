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

abstract class OIDplusSqlSlangPlugin extends OIDplusPlugin {

	/**
	 * @return string
	 */
	public abstract static function id(): string;

	/**
	 * @return bool
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	public final function isActive(): bool {
		return $this->id() == OIDplus::db()->getSlang()->id();
	}

	/**
	 * @return string
	 */
	public abstract function sqlDate(): string;

	/**
	 * @param OIDplusDatabaseConnection $db
	 * @return bool
	 */
	public abstract function detect(OIDplusDatabaseConnection $db): bool;

	/**
	 * @param OIDplusDatabaseConnection $db
	 * @return string
	 */
	public abstract function getDbmsVersion(OIDplusDatabaseConnection $db): string;

	/**
	 * Please note: This insert_id() function should use SQL to receive
	 * the last inserted ID. If the database connection provider (e.g. PDO)
	 * offers a way to fetch the last inserted ID, please use this instead!
	 * So, please do NOT use  OIDplus::db()->getSlang()->insert_id()
	 * but instead use        OIDplus::db()->insert_id()
	 * This way, the database connection provider can override that function
	 * with their own method of fetching the last inserted ID.
	 * @param OIDplusDatabaseConnection $db
	 * @return int 0 on failure.
	 */
	public abstract function insert_id(OIDplusDatabaseConnection $db): int;

	/**
	 * @param string $cont
	 * @param string $table
	 * @param string $prefix
	 * @return string
	 */
	public abstract function setupSetTablePrefix(string $cont, string $table, string $prefix): string;

	/**
	 * @param string $database
	 * @return string
	 */
	public abstract function setupCreateDbIfNotExists(string $database): string;

	/**
	 * @param string $database
	 * @return string
	 */
	public abstract function setupUseDatabase(string $database): string;

	/**
	 * @param string $sql
	 * @return string
	 */
	public abstract function filterQuery(string $sql): string;

	/**
	 * @param bool $bool
	 * @return string
	 */
	public abstract function getSQLBool(bool $bool): string;

	/**
	 * @param string $str
	 * @return string
	 */
	public abstract function escapeString(string $str): string;

	/**
	 * @param string $expr1
	 * @param string $expr2
	 * @return string
	 */
	public abstract function isNullFunction(string $expr1, string $expr2): string;

	/**
	 * @param string $sql
	 * @return string
	 */
	public abstract function lowerCase(string $sql): string;

	/**
	 * @param string $sql
	 * @return string
	 */
	public abstract function upperCase(string $sql): string;

	/**
	 * This gives the SQL slang plugin the chance to review the result before it is passed to the application.
	 * @param OIDplusQueryResult $res
	 * @param string $sql
	 * @param array|null $prepared_args
	 * @return void
	 */
	public function reviewResult(OIDplusQueryResult $res, string $sql, ?array $prepared_args=null): void {
		// nothing here. Override it is you need it.
	}

	/**
	 * @param string $sql
	 * @return bool
	 */
	public function fetchableRowsExpected(string $sql): bool {
		return str_starts_with(trim(strtolower($sql)),'select');
	}

	/**
	 * @param OIDplusDatabaseConnection $db
	 * @param string $tableName
	 * @return bool
	 */
	public function tableExists(OIDplusDatabaseConnection $db, string $tableName): bool {
		try {
			// Attention: This query could interrupt transactions if Rollback-On-Error is enabled
			$db->query("select 0 from ".$tableName." where 1=0");
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

}
