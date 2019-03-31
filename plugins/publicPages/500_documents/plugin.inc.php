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

class OIDplusPagePublicDocuments extends OIDplusPagePlugin {
	public function type() {
		return 'public';
	}

	public function priority() {
		return 500;
	}

	public function action(&$handled) {
		// Nothing
	}

	public function cfgLoadConfig() {
		// Nothing
	}

	public function cfgSetValue($name, $value) {
		// Nothing
	}

	private static function getDocumentTitle($file) {
		$cont = file_get_contents($file);
		if (preg_match('@<title>(.+)</title>@ismU', $cont, $m)) return $m[1];
		if (preg_match('@<h1>(.+)</h1>@ismU', $cont, $m)) return $m[1];
		if (preg_match('@<h2>(.+)</h2>@ismU', $cont, $m)) return $m[1];
		if (preg_match('@<h3>(.+)</h3>@ismU', $cont, $m)) return $m[1];
		if (preg_match('@<h4>(.+)</h4>@ismU', $cont, $m)) return $m[1];
		if (preg_match('@<h5>(.+)</h5>@ismU', $cont, $m)) return $m[1];
		if (preg_match('@<h6>(.+)</h6>@ismU', $cont, $m)) return $m[1];
		return basename($file);
	}

	public function gui($id, &$out, &$handled) {
		if (explode('$',$id)[0] === 'oidplus:documents') {
			$handled = true;

			$file = @explode('$',$id)[1];
			$auth = @explode('$',$id)[2];

			if (!OIDplus::authUtils()::validateAuthKey("oidplus:documents;$file", $auth)) {
				$out['title'] = 'Access denied';
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>Invalid authentification token</p>';
				return $out;
			}

			if (file_exists($file) && (!is_dir($file))) {
				$out['title'] = $this->getDocumentTitle($file);

				$icon_candidate = pathinfo($file)['dirname'].'/'.pathinfo($file)['filename'].'_big.png';
				if (file_exists($icon_candidate)) {
					$out['icon'] = $icon_candidate;
				} else if (file_exists(__DIR__.'/icon_leaf_big.png')) {
					$out['icon'] = 'plugins/publicPages/'.basename(__DIR__).'/icon_leaf_big.png';
				} else {
					$out['icon'] = '';
				}

				$cont = file_get_contents($file);
				$cont = preg_replace('@^(.+)<body@isU', '', $cont);
				$cont = preg_replace('@</body>.+$@isU', '', $cont);
				$cont = preg_replace('@<title>.+</title>@isU', '', $cont);
				$cont = preg_replace('@<h1>.+</h1>@isU', '', $cont, 1);

				$out['text'] = $cont;
			} else if (is_dir($file)) {
				$out['title'] = ($file == 'doc/') ? 'Documents' : basename($file);

				if ($file == 'doc/') {
					$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/publicPages/'.basename(__DIR__).'/icon_big.png' : '';
				} else {
					$icon_candidate = pathinfo($file)['dirname'].'/'.pathinfo($file)['filename'].'_big.png';
					if (file_exists($icon_candidate)) {
						$out['icon'] = $icon_candidate;
					} else if (file_exists(__DIR__.'/icon_folder_big.png')) {
						$out['icon'] = 'plugins/publicPages/'.basename(__DIR__).'/icon_folder_big.png';
					} else {
						$out['icon'] = null; // no icon
					}
				}

				if (file_exists(__DIR__.'/treeicon.png')) {
					$tree_icon = 'plugins/publicPages/'.basename(__DIR__).'/treeicon.png';
				} else {
					$tree_icon = null; // default icon (folder)
				}

				$out['text'] = '';

				$count = 0;

				$dirs = glob($file.'*'.'/');
				asort($dirs);
				foreach ($dirs as $dir) {
					$icon_candidate = pathinfo($dir)['dirname'].'/'.pathinfo($dir)['filename'].'_tree.png';
					if (file_exists($icon_candidate)) {
						$tree_icon = $icon_candidate;
					} else if (file_exists(__DIR__.'/treeicon_folder.png')) {
						$tree_icon = 'plugins/publicPages/'.basename(__DIR__).'/treeicon_folder.png';
					} else {
						$tree_icon = null; // no icon
					}

					$ic = empty($tree_icon) ? '' : '<img src="'.$tree_icon.'" alt="">';

					$out['text'] .= '<p><a href="?goto=oidplus:documents$'.$dir.'$'.OIDplus::authUtils()::makeAuthKey("oidplus:documents;$dir").'">'.$ic.' '.htmlentities(basename($dir)).'</a></p>';
					$count++;
				}

				$files = glob($file.'/*.htm*');
				asort($files);
				foreach ($files as $file) {
					$icon_candidate = pathinfo($file)['dirname'].'/'.pathinfo($file)['filename'].'_tree.png';
					if (file_exists($icon_candidate)) {
						$tree_icon = $icon_candidate;
					} else if (file_exists(__DIR__.'/treeicon_leaf.png')) {
						$tree_icon = 'plugins/publicPages/'.basename(__DIR__).'/treeicon_leaf.png';
					} else {
						$tree_icon = null; // default icon (folder)
					}
					$ic = empty($tree_icon) ? '' : '<img src="'.$tree_icon.'" alt="">';

					$out['text'] .= '<p><a href="?goto=oidplus:documents$'.$file.'$'.OIDplus::authUtils()::makeAuthKey("oidplus:documents;$file").'">'.$ic.' '.htmlentities($this->getDocumentTitle($file)).'</a></p>';
					$count++;
				}

				if ($count == 0) {
					$out['text'] .= '<p>This folder does not contain any elements</p>';
				}
			} else {
				$out['title'] = 'Not found';
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>This document doesn\'t exist anymore.</p>';
				return $out;
			}
		}
	}

