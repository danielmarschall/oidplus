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

min_password_length = 10; // see also setup/setup.js

function hexToBase64(str) {
	return btoa(String.fromCharCode.apply(null,
	            str.replace(/\r|\n/g, "").replace(/([\da-fA-F]{2}) ?/g, "0x$1 ").replace(/ +$/, "").split(" ")));
}

function rehash_admin_pwd() {
	var error = "";

	if (document.getElementById('admin_password').value.length == 0) {
		document.getElementById('config').innerHTML = "";
		return;
	}

	if (document.getElementById('admin_password').value.length < min_password_length) {
		error += _L("Password is too short. Need at least %1 characters",min_password_length)+"<br>";
	}

	if (document.getElementById('admin_password').value != document.getElementById('admin_password2').value) {
		error += _L("Passwords do not match")+"<br>";
	}

	if (error != "") {
		document.getElementById('config').innerHTML = error;
	} else {
		document.getElementById('config').innerHTML = 'OIDplus::baseConfig()->setValue(\'ADMIN_PASSWORD\',    \'' + hexToBase64(sha3_512(document.getElementById('admin_password').value)) + '\'); // base64 encoded SHA3-512 hash<br>';
	}
}