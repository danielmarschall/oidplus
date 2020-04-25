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

	public function num_rows(): int {
		if ($this->no_resultset) throw new OIDplusException("The query has returned no result set (i.e. it was not a SELECT query)");
		return odbc_num_rows($this->res);
	}

	public function fetch_array()/*: ?array*/ {
		if ($this->no_resultset) throw new OIDplusException("The query has returned no result set (i.e. it was not a SELECT query)");
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
		if ($this->no_resultset) throw new OIDplusException("The query has returned no result set (i.e. it was not a SELECT query)");
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
