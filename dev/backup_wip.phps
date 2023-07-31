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

require_once __DIR__ . '/../includes/oidplus.inc.php';

set_exception_handler(array(OIDplusGui::class, 'html_exception_handler'));

ob_start(); // allow cookie headers to be sent

OIDplus::init(true);




define('BACKUP_RECOVERY_SPECIAL_TEST', true); // TODO: Disable on release! Just for testing the backup/restore procedure!!!




/*private*/ function oidplus_num_rows_list(array $num_rows): string {
	$out = '';

	$ary2 = [];
	foreach ($num_rows as $table => $cnt) {
		if ($cnt !== "n/a") $ary2[] = "$table=$cnt";
	}
	$out = implode(", ", $ary2);

	if ($out === '') $out = 'No tables selected';
	return $out;
}



// ================ Backup ================

/*public*/ function oidplus_backup_db(string $backup_file, bool $export_objects=true, bool $export_ra=true, bool $export_config=false, bool $export_log=false): void {
	$num_rows = [
		"objects" => $export_objects ? 0 : "n/a",
		"asn1id" => $export_objects ? 0 : "n/a",
		"iri" => $export_objects ? 0 : "n/a",
		"ra" => $export_ra ? 0 : "n/a",
		"config" => $export_config ? 0 : "n/a",
		"log" => $export_log ? 0 : "n/a",
		"log_object" => $export_log ? 0 : "n/a",
		"log_user" => $export_log ? 0 : "n/a"
	];

	if (BACKUP_RECOVERY_SPECIAL_TEST) {
		if ($export_objects) {
			OIDplus::db()->query("delete from ###objects where id like '%_CLONE'");
			OIDplus::db()->query("delete from ###asn1id where oid like '%_CLONE'");
			OIDplus::db()->query("delete from ###iri where oid like '%_CLONE'");
		}
		if ($export_ra) {
			OIDplus::db()->query("delete from ###ra where email like '%_CLONE'");
		}
		if ($export_config) {
			OIDplus::db()->query("delete from ###config where name <> 'oidplus_private_key' and name <> 'oidplus_public_key' and name like '%_CLONE'");
		}
		if ($export_log) {
			OIDplus::db()->query("delete from ###log where addr like '%_CLONE'");
			OIDplus::db()->query("delete from ###log_object where object like '%_CLONE'");
			OIDplus::db()->query("delete from ###log_user where username like '%_CLONE'");
		}
	}

	// Backup objects (Tables objects, asn1id, iri)
	$objects = [];
	if ($export_objects) {
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
	}

	// Backup RAs (Table ra)
	$ra = [];
	if ($export_ra) {
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
	}

	// Backup configuration (Table config)
	$config = [];
	if ($export_config) {
		$res = OIDplus::db()->query("select * from ###config where name <> 'oidplus_private_key' and name <> 'oidplus_public_key' order by name");
		while ($row = $res->fetch_array()) {
			$num_rows["config"]++;
			$config[] = [
				"name" => $row["name"],
				"value" => $row["value"],
				"description" => $row["description"],
				"protected" => $row["protected"],
				"visible" => $row["visible"]
			];
		}
	}

	// Backup logs (Tables log, log_object, log_user)
	$log = [];
	if ($export_log) {
		$res = OIDplus::db()->query("select * from ###log order by id");
		$rows = [];
		while ($row = $res->fetch_array()) {
			// Not all databases support multiple active rows, so we need to read it in a isolated loop
			$rows[] = $row;
		}
		foreach ($rows as $row) {
			$num_rows["log"]++;

			$log_objects = [];
			$res2 = OIDplus::db()->query("select * from ###log_object where log_id = ? order by id", array($row["id"]));
			while ($row2 = $res2->fetch_array()) {
				$num_rows["log_object"]++;
				$log_objects[] = [
					"object" => $row2['object'],
					"severity" => $row2['severity']
				];
			}

			$log_users = [];
			$res2 = OIDplus::db()->query("select * from ###log_user where log_id = ? order by id", array($row["id"]));
			while ($row2 = $res2->fetch_array()) {
				$num_rows["log_user"]++;
				$log_users[] = [
					"username" => $row2['username'],
					"severity" => $row2['severity']
				];
			}

			$log[] = [
				"unix_ts" => $row["unix_ts"],
				"addr" => $row["addr"],
				"event" => $row["event"],
				"objects" => $log_objects,
				"users" => $log_users
			];
		}
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
		"ra" => $ra,
		"config" => $config,
		"log" => $log
	];


	// Done!

	$encoded_data = json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
	if (@file_put_contents($backup_file, $encoded_data) === false) {
		throw new OIDplusException("Could not write file to disk: $backup_file");
	}

	OIDplus::logger()->log("V2:[INFO]A", "Created backup: ".oidplus_num_rows_list($num_rows));

	echo "<p>Backup done: $backup_file</p>";
	foreach ($num_rows as $table_name => $cnt) {
		if ($cnt !== "n/a")  echo "<p>... $table_name: $cnt datasets</p>";
	}
	echo "<hr>";
	echo '<pre>';
	echo htmlentities($encoded_data);
	echo '</pre>';
}




