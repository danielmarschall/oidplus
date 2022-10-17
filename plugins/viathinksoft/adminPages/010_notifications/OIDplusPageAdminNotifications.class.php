<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2022 Daniel Marschall, ViaThinkSoft
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

if (!defined('INSIDE_OIDPLUS')) die();

class OIDplusPageAdminNotifications extends OIDplusPagePluginAdmin {

	public function init($html=true) {
	}

	public function gui($id, &$out, &$handled) {
		$parts = explode('$',$id);
		$id = $parts[0];

		if ($id == 'oidplus:notifications') {
			$handled = true;
			$ra_email = isset($parts[1]) ? $parts[1] : null/*no filter*/;

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
				if ($plugin->implementsFeature('1.3.6.1.4.1.37476.2.5.2.3.8')) {
					$notifications = $plugin->getNotifications($ra_email);
					if ($notifications) {
						foreach ($notifications as $notification) {
							list($severity, $htmlMessage) = $notification;

							// Same severities as the log plugin (also same CSS classes)
							if ($severity == 'OK')   $severity = 1; // (this makes no sense)
							if ($severity == 'INFO') $severity = 2;
							if ($severity == 'WARN') $severity = 3;
							if ($severity == 'ERR')  $severity = 4;
							if ($severity == 'CRIT') $severity = 5;

							if (!isset($notifications_by_sev[$severity])) $notifications_by_sev[$severity] = array();
							$notifications_by_sev[$severity][] = $htmlMessage;
						}
					}
				}
			}

			if (count($notifications_by_sev) == 0) {

				$out['text'] .= '<br><p><i>'._L('No notifications').'</i></p>';

			} else {
				krsort($notifications_by_sev);

				foreach ($notifications_by_sev as $severity => $htmlMessages) {
					if (count($htmlMessages) == 0) continue;

					if ($severity == 1) $sev_hf = _L('OK');
					else if ($severity == 2) $sev_hf = _L('Informational');
					else if ($severity == 3) $sev_hf = _L('Warnings');
					else if ($severity == 4) $sev_hf = _L('Errors');
					else if ($severity == 5) $sev_hf = _L('Critical issues');
					else $sev_hf = _L('Severity %1', $severity-1);

					$out['text'] .= '<h2><span class="severity_'.$severity.'">'.$sev_hf.' ('.count($htmlMessages).')</span></h2>';
					$out['text'] .= '<span class="severity_'.$severity.'"><ol>';
					foreach ($htmlMessages as $htmlMessage) {
						$out['text'] .= '<li>'.$htmlMessage.'</li>';
					}
					$out['text'] .= '</ol></span>';
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

		$json[] = array(
			'id' => 'oidplus:notifications$admin',
			'icon' => $tree_icon,
			'text' => _L('Notifications')
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}

	public function implementsFeature($id) {
		if (strtolower($id) == '1.3.6.1.4.1.37476.2.5.2.3.8') return true; // getNotifications()
		return false;
	}

	private function webAccessWorks($dir) {
		// Attention! This check does not work if OIDplus is password protected!
		//            The only real solution is to check via JavaScript, which is done by setup/
		$url = OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).$dir;
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

	private function getNotificationsCheckDirAccess($dir) {
		$notifications = array();
		if (($url = $this->webAccessWorks($dir)) !== false) {
			// Re-use message taken from setup/includes/setup_base.js
			$msg = _L('Attention: The following directory is world-readable: %1 ! You need to configure your web server to restrict access to this directory! (For Apache see <i>.htaccess</i>, for Microsoft IIS see <i>web.config</i>, for Nginx see <i>nginx.conf</i>).','<a target="_blank" href="'.$url.'">'.$dir.'</a>');
			$notifications[] = array('CRIT', $msg);
		}
		return $notifications;
	}

	public function getNotifications($user=null): array {
		// Interface 1.3.6.1.4.1.37476.2.5.2.3.8
		// These are some basic "system" checks, no checks from other plugin. So we add them to our plugin instead.
		$notifications = array();
		if ((!$user || ($user == 'admin')) && OIDplus::authUtils()->isAdminLoggedIn()) {
			// Check if critical directories are world-readable
			if ($this->webAccessWorks('index.php') === false) {
				$notifications[] = array('INFO', _L("The system can't check if critical directories (%1) are readable via web-browser. Please verify it manually.", 'userdata, res, dev, includes, setup/includes'));
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
			if (!is_writeable(OIDplus::localpath(null).'userdata/cache/')) {
				$notifications[] = array('ERR', _L('Directory %1 is not writeable. Please check the permissions!', 'userdata/cache/'));
			}
		}
		return $notifications;
	}

}
