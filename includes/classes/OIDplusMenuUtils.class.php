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

if (!defined('INSIDE_OIDPLUS')) die();

class OIDplusMenuUtils {

	public static function nonjs_menu() {
		$json = array();

		$static_node_id = isset($_REQUEST['goto']) ? $_REQUEST['goto'] : 'oidplus:system';

		foreach (OIDplus::getPagePlugins() as $plugin) {
			// Note: The system (OIDplusMenuUtils) does only show the menu of
			//       publicPage plugins. Menu entries for RAs and Admins are
			//       handled by the tree() function of the plugin publicPages/090_login
			if (is_subclass_of($plugin, OIDplusPagePluginPublic::class)) {
				$plugin->tree($json, null, true, $static_node_id);
			}
		}

		foreach ($json as $x) {
			if ($static_node_id == $x['id']) echo '<b>';
			if (isset($x['indent'])) echo str_repeat('&nbsp;', $x['indent']*5);
			$cur_lang = OIDplus::getCurrentLang();
			if ($cur_lang != OIDplus::DEFAULT_LANGUAGE) {
				echo '<a href="?lang='.$cur_lang.'&amp;goto='.urlencode($x['id']).'">';
			} else {
				echo '<a href="?goto='.urlencode($x['id']).'">';
			}
			if (!empty($x['icon'])) echo '<img src="'.$x['icon'].'" alt=""> ';
			echo htmlentities($x['id']).' | '.htmlentities($x['text']).'</a><br>';
			if ($static_node_id == $x['id']) echo '</b>';
		}

	}

	// req_id comes from jsTree via AJAX
	// req_goto comes from the user (GET argument)
	public static function json_tree($req_id, $req_goto) {
		$json = array();

		if (!isset($req_id) || ($req_id == '#')) {
			foreach (OIDplus::getPagePlugins() as $plugin) {
				// Note: The system (OIDplusMenuUtils) does only show the menu of
				//       publicPage plugins. Menu entries for RAs and Admins are
				//       handled by the tree() function of the plugin publicPages/090_login
				if (is_subclass_of($plugin, OIDplusPagePluginPublic::class)) {
					$plugin->tree($json, null, false, $req_goto);
				}
			}
		} else {
			$json = self::tree_populate($req_id);
		}

		return $json;
	}

	public static function tree_populate($parent, $goto_path=null) {
		$children = array();

		$parentObj = OIDplusObject::parse($parent);

		@list($namespace, $oid) = explode(':', $parent, 2);
		if ($namespace == 'oid') $oid = substr($oid, 1); // Remove leading dot

		if (is_array($goto_path)) array_shift($goto_path);

		$confidential_oids = array();

		$res = OIDplus::db()->query("select id from ###objects where confidential = ?", array(true));
		while ($row = $res->fetch_array()) {
			$confidential_oids[] = $row['id'];
		}

		$res = OIDplus::db()->query("select * from ###objects where parent = ? order by ".OIDplus::db()->natOrder('id'), array($parent));
		while ($row = $res->fetch_array()) {
			$obj = OIDplusObject::parse($row['id']);

			if (!$obj->userHasReadRights()) continue;

			$child = array();
			$child['id'] = $row['id'];

			// Determine display name (relative OID)
			$child['text'] = $obj->jsTreeNodeName($parentObj);
			$child['text'] .= empty($row['title']) ? /*' -- <i>'.htmlentities('Title missing').'</i>'*/ '' : ' -- <b>' . htmlentities($row['title']) . '</b>';

			$is_confidential = false;
			foreach ($confidential_oids as $test) {
				$is_confidential |= ($row['id'] === $test) || (strpos($row['id'],$test.'.') === 0);
			}
			if ($is_confidential) {
				$child['text'] = '<font color="gray"><i>'.$child['text'].'</i></font>';
			}

			// Determine icon
			$child['icon'] = $obj->getIcon($row);

			// Check if there are more sub OIDs
			if ($goto_path === true) {
				$child['children'] = self::tree_populate($row['id'], $goto_path);
				$child['state'] = array("opened" => true);
			} else if (!is_null($goto_path) && (count($goto_path) > 0) && ($goto_path[0] === $row['id'])) {
				$child['children'] = self::tree_populate($row['id'], $goto_path);
				$child['state'] = array("opened" => true);
			} else {
				$obj_children = $obj->getChildren();

				// Variant 1: Fast, but does not check for hidden OIDs
				//$child_count = count($obj_children);

				// variant 2
				$child_count = 0;
				foreach ($obj_children as $obj_test) {
					if (!$obj_test->userHasReadRights()) continue;
					$child_count++;
				}

				$child['children'] = $child_count > 0;
			}

			$children[] = $child;
		}

		return $children;
	}
}
