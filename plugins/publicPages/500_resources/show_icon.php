<?php

/*
 * OIDplus 2.0
 * Copyright 2020 Daniel Marschall, ViaThinkSoft
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

header('Content-Type:image/png');

$spacer_png = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=";

if (!isset($_REQUEST['file'])) {
	die($spacer_png);
} else {
	$file = $_REQUEST['file'];
}

if (!isset($_REQUEST['mode'])) {
	die($spacer_png);
} else {
	$mode = $_REQUEST['mode'];
}

$candidate1 = __DIR__ . '/../../../userdata/resources/' . $file;
$candidate2 = __DIR__ . '/../../../res/' . $file;

if (file_exists($candidate1) || is_dir($candidate1)) {
	$file = $candidate1;
} else {
	$file = $candidate2;
}

if (($mode == 'treeicon_folder') || ($mode == 'treeicon_leaf_url') || ($mode == 'treeicon_leaf_doc')) {
	if (file_exists($icon_candidate = pathinfo($file)['dirname'].'/'.pathinfo($file)['filename'].'_tree.png')) {
		echo file_get_contents($icon_candidate);
	} else if (file_exists($icon_candidate = __DIR__.'/'.$mode.'.png')) {
		echo file_get_contents($icon_candidate);
	} else {
		echo $spacer_png; // should not happen
	}
} else if (($mode == 'icon_leaf_url_big') || ($mode == 'icon_leaf_doc_big') || ($mode == 'icon_folder_big')) {
	
	if (file_exists($icon_candidate = pathinfo($file)['dirname'].'/'.pathinfo($file)['filename'].'_big.png')) {
		echo file_get_contents($icon_candidate);
	} else if (file_exists($icon_candidate = __DIR__.'/'.$mode.'.png')) {
		echo file_get_contents($icon_candidate);
	} else {
		echo $spacer_png; // should not happen
	}
	
} else {
	echo $spacer_png; // should not happen
}
