<?php

/*
 * VtsLDAPUtils - Simple LDAP helper functions
 * Copyright 2021 - 2023 Daniel Marschall, ViaThinkSoft
 * Revision: 2023-04-09
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

class VtsLDAPUtils {

	protected $conn = null;

	private static function _L(string $str, ...$sprintfArgs): string {
		if (function_exists('_L')) {
			return _L($str, $sprintfArgs);
		} else if (function_exists('my_vsprintf')) {
			return my_vsprintf($str, $sprintfArgs);
		} else {
		        $n = 1;
		        foreach ($sprintfArgs as $val) {
		                $str = str_replace("%$n", $val, $str);
		                $n++;
		        }
		        $str = str_replace("%%", "%", $str);
		        return $str;
		}
	}

	public static function getString($ldap_userinfo, $attributeName) {
		$ary = self::getArray($ldap_userinfo, $attributeName);
		return implode("\n", $ary);
	}

	public static function getArray($ldap_userinfo, $attributeName) {
		$ary = array();
		if (isset($ldap_userinfo[$attributeName])) {
			$cnt = $ldap_userinfo[$attributeName]['count'];
			for ($i=0; $i<$cnt; $i++) {
				$ary[] = $ldap_userinfo[$attributeName][$i];
			}
		}
		return $ary;
	}

	public function isMemberOfRec($userDN, $groupDN) {

		if (isset($userDN['dn'])) $userDN = $userDN['dn'];
		if (isset($groupDN['dn'])) $groupDN = $groupDN['dn'];

		if (!$this->conn) throw new Exception('LDAP not connected');
		$res = ldap_read($this->conn, $groupDN, "(objectClass=*)");
		if (!$res) return false;
		$entries = ldap_get_entries($this->conn, $res);
		if (!isset($entries[0])) return false;
		if (!isset($entries[0]['member'])) return false;
		if (!isset($entries[0]['member']['count'])) return false;
		$cntMember = $entries[0]['member']['count'];
		for ($iMember=0; $iMember<$cntMember; $iMember++) {
			$groupOrUser = $entries[0]['member'][$iMember];
			if (strtolower($groupOrUser) == strtolower($userDN)) return true;
			if ($this->isMemberOfRec($userDN, $groupOrUser)) return true;
		}
		return false;
	}

	public function __destruct() {
		$this->disconnect();
	}

	public function disconnect() {
		if ($this->conn) {
			//ldap_unbind($this->conn); // commented out because ldap_unbind() kills the link descriptor
			ldap_close($this->conn);
			$this->conn = null;
		}
	}

	public function connect($cfg_ldap_server, $cfg_ldap_port) {
		$this->disconnect();

		// Connect to the server
		if (!empty($cfg_ldap_port)) {
			if (!($ldapconn = @ldap_connect($cfg_ldap_server, $cfg_ldap_port))) throw new Exception(self::_L('Cannot connect to LDAP server'));
		} else {
			if (!($ldapconn = @ldap_connect($cfg_ldap_server))) throw new Exception(self::_L('Cannot connect to LDAP server'));
		}
		ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

		$this->conn = $ldapconn;
	}

	public function login($username, $password) {
		return @ldap_bind($this->conn, $username, $password);
	}

	public function getUserInfo($userPrincipalName, $cfg_ldap_base_dn) {
		$cfg_ldap_user_filter = "(&(objectClass=user)(objectCategory=person)(userPrincipalName=".ldap_escape($userPrincipalName, '', LDAP_ESCAPE_FILTER)."))";

		if (!($result = @ldap_search($this->conn,$cfg_ldap_base_dn, $cfg_ldap_user_filter))) throw new Exception(self::_L('Error in search query: %1', ldap_error($this->conn)));
		$data = ldap_get_entries($this->conn, $result);
		$ldap_userinfo = array();

		if ($data['count'] == 0) return false; /* @phpstan-ignore-line */
		$ldap_userinfo = $data[0];

		// empty($ldap_userinfo) can happen if the user did not log-in using their correct userPrincipalName (e.g. "username@domainname" instead of "username@domainname.local")
		return empty($ldap_userinfo) ? false : $ldap_userinfo;
	}

}
