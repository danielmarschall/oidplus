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

class OIDplusPageAdminSystemFileCheck extends OIDplusPagePluginAdmin {

	public function init($html=true) {
	}

	public function action($actionID, $params) {
	}

	public function gui($id, &$out, &$handled) {
		$parts = explode('.',$id,2);
		if (!isset($parts[1])) $parts[1] = '';
		if ($parts[0] == 'oidplus:system_file_check') {
			@set_time_limit(0);

			$handled = true;
			$out['title'] = _L('System file check');
			$out['icon']  = OIDplus::webpath(__DIR__).'icon_big.png';

			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>'._L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')).'</p>';
				return;
			}

			$out['text'] = '<p>'._L('This tool compares the checksums of the files of your OIDplus installation with the checksums of the OIDplus original SVN version.').'</p>';
			$out['text'] .= '<p>'._L('Differences could have various reasons, for example, a hotfix you have applied or additional plugins you have installed.').'</p>';
			$out['text'] .= '<p>'._L('Please note: If you believe that you were hacked, you should not trust the output of this tool, because it might be compromised, too.').'</p>';

			$ver = OIDplus::getVersion();
			if (substr($ver,0,4) !== 'svn-') {
				$out['text'] = '<p><font color="red">'.strtoupper(_L('Error')).': '._L('Cannot determine version of the system').'</font></p>';
				return;
			}
			$ver = substr($ver,strlen('svn-'));


			$out['text'] .= '<h2>'._L('Result (comparison with SVN revision %1)', $ver).'</h2>';

			$out['text'] .= '<pre>';

			try {
				$mine = self::getDirContents(OIDplus::localpath());
				$theirs = self::checksumFileToArray('https://www.oidplus.com/checksums/svn-rev'.$ver.'.txt');

				$num = 0;

				foreach ($mine as $filename_old => $hash_old) {
					if (!isset($theirs[$filename_old])) {
						if (
						  (substr($filename_old, 0, strlen('userdata/')) !== 'userdata/') &&
						  (substr($filename_old, 0, strlen('userdata_pub/')) !== 'userdata_pub/') &&
						  ($filename_old !== 'oidplus_version.txt')
						){
							$num++;
							$out['text'] .= "<b>"._L('Additional file').":</b> $filename_old\n";
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
							$out['text'] .= "<b>"._L('Checksum mismatch').":</b> $filename_old (<a target=\"_blank\" href=\"https://svn.viathinksoft.com/cgi-bin/viewvc.cgi/oidplus/trunk/$filename_old?revision=$ver&view=co\">"._L('Expected file contents')."</a>)\n";
						}
					}
				}

				if ($num == 0) {
					$out['text'] .= _L('Everything OK!');
				}
			} catch (Exception $e) {
				$out['text'] .= strtoupper(_L('Error')).': '.htmlentities($e->getMessage());
			}

			$out['text'] .= '</pre>';
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
			'id' => 'oidplus:system_file_check',
			'icon' => $tree_icon,
			'text' => _L('System file check')
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}

	private static function getDirContents($dir, $basepath = null, &$results = array()) {
		if (is_null($basepath)) $basepath = $dir;
		$basepath = realpath($basepath) . DIRECTORY_SEPARATOR;
		$dir = realpath($dir) . DIRECTORY_SEPARATOR;
		$files = scandir($dir);
		foreach ($files as $file) {
			$path = realpath($dir . DIRECTORY_SEPARATOR . $file);
			if (!is_dir($path)) {
				$xpath = substr($path, strlen($basepath));
				$xpath = str_replace('\\', '/', $xpath);
				$results[$xpath] = hash_file('sha256', $path);
			} else if ($file != "." && $file != ".." && $file != ".svn" && $file != ".git") {
				self::getDirContents($path, $basepath, $results);
				$xpath = substr($path, strlen($basepath));
				$xpath = str_replace('\\', '/', $xpath);
				$results[$xpath] = hash('sha256', '');
			}
		}
		return $results;
	}

	private static function checksumFileToArray($checksumfile) {
		$out = array();
		$lines = @file($checksumfile);
		if (!$lines) {
			throw new OIDplusException(_L("Cannot download checksum file. Please try again later."));
		}
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
