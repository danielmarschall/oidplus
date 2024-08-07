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

namespace ViaThinkSoft\OIDplus\Plugins\RaPages\ChangeEMail;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusConfig;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusHtmlException;
use ViaThinkSoft\OIDplus\Core\OIDplusMailException;
use ViaThinkSoft\OIDplus\Core\OIDplusObject;
use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginRa;
use ViaThinkSoft\OIDplus\Core\OIDplusRA;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusPageRaChangeEMail extends OIDplusPagePluginRa {

	/**
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 * @throws OIDplusMailException
	 */
	private function action_Request(array $params): array {
		if (!OIDplus::config()->getValue('allow_ra_email_change') && !OIDplus::authUtils()->isAdminLoggedIn()) {
			throw new OIDplusException(_L('This functionality has been disabled by the administrator.'));
		}

		_CheckParamExists($params, 'old_email');
		_CheckParamExists($params, 'new_email');

		$old_email = $params['old_email'];
		$new_email = $params['new_email'];

		$ra = new OIDplusRA($old_email);
		if ($ra->isPasswordLess() && !OIDplus::authUtils()->isAdminLoggedIn()) {
			throw new OIDplusException(_L('E-Mail-Address cannot be changed because this user does not have a password'));
		}

		if (!OIDplus::authUtils()->isRaLoggedIn($old_email) && !OIDplus::authUtils()->isAdminLoggedIn()) {
			throw new OIDplusException(_L('Authentication error. Please log in as admin, or as the RA to update its email address.'), null, 401);
		}

		if (!OIDplus::mailUtils()->validMailAddress($new_email)) {
			throw new OIDplusException(_L('eMail address is invalid.'));
		}

		$res = OIDplus::db()->query("select * from ###ra where email = ?", array($old_email));
		if (!$res->any()) {
			throw new OIDplusException(_L('eMail address does not exist anymore. It was probably already changed.'));
		}

		$res = OIDplus::db()->query("select * from ###ra where email = ?", array($new_email));
		if ($res->any()) {
			throw new OIDplusException(_L('eMail address is already used by another RA. To merge accounts, please contact the superior RA of your objects and request an owner change of your objects.'));
		}

		if (OIDplus::authUtils()->isAdminLoggedIn()) {
			$ra_was_logged_in = OIDplus::authUtils()->isRaLoggedIn($old_email);

			$ra = new OIDplusRA($old_email);

			// Change RA email
			$ra->change_email($new_email);
			OIDplus::logger()->log("V2:[WARN]RA(%1)+[INFO]RA(%2)+[OK]A", "Admin changed email address '%1' to '%2'", $old_email, $new_email);

			// Change objects
			$res = OIDplus::db()->query("select id from ###objects where ra_email = ?", array($old_email));
			while ($row = $res->fetch_array()) {
				OIDplus::logger()->log("V2:[INFO]OID(%1)+SUPOID(%1)", "Admin changed email address of RA '%2' (owner of %1) to '%3'", $row['id'], $old_email, $new_email);
			}
			OIDplus::db()->query("update ###objects set ra_email = ? where ra_email = ?", array($new_email, $old_email));
			OIDplusObject::resetObjectInformationCache();

			// Re-login
			if ($ra_was_logged_in) {
				OIDplus::authUtils()->raLogout($old_email);
				OIDplus::authUtils()->raLogin($new_email);
			}

			return array("status" => 0);
		} else {
			OIDplus::logger()->log("V2:[INFO]RA(%1)+RA(%2)", "Requested email address change from '%1' to '%2'", $old_email, $new_email);

			$activate_url = OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL) . '?goto='.urlencode('oidplus:activate_new_ra_email$'.$old_email.'$'.$new_email.'$'.OIDplus::authUtils()->makeAuthKey(['5ef24124-f4fb-11ed-b67e-3c4a92df8582',$old_email,$new_email]));

			$message = file_get_contents(__DIR__ . '/change_request_email.tpl');
			$message = str_replace('{{SYSTEM_URL}}', OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL), $message);
			$message = str_replace('{{SYSTEM_TITLE}}', OIDplus::config()->getValue('system_title'), $message);
			$message = str_replace('{{ADMIN_EMAIL}}', OIDplus::config()->getValue('admin_email'), $message);
			$message = str_replace('{{OLD_EMAIL}}', $old_email, $message);
			$message = str_replace('{{NEW_EMAIL}}', $new_email, $message);
			$message = str_replace('{{ACTIVATE_URL}}', $activate_url, $message);
			OIDplus::mailUtils()->sendMail($new_email, OIDplus::config()->getValue('system_title').' - Change email request', $message);

			return array("status" => 0);
		}
	}

	/**
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 * @throws OIDplusMailException
	 */
	private function action_Activate(array $params): array {
		if (!OIDplus::config()->getValue('allow_ra_email_change')) {
			throw new OIDplusException(_L('This functionality has been disabled by the administrator.'));
		}

		_CheckParamExists($params, 'old_email');
		_CheckParamExists($params, 'new_email');
		_CheckParamExists($params, 'password');
		_CheckParamExists($params, 'auth');

		$old_email = $params['old_email'];
		$new_email = $params['new_email'];
		$password = $params['password'];

		$auth = $params['auth'];

		$ra_was_logged_in = OIDplus::authUtils()->isRaLoggedIn($old_email);

		$ra = new OIDplusRA($old_email);
		if ($ra->isPasswordLess() && !OIDplus::authUtils()->isAdminLoggedIn()) {
			throw new OIDplusException(_L('E-Mail-Address cannot be changed because this user does not have a password'));
		}

		if (!OIDplus::authUtils()->validateAuthKey(['5ef24124-f4fb-11ed-b67e-3c4a92df8582',$old_email,$new_email], $auth, OIDplus::config()->getValue('max_ra_email_change_time', -1))) {
			throw new OIDplusException(_L('Invalid or expired authentication key'));
		}

		$res = OIDplus::db()->query("select * from ###ra where email = ?", array($old_email));
		if (!$res->any()) {
			throw new OIDplusException(_L('eMail address does not exist anymore. It was probably already changed.'));
		}

		$res = OIDplus::db()->query("select * from ###ra where email = ?", array($new_email));
		if ($res->any()) {
			throw new OIDplusException(_L('eMail address is already used by another RA. To merge accounts, please contact the superior RA of your objects and request an owner change of your objects.'));
		}

		$ra = new OIDplusRA($old_email);
		if (!$ra->isPasswordLess()) {
			if (!$ra->checkPassword($password)) {
				throw new OIDplusException(_L('Wrong password'));
			}
		}

		// Change address of RA
		$ra->change_email($new_email);
		OIDplus::logger()->log("V2:[OK]RA(%2)+RA(%1)", "RA '%1' has changed their email address to '%2'", $old_email, $new_email);

		// Change objects
		$res = OIDplus::db()->query("select id from ###objects where ra_email = ?", array($old_email));
		while ($row = $res->fetch_array()) {
			OIDplus::logger()->log("V2:[INFO]OID(%1)+SUPOID(%1)", "RA '%2' (owner of %1) has changed their email address to '%3'", $row['id'], $old_email, $new_email);
		}
		OIDplus::db()->query("update ###objects set ra_email = ? where ra_email = ?", array($new_email, $old_email));
		OIDplusObject::resetObjectInformationCache();

		// Re-login
		if ($ra_was_logged_in) {
			OIDplus::authUtils()->raLogout($old_email);
			OIDplus::authUtils()->raLogin($new_email);
		}

		// Send email
		$message = file_get_contents(__DIR__ . '/email_change_confirmation.tpl');
		$message = str_replace('{{SYSTEM_URL}}', OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL), $message);
		$message = str_replace('{{SYSTEM_TITLE}}', OIDplus::config()->getValue('system_title'), $message);
		$message = str_replace('{{ADMIN_EMAIL}}', OIDplus::config()->getValue('admin_email'), $message);
		$message = str_replace('{{OLD_EMAIL}}', $old_email, $message);
		$message = str_replace('{{NEW_EMAIL}}', $new_email, $message);
		OIDplus::mailUtils()->sendMail($old_email, OIDplus::config()->getValue('system_title').' - eMail address changed', $message);

		return array("status" => 0);
	}

	/**
	 * @param string $actionID
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 * @throws OIDplusMailException
	 */
	public function action(string $actionID, array $params): array {
		if ($actionID == 'change_ra_email') {
			return $this->action_Request($params);
		} else if ($actionID == 'activate_new_ra_email') {
			return $this->action_Activate($params);
		} else {
			return parent::action($actionID, $params);
		}
	}

	/**
	 * @param bool $html
	 * @return void
	 * @throws OIDplusException
	 */
	public function init(bool $html=true): void {
		OIDplus::config()->prepareConfigKey('max_ra_email_change_time', 'Max RA email change time in seconds (0 = infinite)', '0', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < 0)) {
				throw new OIDplusException(_L('Please enter a valid value.'));
			}
		});
		OIDplus::config()->prepareConfigKey('allow_ra_email_change', 'Allow that RAs change their email address (0/1)', '1', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
		        if (($value != '0') && ($value != '1')) {
		                throw new OIDplusException(_L('Please enter a valid value (0=no, 1=yes).'));
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
	public function gui(string $id, array &$out, bool &$handled): void {
		if (explode('$',$id)[0] == 'oidplus:change_ra_email') {
			$handled = true;

			$ra_email = explode('$',$id)[1];

			$out['title'] = _L('Change RA email');
			$out['icon'] = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

			if (!OIDplus::authUtils()->isRaLoggedIn($ra_email) && !OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as the requested RA %2 or as admin.',OIDplus::gui()->link('oidplus:login$ra$'.$ra_email),'<b>'.htmlentities($ra_email).'</b>'), $out['title'], 401);
			}

			$res = OIDplus::db()->query("select * from ###ra where email = ?", array($ra_email));
			if (!$res->any()) {
				throw new OIDplusHtmlException(_L('RA "%1" does not exist','<b>'.htmlentities($ra_email).'</b>'), $out['title']);
			}

			if (!OIDplus::config()->getValue('allow_ra_email_change') && !OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusException(_L('This functionality has been disabled by the administrator.'), $out['title']);
			}

			if (OIDplus::authUtils()->isAdminLoggedIn()) {
				$ra = new OIDplusRA($ra_email);
				if ($ra->isPasswordLess()) {
					$out['text'] .= '<p>'._L('Attention: This user does not have a password because they log in using LDAP or Google OAuth etc.').'</p>';
					$out['text'] .= '<p>'._L('If you change the email address, the user cannot log in anymore, because the LDAP/OAuth plugin identifies the user via email address, not OpenID.').'</p>';
					$out['text'] .= '<p>'._L('If you want to change the email address of the user, please <a %1>define a password</a> for them, so that they can use the regular login method using their new email address.', OIDplus::gui()->link('oidplus:change_ra_password$'.$ra_email)).'</p>';
				}

				$out['text'] .= '<form id="changeRaEmailForm" action="javascript:void(0);" action="javascript:void(0);" onsubmit="return OIDplusPageRaChangeEMail.changeRaEmailFormOnSubmit(true);">';
				$out['text'] .= '<input type="hidden" id="old_email" value="'.htmlentities($ra_email).'"/><br>';
				$out['text'] .= '<div><label class="padding_label">'._L('Old address').':</label><b>'.htmlentities($ra_email).'</b></div>';
				$out['text'] .= '<div><label class="padding_label">'._L('New address').':</label><input type="text" id="new_email" value=""/></div>';
				$out['text'] .= '<br><input type="submit" value="'._L('Change password').'"> '._L('(admin does not require email verification)').'</form>';
			} else {
				$ra = new OIDplusRA($ra_email);
				if ($ra->isPasswordLess()) {
					$out['icon'] = 'img/error.png';
					$out['text'] .= '<p>'._L('Attention: You are logged in without password (via LDAP or Google OAuth etc.).').'</p>';
					$out['text'] .= '<p>'._L('Therefore, you cannot change your email address, otherwise you would lose access to your account!').'</p>';
					$out['text'] .= '<p>'._L('If you want to change your email address, then please <a %1>setup a password</a> first, and then use the regular login method to log in using your new email address.', OIDplus::gui()->link('oidplus:change_ra_password$'.$ra_email)).'</p>';
					return; // throw new OIDplusHtmlException($out['text'], $out['title']);
				}

				$out['text'] .= '<form id="changeRaEmailForm" action="javascript:void(0);" action="javascript:void(0);" onsubmit="return OIDplusPageRaChangeEMail.changeRaEmailFormOnSubmit(false);">';
				$out['text'] .= '<input type="hidden" id="old_email" value="'.htmlentities($ra_email).'"/><br>';
				$out['text'] .= '<div><label class="padding_label">'._L('Old address').':</label><b>'.htmlentities($ra_email).'</b></div>';
				$out['text'] .= '<div><label class="padding_label">'._L('New address').':</label><input type="text" id="new_email" value=""/></div>';
				$out['text'] .= '<br><input type="submit" value="'._L('Send new activation email').'"></form>';
			}
		} else if (explode('$',$id)[0] == 'oidplus:activate_new_ra_email') {
			$handled = true;

			$old_email = explode('$',$id)[1];
			$new_email = explode('$',$id)[2];
			$auth = explode('$',$id)[3];

			$out['title'] = _L('Perform email address change');
			$out['icon'] = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

			if (!OIDplus::config()->getValue('allow_ra_email_change') && !OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusException(_L('This functionality has been disabled by the administrator.'), $out['title']);
			}

			$ra = new OIDplusRA($old_email);
			if ($ra->isPasswordLess() && !OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusException(_L('E-Mail-Address cannot be changed because this user does not have a password'), $out['title']);
			}

			$res = OIDplus::db()->query("select * from ###ra where email = ?", array($old_email));
			if (!$res->any()) {
				throw new OIDplusException(_L('eMail address does not exist anymore. It was probably already changed.'), $out['title']);
			} else {
				$res = OIDplus::db()->query("select * from ###ra where email = ?", array($new_email));
				if ($res->any()) {
					throw new OIDplusException(_L('eMail address is already used by another RA. To merge accounts, please contact the superior RA of your objects and request an owner change of your objects.'), $out['title']);
				} else {
					if (!OIDplus::authUtils()->validateAuthKey(['5ef24124-f4fb-11ed-b67e-3c4a92df8582',$old_email,$new_email], $auth, OIDplus::config()->getValue('max_ra_email_change_time', -1))) {
						throw new OIDplusException(_L('Invalid authorization. Is the URL OK?'), $out['title']);
					} else {
						$out['text'] = '<p>'._L('Old eMail-Address').': <b>'.$old_email.'</b></p>
						<p>'._L('New eMail-Address').': <b>'.$new_email.'</b></p>

						 <form id="activateNewRaEmailForm" action="javascript:void(0);" onsubmit="return OIDplusPageRaChangeEMail.activateNewRaEmailFormOnSubmit();">
					    <input type="hidden" id="old_email" value="'.htmlentities($old_email).'"/>
					    <input type="hidden" id="new_email" value="'.htmlentities($new_email).'"/>
					    <input type="hidden" id="auth" value="'.htmlentities($auth).'"/>

					    <div><label class="padding_label">'._L('Please verify your password').':</label><input type="password" id="password" value=""/></div>
					    <br><input type="submit" value="'._L('Change email address').'">
					  </form>';
					}
				}
			}
		}
	}

	/**
	 * @param array $json
	 * @param string|null $ra_email
	 * @param bool $nonjs
	 * @param string $req_goto
	 * @return bool
	 * @throws OIDplusException
	 */
	public function tree(array &$json, ?string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
		if (!$ra_email) return false;
		if (!OIDplus::authUtils()->isRaLoggedIn($ra_email) && !OIDplus::authUtils()->isAdminLoggedIn()) return false;

		if (file_exists(__DIR__.'/img/main_icon16.png')) {
			$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:change_ra_email$'.$ra_email,
			'icon' => $tree_icon,
			'text' => _L('Change email address')
		);

		return true;
	}

	/**
	 * @param string $request
	 * @return array|false
	 */
	public function tree_search(string $request) {
		return false;
	}
}
