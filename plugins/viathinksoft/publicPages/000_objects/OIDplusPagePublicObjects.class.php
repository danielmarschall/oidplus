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

if (!defined('INSIDE_OIDPLUS')) die();

class OIDplusPagePublicObjects extends OIDplusPagePluginPublic {

	private function get_treeicon_root($ot) {
		$dirs = glob(OIDplus::localpath().'plugins/'.'*'.'/objectTypes/'.$ot::ns());

		if (count($dirs) == 0) {
			$icon = null;
		} else {
			$dir = $dirs[0];
			$icon_name = $ot::treeIconFilename('root');
			if (!$icon_name) return null;
			$icon = $dir.'/'.$icon_name;
			if (!file_exists($icon)) return null;
			$icon = substr($icon, strlen(OIDplus::localpath()));
		}

		return $icon;
	}

	private function ra_change_rec($id, $old_ra, $new_ra) {
		if (is_null($old_ra)) $old_ra = '';
		OIDplus::db()->query("update ###objects set ra_email = ?, updated = ".OIDplus::db()->sqlDate()." where id = ? and ".OIDplus::db()->getSlang()->isNullFunction('ra_email',"''")." = ?", array($new_ra, $id, $old_ra));

		$res = OIDplus::db()->query("select id from ###objects where parent = ? and ".OIDplus::db()->getSlang()->isNullFunction('ra_email',"''")." = ?", array($id, $old_ra));
		while ($row = $res->fetch_array()) {
			$this->ra_change_rec($row['id'], $old_ra, $new_ra);
		}
	}

