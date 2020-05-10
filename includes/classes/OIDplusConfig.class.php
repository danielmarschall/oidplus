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

// OIDplusConfig contains settings that are stored in the database.
// Not to be confused with OIDplusBaseConfig which is the basic ("static")
// configuration stored in userdata/baseconfig/config.inc.php,
// e.g. database access credentials.
class OIDplusConfig implements OIDplusConfigInterface {

	/*public*/ const PROTECTION_EDITABLE = 0;
	/*public*/ const PROTECTION_READONLY = 1;
	/*public*/ const PROTECTION_HIDDEN   = 2;

	protected $values = array();
	protected $dirty = true;
	protected $validateCallbacks = array();

	public function prepareConfigKey($name, $description, $init_value, $protection, $validateCallback) {
		switch ($protection) {
			case OIDplusConfig::PROTECTION_EDITABLE:
				$protected = 0;
				$visible   = 1;
				break;
			case OIDplusConfig::PROTECTION_READONLY:
				$protected = 1;
				$visible   = 1;
				break;
			case OIDplusConfig::PROTECTION_HIDDEN:
				$protected = 1;
				$visible   = 0;
				break;
			default:
				throw new OIDplusException("Invalid protection flag, use OIDplusConfig::PROTECTION_* constants");
		}
	
		if (strlen($name) > 50) {
			throw new OIDplusException("Config key name '$name' is too long. (max 50).");
		}
		if (strlen($description) > 255) {
			throw new OIDplusException("Description for config key '$name' is too long (max 255).");
		}
		$this->buildConfigArray();
		if (!isset($this->values[$name])) {
			OIDplus::db()->query("insert into ###config (name, description, value, protected, visible) values (?, ?, ?, ?, ?)", array($name, $description, $init_value, $protected, $visible));
			$this->values[$name] = $init_value;
		}
		if (!is_null($validateCallback)) {
			$this->validateCallbacks[$name] = $validateCallback;
		}
	}

	protected function buildConfigArray() {
		if ($this->dirty) {
			$this->values = array();
			$res = OIDplus::db()->query("select name, value from ###config");
			while ($row = $res->fetch_object()) {
				$this->values[$row->name] = $row->value;
			}
			$this->dirty = false;
		}
	}

	public function getValue($name, $default=null) {
		$this->buildConfigArray();
		if (isset($this->values[$name])) {
			return $this->values[$name];
		} else {
			return $default;
		}
	}

	public function exists($name) {
		$this->buildConfigArray();
		return !is_null($this->getValue($name));
	}

	public function setValue($name, $value) {
		// Give plugins the possibility to stop the process by throwing an Exception (e.g. if the value is invalid)
		// Required is that the plugin previously prepared the config setting

		if (isset($this->validateCallbacks[$name])) {
			$this->validateCallbacks[$name]($value);
		}

		// Now change the value in the database

		OIDplus::db()->query("update ###config set value = ? where name = ?", array($value, $name));
		$this->values[$name] = $value;
	}

	public function deleteConfigKey($name) {
		$this->buildConfigArray();
		if (isset($this->values[$name])) {
			OIDplus::db()->query("delete from ###config where name = ?", array($name));
		}
	}

}
