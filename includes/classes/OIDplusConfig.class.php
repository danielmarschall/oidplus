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

	public function __construct() {
		// TODO
	}

	public function systemTitle() {
		// TODO
		if (isset($_SERVER['SERVER_NAME']) && (($_SERVER['SERVER_NAME'] == 'oidplus.viathinksoft.com'))) {
			return 'ViaThinkSoft Registration Authority';
		} else {
			return 'OIDplus 2.0';
		}
	}

	public function globalCC() {
		// TODO
		return 'info@daniel-marschall.de';
	}

	public function minRaPasswordLength() {
		// TODO
		return 6;
	}

	/*   hardcoded in setup/ , because during installation, we dont have a settings database
	public function minAdminPasswordLength() {
		return 6;
	}
	*/

	public function maxInviteTime() {
		// TODO
		return 0; // infinite
	}

	public function maxPasswordResetTime() {
		// TODO
		return 0; // infinite
	}

	public function oidinfoExportProtected() {
		// TODO
		// true = oidinfo_export.php requires admin login
		// false = oidinfo_export.php can be accessed without restrictions
		return false;
	}

}

