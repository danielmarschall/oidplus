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

namespace ViaThinkSoft\OIDplus\Plugins\viathinksoft\objectTypes\oid;

use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusObject;
use ViaThinkSoft\OIDplus\Core\OIDplusObjectTypePlugin;
use ViaThinkSoft\OIDplus\Plugins\viathinksoft\publicPages\n000_objects\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_6;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusObjectTypePluginOid extends OIDplusObjectTypePlugin
	implements INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_6 /* gridGeneratorLinks */
{

	/**
	 * @return string
	 */
	public static function getObjectTypeClassName(): string {
		return OIDplusOid::class;
	}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_6
	 * @param OIDplusObject $objParent
	 * @return string
	 */
	public function gridGeneratorLinks(OIDplusObject $objParent): string {
		if ($objParent->nodeId() === 'oid:2.25') {
			return '<br><a href="javascript:OIDplusObjectTypePluginOid.generateRandomUUID(false)">('._L('Generate a random UUID OID').')</a>';
		} else if ($objParent->isRoot()) {
			return '<br><a href="javascript:OIDplusObjectTypePluginOid.generateRandomUUID(true)">('._L('Generate a random UUID OID').')</a>'.
			       '<br><a href="https://hosted.oidplus.com/viathinksoft/?goto=oidplus%3Acom.viathinksoft.freeoid" target="_blank">('._L('Request a free OID from ViaThinkSoft').')</a>'.
			       '<br><a href="https://pen.iana.org/pen/PenApplication.page" target="_blank">('._L('Request a free PEN/OID from IANA').')</a>';
		} else {
			// No generation for normal OIDs atm. TODO: MAYBE in the future a feature like "next free / sequencial OID"
			return '';
		}
	}

	/**
	 * @param string $static_node_id
	 * @param bool $throw_exception
	 * @return string
	 * @throws OIDplusException
	 */
	public static function prefilterQuery(string $static_node_id, bool $throw_exception): string {
		// Convert WEID to OID
		// A WEID is just a different notation of an OID.
		// To allow that people use OID-IP or the GoTo-box with a "weid:" identifier, rewrite it to "oid:", so that the plugin OIDplusObjectTypePluginOid can handle it.
		if (str_starts_with($static_node_id,'weid:')) {
			$ary = explode('$', $static_node_id, 2);
			$weid = $ary[0];
			$oid = WeidOidConverter::weid2oid($weid);
			if ($oid === false) {
				if ($throw_exception) throw new OIDplusException(_L('This is not a valid WEID'));
			} else {
				$ary[0] = $oid;
				$static_node_id = 'oid:'.implode('$', $ary);
			}
		}

		// Special treatment for OIDs: if someone enters an OID in the goto box,
		// prepend "oid:"
		if (sanitizeOID($static_node_id) !== false) {
			$static_node_id = 'oid:'.$static_node_id;
		}

		return $static_node_id;
	}

}
