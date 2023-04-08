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

class OIDplusPageAdminNotifications extends OIDplusPagePluginAdmin
	implements INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8 /* getNotifications */
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
	 * @throws OIDplusException
	 */
	public function gui(string $id, array &$out, bool &$handled) {
		$parts = explode('$',$id);
		$id = $parts[0];

		if ($id == 'oidplus:notifications') {
			$handled = true;
			$ra_email = $parts[1] ?? null/*no filter*/;

			$out['title'] = _L('Notifications');
			$out['icon'] = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

			if ($ra_email == 'admin') {
				if (!OIDplus::authUtils()->isAdminLoggedIn()) {
					$out['icon'] = 'img/error.png';
					$out['text'] = '<p>'._L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')).'</p>';
					return;
				}
			} else if ($ra_email) {
				if (!OIDplus::authUtils()->isRaLoggedIn($ra_email) && !OIDplus::authUtils()->isAdminLoggedIn()) {
					$out['icon'] = 'img/error.png';
					$out['text'] = '<p>'._L('You need to <a %1>log in</a> as the requested RA %2.',OIDplus::gui()->link('oidplus:login$ra$'.$ra_email),'<b>'.htmlentities($ra_email).'</b>').'</p>';
					return;
				}
			} else {
				if ((OIDplus::authUtils()->raNumLoggedIn() == 0) && !OIDplus::authUtils()->isAdminLoggedIn()) {
					$out['icon'] = 'img/error.png';
					$out['text'] = '<p>'._L('You need to <a %1>log in</a>.',OIDplus::gui()->link('oidplus:login')).'</p>';
					return;
				}
			}

			$notifications_by_sev = array();

			foreach (OIDplus::getAllPlugins() as $plugin) {
				if ($plugin instanceof INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8) {
					try {
						$notifications = $plugin->getNotifications($ra_email);
					} catch (\Exception $e) {
						$notifications = array(
							['CRIT', _L('The plugin %1 crashed during the notification-check. Message: %2', get_class($plugin), $e->getMessage())]
						);
					}
					if ($notifications) {
						foreach ($notifications as $notification) {
							$severity = $notification->getSeverityAsInt();
							if (!isset($notifications_by_sev[$severity])) $notifications_by_sev[$severity] = array();
							$notifications_by_sev[$severity][] = $notification;
						}
					}
				}
			}

			if (count($notifications_by_sev) == 0) {

				$out['text'] .= '<br><p><i>'._L('No notifications').'</i></p>';

			} else {
				krsort($notifications_by_sev);

				foreach ($notifications_by_sev as $severity => $notifications) {
					if (count($notifications) == 0) continue;

					$sev_hf = $notifications[0]->getSeverityAsHumanFriendlyString(true);

					$out['text'] .= '<h2><span class="severity_'.$severity.'">'.$sev_hf.' ('.count($notifications).')</span></h2>';
					$out['text'] .= '<span class="severity_'.$severity.'"><ol>';
					foreach ($notifications as $notification) {
						$out['text'] .= '<li>'.$notification->getMessage().'</li>';
					}
					$out['text'] .= '</ol></span>';
				}
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
			'id' => 'oidplus:notifications$admin',
			'icon' => $tree_icon,
			'text' => _L('Notifications')
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
	 * Checks if the system can be accessed publicly
	 * Attention! This check does not work if OIDplus is password protected (solution would be to check via JavaScript,
	 * which is done in setup/), or the URL is in the IntraNet rather than the Internet (only solution would be a
	 * remote URL check service)
	 * @param string $dir
	 * @return false|string
	 * @throws OIDplusException
	 */
	private function webAccessWorks(string $dir) {
		$url = OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).$dir;
		$require_ssl = str_starts_with(strtolower($url),'https:');
		if (!url_get_contents_available($require_ssl)) return false;
		$access_worked = url_get_contents($url) !== false;
		if ($access_worked) return $url;

		if (!$access_worked) {
			$url_alt = OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE).$dir;
			if ($url != $url_alt) {
				$access_worked = url_get_contents($url_alt) !== false;
				if ($access_worked) return $url;
			}
		}

		return false;
	}

	/**
	 * @param string $dir
	 * @return array
	 * @throws OIDplusException
	 */
	private function getNotificationsCheckDirAccess(string $dir): array {
		$notifications = array();
		if (($url = $this->webAccessWorks($dir)) !== false) {
			// Re-use message taken from setup/includes/setup_base.js
			$notifications[] = new OIDplusNotification('CRIT', _L('Attention: The following directory is world-readable: %1 ! You need to configure your web server to restrict access to this directory! (For Apache see <i>.htaccess</i>, for Microsoft IIS see <i>web.config</i>, for Nginx see <i>nginx.conf</i>).','<a target="_blank" href="'.$url.'">'.$dir.'</a>'));
		}
		return $notifications;
	}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8
	 * These are some basic "system" checks, no checks from other plugin. So we add them to our plugin instead.
	 * @param string|null $user
	 * @return array
	 * @throws OIDplusException
	 */
	public function getNotifications(string $user=null): array {
		$notifications = array();
		if ((!$user || ($user == 'admin')) && OIDplus::authUtils()->isAdminLoggedIn()) {
			// Check if critical directories are world-readable
			if ($this->webAccessWorks('index.php') === false) {
				$notifications[] = new OIDplusNotification('INFO', _L("The system can't check if critical directories (%1) are readable via web-browser. Please verify it manually.", 'userdata, res, dev, includes, setup/includes'));
			} else {
				// see setup/includes/setup_base.js
				$forbidden_dirs = array(
					"userdata/index.html",
					"res/ATTENTION.TXT",
					"dev/index.html",
					"includes/index.html",
					"setup/includes/index.html"
					//"plugins/viathinksoft/publicPages/100_whois/whois/cli/index.html"
				);
				foreach ($forbidden_dirs as $dir) {
					$notifications = array_merge($notifications, $this->getNotificationsCheckDirAccess($dir));
				}
			}

			// Check if cache directory is writeable
			$cache_dir = OIDplus::localpath(null).'userdata/cache/';
			if (!is_dir($cache_dir)) {
				$notifications[] = new OIDplusNotification('ERR', _L('Directory %1 does not exist', $cache_dir));
			} else if (!isFileOrPathWritable($cache_dir)) {
				$notifications[] = new OIDplusNotification('ERR', _L('Directory %1 is not writeable. Please check the permissions!', $cache_dir));
			}
		}
		return $notifications;
	}

}
