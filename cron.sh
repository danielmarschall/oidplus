#!/usr/bin/env php
<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2024 Daniel Marschall, ViaThinkSoft
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

use ViaThinkSoft\OIDplus\Core\OIDplus;

$errors = 0;

try {
	require_once __DIR__ . '/includes/oidplus.inc.php';
} catch (Exception $e) {
	fwrite(STDERR, "Core init: ".$e->getMessage());
	exit(1);
}

try {
	// echo "\n\n******************** Base ...\n";
	ob_start();
	OIDplus::init(false, false);
	OIDplus::invoke_shutdown();
	assert(!OIDplus::isTenant());
	ob_end_clean();
} catch (Exception $e) {
	fwrite(STDERR, "Base system: ".$e->getMessage()."\n");
	$errors++;
}

$tenants = glob(__DIR__.'/userdata/tenant/*');
foreach ($tenants as $tenant) {
	$tenant = basename($tenant);
	try {
		// echo "\n\n******************** Tenant $tenant ...\n";
		OIDplus::forceTenantSubDirName($tenant);
		ob_start();
		OIDplus::init(false, false);
		OIDplus::invoke_shutdown();
		assert(OIDplus::isTenant());
		ob_end_clean();
	} catch (Exception $e) {
		fwrite(STDERR, "Tenant $tenant: ".$e->getMessage()."\n");
		$errors++;
	}
}

exit($errors>0 ? 2 : 0);

