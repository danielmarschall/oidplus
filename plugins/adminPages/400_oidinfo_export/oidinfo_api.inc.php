<?php

/*
 * OID-Info.com API for PHP
 * Copyright 2019 Daniel Marschall, ViaThinkSoft
 * Version 2019-11-06
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

error_reporting(E_ALL | E_NOTICE | E_STRICT | E_DEPRECATED);

if(!defined('STDIN'))  define('STDIN',  fopen('php://stdin',  'rb'));
if(!defined('STDOUT')) define('STDOUT', fopen('php://stdout', 'wb'));
if(!defined('STDERR')) define('STDERR', fopen('php://stderr', 'wb'));

if (file_exists(__DIR__ . '/oid_utils.inc.phps')) require_once __DIR__ . '/oid_utils.inc.phps';
if (file_exists(__DIR__ . '/oid_utils.inc.php'))  require_once __DIR__ . '/oid_utils.inc.php';
if (file_exists(__DIR__ . '/xml_utils.inc.phps')) require_once __DIR__ . '/xml_utils.inc.phps';
if (file_exists(__DIR__ . '/xml_utils.inc.php'))  require_once __DIR__ . '/xml_utils.inc.php';
if (file_exists(__DIR__ . '/../includes/oid_utils.inc.php'))  require_once __DIR__ . '/../includes/oid_utils.inc.php';
if (file_exists(__DIR__ . '/../includes/xml_utils.inc.php'))  require_once __DIR__ . '/../includes/xml_utils.inc.php';
if (file_exists(__DIR__ . '/../../includes/oid_utils.inc.php'))  require_once __DIR__ . '/../../includes/oid_utils.inc.php';
if (file_exists(__DIR__ . '/../../includes/xml_utils.inc.php'))  require_once __DIR__ . '/../../includes/xml_utils.inc.php';
if (file_exists(__DIR__ . '/../../../includes/oid_utils.inc.php'))  require_once __DIR__ . '/../../../includes/oid_utils.inc.php';
if (file_exists(__DIR__ . '/../../../includes/xml_utils.inc.php'))  require_once __DIR__ . '/../../../includes/xml_utils.inc.php';

class OIDInfoException extends Exception {
}

class OIDInfoAPI {

	# --- PART 0: Constants

	// First digit of the ping result
	// "-" = error
	// "0" = OID does not exist
	// "1" = OID does exist, but is not approved yet
	// "2" = OID does exist and is accessible
	/*private*/ const PING_IDX_EXISTS = 0;

	// Second digit of the ping result
	// "-" = error
	// "0" = The OID may not be created
	// "1" = OID is not an illegal OID, and none of its ascendant is a leaf and its parent OID is not frozen
	/*private*/ const PING_IDX_MAY_CREATE = 1;

	/*private*/ const SOFT_CORRECT_BEHAVIOR_NONE = 0;
	/*private*/ const SOFT_CORRECT_BEHAVIOR_LOWERCASE_BEGINNING = 1;
	/*private*/ const SOFT_CORRECT_BEHAVIOR_ALL_POSSIBLE = 2;

	/*public*/ const DEFAULT_ILLEGALITY_RULE_FILE = __DIR__ . '/oid_illegality_rules';

	# --- Part 1: "Ping API" for checking if OIDs are available or allowed to create

	public $verbosePingProviders = array('https://misc.daniel-marschall.de/oid-repository/ping_oid.php?oid={OID}');

	private $pingCache = array();

	public $pingCacheMaxAge = 3600;

	public function clearPingCache() {
		$this->pingCache = array();
	}

	public function checkOnlineExists($oid) {
		if (!self::strictCheckSyntax($oid)) return false;

		$pingResult = $this->pingOID($oid);
		$ret = $pingResult[self::PING_IDX_EXISTS] >= 1;
		return $ret;
	}

	public function checkOnlineAvailable($oid) {
		if (!self::strictCheckSyntax($oid)) return false;

		$pingResult = $this->pingOID($oid);
		$ret = $pingResult[self::PING_IDX_EXISTS] == 2;
		return $ret;
	}

	public function checkOnlineAllowed($oid) {
		if (!self::strictCheckSyntax($oid)) return false;

		$pingResult = $this->pingOID($oid);
		return $pingResult[self::PING_IDX_MAY_CREATE] == 1;
	}

	public function checkOnlineMayCreate($oid) {
		if (!self::strictCheckSyntax($oid)) return false;

		$pingResult = $this->pingOID($oid);

		// OID is either illegal, or one of their parents are leaf or frozen
		# if (!checkOnlineAllowed($oid)) return false;
		if ($pingResult[self::PING_IDX_MAY_CREATE] == 0) return false;

		// The OID exists already, so we don't need to create it again
		# if ($this->checkOnlineExists($oid)) return false;
		if ($pingResult[self::PING_IDX_EXISTS] >= 1) return false;

		return true;
	}

	protected function pingOID($oid) {
		if (isset($this->pingCache[$oid])) {
			$cacheAge = $this->pingCache[$oid][0] - time();
			if ($cacheAge <= $this->pingCacheMaxAge) {
				return $this->pingCache[$oid][1];
			}
		}

		if (count($this->verbosePingProviders) == 0) {
			throw new OIDInfoException("No verbose ping provider available!");
		}

		$res = false;
		foreach ($this->verbosePingProviders as $url) {
			$url = str_replace('{OID}', $oid, $url);
			$cn = @file_get_contents($url);
			if ($cn === false) continue;
			$loc_res = trim($cn);
			if (strpos($loc_res, '-') === false) {
				$res = $loc_res;
				break;
			}
		}
		if ($res === false) {
			throw new OIDInfoException("Could not ping OID $oid status!");
		}

		// if ($this->pingCacheMaxAge >= 0) {
			$this->pingCache[$oid] = array(time(), $res);
		//}

		return $res;
	}

	# --- PART 2: Syntax checking

	public static function strictCheckSyntax($oid) {
		return oid_valid_dotnotation($oid, false, false, 1);
	}

	// Returns false if $oid has wrong syntax
	// Return an OID without leading dot or zeroes, if the syntax is acceptable
	public static function trySanitizeOID($oid) {
		// Allow leading dots and leading zeroes, but remove then afterwards
		$ok = oid_valid_dotnotation($oid, true, true, 1);
		if ($ok === false) return false;

		return sanitizeOID($oid, $oid[0] == '.');
	}

	# --- PART 3: XML file creation

	protected static function eMailValid($email) {
		# TODO: use isemail project

		if (empty($email)) return false;

		if (strpos($email, '@') === false) return false;

		$ary = explode('@', $email, 2);
		if (!isset($ary[1])) return false;
		if (strpos($ary[1], '.') === false) return false;

		return true;
	}

	public function softCorrectEMail($email, $params) {
		$email = str_replace(' ', '', $email);
		$email = str_replace('&', '@', $email);
		$email = str_replace('(at)', '@', $email);
		$email = str_replace('[at]', '@', $email);
		$email = str_replace('(dot)', '.', $email);
		$email = str_replace('[dot]', '.', $email);
		$email = trim($email);

		if (!$params['allow_illegal_email'] && !self::eMailValid($email)) {
			return '';
		}

		return $email;
	}

	public function softCorrectPhone($phone, $params) {
		// TODO: if no "+", add "+1" , but only if address is in USA
		// TODO: or use param to fixate country if it is not known
		/*
		NOTE: with german phone numbers, this will cause trouble, even if we assume "+49"
			06223 / 1234
			shall be
			+49 6223 1234
			and not
			+49 06223 1234
		*/

		$phone = str_replace('-', ' ', $phone);
		$phone = str_replace('.', ' ', $phone);
		$phone = str_replace('/', ' ', $phone);
		$phone = str_replace('(', ' ', $phone);
		$phone = str_replace(')', ' ', $phone);

		// HL7 registry has included this accidently
		$phone = str_replace('&quot;', '', $phone);

		$phone = trim($phone);

		return $phone;
	}

	private static function strip_to_xhtml_light($str, $allow_strong_text=false) {
		// <strong> is allowed in the XSD, but not <b>
		$str = str_ireplace('<b>', '<strong>', $str);
		$str = str_ireplace('</b>', '</strong>', $str);

		if (!$allow_strong_text) {
			// <strong> is only used for very important things like the word "deprecated". It should therefore not used for anything else
			$str = str_ireplace('<strong>', '', $str);
			$str = str_ireplace('</strong>', '', $str);
		}

		$str = preg_replace('@<\s*script.+<\s*/script.*>@isU', '', $str);
		$str = preg_replace('@<\s*style.+<\s*/style.*>@isU', '', $str);

		$str = preg_replace_callback(
			'@<(\s*/{0,1}\d*)([^\s/>]+)(\s*[^>]*)>@i',
			function ($treffer) {
				// see http://oid-info.com/xhtml-light.xsd
				$whitelist = array('a', 'br', 'code', 'em', 'font', 'img', 'li', 'strong', 'sub', 'sup', 'ul');

				$pre = $treffer[1];
				$tag = $treffer[2];
				$attrib = $treffer[3];
				if (in_array($tag, $whitelist)) {
					return '<'.$pre.$tag.$attrib.'>';
				} else {
					return '';
				}
			}, $str);

		return $str;
	}

	const OIDINFO_CORRECT_DESC_OPTIONAL_ENDING_DOT = 0;
	const OIDINFO_CORRECT_DESC_ENFORCE_ENDING_DOT = 1;
	const OIDINFO_CORRECT_DESC_DISALLOW_ENDING_DOT = 2;

	public function correctDesc($desc, $params, $ending_dot_policy=self::OIDINFO_CORRECT_DESC_OPTIONAL_ENDING_DOT, $enforce_xhtml_light=false) {
		$desc = trim($desc);

		$desc = preg_replace('@<!\\[CDATA\\[(.+)\\]\\]>@ismU', '\\1', $desc);

		if (substr_count($desc, '>') != substr_count($desc, '<')) {
			$params['allow_html'] = false;
		}

		$desc = str_replace("\r", '', $desc);

		if (!$params['allow_html']) {
			// htmlentities_numeric() does this for us
			/*
			$desc = str_replace('&', '&amp;', $desc);
			$desc = str_replace('<', '&lt;', $desc);
			$desc = str_replace('>', '&gt;', $desc);
			$desc = str_replace('"', '&quot;', $desc);
			$desc = str_replace("'", '&#39;', $desc); // &apos; is not HTML. It is XML
			*/

			$desc = str_replace("\n", '<br />', $desc);
		} else {
			// Some problems we had with HL7 registry
			$desc = preg_replace('@&lt;(/{0,1}(p|i|b|u|ul|li))&gt;@ismU', '<\\1>', $desc);
			# preg_match_all('@&lt;[^ :\\@]+&gt;@ismU', $desc, $m);
			# if (count($m[0]) > 0) print_r($m);

			$desc = preg_replace('@<i>(.+)&lt;i/&gt;@ismU', '<i>\\1</i>', $desc);
			$desc = str_replace('<p><p>', '</p><p>', $desc);

			// <p> are not supported by oid-info.com
			$desc = str_replace('<p>', '<br /><br />', $desc);
			$desc = str_replace('</p>', '', $desc);
		}

		// Escape unicode characters as numeric &#...;
		// The XML 1.0 standard does only has a few entities, but nothing like e.g. &euro; , so we prefer numeric

		//$desc = htmlentities_numeric($desc, $params['allow_html']);
		if (!$params['allow_html']) $desc = htmlentities($desc);
		$desc = html_named_to_numeric_entities($desc);

		// Remove HTML tags which are not allowed
		if ($params['allow_html'] && (!$params['ignore_xhtml_light']) && $enforce_xhtml_light) {
			// oid-info.com does only allow a few HTML tags
			// see http://oid-info.com/xhtml-light.xsd
			$desc = self::strip_to_xhtml_light($desc);
		}

		// Solve some XML problems...
		$desc = preg_replace('@<\s*br\s*>@ismU', '<br/>', $desc); // auto close <br>
		$desc = preg_replace('@(href\s*=\s*)(["\'])(.*)&([^#].*)(\2)@ismU', '\1\2\3&amp;\4\5', $desc); // fix "&" inside href-URLs to &amp;
		// TODO: what do we do if there are more XHTML errors (e.g. additional open tags) which would make the XML invalid?

		// "Trim" <br/>
		do { $desc = preg_replace('@^\s*<\s*br\s*/{0,1}\s*>@isU', '', $desc, -1, $count); } while ($count > 0); // left trim
		do { $desc = preg_replace('@<\s*br\s*/{0,1}\s*>\s*$@isU', '', $desc, -1, $count); } while ($count > 0); // right trim

		// Correct double-encoded stuff
		if (!isset($params['tolerant_htmlentities']) || $params['tolerant_htmlentities']) {
			do {
				$old_desc = $desc;
				# Full list of entities: https://www.freeformatter.com/html-entities.html
				# Max: 8 chars ( &thetasym; )
				# Min: 2 chars ( lt,gt,ni,or,ne,le,ge,Mu,Nu,Xi,Pi,mu,nu,xi,pi )
				$desc = preg_replace('@(&|&amp;)(#|&#35;)(\d+);@ismU', '&#\3;', $desc);
				$desc = preg_replace('@(&|&amp;)([a-zA-Z0-9]{2,8});@ismU', '&\2;', $desc);
			} while ($old_desc != $desc);
		}

		// TODO: use the complete list of oid-info.com
		// TODO: Make this step optional using $params
		/*
		Array
		(
		    [0] => Root OID for
		    [1] => OID for
		    [2] => OID identifying
		    [3] => Top arc for
		    [4] => Arc for
		    [5] => arc root
		    [6] => Node for
		    [7] => Leaf node for
		    [8] => This OID describes
		    [9] => [tT]his oid
		    [10] => This arc describes
		    [11] => This identifies
		    [12] => Identifies a
		    [13] => [Oo]bject [Ii]dentifier
		    [14] => Identifier for
		    [15] => This [Ii]dentifier is for
		    [16] => Identifiers used by
		    [17] => identifier$
		    [18] => This branch
		    [19] => Branch for
		    [20] => Child tree for
		    [21] => Child for
		    [22] => Subtree for
		    [23] => Sub-OID
		    [24] => Tree for
		    [25] => Child object
		    [26] => Parent OID
		    [27] =>  root for
		    [28] => Assigned for
		    [29] => Used to identify
		    [30] => Used in
		    [31] => Used for
		    [32] => For use by
		    [33] => Entry for
		    [34] => This is for
		    [35] =>  ["]?OID["]?
		    [36] => ^OID
		    [37] =>  OID$
		    [38] =>  oid
		    [39] =>  oid$
		    [40] =>  OIDs
		)
		$x = 'Root OID for ; OID for ; OID identifying ; Top arc for ; Arc for ; arc root; Node for ; Leaf node for ; This OID describes ; [tT]his oid ; This arc describes ; This identifies ; Identifies a ; [Oo]bject [Ii]dentifier; Identifier for ; This [Ii]dentifier is for ; Identifiers used by ; identifier$; This branch ; Branch for ; Child tree for ; Child for ; Subtree for ; Sub-OID; Tree for ; Child object; Parent OID;  root for ; Assigned for ; Used to identify ; Used in ; Used for ; For use by ; Entry for ; This is for ;  ["]?OID["]? ; ^OID ;  OID$;  oid ;  oid$;  OIDs';
		$ary = explode('; ', $x);
		print_r($ary);
		*/
		$desc = preg_replace("@^Root OID for the @i",                   '', $desc);
		$desc = preg_replace("@^Root OID for @i",                       '', $desc);
		$desc = preg_replace("@^OID root for the @i",                   '', $desc);
		$desc = preg_replace("@^OID root for @i",                       '', $desc);
		$desc = preg_replace("@^This OID will be used for @i",          '', $desc);
		$desc = preg_replace("@^This will be a generic OID for the @i", '', $desc);
		$desc = preg_replace("@^OID for @i",                            '', $desc);
		$desc = preg_replace("@ Root OID$@i",                           '', $desc);
		$desc = preg_replace("@ OID$@i",                                '', $desc);
		$desc = preg_replace("@ OID Namespace$@i",                      '', $desc);
		$desc = preg_replace("@^OID for @i",                            '', $desc);

		$desc = rtrim($desc);
		if ($ending_dot_policy == self::OIDINFO_CORRECT_DESC_ENFORCE_ENDING_DOT) {
			if (($desc != '') && (substr($desc, -1)) != '.') $desc .= '.';
		} else if ($ending_dot_policy == self::OIDINFO_CORRECT_DESC_DISALLOW_ENDING_DOT) {
			$desc = preg_replace('@\\.$@', '', $desc);
		}

		return $desc;
	}

	public function xmlAddHeader($firstName, $lastName, $email) {
		// TODO: encode

		$firstName = htmlentities_numeric($firstName);
		if (empty($firstName)) {
			throw new OIDInfoException("Please supply a first name");
		}

		$lastName  = htmlentities_numeric($lastName);
		if (empty($lastName)) {
			throw new OIDInfoException("Please supply a last name");
		}

		$email     = htmlentities_numeric($email);
		if (empty($email)) {
			throw new OIDInfoException("Please supply an email address");
		}

//		$out  = "<!DOCTYPE oid-database>\n\n";
		$out  = '<?xml version="1.0" encoding="UTF-8" ?>'."\n";
		$out .= '<oid-database xmlns="http://oid-info.com"'."\n";
		$out .= '              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'."\n";
		$out .= '              xsi:schemaLocation="http://oid-info.com '."\n";
		$out .= '                                  http://oid-info.com/oid.xsd">'."\n";
		$out .= "\t<submitter>\n";
		$out .= "\t\t<first-name>$firstName</first-name>\n";
		$out .= "\t\t<last-name>$lastName</last-name>\n";
		$out .= "\t\t<email>$email</email>\n";
		$out .= "\t</submitter>\n";

		if (!self::eMailValid($email)) {
			throw new OIDInfoException("eMail address '$email' is invalid");
		}

		return $out;
	}

	public function xmlAddFooter() {
		return "</oid-database>\n";
	}

	/*
		-- CODE TEMPLATE --

		$params['allow_html'] = false; // Allow HTML in <description> and <information>
		$params['allow_illegal_email'] = true; // We should allow it, because we don't know if the user has some kind of human-readable anti-spam technique
		$params['soft_correct_behavior'] = OIDInfoAPI::SOFT_CORRECT_BEHAVIOR_NONE;
		$params['do_online_check'] = false; // Flag to disable this online check, because it generates a lot of traffic and runtime.
		$params['do_illegality_check'] = true;
		$params['do_simpleping_check'] = true;
		$params['auto_extract_name'] = '';
		$params['auto_extract_url'] = '';
		$params['always_output_comment'] = false; // Also output comment if there was an error (e.g. OID already existing)
		$params['creation_allowed_check'] = true;
		$params['tolerant_htmlentities'] = true;
		$params['ignore_xhtml_light'] = false;

		$elements['synonymous-identifier'] = ''; // string or array
		$elements['description'] = '';
		$elements['information'] = '';

		$elements['first-registrant']['first-name'] = '';
		$elements['first-registrant']['last-name'] = '';
		$elements['first-registrant']['address'] = '';
		$elements['first-registrant']['email'] = '';
		$elements['first-registrant']['phone'] = '';
		$elements['first-registrant']['fax'] = '';
		$elements['first-registrant']['creation-date'] = '';

		$elements['current-registrant']['first-name'] = '';
		$elements['current-registrant']['last-name'] = '';
		$elements['current-registrant']['address'] = '';
		$elements['current-registrant']['email'] = '';
		$elements['current-registrant']['phone'] = '';
		$elements['current-registrant']['fax'] = '';
		$elements['current-registrant']['modification-date'] = '';

		$oid = '1.2.3';

		$comment = 'test';

		echo $oa->createXMLEntry($oid, $elements, $params, $comment);
	*/
	public function createXMLEntry($oid, $elements, $params, $comment='') {
		// Backward compatibility
		if (!isset($params['do_csv_check']))           $params['do_simpleping_check'] = true;

		// Set default behavior
		if (!isset($params['allow_html']))             $params['allow_html'] = false; // Allow HTML in <description> and <information>
		if (!isset($params['allow_illegal_email']))    $params['allow_illegal_email'] = true; // We should allow it, because we don't know if the user has some kind of human-readable anti-spam technique
		if (!isset($params['soft_correct_behavior']))  $params['soft_correct_behavior'] = self::SOFT_CORRECT_BEHAVIOR_NONE;
		if (!isset($params['do_online_check']))        $params['do_online_check'] = false; // Flag to disable this online check, because it generates a lot of traffic and runtime.
		if (!isset($params['do_illegality_check']))    $params['do_illegality_check'] = true;
		if (!isset($params['do_simpleping_check']))    $params['do_simpleping_check'] = true;
		if (!isset($params['auto_extract_name']))      $params['auto_extract_name'] = '';
		if (!isset($params['auto_extract_url']))       $params['auto_extract_url'] = '';
		if (!isset($params['always_output_comment']))  $params['always_output_comment'] = false; // Also output comment if there was an error (e.g. OID already existing)
		if (!isset($params['creation_allowed_check'])) $params['creation_allowed_check'] = true;
		if (!isset($params['tolerant_htmlentities']))  $params['tolerant_htmlentities'] = true;
		if (!isset($params['ignore_xhtml_light']))     $params['ignore_xhtml_light'] = false;

		$out = '';
		if (!empty($comment)) $out .= "\t\t<!-- $comment -->\n";

		if ($params['always_output_comment']) {
			$err = $out;
		} else {
			$err = false;
		}

		if (isset($elements['dotted_oid'])) {
			throw new OIDInfoException("'dotted_oid' in the \$elements array is not supported. Please use the \$oid argument.");
		}
		if (isset($elements['value'])) {
			// TODO: WHAT SHOULD WE DO WITH THAT?
			throw new OIDInfoException("'value' in the \$elements array is currently not supported.");
		}

		$bak_oid = $oid;
		$oid = self::trySanitizeOID($oid);
		if ($oid === false) {
			fwrite(STDOUT/*STDERR*/,"<!-- ERROR: Ignored '$bak_oid', because it is not a valid OID -->\n");
			return $err;
		}

		if ($params['creation_allowed_check']) {
			if (!$this->oidMayCreate($oid, $params['do_online_check'], $params['do_simpleping_check'], $params['do_illegality_check'])) {
				fwrite(STDOUT/*STDERR*/,"<!-- ERROR: Creation of $oid disallowed -->\n");
				return $err;
			}
		} else {
			if ($params['do_illegality_check'] && ($this->illegalOid($oid))) {
				fwrite(STDOUT/*STDERR*/,"<!-- ERROR: Creation of $oid disallowed -->\n");
				return $err;
			}
		}

		$elements['description'] = $this->correctDesc($elements['description'], $params, self::OIDINFO_CORRECT_DESC_DISALLOW_ENDING_DOT, true);
		$elements['information'] = $this->correctDesc($elements['information'], $params, self::OIDINFO_CORRECT_DESC_ENFORCE_ENDING_DOT, true);

		// Request by O.D. 26 August 2019
		$elements['description'] = trim($elements['description']);
		if (preg_match('@^[a-z]@', $elements['description'], $m)) {
			$ending_dot_policy = self::OIDINFO_CORRECT_DESC_DISALLOW_ENDING_DOT; // for description
			if (($ending_dot_policy != self::OIDINFO_CORRECT_DESC_ENFORCE_ENDING_DOT) && (strpos($elements['description'], ' ') === false)) { // <-- added by DM
				$elements['description'] = '"' . $elements['description'] . '"';
			}
		}
		// End request by O.D. 26. August 2019

		if ($params['auto_extract_name'] || $params['auto_extract_url']) {
			if (!empty($elements['information'])) $elements['information'] .= '<br /><br />';
			if ($params['auto_extract_name'] || $params['auto_extract_url']) {
				$elements['information'] .= 'Automatically extracted from <a href="'.$params['auto_extract_url'].'">'.$params['auto_extract_name'].'</a>.';
			} else if ($params['auto_extract_name']) {
				$elements['information'] .= 'Automatically extracted from '.$params['auto_extract_name'];
			} else if ($params['auto_extract_url']) {
				$hr_url = $params['auto_extract_url'];
				// $hr_url = preg_replace('@^https{0,1}://@ismU', '', $hr_url);
				$hr_url = preg_replace('@^http://@ismU', '', $hr_url);
				$elements['information'] .= 'Automatically extracted from <a href="'.$params['auto_extract_url'].'">'.$hr_url.'</a>.';
			}
		}

		// Validate ASN.1 ID
		if (isset($elements['synonymous-identifier'])) {
			if (!is_array($elements['synonymous-identifier'])) {
				$elements['synonymous-identifier'] = array($elements['synonymous-identifier']);
			}
			foreach ($elements['synonymous-identifier'] as &$synid) {
				if ($synid == '') {
					$synid = null;
					continue;
				}

				$behavior = $params['soft_correct_behavior'];

				if ($behavior == self::SOFT_CORRECT_BEHAVIOR_NONE) {
					if (!oid_id_is_valid($synid)) $synid = null;
				} else if ($behavior == self::SOFT_CORRECT_BEHAVIOR_LOWERCASE_BEGINNING) {
					$synid[0] = strtolower($synid[0]);
					if (!oid_id_is_valid($synid)) $synid = null;
				} else if ($behavior == self::SOFT_CORRECT_BEHAVIOR_ALL_POSSIBLE) {
					$synid = oid_soft_correct_id($synid);
					// if (!oid_id_is_valid($synid)) $synid = null;
				} else {
					throw new OIDInfoException("Unexpected soft-correction behavior for ASN.1 IDs");
				}
			}
		}

		// ATTENTION: the XML-generator will always add <dotted-oid> , but what will happen if additionally an
		// asn1-path (<value>) is given? (the resulting OIDs might be inconsistent/mismatch)
		if (isset($elements['value']) && (!asn1_path_valid($elements['value']))) {
			unset($elements['value']);
		}

		// Validate IRI (currently not supported by oid-info.com, but the tag name is reserved)
		if (isset($elements['iri'])) {
			if (!is_array($elements['iri'])) {
				$elements['iri'] = array($elements['iri']);
			}
			foreach ($elements['iri'] as &$iri) {
				// Numeric-only nicht erlauben. Das wäre ja nur in einem IRI-Pfad gültig, aber nicht als einzelner Identifier
				if (!iri_arc_valid($iri, false)) $iri = null;
			}
		}

		if (isset($elements['first-registrant']['phone']))
		$elements['first-registrant']['phone']   = $this->softCorrectPhone($elements['first-registrant']['phone'], $params);

		if (isset($elements['current-registrant']['phone']))
		$elements['current-registrant']['phone'] = $this->softCorrectPhone($elements['current-registrant']['phone'], $params);

		if (isset($elements['first-registrant']['fax']))
		$elements['first-registrant']['fax']     = $this->softCorrectPhone($elements['first-registrant']['fax'], $params);

		if (isset($elements['current-registrant']['fax']))
		$elements['current-registrant']['fax']   = $this->softCorrectPhone($elements['current-registrant']['fax'], $params);

		if (isset($elements['first-registrant']['email']))
		$elements['first-registrant']['email']   = $this->softCorrectEMail($elements['first-registrant']['email'], $params);

		if (isset($elements['current-registrant']['email']))
		$elements['current-registrant']['email'] = $this->softCorrectEMail($elements['current-registrant']['email'], $params);

		// TODO: if name is empty, but address has 1 line, take it as firstname (but remove hyperlink)

		$out_loc = '';
		foreach ($elements as $name => $val) {
			if (($name == 'first-registrant') || ($name == 'current-registrant')) {
				$out_loc2 = '';
				foreach ($val as $name2 => $val2) {
					if (is_null($val2)) continue;
					if (empty($val2)) continue;

					if (!is_array($val2)) $val2 = array($val2);

					foreach ($val2 as $val3) {
						// if (is_null($val3)) continue;
						if (empty($val3)) continue;

						if ($name2 == 'address') {
							// $val3 = htmlentities_numeric($val3);
							$val3 = $this->correctDesc($val3, $params, self::OIDINFO_CORRECT_DESC_DISALLOW_ENDING_DOT, true);
						} else {
							// $val3 = htmlentities_numeric($val3);
							$val3 = $this->correctDesc($val3, $params, self::OIDINFO_CORRECT_DESC_DISALLOW_ENDING_DOT, false);
						}
						$out_loc2 .= "\t\t\t<$name2>".$val3."</$name2>\n";
					}
				}

				if (!empty($out_loc2)) {
					$out_loc .= "\t\t<$name>\n";
					$out_loc .= $out_loc2;
					$out_loc .= "\t\t</$name>\n";
				}
			} else {
				// if (is_null($val)) continue;
				if (empty($val) && ($name != 'description')) continue; // description is mandatory, according to http://oid-info.com/oid.xsd

				if (!is_array($val)) $val = array($val);

				foreach ($val as $val2) {
					// if (is_null($val2)) continue;
					if (empty($val2) && ($name != 'description')) continue; // description is mandatory, according to http://oid-info.com/oid.xsd

					if (($name != 'description') && ($name != 'information')) { // don't correctDesc description/information, because we already did it above.
						// $val2 = htmlentities_numeric($val2);
						$val2 = $this->correctDesc($val2, $params, self::OIDINFO_CORRECT_DESC_OPTIONAL_ENDING_DOT, false);
					}
					$out_loc .= "\t\t<$name>".$val2."</$name>\n";
				}
			}
		}

		if (!empty($out)) {
			$out = "\t<oid>\n"."\t\t".trim($out)."\n";
		} else {
			$out = "\t<oid>\n";
		}
		$out .= "\t\t<dot-notation>$oid</dot-notation>\n";
		$out .= $out_loc;
		$out .= "\t</oid>\n";

		return $out;
	}

	# --- PART 4: Offline check if OIDs are illegal

	protected $illegality_rules = array();

	public function clearIllegalityRules() {
		$this->illegality_rules = array();
	}

	public function loadIllegalityRuleFile($file) {
		if (!file_exists($file)) {
			throw new OIDInfoException("Error: File '$file' does not exist");
		}

		$lines = file($file);

		if ($lines === false) {
			throw new OIDInfoException("Error: Could not load '$file'");
		}

		$signature = trim(array_shift($lines));
		if (($signature != '[1.3.6.1.4.1.37476.3.1.5.1]') && ($signature != '[1.3.6.1.4.1.37476.3.1.5.2]')) {
			throw new OIDInfoException("'$file' does not seem to a valid illegality rule file (file format OID does not match. Signature $signature unexpected)");
		}

		foreach ($lines as $line) {
			// Remove comments
			$ary  = explode('--', $line);
			$rule = trim($ary[0]);

			if ($rule !== '') $this->addIllegalityRule($rule);
		}
	}

	public function addIllegalityRule($rule) {
		$test = $rule;
		$test = preg_replace('@\\.\\(!\\d+\\)@ismU', '.0', $test); // added in ver 2
		$test = preg_replace('@\\.\\(\\d+\\+\\)@ismU', '.0', $test);
		$test = preg_replace('@\\.\\(\\d+\\-\\)@ismU', '.0', $test);
		$test = preg_replace('@\\.\\(\\d+\\-\\d+\\)@ismU', '.0', $test);
		$test = preg_replace('@\\.\\*@ismU', '.0', $test);

		if (!oid_valid_dotnotation($test, false, false, 1)) {
			throw new OIDInfoException("Illegal illegality rule '$rule'.");
		}

		$this->illegality_rules[] = $rule;
	}

	private static function bigint_cmp($a, $b) {
		if (function_exists('bccomp')) {
			return bccomp($a, $b);
		}

		if (function_exists('gmp_cmp')) {
			return gmp_cmp($a, $b);
		}

		if ($a > $b) return 1;
		if ($a < $b) return -1;
		return 0;
	}

	public function illegalOID($oid, &$illegal_root='') {
		$bak = $oid;
		$oid = self::trySanitizeOID($oid);
		if ($oid === false) {
			$illegal_root = $bak;
			return true; // is illegal
		}

		$rules = $this->illegality_rules;

		foreach ($rules as $rule) {
			$rule = str_replace(array('(', ')'), '', $rule);

			$oarr = explode('.', $oid);
			$rarr = explode('.', $rule);

			if (count($oarr) < count($rarr)) continue;

			$rulefit = true;

			$illrootary = array();

			$vararcs = 0;
			$varsfit = 0;
			for ($i=0; $i<count($rarr); $i++) {
				$oelem = $oarr[$i];
				$relem = $rarr[$i];

				$illrootary[] = $oelem;

				if ($relem == '*') $relem = '0+';

				$startchar = substr($relem, 0, 1);
				$endchar = substr($relem, -1, 1);
				if ($startchar == '!') { // added in ver 2
					$vararcs++;
					$relem = substr($relem, 1, strlen($relem)-1); // cut away first char
					$cmp = self::bigint_cmp($oelem, $relem) != 0;
					if ($cmp) $varsfit++;
				} else if ($endchar == '+') {
					$vararcs++;
					$relem = substr($relem, 0, strlen($relem)-1); // cut away last char
					$cmp = self::bigint_cmp($oelem, $relem) >= 0;
					if ($cmp) $varsfit++;
				} else if ($endchar == '-') {
					$vararcs++;
					$relem = substr($relem, 0, strlen($relem)-1); // cut away last char
					$cmp = self::bigint_cmp($oelem, $relem) <= 0;
					if ($cmp) $varsfit++;
				} else if (strpos($relem, '-') !== false) {
					$vararcs++;
					$limarr = explode('-', $relem);
					$limmin = $limarr[0];
					$limmax = $limarr[1];
					$cmp_min = self::bigint_cmp($oelem, $limmin) >= 0;
					$cmp_max = self::bigint_cmp($oelem, $limmax) <= 0;
					if ($cmp_min && $cmp_max) $varsfit++;
				} else {
					if ($relem != $oelem) {
						$rulefit = false;
						break;
					}
				}
			}

			if ($rulefit && ($vararcs == $varsfit)) {
				$illegal_root = implode('.', $illrootary);
				return true; // is illegal
			}
		}

		$illegal_root = '';
		return false; // not illegal
	}

	# --- PART 5: Misc functions

	function __construct() {
		if (file_exists(self::DEFAULT_ILLEGALITY_RULE_FILE)) {
			$this->loadIllegalityRuleFile(self::DEFAULT_ILLEGALITY_RULE_FILE);
		}
	}

	public static function getPublicURL($oid) {
		return "http://oid-info.com/get/$oid";
	}

	public function oidExisting($oid, $onlineCheck=true, $useSimplePingProvider=true) {
		$bak_oid = $oid;
		$oid = self::trySanitizeOID($oid);
		if ($oid === false) {
			throw new OIDInfoException("'$bak_oid' is not a valid OID");
		}

		$canuseSimplePingProvider = $useSimplePingProvider && $this->simplePingProviderAvailable();
		if ($canuseSimplePingProvider) {
			if ($this->simplePingProviderCheckOID($oid)) return true;
		}
		if ($onlineCheck) {
			return $this->checkOnlineExists($oid);
		}
		if ((!$canuseSimplePingProvider) && (!$onlineCheck)) {
			throw new OIDInfoException("No simple or verbose checking method chosen/available");
		}
		return false;
	}

	public function oidMayCreate($oid, $onlineCheck=true, $useSimplePingProvider=true, $illegalityCheck=true) {
		$bak_oid = $oid;
		$oid = self::trySanitizeOID($oid);
		if ($oid === false) {
			throw new OIDInfoException("'$bak_oid' is not a valid OID");
		}

		if ($illegalityCheck && $this->illegalOID($oid)) return false;

		$canuseSimplePingProvider = $useSimplePingProvider && $this->simplePingProviderAvailable();
		if ($canuseSimplePingProvider) {
			if ($this->simplePingProviderCheckOID($oid)) return false;
		}
		if ($onlineCheck) {
			return $this->checkOnlineMayCreate($oid);
		}
		if ((!$canuseSimplePingProvider) && (!$onlineCheck)) {
			throw new OIDInfoException("No simple or verbose checking method chosen/available");
		}
		return true;
	}

	# --- PART 6: Simple Ping Providers
	# TODO: Question ... can't these provider concepts (SPP and VPP) not somehow be combined?

	protected $simplePingProviders = array();

	public function addSimplePingProvider($addr) {
		if (!isset($this->simplePingProviders[$addr])) {
			if (strtolower(substr($addr, -4, 4)) == '.csv') {
				$this->simplePingProviders[$addr] = new CSVSimplePingProvider($addr);
			} else {
				$this->simplePingProviders[$addr] = new OIDSimplePingProvider($addr);
				// $this->simplePingProviders[$addr]->connect();
			}
		}
		return $this->simplePingProviders[$addr];
	}

	public function removeSimplePingProvider($addr) {
		$this->simplePingProviders[$addr]->disconnect();
		unset($this->simplePingProviders[$addr]);
	}

	public function removeAllSimplePingProviders() {
		foreach ($this->simplePingProviders as $addr => $obj) {
			$this->removeSimplePingProvider($addr);
		}
	}

	public function listSimplePingProviders() {
		$out = array();
		foreach ($this->simplePingProviders as $addr => $obj) {
			$out[] = $addr;
		}
		return $out;
	}

	public function simplePingProviderCheckOID($oid) {
		if (!$this->simplePingProviderAvailable()) {
			throw new OIDInfoException("No simple ping providers available.");
		}

		$one_null = false;
		foreach ($this->simplePingProviders as $addr => $obj) {
			$res = $obj->queryOID($oid);
			if ($res) return true;
			if ($res !== false) $one_null = true;
		}

		return $one_null ? null : false;
	}

	public function simplePingProviderAvailable() {
		return count($this->simplePingProviders) >= 1;
	}

}

