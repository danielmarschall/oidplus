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

namespace ViaThinkSoft\OIDplus\Plugins\Database\SqlSrv;

use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusQueryResult;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusQueryResultSqlSrv extends OIDplusQueryResult {
	/**
	 * @var bool
	 */
	protected $no_resultset;

	/**
	 * @var mixed
	 */
	protected $res;

	/**
	 * @param mixed $res
	 */
	public function __construct($res) {
		$this->no_resultset = is_bool($res);

		if (!$this->no_resultset) {
			$this->res = $res;
		}
	}

	/**
	 *
	 */
	public function __destruct() {
		if ($this->res) {
			// TODO: Added is_resource(), because for some reason, we get errors here "supplied resource is not a valid ss_sqlsrv_stmt resource"
			//       at the end of execution of the dev/test_database_plugins script
			if (is_resource($this->res)) {
				sqlsrv_free_stmt($this->res);
			}
			$this->res = null;
		}
	}

	/**
	 * @return bool
	 */
	public function containsResultSet(): bool {
		return !$this->no_resultset;
	}

	/**
	 * @return int
	 */
	protected function do_num_rows(): int {
		$nr = sqlsrv_num_rows($this->res);
		if ($nr === false) return -1;
		return $nr;
	}

	/**
	 * @return array|null
	 */
	protected function do_fetch_array(): ?array {
		$ary = sqlsrv_fetch_array($this->res);

		if ($ary) {
			foreach ($ary as $key => &$a) {
				// This is a bit TOO object oriented for the rest of OIDplus!
				if ($a instanceof \DateTime) $a = $a->format('Y-m-d H:i:s');
			}
		}

		return $ary;
	}

	/**
	 * @return \stdClass|null
	 */
	//protected function do_fetch_object(): ?\stdClass {
	//	return sqlsrv_fetch_object($this->res, \stdClass::class);
	//}

	/**
	 * The any() function returns true if there is at least one
	 * row in the section.
	 *
	 * @return bool
	 * @throws OIDplusException
	 */
	public function any(): bool {
		return sqlsrv_has_rows($this->res);
	}
}
