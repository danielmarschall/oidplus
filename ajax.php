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

try {
	OIDplus::init(false);

	if (!isset($_REQUEST['action'])) throw new OIDplusException(_L('Action ID is missing'));

	$json_out = null;

	OIDplus::authUtils()->checkCSRF();

	if (isset($_REQUEST['plugin']) && ($_REQUEST['plugin'] != '')) {

		// Actions handled by plugins

		$plugin = OIDplus::getPluginByOid($_REQUEST['plugin']);
		if (!$plugin) {
			throw new OIDplusException(_L('Plugin with OID "%1" not found',$_REQUEST['plugin']));
		}

		if (!OIDplus::baseconfig()->getValue('DISABLE_AJAX_TRANSACTIONS',false) && OIDplus::db()->transaction_supported()) {
			OIDplus::db()->transaction_begin();
		}

		$params = array();
		foreach (array_merge($_POST,$_GET) as $name => $val) {
			if (($name != 'action') && ($name != 'plugin')) {
				$params[$name] = $val;
			}
		}

		$json_out = $plugin->action($_REQUEST['action'], $params);
		if (!is_array($json_out)) {
			throw new OIDplusException(_L('Plugin with OID %1 did not output array of result data',$_REQUEST['plugin']));
		}
		if (!isset($json_out['status'])) $json_out['status'] = -1;

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
			if (!isset($_REQUEST['id'])) throw new OIDplusException(_L('Invalid arguments'));
			try {
				$json_out = OIDplus::gui()::generateContentPage($_REQUEST['id']);
			} catch (Exception $e) {
				$json_out = array();
				$json_out['title'] = _L('Error');
				$json_out['icon'] = 'img/error_big.png';
				$json_out['text'] = $e->getMessage();
			}
		} else if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'tree_search')) {
			// Action:     tree_search
			// Method:     GET / POST
			// Parameters: search
			// Outputs:    JSON
			if (!isset($_REQUEST['search'])) throw new OIDplusException(_L('Invalid arguments'));

			$found = false;
			foreach (OIDplus::getPagePlugins() as $plugin) {
				$json_out = $plugin->tree_search($_REQUEST['search']);
				if ($json_out) {
					$found = true;
					break;
				}
			}

			if (!$found) {
				$json_out = array();
			}
		} else if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'tree_load')) {
			// Action:     tree_load
			// Method:     GET / POST
			// Parameters: id; goto (optional)
			// Outputs:    JSON
			if (!isset($_REQUEST['id'])) throw new OIDplusException(_L('Invalid arguments'));
			$json_out = OIDplus::menuUtils()->json_tree($_REQUEST['id'], isset($_REQUEST['goto']) ? $_REQUEST['goto'] : '');
		} else {
			throw new OIDplusException(_L('Invalid action ID'));
		}
	}

	@header('Content-Type:application/json; charset=utf-8');
	echo json_encode($json_out);

} catch (Exception $e) {

	try {
		if (!OIDplus::baseconfig()->getValue('DISABLE_AJAX_TRANSACTIONS',false) && OIDplus::db()->transaction_supported() && (OIDplus::db()->transaction_level() > 0)) {
			OIDplus::db()->transaction_rollback();
		}
	} catch (Exception $e1) {
	}

	$json_out = array();
	$json_out['status'] = -2;
	$json_out['error'] = $e->getMessage();
	$out = json_encode($json_out);

	if ($out === false) {
		// Some modules (like ODBC) might output non-UTF8 data
		$json_out['error'] = utf8_encode($e->getMessage());
		$out = json_encode($json_out);
	}

	@header('Content-Type:application/json; charset=utf-8');
	echo $out;
}
