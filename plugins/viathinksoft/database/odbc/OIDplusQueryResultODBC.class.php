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

class OIDplusQueryResultODBC extends OIDplusQueryResult {
	protected $no_resultset;
	protected $res;

	public function __construct($res) {
		$this->no_resultset = is_bool($res);

		if (!$this->no_resultset) {
			$this->res = $res;
		}
	}

	public function __destruct() {
		// odbc_close_cursor($this->res);
	}

	public function containsResultSet(): bool {
		return !$this->no_resultset;
	}

	private function num_rows_workaround(): int {
		$dummy = 0;

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

	public function num_rows(): int {
		if ($this->no_resultset) throw new OIDplusException(_L('The query has returned no result set (i.e. it was not a SELECT query)'));
		$ret = odbc_num_rows($this->res);

		// Workaround for drivers that do not support odbc_num_rows (e.g. Microsoft Access)
		if ($ret === -1) $ret = $this->num_rows_workaround();

		if ($ret === -1) throw new OIDplusException(_L('The database driver has problems with "%1"','num_rows'));

		return $ret;
	}

	public function fetch_array()/*: ?array*/ {
		if ($this->no_resultset) throw new OIDplusException(_L('The query has returned no result set (i.e. it was not a SELECT query)'));
		$ret = odbc_fetch_array($this->res);
		if ($ret === false) $ret = null;
		if (!is_null($ret)) {
			// ODBC gives bit(1) as binary, MySQL as integer and PDO as string.
			// We'll do it like MySQL does, even if ODBC is actually more correct.
			foreach ($ret as &$value) {
				if ($value === chr(0)) $value = 0;
				if ($value === chr(1)) $value = 1;
			}
		}
		return $ret;
	}

	public function fetch_object()/*: ?object*/ {
		if ($this->no_resultset) throw new OIDplusException(_L('The query has returned no result set (i.e. it was not a SELECT query)'));
		$ret = odbc_fetch_object($this->res);
		if ($ret === false) $ret = null;
		if (!is_null($ret)) {
			// ODBC gives bit(1) as binary, MySQL as integer and PDO as string.
			// We'll do it like MySQL does, even if ODBC is actually more correct.
			foreach ($ret as &$value) {
				if ($value === chr(0)) $value = 0;
				if ($value === chr(1)) $value = 1;
			}
		}
		return $ret;
	}
}