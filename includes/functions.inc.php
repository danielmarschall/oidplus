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

use ViaThinkSoft\OIDplus\OIDplus;

function is_privatekey_encrypted($privKey) {
	return strpos($privKey,'BEGIN ENCRYPTED PRIVATE KEY') !== false;
}

function verify_private_public_key($privKey, $pubKey) {
	if (!function_exists('openssl_public_encrypt')) return false;
	try {
		if (empty($privKey)) return false;
		if (empty($pubKey)) return false;
		$data = generateRandomString(25);
		$encrypted = '';
		$decrypted = '';
		if (!@openssl_public_encrypt($data, $encrypted, $pubKey)) return false;
		if (!@openssl_private_decrypt($encrypted, $decrypted, $privKey)) return false;
		return $decrypted == $data;
	} catch (\Exception $e) {
		return false;
	}
}

function change_private_key_passphrase($privKeyOld, $passphrase_old, $passphrase_new) {
	$pkey_config = array(
	    //"digest_alg" => "sha512",
	    //"private_key_bits" => 2048,
	    //"private_key_type" => OPENSSL_KEYTYPE_RSA,
	    "config" => class_exists("\\ViaThinkSoft\\OIDplus\\OIDplus") ? OIDplus::getOpenSslCnf() : @getenv('OPENSSL_CONF')
	);
	$privKeyNew = @openssl_pkey_get_private($privKeyOld, $passphrase_old);
	if ($privKeyNew === false) return false;
	@openssl_pkey_export($privKeyNew, $privKeyNewExport, $passphrase_new, $pkey_config);
	if ($privKeyNewExport === false) return false;
	return $privKeyNewExport."";
}

function decrypt_private_key($privKey, $passphrase) {
	return change_private_key_passphrase($privKey, $passphrase, null);
}

function encrypt_private_key($privKey, $passphrase) {
	return change_private_key_passphrase($privKey, null, $passphrase);
}

function smallhash($data) { // get 31 bits from SHA1. Values 0..2147483647
	return (hexdec(substr(sha1($data),-4*2)) & 0x7FFFFFFF);
}

function split_firstname_lastname($name) {
	$ary = explode(' ', $name);
	$last_name = array_pop($ary);
	$first_name = implode(' ', $ary);
	return array($first_name, $last_name);
}

function originHeaders() {
	// CORS
	// Author: Till Wehowski
	// TODO: add to class OIDplus

	header("Access-Control-Allow-Credentials: true");
	header("Access-Control-Allow-Origin: ".strip_tags(((isset($_SERVER['HTTP_ORIGIN'])) ? $_SERVER['HTTP_ORIGIN'] : "*")));

	header("Access-Control-Allow-Headers: If-None-Match, X-Requested-With, Origin, X-Frdlweb-Bugs, Etag, X-Forgery-Protection-Token, X-CSRF-Token");

	if (isset($_SERVER['HTTP_ORIGIN'])) {
		header('X-Frame-Options: ALLOW-FROM '.$_SERVER['HTTP_ORIGIN']);
	} else {
		header_remove("X-Frame-Options");
	}

	$expose = array('Etag', 'X-CSRF-Token');
	foreach (headers_list() as $num => $header) {
		$h = explode(':', $header);
		$expose[] = trim($h[0]);
	}
	header("Access-Control-Expose-Headers: ".implode(',',$expose));

	header("Vary: Origin");
}

if (!function_exists('mb_wordwrap')) {
	function mb_wordwrap($str, $width = 75, $break = "\n", $cut = false) {
		// https://stackoverflow.com/a/4988494/488539
		$lines = explode($break, $str);
		foreach ($lines as &$line) {
			$line = rtrim($line);
			if (mb_strlen($line) <= $width) {
				continue;
			}
			$words = explode(' ', $line);
			$line = '';
			$actual = '';
			foreach ($words as $word) {
				if (mb_strlen($actual.$word) <= $width) {
					$actual .= $word.' ';
				} else {
					if ($actual != '') {
						$line .= rtrim($actual).$break;
					}
					$actual = $word;
					if ($cut) {
						while (mb_strlen($actual) > $width) {
							$line .= mb_substr($actual, 0, $width).$break;
							$actual = mb_substr($actual, $width);
						}
					}
					$actual .= ' ';
				}
			}
			$line .= trim($actual);
		}
		return implode($break, $lines);
	}
}

function httpOutWithETag($out, $contentType, $filename='') {
	$etag = md5($out);
	header("Etag: $etag");
	header("Content-MD5: $etag"); // RFC 2616 clause 14.15
	if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && (trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag)) {
		if (PHP_SAPI != 'cli') @http_response_code(304); // 304 Not Modified
	} else {
		header("Content-Type: $contentType");
		if (!empty($filename)) {
			header('Content-Disposition:inline; filename="'.$filename.'"');
		}
		echo $out;
	}
	die();
}

