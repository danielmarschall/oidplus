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

// TODO: getPluginManifest can get NULL !!!

class OIDplusPageAdminPlugins extends OIDplusPagePluginAdmin {

	public function action(&$handled) {
	}

	public function init($html=true) {
	}

	public function gui($id, &$out, &$handled) {
		$tmp = explode('$',$id);
		$classname = isset($tmp[1]) ? $tmp[1] : null;

		$parts = explode('.',$tmp[0],2);
		if (!isset($parts[1])) $parts[1] = '';
		if ($parts[0] != 'oidplus:system_plugins') return;
		$handled = true;
		$out['title'] = "Installed plugins";
		$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';

		if (!OIDplus::authUtils()::isAdminLoggedIn()) {
			$out['icon'] = 'img/error_big.png';
			$out['text'] = '<p>You need to <a '.OIDplus::gui()->link('oidplus:login').'>log in</a> as administrator.</p>';
			return;
		}

		if (!is_null($classname)) {
			$out['title'] = htmlentities($classname);
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';

			$reflector = new \ReflectionClass($classname);
			$pluginManifest = OIDplus::getPluginManifest($classname);

			$out['text'] .= '<div><label class="padding_label">Classname</label><b>'.htmlentities($classname).'</b></div>'.
					'<div><label class="padding_label">Location</label><b>'.htmlentities(dirname($reflector->getFileName())).'</b></div>'.
					'<div><label class="padding_label">Plugin type</label><b>'.htmlentities(get_parent_class($classname)).'</b></div>'.
					'<div><label class="padding_label">Plugin name</label><b>'.htmlentities(empty($pluginManifest->getName()) ? 'n/a' : $pluginManifest->getName()).'</b></div>'.
					'<div><label class="padding_label">Plugin author</label><b>'.htmlentities(empty($pluginManifest->getAuthor()) ? 'n/a' : $pluginManifest->getAuthor()).'</b></div>'.
					'<div><label class="padding_label">Plugin version</label><b>'.htmlentities(empty($pluginManifest->getVersion()) ? 'n/a' : $pluginManifest->getVersion()).'</b></div>'.
					(!empty($pluginManifest->getHtmlDescription()) ? '<br><p><b>Additional information:</b></p>' : '').
					$pluginManifest->getHtmlDescription();
		} else {
			$show_pages_public = false;
			$show_pages_ra = false;
			$show_pages_admin = false;
			$show_db_active = false;
			$show_db_inactive = false;
			$show_sql_active = false;
			$show_sql_inactive = false;
			$show_obj_active = false;
			$show_obj_inactive = false;
			$show_auth = false;
			$show_logger = false;

			if ($parts[1] == '') {
				$out['title'] = "Installed plugins";
				$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';
				$show_pages_public = true;
				$show_pages_ra = true;
				$show_pages_admin = true;
				$show_db_active = true;
				$show_db_inactive = true;
				$show_sql_active = true;
				$show_sql_inactive = true;
				$show_obj_active = true;
				$show_obj_inactive = true;
				$show_auth = true;
				$show_logger = true;
			} else if ($parts[1] == 'pages') {
				$out['title'] = "Page plugins";
				$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';
				$show_pages_public = true;
				$show_pages_ra = true;
				$show_pages_admin = true;
			} else if ($parts[1] == 'pages.public') {
				$out['title'] = "Public page plugins";
				$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';
				$show_pages_public = true;
			} else if ($parts[1] == 'pages.ra') {
				$out['title'] = "RA page plugins";
				$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';
				$show_pages_ra = true;
			} else if ($parts[1] == 'pages.admin') {
				$out['title'] = "Admin page plugins";
				$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';
				$show_pages_admin = true;
			} else if ($parts[1] == 'objects') {
				$out['title'] = "Object type plugins";
				$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';
				$show_obj_active = true;
				$show_obj_inactive = true;
			} else if ($parts[1] == 'objects.enabled') {
				$out['title'] = "Object type plugins (enabled)";
				$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';
				$show_obj_active = true;
			} else if ($parts[1] == 'objects.disabled') {
				$out['title'] = "Object type plugins (disabled)";
				$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';
				$show_obj_inactive = true;
			} else if ($parts[1] == 'database') {
				$out['title'] = "Database provider plugins";
				$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';
				$show_db_active = true;
				$show_db_inactive = true;
			} else if ($parts[1] == 'database.enabled') {
				$out['title'] = "Database provider plugins (active)";
				$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';
				$show_db_active = true;
			} else if ($parts[1] == 'database.disabled') {
				$out['title'] = "Database provider plugins (inactive)";
				$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';
				$show_db_inactive = true;
			} else if ($parts[1] == 'sql') {
				$out['title'] = "SQL slang plugins";
				$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';
				$show_sql_active = true;
				$show_sql_inactive = true;
			} else if ($parts[1] == 'sql.enabled') {
				$out['title'] = "SQL slang plugins (active)";
				$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';
				$show_sql_active = true;
			} else if ($parts[1] == 'sql.disabled') {
				$out['title'] = "SQL slang plugins (inactive)";
				$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';
				$show_sql_inactive = true;
			} else if ($parts[1] == 'auth') {
				$out['title'] = "RA authentication";
				$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';
				$show_auth = true;
			} else if ($parts[1] == 'logger') {
				$out['title'] = "Logger";
				$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';
				$show_logger = true;
			} else {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>Invalid arguments.</p>';
				return;
			}

			$pp_public = array();
			$pp_ra = array();
			$pp_admin = array();

			foreach (OIDplus::getPagePlugins() as $plugin) {
				if (is_subclass_of($plugin, OIDplusPagePluginPublic::class)) {
					$pp_public[] = $plugin;
				}
				if (is_subclass_of($plugin, OIDplusPagePluginRa::class)) {
					$pp_ra[] = $plugin;
				}
				if (is_subclass_of($plugin, OIDplusPagePluginAdmin::class)) {
					$pp_admin[] = $plugin;
				}
			}

			if ($show_pages_public) {
				if (count($plugins = $pp_public) > 0) {
					$out['text'] .= '<h2>Public page plugins</h2>';
					$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
					$out['text'] .= '<table class="table table-bordered table-striped">';
					$out['text'] .= '	<tr>';
					$out['text'] .= '		<th width="25%">Class name</th>';
					$out['text'] .= '		<th width="25%">Plugin name</th>';
					$out['text'] .= '		<th width="25%">Plugin version</th>';
					$out['text'] .= '		<th width="25%">Plugin author</th>';
					$out['text'] .= '	</tr>';
					foreach ($plugins as $plugin) {
						$out['text'] .= '	<tr>';
						$pluginManifest = OIDplus::getPluginManifest($plugin);
						$out['text'] .= '<td><a '.OIDplus::gui()->link('oidplus:system_plugins$'.get_class($plugin)).'>'.htmlentities(get_class($plugin)).'</a></td>';
						$out['text'] .= '<td>' . htmlentities(empty($pluginManifest->getName()) ? 'n/a' : $pluginManifest->getName()) . '</td>';
						$out['text'] .= '<td>' . htmlentities(empty($pluginManifest->getVersion()) ? 'n/a' : $pluginManifest->getVersion()) . '</td>';
						$out['text'] .= '<td>' . htmlentities(empty($pluginManifest->getAuthor()) ? 'n/a' : $pluginManifest->getAuthor()) . '</td>';
						$out['text'] .= '	</tr>';
					}
					$out['text'] .= '</table>';
					$out['text'] .= '</div></div>';
				}
			}

			if ($show_pages_ra) {
				if (count($plugins = $pp_ra) > 0) {
					$out['text'] .= '<h2>RA page plugins</h2>';
					$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
					$out['text'] .= '<table class="table table-bordered table-striped">';
					$out['text'] .= '	<tr>';
					$out['text'] .= '		<th width="25%">Class name</th>';
					$out['text'] .= '		<th width="25%">Plugin name</th>';
					$out['text'] .= '		<th width="25%">Plugin version</th>';
					$out['text'] .= '		<th width="25%">Plugin author</th>';
					$out['text'] .= '	</tr>';
					foreach ($plugins as $plugin) {
						$out['text'] .= '	<tr>';
						$pluginManifest = OIDplus::getPluginManifest($plugin);
						$out['text'] .= '<td><a '.OIDplus::gui()->link('oidplus:system_plugins$'.get_class($plugin)).'>'.htmlentities(get_class($plugin)).'</a></td>';
						$out['text'] .= '<td>' . htmlentities(empty($pluginManifest->getName()) ? 'n/a' : $pluginManifest->getName()) . '</td>';
						$out['text'] .= '<td>' . htmlentities(empty($pluginManifest->getVersion()) ? 'n/a' : $pluginManifest->getVersion()) . '</td>';
						$out['text'] .= '<td>' . htmlentities(empty($pluginManifest->getAuthor()) ? 'n/a' : $pluginManifest->getAuthor()) . '</td>';
						$out['text'] .= '	</tr>';
					}
					$out['text'] .= '</table>';
					$out['text'] .= '</div></div>';
				}
			}

			if ($show_pages_admin) {
				if (count($plugins = $pp_admin) > 0) {
					$out['text'] .= '<h2>Admin page plugins</h2>';
					$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
					$out['text'] .= '<table class="table table-bordered table-striped">';
					$out['text'] .= '	<tr>';
					$out['text'] .= '		<th width="25%">Class name</th>';
					$out['text'] .= '		<th width="25%">Plugin name</th>';
					$out['text'] .= '		<th width="25%">Plugin version</th>';
					$out['text'] .= '		<th width="25%">Plugin author</th>';
					$out['text'] .= '	</tr>';
					foreach ($plugins as $plugin) {
						$out['text'] .= '	<tr>';
						$pluginManifest = OIDplus::getPluginManifest($plugin);
						$out['text'] .= '<td><a '.OIDplus::gui()->link('oidplus:system_plugins$'.get_class($plugin)).'>'.htmlentities(get_class($plugin)).'</a></td>';
						$out['text'] .= '<td>' . htmlentities(empty($pluginManifest->getName()) ? 'n/a' : $pluginManifest->getName()) . '</td>';
						$out['text'] .= '<td>' . htmlentities(empty($pluginManifest->getVersion()) ? 'n/a' : $pluginManifest->getVersion()) . '</td>';
						$out['text'] .= '<td>' . htmlentities(empty($pluginManifest->getAuthor()) ? 'n/a' : $pluginManifest->getAuthor()) . '</td>';
						$out['text'] .= '	</tr>';
					}
					$out['text'] .= '</table>';
					$out['text'] .= '</div></div>';
				}
			}

			if ($show_obj_active || $show_obj_inactive) {
				$enabled = $show_obj_active ? OIDplus::getObjectTypePluginsEnabled() : array();
				$disabled = $show_obj_inactive ? OIDplus::getObjectTypePluginsDisabled() : array();
				if (count($plugins = array_merge($enabled, $disabled)) > 0) {
					$out['text'] .= '<h2>Object types</h2>';
					$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
					$out['text'] .= '<table class="table table-bordered table-striped">';
					$out['text'] .= '	<tr>';
					$out['text'] .= '		<th width="25%">Class name</th>';
					$out['text'] .= '		<th width="25%">Plugin name</th>';
					$out['text'] .= '		<th width="25%">Plugin version</th>';
					$out['text'] .= '		<th width="25%">Plugin author</th>';
					$out['text'] .= '	</tr>';
					foreach ($plugins as $plugin) {
						$out['text'] .= '	<tr>';
						$pluginManifest = OIDplus::getPluginManifest($plugin);
						if (in_array($plugin, $enabled)) {
							$out['text'] .= '<td><a '.OIDplus::gui()->link('oidplus:system_plugins$'.get_class($plugin)).'>'.htmlentities(get_class($plugin)).'</a></td>';
						} else {
							$out['text'] .= '<td><a '.OIDplus::gui()->link('oidplus:system_plugins$'.get_class($plugin)).'><font color="gray">'.htmlentities(get_class($plugin)).' (disabled)</font></a></td>';
						}
						$out['text'] .= '<td>' . htmlentities(empty($pluginManifest->getName()) ? 'n/a' : $pluginManifest->getName()) . '</td>';
						$out['text'] .= '<td>' . htmlentities(empty($pluginManifest->getVersion()) ? 'n/a' : $pluginManifest->getVersion()) . '</td>';
						$out['text'] .= '<td>' . htmlentities(empty($pluginManifest->getAuthor()) ? 'n/a' : $pluginManifest->getAuthor()) . '</td>';
						$out['text'] .= '	</tr>';
					}
					$out['text'] .= '</table>';
					$out['text'] .= '</div></div>';
				}
			}

			if ($show_db_active || $show_db_inactive) {
				if (count($plugins = OIDplus::getDatabasePlugins()) > 0) {
					$out['text'] .= '<h2>Database providers</h2>';
					$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
					$out['text'] .= '<table class="table table-bordered table-striped">';
					$out['text'] .= '	<tr>';
					$out['text'] .= '		<th width="25%">Class name</th>';
					$out['text'] .= '		<th width="25%">Plugin name</th>';
					$out['text'] .= '		<th width="25%">Plugin version</th>';
					$out['text'] .= '		<th width="25%">Plugin author</th>';
					$out['text'] .= '	</tr>';
					foreach ($plugins as $plugin) {
						$active = $plugin::id() == OIDplus::baseConfig()->getValue('DATABASE_PLUGIN');
						if ($active && !$show_db_active) continue;
						if (!$active && !$show_db_inactive) continue;

						$out['text'] .= '	<tr>';
						$pluginManifest = OIDplus::getPluginManifest($plugin);
						if ($active) {
							$out['text'] .= '<td><a '.OIDplus::gui()->link('oidplus:system_plugins$'.get_class($plugin)).'><b>'.htmlentities(get_class($plugin)).'</b> (active)</a></td>';
						} else {
							$out['text'] .= '<td><a '.OIDplus::gui()->link('oidplus:system_plugins$'.get_class($plugin)).'>'.htmlentities(get_class($plugin)).'</a></td>';
						}
						$out['text'] .= '<td>' . htmlentities(empty($pluginManifest->getName()) ? 'n/a' : $pluginManifest->getName()) . '</td>';
						$out['text'] .= '<td>' . htmlentities(empty($pluginManifest->getVersion()) ? 'n/a' : $pluginManifest->getVersion()) . '</td>';
						$out['text'] .= '<td>' . htmlentities(empty($pluginManifest->getAuthor()) ? 'n/a' : $pluginManifest->getAuthor()) . '</td>';
						$out['text'] .= '	</tr>';
					}
					$out['text'] .= '</table>';
					$out['text'] .= '</div></div>';
				}
			}

			if ($show_sql_active || $show_sql_inactive) {
				if (count($plugins = OIDplus::getSqlSlangPlugins()) > 0) {
					$out['text'] .= '<h2>SQL slang plugins</h2>';
					$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
					$out['text'] .= '<table class="table table-bordered table-striped">';
					$out['text'] .= '	<tr>';
					$out['text'] .= '		<th width="25%">Class name</th>';
					$out['text'] .= '		<th width="25%">Plugin name</th>';
					$out['text'] .= '		<th width="25%">Plugin version</th>';
					$out['text'] .= '		<th width="25%">Plugin author</th>';
					$out['text'] .= '	</tr>';
					foreach ($plugins as $plugin) {
						$active = $plugin::id() == OIDplus::db()->getSlang()::id();
						if ($active && !$show_sql_active) continue;
						if (!$active && !$show_sql_inactive) continue;

						$out['text'] .= '	<tr>';
						$pluginManifest = OIDplus::getPluginManifest($plugin);
						if ($active) {
							$out['text'] .= '<td><a '.OIDplus::gui()->link('oidplus:system_plugins$'.get_class($plugin)).'><b>'.htmlentities(get_class($plugin)).'</b> (active)</a></td>';
						} else {
							$out['text'] .= '<td><a '.OIDplus::gui()->link('oidplus:system_plugins$'.get_class($plugin)).'>'.htmlentities(get_class($plugin)).'</a></td>';
						}
						$out['text'] .= '<td>' . htmlentities(empty($pluginManifest->getName()) ? 'n/a' : $pluginManifest->getName()) . '</td>';
						$out['text'] .= '<td>' . htmlentities(empty($pluginManifest->getVersion()) ? 'n/a' : $pluginManifest->getVersion()) . '</td>';
						$out['text'] .= '<td>' . htmlentities(empty($pluginManifest->getAuthor()) ? 'n/a' : $pluginManifest->getAuthor()) . '</td>';
						$out['text'] .= '	</tr>';
					}
					$out['text'] .= '</table>';
					$out['text'] .= '</div></div>';
				}
			}

			if ($show_auth) {
				if (count($plugins = OIDplus::getAuthPlugins()) > 0) {
					$out['text'] .= '<h2>RA authentication providers</h2>';
					$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
					$out['text'] .= '<table class="table table-bordered table-striped">';
					$out['text'] .= '	<tr>';
					$out['text'] .= '		<th width="25%">Class name</th>';
					$out['text'] .= '		<th width="25%">Plugin name</th>';
					$out['text'] .= '		<th width="25%">Plugin version</th>';
					$out['text'] .= '		<th width="25%">Plugin author</th>';
					$out['text'] .= '	</tr>';
					foreach ($plugins as $plugin) {
						$out['text'] .= '	<tr>';
						$pluginManifest = OIDplus::getPluginManifest($plugin);
						$out['text'] .= '<td><a '.OIDplus::gui()->link('oidplus:system_plugins$'.get_class($plugin)).'>'.htmlentities(get_class($plugin)).'</a></td>';
						$out['text'] .= '<td>' . htmlentities(empty($pluginManifest->getName()) ? 'n/a' : $pluginManifest->getName()) . '</td>';
						$out['text'] .= '<td>' . htmlentities(empty($pluginManifest->getVersion()) ? 'n/a' : $pluginManifest->getVersion()) . '</td>';
						$out['text'] .= '<td>' . htmlentities(empty($pluginManifest->getAuthor()) ? 'n/a' : $pluginManifest->getAuthor()) . '</td>';
						$out['text'] .= '	</tr>';
					}
					$out['text'] .= '</table>';
					$out['text'] .= '</div></div>';
				}
			}

			if ($show_logger) {
				if (count($plugins = OIDplus::getLoggerPlugins()) > 0) {
					$out['text'] .= '<h2>Logger plugins</h2>';
					$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
					$out['text'] .= '<table class="table table-bordered table-striped">';
					$out['text'] .= '	<tr>';
					$out['text'] .= '		<th width="25%">Class name</th>';
					$out['text'] .= '		<th width="25%">Plugin name</th>';
					$out['text'] .= '		<th width="25%">Plugin version</th>';
					$out['text'] .= '		<th width="25%">Plugin author</th>';
					$out['text'] .= '	</tr>';
					foreach ($plugins as $plugin) {
						$reason = '';
						$active = $plugin->available($reason);

						$out['text'] .= '	<tr>';
						$pluginManifest = OIDplus::getPluginManifest($plugin);
						if ($active) {
							$out['text'] .= '<td><a '.OIDplus::gui()->link('oidplus:system_plugins$'.get_class($plugin)).'>'.htmlentities(get_class($plugin)).'</a></td>';
						} else {
							$out['text'] .= '<td><a '.OIDplus::gui()->link('oidplus:system_plugins$'.get_class($plugin)).'><font color="gray">'.htmlentities(get_class($plugin)).'</font></a> <font color="gray">(not available: '.htmlentities($reason).')</font></td>';
						}
						$out['text'] .= '<td>' . htmlentities(empty($pluginManifest->getName()) ? 'n/a' : $pluginManifest->getName()) . '</td>';
						$out['text'] .= '<td>' . htmlentities(empty($pluginManifest->getVersion()) ? 'n/a' : $pluginManifest->getVersion()) . '</td>';
						$out['text'] .= '<td>' . htmlentities(empty($pluginManifest->getAuthor()) ? 'n/a' : $pluginManifest->getAuthor()) . '</td>';
						$out['text'] .= '	</tr>';
					}
					$out['text'] .= '</table>';
					$out['text'] .= '</div></div>';
				}
			}
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (!OIDplus::authUtils()::isAdminLoggedIn()) return false;

		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = OIDplus::webpath(__DIR__).'treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$tree_icon_pages = $tree_icon; // TODO
		$tree_icon_pages_public = $tree_icon; // TODO
		$tree_icon_pages_ra = $tree_icon; // TODO
		$tree_icon_pages_admin = $tree_icon; // TODO
		$tree_icon_db_active = $tree_icon; // TODO
		$tree_icon_db_inactive = $tree_icon; // TODO
		$tree_icon_sql_active = $tree_icon; // TODO
		$tree_icon_sql_inactive = $tree_icon; // TODO
		$tree_icon_obj_active = $tree_icon; // TODO
		$tree_icon_obj_inactive = $tree_icon; // TODO
		$tree_icon_auth = $tree_icon; // TODO
		$tree_icon_logger = $tree_icon; // TODO

		$pp_public = array();
		$pp_ra = array();
		$pp_admin = array();

		foreach (OIDplus::getPagePlugins() as $plugin) {
			if (is_subclass_of($plugin, OIDplusPagePluginPublic::class)) {
				$pp_public[] = $plugin;
			}
			if (is_subclass_of($plugin, OIDplusPagePluginRa::class)) {
				$pp_ra[] = $plugin;
			}
			if (is_subclass_of($plugin, OIDplusPagePluginAdmin::class)) {
				$pp_admin[] = $plugin;
			}
		}


		$public_plugins = array();
		foreach ($pp_public as $plugin) {
			$pluginManifest = OIDplus::getPluginManifest($plugin);
			$txt = (empty($pluginManifest->getName())) ? get_class($plugin) : $pluginManifest->getName();

			$public_plugins[] = array(
				'id' => 'oidplus:system_plugins$'.get_class($plugin),
				'icon' => $tree_icon_pages_public,
				'text' => $txt,
			);
		}
		$ra_plugins = array();
		foreach ($pp_ra as $plugin) {
			$pluginManifest = OIDplus::getPluginManifest($plugin);
			$txt = (empty($pluginManifest->getName())) ? get_class($plugin) : $pluginManifest->getName();

			$ra_plugins[] = array(
				'id' => 'oidplus:system_plugins$'.get_class($plugin),
				'icon' => $tree_icon_pages_ra,
				'text' => $txt,
			);
		}
		$admin_plugins = array();
		foreach ($pp_admin as $plugin) {
			$pluginManifest = OIDplus::getPluginManifest($plugin);
			$txt = (empty($pluginManifest->getName())) ? get_class($plugin) : $pluginManifest->getName();

			$admin_plugins[] = array(
				'id' => 'oidplus:system_plugins$'.get_class($plugin),
				'icon' => $tree_icon_pages_admin,
				'text' => $txt,
			);
		}
		$db_plugins = array();
		foreach (OIDplus::getDatabasePlugins() as $plugin) {
			$pluginManifest = OIDplus::getPluginManifest($plugin);
			$txt = (empty($pluginManifest->getName())) ? get_class($plugin) : $pluginManifest->getName();

			if ($plugin::id() == OIDplus::baseConfig()->getValue('DATABASE_PLUGIN')) {
				$db_plugins[] = array(
					'id' => 'oidplus:system_plugins$'.get_class($plugin),
					'icon' => $tree_icon_db_active,
					'text' => $txt,
				 );
			} else {
				$db_plugins[] = array(
					'id' => 'oidplus:system_plugins$'.get_class($plugin),
					'icon' => $tree_icon_db_inactive,
					'text' => '<font color="gray">'.$txt.'</font>',
				 );
			}
		}
		$sql_plugins = array();
		foreach (OIDplus::getSqlSlangPlugins() as $plugin) {
			$pluginManifest = OIDplus::getPluginManifest($plugin);
			$txt = (empty($pluginManifest->getName())) ? get_class($plugin) : $pluginManifest->getName();

			if ($plugin::id() == OIDplus::db()->getSlang()::id()) {
				$sql_plugins[] = array(
					'id' => 'oidplus:system_plugins$'.get_class($plugin),
					'icon' => $tree_icon_db_active,
					'text' => $txt,
				 );
			} else {
				$sql_plugins[] = array(
					'id' => 'oidplus:system_plugins$'.get_class($plugin),
					'icon' => $tree_icon_db_inactive,
					'text' => '<font color="gray">'.$txt.'</font>',
				 );
			}
		}
		$obj_plugins = array();
		$enabled = OIDplus::getObjectTypePluginsEnabled();
		$disabled = OIDplus::getObjectTypePluginsDisabled();
		foreach (array_merge($enabled, $disabled) as $plugin) {
			$pluginManifest = OIDplus::getPluginManifest($plugin);
			$txt = (empty($pluginManifest->getName())) ? get_class($plugin) : $pluginManifest->getName();
			if (in_array($plugin, $enabled)) {
				$obj_plugins[] = array(
					'id' => 'oidplus:system_plugins$'.get_class($plugin),
					'icon' => $tree_icon_obj_active,
					'text' => $txt,
				 );
			} else {
				$obj_plugins[] = array(
					'id' => 'oidplus:system_plugins$'.get_class($plugin),
					'icon' => $tree_icon_obj_inactive,
					'text' => '<font color="gray">'.$txt.'</font>',
				 );
			}
		}
		$auth_plugins = array();
		foreach (OIDplus::getAuthPlugins() as $plugin) {
			$pluginManifest = OIDplus::getPluginManifest($plugin);
			$txt = (empty($pluginManifest->getName())) ? get_class($plugin) : $pluginManifest->getName();

			$auth_plugins[] = array(
				'id' => 'oidplus:system_plugins$'.get_class($plugin),
				'icon' => $tree_icon_auth,
				'text' => $txt,
			);
		}
		$logger_plugins = array();
		foreach (OIDplus::getLoggerPlugins() as $plugin) {
			$pluginManifest = OIDplus::getPluginManifest($plugin);
			$txt = (empty($pluginManifest->getName())) ? get_class($plugin) : $pluginManifest->getName();

			$reason = '';
			if (!$plugin->available($reason)) $txt = '<font color="gray">'.$txt.'</font>';

			$logger_plugins[] = array(
				'id' => 'oidplus:system_plugins$'.get_class($plugin),
				'icon' => $tree_icon_logger,
				'text' => $txt,
			);
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
				),
				array(
					'id' => 'oidplus:system_plugins.sql',
					'icon' => $tree_icon,
					'text' => 'SQL slangs',
					'children' => $sql_plugins
				),
				array(
					'id' => 'oidplus:system_plugins.auth',
					'icon' => $tree_icon,
					'text' => 'RA authentication',
					'children' => $auth_plugins
				),
				array(
					'id' => 'oidplus:system_plugins.logger',
					'icon' => $tree_icon,
					'text' => 'Logger',
					'children' => $logger_plugins
				)
			)
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}
}
