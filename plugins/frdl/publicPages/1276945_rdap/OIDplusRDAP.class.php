<?php

/*
 * OIDplus 2.0 RDAP
 * Copyright 2019 - 2023 Daniel Marschall, ViaThinkSoft
 * Authors               Daniel Marschall, ViaThinkSoft
 *                       Till Wehowski, Frdlweb
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

namespace Frdlweb\OIDplus;

use ViaThinkSoft\OIDplus\OIDplus;
use ViaThinkSoft\OIDplus\OIDplusBaseClass;
use ViaThinkSoft\OIDplus\OIDplusObject;
use ViaThinkSoft\OIDplus\OIDplusOIDIP;
use ViaThinkSoft\OIDplus\OIDplusPagePublicObjects;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusRDAP extends OIDplusBaseClass {

	/**
	 * @var string
	 */
	protected $rdapBaseUri;

	/**
	 * @var bool
	 */
	protected $useCache;

	/**
	 * @var string
	 */
	protected $rdapCacheDir;

	/**
	 * @var int
	 */
	protected $rdapCacheExpires;

	/**
	 * @throws \ViaThinkSoft\OIDplus\OIDplusException
	 */
	public function __construct() {
		$this->rdapBaseUri = OIDplus::baseConfig()->getValue('RDAP_BASE_URI', OIDplus::webpath() );
		$this->useCache = OIDplus::baseConfig()->getValue('RDAP_CACHE_ENABLED', false );
		$this->rdapCacheDir = OIDplus::baseConfig()->getValue('RDAP_CACHE_DIRECTORY', OIDplus::localpath().'userdata/cache/' );
		$this->rdapCacheExpires = OIDplus::baseConfig()->getValue('RDAP_CACHE_EXPIRES', 60 * 3 );
	}

	/**
	 * @param string $query
	 * @return array
	 * @throws \ViaThinkSoft\OIDplus\OIDplusException
	 */
	public function rdapQuery(string $query): array {
		$query = str_replace('oid:.', 'oid:', $query);
		$n = explode(':', $query);
		if(2>count($n)){
		 array_unshift($n, 'oid');
		 $query = 'oid:'.$query;
		}
		$ns = $n[0];

		if(true === $this->useCache){
			$cacheFile = $this->rdapCacheDir. 'rdap_'
			.sha1(\get_current_user()
				  . $this->rdapBaseUri.__FILE__.$query
				  .OIDplus::authUtils()->makeSecret('cee75760-f4f8-11ed-b67e-3c4a92df8582')
				 )
			.'.'
			.strlen( $this->rdapBaseUri.$query )
			.'.ser'
			;

			$tmp = $this->rdap_read_cache($cacheFile, $this->rdapCacheExpires);
			if ($tmp) return $tmp;
		}else{
			$cacheFile = false;
		}

		$out = [];

		$obj = OIDplusObject::findFitting($query);

		if(!$obj){
			// If object was not found, try if it is an alternative identifier of another object
			$alts = OIDplusPagePublicObjects::getAlternativesForQuery($query);
			foreach ($alts as $alt) {
				if ($obj = OIDplusObject::findFitting($alt)) {
					$query = $obj->nodeId();
					break;
				}
			}

			// Still nothing found?
			if(!$obj){
				$out['error'] = 'Not found';
				if(true === $this->useCache){
					$this->rdap_write_cache($out, $cacheFile);
				}
				return $this->rdap_out($out);
			}
		} else {
			$query = $obj->nodeId();
		}

		$whois_server = '';
		if (OIDplus::config()->getValue('individual_whois_server', '') != '') {
			$whois_server = OIDplus::config()->getValue('individual_whois_server', '');
		}
		else if (OIDplus::config()->getValue('vts_whois', '') != '') {
			$whois_server = OIDplus::config()->getValue('vts_whois', '');
		}
		if (!empty($whois_server)) {
			list($whois_host, $whois_port) = explode(':',"$whois_server:43");
			if ($whois_port === '43') $out['port43'] = $whois_host;
		}

		$parentHandle=$obj->one_up();

		$out['name'] = $obj->nodeId(true);
		$out['objectClassName'] = $ns;
		$out['handle'] = $ns.':'.$n[1];
		$out['parentHandle'] =   (null !== $parentHandle && is_callable([$parentHandle, 'nodeId']) )
		                         ? $parentHandle->nodeId(true)
		                         : null;

		$out['rdapConformance'] = [
			"rdap_level_0", //https://datatracker.ietf.org/doc/html/rfc9083
		];
		$out['links'] = [
			[
				"href"=> 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'],
				"type"=> "application/rdap+json",
				"title"=> sprintf("Information about the %s %s", $ns, $n[1]),
				"value"=> $this->rdapBaseUri.$ns.'/'.$n[1],
				"rel"=> "self"
			],
			[
				"href"=> OIDplus::webpath()."?goto=".urlencode($query),
				"type"=> "text/html",
				"title"=> sprintf("Information about the %s %s in the online repository", $ns, $n[1]),
				"value"=> OIDplus::webpath()."?goto=".urlencode($query),
				"rel"=> "alternate"
			]
		];
		$out['remarks'] = [
			[
				"title"=>"Availability",
				"description"=> [
					sprintf("The %s %s is known.", strtoupper($ns), $n[1]),
				],
				"links"=> []
			],
			[
				"title"=>"Description",
				"description"=> [
					($obj->isConfidential()) ? 'REDACTED FOR PRIVACY' : $obj->getDescription(),
				],
				"links"=> [
					[
						"href"=> OIDplus::webpath()."?goto=".urlencode($query),
						"type"=> "text/html",
						"title"=> sprintf("Information about the %s %s in the online repository", $ns, $n[1]),
						"value"=> OIDplus::webpath()."?goto=".urlencode($query),
						"rel"=> "alternate"
					]
				]
			],

		];

		if (!is_null(OIDplus::getPluginByOid("1.3.6.1.4.1.37476.2.5.2.4.1.100"))) { // OIDplusPagePublicWhois
			$oidIPUrl = OIDplus::webpath().'plugins/viathinksoft/publicPages/100_whois/whois/webwhois.php?query='.urlencode($query);

			$oidip_generator = new OIDplusOIDIP();

			list($oidIP, $dummy_content_type) = $oidip_generator->oidipQuery($query);

			$out['remarks'][] = [
				"title" => "OID-IP Result",
				"description" => $oidIP,
				"links" => [
						[
							"href"=> $oidIPUrl,
							"type"=> "text/plain",
							"title"=> sprintf("OIDIP Result for the %s %s (Plaintext)", $ns, $n[1]),
							"value"=> $oidIPUrl,
							"rel"=> "alternate"
						],
						[
							"href"=> "$oidIPUrl\$format=json",
							"type"=> "application/json",
							"title"=> sprintf("OIDIP Result for the %s %s (JSON)", $ns, $n[1]),
							"value"=> "$oidIPUrl\$format=json",
							"rel"=> "alternate"
						],
						[
							"href"=> "$oidIPUrl\$format=xml",
							"type"=> "application/xml",
							"title"=> sprintf("OIDIP Result for the %s %s (XML)", $ns, $n[1]),
							"value"=> "$oidIPUrl\$format=xml",
							"rel"=> "alternate"
						]
					]
				];

			list($oidIPJSON, $dummy_content_type) = $oidip_generator->oidipQuery("$query\$format=json");
			$out['oidplus_oidip'] = json_decode($oidIPJSON);
		}

		$out['notices']=[
			 [
				"title" => "Authentication Policy",
				"description" =>
				[
					"Access to sensitive data for users with proper credentials."
				],
				"links" =>
				[
					[
						"value" => $this->rdapBaseUri."help",
						"rel" => "alternate",
						"type" => "text/html",
						"href" => OIDplus::webpath()."?goto=oidplus%3Aresources%24OIDplus%2Fprivacy_documentation.html"
					]
				]
			]
		];

		if($obj->isConfidential()){
			$out['remarks'][1]['type'] = "result set truncated due to authorization";
		}

		$out['statuses']=[
			'active',
		];


		if(true === $this->useCache){
			$this->rdap_write_cache($out, $cacheFile);
		}
		return $this->rdap_out($out);
	}

	/**
	 * @param array $out
	 * @param string $cacheFile
	 * @return void
	 */
	protected function rdap_write_cache(array $out, string $cacheFile){
		@file_put_contents($cacheFile, serialize($out));
	}

	/**
	 * @param string $cacheFile
	 * @param int $rdapCacheExpires
	 * @return array|null
	 */
	protected function rdap_read_cache(string $cacheFile, int $rdapCacheExpires){
		if (file_exists($cacheFile) && filemtime($cacheFile) >= time() - $rdapCacheExpires) {
			$out = unserialize(file_get_contents($cacheFile));
			if(is_array($out) || is_object($out)){
				return $this->rdap_out($out);
			}
		}
		return null;
	}

	/**
	 * @param array $out
	 * @return array
	 */
	protected function rdap_out(array $out): array {
		$out_content = json_encode($out);
		$out_type = 'application/rdap+json';
		return array($out_content, $out_type);
	}

}
