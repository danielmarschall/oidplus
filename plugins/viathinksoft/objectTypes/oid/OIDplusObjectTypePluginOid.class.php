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

class OIDplusObjectTypePluginOid extends OIDplusObjectTypePlugin {

	public static function getObjectTypeClassName() {
		return OIDplusOid::class;
	}

	public function implementsFeature($id) {
		if (strtolower($id) == '1.3.6.1.4.1.37476.2.5.2.3.6') return true; // gridGeneratorLinks
		return false;
	}

	public function gridGeneratorLinks($objParent) { // Interface 1.3.6.1.4.1.37476.2.5.2.3.6

		if ($objParent->nodeId() === 'oid:2.25') {
			return '<br><a href="javascript:OIDplusObjectTypePluginOid.generateRandomUUID(false)">('._L('Generate random UUID OID').')</a>';
		} else if ($objParent->isRoot()) {
			return '<br><a href="javascript:OIDplusObjectTypePluginOid.generateRandomUUID(true)">('._L('Generate random UUID OID').')</a>'.
			       '<br><a href="https://oidplus.viathinksoft.com/oidplus/?goto=oidplus:com.viathinksoft.freeoid" target="_blank">('._L('Request free OID from ViaThinkSoft').')</a>'.
			       '<br><a href="https://pen.iana.org/pen/PenApplication.page" target="_blank">('._L('Request free PEN/OID from IANA').')</a>';
		} else {
			// No generation for normal OIDs atm. TODO: MAYBE in future a feature "next free"
			return '';
		}
	}

	public static function prefilterQuery($static_node_id, $throw_exception) {
		// Convert WEID to OID
		// A WEID is just a different notation of an OID.
		// To allow that people use OID-IP or the GoTo-box with a "weid:" identifier, rewrite it to "oid:", so that the plugin OIDplusObjectTypePluginOid can handle it.
		if (str_starts_with($static_node_id,'weid:') && class_exists('WeidOidConverter')) {
			$ary = explode('$', $static_node_id, 2);
			$weid = $ary[0];
			$oid = WeidOidConverter::weid2oid($weid);
			if ($oid === false) {
				if ($throw_exception) throw new OIDplusException('This is not a valid WEID');
			} else {
				$ary[0] = $oid;
				$static_node_id = 'oid:'.implode('$', $ary);
			}
		}

		return $static_node_id;
	}

}
