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

namespace ViaThinkSoft\OIDplus;

class OIDplusPagePublicResources extends OIDplusPagePluginPublic {

	private function getMainTitle() {
		return _L('Documents and Resources');
	}

	public function init($html=true) {
		OIDplus::config()->prepareConfigKey('resource_plugin_autoopen_level', 'Resource plugin: How many levels should be open in the treeview when OIDplus is loaded?', '1', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < 0)) {
				throw new OIDplusException(_L('Please enter a valid value.'));
			}
		});
		OIDplus::config()->delete('resource_plugin_title');
		OIDplus::config()->delete('resource_plugin_path');
		OIDplus::config()->prepareConfigKey('resource_plugin_hide_empty_path','Resource plugin: Hide empty paths? (0=no, 1=yes)', '1', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || (($value != 0) && ($value != 1))) {
				throw new OIDplusException(_L('Please enter a valid value (0=no, 1=yes).'));
			}
		});
	}

	private static function getDocumentContent($file) {
		$file = self::realname($file);
		$file2 = preg_replace('/\.([^.]+)$/', '$'.OIDplus::getCurrentLang().'.\1', $file);
		if (file_exists($file2)) $file = $file2;

		$cont = file_get_contents($file);

		list($html, $js, $css) = extractHtmlContents($cont);
		$cont = '';
		if (!empty($js))  $cont .= "<script>\n$js\n</script>";
		if (!empty($css)) $cont .= "<style>\n$css\n</style>";
		$cont .= stripHtmlComments($html);

		return $cont;
	}

	private static function getDocumentTitle($file) {
		$file = self::realname($file);
		$file2 = preg_replace('/\.([^.]+)$/', '$'.OIDplus::getCurrentLang().'.\1', $file);
		if (file_exists($file2)) $file = $file2;

		$cont = file_get_contents($file);

		// make sure the program works even if the user provided HTML is not UTF-8
		$cont = convert_to_utf8_no_bom($cont);

		$m = array();
		if (preg_match('@<title>(.+)</title>@ismU', $cont, $m)) return $m[1];
		if (preg_match('@<h1>(.+)</h1>@ismU', $cont, $m)) return $m[1];
		if (preg_match('@<h2>(.+)</h2>@ismU', $cont, $m)) return $m[1];
		if (preg_match('@<h3>(.+)</h3>@ismU', $cont, $m)) return $m[1];
		if (preg_match('@<h4>(.+)</h4>@ismU', $cont, $m)) return $m[1];
		if (preg_match('@<h5>(.+)</h5>@ismU', $cont, $m)) return $m[1];
		if (preg_match('@<h6>(.+)</h6>@ismU', $cont, $m)) return $m[1];
		return pathinfo($file, PATHINFO_FILENAME); // filename without extension
	}

	protected static function mayAccessResource($source) {
		if (OIDplus::authUtils()->isAdminLoggedIn()) return true;

		$candidates = array(
			OIDplus::localpath().'userdata/resources/security.ini',
			OIDplus::localpath().'res/security.ini'
		);
		foreach ($candidates as $ini_file) {
			if (file_exists($ini_file)) {
				$data = @parse_ini_file($ini_file, true);
				if (isset($data['Security']) && isset($data['Security'][$source])) {
					$level = $data['Security'][$source];
					if ($level == 'PUBLIC') {
						return true;
					} else if ($level == 'RA') {
						return
							((OIDplus::authUtils()->raNumLoggedIn() > 0) ||
							(OIDplus::authUtils()->isAdminLoggedIn()));
					} else if ($level == 'ADMIN') {
						return OIDplus::authUtils()->isAdminLoggedIn();
					} else {
						throw new OIDplusException(_L('Unexpected security level in %1 (expect PUBLIC, RA or ADMIN)', $ini_file));
					}
				}
			}
		}
		return true;
	}

	private static function myglob($reldir, $onlydir=false) {
		$out = array();

		$root = OIDplus::localpath().'userdata/resources/';
		$res = $onlydir ? @glob($root.ltrim($reldir,'/'), GLOB_ONLYDIR) : @glob($root.ltrim($reldir,'/'));
		if ($res) foreach ($res as &$x) {
			$x = substr($x, strlen($root));
			if (strpos($x,'$') !== false) continue;
			$out[] = $x;
		}

		$root = OIDplus::localpath().'res/';
		$res = $onlydir ? @glob($root.ltrim($reldir,'/'), GLOB_ONLYDIR) : @glob($root.ltrim($reldir,'/'));
		if ($res) foreach ($res as $x) {
			$x = substr($x, strlen($root));
			if (strpos($x,'$') !== false) continue;
			$out[] = $x;
		}

		$out = array_unique($out);

		return array_filter($out, function($v, $k) {
			return self::mayAccessResource($v);
		}, ARRAY_FILTER_USE_BOTH);
	}

	private static function realname($rel) {
		$candidate1 = OIDplus::localpath().'userdata/resources/'.$rel;
		$candidate2 = OIDplus::localpath().'res/'.$rel;
		if (file_exists($candidate1) || is_dir($candidate1)) return $candidate1;
		if (file_exists($candidate2) || is_dir($candidate2)) return $candidate2;
		return null;
	}

	protected static function checkRedirect($source, &$target): bool {
		$candidates = array(
			OIDplus::localpath().'userdata/resources/redirect.ini',
			OIDplus::localpath().'res/redirect.ini'
		);
		foreach ($candidates as $ini_file) {
			if (file_exists($ini_file)) {
				$data = @parse_ini_file($ini_file, true);
				if (isset($data['Redirects']) && isset($data['Redirects'][$source])) {
					$target = $data['Redirects'][$source];
					return true;
				}
			}
		}
		return false;
	}

	public function gui($id, &$out, &$handled) {
		if (explode('$',$id,2)[0] === 'oidplus:resources') {
			$handled = true;

			$tmp = explode('$',$id);
			$file = isset($tmp[1]) ? $tmp[1] : '';
			unset($tmp);

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
				// This will not be logged anymore, because people could spam the log files otherwise
				//OIDplus::logger()->log("[WARN]A!", "LFI/RFI attack blocked (requested file '$file')");
				$out['title'] = _L('Access denied');
				$out['icon'] = 'img/error.png';
				$out['text'] = '<p>'._L('This request is invalid').'</p>';
				return;
			}

			$out['text'] = '';

			// Check for permission

			if ($file != '') {
				if (!self::mayAccessResource($file)) {
					$out['title'] = _L('Access denied');
					$out['icon'] = 'img/error.png';
					$out['text'] = '<p>'._L('Authentication error. Please log in.').'</p>';
					return;
				}
			}

			// Redirections

			if ($file != '') {
				$target = null;
				if (self::checkRedirect($file, $target)) {
					$out['title'] = _L('Please wait...');
					$out['text'] = '<p>'._L('You are being redirected...').'</p><script>window.location.href = '.js_escape($target).';</script>';
					return;
				}
			}

			// First, "Go back to" line

			if ($file != '') {
				$dir = dirname($file);

				if ($dir == '.') {
					if (file_exists(__DIR__.'/img/main_icon16.png')) {
						$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png';
					} else {
						$tree_icon = null; // default icon (folder)
					}

					$ic = empty($tree_icon) ? '' : '<img src="'.$tree_icon.'" alt="">';

					$lng_gobackto = _L('Go back to').':';
					$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:resources').'><img src="img/arrow_back.png" width="16" alt="'._L('Go back').'"> '.$lng_gobackto.' '.$ic.' '.htmlentities($this->getMainTitle()).'</a></p>';
				} else {
					$realdir = self::realname($dir);

					$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'show_icon.php?mode=folder_icon16&lang='.OIDplus::getCurrentLang().'&file='.urlencode($dir);
					/*
					$icon_candidate = pathinfo($realdir)['dirname'].'/'.pathinfo($realdir)['filename'].'_tree.png';
					if (file_exists($icon_candidate)) {
						$tree_icon = $icon_candidate;
					} else if (file_exists(__DIR__.'/img/folder_icon16.png')) {
						$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/folder_icon16.png';
					} else {
						$tree_icon = null; // no icon
					}
					*/

					$ic = /*empty($tree_icon) ? '' : */'<img src="'.$tree_icon.'" alt="">';

					$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:resources$'.rtrim($dir,'/').'/').'><img src="img/arrow_back.png" width="16" alt="'._L('Go back').'"> '._L('Go back to').': '.$ic.' '.htmlentities(self::getFolderTitle($realdir)).'</a></p><br>';
				}
			}

			// Then the content

			$realfile = self::realname($file);
			// $realfile2 = preg_replace('/\.([^.]+)$/', '$'.OIDplus::getCurrentLang().'.\1', $realfile);
			// if (file_exists($realfile2)) $realfile = $realfile2;

			if (file_exists($realfile) && (!is_dir($realfile))) {
				if ((substr($file,-4,4) == '.url') || (substr($file,-5,5) == '.link')) {
					$out['title'] = $this->getHyperlinkTitle($realfile);

					$out['icon'] = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'show_icon.php?mode=leaf_url_icon&lang='.OIDplus::getCurrentLang().'&file='.urlencode($file);
					/*
					$icon_candidate = pathinfo($realfile)['dirname'].'/'.pathinfo($realfile)['filename'].'_big.png';
					if (file_exists($icon_candidate)) {
						$out['icon'] = $icon_candidate;
					} else if (file_exists(__DIR__.'/img/leaf_url_icon.png')) {
						$out['icon'] = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/leaf_url_icon.png';
					} else {
						$out['icon'] = '';
					}
					*/

					// Should not happen though, due to conditionalselect
					$out['text'] .= '<a href="'.htmlentities(self::getHyperlinkURL($realfile)).'" target="_blank">'._L('Open in new window').'</a>';
				} else if ((substr($file,-4,4) == '.htm') || (substr($file,-5,5) == '.html')) {
					$out['title'] = $this->getDocumentTitle($file);

					$out['icon'] = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'show_icon.php?mode=leaf_doc_icon&lang='.OIDplus::getCurrentLang().'&file='.urlencode($file);
					/*
					$icon_candidate = pathinfo($realfile)['dirname'].'/'.pathinfo($realfile)['filename'].'_big.png';
					if (file_exists($icon_candidate)) {
						$out['icon'] = $icon_candidate;
					} else if (file_exists(__DIR__.'/img/leaf_doc_icon.png')) {
						$out['icon'] = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/leaf_doc_icon.png';
					} else {
						$out['icon'] = '';
					}
					*/

					$out['text'] .= self::getDocumentContent($file);
				} else {
					$out['title'] = _L('Unknown file type');
					$out['icon'] = 'img/error.png';
					$out['text'] = '<p>'._L('The system does not know how to handle this file type.').'</p>';
					return;
				}
			} else if (is_dir($realfile)) {
				$out['title'] = ($file == '') ? $this->getMainTitle() : self::getFolderTitle($realfile);

				if ($file == '') {
					$out['icon'] = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';
				} else {
					$out['icon'] = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'show_icon.php?mode=folder_icon&lang='.OIDplus::getCurrentLang().'&file='.urlencode($file);
					/*
					$icon_candidate = pathinfo($realfile)['dirname'].'/'.pathinfo($realfile)['filename'].'_big.png';
					if (file_exists($icon_candidate)) {
						$out['icon'] = $icon_candidate;
					} else if (file_exists(__DIR__.'/img/folder_icon.png')) {
						$out['icon'] = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/folder_icon.png';
					} else {
						$out['icon'] = null; // no icon
					}
					*/
				}

				if (file_exists(__DIR__.'/img/main_icon16.png')) {
					$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png';
				} else {
					$tree_icon = null; // default icon (folder)
				}

				$count = 0;

				$dirs = self::myglob(rtrim($file,'/').'/'.'*', true);
				natcasesort($dirs);
				foreach ($dirs as $dir) {
					$realdir = self::realname($dir);
					$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'show_icon.php?mode=folder_icon16&lang='.OIDplus::getCurrentLang().'&file='.urlencode($dir);
					/*
					$icon_candidate = pathinfo($realdir)['dirname'].'/'.pathinfo($realdir)['filename'].'_tree.png';
					if (file_exists($icon_candidate)) {
						$tree_icon = $icon_candidate;
					} else if (file_exists(__DIR__.'/img/folder_icon16.png')) {
						$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/folder_icon16.png';
					} else {
						$tree_icon = null; // no icon
					}
					*/

					$ic = /*empty($tree_icon) ? '' : */'<img src="'.$tree_icon.'" alt="">';

					$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:resources$'.rtrim($dir,'/').'/').'>'.$ic.' '.htmlentities(self::getFolderTitle($realdir)).'</a></p>';
					$count++;
				}

				$files = array_merge(
					self::myglob(rtrim($file,'/').'/'.'*.htm'), // TODO: also PHP?
					self::myglob(rtrim($file,'/').'/'.'*.html'),
					self::myglob(rtrim($file,'/').'/'.'*.url'),
					self::myglob(rtrim($file,'/').'/'.'*.link')
				);
				natcasesort($files);
				foreach ($files as $file) {
					$realfile = self::realname($file);
					if ((substr($file,-4,4) == '.url') || (substr($file,-5,5) == '.link')) {
						$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'show_icon.php?mode=leaf_url_icon16&lang='.OIDplus::getCurrentLang().'&file='.urlencode($file);
						/*
						$icon_candidate = pathinfo($realfile)['dirname'].'/'.pathinfo($realfile)['filename'].'_tree.png';
						if (file_exists($icon_candidate)) {
							$tree_icon = $icon_candidate;
						} else if (file_exists(__DIR__.'/img/leaf_url_icon16.png')) {
							$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/leaf_url_icon16.png';
						} else {
							$tree_icon = null; // default icon (folder)
						}
						*/

						$ic = /*empty($tree_icon) ? '' : */'<img src="'.$tree_icon.'" alt="">';

						$out['text'] .= '<p><a href="'.htmlentities(self::getHyperlinkURL($realfile)).'" target="_blank">'.$ic.' '.htmlentities($this->getHyperlinkTitle($realfile)).'</a></p>';
						$count++;
					} else {
						$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'show_icon.php?mode=leaf_doc_icon16&lang='.OIDplus::getCurrentLang().'&file='.urlencode($file);
						/*
						$icon_candidate = pathinfo($realfile)['dirname'].'/'.pathinfo($realfile)['filename'].'_tree.png';
						if (file_exists($icon_candidate)) {
							$tree_icon = $icon_candidate;
						} else if (file_exists(__DIR__.'/img/leaf_doc_icon16.png')) {
							$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/leaf_doc_icon16.png';
						} else {
							$tree_icon = null; // default icon (folder)
						}
						*/

						$ic = /*empty($tree_icon) ? '' : */'<img src="'.$tree_icon.'" alt="">';

						$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:resources$'.$file).'>'.$ic.' '.htmlentities($this->getDocumentTitle($file)).'</a></p>';
						$count++;
					}
				}

				if ($count == 0) {
					$out['text'] .= '<p>'._L('This folder does not contain any elements').'</p>';
				}
			} else {
				$out['title'] = _L('Not found');
				$out['icon'] = 'img/error.png';
				$out['text'] = '<p>'._L('This resource doesn\'t exist anymore.').'</p>';
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

			$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'show_icon.php?mode=folder_icon16&lang='.OIDplus::getCurrentLang().'&file='.urlencode($dir);
			/*
			$icon_candidate = pathinfo($realdir)['dirname'].'/'.pathinfo($realdir)['filename'].'_tree.png';
			if (file_exists($icon_candidate)) {
				$tree_icon = $icon_candidate;
			} else if (file_exists(__DIR__.'/img/folder_icon16.png')) {
				$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/folder_icon16.png';
			} else {
				$tree_icon = null; // default icon (folder)
			}
			*/

			$children[] = array(
				'id' => 'oidplus:resources$'.$dir,
				'icon' => $tree_icon,
				'text' => self::getFolderTitle($realdir),
				'children' => $tmp,
				'state' => array("opened" => $depth <= OIDplus::config()->getValue('resource_plugin_autoopen_level', 1)-1)
			);
		}

		$files = array_merge(
			self::myglob($rootdir.'*.htm'), // TODO: Also PHP?
			self::myglob($rootdir.'*.html'),
			self::myglob($rootdir.'*.url'),
			self::myglob($rootdir.'*.link')
		);
		natcasesort($files);
		foreach ($files as $file) {
			$realfile = self::realname($file);
			if ((substr($file,-4,4) == '.url') || (substr($file,-5,5) == '.link')) {
				$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'show_icon.php?mode=leaf_url_icon16&lang='.OIDplus::getCurrentLang().'&file='.urlencode($file);
				/*
				$icon_candidate = pathinfo($realfile)['dirname'].'/'.pathinfo($realfile)['filename'].'_tree.png';
				if (file_exists($icon_candidate)) {
					$tree_icon = $icon_candidate;
				} else if (file_exists(__DIR__.'/img/leaf_url_icon16.png')) {
					$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/leaf_url_icon16.png';
				} else {
					$tree_icon = null; // default icon (folder)
				}
				*/

				$children[] = array(
					'id' => 'oidplus:resources$'.$file,
					'conditionalselect' => 'window.open('.js_escape(self::getHyperlinkURL($realfile)).'); false;',
					'icon' => $tree_icon,
					'text' => $this->getHyperlinkTitle($realfile),
					'state' => array("opened" => $depth <= OIDplus::config()->getValue('resource_plugin_autoopen_level', 1)-1),
					'a_attr' => array(
						'href' => self::getHyperlinkURL($realfile),
						'target' => '_blank'
					)
				);
			} else {
				$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'show_icon.php?mode=leaf_doc_icon16&lang='.OIDplus::getCurrentLang().'&file='.urlencode($file);
				/*
				$icon_candidate = pathinfo($realfile)['dirname'].'/'.pathinfo($realfile)['filename'].'_tree.png';
				if (file_exists($icon_candidate)) {
					$tree_icon = $icon_candidate;
				} else if (file_exists(__DIR__.'/img/leaf_doc_icon16.png')) {
					$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/leaf_doc_icon16.png';
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
				$out[] = $x['id'];
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
			if (file_exists(__DIR__.'/img/main_icon16.png')) {
				$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png';
			} else {
				$tree_icon = null; // default icon (folder)
			}

			$json[] = array(
				'id' => 'oidplus:resources',
				'icon' => $tree_icon,
				'state' => array("opened" => true),
				'text' => $this->getMainTitle(),
				'children' => $children
			);
		}

		return true;
	}

	public function tree_search($request) {
		return false;
	}

	private static function getHyperlinkTitle($file) {
		$file2 = preg_replace('/\.([^.]+)$/', '$'.OIDplus::getCurrentLang().'.\1', $file);
		if (file_exists($file2)) $file = $file2;

		if (substr($file,-4,4) == '.url') {
			return preg_replace('/\\.[^.\\s]{3,4}$/', '', basename($file));
		} else if (substr($file,-5,5) == '.link') {
			/*
			[Link]
			Title=Report a bug
			URL=https://github.com/danielmarschall/oidplus/issues
			*/

			$data = @parse_ini_file($file, true);
			if (!$data) {
				throw new OIDplusException(_L('File %1 has an invalid INI format!',$file));
			}
			if (!isset($data['Link'])) {
				throw new OIDplusException(_L('Could not find "%1" section at %2','Link',$file));
			}
			if (!isset($data['Link']['Title'])) {
				throw new OIDplusException(_L('"%1" is missing in %2','Title',$file));
			}
			return $data['Link']['Title'];
		} else {
			throw new OIDplusException(_L('Unexpected file extension for file %1',$file));
		}
	}

	private static function getHyperlinkURL($file) {
		$file2 = preg_replace('/\.([^.]+)$/', '$'.OIDplus::getCurrentLang().'.\1', $file);
		if (file_exists($file2)) $file = $file2;

		if (substr($file,-4,4) == '.url') {
			/*
			[InternetShortcut]
			URL=http://www.example.com/
			*/

			$data = @parse_ini_file($file, true);
			if (!$data) {
				throw new OIDplusException(_L('File %1 has an invalid INI format!',$file));
			}
			if (!isset($data['InternetShortcut'])) {
				throw new OIDplusException(_L('Could not find "%1" section at %2','InternetShortcut',$file));
			}
			if (!isset($data['InternetShortcut']['URL'])) {
				throw new OIDplusException(_L('"%1" is missing in %2','URL',$file));
			}
			return $data['InternetShortcut']['URL'];
		} else if (substr($file,-5,5) == '.link') {
			/*
			[Link]
			Title=Report a bug
			URL=https://github.com/danielmarschall/oidplus/issues
			*/

			$data = @parse_ini_file($file, true);
			if (!$data) {
				throw new OIDplusException(_L('File %1 has an invalid INI format!',$file));
			}
			if (!isset($data['Link'])) {
				throw new OIDplusException(_L('Could not find "%1" section at %2','Link',$file));
			}
			if (!isset($data['Link']['URL'])) {
				throw new OIDplusException(_L('"%1" is missing in %2','URL',$file));
			}
			return $data['Link']['URL'];
		} else {
			throw new OIDplusException(_L('Unexpected file extension for file %1',$file));
		}

	}

	private static function getFolderTitle($dir) {
		$data = @parse_ini_file("$dir/folder\$".OIDplus::getCurrentLang().".ini", true);
		if ($data && isset($data['Folder']) && isset($data['Folder']['Title'])) {
			return $data['Folder']['Title'];
		}

		$data = @parse_ini_file("$dir/folder.ini", true);
		if ($data && isset($data['Folder']) && isset($data['Folder']['Title'])) {
			return $data['Folder']['Title'];
		}

		return basename($dir);
	}
}
