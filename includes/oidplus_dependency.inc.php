<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2021 Daniel Marschall, ViaThinkSoft
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

function oidplus_get_missing_dependencies() {
	$missing_dependencies = array();

	if (!function_exists('gmp_init')) {
		// GMP Required for includes/uuid_functions.inc.php
		//                  includes/ipv6_functions.inc.php
		//                  plugins/viathinksoft/adminPages/400_oidinfo_export/oidinfo_api.inc.php (if GMP is not available, BC will be used)
		// Note that gmp_supplement.inc.php will implement the GMP functions if BCMath is present.
		// This is the reason why we use function_exists('gmp_init') instead of extension_loaded('gmp')
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$install_hint1 = _L('On Windows, install it by enabling the line %1 in your PHP.ini',
				'extension=php_gmp.dll');
			$install_hint2 = _L('On Windows, it should be installed by default');
		} else {
			$install_hint1 = _L('On Linux, install it by running e.g. %1, and then restart your webserver service, e.g. by running %2',
				'<code>sudo apt-get update && sudo apt-get install php-gmp</code>',
				'<code>sudo service apache2 restart</code>');
			$install_hint2 = _L('On Linux, install it by running e.g. %1, and then restart your webserver service, e.g. by running %2',
				'<code>sudo apt-get update && sudo apt-get install php-bcmath</code>',
				'<code>sudo service apache2 restart</code>');
		}
		$missing_dependencies[] = 'GMP ('.$install_hint1.')'.
		                          '<br>'._L('or alternatively').'<br>' .
		                          'BCMath ('.$install_hint2.')';
	}

	if (!function_exists('mb_substr')) {
		// Required for includes/classes/OIDplusSessionHandler.class.php
		//              includes/oid_utils.inc.php
		//              vendor/matthiasmullie/path-converter/src/Converter.php
		//              vendor/n-other/php-sha3/src/Sha3.php
		// Note that mbstring_supplement.inc.php will implement the MBString functions if iconv is present.
		// This is the reason why we use function_exists('mb_substr') instead of extension_loaded('mbstring')
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$install_hint1 = _L('On Windows, install it by enabling the line %1 in your PHP.ini',
				'extension=php_mbstring.dll');
			$install_hint2 = _L('On Windows, it should be installed by default');
		} else {
			$install_hint1 = _L('On Linux, install it by running e.g. %1, and then restart your webserver service, e.g. by running %2',
				'<code>sudo apt-get update && sudo apt-get install php-mbstring</code>',
				'<code>sudo service apache2 restart</code>');
			$install_hint2 = _L('On Linux, it should be installed by default');
		}
		$missing_dependencies[] = 'MBString ('.$install_hint1.')'.
		                          '<br>'._L('or alternatively').'<br>' .
		                          'iconv ('.$install_hint2.')';
	}

	if (!function_exists('simplexml_load_file')) {
		// Required for includes/classes/OIDplusPluginManifest.class.php (Plugins)
		//              includes/classes/OIDplus.class.php (Translation)
		//              plugins/viathinksoft/adminPages/400_oidinfo_export/OIDplusPageAdminOIDInfoExport.class.php (Import OID from oid-info.com)
		//              dev/translation/*.phps (only for developers)
		// Note: This should not happen because of simplexml_supplement.inc.php
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$install_hint = _L('On Windows, it should be installed by default');
		} else {
			$install_hint = _L('On Linux, install it by running e.g. %1, and then restart your webserver service, e.g. by running %2',
				'<code>sudo apt-get update && sudo apt-get install php-xml</code>',
				'<code>sudo service apache2 restart</code>');
		}
		$missing_dependencies[] = 'SimpleXML ('.$install_hint.')';
	}

	return $missing_dependencies;
}
