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

class OIDplusPagePublicObjects extends OIDplusPagePlugin {
	public function type() {
		return 'public';
	}

	public function priority() {
		return 0;
	}

	public function action(&$handled) {
	}

	public function init($html=true) {
	}

	public function cfgSetValue($name, $value) {
	}

	public function gui($id, &$out, &$handled) {
		if ($id === 'oidplus:system') {
			$handled = true;

			$out['title'] = OIDplus::config()->systemTitle(); // 'Object Database of ' . $_SERVER['SERVER_NAME'];
			$out['icon'] = 'img/system_big.png';
			$out['text'] = file_get_contents('welcome.html');

			if (strpos($out['text'], '%%OBJECT_TYPE_LIST%%') !== false) {
				$tmp = '<ul>';
				foreach (OIDplus::getRegisteredObjectTypes() as $ot) {
					$tmp .= '<li><a href="?goto='.urlencode($ot::root()).'" onclick="openOidInPanel('.js_escape($ot::root()).', true); return false;">'.htmlentities($ot::objectTypeTitle()).'</a></li>';
				}
				$tmp .= '</ul>';
				$out['text'] = str_replace('%%OBJECT_TYPE_LIST%%', $tmp, $out['text']);
			}

			return $out;
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false) {
		$json = array();

		if ($nonjs) {
			$static_node_id = isset($_REQUEST['goto']) ? $_REQUEST['goto'] : 'oidplus:system';

			$json[] = array('id' => 'oidplus:system', 'icon' => 'img/system.png', 'text' => 'System');

			$parent = '';
			$res = OIDplus::db()->query("select parent from ".OIDPLUS_TABLENAME_PREFIX."objects where id = '".OIDplus::db()->real_escape_string($static_node_id)."'");
			while ($row = OIDplus::db()->fetch_object($res)) {
				$parent = $row->parent;
			}

			$objTypesChildren = array();
			foreach (OIDplus::getRegisteredObjectTypes() as $ot) {
				$icon = 'plugins/objectTypes/'.$ot::ns().'/img/treeicon_root.png';
				$json[] = array('id' => $ot::root(), 'icon' => $icon, 'text' => $ot::objectTypeTitle());

				try {
					$tmp = OIDplusObject::parse($static_node_id);
				} catch (Exception $e) {
					$tmp = null;
				}
				if (!is_null($tmp) && ($ot == get_class($tmp))) {
					// TODO: Instead of just having 3 levels (parent, this and children), it would be better if we'd had a full tree of all parents
					//       on the other hand, for giving search engines content, this is good enough
					$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where " .
					                   "parent = '".OIDplus::db()->real_escape_string($static_node_id)."' or " .
					                   "id = '".OIDplus::db()->real_escape_string($static_node_id)."' " .
					                   ((!empty($parent)) ? " or id = '".OIDplus::db()->real_escape_string($parent)."' " : "") .
					                   "order by ".OIDplus::db()->natOrder('id'));
					$z_used = 0;
					$y_used = 0;
					$x_used = 0;
					$stufe = 0;
					$menu_entries = array();
					$stufen = array();
					while ($row = OIDplus::db()->fetch_object($res)) {
						$obj = OIDplusObject::parse($row->id);
						if (is_null($obj)) continue; // might happen if the objectType is not available/loaded
						if (!$obj->userHasReadRights()) continue;
						$txt = $row->title == '' ? '' : ' -- '.htmlentities($row->title);

						if ($row->id == $parent) { $stufe=0; $z_used++; }
						if ($row->id == $static_node_id) { $stufe=1; $y_used++; }
						if ($row->parent == $static_node_id) { $stufe=2; $x_used++; }

						$menu_entry = array('id' => $row->id, 'icon' => '', 'text' => $txt, 'indent' => 0);
						$menu_entries[] = $menu_entry;
						$stufen[] = $stufe;
					}
					if ($x_used) foreach ($menu_entries as $i => &$menu_entry) if ($stufen[$i] >= 2) $menu_entry['indent'] += 1;
					if ($y_used) foreach ($menu_entries as $i => &$menu_entry) if ($stufen[$i] >= 1) $menu_entry['indent'] += 1;
					if ($z_used) foreach ($menu_entries as $i => &$menu_entry) if ($stufen[$i] >= 0) $menu_entry['indent'] += 1;
					$json = array_merge($json, $menu_entries);
				}
			}

			return true;
		} else {
			if (isset($req_goto)) {
					$goto = $req_goto;
					$path = array();
					while (true) {
						$path[] = $goto;
						$res = OIDplus::db()->query("select parent from ".OIDPLUS_TABLENAME_PREFIX."objects where id = '".OIDplus::db()->real_escape_string($goto)."'");
						if (OIDplus::db()->num_rows($res) == 0) break;
						$row = OIDplus::db()->fetch_array($res);
						$goto = $row['parent'];
					}

					$goto_path = array_reverse($path);
			} else {
				$goto_path = null;
			}

			$objTypesChildren = array();
			foreach (OIDplus::getRegisteredObjectTypes() as $ot) {
				$child = array(
						'id' => $ot::root(),
						'text' => $ot::objectTypeTitle(),
						'state' => array("opened" => true),
						'icon' => 'plugins/objectTypes/'.$ot::ns().'/img/treeicon_root.png',
						'children' => OIDplusTree::tree_populate($ot::root(), $goto_path)
					);
				if (!file_exists($child['icon'])) $child['icon'] = null; // default icon (folder)
				$objTypesChildren[] = $child;
			}

			$json[] = array(
				'id' => "oidplus:system",
				'text' => "Objects",
				'state' => array(
					"opened" => true,
					// "selected" => true)  // "selected" ist buggy: 1) Das select-Event wird beim Laden nicht gefeuert 2) Die direkt untergeordneten Knoten lassen sich nicht öffnen (laden für ewig)
				),
				'icon' => 'img/system.png',
				'children' => $objTypesChildren
			);

			return true;
		}
	}
}

OIDplus::registerPagePlugin(new OIDplusPagePublicObjects());
