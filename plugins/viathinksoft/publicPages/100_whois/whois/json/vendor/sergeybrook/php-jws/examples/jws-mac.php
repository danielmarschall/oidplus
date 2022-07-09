<?php
/**
 * JWS-MAC example
 * Usage:
 *  1. Run from examples dir:
 *     $ php ./jws-rsa.php
 */

use SBrook\JWS\JwsMac;
use SBrook\JWS\Exception\JwsException;

// Stand-alone:
require_once("../src/autoload.php");
// Composer:
//require_once("../vendor/autoload.php");

$exitCode = 0;

$secretOne = "8AA829AC3E1FAF5B75C1EC67A610670FFE56BF37";
$secretTwo = "6FB2486F46632DFC171B36ED64E9FA1BAE06FC29";

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
	// Create JwsMac instance:
	$jws = new JwsMac($secretOne);


	// Create original message from $payloadOne and sign with secret key $secretOne (set in constructor):
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

	// Verify original message with right secret key $secretOne (set in constructor):
	$v = $jws->verify($message);
	echo "\nVerifying original message with right secret key:\n";
	echo "Message is " . ($v ? "VALID" : "INVALID") . "\n";

	// Verify original message with wrong secret key $secretTwo:
	$jws->setSecretKey($secretTwo);
	$v = $jws->verify($message);
	echo "\nVerifying original message with wrong secret key:\n";
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

	// Verify fake message with right secret key $secretOne:
	$jws->setSecretKey($secretOne);
	$v = $jws->verify($fakeMessage);
	echo "\nVerifying fake message with right secret key:\n";
	echo "Message is " . ($v ? "VALID" : "INVALID") . "\n";

} catch (JwsException $e) {
	$exitCode = 1;

	do {
		echo "Error (".$e->getCode()."): ".$e->getMessage()."\n\tIn file: ".$e->getFile()." line: ".$e->getLine()."\n";
	} while ($e = $e->getPrevious());
}

exit($exitCode);
