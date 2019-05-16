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

OIDplus::db()->set_charset("UTF8");
OIDplus::db()->query("SET NAMES 'utf8'");

header('Content-Type:application/json; charset=utf-8');

try {
	$handled = false;

	// Action:     (actions defined by plugins)
	// Method:     GET / POST
	// Parameters: ...
	// Outputs:    ...
	foreach (OIDplus::getPagePlugins('*') as $plugin) {
		$plugin->action($handled);
	}

	// Action:     get_description
	// Method:     GET / POST
	// Parameters: id
	// Outputs:    JSON
	if (isset($_REQUEST["action"]) && ($_REQUEST['action'] == 'get_description')) {
		$handled = true;
		if (!isset($_REQUEST['id'])) throw new Exception("Invalid args");
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
		$handled = true;
		if (!isset($_REQUEST['search'])) throw new Exception("Invalid args");

		$found = false;
		foreach (OIDplus::getPagePlugins('*') as $plugin) {
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
		$handled = true;
		if (!isset($_REQUEST['id'])) throw new Exception("Invalid args");
		$json = OIDplusTree::json_tree($_REQUEST['id'], isset($_REQUEST['goto']) ? $_REQUEST['goto'] : '');
		echo $json;
	}

	// === Admin / RA actions ===

	// Action:     delete_ra
	// Method:     POST
	// Parameters: email
	// Outputs:    Text
	if (isset($_POST["action"]) && ($_POST["action"] == "delete_ra")) {
		$handled = true;

		$email = $_POST['email'];

		$ra_logged_in = OIDplus::authUtils()::isRaLoggedIn($email);

		if (!OIDplus::authUtils()::isAdminLoggedIn() && !$ra_logged_in) {
			throw new Exception('You need to log in as administrator');
		}

		if ($ra_logged_in) OIDplus::authUtils()::raLogout($email);

		$ra = new OIDplusRA($email);
		$ra->delete();

		echo json_encode(array("status" => 0));
	}

	// === OID CRUD ===

	// Action:     Delete
	// Method:     POST
	// Parameters: id
	// Outputs:    Text
	if (isset($_POST["action"]) && ($_POST["action"] == "Delete")) {
		$handled = true;

		$id = $_POST['id'];
		$obj = OIDplusObject::parse($id);

		// Prüfen ob zugelassen
		if (!$obj->userHasParentalWriteRights()) throw new Exception('Authentification error. Please log in as the superior RA to delete this OID.');

		// Delete object
		OIDplus::db()->query("delete from ".OIDPLUS_TABLENAME_PREFIX."objects where id = '".OIDplus::db()->real_escape_string($id)."'");

		// Delete orphan stuff
		foreach (OIDplus::getRegisteredObjectTypes() as $ot) {
			$where = "where parent <> '".OIDplus::db()->real_escape_string($ot::root())."' and " .
			         "      parent like '".OIDplus::db()->real_escape_string($ot::root().'%')."' and " .
			         "      parent not in (select id from ".OIDPLUS_TABLENAME_PREFIX."objects where id like '".OIDplus::db()->real_escape_string($ot::root().'%')."')";
			do {
				$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects $where");
				while ($row = OIDplus::db()->fetch_array($res)) {
					if (!OIDplus::db()->query("delete from ".OIDPLUS_TABLENAME_PREFIX."objects where id = '".OIDplus::db()->real_escape_string($row['id'])."'")) {
						throw new Exception(OIDplus::db()->error());
					}
				}
			} while (OIDplus::db()->num_rows($res) > 0);
		}
		OIDplus::db()->query("delete from ".OIDPLUS_TABLENAME_PREFIX."asn1id where well_known <> 1 and oid not in (select id from ".OIDPLUS_TABLENAME_PREFIX."objects where id like 'oid:%');");
		OIDplus::db()->query("delete from ".OIDPLUS_TABLENAME_PREFIX."iri    where well_known <> 1 and oid not in (select id from ".OIDPLUS_TABLENAME_PREFIX."objects where id like 'oid:%');");

		echo json_encode(array("status" => 0));
	}

	// Action:     Update
	// Method:     POST
	// Parameters: id, ra_email, iris, asn1ids, confidential
	// Outputs:    Text
	if (isset($_POST["action"]) && ($_POST["action"] == "Update")) {
		$handled = true;

		$id = $_POST['id'];
		$obj = OIDplusObject::parse($id);

		// Validate RA email address
		$new_ra = $_POST['ra_email'];
		if (!empty($new_ra) && !oidplus_valid_email($new_ra)) {
			throw new Exception('Invalid RA email address');
		}

		// Prüfen ob zugelassen
		if (!$obj->userHasParentalWriteRights()) throw new Exception('Authentification error. Please log in as the superior RA to update this OID.');

		// RA ändern (rekursiv)
		$res = OIDplus::db()->query("select ra_email from ".OIDPLUS_TABLENAME_PREFIX."objects where id = '".OIDplus::db()->real_escape_string($id)."'");
		$row = OIDplus::db()->fetch_array($res);
		$current_ra = $row['ra_email'];

		if ($new_ra != $current_ra) _ra_change_rec($id, $current_ra, $new_ra); // Inherited RAs rekursiv mitändern

		// Replace ASN.1 und IRI IDs
		if ($obj::ns() == 'oid') {
			$oid = $obj;

			$ids = ($_POST['iris'] == '') ? array() : explode(',',$_POST['iris']);
			$ids = array_map('trim',$ids);
			$oid->replaceIris($ids);

			$ids = ($_POST['asn1ids'] == '') ? array() : explode(',',$_POST['asn1ids']);
			$ids = array_map('trim',$ids);
			$oid->replaceAsn1Ids($ids);
		}

		$confidential = $_POST['confidential'] == 'true' ? '1' : '0';
		if (!OIDplus::db()->query("UPDATE ".OIDPLUS_TABLENAME_PREFIX."objects SET confidential = ".OIDplus::db()->real_escape_string($confidential).", updated = now() WHERE id = '".OIDplus::db()->real_escape_string($id)."'")) {
			throw new Exception('Error at setting confidential flag:' . OIDplus::db()->error());
		}

		$status = 0;

		if (!empty($new_ra)) {
			$res = OIDplus::db()->query("select ra_name from ".OIDPLUS_TABLENAME_PREFIX."ra where email = '".OIDplus::db()->real_escape_string($new_ra)."'");
			if (OIDplus::db()->num_rows($res) == 0) $status = 1;
		}

		echo json_encode(array("status" => $status));
	}

	// Action:     Update2
	// Method:     POST
	// Parameters: id, title, description
	// Outputs:    Text
	if (isset($_POST["action"]) && ($_POST["action"] == "Update2")) {
		$handled = true;

		$id = $_POST['id'];
		$obj = OIDplusObject::parse($id);

		// Prüfen ob zugelassen
		if (!$obj->userHasWriteRights()) throw new Exception('Authentification error. Please log in as the RA to update this OID.');

		if (!OIDplus::db()->query("UPDATE ".OIDPLUS_TABLENAME_PREFIX."objects SET title = '".OIDplus::db()->real_escape_string($_POST['title'])."', description = '".OIDplus::db()->real_escape_string($_POST['description'])."', updated = now() WHERE id = '".OIDplus::db()->real_escape_string($id)."'")) {
			throw new Exception(OIDplus::db()->error());
		}

		echo json_encode(array("status" => 0));
	}

	// Action:     Insert
	// Method:     POST
	// Parameters: parent, id, ra_email, confidential, iris, asn1ids
	// Outputs:    Text
	if (isset($_POST["action"]) && ($_POST["action"] == "Insert")) {
		$handled = true;

		// Es wird validiert: ID, ra email, asn1 ids, iri ids

		// Check if you have write rights on the parent (to create a new object)
		$objParent = OIDplusObject::parse($_POST['parent']);
		if (!$objParent->userHasWriteRights()) throw new Exception('Authentification error. Please log in as the correct RA to insert an OID at this arc.');

		// Check if the ID is valid
		if ($_POST['id'] == '') throw new Exception('ID may not be empty');

		// Absoluten OID namen bestimmen
		// Note: At addString() and parse(), the syntax of the ID will be checked
		$id = $objParent->addString($_POST['id']);
		$obj = OIDplusObject::parse($id);

		// Superior RA Änderung durchführen
		$parent = $_POST['parent'];
		$ra_email = $_POST['ra_email'];
		if (!empty($ra_email) && !oidplus_valid_email($ra_email)) {
			throw new Exception('Invalid RA email address');
		}
		$confidential = $_POST['confidential'] == 'true' ? '1' : '0';
		if (!OIDplus::db()->query("INSERT INTO ".OIDPLUS_TABLENAME_PREFIX."objects (id, parent, ra_email, confidential, created) VALUES ('".OIDplus::db()->real_escape_string($id)."', '".OIDplus::db()->real_escape_string($parent)."', '".OIDplus::db()->real_escape_string($ra_email)."', ".OIDplus::db()->real_escape_string($confidential).", now())")) {
			throw new Exception(OIDplus::db()->error());
		}

		// Set ASN.1 und IRI IDs
		if ($obj::ns() == 'oid') {
			$oid = $obj;

			$ids = ($_POST['iris'] == '') ? array() : explode(',',$_POST['iris']);
			$ids = array_map('trim',$ids);
			$oid->replaceIris($ids);

			$ids = ($_POST['asn1ids'] == '') ? array() : explode(',',$_POST['asn1ids']);
			$ids = array_map('trim',$ids);
			$oid->replaceAsn1Ids($ids);
		}

		$status = 0;

		if (!empty($ra_email)) {
			$res = OIDplus::db()->query("select ra_name from ".OIDPLUS_TABLENAME_PREFIX."ra where email = '".OIDplus::db()->real_escape_string($ra_email)."'");
			if (OIDplus::db()->num_rows($res) == 0) $status = 1;
		}

		echo json_encode(array("status" => $status));
	}

	if (!$handled) {
		throw new Exception('Invalid action ID');
	}
} catch (Exception $e) {
	$ary = array();
	$ary['error'] = $e->getMessage();
	echo json_encode($ary);
}

# ---

function _ra_change_rec($id, $old_ra, $new_ra) {
	OIDplus::db()->query("update ".OIDPLUS_TABLENAME_PREFIX."objects set ra_email = '".OIDplus::db()->real_escape_string($new_ra)."', updated = now() where id = '".OIDplus::db()->real_escape_string($id)."' and ifnull(ra_email,'') = '".OIDplus::db()->real_escape_string($old_ra)."'");

	$res = OIDplus::db()->query("select id from ".OIDPLUS_TABLENAME_PREFIX."objects where parent = '".OIDplus::db()->real_escape_string($id)."' and ifnull(ra_email,'') = '".OIDplus::db()->real_escape_string($old_ra)."'");
	while ($row = OIDplus::db()->fetch_array($res)) {
		_ra_change_rec($row['id'], $old_ra, $new_ra);
	}
}
