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

class OIDplusPageAdminRegistration extends OIDplusPagePluginAdmin {

	/*private*/ const QUERY_REGISTER_V1 =         '1.3.6.1.4.1.37476.2.5.2.1.1.1';
	/*private*/ const QUERY_UNREGISTER_V1 =       '1.3.6.1.4.1.37476.2.5.2.1.2.1';
	/*private*/ const QUERY_LISTALLSYSTEMIDS_V1 = '1.3.6.1.4.1.37476.2.5.2.1.3.1';
	/*private*/ const QUERY_LIVESTATUS_V1 =       '1.3.6.1.4.1.37476.2.5.2.1.4.1';

	public function gui($id, &$out, &$handled) {
		if ($id === 'oidplus:srv_registration') {
			$handled = true;
			$out['title'] = _L('System registration settings');
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>'._L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login')).'</p>';
				return;
			}

			$out['text'] = file_get_contents(__DIR__ . '/info.tpl');

			if (!OIDplus::getPkiStatus()) {
				$out['text'] .= '<p><font color="red">'._L('Error: Your system could not generate a private/public key pair. (OpenSSL is probably missing on your system). Therefore, you cannot register/unregister your OIDplus instance.').'</font></p>';
			} else {
				$out['text'] .= '<p><input type="button" onclick="openOidInPanel(\'oidplus:srvreg_status\');" value="'._L('Check status of the registration and collected data').'"></p>';

				if (OIDplus::baseConfig()->getValue('REGISTRATION_HIDE_SYSTEM', false)) {
					$out['text'] .= '<p><font color="red"><b>'._L('Attention!').'</b> '._L('<code>REGISTRATION_HIDE_SYSTEM</code> is set in the local configuration file! Therefore, this system will not register itself, despite of the settings below.').'</font></p>';
				}

				$out['text'] .= '<p>'._L('You can adjust your privacy level here').':</p><p><select name="reg_privacy" id="reg_privacy">';

				# ---

				$out['text'] .= '<option value="0"';
				if (OIDplus::config()->getValue('reg_privacy') == 0) {
					$out['text'] .= ' selected';
				} else {
					$out['text'] .= '';
				}
				$out['text'] .= '>'._L('0 = Register to directory service and automatically publish RA/OID data at oid-info.com').'</option>';

				# ---

				$out['text'] .= '<option value="1"';
				if (OIDplus::config()->getValue('reg_privacy') == 1) {
					$out['text'] .= ' selected';
				} else {
					$out['text'] .= '';
				}
				$out['text'] .= '>'._L('1 = Only register to directory service').'</option>';

				# ---

				$out['text'] .= '<option value="2"';
				if (OIDplus::config()->getValue('reg_privacy') == 2) {
					$out['text'] .= ' selected';
				} else {
					$out['text'] .= '';
				}
				$out['text'] .= '>'._L('2 = Hide system').'</option>';

				# ---

				$out['text'] .= '</select> <input type="button" value="'._L('Change').'" onclick="crudActionRegPrivacyUpdate()"></p>';

				$out['text'] .= '<p>'._L('After clicking "change", your OIDplus system will contact the ViaThinkSoft server to adjust (add or remove information) your privacy setting. This may take a few minutes.').'</p>';

				$out['text'] .= '<p>'._L('<i>Privacy information:</i> Please note that removing your system from the directory does not automatically delete information about OIDs which are already published at oid-info.com. To remove already submitted OIDs at oid-info.com, please contact the <a href="mailto:admin@oid-info.com">OID Repository Webmaster</a>.').'</p>';
			}
		}
		if ($id === 'oidplus:srvreg_status') {
			$handled = true;

			$query = self::QUERY_LIVESTATUS_V1;

			$payload = array(
				"query" => $query, // we must repeat the query because we want to sign it
				"system_id" => OIDplus::getSystemId(false)
			);

			$signature = '';
			if (!@openssl_sign(json_encode($payload), $signature, OIDplus::config()->getValue('oidplus_private_key'))) {
				throw new OIDplusException(_L('Signature failed'));
			}

			$data = array(
				"payload" => $payload,
				"signature" => base64_encode($signature)
			);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'https://oidplus.viathinksoft.com/reg2/query.php');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, "query=$query&data=".base64_encode(json_encode($data)));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_AUTOREFERER, true);
			if (!($res = @curl_exec($ch))) {
				throw new OIDplusException(_L('Communication with ViaThinkSoft server failed: %1',curl_error($ch)));
			}
			curl_close($ch);
			// die("RES: $res\n");
			// if ($res == 'OK') ...

			$out['title'] = _L('Registration live status');
			$out['text']  = '<p><a '.OIDplus::gui()->link('oidplus:srv_registration').'><img src="img/arrow_back.png" width="16"> '._L('Go back to registration settings').'</a></p>' .
			                $res;
		}
	}

	public function sendRegistrationQuery($privacy_level=null) {
		if (is_null($privacy_level)) {
			$privacy_level = OIDplus::config()->getValue('reg_privacy');
		}

		$system_url = OIDplus::getSystemUrl();

		// It is very important that we set the ping time NOW, because ViaThinkSoft might contact us during the ping,
		// and this would cause an endless loop!
		OIDplus::config()->setValue('reg_last_ping', time());

		if (!OIDplus::getPkiStatus()) return false;

		if ($privacy_level == 2) {
			// The user wants to unregister
			// but we only unregister if we are registered. Check this "anonymously" (i.e. without revealing our system ID)
			if (in_array(OIDplus::getSystemId(false), explode(';',file_get_contents('https://oidplus.viathinksoft.com/reg2/query.php?query='.self::QUERY_LISTALLSYSTEMIDS_V1)))) {
				$query = self::QUERY_UNREGISTER_V1;

				$payload = array(
					"query" => $query, // we must repeat the query because we want to sign it
					"system_id" => OIDplus::getSystemId(false)
				);

				$signature = '';
				if (!@openssl_sign(json_encode($payload), $signature, OIDplus::config()->getValue('oidplus_private_key'))) {
					return false; // throw new OIDplusException(_L('Signature failed'));
				}

				$data = array(
					"payload" => $payload,
					"signature" => base64_encode($signature)
				);

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, 'https://oidplus.viathinksoft.com/reg2/query.php');
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, "query=$query&data=".base64_encode(json_encode($data)));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($ch, CURLOPT_AUTOREFERER, true);
				if (!($res = @curl_exec($ch))) {
					return false; // throw new OIDplusException(_L('Communication with ViaThinkSoft server failed: %1',curl_error($ch)));
				}
				curl_close($ch);
				// die("RES: $res\n");
				// if ($res == 'OK') ...
			}
		} else {
			if ($privacy_level == 0) {
				if (class_exists('OIDplusPageAdminOIDInfoExport')) {
					ob_start();
					OIDplusPageAdminOIDInfoExport::outputXML(false); // no online check, because the query should be short (since the query is done while a visitor waits for the response)
					$oidinfo_xml = ob_get_contents();
					ob_end_clean();
				} else {
					$oidinfo_xml = false;
				}
			} else {
				$oidinfo_xml = false;
			}

			$query = self::QUERY_REGISTER_V1;

			$root_oids = array();
			foreach (OIDplus::getEnabledObjectTypes() as $ot) {
				if ($ot::ns() == 'oid') {
					$res = OIDplus::db()->query("select id from ###objects where " .
					                            "parent = 'oid:' " .
					                            "order by ".OIDplus::db()->natOrder('id'));
					while ($row = $res->fetch_array()) {
						$root_oids[] = substr($row['id'],strlen('oid:'));
					}
				}
			}
			$payload = array(
				"query" => $query, // we must repeat the query because we want to sign it
				"privacy_level" => $privacy_level,
				"system_id" => OIDplus::getSystemId(false),
				"public_key" => OIDplus::config()->getValue('oidplus_public_key'),
				"system_url" => $system_url,
				"hide_system_url" => 0,
				"hide_public_key" => 0,
				"admin_email" => OIDplus::config()->getValue('admin_email'),
				"system_title" => OIDplus::config()->getValue('system_title'),
				"oidinfo_xml" => @base64_encode($oidinfo_xml),
				"root_oids" => $root_oids,
				"system_version" => OIDplus::getVersion(),
				"system_install_type" => OIDplus::getInstallType()
			);

			$signature = '';
			if (!@openssl_sign(json_encode($payload), $signature, OIDplus::config()->getValue('oidplus_private_key'))) {
					return false; // throw new OIDplusException(_L('Signature failed'));
			}

			$data = array(
				"payload" => $payload,
				"signature" => base64_encode($signature)
			);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'https://oidplus.viathinksoft.com/reg2/query.php');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, "query=$query&data=".base64_encode(json_encode($data)));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_AUTOREFERER, true);
			if (!($res = @curl_exec($ch))) {
				return false; // throw new OIDplusException(_L('Communication with ViaThinkSoft server failed: %1',curl_error($ch)));
			}
			curl_close($ch);

			if ($res === 'HASH_CONFLICT') {
				OIDplus::logger()->log("[WARN]A!", "Removing SystemID and key pair because there is a hash conflict with another OIDplus system!");

				// Delete the system ID since we have a conflict with the 31-bit hash!
				OIDplus::config()->setValue('oidplus_private_key', '');
				OIDplus::config()->setValue('oidplus_public_key', '');

				// Try to generate a new system ID
				OIDplus::getPkiStatus(true);

				// Enforce a new registration attempt at the next run
				// We will not try again here, because that might lead to an endless loop if the VTS server would always return 'HASH_CONFLCIT'
				OIDplus::config()->setValue('reg_last_ping', 0);
			}

			// die("RES: $res\n");
			// if ($res == 'OK') ...
		}
	}

	public function init($html=true) {
		OIDplus::config()->prepareConfigKey('reg_privacy', '2=Hide your system, 1=Register your system to the ViaThinkSoft directory and oid-info.com, 0=Publish your system to ViaThinkSoft directory and all public contents (RA/OID) to oid-info.com', '0', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (($value != '0') && ($value != '1') && ($value != '2')) {
				throw new OIDplusException(_L('Please enter either 0, 1 or 2.'));
			}
			// Now do a recheck and notify the ViaThinkSoft server
			if (($value == 2) || !OIDplus::baseConfig()->getValue('REGISTRATION_HIDE_SYSTEM', false)) {
				OIDplus::config()->setValue('reg_last_ping', 0);
				$this->sendRegistrationQuery($value);
			}
		});
		OIDplus::config()->prepareConfigKey('reg_ping_interval', 'Registration ping interval (in seconds)', '3600', OIDplusConfig::PROTECTION_HIDDEN, function($value) {

		});
		OIDplus::config()->prepareConfigKey('reg_last_ping', 'Last ping to ViaThinkSoft directory services', '0', OIDplusConfig::PROTECTION_HIDDEN, function($value) {

		});

		// Is it time to register / renew the directory entry?
		// Note: REGISTRATION_HIDE_SYSTEM is an undocumented constant that can be put in the userdata/baseconfig/config.inc.php files of a test system accessing the same database as the productive system that is registered.
		// This avoids that the URL of a productive system is overridden with the URL of a cloned test system (since they use the same database, they also have the same system ID)

		if (!OIDplus::baseConfig()->getValue('REGISTRATION_HIDE_SYSTEM', false)) {
			$privacy_level = OIDplus::config()->getValue('reg_privacy');

			if (php_sapi_name() !== 'cli') { // don't register when called from CLI, otherwise the oidinfo XML can't convert relative links into absolute links
				if ((time()-OIDplus::config()->getValue('reg_last_ping') >= OIDplus::config()->getValue('reg_ping_interval'))) {
					$this->sendRegistrationQuery();
				}
			}
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
			'id' => 'oidplus:srv_registration',
			'icon' => $tree_icon,
			'text' => _L('System registration')
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}

	public function implementsFeature($id) {
		if (strtolower($id) == '1.3.6.1.4.1.37476.2.5.2.3.1') return true; // oobeEntry
		return false;
	}

	public function oobeEntry($step, $do_edits, &$errors_happened)/*: void*/ {
		// Interface 1.3.6.1.4.1.37476.2.5.2.3.1

		echo '<p><u>'._L('Step %1: System registration and automatic publishing (optional)',$step).'</u></p>';

		echo file_get_contents(__DIR__ . '/info.tpl');

		if (!function_exists('curl_exec')) {
			echo '<p><font color="red">';
			echo _L('Note: The "CURL" PHP extension is not installed at your system. Please enable the PHP extension <code>php_curl</code>.');
			echo _L('Therefore, you <b>cannot</b> register your OIDplus instance now.');
			echo '</font></p>';
			return;
		}

		$testurl = 'https://www.google.com/';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $testurl);
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		curl_setopt($ch, CURLOPT_NOBODY, TRUE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if (!$httpCode) {
			echo '<p><font color="red">';
			echo _L('Note: The "CURL" PHP extension cannot access HTTPS webpages. Therefore, you cannot use this feature. Please download <a href="https://curl.haxx.se/ca/cacert.pem">cacert.pem</a>, place it somewhere and then adjust the setting <code>curl.cainfo</code> in PHP.ini.');
			echo _L('Therefore, you <b>cannot</b> register your OIDplus instance now.');
			echo '</font></p>';
			return;
		}

		$pki_status = OIDplus::getPkiStatus();

		if (!$pki_status) {
			echo '<p><font color="red">';
			echo _L('Note: Your system could not generate a private/public key pair. (OpenSSL is probably missing on your system).');
			echo _L('Therefore, you <b>cannot</b> register your OIDplus instance now.');
			echo '</font></p>';
			return;
		}

		echo '<p>'._L('Privacy level').':</p><select name="reg_privacy" id="reg_privacy">';

		# ---

		echo '<option value="0"';
		if (isset($_REQUEST['sent'])) {
			if (isset($_REQUEST['reg_privacy']) && ($_REQUEST['reg_privacy'] == 0)) echo ' selected';
		} else {
			if ((OIDplus::config()->getValue('reg_privacy') == 0) || !OIDplus::config()->getValue('reg_wizard_done')) {
				echo ' selected';
			} else {
				echo '';
			}
		}
		echo '>'._L('0 = Register to directory service and automatically publish RA/OID data at oid-info.com').'</option>';

		# ---

		echo '<option value="1"';
		if (isset($_REQUEST['sent'])) {
			if (isset($_REQUEST['reg_privacy']) && ($_REQUEST['reg_privacy'] == 1)) echo ' selected';
		} else {
			if ((OIDplus::config()->getValue('reg_privacy') == 1)) {
				echo ' selected';
			} else {
				echo '';
			}
		}
		echo '>'._L('1 = Only register to directory service').'</option>';

		# ---

		echo '<option value="2"';
		if (isset($_REQUEST['sent'])) {
			if (isset($_REQUEST['reg_privacy']) && ($_REQUEST['reg_privacy'] == 2)) echo ' selected';
		} else {
			if ((OIDplus::config()->getValue('reg_privacy') == 2)) {
				echo ' selected';
			} else {
				echo '';
			}
		}
		echo '>'._L('2 = Hide system').'</option>';

		# ---

		echo '</select>';

		$msg = '';
		if ($do_edits) {
			try {
				OIDplus::config()->setValue('reg_privacy', $_REQUEST['reg_privacy']);
			} catch (Exception $e) {
				$msg = $e->getMessage();
				$errors_happened = true;
			}
		}
		echo ' <font color="red"><b>'.$msg.'</b></font>';

		echo '<p>'._L('<i>Privacy information:</i> This setting can always be changed in the administrator login / control panel.').'<br>';
		echo _L('<a %1>Click here</a> for more information about privacy related topics.','href="../../../res/OIDplus/privacy_documentation.html" target="_blank"');
		echo '</p>';
	}

}