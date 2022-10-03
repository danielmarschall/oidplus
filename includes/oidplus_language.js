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

function _L() {
	var args = Array.prototype.slice.call(arguments);
	var str = args.shift().trim();

	var tmp = "";
	if (typeof language_messages[getCurrentLang()] == 'undefined') { // do not translate
		tmp = str;
	} else {
		var msg = language_messages[getCurrentLang()][str];
		if (typeof msg != 'undefined') { // do not translate
			tmp = msg;
		} else {
			tmp = str;
		}
	}

	tmp = tmp.replace('###', language_tblprefix);

	var n = 1;
	while (args.length > 0) {
		var val = args.shift();
		tmp = tmp.replace("%"+n, val);
		n++;
	}

	tmp = tmp.replace("%%", "%");

	return tmp;
}

function getCurrentLang() {
	// Note: If the argument "?lang=" is used, PHP will automatically set a Cookie, so it is OK when we only check for the cookie
	var lang = getCookie('LANGUAGE'); // do not translate
	return (typeof lang != 'undefined') ? lang : DEFAULT_LANGUAGE; // do not translate
}

// Note: setLanguage() is defined in includes/oidplus_base.js, because only the
//       main application supports language changing via JavaScript. Setup just
//       reloads the page.

