<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2023 Daniel Marschall, ViaThinkSoft
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

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusException;

define('SPACER_PNG', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII='));

// TODO: should we also check security.ini ?

require_once __DIR__ . '/../../../../includes/oidplus.inc.php';

if (OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_ViaThinkSoft\OIDplus\Plugins\viathinksoft\publicPages\n500_resources\OIDplusPagePublicResources', false)) {
	throw new OIDplusException(_L('This plugin was disabled by the system administrator!'));
}

error_reporting(0);

if (!isset($_REQUEST['file'])) {
	httpOutWithETag(SPACER_PNG, 'image/png', 'spacer.png');
	die();
} else {
	$file = $_REQUEST['file'];
}

if (!isset($_REQUEST['mode'])) {
	httpOutWithETag(SPACER_PNG, 'image/png', 'spacer.png');
	die();
} else {
	$mode = $_REQUEST['mode'];
}

if (!isset($_REQUEST['lang'])) {
	$lang = '';
} else {
	$lang = $_REQUEST['lang'];
}

$candidate1 = OIDplus::getUserDataDir("resources") . $file;
$candidate2 = __DIR__ . '/../../../../res/' . $file;

if (file_exists($candidate1) || is_dir($candidate1)) {
	// It is a file inside userdata/ (or it is overwritten by userdata)
	$file = $candidate1;
} else {
	// It is a file in res/
	$file = $candidate2;
}

if (($mode == 'leaf_url_icon16') || ($mode == 'leaf_doc_icon16') || ($mode == 'folder_icon16')) {

	if (!empty($lang) && file_exists($icon_candidate = getIconCandidate($file, 'png', 'tree', $lang))) {
		httpOutWithETag(file_get_contents($icon_candidate)?:SPACER_PNG, 'image/png', basename($icon_candidate));
	} else if (file_exists($icon_candidate = getIconCandidate($file, 'png', 'tree', ''))) {
		httpOutWithETag(file_get_contents($icon_candidate)?:SPACER_PNG, 'image/png', basename($icon_candidate));
	} else if (file_exists($icon_candidate = __DIR__.'/img/'.$mode.'.png')) { // default icon for mode
		httpOutWithETag(file_get_contents($icon_candidate)?:SPACER_PNG, 'image/png', basename($icon_candidate));
	} else {
		httpOutWithETag(SPACER_PNG, 'image/png'); // should not happen
	}

} else if (($mode == 'leaf_url_icon') || ($mode == 'leaf_doc_icon') || ($mode == 'folder_icon')) {

	if (!empty($lang) && file_exists($icon_candidate = getIconCandidate($file, 'png', 'big', $lang))) {
		httpOutWithETag(file_get_contents($icon_candidate)?:SPACER_PNG, 'image/png', basename($icon_candidate));
	} else if (file_exists($icon_candidate = getIconCandidate($file, 'png', 'big', ''))) {
		httpOutWithETag(file_get_contents($icon_candidate)?:SPACER_PNG, 'image/png', basename($icon_candidate));
	} else if (file_exists($icon_candidate = __DIR__.'/img/'.$mode.'.png')) { // default icon for mode
		httpOutWithETag(file_get_contents($icon_candidate)?:SPACER_PNG, 'image/png', basename($icon_candidate));
	} else {
		httpOutWithETag(SPACER_PNG, 'image/png', 'spacer.png'); // should not happen
	}

} else {

	// Invalid $mode value
	httpOutWithETag(SPACER_PNG, 'image/png', 'spacer.png'); // should not happen

}

# ---

/**
 * @param string $file
 * @param string $picFormat
 * @param string $treeOrBig
 * @param string $lang
 * @return string
 */
function getIconCandidate(string $file, string $picFormat, string $treeOrBig, string $lang): string {
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
