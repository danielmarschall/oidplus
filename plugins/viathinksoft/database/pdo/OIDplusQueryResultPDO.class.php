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

class OIDplusQueryResultPDO extends OIDplusQueryResult {
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

			// This way we can simulate MARS (Multiple Active Result Sets) so that the test case "Simultanous prepared statements" works
			$this->prefetchAll();
		}
	}

	/**
	 *
	 */
	public function __destruct() {
		if ($this->res) {
			$this->res->closeCursor();
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
		$this->prefetchedArray = $this->res->fetchAll();
		foreach ($this->prefetchedArray as &$row) {
			$this->fixFields($row);
		}
	}

	/**
	 * @return int
	 */
	protected function do_num_rows(): int {
		$ret = $this->res->rowCount();

		// -1 can happen when PDO is connected via ODBC that is running a driver that does not support num_rows (e.g. Microsoft Access)
		if ($ret === -1) {
			$this->prefetchAll();
			return count($this->prefetchedArray) + $this->countAlreadyFetched;
		}

		return $ret;
	}

	/**
	 * @return array|null
	 */
	protected function do_fetch_array()/*: ?array*/ {
		$ret = $this->res->fetch(\PDO::FETCH_ASSOC);
		if ($ret === false) $ret = null;
		return $ret;
	}

	/**
	 * @return object|null
	 */
	protected function do_fetch_object()/*: ?object*/ {
		$ret = $this->res->fetch(\PDO::FETCH_OBJ);
		if ($ret === false) $ret = null;
		return $ret;
	}
}
