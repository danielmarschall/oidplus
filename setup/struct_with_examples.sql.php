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

include_once __DIR__ . '/../includes/oidplus.inc.php';

$prefix = isset($_REQUEST['prefix']) ? $_REQUEST['prefix'] : '';
$database = isset($_REQUEST['database']) ? $_REQUEST['database'] : '';
$slang = isset($_REQUEST['slang']) ? $_REQUEST['slang'] : 'mysql';

OIDplus::registerAllPlugins('sql_slang', 'OIDplusSqlSlangPlugin', null);
$slang_plugin = null;
foreach (get_declared_classes() as $c) {
	if (is_subclass_of($c, 'OIDplusSqlSlangPlugin')) {
		$obj = new $c();
		if ($obj::id() === $slang) {
			$slang_plugin = $obj;
			break;
		}
	}
}
if (is_null($slang_plugin)) {
	die('Unknown slang');
}

$cont = trim(file_get_contents(__DIR__.'/sql/struct_'.$slang.'.sql'))."\n\n".
        trim(file_get_contents(__DIR__.'/sql/wellknown_country_'.$slang.'.sql'))."\n\n".
        trim(file_get_contents(__DIR__.'/sql/wellknown_other_'.$slang.'.sql'))."\n\n".
        trim(file_get_contents(__DIR__.'/sql/example_'.$slang.'.sql'))."\n\n";

$table_names = array('objects', 'asn1id', 'iri', 'ra', 'config', 'log', 'log_user', 'log_object');
foreach ($table_names as $table) {
	$cont = $slang_plugin->setupSetTablePrefix($cont, $table, $prefix);
}

if (php_sapi_name() != 'cli') {
	header('Content-Type:text/sql');
	header('Content-Disposition: inline; filename="struct_with_examples.sql"');
}

if (!empty($database)) {
	echo $slang_plugin->setupCreateDbIfNotExists($database);
	echo $slang_plugin->setupUseDatabase($database);
}
echo $cont;
