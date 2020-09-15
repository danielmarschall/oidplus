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

// ATTENTION: If you change something, please make sure that the changes
//            are synchronous with OIDplusPageAdminAutomatedAJAXCalls

class OIDplusPageRaAutomatedAJAXCalls extends OIDplusPagePluginRa {

	private static function getUnlockKey($user) {
		// This key prevents that the system gets hacked with brute
		// force of the user passwords.
		return sha3_512('ANTI-BRUTEFORCE-AJAX/'.$user.'/'.OIDplus::baseConfig()->getValue('SERVER_SECRET',''));
	}

	private $autoLoginList = array();

	private function shutdownLogout() {
		foreach ($this->autoLoginList as $username) {
			OIDplus::authUtils()::raLogout($username);
		}
	}

	public function init($html=true) {
		if (isset($_SERVER['SCRIPT_FILENAME']) && (basename($_SERVER['SCRIPT_FILENAME']) == 'ajax.php')) {
			$input = array_merge($_POST,$_GET);

			if (!isset($input['batch_login_username'])) return;
			if (!isset($input['batch_login_password'])) return;
			if (!isset($input['batch_ajax_unlock_key'])) return;
			if ($input['batch_ajax_unlock_key'] != self::getUnlockKey($input['batch_login_username'])) return;

			if (!OIDplus::authUtils()::isRaLoggedIn($input['batch_login_username']) && OIDplus::authUtils()::raCheckPassword($input['batch_login_username'], $input['batch_login_password'])) {
				OIDplus::authUtils()::raLogin($input['batch_login_username']);
				$this->autoLoginList[] = $input['batch_login_username'];
				register_shutdown_function(array($this,'shutdownLogout'));
			}
		}
	}

	public function gui($id, &$out, &$handled) {
		if (explode('$',$id)[0] == 'oidplus:automated_ajax_information_ra') {
			$handled = true;

			$ra_email = explode('$',$id)[1];

			$out['title'] = _L('Automated AJAX calls');
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';

			if (!OIDplus::authUtils()::isRaLoggedIn($ra_email) && !OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>'._L('You need to <a %1>log in</a> as the requested RA %2 or as admin.',OIDplus::gui()->link('oidplus:login'),'<b>'.htmlentities($ra_email).'</b>').'</p>';
				return;
			}

			$out['text'] .= '<p>'._L('You can make automated calls to your OIDplus account by calling the AJAX API.').'</p>';
			$out['text'] .= '<p>'._L('The URL for the AJAX script is:').':</p>';
			$out['text'] .= '<p><b>'.OIDplus::getSystemUrl().'ajax.php</b></p>';
			$out['text'] .= '<p>'._L('You must at least provide following fields').':</p>';
			$out['text'] .= '<p><pre>';
			$out['text'] .= 'batch_login_username  = "'.htmlentities($ra_email).'"'."\n";
			$out['text'] .= 'batch_login_password  = "........."'."\n";
			$out['text'] .= 'batch_ajax_unlock_key = "'.$this->getUnlockKey($ra_email).'"'."\n";
			$out['text'] .= '</pre></p>';
			$out['text'] .= '<p>'._L('Please keep this information confidential!').'</p>';
			$out['text'] .= '<p>'._L('The batch-fields will automatically perform a one-time-login to fulfill the request. The other fields are the normal fields which are called during the usual operation of OIDplus.').'</p>';
			$out['text'] .= '<p>'._L('Currently, there is no documentation for the AJAX calls. However, you can look at the <b>script.js</b> files of the plugins to see the field names being used. You can also enable network analysis in your web browser debugger (F12) to see the request headers sent to the server during the operation of OIDplus.').'</p>';
			$out['text'] .= '<h2>'._L('Example for adding OID 2.999.123 using JavaScript').'</h2>';
			$out['text'] .= '<pre>'.htmlentities(file_get_contents(__DIR__.'/examples/example_js.html')).'</pre>';
			$out['text'] .= '<h2>'._L('Example for adding OID 2.999.123 using PHP (located at a foreign server)').'</h2>';
			$out['text'] .= '<pre>'.preg_replace("@<br.*>@ismU","",highlight_file(__DIR__.'/examples/example_php.phps',true)).'</pre>';
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (!$ra_email) return false;
		if (!OIDplus::authUtils()::isRaLoggedIn($ra_email) && !OIDplus::authUtils()::isAdminLoggedIn()) return false;

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