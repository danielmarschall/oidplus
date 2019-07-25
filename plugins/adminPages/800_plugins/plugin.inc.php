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
		if ($id == 'oidplus:system_plugins') {
			$handled = true;

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] .= '<p>You need to <a '.oidplus_link('oidplus:login').'>log in</a> as administrator.</p>';
				return $out;
			}

			$out['title'] = "Installed plugins";
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/icon_big.png' : '';

			if (count($plugins = OIDplus::getPagePlugins('public')) > 0) {
				$out['text'] .= '<p><u>Public page plugins:</u></p><ul>';
				foreach ($plugins as $plugin) {
					$out['text'] .= '<li>'.get_class($plugin).'</li>';
				}
				$out['text'] .= '</ul>';
			}

			if (count($plugins = OIDplus::getPagePlugins('ra')) > 0) {
				$out['text'] .= '<p><u>RA page plugins:</u></p><ul>';
				foreach ($plugins as $plugin) {
					$out['text'] .= '<li>'.get_class($plugin).'</li>';
				}
				$out['text'] .= '</ul>';
			}

			if (count($plugins = OIDplus::getPagePlugins('admin')) > 0) {
				$out['text'] .= '<p><u>Admin page plugins:</u></p><ul>';
				foreach ($plugins as $plugin) {
					$out['text'] .= '<li>'.get_class($plugin).'</li>';
				}
				$out['text'] .= '</ul>';
			}

			if (count($plugins = OIDplus::getRegisteredObjectTypes()) > 0) {
				$out['text'] .= '<p><u>Enabled object types:</u></p><ul>';
				foreach ($plugins as $ot) {
					$out['text'] .= '<li>'.$ot::objectTypeTitle().' ('.$ot::ns().')</li>';
				}
				$out['text'] .= '</ul>';
			}

			if (count($plugins = OIDplus::getDisabledObjectTypes()) > 0) {
				$out['text'] .= '<ul><u>Disabled object types:</u></ul>';
				foreach ($plugins as $ot) {
					$out['text'] .= '<li>'.$ot::objectTypeTitle().' ('.$ot::ns().')</li>';
				}
				$out['text'] .= '</ul>';
			}
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:system_plugins',
			'icon' => $tree_icon,
			'text' => 'Plugins'
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}
}

OIDplus::registerPagePlugin(new OIDplusPageAdminPlugins());
