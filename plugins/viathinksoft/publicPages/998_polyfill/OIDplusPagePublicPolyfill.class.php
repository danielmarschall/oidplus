<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2024 Daniel Marschall, ViaThinkSoft
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

namespace ViaThinkSoft\OIDplus\Plugins\PublicPages\Polyfill;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginPublic;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusPagePublicPolyfill extends OIDplusPagePluginPublic {

	/**
	 * @param string $html
	 * @return void
	 */
	public function htmlPostprocess(string &$html): void {
		$tmp = (OIDplus::insideSetup()) ? '?noBaseConfig=1' : '';
		$scrTag = '<script src="'.htmlentities(OIDplus::webpath(__DIR__, OIDplus::PATH_RELATIVE)?:'').'polyfill.min.js.php'.$tmp.'"></script>';

		$html = preg_replace('|(<head([^>]*)>)|imU', "\\1\n\t".str_replace('\\', '\\\\', $scrTag), $html) ?? $html;
	}

}
