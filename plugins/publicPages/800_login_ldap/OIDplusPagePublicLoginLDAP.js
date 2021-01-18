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

/* RA */

function raLoginLdap(email, password) {
	$.ajax({
		url:"ajax.php",
		method:"POST",
		data: {
			csrf_token:csrf_token,
			plugin:"1.3.6.1.4.1.37476.2.5.2.4.1.800",
			action:"ra_login_ldap",
			email:email,
			password:password,
			captcha: document.getElementsByClassName('g-recaptcha').length > 0 ? grecaptcha.getResponse() : null
		},
		error:function(jqXHR, textStatus, errorThrown) {
			alert(_L("Error: %1",errorThrown));
			if (document.getElementsByClassName('g-recaptcha').length > 0) grecaptcha.reset();
		},
		success:function(data) {
			if ("error" in data) {
				alert(_L("Error: %1",data.error));
				if (document.getElementsByClassName('g-recaptcha').length > 0) grecaptcha.reset();
			} else if (data.status >= 0) {
				window.location.href = '?goto=oidplus:system';
				// reloadContent();
			} else {
				alert(_L("Error: %1",data));
				if (document.getElementsByClassName('g-recaptcha').length > 0) grecaptcha.reset();
			}
		}
	});
}

function raLoginLdapOnSubmit() {
	raLoginLdap(document.getElementById("raLoginLdapEMail").value, document.getElementById("raLoginLdapPassword").value);
	return false;
}
