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

class OIDplusRAAuthInfo {

	private $salt;
	private $authKey;

	public function setSalt($salt) {
		if (strlen($salt) > 100) throw new OIDplusException(_L('Field %1 is too long. Max allowed %2','Salt',100));
		$this->salt = $salt;
	}

	public function setAuthKey($authKey) {
		if (strlen($authKey) > 100) throw new OIDplusException(_L('Field %1 is too long. Max allowed %2','Auth key',100));
		$this->authKey = $authKey;
	}

	public function getSalt() {
		return $this->salt;
	}

	public function getAuthKey() {
		return $this->authKey;
	}

	public function __construct($salt, $authKey) {
		$this->setSalt($salt);
		$this->setAuthKey($authKey);
	}

	public function isPasswordLess() {
		return empty($this->authKey);
	}

}
