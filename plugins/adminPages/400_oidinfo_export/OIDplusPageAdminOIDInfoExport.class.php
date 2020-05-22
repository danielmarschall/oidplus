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

	public function init($html=true) {
		// Nothing
	}

	public function gui($id, &$out, &$handled) {
		if ($id === 'oidplus:export') {
			$handled = true;
			$out['title'] = 'Data export';
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>You need to <a '.OIDplus::gui()->link('oidplus:login').'>log in</a> as administrator.</p>';
				return;
			}

			$out['text'] = '<p>Here you can prepare the data export to <b>oid-info.com</b>.</p>'.
				       '<p><a href="'.OIDplus::webpath(__DIR__).'oidinfo_export.php">Generate XML (all)</a></p>'.
				       '<p><a href="'.OIDplus::webpath(__DIR__).'oidinfo_export.php?online=1">Generate XML (only non-existing)</a></p>'.
				       '<p><a href="http://www.oid-info.com/submit.htm">Upload to oid-info.com</a></p>';
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
			'id' => 'oidplus:export',
			'icon' => $tree_icon,
			'text' => 'Data export'
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

				$elements['information'] .= 'See <a href="'.OIDplus::getSystemUrl(false).'?goto='.urlencode($id).'">more information</a>.'; // TODO: system_url() geht nicht bei CLI

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
}
