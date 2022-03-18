<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2021 Daniel Marschall, ViaThinkSoft
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

if (!defined('INSIDE_OIDPLUS')) die();

class OIDplusPageRaLogEvents extends OIDplusPagePluginRa {

	public function init($html=true) {
	}

	public function gui($id, &$out, &$handled) {
		if (explode('$',$id)[0] == 'oidplus:ra_log') {
			$handled = true;

			$ra_email = explode('$',$id)[1];

			$out['title'] = _L('Log messages for RA %1',$ra_email);
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';

			if (!OIDplus::authUtils()->isRaLoggedIn($ra_email) && !OIDplus::authUtils()->isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>'._L('You need to <a %1>log in</a> as the requested RA %2.',OIDplus::gui()->link('oidplus:login$ra$'.$ra_email),'<b>'.htmlentities($ra_email).'</b>').'</p>';
				return;
			}

			$res = OIDplus::db()->query("select * from ###ra where email = ?", array($ra_email));
			if (!$res->any()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = _L('RA "%1" does not exist','<b>'.htmlentities($ra_email).'</b>');
				return;
			}

			$res = OIDplus::db()->query("select lo.unix_ts, lo.addr, lo.event, lu.severity from ###log lo ".
			                            "left join ###log_user lu on lu.log_id = lo.id ".
			                            "where lu.username = ? " .
			                            "order by lo.unix_ts desc", array($ra_email));
			if ($res->any()) {
				$out['text'] = '<pre>';
				while ($row = $res->fetch_array()) {
					$addr = empty($row['addr']) ? _L('no address') : $row['addr'];

					$out['text'] .= '<span class="severity_'.$row['severity'].'">' . date('Y-m-d H:i:s', $row['unix_ts']) . ': ' . htmlentities($row["event"])." (" . htmlentities($addr) . ")</span>\n";
				}
				$out['text'] .= '</pre>';
			} else {
				$out['text'] .= '<p>'._L('Currently there are no log entries').'</p>';
			}

			// TODO: List logs in a table instead of a <pre> text
			// TODO: Load only X events and then re-load new events via AJAX when the user scrolls down
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (!$ra_email) return false;
		if (!OIDplus::authUtils()->isRaLoggedIn($ra_email) && !OIDplus::authUtils()->isAdminLoggedIn()) return false;

		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = OIDplus::webpath(__DIR__).'treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:ra_log$'.$ra_email,
			'icon' => $tree_icon,
			'text' => _L('RA log events')
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}
}