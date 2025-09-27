<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2023 Daniel Marschall, ViaThinkSoft
 * Authors               Daniel Marschall, ViaThinkSoft
 *                       Melanie Wehowski, Frdlweb
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

namespace Frdlweb\OIDplus\Plugins\PublicPages\RDAP;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginPublic;
use ViaThinkSoft\OIDplus\Plugins\PublicPages\Objects\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_2;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusPagePublicRdap extends OIDplusPagePluginPublic
	implements INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_2 /* modifyContent */
{

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_2
	 * @param string $id
	 * @param string $title
	 * @param string $icon
	 * @param string $text
	 * @return void
	 * @throws \ViaThinkSoft\OIDplus\Core\OIDplusException
	 */
	public function modifyContent(string $id, string &$title, string &$icon, string &$text): void {
		$ary = explode(':', $id, 2);
		$ns = $ary[0];
		$id = $ary[1] ?? null;
		$payload = '<br /> <a href="'.OIDplus::webpath(null).
		           'rdap/'.urlencode($ns).'/'.urlencode($id).'" class="gray_footer_font" target="_blank">'._L('RDAP').'</a>';
		$text = str_replace('<!-- MARKER 6 -->', '<!-- MARKER 6 -->'.$payload, $text);
	}

	/**
	 * @param string $request
	 * @return bool
	 * @throws OIDplusException
	 */
	public function handle404(string $request): bool {
		if (!isset($_SERVER['REQUEST_URI']) || !isset($_SERVER["REQUEST_METHOD"])) return false;

		$rel_url = substr($_SERVER['REQUEST_URI'], strlen(OIDplus::webpath(null, OIDplus::PATH_RELATIVE_TO_ROOT)));
		$expect = 'rdap/';
		if (str_starts_with($rel_url, $expect)) {
			originHeaders(); // Allows queries from other domains
			OIDplus::authUtils()->disableCSRF(); // allow access to ajax.php without valid CSRF token

			$rel_url = preg_replace('@^'.preg_quote($expect,'@').'@', '', $rel_url);

			$rel_url = explode('?', $rel_url, 2)[0];
			$ary = explode('/', $rel_url, 2);
			$ns = $ary[0];
			$id = $ary[1] ?? null;
			if ($ns && $id) {
				$query = "$ns:$id";
				$x = new OIDplusRDAP();
				list($out_content, $out_type) = $x->rdapQuery($query);
				if ($out_type) header('Content-Type:'.$out_type);
				echo $out_content;
			}

			OIDplus::invoke_shutdown();
			die(); // return true;
		}

		return false;
	}

}
