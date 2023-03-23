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

class OIDplusIpv6 extends OIDplusObject {
	private $ipv6;
	private $bare;
	private $cidr;

	/**
	 * @param $ipv6
	 * @throws OIDplusException
	 */
	public function __construct($ipv6) {
		$this->ipv6 = $ipv6;

		if (!empty($ipv6)) {
			if (strpos($ipv6, '/') === false) $ipv6 .= '/128';
			list($bare, $cidr) = explode('/', $ipv6);
			$this->bare = $bare;
			$this->cidr = $cidr;
			if (!ipv6_valid($bare)) throw new OIDplusException(_L('Invalid IPv6'));
			if (!is_numeric($cidr)) throw new OIDplusException(_L('Invalid IPv6'));
			if ($cidr < 0) throw new OIDplusException(_L('Invalid IPv6'));
			if ($cidr > 128) throw new OIDplusException(_L('Invalid IPv6'));
			$this->bare = ipv6_normalize($this->bare);
			$this->ipv6 = $this->bare . '/' . $this->cidr;
		}
	}

	/**
	 * @param string $node_id
	 * @return OIDplusIpv6|null
	 */
	public static function parse(string $node_id)/*: ?OIDplusIpv6*/ {
		@list($namespace, $ipv6) = explode(':', $node_id, 2);
		if ($namespace !== self::ns()) return null;
		return new self($ipv6);
	}

	/**
	 * @return string
	 */
	public static function objectTypeTitle(): string {
		return _L('IPv6 Network Blocks');
	}

	/**
	 * @return string
	 */
	public static function objectTypeTitleShort(): string {
		return _L('IPv6');
	}

	/**
	 * @return string
	 */
	public static function ns(): string {
		return 'ipv6';
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
		return $this->ipv6 == '';
	}

	/**
	 * @param bool $with_ns
	 * @return string
	 */
	public function nodeId(bool $with_ns=true): string {
		return $with_ns ? self::root().$this->ipv6 : $this->ipv6;
	}

	/**
	 * @param string $str
	 * @return string
	 * @throws OIDplusException
	 */
	public function addString(string $str): string {
		if (strpos($str, '/') === false) $str .= "/128";

		if (!$this->isRoot()) {
			if (!ipv6_in_cidr($this->bare.'/'.$this->cidr, $str)) {
				throw new OIDplusException(_L('Cannot add this address, because it must be inside the address range of the superior range.'));
			}
		}

		list($ipv6, $cidr) = explode('/', $str);
		if ($cidr < 0) throw new OIDplusException(_L('Invalid IPv6 address %1',$str));
		if ($cidr > 128) throw new OIDplusException(_L('Invalid IPv6 address %1',$str));
		$ipv6_normalized = ipv6_normalize($ipv6);
		if (!$ipv6_normalized) throw new OIDplusException(_L('Invalid IPv6 address %1',$str));
		return self::root().$ipv6_normalized.'/'.$cidr; // overwrite; no hierarchical tree
	}

	/**
	 * @param OIDplusObject $parent
	 * @return string
	 */
	public function crudShowId(OIDplusObject $parent): string {
		return $this->ipv6;
	}

	/**
	 * @param OIDplusObject|null $parent
	 * @return string
	 */
	public function jsTreeNodeName(OIDplusObject $parent = null): string {
		if ($parent == null) return $this->objectTypeTitle();
		return $this->ipv6;
	}

	/**
	 * @return string
	 */
	public function defaultTitle(): string {
		return $this->ipv6;
	}

	/**
	 * @return bool
	 */
	public function isLeafNode(): bool {
		return $this->cidr >= 128;
	}

	/**
	 * @return array
	 */
	private function getTechInfo(): array {
		if ($this->isRoot()) return array();

		$tech_info = array();

		$tech_info[_L('IPv6/CIDR')] = ipv6_normalize($this->bare) . '/' . $this->cidr;
		if ($this->cidr < 128) {
			$tech_info[_L('First address')] = ipv6_cidr_min_ip($this->bare . '/' . $this->cidr);
			$tech_info[_L('Last address')]  = ipv6_cidr_max_ip($this->bare . '/' . $this->cidr);
		}

		return $tech_info;
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
			$title = OIDplusIpv6::objectTypeTitle();

			$res = OIDplus::db()->query("select * from ###objects where parent = ?", array(self::root()));
			if ($res->any()) {
				$content  = '<p>'._L('Please select a network block in the tree view at the left to show its contents.').'</p>';
			} else {
				$content  = '<p>'._L('Currently, no network blocks are registered in the system.').'</p>';
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

			$tech_info = $this->getTechInfo();
			$tech_info_html = '';
			if (count($tech_info) > 0) {
				$tech_info_html .= '<h2>'._L('Technical information').'</h2>';
				$tech_info_html .= '<table border="0">';
				foreach ($tech_info as $key => $value) {
					$tech_info_html .= '<tr><td>'.$key.': </td><td><code>'.$value.'</code></td></tr>';
				}
				$tech_info_html .= '</table>';
			}
			if ($this->cidr == 128) $tech_info_html .= _L('Single host address');

			$content = $tech_info_html;

			$content .= '<h2>'._L('Description').'</h2>%%DESC%%';

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

	/**
	 * @return OIDplusIpv6|null
	 */
	public function one_up()/*: ?OIDplusIpv6*/ {
		$cidr = $this->cidr - 1;
		if ($cidr < 0) return null; // cannot go further up

		$tmp = ipv6_normalize_range($this->bare . '/' . $cidr);
		return self::parse($this->ns() . ':' . $tmp);
	}

	/**
	 * @param $to
	 * @return float|int|mixed|string|null
	 */
	public function distance($to) {
		if (!is_object($to)) $to = OIDplusObject::parse($to);
		if (!($to instanceof $this)) return null;
		$res = ipv6_distance($to->ipv6, $this->ipv6);
		return $res !== false ? $res : null;
	}

	/**
	 * @return string
	 */
	public function getDirectoryName(): string {
		if ($this->isRoot()) return $this->ns();
		$bare = str_replace(':','_',ipv6_normalize($this->bare));
		if ($this->isLeafNode()) {
			return $this->ns().'_'.$bare;
		} else {
			return $this->ns().'_'.$bare.'__'.$this->cidr;
		}
	}

	/**
	 * @param string $mode
	 * @return string
	 */
	public static function treeIconFilename(string $mode): string {
		return 'img/'.$mode.'_icon16.png';
	}
}
