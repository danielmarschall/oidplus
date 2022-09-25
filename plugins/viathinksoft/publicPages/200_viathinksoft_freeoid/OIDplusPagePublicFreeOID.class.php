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

class OIDplusPagePublicFreeOID extends OIDplusPagePluginPublic {

	private static function getFreeRootOid($with_ns) {
		return ($with_ns ? 'oid:' : '').OIDplus::config()->getValue('freeoid_root_oid');
	}

	public static function alreadyHasFreeOid($email, $getId = false){
		$res = OIDplus::db()->query("select id from ###objects where ra_email = ? and id like ? order by ".OIDplus::db()->natOrder('id'), array($email, self::getFreeRootOid(true).'.%'));
		while ($row = $res->fetch_array()) {
			return $getId ? $row['id'] : true;
		}
		return $getId ? null : false;
	}

	public function action($actionID, $params) {
		if (empty(self::getFreeRootOid(false))) throw new OIDplusException(_L('FreeOID service not available. Please ask your administrator.'));

		if ($actionID == 'request_freeoid') {
			_CheckParamExists($params, 'email');
			$email = $params['email'];

			if ($already_registered_oid = $this->alreadyHasFreeOid($email, true)) {
				throw new OIDplusException(_L('This email address already has a FreeOID registered (%1)', $already_registered_oid));
			}

			if (!OIDplus::mailUtils()->validMailAddress($email)) {
				throw new OIDplusException(_L('Invalid email address'));
			}

			OIDplus::getActiveCaptchaPlugin()->captchaVerify($params, 'captcha');

			$root_oid = self::getFreeRootOid(false);
			OIDplus::logger()->log("[INFO]OID(oid:$root_oid)+RA($email)!", "Requested a free OID for email '$email' to be placed into root '$root_oid'");

			$timestamp = time();
			$activate_url = OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL) . '?goto='.urlencode('oidplus:com.viathinksoft.freeoid.activate_freeoid$'.$email.'$'.$timestamp.'$'.OIDplus::authUtils()->makeAuthKey('com.viathinksoft.freeoid.activate_freeoid;'.$email.';'.$timestamp));

			$message = file_get_contents(__DIR__ . '/request_msg.tpl');
			$message = str_replace('{{SYSTEM_URL}}', OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL), $message);
			$message = str_replace('{{SYSTEM_TITLE}}', OIDplus::config()->getValue('system_title'), $message);
			$message = str_replace('{{ADMIN_EMAIL}}', OIDplus::config()->getValue('admin_email'), $message);
			$message = str_replace('{{ACTIVATE_URL}}', $activate_url, $message);

			OIDplus::mailUtils()->sendMail($email, OIDplus::config()->getValue('system_title').' - Free OID request', $message);

