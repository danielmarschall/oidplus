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
	"batch_login_username" => "admin",
	"batch_login_password" => ".......",
	"batch_ajax_unlock_key" => "ee33790b233737da02e0253df666dd8284701f7a"
);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://www.example.com/oidplus/ajax.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($request));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_AUTOREFERER, true);
if (!($res = @curl_exec($ch))) {
	die("Calling AJAX.PHP failed: " . curl_error($ch));
}
curl_close($ch);

$json = json_decode($res, true);

if (!$json) {
	die("Invalid JSON data $res");
}

if (isset($json['error'])) {
	die($json['error']);
} else if ($json['status'] == 0/*OK*/) {
	die("Insert OK");
} else if ($json['status'] == 1/*RaNotExisting*/) {
	die("Insert OK");
} else if ($json['status'] == 2/*RaNotExistingNoInvitation*/) {
	die("Insert OK");
} else {
	die("Error ".print_r($json,true));
}

?>