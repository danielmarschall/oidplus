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

namespace ViaThinkSoft\OIDplus\Plugins\Database\SQLite3;

use ViaThinkSoft\OIDplus\Core\OIDplusQueryResult;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusQueryResultSQLite3 extends OIDplusQueryResult {
	/**
	 * @var bool
	 */
	protected $no_resultset;

	/**
	 * @var mixed
	 */
	protected $res;

	/**
	 * @var array
	 */
	protected $all_results = array();

	/**
	 * @var int
	 */
	protected $cursor = 0;

	/**
	 * @param mixed $res
	 */
	public function __construct($res) {
		if (is_bool($res) || ($res->numColumns() == 0)) {
			// Why do qe need to check numColumns() ?
			// We need to do this because SQLite3::query() will always
			// return a result, even for Non-SELECT queries.
			// If you call fetchArray(), the query (e.g. INSERT)
			// will be executed again.
			$this->no_resultset = true;
			return;
		}

		if (!$this->no_resultset) {
			$this->res = $res;
			while ($row = $this->res->fetchArray(SQLITE3_ASSOC)) {
				// we need that because there is no numRows() function!
				$this->all_results[] = $row;
			}
		}
	}

	/**
	 *
	 */
	public function __destruct() {
		$this->all_results = array();
		if (!is_null($this->res)) {
			$this->res->finalize();
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
		return count($this->all_results);
	}

	/**
	 * @return array|null
	 */
	protected function do_fetch_array(): ?array {
		//$ret = $this->res->fetchArray(SQLITE3_ASSOC);
		$cursor = $this->cursor;
		if (!isset($this->all_results[$cursor])) return null;
		$ret = $this->all_results[$cursor];
		$cursor++;
		$this->cursor = $cursor;

		if ($ret === false) $ret = null;
		return $ret;
	}

}
