<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2024 Daniel Marschall, ViaThinkSoft
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

// You should regulary run this command to see if there are static variables which
// need to be put into the OIDplusContext:
// grep -r "static " | grep -v "static function" | grep -v "static abstract function" | grep "\.php:" | grep -v "vendor/" | grep -v "NoOidplusContextOk"

class OIDplusContext extends OIDplusBaseClass {

	/**
	 * In this array, plugins can put static data which they want to preserve during the session and reset on init(), e.g. with cron.sh on multiple tenants
	 * It is recommended to include OIDs in order to avoid name conflicts!
	 * Example usage: `$res = &OIDplus::getCurrentContext()->pluginData('1.3.6.1.4.1.37476.2.5.2.4.5.2', 'RealPrepareAvailable', false);`
	 */
	public function &pluginData($pluginOid, $attribName, $default) {
		$id = $pluginOid.':'.$attribName;
		if (!isset($this->pluginData[$id])) $this->pluginData[$id] = $default;
		return $this->pluginData[$id];
	}

	/**
	 * @var array
	 */
	private array $pluginData = array();

	// The following fields are static data by the core classes. Plugins SHALL NOT modify them!

	/**
	 * @var OIDplusPagePlugin[]
	 */
	public array $pagePlugins = array();

	/**
	 * @var OIDplusAuthPlugin[]
	 */
	public array $authPlugins = array();

	/**
	 * @var OIDplusLoggerPlugin[]
	 */
	public array $loggerPlugins = array();

	/**
	 * @var OIDplusObjectTypePlugin[]
	 */
	public array $objectTypePlugins = array();

	/**
	 * @var string[]|OIDplusObject[] Classnames of OIDplusObject classes
	 */
	public array $enabledObjectTypes = array();

	/**
	 * @var string[]|OIDplusObject[] Classnames of OIDplusObject classes
	 */
	public array $disabledObjectTypes = array();

	/**
	 * @var OIDplusDatabasePlugin[]
	 */
	public array $dbPlugins = array();

	/**
	 * @var OIDplusCaptchaPlugin[]
	 */
	public array $captchaPlugins = array();

	/**
	 * @var OIDplusSqlSlangPlugin[]
	 */
	public array $sqlSlangPlugins = array();

	/**
	 * @var OIDplusLanguagePlugin[]
	 */
	public array $languagePlugins = array();

	/**
	 * @var OIDplusDesignPlugin[]
	 */
	public array $designPlugins = array();

	/**
	 * @var bool
	 */
	public bool $html = true;

	/**
	 * @var ?OIDplusBaseConfig
	 */
	public ?OIDplusBaseConfig $baseConfig = null;

	/**
	 * @var bool
	 */
	public bool $oldConfigFormatLoaded = false;

	/**
	 * @var ?OIDplusConfig
	 */
	public ?OIDplusConfig $config = null;

	/**
	 * @var ?OIDplusGui
	 */
	public ?OIDplusGui $gui = null;

	/**
	 * @var ?OIDplusAuthUtils
	 */
	public ?OIDplusAuthUtils $authUtils = null;

	/**
	 * @var ?OIDplusMailUtils
	 */
	public ?OIDplusMailUtils $mailUtils = null;

	/**
	 * @var ?OIDplusCookieUtils
	 */
	public ?OIDplusCookieUtils $cookieUtils = null;

	/**
	 * @var ?OIDplusMenuUtils
	 */
	public ?OIDplusMenuUtils $menuUtils = null;

	/**
	 * @var ?OIDplusLogger
	 */
	public ?OIDplusLogger $logger = null;

	/**
	 * @var OIDplusDatabaseConnection|null
	 */
	public ?OIDplusDatabaseConnection $dbMainSession = null;

	/**
	 * @var OIDplusDatabaseConnection|null
	 */
	public ?OIDplusDatabaseConnection $dbIsolatedSession = null;

	/**
	 * @var int|null
	 */
	public ?int $system_id_cache = null;

	/**
	 * @var string|null
	 */
	public ?string $system_guid_cache = null;

	/**
	 * @var bool|null
	 */
	public ?bool $sslAvailableCache = null;

	/**
	 * @var string|null
	 */
	public ?string $forcedTenantSubDirName = null;

	/**
	 * @var array
	 */
	public array $shutdown_functions = array();

	/**
	 * @var array
	 */
	public array $translationArray = array();

	/**
	 * @var ?array
	 */
	public ?array $object_info_cache = null;

	/**
	 * @var ?OIDplusAuthContentStoreJWT
	 */
	public ?OIDplusAuthContentStoreJWT $jwtAuthContentProvider = null;

	/**
	 * @var ?int
	 */
	public ?int $dbLogSessionId = null;

}
