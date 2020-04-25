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

// OIDplusBaseConfig is the basic ("static") configuration stored in includes/config.inc.php,
// e.g. database access credentials.
// Not to be confused with OIDplusConfig which are settings that are stored in the database.
class OIDplusBaseConfig implements OIDplusConfigInterface {

	protected $data = array();

	public function getValue($name, $default=null) {
		return $this->exists($name) ? $this->data[$name] : $default;
	}

	public function setValue($name, $value) {
		$this->data[$name] = $value;
	}

	public function delete($name) {
		unset($this->data[$name]);
	}

	public function exists($name) {
		return isset($this->data[$name]);
	}

	public function deleteAll() {
		$this->data = array();
	}

}
