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

abstract class OIDplusAuthContentStore extends OIDplusBaseClass implements OIDplusGetterSetterInterface {

	// Getter / Setter

	public abstract function getValue($name, $default = NULL);

	public abstract function setValue($name, $value);

	public abstract function exists($name);

	public abstract function delete($name);

	public abstract static function getActiveProvider();

	public abstract function destroySession();

	public abstract function activate();

	public abstract function raLoginEx($email, &$loginfo);

	public abstract function raLogoutEx($email, &$loginfo);

	public abstract function adminLoginEx(&$loginfo);

	public abstract function adminLogoutEx(&$loginfo);

	// RA authentication functions (low-level)

	public function raLogin($email) {
		if (strpos($email, '|') !== false) return;

		$list = $this->getValue('oidplus_ra_logged_in');
		if (is_null($list)) $list = '';

		$ary = ($list == '') ? array() : explode('|', $list);
		if (!in_array($email, $ary)) $ary[] = $email;
		$list = implode('|', $ary);

		$this->setValue('oidplus_ra_logged_in', $list);
	}

	public function raLogout($email) {
		$list = $this->getValue('oidplus_ra_logged_in');
		if (is_null($list)) $list = '';

		$ary = ($list == '') ? array() : explode('|', $list);
		$key = array_search($email, $ary);
		if ($key !== false) unset($ary[$key]);
		$list = implode('|', $ary);

		$this->setValue('oidplus_ra_logged_in', $list);
	}

	public function raNumLoggedIn() {
		return count($this->loggedInRaList());
	}

	public function loggedInRaList() {
		$list = $this->getValue('oidplus_ra_logged_in');
		if (is_null($list)) $list = '';

		$res = array();
		foreach (array_unique(explode('|',$list)) as $ra_email) {
			if ($ra_email == '') continue;
			$res[] = new OIDplusRA($ra_email);
		}
		return $res;
	}

	public function isRaLoggedIn($email) {
		foreach ($this->loggedInRaList() as $ra) {
			if ($email == $ra->raEmail()) return true;
		}
		return false;
	}

	// Admin authentication functions (low-level)

	public function adminLogin() {
		$this->setValue('oidplus_admin_logged_in', 1);
	}

	public function adminLogout() {
		$this->setValue('oidplus_admin_logged_in', 0);
	}

	public function isAdminLoggedIn() {
		return $this->getValue('oidplus_admin_logged_in') == 1;
	}

}
