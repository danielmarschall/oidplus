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

class OIDplusDatabasePluginSQLite3 extends OIDplusDatabasePlugin {

	public static function id(): string {
		return "SQLite3";
	}

	public static function newConnection(): OIDplusDatabaseConnection {
		return new OIDplusDatabaseConnectionSQLite3();
	}

	public static function setupHTML(): string {
		return '<div id="DBPLUGIN_PARAMS_SQLite3">'.
		       '	<p>'._L('SQLite3 database file').':<br><input id="sqlite3_file" type="text" value="includes/oidplus.db" onkeypress="rebuild()" onkeyup="rebuild()">  <span id="sqlite3_file_warn"></span></p>'.
		       '	<p>'._L('SQLite3 encryption passphrase (optional)').':<br><input id="sqlite3_encryption" type="password" autocomplete="new-password" onkeypress="rebuild()" onkeyup="rebuild()"></p>'.
		       '</div>';
	}

	public static function setupJS(): string {
		return file_get_contents(__DIR__ . '/setup.js');
	}
}
