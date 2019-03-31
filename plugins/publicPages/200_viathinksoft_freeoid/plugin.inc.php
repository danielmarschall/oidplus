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

class OIDplusPagePublicFreeOID extends OIDplusPagePlugin {
	public function type() {
		return 'public';
	}

	public function priority() {
		return 200;
	}

	private static function getFreeRootOid() {
		return OIDplusOID::parse('oid:'.OIDplus::config()->getValue('freeoid_root_oid'));
	}

	public function action(&$handled) {
		if ($_POST["action"] == "com.viathinksoft.freeoid.request_freeoid") {
			$handled = true;
			$email = $_POST['email'];

			$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = '".OIDplus::db()->real_escape_string($email)."'");
			if (OIDplus::db()->num_rows($res) > 0) {
				die('This email address already exists.'); // TODO: actually, the person might have something else (like a DOI) and want to have a FreeOID
			}

			if (!oiddb_valid_email($email)) {
				die('Invalid email address');
			}

			if (RECAPTCHA_ENABLED) {
				$secret=RECAPTCHA_PRIVATE;
				$response=$_POST["captcha"];
				$verify=file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret}&response={$response}");
				$captcha_success=json_decode($verify);
				if ($captcha_success->success==false) {
					die('Captcha wrong');
				}
			}

			$timestamp = time();
			$activate_url = OIDplus::system_url() . '?goto='.urlencode('oidplus:com.viathinksoft.freeoid.activate_freeoid$'.$email.'$'.$timestamp.'$'.OIDplus::authUtils()::makeAuthKey('com.viathinksoft.freeoid.activate_freeoid;'.$email.';'.$timestamp));

			$message = file_get_contents(__DIR__ . '/request_msg.tpl');
			$message = str_replace('{{SYSTEM_URL}}', OIDplus::system_url(), $message);
			$message = str_replace('{{SYSTEM_TITLE}}', OIDplus::config()->systemTitle(), $message);
			$message = str_replace('{{ADMIN_EMAIL}}', OIDPLUS_ADMIN_EMAIL, $message);
			$message = str_replace('{{ACTIVATE_URL}}', $activate_url, $message);
			my_mail($email, OIDplus::config()->systemTitle().' - Free OID request', $message, 'daniel-marschall@viathinksoft.de');

			die("OK");
		}