			return array("status" => 0);

		} else if ($actionID == 'activate_freeoid') {
			_CheckParamExists($params, 'email');
			_CheckParamExists($params, 'auth');
			_CheckParamExists($params, 'timestamp');

			$email = $params['email'];
			$auth = $params['auth'];
			$timestamp = $params['timestamp'];

			if (!OIDplus::authUtils()->validateAuthKey('com.viathinksoft.freeoid.activate_freeoid;'.$email.';'.$timestamp, $auth)) {
				throw new OIDplusException(_L('Invalid auth key'));
			}

			if ((OIDplus::config()->getValue('max_ra_invite_time') > 0) && (time()-$timestamp > OIDplus::config()->getValue('max_ra_invite_time'))) {
				throw new OIDplusException(_L('Invitation expired!'));
			}

			// 1. step: Check entered data and add the RA to the database

			$ra = new OIDplusRA($email);
			if (!$ra->existing()) {
				_CheckParamExists($params, 'password1');
				_CheckParamExists($params, 'password2');
				_CheckParamExists($params, 'ra_name');

				$password1 = $params['password1'];
				$password2 = $params['password2'];
				$ra_name = $params['ra_name'];

				if ($password1 !== $password2) {
					throw new OIDplusException(_L('Passwords do not match'));
				}

				if (strlen($password1) < OIDplus::config()->getValue('ra_min_password_length')) {
					$minlen = OIDplus::config()->getValue('ra_min_password_length');
					throw new OIDplusException(_L('Password is too short. Need at least %1 characters',$minlen));
				}

				if (empty($ra_name)) {
					throw new OIDplusException(_L('Please enter your personal name or the name of your group.'));
				}

				$ra->register_ra($password1);
				$ra->setRaName($ra_name);
			} else {
				// RA already exists (e.g. was logged in using Google OAuth)
				$ra_name = $ra->raName();
			}

			// 2. step: Add the new OID to the database

			$url = isset($params['url']) ? $params['url'] : '';
			$title = isset($params['title']) ? $params['title'] : '';

			$root_oid = self::getFreeRootOid(false);
			$new_oid = OIDplusOid::parse('oid:'.$root_oid)->appendArcs($this->freeoid_max_id()+1)->nodeId(false);

			OIDplus::logger()->log("[INFO]OID(oid:$root_oid)+OIDRA(oid:$root_oid)!", "Child OID '$new_oid' added automatically by '$email' (RA Name: '$ra_name')");
			OIDplus::logger()->log("[INFO]OID(oid:$new_oid)+[OK]RA($email)!",            "Free OID '$new_oid' activated (RA Name: '$ra_name')");

			if ((!empty($url)) && (substr($url, 0, 4) != 'http')) $url = 'http://'.$url;

			$description = ''; // '<p>'.htmlentities($ra_name).'</p>';
			if (!empty($url)) {
				$description .= '<p>'._L('More information at %1','<a href="'.htmlentities($url).'">'.htmlentities($url).'</a>').'</p>';
			}

			if (empty($title)) $title = $ra_name;

			try {
				$maxlen = OIDplus::baseConfig()->getValue('LIMITS_MAX_ID_LENGTH')-strlen('oid:');
				if (strlen($new_oid) > $maxlen) {
					throw new OIDplusException(_L('The resulting OID %1 is too long (max allowed length: %2)',$new_oid,$maxlen));
				}

				OIDplus::db()->query("insert into ###objects (id, ra_email, parent, title, description, confidential, created) values (?, ?, ?, ?, ?, ?, ".OIDplus::db()->sqlDate().")", array('oid:'.$new_oid, $email, self::getFreeRootOid(true), $title, $description, false));
			} catch (Exception $e) {
				$ra->delete();
				throw $e;
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
			$message .= "More details: ".OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL)."?goto=oid%3A$new_oid\n";

			OIDplus::mailUtils()->sendMail($email, OIDplus::config()->getValue('system_title')." - OID $new_oid registered", $message);

			// Send delegation information to user

			$message = file_get_contents(__DIR__ . '/allocated_msg.tpl');
			$message = str_replace('{{SYSTEM_URL}}', OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL), $message);
			$message = str_replace('{{SYSTEM_TITLE}}', OIDplus::config()->getValue('system_title'), $message);
			$message = str_replace('{{ADMIN_EMAIL}}', OIDplus::config()->getValue('admin_email'), $message);
			$message = str_replace('{{NEW_OID}}', $new_oid, $message);
			OIDplus::mailUtils()->sendMail($email, OIDplus::config()->getValue('system_title').' - Free OID allocated', $message);

			return array(
				"new_oid" => $new_oid,
				"status" => 0
			);
		} else {
			throw new OIDplusException(_L('Unknown action ID'));
		}
	}

	public function init($html=true) {
		OIDplus::config()->prepareConfigKey('freeoid_root_oid', 'Root-OID of free OID service (a service where visitors can create their own OID using email verification)', '', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (($value != '') && !oid_valid_dotnotation($value,false,false,1)) {
				throw new OIDplusException(_L('Please enter a valid OID in dot notation or nothing'));
			}
		});
	}

	public function gui($id, &$out, &$handled) {
		if (empty(self::getFreeRootOid(false))) return;

		if (explode('$',$id)[0] == 'oidplus:com.viathinksoft.freeoid') {
			$handled = true;

			$out['title'] = _L('Register a free OID');
			$out['icon'] = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

			// Note: We are using the highest OID instead of the rowcount, because there might be OIDs which could have been deleted in between
			$highest_id = $this->freeoid_max_id();

			$out['text'] .= '<p>'._L('Currently <a %1>%2 free OIDs have been</a> registered. Please enter your email below to receive a free OID.',OIDplus::gui()->link(self::getFreeRootOid(true)),$highest_id).'</p>';

			try {
				$out['text'] .= '
				  <form id="freeOIDForm" action="javascript:void(0);" onsubmit="return OIDplusPagePublicFreeOID.freeOIDFormOnSubmit();">
				    '._L('E-Mail').': <input type="text" id="email" value=""/><br><br>
				    '.OIDplus::getActiveCaptchaPlugin()->captchaGenerate().'
				    <br>
				    <input type="submit" value="'._L('Request a free OID').'">
				  </form>';

				$obj = OIDplusOid::parse(self::getFreeRootOid(true));

				if (file_exists(__DIR__ . '/tos$'.OIDplus::getCurrentLang().'.html')) {
					$tos = file_get_contents(__DIR__ . '/tos$'.OIDplus::getCurrentLang().'.html');
				} else {
					$tos = file_get_contents(__DIR__ . '/tos.html');
				}

				list($html, $js, $css) = extractHtmlContents($tos);
				$tos = '';
				if (!empty($js))  $tos .= "<script>\n$js\n</script>";
				if (!empty($css)) $tos .= "<style>\n$css\n</style>";
				$tos .= stripHtmlComments($html);

				$tos = str_replace('{{ADMIN_EMAIL}}', OIDplus::config()->getValue('admin_email'), $tos);
				if ($obj) {
					$tos = str_replace('{{ROOT_OID}}', $obj->getDotNotation(), $tos);
					$tos = str_replace('{{ROOT_OID_ASN1}}', $obj->getAsn1Notation(), $tos);
					$tos = str_replace('{{ROOT_OID_IRI}}', $obj->getIriNotation(), $tos);
				}
				$out['text'] .= $tos;

				if (OIDplus::config()->getValue('freeoid_root_oid') == '1.3.6.1.4.1.37476.9000') {
					$out['text'] .= '<p>'._L('<b>Note:</b> Since September 2022, owners of FreeOID automatically receive a free ISO-7816 compliant <b>Application Identifier</b> (AID) with the format <code>D2:76:00:01:86:F0:(FreeOID):FF:(PIX)</code> (up to 64 bits application specific PIX, depending on the length of the FreeOID number).');
$out['text'] .= ' - <a '.OIDplus::gui()->link('aid:D276000186F1').'>'._L('More information').'</a></p>';
				}
			} catch (Exception $e) {
				$out['text'] = _L('Error: %1',$e->getMessage());
			}
		} else if (explode('$',$id)[0] == 'oidplus:com.viathinksoft.freeoid.activate_freeoid') {
			$handled = true;

			$email = explode('$',$id)[1];
			$timestamp = explode('$',$id)[2];
			$auth = explode('$',$id)[3];

			$out['title'] = _L('Activate Free OID');
			$out['icon'] = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

			if ($already_registered_oid = $this->alreadyHasFreeOid($email, true)) {
				throw new OIDplusException(_L('This email address already has a FreeOID registered (%1)', $already_registered_oid));
			} else {
				if (!OIDplus::authUtils()->validateAuthKey('com.viathinksoft.freeoid.activate_freeoid;'.$email.';'.$timestamp, $auth)) {
					$out['icon'] = 'img/error.png';
					$out['text'] = _L('Invalid authorization. Is the URL OK?');
				} else {
					$ra = new OIDplusRA($email);
					$ra_existing = $ra->existing();

					$out['text'] = '<p>'._L('eMail-Address').': <b>'.$email.'</b></p>';

					$out['text'] .= '  <form id="activateFreeOIDForm" action="javascript:void(0);" onsubmit="return OIDplusPagePublicFreeOID.activateFreeOIDFormOnSubmit();">';
					$out['text'] .= '    <input type="hidden" id="email" value="'.htmlentities($email).'"/>';
					$out['text'] .= '    <input type="hidden" id="timestamp" value="'.htmlentities($timestamp).'"/>';
					$out['text'] .= '    <input type="hidden" id="auth" value="'.htmlentities($auth).'"/>';

					if ($ra_existing) {
						$out['text'] .= '    '._L('Your personal name or the name of your group').':<br><b>'.htmlentities($ra->raName()).'</b><br><br>';
					} else {
						$out['text'] .= '    '._L('Your personal name or the name of your group').':<br><input type="text" id="ra_name" value=""/><br><br>'; // TODO: disable autocomplete
					}
					$out['text'] .= '    '._L('Title of your OID (usually equal to your name, optional)').':<br><input type="text" id="title" value=""/><br><br>';
					$out['text'] .= '    '._L('URL for more information about your project(s) (optional)').':<br><input type="text" id="url" value=""/><br><br>';

					if (!$ra_existing) {
						$out['text'] .= '    <div><label class="padding_label">'._L('Password').':</label><input type="password" id="password1" value=""/></div>';
						$out['text'] .= '    <div><label class="padding_label">'._L('Repeat').':</label><input type="password" id="password2" value=""/></div>';
					}
					$out['text'] .= '    <br><input type="submit" value="'._L('Register').'">';
					$out['text'] .= '  </form>';
				}
			}
		}
	}

	public function publicSitemap(&$out) {
		if (empty(self::getFreeRootOid(false))) return;
		$out[] = 'oidplus:com.viathinksoft.freeoid';
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (empty(self::getFreeRootOid(false))) return false;

		if (file_exists(__DIR__.'/img/main_icon16.png')) {
			$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:com.viathinksoft.freeoid',
			'icon' => $tree_icon,
			'text' => _L('Register a free OID')
		);

		return true;
	}

	# ---

	protected static function freeoid_max_id() {
		$res = OIDplus::db()->query("select id from ###objects where id like ? order by ".OIDplus::db()->natOrder('id'), array(self::getFreeRootOid(true).'.%'));
		$highest_id = 0;
		while ($row = $res->fetch_array()) {
			$arc = substr_count(self::getFreeRootOid(false), '.')+1;
			$highest_id = explode('.',$row['id'])[$arc];
		}
		return $highest_id;
	}

	public function tree_search($request) {
		return false;
	}
}
