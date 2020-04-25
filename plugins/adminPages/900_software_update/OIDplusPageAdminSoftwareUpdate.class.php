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

class OIDplusPageAdminSoftwareUpdate extends OIDplusPagePluginAdmin {

	public function action(&$handled) {
	}

	public function init($html=true) {
	}

	public function gui($id, &$out, &$handled) {
		$parts = explode('.',$id,2);
		if (!isset($parts[1])) $parts[1] = '';
		if ($parts[0] != 'oidplus:software_update') return;
		$handled = true;
		$out['title'] = 'Software update';
		$out['icon']  = OIDplus::webpath(__DIR__).'icon_big.png';

		if (!OIDplus::authUtils()::isAdminLoggedIn()) {
			$out['icon'] = 'img/error_big.png';
			$out['text'] = '<p>You need to <a '.OIDplus::gui()->link('oidplus:login').'>log in</a> as administrator.</p>';
			return;
		}

		$out['text']  = '<p>You can perform a system update by clicking the bottom below.</p>';
		$out['text'] .= '<p><input type="button" onclick="document.location=\'update/\'" value="Start update assistant"></p>';
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (!OIDplus::authUtils()::isAdminLoggedIn()) return false;
		
		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = OIDplus::webpath(__DIR__).'treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:software_update',
			'icon' => $tree_icon,
			'text' => 'Software update'
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}
}
