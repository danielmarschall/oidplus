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

class OIDplusAltId extends OIDplusBaseClass {

	/**
	 * @var string
	 */
	private $ns;
	/**
	 * @var string
	 */
	private $id;
	/**
	 * @var string
	 */
	private $desc;
	/**
	 * @var string
	 */
	private $suffix;

	/**
	 * @var string
	 */
	private $moreInfoUrl;

	/**
	 * @param string $ns
	 * @param string $id
	 * @param string $desc
	 * @param string $suffix
	 */
	function __construct(string $ns, string $id, string $desc, string $suffix='', string $moreInfoUrl='') {
		$this->ns = $ns;
		$this->id = $id;
		$this->desc = $desc;
		$this->suffix = $suffix;
		$this->moreInfoUrl = $moreInfoUrl;
	}

	/**
	 * @return string
	 */
	function getNamespace(): string {
		return $this->ns;
	}

	/**
	 * @return string
	 */
	function getId(): string {
		return $this->id;
	}

	/**
	 * @return string
	 */
	function getDescription(): string {
		return $this->desc;
	}

	/**
	 * @return string
	 */
	function getSuffix(): string {
		return $this->suffix;
	}

	/**
	 * @return string
	 */
	function getMoreInfoUrl(): string {
		return $this->moreInfoUrl;
	}

}
