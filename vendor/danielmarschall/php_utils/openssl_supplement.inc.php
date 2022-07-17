<?php

/*
 * OpenSSL php functions implemented using phpseclib
 * Copyright 2022 Daniel Marschall, ViaThinkSoft
 * Version 2022-07-17
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

// How to use this supplement:
// 1. Include phpseclib using composer and include the autoloader
// 2. Then, include this file. The openssl functions are now available.

// ATTENTION: This supplement/polyfill does only implement a few openssl_*() functions,
// and only a few algorithms: AES and RSA! Feel free to extend this library!
// The sign/verify and encrypt/decrypt functions should be binary compatible with
// the actual openssl functions.

if (!function_exists('openssl_pkey_new') && class_exists('\\phpseclib3\\Crypt\\RSA')) {

	define('OPENSSL_SUPPLEMENT', 1);

	// ---------------------------------------------------------------------

	// https://www.php.net/manual/en/openssl.purpose-check.php
	if (!defined('X509_PURPOSE_SSL_CLIENT')) define('X509_PURPOSE_SSL_CLIENT', 1);
	if (!defined('X509_PURPOSE_SSL_SERVER')) define('X509_PURPOSE_SSL_SERVER', 2);
	if (!defined('X509_PURPOSE_NS_SSL_SERVER')) define('X509_PURPOSE_NS_SSL_SERVER', 3);
	if (!defined('X509_PURPOSE_SMIME_SIGN')) define('X509_PURPOSE_SMIME_SIGN', 4);
	if (!defined('X509_PURPOSE_SMIME_ENCRYPT')) define('X509_PURPOSE_SMIME_ENCRYPT', 5);
	if (!defined('X509_PURPOSE_CRL_SIGN')) define('X509_PURPOSE_CRL_SIGN', 6);
	if (!defined('X509_PURPOSE_ANY')) define('X509_PURPOSE_ANY', 7);

	// https://www.php.net/manual/en/openssl.padding.php
	if (!defined('OPENSSL_PKCS1_PADDING')) define('OPENSSL_PKCS1_PADDING', 1);
	if (!defined('OPENSSL_SSLV23_PADDING')) define('OPENSSL_SSLV23_PADDING', 2);
	if (!defined('OPENSSL_NO_PADDING')) define('OPENSSL_NO_PADDING', 3);
	if (!defined('OPENSSL_PKCS1_OAEP_PADDING')) define('OPENSSL_PKCS1_OAEP_PADDING', 4);

	// https://www.php.net/manual/en/openssl.key-types.php
	if (!defined('OPENSSL_KEYTYPE_RSA')) define('OPENSSL_KEYTYPE_RSA', 0);
	if (!defined('OPENSSL_KEYTYPE_DSA')) define('OPENSSL_KEYTYPE_DSA', 1);
	if (!defined('OPENSSL_KEYTYPE_DH')) define('OPENSSL_KEYTYPE_DH', 2);
	if (!defined('OPENSSL_KEYTYPE_EC')) define('OPENSSL_KEYTYPE_EC', 3);

	// https://www.php.net/manual/en/openssl.pkcs7.flags.php
	if (!defined('PKCS7_TEXT')) define('PKCS7_TEXT', 1);
	if (!defined('PKCS7_BINARY')) define('PKCS7_BINARY', 128);
	if (!defined('PKCS7_NOINTERN')) define('PKCS7_NOINTERN', 16);
	if (!defined('PKCS7_NOVERIFY')) define('PKCS7_NOVERIFY', 32);
	if (!defined('PKCS7_NOCHAIN')) define('PKCS7_NOCHAIN', 8);
	if (!defined('PKCS7_NOCERTS')) define('PKCS7_NOCERTS', 2);
	if (!defined('PKCS7_NOATTR')) define('PKCS7_NOATTR', 256);
	if (!defined('PKCS7_DETACHED')) define('PKCS7_DETACHED', 64);
	if (!defined('PKCS7_NOSIGS')) define('PKCS7_NOSIGS', 4);

	// https://www.php.net/manual/en/openssl.cms.flags.php
	if (!defined('OPENSSL_CMS_TEXT')) define('OPENSSL_CMS_TEXT', 1);
	if (!defined('OPENSSL_CMS_BINARY')) define('OPENSSL_CMS_BINARY', 128);
	if (!defined('OPENSSL_CMS_NOINTERN')) define('OPENSSL_CMS_NOINTERN', 16);
	if (!defined('OPENSSL_CMS_NOVERIFY')) define('OPENSSL_CMS_NOVERIFY', 32);
	if (!defined('OPENSSL_CMS_NOCERTS')) define('OPENSSL_CMS_NOCERTS', 2);
	if (!defined('OPENSSL_CMS_NOATTR')) define('OPENSSL_CMS_NOATTR', 256);
	if (!defined('OPENSSL_CMS_DETACHED')) define('OPENSSL_CMS_DETACHED', 64);
	if (!defined('OPENSSL_CMS_NOSIGS')) define('OPENSSL_CMS_NOSIGS', 12);

	// https://www.php.net/manual/en/openssl.signature-algos.php
	if (!defined('OPENSSL_ALGO_DSS1')) define('OPENSSL_ALGO_DSS1', 5); // Only defined when php/openssl compiled with MD2 support
	if (!defined('OPENSSL_ALGO_SHA1')) define('OPENSSL_ALGO_SHA1', 1);
	if (!defined('OPENSSL_ALGO_SHA224')) define('OPENSSL_ALGO_SHA224', 6);
	if (!defined('OPENSSL_ALGO_SHA256')) define('OPENSSL_ALGO_SHA256', 7);
	if (!defined('OPENSSL_ALGO_SHA384')) define('OPENSSL_ALGO_SHA384', 8);
	if (!defined('OPENSSL_ALGO_SHA512')) define('OPENSSL_ALGO_SHA512', 9);
	if (!defined('OPENSSL_ALGO_RMD160')) define('OPENSSL_ALGO_RMD160', 10);
	if (!defined('OPENSSL_ALGO_MD5')) define('OPENSSL_ALGO_MD5', 2);
	if (!defined('OPENSSL_ALGO_MD4')) define('OPENSSL_ALGO_MD4', 3);
	if (!defined('OPENSSL_ALGO_MD2')) define('OPENSSL_ALGO_MD2', 4); // Only defined when php/openssl compiled with MD2 support

	// https://www.php.net/manual/en/openssl.ciphers.php
	if (!defined('OPENSSL_CIPHER_RC2_40')) define('OPENSSL_CIPHER_RC2_40', 0);
	if (!defined('OPENSSL_CIPHER_RC2_128')) define('OPENSSL_CIPHER_RC2_128', 1);
	if (!defined('OPENSSL_CIPHER_RC2_64')) define('OPENSSL_CIPHER_RC2_64', 2);
	if (!defined('OPENSSL_CIPHER_DES')) define('OPENSSL_CIPHER_DES', 3);
	if (!defined('OPENSSL_CIPHER_3DES')) define('OPENSSL_CIPHER_3DES', 4);
	if (!defined('OPENSSL_CIPHER_AES_128_CBC')) define('OPENSSL_CIPHER_AES_128_CBC', 5);
	if (!defined('OPENSSL_CIPHER_AES_192_CBC')) define('OPENSSL_CIPHER_AES_192_CBC', 6);
	if (!defined('OPENSSL_CIPHER_AES_256_CBC')) define('OPENSSL_CIPHER_AES_256_CBC', 7);

	// https://www.php.net/manual/en/openssl.constversion.php
	// OPENSSL_VERSION_TEXT (string)
	// OPENSSL_VERSION_NUMBER (int)

	// https://www.php.net/manual/en/openssl.constsni.php
	// OPENSSL_TLSEXT_SERVER_NAME (string)

	// https://www.php.net/manual/en/openssl.constants.other.php
	if (!defined('OPENSSL_RAW_DATA')) define('OPENSSL_RAW_DATA', 1);
	if (!defined('OPENSSL_ZERO_PADDING')) define('OPENSSL_ZERO_PADDING', 2);
	if (!defined('OPENSSL_ENCODING_SMIME')) define('OPENSSL_ENCODING_SMIME', 1);
	if (!defined('OPENSSL_ENCODING_DER')) define('OPENSSL_ENCODING_DER', 0);
	if (!defined('OPENSSL_ENCODING_PEM')) define('OPENSSL_ENCODING_PEM', 2);

	// ---------------------------------------------------------------------

	$openssl_supplement_last_error = '';

	function openssl_pkey_new($pkey_config=null) {
		try {
			$algo = $pkey_config && isset($pkey_config["private_key_type"]) ? $pkey_config["private_key_type"] : OPENSSL_KEYTYPE_RSA;
			$bits = $pkey_config && isset($pkey_config["private_key_bits"]) ? $pkey_config["private_key_bits"] : 2048;

			// TODO: Also support $pkey_config['encrypt_key'] and $pkey_config['encrypt_key_cipher'] ?

			if ($algo == OPENSSL_KEYTYPE_RSA) {
				$private = \phpseclib3\Crypt\RSA::createKey($bits);
			} else {
				throw new Exception("Algo not implemented");
			}

			$private = $private->withPadding(\phpseclib3\Crypt\RSA::ENCRYPTION_PKCS1 | \phpseclib3\Crypt\RSA::SIGNATURE_PKCS1);

			$public = $private->getPublicKey()->withPadding(\phpseclib3\Crypt\RSA::ENCRYPTION_PKCS1 | \phpseclib3\Crypt\RSA::SIGNATURE_PKCS1);

			return array($algo, $bits, $private, $public);
		} catch (Exception $e) {
			global $openssl_supplement_last_error;
			$openssl_supplement_last_error = $e->getMessage();
			return false;
		}
	}

	function openssl_pkey_export($res, &$privKey, $passphrase = null, $options = null) {
		try {
			if ($res instanceof \phpseclib3\Crypt\Common\PrivateKey /*\phpseclib3\Crypt\RSA\PrivateKey*/ ) {
				$privKey = $res;
				if (!is_null($passphrase)) {
					$privKey = $res->withPassword($passphrase);
				}
				$privKey = $privKey."";
				return true;
			} else if (is_string($res)) {
				$privKey = $res;
				if (!is_null($passphrase)) {
					$privKey = \phpseclib3\Crypt\RSA::load($privKey);
					$privKey = $res->withPassword($passphrase);
					$privKey = $privKey."";
				}
				return true;
			} else if (is_array($res)) {
				$privKey = $res[2]."";
				if (!is_null($passphrase)) {
					$privKey = \phpseclib3\Crypt\RSA::load($privKey);
					$privKey = $res->withPassword($passphrase);
					$privKey = $privKey."";
				}
				return true;
			} else {
				throw new Exception("Invalid input datatype");
			}
		} catch (Exception $e) {
			global $openssl_supplement_last_error;
			$openssl_supplement_last_error = $e->getMessage();
			return false;
		}
	}

	function openssl_pkey_get_details($res) {
		return array(
			"bits" => $res[1],
			"key" => $res[3]."",
			"type" => $res[0]
		);
	}

	function openssl_public_encrypt($data, &$encrypted, $pubKey) {
		try {
			if (is_string($pubKey)) $pubKey = openssl_pkey_get_public($pubKey);
			if (!is_object($pubKey) || !method_exists($pubKey,'encrypt'))
				throw new Exception("Invalid input datatype");
			$encrypted = $pubKey->encrypt($data);
			return true;
		} catch (Exception $e) {
			global $openssl_supplement_last_error;
			$openssl_supplement_last_error = $e->getMessage();
			return false;
		}
	}

	function openssl_private_decrypt($encrypted, &$decrypted, $privKey) {
		try {
			if (is_string($privKey)) $privKey = openssl_pkey_get_private($privKey);
			if (!is_object($privKey) || !method_exists($privKey,'decrypt'))
				throw new Exception("Invalid input datatype");
			$decrypted = $privKey->decrypt($encrypted);
			return true;
		} catch (Exception $e) {
			global $openssl_supplement_last_error;
			$openssl_supplement_last_error = $e->getMessage();
			return false;
		}
	}

	function openssl_verify($msg, $signature, $public, $algorithm=OPENSSL_ALGO_SHA1) {
		try {
			if ($algorithm == OPENSSL_ALGO_SHA1) $algorithm = 'SHA1';
			if ($algorithm == OPENSSL_ALGO_SHA224) $algorithm = 'SHA224';
			if ($algorithm == OPENSSL_ALGO_SHA256) $algorithm = 'SHA256';
			if ($algorithm == OPENSSL_ALGO_SHA384) $algorithm = 'SHA384';
			if ($algorithm == OPENSSL_ALGO_SHA512) $algorithm = 'SHA512';
			if ($algorithm == OPENSSL_ALGO_RMD160) $algorithm = 'RMD160';
			if ($algorithm == OPENSSL_ALGO_MD5) $algorithm = 'MD5';
			if ($algorithm == OPENSSL_ALGO_MD4) $algorithm = 'MD4';
			if (is_string($public)) $public = openssl_pkey_get_public($public);
			if (!is_object($public) || !method_exists($public,'verify'))
				throw new Exception("Invalid input datatype");
			return $public->withHash($algorithm)->verify($msg, $signature) ? 1 : 0;
		} catch (Exception $e) {
			global $openssl_supplement_last_error;
			$openssl_supplement_last_error = $e->getMessage();
			return false;
		}
	}

	function openssl_sign($msg, &$signature, $private, $algorithm=OPENSSL_ALGO_SHA1) {
		try {
			if ($algorithm == OPENSSL_ALGO_SHA1) $algorithm = 'SHA1';
			if ($algorithm == OPENSSL_ALGO_SHA224) $algorithm = 'SHA224';
			if ($algorithm == OPENSSL_ALGO_SHA256) $algorithm = 'SHA256';
			if ($algorithm == OPENSSL_ALGO_SHA384) $algorithm = 'SHA384';
			if ($algorithm == OPENSSL_ALGO_SHA512) $algorithm = 'SHA512';
			if ($algorithm == OPENSSL_ALGO_RMD160) $algorithm = 'RMD160';
			if ($algorithm == OPENSSL_ALGO_MD5) $algorithm = 'MD5';
			if ($algorithm == OPENSSL_ALGO_MD4) $algorithm = 'MD4';
			if (is_string($private)) $private = openssl_pkey_get_private($private);
			if (!is_object($private) || !method_exists($private,'sign'))
				throw new Exception("Invalid input datatype");
			$signature = $private->withHash($algorithm)->sign($msg);
			return true;
		} catch (Exception $e) {
			global $openssl_supplement_last_error;
			$openssl_supplement_last_error = $e->getMessage();
			return false;
		}
	}

	function openssl_error_string() {
		global $openssl_supplement_last_error;
		$res = $openssl_supplement_last_error;
		$openssl_supplement_last_error = '';
		return $res;
	}

	function openssl_random_pseudo_bytes($len) {
		/*
		if (function_exists('openssl_random_pseudo_bytes')) {
			$a = openssl_random_pseudo_bytes($len);
			if ($a) return $a;
		}
		*/

		if (function_exists('mcrypt_create_iv')) {
			$a = bin2hex(mcrypt_create_iv($len, MCRYPT_DEV_URANDOM));
			if ($a) return $a;
		}

		if (function_exists('random_bytes')) {
			$a = random_bytes($len);
			if ($a) return $a;
		}

		// Fallback to non-secure RNG
		$a = '';
		while (strlen($a) < $len*2) {
			$a .= sha1(uniqid((string)mt_rand(), true));
		}
		$a = substr($a, 0, $len*2);
		return hex2bin($a);
	}

	function openssl_encrypt($data, $cipher_algo, $passphrase, $options=0, $iv="", &$tag=null, $aad="", $tag_length=16) {
		try {
			if (!is_null($tag)) throw new Exception("tag not implemented");
			if ($aad != "") throw new Exception("aad not implemented");
			if ($tag_length != 16) throw new Exception("tag_length not implemented");
			if (!preg_match('@AES\\-(.+)\\-(.+)@i', $cipher_algo, $m)) throw new Exception("Algo not implemented");
			if (($options & OPENSSL_ZERO_PADDING) != 0) throw new Exception("OPENSSL_ZERO_PADDING not implemented");
			$aes = new \phpseclib3\Crypt\AES($m[2]);
			$aes->setKeyLength($m[1]);
			$passphrase = substr($passphrase, 0, $m[1]/8);
			$passphrase = str_pad($passphrase, $m[1]/8, "\0", STR_PAD_RIGHT);
			$aes->setKey($passphrase);
			$aes->setIV($iv);
			$res = $aes->encrypt($data);
			if (($options & OPENSSL_RAW_DATA) == 0) $res = base64_encode($res);
			return $res;
		} catch (Exception $e) {
			global $openssl_supplement_last_error;
			$openssl_supplement_last_error = $e->getMessage();
			return false;
		}
	}

	function openssl_decrypt($data, $cipher_algo, $passphrase, $options=0, $iv="", $tag=null, $aad="") {
		try {
			if (!is_null($tag)) throw new Exception("tag not implemented");
			if ($aad != "") throw new Exception("aad not implemented");
			if (!preg_match('@AES\\-(.+)\\-(.+)@i', $cipher_algo, $m)) throw new Exception("Algo not implemented");
			if (($options & OPENSSL_ZERO_PADDING) != 0) throw new Exception("OPENSSL_ZERO_PADDING not implemented");
			$aes = new \phpseclib3\Crypt\AES($m[2]);
			$aes->setKeyLength($m[1]);
			$passphrase = substr($passphrase, 0, $m[1]/8);
			$passphrase = str_pad($passphrase, $m[1]/8, "\0", STR_PAD_RIGHT);
			$aes->setKey($passphrase);
			$aes->setIV($iv);
			if (($options & OPENSSL_RAW_DATA) == 0) $data = base64_decode($data);
			return $aes->decrypt($data);
		} catch (Exception $e) {
			global $openssl_supplement_last_error;
			$openssl_supplement_last_error = $e->getMessage();
			return false;
		}
	}

	function openssl_free_key($key) {
		// does nothing
	}

	function openssl_get_privatekey($key, $passphrase=null) {
		return openssl_pkey_get_private($key, $passphrase=null);
	}

	function openssl_pkey_get_private($key, $passphrase=null) {
		try {
			if (substr($key,0,7) === 'file://') {
				if (!file_exists($file = substr($key, 7))) throw new Exception("file not found");
				$key = file_get_contents($file);
			}
			if (is_null($passphrase)) $passphrase = false;
			$privKey = \phpseclib3\Crypt\RSA::load($key, $passphrase);
			return $privKey->withPassword(false)->withPadding(\phpseclib3\Crypt\RSA::ENCRYPTION_PKCS1 | \phpseclib3\Crypt\RSA::SIGNATURE_PKCS1); /** @phpstan-ignore-line */ // Call to an undefined method phpseclib3\Crypt\Common\AsymmetricKey::withPadding().
		} catch (Exception $e) {
			global $openssl_supplement_last_error;
			$openssl_supplement_last_error = $e->getMessage();
			return false;
		}
	}

	function openssl_get_publickey($public_key) {
		return openssl_pkey_get_public($public_key);
	}

	function openssl_pkey_get_public($public_key) {
		try {
			if (substr($public_key,0,7) === 'file://') {
				if (!file_exists($file = substr($public_key, 7))) throw new Exception("file not found");
				$public_key = file_get_contents($file);
			}
			$pubKey = \phpseclib3\Crypt\RSA::load($public_key);
			return $pubKey->withPadding(\phpseclib3\Crypt\RSA::ENCRYPTION_PKCS1 | \phpseclib3\Crypt\RSA::SIGNATURE_PKCS1); /** @phpstan-ignore-line */ // Call to an undefined method phpseclib3\Crypt\Common\AsymmetricKey::withPadding().
		} catch (Exception $e) {
			global $openssl_supplement_last_error;
			$openssl_supplement_last_error = $e->getMessage();
			return false;
		}
	}

	function openssl_pkey_free($key) {
	}

}
