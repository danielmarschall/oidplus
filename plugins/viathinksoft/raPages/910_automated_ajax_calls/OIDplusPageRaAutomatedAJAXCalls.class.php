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
//            are synchronous with OIDplusPageAdminAutomatedAJAXCalls

namespace ViaThinkSoft\OIDplus\Plugins\RaPages\AutomatedAjaxCalls;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusAuthContentStoreJWT;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusHtmlException;
use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginRa;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusPageRaAutomatedAJAXCalls extends OIDplusPagePluginRa {

	/**
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 */
	private function action_Blacklist(array $params): array {
		if (!OIDplus::baseConfig()->getValue('JWT_ALLOW_AJAX_USER', true)) {
			throw new OIDplusException(_L('The administrator has disabled this feature. (Base configuration setting %1).','JWT_ALLOW_AJAX_USER'));
		}

		_CheckParamExists($params, 'user');
		$ra_email = $params['user'];

		if (!OIDplus::authUtils()->isRaLoggedIn($ra_email) && !OIDplus::authUtils()->isAdminLoggedIn()) {
			throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as the requested RA %2 or as admin.',OIDplus::gui()->link('oidplus:login$ra$'.$ra_email),'<b>'.htmlentities($ra_email).'</b>'), null, 401);
		}

		$gen = OIDplusAuthContentStoreJWT::JWT_GENERATOR_AJAX;
		$sub = $ra_email;

		OIDplusAuthContentStoreJWT::jwtBlacklist($gen, $sub);

		return array("status" => 0);
	}

	/**
	 * @param string $actionID
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 */
	public function action(string $actionID, array $params): array {
		if ($actionID == 'blacklistJWT') {
			return $this->action_Blacklist($params);
		} else {
			return parent::action($actionID, $params);
		}
	}

	/**
	 * @param string $id
	 * @param array $out
	 * @param bool $handled
	 * @return void
	 * @throws OIDplusException
	 */
	public function gui(string $id, array &$out, bool &$handled): void {
		$parts = explode('$',$id,2);
		$ra_email = $parts[1] ?? '';

		if ($parts[0] == 'oidplus:automated_ajax_information_ra') {
			$handled = true;

			$out['title'] = _L('Automated AJAX calls');
			$out['icon'] = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

			if (!OIDplus::authUtils()->isRaLoggedIn($ra_email) && !OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as the requested RA %2 or as admin.',OIDplus::gui()->link('oidplus:login$ra$'.$ra_email),'<b>'.htmlentities($ra_email).'</b>'), $out['title'], 401);
			}

			if (!OIDplus::baseConfig()->getValue('JWT_ALLOW_AJAX_USER', true)) {
				throw new OIDplusException(_L('The administrator has disabled this feature. (Base configuration setting %1).','JWT_ALLOW_AJAX_USER'), $out['title']);
			}

			$gen = OIDplusAuthContentStoreJWT::JWT_GENERATOR_AJAX;
			$sub = $ra_email;

			$token = OIDplusAuthContentStoreJWT::craftJWT([$sub], false, $gen);

			$out['text'] .= '<p>'._L('You can make automated calls to your OIDplus account by calling the AJAX API.').'</p>';
			$out['text'] .= '<p>'._L('The URL for the AJAX script is:').'</p>';
			$out['text'] .= '<p><b>'.(OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL)?:'.../').'ajax.php</b></p>';
			$out['text'] .= '<p>'._L('You must at least provide following fields:').'</p>';
			$out['text'] .= '<p><pre id="oidplus_auth_jwt">';
			$out['text'] .= htmlentities(OIDplusAuthContentStoreJWT::COOKIE_NAME).' = "'.htmlentities($token).'"'."\n";
			$out['text'] .= '</pre></p>';
			$out['text'] .= '<p><input type="button" value="'._L('Copy to clipboard').'" onClick="copyToClipboard(oidplus_auth_jwt)"></p>';
			$out['text'] .= '<p>'._L('Please keep this information confidential!').'</p>';
			$out['text'] .= '<p>'._L('The JWT-token (secret!) will automatically perform a login to fulfill the request. The other fields are the normal fields which are called during the usual operation of OIDplus.').'</p>';
			$out['text'] .= '<p>'._L('Currently, there is no documentation for the AJAX calls. However, you can look at the <b>script.js</b> files of the plugins to see the field names being used. You can also enable network analysis in your web browser debugger (F12) to see the request headers sent to the server during the operation of OIDplus.').'</p>';

			$out['text'] .= '<h2>'._L('Blacklisted tokens').'</h2>';
			$bl_time = OIDplusAuthContentStoreJWT::jwtGetBlacklistTime($gen, $sub);
			if ($bl_time == 0) {
				$out['text'] .= '<p>'._L('None of the previously generated JWT tokens have been blacklisted.').'</p>';
			} else {
				$out['text'] .= '<p>'._L('All tokens generated before %1 have been blacklisted.',date('d F Y, H:i:s',$bl_time+1)).'</p>';
			}
			$out['text'] .= '<button type="button" name="btn_blacklist_jwt" id="btn_blacklist_jwt" class="btn btn-danger btn-xs" onclick="OIDplusPageRaAutomatedAJAXCalls.blacklistJWT('.js_escape($ra_email).')">'._L('Blacklist all previously generated tokens').'</button>';

			$cont = file_get_contents(__DIR__.'/examples/example_js.html');
			if ($cont) {
				$out['text'] .= '<h2>'._L('Example for adding OID 2.999.123 using JavaScript').'</h2>';
				$cont = str_replace('<url>', (OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL)?:'.../').'ajax.php', $cont);
				$cont = str_replace('<token>', $token, $cont);
				$out['text'] .= '<pre id="example_js">'.htmlentities($cont).'</pre>';
				$out['text'] .= '<p><input type="button" value="'._L('Copy to clipboard').'" onClick="copyToClipboard(example_js)"></p>';
			}

			$cont = file_get_contents(__DIR__.'/examples/example_php.phps');
			if ($cont) {
				$out['text'] .= '<h2>'._L('Example for adding OID 2.999.123 using PHP (located at a foreign server)').'</h2>';
				$cont = str_replace('<url>', (OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL)?:'.../').'ajax.php', $cont);
				$cont = str_replace('<token>', $token, $cont);
				$out['text'] .= '<pre id="example_php">'.preg_replace("@<br.*>@ismU","",highlight_string($cont,true)).'</pre>';
				$out['text'] .= '<p><input type="button" value="'._L('Copy to clipboard').'" onClick="copyToClipboard(example_php)"></p>';
			}

			$cont = file_get_contents(__DIR__.'/examples/example_python.py');
			if ($cont) {
				$out['text'] .= '<h2>'._L('Example for adding OID 2.999.123 using Python').'</h2>';
				$cont = str_replace('<url>', (OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL)?:'.../').'ajax.php', $cont);
				$cont = str_replace('<token>', $token, $cont);
				$out['text'] .= '<pre id="example_python">'.htmlentities($cont).'</pre>';
				$out['text'] .= '<p><input type="button" value="'._L('Copy to clipboard').'" onClick="copyToClipboard(example_python)"></p>';
			}

			$cont = file_get_contents(__DIR__.'/examples/example_vbs.vbs');
			if ($cont) {
				$out['text'] .= '<h2>'._L('Example for adding OID 2.999.123 using VBScript').'</h2>';
				$cont = str_replace('<url>', (OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL)?:'.../').'ajax.php', $cont);
				$cont = str_replace('<token>', $token, $cont);
				$out['text'] .= '<pre id="example_vbs">'.htmlentities($cont).'</pre>';
				$out['text'] .= '<p><input type="button" value="'._L('Copy to clipboard').'" onClick="copyToClipboard(example_vbs)"></p>';
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
	public function tree(array &$json, ?string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
		if (!$ra_email) return false;
		if (!OIDplus::authUtils()->isRaLoggedIn($ra_email) && !OIDplus::authUtils()->isAdminLoggedIn()) return false;

		if (file_exists(__DIR__.'/img/main_icon16.png')) {
			$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:automated_ajax_information_ra$'.$ra_email,
			'icon' => $tree_icon,
			'text' => _L('Automated AJAX calls')
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