function my_vsprintf($str, $args) {
        $n = 1;
        foreach ($args as $val) {
                $str = str_replace("%$n", $val, $str);
                $n++;
        }
        $str = str_replace("%%", "%", $str);
        return $str;
}

function _L($str, ...$sprintfArgs) {
	static $translation_array = array();
	static $translation_loaded = null;

	$str = trim($str);

	if (!class_exists(OIDplus::class)) {
		return my_vsprintf($str, $sprintfArgs);
	}

	$lang = OIDplus::getCurrentLang();
	$ta = OIDplus::getTranslationArray($lang);
	$res = (isset($ta[$lang]) && isset($ta[$lang][$str])) ? $ta[$lang][$str] : $str;

	$res = str_replace('###', OIDplus::baseConfig()->getValue('TABLENAME_PREFIX', ''), $res);

	$res = my_vsprintf($res, $sprintfArgs);

	return $res;
}

function _CheckParamExists($params, $key) {
	if (class_exists(OIDplusException::class)) {
		if (!isset($params[$key])) throw new OIDplusException(_L('Parameter %1 is missing', $key));
	} else {
		if (!isset($params[$key])) throw new Exception(_L('Parameter %1 is missing', $key));
	}
}

function extractHtmlContents($cont) {
	// make sure the program works even if the user provided HTML is not UTF-8
	$cont = convert_to_utf8_no_bom($cont);

	$out_js = '';
	$m = array();
	preg_match_all('@<script[^>]*>(.+)</script>@ismU', $cont, $m);
	foreach ($m[1] as $x) {
		$out_js = $x . "\n\n";
	}

	$out_css = '';
	$m = array();
	preg_match_all('@<style[^>]*>(.+)</style>@ismU', $cont, $m);
	foreach ($m[1] as $x) {
		$out_css = $x . "\n\n";
	}

	$out_html = $cont;
	$out_html = preg_replace('@^(.+)<body[^>]*>@isU', '', $out_html);
	$out_html = preg_replace('@</body>.+$@isU', '', $out_html);
	$out_html = preg_replace('@<title>.+</title>@isU', '', $out_html);
	$out_html = preg_replace('@<h1>.+</h1>@isU', '', $out_html, 1);
	$out_html = preg_replace('@<script[^>]*>(.+)</script>@ismU', '', $out_html);
	$out_html = preg_replace('@<style[^>]*>(.+)</style>@ismU', '', $out_html);

	return array($out_html, $out_js, $out_css);
}

function sha3_512($password, $raw_output=false) {
	if (version_compare(PHP_VERSION, '7.1.0') >= 0) {
		return hash('sha3-512', $password, $raw_output);
	} else {
		return \bb\Sha3\Sha3::hash($password, 512, $raw_output);
	}
}

function sha3_512_hmac($message, $key, $raw_output=false) {
	// RFC 2104 HMAC
	if (version_compare(PHP_VERSION, '7.1.0') >= 0) {
		return hash_hmac('sha3-512', $message, $key, $raw_output);
	} else {
		return \bb\Sha3\Sha3::hash_hmac($message, $key, 512, $raw_output);
	}
}

if (!function_exists('str_ends_with')) {
	// PHP 7.x compatibility
	function str_ends_with($haystack, $needle) {
		$length = strlen($needle);
		return $length > 0 ? substr($haystack, -$length) === $needle : true;
	}
}

if (!function_exists('str_starts_with')) {
	// PHP 7.x compatibility
	function str_starts_with($haystack, $needle) {
		return strpos($haystack, $needle) === 0;
	}
}

function url_get_contents($url, $userAgent='ViaThinkSoft-OIDplus/2.0') {
	if (function_exists('curl_init')) {
		$ch = curl_init();
		if (class_exists(OIDplus::class)) {
			if (ini_get('curl.cainfo') == '') curl_setopt($ch, CURLOPT_CAINFO, OIDplus::localpath() . 'vendor/cacert.pem');
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
		curl_setopt($ch, CURLOPT_POST, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		$res = @curl_exec($ch);
		$error_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
		@curl_close($ch);
		if ($error_code >= 400) return false;
		if ($res === false) return false;
	} else {
		// Attention: HTTPS only works if OpenSSL extension is enabled.
		// Our supplement does not help...
		$opts = [
			"http" => [
				"method" => "GET",
				"header" => "User-Agent: $userAgent\r\n"
			]
		];
		$context = stream_context_create($opts);
		$res = @file_get_contents($url, false, $context);
		if ($res === false) return false;
	}
	return $res;
}

function getSortedQuery() {
	// https://stackoverflow.com/a/51777249/488539
	$url = [];
	parse_str($_SERVER['QUERY_STRING'], $url);
	ksort($url);
	return http_build_query($url);
}
