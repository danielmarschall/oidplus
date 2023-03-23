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

class OIDplusObjectTypePluginAid extends OIDplusObjectTypePlugin {

	/**
	 * @return string
	 */
	public static function getObjectTypeClassName(): string {
		return OIDplusAid::class;
	}

	/**
	 * @param string $id
	 * @return bool
	 */
	public function implementsFeature(string $id): bool {
		if (strtolower($id) == '1.3.6.1.4.1.37476.2.5.2.3.6') return true; // gridGeneratorLinks
		return false;
	}

	/**
	 * Implements interface 1.3.6.1.4.1.37476.2.5.2.3.6
	 * @param $objParent
	 * @return string
	 */
	public function gridGeneratorLinks($objParent): string {
		if ($objParent->isRoot()) {
			return '<br><a href="javascript:OIDplusObjectTypePluginAid.generateRandomAID()">('._L('Generate a random AID - not unique!').')</a>'.
			       '<br><a href="https://oidplus.viathinksoft.com/oidplus/?goto=aid%3AD276000186F" target="_blank">('._L('Request a free AID from ViaThinkSoft').')</a>';
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
