<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2021 Daniel Marschall, ViaThinkSoft
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

include __DIR__.'/../includes/oidplus.inc.php';

header('Content-Type:text/plain');

echo "localpath(null,false): ".OIDplus::localpath(null,false)."\n";
echo "localpath(null,true): ".OIDplus::localpath(null,true)."\n";
echo "localpath(__FILE__,false): ".OIDplus::localpath(__FILE__,false)."\n";
echo "localpath(__FILE__,true): ".OIDplus::localpath(__FILE__,true)."\n";
echo "localpath(__DIR__,false): ".OIDplus::localpath(__DIR__,false)."\n";
echo "localpath(__DIR__,true): ".OIDplus::localpath(__DIR__,true)."\n";

echo "webpath(null,false): ".OIDplus::webpath(null,false)."\n";
echo "webpath(null,true): ".OIDplus::webpath(null,true)."\n";
echo "webpath(__FILE__,false): ".OIDplus::webpath(__FILE__,false)."\n";
echo "webpath(__FILE__,true): ".OIDplus::webpath(__FILE__,true)."\n";
echo "webpath(__DIR__,false): ".OIDplus::webpath(__DIR__,false)."\n";
echo "webpath(__DIR__,true): ".OIDplus::webpath(__DIR__,true)."\n";
