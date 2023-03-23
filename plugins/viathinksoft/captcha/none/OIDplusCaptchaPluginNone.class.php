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

class OIDplusCaptchaPluginNone extends OIDplusCaptchaPlugin {

	/**
	 * @return string
	 */
	public static function id(): string {
		return 'None';
	}

	/**
	 * @return bool
	 */
	public function isVisible(): bool {
		return false;
	}

	/**
	 * @param string|null $header_text
	 * @param string|null $footer_text
	 * @return string
	 */
	public function captchaGenerate(string $header_text=null, string $footer_text=null): string {
		return '<script>
		var oidplus_captcha_response = function() {
			return OIDplusCaptchaPluginNone.captchaResponse();
		};
		var oidplus_captcha_reset = function() {
			return OIDplusCaptchaPluginNone.captchaReset();
		};
		</script>';
	}

	/**
	 * @param array $params
	 * @param string|null $fieldname
	 * @return bool
	 */
	public function captchaVerify(array $params, string $fieldname=null): bool {
		return true;
	}

	/**
	 * @return string
	 */
	public static function setupHTML(): string {
		return '<div id="CAPTCHAPLUGIN_PARAMS_NONE">'.
		       '<p>'._L('No CAPTCHA will be used. Please note that your system will be prone to "Brute force" attacks.').'</p>'.
		       '</div>';
	}

}
