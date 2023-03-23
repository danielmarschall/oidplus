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

namespace ViaThinkSoft\OIDplus;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusPagePublicLoginFacebook extends OIDplusPagePluginPublic {

	/**
	 * @param string $actionID
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 */
	public function action(string $actionID, array $params): array {
		return parent::action($actionID, $params);
	}

	/**
	 * @param bool $html
	 * @return void
	 */
	public function init(bool $html=true) {
		// Nothing
	}

	/**
	 * @param string $id
	 * @param array $out
	 * @param bool $handled
	 * @return void
	 * @throws OIDplusException
	 */
	public function gui(string $id, array &$out, bool &$handled) {
		if ($id === 'oidplus:login_facebook') {
			$handled = true;
			$out['title'] = _L('Login using Facebook');
			$out['icon']  = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png';

			if (!OIDplus::baseConfig()->getValue('FACEBOOK_OAUTH2_ENABLED', false)) {
				$out['icon'] = 'img/error.png';
				$out['text'] = _L('Facebook OAuth authentication is disabled on this system.');
				return;
			}

			$target =
				"https://www.facebook.com/v8.0/dialog/oauth?".
				"client_id=".urlencode(OIDplus::baseConfig()->getValue('FACEBOOK_OAUTH2_CLIENT_ID'))."&".
				"redirect_uri=".urlencode(OIDplus::webpath(__DIR__,OIDplus::PATH_ABSOLUTE_CANONICAL).'oauth.php')."&".
				"state=".urlencode($_COOKIE['csrf_token_weak'])."&".
				"scope=email";
			$out['text'] = '<p>'._L('Please wait...').'</p><script>window.location.href = '.js_escape($target).';</script>';
		}
	}

	/**
	 * @param array $out
	 * @return void
	 */
	public function publicSitemap(array &$out) {
		$out[] = 'oidplus:login_facebook';
	}

	/**
	 * @param array $json
	 * @param string|null $ra_email
	 * @param bool $nonjs
	 * @param string $req_goto
	 * @return bool
	 */
	public function tree(array &$json, string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
		return true;
	}

	/**
	 * @param string $request
	 * @return array|false
	 */
	public function tree_search(string $request) {
		return false;
	}

	/**
	 * @param string $id
	 * @return bool
	 */
	public function implementsFeature(string $id): bool {
		if (strtolower($id) == '1.3.6.1.4.1.37476.2.5.2.3.5') return true; // alternativeLoginMethods()
		if (strtolower($id) == '1.3.6.1.4.1.37476.2.5.2.3.8') return true; // getNotifications()
		return false;
	}

	/**
	 * Implements interface 1.3.6.1.4.1.37476.2.5.2.3.5
	 * @return array
	 * @throws OIDplusException
	 */
	public function alternativeLoginMethods() {
		$logins = array();
		if (OIDplus::baseConfig()->getValue('FACEBOOK_OAUTH2_ENABLED', false)) {
			$logins[] = array(
				'oidplus:login_facebook',
				_L('Login using Facebook'),
				OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png'
			);
		}
		return $logins;
	}

	/**
	 * Implements interface 1.3.6.1.4.1.37476.2.5.2.3.8
	 * @param $user
	 * @return array
	 * @throws OIDplusException
	 */
	public function getNotifications($user=null): array {
		$notifications = array();
		if ((!$user || ($user == 'admin')) && OIDplus::authUtils()->isAdminLoggedIn()) {
			if (OIDplus::baseConfig()->getValue('FACEBOOK_OAUTH2_ENABLED', false)) {
				if (!function_exists('curl_init')) {
					$title = _L('Facebook OAuth Login');
					$notifications[] = array('ERR', _L('OIDplus plugin "%1" is enabled, but the required PHP extension "%2" is not installed.', htmlentities($title), 'php_curl'));
				}
			}
		}
		return $notifications;
	}

}
