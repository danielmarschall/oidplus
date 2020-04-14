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

// DATABASE UPDATE 201 -> 202
// This script will be included by OIDplusDatabasePlugin.class.php inside function afterConnect().
// Parameters: $this is the OIDplusDatabasePlugin class
//             $version is the current version (this script MUST increase the number by 1 when it is done)

$this->transaction_begin();

if (!isset($version)) throw new OIDplusException("Argument 'version' is missing; was the file included in a wrong way?");
if (!isset($this))    throw new OIDplusException("Argument 'this' is missing; was the file included in a wrong way?");

// Change bit(1) types to boolean/tinyint(1)
if ($this->slang() == 'pgsql') {
	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."config  alter protected    drop default");
	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."config  alter protected    type boolean using get_bit(protected   ,0)::boolean");
	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."config  alter protected    set default false");

	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."config  alter visible      drop default");
	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."config  alter visible      type boolean using get_bit(visible     ,0)::boolean");
	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."config  alter visible      set default false");

	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."asn1id  alter standardized drop default");
	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."asn1id  alter standardized type boolean using get_bit(standardized,0)::boolean");
	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."asn1id  alter standardized set default false");

	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."asn1id  alter well_known   drop default");
	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."asn1id  alter well_known   type boolean using get_bit(well_known  ,0)::boolean");
	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."asn1id  alter well_known   set default false");

	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."iri     alter longarc      drop default");
	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."iri     alter longarc      type boolean using get_bit(longarc     ,0)::boolean");
	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."iri     alter longarc      set default false");

	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."iri     alter well_known   drop default");
	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."iri     alter well_known   type boolean using get_bit(well_known  ,0)::boolean");
	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."iri     alter well_known   set default false");

	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."objects alter confidential type boolean using get_bit(confidential,0)::boolean");

	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."ra      alter privacy      drop default");
	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."ra      alter privacy      type boolean using get_bit(privacy     ,0)::boolean");
	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."ra      alter privacy      set default false");
} else if ($this->slang() == 'mysql') {
	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."config  modify protected    boolean");
	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."config  modify visible      boolean");
	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."asn1id  modify standardized boolean");
	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."asn1id  modify well_known   boolean");
	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."iri     modify longarc      boolean");
	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."iri     modify well_known   boolean");
	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."objects modify confidential boolean");
	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."ra      modify privacy      boolean");
}

// Rename log_user.user to log_user.username, since user is a keyword in PostgreSQL and MSSQL
if ($this->slang() == 'pgsql') {
	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."log_user rename column \"user\" to \"username\"");
} else if ($this->slang() == 'mysql') {
	$this->query("alter table ".OIDPLUS_TABLENAME_PREFIX."log_user change `user` `username` varchar(255) NOT NULL");
}

$version = 202;
$this->query("UPDATE ".OIDPLUS_TABLENAME_PREFIX."config SET value = ? WHERE name = 'database_version'", array($version));

$this->transaction_commit();
