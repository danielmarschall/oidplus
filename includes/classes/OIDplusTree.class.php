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

class OIDplusTree {

	public static function nonjs_menu($static_node_id) {
		if ($static_node_id == 'oidplus:system') echo '<b>';
		echo '<a href="?goto=oidplus:system"><img src="img/system.png" alt="System icon"> System</a><br>';
		if ($static_node_id == 'oidplus:system') echo '</b>';

		$parent = '';
		$res = OIDplus::db()->query("select parent from ".OIDPLUS_TABLENAME_PREFIX."objects where id = '".OIDplus::db()->real_escape_string($static_node_id)."'");
		while ($row = OIDplus::db()->fetch_object($res)) {
			$parent = $row->parent;
		}

		$objTypesChildren = array();
		foreach (OIDplus::getRegisteredObjectTypes() as $ot) {
			$icon = 'plugins/objectTypes/'.$ot::ns().'/img/treeicon_root.png';
			if (file_exists($icon)) {
				$icon = '<img src="'.$icon.'" alt="'.$ot::ns().' icon"> ';
			}

			if ($ot::ns().':' == $static_node_id) echo '<b>';
			echo '<a href="?goto='.urlencode($ot::root()).'">'.$icon.htmlentities($ot::objectTypeTitle()).'</a><br>';
			if ($ot::ns().':' == $static_node_id) echo '</b>';

			$tmp = OIDplusObject::parse($static_node_id);
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

					$menu_entry = '<a href="?goto='.htmlentities($row->id).'">'.htmlentities($row->id).'</a>'.$txt;
					if ($row->id === $static_node_id) $menu_entry = '<b>'.$menu_entry.'</b>';
					$menu_entries[] = $menu_entry;
					$stufen[] = $stufe;
				}
				if ($x_used) foreach ($menu_entries as $i => &$menu_entry) if ($stufen[$i] >= 2) $menu_entry = str_repeat('&nbsp;', 5) . $menu_entry;
				if ($y_used) foreach ($menu_entries as $i => &$menu_entry) if ($stufen[$i] >= 1) $menu_entry = str_repeat('&nbsp;', 5) . $menu_entry;
				if ($z_used) foreach ($menu_entries as $i => &$menu_entry) if ($stufen[$i] >= 0) $menu_entry = str_repeat('&nbsp;', 5) . $menu_entry;
				echo implode("<br>\n", $menu_entries)."<br>\n";
			}
		}

		// === Plugins ===

		$json = array();

		foreach (OIDplus::getPagePlugins('public') as $plugin) {
			$plugin->tree($json);
		}

		foreach ($json as $x) {
			if ($static_node_id == $x['id']) echo '<b>';
			echo '<a href="?goto='.urlencode($x['id']).'"><img src="'.$x['icon'].'" alt="'.$x['id'].' icon"> '.htmlentities($x['text']).'</a><br>';
			if ($static_node_id == $x['id']) echo '</b>';
		}

	}

	public static function json_tree($req_id, $req_goto) {

		$lang = array();
		$lang['title_missing'] = 'Title missing';

		if (!isset($req_id) || ($req_id == '#')) {
			$json = array();

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
						'children' => self::tree_populate($ot::root(), $goto_path)
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

			$ra_emails = OIDplus::authUtils()::loggedInRaList();

			$loginChildren = array();

			if (OIDplus::authUtils()::isAdminLoggedIn()) {
				$ra_roots = array();

				foreach (OIDplus::getPagePlugins('admin') as $plugin) {
					$plugin->tree($ra_roots);
				}

				$ra_roots[] = array(
					'id'       => 'oidplus:logout$admin',
					'icon'     => 'img/logout.png',
					'conditionalselect' => 'adminLogout()', // defined in oidplus.js
					'text'     => 'Log out'
				);
				$loginChildren[] = array(
					'id'       => 'oidplus:dummy$'.md5(rand()),
					'text'     => "Logged in as admin",
					'icon'     => 'img/admin.png',
					'conditionalselect' => 'false', // dummy node that can't be selected
					'state'    => array("opened" => true),
					'children' => $ra_roots
				);
			}
			foreach ($ra_emails as $ra_email) {
				$ra_roots = array();

				foreach (OIDplus::getPagePlugins('ra') as $plugin) {
					$plugin->tree($ra_roots, $ra_email);
				}

				$ra_roots[] = array(
					'id'       => 'oidplus:logout$'.$ra_email,
					'conditionalselect' => 'raLogout("'.js_escape($ra_email).'")', // defined in oidplus.js
					'icon'     => 'img/logout.png',
					'text'     => 'Log out'
				);
				foreach (OIDplusObject::getRaRoots($ra_email) as $loc_root) {
					$ico = $loc_root->getIcon();
					$ra_roots[] = array(
						'id' => 'oidplus:raroot$'.$loc_root->nodeId(),
						'text' => 'Jump to RA root '.$loc_root->objectTypeTitleShort().' '.$loc_root->crudShowId(OIDplusObject::parse($loc_root::root())),
						'conditionalselect' => '$("#content_window").html(""); document.location = "?goto="+encodeURI('.js_escape($loc_root->nodeId()).');',
						'icon' => !is_null($ico) ? $ico : 'img/link.png'
					);
				}
				$ra_email_or_name = (new OIDplusRA($ra_email))->raName();
				if ($ra_email_or_name == '') $ra_email_or_name = $ra_email;
				$loginChildren[] = array(
					'id'       => 'oidplus:dummy$'.md5(rand()),
					'text'     => "Logged in as ".htmlentities($ra_email_or_name),
					'icon'     => 'img/ra.png',
					'conditionalselect' => 'false', // dummy node that can't be selected
					'state'    => array("opened" => true),
					'children' => $ra_roots
				);
			}

			$json[] = array(
				'id'       => 'oidplus:login',
				'icon'     => 'img/login.png',
				'text'     => 'Login',
				'state'    => array("opened" => count($loginChildren)>0),
				'children' => $loginChildren
			);

			foreach (OIDplus::getPagePlugins('public') as $plugin) {
				$plugin->tree($json);
			}
		} else {
			$json = self::tree_populate($req_id);
		}

		return json_encode($json);
	}

	private static function tree_populate($parent, $goto_path=null) {
		global $lang;
		$children = array();

		$parentObj = OIDplusObject::parse($parent);

		@list($namespace, $oid) = explode(':', $parent, 2);
		if ($namespace == 'oid') $oid = substr($oid, 1); // führenden Punkt entfernen

		if (!is_null($goto_path)) array_shift($goto_path);

		$confidential_oids = array();

		$res = OIDplus::db()->query("select id from ".OIDPLUS_TABLENAME_PREFIX."objects where confidential = '1'");
		while ($row = OIDplus::db()->fetch_array($res)) {
			$confidential_oids[] = $row['id'];
		}

		$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where parent = '".OIDplus::db()->real_escape_string($parent)."' order by ".OIDplus::db()->natOrder('id'));
		while ($row = OIDplus::db()->fetch_array($res)) {
			$obj = OIDplusObject::parse($row['id']);

			if (!$obj->userHasReadRights()) continue;

			$child = array();
			$child['id'] = $row['id'];

			// Anzeigenamen (relative OID) bestimmen
			$child['text'] = $obj->jsTreeNodeName($parentObj);
			$child['text'] .= empty($row['title']) ? /*' -- <i>'.htmlentities($lang['title_missing']).'</i>'*/ '' : ' -- <b>' . htmlentities($row['title']) . '</b>';

			$is_confidential = false;
			foreach ($confidential_oids as $test) {
				$is_confidential |= ($row['id'] === $test) || (strpos($row['id'],$test.'.') === 0);
			}
			if ($is_confidential) {
				$child['text'] = '<font color="gray"><i>'.$child['text'].'</i></font>';
			}

			// Icon bestimmen
			$child['icon'] = $obj->getIcon($row);

			// Feststellen, ob es weitere Unter-OIDs gibt
			if (!is_null($goto_path) && (count($goto_path) > 0) && ($goto_path[0] === $row['id'])) {
				$child['children'] = self::tree_populate($row['id'], $goto_path);
				$child['state'] = array("opened" => true);
			} else {
				$res2 = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where parent = '".OIDplus::db()->real_escape_string($row['id'])."'");
				$child['children'] = OIDplus::db()->num_rows($res2) > 0;
			}

			$children[] = $child;
		}

		return $children;
	}
}

