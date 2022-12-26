<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2022 Daniel Marschall, ViaThinkSoft
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

namespace ViaThinkSoft\OIDplus;

class OIDplusPageAdminColors extends OIDplusPagePluginAdmin {

	public function htmlHeaderUpdate(&$head_elems) {
		foreach ($head_elems as &$line) {
			if (strpos($line,'oidplus.min.css.php') !== false) {
				$add_css_args = array();
				$add_css_args[] = 'theme='.urlencode(OIDplus::config()->getValue('design','default'));
				$add_css_args[] = 'invert='.urlencode(OIDplus::config()->getValue('color_invert',0));
				$add_css_args[] = 'h_shift='.urlencode(number_format(OIDplus::config()->getValue('color_hue_shift',0)/360,5,'.',''));
				$add_css_args[] = 's_shift='.urlencode(number_format(OIDplus::config()->getValue('color_sat_shift',0)/100,5,'.',''));
				$add_css_args[] = 'v_shift='.urlencode(number_format(OIDplus::config()->getValue('color_val_shift',0)/100,5,'.',''));
				if (count($add_css_args) > 0) {
					$line = str_replace('oidplus.min.css.php?', 'oidplus.min.css.php&', $line);
					$line = str_replace('oidplus.min.css.php', 'oidplus.min.css.php?'.htmlentities(implode('&',$add_css_args)), $line);
				}
			}

			if ((stripos($line,'<meta') !== false) && (stripos($line,'name="theme-color"') !== false)) {
				if (preg_match('@content="(.+)"@ismU', $line, $m)) {
					$theme_color = $m[1];
					$hs = OIDplus::config()->getValue('color_hue_shift',0)/360;
					$ss = OIDplus::config()->getValue('color_sat_shift',0)/100;
					$vs = OIDplus::config()->getValue('color_val_shift',0)/100;
					$theme_color = changeHueOfCSS($theme_color, $hs, $ss, $vs); // "changeHueOfCSS" can also change a single color value if it has the form #xxyyzz or #xyz
					if (OIDplus::config()->getValue('color_invert',0)) {
						$theme_color = invertColorsOfCSS($theme_color);
					}
					$line = preg_replace('@content="(.+)"@ismU', 'content="'.$theme_color.'"', $line);

				}
			}
		}
	}

	public function action($actionID, $params) {
		if ($actionID == 'color_update') {
			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusException(_L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')));
			}

			_CheckParamExists($params, 'hue_shift');
			_CheckParamExists($params, 'sat_shift');
			_CheckParamExists($params, 'val_shift');
			_CheckParamExists($params, 'invcolors');
			_CheckParamExists($params, 'theme');

			OIDplus::config()->setValue('color_hue_shift', $params['hue_shift']);
			OIDplus::config()->setValue('color_sat_shift', $params['sat_shift']);
			OIDplus::config()->setValue('color_val_shift', $params['val_shift']);
			OIDplus::config()->setValue('color_invert',    $params['invcolors']);
			OIDplus::config()->setValue('design',          $params['theme']);

			OIDplus::logger()->log("[OK]A?", "Changed system color theme");

			return array("status" => 0);
		} else {
			throw new OIDplusException(_L('Unknown action ID'));
		}
	}

