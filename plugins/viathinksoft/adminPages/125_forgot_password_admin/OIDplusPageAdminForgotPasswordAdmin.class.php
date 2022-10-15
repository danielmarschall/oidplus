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

if (!defined('INSIDE_OIDPLUS')) die();

class OIDplusPageAdminForgotPasswordAdmin extends OIDplusPagePluginAdmin {

	public function init($html=true) {
	}

	public function gui($id, &$out, &$handled) {
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (!OIDplus::authUtils()->isAdminLoggedIn()) return false;
		if (is_null(OIDplus::getPluginByOid('1.3.6.1.4.1.37476.2.5.2.4.1.92'))) return false; // OIDplusPagePublicForgotPasswordAdmin

		if (file_exists(__DIR__.'/img/main_icon16.png')) {
			$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:forgot_password_admin', // link to the public page plugin!
			'icon' => $tree_icon,
			'text' => _L('Change password')
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}
}
