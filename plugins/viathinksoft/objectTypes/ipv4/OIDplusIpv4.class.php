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
			if (!ipv4_valid($bare)) throw new OIDplusException(_L('Invalid IPv4'));
			if (!is_numeric($cidr)) throw new OIDplusException(_L('Invalid IPv4'));
			if ($cidr < 0) throw new OIDplusException(_L('Invalid IPv4'));
			if ($cidr > 32) throw new OIDplusException(_L('Invalid IPv4'));
			$this->bare = ipv4_normalize($this->bare);
		}
	}

	public static function parse($node_id) {
		@list($namespace, $ipv4) = explode(':', $node_id, 2);
		if ($namespace !== 'ipv4') return false;
		return new self($ipv4);
	}

	public static function objectTypeTitle() {
		return _L('IPv4 Network Blocks');
	}

	public static function objectTypeTitleShort() {
		return _L('IPv4');
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

	public function nodeId($with_ns=true) {
		return $with_ns ? 'ipv4:'.$this->ipv4 : $this->ipv4;
	}

	public function addString($str) {
		if (strpos($str, '/') === false) $str .= "/32";

		if (!$this->isRoot()) {
			if (!ipv4_in_cidr($this->bare.'/'.$this->cidr, $str)) {
				throw new OIDplusException(_L('Cannot add this address, because it must be inside the address range of the superior range.'));
			}
		}

		list($ipv4, $cidr) = explode('/', $str);
		if ($cidr < 0) throw new OIDplusException(_L('Invalid IPv4 address %1',$str));
		if ($cidr > 32) throw new OIDplusException(_L('Invalid IPv4 address %1',$str));
		$ipv4_normalized = ipv4_normalize($ipv4);
		if (!$ipv4_normalized) throw new OIDplusException(_L('Invalid IPv4 address %1',$str));
		return 'ipv4:'.$ipv4_normalized.'/'.$cidr; // overwrite; no hierarchical tree
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
		$icon = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webPath(__DIR__,true).'icon_big.png' : '';

		if ($this->isRoot()) {
			$title = OIDplusIpv4::objectTypeTitle();

			$res = OIDplus::db()->query("select * from ###objects where parent = ?", array(self::root()));
			if ($res->num_rows() > 0) {
				$content  = _L('Please select a network block in the tree view at the left to show its contents.');
			} else {
				$content  = _L('Currently, no network blocks are registered in the system.');
			}

			if (!$this->isLeafNode()) {
				if (OIDplus::authUtils()->isAdminLoggedIn()) {
					$content .= '<h2>'._L('Manage root objects').'</h2>';
				} else {
					$content .= '<h2>'._L('Available objects').'</h2>';
				}
				$content .= '%%CRUD%%';
			}
		} else {
			$title = $this->getTitle();

			$content = '<h2>'._L('Technical information').'</h2>';

			$content .= '<p>'._L('IPv4/CIDR').': <code>' . ipv4_normalize($this->bare) . '/' . $this->cidr . '</code><br>';
			if ($this->cidr < 32) {
				$content .= _L('First address').': <code>' . ipv4_cidr_min_ip($this->bare . '/' . $this->cidr) . '</code><br>';
				$content .= _L('Last address').': <code>' . ipv4_cidr_max_ip($this->bare . '/' . $this->cidr) . '</code></p>';
			} else {
				$content .= _L('Single host address').'</p>';
			}

			$content .= '<h2>'._L('Description').'</h2>%%DESC%%';

			if (!$this->isLeafNode()) {
				if ($this->userHasWriteRights()) {
					$content .= '<h2>'._L('Create or change subsequent objects').'</h2>';
				} else {
					$content .= '<h2>'._L('Subsequent objects').'</h2>';
				}
				$content .= '%%CRUD%%';
			}
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

	public function getDirectoryName() {
		if ($this->isRoot()) return $this->ns();
		$bare = str_replace('.','_',ipv4_normalize($this->bare));
		if ($this->isLeafNode()) {
			return $this->ns().'_'.$bare;
		} else {
			return $this->ns().'_'.$bare.'__'.$this->cidr;
		}
	}
}