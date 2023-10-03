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

use ViaThinkSoft\OIDplus\OIDplus;

function ft_get_oid_data(string $oid) {
	$url = OIDplus::baseConfig()->getValue('OIDINFO_API_URL') . '&oid='.urlencode($oid);
	$options = array('http' => array('user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36'));
	$context  = stream_context_create($options);
	$cont_json = @file_get_contents($url, false, $context);
	if (!$cont_json) {
		sleep(5);
		$cont_json = @file_get_contents($url);
		if (!$cont_json) throw new Exception("Cannot get info of OID $oid\n");
	}
	$data = json_decode($cont_json,true);
	return $data;
}

