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

class OIDplusPagePublicRestApi extends OIDplusPagePluginPublic {

	/**
	 * @param string $request
	 * @return bool
	 * @throws OIDplusException
	 */
	public function handle404(string $request): bool {

		if (!isset($_SERVER['REQUEST_URI']) || !isset($_SERVER["REQUEST_METHOD"])) return false;

		$rel_url = substr($_SERVER['REQUEST_URI'], strlen(OIDplus::webpath(null, OIDplus::PATH_RELATIVE_TO_ROOT)));
		$expect = 'rest/v1/';
		if (str_starts_with($rel_url, $expect)) {
			$rel_url = ltrim($rel_url, $expect);

			$requestMethod = $_SERVER["REQUEST_METHOD"];

			try {
				$cont = @file_get_contents('php://input');
				$json_in = empty($cont) ? [] : @json_decode($cont, true);
				if (!is_array($json_in)) throw new OIDplusException(_L('Invalid JSON data received'), null, 400);

				$json_out = false;
				foreach (OIDplus::getAllPlugins() as $plugin) {
					if ($plugin instanceof INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_9) {
						$json_out = $plugin->restApiCall($requestMethod, $rel_url, $json_in);
						if ($json_out !== false) break;
					}
				}
				if ($json_out === false) {
					throw new OIDplusException(_L('REST endpoint not found'), null, 404);
				}
				if (!isset($json_out['status'])) $json_out['status'] = -1; // status -1 and -2 like in ajax.php
				if (!isset($json_out['status_bits'])) $json_out['status_bits'] = [];
			} catch (\Exception $e) {
				http_response_code($e instanceof OIDplusException ? $e->getHttpStatus() : 500);
				$json_out = array("status" => -2, "status_bits" => [], "error" => $e->getMessage());
			}

			OIDplus::invoke_shutdown();
			@header('Content-Type:application/json; charset=utf-8');
			echo json_encode($json_out);
			die(); // return true;
		}

		return false;
	}

}
