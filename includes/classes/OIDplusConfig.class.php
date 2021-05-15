<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2021 Daniel Marschall, ViaThinkSoft
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

if (!defined('INSIDE_OIDPLUS')) die();

// OIDplusConfig contains settings that are stored in the database.
// Not to be confused with OIDplusBaseConfig which is the basic ("static")
// configuration stored in userdata/baseconfig/config.inc.php,
// e.g. database access credentials.
class OIDplusConfig implements OIDplusConfigInterface {

	/*public*/ const PROTECTION_EDITABLE = 0;
	/*public*/ const PROTECTION_READONLY = 1;
	/*public*/ const PROTECTION_HIDDEN   = 2;

	protected $configTableReadOnce = false; // this ensures that all $values and $descriptions were read

	protected $values = array();
	protected $descriptions = array();
	protected $validateCallbacks = array();

	public function prepareConfigKey($name, $description, $init_value, $protection, $validateCallback) {
		// Check if the protection flag is valid
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
				throw new OIDplusException(_L('Invalid protection flag, use OIDplusConfig::PROTECTION_* constants'));
		}

		// Check length limitations given by the database tables
		if (strlen($name) > 50) {
			throw new OIDplusException(_L('Config key name "%1" is too long (max %2).',$name,50));
		}
		if (strlen($description) > 255) {
			throw new OIDplusException(_L('Description for config key "%1" is too long (max %2).',$name,255));
		}

		// Read all values and descriptions from the database once.
		$this->buildConfigArray();

		// Figure out if we need to create/update something at database level
		if (!isset($this->values[$name])) {
			// Case A: The config setting does not exist in the database. So we create it now.
			OIDplus::db()->query("insert into ###config (name, description, value, protected, visible) values (?, ?, ?, ?, ?)", array($name, $description, $init_value, $protected, $visible));
			$this->values[$name] = $init_value;
			$this->descriptions[$name] = $description;
		} else {
			// Case B: The config setting exists ...
			if ($this->descriptions[$name] != $description) {
				// ... but the human readable description is different.
				// We want to give the plugin authors the possibility to automatically update the config descriptions for their plugins
				// So we just edit the description
				OIDplus::db()->query("update ###config set description = ? where name = ?", array($description, $name));
				$this->descriptions[$name] = $description;
			}
		}

		// Register the validation callback
		if (!is_null($validateCallback)) {
			$this->validateCallbacks[$name] = $validateCallback;
		}
	}

	public function clearCache() {
		$this->configTableReadOnce = false;
		$this->buildConfigArray();
	}

	protected function buildConfigArray() {
		if ($this->configTableReadOnce) return;

		$this->values = array();
		$this->descriptions = array();
		$res = OIDplus::db()->query("select name, description, value from ###config");
		while ($row = $res->fetch_object()) {
			$this->values[$row->name] = $row->value;
			$this->descriptions[$row->name] = $row->description;
		}

		$this->configTableReadOnce = true;
	}

	public function getValue($name, $default=null) {
		if (isset($this->values[$name])) {
			// There is a rare case where this might succeed even
			// before buildConfigArray() was called once:
			// If a setValue() was called, and then getValue() for
			// that same name!
			return $this->values[$name];
		}

		// Read all config settings once and write them in array $this->values
		$this->buildConfigArray();

		// Now we can see if our desired attribute is available
		if (isset($this->values[$name])) {
			return $this->values[$name];
		} else {
			return $default;
		}
	}

	public function exists($name) {
		return !is_null($this->getValue($name, null));
	}

	public function setValue($name, $value) {
		// Avoid unnecessary database writes
		// We do not call getValue() or buildConfigArray(), because they could cause an unnecessary database read
		if (isset($this->values[$name])) {
			if ($this->values[$name] == $value) return;
		}

		// Give plugins the possibility to stop the process by throwing an Exception (e.g. if the value is invalid)
		// Required is that the plugin previously prepared the config setting using prepareConfigKey()
		if (isset($this->validateCallbacks[$name])) {
			$this->validateCallbacks[$name]($value);
		}

		// Now change the value in the database
		OIDplus::db()->query("update ###config set value = ? where name = ?", array($value, $name));
		$this->values[$name] = $value;
	}

	public function delete($name) {
		if ($this->configTableReadOnce) {
			if (isset($this->values[$name])) {
				OIDplus::db()->query("delete from ###config where name = ?", array($name));
			}
		} else {
			// We do not know if the value exists.
			// buildConfigArray() would do many reads which are unnecessary.
			// So we just do a MySQL command to delete the stuff:
			OIDplus::db()->query("delete from ###config where name = ?", array($name));
		}

		unset($this->values[$name]);
		unset($this->descriptions[$name]);
		unset($this->validateCallbacks[$name]);
	}

}
