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

$prefix = isset($_REQUEST['prefix']) ? $_REQUEST['prefix'] : '';
$database = isset($_REQUEST['database']) ? $_REQUEST['database'] : '';
$slang = isset($_REQUEST['slang']) ? $_REQUEST['slang'] : 'mysql';

if (($slang != 'mysql') && ($slang != 'pgsql') && ($slang != 'mssql') && ($slang != 'sqlite')) die('Unknown slang');

$cont = trim(file_get_contents(__DIR__.'/sql/struct_'.$slang.'.sql'))."\n\n".
        trim(file_get_contents(__DIR__.'/sql/wellknown_country_'.$slang.'.sql'))."\n\n".
        trim(file_get_contents(__DIR__.'/sql/wellknown_other_'.$slang.'.sql'))."\n\n";

$table_names = array('objects', 'asn1id', 'iri', 'ra', 'config', 'log', 'log_user', 'log_object');
foreach ($table_names as $table) {
	if ($slang == 'mysql') {
		$cont = str_replace('`'.$table.'`', '`'.$prefix.$table.'`', $cont);
	}
	if ($slang == 'sqlite') {
		$cont = str_replace('`'.$table.'`', '`'.$prefix.$table.'`', $cont);
	}
	if ($slang == 'pgsql') {
		$cont = str_replace('"'.$table.'"', '"'.$prefix.$table.'"', $cont);
		$cont = str_replace('"index_'.$table, '"index_'.$prefix.$table, $cont);
	}
	if ($slang == 'mssql') {
		$cont = str_replace('['.$table.']', '['.$prefix.$table.']', $cont);
		$cont = str_replace('dbo.'.$table, 'dbo.'.$prefix.$table, $cont);
		$cont = str_replace('PK_'.$table, 'PK_'.$prefix.$table, $cont);
		$cont = str_replace('IX_'.$table, 'PK_'.$prefix.$table, $cont);
		$cont = str_replace('DF__'.$table, 'DF__'.$prefix.$table, $cont);
	}
}

if (php_sapi_name() != 'cli') {
	header('Content-Type:text/sql');
	header('Content-Disposition: inline; filename="struct_empty.sql"');
}

if (!empty($database)) {
	if ($slang == 'mysql') {
		echo "CREATE DATABASE IF NOT EXISTS `$database`;\n\n";
		echo "USE `$database`;\n\n";
	}
	if ($slang == 'pgsql') {
		echo "-- CREATE DATABASE $database;\n\n";
		echo "-- \connect $database;\n\n";
	}
	if ($slang == 'mssql') {
		echo "USE [$database]\n\n";
		echo "GO\n\n";
	}
}
echo $cont;
