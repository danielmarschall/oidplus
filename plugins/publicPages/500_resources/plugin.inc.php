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

define('CFG_RESOURCE_PLUGIN_AUTOOPEN_LEVEL', 1);
define('CFG_RESOURCE_PLUGIN_TITLE', 'Documents and resources');
define('CFG_RESOURCE_PLUGIN_PATH', 'res/');
define('CFG_RESOURCE_PLUGIN_HIDE_EMPTY_PATH', true);

class OIDplusPagePublicResources extends OIDplusPagePlugin {
	public function type() {
		return 'public';
	}

	public function priority() {
		return 500;
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

			if (strpos($file, CFG_RESOURCE_PLUGIN_PATH) !== 0) {
				$out['title'] = 'Access denied';
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>Security breach A</p>';
				return $out;
			}

			if (strpos($file, '..') !== false) {
				$out['title'] = 'Access denied';
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>Security breach A</p>';
				return $out;
			}

			$out['text'] = '';

			if ($file != CFG_RESOURCE_PLUGIN_PATH) {
				$dir = dirname($file).'/';

				if ($dir == CFG_RESOURCE_PLUGIN_PATH) {
					if (file_exists(__DIR__.'/treeicon.png')) {
						$tree_icon = 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/treeicon.png';
					} else {
						$tree_icon = null; // default icon (folder)
					}

					$ic = empty($tree_icon) ? '' : '<img src="'.$tree_icon.'" alt="">';

					$out['text'] .= '<p><a '.oidplus_link('oidplus:resources$'.CFG_RESOURCE_PLUGIN_PATH.'$'.OIDplus::authUtils()::makeAuthKey("resources;".CFG_RESOURCE_PLUGIN_PATH)).'><img src="img/arrow_back.png" width="16"> Go back to: '.$ic.' '.htmlentities(CFG_RESOURCE_PLUGIN_TITLE).'</a></p>';
				} else {
					$icon_candidate = pathinfo($dir)['dirname'].'/'.pathinfo($dir)['filename'].'_tree.png';
					if (file_exists($icon_candidate)) {
						$tree_icon = $icon_candidate;
					} else if (file_exists(__DIR__.'/treeicon_folder.png')) {
						$tree_icon = 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/treeicon_folder.png';
					} else {
						$tree_icon = null; // no icon
					}

					$ic = empty($tree_icon) ? '' : '<img src="'.$tree_icon.'" alt="">';

					$out['text'] .= '<p><a '.oidplus_link('oidplus:resources$'.$dir.'$'.OIDplus::authUtils()::makeAuthKey("resources;$dir")).'><img src="img/arrow_back.png" width="16"> Go back to: '.$ic.' '.htmlentities(basename($dir)).'</a></p><br>';
				}
			}

			if (file_exists($file) && (!is_dir($file))) {
				if (substr($file,-4,4) == '.url') {
					$out['title'] = $this->getHyperlinkTitle($file);

					$icon_candidate = pathinfo($file)['dirname'].'/'.pathinfo($file)['filename'].'_big.png';
					if (file_exists($icon_candidate)) {
						$out['icon'] = $icon_candidate;
					} else if (file_exists(__DIR__.'/icon_leaf_url_big.png')) {
						$out['icon'] = 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/icon_leaf_url_big.png';
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
						$out['icon'] = 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/icon_leaf_doc_big.png';
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
				$out['title'] = ($file == CFG_RESOURCE_PLUGIN_PATH) ? CFG_RESOURCE_PLUGIN_TITLE : basename($file);

				if ($file == CFG_RESOURCE_PLUGIN_PATH) {
					$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/icon_big.png' : '';
				} else {
					$icon_candidate = pathinfo($file)['dirname'].'/'.pathinfo($file)['filename'].'_big.png';
					if (file_exists($icon_candidate)) {
						$out['icon'] = $icon_candidate;
					} else if (file_exists(__DIR__.'/icon_folder_big.png')) {
						$out['icon'] = 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/icon_folder_big.png';
					} else {
						$out['icon'] = null; // no icon
					}
				}

				if (file_exists(__DIR__.'/treeicon.png')) {
					$tree_icon = 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/treeicon.png';
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
						$tree_icon = 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/treeicon_folder.png';
					} else {
						$tree_icon = null; // no icon
					}

					$ic = empty($tree_icon) ? '' : '<img src="'.$tree_icon.'" alt="">';

					$out['text'] .= '<p><a '.oidplus_link('oidplus:resources$'.$dir.'$'.OIDplus::authUtils()::makeAuthKey("resources;$dir")).'>'.$ic.' '.htmlentities(basename($dir)).'</a></p>';
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
							$tree_icon = 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/treeicon_leaf_url.png';
						} else {
							$tree_icon = null; // default icon (folder)
						}
						$ic = empty($tree_icon) ? '' : '<img src="'.$tree_icon.'" alt="">';

						$hyperlink_pic = ' <img src="plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/hyperlink.png" widht="13" height="13" alt="Hyperlink" style="top:-3px;position:relative">';

						$out['text'] .= '<p><a href="'.htmlentities(self::getHyperlinkURL($file)).'" target="_blank">'.$ic.' '.htmlentities($this->getHyperlinkTitle($file)).' '.$hyperlink_pic.'</a></p>';
						$count++;
					} else {
						$icon_candidate = pathinfo($file)['dirname'].'/'.pathinfo($file)['filename'].'_tree.png';
						if (file_exists($icon_candidate)) {
							$tree_icon = $icon_candidate;
						} else if (file_exists(__DIR__.'/treeicon_leaf_doc.png')) {
							$tree_icon = 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/treeicon_leaf_doc.png';
						} else {
							$tree_icon = null; // default icon (folder)
						}
						$ic = empty($tree_icon) ? '' : '<img src="'.$tree_icon.'" alt="">';

						$out['text'] .= '<p><a '.oidplus_link('oidplus:resources$'.$file.'$'.OIDplus::authUtils()::makeAuthKey("resources;$file")).'>'.$ic.' '.htmlentities($this->getDocumentTitle($file)).'</a></p>';
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

	private function tree_rec(&$children, $rootdir=CFG_RESOURCE_PLUGIN_PATH, $depth=0) {
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
				$tree_icon = 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/treeicon_folder.png';
			} else {
				$tree_icon = null; // default icon (folder)
			}

			$children[] = array(
				'id' => 'oidplus:resources$'.$dir.'$'.OIDplus::authUtils()::makeAuthKey("resources;$dir"),
				'icon' => $tree_icon,
				'text' => basename($dir),
				'children' => $tmp,
				'state' => array("opened" => $depth <= CFG_RESOURCE_PLUGIN_AUTOOPEN_LEVEL-1)
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
					$tree_icon = 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/treeicon_leaf_url.png';
				} else {
					$tree_icon = null; // default icon (folder)
				}

				$hyperlink_pic = ' <img src="plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/hyperlink.png" widht="13" height="13" alt="Hyperlink" style="top:-3px;position:relative">';

				$children[] = array(
					'id' => 'oidplus:resources$'.$file.'$'.OIDplus::authUtils()::makeAuthKey("resources;$file"),
					'conditionalselect' => 'window.open('.js_escape(self::getHyperlinkURL($file)).'); false;',
					'icon' => $tree_icon,
					'text' => $this->getHyperlinkTitle($file).' '.$hyperlink_pic,
					'state' => array("opened" => $depth <= CFG_RESOURCE_PLUGIN_AUTOOPEN_LEVEL-1)
				);

			} else {
				$icon_candidate = pathinfo($file)['dirname'].'/'.pathinfo($file)['filename'].'_tree.png';
				if (file_exists($icon_candidate)) {
					$tree_icon = $icon_candidate;
				} else if (file_exists(__DIR__.'/treeicon_leaf_doc.png')) {
					$tree_icon = 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/treeicon_leaf_doc.png';
				} else {
					$tree_icon = null; // default icon (folder)
				}
				$children[] = array(
					'id' => 'oidplus:resources$'.$file.'$'.OIDplus::authUtils()::makeAuthKey("resources;$file"),
					'icon' => $tree_icon,
					'text' => $this->getDocumentTitle($file),
					'state' => array("opened" => $depth <= CFG_RESOURCE_PLUGIN_AUTOOPEN_LEVEL-1)
				);
			}
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		$children = array();

		$this->tree_rec($children, CFG_RESOURCE_PLUGIN_PATH);

		if (!CFG_RESOURCE_PLUGIN_HIDE_EMPTY_PATH || (count($children) > 0)) {
			if (file_exists(__DIR__.'/treeicon.png')) {
				$tree_icon = 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/treeicon.png';
			} else {
				$tree_icon = null; // default icon (folder)
			}

			$json[] = array(
				'id' => 'oidplus:resources$'.CFG_RESOURCE_PLUGIN_PATH.'$'.OIDplus::authUtils()::makeAuthKey("resources;".CFG_RESOURCE_PLUGIN_PATH),
				'icon' => $tree_icon,
				'state' => array("opened" => true),
				'text' => CFG_RESOURCE_PLUGIN_TITLE,
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

OIDplus::registerPagePlugin(new OIDplusPagePublicResources());
