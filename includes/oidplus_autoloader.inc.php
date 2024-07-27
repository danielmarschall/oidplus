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

/**
 * This classloader includes plugins/... and includes/classes/...
 */
spl_autoload_register(function ($fq_class_name) {
	$path = explode('\\', $fq_class_name);
	$classname_no_namespace = end($path);

	// Convert "Namespace" to "Folder"
	foreach ($path as &$x) {
		if ((substr($x, 0, 1) == 'n') && (is_numeric(substr($x, 1, 1)))) {
			// Namespaces cannot start with numbers, so we prepend a "n" in front of it.
			// Example:
			// - File       ..................../plugins/viathinksoft/adminPages/101_notifications/*.class.php
			// - Namespace  ViaThinkSoft\OIDplus\Plugins\viathinksoft\adminPages\n101_notifications/*
			$x = substr($x, 1);
		} else {
			// "Keywords" in namespaces are only allowed in PHP 8.0, so we prepend a "_" in front of it
			// Example:
			// - File       ..................../plugins/viathinksoft/design/default/*.class.php
			// - Namespace  ViaThinkSoft\OIDplus\Plugins\viathinksoft\design\_default
			$keywords = array('__halt_compiler', 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'fn', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor');
			foreach ($keywords as $keyword) {
				if ($x == '_'.$keyword) {
					$x = $keyword;
				}
			}
		}
	}
	if (str_starts_with($fq_class_name, "ViaThinkSoft\\OIDplus\\Core\\")) {
		require __DIR__ . "/classes/" . $path[3] . ".class.php";
		return;
	}
	if (str_starts_with($fq_class_name, "ViaThinkSoft\\OIDplus\\Plugins\\")) {
		require __DIR__ . "/../plugins/" . $path[3] . "/" . $path[4] . "/" . $path[5] . "/" . $path[6] . ".class.php";
		return;
	}
});

/**
 * This classloader includes vendor/danielmarschall/...
 */
spl_autoload_register(function ($fq_class_name) {
	$path = explode('\\', $fq_class_name);
	$classname_no_namespace = end($path);

	$tmp = __DIR__ . '/../vendor/danielmarschall/fileformats/'.$classname_no_namespace.'.class.php';
	if (file_exists($tmp)) {
		require $tmp;
		return;
	}

	$tmp = __DIR__ . '/../vendor/danielmarschall/php_utils/'.$classname_no_namespace.'.class.php';
	if (file_exists($tmp)) {
		require $tmp;
		return;
	}

	$tmp = __DIR__ . '/../vendor/danielmarschall/oidconverter/php/'.$classname_no_namespace.'.class.phps';
	if (file_exists($tmp)) {
		require $tmp;
		return;
	}

});

/**
 * Interfaces starting with INTF_OID are "optional interfaces". If they are not found by previous autoloaders,
 * then they will be defined by a "fake interface" that contains nothing.
 * For OIDplus, INTF_OID interfaces are used if plugins communicate with other plugins, i.e.
 * a plugin offers a service which another plugin can use. However, if one of the plugins does not exist,
 * PHP shall not crash! So, this idea of "optional interfaces" was born.
 * Previously, we used "implementsFeature()" which acted like Microsoft COM's GUID interfaces,
 * but this had the downside that types could not be checked.
 */
spl_autoload_register(function ($fq_class_name) {
	$path = explode('\\',$fq_class_name);
	$class_name = array_pop($path);
	$namespace = implode('\\',$path);

	if (str_starts_with($class_name, "INTF_OID_")) {
		if (($namespace != "ViaThinkSoft\\OIDplus\\Plugins\\viathinksoft") && str_starts_with($class_name, "INTF_OID_1_3_6_1_4_1_37476_")) {
			throw new Exception(_L('Third-party plugin tries to access a ViaThinkSoft-INTF_OID interface "%1", but is not in the ViaThinkSoft\\OIDplus namespace', $fq_class_name));
		}

		if (($namespace == "ViaThinkSoft\\OIDplus\\Plugins\\viathinksoft") && !str_starts_with($class_name, "INTF_OID_1_3_6_1_4_1_37476_")) {
			throw new Exception(_L('ViaThinkSoft plugin tries to access a Third-Party-INTF_OID interface "%1", but is not in the third-party namespace', $fq_class_name));
		}

		$fake_content = "";
		if ($namespace) $fake_content .= "namespace $namespace;\n\n";
		$fake_content .= "interface $class_name { }\n\n";
		eval($fake_content);
	}
});
