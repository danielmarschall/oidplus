<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2022 Daniel Marschall, ViaThinkSoft
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

spl_autoload_register(function ($fq_class_name) {
	static $class_refs = null;

	// We only load based on the last element of the class name (ignore namespace)
	// If there are multiple classes matching that name we just include all class files
	$path = explode('\\',$fq_class_name);
	$class_name = array_pop($path);
	$namespace = implode('\\',$path);

	if (is_null($class_refs)) {
		$valid_plugin_folders = array(
			'adminPages',
			'auth',
			'database',
			'design',
			'language',
			'logger',
			'objectTypes',
			'publicPages',
			'raPages',
			'sqlSlang',
			'captcha'
		);

		$func = function(&$class_refs, $class_files, $namespace='') {
			foreach ($class_files as $filename) {
				$cn = strtolower(basename($filename));
				$cn = preg_replace('@(\\.class){0,1}\\.phps{0,1}$@', '', $cn);
				if (!empty($namespace)) {
					if (substr($namespace,-1,1) !== '\\') $namespace .= '\\';
					$cn = strtolower($namespace) . $cn;
				}
				if (!isset($class_refs[$cn])) {
					$class_refs[$cn] = array($filename);
				} else {
					$class_refs[$cn][] = $filename;
				}
			}
		};

		$class_files = array();

		// Global namespace / OIDplus
		// (the last has the highest priority)
		foreach ($valid_plugin_folders as $folder) {
			$class_files = array_merge($class_files, glob(__DIR__ . '/../plugins/'.'*'.'/'.$folder.'/'.'*'.'/'.'*'.'.class.php'));
		}
		$class_files = array_merge($class_files, glob(__DIR__ . '/classes/'.'*'.'.class.php'));
		$class_files = array_merge($class_files, glob(__DIR__ . '/../vendor/danielmarschall/fileformats/'.'*'.'.class.php'));
		$class_files = array_merge($class_files, glob(__DIR__ . '/../vendor/danielmarschall/php_utils/'.'*'.'.class.php'));
		$class_files = array_merge($class_files, glob(__DIR__ . '/../vendor/danielmarschall/oidconverter/php/'.'*'.'.class.phps'));
		$func($class_refs, $class_files);
	}

	$class_name = strtolower($class_name);
	if (isset($class_refs[$class_name])) {
		foreach ($class_refs[$class_name] as $inc) {
			require $inc;
		}
		unset($class_refs[$class_name]); // this emulates a "require_once" and is faster
	}
});

/*
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

	if (($namespace != "ViaThinkSoft\\OIDplus") && str_starts_with($class_name, "INTF_OID_1_3_6_1_4_1_37476_")) {
		throw new Exception(_L('Third-party plugin tries to access a ViaThinkSoft-INTF_OID interface "%1", but is not in the ViaThinkSoft\\OIDplus namespace', $fq_class_name));
	}

	if (($namespace == "ViaThinkSoft\\OIDplus") && !str_starts_with($class_name, "INTF_OID_1_3_6_1_4_1_37476_")) {
		throw new Exception(_L('ViaThinkSoft plugin tries to access a Third-Party-INTF_OID interface "%1", but is not in the third-party namespace', $fq_class_name));
	}

	if (str_starts_with($class_name, "INTF_OID_")) {
		$fake_content = "";
		if ($namespace) $fake_content .= "namespace $namespace;\n\n";
		$fake_content .= "interface $class_name { }\n\n";
		eval($fake_content);
	}
});
