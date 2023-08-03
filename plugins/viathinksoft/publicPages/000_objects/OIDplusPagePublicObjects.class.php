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

namespace ViaThinkSoft\OIDplus;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusPagePublicObjects extends OIDplusPagePluginPublic
	implements INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_1, /* oobeEntry, oobeRequested */
	           INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_2, /* modifyContent */
	           INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8, /* getNotifications */
	           INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_9  /* restApi* */
	           // Important: Do NOT implement INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_7, because our getAlternativesForQuery() is the one that calls others!
{

	/**
	 * @param string|OIDplusObject $ot
	 * @return string|null
	 */
	private function get_treeicon_root($ot)/*: ?string*/ {
		$root = $ot::parse($ot::root());
		if (!$root) return null;
		return $root->getIcon();
	}

	/**
	 * @param string $id
	 * @param string $old_ra
	 * @param string $new_ra
	 * @return void
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	private function ra_change_rec(string $id, string $old_ra, string $new_ra) {
		OIDplus::db()->query("update ###objects set ra_email = ?, updated = ".OIDplus::db()->sqlDate()." where id = ? and ".OIDplus::db()->getSlang()->isNullFunction('ra_email',"''")." = ?", array($new_ra, $id, $old_ra));
		OIDplusObject::resetObjectInformationCache();

		$res = OIDplus::db()->query("select id from ###objects where parent = ? and ".OIDplus::db()->getSlang()->isNullFunction('ra_email',"''")." = ?", array($id, $old_ra));
		while ($row = $res->fetch_array()) {
			$this->ra_change_rec($row['id'], $old_ra, $new_ra);
		}
	}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_2
	 * @param string $id
	 * @param string $title
	 * @param string $icon
	 * @param string $text
	 * @return void
	 * @throws \ViaThinkSoft\OIDplus\OIDplusException
	 */
	public function modifyContent(string $id, string &$title, string &$icon, string &$text) {
		$payload = '<br /> <a href="'.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE)
			.'rest/v1/objects/'.htmlentities($id).'" class="gray_footer_font" target="_blank">'._L('REST API').'</a> '
			.'(<a '.OIDplus::gui()->link('oidplus:rest_api_information_admin$endpoints:1.3.6.1.4.1.37476.2.5.2.4.1.0').' class="gray_footer_font">'._L('Documentation').'</a>)';

		$text = str_replace('<!-- MARKER 6 -->', '<!-- MARKER 6 -->'.$payload, $text);
	}

	/**
	 * @param string $endpoint
	 * @param array $json_in
	 * @return never-return
	 */
	private function restApiCall_OPTIONS(string $endpoint, array $json_in) {
		header("access-control-allow-credentials: true");
		header("access-control-allow-headers: Keep-Alive,User-Agent,Authorization");
		header("access-control-allow-methods: GET, PUT, POST, DELETE, PATCH, OPTIONS");
		header("access-control-allow-origin: *");
		http_response_code(204/*No content*/);
		OIDplus::invoke_shutdown();
		die(); // return array();
	}

	/**
	 * REST method for selecting
	 * @param string $endpoint
	 * @param array $json_in
	 * @return array
	 */
	private function restApiCall_GET(string $endpoint, array $json_in): array {
		$id = substr($endpoint, strlen('objects/'));
		$obj = OIDplusObject::findFitting($id);
		if (!$obj) throw new OIDplusException(_L('The object %1 was not found in this database.', $id), null, 404);

		if (!$obj->userHasReadRights()) throw new OIDplusException('Insufficient authorization to read information about this object.', null, 401);

		$output = array();

		$output['status'] = 0/*OK*/;
		$output['status_bits'] = [];

		//$output['id'] = $obj->nodeId(true);
		$output['ra_email'] = $obj->getRaMail();
		$output['comment'] = $obj->getComment();
		$output['confidential'] = $obj->isConfidential();
		$output['title'] = $obj->getTitle();
		$output['description'] = $obj->getDescription();

		if ($obj instanceof OIDplusOid) {
			$output['asn1ids'] = array(); // TODO: Rename to oid-alphanum-id ?
			foreach ($obj->getAsn1Ids() as $asn) {
				$output['asn1ids'][] = $asn->getName();
			}

			$output['iris'] = array(); // TODO: Rename to oid-unicode-label ?
			foreach ($obj->getIris() as $iri) {
				$output['iris'][] = $iri->getName();
			}
		}

		http_response_code(200);
		return $output;
	}

	/**
	 * REST method for replacing (re-create)
	 * @param string $endpoint
	 * @param array $json_in
	 * @return array
	 */
	private function restApiCall_PUT(string $endpoint, array $json_in): array {
		$id = substr($endpoint, strlen('objects/'));
		$obj = OIDplusObject::parse($id);
		if (!$obj) throw new OIDplusException(_L('%1 action failed because object "%2" cannot be parsed!', 'PUT', $id), null, 400);

		// ATTENTION: Do *not* use $params=$json_in. We intentionally set $params to empty strings if the values do not exist in $json_in,
		//            because PUT is for re-creating the whole object!
		$params = array();
		$params['id'] = $id;
		$params['ra_email'] = $json_in['ra_email'] ?? '';
		$params['comment'] = $json_in['comment'] ?? '';
		$params['confidential'] = $json_in['confidential'] ?? false;
		$params['title'] = $json_in['title'] ?? '';
		$params['description'] = $json_in['description'] ?? '';
		$params['asn1ids'] = $json_in['asn1ids'] ?? array();
		$params['iris'] = $json_in['iris'] ?? array();

		if (OIDplusObject::exists($id)) {
			// TODO: Problem: The superior RA cannot set title/description, so they cannot perform the PUT command!
			$output = self::action_Update($params);
		} else {
			$params['parent'] = $obj->getParent();
			$params['id_fully_qualified'] = true;
			$output = self::action_Insert($params);
		}

		$output['status_bits'] = [];
		if (($output['status'] & 1) == 1) $output['status_bits'][1] = 'RA is not registered, but it can be invited';
		if (($output['status'] & 2) == 2) $output['status_bits'][2] = 'RA is not registered and it cannot be invited';
		if (($output['status'] & 4) == 4) $output['status_bits'][4] = 'OID is a well-known OID, so RA, ASN.1, and IRI identifiers were reset';
		if (($output['status'] & 8) == 8) $output['status_bits'][8] = 'User has write rights to the freshly created OID';

		http_response_code(200);
		return $output;
	}

	/**
	 * REST method for inserting (create)
	 * @param string $endpoint
	 * @param array $json_in
	 * @return array
	 */
	private function restApiCall_POST(string $endpoint, array $json_in): array {
		$id = substr($endpoint, strlen('objects/'));
		$obj = OIDplusObject::parse($id);
		if (!$obj) throw new OIDplusException(_L('%1 action failed because object "%2" cannot be parsed!', 'GET', $id), null, 400);
		$params = $json_in;
		$params['parent'] = $obj->getParent();
		$params['id_fully_qualified'] = true;
		$params['id'] = $id;
		$output = self::action_Insert($params);

		$output['status_bits'] = [];
		if (($output['status'] & 1) == 1) $output['status_bits'][1] = 'RA is not registered, but it can be invited';
		if (($output['status'] & 2) == 2) $output['status_bits'][2] = 'RA is not registered and it cannot be invited';
		if (($output['status'] & 4) == 4) $output['status_bits'][4] = 'OID is a well-known OID, so RA, ASN.1, and IRI identifiers were reset';
		if (($output['status'] & 8) == 8) $output['status_bits'][8] = 'User has write rights to the freshly created OID';

		http_response_code(200);
		return $output;
	}

	/**
	 * REST method for modification
	 * @param string $endpoint
	 * @param array $json_in
	 * @return array
	 */
	private function restApiCall_PATCH(string $endpoint, array $json_in): array {
		$id = substr($endpoint, strlen('objects/'));
		$params = $json_in;
		$params['id'] = $id;
		$output = self::action_Update($params);

		$output['status_bits'] = [];
		if (($output['status'] & 1) == 1) $output['status_bits'][1] = 'RA is not registered, but it can be invited';
		if (($output['status'] & 2) == 2) $output['status_bits'][2] = 'RA is not registered and it cannot be invited';
		if (($output['status'] & 4) == 4) $output['status_bits'][4] = 'OID is a well-known OID, so RA, ASN.1, and IRI identifiers were reset';
		if (($output['status'] & 8) == 8) $output['status_bits'][8] = 'User has write rights to the freshly created OID';

		http_response_code(200);
		return $output;
	}

	/**
	 * REST method for deleting
	 * @param string $endpoint
	 * @param array $json_in
	 * @return array
	 */
	private function restApiCall_DELETE(string $endpoint, array $json_in): array {
		$id = substr($endpoint, strlen('objects/'));
		$params = $json_in;
		$params['id'] = $id;
		$output = self::action_Delete($params);

		$output['status_bits'] = [];

		http_response_code(200);
		return $output;
	}

	/**
	 * Implements INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_9
	 * @param string $requestMethod
	 * @param string $endpoint
	 * @param array $json_in
	 * @return array|false
	 */
	public function restApiCall(string $requestMethod, string $endpoint, array $json_in) {
		if (str_starts_with($endpoint, 'objects/')) {
			if ($requestMethod == "OPTIONS") {
				$this->restApiCall_OPTIONS($endpoint, $json_in);
			} else if ($requestMethod == "GET"/*Select*/) {
				return $this->restApiCall_GET($endpoint, $json_in);
			} else if ($requestMethod == "PUT"/*Replace*/) {
				return $this->restApiCall_PUT($endpoint, $json_in);
			} else if ($requestMethod == "POST"/*Insert*/) {
				return $this->restApiCall_POST($endpoint, $json_in);
			} else if ($requestMethod == "PATCH"/*Modify*/) {
				return $this->restApiCall_PATCH($endpoint, $json_in);
			} else if ($requestMethod == "DELETE"/*Delete*/) {
				return $this->restApiCall_DELETE($endpoint, $json_in);
			} else {
				//throw new OIDplusException(_L("Not implemented"), null, 501);
				throw new OIDplusException(_L("Unsupported request method"), null, 400);
			}
		} else {
			return false;
		}
	}

	/**
	 * Implements INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_9
	 * Outputs information about valid endpoints
	 * @param string $kind Reserved for different kind of output format (i.e. OpenAPI "TODO"). Currently only 'html' is implemented
	 * @return string
	 */
	public function restApiInfo(string $kind='html'): string {
		if ($kind === 'html') {
			$struct = [
				_L('Receive') => [
					'<b>GET</b> '.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).'rest/v1/objects/<abbr title="'._L('e.g. %1', 'oid:2.999').'">[id]</abbr>',
					_L('Input parameters') => [
						'<i>'._L('None').'</i>'
					],
					_L('Output parameters') => [
						'status ('._L('<0 is error, >=0 is success').')',
						'status_bits',
						'error ('._L('if an error occurred').')',
						'ra_email',
						'comment',
						'iris ('._L('for OID only').')',
						'asn1ids ('._L('for OID only').')',
						'confidential',
						'title',
						'description'
					]
				],
				_L('Re-Create') => [
					'<b>PUT</b> '.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).'rest/v1/objects/<abbr title="'._L('e.g. %1', 'oid:2.999').'">[id]</abbr>',
					_L('Input parameters') => [
						'ra_email ('._L('optional').')',
						'comment ('._L('optional').')',
						'iris ('._L('optional').')',
						'asn1ids ('._L('optional').')',
						'confidential ('._L('optional').')',
						'title ('._L('optional').')',
						'description ('._L('optional').')'
					],
					_L('Output parameters') => [
						'status ('._L('<0 is error, >=0 is success').')',
						'status_bits',
						'error ('._L('if an error occurred').')',
						'inserted_id ('._L('if it was created').')'
					]
				],
				_L('Create') => [
					'<b>POST</b> '.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).'rest/v1/objects/<abbr title="'._L('e.g. %1', 'oid:2.999').'">[id]</abbr>',
					_L('Input parameters') => [
						'ra_email ('._L('optional').')',
						'comment ('._L('optional').')',
						'iris ('._L('optional').')',
						'asn1ids ('._L('optional').')',
						'confidential ('._L('optional').')',
						'title ('._L('optional').')',
						'description ('._L('optional').')'
					],
					_L('Output parameters') => [
						'status ('._L('<0 is error, >=0 is success').')',
						'status_bits',
						'error ('._L('if an error occurred').')',
						'inserted_id'
					]
				],
				_L('Update') => [
					'<b>PATCH</b> '.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).'rest/v1/objects/<abbr title="'._L('e.g. %1', 'oid:2.999').'">[id]</abbr>',
					_L('Input parameters') => [
						'ra_email ('._L('optional').')',
						'comment ('._L('optional').')',
						'iris ('._L('optional').')',
						'asn1ids ('._L('optional').')',
						'confidential ('._L('optional').')',
						'title ('._L('optional').')',
						'description ('._L('optional').')'
					],
					_L('Output parameters') => [
						'status ('._L('<0 is error, >=0 is success').')',
						'status_bits',
						'error ('._L('if an error occurred').')',
					]
				],
				_L('Remove') => [
					'<b>DELETE</b> '.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).'rest/v1/objects/<abbr title="'._L('e.g. %1', 'oid:2.999').'">[id]</abbr>',
					_L('Input parameters') => [
						'<i>'._L('None').'</i>'
					],
					_L('Output parameters') => [
						'status ('._L('<0 is error, >=0 is success').')',
						'status_bits',
						'error ('._L('if an error occurred').')',
					]
				]
			];
			return array_to_html_ul_li($struct);
		} else {
			throw new OIDplusException(_L('Invalid REST API information format'), null, 500);
		}
	}

	/**
	 * @param array $params id
	 * @return array status<0 Error, =0 Success
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	private function action_Delete(array $params): array {
		_CheckParamExists($params, 'id');
		$id = $params['id'];
		$obj = OIDplusObject::parse($id);
		if (!$obj) throw new OIDplusException(_L('%1 action failed because object "%2" cannot be parsed!','DELETE',$id));

		if (!OIDplusObject::exists($id)) {
			throw new OIDplusException(_L('Object %1 does not exist',$id), null, 404);
		}

		// Check if permitted
		if (!$obj->userHasParentalWriteRights()) throw new OIDplusException(_L('Authentication error. Please log in as the superior RA to delete this OID.'), null, 401);

		foreach (OIDplus::getAllPlugins() as $plugin) {
			if ($plugin instanceof INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_3) {
				$plugin->beforeObjectDelete($id);
			}
		}

		OIDplus::logger()->log("V2:[WARN]OID(%1)+[OK/WARN]SUPOIDRA(%1)+[OK/INFO]A", "Object '%1' (recursively) deleted", $id);
		OIDplus::logger()->log("V2:[CRIT]OIDRA(%1)", "Lost ownership of object '%1' because it was deleted", $id);

		if ($parentObj = $obj->getParent()) {
			$parent_oid = $parentObj->nodeId();
			OIDplus::logger()->log("V2:[WARN]OID(%2)", "Object '%1' (recursively) deleted", $id, $parent_oid);
		}

		// Delete object
		OIDplus::db()->query("delete from ###objects where id = ?", array($id));
		OIDplusObject::resetObjectInformationCache();

		// Delete orphan stuff
		foreach (OIDplus::getEnabledObjectTypes() as $ot) {
			do {
				$res = OIDplus::db()->query("select tchild.id from ###objects tchild " .
					"left join ###objects tparent on tparent.id = tchild.parent " .
					"where tchild.parent <> ? and tchild.id like ? and tparent.id is null;", array($ot::root(), $ot::root().'%'));
				if (!$res->any()) break;

				while ($row = $res->fetch_array()) {
					$id_to_delete = $row['id'];
					OIDplus::logger()->log("V2:[CRIT]OIDRA(%2)", "Lost ownership of object '%2' because one of the superior objects ('%1') was recursively deleted", $id, $id_to_delete);
					OIDplus::db()->query("delete from ###objects where id = ?", array($id_to_delete));
					OIDplusObject::resetObjectInformationCache();
				}
			} while (true);
		}
		OIDplus::db()->query("delete from ###asn1id where well_known = ? and oid not in (select id from ###objects where id like 'oid:%')", array(false));
		OIDplus::db()->query("delete from ###iri    where well_known = ? and oid not in (select id from ###objects where id like 'oid:%')", array(false));

		foreach (OIDplus::getAllPlugins() as $plugin) {
			if ($plugin instanceof INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_3) {
				$plugin->afterObjectDelete($id);
			}
		}

		return array("status" => 0);
	}

	/**
	 * @param array $params id, ra_email, comment, iris, asn1ids, confidential, title, description
	 * @return array status<0 Error, =0 Success, with the following bitfields for further information:
	 *             x+1 = RA is not registered, but it can be invited
	 *             x+2 = RA is not registered and it cannot be invited
	 *             x+4 = OID is a well-known OID, so RA, ASN.1, and IRI identifiers were reset
	 *             x+8 = User has write rights to the freshly created OID
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	private function action_Update(array $params): array {
		_CheckParamExists($params, 'id');
		$id = $params['id'];
		$obj = OIDplusObject::parse($id);
		if (!$obj) throw new OIDplusException(_L('%1 action failed because object "%2" cannot be parsed!','UPDATE',$id));

		if (!OIDplusObject::exists($id)) {
			throw new OIDplusException(_L('Object %1 does not exist',$id), null, 404);
		}

		// Check if permitted
		if (isset($params['title']) || isset($params['description'])) {
			if (!$obj->userHasWriteRights()) throw new OIDplusException(_L('Authentication error. Please log in as the RA to update this OID.'), null, 401);
		}
		if (isset($params['ra_email']) || isset($params['comment']) || isset($params['iris']) || isset($params['asn1ids']) || isset($params['confidential'])) {
			if (!$obj->userHasParentalWriteRights()) throw new OIDplusException(_L('Authentication error. Please log in as the superior RA to update this OID.'), null, 401);
		}

		foreach (OIDplus::getAllPlugins() as $plugin) {
			if ($plugin instanceof INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_3) {
				$plugin->beforeObjectUpdateSuperior($id, $params);
			}
		}

		// First, do a simulation for ASN.1 IDs and IRIs to check if there are any problems (then an Exception will be thrown)
		if ($obj::ns() == 'oid') {
			assert($obj instanceof OIDplusOid); //assert(get_class($obj) === "ViaThinkSoft\OIDplus\OIDplusOid");
			if (!$obj->isWellKnown()) {
				if (isset($params['iris'])) {
					$ids = ($params['iris'] == '') ? array() : (is_array($params['iris']) ? $params['iris'] : explode(',',$params['iris']));
					$ids = array_map('trim',$ids);
					$obj->replaceIris($ids, true);
				}

				if (isset($params['asn1ids'])) {
					$ids = ($params['asn1ids'] == '') ? array() : (is_array($params['asn1ids']) ? $params['asn1ids'] : explode(',',$params['asn1ids']));
					$ids = array_map('trim',$ids);
					$obj->replaceAsn1Ids($ids, true);
				}
			}
		}

		// RA E-Mail change
		if (isset($params['ra_email'])) {
			// Validate RA email address
			$new_ra = $params['ra_email'] ?? '';
			if ($obj::ns() == 'oid') {
				assert($obj instanceof OIDplusOid); //assert(get_class($obj) === "ViaThinkSoft\OIDplus\OIDplusOid");
				if ($obj->isWellKnown()) {
					$new_ra = '';
				}
			}
			if (!empty($new_ra) && !OIDplus::mailUtils()->validMailAddress($new_ra)) {
				throw new OIDplusException(_L('Invalid RA email address'));
			}

			// Change RA recursively
			$current_ra = $obj->getRaMail() ?? '';
			if ($new_ra != $current_ra) {
				OIDplus::logger()->log("V2:[INFO]OID(%1)+[OK/INFO]SUPOIDRA(%1)+[OK/INFO]A", "RA of object '%1' changed from '%2' to '%3'", $id, $current_ra, $new_ra);
				if (!empty($current_ra)) OIDplus::logger()->log("V2:[WARN]RA(%2)", "Lost ownership of object '%1' due to RA transfer of superior RA / admin.", $id, $current_ra, $new_ra);
				if (!empty($new_ra)) OIDplus::logger()->log("V2:[INFO]RA(%3)", "Gained ownership of object '%1' due to RA transfer of superior RA / admin.", $id, $current_ra, $new_ra);
				if ($parentObj = $obj->getParent()) {
					$parent_oid = $parentObj->nodeId();
					OIDplus::logger()->log("V2:[INFO]OID(%4)", "RA of object '%1' changed from '%2' to '%3'", $id, $current_ra, $new_ra, $parent_oid);
				}
				$this->ra_change_rec($id, $current_ra, $new_ra); // Recursively change inherited RAs
			}
		}

		// Log if confidentially flag was changed
		OIDplus::logger()->log("V2:[INFO]OID(%1)+[OK/INFO]SUPOIDRA(%1)+[OK/INFO]A", "Identifiers/Confidential flag of object '%1' updated", $id); // TODO: Check if they were ACTUALLY updated!
		if ($parentObj = $obj->getParent()) {
			$parent_oid = $parentObj->nodeId();
			OIDplus::logger()->log("V2:[INFO]OID(%2)", "Identifiers/Confidential flag of object '%1' updated", $id, $parent_oid); // TODO: Check if they were ACTUALLY updated!
		}

		// Replace ASN.1 IDs und IRIs
		if ($obj::ns() == 'oid') {
			assert($obj instanceof OIDplusOid); //assert(get_class($obj) === "ViaThinkSoft\OIDplus\OIDplusOid");
			if (!$obj->isWellKnown()) {
				if (isset($params['iris'])) {
					$ids = ($params['iris'] == '') ? array() : (is_array($params['iris']) ? $params['iris'] : explode(',',$params['iris']));
					$ids = array_map('trim',$ids);
					$obj->replaceIris($ids, false);
				}

				if (isset($params['asn1ids'])) {
					$ids = ($params['asn1ids'] == '') ? array() : (is_array($params['asn1ids']) ? $params['asn1ids'] : explode(',',$params['asn1ids']));
					$ids = array_map('trim',$ids);
					$obj->replaceAsn1Ids($ids, false);
				}
			}

			// TODO: Check if any identifiers have been actually changed,
			// and log it to OID($id), OID($parent), ... (see above)
		}

		if (isset($params['confidential'])) {
			$confidential = oidplus_is_true($params['confidential']);
			OIDplus::db()->query("UPDATE ###objects SET confidential = ? WHERE id = ?", array($confidential, $id));
			OIDplusObject::resetObjectInformationCache();
		}

		if (isset($params['comment'])) {
			$comment = $params['comment'];
			OIDplus::db()->query("UPDATE ###objects SET comment = ? WHERE id = ?", array($comment, $id));
			OIDplusObject::resetObjectInformationCache();
		}

		if (isset($params['title']) || isset($params['description'])) {
			OIDplus::logger()->log("V2:[INFO]OID(%1)+[OK/INFO]OIDRA(%1)+[OK/INFO]A", "Title/Description of object '%1' updated", $id);
		}

		if (isset($params['title'])) {
			$title = $params['title'];
			OIDplus::db()->query("UPDATE ###objects SET title = ? WHERE id = ?", array($title, $id));
			OIDplusObject::resetObjectInformationCache();
		}

		if (isset($params['description'])) {
			$description = $params['description'];
			OIDplus::db()->query("UPDATE ###objects SET description = ? WHERE id = ?", array($description, $id));
			OIDplusObject::resetObjectInformationCache();
		}

		OIDplus::db()->query("UPDATE ###objects SET updated = ".OIDplus::db()->sqlDate()." WHERE id = ?", array($id));
		OIDplusObject::resetObjectInformationCache();

		$status = 0;

		if (!empty($new_ra)) {
			$res = OIDplus::db()->query("select ra_name from ###ra where email = ?", array($new_ra));
			$invitePlugin = OIDplus::getPluginByOid('1.3.6.1.4.1.37476.2.5.2.4.2.92'); // OIDplusPageRaInvite
			if (!$res->any()) $status = !is_null($invitePlugin) && OIDplus::config()->getValue('ra_invitation_enabled') ? 1 : 2;
		}

		if ($obj::ns() == 'oid') {
			assert($obj instanceof OIDplusOid); //assert(get_class($obj) === "ViaThinkSoft\OIDplus\OIDplusOid");
			if ($obj->isWellKnown()) {
				$status += 4;
			}
		}

		if ($obj->userHasWriteRights()) {
			$status += 8;
		}

		foreach (OIDplus::getAllPlugins() as $plugin) {
			if ($plugin instanceof INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_3) {
				$plugin->afterObjectUpdateSuperior($id, $params);
			}
		}

		return array("status" => $status);
	}

	/**
	 * @param array $params parent, id (relative!), ra_email, comment, iris, asn1ids, confidential, title, description
	 * @return array status=<0 Error, =0 Success, with the following bitfields for further information:
	 *             x+1 = RA is not registered, but it can be invited
	 *             x+2 = RA is not registered and it cannot be invited
	 *             x+4 = OID is a well-known OID, so RA, ASN.1, and IRI identifiers were reset
	 *             x+8 = User has write rights to the freshly created OID
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	private function action_Insert(array $params): array {
		// Check if you have write rights on the parent (to create a new object)
		_CheckParamExists($params, 'parent');
		$objParent = OIDplusObject::parse($params['parent']);
		if (!$objParent) throw new OIDplusException(_L('%1 action failed because parent object "%2" cannot be parsed!','INSERT',$params['parent']));

		if (!$objParent->isRoot()) {
			$idParent = $objParent->nodeId();
			if (!OIDplusObject::exists($idParent)) {
				throw new OIDplusException(_L('Parent object %1 does not exist',$idParent), null, 404);
			}
		}

		if (!$objParent->userHasWriteRights()) throw new OIDplusException(_L('Authentication error. Please log in as the correct RA to insert an OID at this arc.'), null, 401);

		// Check if the ID is valid
		_CheckParamExists($params, 'id');
		if ($params['id'] == '') throw new OIDplusException(_L('ID may not be empty'));

		// For the root objects, let the user also enter a WEID
		if ($objParent::ns() == 'oid') {
			assert($objParent instanceof OIDplusOid); //assert(get_class($objParent) === "ViaThinkSoft\OIDplus\OIDplusOid");
			if (strtolower(substr(trim($params['id']),0,5)) === 'weid:') {
				if ($objParent->isRoot()) {
					$params['id'] = \Frdl\Weid\WeidOidConverter::weid2oid($params['id']);
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
		if (oidplus_is_true($params['id_fully_qualified'] ?? false)) {
			// For REST API, the ID is absolute (because this is what is in the URL). We need to verify that ID and Parent matches.
			$id = $params['id'];
			$obj = OIDplusObject::parse($id);
			$objParentTest = !$obj ? null : $obj->getParent();
			if (!$objParentTest || !$objParentTest->equals($objParent)) throw new OIDplusException(_L('Cannot verify that %1 has parent %2', $obj->nodeId(), $objParent->nodeId()));
		} else {
			// For AJAX/UI, the ID is relative to the parent. The absolute ID will be created by PHP's addString(), because JavaScript cannot know the syntax of the Object Type plugin
			$id = $objParent->addString($params['id']);
			$obj = OIDplusObject::parse($id);
		}
		if (!$obj) throw new OIDplusException(_L('%1 action failed because object "%2" cannot be parsed!','INSERT',$id));

		// Check, if the OID exists
		if (OIDplusObject::exists($id)) {
			throw new OIDplusException(_L('Object %1 already exists!',$id));
		}

		foreach (OIDplus::getAllPlugins() as $plugin) {
			if ($plugin instanceof INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_3) {
				$plugin->beforeObjectInsert($id, $params);
			}
		}

		// First simulate if there are any problems of ASN.1 IDs und IRIs
		if ($obj::ns() == 'oid') {
			assert($obj instanceof OIDplusOid); //assert(get_class($obj) === "ViaThinkSoft\OIDplus\OIDplusOid");
			if (!$obj->isWellKnown()) {
				if (isset($params['iris'])) {
					$ids = ($params['iris'] == '') ? array() : (is_array($params['iris']) ? $params['iris'] : explode(',',$params['iris']));
					$ids = array_map('trim',$ids);
					$obj->replaceIris($ids, true);
				}

				if (isset($params['asn1ids'])) {
					$ids = ($params['asn1ids'] == '') ? array() : (is_array($params['asn1ids']) ? $params['asn1ids'] : explode(',',$params['asn1ids']));
					$ids = array_map('trim',$ids);
					$obj->replaceAsn1Ids($ids, true);
				}
			}
		}

		// Apply superior RA change
		$parent = $params['parent'];
		$ra_email = $params['ra_email'] ?? '';
		if ($obj::ns() == 'oid') {
			assert($obj instanceof OIDplusOid); //assert(get_class($obj) === "ViaThinkSoft\OIDplus\OIDplusOid");
			if ($obj->isWellKnown()) {
				$ra_email = '';
			}
		}
		if (!empty($ra_email) && !OIDplus::mailUtils()->validMailAddress($ra_email)) {
			throw new OIDplusException(_L('Invalid RA email address'));
		}

		if (empty($ra_email)) {
			OIDplus::logger()->log("V2:[INFO]OID(%2)+[INFO]OID(%1)+[OK/INFO]OIDRA(%2)+[OK/INFO]A", "Object '%1' created, without defined RA, superior object is '%2'", $id, $parent);
		} else {
			OIDplus::logger()->log("V2:[INFO]OID(%2)+[INFO]OID(%1)+[OK/INFO]OIDRA(%2)+[OK/INFO]A", "Object '%1' created, given to RA '%3', superior object is '%2'", $id, $parent, $ra_email);
		}
		if (!empty($ra_email)) {
			OIDplus::logger()->log("V2:[INFO]RA(%2)", "Gained ownership of newly created object '%1'", $id, $ra_email);
		}

		$confidential = oidplus_is_true($params['confidential'] ?? false);
		$comment = $params['comment'] ?? '';
		$title = $params['title'] ?? ''; // This is very special (only useable in REST API): The superior RA can set the title during creation, even if they lose their ownership by delegating afterwards!
		$description = $params['description'] ?? ''; // This is very special (only useable in REST API): The superior RA can set the title during creation, even if they lose their ownership by delegating afterwards!

		if (strlen($id) > OIDplus::baseConfig()->getValue('LIMITS_MAX_ID_LENGTH')) {
			$maxlen = OIDplus::baseConfig()->getValue('LIMITS_MAX_ID_LENGTH');
			throw new OIDplusException(_L('The identifier %1 is too long (max allowed length: %2)',$id,$maxlen));
		}

		OIDplus::db()->query("INSERT INTO ###objects (id, parent, ra_email, confidential, comment, created, title, description) VALUES (?, ?, ?, ?, ?, ".OIDplus::db()->sqlDate().", ?, ?)", array($id, $parent, $ra_email, $confidential, $comment, $title, $description));
		OIDplusObject::resetObjectInformationCache();

		// Set ASN.1 IDs und IRIs
		if ($obj::ns() == 'oid') {
			assert($obj instanceof OIDplusOid); //assert(get_class($obj) === "ViaThinkSoft\OIDplus\OIDplusOid");
			if (!$obj->isWellKnown()) {
				if (isset($params['iris'])) {
					$ids = ($params['iris'] == '') ? array() : (is_array($params['iris']) ? $params['iris'] : explode(',',$params['iris']));
					$ids = array_map('trim',$ids);
					$obj->replaceIris($ids, false);
				}

				if (isset($params['asn1ids'])) {
					$ids = ($params['asn1ids'] == '') ? array() : (is_array($params['asn1ids']) ? $params['asn1ids'] : explode(',',$params['asn1ids']));
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
			assert($obj instanceof OIDplusOid); //assert(get_class($obj) === "ViaThinkSoft\OIDplus\OIDplusOid");
			if ($obj->isWellKnown()) {
				$status += 4;
			}
		}

		if ($obj->userHasWriteRights()) {
			$status += 8;
		}

		foreach (OIDplus::getAllPlugins() as $plugin) {
			if ($plugin instanceof INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_3) {
				$plugin->afterObjectInsert($id, $params);
			}
		}

		return array(
			"status" => $status,
			"inserted_id" => $id
		);
	}

	/**
	 * Generate UUID (will be used by a few plugins)
	 * @param array $params Nothing
	 * @return array status=0 success, status<0 error. If success, then also uuid, intval
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	private function action_UuidGen(array $params): array {
		$uuid = gen_uuid(OIDplus::config()->getValue('uuidgen_expose_mac', '0') == '1');
		if (!$uuid) return array("status" => -1);
		return array(
			"status" => 0,
			"uuid" => $uuid,
			"intval" => substr(uuid_to_oid($uuid),strlen('2.25.'))
		);
	}

	/**
	 * @param string $actionID
	 * @param array $params
	 * @return array
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	public function action(string $actionID, array $params): array {
		if ($actionID == 'Delete') {
			return $this->action_Delete($params);
		} else if ($actionID == 'Update') {
			return $this->action_Update($params);
		} else if ($actionID == 'Insert') {
			return $this->action_Insert($params);
		} else if ($actionID == 'generate_uuid') {
			return $this->action_UuidGen($params);
		} else {
			return parent::action($actionID, $params);
		}
	}

	/**
	 * @param bool $html
	 * @return void
	 * @throws OIDplusException
	 */
	public function init(bool $html=true) {
		OIDplus::config()->prepareConfigKey('oobe_objects_done', '"Out Of Box Experience" wizard for OIDplusPagePublicObjects done once?', '0', OIDplusConfig::PROTECTION_HIDDEN, function($value) {});
		OIDplus::config()->prepareConfigKey('oid_grid_show_weid', 'Show WEID/Base36 column in CRUD grid of OIDs?', '1', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < 0) || ($value > 1)) {
				throw new OIDplusException(_L('Please enter a valid value (0=no, 1=yes).'));
			}
		});
		OIDplus::config()->delete('uuid_prefer_timebased'); // deprecated
		OIDplus::config()->prepareConfigKey('uuidgen_expose_mac', '1=UUID Generator may expose the MAC address of the system, 0=Use random bits instead of MAC address', '0', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < 0) || ($value > 1)) {
				throw new OIDplusException(_L('Please enter a valid value (0=no, 1=yes).'));
			}
		});
	}

	/**
	 * @param string $id
	 * @param array $out
	 * @return array|false
	 * @throws OIDplusException
	 */
	private function tryObject(string $id, array &$out) {
		$parent = null;
		$res = null;
		$row = null;
		$obj = OIDplusObject::parse($id);
		if (!$obj) return false;
		if ($obj->isRoot()) {
			$obj->getContentPage($out['title'], $out['text'], $out['icon']);
			$objParent = null; // $obj->getParent();
		} else {
			$obj = OIDplusObject::findFitting($id); // this time, the object will be found, not just the object type
			if (!$obj) {
				return false;
			} else {
				$obj->getContentPage($out['title'], $out['text'], $out['icon']);
				if (empty($out['title'])) $out['title'] = $obj->defaultTitle();
				if (empty($out['title'])) $out['title'] = explode(':',$obj->nodeId(),2)[1];
				$objParent = $obj->getParent();
			}
		}
		return array($id, $obj, $objParent);
	}

	/**
	 * @param string $id
	 * @return array
	 */
	public static function getAlternativesForQuery(string $id): array {
		// Attention: This is NOT an implementation of INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_7!
		//            This is the function that calls getAlternativesForQuery() of every plugin that implements INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_7

		// e.g. used for "Reverse Alt Id"
		$alternatives = array();
		foreach (OIDplus::getAllPlugins() as $plugin) {
			if ($plugin instanceof INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_7) {
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
		return $alternatives_tmp;
	}

	/**
	 * @param string $id
	 * @param array $out
	 * @param bool $handled
	 * @return void
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	public function gui(string $id, array &$out, bool &$handled) {
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

			if ($cont) {
				list($html, $js, $css) = extractHtmlContents($cont);
				$cont = '';
				if (!empty($js)) $cont .= "<script>\n$js\n</script>";
				if (!empty($css)) $cont .= "<style>\n$css\n</style>";
				$cont .= stripHtmlComments($html);
			}

			$out['text'] = $cont;

			if (strpos($out['text'], '%%OBJECT_TYPE_LIST%%') !== false) {
				$tmp = '<ul>';
				foreach (OIDplus::getEnabledObjectTypes() as $ot) {
					$tmp .= '<li><a '.OIDplus::gui()->link($ot::root()).'>'.htmlentities($ot::objectTypeTitle()).'</a></li>';
				}
				$tmp .= '</ul>';
				$out['text'] = str_replace('%%OBJECT_TYPE_LIST%%', $tmp, $out['text']);
			}
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
				list($id, $obj, $objParent) = $test;
			} else {
				$objParent = null; // to avoid warnings
			}

			// --- If the object type is disabled or not an object at all (e.g. "oidplus:"), then $handled=false
			//     If the object type is enabled but object not found, $handled=true

			$obj = OIDplusObject::parse($id);

			if ($test === false) {
				if (!$obj) {
					// Object type disabled or not known (e.g. ObjectType "oidplus:").
					$handled = false;
					return;
				} else {
					// Object type enabled but identifier not in database
					$handled = true;
					if (isset($_SERVER['SCRIPT_FILENAME']) && (strtolower(basename($_SERVER['SCRIPT_FILENAME'])) !== 'ajax.php')) { // don't send HTTP error codes in ajax.php, because we want a page and not a JavaScript alert box, when someone enters an invalid OID in the GoTo-Box
						http_response_code(404);
					}
					throw new OIDplusHtmlException(_L('The object %1 was not found in this database.','<code>'.htmlentities($id).'</code>'), _L('Object not found'));
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
				throw new OIDplusHtmlException(_L('Please <a %1>log in</a> to receive information about this object.',OIDplus::gui()->link('oidplus:login')), _L('Access denied'), 401);
			}

			// ---

			$out['text'] = '<!-- MARKER 1 -->' . $out['text']; // use this to better control modifyContent!

			if ($objParent) {
				if ($objParent->isRoot()) {
					$parent_link_text = $objParent->objectTypeTitle();
					$out['text'] = '<p><a '.OIDplus::gui()->link($objParent->root()).'><img src="img/arrow_back.png" width="16" alt="'._L('Go back').'"> '._L('Parent node: %1',htmlentities($parent_link_text)).'</a></p>' . $out['text'];
				} else {
					$parent_title = $objParent->getTitle();
					if (empty($parent_title) && ($objParent->ns() == 'oid')) {
						assert($objParent instanceof OIDplusOid); //assert(get_class($objParent) === "ViaThinkSoft\OIDplus\OIDplusOid");
						// If not title is available, then use an ASN.1 identifier
						$res_asn = OIDplus::db()->query("select name from ###asn1id where oid = ?", array($objParent->nodeId()));
						if ($res_asn->any()) {
							$row_asn = $res_asn->fetch_array();
							$parent_title = $row_asn['name']; // TODO: multiple ASN1 ids?
						}
					}

					$parent_link_text = empty($parent_title) ? explode(':',$objParent->nodeId())[1] : $parent_title.' ('.explode(':',$objParent->nodeId())[1].')';

					$out['text'] = '<p><a '.OIDplus::gui()->link($objParent->nodeId()).'><img src="img/arrow_back.png" width="16" alt="'._L('Go back').'"> '._L('Parent node: %1',htmlentities($parent_link_text)).'</a></p>' . $out['text'];
				}
			} else {
				$parent_link_text = _L('Go back to front page');
				$out['text'] = '<p><a '.OIDplus::gui()->link('oidplus:system').'><img src="img/arrow_back.png" width="16" alt="'._L('Go back').'"> '.htmlentities($parent_link_text).'</a></p>' . $out['text'];
			}

			$out['text'] = '<!-- MARKER 0 -->' . $out['text']; // use this to better control modifyContent!

			// ---

			$out['text'] .= '<!-- MARKER 2 -->'; // use this to better control modifyContent!

			if ($obj) {
				$title = $obj->getTitle() ?? '';
				$description = $obj->getDescription() ?? '';
				if (empty(strip_tags($description)) && (stripos($description,'<img') === false)) {
					if (empty($title)) {
						$desc = '<p><i>'._L('No description for this object available').'</i></p>';
					} else {
						$desc = $title;
					}
				} else {
					$desc = self::objDescription($description);
				}

				// $description is the description in the OID table (which the user edits)
				// $desc is the thing that is shown (it can be a title if no description is there, or an MCE editor if the user has write rights)

				if ($obj->userHasWriteRights()) {
					$rand = ++self::$crudCounter;
					$desc = '<noscript><p><font color="red">'._L('You need to enable JavaScript to edit title or description of this object.').'</font></p>'.$desc.'</noscript>';
					$desc .= '<div class="container box" style="display:none" id="descbox_'.$rand.'">';
					$desc .= _L('Title').': <input type="text" name="title" id="titleedit" value="'.htmlentities($title).'"><br><br>'._L('Description').':<br>';
					$desc .= self::showMCE('description', $description);
					$desc .= '<button type="button" name="update_desc" id="update_desc" class="btn btn-success btn-xs update" onclick="OIDplusPagePublicObjects.updateDesc()">'._L('Update description').'</button>';
					$desc .= '</div>';
					$desc .= '<script>$("#descbox_'.$rand.'")[0].style.display = "block";</script>';
				}
			} else {
				$desc = '';
			}

			// ---

			if (strpos($out['text'], '%%DESC%%') !== false) {
				$out['text'] = str_replace('%%DESC%%', $desc, $out['text']);
			}
			if (strpos($out['text'], '%%CRUD%%') !== false) {
				$out['text'] = str_replace('%%CRUD%%', self::showCrud($obj->nodeId()), $out['text']);
			}
			if (strpos($out['text'], '%%RA_INFO%%') !== false) {
				$out['text'] = str_replace('%%RA_INFO%%', OIDplusPagePublicRaInfo::showRaInfo($obj->getRaMail()), $out['text']);
			}

			$out['text'] .= '<!-- MARKER 3 -->'; // use this to better control modifyContent!
			$out['text'] .= '<!-- MARKER 4 -->'; // use this to better control modifyContent!
			$out['text'] .= '<!-- MARKER 5 -->'; // use this to better control modifyContent!
			$alt_ids = $obj->getAltIds();
			if (count($alt_ids) > 0) {
				$out['text'] .= '<h2>'._L('Alternative Identifiers').'</h2>';

				// Sorty by namespace
				usort($alt_ids, function(OIDplusAltId $a, OIDplusAltId $b) {
					if($a->getNamespace() > $b->getNamespace()) {
						return 1;
					}
					elseif($a->getNamespace() < $b->getNamespace()) {
						return -1;
					}
					else {
						return 0;
					}
				});

				$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
				$out['text'] .= '<table class="table table-bordered table-striped">';
				$out['text'] .= '<thead>';
				$out['text'] .= '<tr><th>'._L('Identifier').'</th><th>'._L('Description').'</th></tr>';
				$out['text'] .= '</thead>';
				$out['text'] .= '<tbody>';
				foreach ($alt_ids as $alt_id) {
					$ns = $alt_id->getNamespace();
					$aid = $alt_id->getId();
					$aiddesc = $alt_id->getDescription();
					$suffix = $alt_id->getSuffix();
					$info = $alt_id->getMoreInfoUrl();
					if ($info) $info = ' <a href="'.$info.'" target="_blank"><img src="img/info.png" style="width:16px;height:16px" alt="'._L('More information').'"></a>';
					$out['text'] .= '<tr><td>'.htmlentities($ns.':'.$aid).($suffix ? '<br/><font size="-1">'.htmlentities($suffix).'</font>' : '').'</td><td>'.htmlentities($aiddesc).$info.'</td></tr>';
				}
				$out['text'] .= '</tbody>';
				$out['text'] .= '</table>';
				$out['text'] .= '</div></div>';
			}

			$out['text'] .= '<!-- MARKER 6 -->'; // use this to better control modifyContent!
			$out['text'] .= '<!-- MARKER 7 -->'; // use this to better control modifyContent!
			$out['text'] .= '<!-- MARKER 8 -->'; // use this to better control modifyContent!
			$out['text'] .= '<!-- MARKER 9 -->'; // use this to better control modifyContent!

			foreach (OIDplus::getAllPlugins() as $plugin) {
				if ($plugin instanceof INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_2) {
					$plugin->modifyContent($obj->nodeId(), $out['title'], $out['icon'], $out['text']);
				}
			}

			for ($i=0; $i<=9; $i++) $out['text'] = str_replace("<!-- MARKER $i -->", '', $out['text']);
		}
	}

	/**
	 * @param array $json
	 * @param array $out
	 * @return void
	 */
	private function publicSitemap_rec(array $json, array &$out) {
		foreach ($json as $x) {
			if (isset($x['id']) && $x['id']) {
				$out[] = $x['id'];
			}
			if (isset($x['children'])) {
				$this->publicSitemap_rec($x['children'], $out);
			}
		}
	}

	/**
	 * @param array $out
	 * @return void
	 */
	public function publicSitemap(array &$out) {
		$json = array();
		$this->tree($json, null/*RA EMail*/, false/*HTML tree algorithm*/, "*"/*display all*/);
		$this->publicSitemap_rec($json, $out);
	}

	/**
	 * @param array $json
	 * @param string|null $ra_email
	 * @param bool $nonjs
	 * @param string $req_goto
	 * @return bool
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	public function tree(array &$json, string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
		if ($nonjs) {
			$json[] = array(
				'id' => 'oidplus:system',
				'icon' => OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png',
				'text' => _L('System')
			);

			$objGoto = OIDplusObject::findFitting($req_goto);
			$objGotoParent = $objGoto ? $objGoto->getParent() : null;
			$parent = $objGotoParent ? $objGotoParent->nodeId() : '';

			$objTypesChildren = array();
			foreach (OIDplus::getEnabledObjectTypes() as $ot) {
				$icon = $this->get_treeicon_root($ot);

				$json[] = array(
					'id' => $ot::root(),
					'icon' => $icon,
					'text' => $ot::objectTypeTitle()
				);

				$tmp = OIDplusObject::parse($req_goto);
				if ($tmp && ($ot == get_class($tmp))) {
					// TODO: Instead of just having 3 levels (parent, this and children), it would be better if we'd had a full tree of all parents
					//       on the other hand, for giving search engines content, this is good enough
					if (empty($parent)) {
						$res = OIDplus::db()->query("select * from ###objects where " .
						                            "parent = ? or " .
						                            "id = ? ", array($req_goto, $req_goto));
					} else {
						$res = OIDplus::db()->query("select * from ###objects where " .
						                            "parent = ? or " .
						                            "id = ? or " .
						                            "id = ? ", array($req_goto, $req_goto, $parent));
					}
					$res->naturalSortByField('id');

					$z_used = 0;
					$y_used = 0;
					$x_used = 0;
					$stufe = 0;
					$menu_entries = array();
					$stufen = array();
					while ($row = $res->fetch_object()) {
						$obj = OIDplusObject::parse($row->id);
						if (!$obj) continue; // might happen if the objectType is not available/loaded
						if (!$obj->userHasReadRights()) continue;
						$txt = ($row->title ?? '') == '' ? '' : ' -- '.htmlentities($row->title);

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
			if ($req_goto === "*") {
				$goto_path = true; // display everything recursively
			} else if ($req_goto !== "") {
				$goto = $req_goto;
				$path = array();
				while (true) {
					$path[] = $goto;
					$objGoto = OIDplusObject::findFitting($goto);
					if (!$objGoto) break;
					$objGotoParent = $objGoto->getParent();
					$goto = $objGotoParent ? $objGotoParent->nodeId() : '';
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
				if ($child['icon'] && !file_exists($child['icon'])) $child['icon'] = null; // default icon (folder)
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

	/**
	 * @param string $request
	 * @return array|false
	 */
	public function tree_search(string $request) {
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

	/**
	 * @var int
	 */
	private static $crudCounter = 0;

	/**
	 * @param string $parent
	 * @return string
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	protected static function showCrud(string $parent='oid:'): string {
		$items_total = 0;
		$items_hidden = 0;

		$objParent = OIDplusObject::parse($parent);
		if (!$objParent) return '';
		$parentNS = $objParent::ns();

		// http://oid-info.com/cgi-bin/display?a=list-by-category&category=Not%20allocating%20identifiers
		$no_asn1 = array(
			'oid:1.3.6.1.4.1',
			'oid:1.3.6.1.4.1.37476.9000',
			'oid:1.3.6.1.4.1.37553.8.8',
			'oid:2.16.276.1',
			//'oid:2.25', // according to Olivier, it is OK that UUID owners define their own ASN.1 ID, since the ASN.1 ID is not required to be unique
			//'oid:1.2.840.113556.1.8000.2554' // Adhoc (GUID/UUID-based) customer use. It is probably the same case as the UUID OIDs, after all, these are UUIDs, too.
		);

		// http://oid-info.com/cgi-bin/display?a=list-by-category&category=Not%20allocating%20Unicode%20labels
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
		                               "where parent = ? ", array($parent));
		$result->naturalSortByField('id');

		$rows = array();
		while ($row = $result->fetch_object()) {
			$obj = OIDplusObject::parse($row->id);
			if ($obj) $rows[] = array($obj,$row);
		}

		$enable_weid_presentation = OIDplus::config()->getValue('oid_grid_show_weid');

		$output  = '<div class="container box"><div id="suboid_table" class="table-responsive">';
		$output .= '<table id="crudTable" class="table table-bordered table-striped">';
		$output .= '<thead>';
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
		$output .= '</thead>';

		$output .= '<tbody>';
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

			$date_created = is_null($row->created) || (explode(' ', $row->created)[0] == '0000-00-00') ? '' : explode(' ', $row->created)[0];
			$date_updated = is_null($row->updated) || (explode(' ', $row->updated)[0] == '0000-00-00') ? '' : explode(' ', $row->updated)[0];

			$output .= '<tr>';
			$output .= '     <td><a href="?goto='.urlencode($row->id).'" onclick="openAndSelectNode('.js_escape($row->id).', '.js_escape($parent).'); return false;">'.htmlentities($show_id).'</a>';
			if ($enable_weid_presentation && ($parentNS == 'oid') && $objParent->isRoot()) {
				// To save space horizontal space, the WEIDs were written below the OIDs
				assert($obj instanceof OIDplusOid); //assert(get_class($obj) === "ViaThinkSoft\OIDplus\OIDplusOid");
				$output .= '<br>'.$obj->getWeidNotation(true);
			}
			$output .= '</td>';
			if ($enable_weid_presentation && ($parentNS == 'oid') && !$objParent->isRoot()) {
				assert($obj instanceof OIDplusOid); //assert(get_class($obj) === "ViaThinkSoft\OIDplus\OIDplusOid");
				$output .= '	<td>'.htmlentities($obj->weidArc()).'</td>';
			}
			if ($objParent->userHasWriteRights()) {
				if ($parentNS == 'oid') {
					if ($accepts_asn1) $output .= '     <td><input type="text" id="asn1ids_'.$row->id.'" value="'.implode(', ', $asn1ids).'"></td>';
					if ($accepts_iri)  $output .= '     <td><input type="text" id="iris_'.$row->id.'" value="'.implode(', ', $iris).'"></td>';
				}
				$output .= '     <td><input type="text" id="ra_email_'.$row->id.'" value="'.htmlentities($row->ra_email ?? '').'"></td>';
				$output .= '     <td><input type="text" id="comment_'.$row->id.'" value="'.htmlentities($row->comment ?? '').'"></td>';
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
				$output .= '     <td><a '.OIDplus::gui()->link('oidplus:rainfo$'.str_replace('@','&',$row->ra_email??'')).'>'.htmlentities(empty($row->ra_name??'') ? str_replace('@','&',$row->ra_email??'') : $row->ra_name).'</a></td>';
				$output .= '     <td>'.htmlentities($row->comment ?? '').'</td>';
				$output .= '     <td>'.$date_created.'</td>';
				$output .= '     <td>'.$date_updated.'</td>';
			}
			$output .= '</tr>';
		}
		$output .= '</tbody>';

		$parent_ra_email = $objParent->getRaMail() ;

		// "Create OID" row
		if ($objParent->userHasWriteRights()) {
			$output .= '<tfoot>';
			$output .= '<tr>';
			$prefix = $objParent->crudInsertPrefix();

			$suffix = $objParent->crudInsertSuffix();
			foreach (OIDplus::getObjectTypePlugins() as $plugin) {
				if (($plugin instanceof INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_6) && ($plugin::getObjectTypeClassName()::ns() == $parentNS)) {
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
			$output .= '     <td><input type="text" id="ra_email" value="'.htmlentities($parent_ra_email ?? '').'"></td>';
			$output .= '     <td><input type="text" id="comment" value=""></td>';
			$output .= '     <td><input type="checkbox" id="hide"></td>';
			$output .= '     <td><button type="button" name="insert" id="insert" class="btn btn-success btn-xs update" onclick="OIDplusPagePublicObjects.crudActionInsert('.js_escape($parent).')">'._L('Insert').'</button></td>';
			$output .= '     <td></td>';
			$output .= '     <td></td>';
			$output .= '     <td></td>';
			$output .= '</tr>';
			$output .= '</tfoot>';
		} else {
			if ($items_total-$items_hidden == 0) {
				$cols = ($parentNS == 'oid') ? 7 : 5;
				if ($enable_weid_presentation && ($parentNS == 'oid') && !$objParent->isRoot()) {
					$cols++;
				}
				$output .= '<tfoot>';
				$output .= '<tr><td colspan="'.$cols.'">'._L('No items available').'</td></tr>';
				$output .= '</tfoot>';
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

	/**
	 * @param string $html
	 * @return string
	 */
	protected static function objDescription(string $html): string {
		// We allow HTML, but no hacking
		$html = anti_xss($html);

		return trim_br($html);
	}

	/**
	 * 'quickbars' added 11 July 2019: Disabled because of two problems:
	 *                                 1. When you load TinyMCE via AJAX using the left menu, the quickbar is immediately shown, even if TinyMCE does not have the focus
	 *                                 2. When you load a page without TinyMCE using the left menu, the quickbar is still visible, although there is no edit
	 * 'colorpicker', 'textcolor' and 'contextmenu' added in 07 April 2020, because it is built in in the core.
	 * 'importcss' added 17 September 2020, because it breaks the "Format/Style" dropdown box ("styleselect" toolbar)
	 * 'legacyoutput' added 24 September 2021, because it is declared as deprecated
	 * 'spellchecker' added 6 October 2021, because it is declared as deprecated and marked for removal in TinyMCE 6.0
	 * 'imagetools' and 'toc' added 23 February 2022, because they are declared as deprecated and marked for removal in TinyMCE 6.0 ("moving to premium")
	 * @var string[]
	 */
	public static $exclude_tinymce_plugins = array('fullpage', 'bbcode', 'quickbars', 'colorpicker', 'textcolor', 'contextmenu', 'importcss', 'legacyoutput', 'spellchecker', 'imagetools', 'toc');

	/**
	 * @param string $name
	 * @param string $content
	 * @return string
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	protected static function showMCE(string $name, string $content): string {
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

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_1
	 * @return bool
	 * @throws OIDplusException
	 */
	public function oobeRequested(): bool {
		return OIDplus::config()->getValue('oobe_objects_done') == '0';
	}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_1
	 * @param int $step
	 * @param bool $do_edits
	 * @param bool $errors_happened
	 * @return void
	 */
	public function oobeEntry(int $step, bool $do_edits, bool &$errors_happened)/*: void*/ {
		echo '<h2>'._L('Step %1: Enable/Disable object type plugins',$step).'</h2>';
		echo '<p>'._L('Which object types do you want to manage using OIDplus?').'</p>';

		$enabled_ary = array();

		foreach (OIDplus::getEnabledObjectTypes() as $ot) {
			echo '<input type="checkbox" name="enable_ot_'.$ot::ns().'" id="enable_ot_'.$ot::ns().'"';
			if (isset($_POST['sent'])) {
			        if (isset($_POST['enable_ot_'.$ot::ns()])) {
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
			if (isset($_POST['sent'])) {
			        if (isset($_POST['enable_ot_'.$ot::ns()])) {
					echo ' checked';
					$enabled_ary[] = $ot::ns();
				}
			} else {
			        echo ''; // <-- difference
			}
			echo '> <label for="enable_ot_'.$ot::ns().'">'.htmlentities($ot::objectTypeTitle()).'</label><br>';
		}

		$htmlmsg = '';
		if ($do_edits) {
			try {
				OIDplus::config()->setValue('objecttypes_enabled', implode(';', $enabled_ary));
				OIDplus::config()->setValue('oobe_objects_done', '1');
			} catch (\Exception $e) {
				$htmlmsg = $e instanceof OIDplusException ? $e->getHtmlMessage() : htmlentities($e->getMessage());
				$errors_happened = true;
			}
		}

		echo ' <font color="red"><b>'.$htmlmsg.'</b></font>';
	}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8
	 * @param string|null $user
	 * @return array
	 * @throws OIDplusException
	 */
	public function getNotifications(string $user=null): array {
		$notifications = array();
		$res = OIDplus::db()->query("select id, title from ###objects");
		$res->naturalSortByField('id');
		if ($res->any()) {
			$is_admin_logged_in = OIDplus::authUtils()->isAdminLoggedIn(); // run just once, for performance
			while ($row = $res->fetch_array()) {
				if (empty($row['title'])) {
					if ($user === 'admin') {
						$accept = $is_admin_logged_in;
					} else {
						$accept = false;
						if ($obj = OIDplusObject::parse($row['id'])) {
							if ($obj->userHasWriteRights($user)) {
								$accept = true;
							}
						}
					}

					if ($accept) {
						$notifications[] = new OIDplusNotification('WARN', _L('Object %1 has no title.', '<a '.OIDplus::gui()->link($row['id']).'>'.htmlentities($row['id']).'</a>'));
					}
				}
			}
		}
		return $notifications;
	}

}
