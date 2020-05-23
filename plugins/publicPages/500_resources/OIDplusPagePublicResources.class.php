<?php

/*
 * OIDplus 2.0
 * Copyright 2020 Daniel Marschall, ViaThinkSoft
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

class OIDplusPagePublicResources extends OIDplusPagePluginPublic {

	public function init($html=true) {
		OIDplus::config()->prepareConfigKey('resource_plugin_autoopen_level', 'Resource plugin: How many levels should be open in the treeview when OIDplus is loaded?', 1, OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < 0)) {
				throw new OIDplusException("Please enter a valid value.");
			}
		});
		OIDplus::config()->prepareConfigKey('resource_plugin_title',          'Resource plugin: Title of the resource section?', 'Documents and resources', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (empty($value)) {
				throw new OIDplusException("Please enter a title.");
			}
		});
		OIDplus::config()->deleteConfigKey('resource_plugin_path');
		OIDplus::config()->prepareConfigKey('resource_plugin_hide_empty_path','Resource plugin: Hide empty paths? 1=on, 0=off', 1, OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || (($value != 0) && ($value != 1))) {
				throw new OIDplusException("Please enter a valid value (0=off, 1=on).");
			}
		});
	}

	private static function getDocumentTitle($file) {

		$file = rtrim(OIDplus::basePath(),'/').'/'.self::realname($file);

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

	private static function myglob($reldir, $onlydir=false) {
		$out = array();

		$root = OIDplus::basePath().'/userdata/resources/';
		$res = $onlydir ? glob($root.ltrim($reldir,'/'), GLOB_ONLYDIR) : glob($root.ltrim($reldir,'/'));
		foreach ($res as &$x) {
			$x = substr($x, strlen($root));
			$out[] = $x;
		}

		$root = OIDplus::basePath().'/res/';
		$res = $onlydir ? glob($root.ltrim($reldir,'/'), GLOB_ONLYDIR) : glob($root.ltrim($reldir,'/'));
		foreach ($res as $x) {
			$x = substr($x, strlen($root));
			$out[] = $x;
		}

		return array_unique($out);
	}

	private static function realname($rel) {
		$candidate1 = OIDplus::basePath().'/userdata/resources/'.$rel;
		$candidate2 = OIDplus::basePath().'/res/'.$rel;
		if (file_exists($candidate1) || is_dir($candidate1)) return "userdata/resources/$rel";
		if (file_exists($candidate2) || is_dir($candidate2)) return "res/$rel";
	}

	public function gui($id, &$out, &$handled) {
		if (explode('$',$id,2)[0] === 'oidplus:resources') {
			$handled = true;

			$file = @explode('$',$id)[1];

			// Security checks

			if (
				($file != '') && (
				(strpos($file, chr(0)) !== false) || // Directory traversal (LFI,RFI) helper
				(strpos($file, '../') !== false) || ($file[0] == '/') || ($file[0] == '~') || // <-- Local File Injection (LFI)
				($file[0] == '.') || (strpos($file, '/.') !== false) ||                       // <-- Calling hidden files e.g. ".htpasswd"
				(strpos($file, '://') !== false)                                              // <-- Remote File Injection (RFI)
			   )) {
				if (strpos($file, chr(0)) !== false) {
					$file = str_replace(chr(0), '[NUL]', $file);
				}
				OIDplus::logger()->log("[WARN]A!", "LFI/RFI attack blocked (requested file '$file')");
				$out['title'] = 'Access denied';
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>This request is invalid</p>';
				return;
			}

			$out['text'] = '';

			// First, "Go back to" line

			if ($file != '') {
				$dir = dirname($file);

				if ($dir == '.') {
					if (file_exists(__DIR__.'/treeicon.png')) {
						$tree_icon = OIDplus::webpath(__DIR__).'treeicon.png';
					} else {
						$tree_icon = null; // default icon (folder)
					}

					$ic = empty($tree_icon) ? '' : '<img src="'.$tree_icon.'" alt="">';

					$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:resources').'><img src="img/arrow_back.png" width="16"> Go back to: '.$ic.' '.htmlentities(OIDplus::config()->getValue('resource_plugin_title', 'Documents and resources')).'</a></p>';
				} else {
					$realdir = self::realname($dir);

					$tree_icon = OIDplus::webpath(__DIR__).'show_icon.php?mode=treeicon_folder&file='.urlencode($dir);
					/*
					$icon_candidate = pathinfo($realdir)['dirname'].'/'.pathinfo($realdir)['filename'].'_tree.png';
					if (file_exists($icon_candidate)) {
						$tree_icon = $icon_candidate;
					} else if (file_exists(__DIR__.'/treeicon_folder.png')) {
						$tree_icon = OIDplus::webpath(__DIR__).'treeicon_folder.png';
					} else {
						$tree_icon = null; // no icon
					}
					*/

					$ic = empty($tree_icon) ? '' : '<img src="'.$tree_icon.'" alt="">';

					$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:resources$'.rtrim($dir,'/').'/').'><img src="img/arrow_back.png" width="16"> Go back to: '.$ic.' '.htmlentities(basename($dir)).'</a></p><br>';
				}
			}

			// Then the content

			$realfile = self::realname($file);

			if (file_exists($realfile) && (!is_dir($realfile))) {
				if (substr($file,-4,4) == '.url') {
					$out['title'] = $this->getHyperlinkTitle($realfile);

					$out['icon'] = OIDplus::webpath(__DIR__).'show_icon.php?mode=icon_leaf_url_big&file='.urlencode($file);
					/*
					$icon_candidate = pathinfo($realfile)['dirname'].'/'.pathinfo($realfile)['filename'].'_big.png';
					if (file_exists($icon_candidate)) {
						$out['icon'] = $icon_candidate;
					} else if (file_exists(__DIR__.'/icon_leaf_url_big.png')) {
						$out['icon'] = OIDplus::webpath(__DIR__).'icon_leaf_url_big.png';
					} else {
						$out['icon'] = '';
					}
					*/

					// Should not happen though, due to conditionalselect
					$out['text'] .= '<a href="'.htmlentities(self::getHyperlinkURL($realfile)).'" target="_blank">Open in new window</a>';
				} else if ((substr($file,-4,4) == '.htm') || (substr($file,-5,5) == '.html')) {
					$out['title'] = $this->getDocumentTitle($file);

					$out['icon'] = OIDplus::webpath(__DIR__).'show_icon.php?mode=icon_leaf_doc_big&file='.urlencode($file);
					/*
					$icon_candidate = pathinfo($realfile)['dirname'].'/'.pathinfo($realfile)['filename'].'_big.png';
					if (file_exists($icon_candidate)) {
						$out['icon'] = $icon_candidate;
					} else if (file_exists(__DIR__.'/icon_leaf_doc_big.png')) {
						$out['icon'] = OIDplus::webpath(__DIR__).'icon_leaf_doc_big.png';
					} else {
						$out['icon'] = '';
					}
					*/

					$cont = file_get_contents($realfile);
					$cont = preg_replace('@^(.+)<body[^>]*>@isU', '', $cont);
					$cont = preg_replace('@</body>.+$@isU', '', $cont);
					$cont = preg_replace('@<title>.+</title>@isU', '', $cont);
					$cont = preg_replace('@<h1>.+</h1>@isU', '', $cont, 1);

					$out['text'] .= $cont;
				} else {
					$out['title'] = 'Unknown file type';
					$out['icon'] = 'img/error_big.png';
					$out['text'] = '<p>The system does not know how to handle this file type.</p>';
					return;
				}
			} else if (is_dir($realfile)) {
				$out['title'] = ($file == '') ? OIDplus::config()->getValue('resource_plugin_title', 'Documents and resources') : basename($file);

				if ($file == '') {
					$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';
				} else {
					$out['icon'] = OIDplus::webpath(__DIR__).'show_icon.php?mode=icon_folder_big&file='.urlencode($file);
					/*
					$icon_candidate = pathinfo($realfile)['dirname'].'/'.pathinfo($realfile)['filename'].'_big.png';
					if (file_exists($icon_candidate)) {
						$out['icon'] = $icon_candidate;
					} else if (file_exists(__DIR__.'/icon_folder_big.png')) {
						$out['icon'] = OIDplus::webpath(__DIR__).'icon_folder_big.png';
					} else {
						$out['icon'] = null; // no icon
					}
					*/
				}

				if (file_exists(__DIR__.'/treeicon.png')) {
					$tree_icon = OIDplus::webpath(__DIR__).'treeicon.png';
				} else {
					$tree_icon = null; // default icon (folder)
				}

				$count = 0;

				$dirs = self::myglob(rtrim($file,'/').'/'.'*', true);
				natcasesort($dirs);
				foreach ($dirs as $dir) {
					$realdir = self::realname($dir);
					$tree_icon = OIDplus::webpath(__DIR__).'show_icon.php?mode=treeicon_folder&file='.urlencode($dir);
					/*
					$icon_candidate = pathinfo($realdir)['dirname'].'/'.pathinfo($realdir)['filename'].'_tree.png';
					if (file_exists($icon_candidate)) {
						$tree_icon = $icon_candidate;
					} else if (file_exists(__DIR__.'/treeicon_folder.png')) {
						$tree_icon = OIDplus::webpath(__DIR__).'treeicon_folder.png';
					} else {
						$tree_icon = null; // no icon
					}
					*/

					$ic = empty($tree_icon) ? '' : '<img src="'.$tree_icon.'" alt="">';

					$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:resources$'.rtrim($dir,'/').'/').'>'.$ic.' '.htmlentities(basename($dir)).'</a></p>';
					$count++;
				}

				$files = array_merge(
					self::myglob(rtrim($file,'/').'/'.'*.htm*'), // TODO: also PHP?
					self::myglob(rtrim($file,'/').'/'.'*.url')
				);
				natcasesort($files);
				foreach ($files as $file) {
					$realfile = self::realname($file);
					if (substr($file,-4,4) == '.url') {
						$tree_icon = OIDplus::webpath(__DIR__).'show_icon.php?mode=treeicon_leaf_url&file='.urlencode($file);
						/*
						$icon_candidate = pathinfo($realfile)['dirname'].'/'.pathinfo($realfile)['filename'].'_tree.png';
						if (file_exists($icon_candidate)) {
							$tree_icon = $icon_candidate;
						} else if (file_exists(__DIR__.'/treeicon_leaf_url.png')) {
							$tree_icon = OIDplus::webpath(__DIR__).'treeicon_leaf_url.png';
						} else {
							$tree_icon = null; // default icon (folder)
						}
						*/

						$ic = empty($tree_icon) ? '' : '<img src="'.$tree_icon.'" alt="">';

						$hyperlink_pic = ' <img src="'.OIDplus::webpath(__DIR__).'hyperlink.png" widht="13" height="13" alt="Hyperlink" style="top:-3px;position:relative">';

						$out['text'] .= '<p><a href="'.htmlentities(self::getHyperlinkURL($realfile)).'" target="_blank">'.$ic.' '.htmlentities($this->getHyperlinkTitle($realfile)).' '.$hyperlink_pic.'</a></p>';
						$count++;
					} else {
						$tree_icon = OIDplus::webpath(__DIR__).'show_icon.php?mode=treeicon_leaf_doc&file='.urlencode($file);
						/*
						$icon_candidate = pathinfo($realfile)['dirname'].'/'.pathinfo($realfile)['filename'].'_tree.png';
						if (file_exists($icon_candidate)) {
							$tree_icon = $icon_candidate;
						} else if (file_exists(__DIR__.'/treeicon_leaf_doc.png')) {
							$tree_icon = OIDplus::webpath(__DIR__).'treeicon_leaf_doc.png';
						} else {
							$tree_icon = null; // default icon (folder)
						}
						*/

						$ic = empty($tree_icon) ? '' : '<img src="'.$tree_icon.'" alt="">';

						$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:resources$'.$file).'>'.$ic.' '.htmlentities($this->getDocumentTitle($file)).'</a></p>';
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
			}
		}
	}

	private function tree_rec(&$children, $rootdir=null, $depth=0) {
		if (is_null($rootdir)) $rootdir = '';
		if ($depth > 100) return false; // something is wrong!

		$dirs = self::myglob($rootdir.'*'.'/', true);
		natcasesort($dirs);
		foreach ($dirs as $dir) {
			$tmp = array();

			$this->tree_rec($tmp, $dir, $depth+1);

			$realdir = self::realname($dir);

			$tree_icon = OIDplus::webpath(__DIR__).'show_icon.php?mode=treeicon_folder&file='.urlencode($dir);
			/*
			$icon_candidate = pathinfo($realdir)['dirname'].'/'.pathinfo($realdir)['filename'].'_tree.png';
			if (file_exists($icon_candidate)) {
				$tree_icon = $icon_candidate;
			} else if (file_exists(__DIR__.'/treeicon_folder.png')) {
				$tree_icon = OIDplus::webpath(__DIR__).'treeicon_folder.png';
			} else {
				$tree_icon = null; // default icon (folder)
			}
			*/

			$children[] = array(
				'id' => 'oidplus:resources$'.$dir,
				'icon' => $tree_icon,
				'text' => basename($dir),
				'children' => $tmp,
				'state' => array("opened" => $depth <= OIDplus::config()->getValue('resource_plugin_autoopen_level', 1)-1)
			);
		}

		$files = array_merge(
			self::myglob($rootdir.'*.htm*'), // TODO: Also PHP?
			self::myglob($rootdir.'*.url')
		);
		natcasesort($files);
		foreach ($files as $file) {
			$realfile = self::realname($file);
			if (substr($file,-4,4) == '.url') {
				$tree_icon = OIDplus::webpath(__DIR__).'show_icon.php?mode=treeicon_leaf_url&file='.urlencode($file);
				/*
				$icon_candidate = pathinfo($realfile)['dirname'].'/'.pathinfo($realfile)['filename'].'_tree.png';
				if (file_exists($icon_candidate)) {
					$tree_icon = $icon_candidate;
				} else if (file_exists(__DIR__.'/treeicon_leaf_url.png')) {
					$tree_icon = OIDplus::webpath(__DIR__).'treeicon_leaf_url.png';
				} else {
					$tree_icon = null; // default icon (folder)
				}
				*/

				$hyperlink_pic = ' <img src="'.OIDplus::webpath(__DIR__).'hyperlink.png" widht="13" height="13" alt="Hyperlink" style="top:-3px;position:relative">';

				$children[] = array(
					'id' => 'oidplus:resources$'.$file,
					'conditionalselect' => 'window.open('.js_escape(self::getHyperlinkURL($realfile)).'); false;',
					'icon' => $tree_icon,
					'text' => $this->getHyperlinkTitle($realfile).' '.$hyperlink_pic,
					'state' => array("opened" => $depth <= OIDplus::config()->getValue('resource_plugin_autoopen_level', 1)-1)
				);
			} else {
				$tree_icon = OIDplus::webpath(__DIR__).'show_icon.php?mode=treeicon_leaf_doc&file='.urlencode($file);
				/*
				$icon_candidate = pathinfo($realfile)['dirname'].'/'.pathinfo($realfile)['filename'].'_tree.png';
				if (file_exists($icon_candidate)) {
					$tree_icon = $icon_candidate;
				} else if (file_exists(__DIR__.'/treeicon_leaf_doc.png')) {
					$tree_icon = OIDplus::webpath(__DIR__).'treeicon_leaf_doc.png';
				} else {
					$tree_icon = null; // default icon (folder)
				}
				*/
				$children[] = array(
					'id' => 'oidplus:resources$'.$file,
					'icon' => $tree_icon,
					'text' => $this->getDocumentTitle($file),
					'state' => array("opened" => $depth <= OIDplus::config()->getValue('resource_plugin_autoopen_level', 1)-1)
				);
			}
		}
	}

	private function publicSitemap_rec($json, &$out) {
		foreach ($json as $x) {
			if (isset($x['id']) && $x['id']) {
				$out[] = OIDplus::getSystemUrl().'?goto='.urlencode($x['id']);
			}
			if (isset($x['children'])) {
				$this->publicSitemap_rec($x['children'], $out);
			}
		}
	}

	public function publicSitemap(&$out) {
		$json = array();
		$this->tree($json, null/*RA EMail*/, false/*HTML tree algorithm*/, true/*display all*/);
		$this->publicSitemap_rec($json, $out);
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		$children = array();

		$this->tree_rec($children, '/');

		if (!OIDplus::config()->getValue('resource_plugin_hide_empty_path', true) || (count($children) > 0)) {
			if (file_exists(__DIR__.'/treeicon.png')) {
				$tree_icon = OIDplus::webpath(__DIR__).'treeicon.png';
			} else {
				$tree_icon = null; // default icon (folder)
			}

			$json[] = array(
				'id' => 'oidplus:resources',
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
