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

namespace ViaThinkSoft\OIDplus\Plugins\Auth\BCrypt;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusAuthPlugin;
use ViaThinkSoft\OIDplus\Core\OIDplusConfig;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusRAAuthInfo;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusAuthPluginBCrypt extends OIDplusAuthPlugin {

	/**
	 * @return string
	 */
	public function id(): string {
		return 'A3_bcrypt';
	}

	/**
	 * @param bool $html
	 * @return void
	 * @throws OIDplusException
	 */
	public function init(bool $html=true): void {
		// Note: Example #3 here https://www.php.net/manual/en/function.password-hash.php can help you with finding a good cost value
		OIDplus::config()->prepareConfigKey('ra_bcrypt_cost', 'How complex should the BCrypt hash of RA passwords be? (Only for plugin A3_bcrypt; values 4-31, default 10)', '10', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (empty($value) || !is_numeric($value) || ($value<4) || ($value>31)) {
				throw new OIDplusException(_L('Invalid value for "cost" (must be 4-31, default 10).'));
			}
		});
	}

	/**
	 * @param string $authKey
	 * @return bool
	 */
	private function supportedCryptAlgo(#[\SensitiveParameter]
	                                    string $authKey): bool {
		return str_starts_with($authKey, '$2$')  ||
		       str_starts_with($authKey, '$2a$') ||
		       str_starts_with($authKey, '$2b$') ||
		       str_starts_with($authKey, '$2x$') ||
		       str_starts_with($authKey, '$2y$');
	}

	/**
	 * @param OIDplusRAAuthInfo $authInfo
	 * @param string $check_password
	 * @return bool
	 */
	public function verify(#[\SensitiveParameter]
	                       OIDplusRAAuthInfo $authInfo,
	                       #[\SensitiveParameter]
	                       string $check_password): bool {
		$authKey = $authInfo->getAuthKey();

		if (!$this->supportedCryptAlgo($authKey)) {
			// Unsupported algorithm
			return false;
		}

		// $2a$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy
		//  \/ \/ \____________________/\_____________________________/
		// Alg Cost   Salt (128 bit)             Hash

		return password_verify($check_password, $authKey);
	}

	/**
	 * @param string $password
	 * @return OIDplusRAAuthInfo
	 * @throws OIDplusException
	 */
	public function generate(#[\SensitiveParameter]
	                         string $password): OIDplusRAAuthInfo {
		if (strlen($password) > 72) throw new OIDplusException(_L('Password is too long (max %1 bytes)',72));
		$cost = OIDplus::config()->getValue('ra_bcrypt_cost', 10);
		$calc_authkey = password_hash($password, PASSWORD_BCRYPT, array("cost" => $cost));
		if (!$calc_authkey) throw new OIDplusException(_L('Error creating password hash'));
		assert($this->supportedCryptAlgo($calc_authkey));
		return new OIDplusRAAuthInfo($calc_authkey);
	}

	/**
	 * @param string $reason
	 * @return bool
	 */
	public function availableForHash(string &$reason): bool {
		if (version_compare(PHP_VERSION, '7.4.0') >= 0) {
			$ok = in_array('2',  password_algos()) ||
			      in_array('2a', password_algos()) ||
			      in_array('2b', password_algos()) ||
			      in_array('2x', password_algos()) ||
			      in_array('2y', password_algos());

		} else {
			$ok = defined('PASSWORD_BCRYPT');
		}

		if ($ok) return true;

		$reason = _L('No fitting hash algorithm found');
		return false;
	}

	/**
	 * @param string $reason
	 * @return bool
	 */
	public function availableForVerify(string &$reason): bool {
		return $this->availableForHash($reason);
	}

}
