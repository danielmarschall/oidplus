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

abstract class OIDplusQueryResult extends OIDplusBaseClass {

	/**
	 * @return bool
	 */
	abstract public function containsResultSet(): bool;

	/**
	 * @return int
	 */
	abstract protected function do_num_rows(): int;

	/**
	 * @var array|null
	 */
	protected $prefetchedArray = null;

	/**
	 * @var int
	 */
	protected $countAlreadyFetched = 0;

	/**
	 * Please override this method if the database driver can perform a "fetch all" in its own way
	 *
	 * @return void
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 * @throws \ReflectionException
	 */
	public function prefetchAll() {
		if (!is_null($this->prefetchedArray)) return;
		$pfa = array();
		while ($row = $this->fetch_array()) {
			$pfa[] = $row; // you may not edit $this->prefetchedArray at this step, because $this->>fetch_array() checks it
			$this->countAlreadyFetched--; // because fetch_array() increases $this->countAlreadyFetched, we need to revert it
		}
		$this->prefetchedArray = $pfa;
	}

	/**
	 * @return int
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	public final function num_rows(): int {
		if (!$this->containsResultSet()) throw new OIDplusException(_L('The query has returned no result set (i.e. it was not a SELECT query)'));

		if (!is_null($this->prefetchedArray)) {
			return count($this->prefetchedArray) + $this->countAlreadyFetched;
		}

		$ret = $this->do_num_rows();

		if ($ret === -1) throw new OIDplusException(_L('The database driver has problems with "%1"','num_rows'));

		return $ret;
	}

	/**
	 * Plugins can override and extend this method. It post-processes contents of fetch_array() and fetch_object()
	 * to fix various issues with database drivers.
	 *
	 * @param array|object &$ret
	 * @return void
	 */
	protected function fixFields(&$ret) {
		// ODBC gives bit(1) as binary, MySQL as integer and PDO as string.
		// We'll do it like MySQL does, although ODBC semms to be more correct.
		// We don't put this code into OIDplusQueryResultODBC.class.php, because other
		// DBMS might do the same - and then we would be prepared.
		foreach ($ret as &$value) {
			if ($value === chr(0)) $value = 0;
			if ($value === chr(1)) $value = 1;
		}
		unset($value);

		// Oracle and Firebird returns $ret['VALUE'] because unquoted column-names are always upper-case
		// We can't quote every single column throughout the whole program, so we use this workaround...
		if (is_array($ret)) {
			foreach ($ret as $name => $val) {
				$ret[strtolower($name)] = $val;
				$ret[strtoupper($name)] = $val;
			}
		} else if (is_object($ret)) {
			foreach ($ret as $name => $val) {
				$ret->{strtoupper($name)} = $val;
				$ret->{strtolower($name)} = $val;
			}
		} else {
			assert(false);
		}
	}

	/**
	 * Please override do_fetch_object(), do_fetch_array(), or both.
	 * @return array|null
	 */
	protected function do_fetch_array()/*: ?array*/ {
		assert(false);
		return null;
	}

	/**
	 * @return array|null
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 * @throws \ReflectionException
	 */
	public final function fetch_array()/*: ?array*/ {
		if (!$this->containsResultSet()) throw new OIDplusException(_L('The query has returned no result set (i.e. it was not a SELECT query)'));
		if (!is_null($this->prefetchedArray)) {
			// Prefetched value exists. Use it.
			$ary = array_shift($this->prefetchedArray);
		} else {
			$reflector = new \ReflectionMethod($this, 'do_fetch_array');
			$isImplemented = ($reflector->getDeclaringClass()->getName() !== self::class);
			if ($isImplemented) {
				// do_fetch_array() is implemented. Use it.
				$ary = $this->do_fetch_array();
			} else {
				// Use the implementation of do_fetch_object()
				$reflector = new \ReflectionMethod($this, 'do_fetch_object');
				$isImplemented = ($reflector->getDeclaringClass()->getName() !== self::class);
				if (!$isImplemented) {
					throw new OIDplusException(_L("Class %1 is erroneous: At least one fetch-method needs to be overridden", get_class($this)));
				}
				$obj = $this->do_fetch_object();
				$ary = is_null($obj) ? null : stdobj_to_array($obj);
			}
		}
		if (!is_null($ary)) {
			$this->countAlreadyFetched++;
			$this->fixFields($ary);
		}
		return $ary;
	}

	/**
	 * Please override do_fetch_object(), do_fetch_array(), or both.
	 * @return object|null
	 */
	protected function do_fetch_object()/*: ?\stdClass*/ {
		assert(false);
		return null;
	}

	/**
	 * @return object|null
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 * @throws \ReflectionException
	 */
	public final function fetch_object()/*: ?\stdClass*/ {
		if (!$this->containsResultSet()) throw new OIDplusException(_L('The query has returned no result set (i.e. it was not a SELECT query)'));
		if (!is_null($this->prefetchedArray)) {
			// Prefetched value exists (as array). Convert and use it.
			$ary = array_shift($this->prefetchedArray);
			$obj = is_null($ary) ? null : array_to_stdobj($ary);
		} else {
			$reflector = new \ReflectionMethod($this, 'do_fetch_object');
			$isImplemented = ($reflector->getDeclaringClass()->getName() !== self::class);
			if ($isImplemented) {
				// do_fetch_object() is implemented. Use it.
				$obj = $this->do_fetch_object();
			} else {
				// Use the implementation of do_fetch_array()
				$reflector = new \ReflectionMethod($this, 'do_fetch_array');
				$isImplemented = ($reflector->getDeclaringClass()->getName() !== self::class);
				if (!$isImplemented) {
					throw new OIDplusException(_L("Class %1 is erroneous: At least one fetch-method needs to be overridden", get_class($this)));
				}
				$ary = $this->do_fetch_array();
				$obj = is_null($ary) ? null : array_to_stdobj($ary);
			}
		}
		if (!is_null($obj)) {
			$this->countAlreadyFetched++;
			$this->fixFields($obj);
		}
		return $obj;
	}

	/**
	 * The any() function returns true if there is at least one
	 * row in the section. By default, num_rows() will be used.
	 * Plugins can override this method if they have a possibility
	 * of making this functionality more efficient.
	 *
	 * @return bool
	 * @throws OIDplusException
	 */
	public function any(): bool {
		return $this->num_rows() > 0;
	}

	/**
	 * @param string $dbField
	 * @return void
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 * @throws \ReflectionException
	 */
	public final function naturalSortByField(string $dbField) { // TODO: Argument asc or desc order
		if (is_null($this->prefetchedArray)) {
			$this->prefetchAll();
		}

		// Sort $this->prefetchedArray by field $dbField
		natsort_field($this->prefetchedArray, $dbField);
	}
}