	public function init($html=true) {
		OIDplus::config()->prepareConfigKey('color_hue_shift', 'HSV Hue shift of CSS colors (-360..360)', '0', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < -360) || ($value > 360)) {
				throw new OIDplusException(_L('Please enter a valid value.'));
			}
		});
		OIDplus::config()->prepareConfigKey('color_sat_shift', 'HSV Saturation shift of CSS colors (-100..100)', '0', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < -100) || ($value > 100)) {
				throw new OIDplusException(_L('Please enter a valid value.'));
			}
		});
		OIDplus::config()->prepareConfigKey('color_val_shift', 'HSV Value shift of CSS colors (-100..100)', '0', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < -100) || ($value > 100)) {
				throw new OIDplusException(_L('Please enter a valid value.'));
			}
		});
		OIDplus::config()->prepareConfigKey('color_invert', 'Invert colors? (0=no, 1=yes)', '0', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < 0) || ($value > 1)) {
				throw new OIDplusException(_L('Please enter a valid value (0=no, 1=yes).'));
			}
		});
		OIDplus::config()->prepareConfigKey('design', 'Which design to use (must exist in plugins/[vendorname]/design/)?', 'default', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			$good = true;
			if (strpos($value,'/') !== false) $good = false;
			if (strpos($value,'\\') !== false) $good = false;
			if (strpos($value,'..') !== false) $good = false;
			if (!$good) {
				throw new OIDplusException(_L('Invalid design folder name. Do only enter a folder name, not an absolute or relative path'));
			}

			if (!wildcard_is_dir(OIDplus::localpath().'plugins/'.'*'.'/design/'.$value)) {
				throw new OIDplusException(_L('The design "%1" does not exist in plugin directory %2',$value,'plugins/[vendorname]/design/'));
			}
		});
		OIDplus::config()->prepareConfigKey('oobe_colors_done', '"Out Of Box Experience" wizard for OIDplusPageAdminColors done once?', '0', OIDplusConfig::PROTECTION_HIDDEN, function($value) {});
	}

	public function gui($id, &$out, &$handled) {
		if ($id === 'oidplus:colors') {
			$handled = true;
			$out['title'] = _L('Design');
			$out['icon']  = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png';

			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				$out['icon'] = 'img/error.png';
				$out['text'] = '<p>'._L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')).'</p>';
				return;
			}

			$out['text']  = '<br><p>';
			$out['text'] .= '  <label for="theme">'._L('Design').':</label>';
			$out['text'] .= '  <select name="theme" id="theme">';
			foreach (OIDplus::getDesignPlugins() as $plugin) {
				$folder = basename($plugin->getPluginDirectory());
				$selected = $folder == OIDplus::config()->getValue('design') ? ' selected="true"' : '';
				$out['text'] .= '<option value="'.htmlentities($folder).'"'.$selected.'>'.htmlentities($plugin->getManifest()->getName()).'</option>';
			}
			$out['text'] .= '  </select>';
			$out['text'] .= '</p>';

			$out['text'] .= '<br><p>';
			$out['text'] .= '  <label for="amount">'._L('Hue shift').':</label>';
			$out['text'] .= '  <input type="text" id="hshift" readonly style="border:0; background:transparent; font-weight:bold;">';
			$out['text'] .= '</p>';
			$out['text'] .= '<div id="slider-hshift"></div>';

			$out['text'] .= '<br><p>';
			$out['text'] .= '  <label for="amount">'._L('Saturation shift').':</label>';
			$out['text'] .= '  <input type="text" id="sshift" readonly style="border:0; background:transparent; font-weight:bold;">';
			$out['text'] .= '</p>';
			$out['text'] .= '<div id="slider-sshift"></div>';

			$out['text'] .= '<br><p>';
			$out['text'] .= '  <label for="amount">'._L('Value shift').':</label>';
			$out['text'] .= '  <input type="text" id="vshift" readonly style="border:0; background:transparent; font-weight:bold;">';
			$out['text'] .= '</p>';
			$out['text'] .= '<div id="slider-vshift"></div>';

			$out['text'] .= '<p><div><input type="checkbox" id="icolor"> <label for="icolor">'._L('Invert colors').'</label></div></p>';

			$out['text'] .= '<script>';
			$out['text'] .= 'if (OIDplusPageAdminColors.hue_shift == null) OIDplusPageAdminColors.hue_shift = OIDplusPageAdminColors.hue_shift_saved = '.OIDplus::config()->getValue('color_hue_shift').";\n";
			$out['text'] .= 'if (OIDplusPageAdminColors.sat_shift == null) OIDplusPageAdminColors.sat_shift = OIDplusPageAdminColors.sat_shift_saved = '.OIDplus::config()->getValue('color_sat_shift').";\n";
			$out['text'] .= 'if (OIDplusPageAdminColors.val_shift == null) OIDplusPageAdminColors.val_shift = OIDplusPageAdminColors.val_shift_saved = '.OIDplus::config()->getValue('color_val_shift').";\n";
			$out['text'] .= 'if (OIDplusPageAdminColors.invcolors == null) OIDplusPageAdminColors.invcolors = OIDplusPageAdminColors.invcolors_saved = '.OIDplus::config()->getValue('color_invert').";\n";
			$out['text'] .= 'if (OIDplusPageAdminColors.activetheme == null) OIDplusPageAdminColors.activetheme_saved = '.js_escape(OIDplus::config()->getValue('design')).";\n";
			$out['text'] .= 'OIDplusPageAdminColors.setup_color_sliders();';
			$out['text'] .= '</script>';

			$out['text'] .= '<br>';
			$out['text'] .= '<input type="button" onclick="OIDplusPageAdminColors.color_reset_sliders_cfg()" value="'._L('Reset to last saved config').'">'.str_repeat('&nbsp;',5);
			$out['text'] .= '<input type="button" onclick="OIDplusPageAdminColors.color_reset_sliders_factory()" value="'._L('Reset default setting').'">'.str_repeat('&nbsp;',5);
			$out['text'] .= '<input type="button" onclick="OIDplusPageAdminColors.test_color_theme()" value="'._L('Test').'">'.str_repeat('&nbsp;',5);
			$out['text'] .= '<input type="button" onclick="OIDplusPageAdminColors.crudActionColorUpdate()" value="'._L('Set permanently').'">';
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (!OIDplus::authUtils()->isAdminLoggedIn()) return false;

                if (file_exists(__DIR__.'/img/main_icon16.png')) {
                        $tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png';
                } else {
                        $tree_icon = null; // default icon (folder)
                }

                $json[] = array(
                        'id' => 'oidplus:colors',
                        'icon' => $tree_icon,
                        'text' => _L('Design')
                );

                return true;
	}

	public function tree_search($request) {
		return false;
	}

	public function implementsFeature($id) {
		if (strtolower($id) == '1.3.6.1.4.1.37476.2.5.2.3.1') return true; // oobeEntry, oobeRequested
		return false;
	}

	public function oobeRequested(): bool {
		// Interface 1.3.6.1.4.1.37476.2.5.2.3.1

		return OIDplus::config()->getValue('oobe_colors_done') == '0';
	}

	public function oobeEntry($step, $do_edits, &$errors_happened)/*: void*/ {
		// Interface 1.3.6.1.4.1.37476.2.5.2.3.1

		echo '<h2>'._L('Step %1: Color Theme',$step).'</h2>';

		echo '<input type="checkbox" name="color_invert" id="color_invert"';
		if (isset($_POST['sent'])) {
		        if ($set_value = isset($_POST['color_invert'])) {
				echo ' checked';
			}
		} else {
			if ($set_value = (OIDplus::config()->getValue('color_invert') == 1)) {
				echo ' checked';
			}
		}
		echo '> <label for="color_invert">'._L('Dark Theme (inverted colors)').'</label><br>';

		$msg = '';
		if ($do_edits) {
			try {
				OIDplus::config()->setValue('color_invert', $set_value ? 1 : 0);
				OIDplus::config()->setValue('oobe_colors_done', '1');
			} catch (\Exception $e) {
				$msg = $e->getMessage();
				$errors_happened = true;
			}
		}

		echo ' <font color="red"><b>'.$msg.'</b></font>';
	}

}
