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

if (!defined('IN_OIDPLUS')) die();

class OIDplusPagePublicObjects extends OIDplusPagePluginPublic {

	public static function getPluginInformation() {
		$out = array();
		$out['name'] = 'Objects';
		$out['author'] = 'ViaThinkSoft';
		$out['version'] = null;
		$out['descriptionHTML'] = null;
		return $out;
	}

	public function priority() {
		return 0;
	}

	public function action(&$handled) {

		// Action:     Delete
		// Method:     POST
		// Parameters: id
		// Outputs:    Text
		if (isset($_POST["action"]) && ($_POST["action"] == "Delete")) {
			$handled = true;

			$id = $_POST['id'];
			$obj = OIDplusObject::parse($id);
			if ($obj === null) throw new OIDplusException("DELETE action failed because object '$id' cannot be parsed!");

			if (OIDplus::db()->query("select id from ".OIDPLUS_TABLENAME_PREFIX."objects where id = ?", array($id))->num_rows() == 0) {
				throw new OIDplusException("Object '$id' does not exist");
			}

			// Check if permitted
			if (!$obj->userHasParentalWriteRights()) throw new OIDplusException('Authentification error. Please log in as the superior RA to delete this OID.');

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
					if ($res->num_rows() == 0) break;

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
			if ($obj === null) throw new OIDplusException("UPDATE action failed because object '$id' cannot be parsed!");

			if (OIDplus::db()->query("select id from ".OIDPLUS_TABLENAME_PREFIX."objects where id = ?", array($id))->num_rows() == 0) {
				throw new OIDplusException("Object '$id' does not exist");
			}

			// Check if permitted
			if (!$obj->userHasParentalWriteRights()) throw new OIDplusException('Authentification error. Please log in as the superior RA to update this OID.');

			// Validate RA email address
			$new_ra = $_POST['ra_email'];
			if (!empty($new_ra) && !OIDplus::mailUtils()->validMailAddress($new_ra)) {
				throw new OIDplusException('Invalid RA email address');
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
			if ($row = $res->fetch_array()) {
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
				if ($res->num_rows() == 0) $status = class_exists('OIDplusPageRaInvite') && OIDplus::config()->getValue('ra_invitation_enabled') ? 1 : 2;
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
			if ($obj === null) throw new OIDplusException("UPDATE2 action failed because object '$id' cannot be parsed!");

			if (OIDplus::db()->query("select id from ".OIDPLUS_TABLENAME_PREFIX."objects where id = ?", array($id))->num_rows() == 0) {
				throw new OIDplusException("Object '$id' does not exist");
			}

			// Check if allowed
			if (!$obj->userHasWriteRights()) throw new OIDplusException('Authentification error. Please log in as the RA to update this OID.');

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
			if ($objParent === null) throw new OIDplusException("INSERT action failed because parent object '".$_POST['parent']."' cannot be parsed!");

			if (!$objParent::root() && (OIDplus::db()->query("select id from ".OIDPLUS_TABLENAME_PREFIX."objects where id = ?", array($objParent->nodeId()))->num_rows() == 0)) {
				throw new OIDplusException("Parent object '".($objParent->nodeId())."' does not exist");
			}

			if (!$objParent->userHasWriteRights()) throw new OIDplusException('Authentification error. Please log in as the correct RA to insert an OID at this arc.');

			// Check if the ID is valid
			if ($_POST['id'] == '') throw new OIDplusException('ID may not be empty');

			// Determine absolute OID name
			// Note: At addString() and parse(), the syntax of the ID will be checked
			$id = $objParent->addString($_POST['id']);

			// Check, if the OID exists
			$test = OIDplus::db()->query("select id from ".OIDPLUS_TABLENAME_PREFIX."objects where id = ?", array($id));
			if ($test->num_rows() >= 1) {
				throw new OIDplusException("Object $id already exists!");
			}

			$obj = OIDplusObject::parse($id);
	                if ($obj === null) throw new OIDplusException("INSERT action failed because object '$id' cannot be parsed!");

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
			if (!empty($ra_email) && !OIDplus::mailUtils()->validMailAddress($ra_email)) {
				throw new OIDplusException('Invalid RA email address');
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
				throw new OIDplusException("The identifier '$id' is too long (max allowed length: ".OIDPLUS_MAX_ID_LENGTH.")");
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
				if ($res->num_rows() == 0) $status = class_exists('OIDplusPageRaInvite') && OIDplus::config()->getValue('ra_invitation_enabled') ? 1 : 2;
			}

			echo json_encode(array("status" => $status));
		}

	}

	public function init($html=true) {
	}

	public function cfgSetValue($name, $value) {
	}

	public function gui($id, &$out, &$handled) {
		if ($id === 'oidplus:system') {
			$handled = true;

			$out['title'] = OIDplus::config()->getValue('system_title'); // 'Object Database of ' . $_SERVER['SERVER_NAME'];
			$out['icon'] = OIDplus::webpath(__DIR__).'system_big.png';

			if (file_exists(__DIR__ . '/welcome.local.html')) {
				$out['text'] = file_get_contents(__DIR__ . '/welcome.local.html');
			} else if (file_exists(__DIR__ . '/welcome.html')) {
				$out['text'] = file_get_contents(__DIR__ . '/welcome.html');
			} else {
				$out['text'] = '';
			}

			if (strpos($out['text'], '%%OBJECT_TYPE_LIST%%') !== false) {
				$tmp = '<ul>';
				foreach (OIDplus::getEnabledObjectTypes() as $ot) {
					$tmp .= '<li><a '.OIDplus::gui()->link($ot::root()).'>'.htmlentities($ot::objectTypeTitle()).'</a></li>';
				}
				$tmp .= '</ul>';
				$out['text'] = str_replace('%%OBJECT_TYPE_LIST%%', $tmp, $out['text']);
			}

			return $out;
		}

		try {
			$obj = OIDplusObject::parse($id);
		} catch (Exception $e) {
			$obj = null;
		}

		if (!is_null($obj)) {
			$handled = true;

			if (!$obj->userHasReadRights()) {
				$out['title'] = 'Access denied';
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>Please <a '.OIDplus::gui()->link('oidplus:login').'>log in</a> to receive information about this object.</p>';
				return $out;
			}

			$parent = null;
			$res = null;
			$row = null;
			$matches_any_registered_type = false;
			foreach (OIDplus::getEnabledObjectTypes() as $ot) {
				if ($obj = $ot::parse($id)) {
					$matches_any_registered_type = true;
					if ($obj->isRoot()) {
						$obj->getContentPage($out['title'], $out['text'], $out['icon']);
						$parent = null; // $obj->getParent();
						break;
					} else {
						$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where id = ?", array($obj->nodeId()));
						if ($res->num_rows() == 0) {
							http_response_code(404);
							$out['title'] = 'Object not found';
							$out['icon'] = 'img/error_big.png';
							$out['text'] = 'The object <code>'.htmlentities($id).'</code> was not found in this database.';
							return $out;
						} else {
							$row = $res->fetch_array(); // will be used further down the code
							$obj->getContentPage($out['title'], $out['text'], $out['icon']);
							if (empty($out['title'])) $out['title'] = explode(':',$id,2)[1];
							$parent = $obj->getParent();
							break;
						}
					}
				}
			}
			if (!$matches_any_registered_type) {
				http_response_code(404);
				$out['title'] = 'Object not found';
				$out['icon'] = 'img/error_big.png';
				$out['text'] = 'The object <code>'.htmlentities($id).'</code> was not found in this database.';
				return $out;
			}

			// ---

			if ($parent) {
				if ($parent->isRoot()) {

					$parent_link_text = $parent->objectTypeTitle();
					$out['text'] = '<p><a '.OIDplus::gui()->link($parent->root()).'><img src="img/arrow_back.png" width="16"> Parent node: '.htmlentities($parent_link_text).'</a></p>' . $out['text'];

				} else {
					$res_ = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where id = ?", array($parent->nodeId()));
					if ($res_->num_rows() > 0) {
						$row_ = $res_->fetch_array();

						$parent_title = $row_['title'];
						if (empty($parent_title) && ($parent->ns() == 'oid')) {
							// If not title is available, then use an ASN.1 identifier
							$res_ = OIDplus::db()->query("select name from ".OIDPLUS_TABLENAME_PREFIX."asn1id where oid = ?", array($parent->nodeId()));
							if ($res_->num_rows() > 0) {
								$row_ = $res_->fetch_array();
								$parent_title = $row_['name']; // TODO: multiple ASN1 ids?
							}
						}

						$parent_link_text = empty($parent_title) ? explode(':',$parent->nodeId())[1] : $parent_title.' ('.explode(':',$parent->nodeId())[1].')';

						$out['text'] = '<p><a '.OIDplus::gui()->link($parent->nodeId()).'><img src="img/arrow_back.png" width="16"> Parent node: '.htmlentities($parent_link_text).'</a></p>' . $out['text'];
					} else {
						$out['text'] = '';
					}
				}
			} else {
				$parent_link_text = 'Go back to front page';
				$out['text'] = '<p><a '.OIDplus::gui()->link('oidplus:system').'><img src="img/arrow_back.png" width="16"> '.htmlentities($parent_link_text).'</a></p>' . $out['text'];
			}

			// ---

			if (!is_null($row) && isset($row['description'])) {
				if (empty($row['description'])) {
					if (empty($row['title'])) {
						$desc = '<p><i>No description for this object available</i></p>';
					} else {
						$desc = $row['title'];
					}
				} else {
					$desc = self::objDescription($row['description']);
				}

				if ($obj->userHasWriteRights()) {
					$rand = ++self::$crudCounter;
					$desc = '<noscript><p><b>You need to enable JavaScript to edit title or description of this object.</b></p>'.$desc.'</noscript>';
					$desc .= '<div class="container box" style="display:none" id="descbox_'.$rand.'">';
					$desc .= 'Title: <input type="text" name="title" id="titleedit" value="'.htmlentities($row['title']).'"><br><br>Description:<br>';
					$desc .= self::showMCE('description', $row['description']);
					$desc .= '<button type="button" name="update_desc" id="update_desc" class="btn btn-success btn-xs update" onclick="updateDesc()">Update description</button>';
					$desc .= '</div>';
					$desc .= '<script>document.getElementById("descbox_'.$rand.'").style.display = "block";</script>';
				}
			} else {
				$desc = '';
			}

			// ---

			if (strpos($out['text'], '%%DESC%%') !== false)
				$out['text'] = str_replace('%%DESC%%',    $desc,                              $out['text']);
			if (strpos($out['text'], '%%CRUD%%') !== false)
				$out['text'] = str_replace('%%CRUD%%',    self::showCrud($id),                $out['text']);
			if (strpos($out['text'], '%%RA_INFO%%') !== false)
				$out['text'] = str_replace('%%RA_INFO%%', OIDplusPagePublicRaInfo::showRaInfo($row['ra_email']), $out['text']);

			$alt_ids = $obj->getAltIds();
			if (count($alt_ids) > 0) {
				$out['text'] .= "<h2>Alternative Identifiers</h2>";
				foreach ($alt_ids as $alt_id) {
					$ns = $alt_id->getNamespace();
					$aid = $alt_id->getId();
					$aiddesc = $alt_id->getDescription();
					$out['text'] .= "$aiddesc <code>$ns:$aid</code><br>";
				}
			}

			foreach (OIDplus::getPagePlugins('public') as $plugin) $plugin->modifyContent($id, $out['title'], $out['icon'], $out['text']);
			foreach (OIDplus::getPagePlugins('ra')     as $plugin) $plugin->modifyContent($id, $out['title'], $out['icon'], $out['text']);
			foreach (OIDplus::getPagePlugins('admin')  as $plugin) $plugin->modifyContent($id, $out['title'], $out['icon'], $out['text']);
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if ($nonjs) {
			$json[] = array('id' => 'oidplus:system', 'icon' => OIDplus::webpath(__DIR__).'system.png', 'text' => 'System');

			$parent = '';
			$res = OIDplus::db()->query("select parent from ".OIDPLUS_TABLENAME_PREFIX."objects where id = ?", array($req_goto));
			while ($row = $res->fetch_object()) {
				$parent = $row->parent;
			}

			$objTypesChildren = array();
			foreach (OIDplus::getEnabledObjectTypes() as $ot) {
				$icon = 'plugins/objectTypes/'.$ot::ns().'/img/treeicon_root.png';
				$json[] = array('id' => $ot::root(), 'icon' => $icon, 'text' => $ot::objectTypeTitle());

				try {
					$tmp = OIDplusObject::parse($req_goto);
				} catch (Exception $e) {
					$tmp = null;
				}
				if (!is_null($tmp) && ($ot == get_class($tmp))) {
					// TODO: Instead of just having 3 levels (parent, this and children), it would be better if we'd had a full tree of all parents
					//       on the other hand, for giving search engines content, this is good enough
					if (empty($parent)) {
						$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where " .
										   "parent = ? or " .
										   "id = ? " .
										   "order by ".OIDplus::db()->natOrder('id'), array($req_goto, $req_goto));
					} else {
						$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where " .
										   "parent = ? or " .
										   "id = ? or " .
										   "id = ? ".
										   "order by ".OIDplus::db()->natOrder('id'), array($req_goto, $req_goto, $parent));
					}

					$z_used = 0;
					$y_used = 0;
					$x_used = 0;
					$stufe = 0;
					$menu_entries = array();
					$stufen = array();
					while ($row = $res->fetch_object()) {
						$obj = OIDplusObject::parse($row->id);
						if (is_null($obj)) continue; // might happen if the objectType is not available/loaded
						if (!$obj->userHasReadRights()) continue;
						$txt = $row->title == '' ? '' : ' -- '.htmlentities($row->title);

						if ($row->id == $parent) { $stufe=0; $z_used++; }
						if ($row->id == $req_goto) { $stufe=1; $y_used++; }
						if ($row->parent == $req_goto) { $stufe=2; $x_used++; }

						$menu_entry = array('id' => $row->id, 'icon' => '', 'text' => $txt, 'indent' => 0);
						$menu_entries[] = $menu_entry;
						$stufen[] = $stufe;
					}
					if ($x_used) foreach ($menu_entries as $i => &$menu_entry) if ($stufen[$i] >= 2) $menu_entry['indent'] += 1;
					if ($y_used) foreach ($menu_entries as $i => &$menu_entry) if ($stufen[$i] >= 1) $menu_entry['indent'] += 1;
					if ($z_used) foreach ($menu_entries as $i => &$menu_entry) if ($stufen[$i] >= 0) $menu_entry['indent'] += 1;
					$json = array_merge($json, $menu_entries);
				}
			}

			return true;
		} else {
			if (isset($req_goto)) {
				$goto = $req_goto;
				$path = array();
				while (true) {
					$path[] = $goto;
					$res = OIDplus::db()->query("select parent from ".OIDPLUS_TABLENAME_PREFIX."objects where id = ?", array($goto));
					if ($res->num_rows() == 0) break;
					$row = $res->fetch_array();
					$goto = $row['parent'];
					if ($goto == '') continue;
				}

				$goto_path = array_reverse($path);
			} else {
				$goto_path = null;
			}

			$objTypesChildren = array();
			foreach (OIDplus::getEnabledObjectTypes() as $ot) {
				$child = array('id' => $ot::root(),
				               'text' => $ot::objectTypeTitle(),
				               'state' => array("opened" => true),
				               'icon' => 'plugins/objectTypes/'.$ot::ns().'/img/treeicon_root.png',
				               'children' => OIDplus::menuUtils()->tree_populate($ot::root(), $goto_path)
				               );
				if (!file_exists($child['icon'])) $child['icon'] = null; // default icon (folder)
				$objTypesChildren[] = $child;
			}

			$json[] = array(
				'id' => "oidplus:system",
				'text' => "Objects",
				'state' => array(
					"opened" => true,
					// "selected" => true)  // "selected" ist buggy: 1) Das select-Event wird beim Laden nicht gefeuert 2) Die direkt untergeordneten Knoten lassen sich nicht öffnen (laden für ewig)
				),
				'icon' => OIDplus::webpath(__DIR__).'system.png',
				'children' => $objTypesChildren
			);

			return true;
		}
	}

	public function tree_search($request) {
		$ary = array();
		if ($obj = OIDplusObject::parse($request)) {
			if ($obj->userHasReadRights()) {
				do {
					$ary[] = $obj->nodeId();
				} while ($obj = $obj->getParent());
				$ary = array_reverse($ary);
			}
		}
		return $ary;
	}

	private static $crudCounter = 0;

	protected static function showCrud($parent='oid:') {
		$items_total = 0;
		$items_hidden = 0;

		$objParent = OIDplusObject::parse($parent);
		$parentNS = $objParent::ns();

		$result = OIDplus::db()->query("select o.*, r.ra_name " .
		                               "from ".OIDPLUS_TABLENAME_PREFIX."objects o " .
		                               "left join ".OIDPLUS_TABLENAME_PREFIX."ra r on r.email = o.ra_email " .
		                               "where parent = ? " .
		                               "order by ".OIDplus::db()->natOrder('id'), array($parent));
		$rows = array();
		if ($parentNS == 'oid') {
			$one_weid_available = $objParent->isWeid(true);
			while ($row = $result->fetch_object()) {
				$obj = OIDplusObject::parse($row->id);
				$rows[] = array($obj,$row);
				if (!$one_weid_available) {
					if ($obj->isWeid(true)) $one_weid_available = true;
				}
			}
		} else {
			$one_weid_available = false;
			while ($row = $result->fetch_object()) {
				$obj = OIDplusObject::parse($row->id);
				$rows[] = array($obj,$row);
			}
		}

		$output = '';
		$output .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
		$output .= '<table class="table table-bordered table-striped">';
		$output .= '	<tr>';
		$output .= '	     <th>ID'.(($parentNS == 'gs1') ? ' (without check digit)' : '').'</th>';
		if ($parentNS == 'oid') {
			if ($one_weid_available) $output .= '	     <th>WEID</th>';
			$output .= '	     <th>ASN.1 IDs (comma sep.)</th>';
			$output .= '	     <th>IRI IDs (comma sep.)</th>';
		}
		$output .= '	     <th>RA</th>';
		$output .= '	     <th>Comment</th>';
		if ($objParent->userHasWriteRights()) {
			$output .= '	     <th>Hide</th>';
			$output .= '	     <th>Update</th>';
			$output .= '	     <th>Delete</th>';
		}
		$output .= '	     <th>Created</th>';
		$output .= '	     <th>Updated</th>';
		$output .= '	</tr>';

		foreach ($rows as list($obj,$row)) {
			$items_total++;
			if (!$obj->userHasReadRights()) {
				$items_hidden++;
				continue;
			}

			$show_id = $obj->crudShowId($objParent);

			$asn1ids = array();
			$res2 = OIDplus::db()->query("select name from ".OIDPLUS_TABLENAME_PREFIX."asn1id where oid = ? order by lfd", array($row->id));
			while ($row2 = $res2->fetch_array()) {
				$asn1ids[] = $row2['name'];
			}

			$iris = array();
			$res2 = OIDplus::db()->query("select name from ".OIDPLUS_TABLENAME_PREFIX."iri where oid = ? order by lfd", array($row->id));
			while ($row2 = $res2->fetch_array()) {
				$iris[] = $row2['name'];
			}

			$date_created = explode(' ', $row->created)[0] == '0000-00-00' ? '' : explode(' ', $row->created)[0];
			$date_updated = explode(' ', $row->updated)[0] == '0000-00-00' ? '' : explode(' ', $row->updated)[0];

			$output .= '<tr>';
			$output .= '     <td><a href="?goto='.urlencode($row->id).'" onclick="openAndSelectNode('.js_escape($row->id).', '.js_escape($parent).'); return false;">'.htmlentities($show_id).'</a></td>';
			if ($objParent->userHasWriteRights()) {
				if ($parentNS == 'oid') {
					if ($one_weid_available) {
						if ($obj->isWeid(false)) {
							$output .= '	<td>'.$obj->weidArc().'</td>';
						} else {
							$output .= '	<td>n/a</td>';
						}
					}
					$output .= '     <td><input type="text" id="asn1ids_'.$row->id.'" value="'.implode(', ', $asn1ids).'"></td>';
					$output .= '     <td><input type="text" id="iris_'.$row->id.'" value="'.implode(', ', $iris).'"></td>';
				}
				$output .= '     <td><input type="text" id="ra_email_'.$row->id.'" value="'.htmlentities($row->ra_email).'"></td>';
				$output .= '     <td><input type="text" id="comment_'.$row->id.'" value="'.htmlentities($row->comment).'"></td>';
				$output .= '     <td><input type="checkbox" id="hide_'.$row->id.'" '.($row->confidential ? 'checked' : '').'></td>';
				$output .= '     <td><button type="button" name="update_'.$row->id.'" id="update_'.$row->id.'" class="btn btn-success btn-xs update" onclick="crudActionUpdate('.js_escape($row->id).', '.js_escape($parent).')">Update</button></td>';
				$output .= '     <td><button type="button" name="delete_'.$row->id.'" id="delete_'.$row->id.'" class="btn btn-danger btn-xs delete" onclick="crudActionDelete('.js_escape($row->id).', '.js_escape($parent).')">Delete</button></td>';
				$output .= '     <td>'.$date_created.'</td>';
				$output .= '     <td>'.$date_updated.'</td>';
			} else {
				if ($asn1ids == '') $asn1ids = '<i>(none)</i>';
				if ($iris == '') $iris = '<i>(none)</i>';
				if ($parentNS == 'oid') {
					if ($one_weid_available) {
						if ($obj->isWeid(false)) {
							$output .= '	<td>'.$obj->weidArc().'</td>';
						} else {
							$output .= '	<td>n/a</td>';
						}
					}
					$asn1ids_ext = array();
					foreach ($asn1ids as $asn1id) {
						$asn1ids_ext[] = '<a href="?goto='.urlencode($row->id).'" onclick="openAndSelectNode('.js_escape($row->id).', '.js_escape($parent).'); return false;">'.$asn1id.'</a>';
					}
					$output .= '     <td>'.implode(', ', $asn1ids_ext).'</td>';
					$output .= '     <td>'.implode(', ', $iris).'</td>';
				}
				$output .= '     <td><a '.OIDplus::gui()->link('oidplus:rainfo$'.str_replace('@','&',$row->ra_email)).'>'.htmlentities(empty($row->ra_name) ? str_replace('@','&',$row->ra_email) : $row->ra_name).'</a></td>';
				$output .= '     <td>'.htmlentities($row->comment).'</td>';
				$output .= '     <td>'.$date_created.'</td>';
				$output .= '     <td>'.$date_updated.'</td>';
			}
			$output .= '</tr>';
		}

		$result = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where id = ?", array($parent));
		$parent_ra_email = $result->num_rows() > 0 ? $result->fetch_object()->ra_email : '';

		if ($objParent->userHasWriteRights()) {
			$output .= '<tr>';
			$prefix = is_null($objParent) ? '' : $objParent->crudInsertPrefix();
			if ($parentNS == 'oid') {
				if ($objParent->isWeid(true)) {
					$output .= '     <td>'.$prefix.' <input oninput="frdl_oidid_change()" type="text" id="id" value="" style="width:100%;min-width:100px"></td>'; // TODO: idee classname vergeben, z.B. "OID" und dann mit einem oid-spezifischen css die breite einstellbar machen, somit hat das plugin mehr kontrolle über das aussehen und die mindestbreiten
					$output .= '     <td><input type="text" name="weid" id="weid" value="" oninput="frdl_weid_change()"></td>';
				} else {
					$output .= '     <td>'.$prefix.' <input type="text" id="id" value="" style="width:100%;min-width:50px"></td>'; // TODO: idee classname vergeben, z.B. "OID" und dann mit einem oid-spezifischen css die breite einstellbar machen, somit hat das plugin mehr kontrolle über das aussehen und die mindestbreiten
					if ($one_weid_available) $output .= '     <td></td>'; // WEID-editor not available for root nodes. Do it manually, please
				}
			} else {
				$output .= '     <td>'.$prefix.' <input type="text" id="id" value=""></td>';
			}
			if ($parentNS == 'oid') $output .= '     <td><input type="text" id="asn1ids" value=""></td>';
			if ($parentNS == 'oid') $output .= '     <td><input type="text" id="iris" value=""></td>';
			$output .= '     <td><input type="text" id="ra_email" value="'.htmlentities($parent_ra_email).'"></td>';
			$output .= '     <td><input type="text" id="comment" value=""></td>';
			$output .= '     <td><input type="checkbox" id="hide"></td>';
			$output .= '     <td><button type="button" name="insert" id="insert" class="btn btn-success btn-xs update" onclick="crudActionInsert('.js_escape($parent).')">Insert</button></td>';
			$output .= '     <td></td>';
			$output .= '     <td></td>';
			$output .= '     <td></td>';
			$output .= '</tr>';
		} else {
			if ($items_total-$items_hidden == 0) {
				$cols = ($parentNS == 'oid') ? 7 : 5;
				if ($one_weid_available) $cols++;
				$output .= '<tr><td colspan="'.$cols.'">No items available</td></tr>';
			}
		}

		$output .= '</table>';
		$output .= '</div></div>';

		if ($items_hidden == 1) {
			$output .= '<p>'.$items_hidden.' item is hidden. Please <a '.OIDplus::gui()->link('oidplus:login').'>log in</a> to see it.</p>';
		} else if ($items_hidden > 1) {
			$output .= '<p>'.$items_hidden.' items are hidden. Please <a '.OIDplus::gui()->link('oidplus:login').'>log in</a> to see them.</p>';
		}

		return $output;
	}

	protected static function objDescription($html) {
		// We allow HTML, but no hacking
		$html = anti_xss($html);

		return trim_br($html);
	}

	// 'quickbars' added 11 July 2019: Disabled because of two problems:
	//                                 1. When you load TinyMCE via AJAX using the left menu, the quickbar is immediately shown, even if TinyMCE does not have the focus
	//                                 2. When you load a page without TinyMCE using the left menu, the quickbar is still visible, although there is no edit
	// 'colorpicker', 'textcolor' and 'contextmenu' added in 07 April 2020, because it is built in in the core.
	public static $exclude_tinymce_plugins = array('fullpage', 'bbcode', 'quickbars', 'colorpicker', 'textcolor', 'contextmenu');

	protected static function showMCE($name, $content) {
		$mce_plugins = array();
		foreach (glob(__DIR__ . '/../../3p/tinymce/plugins/*') as $m) { // */
			$mce_plugins[] = basename($m);
		}

		foreach (self::$exclude_tinymce_plugins as $exclude) {
			$index = array_search($exclude, $mce_plugins);
			if ($index !== false) unset($mce_plugins[$index]);
		}

		$out = '<script>
				tinymce.remove("#'.$name.'");
				tinymce.EditorManager.baseURL = "3p/tinymce";
				tinymce.init({
					document_base_url: "'.OIDplus::getSystemUrl().'",
					selector: "#'.$name.'",
					height: 200,
					statusbar: false,
//					menubar:false,
//					toolbar: "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table | fontsizeselect",
					toolbar: "undo redo | styleselect | bold italic underline forecolor | bullist numlist | outdent indent | table | fontsizeselect",
					plugins: "'.implode(' ', $mce_plugins).'",
					mobile: {
						theme: "mobile",
						toolbar: "undo redo | styleselect | bold italic underline forecolor | bullist numlist | outdent indent | table | fontsizeselect",
						plugins: "'.implode(' ', $mce_plugins).'"
					}

				});
			</script>';

		$content = htmlentities($content); // For some reason, if we want to display the text "<xyz>" in TinyMCE, we need to double-encode things! &lt; will not be accepted, we need &amp;lt; ... why?

		$out .= '<textarea name="'.htmlentities($name).'" id="'.htmlentities($name).'">'.trim($content).'</textarea><br>';

		return $out;
	}

}
