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

function isMobile() {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) return false;

        // https://deviceatlas.com/blog/list-of-user-agent-strings
        return
                (stripos($_SERVER['HTTP_USER_AGENT'], 'mobile') !== false) ||
                (stripos($_SERVER['HTTP_USER_AGENT'], 'iphone') !== false) ||
                (stripos($_SERVER['HTTP_USER_AGENT'], 'android') !== false) ||
                (stripos($_SERVER['HTTP_USER_AGENT'], 'windows phone') !== false);
}

if (isMobile()) {
	require_once 'index_mobile.php';
} else {
	require_once 'index_desktop.php';
}

