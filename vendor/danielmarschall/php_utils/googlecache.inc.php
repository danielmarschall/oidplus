<?php

/*
 * Google GetCache
 * Copyright 2015 Daniel Marschall, ViaThinkSoft
 * Version 2015-06-26
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

function google_getcache($url) {
	$options = array(
	  'http'=>array(
	    'method'=>"GET",
	    'header'=>"Accept-language: en\r\n" .
	              "Cookie: foo=bar\r\n" .  // check function.stream-context-create on php.net
	              "User-Agent: Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)"
	  )
	);

	$context = stream_context_create($options);

	$url = 'https://www.google.de/search?q='.urlencode($url);
	$cont = file_get_contents($url, false, $context);
	preg_match_all('@(http://webcache.googleusercontent.com/.+)"@ismU', $cont, $m);
	if (!isset($m[1][0])) return false;
	$url = urldecode($m[1][0]);
	return file_get_contents($url, false, $context);
}
