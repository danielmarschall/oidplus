<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2023 Daniel Marschall, ViaThinkSoft
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

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusGui;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusRA;

# More information about the OAuth2 implementation:
# - https://developers.facebook.com/docs/facebook-login/manually-build-a-login-flow
# - https://developers.facebook.com/tools/explorer/

require_once __DIR__ . '/../../../../includes/oidplus.inc.php';

set_exception_handler(array(OIDplusGui::class, 'html_exception_handler'));

OIDplus::init(true);

if (OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_ViaThinkSoft\OIDplus\Plugins\viathinksoft\publicPages\n820_login_facebook\OIDplusPagePublicLoginFacebook', false)) {
	throw new OIDplusException(_L('This plugin was disabled by the system administrator!'));
}

if (!OIDplus::baseConfig()->getValue('FACEBOOK_OAUTH2_ENABLED', false)) {
	throw new OIDplusException(_L('Facebook OAuth authentication is disabled on this system.'));
}

_CheckParamExists($_GET, 'code');
_CheckParamExists($_GET, 'state');
_CheckParamExists($_COOKIE, 'csrf_token_weak');

$ary = explode('|', $_GET['state'], 2);
if (count($ary) !== 2) {
	die(_L('Invalid State'));
}
$redirect_uri = $ary[0] ?? ''; // Attention: Comes from the client. The OAuth2 server MUST verify it! (Google and Facebook does this)
$check_csrf = $ary[1] ?? '';

if ($check_csrf != $_COOKIE['csrf_token_weak']) {
	die(_L('Missing or wrong CSRF Token'));
}

// Get access token

$cont = url_post_contents(
	"https://graph.facebook.com/v8.0/oauth/access_token?".
		"client_id=".urlencode(OIDplus::baseConfig()->getValue('FACEBOOK_OAUTH2_CLIENT_ID'))."&".
		"redirect_uri=".urlencode($redirect_uri)."&".
		"client_secret=".urlencode(OIDplus::baseConfig()->getValue('FACEBOOK_OAUTH2_CLIENT_SECRET'))."&".
		"code=".$_GET['code']
);

if ($cont === false) {
	throw new OIDplusException(_L('Communication with %1 server failed', 'Facebook'));
}

$data = json_decode($cont,true);
if (isset($data['error'])) {
	echo '<h2>Error at step 2</h2>';
	echo '<p>'.$data['error']['message'].'</p>';
	die();
}
$access_token = $data['access_token'];

// Get user infos

$cont = url_post_contents(
	"https://graph.facebook.com/v8.0/me?".
		"fields=id,email,name&".
		"access_token=".urlencode($access_token)
);

if ($cont === false) {
	throw new OIDplusException(_L('Communication with %1 server failed', 'Facebook'));
}

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

	OIDplus::logger()->log("V2:[INFO]RA(%1)", "RA '%1' was created because of successful Facebook OAuth2 login", $email);
}

OIDplus::authUtils()->raLoginEx($email, 'Facebook-OAuth2');

OIDplus::db()->query("UPDATE ###ra set last_login = ".OIDplus::db()->sqlDate()." where email = ?", array($email));

// Go back to OIDplus

$loc = OIDplus::webpath(null,OIDplus::PATH_RELATIVE);
OIDplus::invoke_shutdown();
header('Location:'.$loc);
