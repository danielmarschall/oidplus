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

class OIDplusNotification extends OIDplusBaseClass {

	/**
	 * @var string
	 */
	private $severity;

	/**
	 * @var string
	 */
	private $message; // TODO: Rename this to $htmlMessage everywhere

	/**
	 * @param string $severity One of OK, INFO, WARN, ERR, or CRIT
	 * @param string $message
	 * @throws OIDplusException
	 */
	public function __construct(string $severity, string $message) {
		// Same severities as the log plugin (also same CSS classes)
		if (($severity != 'OK') && ($severity != 'INFO') && ($severity != 'WARN') && ($severity != 'ERR') && ($severity != 'CRIT')) {
			throw new OIDplusException(_L('Invalid severity "%1"', $severity));
		}

		$this->severity = $severity;
		$this->message = $message;
	}

	/**
	 * @return int
	 */
	public function getSeverityAsInt(): int {
		if      ($this->severity == 'OK')   return 1; // (this makes no sense)
		else if ($this->severity == 'INFO') return 2;
		else if ($this->severity == 'WARN') return 3;
		else if ($this->severity == 'ERR')  return 4;
		else if ($this->severity == 'CRIT') return 5;
		else assert(false);
		return 0; // otherwise PHPstan complains...
	}

	/**
	 * @return string
	 */
	public function getSeverityAsString(): string {
		return $this->severity;
	}

	/**
	 * @param bool $plural
	 * @return string
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	public function getSeverityAsHumanFriendlyString(bool $plural): string {
		if ($plural) {
			if      ($this->severity == 'OK')   return _L('OK');
			else if ($this->severity == 'INFO') return _L('Informational');
			else if ($this->severity == 'WARN') return _L('Warnings');
			else if ($this->severity == 'ERR')  return _L('Errors');
			else if ($this->severity == 'CRIT') return _L('Critical issues');
			else assert(false);
		} else {
			if      ($this->severity == 'OK')   return _L('OK');
			else if ($this->severity == 'INFO') return _L('Informational');
			else if ($this->severity == 'WARN') return _L('Warning');
			else if ($this->severity == 'ERR')  return _L('Error');
			else if ($this->severity == 'CRIT') return _L('Critical issue');
			else assert(false);
		}
		return ''; // otherwise PHPstan complains...
	}

	/**
	 * @return string
	 */
	public function getMessage(): string {
		// TODO: Rename this method to getHtmlMessage() everywhere
		return $this->message;
	}

}
