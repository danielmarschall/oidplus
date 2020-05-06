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

class OIDplusPageAdminColors extends OIDplusPagePluginAdmin {

	public function action(&$handled) {
		if (isset($_POST["action"]) && ($_POST["action"] == "color_update")) {
			$handled = true;

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				throw new OIDplusException('You need to log in as administrator.');
			}

			OIDplus::config()->setValue('color_hue_shift', $_POST['hue_shift']);
			OIDplus::config()->setValue('color_sat_shift', $_POST['sat_shift']);
			OIDplus::config()->setValue('color_val_shift', $_POST['val_shift']);
			OIDplus::config()->setValue('color_invert',    $_POST['invcolors']);

			OIDplus::logger()->log("[OK]A?", "Changed system color theme");

			echo json_encode(array("status" => 0));
		}
	}

	public function init($html=true) {
		OIDplus::config()->prepareConfigKey('color_hue_shift', 'HSV Hue shift of CSS colors (-360..360)', '0', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < -360) || ($value > 360)) {
				throw new OIDplusException("Please enter a valid value.");
			}
		});
		OIDplus::config()->prepareConfigKey('color_sat_shift', 'HSV Saturation shift of CSS colors (-100..100)', '0', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < -100) || ($value > 100)) {
				throw new OIDplusException("Please enter a valid value.");
			}
		});
		OIDplus::config()->prepareConfigKey('color_val_shift', 'HSV Value shift of CSS colors (-100..100)', '0', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < -100) || ($value > 100)) {
				throw new OIDplusException("Please enter a valid value.");
			}
		});
		OIDplus::config()->prepareConfigKey('color_invert', 'Invert colors? (0=no, 1=yes)', '0', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < 0) || ($value > 1)) {
				throw new OIDplusException("Please enter a valid value (0 or 1).");
			}
		});
	}

	public function gui($id, &$out, &$handled) {
		if ($id === 'oidplus:colors') {
			$handled = true;
			$out['title'] = 'Colors';
			$out['icon']  = OIDplus::webpath(__DIR__).'icon_big.png';

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>You need to <a '.OIDplus::gui()->link('oidplus:login').'>log in</a> as administrator.</p>';
				return;
			}

			$out['text']  = '<br><p>';
			$out['text'] .= '  <label for="amount">Hue shift:</label>';
			$out['text'] .= '  <input type="text" id="hshift" readonly style="border:0; background:transparent; font-weight:bold;">';
			$out['text'] .= '</p>';
			$out['text'] .= '<div id="slider-hshift"></div>';

			$out['text'] .= '<br><p>';
			$out['text'] .= '  <label for="amount">Saturation shift:</label>';
			$out['text'] .= '  <input type="text" id="sshift" readonly style="border:0; background:transparent; font-weight:bold;">';
			$out['text'] .= '</p>';
			$out['text'] .= '<div id="slider-sshift"></div>';

			$out['text'] .= '<br><p>';
			$out['text'] .= '  <label for="amount">Value shift:</label>';
			$out['text'] .= '  <input type="text" id="vshift" readonly style="border:0; background:transparent; font-weight:bold;">';
			$out['text'] .= '</p>';
			$out['text'] .= '<div id="slider-vshift"></div>';

			$out['text'] .= '<br><p>';
			$out['text'] .= '  <label for="amount">Invert colors:</label>';
			$out['text'] .= '  <input type="text" id="icolor" readonly style="border:0; background:transparent; font-weight:bold;">';
			$out['text'] .= '</p>';
			$out['text'] .= '<div id="slider-icolor"></div>';

			$out['text'] .= '<script>';
			$out['text'] .= 'if (g_hue_shift == null) g_hue_shift = g_hue_shift_saved = '.OIDplus::config()->getValue('color_hue_shift').";\n";
			$out['text'] .= 'if (g_sat_shift == null) g_sat_shift = g_sat_shift_saved = '.OIDplus::config()->getValue('color_sat_shift').";\n";
			$out['text'] .= 'if (g_val_shift == null) g_val_shift = g_val_shift_saved = '.OIDplus::config()->getValue('color_val_shift').";\n";
			$out['text'] .= 'if (g_invcolors == null) g_invcolors = g_invcolors_saved = '.OIDplus::config()->getValue('color_invert').";\n";
			$out['text'] .= 'setup_color_sliders();';
			$out['text'] .= '</script>';

			$out['text'] .= '<br>';
			$out['text'] .= '<input type="button" onclick="color_reset_sliders_cfg()" value="Reset to last saved config">'.str_repeat('&nbsp;',5);
			$out['text'] .= '<input type="button" onclick="color_reset_sliders_factory()" value="Reset default setting">'.str_repeat('&nbsp;',5);
			$out['text'] .= '<input type="button" onclick="test_color_theme()" value="Test">'.str_repeat('&nbsp;',5);
			$out['text'] .= '<input type="button" onclick="crudActionColorUpdate()" value="Set permanently">';
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
                        'id' => 'oidplus:colors',
                        'icon' => $tree_icon,
                        'text' => 'Colors'
                );

                return true;
	}

	public function tree_search($request) {
		return false;
	}

	public function implementsFeature($id) {
		if (strtolower($id) == '1.3.6.1.4.1.37476.2.5.2.3.1') return true; // oobeEntry
		return false;
	}

	public function oobeEntry($step, $do_edits, &$errors_happened)/*: void*/ {
		// Interface 1.3.6.1.4.1.37476.2.5.2.3.1

		echo "<p><u>Step $step: Color Theme</u></p>";

		echo '<input type="checkbox" name="color_invert" id="color_invert"';
		if (isset($_REQUEST['sent'])) {
		        if ($set_value = isset($_REQUEST['color_invert'])) {
				echo ' checked';
			}
		} else {
			if (OIDplus::config()->getValue('color_invert') == 1) {
				echo ' checked';
			}
		}
		echo '> <label for="color_invert">Dark Theme (inverted colors)</label><br>';

		$msg = '';
		if ($do_edits) {
			try {
				OIDplus::config()->setValue('color_invert', $set_value ? 1 : 0);
			} catch (Exception $e) {
				$msg = $e->getMessage();
				$errors_happened = true;
			}
		}

		echo ' <font color="red"><b>'.$msg.'</b></font>';
	}

}
