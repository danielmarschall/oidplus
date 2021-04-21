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

var OIDplusPageRaChangeEMail = {

	changeRaEmailFormOnSubmit: function(isadmin) {
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
				plugin:"1.3.6.1.4.1.37476.2.5.2.4.2.102",
				action: "change_ra_email",
				old_email: $("#old_email").val(),
				new_email: $("#new_email").val(),
			},
			error:function(jqXHR, textStatus, errorThrown) {
				if (errorThrown == "abort") return;
				alert(_L("Error: %1",errorThrown));
			},
			success: function(data) {
				if ("error" in data) {
					alert(_L("Error: %1",data.error));
				} else if (data.status >= 0) {
					if (isadmin) {
						alert(_L("eMail address of RA changed"));
						//openOidInPanel('oidplus:rainfo$'+$("#new_email").val(),true);
						// We need to reload the whole page, because the tree at the left contains a "List RA" list with the RAs
						window.location.href = '?goto='+encodeURIComponent('oidplus:rainfo$'+$("#new_email").val());
					} else {
						alert(_L("Verification eMail sent"));
						//window.location.href = '?goto=oidplus:system';
						//reloadContent();
					}
				} else {
					alert(_L("Error: %1",data));
				}
			}
		});
		return false;
	},

	activateNewRaEmailFormOnSubmit: function() {
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
				plugin:"1.3.6.1.4.1.37476.2.5.2.4.2.102",
				action: "activate_new_ra_email",
				password: $("#password").val(),
				old_email: $("#old_email").val(),
				new_email: $("#new_email").val(),
				auth: $("#auth").val(),
				timestamp: $("#timestamp").val()
			},
			error:function(jqXHR, textStatus, errorThrown) {
				if (errorThrown == "abort") return;
				alert(_L("Error: %1",errorThrown));
			},
			success: function(data) {
				if ("error" in data) {
					alert(_L("Error: %1",data.error));
				} else if (data.status >= 0) {
					alert(_L("Done"));
					window.location.href = '?goto=oidplus:system';
					//reloadContent();
				} else {
					alert(_L("Error: %1",data));
				}
			}
		});
		return false;
	}

};