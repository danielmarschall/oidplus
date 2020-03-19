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

class OIDplusPageAdminCreateRa extends OIDplusPagePlugin {
	public static function getPluginInformation() {
		$out = array();
		$out['name'] = 'Create RA';
		$out['author'] = 'ViaThinkSoft';
		$out['version'] = null;
		$out['descriptionHTML'] = null;
		return $out;
	}

	public function type() {
		return 'admin';
	}

	public function priority() {
		return 130;
	}

	public function action(&$handled) {
		if (isset($_POST["action"]) && ($_POST["action"] == "create_ra")) {
			$handled = true;

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>You need to <a '.oidplus_link('oidplus:login').'>log in</a> as administrator.</p>';
				return $out;
			}

			$email = $_POST['email'];
			$password1 = $_POST['password1'];
			$password2 = $_POST['password2'];

			if (!oidplus_valid_email($email)) {
				die(json_encode(array("error" => 'eMail address is invalid.')));
			}

			$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = ?", array($email)); // TODO: this should be a static function in the RA class
			if (OIDplus::db()->num_rows($res) > 0) {
				die(json_encode(array("error" => 'RA does already exist')));
			}

			if ($password1 !== $password2) {
				die(json_encode(array("error" => 'Passwords are not equal')));
			}

			if (strlen($password1) < OIDplus::config()->minRaPasswordLength()) {
				die(json_encode(array("error" => 'Password is too short. Minimum password length: '.OIDplus::config()->minRaPasswordLength())));
			}

			OIDplus::logger()->log("RA($email)!/A?", "RA '$email' was created by the admin, without email address verification or invitation");

			$ra = new OIDplusRA($email);
			$ra->register_ra($password1);

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
		if ($id == 'oidplus:create_ra') {
			$handled = true;
			$out['title'] = 'Manual creation of a RA';
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/icon_big.png' : '';

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>You need to <a '.oidplus_link('oidplus:login').'>log in</a> as administrator.</p>';
				return $out;
			}

			$out['text'] .= '<form id="adminCreateRaFrom" onsubmit="return adminCreateRaFormOnSubmit();">';
			$out['text'] .= '<div><label class="padding_label">E-Mail:</label><input type="text" id="email" value=""></div>';
			$out['text'] .= '<div><label class="padding_label">Password:</label><input type="password" id="password1" value=""/></div>';
			$out['text'] .= '<div><label class="padding_label">Repeat:</label><input type="password" id="password2" value=""/></div>';
			$out['text'] .= '<br><input type="submit" value="Create"></form>';
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:create_ra',
			'icon' => $tree_icon,
			'text' => 'Create RA manually'
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}
}
