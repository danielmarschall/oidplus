<?php

$rfc = 5234;

$ref = file_get_contents("https://www.rfc-editor.org/refs/ref$rfc.txt");

$ref = preg_replace('@, (BCP|STD|DOI|RFC) (\\d)@', ', \\1\\\\0\\2', $ref);
$ref = preg_replace('@(January|February|March|April|May|June|July|August|September|October|November|December) (\\d)@', '\\1\\\\0\\2', $ref);
$ref = str_replace(', <http', ",\n.in 14\n<http", $ref);

echo '.ti 3'."\n";
echo '.in 14'."\n";
echo '.\" https://www.rfc-editor.org/refs/ref'.$rfc.'.txt'."\n";
echo "[RFC$rfc]  $ref\n\n";
