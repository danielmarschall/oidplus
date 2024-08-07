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

namespace ViaThinkSoft\OIDplus\Plugins\ObjectTypes\X500DN;

use ViaThinkSoft\OIDplus\Core\OIDplusObject;
use ViaThinkSoft\OIDplus\Core\OIDplusObjectTypePlugin;
use ViaThinkSoft\OIDplus\Plugins\PublicPages\Objects\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_6;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusObjectTypePluginx500DN extends OIDplusObjectTypePlugin
	implements INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_6 /* gridGeneratorLinks */
{

	/**
	 * @return string
	 */
	public static function getObjectTypeClassName(): string {
		return OIDplusX500DN::class;
	}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_6
	 * @param OIDplusObject $objParent
	 * @return string
	 */
	public function gridGeneratorLinks(OIDplusObject $objParent): string {
		if ($objParent->isRoot()) {
			return '<br><font size="-2">'._L('Format: %1', '/c=de/o=ViaThinkSoft/ou=Development').'</font>';
		} else {
			return '<br><font size="-2">'._L('Format: %1', 'ou=Development').'</font>';
		}
	}

}
