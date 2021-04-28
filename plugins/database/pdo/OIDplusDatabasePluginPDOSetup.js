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
	$("#DBPLUGIN_PARAMS_PDO")[0].style.display = (strPlugin == 'PDO') ? "Block" : "None";
});

rebuild_callbacks.push(function() {
	var e = $("#db_plugin")[0];
	var strPlugin = e.options[e.selectedIndex].value;
	if (strPlugin != 'PDO') return true;

	$("#struct_cli_1")[0].innerHTML = '';
	$("#struct_cli_2")[0].innerHTML = '';
	$("#struct_1")[0].href = 'struct_empty.sql.php';
	$("#struct_2")[0].href = 'struct_with_examples.sql.php';

	error = false;

	// Check 1: dsn must not be empty
	if ($("#pdo_dsn")[0].value.length == 0)
	{
		$("#pdo_dsn_warn")[0].innerHTML = '<font color="red">'+_L('Please specify a DSN!')+'</font>';
		$("#config")[0].innerHTML = '<b>&lt?php</b><br><br><i>// ERROR: Please specify a DSN!</i>'; // do not translate
		error = true;
	} else {
		$("#pdo_dsn_warn")[0].innerHTML = '';
	}

	// Check 2: Username must not be empty
	if ($("#pdo_username")[0].value.length == 0)
	{
		$("#pdo_username_warn")[0].innerHTML = '<font color="red">'+_L('Please specify a username!')+'</font>';
		$("#config")[0].innerHTML = '<b>&lt?php</b><br><br><i>// ERROR: Please specify a username!</i>'; // do not translate
		error = true;
	} else {
		$("#pdo_username_warn")[0].innerHTML = '';
	}

	$("#struct_1")[0].href = setupdir+'struct_empty.sql.php?plugin=pdo&prefix='+encodeURI($("#tablename_prefix")[0].value)+'&slang='+encodeURI($("#pdo_slang")[0].value);
	$("#struct_2")[0].href = setupdir+'struct_with_examples.sql.php?plugin=pdo&prefix='+encodeURI($("#tablename_prefix")[0].value)+'&slang='+encodeURI($("#pdo_slang")[0].value);
	if ($("#pdo_slang")[0].value == 'mysql') {
		$("#struct_cli_1")[0].innerHTML = '<br>'+_L('or via command line:')+'<br><code>curl -s "'+$("#struct_1")[0].href+'" | mysql -u '+$("#pdo_username")[0].value+' -p</code>';
		$("#struct_cli_2")[0].innerHTML = '<br>'+_L('or via command line:')+'<br><code>curl -s "'+$("#struct_2")[0].href+'" | mysql -u '+$("#pdo_username")[0].value+' -p</code>';
	} else if ($("#pdo_slang")[0].value == 'pgsql') {
		$("#struct_cli_1")[0].innerHTML = '<br>'+_L('or via command line:')+'<br><code>curl -s "'+$("#struct_1")[0].href+'" | psql -h <font color="red">localhost</font> -U '+$("#pdo_username")[0].value+' -d <font color="red">oidplus</font> -a</code>';
		$("#struct_cli_2")[0].innerHTML = '<br>'+_L('or via command line:')+'<br><code>curl -s "'+$("#struct_2")[0].href+'" | psql -h <font color="red">localhost</font> -U '+$("#pdo_username")[0].value+' -d <font color="red">oidplus</font> -a</code>';
	} else {
		$("#struct_cli_1")[0].innerHTML = '';
		$("#struct_cli_2")[0].innerHTML = '';
	}

	return !error;
});

rebuild_config_callbacks.push(function() {
	var e = $("#db_plugin")[0];
	var strPlugin = e.options[e.selectedIndex].value;
	if (strPlugin != 'PDO') return '';

	return 'OIDplus::baseConfig()->setValue(\'PDO_DSN\',           \''+$("#pdo_dsn")[0].value+'\');<br>' +
	       'OIDplus::baseConfig()->setValue(\'PDO_USERNAME\',      \''+$("#pdo_username")[0].value+'\');<br>' +
	       'OIDplus::baseConfig()->setValue(\'PDO_PASSWORD\',      base64_decode(\''+b64EncodeUnicode($("#pdo_password")[0].value)+'\'));<br>' +
	       'OIDplus::baseConfig()->setValue(\'FORCE_DBMS_SLANG\',  \''+$("#pdo_slang")[0].value+'\');<br>'; // optional
});
