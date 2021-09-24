<?php

define('UA_NAME', 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)');

function file_get_contents2($url) {
	$out = array();
	exec("vtor -- wget -q -U ".escapeshellarg(UA_NAME)." -O - ".escapeshellarg($url), $out, $code);
	if ($code != 0) return false;
	return implode("\n", $out);
}
