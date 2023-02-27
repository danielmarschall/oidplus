<?php

/*
 * OIDplus 2.0
 * Copyright 2023 Daniel Marschall, ViaThinkSoft
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

class OIDplusAuthPluginVtsMcf extends OIDplusAuthPlugin {

	public function verify(OIDplusRAAuthInfo $authInfo, $check_password) {
		$authKey = $authInfo->getAuthKey();

		if (vts_crypt_version($authKey) != '0') {
			return vts_password_verify($check_password, $authKey);
		} else {
			return false;
		}
	}

	public function generate($password): OIDplusRAAuthInfo {
		$hashalgo = 'sha3-512'; // we can safely use it, because we have a pure-PHP implementation shipped with OIDplus

		$salt = random_bytes_ex(50, true, true);

		if (function_exists('sha3_512_hmac')) {
			$calc_authkey = vts_password_hash($password, PASSWORD_VTS_MCF1, array(
				'algo' => $hashalgo,
				'mode' => 'hmac'
			));
		} else if (function_exists('sha3_512')) {
			$calc_authkey = vts_password_hash($password, PASSWORD_VTS_MCF1, array(
				'algo' => $hashalgo,
				'mode' => 'ps' // 'ps' means Password+Salt concatenated
			));
		} else {
			$calc_authkey = ''; // avoid PHPstan warning
			assert(false);
		}

		return new OIDplusRAAuthInfo($calc_authkey);
	}

	public function availableForHash(&$reason): bool {
		return function_exists('vts_password_hash') && (function_exists('sha3_512_hmac') || function_exists('sha3_512'));
	}

	public function availableForVerify(&$reason): bool {
		return function_exists('vts_password_verify');
	}

}