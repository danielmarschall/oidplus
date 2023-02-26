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

class OIDplusSqlSlangPluginPgSQL extends OIDplusSqlSlangPlugin {

	public static function id(): string {
		return 'pgsql';
	}

	public function natOrder($fieldname, $order='asc'): string {

		$order = strtolower($order);
		if (($order != 'asc') && ($order != 'desc')) {
			throw new OIDplusException(_L('Invalid order "%1" (needs to be "asc" or "desc")',$order));
		}

		$out = array();

		$max_arc_len = OIDplus::baseConfig()->getValue('LIMITS_MAX_OID_ARC_SIZE') > 131072 ? 131072 : OIDplus::baseConfig()->getValue('LIMITS_MAX_OID_ARC_SIZE'); // Limit of the "numeric()" type

		// 1. Sort by namespace (oid, guid, ...)
		$out[] = "SPLIT_PART($fieldname, ':', 1) $order";

		// 2. Only if namespace is 'oid:': Sort OID as integer array
		$out[] = "STRING_TO_ARRAY(SPLIT_PART($fieldname, 'oid:', 2), '.')::numeric($max_arc_len)[] $order";

		// 3. Otherwise order by ID
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
			return strpos($vers, 'postgresql') !== false;
		} catch (\Exception $e) {
			return false;
		}
	}

	public function insert_id(OIDplusDatabaseConnection $db): int {
		$res = $db->query("SELECT LASTVAL() AS ID");
		$row = $res->fetch_array();
		return (int)$row['ID'];
	}

	public function setupSetTablePrefix($cont, $table, $prefix): string {
		$cont = str_replace('"'.$table.'"', '"'.$prefix.$table.'"', $cont);
		$cont = str_replace('"index_'.$table, '"index_'.$prefix.$table, $cont);
		return $cont;
	}

	public function setupCreateDbIfNotExists($database): string {
		return "-- CREATE DATABASE $database;\n\n";
	}

	public function setupUseDatabase($database): string {
		return "-- \connect $database;\n\n";
	}

	public function isNullFunction($expr1, $expr2): string {
		return "coalesce($expr1, $expr2)";
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
