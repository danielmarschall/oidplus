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

// DEFAULT_LANGUAGE will be set by setup.js.php
// language_messages will be set by setup.js.php
// language_tblprefix will be set by setup.js.php

// TODO: Put these settings in a "setup configuration file" (hardcoded)
min_password_length = 10; // see also plugins/viathinksoft/publicPages/092_forgot_password_admin/script.js
password_salt_length = 10;
bcrypt_rounds = 10;

function adminGeneratePassword(password) {
	var salt = generateRandomString(password_salt_length);
	return salt+'$'+hexToBase64(sha3_512(salt+password));
}

var bCryptWorker = null;
var g_prevBcryptPw = null;
var g_last_admPwdHash = null;
var g_last_pwComment = null;

function rebuild() {
	var pw = $("#admin_password")[0].value;

	if (pw != g_prevBcryptPw) {
		// sync call to calculate SHA3
		var admPwdHash = adminGeneratePassword(pw);
		var pwComment = 'salted, base64 encoded SHA3-512 hash';
		doRebuild(admPwdHash, pwComment);

		// "async" call to calculate bcrypt (via web-worker)
		if (bCryptWorker != null) {
			g_prevBcryptPw = null;
			bCryptWorker.terminate();
		}
		bCryptWorker = new Worker('../bcrypt_worker.js');
		bCryptWorker.postMessage([pw, bcrypt_rounds]);
		bCryptWorker.onmessage = function (event) {
			var admPwdHash = event.data;
			var pwComment = 'bcrypt encoded hash';
			doRebuild(admPwdHash, pwComment);
			g_prevBcryptPw = pw;
		};
	} else {
		doRebuild(g_last_admPwdHash, g_last_pwComment);
	}
}

function doRebuild(admPwdHash, pwComment) {
	g_last_admPwdHash = admPwdHash;
	g_last_pwComment = pwComment;

	var error = false;

	if ($("#config")[0] == null) return;

	// Check 1: Has the password the correct length?
	if ($("#admin_password")[0].value.length < min_password_length)
	{
		$("#password_warn")[0].innerHTML = '<font color="red">'+_L('Password must be at least %1 characters long',min_password_length)+'</font>';
		$("#config")[0].innerHTML = '<b>&lt?php</b><br><br><i>// ERROR: Password must be at least '+min_password_length+' characters long</i>'; // do not translate
		error = true;
	} else {
		$("#password_warn")[0].innerHTML = '';
	}

	// Check 2: Do the passwords match?
	if ($("#admin_password")[0].value != $("#admin_password2")[0].value) {
		$("#password_warn2")[0].innerHTML = '<font color="red">'+_L('The passwords do not match!')+'</font>';
		error = true;
	} else {
		$("#password_warn2")[0].innerHTML = '';
	}

	// Check 3: Ask the database or captcha plugins for verification of their data
	for (var i = 0; i < rebuild_callbacks.length; i++) {
		var f = rebuild_callbacks[i];
		if (!f()) {
			error = true;
		}
	}

	// Continue
	if (!error)
	{
		var e = $("#db_plugin")[0];
		var strDatabasePlugin = e.options[e.selectedIndex].value;
		var e = $("#captcha_plugin")[0];
		var strCaptchaPlugin = e.options[e.selectedIndex].value;

		$("#config")[0].innerHTML = '<b>&lt?php</b><br><br>' +
			'<i>// To renew this file, please run setup/ in your browser.</i><br>' + // do not translate
			'<i>// If you don\'t want to run setup again, you can also change most of the settings directly in this file.</i><br>' + // do not translate
			'<i>// List of possible values: doc/config_values.txt</i><br>' + // do not translate
			'<br>' +
			'use ViaThinkSoft\\OIDplus\\OIDplus;<br>' +
			'<br>' +
			'OIDplus::baseConfig()->setValue(\'CONFIG_VERSION\',    2.1);<br>' +
			'<br>' +
			// Passwords are Base64 encoded to avoid that passwords can be read upon first sight,
			// e.g. if collegues are looking over your shoulder while you accidently open (and quickly close) userdata/baseconfig/config.inc.php
			'OIDplus::baseConfig()->setValue(\'ADMIN_PASSWORD\',    \'' + admPwdHash + '\'); // '+pwComment+'<br>' +
			'<br>' +
			'OIDplus::baseConfig()->setValue(\'DATABASE_PLUGIN\',   \''+strDatabasePlugin+'\');<br>';
		for (var i = 0; i < rebuild_config_callbacks.length; i++) {
			var f = rebuild_config_callbacks[i];
			var cont = f();
			if (cont) {
				$("#config")[0].innerHTML = $("#config")[0].innerHTML + cont;
			}
		}
		$("#config")[0].innerHTML = $("#config")[0].innerHTML +
			//'<br>' +
			'OIDplus::baseConfig()->setValue(\'TABLENAME_PREFIX\',  \''+$("#tablename_prefix")[0].value+'\');<br>' +
			'<br>' +
			'OIDplus::baseConfig()->setValue(\'SERVER_SECRET\',     \''+generateRandomString(32)+'\');<br>' +
			'<br>' +
			'OIDplus::baseConfig()->setValue(\'CAPTCHA_PLUGIN\',    \''+strCaptchaPlugin+'\');<br>';
		for (var i = 0; i < captcha_rebuild_config_callbacks.length; i++) {
			var f = captcha_rebuild_config_callbacks[i];
			var cont = f();
			if (cont) {
				$("#config")[0].innerHTML = $("#config")[0].innerHTML + cont;
			}
		}

		$("#config")[0].innerHTML = $("#config")[0].innerHTML +
			'<br>' +
			'OIDplus::baseConfig()->setValue(\'ENFORCE_SSL\',       '+$("#enforce_ssl")[0].value+');<br>';

		if ($("#canonical_url")[0].value.trim() != '') {
			$("#config")[0].innerHTML = $("#config")[0].innerHTML +
				'<br>' +
				'OIDplus::baseConfig()->setValue(\'CANONICAL_SYSTEM_URL\', \''+$("#canonical_url")[0].value.trim()+'\');<br>';
		}

		$("#config")[0].innerHTML = $("#config")[0].innerHTML.replaceAll(' ', '&nbsp;');
	}

	// In case something is not good, do not allow the user to continue with the other configuration steps:
	if (error) {
		$("#step2")[0].style.display = "None";
		$("#step3")[0].style.display = "None";
		$("#step4")[0].style.display = "None";
	} else {
		$("#step2")[0].style.display = "Block";
		$("#step3")[0].style.display = "Block";
		$("#step4")[0].style.display = "Block";
	}
}

