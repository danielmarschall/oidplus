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

class OIDplusOIDIP {

	protected $XML_SCHEMA_URN;
	protected $XML_SCHEMA_URL;
	protected $JSON_SCHEMA_URN;
	protected $JSON_SCHEMA_URL;

	/**
	 * @throws OIDplusException
	 */
	public function __construct() {
		// NOTES:
		// - XML_SCHEMA_URN must be equal to the string in the .xsd file!
		// - the schema file names and draft version are also written in OIDplusPagePublicWhois.class.php
		$this->XML_SCHEMA_URN  = 'urn:ietf:id:draft-viathinksoft-oidip-05';
		$this->XML_SCHEMA_URL  = OIDplus::webpath(__DIR__,OIDplus::PATH_ABSOLUTE).'whois/draft-viathinksoft-oidip-05.xsd';
		$this->JSON_SCHEMA_URN = 'urn:ietf:id:draft-viathinksoft-oidip-05';
		$this->JSON_SCHEMA_URL = OIDplus::webpath(__DIR__,OIDplus::PATH_ABSOLUTE).'whois/draft-viathinksoft-oidip-05.json';
	}

	/**
	 * @param $query
	 * @return array|void
	 * @throws OIDplusException
	 */
	public function oidipQuery($query) {

		$out_type = null;
		$out_content = '';

		// Split input into query and arguments
		$chunks = explode('$', $query);
		$query = array_shift($chunks);
		$original_query = $query;
		$arguments = array();
		foreach ($chunks as $chunk) {
			if (strtolower(substr($chunk,0,5)) !== 'auth=') $original_query .= '$'.$chunk;

			if (strpos($chunk,'=') === false) continue;
			$tmp = explode('=',$chunk,2);

			//if (!preg_match('@^[a-z0-9]+$@', $tmp[0], $m)) continue; // be strict with the names. They must be lowercase (TODO: show "Service error" message?)
			$tmp[0] = strtolower($tmp[0]); // be tolerant instead

			$arguments[$tmp[0]] = $tmp[1];
		}

		$query = str_replace('oid:.', 'oid:', $query); // allow leading dot

		if (isset($arguments['format'])) {
			$format = $arguments['format'];
		} else {
			$format = 'text'; // default
		}

		if (isset($arguments['auth'])) {
			$authTokens = explode(',', $arguments['auth']);
		} else {
			$authTokens = array();
		}

		// $lang= is not implemented, since we don't know in which language the OID descriptions are written by the site operator
		/*
		if (isset($arguments['lang'])) {
			$languages = explode(',', $arguments['lang']);
		} else {
			$languages = array();
		}
		*/

		$unimplemented_format = ($format != 'text') && ($format != 'json') && ($format != 'xml');
		if ($unimplemented_format) {
			$format = 'text';
		}

		// Step 1: Collect data

		$out = array();

		// ATTENTION: THE ORDER IS IMPORTANT FOR THE XML VALIDATION!
		// The order of the RFC is the same as in the XSD
		$out[] = $this->_oidip_attr('query', $original_query);

		$query = OIDplus::prefilterQuery($query, false);

		if ($unimplemented_format) {
			$out[] = $this->_oidip_attr('result', 'Service error');
			$out[] = $this->_oidip_attr('message', 'Format is not implemented');
			$out[] = $this->_oidip_attr('lang', 'en-US');
		} else {

			$distance = null;
			$found = null;

			$obj = OIDplusObject::parse($query);

			$only_wellknown_ids_found = false;
			$continue = false;

			if (!$obj) {
				// Object type not known or invalid syntax of $query
				$out[] = $this->_oidip_attr('result', 'Not found'); // DO NOT TRANSLATE!
				$continue = false;
			} else {

				$query = $obj->nodeId(); // normalize

				$obj = null;
				$distance = 0;

				$init_query = $query;
				while (true) {

					$obj_test = OIDplusObject::findFitting($query);
					if ($obj_test) {
						$obj = $obj_test;
					} else {
						$alts = OIDplusPagePublicObjects::getAlternativesForQuery($query);
						foreach ($alts as $alt) {
							$obj_test = OIDplusObject::findFitting($alt);
							if ($obj_test) {
								$query = $alt;
								$obj = $obj_test;
							}
						}
					}
					if ($obj) {
						if ($distance > 0) {
							$out[] = $this->_oidip_attr('result', 'Not found; superior object found'); // DO NOT TRANSLATE!
							$out[] = $this->_oidip_attr('distance', $distance); // DO NOT TRANSLATE
						} else {
							$out[] = $this->_oidip_attr('result', 'Found'); // DO NOT TRANSLATE!
						}
						$continue = true;
						break;
					}

					if (substr($query,0,4) === 'oid:') {
						$query_prev = $query;
						$query = 'oid:'.oid_up(explode(':',$query,2)[1]);
						if ($query == $query_prev) break;
						$distance++;
					} else {
						// getParent() will find the parent which DOES exist in the DB.
						// It does not need to be the direct parent (like ->one_up() does)
						$obj = OIDplusObject::parse($query)->getParent(); // For objects, we assume that they are parents of each other
						if ($obj) {
							$distance = $obj->distance($query);
							assert(OIDplusObject::findFitting($query));

							$query = $obj->nodeId();
						}

						if ($distance > 0) {
							$out[] = $this->_oidip_attr('result', 'Not found; superior object found'); // DO NOT TRANSLATE!
							$out[] = $this->_oidip_attr('distance', $distance); // DO NOT TRANSLATE
						}
						$continue = true;

						break;
					}
				}

				if ((substr($query,0,4) === 'oid:') && (!$obj)) {
					$query = $init_query;
					$distance = 0;
					while (true) {
						// Checks if there is any identifier (so it is a well-known OID)
						$res_test = OIDplus::db()->query("select * from ###asn1id where oid = ? union select * from ###iri where oid = ?", array($query, $query));
						if ($res_test->any()) {
							$obj = OIDplusObject::parse($query);
							if ($distance > 0) {
								$out[] = $this->_oidip_attr('result', 'Not found; superior object found'); // DO NOT TRANSLATE!
								$out[] = $this->_oidip_attr('distance', $distance); // DO NOT TRANSLATE
							} else {
								$out[] = $this->_oidip_attr('result', 'Found'); // DO NOT TRANSLATE!
							}
							$only_wellknown_ids_found = true; // Information partially available
							$continue = true;
							break;
						}
						$query_prev = $query;
						$query = 'oid:'.oid_up(explode(':',$query,2)[1]);
						if ($query == $query_prev) break;
						$distance++;
					}
				}

				if (!$obj) {
					$out[] = $this->_oidip_attr('result', 'Not found'); // DO NOT TRANSLATE!
					$continue = false;
				}

				$found = $distance == 0;
			}

			if ($continue) {
				$out[] = '';

				// ATTENTION: THE ORDER IS IMPORTANT FOR THE XML VALIDATION!
				// The order of the RFC is the same as in the XSD
				$out[] = $this->_oidip_attr('object', $query); // DO NOT TRANSLATE!
				if (!$this->allowObjectView($obj, $authTokens)) {
					$out[] = $this->_oidip_attr('status', 'Information unavailable'); // DO NOT TRANSLATE!
					$out[] = $this->_oidip_attr('attribute', 'confidential'); // DO NOT TRANSLATE!
				} else {
					if ($only_wellknown_ids_found) {
						$out[] = $this->_oidip_attr('status', 'Information partially available'); // DO NOT TRANSLATE!
					} else {
						$out[] = $this->_oidip_attr('status', 'Information available'); // DO NOT TRANSLATE!
					}

					// $this->_oidip_attr('lang', ...); // not implemented (since we don't know the language of the texts written by the page operator)

					if ($obj) {
						$out[] = $this->_oidip_attr('name', $obj->getTitle()); // DO NOT TRANSLATE!

						$cont = $obj->getDescription();
						$cont = preg_replace('@<a[^>]+href\s*=\s*["\']([^\'"]+)["\'][^>]*>(.+)<\s*/\s*a\s*>@ismU', '\2 (\1)', $cont);
						$cont = preg_replace('@<br.*>@', "\n", $cont);
						$cont = preg_replace('@\\n+@', "\n", $cont);
						$out[] = $this->_oidip_attr('description', trim(html_entity_decode(strip_tags($cont)))); // DO NOT TRANSLATE!
					}

					// $this->_oidip_attr('information', ...); Not used. Contains additional information, e.g. Management Information Base (MIB) definitions.

					if ($only_wellknown_ids_found) {
						if (substr($query,0,4) === 'oid:') {
							// Since it is well-known, oid-info.com will most likely have it described
							$out[] = $this->_oidip_attr('url', 'https://oid-rep.orange-labs.fr/get/'.$obj->nodeId(false));
						}
					} else {
						$out[] = $this->_oidip_attr('url', OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE).'?goto='.urlencode($obj->nodeId(true)));
					}

					if (substr($query,0,4) === 'oid:') {
						assert($obj instanceof OIDplusOid); //assert(get_class($obj) === "ViaThinkSoft\OIDplus\OIDplusOid");

						$out[] = $this->_oidip_attr('asn1-notation', $obj->getAsn1Notation(false)); // DO NOT TRANSLATE!
						$out[] = $this->_oidip_attr('iri-notation', $obj->getIriNotation(false)); // DO NOT TRANSLATE!

						$res_asn = OIDplus::db()->query("select * from ###asn1id where oid = ?", array($obj->nodeId()));
						while ($row_asn = $res_asn->fetch_object()) {
							$out[] = $this->_oidip_attr('identifier', $row_asn->name); // DO NOT TRANSLATE!
						}

						$res_asn = OIDplus::db()->query("select * from ###asn1id where standardized = ? and oid = ?", array(true, $obj->nodeId()));
						while ($row_asn = $res_asn->fetch_object()) {
							$out[] = $this->_oidip_attr('standardized-id', $row_asn->name); // DO NOT TRANSLATE!
						}

						$res_iri = OIDplus::db()->query("select * from ###iri where oid = ?", array($obj->nodeId()));
						while ($row_iri = $res_iri->fetch_object()) {
							$out[] = $this->_oidip_attr('unicode-label', $row_iri->name); // DO NOT TRANSLATE!
						}

						$res_iri = OIDplus::db()->query("select * from ###iri where longarc = ? and oid = ?", array(true, $obj->nodeId()));
						while ($row_iri = $res_iri->fetch_object()) {
							$out[] = $this->_oidip_attr('long-arc', $row_iri->name); // DO NOT TRANSLATE!
						}
					}

					// $this->_oidip_attr('oidip-service', ...); Not used.

					// $this->_oidip_attr('oidip-pubkey', ...); Not used.

					if ($obj->implementsFeature('1.3.6.1.4.1.37476.2.5.2.3.4')) {
						// Also ask $obj for extra attributes:
						// This way we could add various additional information, e.g. IPv4/6 range analysis, interpretation of GUID, etc.
						$obj->whoisObjectAttributes($obj->nodeId(), $out); /** @phpstan-ignore-line */
					}

					foreach (OIDplus::getAllPlugins() as $plugin) {
						if ($plugin->implementsFeature('1.3.6.1.4.1.37476.2.5.2.3.4')) {
							$plugin->whoisObjectAttributes($obj->nodeId(), $out); /** @phpstan-ignore-line */
						}
					}

					if ($obj->isConfidential()) { // yes, we use isConfidential() instead of $this->allowObjectView()!
						$out[] = $this->_oidip_attr('attribute', 'confidential'); // DO NOT TRANSLATE!
					}

					if (substr($query,0,4) === 'oid:') {
						$sParent = 'oid:'.oid_up(explode(':',$query,2)[1]);

						$objTest = OIDplusObject::parse($sParent);
						if ($this->allowObjectView($objTest, $authTokens)) {
							$out[] = $this->_oidip_attr('parent', $sParent.$this->show_asn1_appendix($sParent)); // DO NOT TRANSLATE!
						} else {
							$out[] = $this->_oidip_attr('parent', $sParent); // DO NOT TRANSLATE!
						}
					} else {
						$objParent = $obj->getParent();
						$sParent = $objParent ? $objParent->nodeId() : '';
						if (!empty($sParent) && (!$this->is_root($sParent))) {
							$out[] = $this->_oidip_attr('parent', $sParent); // DO NOT TRANSLATE!
						}
					}

					$res_children = OIDplus::db()->query("select * from ###objects where parent = ? order by ".OIDplus::db()->natOrder('id'), array($obj->nodeId()));
					while ($row_children = $res_children->fetch_object()) {
						$objTest = OIDplusObject::parse($row_children->id);
						if ($this->allowObjectView($objTest, $authTokens)) {
							$out[] = $this->_oidip_attr('subordinate', $row_children->id.$this->show_asn1_appendix($row_children->id)); // DO NOT TRANSLATE!
						} else {
							$out[] = $this->_oidip_attr('subordinate', $row_children->id); // DO NOT TRANSLATE!
						}
					}

					if ($obj) {
						if ($tim = $obj->getCreatedTime()) $out[] = $this->_oidip_attr('created', date('Y-m-d H:i:s', strtotime($tim))); // DO NOT TRANSLATE!
						if ($tim = $obj->getUpdatedTime()) $out[] = $this->_oidip_attr('updated', date('Y-m-d H:i:s', strtotime($tim))); // DO NOT TRANSLATE!
					}

					$out[] = '';

					// ATTENTION: THE ORDER IS IMPORTANT FOR THE XML VALIDATION!
					// The order of the RFC is the same as in the XSD
					$res_ra = OIDplus::db()->query("select * from ###ra where email = ?", array($obj ? $obj->getRaMail() : ''));
					if ($row_ra = $res_ra->fetch_object()) {
						$out[] = $this->_oidip_attr('ra', (!empty($row_ra->ra_name) ? $row_ra->ra_name : (!empty($row_ra->email) ? $row_ra->email : /*_L*/('Unknown')))); // DO NOT TRANSLATE!

						if (!$this->allowRAView($row_ra, $authTokens)) {
							$out[] = $this->_oidip_attr('ra-status', 'Information partially available'); // DO NOT TRANSLATE!
						} else {
							$out[] = $this->_oidip_attr('ra-status', 'Information available'); // DO NOT TRANSLATE!
						}

						// $this->_oidip_attr('ra-lang', ...); // not implemented (since we don't know the language of the texts written by the page operator)

						$tmp = array();
						if (!empty($row_ra->office)) $tmp[] = $row_ra->office;
						if (!empty($row_ra->organization)) $tmp[] = $row_ra->organization;
						$tmp = implode(', ', $tmp);

						$out[] = $this->_oidip_attr('ra-contact-name', $row_ra->personal_name.(!empty($tmp) ? " ($tmp)" : '')); // DO NOT TRANSLATE!
						if (!$this->allowRAView($row_ra, $authTokens)) {
							if (!empty($row_ra->street) || !empty($row_ra->zip_town) || !empty($row_ra->country)) {
								$out[] = $this->_oidip_attr('ra-address', /*_L*/('(redacted)')); // DO NOT TRANSLATE!
							}
							$out[] = $this->_oidip_attr('ra-phone', (!empty($row_ra->phone) ? /*_L*/('(redacted)') : '')); // DO NOT TRANSLATE!
							$out[] = $this->_oidip_attr('ra-mobile', (!empty($row_ra->mobile) ? /*_L*/('(redacted)') : '')); // DO NOT TRANSLATE!
							$out[] = $this->_oidip_attr('ra-fax', (!empty($row_ra->fax) ? /*_L*/('(redacted)') : '')); // DO NOT TRANSLATE!
						} else {
							$address = array();
							if (!empty($row_ra->street))   $address[] = $row_ra->street; // DO NOT TRANSLATE!
							if (!empty($row_ra->zip_town)) $address[] = $row_ra->zip_town; // DO NOT TRANSLATE!
							if (!empty($row_ra->country))  $address[] = $row_ra->country; // DO NOT TRANSLATE!
							if (count($address) > 0) $out[] = $this->_oidip_attr('ra-address', implode("\n",$address));
							$out[] = $this->_oidip_attr('ra-phone', $row_ra->phone); // DO NOT TRANSLATE!
							$out[] = $this->_oidip_attr('ra-mobile', $row_ra->mobile); // DO NOT TRANSLATE!
							$out[] = $this->_oidip_attr('ra-fax', $row_ra->fax); // DO NOT TRANSLATE!
						}
						$out[] = $this->_oidip_attr('ra-email', $obj->getRaMail()); // DO NOT TRANSLATE!

						// $this->_oidip_attr('ra-url', ...); Not used.

						$ra = new OIDplusRA($obj->getRaMail());
						if ($ra->implementsFeature('1.3.6.1.4.1.37476.2.5.2.3.4')) {
							$ra->whoisRaAttributes($obj->getRaMail(), $out); /** @phpstan-ignore-line */
						}

						foreach (OIDplus::getAllPlugins() as $plugin) {
							if ($plugin->implementsFeature('1.3.6.1.4.1.37476.2.5.2.3.4')) {
								$plugin->whoisRaAttributes($obj->getRaMail(), $out); /** @phpstan-ignore-line */
							}
						}

						// yes, we use row_ra->privacy() instead of $this->allowRAView(), becuase $this->allowRAView=true if auth token is given; and we want to inform the person that they content they are viewing is confidential!
						if ($row_ra->privacy) {
							$out[] = $this->_oidip_attr('ra-attribute', 'confidential'); // DO NOT TRANSLATE!
						}

						if ($row_ra->registered) $out[] = $this->_oidip_attr('ra-created', date('Y-m-d H:i:s', strtotime($row_ra->registered))); // DO NOT TRANSLATE!
						if ($row_ra->updated)    $out[] = $this->_oidip_attr('ra-updated', date('Y-m-d H:i:s', strtotime($row_ra->updated))); // DO NOT TRANSLATE!
					} else {
						$out[] = $this->_oidip_attr('ra', ($obj && !empty($obj->getRaMail()) ? $obj->getRaMail() : /*_L*/('Unknown'))); // DO NOT TRANSLATE!
						if ($obj) {
							foreach (OIDplus::getAllPlugins() as $plugin) {
								if ($plugin->implementsFeature('1.3.6.1.4.1.37476.2.5.2.3.4')) {
									$plugin->whoisRaAttributes($obj->getRaMail(), $out); /** @phpstan-ignore-line */
								}
							}
						}
						$out[] = $this->_oidip_attr('ra-status', 'Information unavailable'); // DO NOT TRANSLATE!
					}
				}
			}
		}

