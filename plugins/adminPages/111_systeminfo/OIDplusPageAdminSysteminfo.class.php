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

class OIDplusPageAdminSysteminfo extends OIDplusPagePluginAdmin {

	public function action($actionID, $params) {
	}

	public function init($html=true) {
	}

	public function gui($id, &$out, &$handled) {
		if ($id === 'oidplus:systeminfo') {
			$handled = true;
			$out['title'] = 'System information';
			$out['icon']  = OIDplus::webpath(__DIR__).'icon_big.png';

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>You need to <a '.OIDplus::gui()->link('oidplus:login').'>log in</a> as administrator.</p>';
				return;
			}

			$out['text']  = '';

			# ---

			$out['text'] .= '<h2>OIDplus</h2>';
			$sysid_oid = OIDplus::getSystemId(true);
			if (!$sysid_oid) $sysid_oid = 'unknown';
			$out['text'] .= '<p>System OID: '.$sysid_oid.'</p>';

			$sys_url = OIDplus::getSystemUrl();
			$out['text'] .= '<p>System URL: '.$sys_url.'</p>';

			$sys_ver = OIDplus::getVersion();
			if (!$sys_ver) $sys_ver = 'unknown';
			$out['text'] .= '<p>System version: '.$sys_ver.'</p>';

			$sys_install_type = OIDplus::getInstallType();
			$out['text'] .= '<p>Installation type: '.$sys_install_type.'</p>';

			$sys_title = OIDplus::config()->getValue('system_title');
			$out['text'] .= '<p>System title: '.$sys_title.'</p>';

			# ---

			$out['text'] .= '<h2>PHP</h2>';
			$out['text'] .= '<p>PHP version: '.phpversion().'</p>';

			# ---

			$out['text'] .= '<h2>Webserver</h2>';
			$out['text'] .= '<p>Server software: '.(isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'unknown').'</p>';

			# ---

			$out['text'] .= '<h2>Database</h2>';
			$out['text'] .= '<p>Database provider: '.get_class(OIDplus::db()).'</p>';
			$out['text'] .= '<p>SQL slang: '.get_class(OIDplus::db()->getSlang()).'</p>';

			// TODO: can we somehow get the DBMS version, connection string etc?

			# ---

		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (!OIDplus::authUtils()::isAdminLoggedIn()) return false;

		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = OIDplus::webpath(__DIR__).'treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:systeminfo',
			'icon' => $tree_icon,
			'text' => 'System information'
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}

	public function implementsFeature($id) {
		return false;
	}
}
