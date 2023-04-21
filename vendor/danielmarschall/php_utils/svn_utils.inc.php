<?php

/*
 * PHP svn functions
 * Copyright 2021 - 2023 Daniel Marschall, ViaThinkSoft
 * Revision 2023-04-21
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

function get_svn_revision($dir='') {
	if (!empty($dir)) $dir .= '/';
	if (!is_dir($dir)) return false;
	if (is_dir($dir.'/.svn')) $dir .= '/.svn/';

	// Try to find out the SVN version using the shell
	$output = @shell_exec('svnversion '.escapeshellarg($dir).' 2>&1');
	$match = array();
	if (preg_match('/\d+/', $output, $match)) {
		return ($cachedVersion = $match[0]);
	}

	$output = @shell_exec('svn info '.escapeshellarg($dir).' 2>&1');
	if (preg_match('/Revision:\s*(\d+)/m', $output, $match)) { // do not translate
		return ($cachedVersion = $match[1]);
	}

	// If that failed, try to get the version via access of the database files
	if (class_exists('SQLite3')) {
		try {
			$db = new SQLite3($dir.'.svn/wc.db');
			$results = $db->query('SELECT MIN(revision) AS rev FROM NODES_BASE');
			while ($row = $results->fetchArray()) {
				return ($cachedVersion = $row['rev']);
			}
			$db->close();
			$db = null;
		} catch (Exception $e) {
		}
	}
	if (class_exists('PDO')) {
		try {
			$pdo = new PDO('sqlite:'.$dir.'.svn/wc.db');
			$res = $pdo->query('SELECT MIN(revision) AS rev FROM NODES_BASE');
			$row = $res->fetch();
			if ($row !== false) {
				return ($cachedVersion = $row['rev']);
			}
			$pdo = null;
		} catch (Exception $e) {
		}
	}

	// We couldn't get the revision info
	// Try parsing the binary file. It is a bit risky though...
	return get_svn_revision_without_sqlite3($dir);
}

function get_svn_revision_without_sqlite3($svn_path, $base='trunk') {
	if (!empty($svn_path)) $svn_path .= '/';
	if (!is_dir($svn_path)) return false;
	if (!is_dir($svn_path.'/.svn')) $svn_path .= '/../';

	$fil = file_get_contents($svn_path.'/.svn/wc.db');
	preg_match_all('@('.preg_quote($base,'@').'/[a-z0-9!"#$%&\'()*+,.\/:;<=>?\@\[\] ^_`{|}~-]+)(..)normal(file|dir)@', $fil, $m, PREG_SET_ORDER);

	$files = array();
	foreach ($m as list($dummy, $fil, $revision)) {
		$val = hexdec(bin2hex($revision));

		$tmp = explode("$base/", $fil);
		$fil = end($tmp);

		if (!file_exists($svn_path."/$base/$fil")) continue; // deleted files (deleted rows?!) might be still in the binary

		if (!isset($files[$fil])) $files[$fil] = -1;
		if ($files[$fil] < $val) $files[$fil] = $val;
	}

	$arr = array_values($files);

	/*
	foreach ($files as $name => $val) {
		if ($val != 1228) echo "DEBUG Unexpected: $val / $fil\n";
	}
	*/

    $num = count($arr);
    $middleVal = floor(($num - 1) / 2);
    if($num % 2) {
        $median = $arr[$middleVal];
    } else {
        $lowMid = $arr[$middleVal];
        $highMid = $arr[$middleVal + 1];
        $median = (($lowMid + $highMid) / 2);
    }

    return $median;
}
