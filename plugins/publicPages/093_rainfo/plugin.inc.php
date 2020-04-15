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

class OIDplusPagePublicRaInfo extends OIDplusPagePluginPublic {

	public static function getPluginInformation() {
		$out = array();
		$out['name'] = 'RA-Info';
		$out['author'] = 'ViaThinkSoft';
		$out['version'] = null;
		$out['descriptionHTML'] = null;
		return $out;
	}

	public function priority() {
		return 93;
	}

	public function action(&$handled) {
	}

	public function init($html=true) {
	}

	public function cfgSetValue($name, $value) {
	}

	public function gui($id, &$out, &$handled) {
		if (explode('$',$id)[0] == 'oidplus:rainfo') {
			$handled = true;

			$antispam_email = explode('$',$id.'$')[1];
			$ra_email = str_replace('&', '@', $antispam_email);

			$out['icon'] = OIDplus::webpath(__DIR__).'rainfo_big.png';

			if (empty($ra_email)) {
				$out['title'] = 'Object roots without RA';
				$out['text'] = '<p>Following object roots have an undefined Registration Authority:</p>';
			} else {
				$res = OIDplus::db()->query("select ra_name from ".OIDPLUS_TABLENAME_PREFIX."ra where email = ?", array($ra_email));
				$out['title'] = '';
				if ($row = $res->fetch_array()) {
					$out['title'] = $row['ra_name'];
				}
				if (empty($out['title'])) {
					$out['title'] = $antispam_email;
				}
				$out['text'] = $this->showRAInfo($ra_email);
			}

			$out['text'] .= '<br><br>';

			foreach (OIDplusObject::getRaRoots($ra_email) as $loc_root) {
				$ico = $loc_root->getIcon();
				$icon = !is_null($ico) ? $ico : OIDplus::webpath(__DIR__).'treeicon_link.png';
				$out['text'] .= '<p><a '.OIDplus::gui()->link($loc_root->nodeId()).'><img src="'.$icon.'"> Jump to RA root '.$loc_root->objectTypeTitleShort().' '.$loc_root->crudShowId(OIDplusObject::parse($loc_root::root())).'</a></p>';
			}

			if (!empty($ra_email)) {
				$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = ?", array($ra_email));
				if ($res->num_rows() > 0) {
					if (OIDplus::authUtils()::isRALoggedIn($ra_email) || OIDplus::authUtils()::isAdminLoggedIn()) {
						if (class_exists('OIDplusPageRaEditContactData')) {
							$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:edit_ra$'.$ra_email).'>Edit contact data</a></p>';
						}
					}

					if (OIDplus::authUtils()::isAdminLoggedIn()) {
						if (class_exists("OIDplusPageAdminListRAs")) {
							$out['text'] .= '<p><a href="#" onclick="return deleteRa('.js_escape($ra_email).','.js_escape('oidplus:list_ra').')">Delete this RA</a></p>';
						} else {
							$out['text'] .= '<p><a href="#" onclick="return deleteRa('.js_escape($ra_email).','.js_escape('oidplus:system').')">Delete this RA</a></p>';
						}

						if (class_exists('OIDplusPageRaChangePassword')) {
							$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:change_ra_password$'.$ra_email).'>Change password of this RA</a>';
						}
					}
				}

				if (OIDplus::authUtils()::isRALoggedIn($ra_email) || OIDplus::authUtils()::isAdminLoggedIn()) {
					$res = OIDplus::db()->query("select lo.unix_ts, lo.addr, lo.event from ".OIDPLUS_TABLENAME_PREFIX."log lo ".
					                            "left join ".OIDPLUS_TABLENAME_PREFIX."log_user lu on lu.log_id = lo.id ".
					                            "where lu.username = ? " .
					                            "order by lo.unix_ts desc", array($ra_email));
					$out['text'] .= '<h2>Log messages for RA '.htmlentities($ra_email).'</h2>';
					if ($res->num_rows() > 0) {
						$out['text'] .= '<pre>';
						while ($row = $res->fetch_array()) {
							$addr = empty($row['addr']) ? 'no address' : $row['addr'];

							$out['text'] .= date('Y-m-d H:i:s', $row['unix_ts']) . ': ' . htmlentities($row["event"])." (" . htmlentities($addr) . ")\n";
						}
						$out['text'] .= '</pre>';

						// TODO: List logs in a table instead of a <pre> text
						// TODO: Load only X events and then re-load new events via AJAX when the user scrolls down
					} else {
						$out['text'] .= '<p>Currently there are no log entries</p>';
					}
				}
			}
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		return false;
	}

	public static function showRAInfo($email) {
		$out = '';

		if (empty($email)) {
			$out = '<p>The superior RA did not define a RA for this OID.</p>';
			return $out;
		}

		$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = ?", array($email));
		if ($res->num_rows() === 0) {
			$out = '<p>The RA <a href="mailto:'.htmlentities($email).'">'.htmlentities($email).'</a> is not registered in the database.</p>';
		} else {
			$row = $res->fetch_array();
			$out = '<b>'.htmlentities($row['ra_name']).'</b><br>'; // TODO: if you are not already at the page "oidplus:rainfo", then link to it now
			$out .= 'E-Mail: <a href="mailto:'.htmlentities($email).'">'.htmlentities($email).'</a><br>';
			if (trim($row['personal_name']) !== '') $out .= htmlentities($row['personal_name']).'<br>';
			if (trim($row['organization']) !== '') $out .= htmlentities($row['organization']).'<br>';
			if (trim($row['office']) !== '') $out .= htmlentities($row['office']).'<br>';
			if ($row['privacy']) {
				// TODO: meldung nur anzeigen, wenn benutzer überhaupt straße, adresse etc hat
				// TODO: aber der admin soll es sehen, und der user selbst (mit anmerkung, dass es privat ist)
				$out .= '<p>The RA does not want to publish their personal information.</p>';
			} else {
				if (trim($row['street']) !== '') $out .= htmlentities($row['street']).'<br>';
				if (trim($row['zip_town']) !== '') $out .= htmlentities($row['zip_town']).'<br>';
				if (trim($row['country']) !== '') $out .= htmlentities($row['country']).'<br>';
				$out .= '<br>';
				if (trim($row['phone']) !== '') $out .= htmlentities($row['phone']).'<br>';
				if (trim($row['fax']) !== '') $out .= htmlentities($row['fax']).'<br>';
				if (trim($row['mobile']) !== '') $out .= htmlentities($row['mobile']).'<br>';
				$out .= '<br>';
			}
		}

		return trim_br($out);
	}

	public function tree_search($request) {
		return false;
	}
}
