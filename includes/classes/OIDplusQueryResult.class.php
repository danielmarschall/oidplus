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
	abstract public function containsResultSet(): bool;
	abstract public function num_rows(): int;
	abstract public function fetch_array()/*: ?array*/;
	abstract public function fetch_object()/*: ?object*/;

	public function any(): bool {
		// The any() function returns true if there is at least one
		// row in the section. By default, num_rows() will be used.
		// Plugins can override this method if they have a possibility
		// of making this functionality more efficient.
		return $this->num_rows() > 0;
	}
}
