<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2024 Daniel Marschall, ViaThinkSoft
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

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusGui;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Plugins\PublicPages\RestApi\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_9;

for ($sysdir_depth=4; $sysdir_depth<=7; $sysdir_depth++) {
	// The plugin directory can be in plugins (i=4), userdata_pub/plugins (i=5), or userdata_pub/tenant/.../plugins/ (i=7)
	$candidate = __DIR__. str_repeat('/..', $sysdir_depth) . '/includes/oidplus.inc.php';
	if (file_exists($candidate)) {
		require_once $candidate;
		break;
	}
}

set_exception_handler(array(OIDplusGui::class, 'html_exception_handler'));

OIDplus::init(true);

if (OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_1.3.6.1.4.1.37476.2.5.2.4.1.2', false)) {
	throw new OIDplusException(_L('This plugin was disabled by the system administrator!'));
}

originHeaders();

# ---

define('OPENAPI_VERSION', '3.1.0');

$output_ary = [
	'openapi' => OPENAPI_VERSION,
	'info' => [
		'version' => OIDplus::getVersion(),
		'title' => _L('OIDplus REST API'),
		'description' => _L('This OpenAPI specification contains all REST endpoints that are installed on this system "%1"',
		                 '['.OIDplus::config()->getValue('system_title').']('.OIDplus::webpath(null).')'),
		'license' => [
			'name' => _L('Apache 2.0 License'),
			'identifier' => 'Apache-2.0' // https://spdx.org/licenses/
		],
		'contact' => [
			'name' => _L('%1 System Administrator', OIDplus::config()->getValue('system_title')),
			'email' => OIDplus::config()->getValue('admin_email')
		]
	],
	'servers' => [
		[
			'url' => OIDplus::webpath(null, OIDplus::PATH_ABSOLUTE).'rest/v1/',
			'description' => OIDplus::config()->getValue('admin_email')
		]
	],
	'tags' => [], // will be filled from contents of all plugins
	'paths' => [], // will be filled from contents of all plugins
	'components' => [
		'securitySchemes' => [
			'BearerAuth' => [
				'type' => 'http',
				'scheme' => 'bearer',
				'bearerFormat' => 'JWT'
			]
		]
	]
];

foreach (['tags','paths'] as $accepted_node) {
	$output_ary[$accepted_node] = [];
	foreach (OIDplus::getAllPlugins() as $plugin) {
		if ($plugin instanceof INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_9) {
			$ary = json_decode($plugin->restApiInfo('openapi-'.OPENAPI_VERSION.'-json'),true);
			if (is_array($ary)) {
				if (trim($ary['openapi']??'') != OPENAPI_VERSION) {
					throw new OIDplusException(_L("OpenAPI version %1 not found in plugin's response", OPENAPI_VERSION));
				}
				$tmp = $ary[$accepted_node] ?? [];
				if (is_array($tmp) && (count($tmp)>0)) {
					$output_ary[$accepted_node] = array_merge($output_ary[$accepted_node], $tmp);
				}
			}
		}
	}
}

OIDplus::invoke_shutdown();

header('Content-Type: application/vnd.oai.openapi+json;version='.OPENAPI_VERSION);
header('Content-Disposition: attachment; filename="oidplus_openapi.json"');
//header('Content-Type: text/json'); // just for debugging (display contents inline)

echo json_encode($output_ary);