// ================ Recovery ================

/*public*/ function oidplus_restore_db(string $backup_file, bool $import_objects=true, bool $import_ra=true, bool $import_config=false, bool $import_log=false): void {
	$num_rows = [
		"objects" => $import_objects ? 0 : "n/a",
		"asn1id" => $import_objects ? 0 : "n/a",
		"iri" => $import_objects ? 0 : "n/a",
		"ra" => $import_ra ? 0 : "n/a",
		"config" => $import_config ? 0 : "n/a",
		"log" => $import_log ? 0 : "n/a",
		"log_object" => $import_log ? 0 : "n/a",
		"log_user" => $import_log ? 0 : "n/a"
	];

	$cont = @file_get_contents($backup_file);
	if ($cont === false) throw new OIDplusException("Could not read file from disk: $backup_file");
	$json = @json_decode($cont,true);
	if ($json === false) throw new OIDplusException("Could not decode JSON structure of $backup_file");

	if (OIDplus::db()->transaction_supported()) OIDplus::db()->transaction_begin();
	try {

		// Restore objects (Tables objects, asn1id, iri)
		if ($import_objects) {
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
		}

		// Restore RAs (Table ra)
		if ($import_ra) {
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
		}

		// Restore configuration (Table config)
		if ($import_config) {
			if (!BACKUP_RECOVERY_SPECIAL_TEST) {
				OIDplus::db()->query("delete from ###config where name <> 'oidplus_private_key' and name <> 'oidplus_public_key'");
			}

			foreach (($json["config"]??[]) as $row) {
				if (BACKUP_RECOVERY_SPECIAL_TEST) {
					$row['name'] .= '_CLONE';
				}

				$num_rows["config"]++;
				OIDplus::db()->query("insert into ###config (name, value, description, protected, visible) values (?, ?, ?, ?, ?)",
					array($row["name"]??null,
						$row["value"]??null,
						$row["description"]??null,
						$row["protected"]??null,
						$row["visible"]??null)
				);
			}

		}

		// Restore logs (Tables log, log_object, log_user)
		if ($import_log) {
			if (!BACKUP_RECOVERY_SPECIAL_TEST) {
				OIDplus::db()->query("delete from ###log");
				OIDplus::db()->query("delete from ###log_object");
				OIDplus::db()->query("delete from ###log_user");
			}
			foreach (($json["log"]??[]) as $row) {
				if (BACKUP_RECOVERY_SPECIAL_TEST) {
					$row['addr'] .= '_CLONE';
				}

				$num_rows["log"]++;
				OIDplus::db()->query("insert into ###log (unix_ts, addr, event) values (?, ?, ?)",
					array($row["unix_ts"]??null,
						$row["addr"]??null,
						$row["event"]??null)
				);
				$row['id'] = OIDplus::db()->insert_id();

				foreach (($row["objects"]??[]) as $row2) {
					$num_rows["log_object"]++;
					OIDplus::db()->query("insert into ###log_object (log_id, object, severity) values (?, ?, ?)",
						array($row["id"]??null, // sic: $row, not $row2
							$row2["object"]??null,
							$row2["severity"]??null)
					);
				}

				foreach (($row["users"]??[]) as $row2) {
					$num_rows["log_user"]++;
					OIDplus::db()->query("insert into ###log_user (log_id, username, severity) values (?, ?, ?)",
						array($row["id"]??null, // sic: $row, not $row2
							$row2["username"]??null,
							$row2["severity"]??null)
					);
				}
			}
		}

		// Done!

		OIDplus::logger()->log("V2:[WARN]A", "EXECUTED OBJECT AND RA DATABASE BACKUP RECOVERY: ".oidplus_num_rows_list($num_rows));

		if (OIDplus::db()->transaction_supported()) OIDplus::db()->transaction_commit();

		echo "<p>Backup restore done: $backup_file</p>";
		foreach ($num_rows as $table_name => $cnt) {
			if ($cnt !== "n/a")  echo "<p>... $table_name: $cnt datasets</p>";
		}
		echo "<hr>";

	} catch (\Exception $e) {
		if (OIDplus::db()->transaction_supported()) OIDplus::db()->transaction_rollback();
		throw $e;
	}
}



if (!is_dir(OIDplus::localpath().'/userdata/backups/')) @mkdir(OIDplus::localpath().'/userdata/backups/');
$backup_file = OIDplus::localpath().'/userdata/backups/oidplus-'.date('Y-m-d-H-i-s').'.bak.json';
oidplus_backup_db($backup_file, true, true, true, true);
oidplus_restore_db($backup_file, true, true, true, true);




OIDplus::invoke_shutdown();
