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

class OIDplusPageAdminLogEvents extends OIDplusPagePlugin {
	public function type() {
		return 'admin';
	}

	public function priority() {
		return 600;
	}

	public function action(&$handled) {
	}

	public function init($html=true) {
	}

	public function cfgSetValue($name, $value) {
	}

	public function gui($id, &$out, &$handled) {
		if ($id == 'oidplus:system_log') {
			$handled = true;
			$out['title'] = "All log messages";
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/icon_big.png' : '';

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>You need to <a '.oidplus_link('oidplus:login').'>log in</a> as administrator.</p>';
				return $out;
			}

			$res = OIDplus::db()->query("select lo.id, lo.unix_ts, lo.addr, lo.event from ".OIDPLUS_TABLENAME_PREFIX."log lo ".
			                            "left join ".OIDPLUS_TABLENAME_PREFIX."log_user lu on lu.log_id = lo.id ".
			                            //"where lu.user = 'admin' " .
			                            "order by lo.unix_ts desc");
			if (OIDplus::db()->num_rows($res) > 0) {
				$out['text'] = '<pre>';
				while ($row = OIDplus::db()->fetch_array($res)) {
					$users = array();
					$res2 = OIDplus::db()->query("select user from ".OIDPLUS_TABLENAME_PREFIX."log_user ".
					                             "where log_id = ?", array($row['id']));
					while ($row2 = OIDplus::db()->fetch_array($res2)) {
						$users[] = $row2['user'];
					}
					$users = count($users) > 0 ? ", ".implode('/',$users) : '';

					$addr = empty($row['addr']) ? 'no address' : $row['addr'];

					$out['text'] .= date('Y-m-d H:i:s', $row['unix_ts']) . ': ' . htmlentities($row["event"])." (" . htmlentities($addr.$users) . ")\n";
				}
				$out['text'] .= '</pre>';
			} else {
				$out['text'] .= '<p>Currently there are no log entries</p>';
			}

			// TODO: List logs in a table instead of a <pre> text
			// TODO: Load only X events and then re-load new events via AJAX when the user scrolls down
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:system_log',
			'icon' => $tree_icon,
			'text' => 'All log events'
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}
}

OIDplus::registerPagePlugin(new OIDplusPageAdminLogEvents());
