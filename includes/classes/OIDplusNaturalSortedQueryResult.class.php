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
	 * @return int
	 */
	private $cursor = 0;

	/**
	 * @return int
	 */
	private $record_count = 0;

	/**
	* @param OIDplusQueryResult $res
	* @param string $dbField
	*/
	public function __construct(OIDplusQueryResult $res, string $dbField) {
		$this->no_resultset = !$res->containsResultSet();

		if (!$this->no_resultset) {
			$this->rows = array();
			while ($row = $res->fetch_array()) {
				$this->rows[] = $row;
			}
			$this->cursor = 0;
			$this->record_count = count($this->rows);

			// Sort $this->rows by field $dbField
			natsort_field($this->rows, $dbField);
		} else {
			$this->rows = array();
			$this->cursor = 0;
			$this->record_count = 0;
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
		return count($this->rows);
	}

	/**
	 * @return array|null
	 */
	protected function do_fetch_array()/*: ?array*/ {
		//return array_shift($this->rows);
		// This is probably faster for large arrays:
		if ($this->cursor >= $this->record_count) return null;
		return $this->rows[$this->cursor++];
	}

}
