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

namespace ViaThinkSoft\OIDplus\Plugins\Database\SQLite3;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusDatabaseConnection;
use ViaThinkSoft\OIDplus\Core\OIDplusDatabasePlugin;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusDatabasePluginSQLite3 extends OIDplusDatabasePlugin {

	/**
	 * @return string
	 */
	public static function id(): string {
		return "SQLite3";
	}

	/**
	 * @return OIDplusDatabaseConnection
	 */
	public static function newConnection(): OIDplusDatabaseConnection {
		return new OIDplusDatabaseConnectionSQLite3();
	}

	/**
	 * @return string
	 */
	public static function setupHTML(): string {
		$default_file = OIDplus::getUserDataDir("database").'oidplus.db';
		$default_file = substr($default_file, strlen(OIDplus::localpath(NULL))); // "censor" the system local path
		return '<div id="DBPLUGIN_PARAMS_SQLite3">'.
		       '	<p>'._L('SQLite3 database file').':<br><input id="sqlite3_file" type="text" value="'.htmlentities($default_file).'" onkeypress="rebuild()" onkeyup="rebuild()">  <span id="sqlite3_file_warn"></span><br>'.
		       '	<i>Please note that the database file and the folder must have write-permissions (<a href="https://stackoverflow.com/questions/3319112/sqlite-error-attempt-to-write-a-readonly-database-during-insert" target="_blank">more information</a>)</i></p>'.
		       '	<p>'._L('SQLite3 encryption passphrase (optional)').':<br><input id="sqlite3_encryption" type="password" autocomplete="new-password" onkeypress="rebuild()" onkeyup="rebuild()"></p>'.
		       '</div>';
	}

}
