<?php

/*
 * OIDplus 2.0
 * Copyright 2020 Daniel Marschall, ViaThinkSoft
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

/**
 * This function will be called by OIDplusDatabaseConnection.class.php at method afterConnect().
 * @param OIDplusDatabaseConnection $db is the OIDplusDatabaseConnection class
 * @param string $version is the current version (this script MUST increase the number by 1 when it is done)
 * @throws OIDplusException
 */
function oidplus_dbupdate_200_201(OIDplusDatabaseConnection $db, string &$version) {
    if ($db->transaction_supported()) $db->transaction_begin();
    
    $db->query("ALTER TABLE ###objects ADD comment varchar(255) NULL");
    
    $version = 201;
    $db->query("UPDATE ###config SET value = ? WHERE name = 'database_version'", array($version));
    
    if ($db->transaction_supported()) $db->transaction_commit();
}