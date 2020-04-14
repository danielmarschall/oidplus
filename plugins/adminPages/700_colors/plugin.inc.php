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

class OIDplusPageAdminColors extends OIDplusPagePlugin {
	public static function getPluginInformation() {
		$out = array();
		$out['name'] = 'Colors';
		$out['author'] = 'ViaThinkSoft';
		$out['version'] = null;
		$out['descriptionHTML'] = null;
		return $out;
	}

	public function type() {
		return 'admin';
	}

	public function priority() {
		return 700;
	}

	public function action(&$handled) {
		if (isset($_POST["action"]) && ($_POST["action"] == "color_update")) {
			$handled = true;

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				throw new OIDplusException('You need to log in as administrator.');
			}

			OIDplus::config()->setValue('color_hue_shift', $_POST['hue_shift']);
			OIDplus::config()->setValue('color_sat_shift', $_POST['sat_shift']);
			OIDplus::config()->setValue('color_val_shift', $_POST['val_shift']);

			OIDplus::logger()->log("A?", "Changed system color theme");

			echo json_encode(array("status" => 0));
		}
	}

	public function init($html=true) {
		OIDplus::config()->prepareConfigKey('color_hue_shift', 'HSV Hue shift of CSS colors (-360..360)', '0', 0, 1);
		OIDplus::config()->prepareConfigKey('color_sat_shift', 'HSV Saturation shift of CSS colors (-100..100)', '0', 0, 1);
		OIDplus::config()->prepareConfigKey('color_val_shift', 'HSV Value shift of CSS colors (-100..100)', '0', 0, 1);
	}

	public function cfgSetValue($name, $value) {
		if ($name == 'color_hue_shift') {
			if (!is_numeric($value) || ($value < -360) || ($value > 360)) {
				throw new OIDplusException("Please enter a valid value.");
			}
		}
		if ($name == 'color_sat_shift') {
			if (!is_numeric($value) || ($value < -100) || ($value > 100)) {
				throw new OIDplusException("Please enter a valid value.");
			}
		}
		if ($name == 'color_val_shift') {
			if (!is_numeric($value) || ($value < -100) || ($value > 100)) {
				throw new OIDplusException("Please enter a valid value.");
			}
		}
	}

	public function gui($id, &$out, &$handled) {
		if ($id === 'oidplus:colors') {
			$handled = true;
			$out['title'] = 'Colors';
			$out['icon']  = OIDplus::webpath(__DIR__).'icon_big.png';

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>You need to <a '.OIDplus::gui()->link('oidplus:login').'>log in</a> as administrator.</p>';
			} else {
				$out['text']  = '<p>';
				$out['text'] .= '  <label for="amount">Hue shift:</label>';
				$out['text'] .= '  <input type="text" id="hshift" readonly style="border:0; background:transparent; font-weight:bold;">';
				$out['text'] .= '</p>';
				$out['text'] .= '<div id="slider-hshift"></div>';
				$out['text'] .= '<p>';
				$out['text'] .= '  <label for="amount">Saturation shift:</label>';
				$out['text'] .= '  <input type="text" id="sshift" readonly style="border:0; background:transparent; font-weight:bold;">';
				$out['text'] .= '</p>';
				$out['text'] .= '<div id="slider-sshift"></div>';
				$out['text'] .= '<p>';
				$out['text'] .= '  <label for="amount">Value shift:</label>';
				$out['text'] .= '  <input type="text" id="vshift" readonly style="border:0; background:transparent; font-weight:bold;">';
				$out['text'] .= '</p>';
				$out['text'] .= '<div id="slider-vshift"></div>';
				$out['text'] .= '<script>';
				$out['text'] .= 'if (g_hue_shift == null) g_hue_shift = g_hue_shift_saved = '.OIDplus::config()->getValue('color_hue_shift').";\n";
				$out['text'] .= 'if (g_sat_shift == null) g_sat_shift = g_sat_shift_saved = '.OIDplus::config()->getValue('color_sat_shift').";\n";
				$out['text'] .= 'if (g_val_shift == null) g_val_shift = g_val_shift_saved = '.OIDplus::config()->getValue('color_val_shift').";\n";
				$out['text'] .= 'setup_color_sliders();';
				$out['text'] .= '</script>';
				$out['text'] .= '<br>';
				$out['text'] .= '<input type="button" onclick="color_reset_sliders_cfg()" value="Reset to last saved config">'.str_repeat('&nbsp;',5);
				$out['text'] .= '<input type="button" onclick="color_reset_sliders_factory()" value="Reset default setting">'.str_repeat('&nbsp;',5);
				$out['text'] .= '<input type="button" onclick="test_color_theme()" value="Test">'.str_repeat('&nbsp;',5);
				$out['text'] .= '<input type="button" onclick="crudActionColorUpdate()" value="Set permanently">';
			}
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
                if (file_exists(__DIR__.'/treeicon.png')) {
                        $tree_icon = OIDplus::webpath(__DIR__).'treeicon.png';
                } else {
                        $tree_icon = null; // default icon (folder)
                }

                $json[] = array(
                        'id' => 'oidplus:colors',
                        'icon' => $tree_icon,
                        'text' => 'Colors'
                );

                return true;
	}

	public function tree_search($request) {
		return false;
	}
}
