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

namespace ViaThinkSoft\OIDplus\Plugins\PublicPages\RestApi;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginPublic;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusPagePublicRestApi extends OIDplusPagePluginPublic {

	// === PART 1: REST request handling (/rest/v1/...) ===

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
			originHeaders(); // Allows queries from other domains
			OIDplus::authUtils()->disableCSRF(); // allow access to ajax.php without valid CSRF token

			$rel_url = preg_replace('@^'.preg_quote($expect,'@').'@', '', $rel_url);

			$requestMethod = $_SERVER["REQUEST_METHOD"];

			if (!OIDplus::baseconfig()->getValue('DISABLE_REST_TRANSACTIONS',false) && OIDplus::db()->transaction_supported()) {
				OIDplus::db()->transaction_begin();
			}
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
				if (!isset($json_out['status'])) {
					$json_out['status'] = -1; // status -1 and -2 like in ajax.php
					if (!isset($json_out['error'])) $json_out['error'] = _L('The plugin did not return a status value');
				}
				if (!isset($json_out['status_bits'])) $json_out['status_bits'] = [];
				if (!OIDplus::baseconfig()->getValue('DISABLE_REST_TRANSACTIONS',false) && OIDplus::db()->transaction_supported()) {
					OIDplus::db()->transaction_commit();
				}
			} catch (\Exception $e) {
				if (!OIDplus::baseconfig()->getValue('DISABLE_REST_TRANSACTIONS',false) && OIDplus::db()->transaction_supported()) {
					if (OIDplus::db()->transaction_supported()) OIDplus::db()->transaction_rollback();
				}
				http_response_code($e instanceof OIDplusException ? $e->getHttpStatus() : 500);
				$json_out = array("status" => -2, "status_bits" => [], "error" => $e->getMessage());
			}

			OIDplus::invoke_shutdown();
			@header('Content-Type:application/json; charset=utf-8');
			echo json_encode($json_out, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
			die(); // return true;
		}

		return false;
	}

	// === PART 2: GUI (Swagger UI) ===

	/**
	 * @param string $html
	 * @return void
	 */
	public function htmlPostprocess(string &$html): void {
		$scrTag = '<link rel="stylesheet" type="text/css" href="plugins/viathinksoft/publicPages/002_rest_api/swagger-ui/swagger-ui.css" />'.
		          '<link rel="stylesheet" type="text/css" href="plugins/viathinksoft/publicPages/002_rest_api/swagger-ui/index.css" />'.
		          '<script src="plugins/viathinksoft/publicPages/002_rest_api/swagger-ui/swagger-ui-bundle.js" charset="UTF-8"> </script>'.
		          '<script src="plugins/viathinksoft/publicPages/002_rest_api/swagger-ui/swagger-ui-standalone-preset.js" charset="UTF-8"> </script>';
		$html = preg_replace('|(<head([^>]*)>)|imU', "\\1\n\t".str_replace('\\', '\\\\', $scrTag), $html);
	}

	/**
	 * @param string $id
	 * @param array $out
	 * @param bool $handled
	 * @return void
	 * @throws OIDplusException
	 */
	public function gui(string $id, array &$out, bool &$handled): void {
		if (explode('$',$id)[0] == 'oidplus:rest_api_documentation') {
			$handled = true;

			$out['title'] = _L('REST API Documentation');
			$out['icon'] = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

			$out['text']  = '<div id="swagger-ui"></div>'."\n";
#			$out['text'] .= '<style>.swagger-ui .topbar { display: none; }</style>'."\n";
			$out['text'] .= '<script>'."\n";
			$out['text'] .= ''."\n";
			$out['text'] .= '  window.ui = SwaggerUIBundle({'."\n";
			$out['text'] .= '    url: "'.OIDplus::webpath(__DIR__.'/openapi_yaml.php').'",'."\n";
			$out['text'] .= '    dom_id: "#swagger-ui",'."\n";
			$out['text'] .= '    deepLinking: true,'."\n";
			$out['text'] .= '    presets: ['."\n";
			$out['text'] .= '      SwaggerUIBundle.presets.apis,'."\n";
#			$out['text'] .= '      SwaggerUIStandalonePreset'."\n";
			$out['text'] .= '    ],'."\n";
			$out['text'] .= '    plugins: ['."\n";
#			$out['text'] .= '      SwaggerUIBundle.plugins.DownloadUrl'."\n";
			$out['text'] .= '    ],'."\n";
#			$out['text'] .= '    layout: "StandaloneLayout"'."\n";
			$out['text'] .= '  });'."\n";
			$out['text'] .= ''."\n";
			$out['text'] .= '</script>'."\n";
		}
	}

	/**
	 * @param array $json
	 * @param string|null $ra_email
	 * @param bool $nonjs
	 * @param string $req_goto
	 * @return bool
	 * @throws OIDplusException
	 */
	public function tree(array &$json, ?string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
		if (file_exists(__DIR__.'/img/main_icon16.png')) {
			$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:rest_api_documentation',
			'icon' => $tree_icon,
			'text' => _L('REST API Documentation')
		);

		return true;
	}

	/**
	 * @param string $request
	 * @return array|false
	 */
	public function tree_search(string $request) {
		return false;
	}

}
