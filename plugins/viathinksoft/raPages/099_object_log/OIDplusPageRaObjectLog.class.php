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

class OIDplusPageRaObjectLog extends OIDplusPagePluginRa
	implements INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_2 /* modifyContent */
{

	/**
	 * @param bool $html
	 * @return void
	 */
	public function init(bool $html=true) {
	}

	/**
	 * @param string $id
	 * @param array $out
	 * @param bool $handled
	 * @return void
	 */
	public function gui(string $id, array &$out, bool &$handled) {
	}

	/**
	 * @param array $json
	 * @param string|null $ra_email
	 * @param bool $nonjs
	 * @param string $req_goto
	 * @return bool
	 */
	public function tree(array &$json, string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
		//if (!$ra_email) return false;
		//if (!OIDplus::authUtils()->isRaLoggedIn($ra_email) && !OIDplus::authUtils()->isAdminLoggedIn()) return false;

		return false;
	}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_2
	 * @param string $id
	 * @param string $title
	 * @param string $icon
	 * @param string $text
	 * @return void
	 * @throws \ViaThinkSoft\OIDplus\OIDplusException
	 */
	public function modifyContent(string $id, string &$title, string &$icon, string &$text) {
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
		if ($res->any()) {
			$text .= '<pre>';
			while ($row = $res->fetch_array()) {
				$users = array();
				$res2 = OIDplus::db()->query("select username, severity from ###log_user ".
				                             "where log_id = ?", array((int)$row['id']));
				while ($row2 = $res2->fetch_array()) {
					$users[] = $row2['username'];
				}
				$users = count($users) > 0 ? ", ".implode('/',$users) : '';

				$addr = empty($row['addr']) ? _L('no address') : $row['addr'];

				$text .= '<span class="severity_'.$row['severity'].'">' . date('Y-m-d H:i:s', $row['unix_ts']) . ': ' . htmlentities($row["event"]??'')." (" . htmlentities($addr.$users) . ")</span>\n";
			}
			$text .= '</pre>';

			// TODO: List logs in a table instead of a <pre> text
			// TODO: Load only X events and then re-load new events via AJAX when the user scrolls down
		} else {
			$text .= '<p>'._L('Currently there are no log entries').'</p>';
		}

	}

	/**
	 * @param string $request
	 * @return array|false
	 */
	public function tree_search(string $request) {
		return false;
	}
}
