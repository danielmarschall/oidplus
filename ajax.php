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
	OIDplus::db()->transaction_begin();
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

		$ra_logged_in = OIDplus::authUtils()->isRaLoggedIn($email);

		if (!OIDplus::authUtils()->isAdminLoggedIn() && !$ra_logged_in) {
			throw new Exception('Authentification error. Please log in.');
		}

		if ($ra_logged_in) OIDplus::authUtils()->raLogout($email);

		$ra = new OIDplusRA($email);
		$ra->delete();

		OIDplus::logger()->log("RA($email)?/A?", "RA '$email' deleted");

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
		if ($obj === null) throw new Exception("DELETE action failed because object '$id' cannot be parsed!");

		// Check if permitted
		if (!$obj->userHasParentalWriteRights()) throw new Exception('Authentification error. Please log in as the superior RA to delete this OID.');

		OIDplus::logger()->log("OID($id)+SUPOIDRA($id)?/A?", "Object '$id' (recursively) deleted");
		OIDplus::logger()->log("OIDRA($id)!", "Lost ownership of object '$id' because it was deleted");

		if ($parentObj = $obj->getParent()) {
			OIDplus::logger()->log("OID(".$parentObj->nodeId().")", "Object '$id' (recursively) deleted");
		}

		// Delete object
		OIDplus::db()->query("delete from ".OIDPLUS_TABLENAME_PREFIX."objects where id = ?", array($id));

		// Delete orphan stuff
		foreach (OIDplus::getEnabledObjectTypes() as $ot) {
			do {
				$res = OIDplus::db()->query("select tchild.id from ".OIDPLUS_TABLENAME_PREFIX."objects tchild " .
				                            "left join ".OIDPLUS_TABLENAME_PREFIX."objects tparent on tparent.id = tchild.parent " .
				                            "where tchild.parent <> ? and tchild.id like ? and tparent.id is null;", array($ot::root(), $ot::root().'%'));
				if ($res->num_rows() == 0) break; // we need to call num_rows() before fetch_array()   [Problem with ODBC/MsSQL]

				while ($row = $res->fetch_array()) {
					$id_to_delete = $row['id'];
					OIDplus::logger()->log("OIDRA($id_to_delete)!", "Lost ownership of object '$id_to_delete' because one of the superior objects ('$id') was recursively deleted");
					OIDplus::db()->query("delete from ".OIDPLUS_TABLENAME_PREFIX."objects where id = ?", array($id_to_delete));
				}
			} while (true);
		}
		OIDplus::db()->query("delete from ".OIDPLUS_TABLENAME_PREFIX."asn1id where well_known = '0' and oid not in (select id from ".OIDPLUS_TABLENAME_PREFIX."objects where id like 'oid:%')");
		OIDplus::db()->query("delete from ".OIDPLUS_TABLENAME_PREFIX."iri    where well_known = '0' and oid not in (select id from ".OIDPLUS_TABLENAME_PREFIX."objects where id like 'oid:%')");

		echo json_encode(array("status" => 0));
	}

	// Action:     Update
	// Method:     POST
	// Parameters: id, ra_email, comment, iris, asn1ids, confidential
	// Outputs:    Text
	if (isset($_POST["action"]) && ($_POST["action"] == "Update")) {
		$handled = true;

		$id = $_POST['id'];
		$obj = OIDplusObject::parse($id);
		if ($obj === null) throw new Exception("UPDATE action failed because object '$id' cannot be parsed!");

		// Check if permitted
		if (!$obj->userHasParentalWriteRights()) throw new Exception('Authentification error. Please log in as the superior RA to update this OID.');

		// Validate RA email address
		$new_ra = $_POST['ra_email'];
		if (!empty($new_ra) && !oidplus_valid_email($new_ra)) {
			throw new Exception('Invalid RA email address');
		}

		// First, do a simulation for ASN.1 IDs and IRIs to check if there are any problems (then an Exception will be thrown)
		if ($obj::ns() == 'oid') {
			$ids = ($_POST['iris'] == '') ? array() : explode(',',$_POST['iris']);
			$ids = array_map('trim',$ids);
			$obj->replaceIris($ids, true);

			$ids = ($_POST['asn1ids'] == '') ? array() : explode(',',$_POST['asn1ids']);
			$ids = array_map('trim',$ids);
			$obj->replaceAsn1Ids($ids, true);
		}

		// Change RA recursively
		$res = OIDplus::db()->query("select ra_email from ".OIDPLUS_TABLENAME_PREFIX."objects where id = ?", array($id));
		$row = $res->fetch_array();
		$current_ra = $row['ra_email'];
		if ($new_ra != $current_ra) {
			OIDplus::logger()->log("OID($id)+SUPOIDRA($id)?/A?", "RA of object '$id' changed from '$current_ra' to '$new_ra'");
			OIDplus::logger()->log("RA($current_ra)!",           "Lost ownership of object '$id' due to RA transfer of superior RA / admin.");
			OIDplus::logger()->log("RA($new_ra)!",               "Gained ownership of object '$id' due to RA transfer of superior RA / admin.");
			if ($parentObj = $obj->getParent()) {
				OIDplus::logger()->log("OID(".$parentObj->nodeId().")", "RA of object '$id' changed from '$current_ra' to '$new_ra'");
			}
			_ra_change_rec($id, $current_ra, $new_ra); // Inherited RAs rekursiv mitändern
		}

		// Log if confidentially flag was changed
		OIDplus::logger()->log("OID($id)+SUPOIDRA($id)?/A?", "Identifiers/Confidential flag of object '$id' updated"); // TODO: Check if they were ACTUALLY updated!
		if ($parentObj = $obj->getParent()) {
			OIDplus::logger()->log("OID(".$parentObj->nodeId().")", "Identifiers/Confidential flag of object '$id' updated"); // TODO: Check if they were ACTUALLY updated!
		}

		// Replace ASN.1 IDs und IRIs
		if ($obj::ns() == 'oid') {
			$ids = ($_POST['iris'] == '') ? array() : explode(',',$_POST['iris']);
			$ids = array_map('trim',$ids);
			$obj->replaceIris($ids, false);

			$ids = ($_POST['asn1ids'] == '') ? array() : explode(',',$_POST['asn1ids']);
			$ids = array_map('trim',$ids);
			$obj->replaceAsn1Ids($ids, false);

			// TODO: Check if any identifiers have been actually changed,
			// and log it to OID($id), OID($parent), ... (see above)
		}

		$confidential = $_POST['confidential'] == 'true';
		$comment = $_POST['comment'];
		if (OIDplus::db()->slang() == 'mssql') {
			OIDplus::db()->query("UPDATE ".OIDPLUS_TABLENAME_PREFIX."objects SET confidential = ?, comment = ?, updated = getdate() WHERE id = ?", array($confidential, $comment, $id));
		} else {
			// MySQL + PgSQL
			OIDplus::db()->query("UPDATE ".OIDPLUS_TABLENAME_PREFIX."objects SET confidential = ?, comment = ?, updated = now() WHERE id = ?", array($confidential, $comment, $id));
		}

		$status = 0;

		if (!empty($new_ra)) {
			$res = OIDplus::db()->query("select ra_name from ".OIDPLUS_TABLENAME_PREFIX."ra where email = ?", array($new_ra));
			if ($res->num_rows() == 0) $status = OIDplus::config()->getValue('ra_invitation_enabled') ? 1 : 2;
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
		if ($obj === null) throw new Exception("UPDATE2 action failed because object '$id' cannot be parsed!");

		// Check if allowed
		if (!$obj->userHasWriteRights()) throw new Exception('Authentification error. Please log in as the RA to update this OID.');

		OIDplus::logger()->log("OID($id)+OIDRA($id)?/A?", "Title/Description of object '$id' updated");

		if (OIDplus::db()->slang() == 'mssql') {
			OIDplus::db()->query("UPDATE ".OIDPLUS_TABLENAME_PREFIX."objects SET title = ?, description = ?, updated = getdate() WHERE id = ?", array($_POST['title'], $_POST['description'], $id));
		} else {
			// MySQL + PgSQL
			OIDplus::db()->query("UPDATE ".OIDPLUS_TABLENAME_PREFIX."objects SET title = ?, description = ?, updated = now() WHERE id = ?", array($_POST['title'], $_POST['description'], $id));
		}

		echo json_encode(array("status" => 0));
	}

	// Action:     Insert
	// Method:     POST
	// Parameters: parent, id, ra_email, confidential, iris, asn1ids
	// Outputs:    Text
	if (isset($_POST["action"]) && ($_POST["action"] == "Insert")) {
		$handled = true;

		// Validated are: ID, ra email, asn1 ids, iri ids

		// Check if you have write rights on the parent (to create a new object)
		$objParent = OIDplusObject::parse($_POST['parent']);
		if ($objParent === null) throw new Exception("INSERT action failed because parent object '".$_POST['parent']."' cannot be parsed!");
		if (!$objParent->userHasWriteRights()) throw new Exception('Authentification error. Please log in as the correct RA to insert an OID at this arc.');

		// Check if the ID is valid
		if ($_POST['id'] == '') throw new Exception('ID may not be empty');

		// Determine absolute OID name
		// Note: At addString() and parse(), the syntax of the ID will be checked
		$id = $objParent->addString($_POST['id']);

		// Check, if the OID exists
		$test = OIDplus::db()->query("select id from ".OIDPLUS_TABLENAME_PREFIX."objects where id = ?", array($id));
		if ($test->num_rows() >= 1) {
			throw new Exception("Object $id already exists!");
		}

		$obj = OIDplusObject::parse($id);
                if ($obj === null) throw new Exception("INSERT action failed because object '$id' cannot be parsed!");

		// First simulate if there are any problems of ASN.1 IDs und IRIs
		if ($obj::ns() == 'oid') {
			$ids = ($_POST['iris'] == '') ? array() : explode(',',$_POST['iris']);
			$ids = array_map('trim',$ids);
			$obj->replaceAsn1Ids($ids, true);

			$ids = ($_POST['asn1ids'] == '') ? array() : explode(',',$_POST['asn1ids']);
			$ids = array_map('trim',$ids);
			$obj->replaceIris($ids, true);
		}

		// Apply superior RA change
		$parent = $_POST['parent'];
		$ra_email = $_POST['ra_email'];
		if (!empty($ra_email) && !oidplus_valid_email($ra_email)) {
			throw new Exception('Invalid RA email address');
		}

		OIDplus::logger()->log("OID($parent)+OID($id)+OIDRA($parent)?/A?", "Object '$id' created, ".(empty($ra_email) ? "without defined RA" : "given to RA '$ra_email'")).", superior object is '$parent'";
		if (!empty($ra_email)) {
			OIDplus::logger()->log("RA($ra_email)!", "Gained ownership of newly created object '$id'");
		}

		$confidential = $_POST['confidential'] == 'true';
		$comment = $_POST['comment'];
		$title = '';
		$description = '';
		
		if (strlen($id) > OIDPLUS_MAX_ID_LENGTH) {
			throw new Exception("The identifier '$id' is too long (max allowed length: ".OIDPLUS_MAX_ID_LENGTH.")");
		}
	
		if (OIDplus::db()->slang() == 'mssql') {
			OIDplus::db()->query("INSERT INTO ".OIDPLUS_TABLENAME_PREFIX."objects (id, parent, ra_email, confidential, comment, created, title, description) VALUES (?, ?, ?, ?, ?, getdate(), ?, ?)", array($id, $parent, $ra_email, $confidential, $comment, $title, $description));
		} else {
			// MySQL + PgSQL
			OIDplus::db()->query("INSERT INTO ".OIDPLUS_TABLENAME_PREFIX."objects (id, parent, ra_email, confidential, comment, created, title, description) VALUES (?, ?, ?, ?, ?, now(), ?, ?)", array($id, $parent, $ra_email, $confidential, $comment, $title, $description));
		}

		// Set ASN.1 IDs und IRIs
		if ($obj::ns() == 'oid') {
			$ids = ($_POST['iris'] == '') ? array() : explode(',',$_POST['iris']);
			$ids = array_map('trim',$ids);
			$obj->replaceIris($ids, false);

			$ids = ($_POST['asn1ids'] == '') ? array() : explode(',',$_POST['asn1ids']);
			$ids = array_map('trim',$ids);
			$obj->replaceAsn1Ids($ids, false);
		}

		$status = 0;

		if (!empty($ra_email)) {
			// Do we need to notify that the RA does not exist?
			$res = OIDplus::db()->query("select ra_name from ".OIDPLUS_TABLENAME_PREFIX."ra where email = ?", array($ra_email));
			if ($res->num_rows() == 0) $status = OIDplus::config()->getValue('ra_invitation_enabled') ? 1 : 2;
		}

		echo json_encode(array("status" => $status));
	}

	if (!$handled) {
		throw new Exception('Invalid action ID');
	}

	OIDplus::db()->transaction_commit();
} catch (Exception $e) {
	try {
		OIDplus::db()->transaction_rollback();
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
	if (OIDplus::db()->slang() == 'mssql') {
		OIDplus::db()->query("update ".OIDPLUS_TABLENAME_PREFIX."objects set ra_email = ?, updated = getdate() where id = ? and ifnull(ra_email,'') = ?", array($new_ra, $id, $old_ra));
	} else {
		// MySQL + PgSQL
		OIDplus::db()->query("update ".OIDPLUS_TABLENAME_PREFIX."objects set ra_email = ?, updated = now() where id = ? and ifnull(ra_email,'') = ?", array($new_ra, $id, $old_ra));
	}

	$res = OIDplus::db()->query("select id from ".OIDPLUS_TABLENAME_PREFIX."objects where parent = ? and ifnull(ra_email,'') = ?", array($id, $old_ra));
	while ($row = $res->fetch_array()) {
		_ra_change_rec($row['id'], $old_ra, $new_ra);
	}
}
