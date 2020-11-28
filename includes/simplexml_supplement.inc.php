<?php

/*
 * PHP SimpleXML-Supplement
 * Copyright 2020 Daniel Marschall, ViaThinkSoft
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
// - The supplement does not support attributes
// - The supplement is slow because of regular expressions
// - Many functions like "asXML" are not implemented
//
// So, if you want to use the SimpleXML supplement, then please carefully
// test it with your application if it works.
// ========================================

if (!function_exists('simplexml_load_string')) {

	function simplexml_load_file($file): SimpleXMLElement {
		return simplexml_load_string(file_get_contents($file));
	}

	function simplexml_load_string($testxml): SimpleXMLElement {
		$out = new SimpleXMLElement();

		$testxml = preg_replace('@<!\\-\\-.+\\-\\->@','',$testxml); // remove comments
		$testxml = preg_replace('@<(\\S+)[^>]*/>@smU','<\\1></\\1>',$testxml); // <x/> => <x></x>

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
		preg_match_all('@<(\\S+?)[^>]*>(.*)</\\1>@smU', $testxml, $m, PREG_SET_ORDER);
		foreach ($m as $n) {
			$name = $n[1];
			$val  = $n[2];

			$val = str_replace('<![CDATA[', '', $val);
			$val = str_replace(']]>', '', $val);
			$val = trim($val);

			$out->addChild($name, $val);
		}

		if (!is_null($root_element)) {
			$out = $out->$root_element;
		}

		return $out;
	}

	class SimpleXMLElement implements ArrayAccess {

		public function isSupplement() {
			return true;
		}

		public function __toString() {
			$data = /*$this->data;*/get_object_vars($this);
			if (is_array($data)) {
				if (isset($data[0])) {
					return $data[0];
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
			return new SimpleXMLElement();
		}

		public function addChild($name, $val=null) {
			if ($val == null) $val = new SimpleXMLElement();

			if ((substr(trim($val),0,1) === '<') || (trim($val) == '')) {
				$val = simplexml_load_string($val);
			}

			$data = /*$this->data;*/get_object_vars($this);
			if (!isset($data[$name])) {
				if (is_object($val)) {
					$data[$name] = $val;
				} else {
					// Adding primitve value
					$data[$name][] = $val;
				}
			} else if (is_array($data[$name])) {
				// Add to an array of existing sub-nodes
				$data[$name][] = $val;
			} else if ($data[$name] instanceof SimpleXMLElement) {
				$vars = get_object_vars($data[$name]);
				$complex = false;
				$max = -1;
				foreach ($vars as $x => $dummy) {
					if (!is_numeric($x)) {
						$complex = true;
						break;
					} else {
						if ($x > $max) $max = $x;
					}
				}
				if ($complex) {
					// Adding a primitive value to a node that contains a comlpex value
					$data[$name] = array(
						$data[$name],
						$val
					);
				} else {
					// Adding a primitive value to a "fake" array
					// (Real SimpleXMLElement says that print_r($xml) says that member "a" is an array, but if you call print_r($xml->a), it is an SimpleXMLElement?!)
					$max++;
					$data[$name]->$max = $val;
				}
			} else {
				// Adding a primitive value to a node that contains a single primitive value
				$data[$name] = array(
					$data[$name],
					$val
				);
			}

			if (is_array($data[$name])) {
				$test = new SimpleXMLElement();
				foreach ($data[$name] as $n => $val) {
					$test->$n = $val;
				}
				$this->$name = $test;
			} else {
				$this->$name = $data[$name];
			}

			return $val;
		}
	}

}



/*
$testxml = <<<EOF
<?xml version="1.0" ?>
<translation>
	<a>x</a>
	<a>y</a>
	<b>z</b>
	<c>
		<a>p</a>
		<a>q</a>
	</c>
	<d>
		<a>p</a>
		<a>q</a>
	</d>
	<e></e>
</translation>
EOF;

$xml = simplexml_load_string($testxml);
$xml->addChild('a', 'm1');
$xml->addChild('a', 'm2');
$xml->addChild('d', 'n');
$character = $xml->addChild('char');
$character->addChild('name', 'Mr. Parser');
$character->addChild('actor', 'John Doe');
//echo "X=".$xml->ma;
//$xml->ma = 3;
//echo "X=".$xml->ma;
//print_r($xml->a);
//print_r($xml->d[0]);
//print_r($xml);
//print_r((string)($xml->a));
//foreach ($xml->a as $x) {
//	echo "$x\n";
//}
//foreach ((array)$xml->a as $x) {
//	echo "$x\n";
//}
//foreach ((array)$xml->jsSetup->file as $js_file) {
//}
//$xml = simplexml_load_file(__DIR__.'/../plugins/database/mysqli/manifest.xml');
//print_r($xml);
//echo (string)$xml->php->mainclass;
$testxml = <<<EOF
<?xml version="1.0" ?>
<oid-database>
	<oid>
		<dot-notation>2.999</dot-notation>
	</oid>
	<oid>
		<dot-notation>2.999.2</dot-notation>
	</oid>
</oid-database>
EOF;
$xml = simplexml_load_string($testxml);
print_r($xml);
foreach ($xml->oid as $oid) {
	echo $oid->{'dot-notation'}->__toString()."\n";
}
*/

