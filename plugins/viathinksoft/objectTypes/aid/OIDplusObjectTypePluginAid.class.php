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

namespace ViaThinkSoft\OIDplus\Plugins\ObjectTypes\AID;

use ViaThinkSoft\OIDplus\Core\OIDplusObject;
use ViaThinkSoft\OIDplus\Core\OIDplusObjectTypePlugin;
use ViaThinkSoft\OIDplus\Plugins\PublicPages\Objects\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_6;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusObjectTypePluginAid extends OIDplusObjectTypePlugin
	implements INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_6 /* gridGeneratorLinks */
{

	/**
	 * @return string
	 */
	public static function getObjectTypeClassName(): string {
		return OIDplusAid::class;
	}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_6
	 * @param OIDplusObject $objParent
	 * @return string
	 */
	public function gridGeneratorLinks(OIDplusObject $objParent): string {
		if ($objParent->isRoot()) {
			return '<br><a href="javascript:OIDplusObjectTypePluginAid.generateRandomAID('.js_escape($objParent->nodeId(false)).')">('._L('Generate a random AID - not unique!').')</a>'.
			       '<br><a href="https://hosted.oidplus.com/viathinksoft/?goto=aid%3AD276000186F" target="_blank">('._L('Request a free AID from ViaThinkSoft').')</a>';
		} else if (!$objParent->isLeafNode()) {
			if (substr($objParent->nodeId(false),0,1) == 'F') {
				return '<br><a href="javascript:OIDplusObjectTypePluginAid.generateRandomAID('.js_escape($objParent->nodeId(false)).')">('._L('Generate a random AID - not unique!').')</a>';
			} else {
				return '<br><a href="javascript:OIDplusObjectTypePluginAid.generateRandomAID('.js_escape($objParent->nodeId(false)).')">('._L('Generate a random AID').')</a>';
			}
		} else {
			return '';
		}
	}

	/**
	 * @param string $static_node_id
	 * @param bool $throw_exception
	 * @return string
	 */
	public static function prefilterQuery(string $static_node_id, bool $throw_exception): string {
		if (str_starts_with($static_node_id,'aid:')) {
			$static_node_id = str_replace(' ', '', $static_node_id);

			$tmp = explode(':',$static_node_id,2);
			if (isset($tmp[1])) $tmp[1] = strtoupper($tmp[1]);
			$static_node_id = implode(':',$tmp);
		}
		return $static_node_id;
	}

}
