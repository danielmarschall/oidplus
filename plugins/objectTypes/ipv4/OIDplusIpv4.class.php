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

class OIDplusIpv4 extends OIDplusObject {
	private $ipv4;
	private $bare;
	private $cidr;

	public function __construct($ipv4) {
		$this->ipv4 = $ipv4;

		if (!empty($ipv4)) {
			if (strpos($ipv4, '/') === false) $ipv4 .= '/32';
			list($bare, $cidr) = explode('/', $ipv4);
			$this->bare = $bare;
			$this->cidr = $cidr;
			if (!ipv4_valid($bare)) throw new Exception("Invalid IPv4");
			if (!is_numeric($cidr)) throw new Exception("Invalid IPv4");
			if ($cidr < 0) throw new Exception("Invalid IPv4");
			if ($cidr > 32) throw new Exception("Invalid IPv4");
		}
	}

	public static function parse($node_id) {
		@list($namespace, $ipv4) = explode(':', $node_id, 2);
		if ($namespace !== 'ipv4') return false;
		return new self($ipv4);
	}

	public static function objectTypeTitle() {
		return "IPv4 Network Blocks";
	}

	public static function objectTypeTitleShort() {
		return "IPv4";
	}

	public static function ns() {
		return 'ipv4';
	}

	public static function root() {
		return 'ipv4:';
	}

	public function isRoot() {
		return $this->ipv4 == '';
	}

	public function nodeId() {
		return 'ipv4:'.$this->ipv4;
	}

	public function addString($str) {
		if (!$this->isRoot()) {
			$test = (strpos($str, '/') === false) ? "$str/32" : $str;
			if (!ipv4_in_cidr($this->bare.'/'.$this->cidr, $test)) {
				throw new Exception("Cannot add this address, because it must be inside the address range of the superior range.");
			}
		}

		return 'ipv4:'.$str; // overwrite; no hierarchical tree
	}

	public function crudShowId(OIDplusObject $parent) {
		return $this->ipv4;
	}

	public function crudInsertPrefix() {
		return '';
	}

	public function jsTreeNodeName(OIDplusObject $parent = null) {
		if ($parent == null) return $this->objectTypeTitle();
		return $this->ipv4;
	}

	public function defaultTitle() {
		return $this->ipv4;
	}

	public function isLeafNode() {
		return $this->cidr >= 32;
	}

	public function getContentPage(&$title, &$content, &$icon) {
		if ($this->isRoot()) {
			$title = OIDplusIpv4::objectTypeTitle();

			$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where parent = '".OIDplus::db()->real_escape_string(self::root())."'");
			if (OIDplus::db()->num_rows($res) > 0) {
				$content  = 'Please select a network block in the tree view at the left to show its contents.';
			} else {
				$content  = 'Currently, no network blocks are registered in the system.';
			}

			if (!$this->isLeafNode()) {
				if (OIDplus::authUtils()::isAdminLoggedIn()) {
					$content .= '<h2>Manage root objects</h2>';
				} else {
					$content .= '<h2>Available objects</h2>';
				}
				$content .= '%%CRUD%%';
			}
		} else {
			$content = '<h2>Technical information</h2>';

			$content .= '<p>IPv4/CIDR: <code>' . ipv4_normalize($this->bare) . '/' . $this->cidr . '</code><br>';
			if ($this->cidr < 32) {
				$content .= 'First address: <code>' . ipv4_cidr_min_ip($this->bare . '/' . $this->cidr) . '</code><br>';
				$content .= 'Last address: <code>' . ipv4_cidr_max_ip($this->bare . '/' . $this->cidr) . '</code></p>';
			} else {
				$content .= 'Single host address</p>';
			}

			$content .= '<h2>Description</h2>%%DESC%%';

			if (!$this->isLeafNode()) {
				if ($this->userHasWriteRights()) {
					$content .= '<h2>Create or change subsequent objects</h2>';
				} else {
					$content .= '<h2>Subsequent objects</h2>';
				}
				$content .= '%%CRUD%%';
			}

			$content .= '<br>%%WHOIS%%';
		}
	}

	public function one_up() {
		$cidr = $this->cidr - 1;
		if ($cidr < 0) return false; // cannot go further up

		$tmp = ipv4_normalize_range($this->bare . '/' . $cidr);
		return self::parse($this->ns() . ':' . $tmp);
	}

	public function distance($to) {
		if (!is_object($to)) $to = OIDplusObject::parse($to);
		if (!($to instanceof $this)) return false;
		return ipv4_distance($to->ipv4, $this->ipv4);
	}
}

OIDplusObject::$registeredObjectTypes[] = 'OIDplusIpv4';

