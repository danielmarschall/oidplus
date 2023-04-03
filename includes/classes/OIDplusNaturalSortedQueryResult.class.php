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

class OIDplusNaturalSortedQueryResult extends OIDplusQueryResult {

	/**
	* @var array
	*/
	private $rows = [];

	/**
	 * @return bool
	 */
	private $no_resultset = false;

	/**
	* @param OIDplusQueryResult $res
	* @param string $dbField
	*/
	public function __construct(OIDplusQueryResult $res, string $dbField) {
		$this->no_resultset = !$res->containsResultSet();

		if (!$this->no_resultset) {
			while ($row = $res->fetch_array()) {
				$this->rows[] = $row;
			}

			// Sort $this->rows by field $dbField
			natsort_field($this->rows, $dbField);
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
	public function num_rows(): int {
		if ($this->no_resultset) throw new OIDplusException(_L('The query has returned no result set (i.e. it was not a SELECT query)'));
		return count($this->rows);
	}

	/**
	 * @return array|null
	 */
	public function fetch_array()/*: ?array*/ {
		if ($this->no_resultset) throw new OIDplusException(_L('The query has returned no result set (i.e. it was not a SELECT query)'));
		return array_shift($this->rows);
	}

	/**
	 * @return object|null
	 */
	public function fetch_object()/*: ?object*/ {
		if ($this->no_resultset) throw new OIDplusConfigInitializationException(_L('The query has returned no result set (i.e. it was not a SELECT query)'));

		$ary = $this->fetch_array();
		if (!$ary) return null;

		$obj = new \stdClass;
		foreach ($ary as $name => $val) {
			$obj->$name = $val;
		}
		return $obj;
	}

}
