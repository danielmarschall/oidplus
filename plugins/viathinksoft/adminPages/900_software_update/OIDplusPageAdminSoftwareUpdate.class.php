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

class OIDplusPageAdminSoftwareUpdate extends OIDplusPagePluginAdmin
	implements INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8 /* getNotifications */
{

	/**
	 * @param bool $html
	 * @return void
	 */
	public function init(bool $html=true) {
	}

	/**
	 * @return string
	 */
	private function getGitCommand(): string {
		return 'git --git-dir='.escapeshellarg(OIDplus::findGitFolder()).' --work-tree='.escapeshellarg(OIDplus::localpath()).' -C "" pull origin master -s recursive -X theirs';
	}

	/**
	 * @return string
	 */
	private function getSvnCommand(): string {
		return 'svn update --accept theirs-full';
	}

	/**
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 */
	private function action_Update(array $params): array {
		@set_time_limit(0);

		if (!OIDplus::authUtils()->isAdminLoggedIn()) {
			throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')), null, 401);
		}

		if (OIDplus::getInstallType() === 'git-wc') {
			$cmd = $this->getGitCommand().' 2>&1';

			$ec = -1;
			$out = array();
			exec($cmd, $out, $ec);

			$res = _L('Execute command:').' '.$cmd."\n\n".trim(implode("\n",$out));
			if ($ec === 0) {
				$next_version = 'HEAD'; // do not translate
				return array("status" => 0, "content" => $res, "rev" => $next_version);
			} else {
				return array("status" => -1, "error" => $res, "content" => "");
			}
		}
		else if (OIDplus::getInstallType() === 'svn-wc') {
			$cmd = $this->getSvnCommand().' 2>&1';

			$ec = -1;
			$out = array();
			exec($cmd, $out, $ec);

			$res = _L('Execute command:').' '.$cmd."\n\n".trim(implode("\n",$out));
			if ($ec === 0) {
				$next_version = 'HEAD'; // do not translate
				return array("status" => 0, "content" => $res, "rev" => $next_version);
			} else {
				return array("status" => -1, "error" => $res, "content" => "");
			}
		}
		else if (OIDplus::getInstallType() === 'manual') {

			$update_version = $params['update_version'] ?? 1;
			if (($update_version != 1) && ($update_version != 2) && ($update_version != 3)) {
				throw new OIDplusException(_L('Unknown update version'));
			}

			if ($update_version >= 3) {
				$next_version = $params['next_version'];
				$max_version = $params['max_version'] ?? null;
			} else {
				$next_version = $params['rev'];
				$max_version = null;
			}

			// Prepare update for all next versions

			$downloaded_changescripts = [];

			$ver = $next_version;
			do {
				// Download and unzip

				$cont = false;
				$basename = 'changescript_'.$ver.'.txt';
				for ($retry=1; $retry<=3; $retry++) {
					if (function_exists('gzdecode')) {
						$url = OIDplus::getEditionInfo()['update_packages'].$basename.'.gz';
						$cont = url_get_contents($url);
						if ($cont !== false) $cont = @gzdecode($cont);
					} else {
						$url = OIDplus::getEditionInfo()['update_packages'].$basename;
						$cont = url_get_contents($url);
					}
					if ($cont !== false) {
						break;
					} else {
						sleep(1);
					}
				}
				if ($cont === false) throw new OIDplusException(_L("Update %1 could not be downloaded from the remote server (%2). Please try again later.",$ver,$url));

				// Check signature...

				if (function_exists('openssl_verify')) {
					$m = array();
					if (!preg_match('@<\?php /\* <ViaThinkSoftSignature>(.+)</ViaThinkSoftSignature> \*/ \?>\n@ismU', $cont, $m)) {
						throw new OIDplusException(_L("Update package file of revision %1 not digitally signed",$ver));
					}
					$signature = base64_decode($m[1]);

					$naked = preg_replace('@<\?php /\* <ViaThinkSoftSignature>(.+)</ViaThinkSoftSignature> \*/ \?>\n@ismU', '', $cont);
					$hash = hash("sha256", $naked.$basename);

					$public_key = "-----BEGIN PUBLIC KEY-----\r\n".wordwrap(OIDplus::getEditionInfo()['update_public_key'], 64, "\r\n", true)."\r\n-----END PUBLIC KEY-----\r\n";
					if (!openssl_verify($hash, $signature, $public_key, OPENSSL_ALGO_SHA256)) {
						throw new OIDplusException(_L("Update package file of revision %1: Signature invalid",$ver));
					}

				}

				// All OK! Now write the file

				$tmp_filename = 'update_'.generateRandomString(10).'.tmp.php';
				$local_file = OIDplus::localpath().$tmp_filename;

				@file_put_contents($local_file, $cont);

				if (!file_exists($local_file) || (@file_get_contents($local_file) !== $cont)) {
					throw new OIDplusException(_L('Update file could not written. Probably there are no write-permissions to the root folder.'));
				}

				$downloaded_changescripts[] = [ $ver, $tmp_filename ];

			} while (($update_version>=3)&&($ver!=$max_version)&&($ver=$this->getNextVersionFrom($ver,false)));

			# ---


			if ($update_version == 1) {
				// Now call the written file
				// Note: we may not use eval($cont) because the script uses die(),
				// and things in the script might collide with currently (un)loaded source code files, shutdown procedues, etc.
				$web_file = OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).$tmp_filename;
				$res = url_get_contents($web_file);
				if ($res === false) {
					$web_file = OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE).$tmp_filename;
					$res = url_get_contents($web_file);
					if ($res === false) {
						throw new OIDplusException(_L('Update-script %1 could not be executed',$web_file));
					}
				}
				return array("status" => 0, "content" => $res, "rev" => $next_version);
			} else if ($update_version == 2) {
				// In this version, the client will call the web-update file.
				// This has the advantage that it will also work if the system is htpasswd protected
				return array("status" => 0, "update_file" => $tmp_filename, "rev" => $next_version);
			} else if ($update_version == 3) {
				// Version 3:
				// - All changescripts are downloaded at once and then processed purely in JS (reduces risk of bricking if an intermediate version is broken)
				// - We return the next version(s) in the list of changescripts
				// - Versions are not SVN revisions anymore, but version strings
				return array("status" => 0, "update_files" => $downloaded_changescripts);
			} else {
				throw new OIDplusException(_L("Unexpected update version"));
			}
		}
		else {
			throw new OIDplusException(_L('Multiple version files/directories (.git and .svn) are existing! Therefore, the distribution channel is ambiguous!'));
		}
	}

	/**
	 * @param string $actionID
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 */
	public function action(string $actionID, array $params): array {
		if ($actionID == 'update_now') {
			return $this->action_Update($params);
		} else {
			return parent::action($actionID, $params);
		}
	}

	/**
	 * @param string $id
	 * @param array $out
	 * @param bool $handled
	 * @return void
	 * @throws OIDplusException
	 */
	public function gui(string $id, array &$out, bool &$handled) {
		if ($id == 'oidplus:software_update') {
			@set_time_limit(0);

			$handled = true;
			$out['title'] = _L('Software update');
			$out['icon']  = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png';

			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')), $out['title'], 401);
			}

			$out['text'] .= '<div id="update_versioninfo">';

			$out['text'] .= '<p><u>'._L('There are three possibilities how to keep OIDplus up-to-date').':</u></p>';

			if (isset(OIDplus::getEditionInfo()['svnrepo']) && (OIDplus::getEditionInfo()['svnrepo'] != '')) {
				$out['text'] .= '<p><b>'._L('Method A').'</b>: '._L('Install OIDplus using the subversion tool in your SSH/Linux shell using the command <code>svn co %1</code> and update it regularly with the command <code>svn update</code> . This will automatically download the latest version and check for conflicts.',htmlentities(OIDplus::getEditionInfo()['svnrepo']).'/trunk/');
				if (!str_starts_with(PHP_OS, 'WIN')) {
					$out['text'] .= ' '._L('Make sure that you invoke the <code>%1</code> command as the user who runs PHP or that you <code>%1</code> the files after invoking <code>%2</code>','chown -R ...','svn update');
				}
				$out['text'] .= '</p>';
			} else {
				$out['text'] .= '<p><b>'._L('Method A').'</b>: '._L('Distribution via %1 is not possible with this edition of OIDplus','GIT').'</p>';
			}

			if (isset(OIDplus::getEditionInfo()['gitrepo']) && (OIDplus::getEditionInfo()['gitrepo'] != '')) {
				$out['text'] .= '<p><b>'._L('Method B').'</b>: '._L('Install OIDplus using the Git client in your SSH/Linux shell using the command <code>git clone %1</code> and update it regularly with the command <code>git pull</code> . This will automatically download the latest version and check for conflicts.',htmlentities(OIDplus::getEditionInfo()['gitrepo'].'.git'));
				if (!str_starts_with(PHP_OS, 'WIN')) {
					$out['text'] .= ' '._L('Make sure that you invoke the <code>%1</code> command as the user who runs PHP or that you <code>%1</code> the files after invoking <code>%2</code>','chown -R ...','git pull');
				}
				$out['text'] .= '</p>';
			} else {
				$out['text'] .= '<p><b>'._L('Method B').'</b>: '._L('Distribution via %1 is not possible with this edition of OIDplus','SVN').'</p>';
			}

			if (isset(OIDplus::getEditionInfo()['downloadpage']) && (OIDplus::getEditionInfo()['downloadpage'] != '')) {
				$out['text'] .= '<p><b>'._L('Method C').'</b>: '._L('Install OIDplus by downloading an archive file from %1, which contains the latest development version, and extract it to your webspace. This update-tool will then update the files using change-scripts from the remote update server. It is required that the files on your webspace have create/write/delete permissions.','<a href="'.OIDplus::getEditionInfo()['downloadpage'].'">'.parse_url(OIDplus::getEditionInfo()['downloadpage'])['host'].'</a>').'</p>';
			} else {
				$out['text'] .= '<p><b>'._L('Method C').'</b>: '._L('Distribution via %1 is not possible with this edition of OIDplus','Snapshot').'</p>';
			}


			$out['text'] .= '<hr>';

			$installType = OIDplus::getInstallType();

			if ($installType === 'ambigous') {
				$out['text'] .= '<font color="red">'.mb_strtoupper(_L('Error')).': '._L('Multiple version files/directories (.git and .svn) are existing! Therefore, the distribution channel is ambiguous!').'</font>';
				$out['text'] .= '</div>';
			} else if ($installType === 'unknown') {
				$out['text'] .= '<font color="red">'.mb_strtoupper(_L('Error')).': '._L('The version cannot be determined, and the update needs to be applied manually!').'</font>';
				$out['text'] .= '</div>';
			} else if (($installType === 'svn-wc') || ($installType === 'git-wc') || ($installType === 'manual')) {
				if ($installType === 'svn-wc') {
					$out['text'] .= '<p>'._L('You are using <b>method A</b> (SVN working copy).').'</p>';
					$requireInfo = _L('shell access with svn/svnversion tool, or PDO/SQLite3 PHP extension');
					$updateCommand = $this->getSvnCommand();
				} else if ($installType === 'git-wc') {
					$out['text'] .= '<p>'._L('You are using <b>method B</b> (Git working copy).').'</p>';
					$requireInfo = _L('shell access with Git client');
					$updateCommand = $this->getGitCommand();
				} else if ($installType === 'manual') {
					$out['text'] .= '<p>'._L('You are using <b>method C</b> (Snapshot file).').'</p>';
					$requireInfo = ''; // unused
					$updateCommand = ''; // unused
				} else {
					assert(false);
				}

				$local_installation = OIDplus::getVersion();
				$newest_version = $this->getLatestVersion(false);

				$out['text'] .= _L('Local installation: %1',($local_installation ?: _L('unknown'))).'<br>';
				$out['text'] .= _L('Latest published version: %1',($newest_version ?: _L('unknown'))).'<br><br>';

				if (!$newest_version) {
					if (!url_get_contents_available(true, $reason)) {
						$out['text'] .= '<p><font color="red">'._L('OIDplus could not determine the latest version.').'<br>'.$reason.'</p>';
					} else {
						$out['text'] .= '<p><font color="red">'._L('OIDplus could not determine the latest version.').'<br>'._L('Probably the remote server could not be reached.').'</font></p>';
					}
					$out['text'] .= '</div>';
				} else if (!$local_installation) {
					if ($installType === 'manual') {
						$out['text'] .= '<p><font color="red">'._L('OIDplus could not determine its version.').'</font></p>';
					} else {
						$out['text'] .= '<p><font color="red">'._L('OIDplus could not determine its version. (Required: %1). Please update your system manually via the "%2" command regularly.',$requireInfo,$updateCommand).'</font></p>';
					}
					$out['text'] .= '</div>';
				} else if (version_compare($local_installation, $newest_version) >= 0) {
					$out['text'] .= '<p><font color="green">'._L('You are already using the latest version of OIDplus.').'</font></p>';
					$out['text'] .= '</div>';
				} else {
					if (($installType === 'svn-wc') || ($installType === 'git-wc')) {
						if ($installType === 'svn-wc') {
							$shell_diff_cmd = 'svn stat';
						} else if ($installType === 'git-wc') {
							$shell_diff_cmd = 'git status -s';
						} else {
							$shell_diff_cmd = '';
						}

						$can_access_shell = true;
						if ($shell_diff_cmd) {
							$cout = [];
							exec("svn stat", $cout, $ec);
							if ($ec === 0) {
								// TODO: should this also be shown when there is no update available?
								if (trim(implode('',$cout)) !== '') {
									$out['text'] .= '<p><font color="red">'._L('WARNING: There are changes in your working copy which WILL be reverted if you continue!').'</font></p>';
									$out['text'] .= '<p><font color="red">'._L('Detected changes:').'</font></p>';
									$out['text'] .= '<p><font color="red"><pre>'.htmlentities(implode("\n",$cout)).'</pre></font></p>';
								} else {
									$out['text'] .= '<p><font color="green">'._L('Working copy is clean.').'</font></p>';
								}
							} else {
								$can_access_shell = false;
							}
						}

						$out['text'] .= '<p><font color="blue">'._L('Please enter %1 into the SSH shell to update OIDplus to the latest version.','<code>'.$updateCommand.'</code>').'</font></p>';
						if ($can_access_shell) {
							$out['text'] .= '<p>'._L('Alternatively, click this button to execute the command through the web-interface (command execution and write permissions required).').'</p>';
						}
					}

					$next_version = $this->getNextVersionFrom($local_installation,false);
					if ($next_version) {
						$out['text'] .= '<p><input type="button" onclick="OIDplusPageAdminSoftwareUpdate.doUpdateOIDplus('.js_escape($local_installation).', '.js_escape($next_version).', '.js_escape($newest_version).')" value="'._L('Update NOW').'"></p>';
					} else {
						$out['text'] .= '<p><font color="red">'._L('Could not determine next version. Please try again later.').'</font></p>';
					}

					// TODO: Open "system_file_check" without page reload.
					// TODO: Only show link if the plugin is installed
					$out['text'] .= '<p><font color="red">'.mb_strtoupper(_L('Warning')).': '._L('Please make a backup of your files before updating. In case of an error, the OIDplus system (including this update-assistant) might become unavailable. Also, since the web-update does not contain collision-detection, changes you have applied (like adding, removing or modified files) might get reverted/lost! (<a href="%1">Click here to check which files have been modified</a>) In case the update fails, you can download and extract the complete <a href="%s">archive file</a> again. Since all your data should lay inside the folder "userdata" and "userdata_pub", this should be safe.','?goto='.urlencode('oidplus:system_file_check'),OIDplus::getEditionInfo()['downloadpage']).'</font></p>';

					$out['text'] .= '</div>';

					$out['text'] .= $this->showPreview($local_installation, $newest_version);
				}
			}
		} else {
			$handled = false;
		}
	}

	/**
	 * @param array $json
	 * @param string|null $ra_email
	 * @param bool $nonjs
	 * @param string $req_goto
	 * @return bool
	 * @throws OIDplusException
	 */
	public function tree(array &$json, string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
		if (!OIDplus::authUtils()->isAdminLoggedIn()) return false;

		if (file_exists(__DIR__.'/img/main_icon16.png')) {
			$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png';
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

	/**
	 * @param string $request
	 * @return array|false
	 */
	public function tree_search(string $request) {
		return false;
	}


	/*
	 * @return array|false
	 */
	private function changeLogJson() {
		static $cache = null;
		if (!is_null($cache)) return $cache;

		$cache_file = OIDplus::localpath() . 'userdata/cache/master_changelog.json';
		if ((file_exists($cache_file)) && (time()-filemtime($cache_file) <= 10*60/*10 Minutes*/)) {
			$changelog = file_get_contents($cache_file);
		} else {
			$master_changelog = OIDplus::getEditionInfo()['master_changelog'];
			if ((stripos($master_changelog,'http://')===0) || (stripos($master_changelog,'https://')===0)) {
				$changelog = @url_get_contents($master_changelog);
			} else {
				$changelog = @file_get_contents($master_changelog);
			}
			if (!$changelog) return false;
			file_put_contents($cache_file, $changelog);
		}

		$json = @json_decode($changelog, true);
		if (!$json) return false;

		$cache = $json;
		return $json;
	}

	/**
	 * @param string $local_ver
	 * @return false|string
	 */
	private function showChangelog(string $local_ver) {
		try {
			$json = $this->changeLogJson();
			if (!$json) return false;

			$content = '';

			foreach ($json as $data) {
				if (!isset($data['version'])) continue;
				if (version_compare($data['version'], $local_ver) <= 0) continue;

				$data['msg'] = implode("\n", $data['changes']);
				$comment = empty($data['msg']) ? _L('No comment') : $data['msg'];
				$tex = _L("Version %1",$data['version'])." (".$data['date'].") ";
				$tex = str_pad($tex, 48, ' ', STR_PAD_RIGHT);
				$content .= trim($tex . str_replace("\n", "\n".str_repeat(' ', strlen($tex)), $comment));
				$content .= "\n";
			}

			return $content;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * @param bool $allow_dev_version
	 * @return false|string
	 */
	private function getLatestVersion(bool $allow_dev_version=true) {
		try {
			$json = $this->changeLogJson();
			return OIDplus::getVersion($json, $allow_dev_version);
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * @param string $prev_version
	 * @param bool $allow_dev_version
	 * @return false|string
	 */
	private function getNextVersionFrom(string $prev_version, bool $allow_dev_version=true) {
		$json = $this->changeLogJson();
		if (!$json) return false;
		$next_version = false;
		foreach ($json as $v) {
			if (!isset($v['version'])) continue;
			if (!$allow_dev_version && str_ends_with($v['version'],'-dev')) continue;
			if ($v['version'] == $prev_version) {
				break;
			}
			$next_version = $v['version']; // the order of $json is critical: the version in front of our current version is the next available version
		}
		return $next_version;
	}

	/**
	 * @param string $local_installation
	 * @param string $newest_version
	 * @return string
	 */
	private function showPreview(string $local_installation, string $newest_version): string {
		$out = '<h2 id="update_header">'._L('Preview of update %1 &rarr; %2',$local_installation,$newest_version).'</h2>';

		ob_start();
		try {
			$cont = $this->showChangelog($local_installation);
		} catch (\Exception $e) {
			$htmlmsg = $e instanceof OIDplusException ? $e->getHtmlMessage() : htmlentities($e->getMessage());
			$cont = _L('Error: %1',$htmlmsg);
		}
		ob_end_clean();

		$cont = preg_replace('@!!!(.+)\\n@', '<font color="red">!!!\\1</font>'."\n", "$cont\n");
		$cont = preg_replace('@\\*\\*\\*(.+)\\n@', '<strong>!!!\\1</strong>'."\n", "$cont\n");

		$out .= '<pre id="update_infobox">'.$cont.'</pre>';

		return $out;
	}

	/**
	 * Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8
	 * @param string|null $user
	 * @return array
	 * @throws OIDplusException
	 */
	public function getNotifications(string $user=null): array {
		$notifications = array();
		if ((!$user || ($user == 'admin')) && OIDplus::authUtils()->isAdminLoggedIn()) {

			// Following code is based on the VNag plugin (admin 901) code

			$installType = OIDplus::getInstallType();

			if ($installType === 'ambigous') {
				$out_stat = 'WARN';
				$out_msg  = _L('Multiple version files/directories (.git and .svn) are existing! Therefore, the distribution channel is ambiguous!');
			} else if ($installType === 'unknown') {
				$out_stat = 'WARN';
				$out_msg  = _L('The version cannot be determined, and the update needs to be applied manually!');
			} else if (($installType === 'svn-wc') || ($installType === 'git-wc')) {
				if (!url_get_contents_available(true, $reason)) {
					$out_stat = 'WARN';
					$out_msg  = _L('OIDplus could not determine the latest version.').' '.$reason;
				} else {
					$local_installation = OIDplus::getVersion();
					$newest_version = $this->getLatestVersion(false);

					$requireInfo = ($installType === 'svn-wc') ? _L('shell access with svn/svnversion tool, or PDO/SQLite3 PHP extension') : _L('shell access with Git client');
					$updateCommand = ($installType === 'svn-wc') ? 'svn update' : 'git pull';

					if (!$newest_version) {
						$out_stat = 'WARN';
						$out_msg = _L('OIDplus could not determine the latest version.') . ' ' . _L('Probably the remote server could not be reached.');
					} else if (!$local_installation) {
						$out_stat = 'WARN';
						$out_msg = _L('OIDplus could not determine its version (Required: %1). Please update your system manually via the "%2" command regularly.', $requireInfo, $updateCommand);
					} else if (version_compare($local_installation, $newest_version) >= 0) {
						$out_stat = 'INFO';
						$out_msg = _L('You are using the latest version of OIDplus (%1 local / %2 remote)', $local_installation, $newest_version);
					} else {
						$out_stat = 'WARN';
						$out_msg = _L('OIDplus is outdated. (%1 local / %2 remote)', $local_installation, $newest_version);
					}
				}
			} else if ($installType === 'manual') {
				if (!url_get_contents_available(true, $reason)) {
					$out_stat = 'WARN';
					$out_msg  = _L('OIDplus could not determine the latest version.').' '.$reason;
				} else {
					$local_installation = OIDplus::getVersion();
					$newest_version = $this->getLatestVersion(false);

					if (!$newest_version) {
						$out_stat = 'WARN';
						$out_msg = _L('OIDplus could not determine the latest version.') . ' ' . _L('Probably the remote server could not be reached.');
					} else if (!$local_installation) {
						$out_stat = 'WARN';
						$out_msg = _L('OIDplus could not determine its version. Please update your system manually by downloading the latest archive file from oidplus.com.');
					} else if (version_compare($local_installation, $newest_version) >= 0) {
						$out_stat = 'INFO';
						$out_msg = _L('You are using the latest version of OIDplus (%1 local / %2 remote)', $local_installation, $newest_version);
					} else {
						$out_stat = 'WARN';
						$out_msg = _L('OIDplus is outdated. (%1 local / %2 remote)', $local_installation, $newest_version);
					}
				}
			} else {
				assert(false);
				return $notifications;
			}

			if ($out_stat != 'INFO') {
				$out_msg = '<a '.OIDplus::gui()->link('oidplus:software_update').'>'._L('Software update').'</a>: ' . $out_msg;

				$notifications[] = new OIDplusNotification($out_stat, $out_msg);
			}

		}
		return $notifications;
	}

}
