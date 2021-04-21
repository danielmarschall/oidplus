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

class OIDplusPageAdminSoftwareUpdate extends OIDplusPagePluginAdmin {

	public function init($html=true) {
	}

	public function action($actionID, $params) {
		if ($actionID == 'update_now') {
			@set_time_limit(0);


			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusException(_L('You need to log in as administrator.'));
			}

			$error = "";
			try {
				$svn = new phpsvnclient(parse_ini_file(__DIR__.'/consts.ini')['svn']);
				$svn->versionFile = 'oidplus_version.txt';

				// We are caching the changed file logs here only in the preview mode.
				// Reason: We want to avoid that the "update/" page becomes an
				// DoS attack vector if there hasn't been an update for a long time,
				// and the list is very large.
				// But we don't want to use cache in the real update, because
				// otherwise it might break the system if an update is made
				// while the ViaThinkSoft server is down (because the file list
				// is cached, and therefore "delete" actions can be made, while
				// adding/downloading does not work)
				$svn->use_cache = false;

				ob_start();
				if (!$svn->updateWorkingCopy(OIDplus::localpath().'oidplus_version.txt', '/trunk', OIDplus::localpath(), false)) {
					$error = _L("Some updates failed. Please see details in the update protocol.");
				}
				$cont = ob_get_contents();
				$cont = str_replace(OIDplus::localpath(), '...', $cont);
				ob_end_clean();
			} catch (Exception $e) {
				$error = $e->getMessage();
			}

			if ($error != "") {
				return array("status" => -1, "error" => $error, "content" => $cont);
			} else {
				return array("status" => 0, "content" => $cont);
			}
		}
	}

	public function gui($id, &$out, &$handled) {
		$parts = explode('.',$id,2);
		if (!isset($parts[1])) $parts[1] = '';
		if ($parts[0] == 'oidplus:software_update') {
			@set_time_limit(0);

			$handled = true;
			$out['title'] = _L('Software update');
			$out['icon']  = OIDplus::webpath(__DIR__).'icon_big.png';

			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>'._L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login')).'</p>';
				return;
			}

			$out['text'] .= '<p><u>'._L('There are three possibilities how to keep OIDplus up-to-date').':</u></p>';

			$out['text'] .= '<p><b>'._L('Method A').'</b>: '._L('Install OIDplus using the subversion tool in your SSH/Linux shell using the command <code>svn co %1</code> and update it regularly with the command <code>svn update</code> . This will automatically download the latest version and check for conflicts. Highly recommended if you have a Shell/SSH access to your webspace!',htmlentities(parse_ini_file(__DIR__.'/consts.ini')['svn']).'/trunk').'</p>';

			$out['text'] .= '<p><b>'._L('Method B').'</b>: '._L('Install OIDplus using the Git client in your SSH/Linux shell using the command <code>git clone %1</code> and update it regularly with the command <code>git pull</code> . This will automatically download the latest version and check for conflicts. Highly recommended if you have a Shell/SSH access to your webspace!','https://github.com/danielmarschall/oidplus.git').'</p>';

			$out['text'] .= '<p><b>'._L('Method C').'</b>: '._L('Install OIDplus by downloading a ZIP file from www.viathinksoft.com, which contains an SVN snapshot, and extract it to your webspace. The ZIP file contains a file named "oidplus_version.txt" which contains the SVN revision of the snapshot. This update-tool will then try to update your files on-the-fly by downloading them from the ViaThinkSoft SVN repository directly into your webspace directory. A change conflict detection is NOT implemented. It is required that the files on your webspace have create/write/delete permissions. Only recommended if you have no access to the SSH/Linux shell.').'</p>';

			$out['text'] .= '<hr>';

			$installType = OIDplus::getInstallType();

			if ($installType === 'ambigous') {
				$out['text'] .= '<font color="red">'.strtoupper(_L('Error')).': '._L('Multiple version files/directories (oidplus_version.txt, .git and .svn) are existing! Therefore, the version is ambiguous!').'</font>';
			} else if ($installType === 'unknown') {
				$out['text'] .= '<font color="red">'.strtoupper(_L('Error')).': '._L('The version cannot be determined, and the update needs to be applied manually!').'</font>';
			} else if (($installType === 'svn-wc') || ($installType === 'git-wc')) {
				if ($installType === 'svn-wc') {
					$out['text'] .= '<p>'._L('You are using <b>method A</b> (SVN working copy).').'</p>';
				} else {
					$out['text'] .= '<p>'._L('You are using <b>method B</b> (Git working copy).').'</p>';
				}

				$local_installation = OIDplus::getVersion();
				try {
					$svn = new phpsvnclient(parse_ini_file(__DIR__.'/consts.ini')['svn']);
					$newest_version = 'svn-'.$svn->getVersion();
				} catch (Exception $e) {
					$newest_version = false;
				}

				$out['text'] .= _L('Local installation: %1',($local_installation ? $local_installation : _L('unknown'))).'<br>';
				$out['text'] .= _L('Latest published version: %1',($newest_version ? $newest_version : _L('unknown'))).'<br>';

				$requireInfo = ($installType === 'svn-wc') ? _L('shell access with svn/svnversion tool, or PDO/SQLite3 PHP extension') : _L('shell access with Git client');
				$updateCommand = ($installType === 'svn-wc') ? 'svn update' : 'git pull';

				if (!$newest_version) {
					$out['text'] .= '<p><font color="red">'._L('OIDplus could not determine the latest version. Probably the ViaThinkSoft server could not be reached.').'</font></p>';
				}
				else if (!$local_installation) {
					$out['text'] .= '<p><font color="red">'._L('OIDplus could not determine its version. (Required: %1). Please update your system manually via the "%2" command regularly.',$requireInfo,$updateCommand).'</font></p>';
				} else if ($local_installation == $newest_version) {
					$out['text'] .= '<p><font color="green">'._L('You are already using the latest version of OIDplus.').'</font></p>';
				} else {
					$out['text'] .= '<p><font color="blue">'._L('Please enter %1 into the SSH shell to update OIDplus to the latest version.','<code>'.$updateCommand.'</code>').'</font></p>';

					$out['text'] .= '<h2 id="update_header">'._L('Preview of update %1 &rarr; %2',$local_installation,$newest_version).'</h2>';

					ob_start();
					try {
						$svn = new phpsvnclient(parse_ini_file(__DIR__.'/consts.ini')['svn']);
						$svn->use_cache = true;
						$svn->updateWorkingCopy(str_replace('svn-', '', $local_installation), '/trunk', OIDplus::localpath(), true);
						$cont = ob_get_contents();
						$cont = str_replace(OIDplus::localpath(), '...', $cont);
					} catch (Exception $e) {
						$cont = _L('Error: %1',$e->getMessage());
					}
					ob_end_clean();

					$out['text'] .= '<pre id="update_infobox">'.$cont.'</pre>';
				}
			} else if ($installType === 'svn-snapshot') {
				$out['text'] .= '<p>'._L('You are using <b>method C</b> (Snapshot ZIP file with oidplus_version.txt file).').'</p>';

				$local_installation = OIDplus::getVersion();
				try {
					$svn = new phpsvnclient(parse_ini_file(__DIR__.'/consts.ini')['svn']);
					$newest_version = 'svn-'.$svn->getVersion();
				} catch (Exception $e) {
					$newest_version = false;
				}

				$out['text'] .= _L('Local installation: %1',($local_installation ? $local_installation : _L('unknown'))).'<br>';
				$out['text'] .= _L('Latest published version: %1',($newest_version ? $newest_version : _L('unknown'))).'<br>';

				if (!$newest_version) {
					$out['text'] .= '<p><font color="red">'._L('OIDplus could not determine the latest version. Probably the ViaThinkSoft server could not be reached.').'</font></p>';
				}
				else if ($local_installation == $newest_version) {
					$out['text'] .= '<p><font color="green">'._L('You are already using the latest version of OIDplus.').'</font></p>';
				} else {
					$out['text'] .= '<p><font color="red">'.strtoupper(_L('Warning')).': '._L('Please make a backup of your files before updating. In case of an error, the OIDplus system (including this update-assistant) might become unavailable. Also, since the web-update does not contain collision-detection, changes you have applied (like adding, removing or modified files) might get reverted/lost! In case the update fails, you can download and extract the complete <a href="https://www.viathinksoft.com/projects/oidplus">SVN-Snapshot ZIP file</a> again. Since all your data should lay inside the folder "userdata" and "userdata_pub", this should be safe.').'</font></p>';
					$out['text'] .= '<form method="POST" action="index.php">';

					$out['text'] .= '<p><input type="button" onclick="OIDplusPageAdminSoftwareUpdate.doUpdateOIDplus()" value="'._L('Update NOW').'"></p>';

					$out['text'] .= '<h2 id="update_header">'._L('Preview of update %1 &rarr; %2',$local_installation,$newest_version).'</h2>';

					ob_start();
					try {
						$svn = new phpsvnclient(parse_ini_file(__DIR__.'/consts.ini')['svn']);
						$svn->use_cache = true;
						$svn->updateWorkingCopy(OIDplus::localpath().'/oidplus_version.txt', '/trunk', OIDplus::localpath(), true);
						$cont = ob_get_contents();
						$cont = str_replace(OIDplus::localpath(), '...', $cont);
					} catch (Exception $e) {
						$cont = _L('Error: %1',$e->getMessage());
					}
					ob_end_clean();

					$out['text'] .= '<pre id="update_infobox">'.$cont.'</pre>';
				}
			}
		} else {
			$handled = false;
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (!OIDplus::authUtils()->isAdminLoggedIn()) return false;

		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = OIDplus::webpath(__DIR__).'treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:software_update',
			'icon' => $tree_icon,
			'text' => _L('Software update')
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}
}
