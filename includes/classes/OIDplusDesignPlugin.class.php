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

namespace ViaThinkSoft\OIDplus\Core;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

abstract class OIDplusDesignPlugin extends OIDplusPlugin {

	/**
	 * @return string
	 */
	public abstract function id(): string;

	/**
	 * @return string
	 */
	public function getThemeColor(): string {
		return ''; // no theme color
	}

	/**
	 * @return bool
	 * @throws OIDplusException
	 */
	public final function isActive(): bool {
		return $this->id() == OIDplus::config()->getValue('design');
	}

}
