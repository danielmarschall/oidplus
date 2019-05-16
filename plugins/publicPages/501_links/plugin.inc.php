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

class OIDplusPagePublicLinks extends OIDplusPagePlugin {
	public function type() {
		return 'public';
	}

	public function priority() {
		return 501;
	}

	public function action(&$handled) {
		// Nothing
	}

	public function init($html=true) {
		// Nothing
	}

	public function cfgSetValue($name, $value) {
		// Nothing
	}

	private static function getHyperlinkTitle($file) {
		return preg_replace('/\\.[^.\\s]{3,4}$/', '', basename($file));
	}

	private static function getHyperlinkURL($file) {
		/*
		[{000214A0-0000-0000-C000-000000000046}]
		Prop3=19,2
		[InternetShortcut]
		URL=http://www.example.com/
		IDList=
		*/
		$cont = file_get_contents($file);
		if (!preg_match('@URL=(.+)\n@ismU', $cont, $m)) return null;
		return trim($m[1]);
	}

	public function gui($id, &$out, &$handled) {
		if (explode('$',$id)[0] === 'oidplus:links') {
			$handled = true;

			$file = @explode('$',$id)[1];
			$auth = @explode('$',$id)[2];

			if (!OIDplus::authUtils()::validateAuthKey("oidplus:links;$file", $auth)) {
				$out['title'] = 'Access denied';
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>Invalid authentification token</p>';
				return $out;
			}

			if (file_exists($file) && (!is_dir($file))) {
				$out['title'] = $this->getHyperlinkTitle($file);

				$icon_candidate = pathinfo($file)['dirname'].'/'.pathinfo($file)['filename'].'_big.png';
				if (file_exists($icon_candidate)) {
					$out['icon'] = $icon_candidate;
				} else if (file_exists(__DIR__.'/icon_leaf_big.png')) {
					$out['icon'] = 'plugins/publicPages/'.basename(__DIR__).'/icon_leaf_big.png';
				} else {
					$out['icon'] = '';
				}

				// Should not happen though, due to conditionalselect
				$out['text'] = '<a href="'.htmlentities(self::getHyperlinkURL($file)).'" target="_blank">Open in new window</a>';
			} else if (is_dir($file)) {
				$out['title'] = ($file == 'links/') ? 'External resources' : basename($file);

				if ($file == 'links/') {
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

				$dirs = glob($file.'*'.'/', GLOB_ONLYDIR);
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

					$out['text'] .= '<p><a href="?goto=oidplus:links$'.$dir.'$'.OIDplus::authUtils()::makeAuthKey("oidplus:links;$dir").'">'.$ic.' '.htmlentities(basename($dir)).'</a></p>';
					$count++;
				}

				$files = glob($file.'/*.url');
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

					$out['text'] .= '<p><a href="'.htmlentities(self::getHyperlinkURL($file)).'" target="_blank">'.$ic.' '.htmlentities($this->getHyperlinkTitle($file)).'</a></p>';
					$count++;
				}

				if ($count == 0) {
					$out['text'] .= '<p>This folder does not contain any elements</p>';
				}
			} else {
				$out['title'] = 'Not found';
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>This resource doesn\'t exist anymore.</p>';
				return $out;
			}
		}
	}

	private function tree_rec(&$children, $rootdir='links/', $depth=0) {
		if ($depth > 100) return false; // something is wrong!

		$dirs = glob($rootdir.'*'.'/', GLOB_ONLYDIR);
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
				'id' => 'oidplus:links$'.$dir.'$'.OIDplus::authUtils()::makeAuthKey("oidplus:links;$dir"),
				'icon' => $tree_icon,
				'text' => basename($dir),
				'children' => $tmp
			);
		}

		$files = glob($rootdir.'*.url');
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
				'id' => 'oidplus:links$'.$file.'$'.OIDplus::authUtils()::makeAuthKey("oidplus:links;$file"),
				'conditionalselect' => 'window.open('.js_escape(self::getHyperlinkURL($file)).'); false;',
				'icon' => $tree_icon,
				'text' => $this->getHyperlinkTitle($file)
			);
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		$children = array();

		$this->tree_rec($children, 'links/');

		if (count($children) > 0) {
			if (file_exists(__DIR__.'/treeicon.png')) {
				$tree_icon = 'plugins/publicPages/'.basename(__DIR__).'/treeicon.png';
			} else {
				$tree_icon = null; // default icon (folder)
			}

			$json[] = array(
				'id' => 'oidplus:links$links/$'.OIDplus::authUtils()::makeAuthKey("oidplus:links;links/"),
				'icon' => $tree_icon,
				'state' => array("opened" => true),
				'text' => 'External resources',
				'children' => $children
			);
		}

		return true;
	}
}

OIDplus::registerPagePlugin(new OIDplusPagePublicLinks());
