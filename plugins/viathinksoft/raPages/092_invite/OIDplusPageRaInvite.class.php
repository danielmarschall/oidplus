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

class OIDplusPageRaInvite extends OIDplusPagePluginRa {

	public function action($actionID, $params) {
		if ($actionID == 'invite_ra') {
			$email = $params['email'];

			if (!OIDplus::mailUtils()->validMailAddress($email)) {
				throw new OIDplusException(_L('Invalid email address'));
			}

			OIDplus::getActiveCaptchaPlugin()->captchaVerify($params, 'captcha');

			$this->inviteSecurityCheck($email);
			// TODO: should we also log who has invited?
			OIDplus::logger()->log("[INFO]RA($email)!", "RA '$email' has been invited");

			$timestamp = time();
			$activate_url = OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL) . '?goto='.urlencode('oidplus:activate_ra$'.$email.'$'.$timestamp.'$'.OIDplus::authUtils()->makeAuthKey('activate_ra;'.$email.';'.$timestamp));

			$message = $this->getInvitationText($email);
			$message = str_replace('{{ACTIVATE_URL}}', $activate_url, $message);

			OIDplus::mailUtils()->sendMail($email, OIDplus::config()->getValue('system_title').' - Invitation', $message);

			return array("status" => 0);

		} else if ($actionID == 'activate_ra') {

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

			if (!OIDplus::authUtils()->validateAuthKey('activate_ra;'.$email.';'.$timestamp, $auth)) {
				throw new OIDplusException(_L('Invalid auth key'));
			}

			if ((OIDplus::config()->getValue('max_ra_invite_time') > 0) && (time()-$timestamp > OIDplus::config()->getValue('max_ra_invite_time'))) {
				throw new OIDplusException(_L('Invitation expired!'));
			}

			if ($password1 !== $password2) {
				throw new OIDplusException(_L('Passwords do not match'));
			}

			if (strlen($password1) < OIDplus::config()->getValue('ra_min_password_length')) {
				$minlen = OIDplus::config()->getValue('ra_min_password_length');
				throw new OIDplusException(_L('Password is too short. Need at least %1 characters',$minlen));
			}

			OIDplus::logger()->log("[OK]RA($email)!", "RA '$email' has been registered due to invitation");

			$ra = new OIDplusRA($email);
			$ra->register_ra($password1);

			return array("status" => 0);
		} else {
			throw new OIDplusException(_L('Unknown action ID'));
		}
	}

	public function init($html=true) {
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

	public function gui($id, &$out, &$handled) {
		if (explode('$',$id)[0] == 'oidplus:invite_ra') {
			$handled = true;

			$email = explode('$',$id)[1];
			$origin = explode('$',$id)[2];

			$out['title'] = _L('Invite a Registration Authority');

			if (!OIDplus::config()->getValue('ra_invitation_enabled')) {
				$out['icon'] = 'img/error.png';
				$out['text'] = '<p>'._L('Invitations are disabled by the administrator.').'</p>';
				return;
			}

			$out['icon'] = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/invite_icon.png';

			try {
				$this->inviteSecurityCheck($email);
				$cont = $this->getInvitationText($email);

				$out['text'] .= '<p>'._L('You have chosen to invite %1 as a Registration Authority. If you click "Send", the following email will be sent to %2:','<b>'.$email.'</b>',$email).'</p><p><i>'.nl2br(htmlentities($cont)).'</i></p>
				  <form id="inviteForm" action="javascript:void(0);" onsubmit="return OIDplusPageRaInvite.inviteFormOnSubmit();">
				    <input type="hidden" id="email" value="'.htmlentities($email).'"/>
				    <input type="hidden" id="origin" value="'.htmlentities($origin).'"/>
				    '.OIDplus::getActiveCaptchaPlugin()->captchaGenerate().'
				    <br>
				    <input type="submit" value="'._L('Send invitation').'">
				  </form>';

			} catch (\Exception $e) {

				$out['icon'] = 'img/error.png';
				$out['text'] = _L('Error: %1',$e->getMessage());

			}
		} else if (explode('$',$id)[0] == 'oidplus:activate_ra') {
			$handled = true;

			$email = explode('$',$id)[1];
			$timestamp = explode('$',$id)[2];
			$auth = explode('$',$id)[3];

			$out['title'] = _L('Register as Registration Authority');

			if (!OIDplus::config()->getValue('ra_invitation_enabled')) {
				$out['icon'] = 'img/error.png';
				$out['text'] = '<p>'._L('Invitations are disabled by the administrator.').'</p>';
				return;
			}

			$out['icon'] = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/activate_icon.png';

			$res = OIDplus::db()->query("select * from ###ra where email = ?", array($email));
			if ($res->any()) {
				$out['text'] = _L('This RA is already registered and does not need to be invited.');
			} else {
				if (!OIDplus::authUtils()->validateAuthKey('activate_ra;'.$email.';'.$timestamp, $auth)) {
					$out['icon'] = 'img/error.png';
					$out['text'] = _L('Invalid authorization. Is the URL OK?');
				} else {
					// TODO: like in the FreeOID plugin, we could ask here at least for a name for the RA
					$out['text'] = '<p>'._L('E-Mail-Address').': <b>'.$email.'</b></p>

					  <form id="activateRaForm" action="javascript:void(0);" onsubmit="return OIDplusPageRaInvite.activateRaFormOnSubmit();">
					    <input type="hidden" id="email" value="'.htmlentities($email).'"/>
					    <input type="hidden" id="timestamp" value="'.htmlentities($timestamp).'"/>
					    <input type="hidden" id="auth" value="'.htmlentities($auth).'"/>
					    <div><label class="padding_label">'._L('New password').':</label><input type="password" id="password1" value=""/></div>
					    <div><label class="padding_label">'._L('Repeat').':</label><input type="password" id="password2" value=""/></div>
					    <br><input type="submit" value="'._L('Register').'">
					  </form>';
				}
			}
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		//if (!$ra_email) return false;
		//if (!OIDplus::authUtils()->isRaLoggedIn($ra_email) && !OIDplus::authUtils()->isAdminLoggedIn()) return false;

		return false;
	}

	private function inviteSecurityCheck($email) {
		$res = OIDplus::db()->query("select * from ###ra where email = ?", array($email));
		if ($res->any()) {
			throw new OIDplusException(_L('This RA is already registered and does not need to be invited.'));
		}

		if (!OIDplus::authUtils()->isAdminLoggedIn()) {
			// Check if the RA may invite the user (i.e. the they are the parent of an OID of that person)
			$ok = false;
			$res = OIDplus::db()->query("select parent from ###objects where ra_email = ?", array($email));
			while ($row = $res->fetch_array()) {
				$objParent = OIDplusObject::parse($row['parent']);
				if (is_null($objParent)) throw new OIDplusException(_L('Type of %1 unknown',$row['parent']));
				if ($objParent->userHasWriteRights()) {
					$ok = true;
				}
			}
			if (!$ok) {
				throw new OIDplusException(_L('You may not invite this RA. Maybe you need to <a %1>log in</a> again.',OIDplus::gui()->link('oidplus:login')));
			}
		}
	}

	private function getInvitationText($email) {
		$list_of_oids = array();
		$res = OIDplus::db()->query("select id from ###objects where ra_email = ?", array($email));
		while ($row = $res->fetch_array()) {
			$list_of_oids[] = $row['id'];
		}

		$message = file_get_contents(__DIR__ . '/invite_msg.tpl');

		// Resolve stuff
		$message = str_replace('{{SYSTEM_URL}}', OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL), $message);
		$message = str_replace('{{OID_LIST}}', implode("\n", $list_of_oids), $message);
		$message = str_replace('{{ADMIN_EMAIL}}', OIDplus::config()->getValue('admin_email'), $message);
		$message = str_replace('{{PARTY}}', OIDplus::authUtils()->isAdminLoggedIn() ? 'the system administrator' : 'a superior Registration Authority', $message);

		// {{ACTIVATE_URL}} will be resolved in ajax.php

		return $message;
	}

	public function tree_search($request) {
		return false;
	}
}
