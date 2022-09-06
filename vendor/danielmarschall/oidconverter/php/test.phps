<?php

/*
 * tests.phps, Version 1.0; Based on version 1.11 of oid.c
 * Copyright 2014-2015 Daniel Marschall, ViaThinkSoft
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


// TODO: more testcases

error_reporting(E_ALL | E_NOTICE | E_DEPRECATED | E_STRICT);

require_once __DIR__ . '/OidDerConverter.class.phps';

assert(OidDerConverter::hexarrayToStr(OidDerConverter::oidToDER('.2.999')) == '06 02 88 37');
assert(OidDerConverter::derToOID(array(0x06, 0x02, 0x88, 0x37)) == '.2.999');

assert(OidDerConverter::hexarrayToStr(OidDerConverter::oidToDER('2.999', true)) == '0D 03 02 87 67');
assert(OidDerConverter::derToOID(array(0x0D, 0x03, 0x02, 0x87, 0x67)) == '2.999');

assert(OidDerConverter::derToOID(OidDerConverter::hexstrToArray('0D 03 02 87 67')) == '2.999');
assert(OidDerConverter::derToOID(OidDerConverter::hexstrToArray('\x0D\x03\x02\x87\x67')) == '2.999');
