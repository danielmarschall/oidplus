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

class OIDplusPageRaObjectLog extends OIDplusPagePluginRa {

	public static function getPluginInformation() {
		$out = array();
		$out['name'] = 'Object Log';
		$out['author'] = 'ViaThinkSoft';
		$out['version'] = null;
		$out['descriptionHTML'] = null;
		return $out;
	}

	public function priority() {
		return 99;
	}

	public function action(&$handled) {
		// Nothing
	}

	public function init($html=true) {
	}

	public function cfgSetValue($name, $value) {
	}

	public function gui($id, &$out, &$handled) {
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		return false;
	}

	public function modifyContent($id, &$title, &$icon, &$text) {
		$obj = OIDplusObject::parse($id);
		if (!$obj) return;
		if (!$obj->userHasWriteRights()) return;

		// TODO: I want that this content comes before the WHOIS modifyContent.
		//       The problem is that first all public and then all RA plugins get loaded, not mixed by their priority
		$res = OIDplus::db()->query("select lo.id, lo.unix_ts, lo.addr, lo.event from ".OIDPLUS_TABLENAME_PREFIX."log lo ".
		                            "left join ".OIDPLUS_TABLENAME_PREFIX."log_object lu on lu.log_id = lo.id ".
		                            "where lu.object = ? " .
		                            "order by lo.unix_ts desc", array($id));
		$text .= "<h2>Log messages for object ".htmlentities($id)."</h2>";
		if ($res->num_rows() > 0) {
			$text .= '<pre>';
			while ($row = $res->fetch_array()) {
				$users = array();
				$res2 = OIDplus::db()->query("select username from ".OIDPLUS_TABLENAME_PREFIX."log_user ".
				                             "where log_id = ?", array($row['id']));
				while ($row2 = $res2->fetch_array()) {
					$users[] = $row2['username'];
				}
				$users = count($users) > 0 ? ", ".implode('/',$users) : '';

				$addr = empty($row['addr']) ? 'no address' : $row['addr'];

				$text .= date('Y-m-d H:i:s', $row['unix_ts']) . ': ' . htmlentities($row["event"])." (" . htmlentities($addr.$users) . ")\n";
			}
			$text .= '</pre>';

			// TODO: List logs in a table instead of a <pre> text
			// TODO: Load only X events and then re-load new events via AJAX when the user scrolls down
		} else {
			$text .= '<p>Currently there are no log entries</p>';
		}

	}

	public function tree_search($request) {
		return false;
	}
}
