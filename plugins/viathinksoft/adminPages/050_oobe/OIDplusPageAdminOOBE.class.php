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

class OIDplusPageAdminOOBE extends OIDplusPagePluginAdmin {

	public function gui($id, &$out, &$handled) {
		// Nothing
	}

	public function oobeRequired() {
		$oobe_done = OIDplus::config()->getValue('oobe_main_done') == '1';

		foreach (OIDplus::getAllPlugins() as $plugin) {
			if ($plugin->implementsFeature('1.3.6.1.4.1.37476.2.5.2.3.1')) {
				if ($plugin->oobeRequested()) {
					$oobe_done = false;
					break;
				}
			}
		}

		return !$oobe_done;
	}

	public function init($html=true) {
		OIDplus::config()->delete('reg_wizard_done'); // deprecated name
		OIDplus::config()->prepareConfigKey('oobe_main_done', '"Out Of Box Experience" wizard for the system settings done once?', '0', OIDplusConfig::PROTECTION_HIDDEN, function($value) {});

		if ($this->oobeRequired()) {
			if ($html) {
				// Show registration/configuration wizard once
				if (basename($_SERVER['SCRIPT_NAME']) != 'oobe.php') {
					header('Location:'.OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'oobe.php');
					die(_L('Redirecting to Out-Of-Box-Experience wizard...'));
				}
			} else {
				// In the OOBE, "get_challenge" of the ViaThinkSoft Captcha will raise the error:
				// "A plugin has requested that the initialization wizard (OOBE) is shown. Please reload the page."
				// So we must not continue if the referrer is OOBE.
				if (isset($_SERVER['HTTP_REFERER']) && (strpos($_SERVER['HTTP_REFERER'],'050_oobe/oobe.php') !== false)) return;

				// This is another very special case...
				// When the Registration OOBE is saved, the 'reg_privacy" setting will be
				// set, which will call the ViaThinkSoft server. The ViaThinkSoft server
				// then calls the "verify pubkey" action, but this fails because
				// the system is still in OOBE mode!
				if (basename($_SERVER['SCRIPT_NAME']) == 'ajax.php') {
					$req = array_merge($_POST,$_GET);
					if (($req['plugin'] == '1.3.6.1.4.1.37476.2.5.2.4.3.120') && ($req['action'] = 'verify_pubkey')) {
						return;
					}
				}

				// We cannot guarantee that everything works correctly if OOBE never ran once. So abort AJAX and co.
				throw new OIDplusException(_L('A plugin has requested that the initialization wizard (OOBE) is shown. Please reload the page.'));
			}
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		return true;
	}

	public function tree_search($request) {
		return false;
	}
}
