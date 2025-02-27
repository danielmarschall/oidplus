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

namespace ViaThinkSoft\OIDplus\Plugins\AdminPages\Log;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusHtmlException;
use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginAdmin;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusPageAdminLogEvents extends OIDplusPagePluginAdmin {

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
	 * @throws OIDplusException
	 */
	public function gui(string $id, array &$out, bool &$handled): void {
		$parts = explode('$', $id);
		if ($parts[0] == 'oidplus:system_log') {
			$handled = true;
			$out['title'] = _L('All log messages');
			$out['icon'] = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')), $out['title'], 401);
			}

			$page = $parts[1] ?? null;
			if ($page == null) {
				$res = OIDplus::db()->query("select max(id) as cnt from ###log");
				$page = floor($res->fetch_array()['cnt'] / 500) + 1;
			}
			$min = ($page-1) * 500 + 1;
			$max = ($page  ) * 500;

			$res = OIDplus::db()->query("select id, unix_ts, addr, event from ###log ".
			                            "where id >= ? and id <= ? ".
			                            "order by unix_ts desc", [$min, $max]);

			$out['text'] = '<h2>'._L('Page %1 (Log ID %2 till %3)', $page, $min, $max).'</h2>';

			$out['text'] .= '<p>';
			if (!is_null($parts[1] ?? null)) $out['text'] .= '<a '.OIDplus::gui()->link($parts[0].'$'.($page+1)).'>Newer log entries</a> -- ';
			$out['text'] .= '<a '.OIDplus::gui()->link($parts[0].'$'.($page-1)).'>Older log entries</a>';
			$out['text'] .= '<p>';

			$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
			$out['text'] .= '<table class="table table-bordered table-striped">';
			$out['text'] .= '<thead>';
			$out['text'] .= '<tr><th>'._L('Time').'</th><th>'._L('Event').'</th><th>'._L('Affected users').'</th><th>'._L('Affected objects').'</th><th>'._L('IP Address').'</th></tr>';
			$out['text'] .= '</thead>';
			$out['text'] .= '<tbody>';

			if ($res->any()) {
				while ($row = $res->fetch_array()) {
					$severity = 0;
					$contains_messages_for_me = false;
					// ---
					$users = array();
					$res2 = OIDplus::db()->query("select username, severity from ###log_user ".
					                             "where log_id = ?", array((int)$row['id']));
					while ($row2 = $res2->fetch_array()) {
						$users[] = $row2['username'];
						if ($row2['username'] == 'admin') {
							$severity = $row2['severity'];
							$contains_messages_for_me = true;
						}
					}
					$users = implode("\n",$users);
					// ---
					$objects = array();
					$res2 = OIDplus::db()->query("select object, severity from ###log_object ".
					                             "where log_id = ?", array((int)$row['id']));
					while ($row2 = $res2->fetch_array()) {
						$objects[] = $row2['object'];
					}
					$objects = implode("\n",$objects);
					// ---
					$addr = empty($row['addr']) ? _L('no address') : $row['addr'];
					// ---

					$a = '<span class="severity_'.$severity.'">';
					$b = '</span>';
					if ($contains_messages_for_me) $a = '<b>'.$a;
					if ($contains_messages_for_me) $b = $b.'</b>';
					$out['text'] .= '<tr>';
					$out['text'] .= '<td>'.$a.date('Y-m-d H:i:s', (int)$row['unix_ts']).$b.'</td>';
					$out['text'] .= '<td>'.$a.htmlentities($row['event']).$b.'</td>';
					$out['text'] .= '<td>'.$a.nl2br(htmlentities($users)).$b.'</td>';
					$out['text'] .= '<td>'.$a.nl2br(htmlentities($objects)).$b.'</td>';
					$out['text'] .= '<td>'.$a.htmlentities($addr).$b.'</td>';
					$out['text'] .= '<tr>';

				}
			} else {
				$out['text'] .= '<tr><td colspan="5">'._L('There are no log entries on this page').'</td></tr>';
			}

			$out['text'] .= '</tbody>';
			$out['text'] .= '</table>';
			$out['text'] .= '</div></div>';

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
	public function tree(array &$json, ?string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
		if (!OIDplus::authUtils()->isAdminLoggedIn()) return false;

		if (file_exists(__DIR__.'/img/main_icon16.png')) {
			$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:system_log',
			'icon' => $tree_icon,
			'text' => _L('All log messages')
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
