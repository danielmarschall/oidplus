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
$output .= "  version: 1.0.0\n";
$output .= "  title: OIDplus REST API\n";
$output .= "  description: This OpenAPI specification contains all REST endpoints that are installed on this system \"".OIDplus::config()->getValue('system_title')."\"\n";
$output .= "  contact:\n";
$output .= "    name: ".OIDplus::config()->getValue('system_title')." System Administrator\n";
$output .= "    url: ".OIDplus::webpath(null, OIDplus::PATH_ABSOLUTE_CANONICAL)."\n";
$output .= "    email: ".OIDplus::config()->getValue('admin_email')."\n";
$output .= "\n";
$output .= "servers:\n";
$output .= "  - url: ".OIDplus::webpath(null, OIDplus::PATH_ABSOLUTE)."rest/v1/\n";
$output .= "    description: ".OIDplus::config()->getValue('system_title')."\n";
$output .= "\n";
$output .= "paths:\n";

$submenu = array();
foreach (OIDplus::getAllPlugins() as $plugin) {
	if ($plugin instanceof INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_9) {
		$yaml = $plugin->restApiInfo('openapi-3.1.0');
		if ($yaml) $output .= get_yaml_only_paths($yaml);
	}
}

$output .= "\n";
$output .= "components:\n";
$output .= "  securitySchemes:\n";
$output .= "    BearerAuth:\n";
$output .= "      type: http\n";
$output .= "      scheme: bearer\n";
$output .= "      bearerFormat: JWT\n";

// https://stackoverflow.com/questions/52541842/what-is-the-media-type-of-an-openapi-schema
header('Content-Type: application/vnd.oai.openapi');
header('Content-Disposition: attachment; filename="oidplus_openapi.yaml"');
//header('Content-Type: text/plain'); // just for debugging
echo $output;

# ---

/**
 * Loads an OpenAPI Yaml file and removes everything except the contents of the node "paths:"
 * @param $content string The YAML file contents
 * @return string The contents of the paths: node
 */
function get_yaml_only_paths(string $content): string {
	// Die YAML-Datei laden
	$inhalt = explode("\n",$content); // Liest die Datei zeilenweise
	$neueInhalte = [];
	$ignorieren = true; // Wir beginnen mit dem Ignorieren
	if (trim($inhalt[0]??'') != 'openapi: '.OPENAPI_VERSION) throw new OIDplusException(_L("OpenAPI version %1 not found in plugin's response", OPENAPI_VERSION));
	foreach ($inhalt as $zeile) {
	    // Trim die Zeile, um überflüssige Leerzeichen zu entfernen
	    $trimmedZeile = trim($zeile);
	    // Prüfen, ob die Zeile mit "paths:" beginnt
	    if ($trimmedZeile === 'paths:') {
	        $ignorieren = false; // Stoppe das Ignorieren
	        //$neueInhalte[] = $zeile; // Füge die Zeile zu den neuen Inhalten hinzu
	        continue; // Gehe zur nächsten Zeile
	    }
	    // Wenn die Zeile nicht leer und nicht mit Leerzeichen beginnt, beginne wieder mit dem Ignorieren
	    if ($trimmedZeile !== '' && !preg_match('/^\s/', $zeile)) {
	        $ignorieren = true; // Beginne wieder mit dem Ignorieren
	    }
	    // Wenn wir nicht ignorieren, füge die Zeile zur neuen Liste hinzu
	    if (!$ignorieren) {
	        // Wenn die Zeile nicht leer ist, füge sie hinzu
	        if ($trimmedZeile !== '') {
	            $neueInhalte[] = $zeile; // Füge die originale Zeile hinzu
	        }
	    }
	}
	return implode("\n", $neueInhalte)."\n";
}
