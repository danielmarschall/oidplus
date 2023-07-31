<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2023 Daniel Marschall, ViaThinkSoft
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

use ViaThinkSoft\OIDplus\OIDplus;
use ViaThinkSoft\OIDplus\OIDplusException;
use ViaThinkSoft\OIDplus\OIDplusGui;

header('Content-Type:text/html; charset=UTF-8');

require_once __DIR__ . '/includes/oidplus.inc.php';

set_exception_handler(array(OIDplusGui::class, 'html_exception_handler'));

ob_start(); // allow cookie headers to be sent

OIDplus::init(true);




define('BACKUP_RECOVERY_SPECIAL_TEST', true); // TODO: Disable on release! Just for testing the backup/restore procedure!!!





// ================ Backup ================

$num_rows = [
	"objects" => 0,
	"asn1id" => 0,
	"iri" => 0,
	"ra" => 0,
	"log" => "n/a", // No backup for this table!
	"log_object" => "n/a", // No backup for this table!
	"log_user" => "n/a", // No backup for this table!
	"config" => "n/a" // No backup for this table!
];

if (BACKUP_RECOVERY_SPECIAL_TEST) {
	OIDplus::db()->query("delete from ###objects where id like '%_CLONE'");
	OIDplus::db()->query("delete from ###asn1id where oid like '%_CLONE'");
	OIDplus::db()->query("delete from ###iri where oid like '%_CLONE'");
	OIDplus::db()->query("delete from ###ra where email like '%_CLONE'");
}

// Backup objects (Tables objects, asn1id, iri)
$objects = [];
$res = OIDplus::db()->query("select * from ###objects order by id");
$rows = [];
while ($row = $res->fetch_array()) {
	// Not all databases support multiple active rows, so we need to read it in a isolated loop
	$rows[] = $row;
}
foreach ($rows as $row) {
	$num_rows["objects"]++;

	$asn1ids = [];
	$res2 = OIDplus::db()->query("select * from ###asn1id where oid = ? order by name", array($row["id"]));
	while ($row2 = $res2->fetch_array()) {
		$num_rows["asn1id"]++;
		$asn1ids[] = [
			"name" => $row2['name'],
			"standardized" => $row2['standardized'],
			"well_known" => $row2['well_known'],
		];
	}

	$iris = [];
	$res2 = OIDplus::db()->query("select * from ###iri where oid = ? order by name", array($row["id"]));
	while ($row2 = $res2->fetch_array()) {
		$num_rows["iri"]++;
		$iris[] = [
			"name" => $row2['name'],
			"longarc" => $row2['longarc'],
			"well_known" => $row2['well_known'],
		];
	}

	$objects[] = [
		"id" => $row["id"],
		"parent" => $row["parent"],
		"title" => $row["title"],
		"description" => $row["description"],
		"ra_email" => $row["ra_email"],
		"confidential" => $row["confidential"],
		"created" => $row["created"],
		"updated" => $row["updated"],
		"comment" => $row["comment"],
		"asn1ids" => $asn1ids,
		"iris" => $iris
	];
}

// Backup RAs (Table ra)
$ra = [];
$res = OIDplus::db()->query("select * from ###ra order by email");
while ($row = $res->fetch_array()) {
	$num_rows["ra"]++;
	$ra[] = [
		"email" => $row["email"],
		"ra_name" => $row["ra_name"],
		"personal_name" => $row["personal_name"],
		"organization" => $row["organization"],
		"office" => $row["office"],
		"street" => $row["street"],
		"zip_town" => $row["zip_town"],
		"country" => $row["country"],
		"phone" => $row["phone"],
		"mobile" => $row["mobile"],
		"fax" => $row["fax"],
		"privacy" => $row["privacy"],
		"authkey" => $row["authkey"],
		"registered" => $row["registered"],
		"updated" => $row["updated"],
		"last_login" => $row["last_login"]
	];
}

// Put everything together
$json = [
	"oidplus_backup" => [
		"file_version" => "2023",
		"origin_systemid" => OIDplus::getSystemId(false) ?: "unknown",
		"created" => date('Y-m-d H:i:s O'),
		"dataset_count" => $num_rows
	],
	"objects" => $objects,
	"ra" => $ra
];

OIDplus::logger()->log("V2:[INFO]A", "Created backup of Objects and RAs");


$backup_file = 'oidplus-'.date('Y-m-d-H-i-s').'.bak.json';

$encoded_data = json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
if (!is_dir(OIDplus::localpath().'/userdata/backups/')) @mkdir(OIDplus::localpath().'/userdata/backups/');
if (@file_put_contents(OIDplus::localpath().'/userdata/backups/'.$backup_file, $encoded_data) === false) {
	throw new OIDplusException("Could not write file to disk: $backup_file");
}

