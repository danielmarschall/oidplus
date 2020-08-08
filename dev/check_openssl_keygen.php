<?php

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
