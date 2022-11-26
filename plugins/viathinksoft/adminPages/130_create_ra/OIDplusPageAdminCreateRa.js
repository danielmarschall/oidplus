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

var OIDplusPageAdminCreateRa = {

	oid: "1.3.6.1.4.1.37476.2.5.2.4.3.130",

	adminCreateRaFormOnSubmit: function() {
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
				plugin: OIDplusPageAdminCreateRa.oid,
				action: "create_ra",
				email: $("#email").val(),
				password1: $("#password1").val(),
				password2: $("#password2").val()
			},
			error: oidplus_ajax_error,
			success: function (data) {
				oidplus_ajax_success(data, function (data) {
					alertSuccess(_L("Account created"));
					//openOidInPanel('oidplus:rainfo$'+$("#email").val(),true);
					// We need to reload the whole page, because the tree at the left contains a "List RA" list with the RAs
					window.location.href = '?goto=' + encodeURIComponent('oidplus:rainfo$' + $("#email").val());
				});
			}
		});
		return false;
	}

};
