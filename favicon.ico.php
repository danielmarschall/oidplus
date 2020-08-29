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

require_once __DIR__ . '/includes/oidplus.inc.php';

if (file_exists(__DIR__.'/userdata/favicon.ico')) {
	$out = file_get_contents(__DIR__.'/userdata/favicon.ico');
} else {
	$out = file_get_contents(__DIR__.'/img/favicon.ico');
}

httpOutWithETag($out, 'image/x-icon', 'favicon.ico');
