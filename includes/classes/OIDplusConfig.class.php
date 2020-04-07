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

if (!defined('IN_OIDPLUS')) die();

class OIDplusConfig {

	protected $values = array();
	protected $dirty = true;

	public function prepareConfigKey($name, $description, $init_value, $protected, $visible) {
		if (strlen($name) > 50) {
			throw new Exception("Config key name '$name' is too long. (max 50).");
		}
		if (strlen($description) > 255) {
			throw new Exception("Description for config key '$name' is too long (max 255).");
		}
		$this->buildConfigArray();
		if (!isset($this->values[$name])) {
			OIDplus::db()->query("insert into ".OIDPLUS_TABLENAME_PREFIX."config (name, description, value, protected, visible) values (?, ?, ?, ?, ?)", array($name, $description, $init_value, $protected, $visible));
			$this->values[$name] = $init_value;
		}
	}

	public function __construct() {
		// TODO: Auslagern in ein Plugin
		$this->prepareConfigKey('system_title', 'What is the name of your RA?', 'OIDplus 2.0', 0, 1);
		$this->prepareConfigKey('admin_email', 'E-Mail address of the system administrator', '', 0, 1);
		$this->prepareConfigKey('global_cc', 'Global CC for all outgoing emails?', '', 0, 1);
		$this->prepareConfigKey('ra_min_password_length', 'Minimum length for RA passwords', '6', 0, 1);
	}

	public function systemTitle() {
		return trim($this->getValue('system_title'));
	}

	public function globalCC() {
		return trim($this->getValue('global_cc'));
	}

	public function minRaPasswordLength() {
		return $this->getValue('ra_min_password_length');
	}

	/* hardcoded in setup/, because during installation, we don't have a settings database
	public function minAdminPasswordLength() {
		return 10;
	}
	*/

	protected function buildConfigArray() {
		if ($this->dirty) {
			$this->values = array();
			$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."config");
			while ($row = $res->fetch_object()) {
				$this->values[$row->name] = $row->value;
			}
			$this->dirty = false;
		}
	}

	public function getValue($name) {
		$this->buildConfigArray();
		if (isset($this->values[$name])) {
			return $this->values[$name];
		} else {
			return null;
		}
	}

	public function exists($name) {
		$this->buildConfigArray();
		return !is_null($this->getValue($name));
	}

	public function setValue($name, $value) {
		// Check for valid values

		if ($name == 'system_title') {
			if (empty($value)) {
				throw new Exception("Please enter a value for the system title.");

			}
		}
		if (($name == 'global_cc') || ($name == 'admin_email')) {
			if (!empty($value) && !oidplus_valid_email($value)) {
				throw new Exception("This is not a correct email address");
			}
		}
		if ($name == 'ra_min_password_length') {
			if (!is_numeric($value) || ($value < 1)) {
				throw new Exception("Please enter a valid password length.");
			}
		}

		if ($name == 'objecttypes_enabled') {
			# TODO: when objecttypes_enabled is changed at the admin control panel, we need to do a reload of the page, so that jsTree will be updated. Is there anything we can do?

			$ary = explode(';',$value);
			$uniq_ary = array_unique($ary);

			if (count($ary) != count($uniq_ary)) {
				throw new Exception("Please check your input. Some object types are double.");
			}

			foreach ($ary as $ot_check) {
				$ns_found = false;
				foreach (OIDplus::getEnabledObjectTypes() as $ot) {
					if ($ot::ns() == $ot_check) {
						$ns_found = true;
						break;
					}
				}
				foreach (OIDplus::getDisabledObjectTypes() as $ot) {
					if ($ot::ns() == $ot_check) {
						$ns_found = true;
						break;
					}
				}
				if (!$ns_found) {
					throw new Exception("Please check your input. Namespace \"$ot_check\" is not found");
				}
			}
		}

		// Give plugins the possibility to stop the process (e.g. if the value is invalid)

		foreach (OIDplus::getPagePlugins('*') as $plugin) {
			$plugin->cfgSetValue($name, $value);
		}

		// Now change the value in the database

		OIDplus::db()->query("update ".OIDPLUS_TABLENAME_PREFIX."config set value = ? where name = ?", array($value, $name));
		$this->values[$name] = $value;
	}

}
