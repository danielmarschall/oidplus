#!/usr/bin/php
<?php

define('APACHE_MIME_TYPES_URL','https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types');

function generateUpToDateMimeArray($url) {
	// Based on https://www.php.net/manual/de/function.mime-content-type.php#107798 , modified
	$s = array();
	$dupcheck = array();
	foreach (@explode("\n",@file_get_contents($url))as $x) {
		if (isset($x[0])&&$x[0]!=='#'&&preg_match_all('#([^\s]+)#',$x,$out)&&isset($out[1])&&($c=count($out[1]))>1) {
			for ($i=1;$i<$c;$i++) {
				if (!isset($dupcheck[$out[1][$i]])) {
					$s[] = "\t'".$out[1][$i]."' => '".$out[1][0]."'";
					$dupcheck[$out[1][$i]] = true;
				}
			}
		}
	}
	return @sort($s) ? "\$mime_types = array(\n".implode($s,",\n")."\n);" : false;
}

$res = generateUpToDateMimeArray(APACHE_MIME_TYPES_URL);
if (!$res) exit(1);

$out = "";
$out .= "<?php\n";
$out .= "\n";
$out .= "// Generated ".date('Y-m-d H:i:s')."\n";
$out .= "// Source: ".APACHE_MIME_TYPES_URL."\n";
$out .= "\n";
$out .= $res;
file_put_contents(__DIR__.'/mimetype_lookup.inc.php', $out);
exit(0);

