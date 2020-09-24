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
	document.getElementById('DBPLUGIN_PARAMS_PDO').style.display = (strPlugin == 'PDO') ? "Block" : "None";
});

rebuild_callbacks.push(function() {
	var e = document.getElementById("db_plugin");
	var strPlugin = e.options[e.selectedIndex].value;
	if (strPlugin != 'PDO') return true;

	document.getElementById('struct_cli_1').innerHTML = '';
	document.getElementById('struct_cli_2').innerHTML = '';
	document.getElementById('struct_1').href = 'struct_empty.sql.php';
	document.getElementById('struct_2').href = 'struct_with_examples.sql.php';

	error = false;

	// Check 1: dsn must not be empty
	if (document.getElementById('pdo_dsn').value.length == 0)
	{
		document.getElementById('pdo_dsn_warn').innerHTML = '<font color="red">'+_L('Please specify a DSN!')+'</font>';
		document.getElementById('config').innerHTML = '<b>&lt?php</b><br><br><i>// ERROR: Please specify a DSN!</i>'; // do not translate
		error = true;
	} else {
		document.getElementById('pdo_dsn_warn').innerHTML = '';
	}

	// Check 2: Username must not be empty
	if (document.getElementById('pdo_username').value.length == 0)
	{
		document.getElementById('pdo_username_warn').innerHTML = '<font color="red">'+_L('Please specify a username!')+'</font>';
		document.getElementById('config').innerHTML = '<b>&lt?php</b><br><br><i>// ERROR: Please specify a username!</i>'; // do not translate
		error = true;
	} else {
		document.getElementById('pdo_username_warn').innerHTML = '';
	}

	document.getElementById('struct_1').href = setupdir+'struct_empty.sql.php?plugin=pdo&prefix='+encodeURI(document.getElementById('tablename_prefix').value)+'&slang='+encodeURI(document.getElementById('pdo_slang').value);
	document.getElementById('struct_2').href = setupdir+'struct_with_examples.sql.php?plugin=pdo&prefix='+encodeURI(document.getElementById('tablename_prefix').value)+'&slang='+encodeURI(document.getElementById('pdo_slang').value);
	if (document.getElementById('pdo_slang').value == 'mysql') {
		document.getElementById('struct_cli_1').innerHTML = '<br>'+_L('or via command line:')+'<br><code>curl -s "'+document.getElementById('struct_1').href+'" | mysql -u '+document.getElementById('pdo_username').value+' -p</code>';
		document.getElementById('struct_cli_2').innerHTML = '<br>'+_L('or via command line:')+'<br><code>curl -s "'+document.getElementById('struct_2').href+'" | mysql -u '+document.getElementById('pdo_username').value+' -p</code>';
	} else if (document.getElementById('pdo_slang').value == 'pgsql') {
		document.getElementById('struct_cli_1').innerHTML = '<br>'+_L('or via command line:')+'<br><code>curl -s "'+document.getElementById('struct_1').href+'" | psql -h <font color="red">localhost</font> -U '+document.getElementById('pdo_username').value+' -d <font color="red">oidplus</font> -a</code>';
		document.getElementById('struct_cli_2').innerHTML = '<br>'+_L('or via command line:')+'<br><code>curl -s "'+document.getElementById('struct_2').href+'" | psql -h <font color="red">localhost</font> -U '+document.getElementById('pdo_username').value+' -d <font color="red">oidplus</font> -a</code>';
	} else {
		document.getElementById('struct_cli_1').innerHTML = '';
		document.getElementById('struct_cli_2').innerHTML = '';
	}

	return !error;
});

rebuild_config_callbacks.push(function() {
	var e = document.getElementById("db_plugin");
	var strPlugin = e.options[e.selectedIndex].value;
	if (strPlugin != 'PDO') return '';

	return 'OIDplus::baseConfig()->setValue(\'PDO_DSN\',           \''+document.getElementById('pdo_dsn').value+'\');<br>' +
	       'OIDplus::baseConfig()->setValue(\'PDO_USERNAME\',      \''+document.getElementById('pdo_username').value+'\');<br>' +
	       'OIDplus::baseConfig()->setValue(\'PDO_PASSWORD\',      base64_decode(\''+b64EncodeUnicode(document.getElementById('pdo_password').value)+'\'));<br>';
});
