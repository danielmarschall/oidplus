<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2021 Daniel Marschall, ViaThinkSoft
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

namespace ViaThinkSoft\OIDplus;

class OIDplusPageRaEditContactData extends OIDplusPagePluginRa {

	public function action($actionID, $params) {
		if ($actionID == 'change_ra_data') {
			_CheckParamExists($params, 'email');

			$email = $params['email'];

			if (!OIDplus::authUtils()->isRaLoggedIn($email) && !OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusException(_L('Authentication error. Please log in as admin, or as the RA to update its data.'));
			}

			$res = OIDplus::db()->query("select * from ###ra where email = ?", array($email));
			if (!$res->any()) {
				throw new OIDplusException(_L('RA does not exist'));
			}

			OIDplus::logger()->log("[?WARN/!OK]RA($email)?/[?INFO/!OK]A?", "Changed RA '$email' contact data/details");

			if (isset($params['ra_name']))
				OIDplus::db()->query("UPDATE ###ra SET ra_name = ? WHERE email = ?", array($params['ra_name'], $email));
			if (isset($params['organization']))
				OIDplus::db()->query("UPDATE ###ra SET organization = ? WHERE email = ?", array($params['organization'], $email));
			if (isset($params['office']))
				OIDplus::db()->query("UPDATE ###ra SET office = ? WHERE email = ?", array($params['office'], $email));
			if (isset($params['personal_name']))
				OIDplus::db()->query("UPDATE ###ra SET personal_name = ? WHERE email = ?", array($params['personal_name'], $email));
			if (isset($params['privacy']))
				OIDplus::db()->query("UPDATE ###ra SET privacy = ? WHERE email = ?", array($params['privacy'], $email));
			if (isset($params['street']))
				OIDplus::db()->query("UPDATE ###ra SET street = ? WHERE email = ?", array($params['street'], $email));
			if (isset($params['zip_town']))
				OIDplus::db()->query("UPDATE ###ra SET zip_town = ? WHERE email = ?", array($params['zip_town'], $email));
			if (isset($params['country']))
				OIDplus::db()->query("UPDATE ###ra SET country = ? WHERE email = ?", array($params['country'], $email));
			if (isset($params['phone']))
				OIDplus::db()->query("UPDATE ###ra SET phone = ? WHERE email = ?", array($params['phone'], $email));
			if (isset($params['mobile']))
				OIDplus::db()->query("UPDATE ###ra SET mobile = ? WHERE email = ?", array($params['mobile'], $email));
			if (isset($params['fax']))
				OIDplus::db()->query("UPDATE ###ra SET fax = ? WHERE email = ?", array($params['fax'], $email));

			OIDplus::db()->query("UPDATE ###ra SET updated = ".OIDplus::db()->sqlDate()." WHERE email = ?", array($email));

			return array("status" => 0);
		} else {
			throw new OIDplusException(_L('Unknown action ID'));
		}
	}

	public function init($html=true) {
		// Nothing
	}

