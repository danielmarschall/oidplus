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

if (!function_exists('openssl_pkey_new')) {
	echo '<font color="red">OpenSSL PHP extension is not installed!</font>';
	die();
}

$pkey_config = array(
	"digest_alg" => "sha512",
	"private_key_bits" => 2048,
	"private_key_type" => OPENSSL_KEYTYPE_RSA,
);

$res = openssl_pkey_new($pkey_config);
if ($res) {
	echo '<font color="green">OpenSSL key generation works!</font>';
} else {
	echo '<font color="red">OpenSSL key generation does not work!</font>';
}
