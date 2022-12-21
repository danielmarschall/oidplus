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

namespace ViaThinkSoft\OIDplus;

class OIDplusPageAdminListRAs extends OIDplusPagePluginAdmin {

	public function init($html=true) {
		// Nothing
	}

	private function get_ralist() {
		$tmp = array();
		if (OIDplus::db()->getSlang()->id() == 'mysql') {
			$res = OIDplus::db()->query("select distinct BINARY(email) as email from ###ra"); // "binary" because we want to ensure that 'distinct' is case sensitive
		} else {
			$res = OIDplus::db()->query("select distinct email as email from ###ra"); // distinct in PGSQL is always case sensitive
		}
		while ($row = $res->fetch_array()) {
			$tmp[$row['email']] = 1;
		}
		if (OIDplus::db()->getSlang()->id() == 'mysql') {
			$res = OIDplus::db()->query("select distinct BINARY(ra_email) as ra_email from ###objects");
		} else {
			$res = OIDplus::db()->query("select distinct ra_email as ra_email from ###objects");
		}
		while ($row = $res->fetch_array()) {
			if (!isset($tmp[$row['ra_email']])) {
				$tmp[$row['ra_email']] = 0;
			} else {
				$tmp[$row['ra_email']] = 2;
			}
		}
		ksort($tmp);

		return $tmp;
	}

	public function gui($id, &$out, &$handled) {
		if ($id === 'oidplus:list_ra') {
			$handled = true;
			$out['title'] = _L('RA Listing');
			$out['icon'] = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				$out['icon'] = 'img/error.png';
				$out['text'] = '<p>'._L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')).'</p>';
				return;
			}

			$out['text'] = '';

			$tmp = $this->get_ralist();

			$raCreatePlugin = OIDplus::getPluginByOid('1.3.6.1.4.1.37476.2.5.2.4.3.130'); // OIDplusPageAdminCreateRa
			if (!is_null($raCreatePlugin)) {
				$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:create_ra').'>Create a new RA manually</a></p>';
			}

			if (count($tmp) == 0) {
				$out['text'] .= '<p>'._L('Currently there are no Registration Authorities.').'</p>';
			}

			foreach ($tmp as $ra_email => $registered) {
				if (empty($ra_email)) {
					$out['text'] .= '<p><b><a '.OIDplus::gui()->link('oidplus:rainfo$').'>'._L('(Objects with undefined RA)').'</a></b></p>';
				} else {
					if ($registered == 0) {
						$out['text'] .= '<p><b><a '.OIDplus::gui()->link('oidplus:rainfo$'.str_replace('@','&',$ra_email)).'>'.htmlentities($ra_email).'</a></b> '._L('(has objects, is not registered)').'</p>';
					}
					if ($registered == 1) {
						$out['text'] .= '<p><b><a '.OIDplus::gui()->link('oidplus:rainfo$'.str_replace('@','&',$ra_email)).'>'.htmlentities($ra_email).'</a></b> '._L('(registered, <font color="red">has no objects</font>)').'</p>';
					}
					if ($registered == 2) {
						$out['text'] .= '<p><b><a '.OIDplus::gui()->link('oidplus:rainfo$'.str_replace('@','&',$ra_email)).'>'.htmlentities($ra_email).'</a></b></p>';
					}
				}
			}
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (!OIDplus::authUtils()->isAdminLoggedIn()) return false;

		if (file_exists(__DIR__.'/img/main_icon16.png')) {
			$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$children = array();
		$tmp = $this->get_ralist();
		foreach ($tmp as $ra_email => $registered) {
			if (empty($ra_email)) {
				$children[] = array(
					'id' => 'oidplus:rainfo$',
					'icon' => $tree_icon,
					'text' => _L('(Objects with undefined RA)')
				);
			} else {
				if ($registered == 0) {
					$children[] = array(
						'id' => 'oidplus:rainfo$'.str_replace('@', '&', $ra_email),
						'icon' => $tree_icon,
						'text' => $ra_email.' <i>'._L('(has objects, is not registered)').'</i>'
					);
				}
				if ($registered == 1) {
					$children[] = array(
						'id' => 'oidplus:rainfo$'.$ra_email,
						'icon' => $tree_icon,
						'text' => $ra_email.' <i><font color="red">'._L('(has no objects)').'</font></i>'
					);
				}
				if ($registered == 2) {
					$children[] = array(
						'id' => 'oidplus:rainfo$'.$ra_email,
						'icon' => $tree_icon,
						'text' => $ra_email
					);
				}
			}
		}

		$json[] = array(
			'id' => 'oidplus:list_ra',
			'icon' => $tree_icon,
			'text' => _L('List RAs'),
			'children' => $children
		);

		return true;
	}

	public function tree_search($request) {
		// We don't need this, because the list of RAs is loaded without lazy-loading,
		// so the node does not need to be searched
		/*
		if (strpos($request, 'oidplus:rainfo$') === 0) {
			if (OIDplus::authUtils()->isAdminLoggedIn()) {
				return array('oidplus:login', ...dummy..., 'oidplus:list_ra', $request);
			}
		}
		*/
		return false;
	}
}