		// Upgrade legacy $out to extended $out format

		$this->_oidip_newout_format($out);

		// Trim all values

		foreach ($out as &$tmp) {
			$tmp['value'] = trim($tmp['value']);
		}

		// Step 2: Format output

		if ($format == 'text') {
			$out_type = 'text/plain; charset=UTF-8';

			$longest_key = 0;
			foreach ($out as $data) {
				$longest_key = max($longest_key, strlen(trim($data['name'])));
			}

			ob_start();

			//$out_content .= '% ' . str_repeat('*', OIDplus::config()->getValue('webwhois_output_format_max_line_length', 80)-2)."\r\n";

			foreach ($out as $data) {
				if ($data['name'] == '') {
					$out_content .= "\r\n";
					continue;
				}

				$key = $data['name'];
				$value = $data['value'];

				// Normalize line-breaks to \r\n, otherwise mb_wordwrap won't work correctly
				$value = str_replace("\r\n", "\n", $value);
				$value = str_replace("\r", "\n", $value);
				$value = str_replace("\n", "\r\n", $value);

				$value = mb_wordwrap($value, OIDplus::config()->getValue('webwhois_output_format_max_line_length', 80) - $longest_key - strlen(':') - OIDplus::config()->getValue('webwhois_output_format_spacer', 2), "\r\n");
				$value = str_replace("\r\n", "\r\n$key:".str_repeat(' ', $longest_key-strlen($key)) . str_repeat(' ', OIDplus::config()->getValue('webwhois_output_format_spacer', 2)), $value);

				if (!empty($value)) {
					$out_content .= $key.':' . str_repeat(' ', $longest_key-strlen($key)) . str_repeat(' ', OIDplus::config()->getValue('webwhois_output_format_spacer', 2)) . $value . "\r\n";
				}
			}

			//$out_content .= '% ' . str_repeat('*', OIDplus::config()->getValue('webwhois_output_format_max_line_length', 80)-2)."\r\n";

			$cont = ob_get_contents();
			ob_end_clean();

			$out_content .= $cont;

			if (OIDplus::getPkiStatus()) {
				$signature = '';
				if (@openssl_sign($cont, $signature, OIDplus::getSystemPrivateKey())) {
					$signature = base64_encode($signature);
					$signature = mb_wordwrap($signature, OIDplus::config()->getValue('webwhois_output_format_max_line_length', 80) - strlen('% '), "\r\n", true);
					$signature = "% -----BEGIN RSA SIGNATURE-----\r\n".
					             preg_replace('/^/m', '% ', $signature)."\r\n".
					             "% -----END RSA SIGNATURE-----\r\n";
					$out_content .= $signature;
				}
			}
		}

