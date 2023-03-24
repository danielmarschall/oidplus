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
	protected $no_resultset;
	protected $res;

	/**
	 * @param $res
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
		}
	}

	/**
	 * @return bool
	 */
	public function containsResultSet(): bool {
		return !$this->no_resultset;
	}

	/**
	 * @var ?array
	 */
	private $prefetchedArray = null;

	/**
	 * @var int
	 */
	private $countAlreadyFetched = 0;

	/**
	 * @return int
	 * @throws OIDplusException
	 */
	public function num_rows(): int {
		if (!is_null($this->prefetchedArray)) {
			return count($this->prefetchedArray) + $this->countAlreadyFetched;
		}

		if ($this->no_resultset) throw new OIDplusException(_L('The query has returned no result set (i.e. it was not a SELECT query)'));

		// This function does not return number of rows selected! For SELECT statements this function will return the number of rows, that were fetched to the buffer with oci_fetch*() functions.
		//return oci_num_rows($this->res);

		$this->prefetchedArray = array();
		oci_fetch_all($this->res, $this->prefetchedArray, 0, -1, OCI_FETCHSTATEMENT_BY_ROW);
		return count($this->prefetchedArray) + $this->countAlreadyFetched;
	}

	/**
	 * @return array|mixed|null
	 * @throws OIDplusException
	 */
	public function fetch_array()/*: ?array*/ {
		if (!is_null($this->prefetchedArray)) {
			$ret = array_shift($this->prefetchedArray);
		} else {
			if ($this->no_resultset) throw new OIDplusException(_L('The query has returned no result set (i.e. it was not a SELECT query)'));
			$ret = oci_fetch_array($this->res);
			if ($ret === false) $ret = null;
		}
		if ($ret) $this->countAlreadyFetched++;

		// Oracle returns $ret['VALUE'] because unquoted column-names are always upper-case
		// We can't quote every single column throughout the whole program, so we use this workaround...
		if ($ret) {
			$keys = array_keys($ret);
			foreach($keys as $key) {
				$ret[strtolower($key)]=$ret[$key];
				$ret[strtoupper($key)]=$ret[$key];
			}
		}

		return $ret;
	}

	/**
	 * @param $ary
	 * @return \stdClass
	 */
	private static function array_to_stdobj($ary) {
		$obj = new \stdClass;
		foreach ($ary as $name => $val) {
			$obj->$name = $val;

			// Oracle returns $ret['VALUE'] because unquoted column-names are always upper-case
			// We can't quote every single column throughout the whole program, so we use this workaround...
			$name = strtolower($name);
			$obj->$name = $val;
			$name = strtoupper($name);
			$obj->$name = $val;
		}
		return $obj;
	}

	/**
	 * @return false|object|\stdClass|null
	 * @throws OIDplusException
	 */
	public function fetch_object()/*: ?object*/ {
		if (!is_null($this->prefetchedArray)) {
			$ary = array_shift($this->prefetchedArray);
			$ret = is_null($ary) ? null : self::array_to_stdobj($ary);
		} else {
			if ($this->no_resultset) throw new OIDplusException(_L('The query has returned no result set (i.e. it was not a SELECT query)'));
			$ret = oci_fetch_object($this->res);
			if ($ret === false) $ret = null;
		}
		if ($ret) $this->countAlreadyFetched++;

		// Oracle returns $ret['VALUE'] because unquoted column-names are always upper-case
		// We can't quote every single column throughout the whole program, so we use this workaround...
		if ($ret) {
			foreach ($ret as $name => $val) {
				$ret->{strtoupper($name)} = $val;
				$ret->{strtolower($name)} = $val;
			}
		}

		return $ret;
	}
}
