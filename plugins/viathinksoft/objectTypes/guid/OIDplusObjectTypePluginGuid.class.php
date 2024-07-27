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

namespace ViaThinkSoft\OIDplus\Plugins\viathinksoft\objectTypes\guid;

use ViaThinkSoft\OIDplus\Core\OIDplusObject;
use ViaThinkSoft\OIDplus\Core\OIDplusObjectTypePlugin;
use ViaThinkSoft\OIDplus\Plugins\viathinksoft\publicPages\n000_objects\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_6;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusObjectTypePluginGuid extends OIDplusObjectTypePlugin
	implements INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_6 /* gridGeneratorLinks */
{

	/**
	 * @return string
	 */
	public static function getObjectTypeClassName(): string {
		return OIDplusGuid::class;
	}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_6
	 * @param OIDplusObject $objParent
	 * @return string
	 */
	public function gridGeneratorLinks(OIDplusObject $objParent): string {
		return '<br><a href="javascript:OIDplusObjectTypePluginGuid.generateRandomGUID(false)">('._L('Generate a random GUID').')</a>';
	}

	/**
	 * @param string $static_node_id
	 * @param bool $throw_exception
	 * @return string
	 */
	public static function prefilterQuery(string $static_node_id, bool $throw_exception): string {
		// Redirect UUID to GUID
		// The OID-IP Internet Draft writes at section "Alternative Namespaces":
		//     "If available, a formal URN namespace identifier (as defined in RFC\08141, section\05.1 [RFC8141]) SHOULD be used, e.g. 'uuid' should be used instead of 'guid'."
		// However, our plugin OIDplusObjectTypePluginGuid serves the namespace "guid".
		// Therefore redirect "uuid" to "guid", so that people can use OID-IP or the GoTo-box with an "uuid:" input
		$static_node_id = preg_replace('@^uuid:@', 'guid:', $static_node_id);

		// If someone enters a valid GUID/UUID in the goto-box, prepend "guid:" to it.
		if (uuid_valid($static_node_id)) {
			$static_node_id = uuid_canonize($static_node_id); // optional: canonize it
			$static_node_id = 'guid:'.$static_node_id;
		}

		return $static_node_id;
	}

}
