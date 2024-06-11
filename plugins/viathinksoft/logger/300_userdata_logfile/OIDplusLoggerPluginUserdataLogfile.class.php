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

class OIDplusLoggerPluginUserdataLogfile extends OIDplusLoggerPlugin {

	/**
	 * @param string $reason
	 * @return bool
	 */
	public function available(string &$reason): bool {
		if (!is_dir($dir = OIDplus::getUserDataDir("logs"))) {
			$reason = _L('Directory %1 not existing', $dir);
			return false;
		}

		if (@file_put_contents($dir.'oidplus.log', '', FILE_APPEND) === false) {
			$reason = _L('File %1 not writeable', $dir.'oidplus.log');
			return false;
		}

		$reason = '';
		return true;
	}

	/**
	 * @param OIDplusLogEvent $event
	 * @return bool
	 */
	public function log(OIDplusLogEvent $event): bool {
		if (!is_dir(OIDplus::getUserDataDir("logs"))) return false;

		$users_names = array();
		$objects_names = array();
		foreach ($event->getTargets() as $target) {
			if ($target instanceof OIDplusLogTargetUser) {
				$users_names[] = $target->getUsername();
			} else if ($target instanceof OIDplusLogTargetObject) {
				$objects_names[] = $target->getObject();
			} else {
				assert(false);
			}
		}
		$users_info = count($users_names) == 0 ? '' : ' ('._L('affected users: %1',implode(', ',$users_names)).')';
		$objects_info = count($objects_names) == 0 ? '' : ' ('._L('affected objects: %1',implode(', ',$objects_names)).')';

		$ts = date('Y-m-d H:i:s');
		$addr = OIDplus::getClientIpAddress() ?: _L('unknown');

		// Note: $ts was put into brackets, because there is probably a bug in fail2ban that does not allow the date/time being at offset 0
		// "WARNING Found a match for '020-05-11 22:50:58 [192.168.69.89] Failed login ..."
		$line = "[$ts] [$addr] ".$event->getMessage().$users_info.$objects_info;

		return @file_put_contents(OIDplus::getUserDataDir("logs").'oidplus.log', "$line\n", FILE_APPEND) !== false;
	}
}