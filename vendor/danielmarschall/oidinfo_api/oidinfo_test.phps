<?php

/*
 * OID-Info.com API for PHP
 * Copyright 2019 Daniel Marschall, ViaThinkSoft
 * Version 2019-11-01
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

require_once __DIR__ . '/oidinfo_api.inc.phps';

$oa = new OIDInfoAPI();

$oa->loadIllegalityRuleFile('oid_illegality_rules');

assert($oa->illegalOID('1.3.6.1.2.1.9999') === true);
assert($oa->illegalOID('1.3.6.1.2.1.9999.123') === true);
assert($oa->illegalOID('2.999') === false);
assert($oa->illegalOID('3') === true);
assert($oa->illegalOID('1') === false);
assert($oa->illegalOID('1.0.16') === true);
assert($oa->illegalOID('1.2.6.0') === true); // 1.2.6 is illegal -> 1.2.6.0 too
assert($oa->illegalOID('2.25.340282366920938463463374607431768211455') === false);
assert($oa->illegalOID('2.25.340282366920938463463374607431768211456') === true);

assert($oa->strictCheckSyntax('0') === true);
assert($oa->strictCheckSyntax('1') === true);
assert($oa->strictCheckSyntax('(requesting)') === false);
