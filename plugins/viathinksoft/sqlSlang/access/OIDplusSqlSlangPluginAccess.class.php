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

class OIDplusSqlSlangPluginAccess extends OIDplusSqlSlangPlugin {

	/**
	 * @return string
	 */
	public static function id(): string {
		return 'access';
	}

	/**
	 * @return string
	 */
	public function sqlDate(): string {
		return 'date()';
	}

	/**
	 * @param OIDplusDatabaseConnection $db
	 * @return bool
	 */
	public function detect(OIDplusDatabaseConnection $db): bool {
		/*
		if ($tables = @odbc_tables($db->conn)) {
			while ($row = @odbc_fetch_array($tables)) {
				if (($row['TABLE_NAME'] == 'MSysACEs') ||
					($row['TABLE_NAME'] == 'MSysObjects') ||
					($row['TABLE_NAME'] == 'MSysQueries') ||
					($row['TABLE_NAME'] == 'MSysRelationships'))
				{
					return true;
				}
			}
		}
		return false;
		*/

		$err_a = '';
		try {
			// On this table, there are often no read permissions, so we need to find out if the error message is different
			$db->query("select * from MSysObjects");
		} catch (\Exception $e) {
			$err_a = $db->error();
		}
		$err_a = str_replace('MSysObjects', '', $err_a);

		$err_b = '';
		try {
			$db->query("select * from XYZObjects");
		} catch (\Exception $e) {
			$err_b = $db->error();
		}
		$err_b = str_replace('XYZObjects', '', $err_b);

		return (!empty($err_a) && !empty($err_b) && ($err_a != $err_b));
	}

	/**
	 * @param OIDplusDatabaseConnection $db
	 * @return int
	 * @throws OIDplusException
	 */
	public function insert_id(OIDplusDatabaseConnection $db): int {
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
		$cont = str_replace('PK_'.$table, 'PK_'.$prefix.$table, $cont);
		return str_replace('IX_'.$table, 'PK_'.$prefix.$table, $cont);
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
		return "";
	}

	/**
	 * @param string $expr1
	 * @param string $expr2
	 * @return string
	 */
	public function isNullFunction(string $expr1, string $expr2): string {
		return "iif($expr1 is null, $expr2, $expr1)";
	}

	/**
	 * @param string $sql
	 * @return string
	 */
	public function filterQuery(string $sql): string {
		// value => [value]
		$sql = preg_replace('@\\b(value)\\b@i', '[\\1]', $sql);

		// This function does following:
		// Input:  select * from   T left join X on ...  left join Y on ...  left join Z on ...
		// Output: select * from ((T left join X on ...) left join Y on ...) left join Z on ...
		$ary = preg_split("@\\bunion\\b@i", $sql);
		foreach ($ary as &$x) {
			$INVALIDATE_SEQUENCE = '~X~X~X~X~X~X';
			$REGEX_JOIN = '(?<!'.$INVALIDATE_SEQUENCE.')(left|right|full|inner)\\s+(outer\\s+){0,1}join';
			do {
				$count = 0;
				$x = preg_replace("@from\\s+(.+)\\s+(".$REGEX_JOIN.")\\s+(.+)(".$REGEX_JOIN.")@ismU",
								  'from (\1 '.$INVALIDATE_SEQUENCE.'\2 \5) \6', $x, 1, $count);
			} while ($count > 0);
			$x = str_replace($INVALIDATE_SEQUENCE,'',$x);
		}
		return implode(' union ', $ary);
	}

	/**
	 * @param bool $bool
	 * @return string
	 */
	public function getSQLBool(bool $bool): string {
		return $bool ? '-1' : '0';
	}

	/**
	 * @param string $str
	 * @return string
	 */
	public function escapeString(string $str): string {
		return str_replace("'", "''", $str);
	}
}
