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

class OIDplusCaptchaPluginRecaptcha extends OIDplusCaptchaPlugin
	implements INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8  /* getNotifications */
{

	/**
	 *
	 */
	/*public*/ const RECAPTCHA_V2_CHECKBOX  = 1;

	/**
	 *
	 */
	/*public*/ const RECAPTCHA_V2_INVISIBLE = 2;

	/**
	 *
	 */
	/*public*/ const RECAPTCHA_V3           = 3;

	/**
	 * @return string
	 */
	public static function id(): string {
		return 'reCAPTCHA';
	}

	/**
	 * @return bool
	 * @throws OIDplusException
	 */
	public function isVisible(): bool {
		return OIDplus::baseConfig()->getValue('RECAPTCHA_VERSION', self::RECAPTCHA_V2_CHECKBOX) == self::RECAPTCHA_V2_CHECKBOX;
	}

	/**
	 * @param string|null $header_text
	 * @param string|null $footer_text
	 * @return string
	 * @throws OIDplusException
	 */
	public function captchaGenerate(string $header_text=null, string $footer_text=null): string {
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

	/**
	 * @param array $params
	 * @param string|null $fieldname
	 * @return void
	 * @throws OIDplusException
	 */
	public function captchaVerify(array $params, string $fieldname=null) {
		$secret=OIDplus::baseConfig()->getValue('RECAPTCHA_PRIVATE', '');

		if (is_null($fieldname)) $fieldname = 'oidplus-recaptcha-response'; // no individual AJAX field name (created by oidplus_captcha_response()) means that it is a plain POST event (e.g. by oobe.php)
		_CheckParamExists($params, $fieldname);
		$response=$params[$fieldname];

		$verify=url_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.urlencode($secret).'&response='.urlencode($response).'&remoteip='.urlencode($_SERVER['REMOTE_ADDR']));
		if ($verify === false) {
			throw new OIDplusException(_L('CAPTCHA not successfully verified').' (Web request failed)');
		}
		$captcha_success=@json_decode($verify);
		$SCORE_THRESHOLD = 0.5; // TODO: Make Score configurable (only V3)
		if (!$captcha_success) {
			throw new OIDplusException(_L('CAPTCHA not successfully verified').' ('._L('JSON Decode failed').')');
		}
		if (!$captcha_success->success) {
			throw new OIDplusException(_L('CAPTCHA not successfully verified').' ('._L('Failed').')');
		}
		if (isset($captcha_success->score) && ($captcha_success->score < $SCORE_THRESHOLD)) {
			throw new OIDplusException(_L('CAPTCHA not successfully verified').' ('._L('Score %1 too low', $captcha_success->score).')');
		}
	}

	/**
	 * @return string
	 */
	public static function setupHTML(): string {
		$curl_status = url_get_contents_available(true, $reason) ? 1 : 0;
		return '<div id="CAPTCHAPLUGIN_PARAMS_RECAPTCHA">'.
		       '<p>(<a href="https://developers.google.com/recaptcha/intro" target="_blank">'._L('more information and obtain key').'</a>)</p>'.
		       '<p>'._L('reCAPTCHA Version').'<br><select id="recaptcha_version">'.
		       // Note: JavaScript will add "\ViaThinkSoft\OIDplus\OIDplusCaptchaPluginRecaptcha::" in front of the name
		       '    <option name="RECAPTCHA_V2_CHECKBOX">reCAPTCHA V2 Checkbox</option>'.
		       '    <option name="RECAPTCHA_V2_INVISIBLE">reCAPTCHA V2 Invisible</option>'.
		       '    <option name="RECAPTCHA_V3">reCAPTCHA V3</option>'.
		       '</select></p>'.
		       '<p>'._L('reCAPTCHA Public key').'<br><input id="recaptcha_public" type="text" onkeypress="rebuild()" onkeyup="rebuild()"> <span id="recaptcha_public_warn"></span></p>'.
		       '<p>'._L('reCAPTCHA Private key').'<br><input id="recaptcha_private" type="text" onkeypress="rebuild()" onkeyup="rebuild()"> <span id="recaptcha_private_warn"></span></p>'.
		       (!$curl_status ? '<p><font color="red">'._L('The %1 plugin cannot connect to the Internet.', self::id()).' '.$reason.'</font></p>' : '').
		       '</div>';
	}

	/**
	 * @param array $http_headers
	 * @return void
	 */
	function httpHeaderCheck(array &$http_headers) {
		$http_headers["Content-Security-Policy"]["script-src"][] = "https://www.google.com/";
		$http_headers["Content-Security-Policy"]["script-src"][] = "https://www.gstatic.com/";
		$http_headers["Content-Security-Policy"]["img-src"][]    = "https://www.google.com/";
		$http_headers["Content-Security-Policy"]["img-src"][]    = "https://www.gstatic.com/";
		$http_headers["Content-Security-Policy"]["frame-src"][]  = "https://www.google.com/";
		$http_headers["Content-Security-Policy"]["frame-src"][]  = "https://www.gstatic.com/";
	}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8
	 * @param string|null $user
	 * @return array
	 * @throws OIDplusException
	 */
	public function getNotifications(string $user=null): array {
		$notifications = array();
		if ((!$user || ($user == 'admin')) && OIDplus::authUtils()->isAdminLoggedIn()) {
			if ($this->isActive()) {
				if (!url_get_contents_available(true, $reason)) {
					$notifications[] = new OIDplusNotification('CRIT', _L('CAPTCHA plugin "%1" is active, but OIDplus cannot connect to the Internet. Users will not be able to log in!', htmlentities(self::id())) . ' ' . $reason);
				}
			}
		}
		return $notifications;
	}

}
