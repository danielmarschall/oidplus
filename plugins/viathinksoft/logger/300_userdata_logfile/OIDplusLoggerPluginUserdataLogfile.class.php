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

namespace ViaThinkSoft\OIDplus;

class OIDplusLoggerPluginUserdataLogfile extends OIDplusLoggerPlugin {

	public static function available(&$reason)/*: bool*/ {
		if (!is_dir(OIDplus::localpath().'userdata/logs/')) {
			$reason = _L('Directory userdata/logs/ not existing');
			return false;
		}

		if (@file_put_contents(OIDplus::localpath().'userdata/logs/oidplus.log', '', FILE_APPEND) === false) {
			$reason = _L('File userdata/logs/oidplus.log not writeable');
			return false;
		}

		$reason = '';
		return true;
	}

	public static function log($event, $users, $objects)/*: bool*/ {
		if (!is_dir(OIDplus::localpath().'userdata/logs/')) return false;

		$users_names = array();
		foreach ($users as list($severity, $username)) $users_names[] = $username;
		$users_info = count($users_names) == 0 ? '' : ' ('._L('affected users: %1',implode(', ',$users_names)).')';

		$objects_names = array();
		foreach ($objects as list($severity, $objectname)) $objects_names[] = $objectname;
		$objects_info = count($objects_names) == 0 ? '' : ' ('._L('affected objects: %1',implode(', ',$objects_names)).')';

		$ts = date('Y-m-d H:i:s');
		$addr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : _L('unknown');

		// Note: $ts was put into brackets, because there is probably a bug in fail2ban that does not allow the date/time being at offset 0
		// "WARNING Found a match for '020-05-11 22:50:58 [192.168.69.89] Failed login ..."
		$line = "[$ts] [$addr] $event$users_info$objects_info";

		return @file_put_contents(OIDplus::localpath().'userdata/logs/oidplus.log', "$line\n", FILE_APPEND) !== false;
	}
}