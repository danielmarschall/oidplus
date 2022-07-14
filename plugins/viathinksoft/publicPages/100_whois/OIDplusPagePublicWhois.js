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

	openInBrowser: function() {
		var url = $("#whois_url_bar")[0].innerText;
		//document.location.href = url;
		window.open(url);
	},

	refresh_whois_url_bar: function() {
		var format = "text";
		var radios = $("[name=format]");

		for (var i = 0, length = radios.length; i < length; i++) {
			if (radios[i].checked) {
				format = radios[i].value;
			}
		}

		var query = $("#whois_query")[0].value;
		if (format != "text") query += "$format="+format;

		var auth = $("#whois_auth")[0].value;
		if (auth != "") query += "$auth="+auth;

		$("#whois_url_bar")[0].innerHTML = getSystemUrl() + 'plugins/viathinksoft/publicPages/100_whois/whois/webwhois.php?query=' + encodeURIComponent(query);
		$("#whois_query_bar")[0].innerHTML = query;
	}

};
