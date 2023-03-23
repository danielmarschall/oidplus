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

class OIDplusOidIri {
	/**
	 * @var string
	 */
	private $name = '';

	/**
	 * @var bool
	 */
	private $longarc = false;

	/**
	 * @var bool
	 */
	private $well_known = false;

	/**
	 * @param string $name
	 * @param bool $longarc
	 * @param bool $well_known
	 */
	function __construct(string $name, bool $longarc, bool $well_known) {
		$this->name = $name;
		$this->longarc = $longarc;
		$this->well_known = $well_known;
	}

	/**
	 * @return string
	 */
	function getName(): string {
		return $this->name;
	}

	/**
	 * @return bool
	 */
	function isLongarc(): bool {
		return $this->longarc;
	}

	/**
	 * @return bool
	 */
	function isWellKnown(): bool {
		return $this->well_known;
	}

	/**
	 * @return string
	 */
	function __toString(): string {
		return $this->name;
	}
}
