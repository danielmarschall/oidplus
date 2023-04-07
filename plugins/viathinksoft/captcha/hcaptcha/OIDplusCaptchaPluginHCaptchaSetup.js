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

captcha_plugin_combobox_change_callbacks.push(function(strPlugin) {
	$("#CAPTCHAPLUGIN_PARAMS_HCAPTCHA")[0].style.display = (strPlugin == 'hCaptcha') ? "Block" : "None";
});

rebuild_callbacks.push(function() {
	var e = $("#captcha_plugin")[0];
	var strPlugin = e.options[e.selectedIndex].value;
	if (strPlugin != 'hCaptcha') return true;

	$("#hcaptcha_sitekey")[0].innerHTML = '';
	$("#hcaptcha_secret")[0].innerHTML = '';

	error = false;

	// Check 1: Site key must not be empty
	if ($("#hcaptcha_sitekey")[0].value.length == 0)
	{
		$("#hcaptcha_sitekey_warn")[0].innerHTML = '<font color="red">'+_L('Please specify a site key!')+'</font>';
		$("#config")[0].innerHTML = '<b>&lt?php</b><br><br><i>// ERROR: Please specify a hCaptcha site key!</i>'; // do not translate
		error = true;
	} else {
		$("#hcaptcha_sitekey_warn")[0].innerHTML = '';
	}

	// Check 2: secret must not be empty
	if ($("#hcaptcha_secret")[0].value.length == 0)
	{
		$("#hcaptcha_secret_warn")[0].innerHTML = '<font color="red">'+_L('Please specify a secret!')+'</font>';
		$("#config")[0].innerHTML = '<b>&lt?php</b><br><br><i>// ERROR: Please specify a hCaptcha secret!</i>'; // do not translate
		error = true;
	} else {
		$("#hcaptcha_secret_warn")[0].innerHTML = '';
	}

	if ($("#hcaptcha_curl_status")[0].value != '1') {
		error = true;
	}

	return !error;
});

captcha_rebuild_config_callbacks.push(function() {
	var e = $("#captcha_plugin")[0];
	var strPlugin = e.options[e.selectedIndex].value;
	if (strPlugin != 'hCaptcha') return '';
	return 'OIDplus::baseConfig()->setValue(\'HCAPTCHA_SITEKEY\',  ' + jsString($("#hcaptcha_sitekey")[0].value) + ');<br>' +
	       'OIDplus::baseConfig()->setValue(\'HCAPTCHA_SECRET\',   ' + jsString($("#hcaptcha_secret")[0].value) + ');<br>';
});
