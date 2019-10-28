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

if (!defined('IN_OIDPLUS')) die();

class OIDplusDoi extends OIDplusObject {
	private $doi;

	public function __construct($doi) {
		// TODO: syntax checks
		$this->doi = $doi;
	}

	public static function parse($node_id) {
		@list($namespace, $doi) = explode(':', $node_id, 2);
		if ($namespace !== 'doi') return false;
		return new self($doi);
	}

	public static function objectTypeTitle() {
		return "Digital Object Identifier (DOI)";
	}

	public static function objectTypeTitleShort() {
		return "DOI";
	}

	public static function ns() {
		return 'doi';
	}

	public static function root() {
		return 'doi:';
	}

	public function isRoot() {
		return $this->doi == '';
	}

	public function nodeId() {
		return 'doi:'.$this->doi;
	}

	public function addString($str) {
		if ($this->isRoot()) {
			// Parent is root, so $str is the base DOI (10.xxxx)
			$base = $str;
			if (!self::validBaseDoi($base)) {
				throw new Exception("Invalid DOI $base . It must have syntax 10.xxxx");
			}
			return 'doi:' . $base;
		} else if (self::validBaseDoi($this->doi)) {
			// First level: We add a pubilcation to the base
			return 'doi:' . $this->doi . '/' . $str;
		} else {
			// We just add an additional string to the already existing publication, e.g. a graphic reference or chapter
			return 'doi:' . $this->doi . $str;
		}
	}

	public function crudShowId(OIDplusObject $parent) {
		return $this->doi;
	}

	public function crudInsertPrefix() {
		return $this->isRoot() ? '' : substr($this->addString(''), strlen(self::ns())+1);
	}

	public function jsTreeNodeName(OIDplusObject $parent = null) {
		if ($parent == null) return $this->objectTypeTitle();
		$out = $this->doi;
		$ary = explode('/', $out, 2);
		if (count($ary) > 1) $out = $ary[1];
		return $out;
	}

	public function defaultTitle() {
		return 'DOI ' . $this->doi;
	}

	public function isLeafNode() {
		return false;
	}

	public function getContentPage(&$title, &$content, &$icon) {
		$icon = file_exists(__DIR__.'/icon_big.png') ? 'plugins/objectTypes/'.basename(__DIR__).'/icon_big.png' : '';

		if ($this->isRoot()) {
			$title = OIDplusDoi::objectTypeTitle();

			$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where parent = ?", array(self::root()));
			if (OIDplus::db()->num_rows($res) > 0) {
				$content = 'Please select an DOI in the tree view at the left to show its contents.';
			} else {
				$content = 'Currently, no DOIs are registered in the system.';
			}

			if (!$this->isLeafNode()) {
				if (OIDplus::authUtils()::isAdminLoggedIn()) {
					$content .= '<h2>Manage your DOIs</h2>';
				} else {
					$content .= '<h2>Available DOIs</h2>';
				}
				$content .= '%%CRUD%%';
			}
		} else {
			$title = $this->getTitle();

			$pure = explode(':',$this->nodeId())[1];
			$content = '<h3><a target="_blank" href="https://dx.doi.org/'.htmlentities($pure).'">Resolve '.htmlentities($pure).'</a></h3>';

			$content .= '<h2>Description</h2>%%DESC%%'; // TODO: add more meta information about the object type

			if (!$this->isLeafNode()) {
				if ($this->userHasWriteRights()) {
					$content .= '<h2>Create or change subsequent objects</h2>';
				} else {
					$content .= '<h2>Subsequent objects</h2>';
				}
				$content .= '%%CRUD%%';
			}
		}
	}

	# ---

	public static function validBaseDoi($doi) {
		return preg_match('@^10\.\d{4}$@', $doi, $m);
	}

	public function one_up() {
		$oid = $this->doi;

		$p = strrpos($oid, '/');
		if ($p === false) return $oid;
		if ($p == 0) return '/';

		$oid_up = substr($oid, 0, $p);

		return self::parse(self::ns().':'.$oid_up);
	}

	public function distance($to) {
		if (!is_object($to)) $to = OIDplusObject::parse($to);
		if (!($to instanceof $this)) return false;

		$a = $to->doi;
		$b = $this->doi;

		if (substr($a,0,1) == '/') $a = substr($a,1);
		if (substr($b,0,1) == '/') $b = substr($b,1);

		$ary = explode('/', $a);
		$bry = explode('/', $b);

		$min_len = min(count($ary), count($bry));

		for ($i=0; $i<$min_len; $i++) {
			if ($ary[$i] != $bry[$i]) return false;
		}

		return count($ary) - count($bry);
	}
}

OIDplus::registerObjectType('OIDplusDoi');
