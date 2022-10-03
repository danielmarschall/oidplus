<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2022 Daniel Marschall, ViaThinkSoft
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

if (!defined('INSIDE_OIDPLUS')) die();

class OIDplusPagePublicForgotPasswordAdmin extends OIDplusPagePluginPublic {

	public function init($html=true) {
	}

	public function gui($id, &$out, &$handled) {
		if (explode('$',$id)[0] == 'oidplus:forgot_password_admin') {
			$handled = true;

			if (OIDplus::authUtils()->isAdminLoggedIn()) {
				$out['title'] = _L('Change admin password');
			} else {
				$out['title'] = _L('Reset admin password');
			}
			$out['icon']  = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png';

			$out['text']  = '<p>'._L('To reset the password of the administrator, create a hash below and then replace the entry in the file %1.','<b>userdata/baseconfig/config.inc.php</b>').'</p>';
			$out['text'] .= '<div><label class="padding_label">'._L('New password').':</label><input type="password" id="admin_password" onkeypress="OIDplusPagePublicForgotPasswordAdmin.rehash_admin_pwd()" onkeyup="OIDplusPagePublicForgotPasswordAdmin.rehash_admin_pwd()"></div>';
			$out['text'] .= '<div><label class="padding_label">'._L('Repeat').':</label><input type="password" id="admin_password2" onkeypress="OIDplusPagePublicForgotPasswordAdmin.rehash_admin_pwd()" onkeyup="OIDplusPagePublicForgotPasswordAdmin.rehash_admin_pwd()"></div>';
			$out['text'] .= '<p><pre id="config"></pre></p>';
			$out['text'] .= '<p><input type="button" id="copy_clipboard_button" value="'._L('Copy to clipboard').'" onClick="copyToClipboard(config)"></p>';
			$out['text'] .= '<script> $("#copy_clipboard_button").hide(); OIDplusPagePublicForgotPasswordAdmin.rehash_admin_pwd(); </script>';
		}
	}

	public function publicSitemap(&$out) {
		$out[] = 'oidplus:forgot_password_admin';
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		return false;
	}

	public function tree_search($request) {
		return false;
	}
}
