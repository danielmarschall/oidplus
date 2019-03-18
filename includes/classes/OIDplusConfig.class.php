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

class OIDplusConfig {

	protected $values;

	protected function loadConfig() {
		$this->values = array();
		$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."config");
		while ($row = OIDplus::db()->fetch_object($res)) {
			$this->values[$row->name] = $row->value;
		}

		// Add defaults
		OIDplus::db()->query("insert into ".OIDPLUS_TABLENAME_PREFIX."config (name, description, value) values ('system_title', 'What is the name of your RA?', 'OIDplus 2.0')");
		OIDplus::db()->query("insert into ".OIDPLUS_TABLENAME_PREFIX."config (name, description, value) values ('global_cc', 'Global CC for all outgoing emails?', '')");
		OIDplus::db()->query("insert into ".OIDPLUS_TABLENAME_PREFIX."config (name, description, value) values ('ra_min_password_length', 'Minimum length for RA passwords', '6')");
		OIDplus::db()->query("insert into ".OIDPLUS_TABLENAME_PREFIX."config (name, description, value) values ('max_ra_invite_time', 'Max RA invite time in seconds (0 = infinite)', '0')");
		OIDplus::db()->query("insert into ".OIDPLUS_TABLENAME_PREFIX."config (name, description, value) values ('max_ra_pwd_reset_time', 'Max RA password reset time in seconds (0 = infinite)', '0')");
		OIDplus::db()->query("insert into ".OIDPLUS_TABLENAME_PREFIX."config (name, description, value) values ('oidinfo_export_protected', 'OID-info.com export interface protected (requires admin log in), values 0/1', '1')");
		OIDplus::db()->query("insert into ".OIDPLUS_TABLENAME_PREFIX."config (name, description, value) values ('whois_auth_token', 'OID-over-WHOIS authentification token to display confidential data', '')");
	}

	public function __construct() {
		$this->loadConfig();
	}

	public function systemTitle() {
		return trim($this->values['system_title']);
	}

	public function globalCC() {
		return trim($this->values['global_cc']);
	}

	public function minRaPasswordLength() {
		return $this->values['ra_min_password_length'];
	}

	/*   hardcoded in setup/ , because during installation, we dont have a settings database
	public function minAdminPasswordLength() {
		return 6;
	}
	*/

	public function maxInviteTime() {
		return $this->values['max_ra_invite_time'];
	}

	public function maxPasswordResetTime() {
		return $this->values['max_ra_pwd_reset_time'];
	}

	public function oidinfoExportProtected() {
		$val = $this->values['oidinfo_export_protected'];
		if (($val == 'true') || ($val == '1')) return true;
		return false;
	}

	public function authToken() {
		$val = trim($this->values['whois_auth_token']);
		return empty($val) ? false : $val;
	}
}
