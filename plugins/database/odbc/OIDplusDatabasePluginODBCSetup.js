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
	document.getElementById('DBPLUGIN_PARAMS_ODBC').style.display = (strPlugin == 'ODBC') ? "Block" : "None";
});

rebuild_callbacks.push(function() {
	var e = document.getElementById("db_plugin");
	var strPlugin = e.options[e.selectedIndex].value;
	if (strPlugin != 'ODBC') return true;

	document.getElementById('struct_cli_1').innerHTML = '';
	document.getElementById('struct_cli_2').innerHTML = '';
	document.getElementById('struct_1').href = 'struct_empty.sql.php';
	document.getElementById('struct_2').href = 'struct_with_examples.sql.php';

	error = false;

	// Check 1: dsn must not be empty
	if (document.getElementById('odbc_dsn').value.length == 0)
	{
		document.getElementById('odbc_dsn_warn').innerHTML = '<font color="red">'+_L('Please specify a DSN!')+'</font>';
		document.getElementById('config').innerHTML = '<b>&lt?php</b><br><br><i>// ERROR: Please specify a DSN!</i>'; // do not translate
		error = true;
	} else {
		document.getElementById('odbc_dsn_warn').innerHTML = '';
	}

	// Check 2: Username must not be empty
	if (document.getElementById('odbc_username').value.length == 0)
	{
		document.getElementById('odbc_username_warn').innerHTML = '<font color="red">'+_L('Please specify a username!')+'</font>';
		document.getElementById('config').innerHTML = '<b>&lt?php</b><br><br><i>// ERROR: Please specify a username!</i>'; // do not translate
		error = true;
	} else {
		document.getElementById('odbc_username_warn').innerHTML = '';
	}

	document.getElementById('struct_1').href = setupdir+'struct_empty.sql.php?plugin=odbc&prefix='+encodeURI(document.getElementById('tablename_prefix').value)+'&slang='+encodeURI(document.getElementById('odbc_slang').value);
	document.getElementById('struct_2').href = setupdir+'struct_with_examples.sql.php?plugin=odbc&prefix='+encodeURI(document.getElementById('tablename_prefix').value)+'&slang='+encodeURI(document.getElementById('odbc_slang').value);
	if (document.getElementById('odbc_slang').value == 'mysql') {
		document.getElementById('struct_cli_1').innerHTML = '<br>'+_L('or via command line:')+'<br><code>curl -s "'+document.getElementById('struct_1').href+'" | mysql -u '+document.getElementById('odbc_username').value+' -p</code>';
		document.getElementById('struct_cli_2').innerHTML = '<br>'+_L('or via command line:')+'<br><code>curl -s "'+document.getElementById('struct_2').href+'" | mysql -u '+document.getElementById('odbc_username').value+' -p</code>';
	} else if (document.getElementById('odbc_slang').value == 'pgsql') {
		document.getElementById('struct_cli_1').innerHTML = '<br>'+_L('or via command line:')+'<br><code>curl -s "'+document.getElementById('struct_1').href+'" | psql -h <font color="red">localhost</font> -U '+document.getElementById('odbc_username').value+' -d <font color="red">oidplus</font> -a</code>';
		document.getElementById('struct_cli_2').innerHTML = '<br>'+_L('or via command line:')+'<br><code>curl -s "'+document.getElementById('struct_2').href+'" | psql -h <font color="red">localhost</font> -U '+document.getElementById('odbc_username').value+' -d <font color="red">oidplus</font> -a</code>';
	} else {
		document.getElementById('struct_cli_1').innerHTML = '';
		document.getElementById('struct_cli_2').innerHTML = '';
	}

	return !error;
});

rebuild_config_callbacks.push(function() {
	var e = document.getElementById("db_plugin");
	var strPlugin = e.options[e.selectedIndex].value;
	if (strPlugin != 'ODBC') return '';

	return 'OIDplus::baseConfig()->setValue(\'ODBC_DSN\',          \''+document.getElementById('odbc_dsn').value+'\');<br>' +
	       'OIDplus::baseConfig()->setValue(\'ODBC_USERNAME\',     \''+document.getElementById('odbc_username').value+'\');<br>' +
	       'OIDplus::baseConfig()->setValue(\'ODBC_PASSWORD\',     base64_decode(\''+b64EncodeUnicode(document.getElementById('odbc_password').value)+'\'));<br>' +
	       'OIDplus::baseConfig()->setValue(\'FORCE_DBMS_SLANG\',  \''+document.getElementById('odbc_slang').value+'\');<br>'; // optional
});
