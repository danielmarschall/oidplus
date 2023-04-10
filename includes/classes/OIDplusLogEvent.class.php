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

class OIDplusLogEvent extends OIDplusBaseClass {

	/**
	 * @param string $message
	 */
	public function __construct(string $message) {
		$this->setMessage($message);
	}

	/**
	 * @var string
	 */
	private $message;

	/**
	 * @return string
	 */
	public function getMessage(): string {
		return $this->message;
	}

	/**
	 * @param string $message
	 * @return void
	 */
	public function setMessage(string $message) {
		$this->message = $message;
	}

	/**
	 * @var OIDplusLogTarget[]
	 */
	private $targets = [];

	/**
	 * @return OIDplusLogTarget[]
	 */
	public function getTargets(): array {
		return $this->targets;
	}

	/**
	 * @param OIDplusLogTarget $target
	 * @return void
	 */
	public function addTarget(OIDplusLogTarget $target) {
		$this->targets[] = $target;
	}

}
