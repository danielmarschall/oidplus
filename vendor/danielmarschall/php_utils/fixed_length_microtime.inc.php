<?php

/*
 * fixed_length_microtime() for PHP
 * Copyright 2022 Daniel Marschall, ViaThinkSoft
 * Version 2022-03-08
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

function fixed_length_microtime($unique=true) {
	// This function outputs a fixed-length microtime (can be used for sorting)
	// Optionally, it ensures that the output is always different by waiting 1 microsecond

	$ary = explode('.', (string)microtime(true));
	if (!isset($ary[1])) $ary[1] = 0;
	$ret = $ary[0].'_'.str_pad($ary[1], 4, '0', STR_PAD_RIGHT);

	if ($unique) {
		// Make sure value changes by waiting 1 microsecond.
		usleep(1);
	}

	return $ret;
}
