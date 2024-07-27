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

namespace ViaThinkSoft\OIDplus\Plugins\viathinksoft\adminPages\n901_vnag_version_check;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusConfig;
use ViaThinkSoft\OIDplus\Core\OIDplusConfigInitializationException;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusHtmlException;
use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginAdmin;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusPageAdminVNagVersionCheck extends OIDplusPagePluginAdmin {

	/**
	 * @param bool $html
	 * @return void
	 * @throws OIDplusException
	 */
	public function init(bool $html=true): void {
		OIDplus::config()->prepareConfigKey('vnag_version_check_password_protected', 'If set to 1 ("on"), the VNag version check is password protected', '1', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (($value != '0') && ($value != '1')) {
				throw new OIDplusException(_L('Please enter either 0 ("off") or 1 ("on").'));
			}
		});
	}

	/**
	 * @param string $actionID
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 */
	public function action(string $actionID, array $params): array {
		return parent::action($actionID, $params);
	}

	/**
	 * @param string $id
	 * @param array $out
	 * @param bool $handled
	 * @return void
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	public function gui(string $id, array &$out, bool &$handled): void {
		if ($id == 'oidplus:vnag_version_check') {
			@set_time_limit(0);


			$handled = true;
			$out['title'] = _L('VNag version check');
			$out['icon']  = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png';

			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')), $out['title'], 401);
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
			$cont = str_replace('%%REL_WEB_PATH%%',OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE),$cont);
			$cont = str_replace('%%ABS_LOC_PATH%%',OIDplus::localpath(__DIR__,false),$cont);
			$cont = str_replace('%%ABS_WEB_PATH%%',OIDplus::webpath(__DIR__,OIDplus::PATH_ABSOLUTE_CANONICAL),$cont);
			if (OIDplus::config()->getValue('vnag_version_check_password_protected','1') == '1') {
				$cont = str_replace('%%WEBREADER_PASSWORD%%',self::vnag_password(),$cont);
			} else {
				$cont = str_replace('%%WEBREADER_PASSWORD%%','',$cont);
			}
			if (OIDplus::getPkiStatus()) {
				$pubkey = trim(OIDplus::getSystemPublicKey());
				$pubkey = str_replace("\\","\\\\",$pubkey);
				$pubkey = str_replace("\r","\\r",$pubkey);
				$pubkey = str_replace("\n","\\n",$pubkey);
			} else {
				$pubkey = "";
			}
			$cont = str_replace('%%WEBREADER_PUBKEY%%',$pubkey,$cont);

			$out['text'] .= $cont;
		} else {
			$handled = false;
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
			'id' => 'oidplus:vnag_version_check',
			'icon' => $tree_icon,
			'text' => _L('VNag version check')
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
	 * @return string
	 * @throws OIDplusException
	 */
	public static function vnag_password(): string {
		return OIDplus::authUtils()->makeSecret(['65d9f488-f4eb-11ed-b67e-3c4a92df8582']);
	}

}
