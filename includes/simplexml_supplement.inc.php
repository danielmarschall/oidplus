<?php

/*
 * PHP SimpleXML-Supplement
 * Copyright 2020 - 2021 Daniel Marschall, ViaThinkSoft
 * Revision 2021-05-25
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */


// ======== ATTENTION, PLEASE READ ========
// This supplement script was created to support rare PHP installations that
// do not contain SimpleXML, for example at PHP you need to explicitly
// install the package "php-xml" if you want to have SimpleXML (In the PHP
// documentation, it is written that SimpleXML is available to all, which is
// not true).
//
// Beware that the supplement behaves differently than the real SimpleXML!
// (If you know how to improve this, please feel free to send me a patch)
//
// Just a few differences towards the original SimpleXML
// - print_r() looks different
// - The supplement requires that an XML string begins with "<!DOCTYPE" or "<?xml",
//   otherwise, the first element will not be stripped away
// - The supplement is slow because of regular expressions
// - Many functions like "asXML" are not implemented
// - There might be other incompatibilities
//
// So, if you want to use the SimpleXML supplement, then please carefully
// test it with your application if it works.
// ========================================

if (!function_exists('simplexml_load_string')) {

	// We cannot store the number 0, 1, 2, ... as items in the SimpleXMLElement, because PHP 7.0 had a bug
	// that prevented /get_object_vars() from working correctly
	// https://stackoverflow.com/questions/46000541/get-object-vars-returning-different-results-depending-on-php-version
	// https://stackoverflow.com/questions/4914376/failed-to-get-dynamic-instance-variables-via-phps-reflection/4914405#comment76610293_4914405
	define('SIMPLEXML_SUPPLEMENT_MAGIC', '_SIMPLEXML_SUPPLEMENT_IDX_');

	function _simplexml_supplement_isnumeric($x) {
		return substr($x,0,strlen(SIMPLEXML_SUPPLEMENT_MAGIC)) === SIMPLEXML_SUPPLEMENT_MAGIC;
	}
	function _simplexml_supplement_getnumber($x) {
		return (int)substr($x,strlen(SIMPLEXML_SUPPLEMENT_MAGIC));
	}
	function _simplexml_supplement_addnumberprefix($x) {
		return SIMPLEXML_SUPPLEMENT_MAGIC.$x;
	}

	// We may not store the fields "position" and "attrs" in the SimpleXMLElement object,
	// otherweise the typecast SimpleXMLElement=>array will include them
	$_simplexml_supplement_properties = array();

	function simplexml_load_file($file): SimpleXMLElement {
		return simplexml_load_string(file_get_contents($file));
	}

	function simplexml_load_string($testxml): SimpleXMLElement {
		$out = new SimpleXMLElement(); /** @phpstan-ignore-line */

		$testxml = preg_replace('@<!\\-\\-.+\\-\\->@','',$testxml); // remove comments
		$testxml = preg_replace('@<([^>\\s]+)\\s*/>@smU','<\\1></\\1>',$testxml); // <x/> => <x></x>

		if ((stripos($testxml, '<?xml') !== false) || (stripos($testxml, '<!doctype') !== false)) {
			$testxml = preg_replace('@<\\?.+\\?>@','',$testxml);
			$testxml = preg_replace('@<!doctype.+>@i','',$testxml);
			$m = array();
			preg_match('@<(\\S+?)[^>]*>(.*)</\\1>@smU',$testxml,$m); // find root element
			$root_element = $m[1];
		} else {
			$root_element = null;
		}

		$m = array();
		preg_match_all('@<(\\S+?)([^>]*)>(.*)</\\1>@smU', $testxml, $m, PREG_SET_ORDER);
		foreach ($m as $n) {
			$name = $n[1];
			$other = $n[2];
			$val  = $n[3];

			$val = str_replace('<![CDATA[', '', $val);
			$val = str_replace(']]>', '', $val);
			$val = trim($val);

			$new = $out->addChild($name, $val);

			$m2 = array();
			preg_match_all('@(\S+)=\\"([^\\"]+)\\"@smU', $other, $m2, PREG_SET_ORDER);
			foreach ($m2 as $n2) {
				$att_name = $n2[1];
				$att_val = $n2[2];
				$new->addAttribute($att_name, $att_val);
			}
		}

		if (!is_null($root_element)) {
			$out = $out->$root_element;
		}

		return $out;
	}

	class SimpleXMLElement implements ArrayAccess, Iterator {

		function __destruct() {
			global $_simplexml_supplement_properties;
			unset($_simplexml_supplement_properties[spl_object_hash($this)]);
		}

		public function addAttribute($name, $val) {
			global $_simplexml_supplement_properties;
			$_simplexml_supplement_properties[spl_object_hash($this)]['attrs'][$name] = $val;
		}

		public function attributes() {
			global $_simplexml_supplement_properties;
			return $_simplexml_supplement_properties[spl_object_hash($this)]['attrs'];
		}

		public function isSupplement() {
			return true;
		}

		public function __construct($val=null) {
			global $_simplexml_supplement_properties;
			$_simplexml_supplement_properties[spl_object_hash($this)] = array(
				"position" => 0,
				"attrs" => array()
			);
			if (!is_null($val)) {
				$this->{_simplexml_supplement_addnumberprefix(0)} = $val;
			}
		}

		public function isArray() {
			$vars = get_object_vars($this);
			$max = -1;
			foreach ($vars as $x => $dummy) {
				if (!_simplexml_supplement_isnumeric($x)) {
					$max = -1;
					break;
				} else {
					$num = _simplexml_supplement_getnumber($x);
					if ($num > $max) $max = $num;
				}
			}
			return $max > 0;
		}

		public function addToArray($val) {
			$vars = get_object_vars($this);
			$max = -1;
			foreach ($vars as $x => $dummy) {
				if (!_simplexml_supplement_isnumeric($x)) {
					$max = -1;
					break;
				} else {
					$num = _simplexml_supplement_getnumber($x);
					if ($num > $max) $max = $num;
				}
			}
			$max++;
			$this->{_simplexml_supplement_addnumberprefix($max)} = $val;
		}

		public function __toString() {
			$data = get_object_vars($this);
			if (is_array($data)) {
				if (isset($data[_simplexml_supplement_addnumberprefix(0)])) {
					return $data[_simplexml_supplement_addnumberprefix(0)];
				} else {
					return '';
				}
			} else {
				return $data;
			}
		}

		public function offsetExists($offset) {
			return isset($this->$offset);
		}

		public function offsetGet($offset) {
			return $this->$offset;
		}

		public function offsetSet($offset, $value) {
			$this->$offset = $value;
		}

		public function offsetUnset($offset) {
			unset($this->$offset);
		}

		public function __get($name) {
			// Output nothing
			return new SimpleXMLElement(); /** @phpstan-ignore-line */
		}

		public function addChild($name, $val=null) {
			global $_simplexml_supplement_properties;

			if ($val == null) $val = new SimpleXMLElement(); /** @phpstan-ignore-line */

			if ((substr(trim($val),0,1) === '<') || (trim($val) == '')) {
				$val = simplexml_load_string($val);
			}

			$data = get_object_vars($this);

			if (!isset($data[$name])) {
				if ($val instanceof SimpleXMLElement) {
					$this->$name = $val;
				} else {
					$this->$name = new SimpleXMLElement($val);
				}
			} else {
				if (!($val instanceof SimpleXMLElement)) {
					$val = new SimpleXMLElement($val);
				}

				if ($data[$name]->isArray()) {
					$data[$name]->addToArray($val);
				} else {
					$tmp = new SimpleXMLElement(); /** @phpstan-ignore-line */
					$tmp->addToArray($data[$name]);
					$tmp->addToArray($val);
					$this->$name = $tmp;
					$_simplexml_supplement_properties[spl_object_hash($this)]['attrs'] = array();
				}
				return $val;
			}

			return $this->$name;
		}

		public function rewind() {
			global $_simplexml_supplement_properties;
			$_simplexml_supplement_properties[spl_object_hash($this)]['position'] = 0;
		}

		public function current() {
			global $_simplexml_supplement_properties;
			$vars = get_object_vars($this);
			$cnt = 0;
			foreach ($vars as $x => $dummy) {
				if (($dummy instanceof SimpleXMLElement) && !_simplexml_supplement_isnumeric($x) && $dummy->isArray()) {
					$vars2 = get_object_vars($dummy);
					foreach ($vars2 as $x2 => $dummy2) {
						if ($cnt == $_simplexml_supplement_properties[spl_object_hash($this)]['position']) {
							if ($dummy2 instanceof SimpleXMLElement) {
								return $dummy2;
							} else {
								return new SimpleXMLElement($dummy2);
							}
						}
						$cnt++;
					}
				} else {
					if ($cnt == $_simplexml_supplement_properties[spl_object_hash($this)]['position']) {
						if ($dummy instanceof SimpleXMLElement) {
							return $dummy;
						} else {
							return new SimpleXMLElement($dummy);
						}
					}
					$cnt++;
				}
			}


		}

		public function key() {
			global $_simplexml_supplement_properties;
			$vars = get_object_vars($this);
			$cnt = 0;
			foreach ($vars as $x => $dummy) {
				if (($dummy instanceof SimpleXMLElement) && !_simplexml_supplement_isnumeric($x) && $dummy->isArray()) {
					$vars2 = get_object_vars($dummy);
					foreach ($vars2 as $x2 => $dummy2) {
						if ($cnt == $_simplexml_supplement_properties[spl_object_hash($this)]['position']) return $x/*sic*/;
						$cnt++;
					}
				} else {
					if ($cnt == $_simplexml_supplement_properties[spl_object_hash($this)]['position']) return $x;
					$cnt++;
				}
			}
		}

		public function next() {
			global $_simplexml_supplement_properties;
			++$_simplexml_supplement_properties[spl_object_hash($this)]['position'];
		}

		public function valid() {
			global $_simplexml_supplement_properties;

			$vars = get_object_vars($this);
			$cnt = 0;
			foreach ($vars as $x => $dummy) {
				if (($dummy instanceof SimpleXMLElement) && !_simplexml_supplement_isnumeric($x) && $dummy->isArray()) {
					$vars2 = get_object_vars($dummy);
					foreach ($vars2 as $x2 => $dummy2) {
						$cnt++;
					}
				} else {
					$cnt++;
				}
			}

			return $_simplexml_supplement_properties[spl_object_hash($this)]['position'] < $cnt;
		}

	}
}
