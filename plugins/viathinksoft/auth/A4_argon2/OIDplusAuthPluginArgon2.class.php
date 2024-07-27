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

namespace ViaThinkSoft\OIDplus\Plugins\viathinksoft\auth\A4_argon2;

use ViaThinkSoft\OIDplus\Core\OIDplusAuthPlugin;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusRAAuthInfo;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusAuthPluginArgon2 extends OIDplusAuthPlugin {

	/**
	 * @return string
	 */
	public function id(): string {
		return 'A4_argon2';
	}

	/**
	 * @param bool $html
	 * @return void
	 */
	public function init(bool $html=true) {
		// TODO: Let the admin decide about the memory, iterations, and parallelism options
	}

	/**
	 * @param string $authKey
	 * @return bool
	 */
	private function supportedCryptAlgo(string $authKey): bool {
		return str_starts_with($authKey, '$argon2i$') ||
		       str_starts_with($authKey, '$argon2id$');
	}

	/**
	 * @param OIDplusRAAuthInfo $authInfo
	 * @param string $check_password
	 * @return bool
	 */
	public function verify(OIDplusRAAuthInfo $authInfo, string $check_password): bool {
		$authKey = $authInfo->getAuthKey();

		if (!$this->supportedCryptAlgo($authKey)) {
			// Unsupported algorithm
			return false;
		}

		// $argon2i$v=19$m=1024,t=2,p=2$MEhSZkJLQXUxRzljNE5hMw$33pvelMsxqOn/1VV2pnjmKJUECBhilzOZ2+Gq/FxCP4
		//  \_____/ \__/ \____________/ \____________________/ \_________________________________________/
		//   Algo   Vers  Cost options   Salt                   Hash

		return password_verify($check_password, $authKey);
	}

	/**
	 * @return string|int|false
	 */
	private function getBestHashAlgo() { /* @phpstan-ignore-line */
		if ($this->supportsArgon2id()) {
			$hashalgo = PASSWORD_ARGON2ID;
		} else if ($this->supportsArgon2i()) {
			$hashalgo = PASSWORD_ARGON2I;
		} else {
			$hashalgo = false;
		}
		return $hashalgo;
	}

	/**
	 * @param string $password
	 * @return OIDplusRAAuthInfo
	 * @throws OIDplusException
	 */
	public function generate(string $password): OIDplusRAAuthInfo {
		$hashalgo = $this->getBestHashAlgo();
		assert($hashalgo !== false); // Should not happen if we called available() before!
		$calc_authkey = password_hash($password, $hashalgo);
		if (!$calc_authkey) throw new OIDplusException(_L('Error creating password hash'));
		assert($this->supportedCryptAlgo($calc_authkey));
		return new OIDplusRAAuthInfo($calc_authkey);
	}

	/**
	 * @return bool
	 */
	private function supportsArgon2i(): bool {
		if (version_compare(PHP_VERSION, '7.4.0') >= 0) {
			return in_array('argon2i', password_algos());
		} else {
			return defined('PASSWORD_ARGON2I');
		}
	}

	/**
	 * @return bool
	 */
	private function supportsArgon2id(): bool {
		if (version_compare(PHP_VERSION, '7.4.0') >= 0) {
			return in_array('argon2id', password_algos());
		} else {
			return defined('PASSWORD_ARGON2ID');
		}
	}

	/**
	 * @param string $reason
	 * @return bool
	 */
	public function availableForHash(string &$reason): bool {
		if (!$this->supportsArgon2i() && !$this->supportsArgon2id()) {
			$reason = _L('No fitting hash algorithm found');
			return false;
		} else {
			return true;
		}
	}

	/**
	 * @param string $reason
	 * @return bool
	 */
	public function availableForVerify(string &$reason): bool {
		return $this->availableForHash($reason);
	}

}
