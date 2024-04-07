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

class OIDplusPageRaInvite extends OIDplusPagePluginRa {

	/**
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 * @throws OIDplusMailException
	 */
	private function action_Request(array $params): array {
		$email = $params['email'] ?? "";

		OIDplus::getActiveCaptchaPlugin()->captchaVerify($params, 'captcha');

		$this->inviteSecurityCheck($email);

		$by = OIDplus::authUtils()->isAdminLoggedIn() ? 'the system administrator' : 'a superior Registration Authority';
		OIDplus::logger()->log("V2:[INFO]RA(%1)", "RA '%1' has been invited by %2", $email, $by);

		$activate_url = OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL) . '?goto='.urlencode('oidplus:activate_ra$'.$email.'$'.OIDplus::authUtils()->makeAuthKey(['ed840c3e-f4fa-11ed-b67e-3c4a92df8582',$email]));

		$message = $this->getInvitationText($email);
		$message = str_replace('{{ACTIVATE_URL}}', $activate_url, $message);

		OIDplus::mailUtils()->sendMail($email, OIDplus::config()->getValue('system_title').' - Invitation', $message);

		return array("status" => 0);
	}

	/**
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 * @throws OIDplusMailException
	 */
	private function action_Activate(array $params): array {
		_CheckParamExists($params, 'password1');
		_CheckParamExists($params, 'password2');
		_CheckParamExists($params, 'email');
		_CheckParamExists($params, 'auth');

		$password1 = $params['password1'];
		$password2 = $params['password2'];
		$email = $params['email'];
		$auth = $params['auth'];

		if (!OIDplus::authUtils()->validateAuthKey(['ed840c3e-f4fa-11ed-b67e-3c4a92df8582',$email], $auth, OIDplus::config()->getValue('max_ra_invite_time',-1))) {
			throw new OIDplusException(_L('Invalid or expired authentication key'));
		}

		if (!$email || !OIDplus::mailUtils()->validMailAddress($email)) {
			throw new OIDplusException(_L('Invalid email address'));
		}

		if ($password1 !== $password2) {
			throw new OIDplusException(_L('Passwords do not match'));
		}

		if (strlen($password1) < OIDplus::config()->getValue('ra_min_password_length')) {
			$minlen = OIDplus::config()->getValue('ra_min_password_length');
			throw new OIDplusException(_L('Password is too short. Need at least %1 characters',$minlen));
		}

		OIDplus::logger()->log("V2:[OK]RA(%1)", "RA '%1' has been registered due to invitation", $email);

		$ra = new OIDplusRA($email);
		$ra->register_ra($password1);

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
		if ($actionID == 'invite_ra') {
			return $this->action_Request($params);
		} else if ($actionID == 'activate_ra') {
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
	public function init(bool $html=true) {
		OIDplus::config()->prepareConfigKey('max_ra_invite_time', 'Max RA invite time in seconds (0 = infinite)', '0', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < 0)) {
				throw new OIDplusException(_L('Please enter a valid value.'));
			}
		});
		OIDplus::config()->prepareConfigKey('ra_invitation_enabled', 'May RAs be invited? (0=no, 1=yes)', '1', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (($value != 0) && ($value != 1)) {
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
	public function gui(string $id, array &$out, bool &$handled) {
		if (explode('$',$id)[0] == 'oidplus:invite_ra') {
			$handled = true;

			$email = explode('$',$id)[1] ?? null;
			$origin = explode('$',$id)[2] ?? "oidplus:system";

			$out['title'] = _L('Invite a Registration Authority');

			if (!OIDplus::config()->getValue('ra_invitation_enabled')) {
				throw new OIDplusException(_L('Invitations are disabled by the administrator.'), $out['title']);
			}

			$out['icon'] = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/invite_icon.png';

			try {
				$this->inviteSecurityCheck($email);
				$message = $this->getInvitationText($email);
				$message = str_replace('{{ACTIVATE_URL}}', '[...]', $message); // secret. Will only be shown in the email to the invited person

				$out['text'] .= '<p>'._L('You have chosen to invite %1 as a Registration Authority. If you click "Send invitation", the following email will be sent to %2:','<b>'.$email.'</b>',$email).'</p><p><i>'.nl2br(htmlentities($message)).'</i></p>
				  <form id="inviteForm" action="javascript:void(0);" onsubmit="return OIDplusPageRaInvite.inviteFormOnSubmit();">
				    <input type="hidden" id="email" value="'.htmlentities($email).'"/>
				    <input type="hidden" id="origin" value="'.htmlentities($origin).'"/>
				    '.OIDplus::getActiveCaptchaPlugin()->captchaGenerate().'
				    <br>
				    <input type="button" value="'._L('Cancel').'" onclick="history.back()"><!-- TODO: redirect to $origin instead? -->
				    <input type="submit" value="'._L('Send invitation').'">
				  </form>';

			} catch (\Exception $e) {

				$htmlmsg = $e instanceof OIDplusException ? $e->getHtmlMessage() : htmlentities($e->getMessage());
				throw new OIDplusHtmlException(_L('Error: %1',$htmlmsg), $out['title']);

			}
		} else if (explode('$',$id)[0] == 'oidplus:activate_ra') {
			$handled = true;

			$email = explode('$',$id)[1];
			$auth = explode('$',$id)[2];

			$out['title'] = _L('Register as Registration Authority');

			if (!OIDplus::config()->getValue('ra_invitation_enabled')) {
				throw new OIDplusException(_L('Invitations are disabled by the administrator.'), $out['title']);
			}

			$out['icon'] = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/activate_icon.png';

			$res = OIDplus::db()->query("select * from ###ra where email = ?", array($email));
			if ($res->any()) {
				$out['text'] = _L('This RA is already registered and does not need to be invited.');
			} else {
				if (!OIDplus::authUtils()->validateAuthKey(['ed840c3e-f4fa-11ed-b67e-3c4a92df8582',$email], $auth, OIDplus::config()->getValue('max_ra_invite_time',-1))) {
					throw new OIDplusException(_L('Invalid authorization. Is the URL OK?'), $out['title']);
				} else {
					// TODO: like in the FreeOID plugin, we could ask here at least for a name for the RA
					$out['text'] = '<p>'._L('E-Mail-Address').': <b>'.$email.'</b></p>

					  <form id="activateRaForm" action="javascript:void(0);" onsubmit="return OIDplusPageRaInvite.activateRaFormOnSubmit();">
					    <input type="hidden" id="email" value="'.htmlentities($email).'"/>
					    <input type="hidden" id="auth" value="'.htmlentities($auth).'"/>
					    <div><label class="padding_label">'._L('New password').':</label><input type="password" id="password1" value=""/></div>
					    <div><label class="padding_label">'._L('Repeat').':</label><input type="password" id="password2" value=""/></div>
					    <br><input type="submit" value="'._L('Register').'">
					  </form>';
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
	 */
	public function tree(array &$json, string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
		//if (!$ra_email) return false;
		//if (!OIDplus::authUtils()->isRaLoggedIn($ra_email) && !OIDplus::authUtils()->isAdminLoggedIn()) return false;

		return false;
	}

	/**
	 * @param string $email
	 * @return void
	 * @throws OIDplusException
	 */
	private function inviteSecurityCheck(string $email) {
		if (!$email || !OIDplus::mailUtils()->validMailAddress($email)) {
			throw new OIDplusException(_L('Invalid email address'));
		}

		$res = OIDplus::db()->query("select * from ###ra where email = ?", array($email));
		if ($res->any()) {
			throw new OIDplusException(_L('This RA is already registered and does not need to be invited.'));
		}

		if (!OIDplus::authUtils()->isAdminLoggedIn()) {
			// Check if the RA may invite the user (i.e. the they are the parent of an OID of that person)
			$ok = false;
			$res = OIDplus::db()->query("select parent from ###objects where ra_email = ?", array($email));
			while ($row = $res->fetch_array()) {
				if (!$row['parent']) continue;
				$objParent = OIDplusObject::parse($row['parent']);
				if (!$objParent) throw new OIDplusException(_L('Type of %1 unknown',$row['parent']));
				if ($objParent->userHasWriteRights()) {
					$ok = true;
				}
			}
			if (!$ok) {
				throw new OIDplusHtmlException(_L('You may not invite this RA. Maybe you need to <a %1>log in</a> again.',OIDplus::gui()->link('oidplus:login')), null, 401);
			}
		}
	}

	/**
	 * @param string $email
	 * @return string
	 * @throws OIDplusException
	 */
	private function getInvitationText(string $email): string {
		$list_of_oids = array();
		$res = OIDplus::db()->query("select id from ###objects where ra_email = ?", array($email));
		while ($row = $res->fetch_array()) {
			$list_of_oids[] = $row['id'];
		}
		if (count($list_of_oids) == 0) {
			$list_of_oids[] = '(' . _L('None') . ')';
		}

		$message = file_get_contents(__DIR__ . '/invite_msg.tpl');

		// Resolve stuff
		$message = str_replace('{{SYSTEM_URL}}', OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL), $message);
		$message = str_replace('{{OID_LIST}}', implode("\n", $list_of_oids), $message);
		$message = str_replace('{{ADMIN_EMAIL}}', OIDplus::config()->getValue('admin_email'), $message);
		// Note: {{ACTIVATE_URL}} will be resolved by the caller, not here!

		return str_replace('{{PARTY}}', OIDplus::authUtils()->isAdminLoggedIn() ? 'the system administrator' : 'a superior Registration Authority', $message);
	}

	/**
	 * @param string $request
	 * @return array|false
	 */
	public function tree_search(string $request) {
		return false;
	}
}
