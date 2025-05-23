<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2025 Daniel Marschall, ViaThinkSoft
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

namespace ViaThinkSoft\OIDplus\Plugins\AdminPages\OidBaseExport;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusHtmlException;
use ViaThinkSoft\OIDplus\Core\OIDplusObject;
use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginAdmin;
use ViaThinkSoft\OIDplus\Plugins\AdminPages\Notifications\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8;
use ViaThinkSoft\OIDplus\Plugins\AdminPages\Notifications\OIDplusNotification;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusPageAdminOidBaseExport extends OIDplusPagePluginAdmin
	implements INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8 /* getNotifications */
{

	/**
	 *
	 */
	private const QUERY_LIST_OIDBASE_OIDS_V1 = '1.3.6.1.4.1.37476.2.5.2.1.5.1';

	/**
	 *
	 */
	private const QUERY_GET_OIDBASE_DATA_V1  = '1.3.6.1.4.1.37476.2.5.2.1.6.1';

	/**
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 */
	private function action_ImportXml(array $params): array {
		if (!OIDplus::authUtils()->isAdminLoggedIn()) {
			throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')), null,401);
		}

		if (!isset($_FILES['userfile'])) {
			throw new OIDplusException(_L('Please choose a file.'));
		}

		if ($_FILES['userfile']['error']) {
			throw new OIDplusException(_L('Could not receive file (probably it is too large?)'));
		}

		$xml_contents = file_get_contents($_FILES['userfile']['tmp_name']);

		$errors = array();
		list($count_imported_oids, $count_already_existing, $count_errors, $count_warnings) = $this->oidbaseImportXML($xml_contents, $errors, $replaceExistingOIDs=false, $orphan_mode=self::ORPHAN_AUTO_DEORPHAN);
		if (count($errors) > 0) {
			// Note: These "errors" can also be warnings (partial success)
			// TODO: since the output can be very long, should we really show it in a JavaScript alert() ?!
			return array(
				"status" => -1,
				"count_imported_oids" => $count_imported_oids,
				"count_already_existing" => $count_already_existing,
				"count_errors" => $count_errors,
				"count_warnings" => $count_warnings,
				"error" => implode("\n",$errors)
			);
		} else {
			return array(
				"status" => 0,
				"count_imported_oids" => $count_imported_oids,
				"count_already_existing" => $count_already_existing,
				"count_errors" => $count_errors,
				"count_warnings" => $count_warnings
			);
		}
	}

	/**
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 */
	private function action_ImportOidBase(array $params): array {
		if (!OIDplus::authUtils()->isAdminLoggedIn()) {
			throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')), null, 401);
		}

		_CheckParamExists($params, 'oid');

		$oid = $params['oid'];

		$query = self::QUERY_GET_OIDBASE_DATA_V1;

		$payload = array(
			"query" => $query, // we must repeat the query because we want to sign it
			"system_id" => OIDplus::getSystemId(false),
			"oid" => $oid
		);

		$signature = '';
		if (!OIDplus::getPkiStatus() || !@openssl_sign(json_encode($payload), $signature, OIDplus::getSystemPrivateKey())) {
			if (!OIDplus::getPkiStatus()) {
				throw new OIDplusException(_L('Error: Your system could not generate a private/public key pair. (OpenSSL is probably missing on your system). Therefore, you cannot register/unregister your OIDplus instance.'));
			} else {
				throw new OIDplusException(_L('Signature failed'));
			}
		}

		$data = array(
			"payload" => $payload,
			"signature" => base64_encode($signature)
		);

		if (OIDplus::getEditionInfo()['vendor'] != 'ViaThinkSoft') {
			// The oid-base.com import functionality is a confidential API between ViaThinkSoft and oid-base.com and cannot be used in forks of OIDplus
			throw new OIDplusException(_L('This feature is only available in the ViaThinkSoft edition of OIDplus'));
		}

		if (function_exists('gzdeflate')) {
			$compressed = "1";
			$data2 = gzdeflate(json_encode($data));
		} else {
			$compressed = "0";
			$data2 = json_encode($data);
		}

		$res_curl = url_post_contents(
			'https://www.oidplus.com/reg2/query.php',
			array(
				"query"      => $query,
				"compressed" => $compressed,
				"data"       => base64_encode($data2)
			)
		);

		if ($res_curl === false) {
			throw new OIDplusException(_L('Communication with %1 server failed', 'ViaThinkSoft'));
		}

		$json = @json_decode($res_curl, true);

		if (!$json) {
			return array(
				"status" => -1,
				"error" => _L('JSON reply from ViaThinkSoft decoding error: %1',$res_curl)
			);
		}

		if (isset($json['error']) || ($json['status'] < 0)) {
			return array(
				"status" => -1,
				"error" => $json['error'] ?? _L('Received error status code: %1', $json['status'])
			);
		}

		$errors = array();
		list($count_imported_oids, $count_already_existing, $count_errors, $count_warnings) = $this->oidbaseImportXML('<oid-database>'.$json['xml'].'</oid-database>', $errors, $replaceExistingOIDs=false, $orphan_mode=self::ORPHAN_DISALLOW_ORPHANS);
		if (count($errors) > 0) {
			return array("status" => -1, "error" => implode("\n",$errors));
		} else if ($count_imported_oids <> 1) {
			return array("status" => -1, "error" => _L('Imported %1, but expected to import 1',$count_imported_oids));
		} else {
			return array("status" => 0);
		}
	}

	/**
	 * @param string $actionID
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 */
	public function action(string $actionID, array $params): array {
		if ($actionID == 'import_xml_file') {
			return $this->action_ImportXml($params);
		} else if ($actionID == 'import_oidbase_oid') {
			return $this->action_ImportOidBase($params);
		} else {
			return parent::action($actionID, $params);
		}
	}

	/**
	 * @param bool $html
	 * @return void
	 */
	public function init(bool $html=true): void {
		// Nothing
	}

	/**
	 * @param string $id
	 * @param array $out
	 * @param bool $handled
	 * @return void
	 * @throws OIDplusException
	 */
	public function gui(string $id, array &$out, bool &$handled): void {
		$ary = explode('$', $id);
		if (isset($ary[1])) {
			$id = $ary[0];
			$tab = $ary[1];
		} else {
			$tab = 'export';
		}
		if ($id === 'oidplus:oidbase_compare_export') {
			$handled = true;
			$out['title'] = _L('List OIDs in your system which are missing at oid-base.com');
			$out['icon'] = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')), $out['title'], 401);
			}

			$query = self::QUERY_LIST_OIDBASE_OIDS_V1;

			$payload = array(
				"query" => $query, // we must repeat the query because we want to sign it
				"system_id" => OIDplus::getSystemId(false),
				"show_all" => 1 // this is required so that the VTS OIDRA gets no false notifications for adding the systems in the directory 1.3.6.1.4.1.37476.30.9
			);

			$signature = '';
			if (!OIDplus::getPkiStatus() || !@openssl_sign(json_encode($payload), $signature, OIDplus::getSystemPrivateKey())) {
				if (!OIDplus::getPkiStatus()) {
					throw new OIDplusException(_L('Error: Your system could not generate a private/public key pair. (OpenSSL is probably missing on your system). Therefore, you cannot register/unregister your OIDplus instance.'));
				} else {
					throw new OIDplusException(_L('Signature failed'));
				}
			}

			$data = array(
				"payload" => $payload,
				"signature" => base64_encode($signature)
			);

			if (OIDplus::getEditionInfo()['vendor'] != 'ViaThinkSoft') {
				// The oid-base.com import functionality is a confidential API between ViaThinkSoft and oid-base.com and cannot be used in forks of OIDplus
				throw new OIDplusException(_L('This feature is only available in the ViaThinkSoft edition of OIDplus'));
			}

			if (function_exists('gzdeflate')) {
				$compressed = "1";
				$data2 = gzdeflate(json_encode($data));
			} else {
				$compressed = "0";
				$data2 = json_encode($data);
			}

			$res_curl = url_post_contents(
				'https://www.oidplus.com/reg2/query.php',
				array(
					"query"      => $query,
					"compressed" => $compressed,
					"data"       => base64_encode($data2)
				)
			);

			if ($res_curl === false) {
				throw new OIDplusException(_L('Communication with %1 server failed', 'ViaThinkSoft'));
			}

			$out['text'] = '<p><a '.OIDplus::gui()->link('oidplus:datatransfer$export').'><img src="img/arrow_back.png" width="16" alt="'._L('Go back').'"> '._L('Go back to data transfer main page').'</a></p>';

			$json = @json_decode($res_curl, true);

			if (!$json) {
				throw new OIDplusHtmlException($out['text']._L('JSON reply from ViaThinkSoft decoding error: %1',$res_curl), $out['title']);
			}

			if (isset($json['error']) || ($json['status'] < 0)) {
				if (isset($json['error'])) {
					throw new OIDplusHtmlException($out['text']._L('Received error: %1',$json['error']), $out['title']);
				} else {
					throw new OIDplusHtmlException($out['text']._L('Received error status code: %1',$json['status']), $out['title']);
				}
			}

			if (isset($json['error']) || ($json['status'] < 0)) {
				$out['text'] .= '<p>'._L('Error: %1',htmlentities($json['error'])).'</p>';
			} else {
				// TODO: If roots were created or deleted recently, we must do a re-query of the registration, so that the "roots" information at the directory service gets refreshed
				if (count($json['roots']) == 0) $out['text'] .= '<p>'._L('In order to use this feature, you need to have at least one (root) OID added in your system, and the system needs to report the newly added root to the directory service (the reporting interval is 1 hour).').'</p>';
				foreach ($json['roots'] as $root) {
					$oid = $root['oid'];
					$out['text'] .= '<h2>'._L('Root OID %1',$oid).'</h2>';
					if ($root['verified']) {
						$count = 0;
						$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
						$out['text'] .= '<table class="table table-bordered table-striped">';
						$out['text'] .= '<thead>';
						$out['text'] .= '<tr><th colspan="3">'._L('Actions').'</th><th>'._L('OID').'</th></tr>';
						$out['text'] .= '</thead>';
						$out['text'] .= '<tbody>';

						$lookup_nonoid = array();
						$row_lookup = array();

						$all_local_oids_of_root = array();
						$res = OIDplus::db()->query("select * from ###objects where confidential <> 1");
						while ($row = $res->fetch_object()) {
							$obj = OIDplusObject::parse($row->id);
							if (!$obj) continue; // can happen when object type is not enabled
							if ($obj->isConfidential()) continue; // This will also exclude OIDs which are descendants of confidential OIDs
							if (strpos($row->id, 'oid:') === 0) {
								$oid = substr($row->id,strlen('oid:'));
								if (strpos($oid.'.', $root['oid']) === 0) {
									$row_lookup[$oid] = $row;
									$all_local_oids_of_root[] = $oid;
								}
							} else {
								$aids = $obj->getAltIds();
								foreach ($aids as $aid) {
									// TODO: Let Object Type plugins decide if they want that their OID representations get published or not (via a Feature OID implementation)
									if ($aid->getNamespace() == 'oid') {
										$oid = $aid->getId();
										if (strpos($oid.'.', $root['oid']) === 0) {
											$row_lookup[$oid] = $row;
											$all_local_oids_of_root[] = $oid;
											$lookup_nonoid[$oid] = $row->id;
										}
									}
								}
							}
						}

						natsort($all_local_oids_of_root);
						foreach ($all_local_oids_of_root as $local_oid) {
							if (!in_array($local_oid, $root['children'])) {
								$count++;

								// Start: Build oid-base.com create URL

								$row = $row_lookup[$local_oid];

								$url = "https://www.oid-base.com/cgi-bin/manage?f=".oid_up($local_oid)."&a=create";

								$tmp = explode('.',$local_oid);
								$url .= "&nb=".urlencode(array_pop($tmp));

								$asn1_ids = array();
								$res_asn = OIDplus::db()->query("select * from ###asn1id where oid = ?", array($row->id));
								while ($row_asn = $res_asn->fetch_object()) {
									$asn1_ids[] = $row_asn->name; // 'unicode-label' is currently not in the standard format (oid.xsd)
								}
								$url .= "&id=".array_shift($asn1_ids); // urlencode() is already done (see above)
								$url .= "&syn_id=".implode('%0A', $asn1_ids); // urlencode() is already done (see above)

								$iri_ids = array();
								$res_iri = OIDplus::db()->query("select * from ###iri where oid = ?", array($row->id));
								while ($row_iri = $res_iri->fetch_object()) {
									$iri_ids[] = $row_iri->name;
								}
								$url .= "&unicode_label_list=".implode('%0A', $iri_ids); // urlencode() is already done (see above)

								if (!empty($row->title)) {
									$tmp_description = $row->title;
									$tmp_information = $row->description;
									if (trim($row->title) == trim(strip_tags($row->description))) {
										$tmp_information = '';
									}
								} else if (isset($asn1_ids[0])) {
									$tmp_description = '"'.$asn1_ids[0].'"';
									$tmp_information = $row->description;
								} else if (isset($iri_ids[0])) {
									$tmp_description = '"'.$iri_ids[0].'"';
									$tmp_information = $row->description;
								} else if (!empty($row->description)) {
									$tmp_description = $row->description;
									$tmp_information = '';
								} else if (!empty($row->comment)) {
									$tmp_description = $row->comment;
									$tmp_information = '';
								} else {
									$tmp_description = '<i>No description available</i>'; // do not translate
									$tmp_information = '';
								}

								if ($tmp_information != '') {
									$tmp_information .= '<br/><br/>';
								}

								$tmp_information .= 'See <a href="'.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).'?goto='.urlencode($id).'">more information</a>.'; // do not translate

								if (explode(':',$id,2)[0] != 'oid') {
									$tmp_information = "Object: $id\n\n" . $tmp_information; // do not translate
								}

								$url .= "&description=".urlencode(self::repair_relative_links($tmp_description));
								$url .= "&info=".urlencode(self::repair_relative_links($tmp_information));

								$url .= "&current_registrant_email=".urlencode($row->ra_email);

								$res_ra = OIDplus::db()->query("select * from ###ra where email = ?", array($row->ra_email));
								if ($res_ra->any()) {
									$row_ra = $res_ra->fetch_object();

									$tmp = array();
									if (!empty($row_ra->personal_name)) {
										$name_ary = split_firstname_lastname($row_ra->personal_name);
										$tmp_first_name = $name_ary[0];
										$tmp_last_name  = $name_ary[1];
										if (!empty($row_ra->ra_name)       ) $tmp[] = $row_ra->ra_name;
										if (!empty($row_ra->office)        ) $tmp[] = $row_ra->office;
										if (!empty($row_ra->organization)  ) $tmp[] = $row_ra->organization;
									} else {
										$tmp_first_name = $row_ra->ra_name;
										$tmp_last_name  = '';
										if (!empty($row_ra->personal_name) ) $tmp[] = $row_ra->personal_name;
										if (!empty($row_ra->office)        ) $tmp[] = $row_ra->office;
										if (!empty($row_ra->organization)  ) $tmp[] = $row_ra->organization;
									}

									if (empty($tmp_first_name) || empty($tmp_last_name)) {
										$name = self::split_name($tmp_first_name.' '.$tmp_last_name);
										$tmp_first_name = $name[0];
										$tmp_last_name = $name[1];
									}
									$url .= "&current_registrant_first_name=".urlencode($tmp_first_name);
									$url .= "&current_registrant_last_name=".urlencode($tmp_last_name);

									if ((count($tmp) > 0) && ($tmp[0] == $row_ra->ra_name)) array_shift($tmp);
									$tmp = array_unique($tmp);

									if (!$row_ra->privacy) {
										if (!empty($row_ra->street))   $tmp[] = $row_ra->street;
										if (!empty($row_ra->zip_town)) $tmp[] = $row_ra->zip_town;
										if (!empty($row_ra->country))  $tmp[] = $row_ra->country;
										$url .= "&current_registrant_tel=".urlencode(!empty($row_ra->phone) ? $row_ra->phone : $row_ra->mobile);
										$url .= "&current_registrant_fax=".urlencode($row_ra->fax);
									}
									if (empty($row_ra->zip_town) && empty($row_ra->country)) {
										// The address is useless if we do neither know city nor country
										// Ignore it
									} else {
										$tmp = self::split_address_country(implode("<br/>", $tmp));
										$url .= "&current_registrant_address=".urlencode($tmp[0]);
										$url .= "&current_registrant_country=".urlencode($tmp[1]);
									}
								}
								if (!empty($row->updated)) {
									$tmp = explode('-', self::_formatdate($row->updated));
									$url .= "&modification_year=".urlencode($tmp[0]);
									$url .= "&modification_month=".urlencode($tmp[1]);
									$url .= "&modification_day=".urlencode($tmp[2]);
								}

								//$url .= "&submitter_last_name=".urlencode($xml->{'submitter'}->{'last-name'});
								//$url .= "&submitter_first_name=".urlencode($xml->{'submitter'}->{'first-name'});
								//$url .= "&submitter_email=".urlencode($xml->{'submitter'}->{'email'});

								// End: Build oid-base.com create URL

								// Note: "Actions" is at the left, because it has a fixed width, so the user can continue clicking without the links moving if the OID length changes between lines
								$out['text'] .= '<tr id="missing_oid_'.str_replace('.','_',$local_oid).'">'.
								'<td><a '.OIDplus::gui()->link($lookup_nonoid[$local_oid] ?? 'oid:' . $local_oid, true).'>'._L('View local OID').'</a></td>'.
								'<td><a href="javascript:OIDplusPageAdminOidBaseExport.removeMissingOid(\''.$local_oid.'\');">'._L('Ignore for now').'</a></td>'.
								'<td><a target="_blank" href="'.$url.'">'._L('Add to oid-base.com manually').'</a></td>'.
								'<td>'.$local_oid.'</td>'.
								'</tr>';
							}
						}
						$out['text'] .= '</tbody>';
						if ($count == 0) {
							$out['text'] .= '<tfoot>';
							$out['text'] .= '<tr><td colspan="4">'._L('No missing OIDs found').'</td></tr>';
							$out['text'] .= '</tfoot>';
						}
						$out['text'] .= '</table></div></div>';
					} else {
						$out['text'] .= '<p>'._L('This root is not validated. Please send an email to %1 in order to request ownership verification of this root OID.',$json['vts_verification_email']).'</p>';
					}
				}
			}
		}

		if ($id === 'oidplus:oidbase_compare_import') {
			$handled = true;
			$out['title'] = _L('List OIDs at oid-base.com which are missing in your system');
			$out['icon'] = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')), $out['title'], 401);
			}

			$query = self::QUERY_LIST_OIDBASE_OIDS_V1;

			$payload = array(
				"query" => $query, // we must repeat the query because we want to sign it
				"system_id" => OIDplus::getSystemId(false),
				"show_all" => 0
			);

			$signature = '';
			if (!OIDplus::getPkiStatus() || !@openssl_sign(json_encode($payload), $signature, OIDplus::getSystemPrivateKey())) {
				if (!OIDplus::getPkiStatus()) {
					throw new OIDplusException(_L('Error: Your system could not generate a private/public key pair. (OpenSSL is probably missing on your system). Therefore, you cannot register/unregister your OIDplus instance.'));
				} else {
					throw new OIDplusException(_L('Signature failed'));
				}
			}

			$data = array(
				"payload" => $payload,
				"signature" => base64_encode($signature)
			);

			if (OIDplus::getEditionInfo()['vendor'] != 'ViaThinkSoft') {
				// The oid-base.com import functionality is a confidential API between ViaThinkSoft and oid-base.com and cannot be used in forks of OIDplus
				throw new OIDplusException(_L('This feature is only available in the ViaThinkSoft edition of OIDplus'));
			}

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

			$out['text'] = '<p><a '.OIDplus::gui()->link('oidplus:datatransfer$import').'><img src="img/arrow_back.png" width="16" alt="'._L('Go back').'"> '._L('Go back to data transfer main page').'</a></p>';

			$json = @json_decode($res, true);

			if (!$json) {
				throw new OIDplusHtmlException($out['text']._L('JSON reply from ViaThinkSoft decoding error: %1',$res), $out['title']);
			}

			if (isset($json['error']) || ($json['status'] < 0)) {
				if (isset($json['error'])) {
					throw new OIDplusHtmlException($out['text']._L('Received error: %1',$json['error']), $out['title']);
				} else {
					throw new OIDplusHtmlException($out['text']._L('Received error status code: %1',$json['status']), $out['title']);
				}
			}

			$all_local_oids = array();
			$res = OIDplus::db()->query("select id from ###objects");
			while ($row = $res->fetch_array()) {
				if (strpos($row['id'], 'oid:') === 0) {
					$all_local_oids[] = substr($row['id'],strlen('oid:'));
				} else {
					$obj = OIDplusObject::parse($row['id']);
					if (!$obj) continue; // can happen when object type is not enabled
					$aids = $obj->getAltIds();
					foreach ($aids as $aid) {
						// TODO: Let Object Type plugins decide if they want that their OID representations get published or not (via a Feature OID implementation)
						if ($aid->getNamespace() == 'oid') {
							$all_local_oids[] = $aid->getId();
						}
					}
				}
			}

			if (isset($json['error']) || ($json['status'] < 0)) {
				$out['text'] .= '<p>'._L('Error: %1',htmlentities($json['error'])).'</p>';
			} else {
				// TODO: If roots were created or deleted recently, we must do a re-query of the registration, so that the "roots" information at the directory service gets refreshed
				if (count($json['roots']) == 0) $out['text'] .= '<p>'._L('In order to use this feature, you need to have at least one (root) OID added in your system, and the system needs to report the newly added root to the directory service (the reporting interval is 1 hour).').'</p>';
				foreach ($json['roots'] as $root) {
					$oid = $root['oid'];
					$out['text'] .= '<h2>'._L('Root OID %1',$oid).'</h2>';
					// TODO: "Import all" button
					if ($root['verified']) {
						$count = 0;
						$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
						$out['text'] .= '<table class="table table-bordered table-striped">';
						$out['text'] .= '<thead>';
						$out['text'] .= '<tr><th colspan="4">'._L('Actions').'</th><th>'._L('OID').'</th></tr>';
						$out['text'] .= '</thead>';
						$out['text'] .= '<tbody>';
						natsort($root['children']);
						foreach ($root['children'] as $child_oid) {
							if (!in_array($child_oid, $all_local_oids)) {
								$count++;
								// Note: "Actions" is at the left, because it has a fixed width, so the user can continue clicking without the links moving if the OID length changes between lines
								$out['text'] .= '<tr id="missing_oid_'.str_replace('.','_',$child_oid).'">'.
								'<td><a target="_blank" href="https://www.oid-base.com/get/'.$child_oid.'">'._L('View OID at oid-base.com').'</a></td>'.
								'<td><a href="javascript:OIDplusPageAdminOidBaseExport.removeMissingOid(\''.$child_oid.'\');">'._L('Ignore for now').'</a></td>'.
								'<td><a href="mailto:admin@oid-base.com">'._L('Report illegal OID').'</a></td>'.
								(strpos($child_oid,'1.3.6.1.4.1.37476.30.9.') === 0 ? '<td>&nbsp;</td>' : '<td><a href="javascript:OIDplusPageAdminOidBaseExport.importMissingOid(\''.$child_oid.'\');">'._L('Import OID').'</a></td>').
								'<td>'.$child_oid.'</td>'.
								'</tr>';
							}
						}
						$out['text'] .= '</tbody>';
						if ($count == 0) {
							$out['text'] .= '<tfoot>';
							$out['text'] .= '<tr><td colspan="5">'._L('No extra OIDs found').'</td></tr>';
							$out['text'] .= '</tfoot>';
						}
						$out['text'] .= '</table></div></div>';
					} else {
						$out['text'] .= '<p>'._L('This root is not validated. Please send an email to %1 in order to request ownership verification of this root OID.',$json['vts_verification_email']).'</p>';
					}
				}
			}
		}

		if ($id === 'oidplus:datatransfer') {
			$handled = true;
			$out['title'] = _L('Data Exchange (oid-base.com)');
			$out['icon'] = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')), $out['title'], 401);
			}

			$out['text'] = '<noscript>';
			$out['text'] .= '<p><font color="red">'._L('You need to enable JavaScript to use this feature.').'</font></p>';
			$out['text'] .= '</noscript>';

			$out['text'] .= '<br><div id="dataTransferArea" style="visibility: hidden"><div id="dataTransferTab" class="container" style="width:100%;">';

			// ---------------- Tab control
			$out['text'] .= OIDplus::gui()->tabBarStart();
			$out['text'] .= OIDplus::gui()->tabBarElement('export', _L('Export'), $tab === 'export');
			$out['text'] .= OIDplus::gui()->tabBarElement('import', _L('Import'), $tab === 'import');
			$out['text'] .= OIDplus::gui()->tabBarEnd();
			$out['text'] .= OIDplus::gui()->tabContentStart();
			// ---------------- "Export" tab
			$tabcont  = '<h2>'._L('Generate XML file containing all OIDs').'</h2>';
			$tabcont .= '<p>'._L('These XML files are following the <a %1>XML schema</a> of <b>oid-base.com</b>. They can be used for various purposes though.','href="https://www.oid-base.com/oid.xsd" target="_blank"').'</p>';
			$tabcont .= '<p><input type="button" onclick="window.open(\''.OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'oidbase_export.php\',\'_blank\')" value="'._L('Generate XML (all OIDs)').'"></p>';
			$tabcont .= '<p><input type="button" onclick="window.open(\''.OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'oidbase_export.php?online=1\',\'_blank\')" value="'._L('Generate XML (only OIDs which do not exist at oid-base.com)').'"></p>';
			$tabcont .= '<p><a href="https://www.oid-base.com/submit.htm" target="_blank">'._L('Upload XML files manually to oid-base.com').'</a></p>';
			$tabcont .= '<br><p>'._L('Attention: Do not use this XML Export/Import to exchange, backup or restore data between OIDplus systems!<br>It will cause various loss of information, e.g. because Non-OIDs like GUIDs are converted in OIDs and can\'t be converted back.').'</p>';
			if (OIDplus::getEditionInfo()['vendor'] == 'ViaThinkSoft') {
				$tabcont .= '<h2>'._L('Automatic export to oid-base.com').'</h2>';
				$privacy_level = OIDplus::config()->getValue('reg_privacy');
				if ($privacy_level == 0) {
					$tabcont .= '<p>'._L('All your OIDs will automatically submitted to oid-base.com through the remote directory service in regular intervals.').' (<a '.OIDplus::gui()->link('oidplus:srv_registration').'>'._L('Change preference').'</a>)</p>';
				} else {
					$tabcont .= '<p>'._L('If you set the privacy option to "0" (your system is registered), then all your OIDs will be automatically exported to oid-base.com.').' (<a '.OIDplus::gui()->link('oidplus:srv_registration').'>'._L('Change preference').'</a>)</p>';
				}
			}
			$tabcont .= '<h2>'._L('Comparison with oid-base.com').'</h2>';
			$tabcont .= '<p><a '.OIDplus::gui()->link('oidplus:oidbase_compare_export').'>'._L('List OIDs in your system which are missing at oid-base.com').'</a></p>';
			$out['text'] .= OIDplus::gui()->tabContentPage('export', $tabcont, $tab === 'export');
			// ---------------- "Import" tab
			$tabcont  = '<h2>'._L('Import XML file').'</h2>';
			$tabcont .= '<p>'._L('These XML files are following the <a %1>XML schema</a> of <b>oid-base.com</b>.','href="https://www.oid-base.com/oid.xsd" target="_blank"').'</p>';
			// TODO: we need a waiting animation!
			$tabcont .= '<form action="javascript:void(0);" onsubmit="return OIDplusPageAdminOidBaseExport.uploadXmlFileOnSubmit(this);" enctype="multipart/form-data" id="uploadXmlFileForm">';
			$tabcont .= '<div>'._L('Choose XML file here').':<br><input type="file" name="userfile" value="" id="userfile">';
			$tabcont .= '<br><input type="submit" value="'._L('Import XML').'"></div>';
			$tabcont .= '</form>';
			$tabcont .= '<br><p>'._L('Attention: Do not use this XML Export/Import to exchange, backup or restore data between OIDplus systems!<br>It will cause various loss of information, e.g. because Non-OIDs like GUIDs are converted in OIDs and can\'t be converted back.').'</p>';
			$tabcont .= '<h2>'._L('Comparison with oid-base.com').'</h2>';
			$tabcont .= '<p><a '.OIDplus::gui()->link('oidplus:oidbase_compare_import').'>'._L('List OIDs at oid-base.com which are missing in your system').'</a></p>';
			$out['text'] .= OIDplus::gui()->tabContentPage('import', $tabcont, $tab === 'import');
			$out['text'] .= OIDplus::gui()->tabContentEnd();
			// ---------------- Tab control END

			$out['text'] .= '</div></div><script>$("#dataTransferArea")[0].style.visibility = "visible";</script>';
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
			'id' => 'oidplus:datatransfer',
			'icon' => $tree_icon,
			'text' => _L('Data Exchange (oid-base.com)')
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
	 * @param bool $only_non_existing
	 * @return string[]
	 * @throws OIDplusException
	 * @throws \OidInfoException
	 */
	public static function outputXML(bool $only_non_existing): array {
		$out_type = null;
		$out_content = '';

		$oa = new \OIDInfoAPI(); // TODO: Rename to OidBase
		if ($only_non_existing) {
			if (!function_exists('socket_create')) {
				throw new OIDplusException(_L('You must install the PHP "sockets" in order to check for non-existing OIDs.'));
			}
			$oa->addSimplePingProvider('viathinksoft.de:49500');
		}

		$email = OIDplus::config()->getValue('admin_email');
		if (empty($email)) $email = 'unknown@example.com';

		$sys_title = OIDplus::config()->getValue('system_title');
		$name1 = !empty($sys_title) ? $sys_title : 'OIDplus 2.0';
		$name2 = $_SERVER['SERVER_NAME'] ?? 'Export interface';
		$out_content .= $oa->xmlAddHeader($name1, $name2, $email); // do not translate

		$params['allow_html'] = true;
		$params['allow_illegal_email'] = true; // It should be enabled, because the creator could have used some kind of human-readable anti-spam technique
		$params['soft_correct_behavior'] = \OIDInfoAPI::SOFT_CORRECT_BEHAVIOR_NONE;
		$params['do_online_check'] = false; // Flag to disable this online check, because it generates a lot of traffic and runtime.
		$params['do_illegality_check'] = true;
		$params['do_simpleping_check'] = $only_non_existing;
		$params['auto_extract_name'] = '';
		$params['auto_extract_url'] = '';
		$params['always_output_comment'] = false;
		$params['creation_allowed_check'] = $only_non_existing;
		$params['tolerant_htmlentities'] = true;
		$params['ignore_xhtml_light'] = false;

		$nonConfidential = OIDplusObject::getAllNonConfidential();
		natsort($nonConfidential);

		foreach ($nonConfidential as $id) {
			$obj = OIDplusObject::parse($id);
			if ($obj) {
				$elements['identifier'] = array();
				$res_asn = OIDplus::db()->query("select * from ###asn1id where oid = ?", array($id));
				while ($row_asn = $res_asn->fetch_object()) {
					$elements['identifier'][] = $row_asn->name; // 'unicode-label' is currently not in the standard format (oid.xsd)
				}

				$elements['unicode-label'] = array();
				$res_iri = OIDplus::db()->query("select * from ###iri where oid = ?", array($id));
				while ($row_iri = $res_iri->fetch_object()) {
					$elements['unicode-label'][] = $row_iri->name;
				}

				$title = $obj->getTitle();
				$description = $obj->getDescription();
				$comment = $obj->getComment();
				if (!empty($title)) {
					$elements['description'] = htmlentities($title);
					$elements['information'] = $description;
					if (trim($title) == trim(strip_tags($description))) {
						$elements['information'] = '';
					}
				} else if (isset($elements['identifier'][0])) {
					$elements['description'] = '"'.htmlentities($elements['identifier'][0]).'"';
					$elements['information'] = $description;
				} else if (isset($elements['unicode-label'][0])) {
					$elements['description'] = '"'.htmlentities($elements['unicode-label'][0]).'"';
					$elements['information'] = $description;
				} else if (!empty($description)) {
					$elements['description'] = $description;
					$elements['information'] = '';
				} else if (!empty($comment)) {
					$elements['description'] = htmlentities($comment);
					$elements['information'] = '';
				} else {
					// TODO: Actually, in this case the OID should be rejected
					$elements['description'] = '<i>No description available</i>'; // do not translate
					$elements['information'] = '';
				}

				if ($elements['information'] != '') {
					$elements['information'] .= '<br/><br/>';
				}

				$elements['information'] .= 'See <a href="'.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).'?goto='.urlencode($id).'">more information</a>.'; // do not translate

				if (explode(':',$id,2)[0] != 'oid') {
					$elements['information'] = "Object: ".htmlentities($id)."\n\n" . $elements['information']; // do not translate
				}

				$elements['description'] = self::repair_relative_links($elements['description']);
				$elements['information'] = self::repair_relative_links($elements['information']);

				// Function to escape only invalid ampersands
				// Match "&" not followed by a valid entity (e.g., &amp;, &lt;, etc.)
				// TODO: include that into the OIDInfo API?
				$elements['information'] = preg_replace('/&(?![a-zA-Z0-9#]+;)/', '&amp;', $elements['information']);

				$elements['first-registrant']['first-name'] = '';
				$elements['first-registrant']['last-name'] = '';
				$elements['first-registrant']['address'] = '';
				$elements['first-registrant']['email'] = '';
				$elements['first-registrant']['phone'] = '';
				$elements['first-registrant']['fax'] = '';
				$create_ts = $obj->getCreatedTime();
				$elements['first-registrant']['creation-date'] = $create_ts ? self::_formatdate($create_ts) : '';

				$elements['current-registrant']['first-name'] = '';
				$elements['current-registrant']['last-name'] = '';
				$elements['current-registrant']['email'] = $obj->getRaMail();
				$elements['current-registrant']['phone'] = '';
				$elements['current-registrant']['fax'] = '';
				$elements['current-registrant']['address'] = '';

				$res_ra = OIDplus::db()->query("select * from ###ra where email = ?", array($obj->getRaMail()));
				if ($res_ra->any()) {
					$row_ra = $res_ra->fetch_object();

					$tmp = array();
					if (!empty($row_ra->personal_name)) {
						$name_ary = split_firstname_lastname($row_ra->personal_name);
						$elements['current-registrant']['first-name'] = $name_ary[0];
						$elements['current-registrant']['last-name']  = $name_ary[1];
						if (!empty($row_ra->ra_name)       ) $tmp[] = $row_ra->ra_name;
						if (!empty($row_ra->office)        ) $tmp[] = $row_ra->office;
						if (!empty($row_ra->organization)  ) $tmp[] = $row_ra->organization;
					} else {
						$elements['current-registrant']['first-name'] = $row_ra->ra_name;
						$elements['current-registrant']['last-name']  = '';
						if (!empty($row_ra->personal_name) ) $tmp[] = $row_ra->personal_name;
						if (!empty($row_ra->office)        ) $tmp[] = $row_ra->office;
						if (!empty($row_ra->organization)  ) $tmp[] = $row_ra->organization;
					}

					if ((count($tmp) > 0) && ($tmp[0] == $row_ra->ra_name)) array_shift($tmp);
					$tmp = array_unique($tmp);

					if (!$row_ra->privacy) {
						if (!empty($row_ra->street))   $tmp[] = $row_ra->street;
						if (!empty($row_ra->zip_town)) $tmp[] = $row_ra->zip_town;
						if (!empty($row_ra->country))  $tmp[] = $row_ra->country;
						$elements['current-registrant']['phone'] = !empty($row_ra->phone) ? $row_ra->phone : $row_ra->mobile;
						$elements['current-registrant']['fax'] = $row_ra->fax;
					}
					if (empty($row_ra->zip_town) && empty($row_ra->country)) {
						// The address is useless if we do neither know city nor country
						// Ignore it
						$elements['current-registrant']['address'] = '';
					} else {
						$elements['current-registrant']['address'] = implode("<br/>", $tmp);
					}
				}
				$update_ts = $obj->getUpdatedTime();
				$elements['current-registrant']['modification-date'] = $update_ts ? self::_formatdate($update_ts) : '';

				// Request from O.D. 20 May 2019: First registrant should not be empty (especially for cases where Creation and Modify Dates are the same)
				// Actually, this is a problem because we don't know the first registrant.
				// However, since oid-base.com gets their XML very fast (if using registration), it is likely that the reported RA is still the same...
				// ... and changes at the RA are not reported to oid-base.com anyways - the XML is only for creation

				$elements['first-registrant']['first-name'] = $elements['current-registrant']['first-name'];
				$elements['first-registrant']['last-name']  = $elements['current-registrant']['last-name'];
				$elements['first-registrant']['address']    = $elements['current-registrant']['address'];
				$elements['first-registrant']['email']      = $elements['current-registrant']['email'];
				$elements['first-registrant']['phone']      = $elements['current-registrant']['phone'];
				$elements['first-registrant']['fax']        = $elements['current-registrant']['fax'];

				$elements['current-registrant']['first-name'] = '';
				$elements['current-registrant']['last-name'] = '';
				$elements['current-registrant']['address'] = '';
				$elements['current-registrant']['email'] = '';
				$elements['current-registrant']['phone'] = '';
				$elements['current-registrant']['fax'] = '';
				$elements['current-registrant']['modification-date'] = '';

				// End request O.D. 20 May 2019

				list($ns,$id) = explode(':',$obj->nodeId());
				if ($ns == 'oid') {
					$out_content .= $oa->createXMLEntry($id, $elements, $params, $comment=$obj->nodeId());
				} else {
					$alt_ids = $obj->getAltIds(); // TODO: slow!
					foreach ($alt_ids as $alt_id) {
						$ns = $alt_id->getNamespace();
						$id = $alt_id->getId();
						$desc = $alt_id->getDescription();
						if ($ns == 'oid') {
							// TODO: Let Object Type plugins decide if they want that their OID representations get published or not (via a Feature OID implementation)
							if (strpos($id, '2.25.') === 0) continue; // don't spam the uuid arc with GUID objects
							if (strpos($id, '1.2.840.113556.1.8000.2554.') === 0) continue; // don't spam the uuid arc with GUID objects
							if (strpos($id, '1.3.6.1.4.1.54392.1.') === 0) continue; // don't spam the uuid arc with GUID objects
							if (strpos($id, '1.3.6.1.4.1.54392.2.') === 0) continue; // don't spam the uuid arc with GUID objects
							if (strpos($id, '1.3.6.1.4.1.54392.3.') === 0) continue; // don't spam the uuid arc with GUID objects
							$out_content .= $oa->createXMLEntry($id, $elements, $params, $comment=$obj->nodeId());
						}
					}
				}
			}
		}

		$out_content .= $oa->xmlAddFooter();
		$oa = null;

		// TODO: this should actually be done by oidinfo_api.inc.php, not by us!
		$out_content = str_replace(']]>', ']]&gt;', $out_content);
		$out_content = str_replace('<information>', '<information><![CDATA[', $out_content);
		$out_content = str_replace('</information>', ']]></information>', $out_content);
		$out_content = str_replace('<description>', '<description><![CDATA[', $out_content);
		$out_content = str_replace('</description>', ']]></description>', $out_content);

		$out_type = 'text/xml';
		return array($out_content, $out_type);
	}

	/**
	 * @param string $str
	 * @return string
	 */
	private static function _formatdate(string $str): string {
		$str = explode(' ',$str)[0];
		if ($str == '0000-00-00') $str = '';
		return $str;
	}

	/**
	 * @param string $str
	 * @return string
	 * @throws OIDplusException
	 */
	private static function repair_relative_links(string $str): string {
		return preg_replace_callback('@(href\s*=\s*([\'"]))(.+)(\\2)@ismU', function($treffer) {
			$url = $treffer[3];
			if ((stripos($url,'http:') !== 0) && (stripos($url,'https:') !== 0) && (stripos($url,'ftp:') !== 0)) {
				if (stripos($url,'www.') === 0) {
					$url .= 'http://' . $url;
				} else {
					$url = OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL) . $url;
				}
			}
			return $treffer[1].$url.$treffer[4];
		}, $str);
	}

	/**
	 * @param string $address
	 * @return string[]
	 */
	private static function split_address_country(string $address): array {
		global $oidbase_countries;
		$ary = explode("\n", $address);
		$last_line = array_pop($ary);
		$rest = implode("\n", $ary);
		if (isset($oidbase_countries[$last_line])) {
			return array($rest, $oidbase_countries[$last_line]);
		} else {
			return array($rest."\n".$last_line, '');
		}
	}

	/**
	 * @param string $name
	 * @return array
	 */
	private static function split_name(string $name): array {
		// uses regex that accepts any word character or hyphen in last name
		// https://stackoverflow.com/questions/13637145/split-text-string-into-first-and-last-name-in-php
		$name = trim($name);
		$last_name = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
		$first_name = trim( preg_replace('#'.preg_quote($last_name,'#').'#', '', $name ) );
		return array($first_name, $last_name);
	}

	protected const ORPHAN_IGNORE = 0;
	protected const ORPHAN_AUTO_DEORPHAN = 1;
	protected const ORPHAN_DISALLOW_ORPHANS = 2;

	/**
	 * @param string $xml_contents
	 * @param array $errors
	 * @param bool $replaceExistingOIDs
	 * @param int $orphan_mode
	 * @return int[]
	 * @throws OIDplusException
	 */
	protected function oidbaseImportXML(string $xml_contents, array &$errors, bool $replaceExistingOIDs=false, int $orphan_mode=self::ORPHAN_AUTO_DEORPHAN): array {
		// TODO: Implement RA import (let the user decide)
		// TODO: Let the user decide about $replaceExistingOIDs
		// TODO: Let the user decide if "created=now" should be set (this is good when the XML files is created by the user itself to do bulk-inserts)

		$xml_contents = str_replace('<description><![CDATA[', '<description>', $xml_contents);
		$xml_contents = str_replace(']]></description>', '</description>', $xml_contents);
		$xml_contents = str_replace('<description>', '<description><![CDATA[', $xml_contents);
		$xml_contents = str_replace('</description>', ']]></description>', $xml_contents);

		$xml_contents = str_replace('<information><![CDATA[', '<information>', $xml_contents);
		$xml_contents = str_replace(']]></information>', '</information>', $xml_contents);
		$xml_contents = str_replace('<information>', '<information><![CDATA[', $xml_contents);
		$xml_contents = str_replace('</information>', ']]></information>', $xml_contents);

		$xml = @simplexml_load_string($xml_contents);

		$count_already_existing = 0;
		$count_errors = 0;
		$count_warnings = 0;

		if (!$xml) {
			$errors[] = _L('Cannot read XML data. The XML file is probably invalid.');
			$count_errors++;
			return array(0, 0, 1, 0);
		}

		$ok_oids = array();

		foreach ($xml->oid as $xoid) {

			if (isset($xoid->{'dot-notation'})) {
				$dot_notation = $xoid->{'dot-notation'}->__toString();
			} else if (isset($xoid->{'asn1-notation'})) {
				$dot_notation = asn1_to_dot($xoid->{'asn1-notation'}->__toString());
			} else {
				$errors[] = _L('Cannot find dot notation because fields asn1-notation and dot-notation are both not existing');
				$count_errors++;
				continue;
			}

			$id = "oid:$dot_notation";
			$title = isset($xoid->{'description'}) ? html_entity_decode(strip_tags($xoid->{'description'}->__toString())) : '';
			$info = isset($xoid->{'description'}) ? $xoid->{'information'}->__toString() : '';

			// For ASN.1 definitions, "Description" is filled with the definition and "Information" is usually empty
			if (strpos($title,'<br') !== false) {
				$info = $title . $info;
				$title = explode(' ',$title)[0];
			}

			if (isset($xoid->{'current-registrant'}->email)) {
				$ra = $xoid->{'current-registrant'}->email->__toString();
			} else if (isset($xoid->{'first-registrant'}->email)) {
				$ra = $xoid->{'first-registrant'}->email->__toString();
			} else {
				$ra = '';
			}

			if (!oid_valid_dotnotation($dot_notation, false, false)) {
				$errors[] = _L('Ignored OID %1 because its dot notation is illegal or was not found',$dot_notation);
				$count_errors++;
				continue;
			}

			$parent = 'oid:'.oid_up($dot_notation);

			if ($orphan_mode === self::ORPHAN_DISALLOW_ORPHANS) {
				if (!OIDplusObject::exists($parent)) {
					$errors[] = _L('Cannot import %1, because its parent is not in the database.',$dot_notation);
					$count_errors++;
					continue;
				}
			}

			$obj_test = OIDplusObject::findFitting($id);
			if ($obj_test) {
				if ($replaceExistingOIDs) {
					OIDplus::db()->query("delete from ###objects where id = ?", array($id));
					OIDplus::db()->query("delete from ###asn1id where oid = ?", array($id));
					OIDplus::db()->query("delete from ###iri where oid = ?", array($id));
				} else {
					//$errors[] = "Ignore OID '$dot_notation' because it already exists";
					//$count_errors++;
					$count_already_existing++;
					continue;
				}
			}

			// TODO: we can probably get the created and modified timestamp from oid-base.com XML
			OIDplus::db()->query("insert into ###objects (id, parent, title, description, confidential, ra_email) values (?, ?, ?, ?, ?, ?)", array($id, $parent, $title, $info, false, $ra));

			OIDplusObject::resetObjectInformationCache();

			$this_oid_has_warnings = false;

			// ---------------------------------------------------------------------

			$asn1ids = array();
			if (isset($xoid->{'identifier'})) {
				$asn1ids[] = $xoid->{'identifier'}->__toString();
			}
			if (isset($xoid->{'asn1-notation'})) {
				$last_id = asn1_last_identifier($xoid->{'asn1-notation'}->__toString());
				if ($last_id) {
					$asn1ids[] = $last_id;
				}
			}
			if (isset($xoid->{'synonymous-identifier'})) {
				foreach ($xoid->{'synonymous-identifier'} as $synid) {
					$asn1ids[] = $synid->__toString();
				}
			}
			$asn1ids = array_unique($asn1ids);
			foreach ($asn1ids as $asn1id) {
				if (!oid_id_is_valid($asn1id)) {
					$errors[] = _L('Warning').' ['._L('OID %1',$dot_notation).']: '._L('Ignored alphanumeric identifier %1, because it is invalid',$asn1id);
					$this_oid_has_warnings = true;
				} else {
					OIDplus::db()->query("delete from ###asn1id where oid = ? and name = ?", array($id, $asn1id)); // Attention: Requires case-SENSITIVE database collation!!
					OIDplus::db()->query("insert into ###asn1id (oid, name) values (?, ?)", array($id, $asn1id));
				}
			}

			// ---------------------------------------------------------------------

			if (isset($xoid->{'unicode-label'})) {
				$iris = array();
				foreach ($xoid->{'unicode-label'} as $iri) {
					$iris[] = $iri->__toString();
				}
				$iris = array_unique($iris);
				foreach ($iris as $iri) {
					if (!iri_arc_valid($iri, false)) {
						$errors[] = _L('Warning').' ['._L('OID %1',$dot_notation).']: '._L('Ignored Unicode label %1, because it is invalid',$iri);
						$this_oid_has_warnings = true;
					} else {
						OIDplus::db()->query("delete from ###iri where oid = ? and name = ?", array($id, $iri)); // Attention: Requires case-SENSITIVE database collation!!
						OIDplus::db()->query("insert into ###iri (oid, name) values (?, ?)", array($id, $iri));
					}
				}
			}

			if ($this_oid_has_warnings) $count_warnings++;
			$ok_oids[] = $id;
		}

		// De-orphanize
		//if ($orphan_mode === self::ORPHAN_AUTO_DEORPHAN) {
		//	OIDplus::db()->query("update ###objects set parent = 'oid:' where parent like 'oid:%' and parent not in (select id from ###objects)");
		//	OIDplusObject::resetObjectInformationCache();
		//}
		foreach ($ok_oids as $id) {
			// De-orphanize if neccessary
			if ($orphan_mode === self::ORPHAN_AUTO_DEORPHAN) {
				$res = OIDplus::db()->query("select * from ###objects where id = ? and parent not in (select id from ###objects)", array($id));
				if ($res->any()) {
					$errors[] = _L("%1 was de-orphaned (placed as root OID) because its parent is not existing.",$id);
					$count_warnings++;
					OIDplus::db()->query("update ###objects set parent = 'oid:' where id = ? and parent not in (select id from ###objects)", array($id));
					OIDplusObject::resetObjectInformationCache();
				}
			}

			// We do the logging at the end, otherwise SUPOIDRA() might not work correctly if the OIDs were not imported in order or if there were orphans
			OIDplus::logger()->log("V2:[INFO]OID(%1)+[INFO]SUPOID(%1)+[INFO]SUPOIDRA(%1)+[OK/INFO]A", "Object '%1' was automatically created by the XML import tool", $id);
		}

		$count_imported_oids = count($ok_oids);

		return array($count_imported_oids, $count_already_existing, $count_errors, $count_warnings);

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
				$title = _L('OID-Base.com import/export');
				$notifications[] = new OIDplusNotification('ERR', _L('OIDplus plugin "%1" is enabled, but OIDplus cannot connect to the Internet.', '<a '.OIDplus::gui()->link('oidplus:datatransfer').'>'.htmlentities($title).'</a>').' '.$reason);
			}
		}
		return $notifications;
	}

}
