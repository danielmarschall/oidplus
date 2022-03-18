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

if (!defined('INSIDE_OIDPLUS')) die();

class OIDplusLoggerPluginDatabase extends OIDplusLoggerPlugin {

	public static function available(&$reason)/*: bool*/ {
		$reason = '';
		return true;
	}

	public static function log($event, $users, $objects)/*: bool*/ {
		$addr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
		OIDplus::dbIsolated()->query("insert into ###log (addr, unix_ts, event) values (?, ?, ?)", array($addr, time(), $event));
		$log_id = OIDplus::dbIsolated()->insert_id();
		if ($log_id === false) {
			$res = OIDplus::dbIsolated()->query("select max(id) as last_id from ###log");
			if (!$res->any()) throw new OIDplusException(_L('Could not log event'));
			$row = $res->fetch_array();
			$log_id = $row['last_id'];
			if ($log_id == 0) throw new OIDplusException(_L('Could not log event'));
		}

		$object_dupe_check = array();
		foreach ($objects as list($severity, $object)) {
			if (in_array($object, $object_dupe_check)) continue;
			$object_dupe_check[] = $object;
			OIDplus::dbIsolated()->query("insert into ###log_object (log_id, severity, object) values (?, ?, ?)", array($log_id, $severity, $object));
		}

		$user_dupe_check = array();
		foreach ($users as list($severity, $username)) {
			if (in_array($username, $user_dupe_check)) continue;
			$user_dupe_check[] = $username;
			OIDplus::dbIsolated()->query("insert into ###log_user (log_id, severity, username) values (?, ?, ?)", array($log_id, $severity, $username));
		}

		return true;
	}

}
