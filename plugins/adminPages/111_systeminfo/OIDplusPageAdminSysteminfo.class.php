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
			$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
			$out['text'] .= '<table class="table table-bordered table-striped">';
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<th width="50%">Attribute</th>';
			$out['text'] .= '		<th width="50%">Value</th>';
			$out['text'] .= '	</tr>';
			$out['text'] .= '	<tr>';
			$sysid_oid = OIDplus::getSystemId(true);
			if (!$sysid_oid) $sysid_oid = 'unknown';
			$out['text'] .= '		<td>System OID</td>';
			$out['text'] .= '		<td>'.$sysid_oid.'</td>';
			$out['text'] .= '	</tr>';
			$sys_url = OIDplus::getSystemUrl();
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>System URL</td>';
			$out['text'] .= '		<td>'.$sys_url.'</td>';
			$out['text'] .= '	</tr>';
			$sys_ver = OIDplus::getVersion();
			if (!$sys_ver) $sys_ver = 'unknown';
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>System version</td>';
			$out['text'] .= '		<td>'.$sys_ver.'</td>';
			$out['text'] .= '	</tr>';
			$sys_install_type = OIDplus::getInstallType();
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>Installation type</td>';
			$out['text'] .= '		<td>'.$sys_install_type.'</td>';
			$out['text'] .= '	</tr>';
			$sys_title = OIDplus::config()->getValue('system_title');
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>System title</td>';
			$out['text'] .= '		<td>'.$sys_title.'</td>';
			$out['text'] .= '	</tr>';
			$out['text'] .= '</table>';
			$out['text'] .= '</div></div>';

			# ---

			$out['text'] .= '<h2>PHP</h2>';
			$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
			$out['text'] .= '<table class="table table-bordered table-striped">';
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<th width="50%">Attribute</th>';
			$out['text'] .= '		<th width="50%">Value</th>';
			$out['text'] .= '	</tr>';
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>PHP version</td>';
			$out['text'] .= '		<td>'.phpversion().'</td>';
			$out['text'] .= '	</tr>';
			$out['text'] .= '</table>';
			$out['text'] .= '</div></div>';

			# ---

			$out['text'] .= '<h2>Webserver</h2>';
			$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
			$out['text'] .= '<table class="table table-bordered table-striped">';
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<th width="50%">Attribute</th>';
			$out['text'] .= '		<th width="50%">Value</th>';
			$out['text'] .= '	</tr>';
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>Server software</td>';
			$out['text'] .= '		<td>'.(isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'unknown').'</td>';
			$out['text'] .= '	</tr>';
			$out['text'] .= '</table>';
			$out['text'] .= '</div></div>';

			# ---

			$out['text'] .= '<h2>Database</h2>';
			$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
			$out['text'] .= '<table class="table table-bordered table-striped">';
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<th width="50%">Attribute</th>';
			$out['text'] .= '		<th width="50%">Value</th>';
			$out['text'] .= '	</tr>';
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>Database provider</td>';
			$out['text'] .= '		<td>'.OIDplus::db()->getPlugin()->getManifest()->getName().'</td>';
			$out['text'] .= '	</tr>';
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>SQL slang</td>';
			$out['text'] .= '		<td>'.OIDplus::db()->getSlang()->getManifest()->getName().'</td>';
			$out['text'] .= '	</tr>';
			$out['text'] .= '</table>';
			$out['text'] .= '</div></div>';

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
