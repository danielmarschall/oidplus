<?php

/*
 * OIDplus 2.0
 * Copyright 2019 Daniel Marschall, ViaThinkSoft
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

class OIDplusLogger {

	private static function split_maskcodes($maskcodes) {
		$out = array();

		$code = '';
		$bracket_level = 0;
		for ($i=0; $i<strlen($maskcodes); $i++) {
			$char = $maskcodes[$i];
			if ($char == '(') $bracket_level++;
			if ($char == ')') {
				$bracket_level--;
				if ($bracket_level < 0) return false;
			}
			if ((($char == '+') || ($char == '/')) && ($bracket_level == 0)) {
				$out[] = $code;
				$code = '';
			} else {
				$code .= $char;
			}
		}
		if ($code != '') $out[] = $code;

		return $out;
	}

	public static function log($maskcodes, $event) {

		$users = array();
		$objects = array();

		$maskcodes_ary = self::split_maskcodes($maskcodes);
		if ($maskcodes_ary === false) {
			throw new OIDplusException("Invalid maskcode '$maskcodes'");
		}
		foreach ($maskcodes_ary as $maskcode) {
			// OID(x)	Save log entry into the logbook of: Object "x"
			if (preg_match('@^OID\((.+)\)$@ismU', $maskcode, $m)) {
				$object_id = $m[1];
				$objects[] = $object_id;
				if ($object_id == '') throw new OIDplusException("OID logger mask requires OID");
			}

			// OIDRA(x)?	Save log entry into the logbook of: Logged in RA of object "x"
			// Replace ? by ! if the entity does not need to be logged in
			else if (preg_match('@^OIDRA\((.+)\)([\?\!])$@ismU', $maskcode, $m)) {
				$object_id         = $m[1];
				$ra_need_login     = $m[2] == '?';
				if ($object_id == '') throw new OIDplusException("OIDRA logger mask requires OID");
				$obj = OIDplusObject::parse($object_id);
				if ($obj) {
					if ($ra_need_login) {
						foreach (OIDplus::authUtils()->loggedInRaList() as $ra) {
							if ($obj->userHasWriteRights($ra)) $users[] = $ra->raEmail();
						}
					} else {
						// $users[] = $obj->getRa()->raEmail();
						foreach (OIDplusRA::getAllRAs() as $ra) {
							if ($obj->userHasWriteRights($ra)) $users[] = $ra->raEmail();
						}
					}
				}
			}

			// SUPOIDRA(x)?	Save log entry into the logbook of: Logged in RA that owns the superior object of "x"
			// Replace ? by ! if the entity does not need to be logged in
			else if (preg_match('@^SUPOIDRA\((.+)\)([\?\!])$@ismU', $maskcode, $m)) {
				$object_id         = $m[1];
				$ra_need_login     = $m[2] == '?';
				if ($object_id == '') throw new OIDplusException("SUPOIDRA logger mask requires OID");
				$obj = OIDplusObject::parse($object_id);
				if ($obj) {
					if ($ra_need_login) {
						foreach (OIDplus::authUtils()->loggedInRaList() as $ra) {
							if ($obj->userHasParentalWriteRights($ra)) $users[] = $ra->raEmail();
						}
					} else {
						// $users[] = $obj->getParent()->getRa()->raEmail();
						foreach (OIDplusRA::getAllRAs() as $ra) {
							if ($obj->userHasParentalWriteRights($ra)) $users[] = $ra->raEmail();
						}
					}
				}
			}

			// RA(x)?	Save log entry into the logbook of: Logged in RA "x"
			// Replace ? by ! if the entity does not need to be logged in
			else if (preg_match('@^RA\((.+)\)([\?\!])$@ismU', $maskcode, $m)) {
				$ra_email          = $m[1];
				$ra_need_login     = $m[2] == '?';
				if ($ra_need_login && OIDplus::authUtils()->isRaLoggedIn($ra_email)) {
					$users[] = $ra_email;
				} else if (!$ra_need_login) {
					$users[] = $ra_email;
				}
			}

			// A?	Save log entry into the logbook of: A logged in admin
			// Replace ? by ! if the entity does not need to be logged in
			else if (preg_match('@^A([\?\!])$@ismU', $maskcode, $m)) {
				$admin_need_login = $m[1] == '?';
				if ($admin_need_login && OIDplus::authUtils()->isAdminLoggedIn()) {
					$users[] = 'admin';
				} else if (!$admin_need_login) {
					$users[] = 'admin';
				}
			}

			// Unexpected
			else {
				throw new OIDplusException("Unexpected logger mask code '$maskcode'");
			}
		}

		// Now write the log message

		$addr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
		OIDplus::db()->query("insert into ###log (addr, unix_ts, event) values (?, ?, ?)", array($addr, time(), $event));
		$log_id = OIDplus::db()->insert_id();
		if ($log_id === false) {
			$res = OIDplus::db()->query("select max(id) as last_id from ###log");
			if ($res->num_rows() == 0) throw new OIDplusException("Could not log event");
			$row = $res->fetch_array();
			$log_id = $row['last_id'];
		}

		foreach ($objects as $object) {
			OIDplus::db()->query("insert into ###log_object (log_id, object) values (?, ?)", array($log_id, $object));
		}

		foreach ($users as $username) {
			OIDplus::db()->query("insert into ###log_user (log_id, username) values (?, ?)", array($log_id, $username));
		}

	}
}
