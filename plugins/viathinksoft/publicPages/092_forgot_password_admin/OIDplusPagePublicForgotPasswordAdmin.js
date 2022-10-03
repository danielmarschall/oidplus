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

// see also setup/includes/setup_base.js
min_password_length = 10;

// see also setup/includes/setup_base.js
var bCryptWorker = null;
var g_prevBcryptPw = null;
var g_last_admPwdHash = null;
var g_last_pwComment = null;

var OIDplusPagePublicForgotPasswordAdmin = {

	rehash_admin_pwd: function() {
		var error = "";

		if ($("#admin_password")[0].value.length == 0) {
			$("#config")[0].innerHTML = "";
			$("#copy_clipboard_button").hide();
			return;
		}

		if ($("#admin_password")[0].value.length < min_password_length) {
			error += _L("Password is too short. Need at least %1 characters",min_password_length)+"<br>";
		}

		if ($("#admin_password")[0].value != $("#admin_password2")[0].value) {
			error += _L("Passwords do not match")+"<br>";
		}

		if (error != "") {
			$("#config")[0].innerHTML = error;
			$("#copy_clipboard_button").hide();
		} else {
			var pw = $("#admin_password")[0].value;
			$("#copy_clipboard_button").show();

			if (pw != g_prevBcryptPw) {
				// sync call to calculate SHA3
				var admPwdHash = hexToBase64(sha3_512(pw))
				var pwComment = 'salted, base64 encoded SHA3-512 hash';
				$("#config")[0].innerHTML = 'OIDplus::baseConfig()->setValue(\'ADMIN_PASSWORD\',    \'' + admPwdHash + '\'); // '+pwComment+'<br>';
				g_last_admPwdHash = admPwdHash;
				g_last_pwComment = pwComment;

				// "async" call to calculate bcrypt (via web-worker)
				if (bCryptWorker != null) {
					g_prevBcryptPw = null;
					bCryptWorker.terminate();
				}
				bCryptWorker = new Worker('bcrypt_worker.js');
				var rounds = 10; // TODO: make configurable
				bCryptWorker.postMessage([pw, rounds]);
				bCryptWorker.onmessage = function (event) {
					var admPwdHash = event.data;
					var pwComment = 'bcrypt encoded hash';
					$("#config")[0].innerHTML = 'OIDplus::baseConfig()->setValue(\'ADMIN_PASSWORD\',    \'' + admPwdHash + '\'); // '+pwComment+'<br>';
					g_last_admPwdHash = admPwdHash;
					g_last_pwComment = pwComment;
					g_prevBcryptPw = pw;
				};
			} else {
				$("#config")[0].innerHTML = 'OIDplus::baseConfig()->setValue(\'ADMIN_PASSWORD\',    \'' + g_last_admPwdHash + '\'); // '+g_last_pwComment+'<br>';
			}
		}
	}

};
