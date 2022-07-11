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

var OIDplusPagePublicWhois = {

	refresh_whois_url_bar: function() {
		$("#whois_url_bar_section")[0].style.display = "Block"; // because of NoScript

		var format = "";
		var radios = $("[name=format]");

		for (var i = 0, length = radios.length; i < length; i++) {
			if (radios[i].checked) {
				format = radios[i].value;
			}
		}

		$("#whois_url_bar")[0].innerHTML = getSystemUrl() + 'plugins/viathinksoft/publicPages/100_whois/whois/webwhois.php?format=' + format + '&query=' + encodeURIComponent($("#whois_query")[0].value);
	}

};
