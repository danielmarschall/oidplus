<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2021 Daniel Marschall, ViaThinkSoft
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

define('SPACER_PNG', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII='));

// TODO: should we also check security.ini ?

require_once __DIR__ . '/../../../includes/functions.inc.php';

error_reporting(0);

if (!isset($_REQUEST['file'])) {
	httpOutWithETag(SPACER_PNG, 'image/png', 'spacer.png');
} else {
	$file = $_REQUEST['file'];
}

if (!isset($_REQUEST['mode'])) {
	httpOutWithETag(SPACER_PNG, 'image/png', 'spacer.png');
} else {
	$mode = $_REQUEST['mode'];
}

if (!isset($_REQUEST['lang'])) {
	$lang = 'enus';
} else {
	$lang = $_REQUEST['lang'];
}

$candidate1 = __DIR__ . '/../../../userdata/resources/' . $file;
$candidate2 = __DIR__ . '/../../../res/' . $file;

if (file_exists($candidate1) || is_dir($candidate1)) {
	// It is a file inside userdata/ (or it is overwritten by userdata)
	$file = $candidate1;
} else {
	// It is a file in res/
	$file = $candidate2;
}

if (($mode == 'treeicon_folder') || ($mode == 'treeicon_leaf_url') || ($mode == 'treeicon_leaf_doc')) {

	if (file_exists($icon_candidate = getIconCandidate($file, 'png', 'tree', $lang))) {
		httpOutWithETag(file_get_contents($icon_candidate), 'image/png', basename($icon_candidate));
	} else if (file_exists($icon_candidate = getIconCandidate($file, 'png', 'tree', ''))) {
		httpOutWithETag(file_get_contents($icon_candidate), 'image/png', basename($icon_candidate));
	} else if (file_exists($icon_candidate = __DIR__.'/'.$mode.'.png')) { // default icon for mode
		httpOutWithETag(file_get_contents($icon_candidate), 'image/png', basename($icon_candidate));
	} else {
		httpOutWithETag(SPACER_PNG, 'image/png'); // should not happen
	}

} else if (($mode == 'icon_leaf_url_big') || ($mode == 'icon_leaf_doc_big') || ($mode == 'icon_folder_big')) {

	if (file_exists($icon_candidate = getIconCandidate($file, 'png', 'big', $lang))) {
		httpOutWithETag(file_get_contents($icon_candidate), 'image/png', basename($icon_candidate));
	} else if (file_exists($icon_candidate = getIconCandidate($file, 'png', 'big', ''))) {
		httpOutWithETag(file_get_contents($icon_candidate), 'image/png', basename($icon_candidate));
	} else if (file_exists($icon_candidate = __DIR__.'/'.$mode.'.png')) { // default icon for mode
		httpOutWithETag(file_get_contents($icon_candidate), 'image/png', basename($icon_candidate));
	} else {
		httpOutWithETag(SPACER_PNG, 'image/png', 'spacer.png'); // should not happen
	}

} else {

	// Invalid $mode value
	httpOutWithETag(SPACER_PNG, 'image/png', 'spacer.png'); // should not happen

}

# ---

function getIconCandidate($file, $picFormat, $treeOrBig, $lang) {
	$cnt = 0;
	if (!empty($lang)) {
		$appendix = '_'.$treeOrBig.'$'.$lang.'.'.$picFormat;
	} else {
		$appendix = '_'.$treeOrBig.'.'.$picFormat;
	}
	$tmp = preg_replace('/\.([^.]+)$/', $appendix, basename($file), -1, $cnt);
	if ($cnt == 0) $tmp .= $appendix;
	return dirname($file).'/'.$tmp;
}
