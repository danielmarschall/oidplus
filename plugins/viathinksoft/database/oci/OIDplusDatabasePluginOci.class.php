<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2022 Daniel Marschall, ViaThinkSoft
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

class OIDplusDatabasePluginOci extends OIDplusDatabasePlugin {

	public static function id(): string {
		return "Oracle (OCI8)";
	}

	public static function newConnection(): OIDplusDatabaseConnection {
		return new OIDplusDatabaseConnectionOci();
	}

	public static function setupHTML(): string {
		return '<div id="DBPLUGIN_PARAMS_OCI">'.
		       '	<p>'._L('Oracle connection string, e.g. %1', 'localhost/orcl').':<br><input id="oci_conn_str" type="text" onkeypress="rebuild()" onkeyup="rebuild()"> <span id="oci_conn_str_warn"></span></p>'.
		       '	<p>'._L('Oracle username').':<br><input id="oci_username" type="text" onkeypress="rebuild()" onkeyup="rebuild()"> <span id="oci_username_warn"></span></p>'.
		       '	<p>'._L('Oracle password').':<br><input id="oci_password" type="password" autocomplete="new-password" onkeypress="rebuild()" onkeyup="rebuild()"></p>'.
		       '</div>';
	}

}
