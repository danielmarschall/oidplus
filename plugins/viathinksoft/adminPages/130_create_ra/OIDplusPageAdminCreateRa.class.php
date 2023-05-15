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

class OIDplusPageAdminCreateRa extends OIDplusPagePluginAdmin {

	/**
	 * @param string $actionID
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 */
	public function action(string $actionID, array $params): array {
		if ($actionID == 'create_ra') {
			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')), null, 401);
			}

			_CheckParamExists($params, 'email');
			_CheckParamExists($params, 'password1');
			_CheckParamExists($params, 'password2');

			$email = $params['email'];
			$password1 = $params['password1'];
			$password2 = $params['password2'];

			if (!OIDplus::mailUtils()->validMailAddress($email)) {
				throw new OIDplusException(_L('eMail address is invalid.'));
			}

			$res = OIDplus::db()->query("select * from ###ra where email = ?", array($email)); // TODO: this should be a static function in the RA class
			if ($res->any()) {
				throw new OIDplusException(_L('RA does already exist'));
			}

			if ($password1 !== $password2) {
				throw new OIDplusException(_L('Passwords do not match'));
			}

			if (strlen($password1) < OIDplus::config()->getValue('ra_min_password_length')) {
				$minlen = OIDplus::config()->getValue('ra_min_password_length');
				throw new OIDplusException(_L('Password is too short. Need at least %1 characters',$minlen));
			}

			OIDplus::logger()->log("V2:[INFO]RA(%1)+[OK/INFO]A", "RA '%1' was created by the admin, without email address verification or invitation", $email);

			$ra = new OIDplusRA($email);
			$ra->register_ra($password1);

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
		$parts = explode('$',$id,2);
		$id = $parts[0];
		$email = $parts[1] ?? '';

		if ($id == 'oidplus:create_ra') {
			$handled = true;

			$out['title'] = _L('Manual creation of a RA');
			$out['icon'] = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')), $out['title'], 401);
			}

			$out['text'] .= '<form id="adminCreateRaFrom" action="javascript:void(0);" onsubmit="return OIDplusPageAdminCreateRa.adminCreateRaFormOnSubmit();">';
			$out['text'] .= '<div><label class="padding_label">'._L('E-Mail').':</label><input type="text" id="email" value="'.htmlentities($email).'"></div>';
			$out['text'] .= '<div><label class="padding_label">'._L('Password').':</label><input type="password" id="password1" value=""/></div>';
			$out['text'] .= '<div><label class="padding_label">'._L('Repeat').':</label><input type="password" id="password2" value=""/></div>';
			$out['text'] .= '<br><input type="submit" value="'._L('Create').'"></form>';
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
		if (!OIDplus::authUtils()->isAdminLoggedIn()) return false;

		if (file_exists(__DIR__.'/img/main_icon16.png')) {
			$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:create_ra',
			'icon' => $tree_icon,
			'text' => _L('Create RA manually')
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
