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

class OIDplusPageRaEditContactData extends OIDplusPagePluginRa {

	public static function getPluginInformation() {
		$out = array();
		$out['name'] = 'Edit contact data';
		$out['author'] = 'ViaThinkSoft';
		$out['version'] = null;
		$out['descriptionHTML'] = null;
		return $out;
	}

	public function priority() {
		return 100;
	}

	public function action(&$handled) {
		if (isset($_POST["action"]) && ($_POST["action"] == "change_ra_data")) {
			$handled = true;

			$email = $_POST['email'];

			if (!OIDplus::authUtils()::isRaLoggedIn($email) && !OIDplus::authUtils()::isAdminLoggedIn()) {
				throw new OIDplusException('Authentification error. Please log in as the RA to update its data.');
			}

			$res = OIDplus::db()->query("select * from ###ra where email = ?", array($email));
			if ($res->num_rows() == 0) {
				throw new OIDplusException('RA does not exist');
			}

			OIDplus::logger()->log("RA($email)?/A?", "Changed RA '$email' contact data/details");

			OIDplus::db()->query("UPDATE ###ra ".
				"SET ".
				"updated = ".(OIDplus::db()->slang() == 'mssql' ? "getdate()" : "now()").", ".
				"ra_name = ?, ".
				"organization = ?, ".
				"office = ?, ".
				"personal_name = ?, ".
				"privacy = ?, ".
				"street = ?, ".
				"zip_town = ?, ".
				"country = ?, ".
				"phone = ?, ".
				"mobile = ?, ".
				"fax = ? ".
				"WHERE email = ?",
				array(
					$_POST['ra_name'],
					$_POST['organization'],
					$_POST['office'],
					$_POST['personal_name'],
					$_POST['privacy'],
					$_POST['street'],
					$_POST['zip_town'],
					$_POST['country'],
					$_POST['phone'],
					$_POST['mobile'],
					$_POST['fax'],
					$email
				)
			);

			echo json_encode(array("status" => 0));
		}
	}

	public function init($html=true) {
		// Nothing
	}

	public function cfgSetValue($name, $value) {
		// Nothing
	}

	public function gui($id, &$out, &$handled) {
		if (explode('$',$id)[0] == 'oidplus:edit_ra') {
			$handled = true;
			$out['title'] = 'Edit RA contact data';
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';

			$ra_email = explode('$',$id)[1];

			if (!OIDplus::authUtils()::isRaLoggedIn($ra_email) && !OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>You need to <a '.OIDplus::gui()->link('oidplus:login').'>log in</a> as the requested RA <b>'.htmlentities($ra_email).'</b>.</p>';
			} else {
				$out['text'] = '<p>Your email address: <b>'.htmlentities($ra_email).'</b>';

				$res = OIDplus::db()->query("select * from ###ra where email = ?", array($ra_email));
				if ($res->num_rows() == 0) {
					$out['icon'] = 'img/error_big.png';
					$out['text'] = 'RA <b>'.htmlentities($ra_email).'</b> does not exist';
					return $out;
				}
				$row = $res->fetch_array();

				if (class_exists('OIDplusPageRaChangeEMail') && OIDplus::config()->getValue('allow_ra_email_change')) {
					$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:change_ra_email$'.$ra_email).'>Change email address</a></p>';
				} else {
					$out['text'] .= '<p><abbr title="To change the email address, you need to contact the admin or superior RA. They will need to change the email address and invite you (with your new email address) again.">How to change the email address?</abbr></p>';
				}

				// ---

				$out['text'] .= '<p>Change basic information (public):</p>
				  <form id="raChangeContactDataForm" onsubmit="return raChangeContactDataFormOnSubmit();">
				    <input type="hidden" id="email" value="'.htmlentities($ra_email).'"/>
				    <div><label class="padding_label">RA Name:</label><input type="text" id="ra_name" value="'.htmlentities($row['ra_name']).'"/></div>
				    <div><label class="padding_label">Organization:</label><input type="text" id="organization" value="'.htmlentities($row['organization']).'"/></div>
				    <div><label class="padding_label">Office:</label><input type="text" id="office" value="'.htmlentities($row['office']).'"/></div>
				    <div><label class="padding_label">Person name:</label><input type="text" id="personal_name" value="'.htmlentities($row['personal_name']).'"/></div>
				    <br>
				    <div><label class="padding_label">Privacy</label><input type="checkbox" id="privacy" value="" '.($row['privacy'] == 1 ? ' checked' : '').'/> <label for="privacy">Hide postal address and Phone/Fax/Mobile Numbers</label></div>
				    <div><label class="padding_label">Street:</label><input type="text" id="street" value="'.htmlentities($row['street']).'"/></div>
				    <div><label class="padding_label">ZIP/Town:</label><input type="text" id="zip_town" value="'.htmlentities($row['zip_town']).'"/></div>
				    <div><label class="padding_label">Country:</label><input type="text" id="country" value="'.htmlentities($row['country']).'"/></div>
				    <div><label class="padding_label">Phone:</label><input type="text" id="phone" value="'.htmlentities($row['phone']).'"/></div>
				    <div><label class="padding_label">Mobile:</label><input type="text" id="mobile" value="'.htmlentities($row['mobile']).'"/></div>
				    <div><label class="padding_label">Fax:</label><input type="text" id="fax" value="'.htmlentities($row['fax']).'"/></div>
				    <br><input type="submit" value="Change data">
				  </form><br><br>';

				$out['text'] .= '<p><a href="#" onclick="return deleteRa('.js_escape($ra_email).',\'oidplus:system\')">Delete profile</a> (objects stay active)</p>';
			}
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = OIDplus::webpath(__DIR__).'treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:edit_ra$'.$ra_email,
			'icon' => $tree_icon,
			'text' => 'Edit RA contact data'
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}
}
