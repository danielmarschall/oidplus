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

abstract class OIDplusCaptchaPlugin extends OIDplusPlugin {

	/**
	 * @return string
	 */
	public abstract static function id(): string; // this is the name that is set to the configuration value OIDplus::baseConfig()->getValue('CAPTCHA_PLUGIN') to identify the CAPTCHA plugin

	/**
	 * @return bool
	 */
	public abstract function isVisible(): bool;

	/**
	 * @return bool
	 * @throws OIDplusException
	 */
	public final function isActive(): bool {
		return OIDplus::getActiveCaptchaPluginId() == $this->id();
	}

	/**
	 * @param string|null $header_text
	 * @param string|null $footer_text
	 * @return string
	 */
	public abstract function captchaGenerate(?string $header_text=null, ?string $footer_text=null): string;

	/**
	 * @param string[] $params
	 * @param string|null $fieldname
	 * @return void
	 */
	public abstract function captchaVerify(array $params, ?string $fieldname=null);

	/**
	 * @return string
	 */
	public abstract static function setupHTML(): string;

}
