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

class OIDplusPagePublicFreeOID extends OIDplusPagePluginPublic {

	private static function getFreeRootOid($with_ns) {
		return ($with_ns ? 'oid:' : '').OIDplus::config()->getValue('freeoid_root_oid');
	}

	public function action($actionID, $params) {
		if (empty(self::getFreeRootOid(false))) throw new OIDplusException(_L('FreeOID service not available. Please ask your administrator.'));

		if ($actionID == 'request_freeoid') {
			$email = $params['email'];

			$res = OIDplus::db()->query("select * from ###ra where email = ?", array($email));
			if ($res->num_rows() > 0) {
				throw new OIDplusException(_L('This email address already exists.')); // TODO: actually, the person might have something else (like a DOI) and want to have a FreeOID
			}

			if (!OIDplus::mailUtils()->validMailAddress($email)) {
				throw new OIDplusException(_L('Invalid email address'));
			}

			if (OIDplus::baseConfig()->getValue('RECAPTCHA_ENABLED', false)) {
				$secret=OIDplus::baseConfig()->getValue('RECAPTCHA_PRIVATE', '');
				$response=$params["captcha"];
				$verify=file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret}&response={$response}");
				$captcha_success=json_decode($verify);
				if ($captcha_success->success==false) {
					throw new OIDplusException(_L('CAPTCHA not successfully verified'));
				}
			}

			$root_oid = self::getFreeRootOid(false);
			OIDplus::logger()->log("[INFO]OID(oid:$root_oid)+RA($email)!", "Requested a free OID for email '$email' to be placed into root '$root_oid'");

			$timestamp = time();
			$activate_url = OIDplus::webpath(null,false) . '?goto='.urlencode('oidplus:com.viathinksoft.freeoid.activate_freeoid$'.$email.'$'.$timestamp.'$'.OIDplus::authUtils()::makeAuthKey('com.viathinksoft.freeoid.activate_freeoid;'.$email.';'.$timestamp));

			$message = file_get_contents(__DIR__ . '/request_msg.tpl');
			$message = str_replace('{{SYSTEM_URL}}', OIDplus::webpath(null,false), $message);
			$message = str_replace('{{SYSTEM_TITLE}}', OIDplus::config()->getValue('system_title'), $message);
			$message = str_replace('{{ADMIN_EMAIL}}', OIDplus::config()->getValue('admin_email'), $message);
			$message = str_replace('{{ACTIVATE_URL}}', $activate_url, $message);
			OIDplus::mailUtils()->sendMail($email, OIDplus::config()->getValue('system_title').' - Free OID request', $message, OIDplus::config()->getValue('global_cc'));

			return array("status" => 0);

		} else if ($actionID == 'activate_freeoid') {

			$password1 = $params['password1'];
			$password2 = $params['password2'];
			$email = $params['email'];

			$ra_name = $params['ra_name'];
			$url = $params['url'];
			$title = $params['title'];

			$auth = $params['auth'];
			$timestamp = $params['timestamp'];

			if (!OIDplus::authUtils()::validateAuthKey('com.viathinksoft.freeoid.activate_freeoid;'.$email.';'.$timestamp, $auth)) {
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

			if (empty($ra_name)) {
				throw new OIDplusException(_L('Please enter your personal name or the name of your group.'));
			}

			// 1. step: Add the RA to the database

			$ra = new OIDplusRA($email);
			$ra->register_ra($password1);
			$ra->setRaName($ra_name);

			// 2. step: Add the new OID to the database

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
				if ('oid:'.$new_oid > OIDplus::baseConfig()->getValue('LIMITS_MAX_ID_LENGTH')) {
					$maxlen = OIDplus::baseConfig()->getValue('LIMITS_MAX_ID_LENGTH')-strlen('oid:');
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
			$message .= "More details: ".OIDplus::webpath(null,false)."?goto=oid:$new_oid\n";

			OIDplus::mailUtils()->sendMail($email, OIDplus::config()->getValue('system_title')." - OID $new_oid registered", $message, OIDplus::config()->getValue('global_cc'));

			// Send delegation information to user

			$message = file_get_contents(__DIR__ . '/allocated_msg.tpl');
			$message = str_replace('{{SYSTEM_URL}}', OIDplus::webpath(null,false), $message);
			$message = str_replace('{{SYSTEM_TITLE}}', OIDplus::config()->getValue('system_title'), $message);
			$message = str_replace('{{ADMIN_EMAIL}}', OIDplus::config()->getValue('admin_email'), $message);
			$message = str_replace('{{NEW_OID}}', $new_oid, $message);
			OIDplus::mailUtils()->sendMail($email, OIDplus::config()->getValue('system_title').' - Free OID allocated', $message, OIDplus::config()->getValue('global_cc'));

			return array("status" => 0);
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
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';

			$highest_id = $this->freeoid_max_id();

			$out['text'] .= '<p>'._L('Currently <a %1>%2 free OIDs have been</a> registered. Please enter your email below to receive a free OID.',OIDplus::gui()->link(self::getFreeRootOid(true)),$highest_id).'</p>';

			try {
				$out['text'] .= '
				  <form id="freeOIDForm" action="javascript:void(0);" onsubmit="return freeOIDFormOnSubmit();">
				    '._L('E-Mail').': <input type="text" id="email" value=""/><br><br>'.
				 (OIDplus::baseConfig()->getValue('RECAPTCHA_ENABLED', false) ?
				 '<script> grecaptcha.render(document.getElementById("g-recaptcha"), { "sitekey" : "'.OIDplus::baseConfig()->getValue('RECAPTCHA_PUBLIC', '').'" }); </script>'.
				 '<div id="g-recaptcha" class="g-recaptcha" data-sitekey="'.OIDplus::baseConfig()->getValue('RECAPTCHA_PUBLIC', '').'"></div>' : '').
				' <br>
				    <input type="submit" value="'._L('Request free OID').'">
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
				$tos .= $html;

				$tos = str_replace('{{ADMIN_EMAIL}}', OIDplus::config()->getValue('admin_email'), $tos);
				if ($obj) {
					$tos = str_replace('{{ROOT_OID}}', $obj->getDotNotation(), $tos);
					$tos = str_replace('{{ROOT_OID_ASN1}}', $obj->getAsn1Notation(), $tos);
					$tos = str_replace('{{ROOT_OID_IRI}}', $obj->getIriNotation(), $tos);
				}
				$out['text'] .= $tos;
			} catch (Exception $e) {
				$out['text'] = _L('Error: %1',$e->getMessage());
			}
		} else if (explode('$',$id)[0] == 'oidplus:com.viathinksoft.freeoid.activate_freeoid') {
			$handled = true;

			$email = explode('$',$id)[1];
			$timestamp = explode('$',$id)[2];
			$auth = explode('$',$id)[3];

			$out['title'] = _L('Activate Free OID');
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';

			$res = OIDplus::db()->query("select * from ###ra where email = ?", array($email));
			if ($res->num_rows() > 0) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = _L('This RA is already registered.'); // TODO: actually, the person might have something else (like a DOI) and want to have a FreeOID
			} else {
				if (!OIDplus::authUtils()::validateAuthKey('com.viathinksoft.freeoid.activate_freeoid;'.$email.';'.$timestamp, $auth)) {
					$out['icon'] = 'img/error_big.png';
					$out['text'] = _L('Invalid authorization. Is the URL OK?');
				} else {
					$out['text'] = '<p>'._L('eMail-Address').': <b>'.$email.'</b></p>

				  <form id="activateFreeOIDForm" action="javascript:void(0);" onsubmit="return activateFreeOIDFormOnSubmit();">
				    <input type="hidden" id="email" value="'.htmlentities($email).'"/>
				    <input type="hidden" id="timestamp" value="'.htmlentities($timestamp).'"/>
				    <input type="hidden" id="auth" value="'.htmlentities($auth).'"/>

				    '._L('Your personal name or the name of your group').':<br><input type="text" id="ra_name" value=""/><br><br><!-- TODO: disable autocomplete -->
				    '._L('Title of your OID (usually equal to your name, optional)').':<br><input type="text" id="title" value=""/><br><br>
				    '._L('URL for more information about your project(s) (optional)').':<br><input type="text" id="url" value=""/><br><br>

				    <div><label class="padding_label">'._L('Password').':</label><input type="password" id="password1" value=""/></div>
				    <div><label class="padding_label">'._L('Repeat').':</label><input type="password" id="password2" value=""/></div>
				    <br><input type="submit" value="'._L('Register').'">
				  </form>';
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

		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = OIDplus::webpath(__DIR__).'treeicon.png';
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
