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
	$("#CAPTCHAPLUGIN_PARAMS_VtsClientChallenge")[0].style.display = (strPlugin == 'ViaThinkSoft Client Challenge') ? "Block" : "None";
});

rebuild_callbacks.push(function() {
	var e = $("#captcha_plugin")[0];
	var strPlugin = e.options[e.selectedIndex].value;
	if (strPlugin != 'ViaThinkSoft Client Challenge') return true;

	$("#recaptcha_public")[0].innerHTML = '';
	$("#recaptcha_private")[0].innerHTML = '';

	error = false;

	// Nothing to check here

	return !error;
});

captcha_rebuild_config_callbacks.push(function() {
	var e = $("#captcha_plugin")[0];
	var strPlugin = e.options[e.selectedIndex].value;
	if (strPlugin != 'ViaThinkSoft Client Challenge') return '';
	return '<br>';
});
