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

if (!defined('IN_OIDPLUS')) die();

define('QUERY_REGISTER_V1',         '1.3.6.1.4.1.37476.2.5.2.1.1.1');
define('QUERY_UNREGISTER_V1',       '1.3.6.1.4.1.37476.2.5.2.1.2.1');
define('QUERY_LISTALLSYSTEMIDS_V1', '1.3.6.1.4.1.37476.2.5.2.1.3.1');

class OIDplusRegistrationWizard extends OIDplusPagePlugin {
	public function type() {
		return 'admin';
	}

	public function priority() {
		return 120;
	}

	public function action(&$handled) {
		// Nothing
	}

	public function cfgSetValue($name, $value) {
		if ($name == 'reg_privacy') {
			if (($value != '0') && ($value != '1') && ($value != '2')) {
				throw new Exception("Please enter either 0, 1 or 2.");
			}
			// Now do a recheck and notify the ViaThinkSoft server
			OIDplus::config()->setValue('reg_last_ping', 0);
			$this->sendRegistrationQuery($value);
		}
	}

	public function gui($id, &$out, &$handled) {
		if ($id === 'oidplus:reg_status') {
			$handled = true;
			$out['title'] = 'Registration status';
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/adminPages/'.basename(__DIR__).'/icon_big.png' : '';

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] .= '<p>You need to <a '.oidplus_link('oidplus:login').'>log in</a> as administrator.</p>';
			} else {
				$out['text'] = '<p>The registration of your OIDplus installation has various advantages: The public key of your system is published, so that users can check the integrity of your data (e.g. signed OID-over-WHOIS requests). You can optionally also enable the automatic publishing of your public OID information to the repository oid-info.com.</p>'.
				               '<p><input type="button" onclick="window.open(\'https://oidplus.viathinksoft.com/reg2/status.php?systemid='.OIDplus::system_id(false).'\',\'_blank\')" value="Check status of the registration and collected data"></p>';

				if (!function_exists('openssl_sign')) {
					$out['text'] .= '<p><font color="red">Error: OpenSSL plugin is missing in PHP. You cannot (un)register your OIDplus instance.</font></p>';
				} else {
					$out['text'] .= '<p>You can adjust your privacy level here:</p><p><select name="reg_privacy" id="reg_privacy">';

					# ---

					$out['text'] .= '<option value="0"';
					if (OIDplus::config()->getValue('reg_privacy') == 0) {
						$out['text'] .= ' selected';
					} else {
						$out['text'] .= '';
					}
					$out['text'] .= '>0 = Register to directory service and automatically publish RA/OID data at oid-info.com</option>';

					# ---

					$out['text'] .= '<option value="1"';
					if (OIDplus::config()->getValue('reg_privacy') == 1) {
						$out['text'] .= ' selected';
					} else {
						$out['text'] .= '';
					}
					$out['text'] .= '>1 = Only register to directory service</option>';

					# ---

					$out['text'] .= '<option value="2"';
					if (OIDplus::config()->getValue('reg_privacy') == 2) {
						$out['text'] .= ' selected';
					} else {
						$out['text'] .= '';
					}
					$out['text'] .= '>2 = Hide system</option>';

					# ---

					$out['text'] .= '</select> <input type="button" value="Change" onclick="crudActionRegPrivacyUpdate()"></p>';

					$out['text'] .= '<p>After clicking "change", your OIDplus installation will contact the ViaThinkSoft server to adjust (add or remove information) your privacy setting. This may take a few minutes.</p>';
				}

				$out['text'] .= '<p><i>Privacy information:</i> Please note that removing your system from the directory does not automatically delete already submitted OIDs to oid-info.com. To remove already submitted OIDs at oid-info.com, please contact the <a href="mailto:admin@oid-info.com">OID-Info Webmaster</a>.';
			}
		}
	}

	public function sendRegistrationQuery($privacy_level=null) {
		if (is_null($privacy_level)) {
			$privacy_level = OIDplus::config()->getValue('reg_privacy');
		}

		$system_url = OIDplus::system_url();

		if ($privacy_level == 2) {
			// The user wants to unregister
			// but we only unregister if we are registered. Check this "anonymously" (i.e. without revealing our system ID)
			if (in_array(OIDplus::system_id(false), explode(';',file_get_contents('https://oidplus.viathinksoft.com/reg2/query.php?query='.QUERY_LISTALLSYSTEMIDS_V1)))) {
				$query = QUERY_UNREGISTER_V1;

				$payload = array(
					"query" => $query, // we must repeat the query because we want to sign it
					"system_id" => OIDplus::system_id(false)
				);

				$signature = '';
				openssl_sign(json_encode($payload), $signature, OIDplus::config()->getValue('oidplus_private_key'));

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
				$res = curl_exec($ch);
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

			$query = QUERY_REGISTER_V1;

			$root_oids = array();
			foreach (OIDplus::getRegisteredObjectTypes() as $ot) {
				if ($ot::ns() == 'oid') {
					$res = OIDplus::db()->query("select id from ".OIDPLUS_TABLENAME_PREFIX."objects where " .
					                            "parent = 'oid:' " .
					                            "order by ".OIDplus::db()->natOrder('id'));
					while ($row = OIDplus::db()->fetch_array($res)) {
						$root_oids[] = substr($row['id'],strlen('oid:'));
					}
				}
			}
			$payload = array(
				"query" => $query, // we must repeat the query because we want to sign it
				"privacy_level" => $privacy_level,
				"system_id" => OIDplus::system_id(false),
				"public_key" => OIDplus::config()->getValue('oidplus_public_key'),
				"system_url" => $system_url,
				"hide_system_url" => 0,
				"hide_public_key" => 0,
				"admin_email" => OIDplus::config()->getValue('admin_email'),
				"system_title" => OIDplus::config()->systemTitle(),
				"oidinfo_xml" => @base64_encode($oidinfo_xml),
				"root_oids" => $root_oids
			);

			$signature = '';
			openssl_sign(json_encode($payload), $signature, OIDplus::config()->getValue('oidplus_private_key'));

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
			$res = curl_exec($ch);
			curl_close($ch);
			// die("RES: $res\n");
			// if ($res == 'OK') ...
		}

		OIDplus::config()->setValue('reg_last_ping', time());
	}

	public function init($html=true) {
		OIDplus::config()->prepareConfigKey('reg_wizard_done', 'Registration wizard done once?', '0', 1, 0);
		OIDplus::config()->prepareConfigKey('reg_privacy', '2=Hide your system, 1=Register your system to the ViaThinkSoft directory and oid-info.com, 0=Publish your system to ViaThinkSoft directory and all public contents (RA/OID) to oid-info.com', '0', 0, 1);
		OIDplus::config()->prepareConfigKey('reg_ping_interval', 'Registration ping interval (in seconds)', '3600', 0, 0);
		OIDplus::config()->prepareConfigKey('reg_last_ping', 'Last ping to ViaThinkSoft directory services', '0', 1, 0);

		if (function_exists('openssl_sign')) {
			// Show registration wizard once

			if ($html && (OIDplus::config()->getValue('reg_wizard_done') != '1')) {
				if (basename($_SERVER['SCRIPT_NAME']) != 'registration.php') {
					if ($system_url = OIDplus::system_url()) {
						header('Location:'.$system_url.'plugins/adminPages/'.basename(__DIR__).'/registration.php');
					} else {
						header('Location:plugins/adminPages/'.basename(__DIR__).'/registration.php');
					}
					die();
				}
			}

			// Is it time to register / renew directory entry?

			if (OIDplus::config()->getValue('reg_wizard_done') == '1') {
				$privacy_level = OIDplus::config()->getValue('reg_privacy');

				if ((time()-OIDplus::config()->getValue('reg_last_ping') >= OIDplus::config()->getValue('reg_ping_interval'))) {
					$this->sendRegistrationQuery();
				}
			}
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = 'plugins/adminPages/'.basename(__DIR__).'/treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:reg_status',
			'icon' => $tree_icon,
			'text' => 'Registration'
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}
}

OIDplus::registerPagePlugin(new OIDplusRegistrationWizard());
