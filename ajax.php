<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2022 Daniel Marschall, ViaThinkSoft
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

	if (isset($_GET['OIDPLUS_AUTH_JWT']) || isset($_POST['OIDPLUS_AUTH_JWT'])) {
		originHeaders(); // Allows queries from other domains
		OIDplus::authUtils()->disableCSRF(); // allow access to ajax.php without valid CSRF token
	}

	$json_out = null;

	if (isset($_REQUEST['plugin']) && ($_REQUEST['plugin'] != '')) {

		// Actions handled by plugins

		$plugin = OIDplus::getPluginByOid($_REQUEST['plugin']);
		if (!$plugin) {
			throw new OIDplusException(_L('Plugin with OID "%1" not found',$_REQUEST['plugin']));
		}

		$params = array();
		foreach (array_merge($_POST,$_GET) as $name => $val) {
			if (($name != 'action') && ($name != 'plugin')) {
				$params[$name] = $val;
			}
		}

		if (isset($_REQUEST['action']) && ($_REQUEST['action'] != '')) {
			if ($plugin->csrfUnlock($_REQUEST['action'])) {
				originHeaders(); // Allows queries from other domains
				OIDplus::authUtils()->disableCSRF(); // allow access to ajax.php without valid CSRF token
			}

			OIDplus::authUtils()->checkCSRF();

			if (!OIDplus::baseconfig()->getValue('DISABLE_AJAX_TRANSACTIONS',false) && OIDplus::db()->transaction_supported()) {
				OIDplus::db()->transaction_begin();
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
			throw new OIDplusException(_L('Invalid action ID'));
		}

	} else {

		// Actions handled by the system (base functionality like the JS tree)

		OIDplus::authUtils()->checkCSRF();

		if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'get_description')) {
			// Action:     get_description
			// Method:     GET / POST
			// Parameters: id
			// Outputs:    JSON
			_CheckParamExists($_REQUEST, 'id');
			$_REQUEST['id'] = OIDplus::prefilterQuery($_REQUEST['id'], false);
			try {
				$json_out = OIDplus::gui()->generateContentPage($_REQUEST['id']);
			} catch (Exception $e) {
				$json_out = array();
				$json_out['title'] = _L('Error');
				$json_out['icon'] = 'img/error_big.png';
				$json_out['text'] = $e->getMessage();
			}
			$json_out['status'] = 0;
		} else if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'tree_search')) {
			// Action:     tree_search
			// Method:     GET / POST
			// Parameters: search
			// Outputs:    JSON
			_CheckParamExists($_REQUEST, 'search');

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
			_CheckParamExists($_REQUEST, 'id');
			$_REQUEST['id'] = OIDplus::prefilterQuery($_REQUEST['id'], false);
			$json_out = OIDplus::menuUtils()->json_tree($_REQUEST['id'], isset($_REQUEST['goto']) ? $_REQUEST['goto'] : '');
		} else {
			throw new OIDplusException(_L('Invalid action ID'));
		}
	}

	OIDplus::invoke_shutdown();

	@header('Content-Type:application/json; charset=utf-8');
	echo json_encode($json_out);

} catch (Exception $e) {

	try {
		if (!OIDplus::baseconfig()->getValue('DISABLE_AJAX_TRANSACTIONS',false) && OIDplus::db()->transaction_supported() && (OIDplus::db()->transaction_level() > 0)) {
			OIDplus::db()->transaction_rollback();
		}
	} catch (Exception $e1) {
	}

	$errmsg = $e->getMessage();
	$errmsg = strip_tags($errmsg);
	$errmsg = html_entity_decode($errmsg, ENT_QUOTES, 'UTF-8');

	$json_out = array();
	$json_out['status'] = -2;
	$json_out['error'] = $errmsg;
	$out = json_encode($json_out);

	if ($out === false) {
		// Some modules (like ODBC) might output non-UTF8 data
		$json_out['error'] = utf8_encode($errmsg);
		$out = json_encode($json_out);
	}

	@header('Content-Type:application/json; charset=utf-8');

	echo $out;
}
