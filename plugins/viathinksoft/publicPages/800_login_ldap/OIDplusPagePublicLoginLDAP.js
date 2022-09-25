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

var OIDplusPagePublicLoginLDAP = {

	oid: "1.3.6.1.4.1.37476.2.5.2.4.1.800",

	/* RA */

	raLoginLdap: function(email, password, remember_me) {
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
				plugin:OIDplusPagePublicLoginLDAP.oid,
				action:"ra_login_ldap",
				email:email,
				password:password,
				remember_me:remember_me?1:0,
				captcha: oidplus_captcha_response()
			},
			error:function(jqXHR, textStatus, errorThrown) {
				if (errorThrown == "abort") return;
				alertError(_L("Error: %1",errorThrown));
				oidplus_captcha_reset();
			},
			success:function(data) {
				if ("error" in data) {
					alertError(_L("Error: %1",data.error));
					oidplus_captcha_reset();
				} else if (data.status >= 0) {
					window.location.href = '?goto=oidplus%3Asystem';
					// reloadContent();
				} else {
					alertError(_L("Error: %1",data));
					oidplus_captcha_reset();
				}
			}
		});
	},

	raLoginLdapOnSubmit: function() {
		var remember_me = $("#remember_me_ldap").length == 0 ? false : $("#remember_me_ldap")[0].checked;
		OIDplusPagePublicLoginLDAP.raLoginLdap($("#raLoginLdapUsername").val() + $("#ldapUpnSuffix").val(), $("#raLoginLdapPassword").val(), remember_me);
		return false;
	}

};
