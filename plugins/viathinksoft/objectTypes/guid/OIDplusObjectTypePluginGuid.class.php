<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2021 Daniel Marschall, ViaThinkSoft
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

if (!defined('INSIDE_OIDPLUS')) die();

class OIDplusObjectTypePluginGuid extends OIDplusObjectTypePlugin {

	public static function getObjectTypeClassName() {
		return OIDplusGuid::class;
	}

	public function implementsFeature($id) {
		if (strtolower($id) == '1.3.6.1.4.1.37476.2.5.2.3.6') return true; // gridGeneratorLinks
		return false;
	}

	public function gridGeneratorLinks($objParent) { // Interface 1.3.6.1.4.1.37476.2.5.2.3.6
		return '<br><a href="javascript:OIDplusObjectTypePluginGuid.generateRandomGUID(false)">('._L('Generate a random GUID').')</a>';
	}

	public static function prefilterQuery($static_node_id, $throw_exception) {
		// Redirect UUID to GUID
		// The OID-IP Internet Draft writes at section "Alternative Namespaces":
		//     "If available, a formal URN namespace identifier (as defined in RFC\08141, section\05.1 [RFC8141]) SHOULD be used, e.g. 'uuid' should be used instead of 'guid'."
		// However, our plugin OIDplusObjectTypePluginGuid serves the namespace "guid".
		// Therefore redirect "uuid" to "guid", so that people can use OID-IP or the GoTo-box with an "uuid:" input
		$static_node_id = preg_replace('@^uuid:@', 'guid:', $static_node_id);

		return $static_node_id;
	}

}
