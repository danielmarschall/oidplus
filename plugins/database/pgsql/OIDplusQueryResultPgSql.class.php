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

class OIDplusQueryResultPgSql extends OIDplusQueryResult {
	protected $no_resultset;
	protected $res;

	public function __construct($res) {
		$this->no_resultset = is_bool($res);

		if (!$this->no_resultset) {
			$this->res = $res;
		}
	}

	public function __destruct() {
		if ($this->res) {
			pg_free_result($this->res);
		}
	}

	public function containsResultSet(): bool {
		return !$this->no_resultset;
	}

	public function num_rows(): int {
		if ($this->no_resultset) throw new OIDplusException(_L('The query has returned no result set (i.e. it was not a SELECT query)'));
		return pg_num_rows($this->res);
	}

	public function fetch_array()/*: ?array*/ {
		if ($this->no_resultset) throw new OIDplusException(_L('The query has returned no result set (i.e. it was not a SELECT query)'));
		$ret = pg_fetch_array($this->res, null, PGSQL_ASSOC);
		if ($ret === false) $ret = null;
		if (!is_null($ret)) {
			foreach ($ret as $key => &$value){
				$type = pg_field_type($this->res,pg_field_num($this->res, $key));
				if ($type == 'bool'){
					$value = ($value == 't');
				}
			}
		}
		return $ret;
	}

	public function fetch_object()/*: ?object*/ {
		if ($this->no_resultset) throw new OIDplusException(_L('The query has returned no result set (i.e. it was not a SELECT query)'));
		$ret = pg_fetch_object($this->res);
		if ($ret === false) $ret = null;
		if (!is_null($ret)) {
			foreach ($ret as $key => &$value){
				$type = pg_field_type($this->res,pg_field_num($this->res, $key));
				if ($type == 'bool'){
					$value = ($value == 't');
				}
			}
		}
		return $ret;
	}
}