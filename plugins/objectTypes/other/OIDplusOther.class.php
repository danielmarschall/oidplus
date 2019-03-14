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

class OIDplusOther extends OIDplusObject {
	private $other;

	public function __construct($other) {
		$this->other = $other;
	}

	public static function parse($node_id) {
		@list($namespace, $other) = explode(':', $node_id, 2);
		if ($namespace !== 'other') return false;
		return new self($other);
	}

	public static function objectTypeTitle() {
		return "Other objects";
	}

	public static function objectTypeTitleShort() {
		return "Object";
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

	public function nodeId() {
		return 'other:'.$this->other;
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

	public function crudInsertPrefix() {
		return '';
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
		$ary = explode('\\', $this->other); // TODO: aber wenn ein arc ein "\" enthält, geht es nicht. besser von db ablesen?
		$ary = array_reverse($ary);
		return $ary[0];
	}

	public function getContentPage(&$title, &$content) {
		if ($this->isRoot()) {
			$title = OIDplusOther::objectTypeTitle();

			$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where parent = '".OIDplus::db()->real_escape_string(self::root())."'");
			if (OIDplus::db()->num_rows($res) > 0) {
				$content  = 'Please select an object in the tree view at the left to show its contents.';
			} else {
				$content  = 'Currently, no misc objects are registered in the system.';
			}

			if (OIDplus::authUtils()::isAdminLoggedIn()) {
				$content .= '<h2>Manage root objects</h2>';
			} else {
				$content .= '<h2>Available objects</h2>';
			}
			$content .= '%%CRUD%%';
		} else {
			$content = '<h2>Description</h2>%%DESC%%'; // TODO: add more meta information about the object type

			if ($this->userHasWriteRights()) {
				$content .= '<h2>Create or change subsequent objects</h2>';
			} else {
				$content .= '<h2>Subsequent objects</h2>';
			}
			$content .= '%%CRUD%%';
		}
	}
}

OIDplusObject::$registeredObjectTypes[] = 'OIDplusOther';
