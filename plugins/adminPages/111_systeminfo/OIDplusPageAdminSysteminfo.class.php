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
			$out['title'] = _L('System information');
			$out['icon']  = OIDplus::webpath(__DIR__).'icon_big.png';

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>'._L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login')).'</p>';
				return;
			}

			$out['text']  = '';

			# ---

			$out['text'] .= '<h2>'._L('OIDplus').'</h2>';
			$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
			$out['text'] .= '<table class="table table-bordered table-striped">';
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<th width="50%">'._L('Attribute').'</th>';
			$out['text'] .= '		<th width="50%">'._L('Value').'</th>';
			$out['text'] .= '	</tr>';
			$out['text'] .= '	<tr>';

			$sysid_oid = OIDplus::getSystemId(true);
			$out['text'] .= '		<td>'._L('System OID').'</td>';
			$out['text'] .= '		<td>'.(!$sysid_oid ? '<i>'._L('unknown').'</i>' : htmlentities($sysid_oid)).'</td>';
			$out['text'] .= '	</tr>';

			$sys_url = OIDplus::webpath();
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>'._L('System URL').'</td>';
			$out['text'] .= '		<td>'.htmlentities($sys_url).'</td>';
			$out['text'] .= '	</tr>';

			$sys_ver = OIDplus::getVersion();
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>'._L('System version').'</td>';
			$out['text'] .= '		<td>'.(!$sys_ver ? '<i>'._L('unknown').'</i>' : htmlentities($sys_ver)).'</td>';
			$out['text'] .= '	</tr>';

			$sys_install_type = OIDplus::getInstallType();
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>'._L('Installation type').'</td>';
			$out['text'] .= '		<td>'.htmlentities($sys_install_type).'</td>';
			$out['text'] .= '	</tr>';

			$sys_title = OIDplus::config()->getValue('system_title');
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>'._L('System title').'</td>';
			$out['text'] .= '		<td>'.htmlentities($sys_title).'</td>';
			$out['text'] .= '	</tr>';

			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>'._L('System directory').'</td>';
			$out['text'] .= '		<td>'.(isset($_SERVER['SCRIPT_FILENAME']) ? htmlentities(dirname($_SERVER['SCRIPT_FILENAME'])) : '<i>'._L('unknown').'</i>').'</td>';
			$out['text'] .= '	</tr>';

			$out['text'] .= '</table>';
			$out['text'] .= '</div></div>';

			# ---

			$out['text'] .= '<h2>'._L('PHP').'</h2>';
			$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
			$out['text'] .= '<table class="table table-bordered table-striped">';
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<th width="50%">'._L('Attribute').'</th>';
			$out['text'] .= '		<th width="50%">'._L('Value').'</th>';
			$out['text'] .= '	</tr>';
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>'._L('PHP version').'</td>';
			$out['text'] .= '		<td>'.PHP_VERSION.'</td>';
			$out['text'] .= '	</tr>';
			$out['text'] .= '</table>';
			$out['text'] .= '</div></div>';

			# ---

			$out['text'] .= '<h2>'._L('Webserver').'</h2>';
			$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
			$out['text'] .= '<table class="table table-bordered table-striped">';
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<th width="50%">'._L('Attribute').'</th>';
			$out['text'] .= '		<th width="50%">'._L('Value').'</th>';
			$out['text'] .= '	</tr>';
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>'._L('Server software').'</td>';
			$out['text'] .= '		<td>'.(isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '<i>'._L('unknown').'</i>').'</td>';
			$out['text'] .= '	</tr>';
			$out['text'] .= '</table>';
			$out['text'] .= '</div></div>';

			# ---

			$out['text'] .= '<h2>'._L('Operating System').'</h2>';
			$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
			$out['text'] .= '<table class="table table-bordered table-striped">';
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<th width="50%">'._L('Attribute').'</th>';
			$out['text'] .= '		<th width="50%">'._L('Value').'</th>';
			$out['text'] .= '	</tr>';
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>'._L('Operating System').'</td>';
			$out['text'] .= '		<td>'.htmlentities(php_uname("s").' '.php_uname("r").' '.php_uname("v")).'</td>';
			$out['text'] .= '	</tr>';
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>'._L('Machine type').'</td>';
			$out['text'] .= '		<td>'.htmlentities(php_uname("m")).'</td>';
			$out['text'] .= '	</tr>';
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>'._L('Hostname').'</td>';
			$out['text'] .= '		<td>'.htmlentities(php_uname("n")).'</td>';
			$out['text'] .= '	</tr>';
			$out['text'] .= '</table>';
			$out['text'] .= '</div></div>';

			# ---

			$out['text'] .= '<h2>'._L('Database').'</h2>';
			$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
			$out['text'] .= '<table class="table table-bordered table-striped">';
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<th width="50%">'._L('Attribute').'</th>';
			$out['text'] .= '		<th width="50%">'._L('Value').'</th>';
			$out['text'] .= '	</tr>';

			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>'._L('Database provider').'</td>';
			$out['text'] .= '		<td>'.OIDplus::db()->getPlugin()->getManifest()->getName().'</td>';
			$out['text'] .= '	</tr>';

			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>'._L('SQL slang').'</td>';
			$out['text'] .= '		<td>'.OIDplus::db()->getSlang()->getManifest()->getName().'</td>';
			$out['text'] .= '	</tr>';

			$table_prefix = OIDplus::baseConfig()->getValue('TABLENAME_PREFIX');
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>'._L('Table name prefix').'</td>';
			$out['text'] .= '		<td>'.(!empty($table_prefix) ? htmlentities($table_prefix) : '<i>'._L('none').'</i>').'</td>';
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
			'text' => _L('System information')
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