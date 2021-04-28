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

if (!defined('INSIDE_OIDPLUS')) die();

class OIDplusPagePublicForgotPassword extends OIDplusPagePluginPublic {

	public function action($actionID, $params) {
		if ($actionID == 'forgot_password') {
			_CheckParamExists($params, 'email');
			$email = $params['email'];

			if (!OIDplus::mailUtils()->validMailAddress($email)) {
				throw new OIDplusException(_L('Invalid email address'));
			}

			if (OIDplus::baseConfig()->getValue('RECAPTCHA_ENABLED', false)) {
				_CheckParamExists($params, 'captcha');
				$secret=OIDplus::baseConfig()->getValue('RECAPTCHA_PRIVATE', '');
				$response=$params["captcha"];
				$verify=file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret}&response={$response}");
				$captcha_success=json_decode($verify);
				if ($captcha_success->success==false) {
					throw new OIDplusException(_L('CAPTCHA not successfully verified'));
				}
			}

			OIDplus::logger()->log("[WARN]RA($email)!", "A new password for '$email' was requested (forgot password)");

			$timestamp = time();
			$activate_url = OIDplus::webpath(null,false) . '?goto='.urlencode('oidplus:reset_password$'.$email.'$'.$timestamp.'$'.OIDplus::authUtils()->makeAuthKey('reset_password;'.$email.';'.$timestamp));

			$message = $this->getForgotPasswordText($params['email']);
			$message = str_replace('{{ACTIVATE_URL}}', $activate_url, $message);

			OIDplus::mailUtils()->sendMail($email, OIDplus::config()->getValue('system_title').' - Password reset request', $message, OIDplus::config()->getValue('global_cc'));

			return array("status" => 0);

		} else if ($actionID == 'reset_password') {
			
			_CheckParamExists($params, 'password1');
			_CheckParamExists($params, 'password2');
			_CheckParamExists($params, 'email');
			_CheckParamExists($params, 'auth');
			_CheckParamExists($params, 'timestamp');

			$password1 = $params['password1'];
			$password2 = $params['password2'];
			$email = $params['email'];
			$auth = $params['auth'];
			$timestamp = $params['timestamp'];

			if (!OIDplus::authUtils()->validateAuthKey('reset_password;'.$email.';'.$timestamp, $auth)) {
				throw new OIDplusException(_L('Invalid auth key'));
			}

			if ((OIDplus::config()->getValue('max_ra_pwd_reset_time') > 0) && (time()-$timestamp > OIDplus::config()->getValue('max_ra_pwd_reset_time'))) {
				throw new OIDplusException(_L('Invitation expired!'));
			}

			if ($password1 !== $password2) {
				throw new OIDplusException(_L('Passwords do not match'));
			}

			if (strlen($password1) < OIDplus::config()->getValue('ra_min_password_length')) {
				$minlen = OIDplus::config()->getValue('ra_min_password_length');
				throw new OIDplusException(_L('Password is too short. Need at least %1 characters',$minlen));
			}

			OIDplus::logger()->log("[INFO]RA($email)!", "RA '$email' has reset his password (forgot passwort)");

			$ra = new OIDplusRA($email);
			$ra->change_password($password1);

			return array("status" => 0);
		} else {
			throw new OIDplusException(_L('Unknown action ID'));
		}
	}

	public function init($html=true) {
		OIDplus::config()->prepareConfigKey('max_ra_pwd_reset_time', 'Max RA password reset time in seconds (0 = infinite)', '0', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < 0)) {
				throw new OIDplusException(_L('Please enter a valid value.'));
			}
		});
	}

	public function gui($id, &$out, &$handled) {
		if (explode('$',$id)[0] == 'oidplus:forgot_password') {
			$handled = true;

			$out['title'] = _L('Forgot password');
			$out['icon'] = OIDplus::webpath(__DIR__).'forgot_password_big.png';

			try {
				$out['text'] .= '<p>'._L('Please enter the email address of your account, and information about the password reset will be sent to you.').'</p>
				  <form id="forgotPasswordForm" action="javascript:void(0);" onsubmit="return OIDplusPagePublicForgotPassword.forgotPasswordFormOnSubmit();">
				    '._L('E-Mail').': <input type="text" id="email" value=""/><br><br>'.
				 (OIDplus::baseConfig()->getValue('RECAPTCHA_ENABLED', false) ?
				 '<div id="g-recaptcha" class="g-recaptcha" data-sitekey="'.OIDplus::baseConfig()->getValue('RECAPTCHA_PUBLIC', '').'"></div>'.
				 '<script> grecaptcha.render($("#g-recaptcha")[0], { "sitekey" : "'.OIDplus::baseConfig()->getValue('RECAPTCHA_PUBLIC', '').'" }); </script>' : '').
				' <br>
				    <input type="submit" value="'._L('Send recovery information').'">
				  </form>';

			} catch (Exception $e) {

				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>'._L('Error: %1',htmlentities($e->getMessage())).'</p>';

			}
		} else if (explode('$',$id)[0] == 'oidplus:reset_password') {
			$handled = true;

			$email = explode('$',$id)[1];
			$timestamp = explode('$',$id)[2];
			$auth = explode('$',$id)[3];

			$out['title'] = _L('Reset password');
			$out['icon'] = OIDplus::webpath(__DIR__).'reset_password_big.png';

			if (!OIDplus::authUtils()->validateAuthKey('reset_password;'.$email.';'.$timestamp, $auth)) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = _L('Invalid authorization. Is the URL OK?');
			} else {
				$out['text'] = '<p>'._L('E-Mail-Address: %1','<b>'.$email.'</b>').'</p>

				  <form id="resetPasswordForm" action="javascript:void(0);" onsubmit="return OIDplusPagePublicForgotPassword.resetPasswordFormOnSubmit();">
				    <input type="hidden" id="email" value="'.htmlentities($email).'"/>
				    <input type="hidden" id="timestamp" value="'.htmlentities($timestamp).'"/>
				    <input type="hidden" id="auth" value="'.htmlentities($auth).'"/>
				    <div><label class="padding_label">'._L('New password').':</label><input type="password" id="password1" value=""/></div>
				    <div><label class="padding_label">'._L('Repeat').':</label><input type="password" id="password2" value=""/></div>
				    <br><input type="submit" value="'._L('Change password').'">
				  </form>';
			}
		}
	}

	public function publicSitemap(&$out) {
		$out[] = 'oidplus:forgot_password';
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		return false;
	}

	private function getForgotPasswordText($email) {
		$res = OIDplus::db()->query("select * from ###ra where email = ?", array($email));
		if ($res->num_rows() == 0) {
			throw new OIDplusException(_L('This RA does not exist.'));
		}

		$message = file_get_contents(__DIR__ . '/forgot_password.tpl');

		// Resolve stuff
		$message = str_replace('{{SYSTEM_URL}}', OIDplus::webpath(null,false), $message);
		$message = str_replace('{{ADMIN_EMAIL}}', OIDplus::config()->getValue('admin_email'), $message);

		// {{ACTIVATE_URL}} will be resolved in ajax.php

		return $message;
	}

	public function tree_search($request) {
		return false;
	}
}
