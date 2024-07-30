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

namespace ViaThinkSoft\OIDplus\Plugins\PublicPages\RestApi;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_9 {

	/**
	 * @param string $requestMethod
	 * @param string $endpoint
	 * @param array $json_in
	 * @return array|false
	 */
	public function restApiCall(string $requestMethod, string $endpoint, array $json_in)/*: array|false*/;

	/**
	 * Outputs information about valid endpoints
	 * @param string $kind Reserved for different kind of output format (i.e. OpenAPI "TODO"). Currently only 'html' is implemented
	 * @return string
	 */
	public function restApiInfo(string $kind='html'): string;

}
