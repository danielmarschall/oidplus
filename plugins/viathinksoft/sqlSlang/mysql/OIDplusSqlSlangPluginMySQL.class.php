<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2021 Daniel Marschall, ViaThinkSoft
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

class OIDplusSqlSlangPluginMySQL extends OIDplusSqlSlangPlugin {

	public static function id(): string {
		return 'mysql';
	}

	public function natOrder($fieldname, $order='asc'): string {

		$order = strtolower($order);
		if (($order != 'asc') && ($order != 'desc')) {
			throw new OIDplusException(_L('Invalid order "%1" (needs to be "asc" or "desc")',$order));
		}

		$out = array();

		$max_arc_len = OIDplus::baseConfig()->getValue('LIMITS_MAX_OID_ARC_SIZE') > 65 ? 65 : OIDplus::baseConfig()->getValue('LIMITS_MAX_OID_ARC_SIZE'); // Limit of "decimal()" type

		// 1. sort by namespace (oid, guid, ...)
		$out[] = "SUBSTRING_INDEX($fieldname,':',1) $order";

		// 2. sort by first arc (0,1,2)
		$out[] = "SUBSTRING(SUBSTRING_INDEX($fieldname,'.',1), LENGTH(SUBSTRING_INDEX($fieldname,':',1))+2, $max_arc_len) $order";

		for ($i=2; $i<=OIDplus::baseConfig()->getValue('LIMITS_MAX_OID_DEPTH'); $i++) {
			// 3. Sort by the rest arcs one by one, not that MySQL can only handle decimal(65), not decimal($max_arc_len)
			$out[] = "cast(SUBSTRING(SUBSTRING_INDEX($fieldname,'.',$i), LENGTH(SUBSTRING_INDEX($fieldname,'.',".($i-1)."))+2, $max_arc_len) as decimal($max_arc_len)) $order";
		}

		// 4. as last resort, sort by the identifier itself, e.g. if the casts above did fail (happens if it is not an OID)
		$out[] = "$fieldname $order";

		return implode(', ', $out);

	}

	public function sqlDate(): string {
		return 'now()';
	}

	public function detect(OIDplusDatabaseConnection $db): bool {
		try {
			$vers = $db->query("select version() as dbms_version")->fetch_object()->dbms_version;
			$vers = strtolower($vers);
			return (strpos($vers, 'mysql') !== false) || (strpos($vers, 'mariadb') !== false);
		} catch (\Exception $e) {
			return false;
		}
	}

	public function insert_id(OIDplusDatabaseConnection $db): int {
		$res = $db->query("SELECT LAST_INSERT_ID() AS ID");
		$row = $res->fetch_array();
		return (int)$row['ID'];
	}


	public function setupSetTablePrefix($cont, $table, $prefix): string {
		$cont = str_replace('`'.$table.'`', '`'.$prefix.$table.'`', $cont);
		return $cont;
	}

	public function setupCreateDbIfNotExists($database): string {
		return "CREATE DATABASE IF NOT EXISTS `$database`;\n\n";
	}

	public function setupUseDatabase($database): string {
		return "USE `$database`;\n\n";
	}

	public function isNullFunction($expr1, $expr2): string {
		return "ifnull($expr1, $expr2)";
	}

	public function filterQuery($sql): string {
		return $sql;
	}

	public function getSQLBool($bool): string {
		return $bool ? '1' : '0';
	}

	public function escapeString($str): string {
		return str_replace("'", "''", $str);
	}
}