		if ($format == 'json') {
			$ary = array();

			$current_section = array();
			$ary[] = &$current_section;

			foreach ($out as $data) {
				if ($data['name'] == '') {
					unset($current_section);
					$current_section = array();
					$ary[] = &$current_section;
				} else {
					$key = $data['name'];
					$val = trim($data['value']);
					if (!empty($val)) {
						if (!isset($current_section[$key])) {
							$current_section[$key] = $val;
						} elseif (is_array($current_section[$key])) {
							$current_section[$key][] = $val;
						} else {
							$current_section[$key] = array($current_section[$key], $val);
						}
					}
				}
			}

			// Change $ary=[["query",...], ["object",...], ["ra",...]]
			// to     $bry=["querySection"=>["query",...], "objectsection"=>["object",...], "raSection"=>["ra",...]]
			$bry = array();
			foreach ($ary as $cry) {
				$dry = array_keys($cry);
				if (count($dry) == 0) continue;
				$bry[$dry[0].'Section'] = $cry; /** @phpstan-ignore-line */ // PHPStan thinks that count($dry) is always 0
			}

			// Remove 'ra-', 'ra1-', ... field prefixes from JSON (the prefix is only for the text view)
			foreach ($bry as $section_name => &$cry) { /** @phpstan-ignore-line */ // PHPStan thinks that $bry is empty
				if (preg_match('@^(ra\d*)\\Section@', $section_name, $m)) {
					$ra_id = str_replace('Section', '', $section_name);
					$dry = array();
					foreach ($cry as $name => $val) {
						if ($name == $ra_id) $name = 'ra'; // First field is always 'ra' even in 'ra1Section' etc.
						$name = preg_replace('@^('.preg_quote($ra_id,'@').')\\-@', '', $name);
						$dry[$name] = $val;
					}
					$cry = $dry;
				}
			}

			$ary = array(
				// We use the URN here, because $id of the schema also uses the URN
				'$schema' => $this->JSON_SCHEMA_URN,

				// we need this NAMED root, otherwise PHP will name the sections "0", "1", "2" if the array is not sequencial (e.g. because "signature" is added)
				'oidip' => $bry
			);

			$json = json_encode($ary);

			if (OIDplus::getPkiStatus()) {
				try {
					require_once __DIR__.'/whois/json/security.inc.php';
					$json = oidplus_json_sign($json, OIDplus::getSystemPrivateKey(), OIDplus::getSystemPublicKey());
				} catch (\Exception $e) {
					// die($e->getMessage());
				}
			}

			// Good JSON schema validator here: https://www.jsonschemavalidator.net
			$out_type = 'application/json; charset=UTF-8';
			$out_content .= $json;
		}

