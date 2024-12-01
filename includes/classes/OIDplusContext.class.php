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

// You should regularly run this command inside includes/classes/ to see if there are static variables which
// need to be put into the context:   grep -r "static " | grep -v "static function"
class OIDplusContext extends OIDplusBaseClass {
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
	 * @var bool
	 */
	public bool $getDefaultLang_thrownOnce = false; // avoid endless loop inside OIDplusConfigInitializationException

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
