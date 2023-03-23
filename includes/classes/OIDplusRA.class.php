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

class OIDplusRA extends OIDplusBaseClass {
	private $email = null;

	/**
	 * @param string $email
	 */
	public function __construct(string $email) {
		$this->email = $email;
	}

	/**
	 * @return string
	 */
	public function raEmail(): string {
		return $this->email;
	}

	/**
	 * @return bool
	 * @throws OIDplusException
	 */
	public function existing(): bool {
		$res = OIDplus::db()->query("select email from ###ra where email = ?", array($this->email));
		return ($res->any());
	}

	/**
	 * @return string
	 * @throws OIDplusException
	 */
	public function raName(): string {
		$res = OIDplus::db()->query("select ra_name from ###ra where email = ?", array($this->email));
		if (!$res->any()) return _L('(RA not in database)');
		$row = $res->fetch_array();
		return $row['ra_name'];
	}

	/**
	 * @return OIDplusRA[]
	 * @throws OIDplusException
	 */
	public static function getAllRAs(): array {
		$out = array();
		$res = OIDplus::db()->query("select email from ###ra");
		while ($row = $res->fetch_array()) {
			$out[] = new OIDplusRA($row['email']);
		}
		return $out;
	}

	/**
	 * @param string $new_password
	 * @return void
	 * @throws OIDplusException
	 */
	public function change_password(string $new_password) {
		$authInfo = OIDplus::authUtils()->raGeneratePassword($new_password);
		$calc_authkey = $authInfo->getAuthKey();
		OIDplus::db()->query("update ###ra set authkey=? where email = ?", array($calc_authkey, $this->email));
	}

	/**
	 * @param string $new_email
	 * @return void
	 * @throws OIDplusException
	 */
	public function change_email(string $new_email) {
		OIDplus::db()->query("update ###ra set email = ? where email = ?", array($new_email, $this->email));
	}

	/**
	 * @param string|null $new_password
	 * @return void
	 * @throws OIDplusException
	 */
	public function register_ra(/*?string*/ $new_password) {
		if (is_null($new_password)) {
			// Invalid password (used for LDAP/OAuth)
			$calc_authkey = '';
		} else {
			$authInfo = OIDplus::authUtils()->raGeneratePassword($new_password);
			$calc_authkey = $authInfo->getAuthKey();
		}

		OIDplus::db()->query("insert into ###ra (authkey, email, registered, ra_name, personal_name, organization, office, street, zip_town, country, phone, mobile, fax) values (?, ?, ".OIDplus::db()->sqlDate().", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array($calc_authkey, $this->email, "", "", "", "", "", "", "", "", "", ""));
	}

	/**
	 * @return OIDplusRAAuthInfo|null
	 * @throws OIDplusException
	 */
	public function getAuthInfo()/*: ?OIDplusRAAuthInfo*/ {
		$ra_res = OIDplus::db()->query("select authkey from ###ra where email = ?", array($this->email));
		if (!$ra_res->any()) return null; // User not found
		$ra_row = $ra_res->fetch_array();

		return new OIDplusRAAuthInfo($ra_row['authkey']);
	}

	/**
	 * @param string $password
	 * @return bool
	 * @throws OIDplusException
	 */
	public function checkPassword(string $password): bool {
		return OIDplus::authUtils()->raCheckPassword($this->email, $password);
	}

	/**
	 * @return void
	 * @throws OIDplusException
	 */
	public function delete() {
		OIDplus::db()->query("delete from ###ra where email = ?", array($this->email));
	}

	/**
	 * @param string $ra_name
	 * @return void
	 * @throws OIDplusException
	 */
	public function setRaName(string $ra_name) {
		OIDplus::db()->query("update ###ra set ra_name = ? where email = ?", array($ra_name, $this->email));
	}

	/**
	 * @return bool|null
	 * @throws OIDplusException
	 */
	public function isPasswordLess()/*: ?bool*/ {
		$authInfo = $this->getAuthInfo();
		if (!$authInfo) return null; // user not found
		return $authInfo->isPasswordLess();
	}
}
