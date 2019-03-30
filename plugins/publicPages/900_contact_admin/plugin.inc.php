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

class OIDplusPagePublicContactEMail extends OIDplusPagePlugin {
	public function type() {
		return 'public';
	}

	public function priority() {
		return 900;
	}

	public function action(&$handled) {
		// Nothing
	}

	public function cfgLoadConfig() {
		// Nothing
	}

	public function cfgSetValue($name, $value) {
		// Nothing
	}

	public function gui($id, &$out, &$handled) {
		if ($id === 'oidplus:contact') {
			$handled = true;
			$out['title'] = 'Contact system admin';
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/publicPages/'.basename(__DIR__).'/icon_big.png' : '';

			if (empty(OIDPLUS_ADMIN_EMAIL)) {
				$out['text'] = '<p>The administrator of this system has not entered a contact email address.';
			} else {
				$out['text'] = '<p>You can contact the administrator of this OIDplus system at this email address:</p><p><a href="mailto:'.htmlentities(OIDPLUS_ADMIN_EMAIL).'">'.htmlentities(OIDPLUS_ADMIN_EMAIL).'</a></p>';
			}

			if (OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['text'] .= '<p><abbr title="Edit the file includes/config.inc.php">How to change this address?</abbr></p>';
			}
		}
	}

	public function tree(&$json, $ra_email=null) {
		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = 'plugins/publicPages/'.basename(__DIR__).'/treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:contact',
			'icon' => $tree_icon,
			'text' => 'Contact sysadmin'
		);
	}
}

OIDplus::registerPagePlugin(new OIDplusPagePublicContactEMail());
