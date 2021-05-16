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
//            are synchronous with OIDplusPageAdminAutomatedAJAXCalls

if (!defined('INSIDE_OIDPLUS')) die();

class OIDplusPageRaAutomatedAJAXCalls extends OIDplusPagePluginRa {

	public function action($actionID, $params) {
		if ($actionID == 'blacklistJWT') {
			if (!OIDplus::baseConfig()->getValue('JWT_ALLOW_AJAX_USER', true)) {
				throw new OIDplusException(_L('The administrator has disabled this feature. (Base configuration setting %1).','JWT_ALLOW_AJAX_USER'));
			}

			_CheckParamExists($params, 'user');
			$ra_email = $params['user'];

			if (!OIDplus::authUtils()->isRaLoggedIn($ra_email) && !OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusException(_L('You need to <a %1>log in</a> as the requested RA %2 or as admin.',OIDplus::gui()->link('oidplus:login$ra$'.$ra_email),'<b>'.htmlentities($ra_email).'</b>'));
			}

			$gen = 0; // 0=Automated AJAX, 1=Reserved for normal login, 2=Manually "crafted"
			$sub = $ra_email;

			$cfg = 'jwt_nbf_gen('.$gen.')_sub('.base64_encode(md5($sub,true)).')';
			OIDplus::config()->prepareConfigKey($cfg, 'Blacklist (NBF) of JWT token for $sub with generator $gen (Automated AJAX)', time()-1, OIDplusConfig::PROTECTION_HIDDEN, function($value) {});
			OIDplus::config()->setValue($cfg,time()-1);

			return array("status" => 0);
		} else {
			throw new OIDplusException(_L('Unknown action ID'));
		}
	}

	public function gui($id, &$out, &$handled) {
		if (explode('$',$id)[0] == 'oidplus:automated_ajax_information_ra') {
			$handled = true;

			$ra_email = explode('$',$id)[1];

			$out['title'] = _L('Automated AJAX calls');
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';

			if (!OIDplus::authUtils()->isRaLoggedIn($ra_email) && !OIDplus::authUtils()->isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>'._L('You need to <a %1>log in</a> as the requested RA %2 or as admin.',OIDplus::gui()->link('oidplus:login$ra$'.$ra_email),'<b>'.htmlentities($ra_email).'</b>').'</p>';
				return;
			}

			if (!OIDplus::baseConfig()->getValue('JWT_ALLOW_AJAX_USER', true)) {
				$out['text'] = '<p>'._L('The administrator has disabled this feature. (Base configuration setting %1).','JWT_ALLOW_AJAX_USER').'</p>';
				return;
			}

			$gen = 0; // 0=Automated AJAX, 1=Reserved for normal login, 2=Manually "crafted"
			$sub = $ra_email;

			$authSimulation = new OIDplusAuthContentStoreJWT();
			$authSimulation->raLogin($ra_email);
			$authSimulation->setValue('oidplus_generator', $gen);
			$authSimulation->setValue('sub', $sub); // JWT "sub" attribute
			$token = $authSimulation->GetJWTToken();

			$out['text'] .= '<p>'._L('You can make automated calls to your OIDplus account by calling the AJAX API.').'</p>';
			$out['text'] .= '<p>'._L('The URL for the AJAX script is:').'</p>';
			$out['text'] .= '<p><b>'.OIDplus::webpath(null,false).'ajax.php</b></p>';
			$out['text'] .= '<p>'._L('You must at least provide following fields:').'</p>';
			$out['text'] .= '<p><pre>';
			$out['text'] .= 'OIDPLUS_AUTH_JWT = "'.htmlentities($token).'"'."\n";
			$out['text'] .= '</pre></p>';
			$out['text'] .= '<p>'._L('Please keep this information confidential!').'</p>';
			$out['text'] .= '<p>'._L('The JWT-token (secret!) will automatically perform a one-time-login to fulfill the request. The other fields are the normal fields which are called during the usual operation of OIDplus.').'</p>';
			$out['text'] .= '<p>'._L('Currently, there is no documentation for the AJAX calls. However, you can look at the <b>script.js</b> files of the plugins to see the field names being used. You can also enable network analysis in your web browser debugger (F12) to see the request headers sent to the server during the operation of OIDplus.').'</p>';

			$out['text'] .= '<h2>'._L('Blacklisted tokens').'</h2>';
			$cfg = 'jwt_nbf_gen('.$gen.')_sub('.base64_encode(md5($sub,true)).')';
			$nbf = OIDplus::config()->getValue($cfg,0);
			if ($nbf == 0) {
				$out['text'] .= '<p>'._L('None of the previously generated JWT tokens have been blacklisted.').'</p>';
			} else {
				$out['text'] .= '<p>'._L('All tokens generated before %1 have been blacklisted.',date('d F Y, H:i:s',$nbf+1)).'</p>';
			}
			$out['text'] .= '<button type="button" name="btn_blacklist_jwt" id="btn_blacklist_jwt" class="btn btn-danger btn-xs" onclick="OIDplusPageRaAutomatedAJAXCalls.blacklistJWT('.js_escape($ra_email).')">'._L('Blacklist all previously generated tokens').'</button>';

			$out['text'] .= '<h2>'._L('Example for adding OID 2.999.123 using JavaScript').'</h2>';
			$cont = file_get_contents(__DIR__.'/examples/example_js.html');
			$cont = str_replace('<url>', OIDplus::webpath(null,false).'ajax.php', $cont);
			$cont = str_replace('<token>', $token, $cont);
			$out['text'] .= '<pre>'.htmlentities($cont).'</pre>';

			$out['text'] .= '<h2>'._L('Example for adding OID 2.999.123 using PHP (located at a foreign server)').'</h2>';
			$cont = file_get_contents(__DIR__.'/examples/example_php.phps');
			$cont = str_replace('<url>', OIDplus::webpath(null,false).'ajax.php', $cont);
			$cont = str_replace('<token>', $token, $cont);
			$out['text'] .= '<pre>'.preg_replace("@<br.*>@ismU","",highlight_string($cont,true)).'</pre>';

			$out['text'] .= '<h2>'._L('Example for adding OID 2.999.123 using VBScript').'</h2>';
			$cont = file_get_contents(__DIR__.'/examples/example_vbs.vbs');
			$cont = str_replace('<url>', OIDplus::webpath(null,false).'ajax.php', $cont);
			$cont = str_replace('<token>', $token, $cont);
			$out['text'] .= '<pre>'.htmlentities($cont).'</pre>';
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (!$ra_email) return false;
		if (!OIDplus::authUtils()->isRaLoggedIn($ra_email) && !OIDplus::authUtils()->isAdminLoggedIn()) return false;

		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = OIDplus::webpath(__DIR__).'treeicon.png';
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

	public function tree_search($request) {
		return false;
	}
}
