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

abstract class OIDplusDatabasePlugin extends OIDplusPlugin {

	/**
	 * @return string
	 */
	public abstract static function id(): string; // this is the name that is set to the configuration value OIDplus::baseConfig()->getValue('DATABASE_PLUGIN') to identify the database plugin

	/**
	 * @return bool
	 * @throws OIDplusException
	 */
	public final function isActive(): bool {
		return (strtolower($this->id()) == strtolower(OIDplus::baseConfig()->getValue('DATABASE_PLUGIN','')));
	}

	/**
	 * @return OIDplusDatabaseConnection
	 */
	public abstract static function newConnection(): OIDplusDatabaseConnection;

	/**
	 * @return string
	 */
	public abstract static function setupHTML(): string;

}
