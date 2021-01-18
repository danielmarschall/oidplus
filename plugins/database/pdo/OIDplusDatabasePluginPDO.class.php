<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2021 Daniel Marschall, ViaThinkSoft
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

if (!defined('INSIDE_OIDPLUS')) die();

class OIDplusDatabasePluginPDO extends OIDplusDatabasePlugin {

	public static function id(): string {
		return "PDO";
	}

	public static function newConnection(): OIDplusDatabaseConnection {
		return new OIDplusDatabaseConnectionPDO();
	}

	public static function setupHTML(): string {
		return '<div id="DBPLUGIN_PARAMS_PDO">'.
		       '	<p>PDO DSN (<a href="https://www.php.net/manual/de/pdo.drivers.php" target="_blank">more information</a>):<br><input id="pdo_dsn" type="text" value="mysql:dbname=oidplus;host=localhost" onkeypress="rebuild()" onkeyup="rebuild()" style="width:600px">  <span id="pdo_dsn_warn"></span>'.
		       '	<br><font size="-1">'._L('Example for MySQL/MariaDB').': <i>mysql:dbname=oidplus;host=localhost;port=3306</i>'.
		       '	<br>'._L('Example for PostgreSQL').':    <i>pgsql:dbname=oidplus;host=localhost;port=5432</i></font></p>'.
		       ''.
		       '	<p>'._L('PDO Username').':<br><input id="pdo_username" type="text" value="root" onkeypress="rebuild()" onkeyup="rebuild()">  <span id="pdo_username_warn"></span></p>'.
		       '	<p>'._L('PDO Password').':<br><input id="pdo_password" type="password" value="" onkeypress="rebuild()" onkeyup="rebuild()">  <span id="pdo_password_warn"></span></p>'.
		       '	<p>'._L('Which DBMS (SQL dialect) is used?').'<br>'.
		       '	<i>'._L('Attention: If your DBMS is not listed, OIDplus will most likely not work. If you know that your DBMS is compatible with one of these listed SQL dialects, you can choose that dialect.').'</i><br>'.
		       '	<select name="pdo_slang" id="pdo_slang" onChange="dbplugin_changed()">'.
		       '	<!-- %SQL_SLANG_SELECTION% -->'.
		       '	</select></p></div>'.
		       '</div>';
	}

}
