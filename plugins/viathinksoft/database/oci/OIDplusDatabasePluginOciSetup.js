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

plugin_combobox_change_callbacks.push(function(strPlugin) {
	$("#DBPLUGIN_PARAMS_OCI")[0].style.display = (strPlugin == 'Oracle (OCI8)') ? "Block" : "None";
});

rebuild_callbacks.push(function() {
	var e = $("#db_plugin")[0];
	var strPlugin = e.options[e.selectedIndex].value;
	if (strPlugin != 'Oracle (OCI8)') return true;

	$("#struct_cli_1")[0].innerHTML = '';
	$("#struct_cli_2")[0].innerHTML = '';
	$("#struct_1")[0].href = 'struct_empty.sql.php';
	$("#struct_2")[0].href = 'struct_with_examples.sql.php';

	error = false;

	// Check 1: connection string must not be empty
	if ($("#oci_conn_str")[0].value.length == 0)
	{
		$("#oci_conn_str_warn")[0].innerHTML = '<font color="red">'+_L('Please specify a TNS / connection string!')+'</font>';
		$("#config")[0].innerHTML = '<b>&lt?php</b><br><br><i>// ERROR: Please specify a TNS / connection string!</i>'; // do not translate
		error = true;
	} else {
		$("#oci_conn_str_warn")[0].innerHTML = '';
	}

	// Check 2: Username must not be empty
	if ($("#oci_username")[0].value.length == 0)
	{
		$("#oci_username_warn")[0].innerHTML = '<font color="red">'+_L('Please specify a username!')+'</font>';
		$("#config")[0].innerHTML = '<b>&lt?php</b><br><br><i>// ERROR: Please specify a username!</i>'; // do not translate
		error = true;
	} else {
		$("#oci_username_warn")[0].innerHTML = '';
	}

	$("#struct_1")[0].href = setupdir+'struct_empty.sql.php?plugin=oci&prefix='+encodeURI($("#tablename_prefix")[0].value)+'&slang=oracle';
	$("#struct_2")[0].href = setupdir+'struct_with_examples.sql.php?plugin=oci&prefix='+encodeURI($("#tablename_prefix")[0].value)+'&slang=oracle';
	$("#struct_cli_1")[0].innerHTML = '';//TODO
	$("#struct_cli_2")[0].innerHTML = '';//TODO

	return !error;
});

rebuild_config_callbacks.push(function() {
	var e = $("#db_plugin")[0];
	var strPlugin = e.options[e.selectedIndex].value;
	if (strPlugin != 'Oracle (OCI8)') return '';

	return 'OIDplus::baseConfig()->setValue(\'OCI_CONN_STR\',      ' + jsString($("#oci_conn_str")[0].value) + ');<br>' +
	       'OIDplus::baseConfig()->setValue(\'OCI_USERNAME\',      ' + jsString($("#oci_username")[0].value) + ');<br>' +
	       'OIDplus::baseConfig()->setValue(\'OCI_PASSWORD\',      ' + _b64EncodeUnicode($("#oci_password")[0].value) + ');<br>';
});
