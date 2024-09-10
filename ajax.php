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
use ViaThinkSoft\OIDplus\Core\OIDplusAuthContentStoreJWT;
use ViaThinkSoft\OIDplus\Plugins\ObjectTypes\OID\WeidOidConverter;

require_once __DIR__ . '/includes/oidplus.inc.php';

try {
	OIDplus::init(false);

	if (isset($_GET[OIDplusAuthContentStoreJWT::COOKIE_NAME]) || isset($_POST[OIDplusAuthContentStoreJWT::COOKIE_NAME])) {
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
			try {
				$json_out = $plugin->action($_REQUEST['action'], $params);
				if (!isset($json_out['status'])) $json_out['status'] = -1; // status -1 and -2 like in REST API
				if (!OIDplus::baseconfig()->getValue('DISABLE_AJAX_TRANSACTIONS',false) && OIDplus::db()->transaction_supported()) {
					OIDplus::db()->transaction_commit();
				}
			} catch (\Exception $e) {
				if (!OIDplus::baseconfig()->getValue('DISABLE_AJAX_TRANSACTIONS',false) && OIDplus::db()->transaction_supported()) {
					if (OIDplus::db()->transaction_supported()) OIDplus::db()->transaction_rollback();
				}
				throw $e;
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
				$json_out['id'] = $_REQUEST['id'];
			} catch (\Exception $e) {
				$json_out = array();
				$json_out['title'] = _L('Error');
				$json_out['icon'] = 'img/error.png';
				$htmlmsg = $e instanceof OIDplusException ? $e->getHtmlMessage() : htmlentities($e->getMessage());
				if (strtolower(substr($htmlmsg, 0, 3)) === '<p ') {
					$json_out['text'] = $htmlmsg;
				} else {
					$json_out['text'] = '<p>'.$htmlmsg.'</p>';
				}
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

			$was_weid = str_starts_with($_REQUEST['id'],'weid:');

			$_REQUEST['id'] = OIDplus::prefilterQuery($_REQUEST['id'], false);

			if ($was_weid) {
				$_REQUEST['id'] = 'weid:'.substr($_REQUEST['id'],strlen('oid:'));

//$_REQUEST['id'] = WeidOidConverter::oid2weid(substr($_REQUEST['id'],strlen('oid:')));
//echo "X=".$_REQUEST['id'];
			}

			$json_out = OIDplus::menuUtils()->json_tree($_REQUEST['id'], $_REQUEST['goto'] ?? '');
		} else {
			throw new OIDplusException(_L('Invalid action ID'));
		}
	}

	OIDplus::invoke_shutdown();

	@header('Content-Type:application/json; charset=utf-8');
	echo json_encode($json_out, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);

} catch (\Exception $e) {

	try {
		if (!OIDplus::baseconfig()->getValue('DISABLE_AJAX_TRANSACTIONS',false) && OIDplus::db()->transaction_supported() && (OIDplus::db()->transaction_level() > 0)) {
			OIDplus::db()->transaction_rollback();
		}
	} catch (\Exception $e1) {
	}

	$errmsg = $e->getMessage();

	$json_out = array();
	$json_out['status'] = -2;
	$json_out['error'] = strip_tags($errmsg);
	$out = json_encode($json_out, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);

	if ($out === false) {
		// Some modules (like ODBC) might output non-UTF8 data
		$json_out['error'] = vts_utf8_encode(strip_tags($errmsg));
		$out = json_encode($json_out, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
	}

	@header('Content-Type:application/json; charset=utf-8');

	echo $out;
}
