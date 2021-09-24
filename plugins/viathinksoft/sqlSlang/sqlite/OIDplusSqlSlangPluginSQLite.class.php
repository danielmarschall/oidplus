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

if (!defined('INSIDE_OIDPLUS')) die();

class OIDplusSqlSlangPluginSQLite extends OIDplusSqlSlangPlugin {

	public static function id(): string {
		return 'sqlite';
	}

	public function natOrder($fieldname, $order='asc'): string {

		$order = strtolower($order);
		if (($order != 'asc') && ($order != 'desc')) {
			throw new OIDplusException(_L('Invalid order "%1" (needs to be "asc" or "desc")',$order));
		}

		$out = array();

		// If the SQLite database is accessed through the SQLite3 plugin,
		// we use an individual collation, therefore OIDplusDatabasePluginSQLite3 overrides
		// natOrder().
		// If we connected to SQLite using ODBC or PDO, we need to do something else,
		// but that solution is complex, slow and wrong (since it does not support
		// UUIDs etc.)

		$max_depth = OIDplus::baseConfig()->getValue('LIMITS_MAX_OID_DEPTH');
		if ($max_depth > 11) $max_depth = 11; // SQLite3 will crash if max depth > 11 (parser stack overflow); (TODO: can we do something else?!?!)

		// 1. sort by namespace (oid, guid, ...)
		$out[] = "substr($fieldname,0,instr($fieldname,':')) $order";

		// 2. Sort by the rest arcs one by one
		for ($i=1; $i<=$max_depth; $i++) {
			if ($i==1) {
				$arc = "substr($fieldname,5)||'.'";
				$fieldname = $arc;
				$arc = "substr($arc,0,instr($arc,'.'))";
				$out[] = "cast($arc as integer) $order";
			} else {
				$arc = "ltrim(ltrim($fieldname,'0123456789'),'.')";
				$fieldname = $arc;
				$arc = "substr($arc,0,instr($arc,'.'))";
				$out[] = "cast($arc as integer)  $order";
			}
		}

		// 3. as last resort, sort by the identifier itself, e.g. if the function getOidArc always return 0 (happens if it is not an OID)
		$out[] = "$fieldname $order";

		return implode(', ', $out);

	}

	public function sqlDate(): string {
		return 'datetime()';
	}

	public function detect(OIDplusDatabaseConnection $db): bool {
		try {
			$db->query("select sqlite_version as dbms_version");
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	public function insert_id(OIDplusDatabaseConnection $db): int {
		$res = $db->query("SELECT last_insert_rowid() AS ID");
		$row = $res->fetch_array();
		return (int)$row['ID'];
	}

	public function setupSetTablePrefix($cont, $table, $prefix): string {
		$cont = str_replace('`'.$table.'`', '`'.$prefix.$table.'`', $cont);
		return $cont;
	}

	public function setupCreateDbIfNotExists($database): string {
		return "";
	}

	public function setupUseDatabase($database): string {
		return "";
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
