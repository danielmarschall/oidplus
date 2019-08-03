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

class OIDplusPagePublicForgotPassword extends OIDplusPagePlugin {
	public function type() {
		return 'public';
	}

	public function priority() {
		return 91;
	}

	public function action(&$handled) {
		if (isset($_POST["action"]) && ($_POST["action"] == "forgot_password")) {
			$handled = true;

			$email = $_POST['email'];

			if (!oidplus_valid_email($email)) {
				die(json_encode(array("error" => 'Invalid email address')));
			}

			if (RECAPTCHA_ENABLED) {
				$secret=RECAPTCHA_PRIVATE;
				$response=$_POST["captcha"];
				$verify=file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret}&response={$response}");
				$captcha_success=json_decode($verify);
				if ($captcha_success->success==false) {
					die(json_encode(array("error" => 'Captcha wrong')));
				}
			}

			OIDplus::logger()->log("RA($email)!", "A new password for '$email' was requested (forgot password)");

			$timestamp = time();
			$activate_url = OIDplus::system_url() . '?goto='.urlencode('oidplus:reset_password$'.$email.'$'.$timestamp.'$'.OIDplus::authUtils()::makeAuthKey('reset_password;'.$email.';'.$timestamp));

			$message = $this->getForgotPasswordText($_POST['email']);
			$message = str_replace('{{ACTIVATE_URL}}', $activate_url, $message);

			my_mail($email, OIDplus::config()->systemTitle().' - Password reset request', $message, OIDplus::config()->globalCC());

			echo json_encode(array("status" => 0));
		}

		if (isset($_POST["action"]) && ($_POST["action"] == "reset_password")) {
			$handled = true;

			$password1 = $_POST['password1'];
			$password2 = $_POST['password2'];
			$email = $_POST['email'];
			$auth = $_POST['auth'];
			$timestamp = $_POST['timestamp'];

			if (!OIDplus::authUtils()::validateAuthKey('reset_password;'.$email.';'.$timestamp, $auth)) {
				die(json_encode(array("error" => 'Invalid auth key')));
			}

			if ((OIDplus::config()->getValue('max_ra_pwd_reset_time') > 0) && (time()-$timestamp > OIDplus::config()->getValue('max_ra_pwd_reset_time'))) {
				die(json_encode(array("error" => 'Invitation expired!')));
			}

			if ($password1 !== $password2) {
				die(json_encode(array("error" => 'Passwords are not equal')));
			}

			if (strlen($password1) < OIDplus::config()->minRaPasswordLength()) {
				die(json_encode(array("error" => 'Password is too short. Minimum password length: '.OIDplus::config()->minRaPasswordLength())));
			}

			OIDplus::logger()->log("RA($email)!", "RA '$email' has reset his password (forgot passwort)");

			$ra = new OIDplusRA($email);
			$ra->change_password($password1);

			echo json_encode(array("status" => 0));
		}
	}

	public function init($html=true) {
		OIDplus::config()->prepareConfigKey('max_ra_pwd_reset_time', 'Max RA password reset time in seconds (0 = infinite)', '0', 0, 1);
	}

	public function cfgSetValue($name, $value) {
		if ($name == 'max_ra_pwd_reset_time') {
			if (!is_numeric($value) || ($value < 0)) {
				throw new Exception("Please enter a valid value.");
			}
		}
	}

	public function gui($id, &$out, &$handled) {
		if (explode('$',$id)[0] == 'oidplus:forgot_password') {
			$handled = true;

			$out['title'] = 'Forgot password';
			$out['icon'] = 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/forgot_password_big.png';

			try {
				$out['text'] .= '<p>Please enter the email address of your account, and information about the password reset will be sent to you.</p>
				  <form id="forgotPasswordForm" onsubmit="return forgotPasswordFormOnSubmit();">
				    E-Mail: <input type="text" id="email" value=""/><br><br>'.
				 (RECAPTCHA_ENABLED ? '<script> grecaptcha.render(document.getElementById("g-recaptcha"), { "sitekey" : "'.RECAPTCHA_PUBLIC.'" }); </script>'.
				                   '<div id="g-recaptcha" class="g-recaptcha" data-sitekey="'.RECAPTCHA_PUBLIC.'"></div>' : '').
				' <br>
				    <input type="submit" value="Send recovery information">
				  </form>';

			} catch (Exception $e) {

				$out['icon'] = 'img/error_big.png';
				$out['text'] = "Error: ".$e->getMessage();

			}
		} else if (explode('$',$id)[0] == 'oidplus:reset_password') {
			$handled = true;

			$email = explode('$',$id)[1];
			$timestamp = explode('$',$id)[2];
			$auth = explode('$',$id)[3];

			$out['title'] = 'Reset password';
			$out['icon'] = 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/reset_password_big.png';

			if (!OIDplus::authUtils()::validateAuthKey('reset_password;'.$email.';'.$timestamp, $auth)) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = 'Invalid authorization. Is the URL OK?';
			} else {
				$out['text'] = '<p>E-Mail-Adress: <b>'.$email.'</b></p>

				  <form id="resetPasswordForm" onsubmit="return resetPasswordFormOnSubmit();">
				    <input type="hidden" id="email" value="'.htmlentities($email).'"/>
				    <input type="hidden" id="timestamp" value="'.htmlentities($timestamp).'"/>
				    <input type="hidden" id="auth" value="'.htmlentities($auth).'"/>
				    <div><label class="padding_label">New password:</label><input type="password" id="password1" value=""/></div>
				    <div><label class="padding_label">Again:</label><input type="password" id="password2" value=""/></div>
				    <br><input type="submit" value="Change password">
				  </form>';
			}
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		return false;
	}

	private function getForgotPasswordText($email) {
		$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = ?", array($email));
		if (OIDplus::db()->num_rows($res) == 0) {
			throw new Exception("This RA does not exist.");
		}

		$message = file_get_contents(__DIR__ . '/forgot_password.tpl');

		// Resolve stuff
		$message = str_replace('{{SYSTEM_URL}}', OIDplus::system_url(), $message);
		$message = str_replace('{{ADMIN_EMAIL}}', OIDplus::config()->getValue('admin_email'), $message);

		// {{ACTIVATE_URL}} will be resolved in ajax.php

		return $message;
	}

	public function tree_search($request) {
		return false;
	}
}

OIDplus::registerPagePlugin(new OIDplusPagePublicForgotPassword());