	public function action($actionID, $params) {

		// Action:     Delete
		// Method:     POST
		// Parameters: id
		// Outputs:    <0 Error, =0 Success
		if ($actionID == 'Delete') {
			_CheckParamExists($params, 'id');
			$id = $params['id'];
			$obj = OIDplusObject::parse($id);
			if ($obj === null) throw new OIDplusException(_L('%1 action failed because object "%2" cannot be parsed!','DELETE',$id));

			if (!OIDplus::db()->query("select id from ###objects where id = ?", array($id))->any()) {
				throw new OIDplusException(_L('Object %1 does not exist',$id));
			}

			// Check if permitted
			if (!$obj->userHasParentalWriteRights()) throw new OIDplusException(_L('Authentication error. Please log in as the superior RA to delete this OID.'));

			foreach (OIDplus::getPagePlugins() as $plugin) {
				if ($plugin->implementsFeature('1.3.6.1.4.1.37476.2.5.2.3.3')) {
					$plugin->beforeObjectDelete($id);
				}
			}

			OIDplus::logger()->log("[WARN]OID($id)+[?WARN/!OK]SUPOIDRA($id)?/[?INFO/!OK]A?", "Object '$id' (recursively) deleted");
			OIDplus::logger()->log("[CRIT]OIDRA($id)!", "Lost ownership of object '$id' because it was deleted");

			if ($parentObj = $obj->getParent()) {
				$parent_oid = $parentObj->nodeId();
				OIDplus::logger()->log("[WARN]OID($parent_oid)", "Object '$id' (recursively) deleted");
			}

			// Delete object
			OIDplus::db()->query("delete from ###objects where id = ?", array($id));

			// Delete orphan stuff
			foreach (OIDplus::getEnabledObjectTypes() as $ot) {
				do {
					$res = OIDplus::db()->query("select tchild.id from ###objects tchild " .
					                            "left join ###objects tparent on tparent.id = tchild.parent " .
					                            "where tchild.parent <> ? and tchild.id like ? and tparent.id is null;", array($ot::root(), $ot::root().'%'));
					if (!$res->any()) break;

					while ($row = $res->fetch_array()) {
						$id_to_delete = $row['id'];
						OIDplus::logger()->log("[CRIT]OIDRA($id_to_delete)!", "Lost ownership of object '$id_to_delete' because one of the superior objects ('$id') was recursively deleted");
						OIDplus::db()->query("delete from ###objects where id = ?", array($id_to_delete));
					}
				} while (true);
			}
			OIDplus::db()->query("delete from ###asn1id where well_known = ? and oid not in (select id from ###objects where id like 'oid:%')", array(false));
			OIDplus::db()->query("delete from ###iri    where well_known = ? and oid not in (select id from ###objects where id like 'oid:%')", array(false));

			foreach (OIDplus::getPagePlugins() as $plugin) {
				if ($plugin->implementsFeature('1.3.6.1.4.1.37476.2.5.2.3.3')) {
					$plugin->afterObjectDelete($id);
				}
			}

			return array("status" => 0);
		}

		// Action:     Update
		// Method:     POST
		// Parameters: id, ra_email, comment, iris, asn1ids, confidential
		// Outputs:    <0 Error, =0 Success, with following bitfields for further information:
		//             1 = RA is not registered
		//             2 = RA is not registered, but it cannot be invited
		//             4 = OID is a well-known OID, so RA, ASN.1 and IRI identifiers were reset
		else if ($actionID == 'Update') {
			_CheckParamExists($params, 'id');
			$id = $params['id'];
			$obj = OIDplusObject::parse($id);
			if ($obj === null) throw new OIDplusException(_L('%1 action failed because object "%2" cannot be parsed!','UPDATE',$id));

			if (!OIDplus::db()->query("select id from ###objects where id = ?", array($id))->any()) {
				throw new OIDplusException(_L('Object %1 does not exist',$id));
			}

			// Check if permitted
			if (!$obj->userHasParentalWriteRights()) throw new OIDplusException(_L('Authentication error. Please log in as the superior RA to update this OID.'));

			foreach (OIDplus::getPagePlugins() as $plugin) {
				if ($plugin->implementsFeature('1.3.6.1.4.1.37476.2.5.2.3.3')) {
					$plugin->beforeObjectUpdateSuperior($id, $params);
				}
			}

			// First, do a simulation for ASN.1 IDs and IRIs to check if there are any problems (then an Exception will be thrown)
			if ($obj::ns() == 'oid') {
				if (!$obj->isWellKnown()) {
					if (isset($params['iris'])) {
						$ids = ($params['iris'] == '') ? array() : explode(',',$params['iris']);
						$ids = array_map('trim',$ids);
						$obj->replaceIris($ids, true);
					}

					if (isset($params['asn1ids'])) {
						$ids = ($params['asn1ids'] == '') ? array() : explode(',',$params['asn1ids']);
						$ids = array_map('trim',$ids);
						$obj->replaceAsn1Ids($ids, true);
					}
				}
			}

			// RA E-Mail change
			if (isset($params['ra_email'])) {
				// Validate RA email address
				$new_ra = $params['ra_email'];
				if ($obj::ns() == 'oid') {
					if ($obj->isWellKnown()) {
						$new_ra = '';
					}
				}
				if (!empty($new_ra) && !OIDplus::mailUtils()->validMailAddress($new_ra)) {
					throw new OIDplusException(_L('Invalid RA email address'));
				}

				// Change RA recursively
				$res = OIDplus::db()->query("select ra_email from ###objects where id = ?", array($id));
				if ($row = $res->fetch_array()) {
					$current_ra = $row['ra_email'];
					if ($new_ra != $current_ra) {
						OIDplus::logger()->log("[INFO]OID($id)+[?INFO/!OK]SUPOIDRA($id)?/[?INFO/!OK]A?", "RA of object '$id' changed from '$current_ra' to '$new_ra'");
						OIDplus::logger()->log("[WARN]RA($current_ra)!",           "Lost ownership of object '$id' due to RA transfer of superior RA / admin.");
						OIDplus::logger()->log("[INFO]RA($new_ra)!",               "Gained ownership of object '$id' due to RA transfer of superior RA / admin.");
						if ($parentObj = $obj->getParent()) {
							$parent_oid = $parentObj->nodeId();
							OIDplus::logger()->log("[INFO]OID($parent_oid)", "RA of object '$id' changed from '$current_ra' to '$new_ra'");
						}
						$this->ra_change_rec($id, $current_ra, $new_ra); // Recursively change inherited RAs
					}
				}
			}

			// Log if confidentially flag was changed
			OIDplus::logger()->log("[INFO]OID($id)+[?INFO/!OK]SUPOIDRA($id)?/[?INFO/!OK]A?", "Identifiers/Confidential flag of object '$id' updated"); // TODO: Check if they were ACTUALLY updated!
			if ($parentObj = $obj->getParent()) {
				$parent_oid = $parentObj->nodeId();
				OIDplus::logger()->log("[INFO]OID($parent_oid)", "Identifiers/Confidential flag of object '$id' updated"); // TODO: Check if they were ACTUALLY updated!
			}

			// Replace ASN.1 IDs und IRIs
			if ($obj::ns() == 'oid') {
				if (!$obj->isWellKnown()) {
					if (isset($params['iris'])) {
						$ids = ($params['iris'] == '') ? array() : explode(',',$params['iris']);
						$ids = array_map('trim',$ids);
						$obj->replaceIris($ids, false);
					}

					if (isset($params['asn1ids'])) {
						$ids = ($params['asn1ids'] == '') ? array() : explode(',',$params['asn1ids']);
						$ids = array_map('trim',$ids);
						$obj->replaceAsn1Ids($ids, false);
					}
				}

				// TODO: Check if any identifiers have been actually changed,
				// and log it to OID($id), OID($parent), ... (see above)
			}

			if (isset($params['confidential'])) {
				$confidential = $params['confidential'] == 'true';
				OIDplus::db()->query("UPDATE ###objects SET confidential = ? WHERE id = ?", array($confidential, $id));
			}

			if (isset($params['comment'])) {
				$comment = $params['comment'];
				OIDplus::db()->query("UPDATE ###objects SET comment = ? WHERE id = ?", array($comment, $id));
			}

			OIDplus::db()->query("UPDATE ###objects SET updated = ".OIDplus::db()->sqlDate()." WHERE id = ?", array($id));

			$status = 0;

			if (!empty($new_ra)) {
				$res = OIDplus::db()->query("select ra_name from ###ra where email = ?", array($new_ra));
				$invitePlugin = OIDplus::getPluginByOid('1.3.6.1.4.1.37476.2.5.2.4.2.92'); // OIDplusPageRaInvite
				if (!$res->any()) $status = !is_null($invitePlugin) && OIDplus::config()->getValue('ra_invitation_enabled') ? 1 : 2;
			}

			if ($obj::ns() == 'oid') {
				if ($obj->isWellKnown()) {
					$status += 4;
				}
			}

			foreach (OIDplus::getPagePlugins() as $plugin) {
				if ($plugin->implementsFeature('1.3.6.1.4.1.37476.2.5.2.3.3')) {
					$plugin->afterObjectUpdateSuperior($id, $params);
				}
			}

			return array("status" => $status);
		}

		// Action:     Update2
		// Method:     POST
		// Parameters: id, title, description
		// Outputs:    <0 Error, =0 Success
		else if ($actionID == 'Update2') {
			_CheckParamExists($params, 'id');
			$id = $params['id'];
			$obj = OIDplusObject::parse($id);
			if ($obj === null) throw new OIDplusException(_L('%1 action failed because object "%2" cannot be parsed!','UPDATE2',$id));

			if (!OIDplus::db()->query("select id from ###objects where id = ?", array($id))->any()) {
				throw new OIDplusException(_L('Object %1 does not exist',$id));
			}

			// Check if allowed
			if (!$obj->userHasWriteRights()) throw new OIDplusException(_L('Authentication error. Please log in as the RA to update this OID.'));

			foreach (OIDplus::getPagePlugins() as $plugin) {
				if ($plugin->implementsFeature('1.3.6.1.4.1.37476.2.5.2.3.3')) {
					$plugin->beforeObjectUpdateSelf($id, $params);
				}
			}

			OIDplus::logger()->log("[INFO]OID($id)+[?INFO/!OK]OIDRA($id)?/[?INFO/!OK]A?", "Title/Description of object '$id' updated");

			if (isset($params['title'])) {
				$title = $params['title'];
				OIDplus::db()->query("UPDATE ###objects SET title = ? WHERE id = ?", array($title, $id));
			}

			if (isset($params['description'])) {
				$description = $params['description'];
				OIDplus::db()->query("UPDATE ###objects SET description = ? WHERE id = ?", array($description, $id));
			}

			OIDplus::db()->query("UPDATE ###objects SET updated = ".OIDplus::db()->sqlDate()." WHERE id = ?", array($id));

			foreach (OIDplus::getPagePlugins() as $plugin) {
				if ($plugin->implementsFeature('1.3.6.1.4.1.37476.2.5.2.3.3')) {
					$plugin->afterObjectUpdateSelf($id, $params);
				}
			}

			return array("status" => 0);
		}

		// Generate UUID
		else if ($actionID == 'generate_uuid') {
			$uuid = gen_uuid();
			if (!$uuid) return array("status" => 1);
			return array(
				"status" => 0,
				"uuid" => $uuid,
				"intval" => substr(uuid_to_oid($uuid),strlen('2.25.'))
			);
		}

		// Action:     Insert
		// Method:     POST
		// Parameters: parent, id, ra_email, confidential, iris, asn1ids
		// Outputs:    status=<0 Error, =0 Success, with following bitfields for further information:
		//             1 = RA is not registered
		//             2 = RA is not registered, but it cannot be invited
		//             4 = OID is a well-known OID, so RA, ASN.1 and IRI identifiers were reset
		else if ($actionID == 'Insert') {
			// Check if you have write rights on the parent (to create a new object)
			_CheckParamExists($params, 'parent');
			$objParent = OIDplusObject::parse($params['parent']);
			if ($objParent === null) throw new OIDplusException(_L('%1 action failed because parent object "%2" cannot be parsed!','INSERT',$params['parent']));

			if (!$objParent->isRoot() && (!OIDplus::db()->query("select id from ###objects where id = ?", array($objParent->nodeId()))->any())) {
				throw new OIDplusException(_L('Parent object %1 does not exist','".($objParent->nodeId())."'));
			}

			if (!$objParent->userHasWriteRights()) throw new OIDplusException(_L('Authentication error. Please log in as the correct RA to insert an OID at this arc.'));

			// Check if the ID is valid
			_CheckParamExists($params, 'id');
			if ($params['id'] == '') throw new OIDplusException(_L('ID may not be empty'));

			// For the root objects, let the user also enter a WEID
			if ($objParent::ns() == 'oid') {
				if (strtolower(substr(trim($params['id']),0,5)) === 'weid:') {
					if ($objParent->isRoot()) {
						$params['id'] = WeidOidConverter::weid2oid($params['id']);
						if ($params['id'] === false) {
							throw new OIDplusException(_L('Invalid WEID'));
						}
					} else {
						throw new OIDplusException(_L('You can use the WEID syntax only at your object tree root.'));
					}
				}
			}

			// Determine absolute OID name
			// Note: At addString() and parse(), the syntax of the ID will be checked
			$id = $objParent->addString($params['id']);

			// Check, if the OID exists
			$test = OIDplus::db()->query("select id from ###objects where id = ?", array($id));
			if ($test->any()) {
				throw new OIDplusException(_L('Object %1 already exists!',$id));
			}

			$obj = OIDplusObject::parse($id);
			if ($obj === null) throw new OIDplusException(_L('%1 action failed because object "%2" cannot be parsed!','INSERT',$id));

			foreach (OIDplus::getPagePlugins() as $plugin) {
				if ($plugin->implementsFeature('1.3.6.1.4.1.37476.2.5.2.3.3')) {
					$plugin->beforeObjectInsert($id, $params);
				}
			}

			// First simulate if there are any problems of ASN.1 IDs und IRIs
			if ($obj::ns() == 'oid') {
				if (!$obj->isWellKnown()) {
					if (isset($params['iris'])) {
						$ids = ($params['iris'] == '') ? array() : explode(',',$params['iris']);
						$ids = array_map('trim',$ids);
						$obj->replaceIris($ids, true);
					}

					if (isset($params['asn1ids'])) {
						$ids = ($params['asn1ids'] == '') ? array() : explode(',',$params['asn1ids']);
						$ids = array_map('trim',$ids);
						$obj->replaceAsn1Ids($ids, true);
					}
				}
			}

			// Apply superior RA change
			$parent = $params['parent'];
			$ra_email = isset($params['ra_email']) ? $params['ra_email'] : '';
			if ($obj::ns() == 'oid') {
				if ($obj->isWellKnown()) {
					$ra_email = '';
				}
			}
			if (!empty($ra_email) && !OIDplus::mailUtils()->validMailAddress($ra_email)) {
				throw new OIDplusException(_L('Invalid RA email address'));
			}

			OIDplus::logger()->log("[INFO]OID($parent)+[INFO]OID($id)+[?INFO/!OK]OIDRA($parent)?/[?INFO/!OK]A?", "Object '$id' created, ".(empty($ra_email) ? "without defined RA" : "given to RA '$ra_email'")).", superior object is '$parent'";
			if (!empty($ra_email)) {
				OIDplus::logger()->log("[INFO]RA($ra_email)!", "Gained ownership of newly created object '$id'");
			}

			$confidential = isset($params['confidential']) ? ($params['confidential'] == 'true') : false;
			$comment = isset($params['comment']) ? $params['comment'] : '';
			$title = '';
			$description = '';

			if (strlen($id) > OIDplus::baseConfig()->getValue('LIMITS_MAX_ID_LENGTH')) {
				$maxlen = OIDplus::baseConfig()->getValue('LIMITS_MAX_ID_LENGTH');
				throw new OIDplusException(_L('The identifier %1 is too long (max allowed length: %2)',$id,$maxlen));
			}

			OIDplus::db()->query("INSERT INTO ###objects (id, parent, ra_email, confidential, comment, created, title, description) VALUES (?, ?, ?, ?, ?, ".OIDplus::db()->sqlDate().", ?, ?)", array($id, $parent, $ra_email, $confidential, $comment, $title, $description));

			// Set ASN.1 IDs und IRIs
			if ($obj::ns() == 'oid') {
				if (!$obj->isWellKnown()) {
					if (isset($params['iris'])) {
						$ids = ($params['iris'] == '') ? array() : explode(',',$params['iris']);
						$ids = array_map('trim',$ids);
						$obj->replaceIris($ids, false);
					}

					if (isset($params['asn1ids'])) {
						$ids = ($params['asn1ids'] == '') ? array() : explode(',',$params['asn1ids']);
						$ids = array_map('trim',$ids);
						$obj->replaceAsn1Ids($ids, false);
					}
				}
			}

			$status = 0;

			if (!empty($ra_email)) {
				// Do we need to notify that the RA does not exist?
				$res = OIDplus::db()->query("select ra_name from ###ra where email = ?", array($ra_email));
				$invitePlugin = OIDplus::getPluginByOid('1.3.6.1.4.1.37476.2.5.2.4.2.92'); // OIDplusPageRaInvite
				if (!$res->any()) $status = !is_null($invitePlugin) && OIDplus::config()->getValue('ra_invitation_enabled') ? 1 : 2;
			}

			if ($obj::ns() == 'oid') {
				if ($obj->isWellKnown()) {
					$status += 4;
				}
			}

			foreach (OIDplus::getPagePlugins() as $plugin) {
				if ($plugin->implementsFeature('1.3.6.1.4.1.37476.2.5.2.3.3')) {
					$plugin->afterObjectInsert($id, $params);
				}
			}

			return array(
				"status" => $status,
				"inserted_id" => $id
			);
		} else {
			throw new OIDplusException(_L('Unknown action ID'));
		}
	}

