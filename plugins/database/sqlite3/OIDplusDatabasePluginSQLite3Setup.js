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
	document.getElementById('DBPLUGIN_PARAMS_SQLite3').style.display = (strPlugin == 'SQLite3') ? "Block" : "None";
});

rebuild_callbacks.push(function() {
	var e = document.getElementById("db_plugin");
	var strPlugin = e.options[e.selectedIndex].value;
	if (strPlugin != 'SQLite3') return true;

	document.getElementById('struct_cli_1').innerHTML = '';
	document.getElementById('struct_cli_2').innerHTML = '';
	document.getElementById('struct_1').href = 'struct_empty.sql.php';
	document.getElementById('struct_2').href = 'struct_with_examples.sql.php';

	error = false;

	// Check 1: Filename must not be empty
	if (document.getElementById('sqlite3_file').value.length == 0)
	{
		document.getElementById('sqlite3_file_warn').innerHTML = '<font color="red">'+_L('Please specify a filename!')+'</font>';
		document.getElementById('config').innerHTML = '<b>&lt?php</b><br><br><i>// ERROR: Please specify a filename!</i>'; // do not translate
		error = true;
	} else {
		document.getElementById('sqlite3_file_warn').innerHTML = '';
	}

	document.getElementById('struct_1').href = setupdir+'struct_empty.sql.php?plugin=sqlite&prefix='+encodeURI(document.getElementById('tablename_prefix').value)+'&slang=sqlite';
	document.getElementById('struct_2').href = setupdir+'struct_with_examples.sql.php?plugin=sqlite&prefix='+encodeURI(document.getElementById('tablename_prefix').value)+'&slang=sqlite';
	document.getElementById('struct_cli_1').innerHTML = '<br>'+_L('or via command line:')+'<br><code>curl -s "'+document.getElementById('struct_1').href+'" | sqlite3 '+document.getElementById('sqlite3_file').value+'<!-- TODO: encryption key? --></code>';
	document.getElementById('struct_cli_2').innerHTML = '<br>'+_L('or via command line:')+'<br><code>curl -s "'+document.getElementById('struct_2').href+'" | sqlite3 '+document.getElementById('sqlite3_file').value+'<!-- TODO: encryption key? --></code>';

	return !error;
});

rebuild_config_callbacks.push(function() {
	var e = document.getElementById("db_plugin");
	var strPlugin = e.options[e.selectedIndex].value;
	if (strPlugin != 'SQLite3') return '';

	return 'OIDplus::baseConfig()->setValue(\'SQLITE3_FILE\',      \''+document.getElementById('sqlite3_file').value+'\');<br>' +
	       'OIDplus::baseConfig()->setValue(\'SQLITE3_ENCRYPTION\',base64_decode(\''+b64EncodeUnicode(document.getElementById('sqlite3_encryption').value)+'\'));<br>';
});
