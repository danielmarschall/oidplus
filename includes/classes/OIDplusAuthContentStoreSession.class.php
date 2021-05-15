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

class OIDplusAuthContentStoreSession extends OIDplusAuthContentStore {

	protected function getSessionHandler() {
		static $sesHandler = null;
		if (is_null($sesHandler)) {
			$sesHandler = new OIDplusSessionHandler();
		}
		return $sesHandler;
	}

	// Override abstract functions

	public function getValue($name, $default = NULL) {
		return $this->getSessionHandler()->getValue($name, $default);

	}

	public function setValue($name, $value) {
		return $this->getSessionHandler()->setValue($name, $value);
	}

	public function exists($name) {
		return $this->getSessionHandler()->exists($name);
	}

	public function delete($name) {
		return $this->getSessionHandler()->delete($name);
	}

	protected function destroySession() {
		return $this->getSessionHandler()->destroySession();
	}

}
