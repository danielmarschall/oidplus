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

if (!defined('IN_OIDPLUS')) die();

class OIDplusPagePublicForgotPasswordAdmin extends OIDplusPagePlugin {
	public function type() {
		return 'public';
	}

	public function priority() {
		return 92;
	}

	public function action(&$handled) {
	}

	public function init($html=true) {
	}

	public function cfgSetValue($name, $value) {
	}

	public function gui($id, &$out, &$handled) {
		if (explode('$',$id)[0] == 'oidplus:forgot_password_admin') {
			$handled = true;

			$out['title'] = 'Reset admin password';
			$out['icon']  = 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/forgot_password_big.png';

			$out['text']  = '<p>To reset the password of the administrator, create a hash below and then replace the entry in the file <b>includes/config.inc.php</b>.</p>';
            $out['text'] .= '<p>New password: <input type="password" id="admin_password" onkeypress="rehash_admin_pwd()" onkeyup="rehash_admin_pwd()"></p>';
            $out['text'] .= '<p><pre id="config"></pre></p>';
			$out['text'] .= '<script> rehash_admin_pwd(); </script>';
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		/*
		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:forgot_password_admin',
			'icon' => $tree_icon,
			'text' => 'Reset admin password'
		);

		return true;
		*/
		return false;
	}

	public function tree_search($request) {
		return false;
	}
}

OIDplus::registerPagePlugin(new OIDplusPagePublicForgotPasswordAdmin());
