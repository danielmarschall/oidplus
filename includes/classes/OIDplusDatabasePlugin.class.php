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

abstract class OIDplusDatabasePlugin extends OIDplusPlugin {

	public abstract static function id(): string; // this is the name that is set to the configuration value OIDplus::baseConfig()->getValue('DATABASE_PLUGIN') to identify the database plugin

	public abstract static function newConnection(): OIDplusDatabaseConnection;

	public abstract static function setupHTML(): string;

}
