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

class OIDplusCaptchaPluginHCaptcha extends OIDplusCaptchaPlugin {

	public static function id(): string {
		return 'hCaptcha';
	}

	public function isVisible(): bool {
		return true;
	}

	public function captchaGenerate($header_text=null, $footer_text=null) {
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

	public function captchaVerify($params, $fieldname=null) {
		$sitekey=OIDplus::baseConfig()->getValue('HCAPTCHA_SITEKEY', '');
		$secret=OIDplus::baseConfig()->getValue('HCAPTCHA_SECRET', '');

		if (!function_exists('curl_init')) {
			throw new OIDplusException(_L('hCaptcha plugin needs the PHP extension php_curl'));
		}

		// Yes, it is really "g-recaptcha-response"!
		if (is_null($fieldname)) $fieldname = 'g-recaptcha-response'; // no individual field name (created by oidplus_captcha_response()) means that it is a plain POST event (e.g. by oobe.php)
		_CheckParamExists($params, $fieldname);
		$response=$params[$fieldname];

		$ch = curl_init();
		if (ini_get('curl.cainfo') == '') curl_setopt($ch, CURLOPT_CAINFO, OIDplus::localpath() . 'vendor/cacert.pem');
		curl_setopt($ch, CURLOPT_URL, 'https://hcaptcha.com/siteverify');
		curl_setopt($ch, CURLOPT_USERAGENT, 'ViaThinkSoft-OIDplus/2.0');
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "secret=".urlencode($secret)."&response=".urlencode($response)."&remoteip=".urlencode($_SERVER['REMOTE_ADDR'])."&sitekey=".urlencode($sitekey));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		if (!($res = @curl_exec($ch))) {
			throw new OIDplusException(_L('Communication with hCaptcha server failed: %1',curl_error($ch)));
		}
		curl_close($ch);

		$captcha_success=@json_decode($res);
		if (!$captcha_success || ($captcha_success->success==false)) {
			throw new OIDplusException(_L('CAPTCHA not successfully verified').' ('.implode(", ",$captcha_success->{'error-codes'}).')');
		}
	}

	public static function setupHTML(): string {
		$curl_status = function_exists('curl_init') ? 1 : 0;
		return '<div id="CAPTCHAPLUGIN_PARAMS_HCAPTCHA">'.
		       '<p>(<a href="https://www.hcaptcha.com/" target="_blank">'._L('more information and obtain key').'</a>)</p>'.
		       '<p>'._L('hCaptcha Site key').'<br><input id="hcaptcha_sitekey" type="text" onkeypress="rebuild()" onkeyup="rebuild()"> <span id="hcaptcha_sitekey_warn"></span></p>'.
		       '<p>'._L('hCaptcha Secret').'<br><input id="hcaptcha_secret" type="text" onkeypress="rebuild()" onkeyup="rebuild()"> <span id="hcaptcha_secret_warn"></span></p>'.
		       '<input id="hcaptcha_curl_status" value="'.$curl_status.'" type="hidden">'.
		       (!$curl_status ? '<p><font color="red">'._L('hCaptcha plugin needs the PHP extension php_curl').'</font></p>' : '').
		       '</div>';
	}

	function httpHeaderCheck(&$http_headers) {

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

}
