<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2023 Daniel Marschall, ViaThinkSoft
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

namespace ViaThinkSoft\OIDplus\Plugins\AdminPages\SystemConfig;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusHtmlException;
use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginAdmin;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusPageAdminSystemConfig extends OIDplusPagePluginAdmin {

	/**
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 */
	private function action_Update(array $params): array {
		if (!OIDplus::authUtils()->isAdminLoggedIn()) {
			throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')), null, 401);
		}

		_CheckParamExists($params, 'name');
		_CheckParamExists($params, 'value');

		$name = $params['name'];
		$value = $params['value'];

		$res = OIDplus::db()->query("select protected, visible from ###config where name = ?", array($name));
		if (!$res->any()) {
			throw new OIDplusException(_L('Setting does not exist'));
		}
		$row = $res->fetch_array();
		assert(!is_null($row));
		if (($row['protected'] == 1) || ($row['visible'] == 0)) {
			throw new OIDplusException(_L("Setting %1 is read-only",$name));
		}

		$old_value = OIDplus::config()->getValue($name, '');
		OIDplus::config()->setValue($name, $value);
		if ($old_value != $value) {
			OIDplus::logger()->log("V2:[OK/INFO]A", "Changed system config setting '%1' from '%2' to '%3'", $name, $old_value, $value);
		}

		return array("status" => 0);
	}

	/**
	 * @param string $actionID
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 */
	public function action(string $actionID, array $params): array {
		if ($actionID == 'config_update') {
			return $this->action_Update($params);
		} else {
			return parent::action($actionID, $params);
		}
	}

	/**
	 * @param bool $html
	 * @return void
	 */
	public function init(bool $html=true): void {
		// Nothing
	}

	/**
	 * @param string $id
	 * @param array $out
	 * @param bool $handled
	 * @return void
	 * @throws OIDplusException
	 */
	public function gui(string $id, array &$out, bool &$handled): void {
		if (explode('$',$id)[0] == 'oidplus:edit_config') {
			$handled = true;
			$out['title'] = _L('System configuration');
			$out['icon'] = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')), $out['title'], 401);
			}

			$output  = '<div class="container box"><div id="suboid_table" class="table-responsive">';
			$output .= '<table class="table table-bordered table-striped">';
			$output .= '<thead>';
			$output .= '	<tr>';
			$output .= '	     <th>'._L('Setting').'</th>';
			$output .= '	     <th>'._L('Description').'</th>';
			$output .= '	     <th>'._L('Value').'</th>';
			$output .= '	     <th>'._L('Update').'</th>';
			$output .= '	</tr>';
			$output .= '</thead>';
			$output .= '<tbody>';

			OIDplus::config(); // <-- make sure that the config table is loaded/filled correctly before we do a select

			$result = OIDplus::db()->query("select name, description, protected, value from ###config where visible = ? order by name", array(true));
			while ($row = $result->fetch_object()) {
				$output .= '<tr>';
				$output .= '     <td>'.htmlentities($row->name??'').'</td>';
				$output .= '     <td>'.htmlentities($row->description??'').'</td>';
				if ($row->protected == 1) {
					$desc = $row->value;
					if (strlen($desc) > 100) $desc = substr($desc, 0, 100) . '...';
					$output .= '     <td style="word-break: break-all;">'.htmlentities($desc).'</td>';
					$output .= '     <td>&nbsp;</td>';
				} else {
					$output .= '     <td><input type="text" id="config_'.$row->name.'" value="'.htmlentities($row->value??'').'"></td>';
					$output .= '     <td><button type="button" name="config_update_'.$row->name.'" id="config_update_'.$row->name.'" class="btn btn-success btn-xs update" onclick="OIDplusPageAdminSystemConfig.crudActionConfigUpdate('.js_escape($row->name).')">'._L('Update').'</button></td>';
				}
				$output .= '</tr>';
			}

			$output .= '</tbody>';
			$output .= '</table>';
			$output .= '</div></div>';

			$output .= '<br><p>'._L('See also').':</p>';
			$output .= '<ul>';
			$output .= '<li><a href="'.OIDplus::webpath(null,OIDplus::PATH_RELATIVE).'setup/">'._L('Setup part 1: Create %1 (contains database settings, CAPTCHA, admin password and SSL enforcement)',OIDplus::getUserDataDir("baseconfig").'config.inc.php').'</a></li>';
			$oobePlugin = OIDplus::getPluginByOid('1.3.6.1.4.1.37476.2.5.2.4.3.50'); // OIDplusPageAdminOOBE
			if (!is_null($oobePlugin)) {
				$output .= '<li><a href="'.OIDplus::webpath($oobePlugin->getPluginDirectory(),OIDplus::PATH_RELATIVE).'oobe.php">'._L('Setup part 2: Basic settings (they are all available above, too)').'</a></li>';
			} else {
				$output .= '<li>'._L('Setup part 2 requires plugin %1 (the basic settings are all available above, too)','OIDplusPageAdminOOBE').'</a></li>';
			}
			$output .= '</ul>';

			$out['text'] = $output;
		}
	}

	/**
	 * @param array $json
	 * @param string|null $ra_email
	 * @param bool $nonjs
	 * @param string $req_goto
	 * @return bool
	 * @throws OIDplusException
	 */
	public function tree(array &$json, ?string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
		if (!OIDplus::authUtils()->isAdminLoggedIn()) return false;

		if (file_exists(__DIR__.'/img/main_icon16.png')) {
			$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:edit_config',
			'icon' => $tree_icon,
			'text' => _L('System configuration')
		);

		return true;
	}

	/**
	 * @param string $request
	 * @return array|false
	 */
	public function tree_search(string $request) {
		return false;
	}
}
