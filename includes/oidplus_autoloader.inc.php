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

spl_autoload_register(function ($fq_class_name) {
	$path = explode('\\',$fq_class_name);
	$tmp = $path;
	$classname_no_namespace = array_pop($tmp);
	$namespace = implode('\\',$tmp).'\\';
	unset($tmp);

	// --- For quality control, check if INTF_OID_ interfaces have a "legal" OID

	if (str_starts_with($classname_no_namespace, "INTF_OID_")) {
		if (!str_starts_with($namespace, "ViaThinkSoft\\") && str_starts_with($classname_no_namespace, "INTF_OID_1_3_6_1_4_1_37476_")) {
			throw new Exception(_L('Third-party plugin tries to access a ViaThinkSoft-INTF_OID interface "%1", but is not in the ViaThinkSoft\\OIDplus namespace', $fq_class_name));
		}

		if (str_starts_with($namespace, "ViaThinkSoft\\") && !str_starts_with($classname_no_namespace, "INTF_OID_1_3_6_1_4_1_37476_")) {
			throw new Exception(_L('ViaThinkSoft plugin tries to access a Third-Party-INTF_OID interface "%1", but is not in the third-party namespace', $fq_class_name));
		}

		$oid = str_replace('_', '.', substr($classname_no_namespace, strlen("INTF_OID_")));
		if (!oid_valid_dotnotation($oid)) {
			throw new Exception(_L('%1 does not contain a valid OID in its name (expected, e.g. %2)', $fq_class_name, "INTF_OID_2_999"));
		}
	}

	// --- includes includes/classes/... (namespace: ViaThinkSoft\OIDplus\Core\)

	if (str_starts_with($fq_class_name, "ViaThinkSoft\\OIDplus\\Core\\")) {
		if (file_exists($candidate = __DIR__ . "/classes/" . implode("/",array_slice($path, 3)) . ".class.php")) {
			include $candidate;
			return;
		}
	}

	// --- Loading classes of plugins for OIDplus >= 2.0.2

	static $oidplus_autoloader_folders = null;
	if (is_null($oidplus_autoloader_folders)) {
		// Populate the class map
		$oidplus_autoloader_folders = [];
		$ary = OIDplus::getAllPluginManifests('*', true); // note: does filter disabled plugins
		foreach ($ary as $manifest) {
			$oidplus_autoloader_folders[$manifest->getPhpNamespace()] = dirname($manifest->getManifestFile());
		}
	}

	$parts = explode("\\", $fq_class_name);
	for ($i = 1; $i < count($parts); $i++) {
		// For class A\B\C\D: [$a,$b] will be tuples of [A,B\C\D], [A\B,C\D], [A\B\C,D]
		// this allows plugins having sub-namespaces in folders;
		// and it works because plugins need an unique and prefix-free namespace.
	    $a = implode("\\", array_slice($parts, 0, $i));
	    $b = implode("\\", array_slice($parts, $i));
		if (isset($oidplus_autoloader_folders[$a."\\"])) {
			$candidate = $oidplus_autoloader_folders[$a."\\"].DIRECTORY_SEPARATOR.str_replace('\\',DIRECTORY_SEPARATOR,$b).'.class.php';
			if (file_exists($candidate)) {
				include $candidate;
				return;
			}
			break;
		}
	}
	unset($parts);

	// --- include vendor/danielmarschall/...

	if (
		file_exists($tmp = __DIR__ . '/../vendor/danielmarschall/fileformats/'.$classname_no_namespace.'.class.php') ||
		file_exists($tmp = __DIR__ . '/../vendor/danielmarschall/php_utils/'.$classname_no_namespace.'.class.php') ||
		file_exists($tmp = __DIR__ . '/../vendor/danielmarschall/oidconverter/php/'.$classname_no_namespace.'.class.phps')
	) {
		include $tmp;
		return;
	}

	// --- Create fake INTF_OID_ if these optional interfaces were not found

	/*
	 * Interfaces starting with INTF_OID are "optional interfaces". If they are not found by previous autoloaders,
	 * then they will be defined by a "fake interface" that contains nothing.
	 * For OIDplus, INTF_OID interfaces are used if plugins communicate with other plugins, i.e.
	 * a plugin offers a service which another plugin can use. However, if one of the plugins does not exist,
	 * PHP shall not crash! So, this idea of "optional interfaces" was born.
	 * Previously, we used "implementsFeature()" which acted like Microsoft COM's GUID interfaces,
	 * but this had the downside that types could not be checked.
	 */

	if (str_starts_with($classname_no_namespace, "INTF_OID_")) {
		$fake_content = "";
		if ($namespace) $fake_content .= "namespace ".substr($namespace, 0, -1).";\n\n";
		$fake_content .= "interface $classname_no_namespace { }\n\n";
		eval($fake_content);
		return;
	}

});
