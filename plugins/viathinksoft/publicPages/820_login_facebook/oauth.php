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
# - https://developers.facebook.com/docs/facebook-login/manually-build-a-login-flow
# - https://developers.facebook.com/tools/explorer/

require_once __DIR__ . '/../../../../includes/oidplus.inc.php';

OIDplus::init(true);
set_exception_handler(array('OIDplusGui', 'html_exception_handler'));

if (OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_OIDplusPagePublicLoginFacebook', false)) {
	throw new OIDplusException(_L('This plugin was disabled by the system administrator!'));
}

if (!OIDplus::baseConfig()->getValue('FACEBOOK_OAUTH2_ENABLED', false)) {
	throw new OIDplusException(_L('Facebook OAuth authentication is disabled on this system.'));
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

// Get access token

$ch = curl_init();
if (ini_get('curl.cainfo') == '') curl_setopt($ch, CURLOPT_CAINFO, OIDplus::localpath() . 'vendor/cacert.pem');
curl_setopt($ch, CURLOPT_URL,"https://graph.facebook.com/v8.0/oauth/access_token?".
	"client_id=".urlencode(OIDplus::baseConfig()->getValue('FACEBOOK_OAUTH2_CLIENT_ID'))."&".
	"redirect_uri=".urlencode(OIDplus::webpath(__DIR__,OIDplus::PATH_ABSOLUTE_CANONICAL).'oauth.php')."&".
	"client_secret=".urlencode(OIDplus::baseConfig()->getValue('FACEBOOK_OAUTH2_CLIENT_SECRET'))."&".
	"code=".$_GET['code']
);
curl_setopt($ch, CURLOPT_USERAGENT, 'ViaThinkSoft-OIDplus/2.0');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$cont = curl_exec($ch);
curl_close($ch);
$data = json_decode($cont,true);
if (isset($data['error'])) {
	echo '<h2>Error at step 2</h2>';
	echo '<p>'.$data['error']['message'].'</p>';
	die();
}
$access_token = $data['access_token'];

// Get user infos

$ch = curl_init();
if (ini_get('curl.cainfo') == '') curl_setopt($ch, CURLOPT_CAINFO, OIDplus::localpath() . 'vendor/cacert.pem');
curl_setopt($ch, CURLOPT_URL,"https://graph.facebook.com/v8.0/me?".
	"fields=id,email,name&".
	"access_token=".urlencode($access_token)
);
curl_setopt($ch, CURLOPT_USERAGENT, 'ViaThinkSoft-OIDplus/2.0');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$cont = curl_exec($ch);
curl_close($ch);
$data = json_decode($cont,true);
if (isset($data['error'])) {
	throw new OIDplusException(_L('Error receiving the authentication token from %1: %2','Facebook',$data['error']['message']));
}
$personal_name = $data['name'];
$email = !isset($data['email']) ? '' : $data['email'];
if (empty($email)) {
	throw new OIDplusException(_L('Your Facebook account does not have an email address.'));
}

// Everything's done! Now login and/or create account

$ra = new OIDplusRA($email);
if (!$ra->existing()) {
	$ra->register_ra(null); // create a user account without password

	OIDplus::db()->query("update ###ra set ra_name = ?, personal_name = ? where email = ?", array($personal_name, $personal_name, $email));

	OIDplus::logger()->log("[INFO]RA($email)!", "RA '$email' was created because of successful Facebook OAuth2 login");
}

OIDplus::authUtils()->raLoginEx($email, $remember_me=false, 'Facebook-OAuth2');

OIDplus::db()->query("UPDATE ###ra set last_login = ".OIDplus::db()->sqlDate()." where email = ?", array($email));

// Go back to OIDplus

header('Location:'.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL));
