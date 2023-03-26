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

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

abstract class OIDplusBaseClass {

	/**
	 * Use this function to query the plugin if it supports an INTF_OID_* interface.
	 * Interfaces which have the prefix INTF_OID_, following by an OID (underscore instead of dots)
	 * are specially handled by OIDplus. If they do not exist (e.g. because their plugin is not installed),
	 * then they are replaced with an empty interface by the OIDplus autoloader.
	 * @param string $oid
	 * @return bool
	 * @deprecated use "$x instanceof INTF_OID_..." instead, to allow type checking
	 */
	public final function implementsFeature(string $oid): bool {
		$interface_name = "INTF_OID_".str_replace('.', '_', $oid);
		return in_array($interface_name, class_implements($this));
	}

}
