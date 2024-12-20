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

namespace ViaThinkSoft\OIDplus\Core;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusRAAuthInfo extends OIDplusBaseClass {

	/**
	 * @var string
	 */
	private string $authKey;

	/**
	 * @param string $authKey
	 * @return void
	 * @throws OIDplusException
	 */
	public function setAuthKey(#[\SensitiveParameter]
	                           string $authKey): void {
		// 250 is the length of the database field
		if (strlen($authKey) > 250) throw new OIDplusException(_L('Field %1 is too long. Max allowed %2','Auth key',250));
		$this->authKey = $authKey;
	}

	/**
	 * @return string
	 */
	public function getAuthKey(): string {
		return $this->authKey;
	}

	/**
	 * @param string $authKey
	 * @throws OIDplusException
	 */
	public function __construct(#[\SensitiveParameter]
	                            string $authKey) {
		$this->setAuthKey($authKey);
	}

	/**
	 * @return bool
	 */
	public function isPasswordLess(): bool {
		return empty($this->authKey);
	}

}
