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

var OIDplusPageRaInvite = {

	oid: "1.3.6.1.4.1.37476.2.5.2.4.2.92",

	inviteFormOnSubmit: function() {
		$.ajax({
			url: "ajax.php",
			type: "POST",
			beforeSend: function(jqXHR, settings) {
				$.xhrPool.abortAll();
				$.xhrPool.add(jqXHR);
			},
			complete: function(jqXHR, text) {
				$.xhrPool.remove(jqXHR);
			},
			data: {
				csrf_token: csrf_token,
				plugin: OIDplusPageRaInvite.oid,
				action: "invite_ra",
				email: $("#email").val(),
				captcha: oidplus_captcha_response()
			},
			error: function (jqXHR, textStatus, errorThrown) {
				oidplus_ajax_error(jqXHR, textStatus, errorThrown);
				oidplus_captcha_reset();
			},
			success: function (data) {
				var ok = false;
				oidplus_ajax_success(data, function (data) {
					alertSuccess(_L("The RA has been invited via email."));
					window.location.href = '?goto=' + $("#origin").val();
					//reloadContent();
					ok = true;
				});
				if (!ok) oidplus_captcha_reset();
			}
		});
		return false;
	},

	activateRaFormOnSubmit: function() {
		$.ajax({
			url: "ajax.php",
			type: "POST",
			beforeSend: function(jqXHR, settings) {
				$.xhrPool.abortAll();
				$.xhrPool.add(jqXHR);
			},
			complete: function(jqXHR, text) {
				$.xhrPool.remove(jqXHR);
			},
			data: {
				csrf_token:csrf_token,
				plugin: OIDplusPageRaInvite.oid,
				action: "activate_ra",
				email: $("#email").val(),
				auth: $("#auth").val(),
				password1: $("#password1").val(),
				password2: $("#password2").val(),
				timestamp: $("#timestamp").val()
			},
			error: oidplus_ajax_error,
			success: function (data) {
				oidplus_ajax_success(data, function (data) {
					alertSuccess(_L("Registration successful! You can now log in."));
					window.location.href = '?goto=oidplus%3Alogin';
					//reloadContent();
				});
			}
		});
		return false;
	}

};
