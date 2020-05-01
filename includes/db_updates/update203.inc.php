<?php

/*
 * OIDplus 2.0
 * Copyright 2020 Daniel Marschall, ViaThinkSoft
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

// DATABASE UPDATE 203 -> 204
// This script will be included by OIDplusDatabasePlugin.class.php inside function afterConnect().
// Parameters: $this is the OIDplusDatabasePlugin class
//             $version is the current version (this script MUST increase the number by 1 when it is done)

if (!isset($version)) throw new OIDplusException("Argument 'version' is missing; was the file included in a wrong way?");
if (!isset($this))    throw new OIDplusException("Argument 'this' is missing; was the file included in a wrong way?");

$this->transaction_begin();

if ($this->getSlang()::id() == 'mssql') {
	$this->query("ALTER TABLE ###log_object ADD severity [int]");
	$this->query("ALTER TABLE ###log_user ADD severity [int]");
}
else if ($this->getSlang()::id() == 'mysql') {
	$this->query("ALTER TABLE ###log_object ADD severity int(11)");
	$this->query("ALTER TABLE ###log_user ADD severity int(11)");
}
else if ($this->getSlang()::id() == 'pgsql') {
	$this->query("ALTER TABLE ###log_object ADD severity integer");
	$this->query("ALTER TABLE ###log_user ADD severity integer");
}
else if ($this->getSlang()::id() == 'sqlite') {
	$this->query("ALTER TABLE ###log_object ADD severity integer");
	$this->query("ALTER TABLE ###log_user ADD severity integer");
}

$version = 204;
$this->query("UPDATE ###config SET value = ? WHERE name = 'database_version'", array($version));

$this->transaction_commit();
