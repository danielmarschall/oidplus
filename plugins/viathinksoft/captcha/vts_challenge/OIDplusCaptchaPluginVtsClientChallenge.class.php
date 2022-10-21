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

class OIDplusCaptchaPluginVtsClientChallenge extends OIDplusCaptchaPlugin {

	public static function id(): string {
		return 'ViaThinkSoft Client Challenge';
	}

	public function isVisible(): bool {
		return false;
	}

	public function csrfUnlock($actionID) {
		if ($actionID == 'get_challenge') {
			return true;
		}
		return false;
	}

	public function action($actionID, $params) {
		if ($actionID == 'get_challenge') {
			$complexity=500000; // TODO: make configurable
			$server_secret='VtsClientChallenge:'.OIDplus::baseConfig()->getValue('SERVER_SECRET');

			$min = 0;
			$max = $complexity;
			$starttime = time();
			$random = rand($min,$max); // TODO: cryptographic rand
			$ip_target = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
			$challenge = sha3_512($starttime.'/'.$ip_target.'/'.$random);
			$challenge_integrity = sha3_512_hmac($challenge,$server_secret);
			$send_to_client = array($starttime, $ip_target, $challenge, $min, $max, $challenge_integrity);

			return array("status" => 0, "challenge" => $send_to_client);
		}
	}

	public function captchaDomHead() {
		// Here you can add styles and scripts to be included into the HTML <head> part
		return '<script>
		function oidplus_captcha_response() {
			return OIDplusCaptchaPluginVtsClientChallenge.captchaResponse();
		}
		function oidplus_captcha_reset() {
			return OIDplusCaptchaPluginVtsClientChallenge.captchaReset(true);
		}
		</script>

		<script src="'.(OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL) . 'vendor/components/jquery/jquery.min.js').'"></script>
		<script src="'.(OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL) . 'vendor/emn178/js-sha3/src/sha3.js').'"></script>
		<script src="'.(OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE) . 'OIDplusCaptchaPluginVtsClientChallenge.js').'"></script>
		'; // we include OIDplusCaptchaPluginVtsClientChallenge.js not via manifest.xml, otherwise oobe.php would not work
	}

	public function captchaGenerate($header_text=null, $footer_text=null) {
		return '<noscript>'.
			'<p><font color="red">'._L('You need to enable JavaScript to solve the CAPTCHA.').'</font></p>'.
			'</noscript>'.
			'<input type="text" id="vts_validation_result" name="vts_validation_result" value="" style="display:none">'.
			'<script>
			OIDplusCaptchaPluginVtsClientChallenge.captchaReset(true); // try to solve it while the user enters the form
			$("form").submit(function(e){
				if (!OIDplusCaptchaPluginVtsClientChallenge.currentresponse) {
					// if the user is too fast, then we will calculate it now
					OIDplusCaptchaPluginVtsClientChallenge.currentresponse = OIDplusCaptchaPluginVtsClientChallenge.captchaResponse();
				}
				$("#vts_validation_result").val(OIDplusCaptchaPluginVtsClientChallenge.currentresponse);
			});
			</script>';
	}

	public function captchaVerify($params, $fieldname=null) {

		if (is_null($fieldname)) $fieldname = 'vts_validation_result';

		$server_secret='VtsClientChallenge:'.OIDplus::baseConfig()->getValue('SERVER_SECRET');
		$max_time = 10*60; // 10min. TODO: make configurable!

		if (!isset($params[$fieldname])) throw new OIDplusException('No challenge response found');

		list($starttime, $ip_target, $challenge, $answer, $challenge_integrity) = @json_decode($params[$fieldname], true);

		if ($ip_target != (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown')) {
			throw new OIDplusException(_L('Wrong IP address'));
		} else if (time()-$starttime > $max_time) {
			throw new OIDplusException(_L('Challenge expired'));
		} else if ($challenge_integrity != sha3_512_hmac($challenge,$server_secret)) {
			throw new OIDplusException(_L('Challenge integrity failed'));
		} else if ($challenge !== sha3_512($starttime.'/'.$ip_target.'/'.$answer)) {
			throw new OIDplusException(_L('Wrong answer'));
		}
	}

	public static function setupHTML(): string {
		return '<div id="CAPTCHAPLUGIN_PARAMS_VtsClientChallenge">'.
		       '<p>'._L('ViaThinkSoft Client Challenge lets the client computer solve a cryptographic problem instead of letting the user solve a CAPTCHA. This slows down brute-force attacks.').'</p>'.
		       '</div>';
	}

}
