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
			$server_secret='VtsClientChallenge:'.OIDplus::baseConfig()->getValue('SERVER_SECRET');

			$offset = 0; // doesn't matter
			$min = $offset;
			$max = $offset + OIDplus::baseConfig()->getValue('VTS_CAPTCHA_COMPLEXITY', 50000);
			if ($max > mt_getrandmax()) $max = mt_getrandmax();

			$starttime = time();
			$random = mt_rand($min,$max);
			$ip_target = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
			$challenge = sha3_512($starttime.'/'.$ip_target.'/'.$random);
			$challenge_integrity = sha3_512_hmac($challenge,$server_secret);
			$send_to_client = array($starttime, $ip_target, $challenge, $min, $max, $challenge_integrity);

			$open_trans_file = self::getOpenTransFileName($ip_target, $random);
			if (@file_put_contents($open_trans_file, '') === false) {
				throw new OIDplusException(_L('Cannot write file %1', $open_trans_file));
			}

			return array(
				"status" => 0,
				"challenge" => $send_to_client,
				// Autosolve on=calculate result on page load; off=calculate result on form submit
				"autosolve" => OIDplus::baseConfig()->getValue('VTS_CAPTCHA_AUTOSOLVE', true)
			);
		}
	}

	private static function getOpenTransFileName($ip_target, $random) {
		$dir = OIDplus::localpath().'/userdata/cache';
		$server_secret='VtsClientChallenge:'.OIDplus::baseConfig()->getValue('SERVER_SECRET');

		// First, delete challenges which were never completed
		$files = glob($dir.'/vts_client_challenge_*.tmp');
		$expire = strtotime('-3 DAYS');
		foreach ($files as $file) {
			if (!is_file($file)) continue;
			if (filemtime($file) > $expire) continue;
			@unlink($file);
		}

		return $dir.'/vts_client_challenge_'.sha3_512_hmac($ip_target.'/'.$random, $server_secret).'.tmp';
	}

	public function captchaGenerate($header_text=null, $footer_text=null) {
		return '<noscript>'.
		       '<p><font color="red">'._L('You need to enable JavaScript to solve the CAPTCHA.').'</font></p>'.
		       '</noscript>'.
		       '<input type="hidden" id="vts_validation_result" name="vts_validation_result" value="">'.
		       '<script>'.
		       'OIDplusCaptchaPluginVtsClientChallenge.captchaShow('.js_escape(OIDplus::webpath(null,OIDplus::PATH_RELATIVE)).');'.
		       '</script>';
	}

	public function captchaVerify($params, $fieldname=null) {

		if (is_null($fieldname)) $fieldname = 'vts_validation_result';

		$server_secret='VtsClientChallenge:'.OIDplus::baseConfig()->getValue('SERVER_SECRET');

		if (!isset($params[$fieldname])) throw new OIDplusException(_L('No challenge response found').' (A)');

		$client_response = @json_decode($params[$fieldname], true);

		if (!is_array($client_response)) throw new OIDplusException(_L('Challenge response is invalid').' (B)');
		if (count($client_response) != 5) throw new OIDplusException(_L('Challenge response is invalid').' (C)');
		list($starttime, $ip_target, $challenge, $answer, $challenge_integrity) = $client_response;
		if (!is_numeric($starttime)) throw new OIDplusException(_L('Challenge response is invalid').' (D)');
		if (!is_string($ip_target)) throw new OIDplusException(_L('Challenge response is invalid').' (E)');
		if (!is_string($challenge)) throw new OIDplusException(_L('Challenge response is invalid').' (F)');
		if (!is_numeric($answer)) throw new OIDplusException(_L('Challenge response is invalid').' (G)');
		if (!is_string($challenge_integrity)) throw new OIDplusException(_L('Challenge response is invalid').' (H)');

		$open_trans_file = self::getOpenTransFileName($ip_target, $answer);

		$current_ip = (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown');
		if ($ip_target != $current_ip) {
			throw new OIDplusException(_L('IP address has changed. Please try again. (current IP %1, expected %2)', $current_ip, $ip_target));
		} else if (time()-$starttime > OIDplus::baseConfig()->getValue('VTS_CAPTCHA_MAXTIME', 10*60/*10 minutes*/)) {
			throw new OIDplusException(_L('Challenge expired. Please try again.'));
		} else if ($challenge_integrity != sha3_512_hmac($challenge,$server_secret)) {
			throw new OIDplusException(_L('Challenge integrity failed'));
		} else if ($challenge !== sha3_512($starttime.'/'.$ip_target.'/'.$answer)) {
			throw new OIDplusException(_L('Wrong answer'));
		} else if (!file_exists($open_trans_file)) {
			throw new OIDplusException(_L('Challenge submitted twice or transaction missing'));
		} else {
			@unlink($open_trans_file);
		}
	}

	public static function setupHTML(): string {
		return '<div id="CAPTCHAPLUGIN_PARAMS_VtsClientChallenge">'.
		       '<p>'._L('ViaThinkSoft Client Challenge lets the client computer solve a cryptographic problem instead of letting the user solve a CAPTCHA. This slows down brute-force attacks.').'</p>'.
		       '</div>';
	}

}
