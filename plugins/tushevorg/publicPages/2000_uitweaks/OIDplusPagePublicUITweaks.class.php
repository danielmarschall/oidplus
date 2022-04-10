<?php

/*
* MIT License
* 
* Copyright (c) 2022 Simon Tushev
* 
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
* 
* The above copyright notice and this permission notice shall be included in all
* copies or substantial portions of the Software.
* 
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
* SOFTWARE.
*/

if (!defined('INSIDE_OIDPLUS')) die();

class OIDplusPagePublicUITweaks extends OIDplusPagePluginPublic {

	public function init($html=true) {
		OIDplus::config()->prepareConfigKey('uitweaks_expand_objects_tree', 'UITweaks plugin: 1=Fully expand objects tree on page reload, 0=Default behavior', '0', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < 0) || ($value > 1)) {
				throw new OIDplusException(_L('Please enter a valid value.'));
			}
		});
		OIDplus::config()->prepareConfigKey('uitweaks_collapse_login_tree', 'UITweaks plugin: 1=Collapse login tree on page reload, 0=Default behavior', '0', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < 0) || ($value > 1)) {
				throw new OIDplusException(_L('Please enter a valid value.'));
			}
		});
		OIDplus::config()->prepareConfigKey('uitweaks_collapse_res_tree', 'UITweaks plugin: 1=Collapse Documents&Resources tree on page reload, 0=Default behavior', '0', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < 0) || ($value > 1)) {
				throw new OIDplusException(_L('Please enter a valid value.'));
			}
		});

		OIDplus::config()->prepareConfigKey('uitweaks_menu_width', 'UITweaks plugin: default width of tree pane (in px)', '450', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < 0)) {
				throw new OIDplusException(_L('Please enter a valid value.'));
			}
		});		
		OIDplus::config()->prepareConfigKey('uitweaks_menu_remember_width', 'UITweaks plugin: 1=Remember menu width (save to browser.localStorage), 0=Default behavior', '1', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < 0) || ($value > 1)) {
				throw new OIDplusException(_L('Please enter a valid value.'));
			}
		});
	}
	
	public function htmlHeaderUpdate(&$head_elems) {
		$w  = js_escape(OIDplus::config()->getValue('uitweaks_menu_width'));
		$rw = OIDplus::config()->getValue('uitweaks_menu_remember_width') == 1 ? 'true' : 'false';
		$o  = OIDplus::config()->getValue('uitweaks_expand_objects_tree') == 1 ? 'true' : 'false';
		$l  = OIDplus::config()->getValue('uitweaks_collapse_login_tree') == 1 ? 'true' : 'false';
		$r  = OIDplus::config()->getValue('uitweaks_collapse_res_tree')   == 1 ? 'true' : 'false';
		
		$s  = "<script>\n";
		$s .= "  oidplus_menu_width = $w;\n";
		$s .= "  let uitweaks = {\n";
		$s .= "    \"menu_remember_width\": $rw,\n";
		$s .= "    \"expand_objects_tree\": $o,\n";
		$s .= "    \"collapse_login_tree\": $l,\n";
		$s .= "    \"collapse_res_tree\":   $r,\n";
		$s .= "  };\n";
		$s .= "</script>";
		
		$head_elems[] = $s;
	}



}
