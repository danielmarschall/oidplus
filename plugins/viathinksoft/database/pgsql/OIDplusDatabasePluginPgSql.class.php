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

namespace ViaThinkSoft\OIDplus;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusDatabasePluginPgSql extends OIDplusDatabasePlugin {

	public static function id(): string {
		return "PgSQL";
	}

	public static function newConnection(): OIDplusDatabaseConnection {
		return new OIDplusDatabaseConnectionPgSql();
	}

	public static function setupHTML(): string {
		return '<div id="DBPLUGIN_PARAMS_PgSQL">'.
		       '	<p>'._L('PgSQL hostname and port').':<br><input id="pgsql_host" type="text" value="localhost:5432" onkeypress="rebuild()" onkeyup="rebuild()">  <span id="pgsql_host_warn"></span></p>'.
		       '	<p>'._L('PgSQL username').':<br><input id="pgsql_username" type="text" onkeypress="rebuild()" onkeyup="rebuild()"> <span id="pgsql_username_warn"></span></p>'.
		       '	<p>'._L('PgSQL password').':<br><input id="pgsql_password" type="password" autocomplete="new-password" onkeypress="rebuild()" onkeyup="rebuild()"></p>'.
		       '	<p>'._L('PgSQL database name').':<br><input id="pgsql_database" type="text" onkeypress="rebuild()" onkeyup="rebuild()"> <span id="pgsql_database_warn"></span></p>'.
		       '</div>';
	}

}
