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

namespace ViaThinkSoft\OIDplus\Plugins\Database\ODBC;

use ViaThinkSoft\OIDplus\Core\OIDplusDatabaseConnection;
use ViaThinkSoft\OIDplus\Core\OIDplusDatabasePlugin;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusDatabasePluginODBC extends OIDplusDatabasePlugin {

	/**
	 * @return string
	 */
	public static function id(): string {
		return "ODBC";
	}

	/**
	 * @return OIDplusDatabaseConnection
	 */
	public static function newConnection(): OIDplusDatabaseConnection {
		return new OIDplusDatabaseConnectionODBC();
	}

	/**
	 * @return string
	 */
	public static function setupHTML(): string {
		return '<div id="DBPLUGIN_PARAMS_ODBC">'.
		       '	<p>'._L('ODBC DSN').' (<a href="https://www.connectionstrings.com/" target="_blank">'._L('examples').'</a>):<br><input id="odbc_dsn" type="text" value="DRIVER={SQL Server};SERVER=localhost\instancename;DATABASE=oidplus;CHARSET=UTF8" onkeypress="rebuild()" onkeyup="rebuild()" style="width:600px">  <span id="odbc_dsn_warn"></span>'.
		       // TODO: Show these examples based on the slang the user chooses
		       '	<br><font size="-1">'._L('Example for SQL Server').': <i>DRIVER={SQL Server};SERVER=<abbr title="'._L('Alternatively').': localhost,1433">localhost\instancename</abbr>;DATABASE=oidplus;CHARSET=UTF8</i>'.
		       '	<br>'._L('Example for Microsoft Access').': <i>DRIVER={Microsoft Access Driver (*.mdb, *.accdb)};Dbq=C:\inetpub\wwwroot\oidplus\trunk\userdata\database\oidplus.accdb;</i></font></p>'.
		       '	<!-- '._L('Note: You should use the driver "ODBC Driver XX for SQL Server" instead, since it is the latest generation').' -->'.
		       '	<p>'._L('ODBC Username').':<br><input id="odbc_username" type="text" value="root" onkeypress="rebuild()" onkeyup="rebuild()">  <span id="odbc_username_warn"></span>'.
		       '	<br><font size="-1">'._L('Leave username/password empty if no credentials are required for the DBMS.').'</font></p>'.
		       '	<p>'._L('ODBC Password').':<br><input id="odbc_password" type="password" value="" onkeypress="rebuild()" onkeyup="rebuild()">  <span id="odbc_password_warn"></span></p>'.
		       '	<p>'._L('Which DBMS (SQL dialect) is used?').'<br>'.
		       '	<select name="ado_slang" id="odbc_slang" onChange="dbplugin_changed()">'.
		       '	<!-- %SQL_SLANG_SELECTION% -->'.
		       '	</select><br>'.
		       '	<i>'._L('Attention: If your DBMS is not listed, OIDplus will most likely not work. If you know that your DBMS is compatible with one of these listed SQL dialects, you can choose that dialect.').'</i></p>'.
		       '</div>';
	}

}
