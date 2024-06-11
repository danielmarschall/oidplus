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

// OIDplusBaseConfig is the basic ("static") configuration stored in userdata/baseconfig/config.inc.php (or userdata/tenant/.../baseconfig/config.inc.php),
// e.g. database access credentials.
// Not to be confused with OIDplusConfig which are settings that are stored in the database.
class OIDplusBaseConfig extends OIDplusBaseClass implements OIDplusGetterSetterInterface {

	/**
	 * @var array
	 */
	protected $data = array();

	/**
	 * @return string[]
	 */
	public function getAllKeys(): array {
		// TODO: put this method into the interface OIDplusGetterSetterInterface
		return array_keys($this->data);
	}

	/**
	 * @param string $name
	 * @param mixed|null $default
	 * @return mixed|null
	 */
	public function getValue(string $name, $default=null) {
		if ($name == 'SERVER_SECRET') {
			$caller_class = debug_backtrace()[1]['class'];
			if (!str_starts_with($caller_class, 'ViaThinkSoft\\OIDplus\\')) {
				throw new OIDplusException(_L('Outdated plugin: Calling %1 from a plugin is deprecated. Please use %2 instead', $name, 'OIDplus::authUtils()->makeSecret()'));
			}
		}
		return $this->exists($name) ? $this->data[$name] : $default;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function setValue(string $name, $value) {
		// Note: The value is only set at run time level!
		// This function will NOT change the userdata/baseconfig/config.inc.php file!
		$this->data[$name] = $value;
	}

	/**
	 * @param string $name
	 * @return void
	 */
	public function delete(string $name) {
		// Note: The value is only deleted at run time level!
		// This function will NOT change the userdata/baseconfig/config.inc.php file!
		unset($this->data[$name]);
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function exists(string $name): bool {
		return isset($this->data[$name]);
	}

}
