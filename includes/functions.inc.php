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

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusException;

/**
 * @param string $privKey
 * @return bool
 */
function is_privatekey_encrypted(string $privKey): bool {
	return strpos($privKey,'BEGIN ENCRYPTED PRIVATE KEY') !== false;
}

/**
 * @param string $privKey
 * @param string $pubKey
 * @return bool
 */
function verify_private_public_key(string $privKey, string $pubKey): bool {
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

/**
 * @param string $privKeyOld
 * @param string|null $passphrase_old
 * @param string|null $passphrase_new
 * @return false|string
 */
function change_private_key_passphrase(string $privKeyOld, ?string $passphrase_old=null, ?string $passphrase_new=null) {
	$pkey_config = array(
	    //"digest_alg" => "sha512",
	    //"private_key_bits" => 2048,
	    //"private_key_type" => OPENSSL_KEYTYPE_RSA,
	    "config" => class_exists(OIDplus::class) ? OIDplus::getOpenSslCnf() : @getenv('OPENSSL_CONF')
	);
	$privKeyNew = @openssl_pkey_get_private($privKeyOld, $passphrase_old);
	if ($privKeyNew === false) return false;
	if (!@openssl_pkey_export($privKeyNew, $privKeyNewExport, $passphrase_new, $pkey_config)) return false;
	if ($privKeyNewExport === "") return false;
	return "$privKeyNewExport";
}

/**
 * @param string $privKey
 * @param string $passphrase
 * @return false|string
 */
function decrypt_private_key(string $privKey, string $passphrase) {
	return change_private_key_passphrase($privKey, $passphrase, null);
}

/**
 * @param string $privKey
 * @param string $passphrase
 * @return false|string
 */
function encrypt_private_key(string $privKey, string $passphrase) {
	return change_private_key_passphrase($privKey, null, $passphrase);
}

/**
 * @param string $data
 * @return int
 */
function smallhash(string $data): int { // get 31 bits from SHA1. Values 0..2147483647
	return (hexdec(substr(sha1($data),-4*2)) & 0x7FFFFFFF);
}

/**
 * @param string $name
 * @return array
 */
function split_firstname_lastname(string $name): array {
	$ary = explode(' ', $name);
	$last_name = array_pop($ary);
	$first_name = implode(' ', $ary);
	return array($first_name, $last_name);
}

/**
 * @return void
 */
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
	/**
	 * @param string $str
	 * @param int $width
	 * @param string $break
	 * @param bool $cut
	 * @return string
	 */
	function mb_wordwrap(string $str, int $width = 75, string $break = "\n", bool $cut = false): string {
		// https://stackoverflow.com/a/4988494/488539
		assert(strlen($break) > 0);
		$lines = explode("$break", $str);
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

/**
 * @param string $out
 * @param string $contentType
 * @param string $filename
 * @return void
 */
function httpOutWithETag(string $out, string $contentType, string $filename='') {
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

/**
 * @param string $str
 * @param array $args
 * @return string
 */
function my_vsprintf(string $str, array $args): string {
	$n = 1;
	foreach ($args as $val) {
		$str = str_replace("%$n", $val, $str);
		$n++;
	}
	return str_replace("%%", "%", $str);
}

/**
 * @param string $str
 * @param mixed ...$sprintfArgs
 * @return string
 * @throws \ViaThinkSoft\OIDplus\Core\OIDplusConfigInitializationException
 * @throws \ViaThinkSoft\OIDplus\Core\OIDplusException
 */
function _L(string $str, ...$sprintfArgs): string {
	static $translation_array = array();
	static $translation_loaded = null;

	$str = trim($str);

	if (!class_exists(OIDplus::class)) {
		return my_vsprintf($str, $sprintfArgs);
	}

	$lang = OIDplus::getCurrentLang();
	$ta = OIDplus::getTranslationArray($lang);
	$res = $ta[$lang][$str] ?? $str;

	$res = str_replace('###', OIDplus::baseConfig()->getValue('TABLENAME_PREFIX', ''), $res);

	return my_vsprintf($res, $sprintfArgs);
}

/**
 * @param array $params
 * @param string $key
 * @return void
 * @throws \ViaThinkSoft\OIDplus\Core\OIDplusConfigInitializationException
 * @throws \ViaThinkSoft\OIDplus\Core\OIDplusException
 */
function _CheckParamExists(array $params, string $key) {
	if (class_exists(OIDplusException::class)) {
		if (!isset($params[$key])) throw new OIDplusException(_L('Parameter %1 is missing', $key));
	} else {
		if (!isset($params[$key])) throw new Exception(_L('Parameter %1 is missing', $key));
	}
}

/**
 * @param string $cont
 * @return array
 */
function extractHtmlContents(string $cont): array {
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

/**
 * @param string $password
 * @param bool $raw_output
 * @return string
 * @throws Exception
 */
function sha3_512(string $password, bool $raw_output=false): string {
	return hash('sha3-512', $password, $raw_output);
}

/**
 * @param string $message
 * @param string $key
 * @param bool $raw_output
 * @return string
 */
function sha3_512_hmac(string $message, string $key, bool $raw_output=false): string {
	// RFC 2104 HMAC
	return hash_hmac('sha3-512', $message, $key, $raw_output);
}

/**
 * @param string $password
 * @param string $salt
 * @param int $iterations
 * @param int $length
 * @param bool $binary
 * @return string
 */
function sha3_512_pbkdf2(string $password, string $salt, int $iterations, int $length=0, bool $binary=false): string {
	return hash_pbkdf2('sha3-512', $password, $salt, $iterations, $length, $binary);
}

/**
 * @param bool $require_ssl
 * @param string|null $reason
 * @return bool
 * @throws OIDplusException
 * @throws \ViaThinkSoft\OIDplus\Core\OIDplusConfigInitializationException
 */
function url_post_contents_available(bool $require_ssl=true, ?string &$reason=null): bool {
	if (class_exists(OIDplus::class)) {
		if (OIDplus::baseConfig()->getValue('OFFLINE_MODE', false)) {
			$reason = _L('OIDplus is running in offline mode due to the base configuration setting %1.', 'OFFLINE_MODE');
			return false;
		}
	}

	if (function_exists('curl_init')) {
		return true;
	} else {
		$reason = _L('Please install the PHP extension %1, so that OIDplus can connect to the Internet.', '<code>php_curl</code>');
		return false;
	}
}

/**
 * @param string $url
 * @param array $params
 * @param array $extraHeaders
 * @param string $userAgent
 * @return string|false
 * @throws \ViaThinkSoft\OIDplus\Core\OIDplusException
 */
function url_post_contents(string $url, array $params=array(), array $extraHeaders=array(), string $userAgent='ViaThinkSoft-OIDplus/2.0') {
	$require_ssl = str_starts_with(strtolower($url),'https:');
	if (!url_post_contents_available($require_ssl, $reason)) {
		throw new OIDplusException(_L('This feature is not available, because OIDplus cannot connect to the Internet.').' '.$reason);
	}

	$postFields = http_build_query($params);

	$headers = array(
		"User-Agent: $userAgent",
		"Content-Length: ".strlen($postFields)
	);

	foreach ($extraHeaders as $name => $val) {
		$headers[] = "$name: $val";
	}

	if (function_exists('curl_init')) {
		$ch = curl_init();
		if (class_exists(OIDplus::class)) {
			if (ini_get('curl.cainfo') == '') curl_setopt($ch, CURLOPT_CAINFO, OIDplus::localpath() . 'vendor/cacert.pem');
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		$res = @curl_exec($ch);
		$error_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
		@curl_close($ch);
		if ($error_code >= 400) return false;
		if ($res === false) return false;
	} else {
		$res = false;
		assert(false);
	}

	return $res;
}

/**
 * @param bool $require_ssl
 * @param string|null $reason
 * @return bool
 * @throws OIDplusException
 * @throws \ViaThinkSoft\OIDplus\Core\OIDplusConfigInitializationException
 */
function url_get_contents_available(bool $require_ssl=true, ?string &$reason=null): bool {
	if (class_exists(OIDplus::class)) {
		if (OIDplus::baseConfig()->getValue('OFFLINE_MODE', false)) {
			$reason = _L('OIDplus is running in offline mode due to the base configuration setting %1.', 'OFFLINE_MODE');
			return false;
		}
	}

	if (function_exists('curl_init')) {
		// Via cURL
		return true;
	} else {
		// Via file_get_contents()
		if (!ini_get('allow_url_fopen')) {
			$reason = _L('Please install the PHP extension %1 and/or enable %2 in your PHP configuration, so that OIDplus can connect to the Internet.', '<code>php_curl</code>', '<code>allow_url_fopen</code>');
			return false;
		}
		// Use extension_loaded() instead of function_exists(), because our supplement does not help...
		if ($require_ssl && !extension_loaded('openssl')) {
			$reason = _L('Please install the PHP extension %1 and/or %2, so that OIDplus can connect to the Internet.', '<code>php_curl</code>', '<code>php_openssl</code>');
			return false;
		}
		return true;
	}
}

/**
 * @param string $url
 * @param array $extraHeaders
 * @param string $userAgent
 * @return string|false
 */
function url_get_contents(string $url, array $extraHeaders=array(), string $userAgent='ViaThinkSoft-OIDplus/2.0') {
	$require_ssl = str_starts_with(strtolower($url),'https:');
	if (!url_get_contents_available($require_ssl, $reason)) {
		throw new OIDplusException(_L('This feature is not available, because OIDplus cannot connect to the Internet.').' '.$reason);
	}

	$headers = array("User-Agent: $userAgent");
	foreach ($extraHeaders as $name => $val) {
		$headers[] = "$name: $val";
	}
	if (function_exists('curl_init')) {
		$ch = curl_init();
		if (class_exists(OIDplus::class)) {
			if (ini_get('curl.cainfo') == '') curl_setopt($ch, CURLOPT_CAINFO, OIDplus::localpath() . 'vendor/cacert.pem');
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POST, false);
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
				"header" => implode("\r\n",$headers)."\r\n"
			]
		];
		$context = stream_context_create($opts);
		$res = @file_get_contents($url, false, $context);
		if ($res === false) return false;
	}
	return $res;
}

/**
* @param array &$rows
* @param string $fieldName
* @return void
*/
function natsort_field(array &$rows, string $fieldName) {
	usort($rows, function($a,$b) use($fieldName) {
		if ($a[$fieldName] == $b[$fieldName]) return 0; // equal
		$ary = array(
			-1 => $a[$fieldName],
			1 => $b[$fieldName]
		);
		natsort($ary);
		$keys = array_keys($ary);
		return $keys[0];
	});
}

/**
 * @param array $ary
 * @return \stdClass
 */
function array_to_stdobj(array $ary): \stdClass {
	$obj = new \stdClass;
	foreach ($ary as $name => $val) {
		$obj->$name = $val;
	}
	return $obj;
}

/**
 * @param \stdClass $obj
 * @return array
 */
function stdobj_to_array(\stdClass $obj): array {
	$ary = array();
	foreach ($obj as $name => $val) { /* @phpstan-ignore-line */
		$ary[$name] = $val;
	}
	return $ary;
}

/**
 * @return string|false
 */
function get_own_username() {
	$current_user = exec('whoami');
	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
		try {
			if (function_exists('mb_convert_encoding')) {
				$current_user = @mb_convert_encoding($current_user, "UTF-8", "cp850");
			} else if (function_exists('iconv')) {
				$current_user = @iconv("cp850", "UTF-8", $current_user);
			}
		} catch (\Exception $e) {}
		if (function_exists('mb_strtoupper')) {
			$current_user = mb_strtoupper($current_user); // just cosmetics
		}
	}
	if (!$current_user) {
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			// Windows on an IIS server:
			//     getenv('USERNAME')     MARSCHALL$                (That is the "machine account", see https://docs.microsoft.com/en-us/iis/manage/configuring-security/application-pool-identities#accessing-the-network )
			//     get_current_user()     DefaultAppPool
			//     exec('whoami')         iis apppool\defaultapppool
			// Windows with XAMPP:
			//     getenv('USERNAME')     dmarschall
			//     get_current_user()     dmarschall               (even if script has a different NTFS owner!)
			//     exec('whoami')         hickelsoft\dmarschall
			$current_user = get_current_user();
			if (!$current_user) {
				$current_user = getenv('USERNAME');
				$current_user = mb_strtoupper($current_user); // just cosmetics
			}
		} else {
			// On Linux:
			$current_user = exec('id -un');
			if (!$current_user) {
				// PHP'S get_current_user() will get the owner of the PHP script, not the process owner!
				// We want the process owner, so we use posix_geteuid() preferably.
				if (function_exists('posix_geteuid')) {
					$uid = posix_geteuid();
				} else {
					$temp_file = tempnam(sys_get_temp_dir(), 'TMP');
					if ($temp_file !== false) {
						$uid = fileowner($temp_file);
						if ($uid === false) $uid = -1;
						@unlink($temp_file);
					} else {
						$uid = -1;
					}
				}
				if ($uid >= 0) {
					$current_user = '#' . $uid;
					if (function_exists('posix_getpwuid')) {
						$userinfo = posix_getpwuid($uid); // receive username from the UID (requires read access to /etc/passwd)
						if ($userinfo !== false) $current_user = $userinfo['name'];
					}
				} else {
					$current_user = get_current_user();
				}
			}
		}
	}
	return $current_user ?: false;
}

/**
 * @param string $path
 * @return bool
 */
function isFileOrPathWritable(string $path): bool {
	if ($writable_file = (file_exists($path) && is_writable($path))) return true;
	if ($writable_directory = (!file_exists($path) && is_writable(dirname($path)))) return true;
	return false;
}

/**
 * @param string $html
 * @return string
 */
function html_to_text(string $html): string {
	$html = str_replace("\n", "", $html);
	$html = str_ireplace('<br', "\n<br", $html);
	$html = str_ireplace('<p', "\n\n<p", $html);
	$html = strip_tags($html);
	$html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
	return $html;
}

/**
 * Get HTTP request header, e.g. 'Authorization'
 * @param string $name e.g. 'Authorization'
 * @return string|null
 * @see https://stackoverflow.com/questions/40582161/how-to-properly-use-bearer-tokens
 **/
function getHttpRequestHeader(string $name) {
    $headers = null;
    if (isset($_SERVER[$name])) {
        $headers = trim($_SERVER[$name]);
    }
    else if (isset($_SERVER['HTTP_'.str_replace('-','_',strtoupper($name))])) { //Nginx or fast CGI
        $headers = trim($_SERVER["HTTP_".str_replace('-','_',strtoupper($name))]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        //print_r($requestHeaders);
        if (isset($requestHeaders[$name])) {
            $headers = trim($requestHeaders[$name]);
        }
    }
    return $headers;
}

/**
 * get access token from header
 * @see https://stackoverflow.com/questions/40582161/how-to-properly-use-bearer-tokens
 **/
function getBearerToken() {
    $headers = getHttpRequestHeader('Authorization');
    // HEADER: Get the access token from the header
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

/**
 * @param mixed $mixed
 * @return bool
 */
function oidplus_is_true($mixed): bool {
	if (is_null($mixed)) {
		return false;
	} else if (is_string($mixed)) {
		return (strtolower($mixed) == 'true') || ($mixed == '1') || (strtolower($mixed) == 'y') || (strtolower($mixed) == 't') || (strtolower($mixed) == 'on');
	} else if (is_bool($mixed)) {
		return $mixed;
	} else if (is_numeric($mixed)) {
		return $mixed != 0;
	} else {
		return (bool)$mixed; // let PHP decide...
	}
}

/**
 * @param string $data
 * @param string $key
 * @return string
 * @throws \Exception
 */
function encrypt_str(string $data, string $key): string {
	if (!function_exists('openssl_encrypt')) {
		throw new OIDplusException(_L('Decryption failed (OpenSSL not installed)'));
	}

	$iv = random_bytes(16); // AES block size in CBC mode

	// In 2023, OWASP recommended to use 600,000 iterations for PBKDF2-HMAC-SHA256 and 210,000 for PBKDF2-HMAC-SHA512.
	$version = 'V2023A';

	// Encryption
	$ciphertext = openssl_encrypt(
		$data,
		'AES-256-CBC',
		hash_pbkdf2('sha512', $key, '', 210000, 32/*256bit*/, true),
		OPENSSL_RAW_DATA,
		$iv
	);

	// Authentication
	$hmac = sha3_512_hmac($iv . $ciphertext, $key, true);

	return $version . $hmac . $iv . $ciphertext;
}

/**
 * @param string $data
 * @param string $key
 * @return string
 * @throws OIDplusException
 */
function decrypt_str(string $data, string $key): string {
	if (!function_exists('openssl_decrypt')) {
		throw new OIDplusException(_L('Decryption failed (OpenSSL not installed)'));
	}

	$version    = mb_substr($data, 0, 6, '8bit');
	$hmac       = mb_substr($data, 6, 64, '8bit');
	$iv         = mb_substr($data, 70, 16, '8bit');
	$ciphertext = mb_substr($data, 86, null, '8bit');

	if ($version === 'V2023A') {
		// Authentication
		$hmacNew = sha3_512_hmac($iv . $ciphertext, $key, true);
		if (!hash_equals($hmac, $hmacNew)) {
			throw new OIDplusException(_L('Decryption failed (wrong password)'));
		}

		// Decryption
		$cleartext = openssl_decrypt(
			$ciphertext,
			'AES-256-CBC',
			hash_pbkdf2('sha512', $key, '', 210000, 32/*256bit*/, true),
			OPENSSL_RAW_DATA,
			$iv
		);
	} else {
		throw new OIDplusException(_L('Decryption failed (Unexpected encryption version)'));
	}

	if ($cleartext === false) {
		throw new OIDplusException(_L('Decryption failed (Internal error)'));
	}
	return $cleartext;
}

/**
 * Finds a substring that is guaranteed not in $str
 * @param string $str
 * @return string
 */
function find_nonexisting_substr(string $str): string {
	$i = 0;
	do {
		$i++;
		$dummy = "[$i]";
	} while (strpos($str, $dummy) !== false);
	return $dummy;
}

/**
 * Works like explode(), but it respects if $separator is preceded by an backslash escape character
 * @param string $separator
 * @param string $string
 * @param int $limit
 * @return array
 */
function explode_with_escaping(string $separator, string $string, int $limit=PHP_INT_MAX): array {
	$dummy1 = find_nonexisting_substr($string);
	$dummy2 = find_nonexisting_substr($string.$dummy1);

	$string = str_replace('\\\\', $dummy2, $string);
	$string = str_replace('\\'.$separator, $dummy1, $string);

	$ary = explode($separator, $string, $limit);

	foreach ($ary as &$a) {
		$a = str_replace($dummy2, '\\\\', $a);
		$a = str_replace($dummy1, '\\'.$separator, $a);
	}
	unset($a);

	return $ary;
}
