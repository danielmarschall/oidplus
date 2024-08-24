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

namespace ViaThinkSoft\OIDplus\Plugins\PublicPages\LoginGoogle;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginPublic;
use ViaThinkSoft\OIDplus\Plugins\AdminPages\Notifications\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8;
use ViaThinkSoft\OIDplus\Plugins\AdminPages\Notifications\OIDplusNotification;
use ViaThinkSoft\OIDplus\Plugins\PublicPages\Login\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_5;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusPagePublicLoginGoogle extends OIDplusPagePluginPublic
	implements INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_5, /* alternativeLoginMethods */
	           INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8  /* getNotifications */
{

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
	public function init(bool $html=true): void {
		// Nothing
	}

	/**
	 * @param string $id
	 * @param array $out
	 * @param bool $handled
	 * @return void
	 * @throws OIDplusException
	 */
	public function gui(string $id, array &$out, bool &$handled): void {
		if ($id === 'oidplus:login_google') {
			$handled = true;
			$out['title'] = _L('Login using Google');
			$out['icon']  = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png';

			if (!OIDplus::baseConfig()->getValue('GOOGLE_OAUTH2_ENABLED', false)) {
				throw new OIDplusException(_L('Google OAuth authentication is disabled on this system.'), $out['title']);
			}

			_CheckParamExists($_COOKIE, 'csrf_token_weak');

			$out['text']  = '<p>'._L('Please wait...').'</p>';
			$out['text'] .= '<noscript>';
			$out['text'] .= '<p><font color="red">'._L('You need to enable JavaScript to use this feature.').'</font></p>';
			$out['text'] .= '</noscript>';
			$out['text'] .= '<form action="https://accounts.google.com/o/oauth2/v2/auth" method="GET">';
			$out['text'] .= '<input type="hidden" name="response_type" value="'.htmlentities('code').'">'."\n";
			$out['text'] .= '<input type="hidden" name="client_id" value="'.htmlentities(OIDplus::baseConfig()->getValue('GOOGLE_OAUTH2_CLIENT_ID')).'">'."\n";
			$out['text'] .= '<input type="hidden" name="scope" value="'.htmlentities(implode(' ', array(/*'openid',*/ 'email', 'profile'))).'">'."\n";
			$out['text'] .= '<input type="hidden" name="redirect_uri" value="'.htmlentities('').'">'."\n"; // URL will be filled by JavaScript
			$out['text'] .= '<input type="hidden" name="state" value="'.htmlentities($_COOKIE['csrf_token_weak']).'">'."\n"; // URL will be added by JavaScript
			$out['text'] .= '</form>';
			$out['text'] .= '<script>';
			$out['text'] .= 'redir_url = getSystemUrl() + "plugins/viathinksoft/publicPages/810_login_google/oauth.php";';
			$out['text'] .= 'document.forms[0].elements.redirect_uri.value = redir_url;';
			$out['text'] .= 'document.forms[0].elements.state.value = redir_url + "|" + document.forms[0].elements.state.value;';
			$out['text'] .= 'document.forms[0].submit();';
			$out['text'] .= '</script>';
		}
	}

	/**
	 * @param array $out
	 * @return void
	 */
	public function publicSitemap(array &$out): void {
		$out[] = 'oidplus:login_google';
	}

	/**
	 * @param array $json
	 * @param string|null $ra_email
	 * @param bool $nonjs
	 * @param string $req_goto
	 * @return bool
	 */
	public function tree(array &$json, ?string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
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
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_5
	 * @return array
	 * @throws OIDplusException
	 */
	public function alternativeLoginMethods(): array {
		$logins = array();
		if (OIDplus::baseConfig()->getValue('GOOGLE_OAUTH2_ENABLED', false)) {
			$logins[] = array(
				'oidplus:login_google',
				_L('Login using Google'),
				OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png'
			);
		}
		return $logins;
	}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8
	 * @param string|null $user
	 * @return array
	 * @throws OIDplusException
	 */
	public function getNotifications(?string $user=null): array {
		$notifications = array();
		if ((!$user || ($user == 'admin')) && OIDplus::authUtils()->isAdminLoggedIn()) {
			if (OIDplus::baseConfig()->getValue('GOOGLE_OAUTH2_ENABLED', false)) {
				if (!url_post_contents_available(true, $reason)) {
					$title = _L('Google OAuth Login');
					$notifications[] = new OIDplusNotification('ERR', _L('OIDplus plugin "%1" is enabled, but OIDplus cannot connect to the Internet.', htmlentities($title)).' '.$reason);
				}
			}
		}
		return $notifications;
	}

}
