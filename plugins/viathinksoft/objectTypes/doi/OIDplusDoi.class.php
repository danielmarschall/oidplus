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

namespace ViaThinkSoft\OIDplus\Plugins\ObjectTypes\DOI;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusObject;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusDoi extends OIDplusObject {
	/**
	 * @var string
	 */
	private $doi;

	/**
	 * @param string $doi
	 */
	public function __construct(string $doi) {
		// TODO: syntax checks
		$this->doi = $doi;
	}

	/**
	 * @param string $node_id
	 * @return OIDplusDoi|null
	 */
	public static function parse(string $node_id): ?OIDplusDoi {
		@list($namespace, $doi) = explode(':', $node_id, 2);
		if ($namespace !== self::ns()) return null;
		return new self($doi);
	}

	/**
	 * @return string
	 */
	public static function objectTypeTitle(): string {
		return _L('Digital Object Identifier (DOI)');
	}

	/**
	 * @return string
	 */
	public static function objectTypeTitleShort(): string {
		return _L('DOI');
	}

	/**
	 * @return string
	 */
	public static function ns(): string {
		return 'doi';
	}

	/**
	 * @return string
	 */
	public static function root(): string {
		return self::ns().':';
	}

	/**
	 * @return bool
	 */
	public function isRoot(): bool {
		return $this->doi == '';
	}

	/**
	 * @param bool $with_ns
	 * @return string
	 */
	public function nodeId(bool $with_ns=true): string {
		return $with_ns ? self::root().$this->doi : $this->doi;
	}

	/**
	 * @param string $str
	 * @return string
	 * @throws OIDplusException
	 */
	public function addString(string $str): string {
		if ($this->isRoot()) {
			// Parent is root, so $str is the base DOI (10.xxxx)
			$base = $str;
			if (!self::validBaseDoi($base)) {
				throw new OIDplusException(_L('Invalid DOI %1 . It must have syntax 10.xxxx',$base));
			}
			return self::root() . $base;
		} else if (self::validBaseDoi($this->doi)) {
			// First level: We add a pubilcation to the base
			return self::root() . $this->doi . '/' . $str;
		} else {
			// We just add an additional string to the already existing publication, e.g. a graphic reference or chapter
			return self::root() . $this->doi . $str;
		}
	}

	/**
	 * @param OIDplusObject $parent
	 * @return string
	 */
	public function crudShowId(OIDplusObject $parent): string {
		return $this->doi;
	}

	/**
	 * @return string
	 * @throws OIDplusException
	 */
	public function crudInsertPrefix(): string {
		return $this->isRoot() ? '' : substr($this->addString(''), strlen(self::ns())+1);
	}

	/**
	 * @param OIDplusObject|null $parent
	 * @return string
	 */
	public function jsTreeNodeName(?OIDplusObject $parent=null): string {
		if ($parent == null) return $this->objectTypeTitle();
		$out = $this->doi;
		$ary = explode('/', $out, 2);
		if (count($ary) > 1) $out = $ary[1];
		return $out;
	}

	/**
	 * @return string
	 */
	public function defaultTitle(): string {
		return _L('DOI %1',$this->doi);
	}

	/**
	 * @return bool
	 */
	public function isLeafNode(): bool {
		return false;
	}

	/**
	 * @param string $title
	 * @param string $content
	 * @param string $icon
	 * @return void
	 * @throws OIDplusException
	 */
	public function getContentPage(string &$title, string &$content, string &$icon) {
		$icon = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

		if ($this->isRoot()) {
			$title = OIDplusDoi::objectTypeTitle();

			$res = OIDplus::db()->query("select * from ###objects where parent = ?", array(self::root()));
			if ($res->any()) {
				$content = _L('Please select an DOI in the tree view at the left to show its contents.');
			} else {
				$content = _L('Currently, no DOIs are registered in the system.');
			}

			if (!$this->isLeafNode()) {
				if (OIDplus::authUtils()->isAdminLoggedIn()) {
					$content .= '<h2>'._L('Manage your DOIs').'</h2>';
				} else {
					$content .= '<h2>'._L('Available DOIs').'</h2>';
				}
				$content .= '%%CRUD%%';
			}
		} else {
			$title = $this->getTitle();

			$pure = explode(':',$this->nodeId())[1];
			$content = '<h3><a target="_blank" href="https://dx.doi.org/'.htmlentities($pure).'">'._L('Resolve %1',htmlentities($pure)).' </a></h3>';

			if ($this->userHasParentalWriteRights()) {
				$content .= '<h2>'._L('Superior RA Allocation Info').'</h2>%%SUPRA%%';
			}

			$content .= '<h2>'._L('Description').'</h2>%%DESC%%'; // TODO: add more meta information about the object type
			$content .= '<h2>'._L('Registration Authority').'</h2>%%RA_INFO%%';

			if (!$this->isLeafNode()) {
				if ($this->userHasWriteRights()) {
					$content .= '<h2>'._L('Create or change subordinate objects').'</h2>';
				} else {
					$content .= '<h2>'._L('Subordinate objects').'</h2>';
				}
				$content .= '%%CRUD%%';
			}
		}
	}

	# ---

	/**
	 * @param string $doi
	 * @return bool
	 */
	public static function validBaseDoi(string $doi): bool {
		$m = array();
		return preg_match('@^10\.\d{4}$@', $doi, $m);
	}

	/**
	 * @return OIDplusDoi|null
	 */
	public function one_up(): ?OIDplusDoi {
		$oid = $this->doi;

		$p = strrpos($oid, '/');
		if ($p === false) return self::parse($oid);
		if ($p == 0) return self::parse('/');

		$oid_up = substr($oid, 0, $p);

		return self::parse(self::ns().':'.$oid_up);
	}

	/**
	 * @param OIDplusObject|string $to
	 * @return int|null
	 */
	public function distance($to): ?int {
		if (!is_object($to)) $to = OIDplusObject::parse($to);
		if (!$to) return null;
		if (!($to instanceof $this)) return null;

		$a = $to->doi;
		$b = $this->doi;

		if (substr($a,0,1) == '/') $a = substr($a,1);
		if (substr($b,0,1) == '/') $b = substr($b,1);

		$ary = explode('/', $a);
		$bry = explode('/', $b);

		$min_len = min(count($ary), count($bry));

		for ($i=0; $i<$min_len; $i++) {
			if ($ary[$i] != $bry[$i]) return null;
		}

		return count($ary) - count($bry);
	}

	/**
	 * @return string
	 */
	public function getDirectoryName(): string {
		if ($this->isRoot()) return $this->ns();
		return $this->ns().'_'.md5($this->nodeId(false));
	}

	/**
	 * @param string $mode
	 * @return string
	 */
	public static function treeIconFilename(string $mode): string {
		return 'img/'.$mode.'_icon16.png';
	}
}
