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

class OIDplusOther extends OIDplusObject {
	private $other;

	public function __construct($other) {
		// No syntax checks
		$this->other = $other;
	}

	public static function parse($node_id) {
		@list($namespace, $other) = explode(':', $node_id, 2);
		if ($namespace !== 'other') return false;
		return new self($other);
	}

	public static function objectTypeTitle() {
		return _L('Other objects');
	}

	public static function objectTypeTitleShort() {
		return _L('Object');
	}

	public static function ns() {
		return 'other';
	}

	public static function root() {
		return 'other:';
	}

	public function isRoot() {
		return $this->other == '';
	}

	public function nodeId($with_ns=true) {
		return $with_ns ? 'other:'.$this->other : $this->other;
	}

	public function addString($str) {
		if ($this->isRoot()) {
			return 'other:'.$str;
		} else {
			return $this->nodeId() . '\\' . $str;
		}
	}

	public function crudShowId(OIDplusObject $parent) {
		if ($parent->isRoot()) {
			return substr($this->nodeId(), strlen($parent->nodeId()));
		} else {
			return substr($this->nodeId(), strlen($parent->nodeId())+1);
		}
	}

	public function jsTreeNodeName(OIDplusObject $parent = null) {
		if ($parent == null) return $this->objectTypeTitle();
		if ($parent->isRoot()) {
			return substr($this->nodeId(), strlen($parent->nodeId()));
		} else {
			return substr($this->nodeId(), strlen($parent->nodeId())+1);
		}
	}

	public function defaultTitle() {
		$ary = explode('\\', $this->other); // TODO: but if an arc contains "\", this does not work. better read from db?
		$ary = array_reverse($ary);
		return $ary[0];
	}

	public function isLeafNode() {
		return false;
	}

	public function getContentPage(&$title, &$content, &$icon) {
		$icon = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webPath(__DIR__,true).'icon_big.png' : '';

		if ($this->isRoot()) {
			$title = OIDplusOther::objectTypeTitle();

			$res = OIDplus::db()->query("select * from ###objects where parent = ?", array(self::root()));
			if ($res->any()) {
				$content  = _L('Please select an object in the tree view at the left to show its contents.');
			} else {
				$content  = _L('Currently, no misc. objects are registered in the system.');
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

			$content = '<h2>'._L('Description').'</h2>%%DESC%%'; // TODO: add more meta information about the object type

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
		$oid = $this->other;

		$p = strrpos($oid, '\\');
		if ($p === false) return self::parse($oid);
		if ($p == 0) return self::parse('\\');

		$oid_up = substr($oid, 0, $p);

		return self::parse(self::ns().':'.$oid_up);
	}

	public function distance($to) {
		if (!is_object($to)) $to = OIDplusObject::parse($to);
		if (!($to instanceof $this)) return false;

		$a = $to->other;
		$b = $this->other;

		if (substr($a,0,1) == '\\') $a = substr($a,1);
		if (substr($b,0,1) == '\\') $b = substr($b,1);

		$ary = explode('\\', $a);
		$bry = explode('\\', $b);

		$min_len = min(count($ary), count($bry));

		for ($i=0; $i<$min_len; $i++) {
			if ($ary[$i] != $bry[$i]) return false;
		}

		return count($ary) - count($bry);
	}

	public function getDirectoryName() {
		if ($this->isRoot()) return $this->ns();
		return $this->ns().'_'.md5($this->nodeId(false));
	}
}