		if ($_POST["action"] == "com.viathinksoft.freeoid.activate_freeoid") {
			$handled = true;

			$password1 = $_POST['password1'];
			$password2 = $_POST['password2'];
			$email = $_POST['email'];

			$ra_name = $_POST['ra_name'];
			$url = $_POST['url'];
			$title = $_POST['title'];

			$auth = $_POST['auth'];
			$timestamp = $_POST['timestamp'];

			if (!OIDplus::authUtils()::validateAuthKey('com.viathinksoft.freeoid.activate_freeoid;'.$email.';'.$timestamp, $auth)) {
				die('Invalid auth key');
			}

			if ((OIDplus::config()->maxInviteTime() > 0) && (time()-$timestamp > OIDplus::config()->maxInviteTime())) {
				die('Invitation expired!');
			}

			if ($password1 !== $password2) {
				die('Passwords are not equal');
			}

			if (strlen($password1) < OIDplus::config()->minRaPasswordLength()) {
				die('Password is too short. Minimum password length: '.OIDplus::config()->minRaPasswordLength());
			}

			if (empty($ra_name)) {
				die('Please enter your personal name or the name of your group.');
			}

			// 1. step: Add the RA to the database

			$ra = new OIDplusRA($email);
			$ra->register_ra($password1);
			$ra->setRaName($ra_name);

			// 2. step: Add the new OID to the database

			$new_oid = OIDplus::config()->getValue('freeoid_root_oid').'.'.($this->freeoid_max_id()+1);

			if ((!empty($url)) && (substr($url, 0, 4) != 'http')) $url = 'http://'.$url;

			$description = '<p>'.htmlentities($ra_name).'</p>';
			if (!empty($url)) {
				$description .= '<p>More information at <a href="'.htmlentities($url).'">'.htmlentities($url).'</a></p>';
			}

			if (empty($title)) $title = $ra_name;

			if (!OIDplus::db()->query("insert into ".OIDPLUS_TABLENAME_PREFIX."objects (id, ra_email, parent, title, description, confidential, created) values ('".OIDplus::db()->real_escape_string('oid:'.$new_oid)."', '".OIDplus::db()->real_escape_string($email)."', '".OIDplus::db()->real_escape_string('oid:'.OIDplus::config()->getValue('freeoid_root_oid'))."', '".OIDplus::db()->real_escape_string($title)."', '".OIDplus::db()->real_escape_string($description)."', 0, now())")) {
				$ra->delete();
				die(OIDplus::db()->error());
			}

			// Send delegation report email to admin

			$message  = "OID delegation report\n";
			$message .= "\n";
			$message .= "OID: ".$new_oid."\n";;
			$message .= "\n";
			$message .= "RA Name: $ra_name\n";
			$message .= "RA eMail: $email\n";
			$message .= "URL for more information: $url\n";
			$message .= "OID Name: $title\n";
			$message .= "\n";
			$message .= "More details: ".OIDplus::system_url()."?goto=oid:$new_oid\n";

			my_mail($email, OIDplus::config()->systemTitle()." - OID $new_oid registered", $message, OIDplus::config()->globalCC(), 'admin@oid-info.com');

			// Send delegation information to user

			$message = file_get_contents(__DIR__ . '/allocated_msg.tpl');
			$message = str_replace('{{SYSTEM_URL}}', OIDplus::system_url(), $message);
			$message = str_replace('{{SYSTEM_TITLE}}', OIDplus::config()->systemTitle(), $message);
			$message = str_replace('{{ADMIN_EMAIL}}', OIDPLUS_ADMIN_EMAIL, $message);
			$message = str_replace('{{NEW_OID}}', $new_oid, $message);
			my_mail($email, OIDplus::config()->systemTitle().' - Free OID allocated', $message, 'daniel-marschall@viathinksoft.de');

			die('OK');
		}
	}

	public function cfgLoadConfig() {
		OIDplus::db()->query("insert into ".OIDPLUS_TABLENAME_PREFIX."config (name, description, value, protected, visible) values ('freeoid_root_oid', 'Root-OID of free OID service', '', 0, 1)");
	}

	public function cfgSetValue($name, $value) {
		if ($name == 'freeoid_root_oid') {
			if (($value != '') && !oid_valid_dotnotation($value,false,false,1)) {
				throw new Exception("Please enter a valid OID in dot notation or nothing");
			}
		}
	}

	public function gui($id, &$out, &$handled) {
		if (explode('$',$id)[0] == 'oidplus:com.viathinksoft.freeoid') {
			$handled = true;

			$out['title'] = 'Register a free OID';
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/publicPages/'.basename(__DIR__).'/icon_big.png' : '';

			$highest_id = $this->freeoid_max_id();

			$out['text'] .= '<p>Currently <a href="?goto=oid:'.OIDplus::config()->getValue('freeoid_root_oid').'">'.$highest_id.' free OIDs have been</a> registered. Please enter your email below to receive a free OID.</p>';

			try {
				$out['text'] .= '
				  <form id="freeOIDForm" onsubmit="return freeOIDFormOnSubmit();">
				    E-Mail: <input type="text" id="email" value=""/><br><br>'.
				 (RECAPTCHA_ENABLED ? '<script> grecaptcha.render(document.getElementById("g-recaptcha"), { "sitekey" : "'.RECAPTCHA_PUBLIC.'" }); </script>'.
				                   '<div id="g-recaptcha" class="g-recaptcha" data-sitekey="'.RECAPTCHA_PUBLIC.'"></div>' : '').
				' <br>
				    <input type="submit" value="Request free OID">
				  </form>';

				$tos = file_get_contents(__DIR__ . '/tos.html');
				$tos = str_replace('{{ADMIN_EMAIL}}', OIDPLUS_ADMIN_EMAIL, $tos);
				$tos = str_replace('{{ROOT_OID}}', OIDplus::config()->getValue('freeoid_root_oid'), $tos);
				$tos = str_replace('{{ROOT_OID_ASN1}}', self::getFreeRootOid()->getAsn1Notation(), $tos);
				$tos = str_replace('{{ROOT_OID_IRI}}', self::getFreeRootOid()->getIriNotation(), $tos);
				$out['text'] .= $tos;
			} catch (Exception $e) {
				$out['text'] = "Error: ".$e->getMessage();
			}
		} else if (explode('$',$id)[0] == 'oidplus:com.viathinksoft.freeoid.activate_freeoid') {
			$handled = true;

			$email = explode('$',$id)[1];
			$timestamp = explode('$',$id)[2];
			$auth = explode('$',$id)[3];

			$out['title'] = 'Activate Free OID';
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/publicPages/'.basename(__DIR__).'/icon_big.png' : '';

			$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = '".OIDplus::db()->real_escape_string($email)."'");
			if (OIDplus::db()->num_rows($res) > 0) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = 'This RA is already registered.'; // TODO: actually, the person might have something else (like a DOI) and want to have a FreeOID
			} else {
				if (!OIDplus::authUtils()::validateAuthKey('com.viathinksoft.freeoid.activate_freeoid;'.$email.';'.$timestamp, $auth)) {
					$out['icon'] = 'img/error_big.png';
					$out['text'] = 'Invalid authorization. Is the URL OK?';
				} else {
					$out['text'] = '<p>eMail-Address: <b>'.$email.'</b></p>

				  <form id="activateFreeOIDForm" onsubmit="return activateFreeOIDFormOnSubmit();">
				    <input type="hidden" id="email" value="'.htmlentities($email).'"/>
				    <input type="hidden" id="timestamp" value="'.htmlentities($timestamp).'"/>
				    <input type="hidden" id="auth" value="'.htmlentities($auth).'"/>

				    Your personal name or the name of your group:<br><input type="text" id="ra_name" value=""/><br><br><!-- TODO: disable autocomplete -->
				    Title of your OID (usually equal to your name, optional):<br><input type="text" id="title" value=""/><br><br>
				    URL for more information about your project(s) (optional):<br><input type="text" id="url" value=""/><br><br>

				    <label class="padding_label">Password:</label><input type="password" id="password1" value=""/><br><br>
				    <label class="padding_label">Again:</label><input type="password" id="password2" value=""/><br><br>
				    <input type="submit" value="Register">
				  </form>';
				}
			}
		}
	}

	public function tree(&$json, $ra_email=null) {
		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = 'plugins/publicPages/'.basename(__DIR__).'/treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:com.viathinksoft.freeoid',
			'icon' => $tree_icon,
			'text' => 'Register a free OID'
		);
	}

	# ---

	protected function freeoid_max_id() {
		$res = OIDplus::db()->query("select id from ".OIDPLUS_TABLENAME_PREFIX."objects where id like '".OIDplus::db()->real_escape_string('oid:'.OIDplus::config()->getValue('freeoid_root_oid').'.%')."' order by ".OIDplus::db()->natOrder('id'));
		$highest_id = 0;
		while ($row = OIDplus::db()->fetch_array($res)) {
			$arc = substr_count(OIDplus::config()->getValue('freeoid_root_oid'), '.')+1;
			$highest_id = explode('.',$row['id'])[$arc];
		}
		return $highest_id;
	}
}

if (OIDplus::config()->getValue('freeoid_root_oid') != '') {
	OIDplus::registerPagePlugin(new OIDplusPagePublicFreeOID());
}
