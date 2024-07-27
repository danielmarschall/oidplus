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

namespace ViaThinkSoft\OIDplus\Plugins\frdl\publicPages\n1276945_rdap;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginPublic;
use ViaThinkSoft\OIDplus\Plugins\viathinksoft\publicPages\n000_objects\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_2;

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
	public function modifyContent(string $id, string &$title, string &$icon, string &$text) {
	    $payload = '<br /> <a href="'.OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE)
			.'rdap/rdap.php?query='.urlencode($id).'" class="gray_footer_font" target="_blank">'._L('RDAP').'</a>';

		$text = str_replace('<!-- MARKER 6 -->', '<!-- MARKER 6 -->'.$payload, $text);
	}

	/**
	 * @param string $request
	 * @return bool
	 * @throws OIDplusException
	 */
	public function handle404(string $request): bool {
		$namespaces = array();
		foreach (OIDplus::getEnabledObjectTypes() as $ot) {
			$namespaces[] = $ot::ns();
		}
		foreach ($namespaces as $ns) {
			// Note: This only works if OIDplus is located at the domain root (because $request is relative to the domain)
			if (!preg_match('@^/'.preg_quote($ns,'@').'/(.+)$@', $request, $m)) return false;
			$oid = $m[1];
			$query = "$ns:$oid";
			$x = new OIDplusRDAP();
			list($out_content, $out_type) = $x->rdapQuery($query);
			if ($out_type) header('Content-Type:'.$out_type);
			echo $out_content;
			die();
			// return true;
		}
		return false;
	}

}