function checkAccess(dir) {
	if (!dir.toLowerCase().startsWith('https:') && !dir.toLowerCase().startsWith('http:')) {
		var url = '../' + dir;
		var visibleUrl = RemoveLastDirectoryPartOf(window.location.href) + '/' + dir; // xhr.responseURL not available in IE
	} else {
		var url = dir;
		var visibleUrl = dir; // xhr.responseURL not available in IE
	}

	var xhr = new XMLHttpRequest();
	xhr.onreadystatechange = function() {
		if (xhr.readyState === 4) {
			if (xhr.status === 200) {
				$("#systemCheckCaption")[0].style.display = 'block';
				// TODO: At my Ubuntu VM, after fixing an issue with Apache2, the messages still appeared. I had to click the links and press F5 for each one
				//       THERE MUST BE NO CACHE!!!
				$("#dirAccessWarning")[0].innerHTML = $("#dirAccessWarning")[0].innerHTML + _L('Attention: The following directory is world-readable: %1 ! You need to configure your web server to restrict access to this directory! (For Apache see <i>.htaccess</i>, for Microsoft IIS see <i>web.config</i>, for Nginx see <i>nginx.conf</i>).','<a target="_blank" href="'+url+'">'+visibleUrl+'</a>') + '<br>';
			}
		}
	};

	xhr.open('GET', url);
	xhr.send();
}

function dbplugin_changed() {
	var e = $("#db_plugin")[0];
	var strDatabasePlugin = e.options[e.selectedIndex].value;

	for (var i = 0; i < plugin_combobox_change_callbacks.length; i++) {
		var f = plugin_combobox_change_callbacks[i];
		f(strDatabasePlugin);
	}

	rebuild();
}

function captchaplugin_changed() {
	var e = $("#captcha_plugin")[0];
	var strCaptchaPlugin = e.options[e.selectedIndex].value;

	for (var i = 0; i < captcha_plugin_combobox_change_callbacks.length; i++) {
		var f = captcha_plugin_combobox_change_callbacks[i];
		f(strCaptchaPlugin);
	}

	rebuild();
}

function performAccessCheck() {
	$("#dirAccessWarning")[0].innerHTML = "";
	checkAccess("userdata/index.html");
	checkAccess("res/ATTENTION.TXT");
	checkAccess("dev/index.html");
	checkAccess("includes/index.html");
	checkAccess("setup/includes/index.html");
	//checkAccess("plugins/viathinksoft/publicPages/100_whois/whois/cli/index.html");

	if (window.location.href.toLowerCase().startsWith('https://')) {
		$("#enforce_ssl").val('OIDplus::ENFORCE_SSL_YES'); // enforce SSL (because we are already SSL)
	} else {
		// Do a SSL detection now.
		// This is important because on XAMPP the SSL cert is invalid (self signed) and the user might
		// be very confused if the PHP detection (OIDplus::ENFORCE_SSL_AUTO) notices the open 443 port and redirects the user to a
		// big warning page of the browser!
		var xhr = new XMLHttpRequest();
		xhr.onreadystatechange = function() {
			if (xhr.readyState === 4) {
				if (xhr.status === 200) {
					$("#enforce_ssl").val('OIDplus::ENFORCE_SSL_YES'); // enforce SSL (we checked that it loads correctly)
				} else {
					console.log("JS SSL detection result: "+xhr.status);
					$("#enforce_ssl").val('OIDplus::ENFORCE_SSL_NO'); // disable SSL (because it failed, e.g. because of invalid cert or closed port)
				}
			}
		};
		var https_url = window.location.href.replace(/^http:/i, "https:");
		xhr.open('GET', https_url);
		xhr.send();
	}
}

function setupOnLoad() {
	rebuild();
	dbplugin_changed();
	captchaplugin_changed();
	performAccessCheck();
}

window.onload = setupOnLoad;
