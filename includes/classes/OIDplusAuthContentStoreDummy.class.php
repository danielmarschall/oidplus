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

# TODO: Rename class?
abstract class OIDplusAuthContentStoreDummy extends OIDplusAuthContentStore {

	/**
	 * @var array
	 */
	protected $content = array();

	// Override some abstract functions

	/**
	 * @param string $name
	 * @param mixed|null $default
	 * @return mixed|null
	 */
	public function getValue(string $name, $default = NULL) {
		return $this->content[$name] ?? $default;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function setValue(string $name, $value) {
		$this->content[$name] = $value;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function exists(string $name): bool {
		return isset($this->content[$name]);
	}

	/**
	 * @param string $name
	 * @return void
	 */
	public function delete(string $name) {
		unset($this->content[$name]);
	}

}