	private function tree_rec(&$children, $rootdir='doc/', $depth=0) {
		if ($depth > 100) return false; // something is wrong!

		$dirs = glob($rootdir.'*'.'/');
		asort($dirs);
		foreach ($dirs as $dir) {
			$tmp = array();
			$this->tree_rec($tmp, $dir, $depth+1);

			$icon_candidate = pathinfo($dir)['dirname'].'/'.pathinfo($dir)['filename'].'_tree.png';
			if (file_exists($icon_candidate)) {
				$tree_icon = $icon_candidate;
			} else if (file_exists(__DIR__.'/treeicon_folder.png')) {
				$tree_icon = 'plugins/publicPages/'.basename(__DIR__).'/treeicon_folder.png';
			} else {
				$tree_icon = null; // default icon (folder)
			}

			$children[] = array(
				'id' => 'oidplus:documents$'.$dir.'$'.OIDplus::authUtils()::makeAuthKey("oidplus:documents;$dir"),
				'icon' => $tree_icon,
				'text' => $this->getDocumentTitle($dir),
				'children' => $tmp
			);
		}

		$files = glob($rootdir.'*.htm*');
		if (count($files) > 0) {
			asort($files);
			foreach ($files as $file) {
				$icon_candidate = pathinfo($file)['dirname'].'/'.pathinfo($file)['filename'].'_tree.png';
				if (file_exists($icon_candidate)) {
					$tree_icon = $icon_candidate;
				} else if (file_exists(__DIR__.'/treeicon_leaf.png')) {
					$tree_icon = 'plugins/publicPages/'.basename(__DIR__).'/treeicon_leaf.png';
				} else {
					$tree_icon = null; // default icon (folder)
				}

				$children[] = array(
					'id' => 'oidplus:documents$'.$file.'$'.OIDplus::authUtils()::makeAuthKey("oidplus:documents;$file"),
					'icon' => $tree_icon,
					'text' => $this->getDocumentTitle($file)
				);
			}
		}
	}

	public function tree(&$json, $ra_email=null) {
		$children = array();

		$this->tree_rec($children, 'doc/');

		if (count($children) > 0) {
			if (file_exists(__DIR__.'/treeicon.png')) {
				$tree_icon = 'plugins/publicPages/'.basename(__DIR__).'/treeicon.png';
			} else {
				$tree_icon = null; // default icon (folder)
			}

			$json[] = array(
				'id' => 'oidplus:documents$doc/$'.OIDplus::authUtils()::makeAuthKey("oidplus:documents;doc/"),
				'icon' => $tree_icon,
				'state' => array("opened" => true),
				'text' => 'Documents',
				'children' => $children
			);
		}
	}
}

OIDplus::registerPagePlugin(new OIDplusPagePublicDocuments());
