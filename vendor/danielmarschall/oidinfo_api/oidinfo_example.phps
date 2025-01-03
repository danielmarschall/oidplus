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
$oa->addSimplePingProvider('viathinksoft.de:49500');

echo $oa->xmlAddHeader('hello', 'world', 'test@example.com');

$params['allow_html'] = true; // Allow HTML in <description> and <information>
$params['allow_illegal_email'] = true; // It should be enabled, because the creator could have used some kind of human-readable anti-spam technique
$params['soft_correct_behavior'] = OIDInfoAPI::SOFT_CORRECT_BEHAVIOR_NONE;
$params['do_online_check'] = false; // Flag to disable this online check, because it generates a lot of traffic and runtime.
$params['do_illegality_check'] = false;
$params['do_simpleping_check'] = false;
$params['auto_extract_name'] = '';
$params['auto_extract_url'] = '';
$params['always_output_comment'] = false; // Do not output comment if there was an error (e.g. OID already existing)
$params['creation_allowed_check'] = false;
$params['tolerant_htmlentities'] = true;
$params['ignore_xhtml_light'] = false;

$elements['synonymous-identifier'] = ''; // string or array
$elements['description'] = '';
$elements['information'] = '';

$elements['first-registrant']['first-name'] = '';
$elements['first-registrant']['last-name'] = '';
$elements['first-registrant']['address'] = '';
$elements['first-registrant']['email'] = '';
$elements['first-registrant']['phone'] = '';
$elements['first-registrant']['fax'] = '';
$elements['first-registrant']['creation-date'] = '';

$elements['current-registrant']['first-name'] = '';
$elements['current-registrant']['last-name'] = '';
$elements['current-registrant']['address'] = '';
$elements['current-registrant']['email'] = '';
$elements['current-registrant']['phone'] = '';
$elements['current-registrant']['fax'] = '';
$elements['current-registrant']['modification-date'] = '';

$oid = '2.999';
echo $oa->createXMLEntry($oid, $elements, $params, $comment='hello');

$oid = '2.999.1';
echo $oa->createXMLEntry($oid, $elements, $params, $comment='hello');

echo $oa->xmlAddFooter();
