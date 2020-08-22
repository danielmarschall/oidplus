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

class OIDplusPageRaObjectLog extends OIDplusPagePluginRa {

	public function init($html=true) {
	}

	public function gui($id, &$out, &$handled) {
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		//if (!$ra_email) return false;
		//if (!OIDplus::authUtils()::isRaLoggedIn($ra_email) && !OIDplus::authUtils()::isAdminLoggedIn()) return false;

		return false;
	}

	public function implementsFeature($id) {
		if (strtolower($id) == '1.3.6.1.4.1.37476.2.5.2.3.2') return true; // modifyContent
		return false;
	}

	public function modifyContent($id, &$title, &$icon, &$text) {
		// Interface 1.3.6.1.4.1.37476.2.5.2.3.2

		$obj = OIDplusObject::parse($id);
		if (!$obj) return;
		if (!$obj->userHasWriteRights()) return;

		// TODO: I want that this content comes before the WHOIS modifyContent.
		//       The problem is that first all public and then all RA plugins get loaded, not mixed by their priority
		$res = OIDplus::db()->query("select lo.id, lo.unix_ts, lo.addr, lo.event, lu.severity from ###log lo ".
		                            "left join ###log_object lu on lu.log_id = lo.id ".
		                            "where lu.object = ? " .
		                            "order by lo.unix_ts desc", array($id));
		$text .= '<h2>'._L('Log messages for object %1',htmlentities($id)).'</h2>';
		if ($res->num_rows() > 0) {
			$text .= '<pre>';
			while ($row = $res->fetch_array()) {
				$users = array();
				$res2 = OIDplus::db()->query("select username, severity from ###log_user ".
				                             "where log_id = ?", array($row['id']));
				while ($row2 = $res2->fetch_array()) {
					$users[] = $row2['username'];
				}
				$users = count($users) > 0 ? ", ".implode('/',$users) : '';

				$addr = empty($row['addr']) ? _L('no address') : $row['addr'];

				$text .= '<span class="severity_'.$row['severity'].'">' . date('Y-m-d H:i:s', $row['unix_ts']) . ': ' . htmlentities($row["event"])." (" . htmlentities($addr.$users) . ")</span>\n";
			}
			$text .= '</pre>';

			// TODO: List logs in a table instead of a <pre> text
			// TODO: Load only X events and then re-load new events via AJAX when the user scrolls down
		} else {
			$text .= '<p>'._L('Currently there are no log entries').'</p>';
		}

	}

	public function tree_search($request) {
		return false;
	}
}