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

abstract class OIDplusSqlSlangPlugin extends OIDplusPlugin {

	public abstract static function id(): string;

	public abstract function natOrder($fieldname, $order='asc'): string;

	public abstract function sqlDate(): string;

	public abstract function detect(): bool;

	// Please note: This insert_id() function should use SQL to receive
	// the last inserted ID. If the database connection provider (e.g. PDO)
	// offers a way to fetch the last inserted ID, please use this instead!
	// So, please do NOT use  OIDplus::db()->getSlang()->insert_id()
	// but instead use        OIDplus::db()->insert_id()
	// This way, the database connection provider can override that function
	// with their own method of fetching the last inserted ID.
	public abstract function insert_id(): int;

	public abstract function setupSetTablePrefix($cont, $table, $prefix): string;

	public abstract function setupCreateDbIfNotExists($database): string;

	public abstract function setupUseDatabase($database): string;

}
