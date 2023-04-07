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

captcha_plugin_combobox_change_callbacks.push(function(strPlugin) {
	$("#CAPTCHAPLUGIN_PARAMS_RECAPTCHA")[0].style.display = (strPlugin.toLowerCase() == 'reCAPTCHA'.toLowerCase()) ? "Block" : "None";
});

rebuild_callbacks.push(function() {
	var e = $("#captcha_plugin")[0];
	var strPlugin = e.options[e.selectedIndex].value;
	if (strPlugin.toLowerCase() != 'reCAPTCHA'.toLowerCase()) return true;

	$("#recaptcha_public")[0].innerHTML = '';
	$("#recaptcha_private")[0].innerHTML = '';

	error = false;

	// Check 1: Public key must not be empty
	if ($("#recaptcha_public")[0].value.length == 0)
	{
		$("#recaptcha_public_warn")[0].innerHTML = '<font color="red">'+_L('Please specify a public key!')+'</font>';
		$("#config")[0].innerHTML = '<b>&lt?php</b><br><br><i>// ERROR: Please specify a reCAPTCHA public key!</i>'; // do not translate
		error = true;
	} else {
		$("#recaptcha_public_warn")[0].innerHTML = '';
	}

	// Check 2: Private key must not be empty
	if ($("#recaptcha_private")[0].value.length == 0)
	{
		$("#recaptcha_private_warn")[0].innerHTML = '<font color="red">'+_L('Please specify a private key!')+'</font>';
		$("#config")[0].innerHTML = '<b>&lt?php</b><br><br><i>// ERROR: Please specify a reCAPTCHA private key!</i>'; // do not translate
		error = true;
	} else {
		$("#recaptcha_private_warn")[0].innerHTML = '';
	}

	return !error;
});

captcha_rebuild_config_callbacks.push(function() {
	var e = $("#captcha_plugin")[0];
	var strPlugin = e.options[e.selectedIndex].value;
	if (strPlugin.toLowerCase() != 'reCAPTCHA'.toLowerCase()) return '';
	return 'OIDplus::baseConfig()->setValue(\'RECAPTCHA_VERSION\', \\ViaThinkSoft\\OIDplus\\OIDplusCaptchaPluginRecaptcha::'+$("#recaptcha_version").find('option:selected').attr("name")+');<br>' +
	       'OIDplus::baseConfig()->setValue(\'RECAPTCHA_PUBLIC\',  ' + jsString($("#recaptcha_public")[0].value) + ');<br>' +
	       'OIDplus::baseConfig()->setValue(\'RECAPTCHA_PRIVATE\', ' + jsString($("#recaptcha_private")[0].value) + ');<br>';
});
