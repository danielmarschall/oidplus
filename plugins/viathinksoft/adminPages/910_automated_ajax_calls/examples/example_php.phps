<?php

// This is an example script that shows how you can insert an OID
// (in this example "2.999.123") using an authenticated AJAX query ("RPC"-like)

$request = array(
	"plugin" => "1.3.6.1.4.1.37476.2.5.2.4.1.0", // OID of plugin "publicPages/000_objects"
	"action" => "Insert",
	"parent" => "oid:2.999",
	"id" => 123,
	"ra_email" => "test@example.com",
	"comment" => "",
	"asn1ids" => "aaa,bbb,ccc",
	"iris" => "",
	"confidential" => 0,
	"weid" => "",
	"OIDPLUS_AUTH_JWT" => "<token>"
);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, '<url>');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($request));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_AUTOREFERER, true);
if (!($res = @curl_exec($ch))) {
	die("Calling AJAX.PHP failed: " . curl_error($ch));
}
curl_close($ch);

$json = json_decode("$res", true);

if (!$json) {
	die("Invalid JSON data $res");
}

if (isset($json['error'])) {
	die($json['error']."\n");
} else if ($json['status'] >= 0) {
	die("Insert OK\n");
} else {
	die("Error ".print_r($json,true)."\n");
}

?>
