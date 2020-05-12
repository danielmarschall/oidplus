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

class OIDplusSqlSlangPluginMsSQL extends OIDplusSqlSlangPlugin {

	public static function id(): string {
		return 'mssql';
	}

	public function natOrder($fieldname, $order='asc'): string {

		$order = strtolower($order);
		if (($order != 'asc') && ($order != 'desc')) {
			throw new OIDplusException("Invalid order '$order' (needs to be 'asc' or 'desc')");
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

	public function sqlDate(): string {
		return 'getdate()';
	}

	public function detect(): bool {
		try {
			$vers = $this->query("select @@version as dbms_version")->fetch_object()->dbms_version;
			$vers = strtolower($vers);
			return strpos($vers, 'microsoft sql server') !== false;
		} catch (Exception $e) {
			return false;
		}
	}

	public function insert_id(): int {
		// Note: SCOPE_IDENTITY() does not work, does only give 0.
		// $res = $this->query("SELECT SCOPE_IDENTITY() AS ID");
		$res = $this->query("SELECT @@IDENTITY AS ID");
		$row = $res->fetch_array();
		return (int)$row['ID'];
	}

	public function setupSetTablePrefix($cont, $table, $prefix): string {
		$cont = str_replace('['.$table.']', '['.$prefix.$table.']', $cont);
		$cont = str_replace("'dbo.$table'", "'dbo.$prefix$table'", $cont);
		$cont = str_replace('PK_'.$table, 'PK_'.$prefix.$table, $cont);
		$cont = str_replace('IX_'.$table, 'PK_'.$prefix.$table, $cont);
		$cont = str_replace('DF__'.$table, 'DF__'.$prefix.$table, $cont);
		return $cont;
	}

	public function setupCreateDbIfNotExists($database): string {
		return "";
	}

	public function setupUseDatabase($database): string {
		return "USE [$database]\n\nGO\n\n";
	}
}
