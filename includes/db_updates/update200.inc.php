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

// DATABASE UPDATE 200 -> 201
// This script will be included by OIDplusDatabasePlugin.class.php inside function afterConnect().
// Parameters: $this is the OIDplusDatabasePlugin class
//             $version is the current version (this script MUST increase the number by 1 when it is done)

if (!defined('IN_OIDPLUS')) die();

if (!isset($version)) throw new OIDplusException("Argument 'version' is missing; was the file included in a wrong way?");
if (!isset($this))    throw new OIDplusException("Argument 'this' is missing; was the file included in a wrong way?");

$this->transaction_begin();

$this->query("ALTER TABLE ".OIDPLUS_TABLENAME_PREFIX."objects ADD comment varchar(255) NULL");

$version = 201;
$this->query("UPDATE ".OIDPLUS_TABLENAME_PREFIX."config SET value = ? WHERE name = 'database_version'", array($version));

$this->transaction_commit();
