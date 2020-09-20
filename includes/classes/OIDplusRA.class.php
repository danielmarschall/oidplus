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

class OIDplusRA {
	private $email = null;

	public function __construct($email) {
		$this->email = $email;
	}

	public function raEmail() {
		return $this->email;
	}

	public function existing() {
		$res = OIDplus::db()->query("select email from ###ra where email = ?", array($this->email));
		return ($res->num_rows() > 0);
	}

	public function raName() {
		$res = OIDplus::db()->query("select ra_name from ###ra where email = ?", array($this->email));
		if ($res->num_rows() == 0) return _L('(RA not in database)');
		$row = $res->fetch_array();
		return $row['ra_name'];
	}

	public static function getAllRAs() {
		$out = array();
		$res = OIDplus::db()->query("select email from ###ra");
		while ($row = $res->fetch_array()) {
			$out[] = new OIDplusRA($row['email']);
		}
		return $out;
	}

	public function change_password($new_password) {
		$s_salt = uniqid(mt_rand(), true);
		$calc_authkey = 'A2#'.base64_encode(sha3_512($s_salt.$new_password, true));
		OIDplus::db()->query("update ###ra set salt=?, authkey=? where email = ?", array($s_salt, $calc_authkey, $this->email));
	}

	public function change_email($new_email) {
		OIDplus::db()->query("update ###ra set email = ? where email = ?", array($new_email, $this->email));
	}

	public function register_ra($new_password) {
		$s_salt = uniqid(mt_rand(), true);
		$calc_authkey = 'A2#'.base64_encode(sha3_512($s_salt.$new_password, true));
		OIDplus::db()->query("insert into ###ra (salt, authkey, email, registered, ra_name, personal_name, organization, office, street, zip_town, country, phone, mobile, fax) values (?, ?, ?, ".OIDplus::db()->sqlDate().", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array($s_salt, $calc_authkey, $this->email, "", "", "", "", "", "", "", "", "", ""));
	}

	public function checkPassword($password) {
		$ra_res = OIDplus::db()->query("select authkey, salt from ###ra where email = ?", array($this->email));
		if ($ra_res->num_rows() == 0) return false; // User not found
		$ra_row = $ra_res->fetch_array();

		$plugins = OIDplus::getAuthPlugins();
		if (count($plugins) == 0) {
			throw new OIDplusException(_L('No RA authentication plugins found'));
		}
		foreach ($plugins as $plugin) {
			if ($plugin->verify($ra_row['authkey'], $ra_row['salt'], $password)) return true;
		}

		return false;
	}

	public function delete() {
		OIDplus::db()->query("delete from ###ra where email = ?", array($this->email));
	}

	public function setRaName($ra_name) {
		OIDplus::db()->query("update ###ra set ra_name = ? where email = ?", array($ra_name, $this->email));
	}
}