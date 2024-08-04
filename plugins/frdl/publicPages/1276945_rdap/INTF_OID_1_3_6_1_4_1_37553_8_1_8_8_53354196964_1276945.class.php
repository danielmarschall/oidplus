<?php

/*
 * OIDplus 2.0 RDAP
 * Copyright 2019 - 2024 Daniel Marschall, ViaThinkSoft
 * Authors               Daniel Marschall, ViaThinkSoft
 *                       Till Wehowski, Frdlweb
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

namespace Frdlweb\OIDplus\Plugins\PublicPages\RDAP;

use ViaThinkSoft\OIDplus\Core\OIDplusObject;

interface INTF_OID_1_3_6_1_4_1_37553_8_1_8_8_53354196964_1276945 {

	public function rdapExtensions(array $out, string $namespace, string $id, OIDplusObject $obj, string $query) : array;

}
