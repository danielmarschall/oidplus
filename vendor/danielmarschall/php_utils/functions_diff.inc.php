<?php

/*
 * PHP diff functions
 * Copyright 2012 Daniel Marschall, ViaThinkSoft
 * Revision 2012-11-16
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

define('TABS_WS', 6);

function output_unified_diff($fileA, $fileB, $num_lines=3) {
	$fileA = realpath($fileA);
	$fileB = realpath($fileB);

	ob_start();
	system("diff -wbB --ignore-blank-lines -U ".escapeshellarg($num_lines)." ".escapeshellarg($fileA)." ".escapeshellarg($fileB));
	$cont = ob_get_contents();
	ob_end_clean();

	$ary = explode("\n", $cont);
	foreach ($ary as $n => $a) {
		$c = substr($a, 0, 1);
		$c2 = substr($a, 0, 2);
		$c3 = substr($a, 0, 3);

		echo '<code>';
		if (($c3 == '+++') || ($c3 == '---')) {
			echo '<b><font color="gray">'.html_format($a).'</font></b>';
		} else if ($c2 == '@@') {
			echo '<b><font color="blue">'.html_format($a).'</font></b>';
		} else if ($c == '+') {
			echo '<font color="green">'.html_format($a).'</font>';
		} else if ($c == '-') {
			echo '<font color="red">'.html_format($a).'</font>';
		} else {
			echo html_format($a);
		}
		echo "</code><br />\n";
	}
}

function output_diff($fileA, $fileB, $num_lines=3) {
	$fileA = realpath($fileA);
	$fileB = realpath($fileB);

	ob_start();
	system("diff -wbB --ignore-blank-lines ".escapeshellarg($fileA)." ".escapeshellarg($fileB));
	$cont = ob_get_contents();
	ob_end_clean();

	$ary = explode("\n", $cont);
	foreach ($ary as $n => $a) {
		$c = substr($a, 0, 1);

		echo '<code>';
		if (($c == '>') || ($c == '<')) {
			echo '<b><font color="blue">'.html_format($c).'</font></b>'.html_format(substr($a, 1));
		} else {
			echo '<b><font color="blue">'.html_format($a).'</font></b>';
		}
		echo "</code><br />\n";
	}
}

function html_format($x) {
	$x = htmlentities($x);
	$x = str_replace("\t", str_repeat(' ', TABS_WS), $x);
	$x = str_replace(' ', '&nbsp;', $x);
	return $x;
}

?>
