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

class OIDplusQueryResultOci extends OIDplusQueryResult {

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
			oci_free_statement($this->res);
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
	 * @return void
	 */
	public function prefetchAll() {
		if (!is_null($this->prefetchedArray)) return;
		$this->prefetchedArray = array();
		oci_fetch_all($this->res, $this->prefetchedArray, 0, -1, OCI_FETCHSTATEMENT_BY_ROW);
		foreach ($this->prefetchedArray as &$row) { /** @phpstan-ignore-line */
			$this->fixFields($row);
		}
		unset($row); /** @phpstan-ignore-line */
	}

	/**
	 * @return int
	 */
	protected function do_num_rows(): int {
		// This function does not return number of rows selected! For SELECT statements this function will return the number of rows, that were fetched to the buffer with oci_fetch*() functions.
		//return oci_num_rows($this->res);

		if (is_null($this->prefetchedArray)) {
			$this->prefetchAll();
		}

		return count($this->prefetchedArray);
	}

	/**
	 * @return array|null
	 */
	protected function do_fetch_array()/*: ?array*/ {
		$ret = oci_fetch_array($this->res);
		if ($ret === false) $ret = null;
		return $ret;
	}

	/**
	 * @return object|null
	 */
	protected function do_fetch_object()/*: ?object*/ {
		$ret = oci_fetch_object($this->res);
		if ($ret === false) $ret = null;
		return $ret;
	}
}
