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

function raLogout(email) {
	if(!window.confirm(_L("Are you sure that you want to logout?"))) return false;

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
			plugin:"1.3.6.1.4.1.37476.2.5.2.4.1.90",
			action:"ra_logout",
			email:email,
		},
		error:function(jqXHR, textStatus, errorThrown) {
			if (errorThrown == "abort") return;
			alert(_L("Error: %1",errorThrown));
		},
		success:function(data) {
			if ("error" in data) {
				alert(_L("Error: %1",data.error));
			} else if (data.status >= 0) {
				window.location.href = '?goto=oidplus:system';
				// reloadContent();
			} else {
				alert(_L("Error: %1",data));
			}
		}
	});
}

function raLogin(email, password) {
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
			plugin:"1.3.6.1.4.1.37476.2.5.2.4.1.90",
			action:"ra_login",
			email:email,
			password:password,
			captcha: document.getElementsByClassName('g-recaptcha').length > 0 ? grecaptcha.getResponse() : null
		},
		error:function(jqXHR, textStatus, errorThrown) {
			if (errorThrown == "abort") return;
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

function raLoginOnSubmit() {
	raLogin(document.getElementById("raLoginEMail").value, document.getElementById("raLoginPassword").value);
	return false;
}

/* Admin */

function adminLogin(password) {
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
			plugin:"1.3.6.1.4.1.37476.2.5.2.4.1.90",
			action:"admin_login",
			password:password,
			captcha: document.getElementsByClassName('g-recaptcha').length > 0 ? grecaptcha.getResponse() : null
		},
		error:function(jqXHR, textStatus, errorThrown) {
			if (errorThrown == "abort") return;
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

function adminLogout() {
	if(!window.confirm(_L("Are you sure that you want to logout?"))) return false;

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
			plugin:"1.3.6.1.4.1.37476.2.5.2.4.1.90",
			action:"admin_logout",
		},
		error:function(jqXHR, textStatus, errorThrown) {
			if (errorThrown == "abort") return;
			alert(_L("Error: %1",errorThrown));
		},
		success:function(data) {
			if ("error" in data) {
				alert(_L("Error: %1",data.error));
			} else if (data.status >= 0) {
				window.location.href = '?goto=oidplus:system';
				// reloadContent();
			} else {
				alert(_L("Error: %1",data));
			}
		}
	});
}

function adminLoginOnSubmit() {
	adminLogin(document.getElementById("adminLoginPassword").value);
	return false;
}
