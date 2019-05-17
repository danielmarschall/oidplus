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

interface OIDplusDataBase {
	public function query($sql);
	public function num_rows($res);
	public function fetch_array($res);
	public function fetch_object($res);
	public function real_escape_string($str);
	public function escape_bool($str);
	public function set_charset($charset);
	public function error();
	public function natOrder($fieldname, $maxdepth=100);
}