		if ($format == 'xml') {
			$xml_oidip = '<oidip><section>';
			foreach ($out as $data) {
				if ($data['name'] == '') {
					$xml_oidip .= '</section><section>';
				} else {
					if ($data['xmlns'] != '') {
						$key = $data['xmlns'].':'.$data['name'];
					} else {
						$key = $data['name'];
					}
					$val = trim($data['value']);
					if (!empty($val)) {
						if (strpos($val,"\n") !== false) {
							$val = str_replace(']]>', ']]]]><![CDATA[>', $val); // Escape ']]>'
							$xml_oidip .= "<$key><![CDATA[$val]]></$key>";
						} else {
							$xml_oidip .= "<$key>".htmlspecialchars($val, ENT_XML1)."</$key>";
						}
					}
				}
			}
			$xml_oidip .= '</section></oidip>';

			$xml_oidip = preg_replace('@<section><(.+)>(.+)</section>@ismU', '<\\1Section><\\1>\\2</\\1Section>', $xml_oidip);

			// Remove 'ra-', 'ra1-', ... field prefixes from XML (the prefix is only for the text view)
			$xml_oidip = preg_replace('@<(/{0,1})ra\\d*-@', '<\\1', $xml_oidip);

			// <ra1Section><ra1>...</ra1> => <ra1Section><ra>...</ra>
			$xml_oidip = preg_replace('@<ra(\\d+)Section><ra\\1>(.+)</ra\\1>@U', '<ra\\1Section><ra>\\2</ra>', $xml_oidip);

			/* Debug:
			$out_type = 'application/xml; charset=UTF-8';
			$out_content .= $xml_oidip;
			die();
			*/

			// Very good XSD validator here: https://www.liquid-technologies.com/online-xsd-validator
			// Also good: https://www.utilities-online.info/xsdvalidation (but does not accept &amp; or &quot; results in "Premature end of data in tag description line ...")
			// Note:
			// - These do not support XSD 1.1
			// - You need to host http://www.w3.org/TR/2002/REC-xmldsig-core-20020212/xmldsig-core-schema.xsd yourself, otherwise there will be a timeout in the validation!!!

			$extra_schemas = array();
			foreach ($out as $data) {
				if (isset($data['xmlns']) && ($data['xmlns'] != '') && !isset($extra_schemas[$data['xmlns']])) {
					$extra_schemas[$data['xmlns']] = array($data['xmlschema'], $data['xmlschemauri']);
				}
			}

			$xml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes" ?'.'>'."\n";
			$xml .= '<root xmlns="'.$this->XML_SCHEMA_URN.'"'."\n";
			$xml .= '      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'."\n";
			foreach ($extra_schemas as $xmlns => list($schema,$schemauri)) {
				$xml .= '      xmlns:'.$xmlns.'="'.$schema.'"'."\n";
			}
			$xml .= '      xsi:schemaLocation="'.$this->XML_SCHEMA_URN.' '.$this->XML_SCHEMA_URL;
			foreach ($extra_schemas as $xmlns => list($schema,$schemauri)) {
				$xml .= ' '.$schema.' '.$schemauri;
			}
			$xml .= '">'."\n";
			$xml .= $xml_oidip."\n";
			$xml .= '</root>';

			if (!OIDplus::getPkiStatus()) {
				$xml .= '<!-- Cannot add signature: OIDplus PKI is not initialized (OpenSSL missing?) -->';
			} else if (!class_exists('DOMDocument')) {
				$xml .= '<!-- Cannot add signature: "PHP-XML" extension not installed -->';
			} else {
				try {
					require_once __DIR__.'/whois/xml/security.inc.php';
					$xml = oidplus_xml_sign($xml, OIDplus::getSystemPrivateKey(), OIDplus::getSystemPublicKey());
				} catch (\Exception $e) {
					$xml .= '<!-- Cannot add signature: '.$e.' -->';
				}
			}

			$out_type = 'application/xml; charset=UTF-8';
			$out_content .= $xml;
		}

