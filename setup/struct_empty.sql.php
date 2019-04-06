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

$cont = trim(file_get_contents(__DIR__.'/sql/struct.sql'))."\n\n".
        trim(file_get_contents(__DIR__.'/sql/wellknown_country.sql'))."\n\n".
        trim(file_get_contents(__DIR__.'/sql/wellknown_other.sql'))."\n\n";

$table_names = array('objects', 'asn1id', 'iri', 'ra', 'config');
foreach ($table_names as $table) {
	$cont = str_replace('`'.$table.'`', '`'.$prefix.$table.'`', $cont);
}

if (php_sapi_name() != 'cli') {
	header('Content-Type:text/sql');
}

echo "USE `".$database."`;\n\n";
echo $cont;
