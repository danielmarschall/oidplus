<?php

/*
 * Grep functions for PHP
 * Copyright 2012-2013 Daniel Marschall, ViaThinkSoft
 * Version 2013-03-08
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

# TODO: if console available, use it

// "grep"
function grep(&$array, $substr) {
	if (!is_array($array)) return false;
	$ret = array();
	foreach ($array as &$a) {
		if (strpos($a, $substr) !== false) $ret[] = $a;
	}
	return $ret;
}

// "grep -v"
function antigrep(&$array, $substr) {
	if (!is_array($array)) return false;
	$ret = array();
	foreach ($array as &$a) {
		if (strpos($a, $substr) === false) $ret[] = $a;
	}
	return $ret;
}
