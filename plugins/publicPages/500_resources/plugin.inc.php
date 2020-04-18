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

if (!defined('IN_OIDPLUS')) die();

class OIDplusPagePublicResources extends OIDplusPagePluginPublic {

	public static function getPluginInformation() {
		$out = array();
		$out['name'] = 'Resources';
		$out['author'] = 'ViaThinkSoft';
		$out['version'] = null;
		$out['descriptionHTML'] = null;
		return $out;
	}

	public function priority() {
		return 500;
	}

	public function action(&$handled) {
		// Nothing
	}

	public function init($html=true) {
		OIDplus::config()->prepareConfigKey('resource_plugin_autoopen_level', 'Resource plugin: How many levels should be open in the treeview when OIDplus is loaded?', 1, 0, 1);
		OIDplus::config()->prepareConfigKey('resource_plugin_title',          'Resource plugin: Title of the resource section?', 'Documents and resources', 0, 1);
		OIDplus::config()->prepareConfigKey('resource_plugin_path',           'Resource plugin: Path that contains the documents?', 'res/', 0, 1);
		OIDplus::config()->prepareConfigKey('resource_plugin_hide_empty_path','Resource plugin: Hide empty paths? 1=on, 0=off', 1, 0, 1);
	}

	public function cfgSetValue($name, $value) {
		if ($name == 'resource_plugin_autoopen_level') {
			if (!is_numeric($value) || ($value < 0)) {
				throw new OIDplusException("Please enter a valid value.");
			}
		}
		
		if ($name == 'resource_plugin_path') {
			// TODO: check if path exists
		}
		
		if ($name == 'resource_plugin_hide_empty_path') {
			if (!is_numeric($value) || (($value != 0) && ($value != 1))) {
				throw new OIDplusException("Please enter a valid value (0=off, 1=on).");
			}
		}
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
		return pathinfo($file, PATHINFO_FILENAME); // filename without extension
	}

