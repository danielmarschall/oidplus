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

class OIDplusSqlSlangPluginMsSQL extends OIDplusSqlSlangPlugin {

	/**
	 * @return string
	 */
	public static function id(): string {
		return 'mssql';
	}

	/**
	 * @param string $fieldname
	 * @param string $order
	 * @return string
	 * @throws OIDplusException
	 */
	public function natOrder(string $fieldname, string $order='asc'): string {

		$order = strtolower($order);
		if (($order != 'asc') && ($order != 'desc')) {
			throw new OIDplusException(_L('Invalid order "%1" (needs to be "asc" or "desc")',$order));
		}

		$out = array();

		$max_arc_len = OIDplus::baseConfig()->getValue('LIMITS_MAX_OID_ARC_SIZE');

		// 1. sort by namespace (oid, guid, ...)
		$out[] = "SUBSTRING($fieldname,1,CHARINDEX(':',$fieldname)-1) $order";

		for ($i=1; $i<=OIDplus::baseConfig()->getValue('LIMITS_MAX_OID_DEPTH'); $i++) {
			// 2. Sort by the rest arcs one by one; note that MySQL can only handle decimal(65), not decimal($max_arc_len)
			$out[] = "dbo.getOidArc($fieldname, $max_arc_len, $i) $order";
		}

		// 3. as last resort, sort by the identifier itself, e.g. if the function getOidArc always return 0 (happens if it is not an OID)
		$out[] = "$fieldname $order";

		return implode(', ', $out);

	}

	/**
	 * @return string
	 */
	public function sqlDate(): string {
		return 'getdate()';
	}

	/**
	 * @param OIDplusDatabaseConnection $db
	 * @return bool
	 */
	public function detect(OIDplusDatabaseConnection $db): bool {
		try {
			$vers = $db->query("select @@version as dbms_version")->fetch_object()->dbms_version;
			$vers = strtolower($vers);
			return strpos($vers, 'microsoft sql server') !== false;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * @param OIDplusDatabaseConnection $db
	 * @return int
	 * @throws OIDplusException
	 */
	public function insert_id(OIDplusDatabaseConnection $db): int {
		// Note: SCOPE_IDENTITY() does not work, does only give 0.
		// $res = $db->query("SELECT SCOPE_IDENTITY() AS ID");
		$res = $db->query("SELECT @@IDENTITY AS ID");
		$row = $res->fetch_array();
		return (int)$row['ID'];
	}

	/**
	 * @param string $cont
	 * @param string $table
	 * @param string $prefix
	 * @return string
	 */
	public function setupSetTablePrefix(string $cont, string $table, string $prefix): string {
		$cont = str_replace('['.$table.']', '['.$prefix.$table.']', $cont);
		$cont = str_replace("'dbo.$table'", "'dbo.$prefix$table'", $cont);
		$cont = str_replace('PK_'.$table, 'PK_'.$prefix.$table, $cont);
		$cont = str_replace('IX_'.$table, 'PK_'.$prefix.$table, $cont);
		$cont = str_replace('DF__'.$table, 'DF__'.$prefix.$table, $cont);
		return $cont;
	}

	/**
	 * @param string $database
	 * @return string
	 */
	public function setupCreateDbIfNotExists(string $database): string {
		return "";
	}

	/**
	 * @param string $database
	 * @return string
	 */
	public function setupUseDatabase(string $database): string {
		return "USE [$database]\n\nGO\n\n";
	}

	/**
	 * @param string $expr1
	 * @param string $expr2
	 * @return string
	 */
	public function isNullFunction(string $expr1, string $expr2): string {
		return "isnull($expr1, $expr2)";
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
}
