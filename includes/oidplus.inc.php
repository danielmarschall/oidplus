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

define('IN_OIDPLUS', true);

if (php_sapi_name() != 'cli') {
	header('X-Content-Type-Options: nosniff');
	header('X-XSS-Protection: 1; mode=block');
	header("Content-Security-Policy: default-src 'self' blob: https://fonts.gstatic.com https://www.google.com/ https://www.gstatic.com/ https://cdnjs.cloudflare.com/; ".
	       "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com/; ".
	       "img-src data: http: https:; ".
	       "script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: https://www.google.com/ https://www.gstatic.com/ https://cdnjs.cloudflare.com/ https://polyfill.io/; ".
	       "frame-ancestors 'none'; ".
	       "object-src 'none'");
	header('X-Frame-Options: SAMEORIGIN');
	header('Referrer-Policy: no-referrer-when-downgrade');
}

require_once __DIR__ . '/../3p/0xbb/Sha3.class.php';
require_once __DIR__ . '/SecureMailer.class.php';

require_once __DIR__ . '/functions.inc.php';
require_once __DIR__ . '/oid_utils.inc.php';
require_once __DIR__ . '/uuid_utils.inc.php';
require_once __DIR__ . '/ipv4_functions.inc.php';
require_once __DIR__ . '/ipv6_functions.inc.php';
require_once __DIR__ . '/anti_xss.inc.php';

// ---

spl_autoload_register(function ($class_name) {
	$candidate = __DIR__ . '/classes/' . $class_name . '.class.php';
	if (file_exists($candidate)) require_once $candidate;
});
