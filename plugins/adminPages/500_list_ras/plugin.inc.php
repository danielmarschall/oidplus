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

class OIDplusPageAdminListRAs extends OIDplusPagePlugin {
	public static function getPluginInformation() {
		$out = array();
		$out['name'] = 'List RAs';
		$out['author'] = 'ViaThinkSoft';
		$out['version'] = null;
		$out['descriptionHTML'] = null;
		return $out;
	}

	public function type() {
		return 'admin';
	}

	public function priority() {
		return 500;
	}

	public function action(&$handled) {
		// Nothing
	}

	public function init($html=true) {
		// Nothing
	}

	public function cfgSetValue($name, $value) {
		// Nothing
	}

	private function get_ralist() {
		$tmp = array();
		if (OIDplus::db()->slang() == 'mysql') {
			$res = OIDplus::db()->query("select distinct BINARY(email) as email from ".OIDPLUS_TABLENAME_PREFIX."ra"); // "binary" because we want to ensure that 'distinct' is case sensitive
		} else {
			$res = OIDplus::db()->query("select distinct email as email from ".OIDPLUS_TABLENAME_PREFIX."ra"); // distinct in PGSQL is always case sensitive
		}
		while ($row = $res->fetch_array()) {
			$tmp[$row['email']] = 1;
		}
		if (OIDplus::db()->slang() == 'mysql') {
			$res = OIDplus::db()->query("select distinct BINARY(ra_email) as ra_email from ".OIDPLUS_TABLENAME_PREFIX."objects");
		} else {
			$res = OIDplus::db()->query("select distinct ra_email as ra_email from ".OIDPLUS_TABLENAME_PREFIX."objects");
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
			$out['title'] = 'RA Listing';
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>You need to <a '.OIDplus::gui()->link('oidplus:login').'>log in</a> as administrator.</p>';
			} else {
				$out['text'] = '';

				$tmp = $this->get_ralist();
				
				if (count($tmp) == 0) {
					$out['text'] .= '<p>Currently there are no Registration Authorities.</p>';
				}

				foreach ($tmp as $ra_email => $registered) {
					if (empty($ra_email)) {
						$out['text'] .= '<p><b><a '.OIDplus::gui()->link('oidplus:rainfo$').'>(Objects with undefined RA)</a></b></p>';
					} else {
						if ($registered == 0) {
							$out['text'] .= '<p><b><a '.OIDplus::gui()->link('oidplus:rainfo$'.str_replace('@','&',$ra_email)).'>'.htmlentities($ra_email).'</a></b> (has objects, is not registered)</p>';
						}
						if ($registered == 1) {
							$out['text'] .= '<p><b><a '.OIDplus::gui()->link('oidplus:rainfo$'.str_replace('@','&',$ra_email)).'>'.htmlentities($ra_email).'</a></b> (registered, <font color="red">has no objects</font>)</p>';
						}
						if ($registered == 2) {
							$out['text'] .= '<p><b><a '.OIDplus::gui()->link('oidplus:rainfo$'.str_replace('@','&',$ra_email)).'>'.htmlentities($ra_email).'</a></b></p>';
						}
					}
				}
			}
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = OIDplus::webpath(__DIR__).'treeicon.png';
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
					'text' => '(Objects with undefined RA)'
				);
			} else {
				if ($registered == 0) {
					$children[] = array(
						'id' => 'oidplus:rainfo$'.str_replace('@', '&', $ra_email),
						'icon' => $tree_icon,
						'text' => $ra_email.' <i>(has objects, is not registered)</i>'
					);
				}
				if ($registered == 1) {
					$children[] = array(
						'id' => 'oidplus:rainfo$'.$ra_email,
						'icon' => $tree_icon,
						'text' => $ra_email.' <i><font color="red">(has no objects)</font></i>'
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
			'text' => 'List RAs',
			'children' => $children
		);

		return true;
	}

	public function tree_search($request) {
		// We don't need this, because the list of RAs is loaded without lazy-loading,
		// so the node does not need to be searched
		/*
		if (strpos($request, 'oidplus:rainfo$') === 0) {
			if (OIDplus::authUtils()::isAdminLoggedIn()) {
				return array('oidplus:login', ...dummy..., 'oidplus:list_ra', $request);
			}
		}
		*/
		return false;
	}
}
