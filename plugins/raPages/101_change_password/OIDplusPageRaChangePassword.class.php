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

class OIDplusPageRaChangePassword extends OIDplusPagePluginRa {

	public function action($actionID, $params) {
		if ($actionID == 'change_ra_password') {
			$email = $params['email'];

			$res = OIDplus::db()->query("select * from ###ra where email = ?", array($email));
			if ($res->num_rows() == 0) {
				throw new OIDplusException(_L('RA does not exist'));
			}

			if (!OIDplus::authUtils()::isRaLoggedIn($email) && !OIDplus::authUtils()::isAdminLoggedIn()) {
				throw new OIDplusException(_L('Authentication error. Please log in as admin, or as the RA to update its data.'));
			}

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				$old_password = $params['old_password'];
			}
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
				if (!OIDplus::authUtils()::isAdminLoggedIn()) {
					if (!$ra->checkPassword($old_password)) {
						throw new OIDplusException(_L('Old password incorrect'));
					}
				}
				OIDplus::logger()->log("[?WARN/!OK]RA($email)?/[?INFO/!OK]A?", "Password of RA '$email' changed");
			} else {
				OIDplus::logger()->log("[?WARN/!OK]RA($email)?/[?INFO/!OK]A?", "Password of RA '$email' created");
			}
			$ra->change_password($password1);

			return array("status" => 0);
		} else {
			throw new OIDplusException(_L('Unknown action ID'));
		}
	}

	public function init($html=true) {
		// Nothing
	}

	public function gui($id, &$out, &$handled) {
		if (explode('$',$id)[0] == 'oidplus:change_ra_password') {
			$handled = true;

			$ra_email = explode('$',$id)[1];
			$ra = new OIDplusRA($ra_email);

			$out['title'] = $ra->isPasswordLess() ? _L('Create password') : _L('Change RA password');
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';

			if (!OIDplus::authUtils()::isRaLoggedIn($ra_email) && !OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>'._L('You need to <a %1>log in</a> as the requested RA %2.',OIDplus::gui()->link('oidplus:login'),'<b>'.htmlentities($ra_email).'</b>').'</p>';
				return;
			}

			$res = OIDplus::db()->query("select * from ###ra where email = ?", array($ra_email));
			if ($res->num_rows() == 0) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = _L('RA "%1" does not exist','<b>'.htmlentities($ra_email).'</b>');
				return;
			}

			$out['text'] .= '<form id="raChangePasswordForm" action="javascript:void(0);" onsubmit="return raChangePasswordFormOnSubmit();">';
			$out['text'] .= '<input type="hidden" id="email" value="'.htmlentities($ra_email).'"/><br>';
			$out['text'] .= '<div><label class="padding_label">'._L('E-Mail').':</label><b>'.htmlentities($ra_email).'</b></div>';
			if (!$ra->isPasswordLess()) {
				if (OIDplus::authUtils()::isAdminLoggedIn()) {
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

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (!$ra_email) return false;
		if (!OIDplus::authUtils()::isRaLoggedIn($ra_email) && !OIDplus::authUtils()::isAdminLoggedIn()) return false;

		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = OIDplus::webpath(__DIR__).'treeicon.png';
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

	public function tree_search($request) {
		return false;
	}
}
