#!/usr/bin/env php
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

class ManifestMigrationUtils {

    public static function ConvertManifest($file, $sid, $has_css, $has_js, $has_cssSetup, $has_jsSetup) {
#	echo "$file\n";
        $fileContents= file_get_contents($file);
        $simpleXml = simplexml_load_string($fileContents);

	$data = [
		"\$schema" => "urn:oid:1.3.6.1.4.1.37476.2.5.2.5.$sid.1",
		"manifest" => [
			"type" => "".$simpleXml->type[0],
			"info" => [
				"name" => "".$simpleXml->info[0]->name[0],
				"author" => "".$simpleXml->info[0]->author[0],
				"license" => "".$simpleXml->info[0]->license[0],
				"version" => "".$simpleXml->info[0]->version[0],
				"descriptionHTML" => trim(str_replace(['\r','\n','\t'], '', "".$simpleXml->info[0]->descriptionHTML[0])),
				"oid" => "".$simpleXml->info[0]->oid[0],
			],
			"php" => [
				"mainclass" => "".$simpleXml->php[0]->mainclass[0]
			]
		]
	];

	if ($has_cssSetup) {
		$data["manifest"]["cssSetup"] = array();
		if (count((array)$simpleXml->cssSetup) > 0)
		foreach ($simpleXml->cssSetup->file as $cssSetup) {
			$data["manifest"]["cssSetup"][] = "".$cssSetup;
		}
	}

	if ($has_jsSetup) {
		$data["manifest"]["jsSetup"] = array();
		if (count((array)$simpleXml->jsSetup) > 0)
		foreach ($simpleXml->jsSetup->file as $jsSetup) {
			$data["manifest"]["jsSetup"][] = "".$jsSetup;
		}
	}

	if ($has_css) {
		$data["manifest"]["css"] = array();
		if (count((array)$simpleXml->css) > 0)
		foreach ($simpleXml->css->file as $css) {
			$data["manifest"]["css"][] = "".$css;
		}
	}

	if ($has_js) {
		$data["manifest"]["js"] = array();
		if (count((array)$simpleXml->js) > 0)
		foreach ($simpleXml->js->file as $js) {
			$data["manifest"]["js"][] = "".$js;
		}
	}

	if ($sid == 3) {
		$data["manifest"]["language"] = [];
		$data["manifest"]["language"]["code"] = "".$simpleXml->language[0]->code[0];
		$data["manifest"]["language"]["flag"] = "".$simpleXml->language[0]->flag[0];
		$data["manifest"]["language"]["messages"] = "".$simpleXml->language[0]->messages[0];
	}

        return self::prettyJson($data);
    }

    public static function prettyJson($data) {
        $json = json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);

	$json = preg_replace_callback(
	    '/^(?: {4})+/m',
	    function($m) {
	        return str_repeat("\t", strlen($m[0]) / 4);
	    },
	    $json
	);

        return $json;
    }

    public static function MakeSchema($sid, $has_css, $has_js, $has_cssSetup, $has_jsSetup) {

$data = <<<EOF

{
  "\$schema": "https://json-schema.org/draft/2020-12/schema",
  "\$id": "urn:oid:1.3.6.1.4.1.37476.2.5.2.5.$sid.1",
  "type": "object",
  "properties": {
    "manifest": {
      "type": "object",
      "properties": {
        "type": {
          "type": "string"
        },
        "info": {
          "type": "object",
          "properties": {
            "name": {
              "type": "string"
            },
            "author": {
              "type": "string"
            },
            "license": {
              "type": "string"
            },
            "version": {
              "type": "string"
            },
            "descriptionHTML": {
              "type": "string"
            },
            "oid": {
              "type": "string"
            }
          },
          "required": [
            "name",
            "author",
            "license",
            "version",
            "descriptionHTML",
            "oid"
          ]
        },
        "php": {
          "type": "object",
          "properties": {
            "mainclass": {
              "type": "string"
            }
          },
          "required": [
            "mainclass"
          ]
        },

EOF;

if ($has_css) $data .= <<<EOF
        "css": {
          "type": "array",
          "items": [
            {
              "type": "string"
            }
          ]
        },
EOF;


if ($has_js) $data .= <<<EOF
        "js": {
          "type": "array",
          "items": [
            {
              "type": "string"
            }
          ]
        },
EOF;

if ($has_cssSetup) $data .= <<<EOF
        "cssSetup": {
          "type": "array",
          "items": [
            {
              "type": "string"
            }
          ]
        },
EOF;

if ($has_jsSetup) $data .= <<<EOF
        "jsSetup": {
          "type": "array",
          "items": [
            {
              "type": "string"
            }
          ]
        },
EOF;

$data = substr(trim($data),0,strlen(trim($data))-1);

$data .= <<<EOF

      },
      "required": [
        "type",
        "info",
        "php"
      ]
    }
  },
  "required": [
    "manifest"
  ]
}

EOF;

$data = trim($data);

	return self::prettyJson(json_decode($data,true,512,JSON_THROW_ON_ERROR));

    }

}

$plugin_types = [
	"adminPages" => [2, true, true, false, false],
	"auth" => [8, false, false, false, false],
	"captcha" => [12, true, true, true, true],
	"database" => [6, false, false, true, true],
	"design" => [7, true, false, false, false],
	"language" => [3, false, false, false, false],
	"logger" => [9, false, false, false, false],
	"objectTypes" => [10, true, true, false, false], # due to interface gridGeneratorLinks (INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_6) this plugin type can also have CSS and JS
	"publicPages" => [2, true, true, false, false],
	"raPages" => [2, true, true, false, false],
	"sqlSlang" => [11, false, false, false, false]
];

foreach ($plugin_types as $subfolder => $attrib) {
	// Convert manifest.xml to manifest.json
	$files = glob(__DIR__."/../plugins/*/$subfolder/*/manifest.xml");
	if (count($files) == 0) echo "Attention: $subfolder nothing found\n";
	foreach ($files as $file) {
		$json = ManifestMigrationUtils::ConvertManifest($file, $attrib[0], $attrib[1], $attrib[2], $attrib[3], $attrib[4]);
		$file = str_replace(".xml", ".json", $file);
		file_put_contents($file, $json);
	}
	// Generate JSON Schema
	if (strpos($subfolder,'Pages')!==false) $subfolder = "page";
	$json = ManifestMigrationUtils::MakeSchema($attrib[0], $attrib[1], $attrib[2], $attrib[3], $attrib[4]);
	$file = __DIR__."/../plugins/manifest_plugin_$subfolder.json";
	file_put_contents($file, $json);
}
