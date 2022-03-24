<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2021 Daniel Marschall, ViaThinkSoft
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

class OIDplusPagePublicAttachments extends OIDplusPagePluginPublic {

	const DIR_UNLOCK_FILE = 'oidplus_upload.dir';

	private static function checkUploadDir($dir) {
		if (!is_dir($dir)) {
			throw new OIDplusException(_L('The attachment directory "%1" is not existing.', $dir));
		}

		$realdir = realpath($dir);
		if ($realdir === false) {
			throw new OIDplusException(_L('The attachment directory "%1" cannot be resolved (realpath).', $dir));
		}

		$unlock_file = $realdir . DIRECTORY_SEPARATOR . self::DIR_UNLOCK_FILE;
		if (!file_exists($unlock_file)) {
			throw new OIDplusException(_L('Unlock file "%1" is not existing in attachment directory "%2".', self::DIR_UNLOCK_FILE, $dir));
		}

		if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
			// Linux check 1: Check for critical directories
			if (self::isCriticalLinuxDirectory($realdir)) {
				throw new OIDplusException(_L('The attachment directory must not be inside a critical system directory!'));
			}

			// Linux check 2: Check file owner
			$file_owner_a = fileowner(OIDplus::localpath().'index.php');
			if ($file_owner_a === false) {
				$file_owner_a = -1;
				$file_owner_a_name = '???';
			} else {
				$tmp = function_exists('posix_getpwuid') ? posix_getpwuid($file_owner_a) : false;
				$file_owner_a_name = $tmp !== false ? $tmp['name'] : 'UID '.$file_owner_a;
			}

			$file_owner_b = fileowner($unlock_file);
			if ($file_owner_b === false) {
				$file_owner_b = -1;
				$file_owner_b_name = '???';
			} else {
				$tmp = function_exists('posix_getpwuid') ? posix_getpwuid($file_owner_b) : false;
				$file_owner_b_name = $tmp !== false ? $tmp['name'] : 'UID '.$file_owner_b;
			}

			if ($file_owner_a != $file_owner_b) {
				throw new OIDplusException(_L('Owner of unlock file "%1" is wrong. It is "%2", but it should be "%3".', $unlock_file, $file_owner_b_name, $file_owner_a_name));
			}
		} else {
			// Windows check 1: Check for critical directories
			if (self::isCriticalWindowsDirectory($realdir)) {
				throw new OIDplusException(_L('The attachment directory must not be inside a critical system directory!'));
			}

			// Note: We will not query the file owner in Windows systems.
			// It would be possible, however, on Windows systems, the file
			// ownership is rather hidden to the user and the user needs
			// to go into several menus and windows in order to see/change
			// the owner. We don't want to over-complicate it to the Windows admin.
		}
	}

	private static function isCriticalWindowsDirectory($dir) {
		$dir .= '\\';
		$windir = isset($_SERVER['SystemRoot']) ? $_SERVER['SystemRoot'].'\\' : 'C:\\Windows\\';
		if (stripos($dir,$windir) === 0) return true;
		return false;
	}

	private static function isCriticalLinuxDirectory($dir) {
		if ($dir == '/') return true;
		$dir .= '/';
		if (strpos($dir,'/bin/') === 0) return true;
		if (strpos($dir,'/boot/') === 0) return true;
		if (strpos($dir,'/dev/') === 0) return true;
		if (strpos($dir,'/etc/') === 0) return true;
		if (strpos($dir,'/lib') === 0) return true;
		if (strpos($dir,'/opt/') === 0) return true;
		if (strpos($dir,'/proc/') === 0) return true;
		if (strpos($dir,'/root/') === 0) return true;
		if (strpos($dir,'/run/') === 0) return true;
		if (strpos($dir,'/sbin/') === 0) return true;
		if (strpos($dir,'/sys/') === 0) return true;
		if (strpos($dir,'/tmp/') === 0) return true;
		if (strpos($dir,'/usr/bin/') === 0) return true;
		if (strpos($dir,'/usr/include/') === 0) return true;
		if (strpos($dir,'/usr/lib') === 0) return true;
		if (strpos($dir,'/usr/sbin/') === 0) return true;
		if (strpos($dir,'/usr/src/') === 0) return true;
		if (strpos($dir,'/var/cache/') === 0) return true;
		if (strpos($dir,'/var/lib') === 0) return true;
		if (strpos($dir,'/var/lock/') === 0) return true;
		if (strpos($dir,'/var/log/') === 0) return true;
		if (strpos($dir,'/var/mail/') === 0) return true;
		if (strpos($dir,'/var/opt/') === 0) return true;
		if (strpos($dir,'/var/run/') === 0) return true;
		if (strpos($dir,'/var/spool/') === 0) return true;
		if (strpos($dir,'/var/tmp/') === 0) return true;
		return false;
	}

	public static function getUploadDir($id=null) {
		// Get base path
		$cfg = OIDplus::config()->getValue('attachment_upload_dir', '');
		$cfg = trim($cfg);
		if ($cfg === '') {
			$basepath = OIDplus::localpath() . 'userdata' . DIRECTORY_SEPARATOR . 'attachments';
		} else {
			$basepath = $cfg;
		}

		try {
			self::checkUploadDir($basepath);
		} catch (Exception $e) {
			$error = _L('This functionality is not available due to a misconfiguration');
			if (OIDplus::authUtils()->isAdminLoggedIn()) {
				$error .= ': '.$e->getMessage();
			} else {
				$error .= '. '._L('Please notify the system administrator. After they log-in, they can see the reason at this place.');
			}
			throw new OIDplusException($error);
		}

		// Get object-specific path
		if (!is_null($id)) {
			$obj = OIDplusObject::parse($id);
			if ($obj === null) throw new OIDplusException(_L('Invalid object "%1"',$id));

			$path_v1 = $basepath . DIRECTORY_SEPARATOR . $obj->getLegacyDirectoryName();
			$path_v1_bug = $basepath . $obj->getLegacyDirectoryName();
			$path_v2 = $basepath . DIRECTORY_SEPARATOR . $obj->getDirectoryName();

			if (is_dir($path_v1)) return $path_v1; // backwards compatibility
			if (is_dir($path_v1_bug)) return $path_v1_bug; // backwards compatibility
			return $path_v2;
		} else {
			return $basepath;
		}
	}

	private function raMayDelete() {
		return OIDplus::config()->getValue('attachments_allow_ra_delete', 0);
	}

	private function raMayUpload() {
		return OIDplus::config()->getValue('attachments_allow_ra_upload', 0);
	}

	public function action($actionID, $params) {

		if ($actionID == 'deleteAttachment') {
			_CheckParamExists($params, 'id');
			$id = $params['id'];
			$obj = OIDplusObject::parse($id);
			if ($obj === null) throw new OIDplusException(_L('Invalid object "%1"',$id));
			if (!$obj->userHasWriteRights()) throw new OIDplusException(_L('Authentication error. Please log in as admin, or as the RA of "%1" to upload an attachment.',$id));

			if (!OIDplus::authUtils()->isAdminLoggedIn() && !$this->raMayDelete()) {
				throw new OIDplusException(_L('The administrator has disabled deleting attachments by RAs.'));
			}

			_CheckParamExists($params, 'filename');
			$req_filename = $params['filename'];
			if (strpos($req_filename, '/') !== false) throw new OIDplusException(_L('Illegal file name'));
			if (strpos($req_filename, '\\') !== false) throw new OIDplusException(_L('Illegal file name'));
			if (strpos($req_filename, '..') !== false) throw new OIDplusException(_L('Illegal file name'));
			if (strpos($req_filename, chr(0)) !== false) throw new OIDplusException(_L('Illegal file name'));

			$uploaddir = self::getUploadDir($id);
			$uploadfile = $uploaddir . DIRECTORY_SEPARATOR . basename($req_filename);

			if (!file_exists($uploadfile)) throw new OIDplusException(_L('File does not exist'));
			@unlink($uploadfile);
			if (file_exists($uploadfile)) {
				OIDplus::logger()->log("[ERR]OID($id)+[ERR]A!", "Attachment file '".basename($uploadfile)."' could not be deleted from object '$id' (problem with permissions?)");
				$msg = _L('Attachment file "%1" could not be deleted from object "%2" (problem with permissions?)',basename($uploadfile),$id);
				if (OIDplus::authUtils()->isAdminLoggedIn()) {
					throw new OIDplusException($msg);
				} else {
					throw new OIDplusException($msg.'. '._L('Please contact the system administrator.'));
				}
			} else {
				// If it was the last file, delete the empty directory
				$ary = @glob($uploaddir . DIRECTORY_SEPARATOR . '*');
				if (is_array($ary) && (count($ary) == 0)) @rmdir($uploaddir);
			}

			OIDplus::logger()->log("[OK]OID($id)+[?INFO/!OK]OIDRA($id)?/[?INFO/!OK]A?", "Deleted attachment '".basename($uploadfile)."' from object '$id'");

			return array("status" => 0);

		} else if ($actionID == 'uploadAttachment') {
			_CheckParamExists($params, 'id');
			$id = $params['id'];
			$obj = OIDplusObject::parse($id);
			if ($obj === null) throw new OIDplusException(_L('Invalid object "%1"',$id));
			if (!$obj->userHasWriteRights()) throw new OIDplusException(_L('Authentication error. Please log in as admin, or as the RA of "%1" to upload an attachment.',$id));

			if (!OIDplus::authUtils()->isAdminLoggedIn() && !$this->raMayUpload()) {
				throw new OIDplusException(_L('The administrator has disabled uploading attachments by RAs.'));
			}

			if (!isset($_FILES['userfile'])) {
				throw new OIDplusException(_L('Please choose a file.'));
			}

			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
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
			$uploadfile = $uploaddir . DIRECTORY_SEPARATOR . basename($req_filename);

			if (!is_dir($uploaddir)) {
				@mkdir($uploaddir, 0777, true);
				if (!is_dir($uploaddir)) {
					OIDplus::logger()->log("[ERR]OID($id)+[ERR]A!", "Upload attachment '".basename($uploadfile)."' to object '$id' failed: Cannot create directory '".basename($uploaddir)."' (problem with permissions?)");
					$msg = _L('Upload attachment "%1" to object "%2" failed',basename($uploadfile),$id).': '._L('Cannot create directory "%1" (problem with permissions?)',basename($uploaddir));
					if (OIDplus::authUtils()->isAdminLoggedIn()) {
						throw new OIDplusException($msg);
					} else {
						throw new OIDplusException($msg.'. '._L('Please contact the system administrator.'));
					}
				}
			}

			if (!@move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
				OIDplus::logger()->log("[ERR]OID($id)+[ERR]A!", "Upload attachment '".basename($uploadfile)."' to object '$id' failed: Cannot move uploaded file into directory (problem with permissions?)");
				$msg = _L('Upload attachment "%1" to object "%2" failed',basename($uploadfile),$id).': '._L('Cannot move uploaded file into directory (problem with permissions?)');
				if (OIDplus::authUtils()->isAdminLoggedIn()) {
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

		$info_txt  = 'Alternative directory for attachments. It must contain a file named "';
		$info_txt .= self::DIR_UNLOCK_FILE;
		$info_txt .= '"';
		if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
			$info_txt .= ' with the same owner as index.php';
		}
		$info_txt .= '. If this setting is empty, then the userdata directory is used.';
		OIDplus::config()->prepareConfigKey('attachment_upload_dir', $info_txt, '', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (trim($value) !== '') {
				self::checkUploadDir($value);
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

		$output = '';
		$doshow = false;

		try {
			$upload_dir = self::getUploadDir($id);
			$files = @glob($upload_dir . DIRECTORY_SEPARATOR . '*');
			$found_files = false;

			$obj = OIDplusObject::parse($id);
			if ($obj === null) throw new OIDplusException(_L('Invalid object "%1"',$id));
			$can_upload = OIDplus::authUtils()->isAdminLoggedIn() || ($this->raMayUpload() && $obj->userHasWriteRights());
			$can_delete = OIDplus::authUtils()->isAdminLoggedIn() || ($this->raMayDelete() && $obj->userHasWriteRights());

			if (OIDplus::authUtils()->isAdminLoggedIn()) {
				$output .= '<p>'._L('Admin info: The directory is %1','<b>'.htmlentities($upload_dir).'</b>').'</p>';
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
			if ($files) foreach ($files as $file) {
				if (is_dir($file)) continue;

				$output .= '<tr>';
				$output .= '<td>'.htmlentities(basename($file)).'</td>';
				$output .= '<td>'.htmlentities(self::convert_filesize(filesize($file), 0)).'</td>';
				$lookup_files = array(
					OIDplus::localpath().'userdata/attachments/filetypes$'.OIDplus::getCurrentLang().'.conf',
					OIDplus::localpath().'userdata/attachments/filetypes.conf',
					OIDplus::localpath().'vendor/danielmarschall/fileformats/filetypes$'.OIDplus::getCurrentLang().'.local', // not recommended
					OIDplus::localpath().'vendor/danielmarschall/fileformats/filetypes.local', // not recommended
					OIDplus::localpath().'vendor/danielmarschall/fileformats/filetypes$'.OIDplus::getCurrentLang().'.conf',
					OIDplus::localpath().'vendor/danielmarschall/fileformats/filetypes.conf'
				);
				$output .= '<td>'.htmlentities(VtsFileTypeDetect::getDescription($file, $lookup_files)).'</td>';

				$output .= '     <td><button type="button" name="download_'.md5($file).'" id="download_'.md5($file).'" class="btn btn-success btn-xs download" onclick="OIDplusPagePublicAttachments.downloadAttachment('.js_escape(OIDplus::webpath(__DIR__,true)).', current_node,'.js_escape(basename($file)).')">'._L('Download').'</button></td>';
				if ($can_delete) {
					$output .= '     <td><button type="button" name="delete_'.md5($file).'" id="delete_'.md5($file).'" class="btn btn-danger btn-xs delete" onclick="OIDplusPagePublicAttachments.deleteAttachment(current_node,'.js_escape(basename($file)).')">'._L('Delete').'</button></td>';
				}

				$output .= '</tr>';
				$doshow = true;
				$found_files = true;
			}

			if (!$found_files) $output .= '<tr><td colspan="'.($can_delete ? 5 : 4).'"><i>'._L('No attachments').'</i></td></tr>';

			$output .= '</table></div>';

			if ($can_upload) {
				$output .= '<form action="javascript:void(0);" onsubmit="return OIDplusPagePublicAttachments.uploadAttachmentOnSubmit(this);" enctype="multipart/form-data" id="uploadAttachmentForm">';
				$output .= '<input type="hidden" name="id" value="'.htmlentities($id).'">';
				$output .= '<div>'._L('Add a file attachment').':<input type="file" name="userfile" value="" id="fileAttachment">';
				$output .= '<br><input type="submit" value="'._L('Upload').'"></div>';
				$output .= '</form>';
				$doshow = true;
			}
		} catch (Exception $e) {
			$doshow = true;
			$output = '<p>'.$e->getMessage().'</p>';
		}

		$output = '<h2>'._L('File attachments').'</h2>' .
		          '<div class="container box">' .
		          $output .
		          '</div>';
		if ($doshow) $text .= $output;
	}

	public function beforeObjectDelete($id) {} // Interface 1.3.6.1.4.1.37476.2.5.2.3.3
	public function afterObjectDelete($id) {
		// Interface 1.3.6.1.4.1.37476.2.5.2.3.3
		// Delete the attachment folder including all files in it (note: Subfolders are not possible)
		$uploaddir = self::getUploadDir($id);
		if ($uploaddir != '') {
			$ary = @glob($uploaddir . DIRECTORY_SEPARATOR . '*');
			if ($ary) foreach ($ary as $a) @unlink($a);
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

		$files = @glob(self::getUploadDir($id) . DIRECTORY_SEPARATOR . '*');
		if ($files) foreach ($files as $file) {
			$out[] = 'attachment-name: '.basename($file);
			$out[] = 'attachment-url: '.OIDplus::webpath(__DIR__,true).'download.php?id='.urlencode($id).'&filename='.urlencode(basename($file));
		}

	}
	public function whoisRaAttributes($email, &$out) {} // Interface 1.3.6.1.4.1.37476.2.5.2.3.4
}
