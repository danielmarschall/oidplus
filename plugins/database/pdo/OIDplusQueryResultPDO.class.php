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

class OIDplusQueryResultPDO extends OIDplusQueryResult {
	protected $no_resultset;
	protected $res;

	public function __construct($res) {
		$this->no_resultset = is_bool($res);

		if (!$this->no_resultset) {
			$this->res = $res;
		}

		// This way we can simulate MARS (Multiple Active Result Sets) so that the test case "Simultanous prepared statements" works
		$this->prefetchedArray = $this->res->fetchAll();
	}

	public function __destruct() {
		if ($this->res) $this->res->closeCursor();
	}

	public function containsResultSet(): bool {
		return !$this->no_resultset;
	}

	private $prefetchedArray = null;
	private $countAlreadyFetched = 0;

	public function num_rows(): int {
		if (!is_null($this->prefetchedArray)) {
			return count($this->prefetchedArray) + $this->countAlreadyFetched;
		}

		if ($this->no_resultset) throw new OIDplusException(_L('The query has returned no result set (i.e. it was not a SELECT query)'));
		$ret = $this->res->rowCount();

		// -1 can happen when PDO is connected via ODBC that is running a driver that does not support num_rows (e.g. Microsoft Access)
		// if ($ret === -1) throw new OIDplusException(_L('The database driver has problems with "%1"','num_rows'));
		if ($ret === -1) {
			$this->prefetchedArray = $this->res->fetchAll();
			return count($this->prefetchedArray) + $this->countAlreadyFetched;
		}

		return $ret;
	}

	public function fetch_array()/*: ?array*/ {
		if (!is_null($this->prefetchedArray)) {
			$ret = array_shift($this->prefetchedArray);
		} else {
			if ($this->no_resultset) throw new OIDplusException(_L('The query has returned no result set (i.e. it was not a SELECT query)'));
			$ret = $this->res->fetch(PDO::FETCH_ASSOC);
			if ($ret === false) $ret = null;
		}
		if ($ret) $this->countAlreadyFetched++;
		return $ret;
	}

	private static function array_to_stdobj($ary) {
		$obj = new stdClass;
		foreach ($ary as $name => $val) {
			$obj->$name = $val;
		}
		return $obj;
	}

	public function fetch_object()/*: ?object*/ {
		if (!is_null($this->prefetchedArray)) {
			$ary = array_shift($this->prefetchedArray);
			$ret = is_null($ary) ? null : self::array_to_stdobj($ary);
		} else {
			if ($this->no_resultset) throw new OIDplusException(_L('The query has returned no result set (i.e. it was not a SELECT query)'));
			$ret = $this->res->fetch(PDO::FETCH_OBJ);
			if ($ret === false) $ret = null;
		}
		if ($ret) $this->countAlreadyFetched++;
		return $ret;
	}
}