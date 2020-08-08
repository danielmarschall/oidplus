<?php

/*

	If this does not work, do following:

	Download:
	https://curl.haxx.se/ca/cacert.pem

	Place it somewhere.

	Edit PHP.INI:
	[curl]
	curl.cainfo=C:\inetpub\cacert.pem

*/

$url = 'https://www.viathinksoft.de/';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HEADER, TRUE);
curl_setopt($ch, CURLOPT_NOBODY, TRUE); // remove body
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
$head = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode != 0) {
	echo '<font color="green">CURL to HTTPS works!</font>';
} else {
	echo '<font color="red">CURL to HTTPS does not work!</font>';
}
