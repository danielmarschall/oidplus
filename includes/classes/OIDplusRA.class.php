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

class OIDplusRA {
	private $email = null;

	public function __construct($email) {
		$this->email = $email;
	}

	public function raEmail() {
		return $this->email;
	}

	public function raName() {
		$res = OIDplus::db()->query("select ra_name from ".OIDPLUS_TABLENAME_PREFIX."ra where email = ?", array($this->email));
		if ($res->num_rows() == 0) return "(RA not in database)";
		$row = $res->fetch_array();
		return $row['ra_name'];
	}

	public static function getAllRAs() {
		$out = array();
		$res = OIDplus::db()->query("select email from ".OIDPLUS_TABLENAME_PREFIX."ra");
		while ($row = $res->fetch_array()) {
			$out[] = new OIDplusRA($row['email']);
		}
		return $out;
	}

	public function change_password($new_password) {
		$s_salt = substr(md5(rand()), 0, 7);
		$calc_authkey = 'A2#'.base64_encode(version_compare(PHP_VERSION, '7.1.0') >= 0 ? hash('sha3-512', $s_salt.$new_password, true) : bb\Sha3\Sha3::hash($s_salt.$new_password, 512, true));
		OIDplus::db()->query("update ".OIDPLUS_TABLENAME_PREFIX."ra set salt=?, authkey=? where email = ?", array($s_salt, $calc_authkey, $this->email));
	}

	public function change_email($new_email) {
		OIDplus::db()->query("update ".OIDPLUS_TABLENAME_PREFIX."ra set email = ? where email = ?", array($new_email, $this->email));
	}

	public function register_ra($new_password) {
		$s_salt = substr(md5(rand()), 0, 7);
		$calc_authkey = 'A2#'.base64_encode(version_compare(PHP_VERSION, '7.1.0') >= 0 ? hash('sha3-512', $s_salt.$new_password, true) : bb\Sha3\Sha3::hash($s_salt.$new_password, 512, true));
		OIDplus::db()->query("insert into ".OIDPLUS_TABLENAME_PREFIX."ra (salt, authkey, email, registered) values (?, ?, ?, now())", array($s_salt, $calc_authkey, $this->email));
	}

	public function checkPassword($password) {
		$ra_res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = ?", array($this->email));
		$ra_row = $ra_res->fetch_array();

		$plugins = OIDplus::getAuthPlugins();
		if (count($plugins) == 0) {
			throw new Exception("No RA authentication plugins found");
		}
		foreach ($plugins as $plugin) {
			if ($plugin->verify($ra_row['authkey'], $ra_row['salt'], $password)) return true;
		}

		return false;
	}

	public function delete() {
		OIDplus::db()->query("delete from ".OIDPLUS_TABLENAME_PREFIX."ra where email = ?", array($this->email));
	}

	public function setRaName($ra_name) {
		OIDplus::db()->query("update ".OIDPLUS_TABLENAME_PREFIX."ra set ra_name = ? where email = ?", array($ra_name, $this->email));
	}
}
