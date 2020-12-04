<?php

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

$url = 'https://www.viathinksoft.de/';

if (!function_exists("curl_init")) {
	echo '<p><font color="red">CURL not installed!</font></p>';
	die();
}

$ch = curl_init();
if (ini_get('curl.cainfo') == '') {
	if (file_exists(__DIR__.'/../3p/certs/cacert.pem')) {
		curl_setopt($ch, CURLOPT_CAINFO, __DIR__.'/../3p/certs/cacert.pem');
		echo '<p>Loaded fallback CURLOPT_CAINFO from OIDplus</p>';
	}
	else if (file_exists(__DIR__.'/3p/certs/cacert.pem')) {
		curl_setopt($ch, CURLOPT_CAINFO, __DIR__.'/3p/certs/cacert.pem');
		echo '<p>Loaded fallback CURLOPT_CAINFO from OIDplus</p>';
	} else {
		echo '<p><font color="red">curl.cainfo is missing and fallback certificates (3p/certs/cacert.pem) not found</font></p>';
	}
}
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HEADER, TRUE);
curl_setopt($ch, CURLOPT_NOBODY, TRUE); // remove body
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
$head = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode != 0) {
	echo '<p><font color="green">CURL to HTTPS works!</font></p>';
} else {
	echo '<p><font color="red">CURL to HTTPS does not work!</font></p>';
}
