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

namespace ViaThinkSoft\OIDplus\Plugins\viathinksoft\database\mysqli;

use ViaThinkSoft\OIDplus\Core\OIDplusDatabaseConnection;
use ViaThinkSoft\OIDplus\Core\OIDplusDatabasePlugin;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusDatabasePluginMySQLi extends OIDplusDatabasePlugin {

	/**
	 * @return string
	 */
	public static function id(): string {
		return "MySQL";
	}

	/**
	 * @return OIDplusDatabaseConnection
	 */
	public static function newConnection(): OIDplusDatabaseConnection {
		return new OIDplusDatabaseConnectionMySQLi();
	}

	/**
	 * @return string
	 */
	public static function setupHTML(): string {
		return '<div id="DBPLUGIN_PARAMS_MySQL">'.
		       '	<p>'._L('MySQL hostname and port').':<br><input id="mysql_host" type="text" value="localhost:3306" onkeypress="rebuild()" onkeyup="rebuild()">  <span id="mysql_host_warn"></span></p>'.
		       '	<p>'._L('MySQL username').':<br><input id="mysql_username" type="text" onkeypress="rebuild()" onkeyup="rebuild()"> <span id="mysql_username_warn"></span></p>'.
		       '	<p>'._L('MySQL password').':<br><input id="mysql_password" type="password" autocomplete="new-password" onkeypress="rebuild()" onkeyup="rebuild()"></p>'.
		       '	<p>'._L('MySQL database name').':<br><input id="mysql_database" type="text" onkeypress="rebuild()" onkeyup="rebuild()"> <span id="mysql_database_warn"></span></p>'.
		       '</div>';
	}

}
