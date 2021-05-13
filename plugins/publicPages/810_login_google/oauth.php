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

require_once __DIR__ . '/../../../includes/oidplus.inc.php';

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
	die(_L('Wrong CSRF Token'));
}

if (!function_exists('curl_init')) {
	die(_L('The "%1" PHP extension is not installed at your system. Please enable the PHP extension <code>%2</code>.','CURL','php_curl'));
}

$ch = curl_init();
if (ini_get('curl.cainfo') == '') curl_setopt($ch, CURLOPT_CAINFO, OIDplus::localpath() . '3p/certs/cacert.pem');
curl_setopt($ch, CURLOPT_URL,"https://oauth2.googleapis.com/token");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS,
	"grant_type=authorization_code&".
	"code=".urlencode($_GET['code'])."&".
	"redirect_uri=".urlencode(OIDplus::webpath(__DIR__,false).'oauth.php')."&".
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
	// and https://github.com/firebase/php-jwt/blob/master/src/JWT.php
	// Note: We do not need to verify the signature because the token comes directly from Google,
	//       but we do it anyway. Just to be sure!
	$verification_certs = json_decode(file_get_contents('https://www.googleapis.com/oauth2/v1/certs'), true);
	$data = decode_idtoken($id_token, $verification_certs);
	if (($data === false) || !isset($data['iss']) || ($data['iss'] !== 'https://accounts.google.com')) {
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
			if (ini_get('curl.cainfo') == '') curl_setopt($ch, CURLOPT_CAINFO, OIDplus::localpath() . '3p/certs/cacert.pem');
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

		OIDplus::logger()->log("[OK]RA($email)!", "RA '$email' logged in via Google OAuth2");
		OIDplus::authUtils()->raLogin($email);

		OIDplus::db()->query("UPDATE ###ra set last_login = ".OIDplus::db()->sqlDate()." where email = ?", array($email));

		// Go back to OIDplus

		header('Location:'.OIDplus::webpath(null,false));
	}

} finally {

	// We now have the data of the person that wanted to log in
	// So we can log off again
	$ch = curl_init();
	if (ini_get('curl.cainfo') == '') curl_setopt($ch, CURLOPT_CAINFO, OIDplus::localpath() . '3p/certs/cacert.pem');
	curl_setopt($ch, CURLOPT_URL,"https://oauth2.googleapis.com/revoke");
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

# ----------------------------------

function decode_idtoken($id_token, $verification_certs=null) {
	// Parts taken and simplified from https://github.com/firebase/php-jwt , licensed by BSD-3-clause
	// Here is a great page for encode and decode tokens for testing: https://jwt.io/

	$parts = explode('.', $id_token);
	if (count($parts) === 5) return false; // encrypted JWT not yet supported
	if (count($parts) !== 3) return false;
	list($header_base64, $payload_base64, $signature_base64) = $parts;

	$header_ary = json_decode(urlsafeB64Decode($header_base64),true);
	if ($header_ary['typ'] !== 'JWT') return false;

	if ($verification_certs) {
		$key = isset($header_ary['kid']) ? $verification_certs[$header_ary['kid']] : $verification_certs;

		$msg = $header_base64.'.'.$payload_base64;
		$signature = urlsafeB64Decode($signature_base64);

		$jwt_algo = $header_ary['alg'];
		if ($jwt_algo != 'none') {
			$php_algo = 'SHA'.substr($jwt_algo,2,3);
			switch (substr($jwt_algo,0,2)) {
				case 'ES':
					// OpenSSL expects an ASN.1 DER sequence for ES256 signatures
					$signature = signatureToDER($signature);
					if (!function_exists('openssl_verify')) break; // if OpenSSL is not installed, we just accept the JWT
					if (!openssl_verify($msg, $signature, $key, $php_algo)) return false;
					break;
				case 'RS':
					if (!function_exists('openssl_verify')) break; // if OpenSSL is not installed, we just accept the JWT
					if (!openssl_verify($msg, $signature, $key, $php_algo)) return false;
					break;
				case 'HS':
					$hash = @hash_hmac($php_algo, $msg, $key, true);
					if (!$hash) break; // if the hash algo is not available, we just accept the JWT
					if (!hash_equals($signature, $hash)) return false;
					break;
				case 'PS':
					file_put_contents($msg_file = tempnam("/tmp", ""), $msg);
					file_put_contents($sig_file = tempnam("/tmp", ""), $signature);
					file_put_contents($key_file = tempnam("/tmp", ""), $key);
					$ec = -1;
					$out = array();
					$cmd = "openssl dgst -".strtolower($php_algo)." -sigopt rsa_padding_mode:pss -sigopt rsa_pss_saltlen:-1 -verify ".escapeshellarg($key_file)." -signature ".escapeshellarg($sig_file)." ".escapeshellarg($msg_file);
					$cmd .= (strtoupper(substr(PHP_OS,0,3)) === 'WIN') ? ' 2> NUL' : ' 2> /dev/null';
					exec($cmd, $out, $ec);
					unlink($msg_file);
					unlink($sig_file);
					unlink($key_file);
					if (($ec !== 0) && (count($out) === 0)) break; // If OpenSSL is not found, we just accept the JWT
					if (($ec !== 0) || (strpos(implode("\n",$out),"Verified OK") === false)) return false;
					break;
				default:
					return false;
			}
		}
	}

	$payload_ary = json_decode(urlsafeB64Decode($payload_base64), true);

	$leeway = 60; // 1 Minute
	if (isset($payload_ary['nbf']) && (time()+$leeway<$payload_ary['nbf'])) return false;
	if (isset($payload_ary['exp']) && (time()-$leeway>$payload_ary['exp'])) return false;

	return $payload_ary;
}

function urlsafeB64Decode($input) {
	// Taken from https://github.com/firebase/php-jwt , licensed by BSD-3-clause
	$remainder = strlen($input) % 4;
	if ($remainder) {
		$padlen = 4 - $remainder;
		$input .= str_repeat('=', $padlen);
	}
	return base64_decode(strtr($input, '-_', '+/'));
}

function signatureToDER($sig) {
	// Taken from https://github.com/firebase/php-jwt , licensed by BSD-3-clause, modified

	// Separate the signature into r-value and s-value
	list($r, $s) = str_split($sig, (int) (strlen($sig) / 2));

	// Trim leading zeros
	$r = ltrim($r, "\x00");
	$s = ltrim($s, "\x00");

	// Convert r-value and s-value from unsigned big-endian integers to signed two's complement
	if (ord($r[0]) > 0x7f) $r = "\x00" . $r;
	if (ord($s[0]) > 0x7f) $s = "\x00" . $s;

	$der_r = chr(0x00/*primitive*/ | 0x02/*INTEGER*/).chr(strlen($r)).$r;
	$der_s = chr(0x00/*primitive*/ | 0x02/*INTEGER*/).chr(strlen($s)).$s;
	$der = chr(0x20/*constructed*/ | 0x10/*SEQUENCE*/).chr(strlen($der_r.$der_s)).$der_r.$der_s;
	return $der;
}
