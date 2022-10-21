<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2022 Daniel Marschall, ViaThinkSoft
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

	/*public*/ const RECAPTCHA_V2_CHECKBOX  = 1;
	/*public*/ const RECAPTCHA_V2_INVISIBLE = 2;
	/*public*/ const RECAPTCHA_V3           = 3;

	public static function id(): string {
		return 'reCAPTCHA'; // TODO: Now it is called "reCAPTCHA"
	}

	public function isVisible(): bool {
		return OIDplus::baseConfig()->getValue('RECAPTCHA_VERSION', self::RECAPTCHA_V2_CHECKBOX) == self::RECAPTCHA_V2_CHECKBOX;
	}

	public function captchaDomHead() {
		// Here you can add styles and scripts to be included into the HTML <head> part
	}

	public function captchaGenerate($header_text=null, $footer_text=null) {
		return '<noscript>'.
		       '<p><font color="red">'._L('You need to enable JavaScript to solve the CAPTCHA.').'</font></p>'.
		       '</noscript>'.
		       (!$this->isVisible() || !$header_text ? '' : '<p>'.$header_text.'</p>').
		       '<div id="recaptcha"></div>'.
		       '<input type="hidden" id="oidplus-recaptcha-response" name="oidplus-recaptcha-response">'.
		       '<script>'.
//		       '    $("form").submit(function(e){'.
//		       // TODO: The form must not be submitted before recaptchaFinished() is called!
//		       '         event.preventDefault();'.
//		       '    });'.
		       '    var recaptchaLoaded = function() {'.
		       '        console.log("reCAPTCHA ready");'.
		       '        grecaptcha.render("recaptcha", {'.
		       '            "sitekey": "'.OIDplus::baseConfig()->getValue('RECAPTCHA_PUBLIC', '').'",'.
		       ($this->isVisible() ? '' :
		       '            "size": "invisible",').
		       '            "callback": function (token) {'. // TODO: also 'expired-callback' and 'error-callback'
		       '                console.log("reCAPTCHA solved");'.
		       '                document.getElementById("oidplus-recaptcha-response").value = token;'.
		       '            }'.
		       '        });'.
		       ($this->isVisible() ? '' :
                       '        grecaptcha.execute();').
		       '    };'.
		       '    var oidplus_captcha_response = function() {'.
		       '        return document.getElementById("oidplus-recaptcha-response").value;'.
		       '    };'.
		       '    var oidplus_captcha_reset = function() {'.
		       '        grecaptcha.reset();'.
		       ($this->isVisible() ? '' :
		       '        grecaptcha.execute();').
		       '    };'.
		       '</script>'.
		       '<script src="https://www.google.com/recaptcha/api.js?onload=recaptchaLoaded&render=explicit" async defer></script>'.
		       (!$this->isVisible() || !$footer_text ? '' : '<p>'.$footer_text.'</p>');
	}

	public function captchaVerify($params, $fieldname=null) {
		$secret=OIDplus::baseConfig()->getValue('RECAPTCHA_PRIVATE', '');

		if (is_null($fieldname)) $fieldname = 'oidplus-recaptcha-response'; // no individual AJAX field name (created by oidplus_captcha_response()) means that it is a plain POST event (e.g. by oobe.php)
		_CheckParamExists($params, $fieldname);
		$response=$params[$fieldname];

		$verify=url_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.urlencode($secret).'&response='.urlencode($response).'&remoteip='.urlencode($_SERVER['REMOTE_ADDR']));
		if (!$verify) {
			throw new OIDplusException(_L('CAPTCHA not successfully verified').' (Web request failed)');
		}
		$captcha_success=@json_decode($verify);
		$SCORE_THRESHOLD = 0.5; // TODO: Make Score configurable (only V3)
		if (!$captcha_success) {
			throw new OIDplusException(_L('CAPTCHA not successfully verified').' (JSON Decode failed)');
		}
		if ($captcha_success->success==false) {
			throw new OIDplusException(_L('CAPTCHA not successfully verified').' (Failed)');
		}
		if (isset($captcha_success->score) && ($captcha_success->score < $SCORE_THRESHOLD)) {
			throw new OIDplusException(_L('CAPTCHA not successfully verified').' (Score '.($captcha_success->score).' too low)');
		}
	}

	public static function setupHTML(): string {
		return '<div id="CAPTCHAPLUGIN_PARAMS_RECAPTCHA">'.
		       '<p>(<a href="https://developers.google.com/recaptcha/intro" target="_blank">'._L('more information and obtain key').'</a>)</p>'.
		       '<p>'._L('reCAPTCHA Version').'<br><select id="recaptcha_version">'.
		       '    <option name="OIDplusCaptchaPluginRecaptcha::RECAPTCHA_V2_CHECKBOX">reCAPTCHA V2 Checkbox</option>'.
		       '    <option name="OIDplusCaptchaPluginRecaptcha::RECAPTCHA_V2_INVISIBLE">reCAPTCHA V2 Invisible</option>'.
		       '    <option name="OIDplusCaptchaPluginRecaptcha::RECAPTCHA_V3">reCAPTCHA V3</option>'.
		       '</select></p>'.
		       '<p>'._L('reCAPTCHA Public key').'<br><input id="recaptcha_public" type="text" onkeypress="rebuild()" onkeyup="rebuild()"> <span id="recaptcha_public_warn"></span></p>'.
		       '<p>'._L('reCAPTCHA Private key').'<br><input id="recaptcha_private" type="text" onkeypress="rebuild()" onkeyup="rebuild()"> <span id="recaptcha_private_warn"></span></p>'.
		       '</div>';
	}

	function httpHeaderCheck(&$http_headers) {
		$http_headers["Content-Security-Policy"]["script-src"][] = "https://www.google.com/";
		$http_headers["Content-Security-Policy"]["script-src"][] = "https://www.gstatic.com/";
		$http_headers["Content-Security-Policy"]["img-src"][]    = "https://www.google.com/";
		$http_headers["Content-Security-Policy"]["img-src"][]    = "https://www.gstatic.com/";
		$http_headers["Content-Security-Policy"]["frame-src"][]  = "https://www.google.com/";
		$http_headers["Content-Security-Policy"]["frame-src"][]  = "https://www.gstatic.com/";
	}

}
