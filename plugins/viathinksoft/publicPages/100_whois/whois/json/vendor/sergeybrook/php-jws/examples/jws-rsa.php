<?php
/**
 * JWS-RSA example
 * Usage:
 *  1. Generate two key/certificate pairs - run from examples/cert dir:
 *     $ ./create-cert.sh one 365
 *     $ ./create-cert.sh two 365
 *     This will create "prv-one.key"/"pub-one.crt" and "prv-two.key"/"pub-two.crt" pairs in examples/cert dir.
 *  2. Run from examples dir:
 *     $ php ./jws-rsa.php
 */

use SBrook\JWS\JwsRsa;
use SBrook\JWS\Exception\JwsException;

// Stand-alone:
require_once("../src/autoload.php");
// Composer:
//require_once("../vendor/autoload.php");

$exitCode = 0;

$prvOne = "file://./cert/prv-one.key";
$prvOnePass = "password";

$pubOne = "file://./cert/pub-one.crt";
$pubTwo = "file://./cert/pub-two.crt";

// For JWS registered header parameter names see (RFC 7515, Section 4.1)
$header = [
	"typ" => "TXT",
	"ts0" => "",
	"ts1" => 0,
	"ts2" => false,
	"ts3" => null,
	"ts4" => chr(32),
	"ts5" => chr(7)
];

$payloadOne = "Original message content";
$payloadTwo = "Fake message content";

try {
	// Create JwsRsa instance:
	$jws = new JwsRsa();


	// Create original message from $payloadOne and sign with private key $prvOne:
	$jws->setPrivateKey($prvOne, $prvOnePass);
	$message = $jws->sign($payloadOne, $header);
	echo "\nOriginal message:\n";
	echo "--- BEGIN JWS ---\n$message\n---- END JWS ----\n";

	// Get original message header:
	$h = $jws->getHeader($message);
	// JSON encode just to more clearly show the values:
	echo "\nHeader => " . json_encode($h) . "\n";

	// Get original message payload:
	$p = $jws->getPayload($message);
	echo "\nPayload => \"$p\"\n";

	// Verify original message with right public key $pubOne:
	$jws->setPublicKey($pubOne);
	$v = $jws->verify($message);
	echo "\nVerifying original message with right public key:\n";
	echo "Message is " . ($v ? "VALID" : "INVALID") . "\n";

	// Verify original message with wrong public key $pubTwo:
	$jws->setPublicKey($pubTwo);
	$v = $jws->verify($message);
	echo "\nVerifying original message with wrong public key:\n";
	echo "Message is " . ($v ? "VALID" : "INVALID") . "\n";


	echo "\n" . str_repeat("=", 80) . "\n";
	// Now, let's manipulate original message by putting a fake content into it:

	// Get header and signature from original message:
	list($h, , $s) = explode(".", $message);
	// Rebuild message with fake payload $payloadTwo:
	$fakeMessage = $h . "." . base64_encode($payloadTwo) . "." . $s;
	echo "\nFake message:\n";
	echo "--- BEGIN JWS ---\n$fakeMessage\n---- END JWS ----\n";

	// Get fake message payload:
	$p = $jws->getPayload($fakeMessage);
	echo "\nPayload => \"$p\"\n";

	// Verify fake message with right public key $pubOne:
	$jws->setPublicKey($pubOne);
	$v = $jws->verify($fakeMessage);
	echo "\nVerifying fake message with right public key:\n";
	echo "Message is " . ($v ? "VALID" : "INVALID") . "\n";

} catch (JwsException $e) {
	$exitCode = 1;

	do {
		echo "Error (".$e->getCode()."): ".$e->getMessage()."\n\tIn file: ".$e->getFile()." line: ".$e->getLine()."\n";
	} while ($e = $e->getPrevious());
}

exit($exitCode);

