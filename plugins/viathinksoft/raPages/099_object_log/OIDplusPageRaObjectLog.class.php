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

namespace ViaThinkSoft\OIDplus\Plugins\viathinksoft\raPages\n099_object_log;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusObject;
use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginRa;
use ViaThinkSoft\OIDplus\Plugins\viathinksoft\publicPages\n000_objects\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_2;

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
	public function init(bool $html=true): void {
	}

	/**
	 * @param string $id
	 * @param array $out
	 * @param bool $handled
	 * @return void
	 */
	public function gui(string $id, array &$out, bool &$handled): void {
	}

	/**
	 * @param array $json
	 * @param string|null $ra_email
	 * @param bool $nonjs
	 * @param string $req_goto
	 * @return bool
	 */
	public function tree(array &$json, ?string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
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
	 * @throws \ViaThinkSoft\OIDplus\Core\OIDplusException
	 */
	public function modifyContent(string $id, string &$title, string &$icon, string &$text): void {
		$obj = OIDplusObject::parse($id);
		if (!$obj) return;
		if (!$obj->userHasWriteRights()) return;

		// TODO: !!! correctly implement page scrolling!!! Problem: We cannot use "limit" because this is MySQL. We cannot use "top" because it is SQL server
		//           We cannot use  id>? and id<? like in admin_log, because users don't have all IDs, just a few, so we cannot filter by ID
		$res = OIDplus::db()->query("select lo.id, lo.unix_ts, lo.addr, lo.event, lu.severity from ###log lo ".
		                            "left join ###log_object lu on lu.log_id = lo.id ".
		                            "where lu.object = ? " .
		                            "order by lo.unix_ts desc", array($id));
		$text .= '<h2>'._L('Log messages for object %1',htmlentities($id)).'</h2>';

		$text .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
		$text .= '<table class="table table-bordered table-striped">';
		$text .= '<thead>';
		$text .= '<tr><th>'._L('Time').'</th><th>'._L('Event').'</th><th>'._L('Affected users').'</th><!--<th>'._L('Affected objects').'</th>--><th>'._L('IP Address').'</th></tr>';
		$text .= '</thead>';
		$text .= '<tbody>';

		if ($res->any()) {
			$count = 0;
			while ($row = $res->fetch_array()) {
				$count++;
				if ($count > 100) break; // TODO: also allow to watch older entries

				$addr = empty($row['addr']) ? _L('no address') : $row['addr'];

				$users = array();
				$res2 = OIDplus::db()->query("select username, severity from ###log_user ".
				                             "where log_id = ?", array((int)$row['id']));
				while ($row2 = $res2->fetch_array()) {
					$users[] = $row2['username'];
				}
				$users = implode("\n",$users);

				$a = '<span class="severity_'.$row['severity'].'">';
				$b = '</span>';
				$text .= '<tr>';
				$text .= '<td>'.$a.date('Y-m-d H:i:s', (int)$row['unix_ts']).$b.'</td>';
				$text .= '<td>'.$a.htmlentities($row['event']).$b.'</td>';
				$text .= '<td>'.$a.nl2br(htmlentities($users)).$b.'</td>';
				#$text .= '<td>'.$a.nl2br(htmlentities($objects)).$b.'</td>';
				$text .= '<td>'.$a.htmlentities($addr).$b.'</td>';
				$text .= '<tr>';
			}
		} else {
			$text .= '<tr><td colspan="4">'._L('There are no log entries on this page').'</td></tr>';
		}

		$text .= '</tbody>';
		$text .= '</table>';
		$text .= '</div></div>';
	}

	/**
	 * @param string $request
	 * @return array|false
	 */
	public function tree_search(string $request) {
		return false;
	}
}
