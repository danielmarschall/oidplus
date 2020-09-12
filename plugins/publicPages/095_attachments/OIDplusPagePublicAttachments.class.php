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

class OIDplusPagePublicAttachments extends OIDplusPagePluginPublic {

	public static function getUploadDir($id) {
		$path = realpath(__DIR__.'/../../../').'/userdata/attachments/';

		$obj = OIDplusObject::parse($id);
		if ($obj === null) throw new OIDplusException(_L('Invalid object "%1"',$id));
		if ($obj::ns() == 'oid') {
			$oid = $obj->nodeId(false);
		} else {
			$oid = null;
			$alt_ids = $obj->getAltIds();
			foreach ($alt_ids as $alt_id) {
				if ($alt_id->getNamespace() == 'oid') {
					$oid = $alt_id->getId();
					break; // we prefer the first OID (for GUIDs, the first OID is the OIDplus-OID, and the second OID is the UUID OID)
				}
			}
		}

		if (!is_null($oid) && ($oid != '')) {
			// For OIDs, it is the OID, for other identifiers
			// it it the OID alt ID (generated using the SystemID)
			$path .= str_replace('.', '_', $oid);

		} else {
			// Can happen if you don't have a system ID (due to missing OpenSSL plugin)
			$path .= md5($obj->nodeId(true)); // we don't use $id, because $obj->nodeId(true) is possibly more canonical than $id
		}

		return $path;
	}

	private function raMayDelete() {
		return OIDplus::config()->getValue('attachments_allow_ra_delete', 0);
	}

	private function raMayUpload() {
		return OIDplus::config()->getValue('attachments_allow_ra_upload', 0);
	}

