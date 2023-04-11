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

// TODO: should this be a different plugin type? A page without gui is weird!
// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusPagePublicRaBaseUtils extends OIDplusPagePluginPublic {

	/**
	 * @param string $actionID
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 */
	public function action(string $actionID, array $params): array {

		// Action:     delete_ra
		// Method:     POST
		// Parameters: email
		// Outputs:    Text
		if ($actionID == 'delete_ra') {
			_CheckParamExists($params, 'email');

			$email = $params['email'];

			$ra_logged_in = OIDplus::authUtils()->isRaLoggedIn($email);

			if (!OIDplus::authUtils()->isAdminLoggedIn() && !$ra_logged_in) {
				throw new OIDplusException(_L('Authentication error. Please log in.'));
			}

			if ($ra_logged_in) OIDplus::authUtils()->raLogout($email);

			$ra = new OIDplusRA($email);
			if (!$ra->existing()) {
				throw new OIDplusException(_L('RA "%1" does not exist.',$email));
			}
			$ra->delete();
			$ra = null;

			OIDplus::logger()->log("[?WARN/!OK]RA(%1)!/[?INFO/!OK]A?", "RA '%1' deleted", $email);

			return array("status" => 0);
		} else {
			return parent::action($actionID, $params);
		}

	}

	/**
	 * @param bool $html
	 * @return void
	 * @throws OIDplusException
	 */
	public function init(bool $html=true) {
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
	public function gui(string $id, array &$out, bool &$handled) {
	}

	/**
	 * @param array $out
	 * @return void
	 */
	public function publicSitemap(array &$out) {
	}

	/**
	 * @param array $json
	 * @param string|null $ra_email
	 * @param bool $nonjs
	 * @param string $req_goto
	 * @return bool
	 */
	public function tree(array &$json, string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
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
