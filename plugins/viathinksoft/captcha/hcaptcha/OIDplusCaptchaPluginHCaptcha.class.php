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

class OIDplusCaptchaPluginHCaptcha extends OIDplusCaptchaPlugin
	implements INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8  /* getNotifications */
{

	/**
	 * @return string
	 */
	public static function id(): string {
		return 'hCaptcha';
	}

	/**
	 * @return bool
	 */
	public function isVisible(): bool {
		return true;
	}

	/**
	 * @param string|null $header_text
	 * @param string|null $footer_text
	 * @return string
	 * @throws OIDplusException
	 */
	public function captchaGenerate(string $header_text=null, string $footer_text=null): string {
		return ($header_text ? '<p>'.$header_text.'</p>' : '') .
		       '<noscript>'.
		       '<p><font color="red">'._L('You need to enable JavaScript to solve the CAPTCHA.').'</font></p>'.
		       '</noscript>'.
		       '<div id="h-captcha"></div>'.
		       '<script src="https://js.hcaptcha.com/1/api.js"></script>'.
		       '<script>'.
		       'OIDplusCaptchaPluginHCaptcha.captchaShow('.js_escape(OIDplus::baseConfig()->getValue('HCAPTCHA_SITEKEY', '')).')'.
		       '</script>'.
		       ($footer_text ? '<p>'.$footer_text.'</p>' : '');
	}

	/**
	 * @param string[] $params
	 * @param string|null $fieldname
	 * @return void
	 * @throws OIDplusException
	 */
	public function captchaVerify(array $params, string $fieldname=null) {
		$sitekey=OIDplus::baseConfig()->getValue('HCAPTCHA_SITEKEY', '');
		$secret=OIDplus::baseConfig()->getValue('HCAPTCHA_SECRET', '');

		// Yes, it is really "g-recaptcha-response"!
		if (is_null($fieldname)) $fieldname = 'g-recaptcha-response'; // no individual field name (created by oidplus_captcha_response()) means that it is a plain POST event (e.g. by oobe.php)
		_CheckParamExists($params, $fieldname);
		$response=$params[$fieldname];

		$res = url_post_contents(
			'https://hcaptcha.com/siteverify',
			array(
				"secret"   => $secret,
				"response" => $response,
				"remoteip" => OIDplus::getClientIpAddress() ?: '',
				"sitekey"  => $sitekey
			)
		);

		if ($res === false) {
			throw new OIDplusException(_L('Communication with %1 server failed', 'hCaptcha'));
		}

		$captcha_success=@json_decode($res);
		if (!$captcha_success || !$captcha_success->success) {
			throw new OIDplusException(_L('CAPTCHA not successfully verified').' ('.implode(", ",$captcha_success->{'error-codes'}).')');
		}
	}

	/**
	 * @return string
	 */
	public static function setupHTML(): string {
		$curl_status = url_post_contents_available(true, $reason) ? 1 : 0;
		return '<div id="CAPTCHAPLUGIN_PARAMS_HCAPTCHA">'.
		       '<p>(<a href="https://www.hcaptcha.com/" target="_blank">'._L('more information and obtain key').'</a>)</p>'.
		       '<p>'._L('hCaptcha Site key').'<br><input id="hcaptcha_sitekey" type="text" onkeypress="rebuild()" onkeyup="rebuild()"> <span id="hcaptcha_sitekey_warn"></span></p>'.
		       '<p>'._L('hCaptcha Secret').'<br><input id="hcaptcha_secret" type="text" onkeypress="rebuild()" onkeyup="rebuild()"> <span id="hcaptcha_secret_warn"></span></p>'.
		       '<input id="hcaptcha_curl_status" value="'.$curl_status.'" type="hidden">'.
		       (!$curl_status ? '<p><font color="red">'._L('The %1 plugin cannot connect to the Internet.', self::id()).' '.$reason.'</font></p>' : '').
		       '</div>';
	}

	/**
	 * @param array $http_headers
	 * @return void
	 */
	function httpHeaderCheck(array &$http_headers) {

		// If you use CSP headers, please add the following to your configuration:
		// script-src should include https://hcaptcha.com, https://*.hcaptcha.com
		$http_headers["Content-Security-Policy"]["script-src"][] = "https://hcaptcha.com";
		$http_headers["Content-Security-Policy"]["script-src"][] = "https://*.hcaptcha.com";
		// frame-src should include https://hcaptcha.com, https://*.hcaptcha.com
		$http_headers["Content-Security-Policy"]["frame-src"][] = "https://hcaptcha.com";
		$http_headers["Content-Security-Policy"]["frame-src"][] = "https://*.hcaptcha.com";
		// style-src should include https://hcaptcha.com, https://*.hcaptcha.com
		$http_headers["Content-Security-Policy"]["style-src"][] = "https://hcaptcha.com";
		$http_headers["Content-Security-Policy"]["style-src"][] = "https://*.hcaptcha.com";
		// connect-src should include https://hcaptcha.com, https://*.hcaptcha.com
		$http_headers["Content-Security-Policy"]["connect-src"][] = "https://hcaptcha.com";
		$http_headers["Content-Security-Policy"]["connect-src"][] = "https://*.hcaptcha.com";

		//If you are an enterprise customer and would like to enable additional verification to be performed, you can optionally choose the following CSP strategy:
		//unsafe-eval and unsafe-inline should include https://hcaptcha.com, https://*.hcaptcha.com
		//$http_headers["Content-Security-Policy"]["unsafe-eval"][] = "https://hcaptcha.com";
		//$http_headers["Content-Security-Policy"]["unsafe-eval"][] = "https://*.hcaptcha.com";
		//$http_headers["Content-Security-Policy"]["unsafe-inline"][] = "https://hcaptcha.com";
		//$http_headers["Content-Security-Policy"]["unsafe-inline"][] = "https://*.hcaptcha.com";

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
				if (!url_post_contents_available(true, $reason)) {
					$notifications[] = new OIDplusNotification('CRIT', _L('OIDplus plugin "%1" is enabled, but OIDplus cannot connect to the Internet.', htmlentities(self::id())) . ' ' . $reason);
				}
			}
		}
		return $notifications;
	}

}