interface IOIDSimplePingProvider {
	public function queryOID($oid);
	public function disconnect();
	public function connect();
}

class CSVSimplePingProvider implements IOIDSimplePingProvider {
	protected $csvfile = '';
	protected $lines = array();
	protected $filemtime = 0;

	public function queryOID($oid) {
		$this->reloadCSV();
		return in_array($oid, $this->lines);
	}

	public function disconnect() {
		// Nothing
	}

	public function connect() {
		// Nothing
	}

	// TODO: This cannot handle big CSVs. We need to introduce the old code of "2016-09-02_old_oidinfo_api_with_csv_reader.zip" here.
	protected function reloadCSV() {
		if (!file_exists($this->csvfile)) {
			throw new OIDInfoException("File '".$this->csvfile."' does not exist");
		}
		$filemtime = filemtime($this->csvfile);
		if ($filemtime != $this->filemtime) {
			$this->lines = file($csvfile);
			$this->filemtime = $filemtime;
		}
	}

	function __construct($csvfile) {
		$this->csvfile = $csvfile;
		$this->reloadCSV();
	}
}


class OIDSimplePingProvider implements IOIDSimplePingProvider {
	protected $addr = '';
	protected $connected = false;
	protected $socket = null;

	const SPP_MAX_CONNECTION_ATTEMPTS = 3; // TODO: Auslagern in OIDInfoAPI Klasse...?