	public function init($html=true) {
		OIDplus::config()->prepareConfigKey('oobe_objects_done', '"Out Of Box Experience" wizard for OIDplusPagePublicObjects done once?', '0', OIDplusConfig::PROTECTION_HIDDEN, function($value) {});
		OIDplus::config()->prepareConfigKey('oid_grid_show_weid', 'Show WEID/Base36 column in CRUD grid of OIDs?', '1', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < 0) || ($value > 1)) {
				throw new OIDplusException(_L('Please enter a valid value (0=no, 1=yes).'));
			}
		});
	}

	private function tryObject($id, &$out) {
		$parent = null;
		$res = null;
		$row = null;
		$obj = OIDplusObject::parse($id);
		if (is_null($obj)) return false;
		if ($obj->isRoot()) {
			$obj->getContentPage($out['title'], $out['text'], $out['icon']);
			$parent = null; // $obj->getParent();
		} else {
			$res = OIDplus::db()->query("select * from ###objects where id = ?", array($obj->nodeId()));
			if (!$res->any()) {
				return false;
			} else {
				$row = $res->fetch_array(); // will be used further down the code
				$obj->getContentPage($out['title'], $out['text'], $out['icon']);
				if (empty($out['title'])) $out['title'] = explode(':',$obj->nodeId(),2)[1];
				$parent = $obj->getParent();
			}
		}
		return array($id, $parent, $res, $row);
	}

	public static function getAlternativesForQuery($id) {
		// Attention: This is NOT an implementation of 1.3.6.1.4.1.37476.2.5.2.3.7 !
		//            This is the function that calls getAlternativesForQuery() of every plugin that implements 1.3.6.1.4.1.37476.2.5.2.3.7

		// e.g. used for "Reverse Alt Id"
		$alternatives = array();
		foreach (array_merge(OIDplus::getPagePlugins(),OIDplus::getObjectTypePlugins()) as $plugin) {
			if ($plugin->implementsFeature('1.3.6.1.4.1.37476.2.5.2.3.7')) {
				$tmp = $plugin->getAlternativesForQuery($id);
				if (is_array($tmp)) {
					$alternatives = array_merge($tmp, $alternatives);
				}
			}
		}

		// If something is more than one time, remove it
		$alternatives = array_unique($alternatives);

		// If a plugin accidentally added the own ID, remove it. This function lists only alternatives, not the own ID
		$alternatives_tmp = array();
		foreach ($alternatives as $alt) {
			if ($alt !== $id) $alternatives_tmp[] = $alt;
		}
		$alternatives = $alternatives_tmp;

		return $alternatives;
	}

	public function gui($id, &$out, &$handled) {
		if ($id === 'oidplus:system') {
			$handled = true;

			$out['title'] = OIDplus::config()->getValue('system_title');
			$out['icon'] = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png';

			if (file_exists(OIDplus::localpath() . 'userdata/welcome/welcome$'.OIDplus::getCurrentLang().'.html')) {
				$cont = file_get_contents(OIDplus::localpath() . 'userdata/welcome/welcome$'.OIDplus::getCurrentLang().'.html');
			} else if (file_exists(OIDplus::localpath() . 'userdata/welcome/welcome.html')) {
				$cont = file_get_contents(OIDplus::localpath() . 'userdata/welcome/welcome.html');
			} else if (file_exists(__DIR__ . '/welcome$'.OIDplus::getCurrentLang().'.html')) {
				$cont = file_get_contents(__DIR__ . '/welcome$'.OIDplus::getCurrentLang().'.html');
			} else if (file_exists(__DIR__ . '/welcome.html')) {
				$cont = file_get_contents(__DIR__ . '/welcome.html');
			} else {
				$cont = '';
			}

			list($html, $js, $css) = extractHtmlContents($cont);
			$cont = '';
			if (!empty($js))  $cont .= "<script>\n$js\n</script>";
			if (!empty($css)) $cont .= "<style>\n$css\n</style>";
			$cont .= stripHtmlComments($html);

			$out['text'] = $cont;

			if (strpos($out['text'], '%%OBJECT_TYPE_LIST%%') !== false) {
				$tmp = '<ul>';
				foreach (OIDplus::getEnabledObjectTypes() as $ot) {
					$tmp .= '<li><a '.OIDplus::gui()->link($ot::root()).'>'.htmlentities($ot::objectTypeTitle()).'</a></li>';
				}
				$tmp .= '</ul>';
				$out['text'] = str_replace('%%OBJECT_TYPE_LIST%%', $tmp, $out['text']);
			}

			return;
		}

		// Never answer to an object type that is called 'oidplus:',
		// otherwise, an object type plugin could break the whole system!
		else if ((strpos($id,':') !== false) && (!str_starts_with($id,'oidplus:'))) {

			// --- Try to find the object or an alternative

			$test = $this->tryObject($id, $out);
			if ($test === false) {
				// try to find an alternative
				$alternatives = $this->getAlternativesForQuery($id);
				foreach ($alternatives as $alternative) {
					$test = $this->tryObject($alternative, $out);
					if ($test !== false) break; // found something
				}
			}
			if ($test !== false) {
				list($id, $parent, $res, $row) = $test;
			}

			// --- If the object type is disabled or not an object at all (e.g. "oidplus:"), then $handled=false
			//     If the object type is enabled but object not found, $handled=true

			$obj = OIDplusObject::parse($id);

			if ($test === false) {
				if (is_null($obj)) {
					// Object type disabled or not known (e.g. ObjectType "oidplus:").
					$handled = false;
					return;
				} else {
					// Object type enabled but identifier not in database
					$handled = true;
					if (isset($_SERVER['SCRIPT_FILENAME']) && (strtolower(basename($_SERVER['SCRIPT_FILENAME'])) !== 'ajax.php')) { // don't send HTTP error codes in ajax.php, because we want a page and not a JavaScript alert box, when someone enters an invalid OID in the GoTo-Box
						http_response_code(404);
					}
					$out['title'] = _L('Object not found');
					$out['icon'] = 'img/error.png';
					$out['text'] = _L('The object %1 was not found in this database.','<code>'.htmlentities($id).'</code>');
					return;
				}
			} else {
				$handled = true;
			}

			unset($test);

			// --- If found, do we have read rights?

			if (!$obj->userHasReadRights()) {
				if (isset($_SERVER['SCRIPT_FILENAME']) && (strtolower(basename($_SERVER['SCRIPT_FILENAME'])) !== 'ajax.php')) { // don't send HTTP error codes in ajax.php, because we want a page and not a JavaScript alert box, when someone enters an invalid OID in the GoTo-Box
					http_response_code(403);
				}
				$out['title'] = _L('Access denied');
				$out['icon'] = 'img/error.png';
				$out['text'] = '<p>'._L('Please <a %1>log in</a> to receive information about this object.',OIDplus::gui()->link('oidplus:login')).'</p>';
				return;
			}

			// ---

			if ($parent) {
				if ($parent->isRoot()) {

					$parent_link_text = $parent->objectTypeTitle();
					$out['text'] = '<p><a '.OIDplus::gui()->link($parent->root()).'><img src="img/arrow_back.png" width="16" alt="'._L('Go back').'"> '._L('Parent node: %1',htmlentities($parent_link_text)).'</a></p>' . $out['text'];

				} else {
					$res_ = OIDplus::db()->query("select * from ###objects where id = ?", array($parent->nodeId()));
					if ($res_->any()) {
						$row_ = $res_->fetch_array();

						$parent_title = $row_['title'];
						if (empty($parent_title) && ($parent->ns() == 'oid')) {
							// If not title is available, then use an ASN.1 identifier
							$res_ = OIDplus::db()->query("select name from ###asn1id where oid = ?", array($parent->nodeId()));
							if ($res_->any()) {
								$row_ = $res_->fetch_array();
								$parent_title = $row_['name']; // TODO: multiple ASN1 ids?
							}
						}

						$parent_link_text = empty($parent_title) ? explode(':',$parent->nodeId())[1] : $parent_title.' ('.explode(':',$parent->nodeId())[1].')';

						$out['text'] = '<p><a '.OIDplus::gui()->link($parent->nodeId()).'><img src="img/arrow_back.png" width="16" alt="'._L('Go back').'"> '._L('Parent node: %1',htmlentities($parent_link_text)).'</a></p>' . $out['text'];
					} else {
						$out['text'] = '';
					}
				}
			} else {
				$parent_link_text = _L('Go back to front page');
				$out['text'] = '<p><a '.OIDplus::gui()->link('oidplus:system').'><img src="img/arrow_back.png" width="16" alt="'._L('Go back').'"> '.htmlentities($parent_link_text).'</a></p>' . $out['text'];
			}

			// ---

			// DM 16.03.2022 commented out isset() because Oracle does not set 'description' if it is NULL?!
			if (!is_null($row)/* && isset($row['description'])*/) {
				if (empty($row['description'])) {
					if (empty($row['title'])) {
						$desc = '<p><i>'._L('No description for this object available').'</i></p>';
					} else {
						$desc = $row['title'];
					}
				} else {
					$desc = self::objDescription($row['description']);
				}

				if ($obj->userHasWriteRights()) {
					$rand = ++self::$crudCounter;
					$desc = '<noscript><p><b>'._L('You need to enable JavaScript to edit title or description of this object.').'</b></p>'.$desc.'</noscript>';
					$desc .= '<div class="container box" style="display:none" id="descbox_'.$rand.'">';
					$desc .= _L('Title').': <input type="text" name="title" id="titleedit" value="'.htmlentities($row['title']).'"><br><br>'._L('Description').':<br>';
					$desc .= self::showMCE('description', $row['description']);
					$desc .= '<button type="button" name="update_desc" id="update_desc" class="btn btn-success btn-xs update" onclick="OIDplusPagePublicObjects.updateDesc()">'._L('Update description').'</button>';
					$desc .= '</div>';
					$desc .= '<script>$("#descbox_'.$rand.'")[0].style.display = "block";</script>';
				}
			} else {
				$desc = '';
			}

			// ---

			if (strpos($out['text'], '%%DESC%%') !== false)
				$out['text'] = str_replace('%%DESC%%',    $desc,                              $out['text']);
			if (strpos($out['text'], '%%CRUD%%') !== false)
				$out['text'] = str_replace('%%CRUD%%',    self::showCrud($obj->nodeId()),     $out['text']);
			if (strpos($out['text'], '%%RA_INFO%%') !== false)
				$out['text'] = str_replace('%%RA_INFO%%', OIDplusPagePublicRaInfo::showRaInfo($row['ra_email']), $out['text']);

			$alt_ids = $obj->getAltIds();
			if (count($alt_ids) > 0) {
				$out['text'] .= '<h2>'._L('Alternative Identifiers').'</h2>';
				foreach ($alt_ids as $alt_id) {
					$ns = $alt_id->getNamespace();
					$aid = $alt_id->getId();
					$aiddesc = $alt_id->getDescription();
					$suffix = $alt_id->getSuffix();
					$out['text'] .= "$aiddesc: <code>$ns:$aid</code>$suffix<br>";
				}
			}

			foreach (OIDplus::getPagePlugins() as $plugin) {
				if ($plugin->implementsFeature('1.3.6.1.4.1.37476.2.5.2.3.2')) {
					$plugin->modifyContent($obj->nodeId(), $out['title'], $out['icon'], $out['text']);
				}
			}
		}
	}

	private function publicSitemap_rec($json, &$out) {
		foreach ($json as $x) {
			if (isset($x['id']) && $x['id']) {
				$out[] = $x['id'];
			}
			if (isset($x['children'])) {
				$this->publicSitemap_rec($x['children'], $out);
			}
		}
	}

	public function publicSitemap(&$out) {
		$json = array();
		$this->tree($json, null/*RA EMail*/, false/*HTML tree algorithm*/, true/*display all*/);
		$this->publicSitemap_rec($json, $out);
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if ($nonjs) {
			$json[] = array(
				'id' => 'oidplus:system',
				'icon' => OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png',
				'text' => _L('System')
			);

			$parent = '';
			$res = OIDplus::db()->query("select parent from ###objects where id = ?", array($req_goto));
			while ($row = $res->fetch_object()) {
				$parent = $row->parent;
			}

			$objTypesChildren = array();
			foreach (OIDplus::getEnabledObjectTypes() as $ot) {
				$icon = $this->get_treeicon_root($ot);

				$json[] = array(
					'id' => $ot::root(),
					'icon' => $icon,
					'text' => $ot::objectTypeTitle()
				);

				$tmp = OIDplusObject::parse($req_goto);
				if (!is_null($tmp) && ($ot == get_class($tmp))) {
					// TODO: Instead of just having 3 levels (parent, this and children), it would be better if we'd had a full tree of all parents
					//       on the other hand, for giving search engines content, this is good enough
					if (empty($parent)) {
						$res = OIDplus::db()->query("select * from ###objects where " .
						                            "parent = ? or " .
						                            "id = ? " .
						                            "order by ".OIDplus::db()->natOrder('id'), array($req_goto, $req_goto));
					} else {
						$res = OIDplus::db()->query("select * from ###objects where " .
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
			if ($req_goto === true) {
				$goto_path = true; // display everything recursively
			} else if (isset($req_goto)) {
				$goto = $req_goto;
				$path = array();
				while (true) {
					$path[] = $goto;
					$res = OIDplus::db()->query("select parent from ###objects where id = ?", array($goto));
					if (!$res->any()) break;
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
				$icon = $this->get_treeicon_root($ot);

				$child = array('id' => $ot::root(),
				               'text' => $ot::objectTypeTitle(),
				               'state' => array("opened" => true),
				               'icon' => $icon,
				               'children' => OIDplus::menuUtils()->tree_populate($ot::root(), $goto_path)
				               );
				if (!file_exists($child['icon'])) $child['icon'] = null; // default icon (folder)
				$objTypesChildren[] = $child;
			}

			$json[] = array(
				'id' => "oidplus:system",
				'text' => _L('Objects'),
				'state' => array(
					"opened" => true,
					// "selected" => true)  // "selected" is buggy:
					// 1) The select-event will not be triggered upon loading
					// 2) The nodes directly blow cannot be opened (loading infinite time)
				),
				'icon' => OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png',
				'children' => $objTypesChildren
			);

			return true;
		}
	}

	public function tree_search($request) {
		$ary = array();
		$found_leaf = false;
		if ($obj = OIDplusObject::parse($request)) {
			$found_leaf = OIDplusObject::exists($request);
			do {
				if ($obj->userHasReadRights()) {
					$ary[] = $obj->nodeId();
				}
			} while ($obj = $obj->getParent());
			$ary = array_reverse($ary);
		}
		if (!$found_leaf) {
			$alternatives = $this->getAlternativesForQuery($request);
			foreach ($alternatives as $alternative) {
				$ary_ = array();
				if ($obj = OIDplusObject::parse($alternative)) {
					if ($obj->userHasReadRights() && OIDplusObject::exists($alternative)) {
						do {
							$ary_[] = $obj->nodeId();
						} while ($obj = $obj->getParent());
						$ary_ = array_reverse($ary_);
					}
				}
				if (!empty($ary_)) {
					$ary = $ary_;
					break;
				}
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

		// http://www.oid-info.com/cgi-bin/display?a=list-by-category&category=Not%20allocating%20identifiers
		$no_asn1 = array(
			'oid:1.3.6.1.4.1',
			'oid:1.3.6.1.4.1.37476.9000',
			'oid:1.3.6.1.4.1.37553.8.8',
			'oid:2.16.276.1',
			//'oid:2.25', // according to Olivier, it is OK that UUID owners define their own ASN.1 ID, since the ASN.1 ID is not required to be unique
			//'oid:1.2.840.113556.1.8000.2554' // Adhoc (GUID/UUID-based) customer use. It is probably the same case as the UUID OIDs, after all, these are UUIDs, too.
		);

		// http://www.oid-info.com/cgi-bin/display?a=list-by-category&category=Not%20allocating%20Unicode%20labels
		$no_iri = array(
			'oid:1.2.250.1',
			'oid:1.3.6.1.4.1',
			'oid:1.3.6.1.4.1.37476.9000',
			'oid:1.3.6.1.4.1.37553.8.8',
			'oid:2.16.276.1',
			'oid:2.25'
		);

		$accepts_asn1 = ($parentNS == 'oid') && (!in_array($objParent->nodeId(), $no_asn1)) && (!is_uuid_oid($objParent->nodeId(),true));
		$accepts_iri  = ($parentNS == 'oid') && (!in_array($objParent->nodeId(), $no_iri)) && (!is_uuid_oid($objParent->nodeId(),true));

		$result = OIDplus::db()->query("select o.*, r.ra_name " .
		                               "from ###objects o " .
		                               "left join ###ra r on r.email = o.ra_email " .
		                               "where parent = ? " .
		                               "order by ".OIDplus::db()->natOrder('id'), array($parent));

		$rows = array();
		while ($row = $result->fetch_object()) {
			$obj = OIDplusObject::parse($row->id);
			$rows[] = array($obj,$row);
		}

		$enable_weid_presentation = OIDplus::config()->getValue('oid_grid_show_weid');

		$output = '';
		$output .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
		$output .= '<table class="table table-bordered table-striped">';
		$output .= '	<tr>';
		$output .= '	     <th>'._L('ID').(($parentNS == 'gs1') ? ' '._L('(without check digit)') : '').'</th>';
		if ($enable_weid_presentation && ($parentNS == 'oid') && !$objParent->isRoot()) {
			$output .= '	     <th><abbr title="'._L('Binary-to-text encoding used for WEIDs').'">'._L('Base36').'</abbr></th>';
		}
		if ($parentNS == 'oid') {
			if ($accepts_asn1) $output .= '	     <th>'._L('ASN.1 IDs (comma sep.)').'</th>';
			if ($accepts_iri)  $output .= '	     <th>'._L('IRI IDs (comma sep.)').'</th>';
		}
		$output .= '	     <th>'._L('RA').'</th>';
		$output .= '	     <th>'._L('Comment').'</th>';
		if ($objParent->userHasWriteRights()) {
			$output .= '	     <th>'._L('Hide').'</th>';
			$output .= '	     <th>'._L('Update').'</th>';
			$output .= '	     <th>'._L('Delete').'</th>';
		}
		$output .= '	     <th>'._L('Created').'</th>';
		$output .= '	     <th>'._L('Updated').'</th>';
		$output .= '	</tr>';

		foreach ($rows as list($obj,$row)) {
			$items_total++;
			if (!$obj->userHasReadRights()) {
				$items_hidden++;
				continue;
			}

			$show_id = $obj->crudShowId($objParent);

			$asn1ids = array();
			$res2 = OIDplus::db()->query("select name from ###asn1id where oid = ? order by lfd", array($row->id));
			while ($row2 = $res2->fetch_array()) {
				$asn1ids[] = $row2['name'];
			}

			$iris = array();
			$res2 = OIDplus::db()->query("select name from ###iri where oid = ? order by lfd", array($row->id));
			while ($row2 = $res2->fetch_array()) {
				$iris[] = $row2['name'];
			}

			$date_created = explode(' ', $row->created)[0] == '0000-00-00' ? '' : explode(' ', $row->created)[0];
			$date_updated = explode(' ', $row->updated)[0] == '0000-00-00' ? '' : explode(' ', $row->updated)[0];

			$output .= '<tr>';
			$output .= '     <td><a href="?goto='.urlencode($row->id).'" onclick="openAndSelectNode('.js_escape($row->id).', '.js_escape($parent).'); return false;">'.htmlentities($show_id).'</a>';
			if ($enable_weid_presentation && ($parentNS == 'oid') && $objParent->isRoot()) {
				// To save space horizontal space, the WEIDs were written below the OIDs
				$output .= '<br>'.$obj->getWeidNotation(true);
			}
			$output .= '</td>';
			if ($enable_weid_presentation && ($parentNS == 'oid') && !$objParent->isRoot()) {
				$output .= '	<td>'.htmlentities($obj->weidArc()).'</td>';
			}
			if ($objParent->userHasWriteRights()) {
				if ($parentNS == 'oid') {
					if ($accepts_asn1) $output .= '     <td><input type="text" id="asn1ids_'.$row->id.'" value="'.implode(', ', $asn1ids).'"></td>';
					if ($accepts_iri)  $output .= '     <td><input type="text" id="iris_'.$row->id.'" value="'.implode(', ', $iris).'"></td>';
				}
				$output .= '     <td><input type="text" id="ra_email_'.$row->id.'" value="'.htmlentities($row->ra_email).'"></td>';
				$output .= '     <td><input type="text" id="comment_'.$row->id.'" value="'.htmlentities($row->comment).'"></td>';
				$output .= '     <td><input type="checkbox" id="hide_'.$row->id.'" '.($row->confidential ? 'checked' : '').'></td>';
				$output .= '     <td><button type="button" name="update_'.$row->id.'" id="update_'.$row->id.'" class="btn btn-success btn-xs update" onclick="OIDplusPagePublicObjects.crudActionUpdate('.js_escape($row->id).', '.js_escape($parent).')">'._L('Update').'</button></td>';
				$output .= '     <td><button type="button" name="delete_'.$row->id.'" id="delete_'.$row->id.'" class="btn btn-danger btn-xs delete" onclick="OIDplusPagePublicObjects.crudActionDelete('.js_escape($row->id).', '.js_escape($parent).')">'._L('Delete').'</button></td>';
				$output .= '     <td>'.$date_created.'</td>';
				$output .= '     <td>'.$date_updated.'</td>';
			} else {
				if ($parentNS == 'oid') {
					if ($asn1ids == '') $asn1ids = '<i>'._L('(none)').'</i>';
					if ($iris == '') $iris = '<i>'._L('(none)').'</i>';
					$asn1ids_ext = array();
					foreach ($asn1ids as $asn1id) {
						$asn1ids_ext[] = '<a href="?goto='.urlencode($row->id).'" onclick="openAndSelectNode('.js_escape($row->id).', '.js_escape($parent).'); return false;">'.$asn1id.'</a>';
					}
					if ($accepts_asn1) $output .= '     <td>'.implode(', ', $asn1ids_ext).'</td>';
					if ($accepts_iri)  $output .= '     <td>'.implode(', ', $iris).'</td>';
				}
				$output .= '     <td><a '.OIDplus::gui()->link('oidplus:rainfo$'.str_replace('@','&',$row->ra_email)).'>'.htmlentities(empty($row->ra_name) ? str_replace('@','&',$row->ra_email) : $row->ra_name).'</a></td>';
				$output .= '     <td>'.htmlentities($row->comment).'</td>';
				$output .= '     <td>'.$date_created.'</td>';
				$output .= '     <td>'.$date_updated.'</td>';
			}
			$output .= '</tr>';
		}

		$result = OIDplus::db()->query("select * from ###objects where id = ?", array($parent));
		$parent_ra_email = $result->any() ? $result->fetch_object()->ra_email : '';

		// "Create OID" row
		if ($objParent->userHasWriteRights()) {
			$output .= '<tr>';
			$prefix = is_null($objParent) ? '' : $objParent->crudInsertPrefix();

			$suffix = is_null($objParent) ? '' : $objParent->crudInsertSuffix();
			foreach (OIDplus::getObjectTypePlugins() as $plugin) {
				if (($plugin::getObjectTypeClassName()::ns() == $parentNS) && $plugin->implementsFeature('1.3.6.1.4.1.37476.2.5.2.3.6')) {
					$suffix .= $plugin->gridGeneratorLinks($objParent);
				}
			}

			if ($parentNS == 'guid') {
				$output .= '     <td>'.$prefix.' <input type="text" id="id" value="" style="width:100%;min-width:275px">'.$suffix.'</td>';
			} else if ($parentNS == 'oid') {
				// TODO: Idea: Give a class name, e.g. "OID" and then with a oid-specific CSS make the width individual. So, every plugin has more control over the appearance and widths of the input fields
				if ($objParent->nodeId() === 'oid:2.25') {
					$output .= '     <td>'.$prefix.' <input type="text" id="id" value="" style="width:100%;min-width:345px">'.$suffix.'</td>';
					if ($enable_weid_presentation) $output .= '     <td>&nbsp;</td>'; // For UUID-OIDs, you must generate a valid one. Don't be tempted to create one using the Base36 input!
				} else if ($objParent->isRoot()) {
					$output .= '     <td>'.$prefix.' <input type="text" id="id" value="" style="width:100%;min-width:345px">'.$suffix.'</td>';
					if ($enable_weid_presentation) $output .= ''; // WEID-editor not available for root nodes at the moment. For the moment you need to enter the OID (TODO: Create JavaScript WEID encoder/decoder)
				} else {
					if ($enable_weid_presentation) {
						$output .= '     <td>'.$prefix.' <input oninput="OIDplusPagePublicObjects.frdl_oidid_change()" type="text" id="id" value="" style="width:100%;min-width:100px">'.$suffix.'</td>';
						$output .= '     <td><input type="text" name="weid" id="weid" value="" oninput="OIDplusPagePublicObjects.frdl_weid_change()" style="width:100%;min-width:100px"></td>';
					} else {
						$output .= '     <td>'.$prefix.' <input type="text" id="id" value="" style="width:100%;min-width:100px">'.$suffix.'</td>';
					}
				}
			} else {
				$output .= '     <td>'.$prefix.' <input type="text" id="id" value="" style="width:100%;min-width:100px">'.$suffix.'</td>';
			}
			if ($accepts_asn1) $output .= '     <td><input type="text" id="asn1ids" value=""></td>';
			if ($accepts_iri)  $output .= '     <td><input type="text" id="iris" value=""></td>';
			$output .= '     <td><input type="text" id="ra_email" value="'.htmlentities($parent_ra_email).'"></td>';
			$output .= '     <td><input type="text" id="comment" value=""></td>';
			$output .= '     <td><input type="checkbox" id="hide"></td>';
			$output .= '     <td><button type="button" name="insert" id="insert" class="btn btn-success btn-xs update" onclick="OIDplusPagePublicObjects.crudActionInsert('.js_escape($parent).')">'._L('Insert').'</button></td>';
			$output .= '     <td></td>';
			$output .= '     <td></td>';
			$output .= '     <td></td>';
			$output .= '</tr>';
		} else {
			if ($items_total-$items_hidden == 0) {
				$cols = ($parentNS == 'oid') ? 7 : 5;
				if ($enable_weid_presentation && ($parentNS == 'oid') && !$objParent->isRoot()) {
					$cols++;
				}
				$output .= '<tr><td colspan="'.$cols.'">'._L('No items available').'</td></tr>';
			}
		}

		$output .= '</table>';
		$output .= '</div></div>';

		if ($items_hidden == 1) {
			$output .= '<p>'._L('One item is hidden. Please <a %1>log in</a> to see it.',$items_hidden,OIDplus::gui()->link('oidplus:login')).'</p>';
		} else if ($items_hidden > 1) {
			$output .= '<p>'._L('%1 items are hidden. Please <a %2>log in</a> to see them.',$items_hidden,OIDplus::gui()->link('oidplus:login')).'</p>';
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
	// 'importcss' added 17 September 2020, because it breaks the "Format/Style" dropdown box ("styleselect" toolbar)
	// 'legacyoutput' added 24 September 2021, because it is declared as deprecated
	// 'spellchecker' added 6 October 2021, because it is declared as deprecated and marked for removal in TinyMCE 6.0
	// 'imagetools' and 'toc' added 23 February 2022, because they are declared as deprecated and marked for removal in TinyMCE 6.0 ("moving to premium")
	public static $exclude_tinymce_plugins = array('fullpage', 'bbcode', 'quickbars', 'colorpicker', 'textcolor', 'contextmenu', 'importcss', 'legacyoutput', 'spellchecker', 'imagetools', 'toc');

	protected static function showMCE($name, $content) {
		$mce_plugins = array();
		foreach (glob(OIDplus::localpath().'vendor/tinymce/tinymce/plugins/*') as $m) { // */
			$mce_plugins[] = basename($m);
		}

		foreach (self::$exclude_tinymce_plugins as $exclude) {
			$index = array_search($exclude, $mce_plugins);
			if ($index !== false) unset($mce_plugins[$index]);
		}

		$oidplusLang = OIDplus::getCurrentLang();

		$langCandidates = array(
			strtolower(substr($oidplusLang,0,2)).'_'.strtoupper(substr($oidplusLang,2,2)), // de_DE
			strtolower(substr($oidplusLang,0,2)) // de
		);
		$tinyMCELang = '';
		foreach ($langCandidates as $candidate) {
			if (file_exists(OIDplus::localpath().'vendor/tweeb/tinymce-i18n/langs/'.$candidate.'.js')) {
				$tinyMCELang = $candidate;
				break;
			}
		}

		$out = '<script>
				tinymce.EditorManager.baseURL = "vendor/tinymce/tinymce";
				tinymce.init({
					document_base_url: "'.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).'",
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
					'.($tinyMCELang == '' ? '' : ', language : "'.$tinyMCELang.'"').'
					'.($tinyMCELang == '' ? '' : ', language_url : "'.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).'vendor/tweeb/tinymce-i18n/langs/'.$tinyMCELang.'.js"').'
				});

				pageChangeRequestCallbacks.push([OIDplusPagePublicObjects.cbQueryTinyMCE, "#'.$name.'"]);
				pageChangeCallbacks.push([OIDplusPagePublicObjects.cbRemoveTinyMCE, "#'.$name.'"]);
			</script>';

		$content = htmlentities($content); // For some reason, if we want to display the text "<xyz>" in TinyMCE, we need to double-encode things! &lt; will not be accepted, we need &amp;lt; ... why?

		$out .= '<textarea name="'.htmlentities($name).'" id="'.htmlentities($name).'">'.trim($content).'</textarea><br>';

		return $out;
	}

	public function implementsFeature($id) {
		if (strtolower($id) == '1.3.6.1.4.1.37476.2.5.2.3.1') return true; // oobeEntry, oobeRequested()
		// Important: Do NOT 1.3.6.1.4.1.37476.2.5.2.3.7 because our getAlternativesForQuery() is the one that calls others!
		return false;
	}

	public function oobeRequested(): bool {
		// Interface 1.3.6.1.4.1.37476.2.5.2.3.1

		return OIDplus::config()->getValue('oobe_objects_done') == '0';
	}

	public function oobeEntry($step, $do_edits, &$errors_happened)/*: void*/ {
		// Interface 1.3.6.1.4.1.37476.2.5.2.3.1

		echo '<p><u>'._L('Step %1: Enable/Disable object type plugins',$step).'</u></p>';
		echo '<p>'._L('Which object types do you want to manage using OIDplus?').'</p>';

		$enabled_ary = array();

		foreach (OIDplus::getEnabledObjectTypes() as $ot) {
			echo '<input type="checkbox" name="enable_ot_'.$ot::ns().'" id="enable_ot_'.$ot::ns().'"';
			if (isset($_REQUEST['sent'])) {
			        if (isset($_REQUEST['enable_ot_'.$ot::ns()])) {
					echo ' checked';
					$enabled_ary[] = $ot::ns();
				}
			} else {
			        echo ' checked';
			}
			echo '> <label for="enable_ot_'.$ot::ns().'">'.htmlentities($ot::objectTypeTitle()).'</label><br>';
		}

		foreach (OIDplus::getDisabledObjectTypes() as $ot) {
			echo '<input type="checkbox" name="enable_ot_'.$ot::ns().'" id="enable_ot_'.$ot::ns().'"';
			if (isset($_REQUEST['sent'])) {
			        if (isset($_REQUEST['enable_ot_'.$ot::ns()])) {
					echo ' checked';
					$enabled_ary[] = $ot::ns();
				}
			} else {
			        echo ''; // <-- difference
			}
			echo '> <label for="enable_ot_'.$ot::ns().'">'.htmlentities($ot::objectTypeTitle()).'</label><br>';
		}

		$msg = '';
		if ($do_edits) {
			try {
				OIDplus::config()->setValue('objecttypes_enabled', implode(';', $enabled_ary));
				OIDplus::config()->setValue('oobe_objects_done', '1');
			} catch (Exception $e) {
				$msg = $e->getMessage();
				$errors_happened = true;
			}
		}

		echo ' <font color="red"><b>'.$msg.'</b></font>';
	}

}
