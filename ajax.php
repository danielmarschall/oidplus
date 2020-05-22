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
	if (!isset($_REQUEST['action'])) throw new OIDplusException("Action ID is missing");

	if (isset($_REQUEST['plugin']) && ($_REQUEST['plugin'] != '')) {

		// Actions handled by plugins

		$plugin = OIDplus::getPluginByOid($_REQUEST['plugin']);
		if (!$plugin) {
			throw new OIDplusException("Plugin with OID '".$_REQUEST['plugin']."' not found");
		}

		if (!OIDplus::baseconfig()->getValue('DISABLE_AJAX_TRANSACTIONS',false) && OIDplus::db()->transaction_supported()) {
			OIDplus::db()->transaction_begin();
		}
		
		$params = $_REQUEST;
		unset($params['action']);
		$plugin->action($_REQUEST['action'], $params);

		if (!OIDplus::baseconfig()->getValue('DISABLE_AJAX_TRANSACTIONS',false) && OIDplus::db()->transaction_supported()) {
			OIDplus::db()->transaction_commit();
		}

	} else {

		// Actions handled by the system (base functionality like the JS tree)

		if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'get_description')) {
			// Action:     get_description
			// Method:     GET / POST
			// Parameters: id
			// Outputs:    JSON
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
		} else if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'tree_search')) {
			// Action:     tree_search
			// Method:     GET / POST
			// Parameters: search
			// Outputs:    JSON
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
		} else if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'tree_load')) {
			// Action:     tree_load
			// Method:     GET / POST
			// Parameters: id; goto (optional)
			// Outputs:    JSON
			$handled = true;
			if (!isset($_REQUEST['id'])) throw new OIDplusException("Invalid args");
			$json = OIDplus::menuUtils()->json_tree($_REQUEST['id'], isset($_REQUEST['goto']) ? $_REQUEST['goto'] : '');
			echo $json;
		} else {
			throw new OIDplusException('Invalid action ID');
		}
	}
} catch (Exception $e) {
	try {
		if (!OIDplus::baseconfig()->getValue('DISABLE_AJAX_TRANSACTIONS',false) && OIDplus::db()->transaction_supported() && (OIDplus::db()->transaction_level() > 0)) {
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
