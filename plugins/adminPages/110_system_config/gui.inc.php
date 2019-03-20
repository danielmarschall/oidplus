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

if (explode('$',$id)[0] == 'oidplus:edit_config') {
	$handled = true;
	$out['title'] = 'System configuration';
	$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/adminPages/'.basename(__DIR__).'/icon_big.png' : '';

	if (!OIDplus::authUtils()::isAdminLoggedIn()) {
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

		$result = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."config order by name");
		while ($row = OIDplus::db()->fetch_object($result)) {
			$output .= '<tr>';
			$output .= '     <td>'.htmlentities($row->name).'</td>';
			$output .= '     <td>'.htmlentities($row->description).'</td>';
			$output .= '     <td><input type="text" id="config_'.$row->name.'" value="'.htmlentities($row->value).'"></td>';
			$output .= '     <td><button type="button" name="config_update_'.$row->name.'" id="config_update_'.$row->name.'" class="btn btn-success btn-xs update" onclick="javascript:crudActionConfigUpdate('.js_escape($row->name).')">Update</button></td>';
			$output .= '</tr>';
		}

		$output .= '</table>';
		$output .= '</div></div>';

		$out['text'] = $output;
	}

	return $out;
}
