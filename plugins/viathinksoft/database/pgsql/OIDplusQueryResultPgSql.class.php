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

namespace ViaThinkSoft\OIDplus\Plugins\Database\PgSQL;

use ViaThinkSoft\OIDplus\Core\OIDplusQueryResult;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusQueryResultPgSql extends OIDplusQueryResult {
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
			pg_free_result($this->res);
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
		return pg_num_rows($this->res);
	}

	/**
	 * @return array|null
	 */
	protected function do_fetch_array(): ?array {
		$ret = pg_fetch_array($this->res, null, PGSQL_ASSOC);
		if ($ret === false) $ret = null;
		if (!is_null($ret)) {
			foreach ($ret as $key => &$value){
				$type = pg_field_type($this->res,pg_field_num($this->res, $key));
				if ($type == 'bool'){
					$value = ($value === 't'); // value is either "t" or "f" (not "true" or "false")
				}
			}
		}
		return $ret;
	}

	/**
	 * @return \stdClass|null
	 */
	protected function do_fetch_object(): ?\stdClass {
		$ret = pg_fetch_object($this->res);
		if ($ret === false) $ret = null;
		if (!is_null($ret)) {
			foreach ($ret as $key => &$value){
				$type = pg_field_type($this->res,pg_field_num($this->res, $key));
				if ($type == 'bool'){
					$value = ($value === 't'); // value is either "t" or "f" (not "true" or "false")
				}
			}
		}
		return $ret;
	}
}
