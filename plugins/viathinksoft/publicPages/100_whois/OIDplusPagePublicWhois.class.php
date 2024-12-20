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

namespace ViaThinkSoft\OIDplus\Plugins\PublicPages\Whois;

use Frdlweb\OIDplus\Plugins\PublicPages\RDAP\INTF_OID_1_3_6_1_4_1_37553_8_1_8_8_53354196964_1276945;
use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusConfig;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusObject;
use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginPublic;
use ViaThinkSoft\OIDplus\Plugins\PublicPages\Objects\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_2;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusPagePublicWhois extends OIDplusPagePluginPublic
	implements INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_2, /* modifyContent */
	           INTF_OID_1_3_6_1_4_1_37553_8_1_8_8_53354196964_1276945 /*rdapExtensions*/
{
	/**
	 * @param bool $html
	 * @return void
	 * @throws OIDplusException
	 */
	public function init(bool $html=true): void {
		OIDplus::config()->prepareConfigKey('whois_auth_token',                       'OID-IP authentication token to display confidential data', '', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			$test_value = preg_replace('@[0-9a-zA-Z]*@', '', $value);
			if ($test_value != '') {
				throw new OIDplusException(_L('Only characters and numbers are allowed as authentication token.'));
			}
		});
		OIDplus::config()->prepareConfigKey('webwhois_output_format_spacer',          'OID-IP Text format: Spacer', '2', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < 0)) {
				throw new OIDplusException(_L('Please enter a valid value.'));
			}
		});
		OIDplus::config()->prepareConfigKey('webwhois_output_format_max_line_length', 'OID-IP Text format: Max line length', '80', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
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
	 * Input from "Accept-Language" HTTP request header, e.g. "fr-CH, fr;q=0.9, en;q=0.8, de;q=0.7, *;q=0.5"
	 * @return string Outputs for example "fr-CH,fr,en,de" according to OID-IP.
	 */
	private static function getAcceptLangInOurFormat(): string {
		$header_val = getHttpRequestHeader('Accept-Language') ?? '';
		$out = [];
		foreach (explode(',', $header_val) as $part) {
			$part = explode(';', $part)[0];
			$part = trim($part);
			if ($part == '*') continue;
			$out[] = $part;
		}
		return implode(',',$out);
	}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37553_8_1_8_8_53354196964_1276945.
	 *
	 * @param array $out
	 * @param string $namespace
	 * @param string $id
	 * @param $obj
	 * @param string $query
	 * @return array
	 * @throws OIDplusException
	 */
	public function rdapExtensions(array $out, string $namespace, string $id, OIDplusObject $obj, string $query): array {
		$ns = $namespace;
		$n = [
			$namespace,
			$id,
		];

		//$oidIPUrl = OIDplus::webpath().'plugins/viathinksoft/publicPages/100_whois/whois/webwhois.php?query='.urlencode($query).'$format=';
		$oidIPUrl = OIDplus::webpath().'oidip/'.urlencode($namespace).'/'.$id.'/';

		$oidip_generator = new OIDplusOIDIP();

		$out['remarks'][] = [
			"title" => "OID-IP Result",
			"description" => [
				sprintf("Additional %s %s was added.", 'OID-IP Result info from RDAP-plugin', "1.3.6.1.4.1.37476.2.5.2.4.1.100"),
			],
			"links" => [
				[
					"href"=> $oidIPUrl.'text',
					"type"=> "text/plain",
					"title"=> sprintf("OIDIP Result for the %s %s (Plaintext)", $ns, $n[1]),
					"value"=> $oidIPUrl.'text',
					"rel"=> "alternate"
				],
				[
					"href"=> $oidIPUrl.'json',
					"type"=> "application/json",
					"title"=> sprintf("OIDIP Result for the %s %s (JSON)", $ns, $n[1]),
					"value"=> $oidIPUrl.'json',
					"rel"=> "alternate"
				],
				[
					"href"=> $oidIPUrl.'xml',
					"type"=> "application/xml",
					"title"=> sprintf("OIDIP Result for the %s %s (XML)", $ns, $n[1]),
					"value"=> $oidIPUrl.'xml',
					"rel"=> "alternate"
				]
			]
		];

		list($oidIPJSON, $dummy_content_type) = $oidip_generator->oidipQuery("$query\$format=json");
		$out['oidplus_oidip'] = json_decode($oidIPJSON);
		$out['rdapConformance'][]='oidplus_oidip';
		$out['oidplus_oidip_properties'] = [
			"\$schema" ,
			"oidip",
		];

		return $out;
	}

	/**
	 * @param string $request
	 * @return bool
	 * @throws OIDplusException
	 */
	public function handle404(string $request): bool {

		if (!isset($_SERVER['REQUEST_URI']) || !isset($_SERVER["REQUEST_METHOD"])) return false;

		$rel_url = substr($_SERVER['REQUEST_URI'], strlen(OIDplus::webpath(null, OIDplus::PATH_RELATIVE_TO_ROOT)));
		$expect = 'oidip/';
		if (str_starts_with($rel_url, $expect)) {
			originHeaders(); // Allows queries from other domains
			OIDplus::authUtils()->disableCSRF(); // allow access to ajax.php without valid CSRF token

			$rel_url = preg_replace('@^'.preg_quote($expect,'@').'@', '', $rel_url);

			$rel_url = explode('?', $rel_url, 2)[0];
			$ary = explode('/', trim($rel_url,'/'), 3);
			$ns = $ary[0];
			$id = $ary[1] ?? null;
			if ($id === 'root') $id = '';
			$format = $ary[2] ?? 'text';
			if ($ns && $id && $format) {
				$query = "$ns:$id\$format=$format";

				$auth = null;
				if (isset($_GET['auth'])) $auth = $_GET['auth'];
				else if (isset($_POST['auth'])) $auth = $_POST['auth'];
				else if ($tmp = getBearerToken()) $auth = $tmp;
				if ($auth) $query .= "\$auth=$auth";

				$lang = null;
				if (isset($_GET['lang'])) $lang = $_GET['lang'];
				else if (isset($_POST['lang'])) $lang = $_POST['lang'];
				else if ($tmp = self::getAcceptLangInOurFormat()) $lang = $tmp;
				if ($lang) $query .= "\$lang=$lang";

				// echo "$query\n\n";

				$x = new OIDplusOIDIP();
				list($out_content, $out_type, $out_http_code) = $x->oidipQuery($query);

				if ($out_http_code) {
					if ($out_http_code == 200) $out_http_code_hf = 'OK';
					else if ($out_http_code == 400) $out_http_code_hf = 'Bad Request';
					else if ($out_http_code == 404) $out_http_code_hf = 'Not Found';
					else if ($out_http_code == 470) $out_http_code_hf = 'Not Found - Superior Object Found';
					else $out_http_code_hf = 'Undefined';
					@header(($_SERVER['SERVER_PROTOCOL']??'HTTP/1.0').' '.$out_http_code.' '.$out_http_code_hf);
				}

				@header('Content-Type: '.$out_type);
				@header("Content-Disposition: inline"); // TODO! DOES NOT WORK! IT ALWAYS DOWNLOADS!
				                                        // https://github.com/whatwg/html/issues/7420
				echo $out_content;
			}

			OIDplus::invoke_shutdown();
			die(); // return true;
		}

		return false;
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
	public function gui(string $id, array &$out, bool &$handled): void {
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

			// $out['text'] .= '<p>'._L('Specification').': <a target="_blank" href="https://htmlpreview.github.io/?https://raw.githubusercontent.com/ViaThinkSoft/standards/master/viathinksoft-std-0002-oidip.html">ViaThinkSoft/Webfan Standard #2</a></p>';
			$out['text'] .= '<p>'._L('Specification').': <a target="_blank" href="https://www.viathinksoft.de/std/viathinksoft-std-0002-oidip.html">ViaThinkSoft/Webfan Standard #2</a></p>';
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
			$out['text'] .= '    <label for="text">'._L('Text format').'</label><br>';
			$out['text'] .= '    <input type="radio" id="json" name="format" value="json" onclick="OIDplusPagePublicWhois.refresh_whois_url_bar()">';
			$out['text'] .= '    <label for="json">'._L('JSON format').'</label> (<a target="_blank" href="https://github.com/ViaThinkSoft/standards/blob/main/viathinksoft-std-0002-oidip.json">'._L('Schema').'</a>)<br>';
			$out['text'] .= '    <input type="radio" id="xml" name="format" value="xml" onclick="OIDplusPagePublicWhois.refresh_whois_url_bar()">';
			$out['text'] .= '    <label for="xml">'._L('XML format').'</label> (<a target="_blank" href="https://github.com/ViaThinkSoft/standards/blob/main/viathinksoft-std-0002-oidip.xsd">'._L('Schema').'</a>)<br>';
			$out['text'] .= '</fieldset><br>';
			# ---
			$out['text'] .= _L('Authentication token(s), comma separated (optional)').':<br>';
			$out['text'] .= '<input type="text" id="whois_auth" name="auth" value="" onkeyup="OIDplusPagePublicWhois.refresh_whois_url_bar()">';
			$out['text'] .= '&nbsp;<span id="whois_auth_invalid" style="display:none"><font color="red"><b>('._L('Invalid').')</b></font></span>';
			$out['text'] .= '<br><br>';
			# ---
			$out['text'] .= '<p><b><u>'._L('Access via WHOIS Protocol').'</u></b></p>';
			$out['text'] .= '<p>'._L('The query according to OID Information Protocol is:').'</p>';
			$out['text'] .= '	<p><pre id="whois_query_bar"></pre></p>';
			$out['text'] .= '	<p><input type="button" value="'._L('Copy to clipboard').'" onClick="copyToClipboard(whois_query_bar)"></p>';
			if ($whois_server != '') {
				$out['text'] .= '<p>'._L('You can use any WHOIS compatible client to query the information from the WHOIS or OID-IP port.').'</p>';
				$out['text'] .= '<p>'._L('The hostname and port number is:').'</p>';
				$out['text'] .= '<p><pre>'.htmlentities($whois_server).'</pre></p>';
			}
			# ---
			$out['text'] .= '<p><b><u>'._L('Access via HTTP Protocol').'</u></b></p>';
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
	public function publicSitemap(array &$out): void {
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
	public function tree(array &$json, ?string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
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
	public function modifyContent(string $id, string &$title, string &$icon, string &$text): void {
		//$oidipUrl = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'whois/webwhois.php?query='.urlencode($id);
		list($ns, $id_no_ns) = explode(':', $id, 2);
		$oidipUrl = OIDplus::webpath().'oidip/'.urlencode($ns).'/'.$id_no_ns;
		$payload = '<br><img src="'.OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/page_pictogram.png" height="15" alt=""> '.
		           _L('OID-IP').': '.
		           '<a href="'.$oidipUrl.'/text" class="gray_footer_font" target="_blank">'._L('Text').'</a>, '.
		           '<a href="'.$oidipUrl.'/json" class="gray_footer_font" target="_blank">'._L('JSON').'</a>, '.
		           '<a href="'.$oidipUrl.'/xml" class="gray_footer_font" target="_blank">'._L('XML').'</a> '.
		           '(<a href="https://www.viathinksoft.de/std/viathinksoft-std-0002-oidip.html" target="_blank" class="gray_footer_font">'._L('Documentation').'</a>)';

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
