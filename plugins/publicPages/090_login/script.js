/*
 * OIDplus 2.0
 * Copyright 2019 Daniel Marschall, ViaThinkSoft
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

function raLogout(email) {
	if(!window.confirm("Are you sure that you want to logout?")) return false;

	$.ajax({
		url:"action.php",
		method:"POST",
		data: {
			action:"ra_logout",
			email:email,
		},
		success:function(data) {
			if (data != "OK") {
				alert("Error: " + data);
			} else {
				document.location = '?goto=oidplus:system';
				// reloadContent();
			}
		}
	});
}

function raLogin(email, password) {
	$.ajax({
		url:"action.php",
		method:"POST",
		data: {
			action:"ra_login",
			email:email,
			password:password,
			captcha: document.getElementsByClassName('g-recaptcha').length > 0 ? grecaptcha.getResponse() : null
		},
		success:function(data) {
			if (data != "OK") {
				alert("Error: " + data);
				grecaptcha.reset();
			} else {
				document.location = '?goto=oidplus:system';
				// reloadContent();
			}
		}
	});
}

function raLoginOnSubmit() {
	raLogin(document.getElementById("raLoginEMail").value, document.getElementById("raLoginPassword").value);
	return false;
}

/* Admin */

function adminLogin(password) {
	$.ajax({
		url:"action.php",
		method:"POST",
		data: {
			action:"admin_login",
			password:password,
			captcha: document.getElementsByClassName('g-recaptcha').length > 0 ? grecaptcha.getResponse() : null
		},
		success:function(data) {
			if (data != "OK") {
				alert("Error: " + data);
				grecaptcha.reset();
			} else {
				document.location = '?goto=oidplus:system';
				// reloadContent();
			}
		}
	});
}

function adminLogout() {
	if(!window.confirm("Are you sure that you want to logout?")) return false;

	$.ajax({
		url:"action.php",
		method:"POST",
		data: {
			action:"admin_logout",
		},
		success:function(data) {
			if (data != "OK") {
				alert("Error: " + data);
			} else {
				document.location = '?goto=oidplus:system';
				// reloadContent();
			}
		}
	});
}

function adminLoginOnSubmit() {
	adminLogin(document.getElementById("adminLoginPassword").value);
	return false;
}
