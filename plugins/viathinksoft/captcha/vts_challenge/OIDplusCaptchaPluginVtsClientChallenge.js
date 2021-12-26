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

var OIDplusCaptchaPluginVtsClientChallenge = {

	oid: "1.3.6.1.4.1.37476.2.5.2.4.11.3",

	currentchallenge: [null,null,null,null,null,null],
	currentresponse: null,

	captchaResponse: function() {
		var data = OIDplusCaptchaPluginVtsClientChallenge.currentchallenge;
		var starttime = data[0];
		var ip_target = data[1];
		var challenge = data[2];
		var min = data[3];
		var max = data[4];
		var challenge_integrity = data[5];

		//$("#loading").show();
		// Without setTimeout, the load/hide of the spinner won't be visible?!
		setTimeout(function() {
			// Note: #loading not defined in oobe.php
			$("#loading").show();
		},1);

		var vts_validation_result = null;
		console.log("start VTS challenge");
		for (i=min; i<=max; i++) {
			if (challenge == sha3_512(starttime+"/"+ip_target+"/"+i)) {
				var answer = i;
				vts_validation_result = JSON.stringify([starttime, ip_target, challenge, answer, challenge_integrity]);
				break;
			}
		}
		console.log("end VTS challenge");

		//$("#loading").hide();
		// Without setTimeout, the load/hide of the spinner won't be visible?!
		setTimeout(function() {
			// Note: #loading not defined in oobe.php
			$("#loading").hide();
		},100);

		return vts_validation_result;
	},

	captchaReset: function(autosolve) {
		$.ajax({
			url:"../../../../ajax.php",
			method:"POST",
			//beforeSend: function(jqXHR, settings) {
			//	$.xhrPool.abortAll();
			//	$.xhrPool.add(jqXHR);
			//},
			//complete: function(jqXHR, text) {
			//	$.xhrPool.remove(jqXHR);
			//},
			data: {
			//	csrf_token:csrf_token,
				plugin:OIDplusCaptchaPluginVtsClientChallenge.oid,
				action:"get_challenge"
			},
			error:function(jqXHR, textStatus, errorThrown) {
				if (errorThrown == "abort") return;
				alert("Error: "+errorThrown); //alert(_L("Error: %1",errorThrown));
			},
			success:function(data) {
				if ("error" in data) {
					alert("Error: "+data.error); //alert(_L("Error: %1",data.error));
				} else if (data.status >= 0) {
					OIDplusCaptchaPluginVtsClientChallenge.currentchallenge = data.challenge;
					OIDplusCaptchaPluginVtsClientChallenge.currentresponse = null;
					if (autosolve) {
						// TODO: Solve using a JS Worker, so that the User UI is not slowed down... Then we also don't need the #loading spinner
						OIDplusCaptchaPluginVtsClientChallenge.currentresponse = OIDplusCaptchaPluginVtsClientChallenge.captchaResponse();
					}
				} else {
					alert("Error: "+data); //alert(_L("Error: %1",data));
				}
			}
		});

	}

}