	public function action($actionID, $params) {

		if ($actionID == 'deleteAttachment') {
			$id = $params['id'];
			$obj = OIDplusObject::parse($id);
			if ($obj === null) throw new OIDplusException(_L('Invalid object "%1"',$id));
			if (!$obj->userHasWriteRights()) throw new OIDplusException(_L('Authentication error. Please log in as admin, or as the RA of "%1" to upload an attachment.',$id));

			if (!OIDplus::authUtils()::isAdminLoggedIn() && !$this->raMayDelete()) {
				throw new OIDplusException(_L('The administrator has disabled deleting attachments by RAs.'));
			}

			$req_filename = $params['filename'];
			if (strpos($req_filename, '/') !== false) throw new OIDplusException(_L('Illegal file name'));
			if (strpos($req_filename, '\\') !== false) throw new OIDplusException(_L('Illegal file name'));
			if (strpos($req_filename, '..') !== false) throw new OIDplusException(_L('Illegal file name'));
			if (strpos($req_filename, chr(0)) !== false) throw new OIDplusException(_L('Illegal file name'));

			$uploaddir = self::getUploadDir($id);
			$uploadfile = $uploaddir . '/' . basename($req_filename);

			if (!file_exists($uploadfile)) throw new OIDplusException(_L('File does not exist'));
			@unlink($uploadfile);
			if (file_exists($uploadfile)) {
				OIDplus::logger()->log("[ERR]OID($id)+[ERR]A!", "Attachment file '".basename($uploadfile)."' could not be deleted from object '$id' (problem with permissions?)");
				$msg = _L('Attachment file "%1" could not be deleted from object "%2" (problem with permissions?)',basename($uploadfile),$id);
				if (OIDplus::authUtils()::isAdminLoggedIn()) {
					throw new OIDplusException($msg);
				} else {
					throw new OIDplusException($msg.'. '._L('Please contact the system administrator.'));
				}
			} else {
				// If it was the last file, delete the empty directory
				$ary = glob($uploaddir . '/' . '*');
				if (count($ary) == 0) @rmdir($uploaddir);
			}

			OIDplus::logger()->log("[OK]OID($id)+[?INFO/!OK]OIDRA($id)?/[?INFO/!OK]A?", "Deleted attachment '".basename($uploadfile)."' from object '$id'");

			return array("status" => 0);

		} else if ($actionID == 'uploadAttachment') {

			$id = $params['id'];
			$obj = OIDplusObject::parse($id);
			if ($obj === null) throw new OIDplusException(_L('Invalid object "%1"',$id));
			if (!$obj->userHasWriteRights()) throw new OIDplusException(_L('Authentication error. Please log in as admin, or as the RA of "%1" to upload an attachment.',$id));

			if (!OIDplus::authUtils()::isAdminLoggedIn() && !$this->raMayUpload()) {
				throw new OIDplusException(_L('The administrator has disabled uploading attachments by RAs.'));
			}

			if (!isset($_FILES['userfile'])) {
				throw new OIDplusException(_L('Please choose a file.'));
			}

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				$banned = explode(',', OIDplus::config()->getValue('attachments_block_extensions', ''));
				foreach ($banned as $ext) {
					$ext = trim($ext);
					if ($ext == '') continue;
					if (strtolower(substr(basename($_FILES['userfile']['name']), -strlen($ext)-1)) == strtolower('.'.$ext)) {
						throw new OIDplusException(_L('The file extension "%1" is banned by the administrator (it can be uploaded by the administrator though)',$ext));
					}
				}
			}

			$req_filename = $_FILES['userfile']['name'];
			if (strpos($req_filename, '/') !== false) throw new OIDplusException(_L('Illegal file name'));
			if (strpos($req_filename, '\\') !== false) throw new OIDplusException(_L('Illegal file name'));
			if (strpos($req_filename, '..') !== false) throw new OIDplusException(_L('Illegal file name'));
			if (strpos($req_filename, chr(0)) !== false) throw new OIDplusException(_L('Illegal file name'));

			$uploaddir = self::getUploadDir($id);
			$uploadfile = $uploaddir . '/' . basename($req_filename);

			if (!is_dir($uploaddir)) {
				@mkdir($uploaddir, 0777, true);
				if (!is_dir($uploaddir)) {
					OIDplus::logger()->log("[ERR]OID($id)+[ERR]A!", "Upload attachment '".basename($uploadfile)."' to object '$id' failed: Cannot create directory '".basename($uploaddir)."' (problem with permissions?)");
					$msg = _L('Upload attachment "%1" to object "%2" failed',basename($uploadfile),$id).': '._L('Cannot create directory "%1" (problem with permissions?)',basename($uploaddir));
					if (OIDplus::authUtils()::isAdminLoggedIn()) {
						throw new OIDplusException($msg);
					} else {
						throw new OIDplusException($msg.'. '._L('Please contact the system administrator.'));
					}
				}
			}

			if (!@move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
				OIDplus::logger()->log("[ERR]OID($id)+[ERR]A!", "Upload attachment '".basename($uploadfile)."' to object '$id' failed: Cannot move uploaded file into directory (problem with permissions?)");
				$msg = _L('Upload attachment "%1" to object "%2" failed',basename($uploadfile),$id).': '._L('Cannot move uploaded file into directory (problem with permissions?)');
				if (OIDplus::authUtils()::isAdminLoggedIn()) {
					throw new OIDplusException($msg);
				} else {
					throw new OIDplusException($msg.'. '._L('Please contact the system administrator.'));
				}
			}

			OIDplus::logger()->log("[OK]OID($id)+[?INFO/!OK]OIDRA($id)?/[?INFO/!OK]A?", "Uploaded attachment '".basename($uploadfile)."' to object '$id'");

			return array("status" => 0);
		} else {
			throw new OIDplusException(_L('Unknown action ID'));
		}
	}

	public function init($html=true) {
		OIDplus::config()->prepareConfigKey('attachments_block_extensions', 'Block file name extensions being used in file attachments (comma separated)', 'exe,scr,pif,bat,com,vbs,cmd', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
		});
		OIDplus::config()->prepareConfigKey('attachments_allow_ra_delete', 'Allow that RAs delete file attachments? (0=no, 1=yes)', '0', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < 0) || ($value > 1)) {
				throw new OIDplusException(_L('Please enter a valid value (0=no, 1=yes).'));
			}
		});
		OIDplus::config()->prepareConfigKey('attachments_allow_ra_upload', 'Allow that RAs upload file attachments? (0=no, 1=yes)', '0', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < 0) || ($value > 1)) {
				throw new OIDplusException(_L('Please enter a valid value (0=no, 1=yes).'));
			}
		});
	}

	public function gui($id, &$out, &$handled) {
		// Nothing
	}

	public function publicSitemap(&$out) {
		// Nothing
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		return false;
	}

	private static function convert_filesize($bytes, $decimals = 2){
		$size = array(_L('Bytes'),_L('KiB'),_L('MiB'),_L('GiB'),_L('TiB'),_L('PiB'),_L('EiB'),_L('ZiB'),_L('YiB'));
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . @$size[$factor];
	}

	public function implementsFeature($id) {
		if (strtolower($id) == '1.3.6.1.4.1.37476.2.5.2.3.2') return true; // modifyContent
		if (strtolower($id) == '1.3.6.1.4.1.37476.2.5.2.3.3') return true; // beforeObject*, afterObject*
		if (strtolower($id) == '1.3.6.1.4.1.37476.2.5.2.3.4') return true; // whois*Attributes
		return false;
	}

	public function modifyContent($id, &$title, &$icon, &$text) {
		// Interface 1.3.6.1.4.1.37476.2.5.2.3.2

		$files = glob(self::getUploadDir($id).'/'.'*');
		$doshow = false;
		$output = '';
		$found_files = false;

		$obj = OIDplusObject::parse($id);
		if ($obj === null) throw new OIDplusException(_L('Invalid object "%1"',$id));
		$can_upload = OIDplus::authUtils()::isAdminLoggedIn() || ($this->raMayUpload() && $obj->userHasWriteRights());
		$can_delete = OIDplus::authUtils()::isAdminLoggedIn() || ($this->raMayDelete() && $obj->userHasWriteRights());

		$output .= '<h2>'._L('File attachments').'</h2>';
		$output .= '<div class="container box">';

		if (OIDplus::authUtils()::isAdminLoggedIn()) {
			$output .= '<p>'._L('Admin info: The directory is %1','<b>'.htmlentities(self::getUploadDir($id)).'</b>').'</p>';
			$doshow = true;
		}

		$output .= '<div id="fileattachments_table" class="table-responsive">';
		$output .= '<table class="table table-bordered table-striped">';
		$output .= '<tr>';
		$output .= '<th>'._L('Filename').'</th>';
		$output .= '<th>'._L('Size').'</th>';
		$output .= '<th>'._L('File type').'</th>';
		$output .= '<th>'._L('Download').'</th>';
		if ($can_delete) $output .= '<th>'._L('Delete').'</th>';
		$output .= '</tr>';
		foreach ($files as $file) {
			if (is_dir($file)) continue;

			$output .= '<tr>';
			$output .= '<td>'.htmlentities(basename($file)).'</td>';
			$output .= '<td>'.htmlentities(self::convert_filesize(filesize($file), 0)).'</td>';
			$lookup_files = array(
				__DIR__.'/../../../userdata/attachments/filetypes$'.OIDplus::getCurrentLang().'.conf',
				__DIR__.'/../../../userdata/attachments/filetypes.conf',
				__DIR__.'/../../../3p/vts_fileformats/filetypes$'.OIDplus::getCurrentLang().'.local', // not recommended
				__DIR__.'/../../../3p/vts_fileformats/filetypes.local', // not recommended
				__DIR__.'/../../../3p/vts_fileformats/filetypes$'.OIDplus::getCurrentLang().'.conf',
				__DIR__.'/../../../3p/vts_fileformats/filetypes.conf'
			);
			$output .= '<td>'.htmlentities(VtsFileTypeDetect::getDescription($file, $lookup_files)).'</td>';

			$output .= '     <td><button type="button" name="download_'.md5($file).'" id="download_'.md5($file).'" class="btn btn-success btn-xs download" onclick="downloadAttachment('.js_escape(OIDplus::webpath(__DIR__)).', current_node,'.js_escape(basename($file)).')">'._L('Download').'</button></td>';
			if ($can_delete) {
				$output .= '     <td><button type="button" name="delete_'.md5($file).'" id="delete_'.md5($file).'" class="btn btn-danger btn-xs delete" onclick="deleteAttachment(current_node,'.js_escape(basename($file)).')">'._L('Delete').'</button></td>';
			}

			$output .= '</tr>';
			$doshow = true;
			$found_files = true;
		}

		if (!$found_files) $output .= '<tr><td colspan="'.($can_delete ? 5 : 4).'"><i>'._L('No attachments').'</i></td></tr>';

		$output .= '</table></div>';

		if ($can_upload) {
			$output .= '<form onsubmit="return uploadAttachmentOnSubmit(this);" enctype="multipart/form-data" id="uploadAttachmentForm">';
			$output .= '<input type="hidden" name="id" value="'.htmlentities($id).'">';
			$output .= '<div>'._L('Add a file attachment').':<input type="file" name="userfile" value="" id="fileAttachment">';
			$output .= '<br><input type="submit" value="'._L('Upload').'"></div>';
			$output .= '</form>';
			$doshow = true;
		}

		$output .= '</div>';

		if ($doshow) $text .= $output;
	}

	public function beforeObjectDelete($id) {} // Interface 1.3.6.1.4.1.37476.2.5.2.3.3
	public function afterObjectDelete($id) {
		// Interface 1.3.6.1.4.1.37476.2.5.2.3.3
		// Delete the attachment folder including all files in it (note: Subfolders are not possible)
		$uploaddir = self::getUploadDir($id);
		if ($uploaddir != '') {
			$ary = glob($uploaddir . '/' . '*');
			foreach ($ary as $a) @unlink($a);
			@rmdir($uploaddir);
			if (is_dir($uploaddir)) {
				OIDplus::logger()->log("[WARN]OID($id)+[WARN]A!", "Attachment directory '$uploaddir' could not be deleted during the deletion of the OID");
			}
		}
	}
	public function beforeObjectUpdateSuperior($id, &$params) {} // Interface 1.3.6.1.4.1.37476.2.5.2.3.3
	public function afterObjectUpdateSuperior($id, &$params) {} // Interface 1.3.6.1.4.1.37476.2.5.2.3.3
	public function beforeObjectUpdateSelf($id, &$params) {} // Interface 1.3.6.1.4.1.37476.2.5.2.3.3
	public function afterObjectUpdateSelf($id, &$params) {} // Interface 1.3.6.1.4.1.37476.2.5.2.3.3
	public function beforeObjectInsert($id, &$params) {} // Interface 1.3.6.1.4.1.37476.2.5.2.3.3
	public function afterObjectInsert($id, &$params) {} // Interface 1.3.6.1.4.1.37476.2.5.2.3.3

	public function tree_search($request) {
		return false;
	}

	public function whoisObjectAttributes($id, &$out) {
		// Interface 1.3.6.1.4.1.37476.2.5.2.3.4

		$files = glob(self::getUploadDir($id).'/'.'*');
		foreach ($files as $file) {
			$out[] = 'attachment-name: '.basename($file);
			$out[] = 'attachment-url: '.OIDplus::getSystemUrl().OIDplus::webpath(__DIR__).'download.php?id='.urlencode($id).'&filename='.urlencode(basename($file));
		}

	}
	public function whoisRaAttributes($email, &$out) {} // Interface 1.3.6.1.4.1.37476.2.5.2.3.4
}
