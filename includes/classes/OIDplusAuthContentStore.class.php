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

	/**
	 * @param string $name
	 * @param mixed|null $default
	 * @return mixed|null
	 */
	public abstract function getValue(string $name, $default = NULL);

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public abstract function setValue(string $name, $value);

	/**
	 * @param string $name
	 * @return bool
	 */
	public abstract function exists(string $name): bool;

	/**
	 * @param string $name
	 * @return void
	 */
	public abstract function delete(string $name);

	/**
	 * @return OIDplusAuthContentStore|null
	 * @throws OIDplusException
	 */
	public abstract static function getActiveProvider()/*: ?OIDplusAuthContentStore*/;

	/**
	 * @return mixed
	 */
	public abstract function destroySession();

	/**
	 * @return mixed
	 */
	public abstract function activate();

	/**
	 * @param string $email
	 * @param string $loginfo
	 * @return void
	 */
	public abstract function raLoginEx(string $email, string &$loginfo);

	/**
	 * @param string $email
	 * @param string $loginfo
	 * @return void
	 */
	public abstract function raLogoutEx(string $email, string &$loginfo);

	/**
	 * @param string $loginfo
	 * @return void
	 */
	public abstract function adminLoginEx(string &$loginfo);

	/**
	 * @param string $loginfo
	 * @return void
	 */
	public abstract function adminLogoutEx(string &$loginfo);

	// RA authentication functions (low-level)

	/**
	 * @param string $email
	 * @return void
	 */
	public function raLogin(string $email) {
		if (strpos($email, '|') !== false) return;

		$list = $this->getValue('oidplus_ra_logged_in');
		if (is_null($list)) $list = '';

		$ary = ($list == '') ? array() : explode('|', $list);
		if (!in_array($email, $ary)) $ary[] = $email;
		$list = implode('|', $ary);

		$this->setValue('oidplus_ra_logged_in', $list);
	}

	/**
	 * @param string $email
	 * @return void
	 */
	public function raLogout(string $email) {
		$list = $this->getValue('oidplus_ra_logged_in');
		if (is_null($list)) $list = '';

		$ary = ($list == '') ? array() : explode('|', $list);
		$key = array_search($email, $ary);
		if ($key !== false) unset($ary[$key]);
		$list = implode('|', $ary);

		$this->setValue('oidplus_ra_logged_in', $list);
	}

	/**
	 * @return int
	 */
	public function raNumLoggedIn(): int {
		return count($this->loggedInRaList());
	}

	/**
	 * @return OIDplusRA[]
	 */
	public function loggedInRaList(): array {
		$list = $this->getValue('oidplus_ra_logged_in');
		if (is_null($list)) $list = '';

		$res = array();
		foreach (array_unique(explode('|',$list)) as $ra_email) {
			if ($ra_email == '') continue;
			$res[] = new OIDplusRA($ra_email);
		}
		return $res;
	}

	/**
	 * @param string $email
	 * @return bool
	 */
	public function isRaLoggedIn(string $email) {
		foreach ($this->loggedInRaList() as $ra) {
			if ($email == $ra->raEmail()) return true;
		}
		return false;
	}

	// Admin authentication functions (low-level)

	/**
	 * @return void
	 */
	public function adminLogin() {
		$this->setValue('oidplus_admin_logged_in', 1);
	}

	/**
	 * @return void
	 */
	public function adminLogout() {
		$this->setValue('oidplus_admin_logged_in', 0);
	}

	/**
	 * @return bool
	 */
	public function isAdminLoggedIn(): bool {
		return $this->getValue('oidplus_admin_logged_in') == 1;
	}

}
