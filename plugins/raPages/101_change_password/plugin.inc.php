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

class OIDplusPageRaChangePassword extends OIDplusPagePluginRa {

	public static function getPluginInformation() {
		$out = array();
		$out['name'] = 'Change password';
		$out['author'] = 'ViaThinkSoft';
		$out['version'] = null;
		$out['descriptionHTML'] = null;
		return $out;
	}

	public function priority() {
		return 101;
	}

	public function action(&$handled) {
		if (isset($_POST["action"]) && ($_POST["action"] == "change_ra_password")) {
			$handled = true;

			$email = $_POST['email'];

			$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = ?", array($email));
			if ($res->num_rows() == 0) {
				throw new OIDplusException('RA does not exist');
			}

			if (!OIDplus::authUtils()::isRaLoggedIn($email) && !OIDplus::authUtils()::isAdminLoggedIn()) {
				throw new OIDplusException('Authentification error. Please log in as the RA or admin to update its data.');
			}

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				$old_password = $_POST['old_password'];
			}
			$password1 = $_POST['new_password1'];
			$password2 = $_POST['new_password2'];

			if ($password1 !== $password2) {
				throw new OIDplusException('Passwords are not equal');
			}

			if (strlen($password1) < OIDplus::config()->getValue('ra_min_password_length')) {
				throw new OIDplusException('New password is too short. Minimum password length: '.OIDplus::config()->getValue('ra_min_password_length'));
			}

			$ra = new OIDplusRA($email);
			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				if (!$ra->checkPassword($old_password)) {
					throw new OIDplusException('Old password incorrect');
				}
			}
			OIDplus::logger()->log("RA($email)?/A?", "Password of RA '$email' changed");
			$ra->change_password($password1);

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
		if (explode('$',$id)[0] == 'oidplus:change_ra_password') {
			$handled = true;
			$out['title'] = 'Change RA password';
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';

			$ra_email = explode('$',$id)[1];

			$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = ?", array($ra_email));
			if ($res->num_rows() == 0) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = 'RA <b>'.htmlentities($ra_email).'</b> does not exist';
				return $out;
			}

			if (!OIDplus::authUtils()::isRaLoggedIn($ra_email) && !OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>You need to <a '.OIDplus::gui()->link('oidplus:login').'>log in</a> as the requested RA <b>'.htmlentities($ra_email).'</b>.</p>';
			} else {
				$out['text'] .= '<form id="raChangePasswordForm" onsubmit="return raChangePasswordFormOnSubmit();">';
				$out['text'] .= '<input type="hidden" id="email" value="'.htmlentities($ra_email).'"/><br>';
				$out['text'] .= '<div><label class="padding_label">E-Mail:</label><b>'.htmlentities($ra_email).'</b></div>';
				if (OIDplus::authUtils()::isAdminLoggedIn()) {
					$out['text'] .= '<div><label class="padding_label">Old password:</label><i>Admin can change the password without verification of the old password.</i></div>';
				} else {
					$out['text'] .= '<div><label class="padding_label">Old password:</label><input type="password" id="old_password" value=""/></div>';
				}
				$out['text'] .= '<div><label class="padding_label">New password:</label><input type="password" id="new_password1" value=""/></div>';
				$out['text'] .= '<div><label class="padding_label">Repeat:</label><input type="password" id="new_password2" value=""/></div>';
				$out['text'] .= '<br><input type="submit" value="Change password"></form>';
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
			'id' => 'oidplus:change_ra_password$'.$ra_email,
			'icon' => $tree_icon,
			'text' => 'Change password'
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}
}
