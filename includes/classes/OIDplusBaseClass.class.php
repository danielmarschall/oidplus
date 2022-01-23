<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2022 Daniel Marschall, ViaThinkSoft
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

abstract class OIDplusBaseClass {

	public function implementsFeature($id) {

		// Use this function to query the plugin if it supports some specific interface
		// Usually, you would use PHP Interfaces. However, the problem with PHP interfaces
		// is, that there will be a fatal error if the interface can't be found (e.g. because
		// the OIDplus plugin is not installed). So we need an "optional" interface.

		return false;
	}

}
