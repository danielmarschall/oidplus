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

var OIDplusPageAdminAutomatedAJAXCalls = {

	oid: "1.3.6.1.4.1.37476.2.5.2.4.3.910",

	blacklistJWT: function() {
		$.ajax({
			url:"ajax.php",
			method:"POST",
			beforeSend: function(jqXHR, settings) {
				$.xhrPool.abortAll();
				$.xhrPool.add(jqXHR);
			},
			complete: function(jqXHR, text) {
				$.xhrPool.remove(jqXHR);
			},
			data: {
				csrf_token:csrf_token,
				plugin:OIDplusPageAdminAutomatedAJAXCalls.oid,
				action:"blacklistJWT"
			},
			error:function(jqXHR, textStatus, errorThrown) {
				if (errorThrown == "abort") return;
				alertError(_L("Error: %1",errorThrown));
			},
			success:function(data) {
				if ("error" in data) {
					alertError(_L("Error: %1",data.error));
				} else if (data.status >= 0) {
					alertSuccess(_L("OK"));
					reloadContent();
				} else {
					alertError(_L("Error: %1",data));
				}
			}
		});
	}

};