	const DEFAULT_PORT = 49500;

	protected function spp_reader_init() {
		$this->spp_reader_uninit();

		$ary = explode(':', $this->addr);
		$host = $ary[0];
		$service_port = isset($ary[1]) ? $ary[1] : self::DEFAULT_PORT;
		$address = @gethostbyname($host);
		if ($address === false) {
			echo "gethostbyname() failed.\n"; // TODO: exceptions? (Auch alle "echos" darunter)
			return false;
		}
		$this->socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if ($this->socket === false) {
			echo "socket_create() failed: " . socket_strerror(socket_last_error()) . "\n";
			return false;
		}
		$result = @socket_connect($this->socket, $address, $service_port);
		if ($result === false) {
			echo "socket_connect() failed: " . socket_strerror(socket_last_error($this->socket)) . "\n";
			return false;
		}

		$this->connected = true;
	}

	protected function spp_reader_avail($oid, $failcount=0) {
		$in = "${oid}\n\0"; // PHP's socket_send() does not send a trailing \n . There needs to be something after the \n ... :(

		if ($failcount >= self::SPP_MAX_CONNECTION_ATTEMPTS) {
			echo "Query $oid: CONNECTION FAILED!\n";
			return null;
		}

		if (!$this->connected) {
			$this->spp_reader_init();
		}

		$s = @socket_send($this->socket, $in, strlen($in), 0);
		if ($s != strlen($in)) {
			// echo "Query $oid: Sending failed\n";
			$this->spp_reader_init();
			if (!$this->socket) return null;
			return $this->spp_reader_avail($oid, $failcount+1);
		}

		$out = @socket_read($this->socket, 2048);
		if (trim($out) == '1') {
			return true;
		} else if (trim($out) == '0') {
			return false;
		} else {
			// echo "Query $oid: Receiving failed\n";
			$this->spp_reader_init();
			if (!$this->socket) return null;
			return $this->spp_reader_avail($oid, $failcount+1);
		}
	}

	protected function spp_reader_uninit() {
		if (!$this->connected) return;
		@socket_close($this->socket);
		$this->connected = false;
	}

	public function queryOID($oid) {
		if (trim($oid) === 'bye') return null;
		return $this->spp_reader_avail($oid);
	}

	public function disconnect() {
		return $this->spp_reader_uninit();
	}

	public function connect() {
		return $this->spp_reader_init();
	}

	function __construct($addr='localhost:49500') {
		$this->addr = $addr;
	}

}