	public function gui($id, &$out, &$handled) {
		if (explode('$',$id)[0] == 'oidplus:edit_ra') {
			$handled = true;

			$ra_email = explode('$',$id)[1];

			$out['title'] = _L('Edit RA contact data');
			$out['icon'] = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

			if (!OIDplus::authUtils()->isRaLoggedIn($ra_email) && !OIDplus::authUtils()->isAdminLoggedIn()) {
				$out['icon'] = 'img/error.png';
				$out['text'] = '<p>'._L('You need to <a %1>log in</a> as the requested RA %2.',OIDplus::gui()->link('oidplus:login$ra$'.$ra_email),'<b>'.htmlentities($ra_email).'</b>').'</p>';
				return;
			}

			$out['text'] = '<p>'._L('Your email address: %1','<b>'.htmlentities($ra_email).'</b>').'</p>';

			$res = OIDplus::db()->query("select * from ###ra where email = ?", array($ra_email));
			if (!$res->any()) {
				$out['icon'] = 'img/error.png';
				$out['text'] = _L('RA "%1" does not exist','<b>'.htmlentities($ra_email).'</b>');
				return;
			}
			$row = $res->fetch_array();

			$changeEMailPlugin = OIDplus::getPluginByOid('1.3.6.1.4.1.37476.2.5.2.4.2.102'); // OIDplusPageRaChangeEMail
			if (!is_null($changeEMailPlugin) && OIDplus::config()->getValue('allow_ra_email_change')) {
				$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:change_ra_email$'.$ra_email).'>'._L('Change email address').'</a></p>';
			} else {
				$out['text'] .= '<p><abbr title="'._L('To change the email address, you need to contact the admin or superior RA. They will need to change the email address and invite you (with your new email address) again.').'">'._L('How to change the email address?').'</abbr></p>';
			}

			// ---

			$out['text'] .= '<p>'._L('Change basic information (public)').':</p>
			  <form id="raChangeContactDataForm" action="javascript:void(0);" onsubmit="return OIDplusPageRaEditContactData.raChangeContactDataFormOnSubmit();">
			    <input type="hidden" id="email" value="'.htmlentities($ra_email).'"/>
			    <div><label class="padding_label">'._L('RA Name').':</label><input type="text" id="ra_name" value="'.htmlentities($row['ra_name']).'"/></div>
			    <div><label class="padding_label">'._L('Organization').':</label><input type="text" id="organization" value="'.htmlentities($row['organization']).'"/></div>
			    <div><label class="padding_label">'._L('Office').':</label><input type="text" id="office" value="'.htmlentities($row['office']).'"/></div>
			    <div><label class="padding_label">'._L('Person name').':</label><input type="text" id="personal_name" value="'.htmlentities($row['personal_name']).'"/></div>
			    <br>
			    <div><label class="padding_label">'._L('Privacy').'</label><input type="checkbox" id="privacy" value="" '.($row['privacy'] == 1 ? ' checked' : '').'/> <label for="privacy">'._L('Hide postal address and Phone/Fax/Mobile Numbers').'</label></div>
			    <div><label class="padding_label">'._L('Street').':</label><input type="text" id="street" value="'.htmlentities($row['street']).'"/></div>
			    <div><label class="padding_label">'._L('ZIP/Town').':</label><input type="text" id="zip_town" value="'.htmlentities($row['zip_town']).'"/></div>
			    <div><label class="padding_label">'._L('Country').':</label><input type="text" id="country" value="'.htmlentities($row['country']).'"/></div>
			    <div><label class="padding_label">'._L('Phone').':</label><input type="text" id="phone" value="'.htmlentities($row['phone']).'"/></div>
			    <div><label class="padding_label">'._L('Mobile').':</label><input type="text" id="mobile" value="'.htmlentities($row['mobile']).'"/></div>
			    <div><label class="padding_label">'._L('Fax').':</label><input type="text" id="fax" value="'.htmlentities($row['fax']).'"/></div>
			    <br><input type="submit" value="'._L('Change data').'">
			  </form><br><br>';

			$raBasePlugin = OIDplus::getPluginByOid('1.3.6.1.4.1.37476.2.5.2.4.1.1'); // OIDplusPagePublicRaBaseUtils
			if (!is_null($raBasePlugin)) {
				$out['text'] .= '<p><a href="#" onclick="return OIDplusPagePublicRaBaseUtils.deleteRa('.js_escape($ra_email).',\'oidplus:system\')">'._L('Delete profile').'</a> '._L('(objects stay active)').'</p>';
			}
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (!$ra_email) return false;
		if (!OIDplus::authUtils()->isRaLoggedIn($ra_email) && !OIDplus::authUtils()->isAdminLoggedIn()) return false;

		if (file_exists(__DIR__.'/img/main_icon16.png')) {
			$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:edit_ra$'.$ra_email,
			'icon' => $tree_icon,
			'text' => _L('Edit RA contact data')
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}
}
