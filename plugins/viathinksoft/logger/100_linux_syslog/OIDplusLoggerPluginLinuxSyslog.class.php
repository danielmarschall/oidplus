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

class OIDplusLoggerPluginLinuxSyslog extends OIDplusLoggerPlugin {

	/**
	 * @param string $reason
	 * @return bool
	 */
	public static function available(string &$reason): bool {
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$reason = _L('Functionality not available on Windows');
			return false;
		}

		if (!@file_exists('/var/log/syslog')) {
			$reason = _L('File %1 does not exist','/var/log/syslog');
			return false;
		}

		if (@file_put_contents('/var/log/syslog', '', FILE_APPEND) === false) {
			$reason = _L('File %1 is not writeable','/var/log/syslog');
			return false;
		}

		$reason = '';
		return true;
	}

	/**
	 * @param string $event
	 * @param array $users
	 * @param array $objects
	 * @return bool
	 */
	public static function log(string $event, array $users, array $objects): bool {
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') return false;

		if (!@file_exists('/var/log/syslog')) return false;

		$users_names = array();
		foreach ($users as list($severity, $username)) $users_names[] = $username;
		$users_info = count($users_names) == 0 ? '' : ' ('._L('affected users: %1',implode(', ',$users_names)).')';

		$objects_names = array();
		foreach ($objects as list($severity, $objectname)) $objects_names[] = $objectname;
		$objects_info = count($objects_names) == 0 ? '' : ' ('._L('affected objects: %1',implode(', ',$objects_names)).')';

		$ts = date('Y-m-d H:i:s');
		$addr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : _L('unknown');
		$line = "[$ts] [$addr] $event$users_info$objects_info";

		return @file_put_contents('/var/log/syslog', "$line\n", FILE_APPEND) !== false;
	}
}