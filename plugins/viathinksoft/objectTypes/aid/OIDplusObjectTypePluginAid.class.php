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

if (!defined('INSIDE_OIDPLUS')) die();

class OIDplusObjectTypePluginAid extends OIDplusObjectTypePlugin {

	public static function getObjectTypeClassName() {
		return OIDplusAid::class;
	}

	public function implementsFeature($id) {
		if (strtolower($id) == '1.3.6.1.4.1.37476.2.5.2.3.6') return true; // gridGeneratorLinks
		return false;
	}

	public function gridGeneratorLinks($objParent) { // Interface 1.3.6.1.4.1.37476.2.5.2.3.6

		if ($objParent->isRoot()) {
			return '<br><a href="javascript:OIDplusObjectTypePluginAid.generateRandomAID()">('._L('Generate a random AID - not unique!').')</a>'.
			       '<br><a href="https://oidplus.viathinksoft.com/oidplus/?goto=aid%3AD276000186F" target="_blank">('._L('Request a free AID from ViaThinkSoft').')</a>';
		} else {
			return '';
		}
	}

	public static function prefilterQuery($static_node_id, $throw_exception) {
		if (str_starts_with($static_node_id,'aid:')) {
			$static_node_id = str_replace(' ', '', $static_node_id);

			$tmp = explode(':',$static_node_id,2);
			if (isset($tmp[1])) $tmp[1] = strtoupper($tmp[1]);
			$static_node_id = implode(':',$tmp);
		}
		return $static_node_id;
	}

}
