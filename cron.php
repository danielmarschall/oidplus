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

// In order to make the system faster, call this script regularly using crontabs
// Example: The automatic publishing of OIDs will then be done by this script
// and not by a random visitor.

// If you cannot use cron.sh or cron.bat for cronjobs, then you can use
// a WebCron service (e.g. https://www.easycron.com/ ) instead, using cron.php


try {
	require_once __DIR__ . '/includes/oidplus.inc.php';

	ob_start();
	OIDplus::init(false);
	OIDplus::invoke_shutdown();
	ob_end_clean();
} catch (Exception $e) {
	http_response_code(500); // Internal Server Error
	echo $e->getMessage();
}
