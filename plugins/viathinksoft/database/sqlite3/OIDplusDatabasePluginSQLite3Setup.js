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
	$("#DBPLUGIN_PARAMS_SQLite3")[0].style.display = (strPlugin == 'SQLite3') ? "Block" : "None";
});

rebuild_callbacks.push(function() {
	var e = $("#db_plugin")[0];
	var strPlugin = e.options[e.selectedIndex].value;
	if (strPlugin != 'SQLite3') return true;

	$("#struct_cli_1")[0].innerHTML = '';
	$("#struct_cli_2")[0].innerHTML = '';
	$("#struct_1")[0].href = 'struct_empty.sql.php';
	$("#struct_2")[0].href = 'struct_with_examples.sql.php';

	error = false;

	// Check 1: Filename must not be empty
	if ($("#sqlite3_file")[0].value.length == 0)
	{
		$("#sqlite3_file_warn")[0].innerHTML = '<font color="red">'+_L('Please specify a filename!')+'</font>';
		$("#config")[0].innerHTML = '<b>&lt?php</b><br><br><i>// ERROR: Please specify a filename!</i>'; // do not translate
		error = true;
	} else {
		$("#sqlite3_file_warn")[0].innerHTML = '';
	}

	$("#struct_1")[0].href = setupdir+'struct_empty.sql.php?plugin=sqlite&prefix='+encodeURI($("#tablename_prefix")[0].value)+'&slang=sqlite';
	$("#struct_2")[0].href = setupdir+'struct_with_examples.sql.php?plugin=sqlite&prefix='+encodeURI($("#tablename_prefix")[0].value)+'&slang=sqlite';
	$("#struct_cli_1")[0].innerHTML = '<br>'+_L('or via command line:')+'<br><code id="struct_cli_1_code">curl -s "'+$("#struct_1")[0].href+'" | sqlite3 '+$("#sqlite3_file")[0].value+'<!-- TODO: encryption key? --></code><br><input type="button" value="'+_L('Copy to clipboard')+'" onClick="copyToClipboard(struct_cli_1_code)">';
	$("#struct_cli_2")[0].innerHTML = '<br>'+_L('or via command line:')+'<br><code id="struct_cli_2_code">curl -s "'+$("#struct_2")[0].href+'" | sqlite3 '+$("#sqlite3_file")[0].value+'<!-- TODO: encryption key? --></code><br><input type="button" value="'+_L('Copy to clipboard')+'" onClick="copyToClipboard(struct_cli_2_code)">';

	return !error;
});

rebuild_config_callbacks.push(function() {
	var e = $("#db_plugin")[0];
	var strPlugin = e.options[e.selectedIndex].value;
	if (strPlugin != 'SQLite3') return '';

	return 'OIDplus::baseConfig()->setValue(\'SQLITE3_FILE\',      \''+$("#sqlite3_file")[0].value+'\');<br>' +
	       'OIDplus::baseConfig()->setValue(\'SQLITE3_ENCRYPTION\','+_b64EncodeUnicode($("#sqlite3_encryption")[0].value)+');<br>';
});
