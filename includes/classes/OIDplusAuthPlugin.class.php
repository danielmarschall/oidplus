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

abstract class OIDplusAuthPlugin extends OIDplusPlugin {

	/**
	 * @return string
	 */
	public abstract function id(): string;

	/**
	 * @param OIDplusRAAuthInfo $authInfo
	 * @param string $check_password
	 * @return bool
	 */
	public abstract function verify(OIDplusRAAuthInfo $authInfo, string $check_password): bool;

	/**
	 * @param string $password
	 * @return OIDplusRAAuthInfo
	 */
	public abstract function generate(string $password): OIDplusRAAuthInfo;

	/**
	 * @param string $reason
	 * @return bool
	 */
	public abstract function availableForHash(string &$reason): bool;

	/**
	 * @param string $reason
	 * @return bool
	 */
	public abstract function availableForVerify(string &$reason): bool;
}
