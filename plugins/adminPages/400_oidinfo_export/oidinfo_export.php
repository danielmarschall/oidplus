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

require_once __DIR__ . '/../../../includes/oidplus.inc.php';
require_once __DIR__ . '/../../../includes/oidinfo_api.inc.php';

header('Content-Type:text/html; charset=UTF-8');

OIDplus::init(true);

OIDplus::db()->set_charset("UTF8");
OIDplus::db()->query("SET NAMES 'utf8'");

# ---

if (OIDplus::config()->getValue('oidinfo_export_protected') && !OIDplus::authUtils()::isAdminLoggedIn()) {
	echo '<p>You need to <a href="'.OIDplus::system_url().'?goto=oidplus:login">log in</a> as administrator.</p>';
	die();
}

header('Content-Type:text/xml');

$oa = new OIDInfoAPI();
$oa->addSimplePingProvider('viathinksoft.de:49500');

$email = OIDplus::config()->getValue('admin_email');
if (empty($email)) $email = 'unknown@example.com';

echo $oa->xmlAddHeader(OIDplus::config()->systemTitle(), isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'Export interface', $email);

$params['allow_html'] = true;
$params['allow_illegal_email'] = true; // It should be enabled, because the creator could have used some kind of human-readable anti-spam technique
$params['soft_correct_behavior'] = OIDInfoAPI::SOFT_CORRECT_BEHAVIOR_NONE;
$params['do_online_check'] = false; // Flag to disable this online check, because it generates a lot of traffic and runtime.
$params['do_illegality_check'] = true;
$params['do_simpleping_check'] = true;
$params['auto_extract_name'] = '';
$params['auto_extract_url'] = '';
$params['always_output_comment'] = false;
$params['creation_allowed_check'] = isset($_GET['online']) && $_GET['online'];
$params['tolerant_htmlentities'] = true;
$params['ignore_xhtml_light'] = false;

$nonConfidential = OIDplusObject::getAllNonConfidential();

foreach ($nonConfidential as $id) {
	$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where id = '".OIDplus::db()->real_escape_string($id)."'");
	if ($row = OIDplus::db()->fetch_object($res)) {
		$elements['identifier'] = array();
		$res2 = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."asn1id where oid = '".OIDplus::db()->real_escape_string($row->id)."'");
		while ($row2 = OIDplus::db()->fetch_object($res2)) {
			$elements['identifier'][] = $row2->name; // 'unicode-label' is currently not in the standard format (oid.xsd)
		}

		$elements['unicode-label'] = array();
		$res2 = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."iri where oid = '".OIDplus::db()->real_escape_string($row->id)."'");
		while ($row2 = OIDplus::db()->fetch_object($res2)) {
			$elements['unicode-label'][] = $row2->name;
		}

		$res2 = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = '".OIDplus::db()->real_escape_string($row->ra_email)."'");
		$row2 = OIDplus::db()->fetch_object($res2);

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
		} else {
			$elements['description'] = '<i>No description available</i>';
			$elements['information'] = '';
		}

		if ($elements['information'] != '') {
			$elements['information'] .= '<br/><br/>';
		}

		$elements['information'] .= 'See <a href="'.OIDplus::system_url(false).'?goto='.urlencode($id).'">more information</a>.'; // TODO: system_url() geht nicht bei CLI

		if (explode(':',$id,2)[0] != 'oid') {
			$elements['information'] = "Object: $id\n\n" . $elements['information'];
		}

		$elements['description'] = repair_relative_links($elements['description']);
		$elements['information'] = repair_relative_links($elements['information']);

		$elements['first-registrant']['first-name'] = '';
		$elements['first-registrant']['last-name'] = '';
		$elements['first-registrant']['address'] = '';
		$elements['first-registrant']['email'] = '';
		$elements['first-registrant']['phone'] = '';
		$elements['first-registrant']['fax'] = '';
		$elements['first-registrant']['creation-date'] = _formatdate($row->created);

		$elements['current-registrant']['first-name'] = $row2 ? $row2->ra_name : '';
		$elements['current-registrant']['last-name'] = '';
		$elements['current-registrant']['email'] = $row->ra_email;

		$elements['current-registrant']['phone'] = '';
		$elements['current-registrant']['fax'] = '';
		$elements['current-registrant']['address'] = '';
		if ($row2) {
			$tmp = array();
			if (!empty($row2->organization)  && ($row2->organization  != $row2->ra_name)) $tmp[] = $row2->organization;
			if (!empty($row2->office)        && ($row2->office        != $row2->ra_name)) $tmp[] = $row2->office;
			if (!empty($row2->personal_name) && ($row2->personal_name != $row2->ra_name)) $tmp[] = (!empty($row2->organization) ? 'c/o ' : '') . $row2->personal_name;
			if (!$row2->privacy) {
				if (!empty($row2->street))   $tmp[] = $row2->street;
				if (!empty($row2->zip_town)) $tmp[] = $row2->zip_town;
				if (!empty($row2->country))  $tmp[] = $row2->country;
				$elements['current-registrant']['phone'] = !empty($row2->phone) ? $row2->phone : $row2->mobile;
				$elements['current-registrant']['fax'] = $row2->fax;
			}
			$elements['current-registrant']['address'] = implode("<br/>", $tmp);
		}
		$elements['current-registrant']['modification-date'] = _formatdate($row->updated);


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
		$oid = $obj->getOid();
		if (empty($oid)) continue; // e.g. if no system ID is available, then we cannot submit non-OID objects
		echo $oa->createXMLEntry($oid, $elements, $params, $comment=$obj->nodeId());
	}

	flush();
}

echo $oa->xmlAddFooter();

# ---

function _formatdate($str) {
	$str = explode(' ',$str)[0];
	if ($str == '0000-00-00') $str = '';
	return $str;
}

function repair_relative_links($str) {
	$str = preg_replace_callback('@(href\s*=\s*([\'"]))(.+)(\\2)@ismU', function($treffer) {
		$url = $treffer[3];
		if ((stripos($url,'http') === false) && (stripos($url,'ftp') === false)) {
			if (stripos($url,'www') === 0) {
				$url .= 'http://' . $url;
			} else {
				$url = OIDplus::system_url() . $url;
			}
		}
		return $treffer[1].$url.$treffer[4];
	}, $str);
	return $str;
}

