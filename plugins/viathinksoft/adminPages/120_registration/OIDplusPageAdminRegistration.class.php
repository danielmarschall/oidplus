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

namespace ViaThinkSoft\OIDplus\Plugins\AdminPages\Registration;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusConfig;
use ViaThinkSoft\OIDplus\Core\OIDplusConfigInitializationException;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusHtmlException;
use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginAdmin;
use ViaThinkSoft\OIDplus\Plugins\AdminPages\Notifications\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8;
use ViaThinkSoft\OIDplus\Plugins\AdminPages\Notifications\OIDplusNotification;
use ViaThinkSoft\OIDplus\Plugins\AdminPages\OOBE\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_1;
use ViaThinkSoft\OIDplus\Plugins\AdminPages\OidInfoExport\OIDplusPageAdminOIDInfoExport;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusPageAdminRegistration extends OIDplusPagePluginAdmin
	implements INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_1, /* oobeRequested, oobeEntry */
	           INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8  /* getNotifications */
{

	/**
	 *
	 */
	private const QUERY_REGISTER_V1 =         '1.3.6.1.4.1.37476.2.5.2.1.1.1';

	/**
	 *
	 */
	private const QUERY_UNREGISTER_V1 =       '1.3.6.1.4.1.37476.2.5.2.1.2.1';

	/**
	 *
	 */
	private const QUERY_LISTALLSYSTEMIDS_V1 = '1.3.6.1.4.1.37476.2.5.2.1.3.1';

	/**
	 *
	 */
	private const QUERY_LIVESTATUS_V1 =       '1.3.6.1.4.1.37476.2.5.2.1.4.1';

	/**
	 * @param string $actionID
	 * @return bool
	 */
	public function csrfUnlock(string $actionID): bool {
		if ($actionID == 'verify_pubkey') return true;
		return parent::csrfUnlock($actionID);
	}

	/**
	 * This action is called by the ViaThinkSoft server in order to verify that the system is in the ownership of the correct private key
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 */
	private function action_VerifyPubKey(array $params): array {
		_CheckParamExists($params, 'challenge');

		$payload = 'oidplus-verify-pubkey:'.sha3_512($params['challenge']);

		$signature = '';
		if (!OIDplus::getPkiStatus() || !@openssl_sign($payload, $signature, OIDplus::getSystemPrivateKey())) {
			throw new OIDplusException(_L('Signature failed'));
		}

		return array(
			"status" => 0,
			"response" => base64_encode($signature)
		);
	}

	/**
	 * @param string $actionID
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 */
	public function action(string $actionID, array $params): array {
		if ($actionID == 'verify_pubkey') {
			return $this->action_VerifyPubKey($params);
		} else {
			return parent::action($actionID, $params);
		}
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
		if ($id === 'oidplus:srv_registration') {
			$handled = true;
			$out['title'] = _L('System registration settings');
			$out['icon'] = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')), $out['title'], 401);
			}

			if (file_exists(__DIR__ . '/info$'.OIDplus::getCurrentLang().'.html')) {
				$info = file_get_contents(__DIR__ . '/info$'.OIDplus::getCurrentLang().'.html');
			} else {
				$info = file_get_contents(__DIR__ . '/info.html');
			}

			list($html, $js, $css) = extractHtmlContents($info);
			$info = '';
			if (!empty($js))  $info .= "<script>\n$js\n</script>";
			if (!empty($css)) $info .= "<style>\n$css\n</style>";
			$info .= stripHtmlComments($html);

			$out['text'] = $info;

			if (!OIDplus::getPkiStatus()) {
				$out['text'] .= '<p><font color="red">'._L('Error: Your system could not generate a private/public key pair. (OpenSSL is probably missing on your system). Therefore, you cannot register/unregister your OIDplus instance.').'</font></p>';
			} else if (!url_post_contents_available(true, $reason)) {
				$out['text'] .= '<p><font color="red">';
				$out['text'] .= _L('OIDplus cannot connect to the Internet (%1). Therefore, you <b>cannot</b> register your OIDplus instance now.', $reason);
				$out['text'] .= '</font></p>';
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

				$out['text'] .= '</select> <input type="button" value="'._L('Change').'" onclick="OIDplusPageAdminRegistration.crudActionRegPrivacyUpdate()"></p>';

				$out['text'] .= '<p>'._L('After clicking "change", your OIDplus system will contact the ViaThinkSoft server to adjust (add or remove information) your privacy setting. This may take a few minutes.').'</p>';

				$out['text'] .= '<p>'._L('<i>Privacy information:</i> Please note that removing your system from the directory does not automatically delete information about OIDs which are already published at oid-info.com. To remove already submitted OIDs at oid-info.com, please contact the <a href="mailto:admin@oid-info.com">OID Repository Webmaster</a>.').'</p>';
			}
		}
		if ($id === 'oidplus:srvreg_status') {
			$handled = true;
			$out['title'] = _L('Registration live status');
			$out['icon'] = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')), $out['title'], 401);
			}

			$query = self::QUERY_LIVESTATUS_V1;

			$payload = array(
				"query" => $query, // we must include $query to the playload, because we want to sign it
				"lang" => OIDplus::getCurrentLang(),
				"system_id" => OIDplus::getSystemId(false)
			);

			$signature = '';
			if (!OIDplus::getPkiStatus() || !@openssl_sign(json_encode($payload), $signature, OIDplus::getSystemPrivateKey())) {
				throw new OIDplusException(_L('Signature failed'));
			}

			$data = array(
				"payload" => $payload,
				"signature" => base64_encode($signature)
			);

			if (function_exists('gzdeflate')) {
				$compressed = "1";
				$data2 = gzdeflate(json_encode($data));
			} else {
				$compressed = "0";
				$data2 = json_encode($data);
			}

			$res = url_post_contents(
				'https://www.oidplus.com/reg2/query.php',
				array(
					"query"      => $query,
					"compressed" => $compressed,
					"data"       => base64_encode($data2)
				)
			);

			if ($res === false) {
				throw new OIDplusException(_L('Communication with %1 server failed', 'ViaThinkSoft'));
			}

			$json = @json_decode($res, true);

			if (!$json) {
				throw new OIDplusException(_L('JSON reply from ViaThinkSoft decoding error: %1',$res), $out['title']);
			}

			if (isset($json['error']) || ($json['status'] < 0)) {
				if (isset($json['error'])) {
					throw new OIDplusException(_L('Received error status code: %1',$json['error']), $out['title']);
				} else {
					throw new OIDplusException(_L('Received error status code: %1',$json['status']), $out['title']);
				}
			}

			$out['text']  = '<p><a '.OIDplus::gui()->link('oidplus:srv_registration').'><img src="img/arrow_back.png" width="16" alt="'._L('Go back').'"> '._L('Go back to registration settings').'</a></p>' .
			                $json['content'];
		}
	}

	/**
	 * @return bool
	 * @throws OIDplusException
	 */
	protected function areWeRegistered(): bool {
		// To check if we are registered. Check it "anonymously" (i.e. without revealing our system ID)
		$res = url_get_contents('https://www.oidplus.com/reg2/query.php?query='.self::QUERY_LISTALLSYSTEMIDS_V1);
		if ($res === false) return false;

		$json = @json_decode($res, true);

		if (!$json) {
			return false; // throw new OIDplusException(_L('JSON reply from ViaThinkSoft decoding error: %1',$res));
		}

		if (isset($json['error']) || ($json['status'] < 0)) {
			if (isset($json['error'])) {
				return false; // throw new OIDplusException(_L('Received error status code: %1',$json['error']));
			} else {
				return false; // throw new OIDplusException(_L('Received error status code: %1',$json['status']));
			}
		}

		$list = $json['list'];

		return in_array(OIDplus::getSystemId(false), $list);
	}

	/**
	 * @param int|null $privacy_level
	 * @return false|void
	 * @throws OIDplusException|\OIDInfoException
	 */
	public function sendRegistrationQuery(?int $privacy_level=null) {

		if (is_null($privacy_level)) {
			$privacy_level = OIDplus::config()->getValue('reg_privacy');
		}

		$system_url = OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL);

		// It is very important that we set the ping time NOW, because ViaThinkSoft might contact us during the ping,
		// and this would cause an endless loop!
		OIDplus::config()->setValue('reg_last_ping', time());

		if (!OIDplus::getPkiStatus()) return false;

		if (!url_post_contents_available(true)) return false;

		if ($privacy_level == 2) {
			// The user wants to unregister,  but we only unregister if we are registered
			if ($this->areWeRegistered()) {
				$query = self::QUERY_UNREGISTER_V1;

				$payload = array(
					"query" => $query, // we must include $query to the payload, because we want to sign it
					"system_id" => OIDplus::getSystemId(false)
				);

				$signature = '';
				if (!OIDplus::getPkiStatus() || !@openssl_sign(json_encode($payload), $signature, OIDplus::getSystemPrivateKey())) {
					return false; // throw new OIDplusException(_L('Signature failed'));
				}

				$data = array(
					"payload" => $payload,
					"signature" => base64_encode($signature)
				);

				if (function_exists('gzdeflate')) {
					$compressed = "1";
					$data2 = gzdeflate(json_encode($data));
				} else {
					$compressed = "0";
					$data2 = json_encode($data);
				}

				$res = url_post_contents(
					'https://www.oidplus.com/reg2/query.php',
					array(
						"query" => $query,
						"compressed" => $compressed,
						"data" => base64_encode($data2)
					)
				);

				if ($res === false) return false; // throw new OIDplusException(_L('Communication with %1 server failed', 'ViaThinkSoft'));

				$json = @json_decode($res, true);

				if (!$json) {
					return false; // throw new OIDplusException(_L('JSON reply from ViaThinkSoft decoding error: %1',$res));
				}

				if (isset($json['error']) || ($json['status'] < 0)) {
					return false; // throw new OIDplusException(_L('Received error status code: %1',isset($json['error']) ? $json['error'] : $json['status']));
				}
			}
		} else {
			if ($privacy_level == 0) {
				$adminExportPlugin = OIDplus::getPluginByOid('1.3.6.1.4.1.37476.2.5.2.4.3.400'); // OIDplusPageAdminOIDInfoExport
				if (!is_null($adminExportPlugin)) {
					list($oidinfo_xml, $dummy_content_type) = OIDplusPageAdminOIDInfoExport::outputXML(false); // no online check, because the query should be short (since the query is done while a visitor waits for the response)
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
					                            "(parent = 'oid:' or " .
					                            // The following two cases are special cases e.g. if there are multiple PEN or UUID-OIDs, and the system owner decides to use the IANA PEN as root OID, but actually, it is not "his" root then! The OIDs inside the IANA PEN root are his root then!
					                            "parent in (select oid from ###asn1id where well_known = ?) or " .
					                            "parent in (select oid from ###iri where well_known = ?)) and " .
					                            // We assume hereby that RAs of well-known OIDs (e.g. IANA) will not use OIDplus for allocating OIDs:
					                            "id not in (select oid from ###asn1id where well_known = ?) and " .
					                            "id not in (select oid from ###iri where well_known = ?)", array(true, true, true, true));
					$res->naturalSortByField('id');
					while ($row = $res->fetch_array()) {
						$root_oids[] = substr($row['id'],strlen('oid:'));
					}
				}
			}
			$payload = array(
				"query" => $query, // we must include $query to the payload, because we want to sign it
				"privacy_level" => $privacy_level,
				"system_id" => OIDplus::getSystemId(false),
				"public_key" => OIDplus::getSystemPublicKey(),
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
			if (!OIDplus::getPkiStatus() || !@openssl_sign(json_encode($payload), $signature, OIDplus::getSystemPrivateKey())) {
				return false; // throw new OIDplusException(_L('Signature failed'));
			}

			$data = array(
				"payload" => $payload,
				"signature" => base64_encode($signature)
			);

			if (function_exists('gzdeflate')) {
				$compressed = "1";
				$data2 = gzdeflate(json_encode($data));
			} else {
				$compressed = "0";
				$data2 = json_encode($data);
			}

			$res = url_post_contents(
				'https://www.oidplus.com/reg2/query.php',
				array(
					"query"      => $query,
					"compressed" => $compressed,
					"data"       => base64_encode($data2)
				)
			);

			if ($res === false) return false; // throw new OIDplusException(_L('Communication with %1 server failed', 'ViaThinkSoft'));

			$json = @json_decode($res, true);

			if (!$json) {
				return false; // throw new OIDplusException(_L('JSON reply from ViaThinkSoft decoding error: %1',$res));
			}

			if (isset($json['error']) || ($json['status'] < 0)) {
				if (isset($json['error'])) {
					return false; // throw new OIDplusException(_L('Received error status code: %1',$json['error']));
				} else {
					return false; // throw new OIDplusException(_L('Received error status code: %1',$json['status']));
				}
			} else if ($json['status'] == 99/*Hash conflict*/) {
				OIDplus::logger()->log("V2:[WARN]A", "Removing SystemID and key pair because there is a hash conflict with another OIDplus system!");

				// Delete the system ID since we have a conflict with the 31-bit hash!
				OIDplus::config()->setValue('oidplus_private_key', '');
				OIDplus::config()->setValue('oidplus_public_key', '');

				// Try to generate a new system ID
				OIDplus::getPkiStatus(true);

				// Enforce a new registration attempt at the next page visit
				// We will not try again here, because that might lead to an endless loop if the VTS server would always return 'HASH_CONFLCIT'
				OIDplus::config()->setValue('reg_last_ping', 0);
			} else if ($json['status'] == 0/*OK*/) {
				// Note: whois.viathinksoft.de:43 uses VGWhoIs, which uses these patterns: https://github.com/danielmarschall/vgwhois/blob/master/main/pattern/oid
				// If your system gets acknowledged by ViaThinkSoft, then vts_whois will be filled with that server name whois.viathinksoft.de:43
				if (isset($json['vts_whois'])) OIDplus::config()->setValue('vts_whois', $json['vts_whois']);

				// ViaThinkSoft certifies the system public key and other system attributes and root objects (requires human verification)
				if (isset($json['vts_cert'])) OIDplus::config()->setValue('vts_cert', $json['vts_cert']);
				if (isset($json['vts_ca'])) OIDplus::config()->setValue('vts_ca', $json['vts_ca']);
			}
		}
	}

	/**
	 * @param bool $html
	 * @return void
	 * @throws OIDplusException
	 */
	public function init(bool $html=true): void {
		if (OIDplus::getEditionInfo()['vendor'] != 'ViaThinkSoft') {
			throw new OIDplusException(_L('This plugin is only available in the ViaThinkSoft edition of OIDplus'));
		}

		// Note: It is important that the default value is '2', otherwise, systems which don't have CURL will fail
		OIDplus::config()->prepareConfigKey('reg_privacy', '2=Hide your system, 1=Register your system to the ViaThinkSoft directory and oid-info.com, 0=Publish your system to ViaThinkSoft directory and all public contents (RA/OID) to oid-info.com', '2', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (($value != '0') && ($value != '1') && ($value != '2')) {
				throw new OIDplusException(_L('Please enter either 0, 1 or 2.'));
			}
			// Now do a recheck and notify the ViaThinkSoft server
			if (($value == 2) || !OIDplus::baseConfig()->getValue('REGISTRATION_HIDE_SYSTEM', false)) {
				OIDplus::config()->setValue('reg_last_ping', 0);
				if (!url_post_contents_available(true, $reason)) throw new OIDplusException(_L('The system cannot contact the ViaThinkSoft server to change the registration settings.').' '.$reason);
				$this->sendRegistrationQuery($value);
			}
		});
		OIDplus::config()->prepareConfigKey('reg_ping_interval', 'Registration ping interval (in seconds)', '3600', OIDplusConfig::PROTECTION_HIDDEN, function($value) {

		});
		OIDplus::config()->prepareConfigKey('reg_last_ping', 'Last ping to ViaThinkSoft directory services', '0', OIDplusConfig::PROTECTION_HIDDEN, function($value) {

		});
		OIDplus::config()->prepareConfigKey('vts_whois', 'ViaThinkSoft Whois Server (if this system is recognized)', '', OIDplusConfig::PROTECTION_READONLY, function($value) {

		});
		OIDplus::config()->prepareConfigKey('vts_cert', 'ViaThinkSoft certificate (requires registration)', '', OIDplusConfig::PROTECTION_HIDDEN, function($value) {

		});
		OIDplus::config()->prepareConfigKey('vts_ca', 'ViaThinkSoft certificate root (requires registration)', '', OIDplusConfig::PROTECTION_HIDDEN, function($value) {

		});
		OIDplus::config()->prepareConfigKey('oobe_registration_done', '"Out Of Box Experience" wizard for OIDplusPageAdminRegistration done once?', '0', OIDplusConfig::PROTECTION_HIDDEN, function($value) {});

		// Is it time to register / renew the directory entry?
		// Note: REGISTRATION_HIDE_SYSTEM is an undocumented constant that can be put in the userdata/baseconfig/config.inc.php files of a test system accessing the same database as the productive system that is registered.
		// This avoids that the URL of a productive system is overridden with the URL of a cloned test system (since they use the same database, they also have the same system ID)

		if (OIDplus::config()->getValue('oobe_registration_done') == '1') {
			if (!OIDplus::baseConfig()->getValue('REGISTRATION_HIDE_SYSTEM', false)) {
				$privacy_level = OIDplus::config()->getValue('reg_privacy');

				if (PHP_SAPI !== 'cli') { // don't register when called from CLI, otherwise the oidinfo XML can't convert relative links into absolute links
					$last_ping = OIDplus::config()->getValue('reg_last_ping');
					if (!is_numeric($last_ping)) $last_ping = 0;
					$last_ping_interval = OIDplus::config()->getValue('reg_ping_interval');
					if (!is_numeric($last_ping_interval)) $last_ping_interval = 3600;

					// Cronjobs get half ping interval, to make sure that a web visitor won't get any delay
					if (OIDplus::isCronjob()) $last_ping_interval /= 2;

					if ((time()-$last_ping >= $last_ping_interval)) {
						try {
							$this->sendRegistrationQuery();
						} catch (\Exception $e) {
							// Don't do anything, because we don't want that a failed registration query blocks the system
							OIDplus::logger()->log('V2:[WARN]A', 'System registration query crashed: %1', $e->getMessage());
						}
					}
				}
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
		if (!OIDplus::authUtils()->isAdminLoggedIn()) return false;

		if (file_exists(__DIR__.'/img/main_icon16.png')) {
			$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png';
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

	/**
	 * @param string $request
	 * @return array|false
	 */
	public function tree_search(string $request) {
		return false;
	}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_1
	 * @return bool
	 * @throws OIDplusException
	 */
	public function oobeRequested(): bool {
		return OIDplus::config()->getValue('oobe_registration_done') == '0';
	}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_1
	 * @param int $step
	 * @param bool $do_edits
	 * @param bool $errors_happened
	 * @return void
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	public function oobeEntry(int $step, bool $do_edits, bool &$errors_happened): void {
		echo '<h2>'._L('Step %1: System registration and automatic publishing (optional)',$step).'</h2>';

		if (file_exists(__DIR__ . '/info$'.OIDplus::getCurrentLang().'.html')) {
			$info = file_get_contents(__DIR__ . '/info$'.OIDplus::getCurrentLang().'.html');
		} else {
			$info = file_get_contents(__DIR__ . '/info.html');
		}

		// make sure the program works even if the user provided HTML is not UTF-8
		$info = convert_to_utf8_no_bom($info);

		echo $info;

		if (!url_post_contents_available(true, $reason)) {
			echo '<p><font color="red">';
			echo _L('OIDplus cannot connect to the Internet (%1). Therefore, you <b>cannot</b> register your OIDplus instance now.', $reason);
			echo '</font></p>';
			if ($do_edits) {
				OIDplus::config()->setValue('oobe_registration_done', '1');
			}
			return;
		}

		$pki_status = OIDplus::getPkiStatus();

		if (!$pki_status) {
			echo '<p><font color="red">';
			echo _L('Your system could not generate a private/public key pair. (OpenSSL is probably missing on your system).').' ';
			echo _L('Therefore, you <b>cannot</b> register your OIDplus instance now.');
			echo '</font></p>';
			if ($do_edits) {
				OIDplus::config()->setValue('oobe_registration_done', '1');
			}
			return;
		}

		echo '<p>'._L('Privacy level').':</p><select name="reg_privacy" id="reg_privacy">';

		# ---

		echo '<option value="0"';
		if (isset($_POST['sent'])) {
			if (isset($_POST['reg_privacy']) && ($_POST['reg_privacy'] == 0)) echo ' selected';
		} else {
			if ((OIDplus::config()->getValue('reg_privacy') == 0) || !OIDplus::config()->getValue('oobe_registration_done')) {
				echo ' selected';
			} else {
				echo '';
			}
		}
		echo '>'._L('0 = Register to directory service and automatically publish RA/OID data at oid-info.com').'</option>';

		# ---

		echo '<option value="1"';
		if (isset($_POST['sent'])) {
			if (isset($_POST['reg_privacy']) && ($_POST['reg_privacy'] == 1)) echo ' selected';
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
		if (isset($_POST['sent'])) {
			if (isset($_POST['reg_privacy']) && ($_POST['reg_privacy'] == 2)) echo ' selected';
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

		$htmlmsg = '';
		if ($do_edits) {
			try {
				OIDplus::config()->setValue('reg_privacy', $_POST['reg_privacy'] ?? 1);
				OIDplus::config()->setValue('oobe_registration_done', '1');
			} catch (\Exception $e) {
				$htmlmsg = $e instanceof OIDplusException ? $e->getHtmlMessage() : htmlentities($e->getMessage());
				$errors_happened = true;
			}
		}
		if (!empty($htmlmsg)) echo ' <font color="red"><b>'.$htmlmsg.'</b></font>';

		echo '<p>'._L('<i>Privacy information:</i> This setting can always be changed in the administrator login / control panel.').'<br>';
		echo _L('<a %1>Click here</a> for more information about privacy related topics.','href="../../../../res/OIDplus/privacy_documentation.html" target="_blank"');
		echo '</p>';
	}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8
	 * @param string|null $user
	 * @return array
	 * @throws OIDplusException
	 */
	public function getNotifications(?string $user=null): array {
		$notifications = array();
		if ((!$user || ($user == 'admin')) && OIDplus::authUtils()->isAdminLoggedIn()) {
			if (!url_post_contents_available(true, $reason)) {
				$title = _L('System registration');
				$notifications[] = new OIDplusNotification('ERR', _L('OIDplus plugin "%1" is enabled, but OIDplus cannot connect to the Internet.', '<a '.OIDplus::gui()->link('oidplus:srv_registration').'>'.htmlentities($title).'</a>').' '.$reason);
			}
		}
		return $notifications;
	}

}
