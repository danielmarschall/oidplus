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

class OIDplusPagePublicRaInfo extends OIDplusPagePlugin {
	public function type() {
		return 'public';
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

			$ra_email = explode('$',$id)[1];
			$ra_email = str_replace('&', '@', $ra_email);

			$out['title'] = 'Registration Authority Information'; // TODO: email addresse reinschreiben? aber wie vor anti spam schützen?
			$out['icon'] = 'plugins/publicPages/'.basename(__DIR__).'/rainfo_big.png';

			if (empty($ra_email)) {
				$out['text'] = '<p>Following object roots have an undefined Registration Authority:</p>';
			} else {
				$out['text'] = $this->showRAInfo($ra_email);
			}

			$out['text'] .= '<br><br>';

			foreach (OIDplusObject::getRaRoots($ra_email) as $loc_root) {
				$ico = $loc_root->getIcon();
				$icon = !is_null($ico) ? $ico : 'plugins/publicPages/'.basename(__DIR__).'/treeicon_link.png';
				$out['text'] .= '<p><a '.oidplus_link($loc_root->nodeId()).'><img src="'.$icon.'"> Jump to RA root '.$loc_root->objectTypeTitleShort().' '.$loc_root->crudShowId(OIDplusObject::parse($loc_root::root())).'</a></p>';
			}

			if (OIDplus::authUtils()::isRALoggedIn($ra_email)) {
				$out['text'] .= '<br><p><a '.oidplus_link('oidplus:edit_ra$'.$ra_email).'>Edit contact info</a></p>';
			}

			if (!empty($ra_email) && OIDplus::authUtils()::isAdminLoggedIn()) {
				if (class_exists("OIDplusPageAdminListRAs")) {
					$out['text'] .= '<br><p><a href="#" onclick="return deleteRa('.js_escape($ra_email).','.js_escape('oidplus:list_ra').')">Delete this RA</a></p>';
				} else {
					$out['text'] .= '<br><p><a href="#" onclick="return deleteRa('.js_escape($ra_email).','.js_escape('oidplus:system').')">Delete this RA</a></p>';
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

		$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = '".OIDplus::db()->real_escape_string($email)."'");
		if (OIDplus::db()->num_rows($res) === 0) {
			$out = '<p>The RA <a href="mailto:'.htmlentities($email).'">'.htmlentities($email).'</a> is not registered in the database.</p>';

		} else {
			$row = OIDplus::db()->fetch_array($res);
			$out = '<b>'.htmlentities($row['ra_name']).'</b><br>';
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
}

OIDplus::registerPagePlugin(new OIDplusPagePublicRaInfo());
