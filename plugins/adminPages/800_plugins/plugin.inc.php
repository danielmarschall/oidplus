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

class OIDplusPageAdminPlugins extends OIDplusPagePlugin {
	public function type() {
		return 'admin';
	}

	public function priority() {
		return 800;
	}

	public function action(&$handled) {
	}

	public function init($html=true) {
	}

	public function cfgSetValue($name, $value) {
	}

	public function gui($id, &$out, &$handled) {
		$parts = explode('.',$id,2);
		if (!isset($parts[1])) $parts[1] = '';
		if ($parts[0] != 'oidplus:system_plugins') return;
		$handled = true;

		if (!OIDplus::authUtils()::isAdminLoggedIn()) {
			$out['icon'] = 'img/error_big.png';
			$out['text'] .= '<p>You need to <a '.oidplus_link('oidplus:login').'>log in</a> as administrator.</p>';
			return $out;
		}

		if (substr($parts[1],0,1) == '$') {
			$classname = substr($parts[1],1);
			$out['title'] = htmlentities($classname);
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/icon_big.png' : '';

			$reflector = new \ReflectionClass($classname);

			$out['text'] .= "<p>Plugin class name $classname</p>".
			"<p>Plugin installed in " . dirname($reflector->getFileName())."</p>".
			"<p>Plugin type " . get_parent_class($classname)."</p>";
		} else {
			$show_pages_public = false;
			$show_pages_ra = false;
			$show_pages_admin = false;
			$show_db_active = false;
			$show_db_inactive = false;
			$show_obj_active = false;
			$show_obj_inactive = false;

			if ($parts[1] == '') {
				$out['title'] = "Installed plugins";
				$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/icon_big.png' : '';
				$show_pages_public = true;
				$show_pages_ra = true;
				$show_pages_admin = true;
				$show_db_active = true;
				$show_db_inactive = true;
				$show_obj_active = true;
				$show_obj_inactive = true;
			} else if ($parts[1] == 'pages') {
				$out['title'] = "Page plugins";
				$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/icon_big.png' : '';
				$show_pages_public = true;
				$show_pages_ra = true;
				$show_pages_admin = true;
			} else if ($parts[1] == 'pages.public') {
				$out['title'] = "Public page plugins";
				$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/icon_big.png' : '';
				$show_pages_public = true;
			} else if ($parts[1] == 'pages.ra') {
				$out['title'] = "RA page plugins";
				$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/icon_big.png' : '';
				$show_pages_ra = true;
			} else if ($parts[1] == 'pages.admin') {
				$out['title'] = "Admin page plugins";
				$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/icon_big.png' : '';
				$show_pages_admin = true;
			} else if ($parts[1] == 'objects') {
				$out['title'] = "Object type plugins";
				$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/icon_big.png' : '';
				$show_obj_active = true;
				$show_obj_inactive = true;
			} else if ($parts[1] == 'objects.enabled') {
				$out['title'] = "Object type plugins (enabled)";
				$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/icon_big.png' : '';
				$show_obj_active = true;
			} else if ($parts[1] == 'objects.disabled') {
				$out['title'] = "Object type plugins (disabled)";
				$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/icon_big.png' : '';
				$show_obj_inactive = true;
			} else if ($parts[1] == 'database') {
				$out['title'] = "Database provider plugins";
				$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/icon_big.png' : '';
				$show_db_active = true;
				$show_db_inactive = true;
			} else if ($parts[1] == 'database.enabled') {
				$out['title'] = "Database provider plugins (active)";
				$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/icon_big.png' : '';
				$show_db_active = true;
			} else if ($parts[1] == 'database.disabled') {
				$out['title'] = "Database provider plugins (inactive)";
				$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/icon_big.png' : '';
				$show_db_inactive = true;
			} else {
				$out['icon'] = 'img/error_big.png';
				$out['text'] .= '<p>Invalid arguments.</p>';
				return $out;
			}

			if ($show_pages_public) {
				if (count($plugins = OIDplus::getPagePlugins('public')) > 0) {
					$out['text'] .= '<p><u>Public page plugins:</u></p><ul>';
					foreach ($plugins as $plugin) {
						$out['text'] .= '<li><a '.oidplus_link('oidplus:system_plugins.$'.get_class($plugin)).'>'.htmlentities(get_class($plugin)).'</a></li>'; // TODO: human friendly names
					}
					$out['text'] .= '</ul>';
				}
			}

			if ($show_pages_ra) {
				if (count($plugins = OIDplus::getPagePlugins('ra')) > 0) {
					$out['text'] .= '<p><u>RA page plugins:</u></p><ul>';
					foreach ($plugins as $plugin) {
						$out['text'] .= '<li><a '.oidplus_link('oidplus:system_plugins.$'.get_class($plugin)).'>'.htmlentities(get_class($plugin)).'</a></li>'; // TODO: human friendly names
					}
					$out['text'] .= '</ul>';
				}
			}

			if ($show_pages_admin) {
				if (count($plugins = OIDplus::getPagePlugins('admin')) > 0) {
					$out['text'] .= '<p><u>Admin page plugins:</u></p><ul>';
					foreach ($plugins as $plugin) {
						$out['text'] .= '<li><a '.oidplus_link('oidplus:system_plugins.$'.get_class($plugin)).'>'.htmlentities(get_class($plugin)).'</a></li>'; // TODO: human friendly names
					}
					$out['text'] .= '</ul>';
				}
			}

			$enabled = $show_obj_active ? OIDplus::getRegisteredObjectTypes() : array();
			$disabled = $show_obj_inactive ? OIDplus::getDisabledObjectTypes() : array();
			$plugins = array_merge($enabled, $disabled);
			if (count($plugins) > 0) {
				$out['text'] .= '<p><u>Object types:</u></p><ul>';
				foreach ($plugins as $ot) {
					if (in_array($ot, $enabled)) {
						$out['text'] .= '<li><a '.oidplus_link('oidplus:system_plugins.$'.$ot).'>'.htmlentities($ot::objectTypeTitle()).' ('.htmlentities($ot::ns()).')</a></li>';
					} else {
						$out['text'] .= '<li><a '.oidplus_link('oidplus:system_plugins.$'.$ot).'><font color="gray">'.htmlentities($ot::objectTypeTitle()).' ('.htmlentities($ot::ns()).', disabled)</font></a></li>';
					}
				}
				$out['text'] .= '</ul>';
			}

			if ($show_db_active || $show_db_inactive) {
				if (count($plugins = OIDplus::getDatabasePlugins()) > 0) {
					$out['text'] .= '<p><u>Database plugins:</u></p><ul>';
					foreach ($plugins as $plugin) {
						if ($plugin::name() == OIDPLUS_DATABASE_PLUGIN) {
							$out['text'] .= $show_db_active ? '<li><a '.oidplus_link('oidplus:system_plugins.$'.get_class($plugin)).'><b>'.htmlentities($plugin::name()).'</b></a></li>' : '';
						} else {
							$out['text'] .= $show_db_inactive ? '<li><a '.oidplus_link('oidplus:system_plugins.$'.get_class($plugin)).'>'.htmlentities($plugin::name()).'</a></li>' : '';
						}
					}
					$out['text'] .= '</ul>';
				}
			}
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$tree_icon_pages = $tree_icon; // TODO
		$tree_icon_pages_public = $tree_icon; // TODO
		$tree_icon_pages_ra = $tree_icon; // TODO
		$tree_icon_pages_admin = $tree_icon; // TODO
		$tree_icon_db_active = $tree_icon; // TODO
		$tree_icon_db_inactive = $tree_icon; // TODO
		$tree_icon_obj_active = $tree_icon; // TODO
		$tree_icon_obj_inactive = $tree_icon; // TODO

		$public_plugins = array();
		foreach (OIDplus::getPagePlugins('public') as $plugin) {
			$public_plugins[] = array(
				'id' => 'oidplus:system_plugins.$'.get_class($plugin),
				'icon' => $tree_icon_pages_public,
				'text' => get_class($plugin),
			);
		}
		$ra_plugins = array();
		foreach (OIDplus::getPagePlugins('ra') as $plugin) {
			$ra_plugins[] = array(
				'id' => 'oidplus:system_plugins.$'.get_class($plugin),
				'icon' => $tree_icon_pages_ra,
				'text' => get_class($plugin),
			);
		}
		$admin_plugins = array();
		foreach (OIDplus::getPagePlugins('admin') as $plugin) {
			$admin_plugins[] = array(
				'id' => 'oidplus:system_plugins.$'.get_class($plugin),
				'icon' => $tree_icon_pages_admin,
				'text' => get_class($plugin),
			);
		}
		$db_plugins = array();
		foreach (OIDplus::getDatabasePlugins() as $plugin) {
			$txt = get_class($plugin);
			if ($plugin::name() == OIDPLUS_DATABASE_PLUGIN) {
				$db_plugins[] = array(
					'id' => 'oidplus:system_plugins.$'.get_class($plugin),
					'icon' => $tree_icon_db_active,
					'text' => $txt,
				 );
			} else {
				$db_plugins[] = array(
					'id' => 'oidplus:system_plugins.$'.get_class($plugin),
					'icon' => $tree_icon_db_inactive,
					'text' => '<font color="gray">'.$txt.'</font>',
				 );
			}
		}
		$obj_plugins = array();
		$enabled = OIDplus::getRegisteredObjectTypes();
		$disabled = OIDplus::getDisabledObjectTypes();
		foreach (array_merge($enabled, $disabled) as $ot) {
			$txt = htmlentities($ot::objectTypeTitle()).' ('.htmlentities($ot::ns()).')';
			if (in_array($ot, $enabled)) {
				$obj_plugins[] = array(
					'id' => 'oidplus:system_plugins.$'.get_class(new $ot('')),
					'icon' => $tree_icon_obj_active,
					'text' => $txt,
				 );
			} else {
				$obj_plugins[] = array(
					'id' => 'oidplus:system_plugins.$'.get_class(new $ot('')),
					'icon' => $tree_icon_obj_inactive,
					'text' => '<font color="gray">'.$txt.'</font>',
				 );
			}
		}
		$json[] = array(
			'id' => 'oidplus:system_plugins',
			'icon' => $tree_icon,
			'text' => 'Plugins',
			'children' => array(
			array(
				'id' => 'oidplus:system_plugins.pages',
				'icon' => $tree_icon,
				'text' => 'Page plugins',
				'children' => array(
					array(
						'id' => 'oidplus:system_plugins.pages.public',
						'icon' => $tree_icon,
						'text' => 'Public',
						'children' => $public_plugins
					),
					array(
						'id' => 'oidplus:system_plugins.pages.ra',
						'icon' => $tree_icon,
						'text' => 'RA',
						'children' => $ra_plugins
					),
					array(
						'id' => 'oidplus:system_plugins.pages.admin',
						'icon' => $tree_icon,
						'text' => 'Admin',
						'children' => $admin_plugins
					)
				)
				),
				array(
					'id' => 'oidplus:system_plugins.objects',
					'icon' => $tree_icon,
					'text' => 'Object types',
					'children' => $obj_plugins
				),
				array(
					'id' => 'oidplus:system_plugins.database',
					'icon' => $tree_icon,
					'text' => 'Database providers',
					'children' => $db_plugins
				)
			)
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}
}

OIDplus::registerPagePlugin(new OIDplusPageAdminPlugins());
