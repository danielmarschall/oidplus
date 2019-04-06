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

class OIDplusRegistrationWizard extends OIDplusPagePlugin {
	public function type() {
		return 'system';
	}

	public function priority() {
		return 000;
	}

	public function action(&$handled) {
		// Nothing
	}

	public function cfgLoadConfig() {
		OIDplus::db()->query("insert into ".OIDPLUS_TABLENAME_PREFIX."config (name, description, value, protected, visible) values ('registration_done', 'Registration wizard done once?', '0', 1, 0)");
		OIDplus::db()->query("insert into ".OIDPLUS_TABLENAME_PREFIX."config (name, description, value, protected, visible) values ('reg_enabled', 'Register your system to the ViaThinkSoft directory?', '0', 0, 1)");
		OIDplus::db()->query("insert into ".OIDPLUS_TABLENAME_PREFIX."config (name, description, value, protected, visible) values ('reg_ping_interval', 'Registration ping interval', '3600', 0, 0)");
		OIDplus::db()->query("insert into ".OIDPLUS_TABLENAME_PREFIX."config (name, description, value, protected, visible) values ('reg_last_ping', 'Last ping to ViaThinkSoft directory services', '0', 1, 0)");
	}

	public function cfgSetValue($name, $value) {
		if ($name == 'reg_enabled') {
			if (($value != '0') && ($value != '1')) {
				throw new Exception("Please enter either 0 or 1.");
			}
		}
	}

	public function gui($id, &$out, &$handled) {
		// nothing
	}

	public function tree(&$json, $ra_email=null) {
		// nothing
	}

	public function init($html=true) {
		if (function_exists('openssl_sign')) {
			// This is what we answer to the ViaThinkSoft server

			if (isset($_REQUEST['vts_regqry'])) {
				$payload = array(
					"version" => 1,
					"vts_directory_listing" => OIDplus::config()->getValue('reg_enabled') ? true : false,
					"oidinfo_xml_unlocked" => OIDplus::config()->exists('oidinfo_export_protected') && !OIDplus::config()->getValue('oidinfo_export_protected') ? true : false,
					"oidinfo_xml_location" => 'plugins/adminPages/400_oidinfo_export/oidinfo_export.php?online=1'
				);

				$signature = '';
				openssl_sign(json_encode($payload), $signature, OIDplus::config()->getValue('oidplus_private_key'));

				$data = array(
					"payload" => $payload,
				"signature" => base64_encode($signature)
				);

				header_remove('Content-Type');
				header('Content-Type: application/json');
				die(json_encode($data));
			}

			// Show registration wizard once

			if ($html && (OIDplus::config()->getValue('registration_done') != '1')) {
				if (basename($_SERVER['SCRIPT_NAME']) != 'registration.php') {
					header('Location:plugins/system/'.basename(__DIR__).'/registration.php');
					die();
				}
			}

			// Is it time to register / renew directory entry?

			if ((OIDplus::config()->getValue('reg_enabled')) &&
			   (time()-OIDplus::config()->getValue('reg_last_ping') >= OIDplus::config()->getValue('reg_ping_interval'))) {
				if ($system_url = OIDplus::system_url()) {
					$payload = array(
						"system_id" => OIDplus::system_id(false),
						"public_key" => OIDplus::config()->getValue('oidplus_public_key'),
						"system_url" => $system_url,
						"hide_system_url" => 0,
						"hide_public_key" => 0
					);

					$signature = '';
					openssl_sign(json_encode($payload), $signature, OIDplus::config()->getValue('oidplus_private_key'));

					$data = array(
						"payload" => $payload,
						"signature" => base64_encode($signature)
					);

					$res = file_get_contents('https://oidplus.viathinksoft.com/reg/register.php?data='.base64_encode(json_encode($data)));
					// die("RES: $res\n");
					// if ($res == 'OK') ...

					OIDplus::config()->setValue('reg_last_ping', time());
				}
			}
		}
	}
}

OIDplus::registerPagePlugin(new OIDplusRegistrationWizard());
