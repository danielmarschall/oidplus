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

class OIDplusObjectTypePluginMac extends OIDplusObjectTypePlugin {

	/**
	 * @return string
	 */
	public static function getObjectTypeClassName(): string {
		return OIDplusMac::class;
	}

	/**
	 * @param string $static_node_id
	 * @param bool $throw_exception
	 * @return string
	 */
	public static function prefilterQuery(string $static_node_id, bool $throw_exception): string {
		$static_node_id = preg_replace('@^eui:@', 'mac:', $static_node_id);
		$static_node_id = preg_replace('@^eli:@', 'mac:', $static_node_id);

		if (str_starts_with($static_node_id,'mac:')) {
			$static_node_id = str_replace(' ', '', $static_node_id);
		}

		// Special treatment for OIDs: if someone enters a valid MAC address in the goto box, prepend "mac:"
		if (((strpos($static_node_id, ':') !== false) || (strpos($static_node_id, '-') !== false)) && mac_valid($static_node_id)) {
			$static_node_id = 'mac:'.$static_node_id;
		}

		return $static_node_id;
	}

}
