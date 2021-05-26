<?php

/*
 * IP functions
 * Copyright 2015 Daniel Marschall, ViaThinkSoft
 * Version 2015-10-27
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

function get_real_ip() {
	/* Eindeutige IP Adresse erhalten, auch bei Proxies und (neu:) von SSH connections im CLI modus */
	// http://lists.phpbar.de/pipermail/php/Week-of-Mon-20040322/007749.html
	// Modificated by VTS
	// Version: 2015-10-27

	// TODO: ipv6

	if (isset($_SERVER['SSH_CLIENT']))     { $ary = explode(' ', $_SERVER['SSH_CLIENT']);     return $ary[0]; }
	if (isset($_SERVER['SSH_CONNECTION'])) { $ary = explode(' ', $_SERVER['SSH_CONNECTION']); return $ary[0]; }

	$client_ip       = (isset($_SERVER['HTTP_CLIENT_IP']))       ? $_SERVER['HTTP_CLIENT_IP']       : '';

	// It is not secure to use these, since they are not validated: http://www.thespanner.co.uk/2007/12/02/faking-the-unexpected/
	// $x_forwarded_for = (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '';
	$x_forwarded_for = '';

	$remote_addr     = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';

	if (!empty($client_ip)) {
		$ip_expl = explode('.', $client_ip);
		$referer = explode('.', $remote_addr);
		if ($referer[0] != $ip_expl[0]) {
			$ip = array_reverse($ip_expl);
			$return = implode('.', $ip);
		} else {
			$return = $client_ip;
		}
	} else if (!empty($x_forwarded_for)) {
		if (strstr($x_forwarded_for, ',')) {
			$ip_expl = explode(',', $x_forwarded_for);
			$return = end($ip_expl);
		} else {
			$return = $x_forwarded_for;
		}
	} else {
		$return = $remote_addr;
	}
	unset ($client_ip, $x_forwarded_for, $remote_addr, $ip_expl);
	return $return;
}
