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

if (file_exists(__DIR__.'/treeicon.png')) {
	$tree_icon = 'plugins/raPages/'.basename(__DIR__).'/treeicon.png';
} else {
	$tree_icon = null; // default icon (folder)
}

$ra_roots[] = array(
	'id' => 'oidplus:change_ra_email$'.$ra_email,
	'icon' => $tree_icon,
	'text' => 'Change email address'
);
