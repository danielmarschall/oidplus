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

function btoa(bin) {
	var tableStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
	var table = tableStr.split("");
	for (var i = 0, j = 0, len = bin.length / 3, base64 = []; i < len; ++i) {
		var a = bin.charCodeAt(j++), b = bin.charCodeAt(j++), c = bin.charCodeAt(j++);
		if ((a | b | c) > 255) throw new Error("String contains an invalid character");
		base64[base64.length] = table[a >> 2] + table[((a << 4) & 63) | (b >> 4)] +
		                       (isNaN(b) ? "=" : table[((b << 2) & 63) | (c >> 6)]) +
		                       (isNaN(b + c) ? "=" : table[c & 63]);
	}
	return base64.join("");
};

function hexToBase64(str) {
	return btoa(String.fromCharCode.apply(null,
	            str.replace(/\r|\n/g, "").replace(/([\da-fA-F]{2}) ?/g, "0x$1 ").replace(/ +$/, "").split(" ")));
}

function b64EncodeUnicode(str) {
	// first we use encodeURIComponent to get percent-encoded UTF-8,
	// then we convert the percent encodings into raw bytes which
	// can be fed into btoa.
	return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g,
	function toSolidBytes(match, p1) {
		return String.fromCharCode('0x' + p1);
	}));
}

function generateRandomString(length) {
	var charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789",
	retVal = "";
	for (var i = 0, n = charset.length; i < length; ++i) {
		retVal += charset.charAt(Math.floor(Math.random() * n));
	}
	return retVal;
}

String.prototype.replaceAll = function(search, replacement) {
	var target = this;
	return target.replace(new RegExp(search, 'g'), replacement);
};

min_password_length = 5;

function rebuild() {
	var error = false;

	// Check 1: Do the passwords match?
	if (document.getElementById('admin_password').value != document.getElementById('admin_password2').value) {
		document.getElementById('password_warn2').innerHTML = '<font color="red">The passwords do not match!</font>';
		error = true;
	} else {
		document.getElementById('password_warn2').innerHTML = '';
	}

	// Check 2: Has the password the correct length?
	if (document.getElementById('admin_password').value.length < min_password_length)
	{
		document.getElementById('password_warn').innerHTML = '<font color="red">Password must be at least '+min_password_length+' characters long</font>';
		document.getElementById('config').innerHTML = '<b>&lt?php</b><br><br><i>// ERROR: Password must be at least '+min_password_length+' characters long</i>';
		error = true;
	}
	else
	{
		document.getElementById('password_warn').innerHTML = '';
		document.getElementById('config').innerHTML = '<b>&lt?php</b><br><br>' +
			'<i>// To renew this file, please run setup/ in your browser.</i><br>' +
			'<i>// If you don\'t want to run setup again, you can also change most of the settings directly in this file.</i><br>' +
			'<br>' +
			'<b>define</b>(\'OIDPLUS_CONFIG_VERSION\',   0.1);<br>' +
			'<br>' +
			'<b>define</b>(\'OIDPLUS_ADMIN_PASSWORD\',   \'' + hexToBase64(sha3_512(document.getElementById('admin_password').value)) + '\'); // base64 encoded SHA3-512 hash<br>' +
			'<b>define</b>(\'OIDPLUS_ADMIN_EMAIL\',      \'' + document.getElementById('admin_email').value + '\');<br>' +
			'<br>' +
			'<b>define</b>(\'OIDPLUS_MYSQL_HOST\',       \''+document.getElementById('mysql_host').value+'\');<br>' +
			'<b>define</b>(\'OIDPLUS_MYSQL_USERNAME\',   \''+document.getElementById('mysql_username').value+'\');<br>' +
			'<b>define</b>(\'OIDPLUS_MYSQL_PASSWORD\',   \''+b64EncodeUnicode(document.getElementById('mysql_password').value)+'\'); // base64 encoded<br>' +
			'<b>define</b>(\'OIDPLUS_MYSQL_DATABASE\',   \''+document.getElementById('mysql_database').value+'\');<br>' +
			'<br>' +
			'<b>define</b>(\'OIDPLUS_TABLENAME_PREFIX\', \''+document.getElementById('tablename_prefix').value+'\');<br>' +
			'<br>' +
			'<b>define</b>(\'OIDPLUS_SESSION_SECRET\',   \''+generateRandomString(32)+'\');<br>' +
			'<br>' +
			'<b>define</b>(\'RECAPTCHA_ENABLED\',        '+(document.getElementById('recaptcha_enabled').checked ? 1 : 0)+');<br>' +
			'<b>define</b>(\'RECAPTCHA_PUBLIC\',         \''+document.getElementById('recaptcha_public').value+'\');<br>' +
			'<b>define</b>(\'RECAPTCHA_PRIVATE\',        \''+document.getElementById('recaptcha_private').value+'\');<br>';

		document.getElementById('config').innerHTML = document.getElementById('config').innerHTML.replaceAll(' ', '&nbsp;');
	}

	// In case something is not good, do not allow the user to continue with the other configuration steps:
	if (error) {
		document.getElementById('step2').style.visibility='hidden';
		document.getElementById('step3').style.visibility='hidden';
		document.getElementById('step4').style.visibility='hidden';
	} else {
		document.getElementById('step2').style.visibility='visible';
		document.getElementById('step3').style.visibility='visible';
		document.getElementById('step4').style.visibility='visible';
	}

	if (document.getElementById('tablename_prefix').value == '') {
		document.getElementById('struct_1').href = 'struct_empty.sql.php';
		document.getElementById('struct_2').href = 'struct_with_examples.sql.php';
	} else {
		document.getElementById('struct_1').href = 'struct_empty.sql.php?prefix='+encodeURI(document.getElementById('tablename_prefix').value);
		document.getElementById('struct_2').href = 'struct_with_examples.sql.php?prefix='+encodeURI(document.getElementById('tablename_prefix').value);
	}
}
