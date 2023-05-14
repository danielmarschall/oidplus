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

class OIDplusPageRaChangePassword extends OIDplusPagePluginRa {

	/**
	 * @param string $actionID
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 */
	public function action(string $actionID, array $params): array {
		if ($actionID == 'change_ra_password') {
			_CheckParamExists($params, 'email');

			$email = $params['email'];

			$res = OIDplus::db()->query("select * from ###ra where email = ?", array($email));
			if (!$res->any()) {
				throw new OIDplusException(_L('RA does not exist'));
			}

			if (!OIDplus::authUtils()->isRaLoggedIn($email) && !OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusException(_L('Authentication error. Please log in as admin, or as the RA to update its data.'), null, 401);
			}

			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				_CheckParamExists($params, 'old_password');
				$old_password = $params['old_password'];
			} else {
				$old_password = '';
			}

			_CheckParamExists($params, 'new_password1');
			_CheckParamExists($params, 'new_password2');

			$password1 = $params['new_password1'];
			$password2 = $params['new_password2'];

			if ($password1 !== $password2) {
				throw new OIDplusException(_L('Passwords do not match'));
			}

			if (strlen($password1) < OIDplus::config()->getValue('ra_min_password_length')) {
				$minlen = OIDplus::config()->getValue('ra_min_password_length');
				throw new OIDplusException(_L('New password is too short. Minimum password length: %1',$minlen));
			}

			$ra = new OIDplusRA($email);
			if (!$ra->isPasswordLess()) {
				if (!OIDplus::authUtils()->isAdminLoggedIn()) {
					if (!$ra->checkPassword($old_password)) {
						throw new OIDplusException(_L('Old password incorrect'));
					}
				}
				OIDplus::logger()->log("V2:[OK/WARN]RA(%1)+[OK/INFO]A", "Password of RA '%1' changed", $email);
			} else {
				OIDplus::logger()->log("V2:[OK/WARN]RA(%1)+[OK/INFO]A", "Password of RA '%1' created", $email);
			}
			$ra->change_password($password1);

			return array("status" => 0);
		} else {
			return parent::action($actionID, $params);
		}
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
		if (explode('$',$id)[0] == 'oidplus:change_ra_password') {
			$handled = true;

			$ra_email = explode('$',$id)[1];
			$ra = new OIDplusRA($ra_email);

			$out['title'] = $ra->isPasswordLess() ? _L('Create password') : _L('Change RA password');
			$out['icon'] = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

			if (!OIDplus::authUtils()->isRaLoggedIn($ra_email) && !OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as the requested RA %2.',OIDplus::gui()->link('oidplus:login$ra$'.$ra_email),'<b>'.htmlentities($ra_email).'</b>'), $out['title'], 401);
			}

			$res = OIDplus::db()->query("select * from ###ra where email = ?", array($ra_email));
			if (!$res->any()) {
				throw new OIDplusHtmlException(_L('RA "%1" does not exist','<b>'.htmlentities($ra_email).'</b>'), $out['title']);
			}

			$out['text'] .= '<form id="raChangePasswordForm" action="javascript:void(0);" onsubmit="return OIDplusPageRaChangePassword.raChangePasswordFormOnSubmit();">';
			$out['text'] .= '<input type="hidden" id="email" value="'.htmlentities($ra_email).'"/><br>';
			$out['text'] .= '<div><label class="padding_label">'._L('E-Mail').':</label><b>'.htmlentities($ra_email).'</b></div>';
			if (!$ra->isPasswordLess()) {
				if (OIDplus::authUtils()->isAdminLoggedIn()) {
					$out['text'] .= '<div><label class="padding_label">'._L('Old password').':</label><i>'._L('Admin can change the password without verification of the old password.').'</i></div>';
				} else {
					$out['text'] .= '<div><label class="padding_label">'._L('Old password').':</label><input type="password" id="old_password" value=""/></div>';
				}
			}
			$out['text'] .= '<div><label class="padding_label">'._L('New password').':</label><input type="password" id="new_password1" value=""/></div>';
			$out['text'] .= '<div><label class="padding_label">'._L('Repeat').':</label><input type="password" id="new_password2" value=""/></div>';
			$out['text'] .= '<br><input type="submit" value="'.($ra->isPasswordLess() ? _L('Create password') : _L('Change password')).'"></form>';
		}
	}

	/**
	 * @param array $json
	 * @param string|null $ra_email
	 * @param bool $nonjs
	 * @param string $req_goto
	 * @return bool
	 * @throws OIDplusException
	 */
	public function tree(array &$json, string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
		if (!$ra_email) return false;
		if (!OIDplus::authUtils()->isRaLoggedIn($ra_email) && !OIDplus::authUtils()->isAdminLoggedIn()) return false;

		if (file_exists(__DIR__.'/img/main_icon16.png')) {
			$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$ra = new OIDplusRA($ra_email);

		$json[] = array(
			'id' => 'oidplus:change_ra_password$'.$ra_email,
			'icon' => $tree_icon,
			'text' => $ra->isPasswordLess() ? _L('Create password') : _L('Change password')
		);

		return true;
	}

	/**
	 * @param string $request
	 * @return array|false
	 */
	public function tree_search(string $request) {
		return false;
	}
}
