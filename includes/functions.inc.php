<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2021 Daniel Marschall, ViaThinkSoft
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

function generateRandomString($length) {
	// Note: This function can be used in temporary file names, so you
	// may not generate illegal file name characters.
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}

function verify_private_public_key($privKey, $pubKey) {
	try {
		if (empty($privKey)) return false;
		if (empty($pubKey)) return false;
		$data = generateRandomString(25);
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
	static $translation_array = array();
	static $translation_loaded = null;

	$str = trim($str);

	if (!class_exists('OIDplus')) {
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
	if (!isset($params[$key])) throw new OIDplusException(_L('Parameter %1 is missing', $key));
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

function sha3_512($password, $raw_output=false) {
	if (version_compare(PHP_VERSION, '7.1.0') >= 0) {
		return hash('sha3-512', $password, $raw_output);
	} else {
		return bb\Sha3\Sha3::hash($password, 512, $raw_output);
	}
}

function get_svn_revision($dir='') {
	if (!empty($dir)) $dir .= '/';

	// Try to get the version via SQLite3
	if (class_exists('SQLite3')) {
		try {
			$db = new SQLite3($dir.'.svn/wc.db');
			$results = $db->query('SELECT MIN(revision) AS rev FROM NODES_BASE');
			while ($row = $results->fetchArray()) {
				return ($cachedVersion = $row['rev']);
			}
			$db->close();
			$db = null;
		} catch (Exception $e) {
		}
	}
	if (class_exists('PDO')) {
		try {
			$pdo = new PDO('sqlite:'.$dir.'.svn/wc.db');
			$res = $pdo->query('SELECT MIN(revision) AS rev FROM NODES_BASE');
			$row = $res->fetch();
			if ($row !== false) {
				return ($cachedVersion = $row['rev']);
			}
			$pdo = null;
		} catch (Exception $e) {
		}
	}

	// Try to find out the SVN version using the shell
	// We don't prioritize this method, because a failed shell access will flood the apache error log with STDERR messages
	$output = @shell_exec('svnversion '.escapeshellarg($dir).' 2>&1');
	$match = array();
	if (preg_match('/\d+/', $output, $match)) {
		return ($cachedVersion = $match[0]);
	}

	$output = @shell_exec('svn info '.escapeshellarg($dir).' 2>&1');
	if (preg_match('/Revision:\s*(\d+)/m', $output, $match)) { // do not translate
		return ($cachedVersion = $match[1]);
	}

	return false;
}

function find_git_folder() {
	// Git command line saves git information in folder ".git"
	// Plesk git saves git information in folder "../../../git/oidplus/" (or similar)
	$dir = realpath(__DIR__);
	if (is_dir($dir.'/.git')) return $dir.'/.git';
	$i = 0;
	do {
		if (is_dir($dir.'/git')) {
			$confs = glob($dir.'/git/'.'*'.'/config');
			foreach ($confs as $conf) {
				$cont = file_get_contents($conf);
				if (strpos($cont, '://github.com/danielmarschall/oidplus') !== false) {
					return dirname($conf);
				}
			}
		}
		$i++;
	} while (($i<100) && ($dir != ($new_dir = realpath($dir.'/../'))) && ($dir = $new_dir));
	return false;
}

function get_gitsvn_revision($dir='') {
	try {
		// requires danielmarschall/git_utils.inc.php
		$git_dir = find_git_folder();
		if ($git_dir === false) return false;
		$commit_msg = git_get_latest_commit_message($git_dir);
	} catch (Exception $e) {
		// Try command-line
		$ec = -1;
		$out = array();
		if (!empty($dir)) {
			@exec('cd '.escapeshellarg($dir).' && git log', $out, $ec);
		} else {
			@exec('git log', $out, $ec);
		}
		if ($ec == 0) {
			$commit_msg = implode("\n", $out);
		} else {
			return false;
		}
	}

	$m = array();
	if (preg_match('%git-svn-id: (.+)@(\\d+) %ismU', $commit_msg, $m)) {
		return $m[2];
	} else {
		return false;
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

function rec_is_dir($dir) {
	$dirs = glob($dir);
	foreach ($dirs as $dir) {
		if (is_dir($dir)) return true;
	}
	return false;
}

function isInternetExplorer() {
	// see also includes/oidplus_base.js
	$ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
	return ((strpos($ua,'MSIE ') !== false) || (strpos($ua,'Trident/') !== false));
}

function url_get_contents($url) {
	if (function_exists('curl_init')) {
		$ch = curl_init();
		if (ini_get('curl.cainfo') == '') curl_setopt($ch, CURLOPT_CAINFO, OIDplus::localpath() . 'vendor/cacert.pem');
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		if (!($res = @curl_exec($ch))) return false;
		curl_close($ch);
	} else {
		$res = @file_get_contents($url);
		if ($res === false) return false;
	}
	return $res;
}
