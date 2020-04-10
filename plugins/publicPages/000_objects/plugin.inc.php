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

class OIDplusPagePublicObjects extends OIDplusPagePlugin {
	public function type() {
		return 'public';
	}

	public static function getPluginInformation() {
		$out = array();
		$out['name'] = 'Objects';
		$out['author'] = 'ViaThinkSoft';
		$out['version'] = null;
		$out['descriptionHTML'] = null;
		return $out;
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
			$out['icon'] = OIDplus::webpath(__DIR__).'system_big.png';
			$out['text'] = file_get_contents('welcome.html');

			if (strpos($out['text'], '%%OBJECT_TYPE_LIST%%') !== false) {
				$tmp = '<ul>';
				foreach (OIDplus::getEnabledObjectTypes() as $ot) {
					$tmp .= '<li><a '.oidplus_link($ot::root()).'>'.htmlentities($ot::objectTypeTitle()).'</a></li>';
				}
				$tmp .= '</ul>';
				$out['text'] = str_replace('%%OBJECT_TYPE_LIST%%', $tmp, $out['text']);
			}

			return $out;
		}

		// Objects will be loaded by includes/classes/OIDplusGui.class.php , if $handled=False all page plugins were probed
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if ($nonjs) {
			$json[] = array('id' => 'oidplus:system', 'icon' => OIDplus::webpath(__DIR__).'system.png', 'text' => 'System');

			$parent = '';
			$res = OIDplus::db()->query("select parent from ".OIDPLUS_TABLENAME_PREFIX."objects where id = ?", array($req_goto));
			while ($row = $res->fetch_object()) {
				$parent = $row->parent;
			}

			$objTypesChildren = array();
			foreach (OIDplus::getEnabledObjectTypes() as $ot) {
				$icon = 'plugins/objectTypes/'.$ot::ns().'/img/treeicon_root.png';
				$json[] = array('id' => $ot::root(), 'icon' => $icon, 'text' => $ot::objectTypeTitle());

				try {
					$tmp = OIDplusObject::parse($req_goto);
				} catch (Exception $e) {
					$tmp = null;
				}
				if (!is_null($tmp) && ($ot == get_class($tmp))) {
					// TODO: Instead of just having 3 levels (parent, this and children), it would be better if we'd had a full tree of all parents
					//       on the other hand, for giving search engines content, this is good enough
					if (empty($parent)) {
						$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where " .
										   "parent = ? or " .
										   "id = ? " .
										   "order by ".OIDplus::db()->natOrder('id'), array($req_goto, $req_goto));
					} else {
						$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where " .
										   "parent = ? or " .
										   "id = ? or " .
										   "id = ? ".
										   "order by ".OIDplus::db()->natOrder('id'), array($req_goto, $req_goto, $parent));
					}

					$z_used = 0;
					$y_used = 0;
					$x_used = 0;
					$stufe = 0;
					$menu_entries = array();
					$stufen = array();
					while ($row = $res->fetch_object()) {
						$obj = OIDplusObject::parse($row->id);
						if (is_null($obj)) continue; // might happen if the objectType is not available/loaded
						if (!$obj->userHasReadRights()) continue;
						$txt = $row->title == '' ? '' : ' -- '.htmlentities($row->title);

						if ($row->id == $parent) { $stufe=0; $z_used++; }
						if ($row->id == $req_goto) { $stufe=1; $y_used++; }
						if ($row->parent == $req_goto) { $stufe=2; $x_used++; }

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
					$res = OIDplus::db()->query("select parent from ".OIDPLUS_TABLENAME_PREFIX."objects where id = ?", array($goto));
					if ($res->num_rows() == 0) break;
					$row = $res->fetch_array();
					$goto = $row['parent'];
					if ($goto == '') continue;
				}

				$goto_path = array_reverse($path);
			} else {
				$goto_path = null;
			}

			$objTypesChildren = array();
			foreach (OIDplus::getEnabledObjectTypes() as $ot) {
				$child = array('id' => $ot::root(),
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
				'icon' => OIDplus::webpath(__DIR__).'system.png',
				'children' => $objTypesChildren
			);

			return true;
		}
	}

	public function tree_search($request) {
		$ary = array();
		if ($obj = OIDplusObject::parse($request)) {
			if ($obj->userHasReadRights()) {
				do {
					$ary[] = $obj->nodeId();
				} while ($obj = $obj->getParent());
				$ary = array_reverse($ary);
			}
		}
		return $ary;
	}
}
