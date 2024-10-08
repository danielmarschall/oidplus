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

namespace ViaThinkSoft\OIDplus\Plugins\Database\MySQLi;

use ViaThinkSoft\OIDplus\Core\OIDplusQueryResult;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusQueryResultMySQLNoNativeDriver extends OIDplusQueryResult {
	// Based on https://www.php.net/manual/de/mysqli-stmt.get-result.php#113398

	/**
	 * @var mixed|null
	 */
	protected $stmt = null;

	/**
	 * @var int|null
	 */
	protected $nCols = null;

	/**
	 * @var bool|null
	 */
	protected $no_resultset = null;

	/**
	 * @param mixed $stmt
	 */
	public function __construct($stmt) {
		$metadata = mysqli_stmt_result_metadata($stmt);

		$this->no_resultset = is_bool($metadata);

		if (!$this->no_resultset) {
			$this->nCols = mysqli_num_fields($metadata);
			$this->stmt = $stmt;

			mysqli_free_result($metadata);

			$this->stmt->store_result();
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
		//$this->stmt->store_result();
		return $this->stmt->num_rows;
	}

	/**
	 * @return array|null
	 */
	protected function do_fetch_array(): ?array {
		// https://stackoverflow.com/questions/10752815/mysqli-get-result-alternative , modified
		$stmt = $this->stmt;
		//$this->stmt->store_result();
		$resultkeys = array();
		$thisName = "";

		if ($stmt->num_rows==0) return null;

		for ($i = 0; $i < $stmt->num_rows; $i++) {
			$metadata = $stmt->result_metadata();
			while ($field = $metadata->fetch_field()) {
				$thisName = $field->name;
				$resultkeys[] = $thisName;
			}
		}

		$ret = array();
		$args = array();
		for ($i=0; $i<$this->nCols; $i++) {
			$ret[$i] = NULL;
			$theValue = $resultkeys[$i];
			$ret[$theValue] = NULL; // will be overwritten by mysqli_stmt_bind_result
			$args[] = &$ret[$theValue];
		}
		if (!mysqli_stmt_bind_result($this->stmt, ...$args)) {
			return null;
		}

		// This should advance the "$stmt" cursor.
		if (!mysqli_stmt_fetch($this->stmt)) {
			return null;
		}

		// Return the array we built.
		return $ret;
	}

}
