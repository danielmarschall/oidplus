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

namespace ViaThinkSoft\OIDplus\Plugins\viathinksoft\adminPages\n401_backup;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusHtmlException;
use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginAdmin;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

const BACKUP_RECOVERY_SPECIAL_TEST = false; // ONLY FOR TESTING BACKUP/RESTORE PROCEDURE DURING DEVELOPMENT

class OIDplusPageAdminDatabaseBackup extends OIDplusPagePluginAdmin
{

	/**
	 * @param string $id
	 * @param array $out
	 * @param bool $handled
	 * @return void
	 * @throws OIDplusException
	 */
	public function gui(string $id, array &$out, bool &$handled): void {
		$ary = explode('$', $id);
		if (isset($ary[1])) {
			$id = $ary[0];
			$tab = $ary[1];
		} else {
			$tab = 'export';
		}

		if ($id === 'oidplus:database_backup') {
			$handled = true;
			$out['title'] = _L('Database Backup');
			$out['icon'] = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')), $out['title'], 401);
			}

			$out['text'] = '<noscript>';
			$out['text'] .= '<p><font color="red">'._L('You need to enable JavaScript to use this feature.').'</font></p>';
			$out['text'] .= '</noscript>';

			$out['text'] .= '<br><div id="databaseBackupArea" style="visibility: hidden"><div id="databaseBackupTab" class="container" style="width:100%;">';

			// ---------------- Tab control
			$out['text'] .= OIDplus::gui()->tabBarStart();
			$out['text'] .= OIDplus::gui()->tabBarElement('export', _L('Backup'), $tab === 'export');
			$out['text'] .= OIDplus::gui()->tabBarElement('import', _L('Restore'), $tab === 'import');
			$out['text'] .= OIDplus::gui()->tabBarEnd();
			$out['text'] .= OIDplus::gui()->tabContentStart();
			// ---------------- "Backup" tab
			$tabcont = '';
			$tabcont .= '<h2>'._L('Create database backup').'</h2>';
			$tabcont .= '<form action="'.OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'download_backup.php" method="POST" target="_blank">';

			$tabcont .= '<p>'._L('Download a database backup file with the following contents:').'</p>';
			$tabcont .= '<p>';
			$tabcont .= '<input type="checkbox" checked name="database_backup_export_objects" id="database_backup_export_objects"> <label for="database_backup_export_objects">'._L('Objects').'</label><br>';
			$tabcont .= '<input type="checkbox" checked name="database_backup_export_ra" id="database_backup_export_ra"> <label for="database_backup_export_ra">'._L('Registration Authorities').'</label><br>';
			$tabcont .= '<input type="checkbox" checked name="database_backup_export_config" id="database_backup_export_config"> <label for="database_backup_export_config">'._L('Configuration').'</label><br>';
			$tabcont .= '<input type="checkbox" checked name="database_backup_export_log" id="database_backup_export_log"> <label for="database_backup_export_log">'._L('Log messages').'</label><br>';
			$tabcont .= '<input type="checkbox" name="database_backup_export_pki" id="database_backup_export_pki"> <label for="database_backup_export_pki">'._L('Private key (Highly confidential!)').'</label><br>';
			$tabcont .= '</p>';

			$tabcont .= '<p>';
			$tabcont .= '<input type="checkbox" name="database_backup_export_encrypt" id="database_backup_export_encrypt"> <label for="database_backup_export_encrypt">'._L('Encrypt backup file with the following password (optional)').':</label><br>';
			$tabcont .= '<label style="margin-left:25px;width:160px" for="database_backup_export_password1">'._L('Password').':</label> <input type="password" name="database_backup_export_password1" id="database_backup_export_password1"><br>';
			$tabcont .= '<label style="margin-left:25px;width:160px" for="database_backup_export_password2">'._L('Password (repeat)').':</label> <input type="password" name="database_backup_export_password2" id="database_backup_export_password2"><br>';
			$tabcont .= '</p>';

			$tabcont .= '<input type="submit" value="'._L('Download backup').'">';

			$tabcont .= '<p><i>'._L('Attention: Some Database Management Systems (DBMS), OIDplus connectivity plugins, and OIDplus SQL Slang plugins might export and import data differently regarding NULL values, time zones, boolean values, Unicode characters, etc. Please use backup/restore with caution and consider testing the restore procedure on a staging environment first.').'</i></p>';

			$tabcont .= '</form>';
			$out['text'] .= OIDplus::gui()->tabContentPage('export', $tabcont, $tab === 'export');
			// ---------------- "Restore" tab

			$tabcont = '';
			$tabcont .= '<h2>'._L('Restore database backup').'</h2>';
			$tabcont .= '<form action="'.OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'restore_backup.php" method="POST" target="_blank" enctype="multipart/form-data">';

			$tabcont .= '<p>'._L('Choose database backup file here').':<br><input type="file" name="userfile" value="" id="userfile"></p>';

			$tabcont .= '<p>'._L('Restore the following contents from the backup file (existing data will be deleted):').'</p>';
			$tabcont .= '<p>';
			$tabcont .= '<input type="checkbox" checked name="database_backup_import_objects" id="database_backup_import_objects"> <label for="database_backup_import_objects">'._L('Objects').'</label><br>';
			$tabcont .= '<input type="checkbox" checked name="database_backup_import_ra" id="database_backup_import_ra"> <label for="database_backup_import_ra">'._L('Registration Authorities').'</label><br>';
			$tabcont .= '<input type="checkbox" checked name="database_backup_import_config" id="database_backup_import_config"> <label for="database_backup_import_config">'._L('Configuration').'</label><br>';
			$tabcont .= '<input type="checkbox" checked name="database_backup_import_log" id="database_backup_import_log"> <label for="database_backup_import_log">'._L('Log messages').'</label><br>';
			$tabcont .= '<input type="checkbox" name="database_backup_import_pki" id="database_backup_import_pki"> <label for="database_backup_import_pki">'._L('Private key').' *</label><br>';
			$tabcont .= '</p>';

			$tabcont .= '<p>';
			$tabcont .= '<label for="database_backup_import_encrypt">'._L('In case the backup is encrypted, enter the decryption password here').':</label><br>';
			$tabcont .= '<label style="margin-left:25px;width:160px" for="database_backup_import_password">'._L('Password').':</label> <input type="password" name="database_backup_import_password" id="database_backup_import_password"><br>';
			$tabcont .= '</p>';

			$tabcont .= '<input type="submit" value="'._L('Restore backup').'">';

			$tabcont .= '<p>* <i>'._L('Attention: In case you are cloning a system, e.g. in order to create a staging environment, please DO NOT copy the private key, as this would cause two systems to have the same System ID.').'</i></p>';


			$tabcont .= '</form>';
			$out['text'] .= OIDplus::gui()->tabContentPage('import', $tabcont, $tab === 'import');
			$out['text'] .= OIDplus::gui()->tabContentEnd();
			// ---------------- Tab control END

			$out['text'] .= '</div></div><script>$("#databaseBackupArea")[0].style.visibility = "visible";</script>';
		}
	}

	/**
	 * @param array $json
	 * @param string|null $ra_email
	 * @param bool $nonjs
	 * @param string $req_goto
	 * @return bool
	 * @throws OIDplusException
	 */
	public function tree(array &$json, ?string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
		if (!OIDplus::authUtils()->isAdminLoggedIn()) return false;

		if (file_exists(__DIR__.'/img/main_icon16.png')) {
			$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:database_backup',
			'icon' => $tree_icon,
			'text' => _L('Database Backup')
		);

		return true;
	}

	/**
	 * @param string $request
	 * @return array|false
	 */
	public function tree_search(string $request) {
		return false;
	}

	/**
	 * @param array $num_rows
	 * @return string
	 */
	private static function num_rows_list(array $num_rows): string {
		$ary2 = [];
		foreach ($num_rows as $table => $cnt) {
			if ($cnt !== "n/a") $ary2[] = "$table=$cnt";
		}
		$out = implode(", ", $ary2);

		if ($out === '') $out = 'No tables selected';
		return $out;
	}

	/**
	 * @param mixed|string|null $datetime
	 * @return mixed|string|null
	 */
	private static function fix_datetime_for_output($datetime) {
		if ($datetime === "0000-00-00 00:00:00") $datetime = null; // MySQL might use this as default instead of NULL... But SQL Server cannot read this.

		if (is_string($datetime) && (substr($datetime,4,1) !== '-')) {
			// Let's hope PHP can convert the database language specific string to ymd
			$time = @strtotime($datetime);
			if ($time) {
				$date = date('Y-m-d H:i:s', $time);
				if ($date) {
					$datetime = $date;
				}
			}
		}
		return $datetime;
	}

	/**
	 * @param bool $showReport
	 * @param bool $export_objects
	 * @param bool $export_ra
	 * @param bool $export_config
	 * @param bool $export_log
	 * @param bool $export_pki
	 * @return string
	 * @throws OIDplusException
	 * @throws \ViaThinkSoft\OIDplus\Core\OIDplusConfigInitializationException
	 */
	public static function createBackup(bool $showReport, bool $export_objects=true, bool $export_ra=true, bool $export_config=false, bool $export_log=false, bool $export_pki=false): string {
		$num_rows = [
			"objects" => $export_objects ? 0 : "n/a",
			"asn1id" => $export_objects ? 0 : "n/a",
			"iri" => $export_objects ? 0 : "n/a",
			"ra" => $export_ra ? 0 : "n/a",
			"config" => $export_config ? 0 : "n/a",
			"log" => $export_log ? 0 : "n/a",
			"log_object" => $export_log ? 0 : "n/a",
			"log_user" => $export_log ? 0 : "n/a",
			"pki" => $export_pki ? 0 : "n/a"
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
						"standardized" => $row2['standardized'] ?? false,
						"well_known" => $row2['well_known'] ?? false,
					];
				}

				$iris = [];
				$res2 = OIDplus::db()->query("select * from ###iri where oid = ? order by name", array($row["id"]));
				while ($row2 = $res2->fetch_array()) {
					$num_rows["iri"]++;
					$iris[] = [
						"name" => $row2['name'],
						"longarc" => $row2['longarc'] ?? false,
						"well_known" => $row2['well_known'] ?? false,
					];
				}

				$objects[] = [
					"id" => $row["id"],
					"parent" => $row["parent"],
					"title" => $row["title"],
					"description" => $row["description"],
					"ra_email" => $row["ra_email"],
					"confidential" => $row["confidential"] ?? false,
					"created" => self::fix_datetime_for_output($row["created"]),
					"updated" => self::fix_datetime_for_output($row["updated"]),
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
					"privacy" => $row["privacy"] ?? false,
					"authkey" => $row["authkey"],
					"registered" => self::fix_datetime_for_output($row["registered"]),
					"updated" => self::fix_datetime_for_output($row["updated"]),
					"last_login" => self::fix_datetime_for_output($row["last_login"])
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
					"protected" => $row["protected"] ?? false,
					"visible" => $row["visible"] ?? false
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
						"severity" => $row2['severity'] ?? 0
					];
				}

				$log_users = [];
				$res2 = OIDplus::db()->query("select * from ###log_user where log_id = ? order by id", array($row["id"]));
				while ($row2 = $res2->fetch_array()) {
					$num_rows["log_user"]++;
					$log_users[] = [
						"username" => $row2['username'],
						"severity" => $row2['severity'] ?? 0
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

		// Backup public/private key
		$pki = [];
		if ($export_pki) {
			$num_rows["pki"]++;
			$pki[] = [
				"private_key" => OIDplus::getSystemPrivateKey(),
				"public_key" => OIDplus::getSystemPublicKey()
			];
		}

		// Put everything together
		$json = [
			"\$schema" => "urn:oid:1.3.6.1.4.1.37476.2.5.2.8.1.1",
			"oidplus_backup" => [
				"created" => date('Y-m-d H:i:s O'),
				"origin_systemid" => ($sysid = OIDplus::getSystemId(false)) ? (int)$sysid : "unknown",
				"dataset_count" => $num_rows
			],
			"objects" => $objects,
			"ra" => $ra,
			"config" => $config,
			"log" => $log,
			"pki" => $pki
		];

		// Done!

		$encoded_data = json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
		if ($encoded_data === false) {
			// Some DBMS plugins might not output UTF-8 correctly. In my test case it was SQL Server on ADO/MSOLEDBSQL (where Unicode does not work in OIDplus for some unknown reason)
			array_walk_recursive($json, function (&$value)
			{
				if (is_string($value)) $value = vts_utf8_encode($value);
			});
			$encoded_data = json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
			if ($encoded_data === false) {
				throw new OIDplusException(_L("%1 failed","json_encode"));
			}
		}

		OIDplus::logger()->log("V2:[INFO]A", "Created backup: ".self::num_rows_list($num_rows));

		if ($showReport) {
			echo "<h1>"._L('Backup done')."</h1>";
			foreach ($num_rows as $table_name => $cnt) {
				if ($cnt !== "n/a")  echo "<p>... $table_name: "._L('%1 datasets', $cnt)."</p>";
			}
			echo "<hr>";
		}

		return $encoded_data;
	}

	/**
	 * @param bool $showReport
	 * @param string $cont
	 * @param bool $import_objects
	 * @param bool $import_ra
	 * @param bool $import_config
	 * @param bool $import_log
	 * @param bool $import_pki
	 * @return void
	 * @throws OIDplusException
	 * @throws \ViaThinkSoft\OIDplus\Core\OIDplusConfigInitializationException
	 */
	public static function restoreBackup(bool $showReport, string $cont, bool $import_objects=true, bool $import_ra=true, bool $import_config=false, bool $import_log=false, bool $import_pki=false): void {
		$num_rows = [
			"objects" => $import_objects ? 0 : "n/a",
			"asn1id" => $import_objects ? 0 : "n/a",
			"iri" => $import_objects ? 0 : "n/a",
			"ra" => $import_ra ? 0 : "n/a",
			"config" => $import_config ? 0 : "n/a",
			"log" => $import_log ? 0 : "n/a",
			"log_object" => $import_log ? 0 : "n/a",
			"log_user" => $import_log ? 0 : "n/a",
			"pki" => $import_pki ? 0 : "n/a"
		];

		//$cont = @file_get_contents($backup_file);
		//if ($cont === false) throw new OIDplusException(_L("Could not read file from disk: %1", $backup_file));
		$json = @json_decode($cont,true);
		if ($json === false) throw new OIDplusException(_L("Could not decode JSON structure of backup file"));

		if (($json["\$schema"]??"") != "urn:oid:1.3.6.1.4.1.37476.2.5.2.8.1.1") {
			throw new OIDplusException(_L("File cannot be restored, because it has a wrong file format (schema)"));
		}

		if ($import_objects) {
			$tmp = $json["oidplus_backup"]["dataset_count"]["objects"] ?? "n/a";
			if ($tmp === "n/a") {
				throw new OIDplusException(_L('Backup cannot be restored, because you want to import "%1", but the file was not created with this data.',"objects"));
			}

			$cnt = count($json["objects"]??[]);
			if ($tmp != $cnt) {
				throw new OIDplusException(_L('Backup cannot be restored, because the number of "%1" does not match',"objects"));
			}

			$tmp = $json["oidplus_backup"]["dataset_count"]["asn1id"] ?? "n/a";
			$cnt_asn1id = 0;
			foreach (($json["objects"]??[]) as $row) {
				$cnt_asn1id += count($row['asn1ids']??[]);
			}
			if ($tmp != $cnt_asn1id) {
				throw new OIDplusException(_L('Backup cannot be restored, because the number of "%1" does not match',"asn1id"));
			}

			$tmp = $json["oidplus_backup"]["dataset_count"]["iri"] ?? "n/a";
			$cnt_iri = 0;
			foreach (($json["objects"]??[]) as $row) {
				$cnt_iri += count($row['iris']??[]);
			}
			if ($tmp != $cnt_iri) {
				throw new OIDplusException(_L('Backup cannot be restored, because the number of "%1" does not match',"iri"));
			}
		}

		if ($import_ra) {
			$tmp = $json["oidplus_backup"]["dataset_count"]["ra"] ?? "n/a";
			if ($tmp === "n/a") {
				throw new OIDplusException(_L('Backup cannot be restored, because you want to import "%1", but the file was not created with this data.',"ra"));
			}
			$cnt = count($json["ra"]??[]);
			if ($tmp != $cnt) {
				throw new OIDplusException(_L('Backup cannot be restored, because the number of "%1" does not match',"ra"));
			}
		}

		if ($import_config) {
			$tmp = $json["oidplus_backup"]["dataset_count"]["config"] ?? "n/a";
			if ($tmp === "n/a") {
				throw new OIDplusException(_L('Backup cannot be restored, because you want to import "%1", but the file was not created with this data.',"config"));
			}
			$cnt = count($json["config"]??[]);
			if ($tmp != $cnt) {
				throw new OIDplusException(_L('Backup cannot be restored, because the number of "%1" does not match',"config"));
			}
		}

		if ($import_log) {
			$tmp = $json["oidplus_backup"]["dataset_count"]["log"] ?? "n/a";
			if ($tmp === "n/a") {
				throw new OIDplusException(_L('Backup cannot be restored, because you want to import "%1", but the file was not created with this data.',"log"));
			}

			$cnt = count($json["log"]??[]);
			if ($tmp != $cnt) {
				throw new OIDplusException(_L('Backup cannot be restored, because the number of "%1" does not match',"log"));
			}

			$tmp = $json["oidplus_backup"]["dataset_count"]["log_object"] ?? "n/a";
			$cnt_objects = 0;
			foreach (($json["log"]??[]) as $row) {
				$cnt_objects += count($row['objects']??[]);
			}
			if ($tmp != $cnt_objects) {
				throw new OIDplusException(_L('Backup cannot be restored, because the number of "%1" does not match',"log_object"));
			}

			$tmp = $json["oidplus_backup"]["dataset_count"]["log_user"] ?? "n/a";
			$cnt_users = 0;
			foreach (($json["log"]??[]) as $row) {
				$cnt_users += count($row['users']??[]);
			}
			if ($tmp != $cnt_users) {
				throw new OIDplusException(_L('Backup cannot be restored, because the number of "%1" does not match',"log_user"));
			}
		}

		if ($import_pki) {
			$tmp = $json["oidplus_backup"]["dataset_count"]["pki"] ?? "n/a";
			if ($tmp === "n/a") {
				throw new OIDplusException(_L('Backup cannot be restored, because you want to import "%1", but the file was not created with this data.',"pki"));
			}
			if (($tmp !== 0) && ($tmp !== 1)) {
				throw new OIDplusException(_L('Backup cannot be restored, because the number of "%1" is invalid',"pki"));
			}
			$cnt = count($json["pki"]??[]);
			if ($tmp != $cnt) {
				throw new OIDplusException(_L('Backup cannot be restored, because the number of "%1" does not match',"pki"));
			}
		}

		if (OIDplus::db()->getSlang()->id() == 'mssql') {
			// MSSQL: Try to find out if the other system created in YMD format
			$has_ymd_format = false;
			foreach (($json["objects"]??[]) as $row) {
				if (substr($row["created"]??'',4,1) === '-') $has_ymd_format = true;
				if (substr($row["updated"]??'',4,1) === '-') $has_ymd_format = true;

			}
			foreach (($json["ra"]??[]) as $row) {
				if (substr($row["registered"]??'',4,1) === '-') $has_ymd_format = true;
				if (substr($row["updated"]??'',4,1) === '-') $has_ymd_format = true;
				if (substr($row["last_login"]??'',4,1) === '-') $has_ymd_format = true;
			}
			if ($has_ymd_format) {
				OIDplus::db()->query("SET DATEFORMAT ymd;");
			}

			// Convert "0000-00-00 00:00:00" (MySQL) to NULL
			if (isset($json["objects"])) {
				foreach ($json["objects"] as &$row) {
					if ($row["created"] === "0000-00-00 00:00:00") $row["created"] = null;
					if ($row["updated"] === "0000-00-00 00:00:00") $row["updated"] = null;
				}
				unset($row);
			}
			if (isset($json["ra"])) {
				foreach ($json["ra"] as &$row) {
					if ($row["registered"] === "0000-00-00 00:00:00") $row["registered"] = null;
					if ($row["updated"] === "0000-00-00 00:00:00") $row["updated"] = null;
					if ($row["last_login"] === "0000-00-00 00:00:00") $row["last_login"] = null;
				}
				unset($row);
			}
		}

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
							(bool)($row["confidential"]??false),
							$row["created"]??'1900-01-01 00:00:00',
							$row["updated"]??'1900-01-01 00:00:00',
							$row["comment"]??null)
					);

					foreach (($row["asn1ids"]??[]) as $row2) {
						$num_rows["asn1id"]++;
						OIDplus::db()->query("insert into ###asn1id (oid, name, standardized, well_known) values (?, ?, ?, ?)",
							array($row["id"]??null, // sic: $row, not $row2
								$row2["name"]??null,
								(bool)($row2["standardized"]??false),
								(bool)($row2["well_known"]??false))
						);
					}

					foreach (($row["iris"]??[]) as $row2) {
						$num_rows["iri"]++;
						OIDplus::db()->query("insert into ###iri (oid, name, longarc, well_known) values (?, ?, ?, ?)",
							array($row["id"]??null, // sic: $row, not $row2
								$row2["name"]??null,
								(bool)($row2["longarc"]??false),
								(bool)($row2["well_known"]??false))
						);
					}
				}
				OIDplus::db()->query("update ###objects set created = null where created = '1900-01-01 00:00:00';");
				OIDplus::db()->query("update ###objects set updated = null where updated = '1900-01-01 00:00:00';");
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
							(bool)($row["privacy"]??false),
							$row["authkey"]??null,
							$row["registered"]??'1900-01-01 00:00:00',
							$row["updated"]??'1900-01-01 00:00:00',
							$row["last_login"]??'1900-01-01 00:00:00')
					);
				}
				OIDplus::db()->query("update ###ra set registered = null where registered = '1900-01-01 00:00:00';");
				OIDplus::db()->query("update ###ra set updated = null where updated = '1900-01-01 00:00:00';");
				OIDplus::db()->query("update ###ra set last_login = null where last_login = '1900-01-01 00:00:00';");
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
							(bool)($row["protected"]??false),
							(bool)($row["visible"]??false))
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
					if ($row['id'] <= 0) {
						throw new OIDplusException(_L("Error during restore of backup: Cannot get insert_id of log entry!"));
					}

					foreach (($row["objects"]??[]) as $row2) {
						$num_rows["log_object"]++;
						OIDplus::db()->query("insert into ###log_object (log_id, object, severity) values (?, ?, ?)",
							array($row["id"], // sic: $row, not $row2
								$row2["object"]??null,
								$row2["severity"]??0)
						);
					}

					foreach (($row["users"]??[]) as $row2) {
						$num_rows["log_user"]++;
						OIDplus::db()->query("insert into ###log_user (log_id, username, severity) values (?, ?, ?)",
							array($row["id"], // sic: $row, not $row2
								$row2["username"]??null,
								$row2["severity"]??0)
						);
					}
				}
			}

			// Restore public/private key
			if ($import_pki) {
				$privkey = $json["pki"][0]["private_key"] ?? null;
				$pubkey = $json["pki"][0]["public_key"] ?? null;
				if ($privkey && $pubkey) {
					$num_rows["pki"]++;
					// Note: If the private key is not encrypted, then it will be re-encrypted during the next call of OIDplus::getPkiStatus()
					OIDplus::db()->query("update ###config set value = ? where name = 'oidplus_private_key'", [$privkey]);
					OIDplus::db()->query("update ###config set value = ? where name = 'oidplus_public_key'", [$pubkey]);
					OIDplus::config()->clearCache();
				}
			}

			// Done!

			OIDplus::logger()->log("V2:[WARN]A", "EXECUTED OBJECT AND RA DATABASE BACKUP RECOVERY: ".self::num_rows_list($num_rows));

			if (OIDplus::db()->transaction_supported()) OIDplus::db()->transaction_commit();

			if ($showReport) {
				echo "<h1>"._L('Backup restore done')."</h1>";
				foreach ($num_rows as $table_name => $cnt) {
					if ($cnt !== "n/a") echo "<p>... $table_name: "._L('%1 datasets', $cnt)."</p>";
				}
				echo "<hr>";
			}

		} catch (\Exception $e) {
			if (OIDplus::db()->transaction_supported()) OIDplus::db()->transaction_rollback();
			throw $e;
		}
	}

}
