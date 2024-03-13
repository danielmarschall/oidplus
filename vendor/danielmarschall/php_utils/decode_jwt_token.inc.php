<?php

/*
 * JWT Decoder for PHP
 * Copyright 2021 Daniel Marschall, ViaThinkSoft
 * Version 2021-05-15
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

function decode_idtoken($id_token, $verification_certs=null, $allowed_algorithms = array()) {
	// Parts taken and simplified from https://github.com/firebase/php-jwt , licensed by BSD-3-clause
	// Here is a great page for encode and decode tokens for testing: https://jwt.io/

	$parts = explode('.', $id_token);
	if (count($parts) === 5) return false; // encrypted JWT not yet supported
	if (count($parts) !== 3) return false;
	list($header_base64, $payload_base64, $signature_base64) = $parts;

	$header_ary = json_decode(urlsafeB64Decode($header_base64),true);
	if ($header_ary['typ'] !== 'JWT') return false;

	if ($verification_certs) {
		$key = isset($header_ary['kid']) ? $verification_certs[$header_ary['kid']] : $verification_certs;

		$msg = $header_base64.'.'.$payload_base64;
		$signature = urlsafeB64Decode($signature_base64);

		$jwt_algo = $header_ary['alg'];

		// see https://auth0.com/blog/critical-vulnerabilities-in-json-web-token-libraries/
		//     https://datatracker.ietf.org/doc/html/rfc8725#section-3.1
		if (!in_array($jwt_algo, $allowed_algorithms)) return false;

		if ($jwt_algo != 'none') {
			$php_algo = 'SHA'.substr($jwt_algo,2,3);
			switch (substr($jwt_algo,0,2)) {
				case 'ES':
					// OpenSSL expects an ASN.1 DER sequence for ES256 signatures
					$signature = signatureToDER($signature);
					if (!function_exists('openssl_verify')) break; // if OpenSSL is not installed, we just accept the JWT
					if (!openssl_verify($msg, $signature, $key, $php_algo)) return false;
					break;
				case 'RS':
					if (!function_exists('openssl_verify')) break; // if OpenSSL is not installed, we just accept the JWT
					if (!openssl_verify($msg, $signature, $key, $php_algo)) return false;
					break;
				case 'HS':
					$hash = @hash_hmac($php_algo, $msg, $key, true);
					if (!$hash) break; // if the hash algo is not available, we just accept the JWT
					if (!hash_equals($hash, $signature)) return false;
					break;
				case 'PS':
					// This feature is new and not yet available in php-jwt
					file_put_contents($msg_file = tempnam("/tmp", ""), $msg);
					file_put_contents($sig_file = tempnam("/tmp", ""), $signature);
					file_put_contents($key_file = tempnam("/tmp", ""), $key);
					$ec = -1;
					$out = array();
					$cmd = "openssl dgst -".strtolower($php_algo)." -sigopt rsa_padding_mode:pss -sigopt rsa_pss_saltlen:-1 -verify ".escapeshellarg($key_file)." -signature ".escapeshellarg($sig_file)." ".escapeshellarg($msg_file);
					$cmd .= (strtoupper(substr(PHP_OS,0,3)) === 'WIN') ? ' 2> NUL' : ' 2> /dev/null';
					exec($cmd, $out, $ec);
					unlink($msg_file);
					unlink($sig_file);
					unlink($key_file);
					if (($ec !== 0) && (count($out) === 0)) break; // If OpenSSL is not found, we just accept the JWT
					if (($ec !== 0) || (strpos(implode("\n",$out),"Verified OK") === false)) return false;
					break;
				default:
					return false;
			}
		}
	}

	$payload_ary = json_decode(urlsafeB64Decode($payload_base64), true);

	$leeway = 60; // 1 Minute
	if (isset($payload_ary['nbf']) && (time()+$leeway<$payload_ary['nbf'])) return false;
	if (isset($payload_ary['exp']) && (time()-$leeway>$payload_ary['exp'])) return false;

	return $payload_ary;
}

function urlsafeB64Decode($input) {
	// Taken from https://github.com/firebase/php-jwt , licensed by BSD-3-clause
	$remainder = strlen($input) % 4;
	if ($remainder) {
		$padlen = 4 - $remainder;
		$input .= str_repeat('=', $padlen);
	}
	return base64_decode(strtr($input, '-_', '+/'));
}

function signatureToDER($sig) {
	// Taken from https://github.com/firebase/php-jwt , licensed by BSD-3-clause, modified

	// Separate the signature into r-value and s-value
	list($r, $s) = str_split($sig, (int) (strlen($sig) / 2));

	// Trim leading zeros
	$r = ltrim($r, "\x00");
	$s = ltrim($s, "\x00");

	// Convert r-value and s-value from unsigned big-endian integers to signed two's complement
	if (ord($r[0]) > 0x7f) $r = "\x00" . $r;
	if (ord($s[0]) > 0x7f) $s = "\x00" . $s;

	$der_r = chr(0x00/*primitive*/ | 0x02/*INTEGER*/).chr(strlen($r)).$r;
	$der_s = chr(0x00/*primitive*/ | 0x02/*INTEGER*/).chr(strlen($s)).$s;
	$der = chr(0x20/*constructed*/ | 0x10/*SEQUENCE*/).chr(strlen($der_r.$der_s)).$der_r.$der_s;
	return $der;
}
