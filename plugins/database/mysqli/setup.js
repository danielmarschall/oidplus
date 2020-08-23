/*
 * OIDplus 2.0
 * Copyright 2019 Daniel Marschall, ViaThinkSoft
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
	document.getElementById('DBPLUGIN_PARAMS_MySQL').style.display = (strPlugin == 'MySQL') ? "Block" : "None";
});

rebuild_callbacks.push(function() {
	var e = document.getElementById("db_plugin");
	var strPlugin = e.options[e.selectedIndex].value;
	if (strPlugin != 'MySQL') return true;

	document.getElementById('struct_cli_1').innerHTML = '';
	document.getElementById('struct_cli_2').innerHTML = '';
	document.getElementById('struct_1').href = 'struct_empty.sql.php';
	document.getElementById('struct_2').href = 'struct_with_examples.sql.php';

	error = false;

	// Check 1: host must not be empty
	if (document.getElementById('mysql_host').value.length == 0)
	{
		document.getElementById('mysql_host_warn').innerHTML = '<font color="red">'+_L('Please specify a host name!')+'</font>';
		document.getElementById('config').innerHTML = '<b>&lt?php</b><br><br><i>// ERROR: Please specify a host name!</i>'; // do not translate
		error = true;
	} else {
		document.getElementById('mysql_host_warn').innerHTML = '';
	}

	// Check 2: Username must not be empty
	if (document.getElementById('mysql_username').value.length == 0)
	{
		document.getElementById('mysql_username_warn').innerHTML = '<font color="red">'+_L('Please specify a username!')+'</font>';
		document.getElementById('config').innerHTML = '<b>&lt?php</b><br><br><i>// ERROR: Please specify a username!</i>'; // do not translate
		error = true;
	} else {
		document.getElementById('mysql_username_warn').innerHTML = '';
	}

	// Check 3: Database name must not be empty
	if (document.getElementById('mysql_database').value.length == 0)
	{
		document.getElementById('mysql_database_warn').innerHTML = '<font color="red">'+_L('Please specify a database name!')+'</font>';
		document.getElementById('config').innerHTML = '<b>&lt?php</b><br><br><i>// ERROR: Please specify a database name!</i>'; // do not translate
		error = true;
	} else {
		document.getElementById('mysql_database_warn').innerHTML = '';
	}

	document.getElementById('struct_1').href = setupdir+'struct_empty.sql.php?plugin=mysql&prefix='+encodeURI(document.getElementById('tablename_prefix').value)+'&database='+encodeURI(document.getElementById('mysql_database').value)+'&slang=mysql';
	document.getElementById('struct_2').href = setupdir+'struct_with_examples.sql.php?plugin=mysql&prefix='+encodeURI(document.getElementById('tablename_prefix').value)+'&database='+encodeURI(document.getElementById('mysql_database').value)+'&slang=mysql';
	document.getElementById('struct_cli_1').innerHTML = '<br>'+_L('or via command line:')+'<br><code>curl -s "'+document.getElementById('struct_1').href+'" | mysql -u '+document.getElementById('mysql_username').value+' -p</code>';
	document.getElementById('struct_cli_2').innerHTML = '<br>'+_L('or via command line:')+'<br><code>curl -s "'+document.getElementById('struct_2').href+'" | mysql -u '+document.getElementById('mysql_username').value+' -p</code>';

	return !error;
});

rebuild_config_callbacks.push(function() {
	var e = document.getElementById("db_plugin");
	var strPlugin = e.options[e.selectedIndex].value;
	if (strPlugin != 'MySQL') return '';

	return 'OIDplus::baseConfig()->setValue(\'MYSQL_HOST\',        \''+document.getElementById('mysql_host').value+'\');<br>' +
	       'OIDplus::baseConfig()->setValue(\'MYSQL_USERNAME\',    \''+document.getElementById('mysql_username').value+'\');<br>' +
	       'OIDplus::baseConfig()->setValue(\'MYSQL_PASSWORD\',    base64_decode(\''+b64EncodeUnicode(document.getElementById('mysql_password').value)+'\'));<br>' +
	       'OIDplus::baseConfig()->setValue(\'MYSQL_DATABASE\',    \''+document.getElementById('mysql_database').value+'\');<br>';
});
