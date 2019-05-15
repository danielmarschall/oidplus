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

class OIDplusPageAdminSystemConfig extends OIDplusPagePlugin {
	public function type() {
		return 'admin';
	}

	public function priority() {
		return 110;
	}

	public function action(&$handled) {
		if ($_POST["action"] == "config_update") {
			$handled = true;

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				die('You need to log in as administrator.');
			}

			$name = $_POST['name'];
			$value = $_POST['value'];

			OIDplus::config()->setValue($name, $value);

			$res = OIDplus::db()->query("select protected from ".OIDPLUS_TABLENAME_PREFIX."config where name = '".OIDplus::db()->real_escape_string($name)."';");
			$row = OIDplus::db()->fetch_array($res);
			if ($row['protected'] == 1) {
				die('Setting is write protected');
			}

			echo "OK";
		}
	}

	public function init($html=true) {
		// Nothing
	}

	public function cfgSetValue($name, $value) {
		// Nothing
	}

	public function gui($id, &$out, &$handled) {
		if (explode('$',$id)[0] == 'oidplus:edit_config') {
			$handled = true;
			$out['title'] = 'System configuration';
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/adminPages/'.basename(__DIR__).'/icon_big.png' : '';

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] .= '<p>You need to <a href="?goto=oidplus:login">log in</a> as administrator.</p>';
			} else {
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

				$result = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."config where `visible` = 1 order by name");
				while ($row = OIDplus::db()->fetch_object($result)) {
					$output .= '<tr>';
					$output .= '     <td>'.htmlentities($row->name).'</td>';
					$output .= '     <td>'.htmlentities($row->description).'</td>';
					if ($row->protected == 1) {
						$desc = $row->value;
						if (strlen($desc) > 100) $desc = substr($desc, 0, 100) . '...';
						$output .= '     <td>'.htmlentities($desc).'</td>';
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
				$output .= '<ul><li><a href="setup/">Setup part 1: Create config.php (contains database settings, ReCAPTCHA, admin password and SSL enforcement)</a></li>';
				$output .= '<li><a href="plugins/system/000_registration/registration.php">Setup part 2: Basic settings (they are all available above, too)</a></li></ul>';

				$out['text'] = $output;
			}

			return $out;
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false) {
		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = 'plugins/adminPages/'.basename(__DIR__).'/treeicon.png';
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
}

OIDplus::registerPagePlugin(new OIDplusPageAdminSystemConfig());
