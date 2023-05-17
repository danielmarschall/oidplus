<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2023 Daniel Marschall, ViaThinkSoft
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

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusPagePublicForgotPassword extends OIDplusPagePluginPublic {

	/**
	 * @param string $actionID
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 * @throws OIDplusMailException
	 */
	public function action(string $actionID, array $params): array {
		if ($actionID == 'forgot_password') {
			_CheckParamExists($params, 'email');
			$email = $params['email'];

			if (!OIDplus::mailUtils()->validMailAddress($email)) {
				throw new OIDplusException(_L('Invalid email address'));
			}

			OIDplus::getActiveCaptchaPlugin()->captchaVerify($params, 'captcha');

			OIDplus::logger()->log("V2:[WARN]RA(%1)", "A new password for '%1' was requested (forgot password)", $email);

			$timestamp = time();
			$activate_url = OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL) . '?goto='.urlencode('oidplus:reset_password$'.$email.'$'.$timestamp.'$'.OIDplus::authUtils()->makeAuthKey('93a16dbe-f4fb-11ed-b67e-3c4a92df8582:'.$email.'/'.$timestamp));

			$message = $this->getForgotPasswordText($params['email']);
			$message = str_replace('{{ACTIVATE_URL}}', $activate_url, $message);

			OIDplus::mailUtils()->sendMail($email, OIDplus::config()->getValue('system_title').' - Password reset request', $message);

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

			if (!OIDplus::authUtils()->validateAuthKey('93a16dbe-f4fb-11ed-b67e-3c4a92df8582:'.$email.'/'.$timestamp, $auth)) {
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

			OIDplus::logger()->log("V2:[INFO]RA(%1)", "RA '%1' has reset his password (forgot passwort)", $email);

			$ra = new OIDplusRA($email);
			$ra->change_password($password1);

			return array("status" => 0);
		} else {
			return parent::action($actionID, $params);
		}
	}

	/**
	 * @param bool $html
	 * @return void
	 * @throws OIDplusException
	 */
	public function init(bool $html=true) {
		OIDplus::config()->prepareConfigKey('max_ra_pwd_reset_time', 'Max RA password reset time in seconds (0 = infinite)', '0', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < 0)) {
				throw new OIDplusException(_L('Please enter a valid value.'));
			}
		});
	}

	/**
	 * @param string $id
	 * @param array $out
	 * @param bool $handled
	 * @return void
	 * @throws OIDplusException
	 */
	public function gui(string $id, array &$out, bool &$handled) {
		if (explode('$',$id)[0] == 'oidplus:forgot_password') {
			$handled = true;

			$out['title'] = _L('Forgot password');
			$out['icon'] = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/forgot_password_icon.png';

			try {
				$out['text'] .= '<p>'._L('Please enter the email address of your account, and information about the password reset will be sent to you.').'</p>
				  <form id="forgotPasswordForm" action="javascript:void(0);" onsubmit="return OIDplusPagePublicForgotPassword.forgotPasswordFormOnSubmit();">
				    '._L('E-Mail').': <input type="text" id="email" value=""/><br><br>
				    '.OIDplus::getActiveCaptchaPlugin()->captchaGenerate().'
				    <br>
				    <input type="submit" value="'._L('Send recovery information').'">
				  </form>';

			} catch (\Exception $e) {

				$htmlmsg = $e instanceof OIDplusException ? $e->getHtmlMessage() : htmlentities($e->getMessage());
				throw new OIDplusHtmlException(_L('Error: %1',$htmlmsg), $out['title']);

			}
		} else if (explode('$',$id)[0] == 'oidplus:reset_password') {
			$handled = true;

			$email = explode('$',$id)[1];
			$timestamp = explode('$',$id)[2];
			$auth = explode('$',$id)[3];

			$out['title'] = _L('Reset password');
			$out['icon'] = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/reset_password_icon.png';

			if (!OIDplus::authUtils()->validateAuthKey('reset_password;'.$email.';'.$timestamp, $auth)) {
				throw new OIDplusException(_L('Invalid authorization. Is the URL OK?'), $out['title']);
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

	/**
	 * @param array $out
	 * @return void
	 */
	public function publicSitemap(array &$out) {
		$out[] = 'oidplus:forgot_password';
	}

	/**
	 * @param array $json
	 * @param string|null $ra_email
	 * @param bool $nonjs
	 * @param string $req_goto
	 * @return bool
	 */
	public function tree(array &$json, string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
		return false;
	}

	/**
	 * @param string $email
	 * @return string
	 * @throws OIDplusException
	 */
	private function getForgotPasswordText(string $email): string {
		$res = OIDplus::db()->query("select * from ###ra where email = ?", array($email));
		if (!$res->any()) {
			throw new OIDplusException(_L('This RA does not exist.'));
		}

		$message = file_get_contents(__DIR__ . '/forgot_password.tpl');

		// Resolve stuff
		// Note: {{ACTIVATE_URL}} will be resolved in ajax.php

		$message = str_replace('{{SYSTEM_URL}}', OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL), $message);

		return str_replace('{{ADMIN_EMAIL}}', OIDplus::config()->getValue('admin_email'), $message);
	}

	/**
	 * @param string $request
	 * @return array|false
	 */
	public function tree_search(string $request) {
		return false;
	}
}