	public function gui($id, &$out, &$handled) {
		if (explode('$',$id)[0] === 'oidplus:resources') {
			$handled = true;

			$file = @explode('$',$id)[1];
			$auth = @explode('$',$id)[2];

			if (!OIDplus::authUtils()::validateAuthKey("resources;$file", $auth)) {
				$out['title'] = 'Access denied';
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>Invalid authentication token</p>';
				return $out;
			}

			if (strpos($file, OIDplus::config()->getValue('resource_plugin_path', 'res/')) !== 0) {
				$out['title'] = 'Access denied';
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>Security breach A</p>';
				return $out;
			}

			if (strpos($file, '..') !== false) {
				$out['title'] = 'Access denied';
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>Security breach B</p>';
				return $out;
			}

			$out['text'] = '';

			if ($file != OIDplus::config()->getValue('resource_plugin_path', 'res/')) {
				$dir = dirname($file).'/';

				if ($dir == OIDplus::config()->getValue('resource_plugin_path', 'res/')) {
					if (file_exists(__DIR__.'/treeicon.png')) {
						$tree_icon = OIDplus::webpath(__DIR__).'treeicon.png';
					} else {
						$tree_icon = null; // default icon (folder)
					}

					$ic = empty($tree_icon) ? '' : '<img src="'.$tree_icon.'" alt="">';

					$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:resources$'.OIDplus::config()->getValue('resource_plugin_path', 'res/').'$'.OIDplus::authUtils()::makeAuthKey("resources;".OIDplus::config()->getValue('resource_plugin_path', 'res/'))).'><img src="img/arrow_back.png" width="16"> Go back to: '.$ic.' '.htmlentities(OIDplus::config()->getValue('resource_plugin_title', 'Documents and resources')).'</a></p>';
				} else {
					$icon_candidate = pathinfo($dir)['dirname'].'/'.pathinfo($dir)['filename'].'_tree.png';
					if (file_exists($icon_candidate)) {
						$tree_icon = $icon_candidate;
					} else if (file_exists(__DIR__.'/treeicon_folder.png')) {
						$tree_icon = OIDplus::webpath(__DIR__).'treeicon_folder.png';
					} else {
						$tree_icon = null; // no icon
					}

					$ic = empty($tree_icon) ? '' : '<img src="'.$tree_icon.'" alt="">';

					$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:resources$'.$dir.'$'.OIDplus::authUtils()::makeAuthKey("resources;$dir")).'><img src="img/arrow_back.png" width="16"> Go back to: '.$ic.' '.htmlentities(basename($dir)).'</a></p><br>';
				}
			}

			if (file_exists($file) && (!is_dir($file))) {
				if (substr($file,-4,4) == '.url') {
					$out['title'] = $this->getHyperlinkTitle($file);

					$icon_candidate = pathinfo($file)['dirname'].'/'.pathinfo($file)['filename'].'_big.png';
					if (file_exists($icon_candidate)) {
						$out['icon'] = $icon_candidate;
					} else if (file_exists(__DIR__.'/icon_leaf_url_big.png')) {
						$out['icon'] = OIDplus::webpath(__DIR__).'icon_leaf_url_big.png';
					} else {
						$out['icon'] = '';
					}

					// Should not happen though, due to conditionalselect
					$out['text'] .= '<a href="'.htmlentities(self::getHyperlinkURL($file)).'" target="_blank">Open in new window</a>';
				} else if ((substr($file,-4,4) == '.htm') || (substr($file,-5,5) == '.html')) {
					$out['title'] = $this->getDocumentTitle($file);

					$icon_candidate = pathinfo($file)['dirname'].'/'.pathinfo($file)['filename'].'_big.png';
					if (file_exists($icon_candidate)) {
						$out['icon'] = $icon_candidate;
					} else if (file_exists(__DIR__.'/icon_leaf_doc_big.png')) {
						$out['icon'] = OIDplus::webpath(__DIR__).'icon_leaf_doc_big.png';
					} else {
						$out['icon'] = '';
					}

					$cont = file_get_contents($file);
					$cont = preg_replace('@^(.+)<body[^>]*>@isU', '', $cont);
					$cont = preg_replace('@</body>.+$@isU', '', $cont);
					$cont = preg_replace('@<title>.+</title>@isU', '', $cont);
					$cont = preg_replace('@<h1>.+</h1>@isU', '', $cont, 1);

					$out['text'] .= $cont;
				} else {
					$out['title'] = 'Unknown file type';
					$out['icon'] = 'img/error_big.png';
					$out['text'] = '<p>The system does not know how to handle this file type.</p>';
					return $out;
				}
			} else if (is_dir($file)) {
				$out['title'] = ($file == OIDplus::config()->getValue('resource_plugin_path', 'res/')) ? OIDplus::config()->getValue('resource_plugin_title', 'Documents and resources') : basename($file);

				if ($file == OIDplus::config()->getValue('resource_plugin_path', 'res/')) {
					$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';
				} else {
					$icon_candidate = pathinfo($file)['dirname'].'/'.pathinfo($file)['filename'].'_big.png';
					if (file_exists($icon_candidate)) {
						$out['icon'] = $icon_candidate;
					} else if (file_exists(__DIR__.'/icon_folder_big.png')) {
						$out['icon'] = OIDplus::webpath(__DIR__).'icon_folder_big.png';
					} else {
						$out['icon'] = null; // no icon
					}
				}

				if (file_exists(__DIR__.'/treeicon.png')) {
					$tree_icon = OIDplus::webpath(__DIR__).'treeicon.png';
				} else {
					$tree_icon = null; // default icon (folder)
				}

				$count = 0;

				$dirs = glob($file.'*'.'/', GLOB_ONLYDIR);
				natcasesort($dirs);
				foreach ($dirs as $dir) {
					$icon_candidate = pathinfo($dir)['dirname'].'/'.pathinfo($dir)['filename'].'_tree.png';
					if (file_exists($icon_candidate)) {
						$tree_icon = $icon_candidate;
					} else if (file_exists(__DIR__.'/treeicon_folder.png')) {
						$tree_icon = OIDplus::webpath(__DIR__).'treeicon_folder.png';
					} else {
						$tree_icon = null; // no icon
					}

					$ic = empty($tree_icon) ? '' : '<img src="'.$tree_icon.'" alt="">';

					$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:resources$'.$dir.'$'.OIDplus::authUtils()::makeAuthKey("resources;$dir")).'>'.$ic.' '.htmlentities(basename($dir)).'</a></p>';
					$count++;
				}

				$files = array_merge(
					glob($file.'/'.'*.htm*'), // TODO: also PHP?
					glob($file.'/'.'*.url')
				);
				natcasesort($files);
				foreach ($files as $file) {
					if (substr($file,-4,4) == '.url') {
						$icon_candidate = pathinfo($file)['dirname'].'/'.pathinfo($file)['filename'].'_tree.png';
						if (file_exists($icon_candidate)) {
							$tree_icon = $icon_candidate;
						} else if (file_exists(__DIR__.'/treeicon_leaf_url.png')) {
							$tree_icon = OIDplus::webpath(__DIR__).'treeicon_leaf_url.png';
						} else {
							$tree_icon = null; // default icon (folder)
						}
						$ic = empty($tree_icon) ? '' : '<img src="'.$tree_icon.'" alt="">';

						$hyperlink_pic = ' <img src="'.OIDplus::webpath(__DIR__).'hyperlink.png" widht="13" height="13" alt="Hyperlink" style="top:-3px;position:relative">';

						$out['text'] .= '<p><a href="'.htmlentities(self::getHyperlinkURL($file)).'" target="_blank">'.$ic.' '.htmlentities($this->getHyperlinkTitle($file)).' '.$hyperlink_pic.'</a></p>';
						$count++;
					} else {
						$icon_candidate = pathinfo($file)['dirname'].'/'.pathinfo($file)['filename'].'_tree.png';
						if (file_exists($icon_candidate)) {
							$tree_icon = $icon_candidate;
						} else if (file_exists(__DIR__.'/treeicon_leaf_doc.png')) {
							$tree_icon = OIDplus::webpath(__DIR__).'treeicon_leaf_doc.png';
						} else {
							$tree_icon = null; // default icon (folder)
						}
						$ic = empty($tree_icon) ? '' : '<img src="'.$tree_icon.'" alt="">';

						$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:resources$'.$file.'$'.OIDplus::authUtils()::makeAuthKey("resources;$file")).'>'.$ic.' '.htmlentities($this->getDocumentTitle($file)).'</a></p>';
						$count++;
					}
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

	private function tree_rec(&$children, $rootdir=null, $depth=0) {
		if (is_null($rootdir)) $rootdir = OIDplus::config()->getValue('resource_plugin_path', 'res/');
		if ($depth > 100) return false; // something is wrong!

		$dirs = glob($rootdir.'*'.'/', GLOB_ONLYDIR);
		natcasesort($dirs);
		foreach ($dirs as $dir) {
			$tmp = array();
			$this->tree_rec($tmp, $dir, $depth+1);

			$icon_candidate = pathinfo($dir)['dirname'].'/'.pathinfo($dir)['filename'].'_tree.png';
			if (file_exists($icon_candidate)) {
				$tree_icon = $icon_candidate;
			} else if (file_exists(__DIR__.'/treeicon_folder.png')) {
				$tree_icon = OIDplus::webpath(__DIR__).'treeicon_folder.png';
			} else {
				$tree_icon = null; // default icon (folder)
			}

			$children[] = array(
				'id' => 'oidplus:resources$'.$dir.'$'.OIDplus::authUtils()::makeAuthKey("resources;$dir"),
				'icon' => $tree_icon,
				'text' => basename($dir),
				'children' => $tmp,
				'state' => array("opened" => $depth <= OIDplus::config()->getValue('resource_plugin_autoopen_level', 1)-1)
			);
		}

		$files = array_merge(
			glob($rootdir.'*.htm*'), // TODO: Also PHP?
			glob($rootdir.'*.url')
		);
		natcasesort($files);
		foreach ($files as $file) {
			if (substr($file,-4,4) == '.url') {

				$icon_candidate = pathinfo($file)['dirname'].'/'.pathinfo($file)['filename'].'_tree.png';
				if (file_exists($icon_candidate)) {
					$tree_icon = $icon_candidate;
				} else if (file_exists(__DIR__.'/treeicon_leaf_url.png')) {
					$tree_icon = OIDplus::webpath(__DIR__).'treeicon_leaf_url.png';
				} else {
					$tree_icon = null; // default icon (folder)
				}

				$hyperlink_pic = ' <img src="'.OIDplus::webpath(__DIR__).'hyperlink.png" widht="13" height="13" alt="Hyperlink" style="top:-3px;position:relative">';

				$children[] = array(
					'id' => 'oidplus:resources$'.$file.'$'.OIDplus::authUtils()::makeAuthKey("resources;$file"),
					'conditionalselect' => 'window.open('.js_escape(self::getHyperlinkURL($file)).'); false;',
					'icon' => $tree_icon,
					'text' => $this->getHyperlinkTitle($file).' '.$hyperlink_pic,
					'state' => array("opened" => $depth <= OIDplus::config()->getValue('resource_plugin_autoopen_level', 1)-1)
				);

			} else {
				$icon_candidate = pathinfo($file)['dirname'].'/'.pathinfo($file)['filename'].'_tree.png';
				if (file_exists($icon_candidate)) {
					$tree_icon = $icon_candidate;
				} else if (file_exists(__DIR__.'/treeicon_leaf_doc.png')) {
					$tree_icon = OIDplus::webpath(__DIR__).'treeicon_leaf_doc.png';
				} else {
					$tree_icon = null; // default icon (folder)
				}
				$children[] = array(
					'id' => 'oidplus:resources$'.$file.'$'.OIDplus::authUtils()::makeAuthKey("resources;$file"),
					'icon' => $tree_icon,
					'text' => $this->getDocumentTitle($file),
					'state' => array("opened" => $depth <= OIDplus::config()->getValue('resource_plugin_autoopen_level', 1)-1)
				);
			}
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		$children = array();

		$this->tree_rec($children, OIDplus::config()->getValue('resource_plugin_path', 'res/'));

		if (!OIDplus::config()->getValue('resource_plugin_hide_empty_path', true) || (count($children) > 0)) {
			if (file_exists(__DIR__.'/treeicon.png')) {
				$tree_icon = OIDplus::webpath(__DIR__).'treeicon.png';
			} else {
				$tree_icon = null; // default icon (folder)
			}

			$json[] = array(
				'id' => 'oidplus:resources$'.OIDplus::config()->getValue('resource_plugin_path', 'res/').'$'.OIDplus::authUtils()::makeAuthKey("resources;".OIDplus::config()->getValue('resource_plugin_path', 'res/')),
				'icon' => $tree_icon,
				'state' => array("opened" => true),
				'text' => OIDplus::config()->getValue('resource_plugin_title', 'Documents and resources'),
				'children' => $children
			);
		}

		return true;
	}

	public function tree_search($request) {
		return false;
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
}
