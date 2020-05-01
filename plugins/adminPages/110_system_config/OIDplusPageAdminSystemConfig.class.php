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

class OIDplusPageAdminSystemConfig extends OIDplusPagePluginAdmin {

	public function action(&$handled) {
		if (isset($_POST["action"]) && ($_POST["action"] == "config_update")) {
			$handled = true;

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				throw new OIDplusException('You need to log in as administrator.');
			}

			$name = $_POST['name'];
			$value = $_POST['value'];

			$res = OIDplus::db()->query("select protected, visible from ###config where name = ?", array($name));
			if ($res->num_rows() == 0) {
				throw new OIDplusException('Setting does not exist');
			}
			$row = $res->fetch_array();
			if (($row['protected'] == 1) || ($row['visible'] == 0)) {
				throw new OIDplusException("Setting '$name' is read-only");
			}

			OIDplus::config()->setValue($name, $value);
			OIDplus::logger()->log("[OK]A?", "Changed system config setting '$name' to '$value'");

			echo json_encode(array("status" => 0));
		}
	}

	public function init($html=true) {
		// Nothing
	}

	public function gui($id, &$out, &$handled) {
		if (explode('$',$id)[0] == 'oidplus:edit_config') {
			$handled = true;
			$out['title'] = 'System configuration';
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>You need to <a '.OIDplus::gui()->link('oidplus:login').'>log in</a> as administrator.</p>';
				return;
			}
			
			$output = '';
			$output .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
			$output .= '<table class="table table-bordered table-striped">';
			$output .= '	<tr>';
			$output .= '	     <th>Setting</th>';
			$output .= '	     <th>Description</th>';
			$output .= '	     <th>Value</th>';
			$output .= '	     <th>Update</th>';
			$output .= '	</tr>';

			OIDplus::config(); // <-- make sure that the config table is loaded/filled correctly before we do a select

			$result = OIDplus::db()->query("select * from ###config where visible = ? order by name", array(true));
			while ($row = $result->fetch_object()) {
				$output .= '<tr>';
				$output .= '     <td>'.htmlentities($row->name).'</td>';
				$output .= '     <td>'.htmlentities($row->description).'</td>';
				if ($row->protected == 1) {
					$desc = $row->value;
					if (strlen($desc) > 100) $desc = substr($desc, 0, 100) . '...';
					$output .= '     <td style="word-break: break-all;">'.htmlentities($desc).'</td>';
					$output .= '     <td>&nbsp;</td>';
				} else {
					$output .= '     <td><input type="text" id="config_'.$row->name.'" value="'.htmlentities($row->value).'"></td>';
					$output .= '     <td><button type="button" name="config_update_'.$row->name.'" id="config_update_'.$row->name.'" class="btn btn-success btn-xs update" onclick="crudActionConfigUpdate('.js_escape($row->name).')">Update</button></td>';
				}
				$output .= '</tr>';
			}

			$output .= '</table>';
			$output .= '</div></div>';

			$output .= '<br><p>See also:</p>';
			$output .= '<ul>';
			$output .= '<li><a href="'.OIDplus::getSystemUrl().'setup/">Setup part 1: Create config.php (contains database settings, ReCAPTCHA, admin password and SSL enforcement)</a></li>';
			if (class_exists('OIDplusPageAdminRegistration')) {
				$reflector = new \ReflectionClass('OIDplusPageAdminRegistration');
				$path = dirname($reflector->getFilename());
				$output .= '<li><a href="'.OIDplus::webpath($path).'oobe.php">Setup part 2: Basic settings (they are all available above, too)</a></li>';
			} else {
				$output .= '<li>Setup part 2 requires plugin OIDplusPageAdminRegistration (the basic settings are all available above, too)</a></li>';
			}
			$output .= '</ul>';

			$out['text'] = $output;
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (!OIDplus::authUtils()::isAdminLoggedIn()) return false;
		
		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = OIDplus::webpath(__DIR__).'treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:edit_config',
			'icon' => $tree_icon,
			'text' => 'System config'
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}
}
