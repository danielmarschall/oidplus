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

class OIDplusPageAdminOIDInfoExport extends OIDplusPagePluginAdmin {

	/*private*/ const QUERY_LIST_OIDINFO_OIDS_V1 = '1.3.6.1.4.1.37476.2.5.2.1.5.1';
	/*private*/ const QUERY_GET_OIDINFO_DATA_V1  = '1.3.6.1.4.1.37476.2.5.2.1.6.1';

	public function action($actionID, $params) {

		if ($actionID == 'import_xml_file') {
			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				throw new OIDplusException('You need to log in as administrator.');
			}

			if (!isset($_FILES['userfile'])) {
				throw new OIDplusException('Please choose a file');
			}

			$xml_contents = file_get_contents($_FILES['userfile']['tmp_name']);

			$errors = array();
			list($count_imported_oids, $count_already_existing, $count_errors, $count_warnings) = $this->oidinfoImportXML($xml_contents, $errors, $replaceExistingOIDs=false, $orphan_mode=self::ORPHAN_AUTO_DEORPHAN);
			if (count($errors) > 0) {
				// Note: These "errors" can also be warnings (partial success)
				// TODO XXX: since the output can be very long, should we really show it in a JavaScript alert() ?!
				return array(
					"status" => 1,
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
		} else if ($actionID == 'import_oidinfo_oid') {
			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				throw new OIDplusException('You need to log in as administrator.');
			}

			$oid = $params['oid'];

			$query = self::QUERY_GET_OIDINFO_DATA_V1;

			$payload = array(
				"query" => $query, // we must repeat the query because we want to sign it
				"system_id" => OIDplus::getSystemId(false),
				"oid" => $oid
			);

			$signature = '';
			if (!@openssl_sign(json_encode($payload), $signature, OIDplus::config()->getValue('oidplus_private_key'))) {
				throw new OIDplusException("Signature failed");
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
				throw new OIDplusException("Communication with ViaThinkSoft server failed: " . curl_error($ch));
			}
			curl_close($ch);

			$json = json_decode($res, true);

			if (!$json) {
				return array("status" => 1, "error" => 'JSON reply from ViaThinkSoft decoding error: ' . $res);
			}

			if (isset($json['error']) || ($json['status'] != 0)) {
				return array("status" => 1, "error" => $json['error']);
			} else {
				$errors = array();
				list($count_imported_oids, $count_already_existing, $count_errors, $count_warnings) = $this->oidinfoImportXML('<oid-database>'.$json['xml'].'</oid-database>', $errors, $replaceExistingOIDs=false, $orphan_mode=self::ORPHAN_DISALLOW_ORPHANS);
				if (count($errors) > 0) {
					return array("status" => 1, "error" => implode("\n",$errors));
				} else if ($count_imported_oids <> 1) {
					return array("status" => 1, "error" => "Imported $count_imported_oids, but expected to import 1");
				} else {
					return array("status" => 0);
				}

			}
		} else {
			throw new OIDplusException("Unknown action ID");
		}
	}

	public function init($html=true) {
		// Nothing
	}

	public function gui($id, &$out, &$handled) {
		if ($id === 'oidplus:oidinfo_compare_export') {
			$handled = true;
			$out['title'] = 'List OIDs in your system which are missing at oid-info.com';
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>You need to <a '.OIDplus::gui()->link('oidplus:login').'>log in</a> as administrator.</p>';
				return;
			}

			$query = self::QUERY_LIST_OIDINFO_OIDS_V1;

			$payload = array(
				"query" => $query, // we must repeat the query because we want to sign it
				"system_id" => OIDplus::getSystemId(false)
			);

			$signature = '';
			if (!@openssl_sign(json_encode($payload), $signature, OIDplus::config()->getValue('oidplus_private_key'))) {
				throw new OIDplusException("Signature failed");
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
				throw new OIDplusException("Communication with ViaThinkSoft server failed: " . curl_error($ch));
			}
			curl_close($ch);

			$json = json_decode($res, true);

			if (!$json) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = 'JSON reply from ViaThinkSoft decoding error: ' . $res;
				return $out;
			}

			$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:datatransfer').'><img src="img/arrow_back.png" width="16"> Go back to data transfer main page</a>'; // TODO: How to automatically jump to the "Export" tab?

