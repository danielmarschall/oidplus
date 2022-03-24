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

// ATTENTION: If you change something, please make sure that the changes
//            are synchronous with OIDplusPageRaAutomatedAJAXCalls

if (!defined('INSIDE_OIDPLUS')) die();

class OIDplusPageAdminAutomatedAJAXCalls extends OIDplusPagePluginAdmin {

	public function action($actionID, $params) {
		if ($actionID == 'blacklistJWT') {
			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusException(_L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')));
			}

			if (!OIDplus::baseConfig()->getValue('JWT_ALLOW_AJAX_ADMIN', true)) {
				throw new OIDplusException(_L('The administrator has disabled this feature. (Base configuration setting %1).','JWT_ALLOW_AJAX_ADMIN'));
			}

			$gen = OIDplusAuthContentStoreJWT::JWT_GENERATOR_AJAX;
			$sub = 'admin';

			OIDplusAuthContentStoreJWT::jwtBlacklist($gen, $sub);

			return array("status" => 0);
		} else {
			throw new OIDplusException(_L('Unknown action ID'));
		}
	}

	public function gui($id, &$out, &$handled) {
		if ($id === 'oidplus:automated_ajax_information_admin') {
			$handled = true;
			$out['title'] = _L('Automated AJAX calls');
			$out['icon'] = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				$out['icon'] = 'img/error.png';
				$out['text'] = '<p>'._L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')).'</p>';
				return;
			}

			if (!OIDplus::baseConfig()->getValue('JWT_ALLOW_AJAX_ADMIN', true)) {
				$out['text'] = '<p>'._L('The administrator has disabled this feature. (Base configuration setting %1).','JWT_ALLOW_AJAX_ADMIN').'</p>';
				return;
			}

			$gen = OIDplusAuthContentStoreJWT::JWT_GENERATOR_AJAX;
			$sub = 'admin';

			$authSimulation = new OIDplusAuthContentStoreJWT();
			$authSimulation->adminLogin();
			$authSimulation->setValue('oidplus_generator', $gen);
			$token = $authSimulation->getJWTToken();

			$out['text'] .= '<p>'._L('You can make automated calls to your OIDplus account by calling the AJAX API.').'</p>';
			$out['text'] .= '<p>'._L('The URL for the AJAX script is:').'</p>';
			$out['text'] .= '<p><b>'.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).'ajax.php</b></p>';
			$out['text'] .= '<p>'._L('You must at least provide following fields:').'</p>';
			$out['text'] .= '<p><pre>';
			$out['text'] .= 'OIDPLUS_AUTH_JWT = "'.htmlentities($token).'"'."\n";
			$out['text'] .= '</pre></p>';
			$out['text'] .= '<p>'._L('Please keep this information confidential!').'</p>';
			$out['text'] .= '<p>'._L('The JWT-token (secret!) will automatically perform a one-time-login to fulfill the request. The other fields are the normal fields which are called during the usual operation of OIDplus.').'</p>';
			$out['text'] .= '<p>'._L('Currently, there is no documentation for the AJAX calls. However, you can look at the <b>script.js</b> files of the plugins to see the field names being used. You can also enable network analysis in your web browser debugger (F12) to see the request headers sent to the server during the operation of OIDplus.').'</p>';

			$out['text'] .= '<h2>'._L('Blacklisted tokens').'</h2>';
			$bl_time = OIDplusAuthContentStoreJWT::jwtGetBlacklistTime($gen, $sub);
			if ($bl_time == 0) {
				$out['text'] .= '<p>'._L('None of the previously generated JWT tokens have been blacklisted.').'</p>';
			} else {
				$out['text'] .= '<p>'._L('All tokens generated before %1 have been blacklisted.',date('d F Y, H:i:s',$bl_time+1)).'</p>';
			}
			$out['text'] .= '<button type="button" name="btn_blacklist_jwt" id="btn_blacklist_jwt" class="btn btn-danger btn-xs" onclick="OIDplusPageAdminAutomatedAJAXCalls.blacklistJWT()">'._L('Blacklist all previously generated tokens').'</button>';

			$out['text'] .= '<h2>'._L('Example for adding OID 2.999.123 using JavaScript').'</h2>';
			$cont = file_get_contents(__DIR__.'/examples/example_js.html');
			$cont = str_replace('<url>', webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).'ajax.php', $cont);
			$cont = str_replace('<token>', $token, $cont);
			$out['text'] .= '<pre>'.htmlentities($cont).'</pre>';

			$out['text'] .= '<h2>'._L('Example for adding OID 2.999.123 using PHP (located at a foreign server)').'</h2>';
			$cont = file_get_contents(__DIR__.'/examples/example_php.phps');
			$cont = str_replace('<url>', OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).'ajax.php', $cont);
			$cont = str_replace('<token>', $token, $cont);
			$out['text'] .= '<pre>'.preg_replace("@<br.*>@ismU","",highlight_string($cont,true)).'</pre>';

			$out['text'] .= '<h2>'._L('Example for adding OID 2.999.123 using Python').'</h2>';
			$cont = file_get_contents(__DIR__.'/examples/example_python.py');
			$cont = str_replace('<url>', OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).'ajax.php', $cont);
			$cont = str_replace('<token>', $token, $cont);
			$out['text'] .= '<pre>'.htmlentities($cont).'</pre>';

			$out['text'] .= '<h2>'._L('Example for adding OID 2.999.123 using VBScript').'</h2>';
			$cont = file_get_contents(__DIR__.'/examples/example_vbs.vbs');
			$cont = str_replace('<url>', webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).'ajax.php', $cont);
			$cont = str_replace('<token>', $token, $cont);
			$out['text'] .= '<pre>'.htmlentities($cont).'</pre>';
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
			'id' => 'oidplus:automated_ajax_information_admin',
			'icon' => $tree_icon,
			'text' => _L('Automated AJAX calls')
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}
}
