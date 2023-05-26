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
		var query = "";
		// -------------------------------------------------------------
		var obj = $("#whois_query")[0].value.trim();
		var tmp = obj.split(":");
		var ns = tmp.shift();
		var id = tmp.join(":");
		if (!/^[a-z0-9]+$/.test(ns) || id.includes("$")) {
			$("#whois_query_invalid")[0].style.display = "Inline";
		} else {
			$("#whois_query_invalid")[0].style.display = "None";
		}
		query = ns + ":" + id;
		// -------------------------------------------------------------
		var format = "text";
		var radios = $("[name=format]");
		for (var i = 0, length = radios.length; i < length; i++) {
			if (radios[i].checked) {
				format = radios[i].value;
			}
		}
		if (format != "text") query += "$format="+format;
		// -------------------------------------------------------------
		var auth = $("#whois_auth")[0].value;
		var invalid_tokens = false;
		if (auth != "") {
			var tokens = auth.split(",");
			for (var i=0; i<tokens.length; i++) {
				var token = tokens[i];
				if ((token == "") || token.includes("$") || token.includes("=")) {
					invalid_tokens = true;
					break;
				}
				tokens[i] = token.trim();
			}
			auth = tokens.join(",");
		}
		if (invalid_tokens) {
			$("#whois_auth_invalid")[0].style.display = "Inline";
			auth = "";
		} else {
			$("#whois_auth_invalid")[0].style.display = "None";
		}
		if (auth != "") query += "$auth="+auth;
		// -------------------------------------------------------------
		$("#whois_url_bar")[0].innerText = getSystemUrl(false) + 'plugins/viathinksoft/publicPages/100_whois/whois/webwhois.php?query=' + encodeURIComponent(query);
		$("#whois_query_bar")[0].innerText = query;
	}

};