			if (isset($json['error']) || ($json['status'] != 0)) {
				$out['text'] = '<p>Error: ' . htmlentities($json['error']) . '</p>';
			} else {
				// TODO: If roots were created or deleted recently, we must do a re-query of the registration, so that the "roots" information at the directory service gets refreshed
				if (count($json['roots']) == 0) $out['text'] = '<p>In order to use this feature, you need to have at least one (root) OID added in your system, and the system needs to report the newly added root to the directory service (the reporting interval is 1 hour).</p>';
				foreach ($json['roots'] as $root) {
					$oid = $root['oid'];
					$out['text'] .= '<h2>Root OID '.$oid.'</h2>';
					if ($root['verified']) {
						$count = 0;
						$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
						$out['text'] .= '<table class="table table-bordered table-striped">';
						$out['text'] .= '<tr><th colspan="3">Actions</th><th>OID</th></tr>';

						$lookup_nonoid = array();
						$row_lookup = array();

						$all_local_oids_of_root = array();
						$res = OIDplus::db()->query("select * from ###objects");
						while ($row = $res->fetch_object()) {
							if (strpos($row->id, 'oid:') === 0) {
								$oid = substr($row->id,strlen('oid:'));
								if (strpos($oid.'.', $root['oid']) === 0) {
									$row_lookup[$oid] = $row;
									$all_local_oids_of_root[] = $oid;
								}
							} else {
								$obj = OIDplusObject::parse($row->id);
								if (!$obj) continue; // can happen when object type is not enabled
								$aids = $obj->getAltIds();
								foreach ($aids as $aid) {
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

						foreach ($all_local_oids_of_root as $local_oid) {
							if (!in_array($local_oid, $root['children'])) {
								$count++;

								// Start: Build oid-info.com create URL

								$row = $row_lookup[$local_oid];

								$url = "http://www.oid-info.com/cgi-bin/manage?f=".oid_up($local_oid)."&a=create";

								$tmp = explode('.',$local_oid);
								$url .= "&nb=".urlencode(array_pop($tmp));

								$asn1_ids = array();
								$res2 = OIDplus::db()->query("select * from ###asn1id where oid = ?", array($row->id));
								while ($row2 = $res2->fetch_object()) {
									$asn1_ids[] = $row2->name; // 'unicode-label' is currently not in the standard format (oid.xsd)
								}
								$url .= "&id=".array_shift($asn1_ids); // urlencode wurde schon oben gemacht
								$url .= "&syn_id=".implode('%0A', $asn1_ids); // urlencode wurde schon oben gemacht

								$iri_ids = array();
								$res2 = OIDplus::db()->query("select * from ###iri where oid = ?", array($row->id));
								while ($row2 = $res2->fetch_object()) {
									$iri_ids[] = $row2->name;
								}
								$url .= "&unicode_label_list=".implode('%0A', $iri_ids); // urlencode wurde schon oben gemacht

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
									$tmp_description = '<i>No description available</i>';
									$tmp_information = '';
								}

								if ($tmp_information != '') {
									$tmp_information .= '<br/><br/>';
								}

								$tmp_information .= 'See <a href="'.OIDplus::getSystemUrl(false).'?goto='.urlencode($id).'">more information</a>.';

								if (explode(':',$id,2)[0] != 'oid') {
									$tmp_information = "Object: $id\n\n" . $tmp_information;
								}

								$url .= "&description=".urlencode(self::repair_relative_links($tmp_description));
								$url .= "&info=".urlencode(self::repair_relative_links($tmp_information));

								$url .= "&current_registrant_email=".urlencode($row->ra_email);

								$res2 = OIDplus::db()->query("select * from ###ra where email = ?", array($row->ra_email));
								if ($res2->num_rows() > 0) {
									$row2 = $res2->fetch_object();

									$tmp = array();
									if (!empty($row2->personal_name)) {
										$name_ary = split_firstname_lastname($row2->personal_name);
										$tmp_first_name = $name_ary[0];
										$tmp_last_name  = $name_ary[1];
										if (!empty($row2->ra_name)       ) $tmp[] = $row2->ra_name;
										if (!empty($row2->office)        ) $tmp[] = $row2->office;
										if (!empty($row2->organization)  ) $tmp[] = $row2->organization;
									} else {
										$tmp_first_name = $row2->ra_name;
										$tmp_last_name  = '';
										if (!empty($row2->personal_name) ) $tmp[] = $row2->personal_name;
										if (!empty($row2->office)        ) $tmp[] = $row2->office;
										if (!empty($row2->organization)  ) $tmp[] = $row2->organization;
									}

									if (empty($tmp_first_name) || empty($tmp_last_name)) {
										$name = self::split_name($tmp_first_name.' '.$tmp_last_name);
										$tmp_first_name = $name[0];
										$tmp_last_name = $name[1];
									}
									$url .= "&current_registrant_first_name=".urlencode($tmp_first_name);
									$url .= "&current_registrant_last_name=".urlencode($tmp_last_name);

									if ((count($tmp) > 0) && ($tmp[0] == $row2->ra_name)) array_shift($tmp);
									array_unique($tmp);

									if (!$row2->privacy) {
										if (!empty($row2->street))   $tmp[] = $row2->street;
										if (!empty($row2->zip_town)) $tmp[] = $row2->zip_town;
										if (!empty($row2->country))  $tmp[] = $row2->country;
										$url .= "&current_registrant_tel=".urlencode(!empty($row2->phone) ? $row2->phone : $row2->mobile);
										$url .= "&current_registrant_fax=".urlencode($row2->fax);
									}
									if (empty($row2->zip_town) && empty($row2->country)) {
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

								// End: Build oid-info.com create URL

								// Note: "Actions" is at the left, because it has a fixed width, so the user can continue clicking without the links moving if the OID length changes between lines
								$out['text'] .= '<tr id="missing_oid_'.str_replace('.','_',$local_oid).'">'.
								'<td><a '.OIDplus::gui()->link(isset($lookup_nonoid[$local_oid]) ? $lookup_nonoid[$local_oid] : 'oid:'.$local_oid, true).'>View local OID</a></td>'.
								'<td><a href="javascript:removeMissingOid(\''.$local_oid.'\');">Ignore for now</a></td>'.
								'<td><a target="_blank" href="'.$url.'">Add to oid-info.com manually</a></td>'.
								'<td>'.$local_oid.'</td>'.
								'</tr>';
							}
						}
						if ($count == 0) {
							$out['text'] = '<tr><td colspan="4">No missing OIDs found</td></tr>';
						}
						$out['text'] .= '</table></div></div>';
					} else {
						$out['text'] .= '<p>This root is not validated. Please send an email to '.$json['vts_verification_email'].' in order to request ownership verification of this root OID.</p>';
					}
				}
			}
		}

		if ($id === 'oidplus:oidinfo_compare_import') {
			$handled = true;
			$out['title'] = 'List OIDs at oid-info.com which are missing in your system';
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>You need to <a '.OIDplus::gui()->link('oidplus:login').'>log in</a> as administrator.</p>';
				return;
			}

			$query = self::QUERY_LIST_OIDINFO_OIDS_V1;

			$payload = array(
				"query" => $query, // we must repeat the query because we want to sign it
				"system_id" => OIDplus::getSystemId(false)
			);

			$signature = '';
			if (!@openssl_sign(json_encode($payload), $signature, OIDplus::config()->getValue('oidplus_private_key'))) {
				throw new OIDplusException("Signature failed");
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
				throw new OIDplusException("Communication with ViaThinkSoft server failed: " . curl_error($ch));
			}
			curl_close($ch);

			$json = json_decode($res, true);

			if (!$json) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = 'JSON reply from ViaThinkSoft decoding error: ' . $res;
				return $out;
			}

			$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:datatransfer').'><img src="img/arrow_back.png" width="16"> Go back to data transfer main page</a>'; // TODO: How to automatically jump to the "Import" tab?

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
						if ($aid->getNamespace() == 'oid') {
							$all_local_oids[] = $aid->getId();
						}
					}
				}
			}

			if (isset($json['error']) || ($json['status'] != 0)) {
				$out['text'] = '<p>Error: ' . htmlentities($json['error']) . '</p>';
			} else {
				// TODO: If roots were created or deleted recently, we must do a re-query of the registration, so that the "roots" information at the directory service gets refreshed
				if (count($json['roots']) == 0) $out['text'] = '<p>In order to use this feature, you need to have at least one (root) OID added in your system, and the system needs to report the newly added root to the directory service (the reporting interval is 1 hour).</p>';
				foreach ($json['roots'] as $root) {
					$oid = $root['oid'];
					$out['text'] .= '<h2>Root OID '.$oid.'</h2>';
					// TODO: "Import all" button
					if ($root['verified']) {
						$count = 0;
						$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
						$out['text'] .= '<table class="table table-bordered table-striped">';
						$out['text'] .= '<tr><th colspan="4">Actions</th><th>OID</th></tr>';
						foreach ($root['children'] as $child_oid) {
							if (!in_array($child_oid, $all_local_oids)) {
								$count++;
								// Note: "Actions" is at the left, because it has a fixed width, so the user can continue clicking without the links moving if the OID length changes between lines
								$out['text'] .= '<tr id="missing_oid_'.str_replace('.','_',$child_oid).'">'.
								'<td><a target="_blank" href="http://www.oid-info.com/get/'.$child_oid.'">View OID at oid-info.com</a></td>'.
								'<td><a href="javascript:removeMissingOid(\''.$child_oid.'\');">Ignore for now</a></td>'.
								'<td><a href="mailto:admin@oid-info.com">Report illegal OID</a></td>'.
								'<td><a href="javascript:importMissingOid(\''.$child_oid.'\');">Import OID</a></td>'.
								'<td>'.$child_oid.'</td>'.
								'</tr>';
							}
						}
						if ($count == 0) {
							$out['text'] = '<tr><td colspan="5">No extra OIDs found</td></tr>';
						}
						$out['text'] .= '</table></div></div>';
					} else {
						$out['text'] .= '<p>This root is not validated. Please send an email to '.$json['vts_verification_email'].' in order to request ownership verification of this root OID.</p>';
					}
				}
			}
		}

		if ($id === 'oidplus:datatransfer') {
			$handled = true;
			$out['title'] = 'Data Transfer';
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>You need to <a '.OIDplus::gui()->link('oidplus:login').'>log in</a> as administrator.</p>';
				return;
			}

			$out['text'] = '';

			$out['text'] .= '<div id="oidinfoMainArea" style="visibility: hidden"><div id="oidinfoMainTab" class="container" style="width:100%;">';
			$out['text'] .= (OIDplus::baseConfig()->getValue('RECAPTCHA_ENABLED', false) ?
			                '<script> grecaptcha.render(document.getElementById("g-recaptcha"), { "sitekey" : "'.OIDplus::baseConfig()->getValue('RECAPTCHA_PUBLIC', '').'" }); </script>'.
			                '<p>Before logging in, please solve the following CAPTCHA</p><div id="g-recaptcha" class="g-recaptcha" data-sitekey="'.OIDplus::baseConfig()->getValue('RECAPTCHA_PUBLIC', '').'"></div>' : '');
			$out['text'] .= '<br>';
			$out['text'] .= '<ul class="nav nav-pills">';
			$out['text'] .= '			<li class="active">';
			$out['text'] .= '			<a href="#1a" data-toggle="tab">Export</a>';
			$out['text'] .= '			</li>';
			$out['text'] .= '			<li><a href="#2a" data-toggle="tab">Import</a>';
			$out['text'] .= '			</li>';
			$out['text'] .= '		</ul>';
			$out['text'] .= '			<div class="tab-content clearfix">';
			$out['text'] .= '			  <div class="tab-pane active" id="1a">';
			// ---------------- Start "Export" tab
			$out['text'] .= '<h2>Generate XML file containing all OIDs</h2>';
			$out['text'] .= '<p>These XML files are following the <a href="http://www.oid-info.com/oid.xsd" target="_blank">XML schema</a> of <b>oid-info.com</b>. They can be used for various purposes though.</p>';
			$out['text'] .= '<p><input type="button" onclick="window.open(\''.OIDplus::webpath(__DIR__).'oidinfo_export.php\',\'_blank\')" value="Generate XML (all)"></p>';
			$out['text'] .= '<p><input type="button" onclick="window.open(\''.OIDplus::webpath(__DIR__).'oidinfo_export.php?online=1\',\'_blank\')" value="Generate XML (only OIDs which do not exist at oid-info.com)"></p>';
			$out['text'] .= '<p><a href="http://www.oid-info.com/submit.htm" target="_blank">Upload XML files manually to oid-info.com</a></p>';
			$out['text'] .= '<br><p>Attention: Do not use this XML Export/Import to exchange, backup or restore data between OIDplus systems!<br>It will cause various loss of information, e.g. because Non-OIDs like GUIDs are converted in OIDs and can\'t be converted back.</p>';
			$out['text'] .= '<h2>Automatic export to oid-info.com</h2>';
			$privacy_level = OIDplus::config()->getValue('reg_privacy');
			if ($privacy_level == 0) {
				$out['text'] .= '<p>All your OIDs will automatically submitted to oid-info.com through the remote directory service in regular intervals. (<a '.OIDplus::gui()->link('oidplus:srv_registration').'>Change preference</a>)</p>';
			} else {
				$out['text'] .= '<p>If you set the privacy option to "0" (your system is registered), then all your OIDs will be automatically exported to oid-info.com. (<a '.OIDplus::gui()->link('oidplus:srv_registration').'>Change preference</a>)</p>';
			}
			$out['text'] .= '<h2>Comparison with oid-info.com</h2>';
			$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:oidinfo_compare_export').'>List OIDs in your system which are missing at oid-info.com</a></p>';
			// ---------------- End "Export" tab
			$out['text'] .= '				</div>';
			$out['text'] .= '				<div class="tab-pane" id="2a">';
			// ---------------- Start "Import" tab
			$out['text'] .= '<h2>Import XML file</h2>';
			$out['text'] .= '<p>These XML files are following the <a href="http://www.oid-info.com/oid.xsd" target="_blank">XML schema</a> of <b>oid-info.com</b>.</p>';
			// TODO XXX: we need a waiting animation!
			$out['text'] .= '<form onsubmit="return uploadXmlFileOnSubmit(this);" enctype="multipart/form-data" id="uploadXmlFileForm">';
			$out['text'] .= '<div>Choose XML file here:<input type="file" name="userfile" value="" id="userfile">';
			$out['text'] .= '<br><input type="submit" value="Import XML"></div>';
			$out['text'] .= '</form>';
			$out['text'] .= '<br><p>Attention: Do not use this XML Export/Import to exchange, backup or restore data between OIDplus systems!<br>It will cause various loss of information, e.g. because Non-OIDs like GUIDs are converted in OIDs and can\'t be converted back.</p>';
			$out['text'] .= '<h2>Comparison with oid-info.com</h2>';
			$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:oidinfo_compare_import').'>List OIDs at oid-info.com which are missing in your system</a></p>';
			// ---------------- End "Import" tab
			$out['text'] .= '				</div>';
			$out['text'] .= '			</div>';
			$out['text'] .= '  </div></div>';

			$out['text'] .= '<script>document.getElementById("oidinfoMainArea").style.visibility = "visible";</script>';
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
			'id' => 'oidplus:datatransfer',
			'icon' => $tree_icon,
			'text' => 'Data Transfer'
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}

	public static function outputXML($only_non_existing) {
		// This file contains class OIDInfoAPI.
		// We cannot include this in init(), because the init
		// of the registration plugin (OIDplusPageAdminRegistration) uses
		// OIDplusPageAdminOIDInfoExport::outputXML() before
		// OIDplusPageAdminOIDInfoExport::init() ,
		// because OIDplusPageAdminRegistration::init() comes first sometimes.
		require_once __DIR__ . '/oidinfo_api.inc.php';

		$oa = new OIDInfoAPI();
		$oa->addSimplePingProvider('viathinksoft.de:49500');

		$email = OIDplus::config()->getValue('admin_email');
		if (empty($email)) $email = 'unknown@example.com';

		echo $oa->xmlAddHeader(OIDplus::config()->getValue('system_title'), isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'Export interface', $email);

		$params['allow_html'] = true;
		$params['allow_illegal_email'] = true; // It should be enabled, because the creator could have used some kind of human-readable anti-spam technique
		$params['soft_correct_behavior'] = OIDInfoAPI::SOFT_CORRECT_BEHAVIOR_NONE;
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
			$res = OIDplus::db()->query("select * from ###objects where id = ?", array($id));
			if ($row = $res->fetch_object()) {
				$elements['identifier'] = array();
				$res2 = OIDplus::db()->query("select * from ###asn1id where oid = ?", array($row->id));
				while ($row2 = $res2->fetch_object()) {
					$elements['identifier'][] = $row2->name; // 'unicode-label' is currently not in the standard format (oid.xsd)
				}

				$elements['unicode-label'] = array();
				$res2 = OIDplus::db()->query("select * from ###iri where oid = ?", array($row->id));
				while ($row2 = $res2->fetch_object()) {
					$elements['unicode-label'][] = $row2->name;
				}

				if (!empty($row->title)) {
					$elements['description'] = $row->title;
					$elements['information'] = $row->description;
					if (trim($row->title) == trim(strip_tags($row->description))) {
						$elements['information'] = '';
					}
				} else if (isset($elements['identifier'][0])) {
					$elements['description'] = '"'.$elements['identifier'][0].'"';
					$elements['information'] = $row->description;
				} else if (isset($elements['unicode-label'][0])) {
					$elements['description'] = '"'.$elements['unicode-label'][0].'"';
					$elements['information'] = $row->description;
				} else if (!empty($row->description)) {
					$elements['description'] = $row->description;
					$elements['information'] = '';
				} else if (!empty($row->comment)) {
					$elements['description'] = $row->comment;
					$elements['information'] = '';
				} else {
					$elements['description'] = '<i>No description available</i>';
					$elements['information'] = '';
				}

				if ($elements['information'] != '') {
					$elements['information'] .= '<br/><br/>';
				}

				$elements['information'] .= 'See <a href="'.OIDplus::getSystemUrl(false).'?goto='.urlencode($id).'">more information</a>.';

				if (explode(':',$id,2)[0] != 'oid') {
					$elements['information'] = "Object: $id\n\n" . $elements['information'];
				}

				$elements['description'] = self::repair_relative_links($elements['description']);
				$elements['information'] = self::repair_relative_links($elements['information']);

				$elements['first-registrant']['first-name'] = '';
				$elements['first-registrant']['last-name'] = '';
				$elements['first-registrant']['address'] = '';
				$elements['first-registrant']['email'] = '';
				$elements['first-registrant']['phone'] = '';
				$elements['first-registrant']['fax'] = '';
				$elements['first-registrant']['creation-date'] = self::_formatdate($row->created);

				$elements['current-registrant']['first-name'] = '';
				$elements['current-registrant']['last-name'] = '';
				$elements['current-registrant']['email'] = $row->ra_email;
				$elements['current-registrant']['phone'] = '';
				$elements['current-registrant']['fax'] = '';
				$elements['current-registrant']['address'] = '';

				$res2 = OIDplus::db()->query("select * from ###ra where email = ?", array($row->ra_email));
				if ($res2->num_rows() > 0) {
					$row2 = $res2->fetch_object();

					$tmp = array();
					if (!empty($row2->personal_name)) {
						$name_ary = split_firstname_lastname($row2->personal_name);
						$elements['current-registrant']['first-name'] = $name_ary[0];
						$elements['current-registrant']['last-name']  = $name_ary[1];
						if (!empty($row2->ra_name)       ) $tmp[] = $row2->ra_name;
						if (!empty($row2->office)        ) $tmp[] = $row2->office;
						if (!empty($row2->organization)  ) $tmp[] = $row2->organization;
					} else {
						$elements['current-registrant']['first-name'] = $row2->ra_name;
						$elements['current-registrant']['last-name']  = '';
						if (!empty($row2->personal_name) ) $tmp[] = $row2->personal_name;
						if (!empty($row2->office)        ) $tmp[] = $row2->office;
						if (!empty($row2->organization)  ) $tmp[] = $row2->organization;
					}

					if ((count($tmp) > 0) && ($tmp[0] == $row2->ra_name)) array_shift($tmp);
					array_unique($tmp);

					if (!$row2->privacy) {
						if (!empty($row2->street))   $tmp[] = $row2->street;
						if (!empty($row2->zip_town)) $tmp[] = $row2->zip_town;
						if (!empty($row2->country))  $tmp[] = $row2->country;
						$elements['current-registrant']['phone'] = !empty($row2->phone) ? $row2->phone : $row2->mobile;
						$elements['current-registrant']['fax'] = $row2->fax;
					}
					if (empty($row2->zip_town) && empty($row2->country)) {
						// The address is useless if we do neither know city nor country
						// Ignore it
						$elements['current-registrant']['address'] = '';
					} else {
						$elements['current-registrant']['address'] = implode("<br/>", $tmp);
					}
				}
				$elements['current-registrant']['modification-date'] = self::_formatdate($row->updated);

				// Request from O.D. 20 May 2019: First registrant should not be empty (especially for cases where Creation and Modify Dates are the same)
				// Actually, this is a problem because we don't know the first registrant.
				// However, since oidinfo gets their XML very fast (if using registration), it is likely that the reported RA is still the same...
				// ... and changes at the RA are not reported to oid-info.com anyways - the XML is only for creation

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

				$obj = OIDplusObject::parse($row->id);

				list($ns,$id) = explode(':',$obj->nodeId());
				if ($ns == 'oid') {
					echo $oa->createXMLEntry($id, $elements, $params, $comment=$obj->nodeId());
				}

				$alt_ids = $obj->getAltIds(); // TODO: slow!
				foreach ($alt_ids as $alt_id) {
					$ns = $alt_id->getNamespace();
					$id = $alt_id->getId();
					$desc = $alt_id->getDescription();
					if ($ns == 'oid') {
						if (strpos($id, '2.25.') === 0) continue; // don't spam the uuid arc with GUID objects
						echo $oa->createXMLEntry($id, $elements, $params, $comment=$obj->nodeId());
					}
				}
			}
		}

		echo $oa->xmlAddFooter();
	}

	private static function _formatdate($str) {
		$str = explode(' ',$str)[0];
		if ($str == '0000-00-00') $str = '';
		return $str;
	}

	private static function repair_relative_links($str) {
		$str = preg_replace_callback('@(href\s*=\s*([\'"]))(.+)(\\2)@ismU', function($treffer) {
			$url = $treffer[3];
			if ((stripos($url,'http:') !== 0) && (stripos($url,'https:') !== 0) && (stripos($url,'ftp:') !== 0)) {
				if (stripos($url,'www.') === 0) {
					$url .= 'http://' . $url;
				} else {
					$url = OIDplus::getSystemUrl() . $url;
				}
			}
			return $treffer[1].$url.$treffer[4];
		}, $str);
		return $str;
	}

	private static function split_address_country($address) {
		global $oidinfo_countries;
		$ary = explode("\n", $address);
		$last_line = array_pop($ary);
		$rest = implode("\n", $ary);
		if (isset($oidinfo_countries[$last_line])) {
			return array($rest, $oidinfo_countries[$last_line]);
		} else {
			return array($rest."\n".$last_line, '');
		}
	}

	private static function split_name($name) {
		// uses regex that accepts any word character or hyphen in last name
		// https://stackoverflow.com/questions/13637145/split-text-string-into-first-and-last-name-in-php
		$name = trim($name);
		$last_name = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
		$first_name = trim( preg_replace('#'.$last_name.'#', '', $name ) );
		return array($first_name, $last_name);
	}

	/*protected*/ const ORPHAN_IGNORE = 0;
	/*protected*/ const ORPHAN_AUTO_DEORPHAN = 1;
	/*protected*/ const ORPHAN_DISALLOW_ORPHANS = 2;

	protected function oidinfoImportXML($xml_contents, &$errors, $replaceExistingOIDs=false, $orphan_mode=self::ORPHAN_AUTO_DEORPHAN) {
		// TODO XXX: Implement RA import (let the user decide)
		// TODO XXX: Let the user decide about $replaceExistingOIDs
		// TODO XXX: Let the user decide if "created=now" should be set (this is good when the XML files is created by the user itself to do bulk-inserts)

		$xml_contents = str_replace('<description>', '<description><![CDATA[', $xml_contents);
		$xml_contents = str_replace('</description>', ']]></description>', $xml_contents);

		$xml_contents = str_replace('<information>', '<information><![CDATA[', $xml_contents);
		$xml_contents = str_replace('</information>', ']]></information>', $xml_contents);

		$xml = @simplexml_load_string($xml_contents);

		$count_already_existing = 0;
		$count_errors = 0;
		$count_warnings = 0;

		if (!$xml) {
			$errors[] = "Cannot read XML data. The XML file is probably invalid.";
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
				$errors[] = "Cannot find dot notation because fields asn1-notation and dot-notation are both not existing";
				$count_errors++;
				continue;
			}

			$id = "oid:$dot_notation";
			$title = isset($xoid->{'description'}) ? $xoid->{'description'}->__toString() : '';
			$info = isset($xoid->{'description'}) ? $xoid->{'information'}->__toString() : '';

			if (isset($xoid->{'current-registrant'}->email)) {
				$ra = $xoid->{'current-registrant'}->email->__toString();
			} else if (isset($xoid->{'first-registrant'}->email)) {
				$ra = $xoid->{'first-registrant'}->email->__toString();
			} else {
				$ra = '';
			}

			if (!oid_valid_dotnotation($dot_notation, false, false)) {
				$errors[] = "Ignored OID '$dot_notation' because its dot notation is illegal or was not found";
				$count_errors++;
				continue;
			}

			$parent = 'oid:'.oid_up($dot_notation);

			if ($orphan_mode === self::ORPHAN_DISALLOW_ORPHANS) {
				$res = OIDplus::db()->query("select * from ###objects where id = ?", array($parent));
				if ($res->num_rows() === 0) {
					$errors[] = "Cannot import $dot_notation, because its parent is not in the database.";
					$count_errors++;
					continue;
				}
			}

			$res = OIDplus::db()->query("select * from ###objects where id = ?", array($id));
			if ($res->num_rows() > 0) {
				if ($replaceExistingOIDs) {
					// TODO: better do this (and the following insert) as transaction
					OIDplus::db()->query("delete from ###asn1id where oid = ?", array($id));
					OIDplus::db()->query("delete from ###iri where oid = ?", array($id));
					OIDplus::db()->query("delete from ###objects where id = ?", array($id));
				} else {
					//$errors[] = "Ignore OID '$dot_notation' because it already exists";
					//$count_errors++;
					$count_already_existing++;
					continue;
				}
			}

			OIDplus::db()->query("insert into ###objects (id, parent, title, description, confidential, ra_email) values (?, ?, ?, ?, ?, ?)", array($id, $parent, $title, $info, 0, $ra));

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
					$errors[] = "Warning: OID '$dot_notation': Ignored alphanumeric identifier '$asn1id' because it is invalid";
					$this_oid_has_warnings = true;
				} else {
					OIDplus::db()->query("delete from ###asn1id where oid = ? and name = ?", array($id, $asn1id));
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
						$errors[] = "Warning: OID '$dot_notation': Ignored Unicode label '$iri' because it is invalid";
						$this_oid_has_warnings = true;
					} else {
						OIDplus::db()->query("delete from ###iri where oid = ? and name = ?", array($id, $iri));
						OIDplus::db()->query("insert into ###iri (oid, name) values (?, ?)", array($id, $iri));
					}
				}
			}

			if ($this_oid_has_warnings) $count_warnings++;
			$ok_oids[] = $id;
		}

		// De-orphanize
		//if ($orphan_mode === self::ORPHAN_AUTO_DEORPHAN) OIDplus::db()->query("update ###objects set parent = 'oid:' where parent like 'oid:%' and parent not in (select id from ###objects)");
		foreach ($ok_oids as $id) {
			// De-orphanize if neccessary
			if ($orphan_mode === self::ORPHAN_AUTO_DEORPHAN) {
				$res = OIDplus::db()->query("select * from ###objects where id = ? and parent not in (select id from ###objects)", array($id));
				if ($res->num_rows() > 0) {
					$errors[] = "'$id' was de-orphanized (placed as root OID) because its parent is not existing.";
					$count_warnings++;
					OIDplus::db()->query("update ###objects set parent = 'oid:' where id = ? and parent not in (select id from ###objects)", array($id));
				}
			}

			// We do the logging at the end, otherwise SUPOIDRA() might not work correctly if the OIDs were not imported in order or if there were orphans
			OIDplus::logger()->log("[INFO]OID($id)+[INFO]SUPOID($id)+[INFO]SUPOIDRA($id)!/[INFO]A!", "Object '$id' was automatically created by the XML import tool");
		}

		$count_imported_oids = count($ok_oids);

		return array($count_imported_oids, $count_already_existing, $count_errors, $count_warnings);

	}

}
