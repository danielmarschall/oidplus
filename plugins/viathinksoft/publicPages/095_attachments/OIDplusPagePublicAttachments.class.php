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

namespace ViaThinkSoft\OIDplus\Plugins\PublicPages\Attachments;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusConfig;
use ViaThinkSoft\OIDplus\Core\OIDplusConfigInitializationException;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusHtmlException;
use ViaThinkSoft\OIDplus\Core\OIDplusObject;
use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginPublic;
use ViaThinkSoft\OIDplus\Plugins\AdminPages\Notifications\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8;
use ViaThinkSoft\OIDplus\Plugins\AdminPages\Notifications\OIDplusNotification;
use ViaThinkSoft\OIDplus\Plugins\PublicPages\Objects\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_2;
use ViaThinkSoft\OIDplus\Plugins\PublicPages\Objects\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_3;
use ViaThinkSoft\OIDplus\Plugins\PublicPages\Whois\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_4;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusPagePublicAttachments extends OIDplusPagePluginPublic
	implements INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_2, /* modifyContent */
	           INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_3, /* beforeObject*, afterObject* */
	           INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_4, /* whois*Attributes */
	           INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8  /* getNotifications */
{

	/**
	 * @param string $dir
	 * @return void
	 * @throws OIDplusException
	 */
	private static function checkUploadDir(string $dir) {
		if (!is_dir($dir)) {
			throw new OIDplusException(_L('The attachment directory "%1" is not existing.', $dir));
		}

		$realdir = realpath($dir);
		if ($realdir === false) {
			throw new OIDplusException(_L('The attachment directory "%1" cannot be resolved (realpath).', $dir));
		}

		// Check for critical directories
		if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
			if (self::isCriticalLinuxDirectory($realdir)) {
				throw new OIDplusException(_L('The attachment directory must not be inside a critical system directory!'));
			}
		} else {
			if (self::isCriticalWindowsDirectory($realdir)) {
				throw new OIDplusException(_L('The attachment directory must not be inside a critical system directory!'));
			}
		}
	}

	/**
	 * @param string $dir
	 * @return bool
	 */
	private static function isCriticalWindowsDirectory(string $dir): bool {
		$dir = rtrim(str_replace('/', '\\', $dir),'\\').'\\';
		$windir = isset($_SERVER['SystemRoot']) ? rtrim($_SERVER['SystemRoot'],'\\').'\\' : 'C:\\Windows\\';
		if (stripos($dir,$windir) === 0) return true;
		return false;
	}

	/**
	 * @param string $dir
	 * @return bool
	 */
	private static function isCriticalLinuxDirectory(string $dir): bool {
		if ($dir == '/') return true;
		$dir = rtrim($dir,'/').'/';
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

	/**
	 * @return string
	 * @throws OIDplusException
	 */
	protected static function getUploadBaseDir(): string {
		// Get base path
		$cfg = OIDplus::config()->getValue('attachment_upload_dir', '');
		$cfg = trim($cfg);
		if ($cfg === '') {
			$basepath = OIDplus::localpath() . 'userdata' . DIRECTORY_SEPARATOR . 'attachments';
		} else {
			$basepath = $cfg;
		}
		return $basepath;
	}

	/**
	 * @param string|null $id
	 * @return string
	 * @throws OIDplusException
	 */
	public static function getUploadDir(?string $id=null): string {
		$basepath = self::getUploadBaseDir();

		try {
			self::checkUploadDir($basepath);
		} catch (\Exception $e) {
			$error = _L('This functionality is not available due to a misconfiguration');
			if (OIDplus::authUtils()->isAdminLoggedIn()) {
				$htmlmsg = $e instanceof OIDplusException ? $e->getHtmlMessage() : htmlentities($e->getMessage());
				$error .= ': '.$htmlmsg;
			} else {
				$error .= '. '._L('Please notify the system administrator. After they log-in, they can see the reason at this place.');
			}
			throw new OIDplusHtmlException($error);
		}

		// Get object-specific path
		if (!is_null($id)) {
			$obj = OIDplusObject::parse($id);
			if (!$obj) throw new OIDplusException(_L('Invalid object "%1"',$id));

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

	/**
	 * @return mixed|null
	 * @throws OIDplusException
	 */
	private function raMayDelete() {
		return OIDplus::config()->getValue('attachments_allow_ra_delete', 0);
	}

	/**
	 * @return mixed|null
	 * @throws OIDplusException
	 */
	private function raMayUpload() {
		return OIDplus::config()->getValue('attachments_allow_ra_upload', 0);
	}


	/**
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 */
	private function action_Delete(array $params): array {
		_CheckParamExists($params, 'id');
		$id = $params['id'];
		$obj = OIDplusObject::parse($id);
		if (!$obj) throw new OIDplusException(_L('Invalid object "%1"',$id));
		if (!$obj->userHasWriteRights()) throw new OIDplusException(_L('Authentication error. Please log in as admin, or as the RA of "%1" to upload an attachment.',$id), null, 401);

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

		foreach (OIDplus::getAllPlugins() as $plugin) {
			if ($plugin instanceof INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_11) {
				$plugin->beforeAttachmentDelete($id, $req_filename);
			}
		}

		if (!file_exists($uploadfile)) throw new OIDplusException(_L('File does not exist'));
		@unlink($uploadfile);
		if (file_exists($uploadfile)) {
			OIDplus::logger()->log("V2:[ERR]OID(%1)+[ERR]A", "Attachment file '%2' could not be deleted from object '%1' (problem with permissions?)", $id, basename($uploadfile));
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

		OIDplus::logger()->log("V2:[OK]OID(%1)+[OK/INFO]OIDRA(%1)+[OK/INFO]A", "Deleted attachment '%2' from object '%1'", $id, basename($uploadfile));

		foreach (OIDplus::getAllPlugins() as $plugin) {
			if ($plugin instanceof INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_11) {
				$plugin->afterAttachmentDelete($id, $req_filename);
			}
		}

		return array("status" => 0);
	}

	/**
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 */
	private function action_Upload(array $params): array {
		_CheckParamExists($params, 'id');
		$id = $params['id'];
		$obj = OIDplusObject::parse($id);
		if (!$obj) throw new OIDplusException(_L('Invalid object "%1"',$id));
		if (!$obj->userHasWriteRights()) throw new OIDplusException(_L('Authentication error. Please log in as admin, or as the RA of "%1" to upload an attachment.',$id), null, 401);

		if (!OIDplus::authUtils()->isAdminLoggedIn() && !$this->raMayUpload()) {
			throw new OIDplusException(_L('The administrator has disabled uploading attachments by RAs.'));
		}

		if (!isset($_FILES['userfile'])) {
			throw new OIDplusException(_L('Please choose a file.'));
		}

		if (!OIDplus::authUtils()->isAdminLoggedIn()) {
			$fname = basename($_FILES['userfile']['name']);

			// 1. If something is on the blacklist, we always block it, even if it is on the whitelist, too
			$banned = explode(',', OIDplus::config()->getValue('attachments_block_extensions', ''));
			foreach ($banned as $ext) {
				$ext = trim($ext);
				if ($ext == '') continue;
				if (strtolower(substr($fname, -strlen($ext)-1)) == strtolower('.'.$ext)) {
					throw new OIDplusException(_L('The file extension "%1" is banned by the administrator (it can be uploaded by the administrator though)',$ext));
				}
			}

			// 2. Something on the whitelist is always OK
			$allowed = explode(',', OIDplus::config()->getValue('attachments_allow_extensions', ''));
			$is_whitelisted = false;
			foreach ($allowed as $ext) {
				$ext = trim($ext);
				if ($ext == '') continue;
				if (strtolower(substr($fname, -strlen($ext)-1)) == strtolower('.'.$ext)) {
					$is_whitelisted = true;
					break;
				}
			}

			// 3. For everything that is neither whitelisted, nor blacklisted, the admin can decide if these grey zone is allowed or blocked
			if (!$is_whitelisted) {
				if (!OIDplus::config()->getValue('attachments_allow_grey_extensions', '1')) {
					$tmp = explode('.', $fname);
					$ext = array_pop($tmp);
					throw new OIDplusException(_L('The file extension "%1" is not on the whitelist (it can be uploaded by the administrator though)',$ext));
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

		foreach (OIDplus::getAllPlugins() as $plugin) {
			if ($plugin instanceof INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_11) {
				$plugin->beforeAttachmentUpload($id, $req_filename, $_FILES['userfile']);
			}
		}

		if (!is_dir($uploaddir)) {
			@mkdir($uploaddir, 0777, true);
			if (!is_dir($uploaddir)) {
				OIDplus::logger()->log("V2:[ERR]OID(%1)+[ERR]A", "Upload attachment '%2' to object '%1' failed: Cannot create directory '%3' (problem with permissions?)", $id, basename($uploadfile), basename($uploaddir));
				$msg = _L('Upload attachment "%1" to object "%2" failed',basename($uploadfile),$id).': '._L('Cannot create directory "%1" (problem with permissions?)',basename($uploaddir));
				if (OIDplus::authUtils()->isAdminLoggedIn()) {
					throw new OIDplusException($msg);
				} else {
					throw new OIDplusException($msg.'. '._L('Please contact the system administrator.'));
				}
			}
		}

		if (!@move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
			OIDplus::logger()->log("V2:[ERR]OID(%1)+[ERR]A", "Upload attachment '%2' to object '%1' failed: Cannot move uploaded file into directory (problem with permissions?)", $id, basename($uploadfile));
			$msg = _L('Upload attachment "%1" to object "%2" failed',basename($uploadfile),$id).': '._L('Cannot move uploaded file into directory (problem with permissions?)');
			if (OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusException($msg);
			} else {
				throw new OIDplusException($msg.'. '._L('Please contact the system administrator.'));
			}
		}

		OIDplus::logger()->log("V2:[OK]OID(%1)+[OK/INFO]OIDRA(%1)+[OK/INFO]A", "Uploaded attachment '%2' to object '%1'", $id, basename($uploadfile));

		foreach (OIDplus::getAllPlugins() as $plugin) {
			if ($plugin instanceof INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_11) {
				$plugin->afterAttachmentUpload($id, $req_filename, $_FILES['userfile']);
			}
		}

		return array("status" => 0);
	}

	/**
	 * @param string $actionID
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 */
	public function action(string $actionID, array $params): array {
		if ($actionID == 'deleteAttachment') {
			return $this->action_Delete($params);
		} else if ($actionID == 'uploadAttachment') {
			return $this->action_Upload($params);
		} else {
			return parent::action($actionID, $params);
		}
	}

	/**
	 * @param bool $html
	 * @return void
	 * @throws OIDplusException
	 */
	public function init(bool $html=true): void {
		OIDplus::config()->prepareConfigKey('attachments_block_extensions', 'Block file name extensions being used in file attachments (comma separated)', 'exe,scr,pif,bat,com,vbs,cmd', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			// TODO: check if a blacklist entry is also on the whitelist (which is not allowed)
		});
		OIDplus::config()->prepareConfigKey('attachments_allow_extensions', 'Allow (whitelist) file name extensions being used in file attachments (comma separated)', '', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			// TODO: check if a whitelist entry is also on the blacklist (which is not allowed)
		});
		OIDplus::config()->prepareConfigKey('attachments_allow_grey_extensions', 'Should file-extensions which are neither be on the whitelist, nor be at the blacklist, be allowed? (1=Yes, 0=No)', '1', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < 0) || ($value > 1)) {
				throw new OIDplusException(_L('Please enter a valid value (0=no, 1=yes).'));
			}
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
		OIDplus::config()->prepareConfigKey('attachment_upload_dir', 'Alternative directory for attachments. If this setting is empty, then the userdata directory is used.', '', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (trim($value) !== '') {
				self::checkUploadDir($value);
			}
		});
	}

	/**
	 * @param string $id
	 * @param array $out
	 * @param bool $handled
	 * @return void
	 */
	public function gui(string $id, array &$out, bool &$handled): void {
		// Nothing
	}

	/**
	 * @param array $out
	 * @return void
	 */
	public function publicSitemap(array &$out): void {
		// Nothing
	}

	/**
	 * @param array $json
	 * @param string|null $ra_email
	 * @param bool $nonjs
	 * @param string $req_goto
	 * @return bool
	 */
	public function tree(array &$json, ?string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
		return false;
	}

	/**
	 * Convert amount of bytes to human-friendly name
	 *
	 * @param int $bytes
	 * @param int $decimals
	 * @return string
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	private static function convert_filesize(int $bytes, int $decimals = 2): string {
		$size = array(_L('Bytes'),_L('KiB'),_L('MiB'),_L('GiB'),_L('TiB'),_L('PiB'),_L('EiB'),_L('ZiB'),_L('YiB'));
		$factor = floor((strlen("$bytes") - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . @$size[$factor];
	}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_2
	 *
	 * @param string $id
	 * @param string $title
	 * @param string $icon
	 * @param string $text
	 * @return void
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	public function modifyContent(string $id, string &$title, string &$icon, string &$text): void {
		$output = '';
		$doshow = false;

		try {
			$upload_dir = self::getUploadDir($id);
			$files = @glob($upload_dir . DIRECTORY_SEPARATOR . '*');
			$found_files = false;

			$obj = OIDplusObject::parse($id);
			if (!$obj) throw new OIDplusException(_L('Invalid object "%1"',$id));
			$can_upload = OIDplus::authUtils()->isAdminLoggedIn() || ($this->raMayUpload() && $obj->userHasWriteRights());
			$can_delete = OIDplus::authUtils()->isAdminLoggedIn() || ($this->raMayDelete() && $obj->userHasWriteRights());

			if (OIDplus::authUtils()->isAdminLoggedIn()) {
				$output .= '<p>'._L('Admin info: The directory is %1','<b>'.htmlentities($upload_dir).'</b>').'</p>';
				$doshow = true;
			}

			$output .= '<div id="fileattachments_table" class="table-responsive">';
			$output .= '<table class="table table-bordered table-striped">';
			$output .= '<thead>';
			$output .= '<tr>';
			$output .= '<th>'._L('Filename').'</th>';
			$output .= '<th>'._L('Size').'</th>';
			$output .= '<th>'._L('File type').'</th>';
			$output .= '<th>'._L('Download').'</th>';
			if ($can_delete) $output .= '<th>'._L('Delete').'</th>';
			$output .= '</tr>';
			$output .= '</thead>';
			$output .= '<tbody>';
			if ($files) foreach ($files as $file) {
				if (is_dir($file)) continue;

				$output .= '<tr>';
				$output .= '<td>'.htmlentities(basename($file)).'</td>';
				$output .= '<td>'.htmlentities(self::convert_filesize(filesize($file), 0)).'</td>';
				$lookup_files = array(
					OIDplus::getUserDataDir("attachments").'filetypes$'.OIDplus::getCurrentLang().'.conf',
					OIDplus::getUserDataDir("attachments").'filetypes.conf',
					OIDplus::localpath().'vendor/danielmarschall/fileformats/filetypes$'.OIDplus::getCurrentLang().'.local', // not recommended
					OIDplus::localpath().'vendor/danielmarschall/fileformats/filetypes.local', // not recommended
					OIDplus::localpath().'vendor/danielmarschall/fileformats/filetypes$'.OIDplus::getCurrentLang().'.conf',
					OIDplus::localpath().'vendor/danielmarschall/fileformats/filetypes.conf'
				);
				$output .= '<td>'.htmlentities(\VtsFileTypeDetect::getDescription($file, $lookup_files)).'</td>';

				$output .= '     <td><button type="button" name="download_'.md5($file).'" id="download_'.md5($file).'" class="btn btn-success btn-xs download" onclick="OIDplusPagePublicAttachments.downloadAttachment('.js_escape(OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE)).', current_node,'.js_escape(basename($file)).')">'._L('Download').'</button></td>';
				if ($can_delete) {
					$output .= '     <td><button type="button" name="delete_'.md5($file).'" id="delete_'.md5($file).'" class="btn btn-danger btn-xs delete" onclick="OIDplusPagePublicAttachments.deleteAttachment(current_node,'.js_escape(basename($file)).')">'._L('Delete').'</button></td>';
				}

				$output .= '</tr>';
				$doshow = true;
				$found_files = true;
			}
			$output .= '</tbody>';

			if (!$found_files) {
				$output .= '<tfoor>';
				$output .= '<tr><td colspan="' . ($can_delete ? 5 : 4) . '"><i>' . _L('No attachments') . '</i></td></tr>';
				$output .= '</tfoot>';
			}

			$output .= '</table></div>';

			if ($can_upload) {
				$output .= '<form action="javascript:void(0);" onsubmit="return OIDplusPagePublicAttachments.uploadAttachmentOnSubmit(this);" enctype="multipart/form-data" id="uploadAttachmentForm">';
				$output .= '<input type="hidden" name="id" value="'.htmlentities($id).'">';
				$output .= '<div>'._L('Add a file attachment').':<input type="file" name="userfile" value="" id="fileAttachment">';
				$output .= '<br><input type="submit" value="'._L('Upload').'"></div>';
				$output .= '</form>';
				$doshow = true;
			}
		} catch (\Exception $e) {
			$doshow = true;
			$htmlmsg = $e instanceof OIDplusException ? $e->getHtmlMessage() : htmlentities($e->getMessage());
			if (strtolower(substr($htmlmsg, 0, 3)) === '<p ') {
				$output = $htmlmsg;
			} else {
				$output = '<p>'.$htmlmsg.'</p>';
			}
		}

		$output = '<h2>'._L('File attachments').'</h2>' .
		          '<div class="container box">' .
		          $output .
		          '</div>';
		if ($doshow) {
			$text = str_replace('<!-- MARKER 5 -->', '<!-- MARKER 5 -->'.$output, $text);
		}
	}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_3
	 * @param string $id
	 * @return void
	 */
	public function beforeObjectDelete(string $id): void {}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_3
	 * @param string $id
	 * @return void
	 * @throws OIDplusException
	 */
	public function afterObjectDelete(string $id): void {
		// Delete the attachment folder including all files in it (note: Subfolders are not possible)
		$uploaddir = self::getUploadDir($id);
		if ($uploaddir != '') {
			$ary = @glob($uploaddir . DIRECTORY_SEPARATOR . '*');
			if ($ary) foreach ($ary as $a) @unlink($a);
			@rmdir($uploaddir);
			if (is_dir($uploaddir)) {
				OIDplus::logger()->log("V2:[WARN]OID(%1)+[WARN]A", "Attachment directory '%2' could not be deleted during the deletion of the OID", $id, $uploaddir);
			}
		}
	}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_3
	 * @param string $id
	 * @param array $params
	 * @return void
	 */
	public function beforeObjectUpdateSuperior(string $id, array &$params): void {}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_3
	 * @param string $id
	 * @param array $params
	 * @return void
	 */
	public function afterObjectUpdateSuperior(string $id, array &$params): void {}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_3
	 * @param string $id
	 * @param array $params
	 * @return void
	 */
	public function beforeObjectUpdateSelf(string $id, array &$params): void {}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_3
	 * @param string $id
	 * @param array $params
	 * @return void
	 */
	public function afterObjectUpdateSelf(string $id, array &$params): void {}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_3
	 * @param string $id
	 * @param array $params
	 * @return void
	 */
	public function beforeObjectInsert(string $id, array &$params): void {}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_3
	 * @param string $id
	 * @param array $params
	 * @return void
	 */
	public function afterObjectInsert(string $id, array &$params): void {}

	/**
	 * @param string $request
	 * @return array|false
	 */
	public function tree_search(string $request) {
		return false;
	}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_4
	 * @param string $id
	 * @param array $out
	 * @return void
	 * @throws OIDplusException
	 */
	public function whoisObjectAttributes(string $id, array &$out): void {
		$xmlns = 'oidplus-attachment-plugin';
		$xmlschema = 'urn:oid:1.3.6.1.4.1.37476.2.5.2.4.1.95.1';
		$xmlschemauri = OIDplus::webpath(__DIR__.'/attachments.xsd',OIDplus::PATH_ABSOLUTE_CANONICAL);

		$files = @glob(self::getUploadDir($id) . DIRECTORY_SEPARATOR . '*');
		if ($files) foreach ($files as $file) {
			$url = OIDplus::webpath(__DIR__,OIDplus::PATH_ABSOLUTE_CANONICAL).'download.php?id='.urlencode($id).'&filename='.urlencode(basename($file));

			$out[] = array(
				'xmlns' => $xmlns,
				'xmlschema' => $xmlschema,
				'xmlschemauri' => $xmlschemauri,
				'name' => 'attachment-name',
				'value' => basename($file)
			);

			$out[] = array(
				'xmlns' => $xmlns,
				'xmlschema' => $xmlschema,
				'xmlschemauri' => $xmlschemauri,
				'name' => 'attachment-url',
				'value' => $url
			);
		}

	}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_4
	 * @param string $email
	 * @param array $out
	 * @return void
	 */
	public function whoisRaAttributes(string $email, array &$out): void {}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8
	 * @param string|null $user
	 * @return array  returns array of array($severity, $htmlMessage)
	 */
	public function getNotifications(?string $user=null): array {
		$notifications = array();
		if ((!$user || ($user == 'admin')) && OIDplus::authUtils()->isAdminLoggedIn()) {
			$error = '';
			try {
				$basepath = self::getUploadBaseDir();
				if (!is_dir($basepath)) {
					throw new OIDplusException(_L('Directory %1 does not exist', $basepath));
				} else {
					self::checkUploadDir($basepath);
					if (!isFileOrPathWritable($basepath)) {
						throw new OIDplusException(_L('Directory %1 is not writeable. Please check the permissions!', $basepath));
					}
				}
			} catch (\Exception $e) {
				$error = _L('The file attachments feature is not available due to a misconfiguration');
				$htmlmsg = $e instanceof OIDplusException ? $e->getHtmlMessage() : htmlentities($e->getMessage());
				$error .= ': ' . $htmlmsg;
			}
			if ($error) {
				$notifications[] = new OIDplusNotification('WARN', $error);
			}
		}
		return $notifications;
	}
}
