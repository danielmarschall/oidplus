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
	 * @return int
	 */
	public final function num_rows(): int {
		if (!$this->containsResultSet()) throw new OIDplusException(_L('The query has returned no result set (i.e. it was not a SELECT query)'));
		return $this->do_num_rows();
	}

	/**
	 * @return array|null
	 */
	protected function do_fetch_array()/*: ?array*/ {
		assert(false);
		return null;
	}

	/**
	 * @return array|null
	 */
	public final function fetch_array()/*: ?array*/ {
		if (!$this->containsResultSet()) throw new OIDplusException(_L('The query has returned no result set (i.e. it was not a SELECT query)'));

		$reflector = new \ReflectionMethod($this, 'do_fetch_array');
		$isImplemented = ($reflector->getDeclaringClass()->getName() !== self::class);
		if ($isImplemented) return $this->do_fetch_array();

		$reflector = new \ReflectionMethod($this, 'do_fetch_object');
		$isImplemented = ($reflector->getDeclaringClass()->getName() !== self::class);
		if (!$isImplemented) {
			throw new OIDplusException(_L("Class %1 is erroneous: At least one fetch-method needs to be overridden", get_class($this)));
		}

		// Convert object to array
		$obj = $this->do_fetch_object();
		if (!$obj) return null;
		$ary = array();
		foreach ($obj as $name => $val) {
			$ary[$name] = $val;
		}
		return $ary;
	}

	/**
	 * @return object|null
	 */
	protected function do_fetch_object()/*: ?object*/ {
		assert(false);
		return null;
	}

	/**
	 * @return object|null
	 */
	public final function fetch_object()/*: ?object*/ {
		if (!$this->containsResultSet()) throw new OIDplusException(_L('The query has returned no result set (i.e. it was not a SELECT query)'));

		$reflector = new \ReflectionMethod($this, 'do_fetch_object');
		$isImplemented = ($reflector->getDeclaringClass()->getName() !== self::class);
		if ($isImplemented) return $this->do_fetch_object();

		$reflector = new \ReflectionMethod($this, 'do_fetch_array');
		$isImplemented = ($reflector->getDeclaringClass()->getName() !== self::class);
		if (!$isImplemented) {
			throw new OIDplusException(_L("Class %1 is erroneous: At least one fetch-method needs to be overridden", get_class($this)));
		}

		// Convert array of object
		$ary = $this->do_fetch_array();
		if (!$ary) return null;
		$obj = new \stdClass;
		foreach ($ary as $name => $val) {
			$obj->$name = $val;
		}
		return $obj;
	}

	/**
	 * The any() function returns true if there is at least one
	 * row in the section. By default, num_rows() will be used.
	 * Plugins can override this method if they have a possibility
	 * of making this functionality more efficient.
	 * @return bool
	 */
	public function any(): bool {
		return $this->num_rows() > 0;
	}
}
