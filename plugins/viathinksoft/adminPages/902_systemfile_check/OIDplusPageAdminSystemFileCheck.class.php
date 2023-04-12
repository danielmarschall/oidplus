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

class OIDplusPageAdminSystemFileCheck extends OIDplusPagePluginAdmin {

	/**
	 * @param bool $html
	 * @return void
	 */
	public function init(bool $html=true) {
	}

	/**
	 * @param string $actionID
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 */
	public function action(string $actionID, array $params): array {
		return parent::action($actionID, $params);
	}

	/**
	 * @param string $id
	 * @param array $out
	 * @param bool $handled
	 * @return void
	 * @throws OIDplusException
	 */
	public function gui(string $id, array &$out, bool &$handled) {
		$parts = explode('$',$id,2);
		if (!isset($parts[1])) $parts[1] = '';
		if ($parts[0] == 'oidplus:system_file_check') {
			$handled = true;
			$out['title'] = _L('System file check');
			$out['icon']  = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png';

			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				$out['icon'] = 'img/error.png';
				$out['text'] = '<p>'._L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')).'</p>';
				return;
			}

			$out['text'] = '<p>'._L('This tool compares the checksums of the files of your OIDplus installation with the checksums of the OIDplus original SVN version.').'<br>';
			$out['text'] .= _L('Differences could have various reasons, for example, a hotfix you have applied.').'<br>';
			$out['text'] .= _L('The folders "userdata" and "userdata_pub" as well as third-party-plugins (folder "plugins" excluding "viathinksoft") are not listed.').'</p>';
			$out['text'] .= '<p>'._L('Please note: If you believe that you were hacked, you should not trust the output of this tool, because it might be compromised, too.').'</p>';

			if ($parts[1] !== 'go') {
				$out['text'] .= '<p><input type="button" '.OIDplus::gui()->link('oidplus:system_file_check$go').' value="'._L('Start scan').'"> ('._L('Attention: Takes a long time!').')</p>';
			} else {
				@set_time_limit(0);

				$ver = OIDplus::getVersion();
				if (substr($ver,0,4) !== 'svn-') {
					$out['text'] = '<p><font color="red">'.mb_strtoupper(_L('Error')).': '._L('Cannot determine version of the system').'</font></p>';
					return;
				}
				$ver = substr($ver,strlen('svn-'));


				$out['text'] .= '<h2>'._L('Result (comparison with SVN revision %1)', $ver).'</h2>';

				$out['text'] .= '<pre>';

				try {
					$theirs = self::checksumFileToArray(sprintf(OIDplus::getEditionInfo()['checksum_file'],$ver));
					if ($theirs === false) {
						throw new OIDplusException(_L("Cannot download checksum file. Please try again later."));
					}

					$mine = self::getDirContents(OIDplus::localpath());

					$num = 0;

					foreach ($mine as $filename_old => $hash_old) {
						$filename_old = str_replace('\\', '/', $filename_old);
						if (!isset($theirs[$filename_old])) {
							if (
								(substr($filename_old, 0, strlen('userdata/')) !== 'userdata/') &&
								(substr($filename_old, 0, strlen('userdata_pub/')) !== 'userdata_pub/') &&

								// Don't list third-party plugins
								((
										(substr($filename_old, 0, strlen('plugins/')) === 'plugins/') &&
										(
											(explode('/',$filename_old)[1] === 'viathinksoft') ||
											(explode('/',$filename_old)[1] === 'index.html') ||
											(substr(explode('/',$filename_old)[1],-4) === '.xsd')
										)
									) || (substr($filename_old, 0, strlen('plugins/')) !== 'plugins/')) &&

								($filename_old !== 'oidplus_version.txt') &&
								($filename_old !== '.version.php') &&
								($filename_old !== 'composer.lock')
							){
								$num++;
								if (is_dir(OIDplus::localpath().$filename_old)) {
									$out['text'] .= "<b>"._L('Additional directory').":</b> $filename_old\n";
								} else {
									$out['text'] .= "<b>"._L('Additional file').":</b> $filename_old\n";
								}
							}
						}
					}

					foreach ($theirs as $filename_new => $hash_new) {
						if (!isset($mine[$filename_new])) {
							$num++;
							$out['text'] .= "<b>"._L('Missing file').":</b> $filename_new\n";
						}
					}

					foreach ($mine as $filename_old => $hash_old) {
						if (isset($theirs[$filename_old])) {
							$hash_new = $theirs[$filename_old];
							if ($hash_old != $hash_new) {
								$num++;
								$svn_url = sprintf(OIDplus::getEditionInfo()['svn_original'],urlencode($filename_old),urlencode($ver));
								$out['text'] .= "<b>"._L('Checksum mismatch').":</b> $filename_old (<a target=\"_blank\" href=\"$svn_url\">"._L('Expected file contents')."</a>)\n";
							}
						}
					}

					if ($num == 0) {
						$out['text'] .= _L('Everything OK!');
					}
				} catch (\Exception $e) {
					$htmlmsg = $e instanceof OIDplusException ? $e->getHtmlMessage() : htmlentities($e->getMessage());
					$out['text'] .= mb_strtoupper(_L('Error')).': '.$htmlmsg;
				}

				$out['text'] .= '</pre>';
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
			'id' => 'oidplus:system_file_check',
			'icon' => $tree_icon,
			'text' => _L('System file check')
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

	/**
	 * @param string $dir
	 * @param string|null $basepath
	 * @param array $results
	 * @return array
	 */
	private static function getDirContents(string $dir, string $basepath = null, array &$results = array()): array {
		if (is_null($basepath)) $basepath = $dir;
		$basepath = realpath($basepath) . DIRECTORY_SEPARATOR;
		$dir = realpath($dir) . DIRECTORY_SEPARATOR;
		$files = scandir($dir);
		foreach ($files as $file) {
			$path = realpath($dir . DIRECTORY_SEPARATOR . $file);
			if (empty($path)) $path = $dir . DIRECTORY_SEPARATOR . $file;
			if (!is_dir($path)) {
				$xpath = substr($path, strlen($basepath));
				$xpath = str_replace('\\', '/', $xpath);
				$results[$xpath] = @hash_file('sha256', $path);
			} else if ($file != "." && $file != ".." && $file != ".svn" && $file != ".git") {
				self::getDirContents($path, $basepath, $results);
				$xpath = substr($path, strlen($basepath));
				$xpath = str_replace('\\', '/', $xpath);
				$results[$xpath] = hash('sha256', '');
			}
		}
		return $results;
	}

	/**
	 * @param string $checksumfile
	 * @return array|false
	 */
	private static function checksumFileToArray(string $checksumfile) {
		$out = array();

		$cont = url_get_contents($checksumfile);
		if ($cont === false) return false;
		$lines = explode("\n", $cont);

		foreach ($lines as $line) {
			$line = trim($line);
			if ($line == '') continue;
			list($hash, $filename) = explode("\t",$line);
			if (substr($filename,0,strlen('trunk/')) == 'trunk/') {
				$out[substr($filename,strlen('trunk/'))] = $hash;
			}
		}
		return $out;
	}

}
