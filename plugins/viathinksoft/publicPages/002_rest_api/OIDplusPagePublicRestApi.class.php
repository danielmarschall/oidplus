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
				$json_out = false;
				foreach (OIDplus::getAllPlugins() as $plugin) {
					if ($plugin instanceof INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_9) {
						$json_out = $plugin->restApiCall($requestMethod, $rel_url);
						if ($json_out !== false) break;
					}
				}
				if ($json_out === false) {
					http_response_code(404);
					$json_out = array("error" => "Endpoint not found");
				}
			} catch (\Exception $e) {
				http_response_code(500);
				$json_out = array("error" => $e->getMessage());
			}

			OIDplus::invoke_shutdown();
			@header('Content-Type:application/json; charset=utf-8');
			echo json_encode($json_out);
			die(); // return true;
		}

		return false;
	}

}