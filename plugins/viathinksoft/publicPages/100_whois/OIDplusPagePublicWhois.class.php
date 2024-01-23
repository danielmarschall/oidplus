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

class OIDplusPagePublicWhois extends OIDplusPagePluginPublic
	implements INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_2 /* modifyContent */
{
	/**
	 * @param bool $html
	 * @return void
	 * @throws OIDplusException
	 */
	public function init(bool $html=true) {
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

	/**
	 * @return mixed|string
	 * @throws OIDplusException
	 */
	private function getExampleId() {
		$firsts = array();
		$first_ns = null;
		foreach (OIDplus::getEnabledObjectTypes() as $ot) {
			$res = OIDplus::db()->query("select id FROM ###objects where parent = ?", array($ot::ns().':'));
			$res->naturalSortByField('id');
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

	/**
	 * @param string $id
	 * @param array $out
	 * @param bool $handled
	 * @return void
	 * @throws OIDplusException
	 */
	public function gui(string $id, array &$out, bool &$handled) {
		if (explode('$',$id)[0] == 'oidplus:whois') {
			$handled = true;

			$example = $this->getExampleId();

			$whois_server = '';
			if (OIDplus::config()->getValue('individual_whois_server', '') != '') {
				$whois_server = OIDplus::config()->getValue('individual_whois_server', '');
			}
			else if (OIDplus::config()->getValue('vts_whois', '') != '') {
				// This config setting is set by the "Registration" plugin
				$whois_server = OIDplus::config()->getValue('vts_whois', '');
			}

			$out['title'] = _L('OID Information Protocol (OID-IP) / WHOIS');
			$out['icon'] = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

			$out['text']  = '<p>'._L('With the OID Information Protocol (OID-IP), you can query object information in a format that is human-readable and machine-readable.').'</p>';

			// Use this if webwhois.php matches the currently uploaded Internet Draft:
			$out['text'] .= '<p>'._L('RFC Internet Draft').': <a target="_blank" href="https://datatracker.ietf.org/doc/draft-viathinksoft-oidip/">draft-viathinksoft-oidip-07</a></p>';
			// Use this if webwhois.php implements something which is not yet uploaded to IETF:
			//$out['text'] .= '<p>'._L('RFC Internet Draft').': <a href="'.OIDplus::webpath(__DIR__.'/whois/rfc/draft-viathinksoft-oidip-07.txt', true).'" target="_blank">draft-viathinksoft-oidip-07</a></p>';
			# ---
			$out['text'] .= '<noscript>';
			$out['text'] .= '<p><font color="red">'._L('You need to enable JavaScript to use this feature.').'</font></p>';
			$out['text'] .= '</noscript>';
			$out['text'] .= '<div id="oidipArea" style="display:none">';
			$out['text'] .= '<h2>'._L('Parameters for new request').'</h2>';
			# ---
			$out['text'] .= _L('Requested object including namespace, e.g. %1','<code>oid:2.999</code>').'<br>';
			$out['text'] .= '<input type="text" id="whois_query" name="query" value="'.htmlentities($example).'" onkeyup="OIDplusPagePublicWhois.refresh_whois_url_bar()">';
			$out['text'] .= '&nbsp;<span id="whois_query_invalid" style="display:none"><font color="red"><b>('._L('Invalid').')</b></font></span>';
			$out['text'] .= '<br><br>';
			# ---
			$out['text'] .= _L('Output format').':<br><fieldset id="whois_format">';
			$out['text'] .= '    <input type="radio" id="text" name="format" value="text" checked onclick="OIDplusPagePublicWhois.refresh_whois_url_bar()">';
			$out['text'] .= '    <label for="text"><code>$format=text</code> '._L('Text format').'</label><br>';
			$out['text'] .= '    <input type="radio" id="json" name="format" value="json" onclick="OIDplusPagePublicWhois.refresh_whois_url_bar()">';
			$out['text'] .= '    <label for="json"><code>$format=json</code>  '._L('JSON format').'</label> (<a target="_blank" href="'.OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'whois/draft-viathinksoft-oidip-07.json">'._L('Schema').'</a>)<br>';
			$out['text'] .= '    <input type="radio" id="xml" name="format" value="xml" onclick="OIDplusPagePublicWhois.refresh_whois_url_bar()">';
			$out['text'] .= '    <label for="xml"><code>$format=xml</code>  '._L('XML format').'</label> (<a target="_blank" href="'.OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'whois/draft-viathinksoft-oidip-07.xsd">'._L('Schema').'</a>)<br>';
			$out['text'] .= '</fieldset><br>';
			# ---
			$out['text'] .= _L('Authentication token(s), comma separated (optional)').':<br>';
			$out['text'] .= '<code>$auth = </code><input type="text" id="whois_auth" name="auth" value="" onkeyup="OIDplusPagePublicWhois.refresh_whois_url_bar()">';
			$out['text'] .= '&nbsp;<span id="whois_auth_invalid" style="display:none"><font color="red"><b>('._L('Invalid').')</b></font></span>';
			$out['text'] .= '<br>';
			# ---
			$out['text'] .= '<h2>'._L('Access via OID Information Protocol').'</h2>';
			$out['text'] .= '<p>'._L('The query according to OID Information Protocol is:').'</p>';
			$out['text'] .= '	<p><pre id="whois_query_bar"></pre></p>';
			$out['text'] .= '	<p><input type="button" value="'._L('Copy to clipboard').'" onClick="copyToClipboard(whois_query_bar)"></p>';
			if ($whois_server != '') {
				$out['text'] .= '<p>'._L('You can use any WHOIS compatible client to query the information from the WHOIS or OID-IP port.').'</p>';
				$out['text'] .= '<p>'._L('The hostname and port number is:').'</p>';
				$out['text'] .= '<p><pre>'.htmlentities($whois_server).'</pre></p>';
			}
			# ---
			$out['text'] .= '<h2>'._L('Access via web-browser').'</h2>';
			$out['text'] .= '<p>'._L('The URL for the Web Service is:').'</p>';
			$out['text'] .= '	<p><pre id="whois_url_bar"></pre></p>';
			$out['text'] .= '	<p>';
			$out['text'] .= '	<input type="button" value="'._L('Copy to clipboard').'" onClick="copyToClipboard(whois_url_bar)">';
			$out['text'] .= '	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			$out['text'] .= '	<input type="button" value="'._L('Open in web-browser').'" onClick="OIDplusPagePublicWhois.openInBrowser()">';
			$out['text'] .= '	</p>';
			$out['text'] .= '</div>';
			# ---
			$out['text'] .= '<script>';
			$out['text'] .= '   $("#oidipArea")[0].style.display = "Block";';  // because of NoScript
			$out['text'] .= '   OIDplusPagePublicWhois.refresh_whois_url_bar();';
			$out['text'] .= '</script>';
		}
	}

	/**
	 * @param array $out
	 * @return void
	 */
	public function publicSitemap(array &$out) {
		$out[] = 'oidplus:whois';
	}

	/**
	 * @param array $json
	 * @param string|null $ra_email
	 * @param bool $nonjs
	 * @param string $req_goto
	 * @return bool
	 * @throws OIDplusException
	 */
	public function tree(array &$json, string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
		if (file_exists(__DIR__.'/img/main_icon16.png')) {
			$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png';
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

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_2
	 * @param string $id
	 * @param string $title
	 * @param string $icon
	 * @param string $text
	 * @return void
	 * @throws OIDplusException
	 */
	public function modifyContent(string $id, string &$title, string &$icon, string &$text) {
		$payload = '<br><img src="'.OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/page_pictogram.png" height="15" alt=""> <a href="'.OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'whois/webwhois.php?query='.urlencode($id).'" class="gray_footer_font" target="_blank">'._L('Whois').'</a>';
		$obj = OIDplusObject::parse($id);
		if ($obj && $obj->userHasParentalWriteRights()) {
			$payload .= '<br><span class="gray_footer_font">'._L('OID-IP Auth Token for displaying full object information: %1 (only applies if the this or superior objects are marked confidential)','<b>'.self::genWhoisAuthToken($id).'</b>').'</span>';
			$payload .= '<br><span class="gray_footer_font">'._L('OID-IP Auth Token for displaying full RA information: %1 (only applies if the RA has set the privacy-flag)','<b>'.self::genWhoisAuthToken('ra:'.$obj->getRaMail()).'</b>').'</span>';
		}

		$text = str_replace('<!-- MARKER 6 -->', '<!-- MARKER 6 -->'.$payload, $text);
	}

	/**
	 * @param string $request
	 * @return array|false
	 */
	public function tree_search(string $request) {
		return false;
	}

	/**
	 * @param string $id
	 * @return int
	 * @throws OIDplusException
	 */
	public static function genWhoisAuthToken(string $id): int {
		return smallhash(OIDplus::authUtils()->makeSecret(['d8f44c7c-f4e9-11ed-86ca-3c4a92df8582',$id]));
	}
}