echo "<p>Backup done: $backup_file</p>";
foreach ($num_rows as $table_name => $cnt) {
	if ($cnt !== "n/a")  echo "<p>... $table_name: $cnt datasets</p>";
}
echo "<hr>";
//echo '<pre>';
//echo htmlentities($encoded_data);
//echo '</pre>';




// ================ Recovery ================

$num_rows = [
	"objects" => 0,
	"asn1id" => 0,
	"iri" => 0,
	"ra" => 0,
	"log" => "n/a", // No backup for this table!
	"log_object" => "n/a", // No backup for this table!
	"log_user" => "n/a", // No backup for this table!
	"config" => "n/a" // No backup for this table!
];

$cont = @file_get_contents(OIDplus::localpath().'/userdata/backups/'.$backup_file);
if ($cont === false) throw new OIDplusException("Could not read file from disk: $backup_file");
$json = @json_decode($cont,true);
if ($json === false) throw new OIDplusException("Could not decode JSON structure of $backup_file");

if (OIDplus::db()->transaction_supported()) OIDplus::db()->transaction_begin();
try {
	if (!BACKUP_RECOVERY_SPECIAL_TEST) {
		OIDplus::db()->query("delete from ###objects");
		OIDplus::db()->query("delete from ###asn1id");
		OIDplus::db()->query("delete from ###iri");
	}
	foreach (($json["objects"]??[]) as $row) {
		if (BACKUP_RECOVERY_SPECIAL_TEST) {
			$row['id'] .= '_CLONE';
			if (substr($row['parent'], -1) != ':') $row['parent'] .= '_CLONE';
		}

		$num_rows["objects"]++;
		OIDplus::db()->query("insert into ###objects (id, parent, title, description, ra_email, confidential, created, updated, comment) values (?, ?, ?, ?, ?, ?, ?, ?, ?)",
			array($row["id"]??null,
				$row["parent"]??null,
				$row["title"]??null,
				$row["description"]??null,
				$row["ra_email"]??null,
				$row["confidential"]??null,
				$row["created"]??null,
				$row["updated"]??null,
				$row["comment"]??null)
		);

		foreach (($row["asn1ids"]??[]) as $row2) {
			$num_rows["asn1id"]++;
			OIDplus::db()->query("insert into ###asn1id (oid, name, standardized, well_known) values (?, ?, ?, ?)",
				array($row["id"]??null, // sic: $row, not $row2
					$row2["name"]??null,
					$row2["standardized"]??null,
					$row2["well_known"]??null)
			);
		}

		foreach (($row["iris"]??[]) as $row2) {
			$num_rows["iri"]++;
			OIDplus::db()->query("insert into ###iri (oid, name, longarc, well_known) values (?, ?, ?, ?)",
				array($row["id"]??null, // sic: $row, not $row2
					$row2["name"]??null,
					$row2["longarc"]??null,
					$row2["well_known"]??null)
			);
		}
	}

	if (!BACKUP_RECOVERY_SPECIAL_TEST) {
		OIDplus::db()->query("delete from ###ra");
	}
	foreach (($json["ra"]??[]) as $row) {
		if (BACKUP_RECOVERY_SPECIAL_TEST) {
			$row['email'] .= '_CLONE';
		}

		$num_rows["ra"]++;
		OIDplus::db()->query("insert into ###ra (email, ra_name, personal_name, organization, office, street, zip_town, country, phone, mobile, fax, privacy, authkey, registered, updated, last_login) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
			array($row["email"]??null,
				$row["ra_name"]??null,
				$row["personal_name"]??null,
				$row["organization"]??null,
				$row["office"]??null,
				$row["street"]??null,
				$row["zip_town"]??null,
				$row["country"]??null,
				$row["phone"]??null,
				$row["mobile"]??null,
				$row["fax"]??null,
				$row["privacy"]??null,
				$row["authkey"]??null,
				$row["registered"]??null,
				$row["updated"]??null,
				$row["last_login"]??null)
		);
	}

	echo "<p>Backup restore done: $backup_file</p>";
	foreach ($num_rows as $table_name => $cnt) {
		if ($cnt !== "n/a")  echo "<p>... $table_name: $cnt datasets</p>";
	}
	echo "<hr>";

	OIDplus::logger()->log("V2:[WARN]A", "EXECUTED OBJECT AND RA DATABASE BACKUP RECOVERY");

	if (OIDplus::db()->transaction_supported()) OIDplus::db()->transaction_commit();
} catch (\Exception $e) {
	if (OIDplus::db()->transaction_supported()) OIDplus::db()->transaction_rollback();
	throw $e;
}






OIDplus::invoke_shutdown();
