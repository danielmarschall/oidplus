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

class OIDplusDomain extends OIDplusObject {
	private $domain;

	public function __construct($domain) {
		// TODO: syntax checks
		$this->domain = $domain;
	}

	public static function parse($node_id) {
		@list($namespace, $domain) = explode(':', $node_id, 2);
		if ($namespace !== 'domain') return false;
		return new self($domain);
	}

	public static function objectTypeTitle() {
		return _L('Domain Names');
	}

	public static function objectTypeTitleShort() {
		return _L('Domain');
	}

	public static function ns() {
		return 'domain';
	}

	public static function root() {
		return 'domain:';
	}

	public function isRoot() {
		return $this->domain == '';
	}

	public function nodeId($with_ns=true) {
		return $with_ns ? 'domain:'.$this->domain : $this->domain;
	}

	public function addString($str) {
		if ($this->isRoot()) {
			return 'domain:'.$str;
		} else {
			if (strpos($str,'.') !== false) throw new OIDplusException(_L('Please only submit one arc.'));
			return 'domain:'.$str.'.'.$this->nodeId(false);
		}
	}

	public function crudShowId(OIDplusObject $parent) {
		return $this->domain;
	}

	public function crudInsertSuffix() {
		return $this->isRoot() ? '' : substr($this->addString(''), strlen(self::ns())+1);
	}

	public function jsTreeNodeName(OIDplusObject $parent = null) {
		if ($parent == null) return $this->objectTypeTitle();
		return $this->domain;
	}

	public function defaultTitle() {
		return $this->domain;
	}

	public function isLeafNode() {
		return false;
	}

	public function getContentPage(&$title, &$content, &$icon) {
		$icon = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webPath(__DIR__,true).'icon_big.png' : '';

		if ($this->isRoot()) {
			$title = OIDplusDomain::objectTypeTitle();

			$res = OIDplus::db()->query("select * from ###objects where parent = ?", array(self::root()));
			if ($res->num_rows() > 0) {
				$content  = _L('Please select a Domain Name in the tree view at the left to show its contents.');
			} else {
				$content  = _L('Currently, no Domain Name is registered in the system.');
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

			$content = '<h3>'.explode(':',$this->nodeId())[1].'</h3>';

			$content .= '<h2>'._L('Description').'</h2>%%DESC%%'; // TODO: add more meta information about the object type

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
		$oid = $this->domain;

		$p = strpos($oid, '.');
		if ($p === false) return self::parse('');

		$oid_up = substr($oid, $p+1);

		return self::parse(self::ns().':'.$oid_up);
	}

	public function distance($to) {
		if (!is_object($to)) $to = OIDplusObject::parse($to);
		if (!($to instanceof $this)) return false;

		$a = $to->domain;
		$b = $this->domain;

		if (substr($a,-1) == '.') $a = substr($a,0,strlen($a)-1);
		if (substr($b,-1) == '.') $b = substr($b,0,strlen($b)-1);

		$ary = explode('.', $a);
		$bry = explode('.', $b);

		$ary = array_reverse($ary);
		$bry = array_reverse($bry);

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
