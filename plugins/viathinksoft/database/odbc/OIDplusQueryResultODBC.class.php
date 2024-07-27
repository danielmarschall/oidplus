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

namespace ViaThinkSoft\OIDplus\Plugins\viathinksoft\database\odbc;

use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusQueryResult;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusQueryResultODBC extends OIDplusQueryResult {
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

			// Since caching prepared statements will cause the testcase "Simultanous prepared statements" to fail,
			// this will fix it.
			$this->prefetchAll();
		}
	}

	/**
	 *
	 */
	public function __destruct() {
		// odbc_close_cursor($this->res);
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
	private function num_rows_workaround(): int {
		$dummy = array();

		// go to the end of the result set
		$till_eof = 0;
		while ($temp = odbc_fetch_into($this->res, $dummy)) $till_eof++;

		// reset cursor
		@odbc_fetch_row($this->res, 0);

		// count the rows in the result set
		$ret = 0;
		while ($temp = odbc_fetch_into($this->res, $dummy)) $ret++;

		// this would indicate that the odbc_fetch_row() could not reset the cursor
		if ($ret < $till_eof) return -1;

		// go back to the row were started with
		@odbc_fetch_row($this->res, $ret-$till_eof);

		return $ret;
	}

	/**
	 * @return int
	 * @throws OIDplusException
	 */
	protected function do_num_rows(): int {
		$ret = odbc_num_rows($this->res);

		// Workaround for drivers that do not support odbc_num_rows (e.g. Microsoft Access)
		if ($ret === -1) $ret = $this->num_rows_workaround();

		return $ret;
	}

	/**
	 * Goes to the last result set (in case a query returns multiple result sets)
	 * @return void
	 */
	protected function gotoLastResultSet() {
		while (@odbc_next_result($this->res)) {
			// Do nothing
		}
	}

	/**
	 * @return array|null
	 */
	protected function do_fetch_array(): ?array {
		//$this->gotoLastResultSet(); // TODO: This causes problems (read dbms_version on null)
		$ret = @odbc_fetch_array($this->res);
		if ($ret === false) $ret = null;
		return $ret;
	}

	/**
	 * @return \stdClass|null
	 */
	protected function do_fetch_object(): ?\stdClass {
		//$this->gotoLastResultSet(); // TODO: This causes problems (read dbms_version on null)
		$ret = @odbc_fetch_object($this->res);
		if ($ret === false) $ret = null;
		return $ret;
	}
}
