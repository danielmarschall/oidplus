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

class OIDplusPageAdminLogEvents extends OIDplusPagePluginAdmin {

	public function action(&$handled) {
	}

	public function init($html=true) {
	}

	public function gui($id, &$out, &$handled) {
		if ($id == 'oidplus:system_log') {
			$handled = true;
			$out['title'] = "All log messages";
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>You need to <a '.OIDplus::gui()->link('oidplus:login').'>log in</a> as administrator.</p>';
				return;
			}

			$res = OIDplus::db()->query("select lo.id, lo.unix_ts, lo.addr, lo.event from ###log lo ".
			                            "order by lo.unix_ts desc");
			if ($res->num_rows() > 0) {
				$out['text'] = '<pre>';
				while ($row = $res->fetch_array()) {
					$severity = 0;
					// ---
					$users = array();
					$res2 = OIDplus::db()->query("select username, severity from ###log_user ".
					                             "where log_id = ?", array($row['id']));
					while ($row2 = $res2->fetch_array()) {
						$users[] = $row2['username'];
						if ($row2['username'] == 'admin') $severity = $row2['severity'];
					}
					$users = count($users) > 0 ? "; affected users: ".implode(', ',$users) : '';
					// ---
					$objects = array();
					$res2 = OIDplus::db()->query("select object, severity from ###log_object ".
					                             "where log_id = ?", array($row['id']));
					while ($row2 = $res2->fetch_array()) {
						$objects[] = $row2['object'];
					}
					$objects = count($objects) > 0 ? "; affected objects: ".implode(', ',$objects) : '';
					// ---
					$addr = empty($row['addr']) ? 'no address' : $row['addr'];
					// ---
					$out['text'] .= '<span class="severity_'.$severity.'">' . date('Y-m-d H:i:s', $row['unix_ts']) . ': ' . htmlentities($row["event"])." (" . htmlentities($addr.$users.$objects) . ")</span>\n";
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
		if (!OIDplus::authUtils()::isAdminLoggedIn()) return false;

		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = OIDplus::webpath(__DIR__).'treeicon.png';
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
