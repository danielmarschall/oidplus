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

class OIDplusPagePublicForgotPasswordAdmin extends OIDplusPagePluginPublic {

	/**
	 * @param bool $html
	 * @return void
	 */
	public function init(bool $html=true) {
	}

	/**
	 * @param string $id
	 * @param array $out
	 * @param bool $handled
	 * @return void
	 * @throws OIDplusException
	 */
	public function gui(string $id, array &$out, bool &$handled) {
		if (explode('$',$id)[0] == 'oidplus:forgot_password_admin') {
			$handled = true;

			if (OIDplus::authUtils()->isAdminLoggedIn()) {
				$out['title'] = _L('Change admin password');
			} else {
				$out['title'] = _L('Reset admin password');
			}
			$out['icon']  = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png';

			$baseconfigfile = OIDplus::getUserDataDir("baseconfig").'config.inc.php';
			$baseconfigfile = substr($baseconfigfile, strlen(OIDplus::localpath(NULL))); // "censor" the system local path
			$out['text']  = '<p>'._L('To reset the password of the administrator, create a hash below and then replace the entry in the file %1.','<b>'.$baseconfigfile.'</b>').'</p>';
			$out['text'] .= '<div><label class="padding_label">'._L('New password').':</label><input type="password" id="admin_password" onkeypress="OIDplusPagePublicForgotPasswordAdmin.rehash_admin_pwd()" onkeyup="OIDplusPagePublicForgotPasswordAdmin.rehash_admin_pwd()"></div>';
			$out['text'] .= '<div><label class="padding_label">'._L('Repeat').':</label><input type="password" id="admin_password2" onkeypress="OIDplusPagePublicForgotPasswordAdmin.rehash_admin_pwd()" onkeyup="OIDplusPagePublicForgotPasswordAdmin.rehash_admin_pwd()"></div>';
			$out['text'] .= '<p><pre id="config"></pre></p>';
			$out['text'] .= '<p><input type="button" id="copy_clipboard_button" value="'._L('Copy to clipboard').'" onClick="copyToClipboard(config)"></p>';
			$out['text'] .= '<script> $("#copy_clipboard_button").hide(); OIDplusPagePublicForgotPasswordAdmin.rehash_admin_pwd(); </script>';
		}
	}

	/**
	 * @param array $out
	 * @return void
	 */
	public function publicSitemap(array &$out) {
		$out[] = 'oidplus:forgot_password_admin';
	}

	/**
	 * @param array $json
	 * @param string|null $ra_email
	 * @param bool $nonjs
	 * @param string $req_goto
	 * @return bool
	 */
	public function tree(array &$json, string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
		return false;
	}

	/**
	 * @param string $request
	 * @return array|false
	 */
	public function tree_search(string $request) {
		return false;
	}
}
