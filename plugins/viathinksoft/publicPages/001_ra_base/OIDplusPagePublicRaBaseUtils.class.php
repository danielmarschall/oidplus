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

namespace ViaThinkSoft\OIDplus\Plugins\viathinksoft\publicPages\n001_ra_base;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusConfig;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginPublic;
use ViaThinkSoft\OIDplus\Core\OIDplusRA;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

// TODO: should this be a different plugin type? A page without gui is weird!
class OIDplusPagePublicRaBaseUtils extends OIDplusPagePluginPublic {

	/**
	 * @param array $params email
	 * @return array
	 * @throws OIDplusException
	 */
	private function action_Delete(array $params): array {
		_CheckParamExists($params, 'email');

		$email = $params['email'];

		$ra_logged_in = OIDplus::authUtils()->isRaLoggedIn($email);

		if (!OIDplus::authUtils()->isAdminLoggedIn() && !$ra_logged_in) {
			throw new OIDplusException(_L('Authentication error. Please log in.'), null, 401);
		}

		if ($ra_logged_in) OIDplus::authUtils()->raLogout($email);

		$ra = new OIDplusRA($email);
		if (!$ra->existing()) {
			throw new OIDplusException(_L('RA "%1" does not exist.',$email));
		}
		$ra->delete();
		$ra = null;

		OIDplus::logger()->log("V2:[OK/WARN]RA(%1)+[OK/INFO]A", "RA '%1' deleted", $email);

		return array("status" => 0);
	}

	/**
	 * @param string $actionID
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 */
	public function action(string $actionID, array $params): array {
		if ($actionID == 'delete_ra') {
			return $this->action_Delete($params);
		} else {
			return parent::action($actionID, $params);
		}
	}

	/**
	 * @param bool $html
	 * @return void
	 * @throws OIDplusException
	 */
	public function init(bool $html=true): void {
		// Will be used by: plugins admin-130, public-091, public-200, ra-092, ra-101
		OIDplus::config()->prepareConfigKey('ra_min_password_length', 'Minimum length for RA passwords', '6', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < 1)) {
				throw new OIDplusException(_L('Please enter a valid password length.'));
			}
		});
	}

	/**
	 * @param string $id
	 * @param array $out
	 * @param bool $handled
	 * @return void
	 */
	public function gui(string $id, array &$out, bool &$handled): void {
	}

	/**
	 * @param array $out
	 * @return void
	 */
	public function publicSitemap(array &$out): void {
	}

	/**
	 * @param array $json
	 * @param string|null $ra_email
	 * @param bool $nonjs
	 * @param string $req_goto
	 * @return bool
	 */
	public function tree(array &$json, ?string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
		return false;
	}

	/**
	 * @param string $request
	 * @return array|false
	 */
	public function tree_search(string $request) {
		return false;
	}
}
