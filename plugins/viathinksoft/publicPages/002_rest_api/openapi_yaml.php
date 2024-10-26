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

$output = '';

$output .= "openapi: ".OPENAPI_VERSION."\n";
$output .= "\n";
$output .= "info:\n";
$output .= "  version: ".OIDplus::getVersion()."\n";
$output .= "  title: OIDplus REST API\n";
$output .= "  description: This OpenAPI specification contains all REST endpoints that are installed on this system \"".OIDplus::config()->getValue('system_title')."\"\n";
$output .= "  license:\n";
$output .= "    name: Apache 2.0 License\n";
$output .= "    identifier: Apache-2.0\n"; // https://spdx.org/licenses/
$output .= "  contact:\n";
$output .= "    name: ".OIDplus::config()->getValue('system_title')." System Administrator\n";
$output .= "    url: ".OIDplus::webpath(null, OIDplus::PATH_ABSOLUTE_CANONICAL)."\n";
$output .= "    email: ".OIDplus::config()->getValue('admin_email')."\n";
$output .= "\n";
$output .= "servers:\n";
$output .= "  - url: ".OIDplus::webpath(null, OIDplus::PATH_ABSOLUTE)."rest/v1/\n";
$output .= "    description: ".OIDplus::config()->getValue('system_title')."\n";
$output .= "\n";
foreach (['tags','paths'] as $accepted_node) {
	$output .= $accepted_node.":\n";
	foreach (OIDplus::getAllPlugins() as $plugin) {
		if ($plugin instanceof INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_9) {
			$yaml = $plugin->restApiInfo('openapi-'.OPENAPI_VERSION);
			if ($yaml) {
				check_openapi_version($yaml);
				$output .= get_yaml_only_node_contents($yaml, $accepted_node);
			}
		}
	}
	$output .= "\n";
}
$output .= "components:\n";
$output .= "  securitySchemes:\n";
$output .= "    BearerAuth:\n";
$output .= "      type: http\n";
$output .= "      scheme: bearer\n";
$output .= "      bearerFormat: JWT\n";

OIDplus::invoke_shutdown();

// https://stackoverflow.com/questions/52541842/what-is-the-media-type-of-an-openapi-schema
header('Content-Type: application/vnd.oai.openapi;version='.OPENAPI_VERSION);
header('Content-Disposition: attachment; filename="oidplus_openapi.yaml"');
//header('Content-Type: text/plain'); // just for debugging
echo $output;

# ---

/**
 * Verifies that the YAML file has the correct OpenAPI version and throws an Exception otherwise
 * @param $content string The YAML file contents
 */
function check_openapi_version(string $content): void {
	$content = explode("\n",$content);
	if (trim($content[0]??'') != 'openapi: '.OPENAPI_VERSION) {
		throw new OIDplusException(_L("OpenAPI version %1 not found in plugin's response", OPENAPI_VERSION));
	}
}

/**
 * Loads an OpenAPI Yaml file and removes everything except the contents of a node
 * @param $content string The YAML file contents
 * @param $node string The name of the node
 * @return string The contents of the node
 */
function get_yaml_only_node_contents(string $content, string $node): string {
	$content = explode("\n",$content);
	$new_contents = [];
	$ignoring = true;
	foreach ($content as $line) {
		if ($line === $node.':') {
			$ignoring = false;
			continue;
		}
		if (trim($line) !== '' && !preg_match('/^\s/', $line)) {
			$ignoring = true;
		}
		if (!$ignoring) {
			$new_contents[] = $line;
		}
	}
	return implode("\n", $new_contents);
}
