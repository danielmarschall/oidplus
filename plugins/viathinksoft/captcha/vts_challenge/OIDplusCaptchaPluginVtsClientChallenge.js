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

		var vts_validation_result = null;
		console.log("VTS Challenge: Calculating solution...");
		for (i=min; i<=max; i++) {
			if (challenge == sha3_512(starttime+"/"+ip_target+"/"+i)) {
				var answer = i;
				vts_validation_result = JSON.stringify([starttime, ip_target, challenge, answer, challenge_integrity]);
				break;
			}
		}

		if (vts_validation_result == null) {
			// If this happens, something is VERY wrong
			console.log("VTS Challenge: Done (FAILED)");
			console.log(data);
			var answer = max+1; // "something invalid"
			vts_validation_result = JSON.stringify([starttime, ip_target, challenge, answer, challenge_integrity]);
		} else {
			console.log("VTS Challenge: Done (SOLVED)");
		}

		return vts_validation_result;
	},

	captchaReset: function(dir) {
		show_waiting_anim(); // we need to block the UI, otherwise we cannot solve challenge later
		OIDplusCaptchaPluginVtsClientChallenge.currentchallenge = [null,null,null,null,null,null];
		OIDplusCaptchaPluginVtsClientChallenge.currentresponse = null;
		console.log("VTS Challenge: Loading Challenge...");
		$.ajax({
			url:dir+"ajax.php",
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
				hide_waiting_anim();
				if (errorThrown == "abort") return;
				alertError(_L("Error: %1",errorThrown));
			},
			success:function(data) {
				if ("error" in data) {
					hide_waiting_anim();
					alertError(_L("Error: %1",data.error));
				} else if (data.status >= 0) {
					OIDplusCaptchaPluginVtsClientChallenge.currentchallenge = data.challenge;
					OIDplusCaptchaPluginVtsClientChallenge.currentresponse = null;
					console.log("VTS Challenge: Loading of challenge complete");
					if (data.autosolve) {
						// That's ok, because the GUI is locked anyway
						OIDplusCaptchaPluginVtsClientChallenge.currentresponse = OIDplusCaptchaPluginVtsClientChallenge.captchaResponse();
					}
					hide_waiting_anim();
				} else {
					hide_waiting_anim();
					alertError(_L("Error: %1",data));
				}
			}
		});

	},

	captchaShow: function(dir) {
		/*var*/ oidplus_captcha_response = function() {
			if (OIDplusCaptchaPluginVtsClientChallenge.currentchallenge[0] == null) {
				// Should not happen, because we are using a loading animation durnig the AJAX "get_challenge" request
				console.error("VTS Challenge: Loading of the challenge is not yet completed! Cannot get the CAPTCHA response!");
			}
			if (!OIDplusCaptchaPluginVtsClientChallenge.currentresponse) {
				// if the user is too fast (or there was no auto-solve), then we will calculate it now
				OIDplusCaptchaPluginVtsClientChallenge.currentresponse = OIDplusCaptchaPluginVtsClientChallenge.captchaResponse();
			}
			return OIDplusCaptchaPluginVtsClientChallenge.currentresponse;
		};
		/*var*/ oidplus_captcha_reset = function() {
			return OIDplusCaptchaPluginVtsClientChallenge.captchaReset(dir);
		};
		oidplus_captcha_reset();
		$("form").submit(function(e){
			$("#vts_validation_result").val(oidplus_captcha_response());
		});
	}

};
