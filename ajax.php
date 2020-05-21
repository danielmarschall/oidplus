<?php

/*
 * OIDplus 2.0
 * Copyright 2019 Daniel Marschall, ViaThinkSoft
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

require_once __DIR__ . '/includes/oidplus.inc.php';

OIDplus::init(false);

header('Content-Type:application/json; charset=utf-8');

try {
	if (!OIDplus::baseconfig()->getValue('DISABLE_AJAX_TRANSACTIONS',false) && OIDplus::db()->transaction_supported()) {
		OIDplus::db()->transaction_begin();
	}
	$handled = false;

	// Action:     (actions defined by plugins)
	// Method:     GET / POST
	// Parameters: ...
	// Outputs:    ...
	foreach (OIDplus::getPagePlugins() as $plugin) {
		$plugin->action($handled);
		if ($handled) break;
	}

	// Action:     get_description
	// Method:     GET / POST
	// Parameters: id
	// Outputs:    JSON
	if (isset($_REQUEST["action"]) && ($_REQUEST['action'] == 'get_description')) {
		// This code is the very base functionality (load content page) and therefore won't be in a plugin
		$handled = true;
		if (!isset($_REQUEST['id'])) throw new OIDplusException("Invalid args");
		try {
			$out = OIDplus::gui()::generateContentPage($_REQUEST['id']);
		} catch(Exception $e) {
			$out = array();
			$out['title'] = 'Error';
			$out['icon'] = 'img/error_big.png';
			$out['text'] = $e->getMessage();
		}
		echo json_encode($out);
	}

	// === jsTree ===

	// Action:     tree_search
	// Method:     GET / POST
	// Parameters: search
	// Outputs:    JSON
	if (isset($_REQUEST["action"]) && ($_REQUEST['action'] == 'tree_search')) {
		// This code is the very base functionality (menu handling)
		$handled = true;
		if (!isset($_REQUEST['search'])) throw new OIDplusException("Invalid args");

		$found = false;
		foreach (OIDplus::getPagePlugins() as $plugin) {
			$res = $plugin->tree_search($_REQUEST['search']);
			if ($res) {
				echo json_encode($res);
				$found = true;
				break;
			}
		}

		if (!$found) {
			echo json_encode(array());
		}
	}

	// Action:     tree_load
	// Method:     GET / POST
	// Parameters: id; goto (optional)
	// Outputs:    JSON
	if (isset($_REQUEST["action"]) && ($_REQUEST['action'] == 'tree_load')) {
		// This code is the very base functionality (menu handling)
		$handled = true;
		if (!isset($_REQUEST['id'])) throw new OIDplusException("Invalid args");
		$json = OIDplus::menuUtils()->json_tree($_REQUEST['id'], isset($_REQUEST['goto']) ? $_REQUEST['goto'] : '');
		echo $json;
	}

	if (!$handled) {
		throw new OIDplusException('Invalid action ID');
	}

	if (!OIDplus::baseconfig()->getValue('DISABLE_AJAX_TRANSACTIONS',false) && OIDplus::db()->transaction_supported()) {
		OIDplus::db()->transaction_commit();
	}
} catch (Exception $e) {
	try {
		if (!OIDplus::baseconfig()->getValue('DISABLE_AJAX_TRANSACTIONS',false) && OIDplus::db()->transaction_supported()) {
			OIDplus::db()->transaction_rollback();
		}
	} catch (Exception $e1) {
	}

	$ary = array();
	$ary['error'] = $e->getMessage();
	$out = json_encode($ary);

	if ($out === false) {
		// Some modules (like ODBC) might output non-UTF8 data
		$ary['error'] = utf8_encode($e->getMessage());
		$out = json_encode($ary);
	}

	die($out);
}

# ---

function _ra_change_rec($id, $old_ra, $new_ra) {
	OIDplus::db()->query("update ###objects set ra_email = ?, updated = ".OIDplus::db()->sqlDate()." where id = ? and ifnull(ra_email,'') = ?", array($new_ra, $id, $old_ra));

	$res = OIDplus::db()->query("select id from ###objects where parent = ? and ifnull(ra_email,'') = ?", array($id, $old_ra));
	while ($row = $res->fetch_array()) {
		_ra_change_rec($row['id'], $old_ra, $new_ra);
	}
}
