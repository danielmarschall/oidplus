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
 * This kind of Exception can contain HTML code
 */
class OIDplusHtmlException extends OIDplusException {

	/**
	 * @var string|null
	 */
	private $htmlTitle;

	/**
	 * @var string
	 */
	private $htmlMessage;

	/**
	 * @param string $message
	 * @param string|null $title
	 */
	public function __construct(string $message, string $title=null, int $httpStatus=500) {
		$this->htmlTitle = $title;
		if ($title) {
			$title = strip_tags($title);
			$title_text = html_to_text($title);
		} else {
			$title_text = $title;
		}

		$this->htmlMessage = $message;
		$message_text = html_to_text($message);

		parent::__construct($message_text, $title_text, $httpStatus);
	}

	/**
	 * @return string
	 */
	public function getHtmlMessage(): string {
		return $this->htmlMessage;
	}

	/**
	 * @return string
	 */
	public function getHtmlTitle(): string {
		return $this->htmlTitle ?? '';
	}

}
