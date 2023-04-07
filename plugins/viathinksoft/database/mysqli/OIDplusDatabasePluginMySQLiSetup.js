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
	$("#DBPLUGIN_PARAMS_MySQL")[0].style.display = (strPlugin == 'MySQL') ? "Block" : "None";
});

rebuild_callbacks.push(function() {
	var e = $("#db_plugin")[0];
	var strPlugin = e.options[e.selectedIndex].value;
	if (strPlugin != 'MySQL') return true;

	$("#struct_cli_1")[0].innerHTML = '';
	$("#struct_cli_2")[0].innerHTML = '';
	$("#struct_1")[0].href = 'struct_empty.sql.php';
	$("#struct_2")[0].href = 'struct_with_examples.sql.php';

	error = false;

	// Check 1: host must not be empty
	if ($("#mysql_host")[0].value.length == 0)
	{
		$("#mysql_host_warn")[0].innerHTML = '<font color="red">'+_L('Please specify a host name!')+'</font>';
		$("#config")[0].innerHTML = '<b>&lt?php</b><br><br><i>// ERROR: Please specify a host name!</i>'; // do not translate
		error = true;
	} else {
		$("#mysql_host_warn")[0].innerHTML = '';
	}

	// Check 2: Username must not be empty
	if ($("#mysql_username")[0].value.length == 0)
	{
		$("#mysql_username_warn")[0].innerHTML = '<font color="red">'+_L('Please specify a username!')+'</font>';
		$("#config")[0].innerHTML = '<b>&lt?php</b><br><br><i>// ERROR: Please specify a username!</i>'; // do not translate
		error = true;
	} else {
		$("#mysql_username_warn")[0].innerHTML = '';
	}

	// Check 3: Database name must not be empty
	if ($("#mysql_database")[0].value.length == 0)
	{
		$("#mysql_database_warn")[0].innerHTML = '<font color="red">'+_L('Please specify a database name!')+'</font>';
		$("#config")[0].innerHTML = '<b>&lt?php</b><br><br><i>// ERROR: Please specify a database name!</i>'; // do not translate
		error = true;
	} else {
		$("#mysql_database_warn")[0].innerHTML = '';
	}

	$("#struct_1")[0].href = setupdir+'struct_empty.sql.php?plugin=mysql&prefix='+encodeURI($("#tablename_prefix")[0].value)+'&database='+encodeURI($("#mysql_database")[0].value)+'&slang=mysql';
	$("#struct_2")[0].href = setupdir+'struct_with_examples.sql.php?plugin=mysql&prefix='+encodeURI($("#tablename_prefix")[0].value)+'&database='+encodeURI($("#mysql_database")[0].value)+'&slang=mysql';
	$("#struct_cli_1")[0].innerHTML = '<br>'+_L('or via command line:')+'<br><code id="struct_cli_1_code">curl -s "'+$("#struct_1")[0].href+'" | mysql -u '+$("#mysql_username")[0].value+' -p</code><br><input type="button" value="'+_L('Copy to clipboard')+'" onClick="copyToClipboard(struct_cli_1_code)">';
	$("#struct_cli_2")[0].innerHTML = '<br>'+_L('or via command line:')+'<br><code id="struct_cli_2_code">curl -s "'+$("#struct_2")[0].href+'" | mysql -u '+$("#mysql_username")[0].value+' -p</code><br><input type="button" value="'+_L('Copy to clipboard')+'" onClick="copyToClipboard(struct_cli_2_code)">';

	return !error;
});

rebuild_config_callbacks.push(function() {
	var e = $("#db_plugin")[0];
	var strPlugin = e.options[e.selectedIndex].value;
	if (strPlugin != 'MySQL') return '';

	return 'OIDplus::baseConfig()->setValue(\'MYSQL_HOST\',        ' + jsString($("#mysql_host")[0].value) + ');<br>' +
	       'OIDplus::baseConfig()->setValue(\'MYSQL_USERNAME\',    ' + jsString($("#mysql_username")[0].value) + ');<br>' +
	       'OIDplus::baseConfig()->setValue(\'MYSQL_PASSWORD\',    ' + _b64EncodeUnicode($("#mysql_password")[0].value) + ');<br>' +
	       'OIDplus::baseConfig()->setValue(\'MYSQL_DATABASE\',    ' + jsString($("#mysql_database")[0].value) + ');<br>';
});
