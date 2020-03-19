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

if (!defined('IN_OIDPLUS')) die();

class OIDplusPagePublicWhois extends OIDplusPagePlugin {
	public function type() {
		return 'public';
	}

	public static function getPluginInformation() {
		$out = array();
		$out['name'] = 'WhoIs';
		$out['author'] = 'ViaThinkSoft';
		$out['version'] = null;
		$out['descriptionHTML'] = null;
		return $out;
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

	private function getExampleId() {
		$firsts = array();
		$first_ns = null;
		foreach (OIDplus::getRegisteredObjectTypes() as $ot) {
			if (is_null($first_ns)) $first_ns = $ot::ns();
			$res = OIDplus::db()->query("SELECT id FROM ".OIDPLUS_TABLENAME_PREFIX."objects WHERE parent = ? ORDER BY id", array($ot::ns().':'));
			if ($row = OIDplus::db()->fetch_array($res))
				$firsts[$ot::ns()] = $row['id'];
		}
		if (count($firsts) == 0) {
			return 'oid:2.999';
		} elseif (isset($firsts['oid'])) {
			return  $firsts['oid'];
		} else {
			return  $firsts[$first_ns];
		}
	}

	public function gui($id, &$out, &$handled) {
		if (explode('$',$id)[0] == 'oidplus:whois') {
			$handled = true;

			$example = $this->getExampleId();

			$out['title'] = 'Web WHOIS';
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/icon_big.png' : '';

			$out['text']  = '';
			$out['text'] .= '<p>With the web based whois service, you can query object information in a machine readable format.</p>';
			$out['text'] .= '<p>RFC draft (for Text format): <a href="plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/whois/rfc/draft-viathinksoft-oidwhois-00.txt">TXT</a> | <a href="plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/whois/rfc/draft-viathinksoft-oidwhois-00.nroff">NROFF</a></p>';
			$out['text'] .= '<form action="plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/whois/webwhois.php" method="GET">';
			$out['text'] .= '	<label class="padding_label">Format:</label><select name="format">';
			$out['text'] .= '		<option value="txt">Text (RFC pending)</option>';
			$out['text'] .= '		<option value="json">JSON</option>';
			$out['text'] .= '		<option value="xml">XML</option>';
			$out['text'] .= '	</select><br>';
			$out['text'] .= '	<label class="padding_label">Query:</label><input type="text" name="query" value="'.htmlentities($example).'" style="width:250px">';
			$out['text'] .= '	<input type="submit" value="Query">';
			$out['text'] .= '</form>';
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/treeicon.png';
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
		$text .= '<br><img src="plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/page_pictogram.png" height="15" alt=""> <a href="plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/whois/webwhois.php?query='.urlencode($id).'" class="gray_footer_font">Whois</a>';
	}

	public function tree_search($request) {
		return false;
	}
}

OIDplus::registerPagePlugin(new OIDplusPagePublicWhois());
