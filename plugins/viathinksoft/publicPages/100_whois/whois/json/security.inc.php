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

// Works with composer.json
// "sergeybrook/php-jws": "^1.0"

/**
 * @param string $json_content
 * @param string $pubkey
 * @return void
 * @throws Exception
 */
function oidplus_json_verify(string $json_content, string $pubkey) {
	require_once __DIR__.'/vendor/autoload.php';

	$jws = new \SBrook\JWS\JwsRsa();

	// Load JSON
	$json = json_decode($json_content);

	// 1. Extract the contents of the "signature" key from the JSON.
	$signature = $json->signature;

	// 2. Remove the "signature" key from the JSON
	unset($json->signature);

	// 3. Canonize the JSON contents using RFC 8785
        $canonicalization = \aywan\JsonCanonicalization\JsonCanonicalizationFactory::getInstance();
	$canonical = $canonicalization->canonicalize($json);
	$actual_payload = $canonical;

	// 4. Compare the canonized JSON to the base64-encoded payload of the JSON Web Signature.
	$expected_payload = $jws->getPayload($signature);
	if ($actual_payload != $expected_payload) {
		// echo "Actual:\n\n$actual_payload\n\n";
		// echo "Expected:\n\n$expected_payload\n\n";
		throw new Exception("Signature verification failed (Payload different)");
	}

	// 5. Verify the JSON Web Signature according to RFC 7515
	$jws->setPublicKey($pubkey);
	$v = $jws->verify($signature);
	if (!$v) {
		throw new Exception("Signature verification failed!");
	}
}

/**
 * @param string $json_content
 * @param string $privkey
 * @param string $pubkey
 * @return false|string
 * @throws Exception
 */
function oidplus_json_sign(string $json_content, string $privkey, string $pubkey) {
	require_once __DIR__.'/vendor/autoload.php';

	$jws = new \SBrook\JWS\JwsRsa();

	// Load JSON
	$input = json_decode($json_content);

	// 1. Make sure that the JSON file has no signature (remove "signature" key if one exists).
	unset($input->signature);

	// 2. Canonize the JSON contents using RFC 8785
        $canonicalization = \aywan\JsonCanonicalization\JsonCanonicalizationFactory::getInstance();
	$canonical = $canonicalization->canonicalize($input);

	// 3. Sign the canonized JSON using a JSON Web Signature (JWS, RFC 7515)

	// For JWS registered header parameter names see (RFC 7515, Section 4.1)
	// Note that the required "alg" argument will be added automatically
	$header = [
		"typ" => "OID-IP", // optional (unused)
		"cty" => "text/json" // optional
	];
	$payload = $canonical;
	$jws->setPrivateKey($privkey, '');
	$signature = $jws->sign($payload, $header);

	// 4. Add the key "signature" into the final JSON. Note that the final JSON does not need to be canonized. It can be pretty-printed.
	$output = $input;
	$output->signature = $signature;

	// Self-test and output
	$json_signed = json_encode($output);
	oidplus_json_verify($json_signed, $pubkey);
	return $json_signed;
}
