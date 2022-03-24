<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2022 Daniel Marschall, ViaThinkSoft
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

class OIDplusPagePublicWhois extends OIDplusPagePluginPublic {

	public function init($html=true) {
		OIDplus::config()->prepareConfigKey('whois_auth_token',                       'OID-over-WHOIS authentication token to display confidential data', '', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			$test_value = preg_replace('@[0-9a-zA-Z]*@', '', $value);
			if ($test_value != '') {
				throw new OIDplusException(_L('Only characters and numbers are allowed as authentication token.'));
			}
		});
		OIDplus::config()->prepareConfigKey('webwhois_output_format_spacer',          'WebWHOIS: Spacer', '2', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < 0)) {
				throw new OIDplusException(_L('Please enter a valid value.'));
			}
		});
		OIDplus::config()->prepareConfigKey('webwhois_output_format_max_line_length', 'WebWHOIS: Max line length', '80', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < 0)) {
				throw new OIDplusException(_L('Please enter a valid value.'));
			}
		});
		OIDplus::config()->prepareConfigKey('individual_whois_server', 'A WHOIS/OID-IP "hostname:port" that will be presented', '', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if ($value == '') return;
			$ary = explode(':', $value);
			if (count($ary) !== 2) {
				throw new OIDplusException(_L('Please enter either an empty string or an input in the format "hostname:port".'));
			}
			// TODO: verify hostname $ary[0]
			// TODO: verify port $ary[1]
		});
	}

	private function getExampleId() {
		$firsts = array();
		$first_ns = null;
		foreach (OIDplus::getEnabledObjectTypes() as $ot) {
			$res = OIDplus::db()->query("SELECT id FROM ###objects WHERE parent = ? ORDER BY id", array($ot::ns().':'));
			if ($row = $res->fetch_array()) {
				if (is_null($first_ns)) $first_ns = $ot::ns();
				$firsts[$ot::ns()] = $row['id'];
			}
		}
		if ((count($firsts) == 0) || is_null($first_ns)) {
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

			$out['title'] = _L('OID Information Protocol (OID-IP) / WHOIS');
			$out['icon'] = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,true).'img/main_icon.png' : '';

			$out['text']  = '';
			$out['text'] .= '<p>'._L('With the OID Information Protocol (OID-IP), you can query object information in a machine-readable format.').'</p>';
			$out['text'] .= '<p>'._L('RFC Internet Draft').': <a target="_blank" href="https://datatracker.ietf.org/doc/draft-viathinksoft-oidip/">draft-viathinksoft-oidip-02</a></p>';
			$out['text'] .= '<h2>'._L('Web query').'</h2>';
			$out['text'] .= '<form action="'.OIDplus::webpath(__DIR__,true).'whois/webwhois.php" method="GET" target="_blank">';
			$out['text'] .= ''._L('Output format').':<br><fieldset id="whois_format">';
			$out['text'] .= '    <input type="radio" id="text" name="format" value="text" checked onclick="OIDplusPagePublicWhois.refresh_whois_url_bar()">';
			$out['text'] .= '    <label for="text"> '._L('Text format').'</label><br>';
			$out['text'] .= '    <input type="radio" id="json" name="format" value="json" onclick="OIDplusPagePublicWhois.refresh_whois_url_bar()">';
			$out['text'] .= '    <label for="json"> '._L('JSON').'</label> (<a target="_blank" href="'.OIDplus::webpath(__DIR__,true).'whois/json_schema.json">'._L('Schema').'</a>)<br>';
			$out['text'] .= '    <input type="radio" id="xml" name="format" value="xml" onclick="OIDplusPagePublicWhois.refresh_whois_url_bar()">';
			$out['text'] .= '    <label for="xml"> '._L('XML').'</label> (<a target="_blank" href="'.OIDplus::webpath(__DIR__,true).'whois/xml_schema.xsd">'._L('Schema').'</a>)<br>';
			//$out['text'] .= '    <input type="radio" id="html" name="format" value="html" onclick="OIDplusPagePublicWhois.refresh_whois_url_bar()">';
			//$out['text'] .= '    <label for="html"> '._L('HTML').'</label><br>';
			$out['text'] .= '</fieldset><br>';
			$out['text'] .= '	<!--<label class="padding_label">-->'._L('Query').':<!--</label>--> <input type="text" id="whois_query" name="query" value="'.htmlentities($example).'" style="width:250px" onkeyup="OIDplusPagePublicWhois.refresh_whois_url_bar()">';
			$out['text'] .= '	<input type="submit" value="'._L('Query').'">';
			$out['text'] .= '</form>';
			$out['text'] .= '<div id="whois_url_bar_section" style="display:none">';
			$out['text'] .= '	<p><pre id="whois_url_bar"></pre></p>';
			$out['text'] .= '	<input type="button" value="'._L('Copy to clipboard').'" onClick="OIDplusPagePublicWhois.copyToClipboard(whois_url_bar)">';
			$out['text'] .= '</div>';

			$whois_server = '';
			if (OIDplus::config()->getValue('individual_whois_server', '') != '') {
				$whois_server = OIDplus::config()->getValue('individual_whois_server', '');
			}
			else if (OIDplus::config()->getValue('vts_whois', '') != '') {
				// This config setting is set by the "Registration" plugin
				$whois_server = OIDplus::config()->getValue('vts_whois', '');
			}

			if ($whois_server != '') {
				$out['text'] .= '<h2>'._L('WHOIS/OID-IP access').'</h2>';
				$out['text'] .= '<p>'._L('You can use any WHOIS compatible client to query the information from the WHOIS/OID-IP port.').'</p>';
				$out['text'] .= '<p>'._L('The hostname and port number is:').'</p>';
				$out['text'] .= '<p><pre>'.htmlentities($whois_server).'</pre></p>';
			}
			$out['text'] .= '<script> OIDplusPagePublicWhois.refresh_whois_url_bar(); </script>';
		}
	}

	public function publicSitemap(&$out) {
		$out[] = 'oidplus:whois';
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (file_exists(__DIR__.'/img/main_icon16.png')) {
			$tree_icon = OIDplus::webpath(__DIR__,true).'img/main_icon16.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:whois',
			'icon' => $tree_icon,
			'text' => _L('OID-IP / WHOIS')
		);

		return true;
	}

	public function implementsFeature($id) {
		if (strtolower($id) == '1.3.6.1.4.1.37476.2.5.2.3.2') return true; // modifyContent
		return false;
	}

	public function modifyContent($id, &$title, &$icon, &$text) {
		// Interface 1.3.6.1.4.1.37476.2.5.2.3.2

		$text .= '<br><img src="'.OIDplus::webpath(__DIR__,true).'img/page_pictogram.png" height="15" alt=""> <a href="'.OIDplus::webpath(__DIR__,true).'whois/webwhois.php?query='.urlencode($id).'" class="gray_footer_font" target="_blank">'._L('Whois').'</a>';

		$obj = OIDplusObject::parse($id);
		if ($obj->userHasParentalWriteRights()) {
			$text .= '<br><span class="gray_footer_font">'._L('OID-WHOIS Auth Token for displaying full object information: %1 (only applies if the this or superior objects are marked confidential)','<b>'.self::genWhoisAuthToken($id).'</b>').'</span>';
			$text .= '<br><span class="gray_footer_font">'._L('OID-WHOIS Auth Token for displaying full RA information: %1 (only applies if the RA has set the privacy-flag)','<b>'.self::genWhoisAuthToken('ra:'.$obj->getRaMail()).'</b>').'</span>';
		}

	}

	public function tree_search($request) {
		return false;
	}

	public static function genWhoisAuthToken($id) {
		return smallhash(OIDplus::baseConfig()->getValue('SERVER_SECRET').'/WHOIS/'.$id);
	}
}
