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

class OIDplusDatabasePluginSqlSrv extends OIDplusDatabasePlugin {

	/**
	 * @return string
	 */
	public static function id(): string {
		return "SQLSRV";
	}

	/**
	 * @return OIDplusDatabaseConnection
	 */
	public static function newConnection(): OIDplusDatabaseConnection {
		return new OIDplusDatabaseConnectionSqlSrv();
	}

	/**
	 * @return string
	 */
	public static function setupHTML(): string {
		return '<div id="DBPLUGIN_PARAMS_SQLSRV">'.
		       '	<p>(<a href="https://learn.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server?view=sql-server-ver16" target="_blank">'._L('Download extension from Microsoft').'</a>)</p>'.
		       '	<p>'._L('SQL Server name').':<br><input id="sqlsrv_host" type="text" value="localhost\oidplus,49010" onkeypress="rebuild()" onkeyup="rebuild()">  <span id="sqlsrv_host_warn"></span>'.
		       '    <font size="-1">'.
		       '	<br>'._L('Example with instance name').': <i>localhost\oidplus</i>'.
		       '	<br>'._L('Example with port').': <i>localhost,49001</i></font></p>'.
		       '	<p>'._L('SQL Server username').':<br><input id="sqlsrv_username" type="text" onkeypress="rebuild()" onkeyup="rebuild()"> <span id="sqlsrv_username_warn"></span>'.
			   '    <br><font size="-1">'._L('Leave username/password empty if you want to use SQL Server Integrated Security or if no credentials are required for the DBMS.').'</font></p>'.
		       '	<p>'._L('SQL Server password').':<br><input id="sqlsrv_password" type="password" autocomplete="new-password" onkeypress="rebuild()" onkeyup="rebuild()"></p>'.
		       '	<p>'._L('SQL Server database name').':<br><input id="sqlsrv_database" type="text" onkeypress="rebuild()" onkeyup="rebuild()"> <span id="sqlsrv_database_warn"></span></p>'.
		       '</div>';
	}

}
