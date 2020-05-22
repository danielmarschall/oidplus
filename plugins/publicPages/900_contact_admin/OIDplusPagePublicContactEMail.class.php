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

class OIDplusPagePublicContactEMail extends OIDplusPagePluginPublic {

	public function init($html=true) {
		// Nothing
	}

	public function gui($id, &$out, &$handled) {
		if ($id === 'oidplus:contact') {
			$handled = true;
			$out['title'] = 'Contact administrator';
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';

			if (empty(OIDplus::config()->getValue('admin_email'))) {
				$out['text'] = '<p>The administrator of this OIDplus system has not entered a contact email address.';
			} else {
				$out['text'] = '<p>You can contact the administrator of this OIDplus system at this email address:</p><p><a href="mailto:'.htmlentities(OIDplus::config()->getValue('admin_email')).'">'.htmlentities(OIDplus::config()->getValue('admin_email')).'</a></p>';
			}
		}
	}

	public function publicSitemap(&$out) {
		$out[] = OIDplus::getSystemUrl().'?goto='.urlencode('oidplus:contact');
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = OIDplus::webpath(__DIR__).'treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:contact',
			'icon' => $tree_icon,
			'text' => 'Contact administrator'
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}
}
