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

class OIDplusPageRaLogEvents extends OIDplusPagePluginRa {

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
	 * @throws OIDplusException
	 */
	public function gui(string $id, array &$out, bool &$handled) {
		$parts = explode('$', $id);
		if ($parts[0] == 'oidplus:ra_log') {
			$ra_email = $parts[1] ?? null;
			if ($ra_email == null) return;

			$handled = true;

			$out['title'] = _L('Log messages for RA %1',$ra_email);
			$out['icon'] = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

			if (!OIDplus::authUtils()->isRaLoggedIn($ra_email) && !OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as the requested RA %2.',OIDplus::gui()->link('oidplus:login$ra$'.$ra_email),'<b>'.htmlentities($ra_email).'</b>'), $out['title'], 401);
			}

			$res = OIDplus::db()->query("select * from ###ra where email = ?", array($ra_email));
			if (!$res->any()) {
				throw new OIDplusHtmlException(_L('RA "%1" does not exist','<b>'.htmlentities($ra_email).'</b>'), $out['title']);
			}


			// TODO: !!! correctly implement page scrolling!!! Problem: We cannot use "limit" because this is MySQL. We cannot use "top" because it is SQL server
			//           We cannot use  id>? and id<? like in admin_log, because users don't have all IDs, just a few, so we cannot filter by ID
			$page = $parts[2] ?? null;
			if ($page == null) {
				$res = OIDplus::db()->query("select max(lo.id) as cnt from ###log lo ".
				                            "left join ###log_user lu on lu.log_id = lo.id ".
				                            "where lu.username = ? " .
				                            "order by lo.unix_ts desc", array($ra_email));
				$page = floor($res->fetch_array()['cnt'] / 50) + 1;
			}
			$min = ($page-1) * 50 + 1;
			$max = ($page  ) * 50;

			$res = OIDplus::db()->query("select lo.unix_ts, lo.addr, lo.event, lu.severity from ###log lo ".
			                            "left join ###log_user lu on lu.log_id = lo.id ".
			                            "where lu.username = ? " .
			                            "and   lo.id >= ? and lo.id <= ? ".
			                            "order by lo.unix_ts desc", array($ra_email, $min, $max));

			$out['text'] = '<h2>'._L('Page %1 (Log ID %2 till %3)', $page, $min, $max).'</h2>';

			$out['text'] .= '<p>';
			if (!is_null($parts[2] ?? null)) $out['text'] .= '<a '.OIDplus::gui()->link($parts[0].'$'.$parts[1].'$'.($page+1)).'>Newer log entries</a> -- ';
			$out['text'] .= '<a '.OIDplus::gui()->link($parts[0].'$'.$parts[1].'$'.($page-1)).'>Older log entries</a>';
			$out['text'] .= '<p>';

			if ($res->any()) {
				$out['text'] .= '<pre>';
				while ($row = $res->fetch_array()) {
					$addr = empty($row['addr']) ? _L('no address') : $row['addr'];

					$out['text'] .= '<span class="severity_'.$row['severity'].'">' . date('Y-m-d H:i:s', (int)$row['unix_ts']) . ': ' . htmlentities($row["event"])." (" . htmlentities($addr) . ")</span>\n";
				}
				$out['text'] .= '</pre>';
			} else {
				$out['text'] .= '<p>'._L('There are no log entries on this page').'</p>';
			}

			// TODO: List logs in a table instead of a <pre> text
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
		if (!$ra_email) return false;
		if (!OIDplus::authUtils()->isRaLoggedIn($ra_email) && !OIDplus::authUtils()->isAdminLoggedIn()) return false;

		if (file_exists(__DIR__.'/img/main_icon16.png')) {
			$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png';
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

	/**
	 * @param string $request
	 * @return array|false
	 */
	public function tree_search(string $request) {
		return false;
	}
}
