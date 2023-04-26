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

class OIDplusDatabasePluginADO extends OIDplusDatabasePlugin {

	/**
	 * @return string
	 */
	public static function id(): string {
		return "ADO";
	}

	/**
	 * @return OIDplusDatabaseConnection
	 */
	public static function newConnection(): OIDplusDatabaseConnection {
		return new OIDplusDatabaseConnectionADO();
	}

	/**
	 * @return string
	 */
	public static function setupHTML(): string {
		return '<div id="DBPLUGIN_PARAMS_ADO">'.
		       '	<p>'._L('ADO ConnectionString').' (<a href="https://www.connectionstrings.com/" target="_blank">'._L('examples').'</a>):<br><input id="ado_connection_string" type="text" value="Provider=MSOLEDBSQL;Data Source=localhost\instancename;Initial Catalog=oidplus;Application Name=OIDplus 2.0;Integrated Security=SSPI" onkeypress="rebuild()" onkeyup="rebuild()" style="width:600px">  <span id="ado_connection_string_warn"></span>'.
		       // TODO: Show these examples based on the slang the user chooses
		       '	<br><font size="-1">'._L('Example for SQL Server').': <i>Provider=<abbr title="'._L('Alternatively').': '._L('Generation %1', 1).' = SQLOLEDB, '._L('Generation %1', 2).' = SQLNCLI11, '._L('Generation %1', 3).' = MSOLEDBSQL">MSOLEDBSQL</abbr>;Data Source=<abbr title="'._L('Alternatively').': localhost,1433">localhost\instancename</abbr>;Initial Catalog=oidplus;Application Name=OIDplus 2.0;<abbr title="'._L('Alternatively').': User Id=...;Password=...">Integrated Security=SSPI</abbr></i>'.
		       '	<br>'._L('Example for Microsoft Access').': <i>Provider=MSDASQL;DRIVER={Microsoft Access Driver (*.mdb, *.accdb)};Dbq=C:\inetpub\wwwroot\oidplus\trunk\userdata\database\oidplus.accdb;</i></font></p>'.
		       '	<!-- '._L('Note: You should use the driver "ODBC Driver XX for SQL Server" instead, since it is the latest generation').' -->'.
		       '	<p>'._L('Which DBMS (SQL dialect) is used?').'<br>'.
		       '	<select name="ado_slang" id="ado_slang" onChange="dbplugin_changed()">'.
		       '	<!-- %SQL_SLANG_SELECTION% -->'.
		       '	</select><br>'.
		       '	<i>'._L('Attention: If your DBMS is not listed, OIDplus will most likely not work. If you know that your DBMS is compatible with one of these listed SQL dialects, you can choose that dialect.').'</i></p>'.
		       '</div>';
	}

}
