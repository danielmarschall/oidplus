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

class OIDplusPageAdminSysteminfo extends OIDplusPagePluginAdmin {

	/**
	 * @param string $actionID
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 */
	public function action(string $actionID, array $params): array {
		return parent::action($actionID, $params);
	}

	/**
	 * @param bool $html
	 * @return void
	 */
	public function init(bool $html=true) {
	}

	/**
	 * @return array|mixed|string|string[]
	 */
	private function getLoadedInis() {
		$s_inis = '';

		$inis = array();
		$main_ini = php_ini_loaded_file();
		if ($main_ini !== false) $s_inis = '<b>'.htmlentities($main_ini).'</b>';

		$more_ini = php_ini_scanned_files();
		if ($more_ini !== false) {
			$inis = explode(',', $more_ini);
			foreach ($inis as $ini) {
				$s_inis .= '<br>'.htmlentities($ini);
			}
		}

		if ($s_inis == '') $s_inis = _L('n/a');

		return $s_inis;
	}

	/**
	 * @param string $id
	 * @param array $out
	 * @param bool $handled
	 * @return void
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	public function gui(string $id, array &$out, bool &$handled) {
		if ($id === 'oidplus:phpinfo') {
			$handled = true;
			$out['title'] = _L('PHP information');
			$out['icon']  = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/php_icon.png';

			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')), $out['title'], 401);
			}

			$out['text'] = '<p><a '.OIDplus::gui()->link('oidplus:systeminfo').'><img src="img/arrow_back.png" width="16" alt="'._L('Go back').'"> '._L('Go back to the system information page').'</a></p>';

			ob_start();
			$res = phpinfo();
			$cont = ob_get_contents();
			ob_end_clean();

			if (!$res) {
				$out['text'] .= '<p><font color="red">'._L('phpinfo() could not be called').'</font></p>';
			} else {
				// phpinfo() sets "img {float: right; border: 0;}". We don't want that.
				$cont = str_replace('img {', 'img.phpinfo {', $cont);
				$cont = str_replace('<img', '<img class="phpinfo"', $cont);

				// phpinfo() sets the link colors. We don't want that
				$cont = preg_replace('@a:.+ {.+}@ismU', '', $cont);

				// phpinfo() sets "h1 {font-size: 150%;}" and "h2 {font-size: 125%;}"
				$cont = preg_replace('@(h1|h2|h3|h4|h5) {.+}@ismU', '', $cont);

				// Make compatible for dark themes by removing all foreground and background colors
				$cont = preg_replace('@(body) {.+}@ismU', '', $cont, 1);
				$cont = preg_replace('@background-color:(.+)[\\};]@ismU', '', $cont);
				$cont = '<span style="font-family: sans-serif;">'.$cont.'</span>';

				// Prevent that dark-color scheme makes the font white in a non-dark-color OIDplus
				$cont = str_replace('prefers-color-scheme', 'xxx', $cont);

				// Font sizes
				$cont = preg_replace('@font-size:\\s*75%;@', '', $cont);
				for ($i=5; $i>=1; $i--) {
					$cont = str_replace('<h'.$i, '<h'.($i+1), $cont);
					$cont = str_replace('</h'.$i, '</h'.($i+1), $cont);
				}

				$out['text'] .= $cont;
			}
		}
		else if ($id === 'oidplus:systeminfo') {
			$handled = true;
			$out['title'] = _L('System information');
			$out['icon']  = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png';

			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')), $out['title'], 401);
			}

			$out['text']  = '';

			# ---

			$out['text'] .= '<h2>'._L('OIDplus 2.0').'</h2>';
			$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
			$out['text'] .= '<table class="table table-bordered table-striped">';
			$out['text'] .= '<thead>';
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<th width="50%">'._L('Attribute').'</th>';
			$out['text'] .= '		<th width="50%">'._L('Value').'</th>';
			$out['text'] .= '	</tr>';
			$out['text'] .= '</thead>';
			$out['text'] .= '<tbody>';

			$sys_title = OIDplus::config()->getValue('system_title');
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>'._L('System title').'</td>';
			$out['text'] .= '		<td>'.htmlentities($sys_title).'</td>';
			$out['text'] .= '	</tr>';

			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>'._L('System directory').'</td>';
			$out['text'] .= '		<td>'.(isset($_SERVER['SCRIPT_FILENAME']) ? htmlentities(dirname($_SERVER['SCRIPT_FILENAME'])) : '<i>'._L('unknown').'</i>').'</td>';
			$out['text'] .= '	</tr>';

			$sys_url = OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL);
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>'._L('System URL').'</td>';
			$out['text'] .= '		<td>'.htmlentities($sys_url).'</td>';
			$out['text'] .= '	</tr>';

			$sysid_oid = OIDplus::getSystemId(true);
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>'._L('System OID').' <abbr title="'._L('OID based on the public key of your OIDplus system. The last arc is also called OIDplus System ID.').'">(?)</abbr></td>';
			$out['text'] .= '		<td>'.(!$sysid_oid ? '<i>'._L('unknown').'</i>' : htmlentities($sysid_oid)).'</td>';
			$out['text'] .= '	</tr>';

			$sysid_guid = OIDplus::getSystemGuid();
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>'._L('System GUID').' <abbr title="'._L('UUIDv8 based on the System ID of your OIDplus system.').'">(?)</abbr></td>';
			$out['text'] .= '		<td>'.(!$sysid_guid ? '<i>'._L('unknown').'</i>' : htmlentities($sysid_guid)).'</td>';
			$out['text'] .= '	</tr>';

			$sysid = OIDplus::getSystemId(false);
			$sysid_aid = $sysid ? 'D276000186B20005'.strtoupper(str_pad(dechex((int)$sysid),8,'0',STR_PAD_LEFT)) : '';
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>'._L('System AID').' <abbr title="'._L('Application Identifier (ISO/IEC 7816) based on the system ID (which is based on the hash of the public key of your OIDplus system).').'">(?)</abbr></td>';
			$out['text'] .= '		<td>'.(!$sysid_aid ? '<i>'._L('unknown').'</i>' : htmlentities($sysid_aid)).' ('._L('No PIX allowed').')</td>';
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

			$out['text'] .= '</tbody>';
			$out['text'] .= '</table>';
			$out['text'] .= '</div></div>';

			# ---

			$out['text'] .= '<h2>'._L('Webserver system').'</h2>';
			$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
			$out['text'] .= '<table class="table table-bordered table-striped">';
			$out['text'] .= '<thead>';
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<th width="50%">'._L('Attribute').'</th>';
			$out['text'] .= '		<th width="50%">'._L('Value').'</th>';
			$out['text'] .= '	</tr>';
			$out['text'] .= '</thead>';
			$out['text'] .= '<tbody>';

			// Operating system (of the webserver)

			if (php_uname("m") == php_uname("n")) {
				// At some hosts like Strato, php_uname() always returns the same string
				// "Linux localhost 3.10.0-1127.10.1.el7.x86_64 #1 SMP"
				$out['text'] .= '	<tr>';
				$out['text'] .= '		<td>'._L('Operating System').'</td>';
				$out['text'] .= '		<td>'.htmlentities(PHP_OS).'</td>';
				$out['text'] .= '	</tr>';
				$out['text'] .= '	<tr>';
				$out['text'] .= '		<td>'._L('Hostname').'</td>';
				$out['text'] .= '		<td>'.htmlentities(gethostname()).'</td>';
				$out['text'] .= '	</tr>';
			} else {
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
			}
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>'._L('Server time').'</td>';
			$out['text'] .= '		<td>'.htmlentities(date('Y-m-d H:i:s P')).'</td>';
			$out['text'] .= '	</tr>';

			// The actual web server stuff

			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>'._L('Web server software').'</td>';
			$out['text'] .= '		<td>'.($_SERVER['SERVER_SOFTWARE'] ?? '<i>' . _L('unknown') . '</i>').'</td>';
			$out['text'] .= '	</tr>';
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>'._L('Web server user account').'</td>';
			$current_user = get_own_username();  // TODO: should we also show the group?
			$out['text'] .= '		<td>'.($current_user === false ? '<i>'._L('unknown').'</i>' : htmlentities($current_user)).'</td>';
			$out['text'] .= '	</tr>';

			// PHP (at webserver)

			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>'._L('PHP version').'</td>';
			$out['text'] .= '		<td>'.PHP_VERSION.'</td>';
			$out['text'] .= '	</tr>';
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>'._L('PHP configuration file(s)').'</td>';
			$out['text'] .= '		<td>'.$this->getLoadedInis().'</td>';
			$out['text'] .= '	</tr>';
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>'._L('PHP installed extensions').'</td>';
			$out['text'] .= '		<td>'.htmlentities(implode(', ',get_loaded_extensions())).'</td>';
			$out['text'] .= '	</tr>';
			$out['text'] .= '</tbody>';
			$out['text'] .= '</table>';

			$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:phpinfo').'>'._L('Show PHP server configuration (phpinfo)').'</a></p>';

			$out['text'] .= '</div></div>';

			# ---

			$out['text'] .= '<h2>'._L('Database system').'</h2>';
			$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
			$out['text'] .= '<table class="table table-bordered table-striped">';
			$out['text'] .= '<thead>';
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<th width="50%">'._L('Attribute').'</th>';
			$out['text'] .= '		<th width="50%">'._L('Value').'</th>';
			$out['text'] .= '	</tr>';
			$out['text'] .= '</thead>';
			$out['text'] .= '<tbody>';

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
			$out['text'] .= '	<tr>';
			$out['text'] .= '		<td>'._L('Server time').'</td>';
			// We use "from ###config" because Oracle DB requires a "from" statement.
			// Instead of creating two queries (one with "select ..." and one with "select ... from dual"),
			// we make this query. It is OK, because the table ###config is never empty and we are only fetching the first row.
			$tmp = OIDplus::db()->query('select '.OIDplus::db()->sqlDate().' as tmp from ###config');
			if ($tmp) $tmp = $tmp->fetch_array();
			$tmp = $tmp['tmp'] ?? _L('n/a');
			$tmp = preg_replace('@\\.\\d{3}$@', '', $tmp); // remove milliseconds of Microsoft SQL Server
			$out['text'] .= '		<td>'.$tmp.'</td>';
			$out['text'] .= '	</tr>';

			$infos = OIDplus::db()->getExtendedInfo();
			foreach ($infos as $name => $val) {
				$out['text'] .= '	<tr>';
				$out['text'] .= '		<td>' . htmlentities($name) . '</td>';
				$out['text'] .= '		<td>' . htmlentities($val ?? '') . '</td>';
				$out['text'] .= '	</tr>';
			}

			$out['text'] .= '</tbody>';
			$out['text'] .= '</table>';
			$out['text'] .= '</div></div>';

			# ---

		}
	}

	/**
	 * @param array $json
	 * @param string|null $ra_email
	 * @param bool $nonjs
	 * @param string $req_goto
	 * @return bool
	 * @throws OIDplusException
	 */
	public function tree(array &$json, string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
		if (!OIDplus::authUtils()->isAdminLoggedIn()) return false;

		if (file_exists(__DIR__.'/img/main_icon16.png')) {
			$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		if (file_exists(__DIR__.'/img/php_icon16.png')) {
			$tree_icon_php = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/php_icon16.png';
		} else {
			$tree_icon_php = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:systeminfo',
			'icon' => $tree_icon,
			'text' => _L('System information'),
			'children' => array(array(
				'id' => 'oidplus:phpinfo',
				'icon' => $tree_icon_php,
				'text' => _L('PHP information')
			))
		);

		return true;
	}

	/**
	 * @param string $request
	 * @return array|false
	 */
	public function tree_search(string $request) {
		return false;
	}

}
