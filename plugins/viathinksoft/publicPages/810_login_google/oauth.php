<?php

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

# More information about the OAuth2 implementation:
# - https://developers.google.com/identity/protocols/oauth2/openid-connect

require_once __DIR__ . '/../../../../includes/oidplus.inc.php';

OIDplus::init(true);
set_exception_handler(array('OIDplusGui', 'html_exception_handler'));

if (OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_OIDplusPagePublicLoginGoogle', false)) {
	throw new OIDplusException(_L('This plugin was disabled by the system administrator!'));
}

if (!OIDplus::baseConfig()->getValue('GOOGLE_OAUTH2_ENABLED', false)) {
	throw new OIDplusException(_L('Google OAuth authentication is disabled on this system.'));
}

_CheckParamExists($_GET, 'code');
_CheckParamExists($_GET, 'state');
_CheckParamExists($_COOKIE, 'csrf_token_weak');

if ($_GET['state'] != $_COOKIE['csrf_token_weak']) {
	die(_L('Missing or wrong CSRF Token'));
}

if (!function_exists('curl_init')) {
	die(_L('The "%1" PHP extension is not installed at your system. Please enable the PHP extension <code>%2</code>.','CURL','php_curl'));
}

$ch = curl_init();
if (ini_get('curl.cainfo') == '') curl_setopt($ch, CURLOPT_CAINFO, OIDplus::localpath() . 'vendor/cacert.pem');
curl_setopt($ch, CURLOPT_URL,"https://oauth2.googleapis.com/token");
curl_setopt($ch, CURLOPT_USERAGENT, 'ViaThinkSoft-OIDplus/2.0');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS,
	"grant_type=authorization_code&".
	"code=".urlencode($_GET['code'])."&".
	"redirect_uri=".urlencode(OIDplus::webpath(__DIR__,OIDplus::PATH_ABSOLUTE_CANONICAL).'oauth.php')."&".
	"client_id=".urlencode(OIDplus::baseConfig()->getValue('GOOGLE_OAUTH2_CLIENT_ID'))."&".
	"client_secret=".urlencode(OIDplus::baseConfig()->getValue('GOOGLE_OAUTH2_CLIENT_SECRET'))
);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$cont = curl_exec($ch);
curl_close($ch);

// Get ID token and Access token
$data = json_decode($cont,true);
if (isset($data['error'])) {
	throw new OIDplusException(_L('Error receiving the authentication token from %1: %2','Google',$data['error'].' '.$data['error_description']));
}
$id_token = $data['id_token'];
$access_token = $data['access_token'];

try {

	// Decode JWT "id_token"
	// see https://medium.com/@darutk/understanding-id-token-5f83f50fa02e
	// Note: We do not need to verify the signature because the token comes directly from Google,
	//       but we do it anyway. Just to be sure!
	$verification_certs = json_decode(url_get_contents('https://www.googleapis.com/oauth2/v1/certs'), true);
	\Firebase\JWT\JWT::$leeway = 60; // leeway in seconds
	$data = (array) \Firebase\JWT\JWT::decode($id_token, $verification_certs, array('ES256', 'ES384', 'RS256', 'RS384', 'RS512'));
	if (!isset($data['iss']) || ($data['iss'] !== 'https://accounts.google.com')) {
		throw new OIDplusException(_L('JWT token could not be decoded'));
	}

	// Check if the email was verified
	$email = $data['email'];
	if ($data['email_verified'] != 'true') {
		throw new OIDplusException(_L('The email address %1 was not verified. Please verify it first!',$email));
	}

	// Everything's done! Now login and/or create account
	if (!empty($email)) {
		$ra = new OIDplusRA($email);
		if (!$ra->existing()) {
			$ra->register_ra(null); // create a user account without password

			// Query user infos
			$ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo'); // Initialise cURL
			if (ini_get('curl.cainfo') == '') curl_setopt($ch, CURLOPT_CAINFO, OIDplus::localpath() . 'vendor/cacert.pem');
			$data_string = '';
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Length: ' . strlen($data_string),
				"Authorization: Bearer ".$access_token
			));
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			$result = curl_exec($ch);
			curl_close($ch);
			$data = json_decode($result,true);
			$personal_name = $data['name']; // = given_name + " " + family_name

			OIDplus::db()->query("update ###ra set ra_name = ?, personal_name = ? where email = ?", array($personal_name, $personal_name, $email));

			OIDplus::logger()->log("[INFO]RA($email)!", "RA '$email' was created because of successful Google OAuth2 login");
		}

		OIDplus::authUtils()->raLoginEx($email, $remember_me=false, 'Google-OAuth2');

		OIDplus::db()->query("UPDATE ###ra set last_login = ".OIDplus::db()->sqlDate()." where email = ?", array($email));

		OIDplus::invoke_shutdown();

		// Go back to OIDplus

		header('Location:'.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL));
	}

} finally {

	// We now have the data of the person that wanted to log in
	// So we can log off again
	$ch = curl_init();
	if (ini_get('curl.cainfo') == '') curl_setopt($ch, CURLOPT_CAINFO, OIDplus::localpath() . 'vendor/cacert.pem');
	curl_setopt($ch, CURLOPT_URL,"https://oauth2.googleapis.com/revoke");
	curl_setopt($ch, CURLOPT_USERAGENT, 'ViaThinkSoft-OIDplus/2.0');
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,
		"client_id=".urlencode(OIDplus::baseConfig()->getValue('GOOGLE_OAUTH2_CLIENT_ID'))."&".
		"client_secret=".urlencode(OIDplus::baseConfig()->getValue('GOOGLE_OAUTH2_CLIENT_SECRET'))."&".
		"token_type_hint=access_token&".
		"token=".urlencode($access_token)
	);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_exec($ch);
	curl_close($ch);

}
