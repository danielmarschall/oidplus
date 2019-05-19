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

class OIDplusPageRaLogEvents extends OIDplusPagePlugin {
	public function type() {
		return 'ra';
	}

	public function priority() {
		return 200;
	}

	public function action(&$handled) {
	}

	public function init($html=true) {
	}

	public function cfgSetValue($name, $value) {
	}

	public function gui($id, &$out, &$handled) {
		if (explode('$',$id)[0] == 'oidplus:ra_log') {
			$handled = true;

			$ra_email = explode('$',$id)[1];

			$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = '".OIDplus::db()->real_escape_string($ra_email)."'");
			if (OIDplus::db()->num_rows($res) == 0) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = 'RA <b>'.htmlentities($ra_email).'</b> does not exist';
				return $out;
			}

			if (!OIDplus::authUtils()::isRaLoggedIn($ra_email) && !OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] .= '<p>You need to <a '.oidplus_link('oidplus:login').'>log in</a> as the requested RA <b>'.htmlentities($ra_email).'</b>.</p>';
				return $out;
			}

			$out['title'] = "Log entries for RA $ra_email";
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/raPages/'.basename(__DIR__).'/icon_big.png' : '';

			$res = OIDplus::db()->query("select lo.unix_ts, lo.addr, lo.event from ".OIDPLUS_TABLENAME_PREFIX."log lo ".
			                            "left join ".OIDPLUS_TABLENAME_PREFIX."log_user lu on lu.log_id = lo.id ".
			                            "where lu.user = '".OIDplus::db()->real_escape_string($ra_email)."' " .
										"order by lo.unix_ts desc");
			if (OIDplus::db()->num_rows($res) > 0) {
				$out['text'] = '<pre>';
				while ($row = OIDplus::db()->fetch_array($res)) {
					$addr = empty($row['addr']) ? 'no address' : $row['addr'];

					$out['text'] .= date('Y-m-d H:i:s', $row['unix_ts']) . ': ' . htmlentities($row["event"])." (" . htmlentities($addr) . ")\n";
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
			$tree_icon = 'plugins/raPages/'.basename(__DIR__).'/treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:ra_log$'.$ra_email,
			'icon' => $tree_icon,
			'text' => 'RA log events'
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}
}

OIDplus::registerPagePlugin(new OIDplusPageRaLogEvents());
