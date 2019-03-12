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

if (isset($_SERVER['SERVER_NAME']) && (($_SERVER['SERVER_NAME'] == 'viathinksoft.de') ||
                                       ($_SERVER['SERVER_NAME'] == 'www.viathinksoft.de') ||
                                       ($_SERVER['SERVER_NAME'] == 'viathinksoft.com') ||
                                       ($_SERVER['SERVER_NAME'] == 'www.viathinksoft.com')))
{

	if (file_exists(__DIR__.'/treeicon.png')) {
		$tree_icon = 'plugins/publicPages/'.basename(__DIR__).'/treeicon.png';
	} else {
		$tree_icon = null; // default icon (folder)
	}

	$json[] = array(
		'id' => 'oidplus:com.viathinksoft.freeoid',
		'icon' => $tree_icon,
		'text' => 'Register a free OID'
	);

}

