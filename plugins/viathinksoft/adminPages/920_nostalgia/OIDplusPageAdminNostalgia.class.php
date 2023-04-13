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

// ATTENTION: If you change something, please make sure that the changes
//            are synchronous with OIDplusPageRaAutomatedAJAXCalls

namespace ViaThinkSoft\OIDplus;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusPageAdminNostalgia extends OIDplusPagePluginAdmin
	implements INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8 /* getNotifications */
{

	/**
	 * @param string $id
	 * @param array $out
	 * @param bool $handled
	 * @return void
	 * @throws OIDplusException
	 */
	public function gui(string $id, array &$out, bool &$handled) {
		if ($id === 'oidplus:nostalgia') {
			$handled = true;
			$out['title'] = _L('Nostalgia');
			$out['icon'] = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')), $out['title']);
			}

			$out['text'] = '<p>'._L('Did you ever wonder what OIDplus would look like if it had been created in the era of MS-DOS, Windows 3.11, or Windows 95? Just download the ZIP files below and have a look!').'</p>';

			if (class_exists('ZipArchive')) {
				$out['text'] .= '<ul>';
				$out['text'] .= '<li><a href="'.OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'export_dos.php">'._L('Download OIDplus for DOS').'</a>, '._L('including your database* (only OIDs)').'</li>';
				$out['text'] .= '<li><a href="https://www.viathinksoft.de/download/252/oidplus_dos.zip">'._L('Download OIDplus for DOS').'</a>, '._L('without data').'</li>';
				$out['text'] .= '<li><a href="'.OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'export_win.php">'._L('Download OIDplus for Windows 3.11, 95, or later').'</a>, '._L('including your database* (only OIDs)').'</li>';
				$out['text'] .= '<li><a href="https://www.viathinksoft.de/download/254/oidplus_win311.zip">'._L('Download OIDplus for Windows 3.11').'</a>, '._L('without data').'</li>';
				$out['text'] .= '<li><a href="https://www.viathinksoft.de/download/253/oidplus_win95.zip">'._L('Download OIDplus for Windows 95 or later').'</a>, '._L('without data').'</li>';
				$out['text'] .= '</ul>';
				$out['text'] .= '<p>'._L('* Please note that the download might be delayed since your OID database is exported and added to the ZIP file.').'</p>';
			} else {
				$out['text'] .= '<ul>';
				$out['text'] .= '<li><a href="https://www.viathinksoft.de/download/252/oidplus_dos.zip">'._L('Download OIDplus for DOS').'</a>, '._L('without data').'</li>';
				$out['text'] .= '<li><a href="https://www.viathinksoft.de/download/254/oidplus_win311.zip">'._L('Download OIDplus for Windows 3.11').'</a>, '._L('without data').'</li>';
				$out['text'] .= '<li><a href="https://www.viathinksoft.de/download/253/oidplus_win95.zip">'._L('Download OIDplus for Windows 95 or later').'</a>, '._L('without data').'</li>';
				$out['text'] .= '</ul>';
				$out['text'] .= '<p><font color="red">'._L('The PHP extension "ZipArchive" needs to be installed to create a ZIP archive with an included database. Otherwise, you can just download the plain program without data.').'</font></p>';
			}
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
		if (!OIDplus::authUtils()->isAdminLoggedIn()) return false;

		if (file_exists(__DIR__.'/img/main_icon16.png')) {
			$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:nostalgia',
			'icon' => $tree_icon,
			'text' => _L('Nostalgia')
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

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8
	 * @param string|null $user
	 * @return array
	 * @throws OIDplusException
	 */
	public function getNotifications(string $user=null): array {
		$notifications = array();
		if ((!$user || ($user == 'admin')) && OIDplus::authUtils()->isAdminLoggedIn()) {
			if (!class_exists('ZipArchive')) {
				$title = _L('Nostalgia');
				$notifications[] = new OIDplusNotification('ERR', _L('OIDplus plugin "%1" is enabled, but the required PHP extension "%2" is not installed.', '<a '.OIDplus::gui()->link('oidplus:nostalgia').'>'.htmlentities($title).'</a>', 'Zip'));
			}
		}
		return $notifications;
	}

}
