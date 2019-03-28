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

if (explode('$',$id)[0] == 'oidplus:change_ra_email') {
	$handled = true;
	$out['title'] = 'Change RA email';
	$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/raPages/'.basename(__DIR__).'/icon_big.png' : '';

	$ra_email = explode('$',$id)[1];

	if (!OIDplus::config()->getValue('allow_ra_email_change')) {
		$out['icon'] = 'img/error_big.png';
		$out['text'] .= '<p>This functionality has been disabled by the administrator.</p>';
	} else if (!OIDplus::authUtils()::isRaLoggedIn($ra_email) && !OIDplus::authUtils()::isAdminLoggedIn()) {
		$out['icon'] = 'img/error_big.png';
		$out['text'] .= '<p>You need to <a href="?goto=oidplus:login">log in</a> as the requested RA <b>'.htmlentities($ra_email).'</b>.</p>';
	} else {
		$out['text'] .= '<form id="changeRaEmailForm" onsubmit="return changeRaEmailFormOnSubmit();">';
		$out['text'] .= '<input type="hidden" id="old_email" value="'.htmlentities($ra_email).'"/><br>';
		$out['text'] .= '<label class="padding_label">Old address:</label><b>'.htmlentities($ra_email).'</b><br><br>';
		$out['text'] .= '<label class="padding_label">New address:</label><input type="text" id="new_email" value=""/><br><br>';
		$out['text'] .= '<input type="submit" value="Send new activation email"></form>';
	}
} else if (explode('$',$id)[0] == 'oidplus:activate_new_ra_email') {
	$handled = true;

	$old_email = explode('$',$id)[1];
	$new_email = explode('$',$id)[2];
	$timestamp = explode('$',$id)[3];
	$auth = explode('$',$id)[4];

	if (!OIDplus::config()->getValue('allow_ra_email_change')) {
		$out['icon'] = 'img/error_big.png';
		$out['text'] .= '<p>This functionality has been disabled by the administrator.</p>';
	} else {
		$out['title'] = 'Perform email address change';
		$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/raPages/'.basename(__DIR__).'/icon_big.png' : '';

		$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = '".OIDplus::db()->real_escape_string($old_email)."'");
		if (OIDplus::db()->num_rows($res) == 0) {
			$out['icon'] = 'img/error_big.png';
			$out['text'] = 'eMail address does not exist anymore. It was probably already changed.';
		} else {
			$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = '".OIDplus::db()->real_escape_string($new_email)."'");
			if (OIDplus::db()->num_rows($res) > 0) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = 'eMail address is already used by another RA. To merge accounts, please contact the superior RA of your objects and request an owner change of your objects.';
			} else {
				if (!OIDplus::authUtils()::validateAuthKey('activate_new_ra_email;'.$old_email.';'.$new_email.';'.$timestamp, $auth)) {
					$out['icon'] = 'img/error_big.png';
					$out['text'] = 'Invalid authorization. Is the URL OK?';
				} else {
					$out['text'] = '<p>Old eMail-Address: <b>'.$old_email.'</b></p>
			                <p>New eMail-Address: <b>'.$new_email.'</b></p>

				         <form id="activateNewRaEmailForm" onsubmit="return activateNewRaEmailFormOnSubmit();">
				    <input type="hidden" id="old_email" value="'.htmlentities($old_email).'"/>
				    <input type="hidden" id="new_email" value="'.htmlentities($new_email).'"/>
				    <input type="hidden" id="timestamp" value="'.htmlentities($timestamp).'"/>
				    <input type="hidden" id="auth" value="'.htmlentities($auth).'"/>

				    <label class="padding_label">Please verify your password:</label><input type="password" id="password" value=""/><br><br>
				    <input type="submit" value="Change email address">
				  </form>';
				}
			}
		}
	}
}
