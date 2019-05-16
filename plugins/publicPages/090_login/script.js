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
		url:"ajax.php",
		method:"POST",
		data: {
			action:"ra_logout",
			email:email,
		},
		error:function(jqXHR, textStatus, errorThrown) {
			alert("Error: " + errorThrown);
		},
		success:function(data) {
			if ("error" in data) {
				alert("Error: " + data.error);
			} else if (data.status == 0) {
				document.location = '?goto=oidplus:system';
				// reloadContent();
			} else {
				alert("Error: " + data);
			}
		}
	});
}

function raLogin(email, password) {
	$.ajax({
		url:"ajax.php",
		method:"POST",
		data: {
			action:"ra_login",
			email:email,
			password:password,
			captcha: document.getElementsByClassName('g-recaptcha').length > 0 ? grecaptcha.getResponse() : null
		},
		error:function(jqXHR, textStatus, errorThrown) {
			alert("Error: " + errorThrown);
			if (document.getElementsByClassName('g-recaptcha').length > 0) grecaptcha.reset();
		},
		success:function(data) {
			if ("error" in data) {
				alert("Error: " + data.error);
				if (document.getElementsByClassName('g-recaptcha').length > 0) grecaptcha.reset();
			} else if (data.status == 0) {
				document.location = '?goto=oidplus:system';
				// reloadContent();
			} else {
				alert("Error: " + data);
				if (document.getElementsByClassName('g-recaptcha').length > 0) grecaptcha.reset();
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
		url:"ajax.php",
		method:"POST",
		data: {
			action:"admin_login",
			password:password,
			captcha: document.getElementsByClassName('g-recaptcha').length > 0 ? grecaptcha.getResponse() : null
		},
		error:function(jqXHR, textStatus, errorThrown) {
			alert("Error: " + errorThrown);
			if (document.getElementsByClassName('g-recaptcha').length > 0) grecaptcha.reset();
		},
		success:function(data) {
			if ("error" in data) {
				alert("Error: " + data.error);
				if (document.getElementsByClassName('g-recaptcha').length > 0) grecaptcha.reset();
			} else if (data.status == 0) {
				document.location = '?goto=oidplus:system';
				// reloadContent();
			} else {
				alert("Error: " + data);
				if (document.getElementsByClassName('g-recaptcha').length > 0) grecaptcha.reset();
			}
		}
	});
}

function adminLogout() {
	if(!window.confirm("Are you sure that you want to logout?")) return false;

	$.ajax({
		url:"ajax.php",
		method:"POST",
		data: {
			action:"admin_logout",
		},
		error:function(jqXHR, textStatus, errorThrown) {
			alert("Error: " + errorThrown);
		},
		success:function(data) {
			if ("error" in data) {
				alert("Error: " + data.error);
			} else if (data.status == 0) {
				document.location = '?goto=oidplus:system';
				// reloadContent();
			} else {
				alert("Error: " + data);
			}
		}
	});
}

function adminLoginOnSubmit() {
	adminLogin(document.getElementById("adminLoginPassword").value);
	return false;
}
