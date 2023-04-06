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

namespace ViaThinkSoft\OIDplus;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusLoggerPluginDatabase extends OIDplusLoggerPlugin {

	/**
	 * @param string $reason
	 * @return bool
	 */
	public static function available(string &$reason): bool {
		$reason = '';
		return true;
	}

	/**
	 * @param string $event
	 * @param array $users
	 * @param array $objects
	 * @return bool
	 * @throws OIDplusException
	 */
	public static function log(string $event, array $users, array $objects): bool {
		$addr = $_SERVER['REMOTE_ADDR'] ?? '';
		OIDplus::dbIsolated()->query("insert into ###log (addr, unix_ts, event) values (?, ?, ?)", array($addr, time(), $event));
		$log_id = OIDplus::dbIsolated()->insert_id();
		if ($log_id === 0) {
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
			OIDplus::dbIsolated()->query("insert into ###log_object (log_id, severity, object) values (?, ?, ?)", array((int)$log_id, (int)$severity, $object));
		}

		$user_dupe_check = array();
		foreach ($users as list($severity, $username)) {
			if (in_array($username, $user_dupe_check)) continue;
			$user_dupe_check[] = $username;
			OIDplus::dbIsolated()->query("insert into ###log_user (log_id, severity, username) values (?, ?, ?)", array((int)$log_id, (int)$severity, $username));
		}

		return true;
	}

}
