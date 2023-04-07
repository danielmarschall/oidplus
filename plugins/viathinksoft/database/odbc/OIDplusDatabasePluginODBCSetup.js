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

plugin_combobox_change_callbacks.push(function(strPlugin) {
	$("#DBPLUGIN_PARAMS_ODBC")[0].style.display = (strPlugin == 'ODBC') ? "Block" : "None";
});

rebuild_callbacks.push(function() {
	var e = $("#db_plugin")[0];
	var strPlugin = e.options[e.selectedIndex].value;
	if (strPlugin != 'ODBC') return true;

	$("#struct_cli_1")[0].innerHTML = '';
	$("#struct_cli_2")[0].innerHTML = '';
	$("#struct_1")[0].href = 'struct_empty.sql.php';
	$("#struct_2")[0].href = 'struct_with_examples.sql.php';

	error = false;

	// Check 1: dsn must not be empty
	if ($("#odbc_dsn")[0].value.length == 0)
	{
		$("#odbc_dsn_warn")[0].innerHTML = '<font color="red">'+_L('Please specify a DSN!')+'</font>';
		$("#config")[0].innerHTML = '<b>&lt?php</b><br><br><i>// ERROR: Please specify a DSN!</i>'; // do not translate
		error = true;
	} else {
		$("#odbc_dsn_warn")[0].innerHTML = '';
	}

	// Check 2: Username must not be empty
	if ($("#odbc_username")[0].value.length == 0)
	{
		$("#odbc_username_warn")[0].innerHTML = '<font color="red">'+_L('Please specify a username!')+'</font>';
		$("#config")[0].innerHTML = '<b>&lt?php</b><br><br><i>// ERROR: Please specify a username!</i>'; // do not translate
		error = true;
	} else {
		$("#odbc_username_warn")[0].innerHTML = '';
	}

	$("#struct_1")[0].href = setupdir+'struct_empty.sql.php?plugin=odbc&prefix='+encodeURI($("#tablename_prefix")[0].value)+'&slang='+encodeURI($("#odbc_slang")[0].value);
	$("#struct_2")[0].href = setupdir+'struct_with_examples.sql.php?plugin=odbc&prefix='+encodeURI($("#tablename_prefix")[0].value)+'&slang='+encodeURI($("#odbc_slang")[0].value);
	if ($("#odbc_slang")[0].value == 'mysql') {
		$("#struct_cli_1")[0].innerHTML = '<br>'+_L('or via command line:')+'<br><code id="struct_cli_1_code">curl -s "'+$("#struct_1")[0].href+'" | mysql -u '+$("#odbc_username")[0].value+' -p</code><br><input type="button" value="'+_L('Copy to clipboard')+'" onClick="copyToClipboard(struct_cli_1_code)">';
		$("#struct_cli_2")[0].innerHTML = '<br>'+_L('or via command line:')+'<br><code id="struct_cli_2_code">curl -s "'+$("#struct_2")[0].href+'" | mysql -u '+$("#odbc_username")[0].value+' -p</code><br><input type="button" value="'+_L('Copy to clipboard')+'" onClick="copyToClipboard(struct_cli_2_code)">';
	} else if ($("#odbc_slang")[0].value == 'pgsql') {
		$("#struct_cli_1")[0].innerHTML = '<br>'+_L('or via command line:')+'<br><code id="struct_cli_1_code">curl -s "'+$("#struct_1")[0].href+'" | psql -h <font color="red">localhost</font> -U '+$("#odbc_username")[0].value+' -d <font color="red">oidplus</font> -a</code><br><input type="button" value="'+_L('Copy to clipboard')+'" onClick="copyToClipboard(struct_cli_1_code)">';
		$("#struct_cli_2")[0].innerHTML = '<br>'+_L('or via command line:')+'<br><code id="struct_cli_2_code">curl -s "'+$("#struct_2")[0].href+'" | psql -h <font color="red">localhost</font> -U '+$("#odbc_username")[0].value+' -d <font color="red">oidplus</font> -a</code><br><input type="button" value="'+_L('Copy to clipboard')+'" onClick="copyToClipboard(struct_cli_2_code)">';
	} else {
		$("#struct_cli_1")[0].innerHTML = '';
		$("#struct_cli_2")[0].innerHTML = '';
	}

	return !error;
});

rebuild_config_callbacks.push(function() {
	var e = $("#db_plugin")[0];
	var strPlugin = e.options[e.selectedIndex].value;
	if (strPlugin != 'ODBC') return '';

	return 'OIDplus::baseConfig()->setValue(\'ODBC_DSN\',          ' + jsString($("#odbc_dsn")[0].value) + ');<br>' +
	       'OIDplus::baseConfig()->setValue(\'ODBC_USERNAME\',     ' + jsString($("#odbc_username")[0].value) + ');<br>' +
	       'OIDplus::baseConfig()->setValue(\'ODBC_PASSWORD\',     ' + _b64EncodeUnicode($("#odbc_password")[0].value) + ');<br>' +
	       'OIDplus::baseConfig()->setValue(\'FORCE_DBMS_SLANG\',  ' + jsString($("#odbc_slang")[0].value) + ');<br>'; // optional
});
