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

class OIDplusCaptchaPluginRecaptcha extends OIDplusCaptchaPlugin {

	public static function id(): string {
		return 'ReCAPTCHA';
	}

	public static function isVisible(): bool {
		// TODO: Also implement Google invisible CAPTCHAs
		return true;
	}

	public function captchaDomHead() {
		// Here you can add styles and scripts to be included into the HTML <head> part
		return '<script>
		function oidplus_captcha_response() {
			return OIDplusCaptchaPluginRecaptcha.captchaResponse();
		}
		function oidplus_captcha_reset() {
			return OIDplusCaptchaPluginRecaptcha.captchaReset();
		}
		</script>
		<script src="https://www.google.com/recaptcha/api.js"></script>';
	}

	public function captchaGenerate($header_text=null, $footer_text=null) {
		return ($header_text ? '<p>'.$header_text.'</p>' : '') .
		       '<noscript>'.
		       '<p><font color="red">'._L('You need to enable JavaScript to solve the CAPTCHA.').'</font></p>'.
		       '</noscript>'.
		       '<div id="g-recaptcha" class="g-recaptcha" data-sitekey="'.OIDplus::baseConfig()->getValue('RECAPTCHA_PUBLIC', '').'"></div>'.
		       //Don't use jQuery, because we might not have included it (e.g. in oobe.php)
		       //'<script> grecaptcha.render($("#g-recaptcha")[0], { "sitekey" : "'.OIDplus::baseConfig()->getValue('RECAPTCHA_PUBLIC', '').'" }); </script>'.
		       // TODO: oobe.php:formatted:42 Uncaught TypeError: grecaptcha.render is not a function at oobe.php:formatted:42 (but it still works?!)
		       '<script> grecaptcha.render(document.getElementById("g-recaptcha"), { "sitekey" : "'.OIDplus::baseConfig()->getValue('RECAPTCHA_PUBLIC', '').'" }); </script>'.
		       ($footer_text ? '<p>'.$footer_text.'</p>' : '');
	}

	public function captchaVerify($params, $fieldname=null) {
		if (is_null($fieldname)) $fieldname = 'g-recaptcha-response'; // no individual field name (created by oidplus_captcha_response()) means that it is a plain POST event (e.g. by oobe.php)

		$secret=OIDplus::baseConfig()->getValue('RECAPTCHA_PRIVATE', '');
		_CheckParamExists($params, $fieldname);
		$response=$params[$fieldname];
		$verify=url_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.urlencode($secret).'&response='.urlencode($response));
		if (!$verify) {
			throw new OIDplusException(_L('CAPTCHA not successfully verified'));
		}
		$captcha_success=json_decode($verify);
		if (!$captcha_success || ($captcha_success->success==false)) {
			throw new OIDplusException(_L('CAPTCHA not successfully verified'));
		}
	}

	public static function setupHTML(): string {
		return '<div id="CAPTCHAPLUGIN_PARAMS_RECAPTCHA">'.
		       '<p>(<a href="https://developers.google.com/recaptcha/intro" target="_blank">'._L('more information and obtain key').'</a>)</p>'.
		       '<p>'._L('reCAPTCHA Public key').'<br><input id="recaptcha_public" type="text" onkeypress="rebuild()" onkeyup="rebuild()"> <span id="recaptcha_public_warn"></span></p>'.
		       '<p>'._L('reCAPTCHA Private key').'<br><input id="recaptcha_private" type="text" onkeypress="rebuild()" onkeyup="rebuild()"> <span id="recaptcha_private_warn"></span></p>'.
		       '</div>';
	}

}
