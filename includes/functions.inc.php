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

function insertWhitespace($str, $index) {
	return substr($str, 0, $index) . ' ' . substr($str, $index);
}

function js_escape($data) {
	// TODO.... json_encode??
	$data = str_replace('\\', '\\\\', $data);
	$data = str_replace('\'', '\\\'', $data);
	return "'" . $data . "'";
}

function trim_br($html) {
	$count = 0;
	do { $html = preg_replace('@^\s*<\s*br\s*/{0,1}\s*>@isU', '', $html, -1, $count); } while ($count > 0); // left trim
	do { $html = preg_replace('@<\s*br\s*/{0,1}\s*>\s*$@isU', '', $html, -1, $count); } while ($count > 0); // right trim
	return $html;
}

function verify_private_public_key($privKey, $pubKey) {
	try {
		if (empty($privKey)) return false;
		if (empty($pubKey)) return false;
		$data = 'TEST';
		$encrypted = '';
		$decrypted = '';
		if (!@openssl_public_encrypt($data, $encrypted, $pubKey)) return false;
		if (!@openssl_private_decrypt($encrypted, $decrypted, $privKey)) return false;
		return $decrypted == $data;
	} catch (Exception $e) {
		return false;
	}
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

function get_calling_function() {
	$ex = new Exception();
	$trace = $ex->getTrace();
	if (!isset($trace[2])) return _L('(main)');
	$final_call = $trace[2];
	return $final_call['file'].':'.$final_call['line'].'/'.$final_call['function'].'()';
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
		header("HTTP/1.1 304 Not Modified");
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
	$lang = OIDplus::getCurrentLang();

	static $translation_array = array();
	static $translation_loaded = null;
	if ($lang != $translation_loaded) {
		$good = true;
		if (strpos($lang,'/') !== false) $good = false; // prevent attack (but actually, the sanitization in getCurrentLang should work)
		if (strpos($lang,'\\') !== false) $good = false; // prevent attack (but actually, the sanitization in getCurrentLang should work)
		if (strpos($lang,'..') !== false) $good = false; // prevent attack (but actually, the sanitization in getCurrentLang should work)
		$translation_file = __DIR__.'/../plugins/language/'.$lang.'/messages.xml';
		if ($good && !file_exists($translation_file)) $good = false;
		if ($good) {
			$xml = simplexml_load_string(file_get_contents($translation_file));
			foreach ($xml->message as $msg) {
				$src = trim($msg->source->__toString());
				$dst = trim($msg->target->__toString());
				$translation_array[$src] = $dst;
			}
			$translation_loaded = $lang;
		}
	}

	if ($lang != $translation_loaded) {
		// Something bad happened (e.g. attack or message file not found)
		$res = $str;
	} else {
		$res = isset($translation_array[$str]) && !empty($translation_array[$str]) ? $translation_array[$str] : $str;
	}

	$res = str_replace('###', OIDplus::baseConfig()->getValue('TABLENAME_PREFIX', ''), $res);

	$res = my_vsprintf($res, $sprintfArgs);

	return $res;
}

function extractHtmlContents($cont) {
	// make sure the program works even if the user provided HTML is not UTF-8
	$cont = iconv(mb_detect_encoding($cont, mb_detect_order(), true), 'UTF-8//IGNORE', $cont);
	$bom = pack('H*','EFBBBF');
	$cont = preg_replace("/^$bom/", '', $cont);

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

function sha3_512($password) {
	if (version_compare(PHP_VERSION, '7.1.0') >= 0) {
		return bin2hex(hash('sha3-512', $password, true));
	} else {
		return bin2hex(bb\Sha3\Sha3::hash($password, 512, true));
	}
}
