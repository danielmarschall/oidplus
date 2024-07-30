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

$oidplus_autoloader_folders = [];

/**
 * Autoloader for OIDplus Plugins >= 2.0.2 (Namespace=>Folder mapping via manifest.json)
 */
spl_autoload_register(function ($fq_class_name) {
	$path = explode('\\', $fq_class_name);
	$classname_no_namespace = array_pop($path);
	$namespace = implode('\\', $path).'\\';

	global $oidplus_autoloader_folders;
	if (isset($oidplus_autoloader_folders[$namespace])) {
		$candidate = $oidplus_autoloader_folders[$namespace].'/'.$classname_no_namespace.'.class.php';
		if (file_exists($candidate)) {
			include $candidate;
		}
	}
});

/**
 * This classloader includes includes/classes/... (namespace: ViaThinkSoft\OIDplus\Core\)
 */
spl_autoload_register(function ($fq_class_name) {
	$path = explode('\\', $fq_class_name);
	$classname_no_namespace = end($path);
	if (str_starts_with($fq_class_name, "ViaThinkSoft\\OIDplus\\Core\\")) {
		require __DIR__ . "/classes/" . implode("/",array_slice($path, 3)) . ".class.php";
	}
});

/**
 * This classloader includes vendor/danielmarschall/...
 */
spl_autoload_register(function ($fq_class_name) {
	$path = explode('\\', $fq_class_name);
	$classname_no_namespace = end($path);
	if (
		file_exists($tmp = __DIR__ . '/../vendor/danielmarschall/fileformats/'.$classname_no_namespace.'.class.php') ||
		file_exists($tmp = __DIR__ . '/../vendor/danielmarschall/php_utils/'.$classname_no_namespace.'.class.php') ||
		file_exists($tmp = __DIR__ . '/../vendor/danielmarschall/oidconverter/php/'.$classname_no_namespace.'.class.phps')
	) {
		require $tmp;
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
	$classname_no_namespace = array_pop($path);
	$namespace = implode('\\',$path);

	if (str_starts_with($classname_no_namespace, "INTF_OID_")) {
		if (!str_starts_with($namespace, "ViaThinkSoft\\") && str_starts_with($classname_no_namespace, "INTF_OID_1_3_6_1_4_1_37476_")) {
			throw new Exception(_L('Third-party plugin tries to access a ViaThinkSoft-INTF_OID interface "%1", but is not in the ViaThinkSoft\\OIDplus namespace', $fq_class_name));
		}

		if (str_starts_with($namespace, "ViaThinkSoft\\") && !str_starts_with($classname_no_namespace, "INTF_OID_1_3_6_1_4_1_37476_")) {
			throw new Exception(_L('ViaThinkSoft plugin tries to access a Third-Party-INTF_OID interface "%1", but is not in the third-party namespace', $fq_class_name));
		}

		$fake_content = "";
		if ($namespace) $fake_content .= "namespace $namespace;\n\n";
		$fake_content .= "interface $classname_no_namespace { }\n\n";
		eval($fake_content);
	}
});
