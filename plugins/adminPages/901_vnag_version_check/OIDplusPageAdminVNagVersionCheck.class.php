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

if (!defined('INSIDE_OIDPLUS')) die();

class OIDplusPageAdminVNagVersionCheck extends OIDplusPagePluginAdmin {

	public function init($html=true) {
	}

	public function action($actionID, $params) {
	}

	public function gui($id, &$out, &$handled) {
		$parts = explode('.',$id,2);
		if (!isset($parts[1])) $parts[1] = '';
		if ($parts[0] == 'oidplus:vnag_version_check') {
			@set_time_limit(0);


			$handled = true;
			$out['title'] = _L('VNag version check');
			$out['icon']  = OIDplus::webpath(__DIR__).'icon_big.png';

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>'._L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login')).'</p>';
				return;
			}

			if (file_exists(__DIR__ . '/tutorial$'.OIDplus::getCurrentLang().'.html')) {
				$cont = file_get_contents(__DIR__ . '/tutorial$'.OIDplus::getCurrentLang().'.html');
			} else if (file_exists(__DIR__ . '/tutorial.html')) {
				$cont = file_get_contents(__DIR__ . '/tutorial.html');
			} else {
				$cont = '';
			}

			$cont = str_replace('%%SYSTEM_URL%%',OIDplus::localpath(),$cont);
			$cont = str_replace('%%REL_LOC_PATH%%',OIDplus::localpath(__DIR__,true),$cont);
			$cont = str_replace('%%REL_WEB_PATH%%',OIDplus::webpath(__DIR__,true),$cont);
			$cont = str_replace('%%ABS_LOC_PATH%%',OIDplus::localpath(__DIR__,false),$cont);
			$cont = str_replace('%%ABS_WEB_PATH%%',OIDplus::webpath(__DIR__,false),$cont);

			$out['text'] .= $cont;
		} else {
			$handled = false;
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (!OIDplus::authUtils()::isAdminLoggedIn()) return false;

		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = OIDplus::webpath(__DIR__).'treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:vnag_version_check',
			'icon' => $tree_icon,
			'text' => _L('VNag version check')
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}
}
