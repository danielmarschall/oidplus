<?php

/*
 * OIDplus 2.0
 * Copyright 2019 Daniel Marschall, ViaThinkSoft
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

class OIDplusPagePublicWhois extends OIDplusPagePlugin {
	public function type() {
		return 'public';
	}

	public function priority() {
		return 100;
	}

	public function action(&$handled) {
		// Nothing
	}

	public function init($html=true) {
		OIDplus::config()->prepareConfigKey('whois_auth_token', 'OID-over-WHOIS authentication token to display confidential data', '', 0, 1);
	}

	public function cfgSetValue($name, $value) {
		if ($name == 'whois_auth_token') {
			$test_value = preg_replace('@[0-9a-zA-Z]*@', '', $value);
			if ($test_value != '') {
				throw new Exception("Only characters and numbers are allowed as authentication token.");
			}
		}
	}

	public function gui($id, &$out, &$handled) {
		if (explode('$',$id)[0] == 'oidplus:whois') {
			$handled = true;

			$out['title'] = 'Web WHOIS';
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/publicPages/'.basename(__DIR__).'/icon_big.png' : '';

			$out['text']  = '';
			$out['text'] .= '<p>With the web based whois service, you can query object information in a machine readable format.</p>';
			$out['text'] .= '<p>RFC draft: <a href="plugins/publicPages/'.basename(__DIR__).'/whois/rfc/draft-viathinksoft-oidwhois-00.txt">TXT</a> | <a href="plugins/publicPages/'.basename(__DIR__).'/whois/rfc/draft-viathinksoft-oidwhois-00.nroff">NROFF</a></p>';
			$out['text'] .= '<form action="plugins/publicPages/'.basename(__DIR__).'/whois/webwhois.php" method="GET">';
			$out['text'] .= '	<input type="text" name="query" value="oid:2.999">';
			$out['text'] .= '	<input type="submit" value="Query">';
			$out['text'] .= '</form>';
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false) {
		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = 'plugins/publicPages/'.basename(__DIR__).'/treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:whois',
			'icon' => $tree_icon,
			'text' => 'Web WHOIS'
		);

		return true;
	}

	public function modifyContent($id, &$title, &$icon, &$text) {
		$text .= '<br><img src="plugins/publicPages/'.basename(__DIR__).'/page_pictogram.png" height="15" alt=""> <a href="plugins/publicPages/'.basename(__DIR__).'/whois/webwhois.php?query='.urlencode($id).'" class="gray_footer_font">Whois</a>';
	}
}

OIDplus::registerPagePlugin(new OIDplusPagePublicWhois());
