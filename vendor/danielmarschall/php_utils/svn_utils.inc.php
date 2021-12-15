<?php

/*
 * PHP svn functions
 * Copyright 2021 Daniel Marschall, ViaThinkSoft
 * Revision 2021-12-15
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
	return false;
}

