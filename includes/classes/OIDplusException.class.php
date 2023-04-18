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

/**
 * Every Exception that is thrown in OIDplus should be an OIDplusException.
 */
class OIDplusException extends \Exception {

	/**
	 * @var string|null
	 */
	protected $title = null;

	/**
	 * @param string $message
	 * @param string|null $title
	 */
	public function __construct(string $message, string $title=null) {
		$this->title = $title;
		parent::__construct($message);
	}

	/**
	 * @return string
	 */
	public function getTitle(): string {
		return $this->title ?? '';
	}

	/**
	 * @return string
	 */
	public function getHtmlTitle(): string {
		return htmlentities($this->getTitle(), ENT_SUBSTITUTE); // ENT_SUBSTITUTE because ODBC drivers might return ANSI instead of UTF-8 stuff
	}
	/**
	 * @return string
	 */
	public function getHtmlMessage(): string {
		return nl2br(htmlentities($this->getMessage(), ENT_SUBSTITUTE)); // ENT_SUBSTITUTE because ODBC drivers might return ANSI instead of UTF-8 stuff
	}

}