		return array($out_content, $out_type);
	}

	/**
	 * @param $id
	 * @return string
	 * @throws OIDplusException
	 */
	protected function show_asn1_appendix($id) {
		if (substr($id,0,4) === 'oid:') {
			$appendix_asn1ids = array();
			$res_asn = OIDplus::db()->query("select * from ###asn1id where oid = ?", array($id));
			while ($row_asn = $res_asn->fetch_object()) {
				$appendix_asn1ids[] = $row_asn->name;
			}

			$appendix = implode(', ', $appendix_asn1ids);
			if (!empty($appendix)) $appendix = " ($appendix)";
		} else {
			$appendix = '';
		}
		return $appendix;
	}

	/**
	 * @param $id
	 * @return bool
	 */
	protected function is_root($id) {
		return empty(explode(':',$id,2)[1]);
	}

	/**
	 * @param $content
	 * @param $authTokens
	 * @return bool
	 */
	protected function authTokenAccepted($content, $authTokens) {
		foreach ($authTokens as $token) {
			if (OIDplusPagePublicWhois::genWhoisAuthToken($content) == $token) return true;
		}
		return false;
	}

	/**
	 * @param $obj
	 * @param $authTokens
	 * @return bool
	 * @throws OIDplusException
	 */
	protected function allowObjectView($obj, $authTokens) {
		// Master auth token (TODO: Have an object-master-token and a ra-master-token?)
		$authToken = trim(OIDplus::config()->getValue('whois_auth_token'));
		if (empty($authToken)) $authToken = false;
		if ($authToken && in_array($authToken, $authTokens)) return true;

		// Per-OID auth tokens

		$curid = $obj->nodeId();
		while ($test_obj = OIDplusObject::findFitting($curid)) {
			// Example: You have an auth Token for 2.999.1.2.3
			// This allows you to view 2.999.1.2.3 and all of its children,
			// as long as they are not confidential (then you need their auth token).
			// 2, 2.999, 2.999.1 and 2.999.1.2 are visible,
			// (because their existence is now obvious).
			if ($test_obj->isConfidential() && !$this->authTokenAccepted($curid, $authTokens)) return false;
			$objParentTest = $test_obj->getParent();
			if (!$objParentTest) break;
			$curid = $objParentTest->nodeId();
		}

		// Allow
		return true;
	}

	/**
	 * @param $row_ra
	 * @param $authTokens
	 * @return bool
	 * @throws OIDplusException
	 */
	protected function allowRAView($row_ra, $authTokens) {
		// Master auth token (TODO: Have an object-master-token and a ra-master-token?)
		$authToken = trim(OIDplus::config()->getValue('whois_auth_token'));
		if (empty($authToken)) $authToken = false;
		if ($authToken && in_array($authToken, $authTokens)) return true;

		// Per-RA auth tokens
		if ($row_ra->privacy && !$this->authTokenAccepted('ra:'.$row_ra->ra_name, $authTokens)) return false;

		// Allow
		return true;
	}

	/**
	 * @param $name
	 * @param $value
	 * @return array
	 */
	protected function _oidip_attr($name, $value) {
		return array(
			'xmlns' => '',
			'xmlschema' => '',
			'xmlschemauri' => '',
			'name' => $name,
			'value' => $value
		);
	}

	/**
	 * @param $out
	 * @return void
	 */
	protected function _oidip_newout_format(&$out) {
		foreach ($out as &$line) {
			if (is_string($line)) {
				$ary = explode(':', $line, 2);
				$key = trim($ary[0]);
				$value = isset($ary[1]) ? trim($ary[1]) : '';

				$line = array(
					'xmlns' => '',
					'xmlschema' => '',
					'xmlschemauri' => '',
					'name' => $key,
					'value' => $value
				);
			} else if (is_array($line)) {
				if (!isset($line['xmlns']))        $line['xmlns'] = '';
				if (!isset($line['xmlschema']))    $line['xmlschema'] = '';
				if (!isset($line['xmlschemauri'])) $line['xmlschemauri'] = '';
				if (!isset($line['name']))         $line['name'] = '';
				if (!isset($line['value']))        $line['value'] = '';
			} else {
				assert(false);
			}
		}
	}

}
