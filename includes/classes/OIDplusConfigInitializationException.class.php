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

class OIDplusConfigInitializationException extends OIDplusHtmlException {

	/**
	 * @param string $message
	 */
	public function __construct(string $message) {
		static $anti_deadlock = false; // [NoOidplusContextOk] this does not need to be in OIDplus::getCurrentContext(), because it is only used here and does not store information acreoss multiple parts of the program
		if ($anti_deadlock) {
			// deadlock can happen if calls to webpath(), getUserDatadir(), etc. fail
			// so, make a minimal construction
			\Exception::__construct($message);
			return;
		}
		$anti_deadlock = true;
		try {
			try {
				$is_tenant = OIDplus::isTenant();
			} catch (\Throwable $e) {
				$is_tenant = false; // just assume
			}

			try {
				$baseconfig_file = OIDplus::getUserDataDir("baseconfig").'config.inc.php';
				$baseconfig_file = substr($baseconfig_file, strlen(OIDplus::localpath(NULL))); // "censor" the system local path
			} catch (\Throwable $e) {
				$baseconfig_file = $is_tenant ? 'userdata/tenant/.../baseconfig/config.inc.php' : 'userdata/baseconfig/config.inc.php';
			}

			try {
				$title = _L('OIDplus initialization error');
				$message_html = '<p>'.$message.'</p>';
				$message_html .= '<p>'._L('Please check the file %1', '<b>'.htmlentities($baseconfig_file).'</b>');
				if (is_dir(__DIR__ . '/../../setup')) {
					$message_html .= ' ' . _L('or run <a href="%1">setup</a> again', OIDplus::webpath(null, OIDplus::PATH_RELATIVE) . 'setup/');
				}
				$message_html .= '</p>';
			} catch (\Throwable $e) {
				// In case something fails very hard (i.e. the translation), then we still must show the Exception somehow!
				// We intentionally catch Exception and Error
				$title = 'OIDplus initialization error';
				$message_html = '<p>'.$message.'</p><p>Please check the file <b>'.htmlentities($baseconfig_file).'</b> or run <b>setup/</b> again</p>';
			}

			parent::__construct($message_html, $title, 500);
		} finally {
			$anti_deadlock = false;
		}
	}

}
