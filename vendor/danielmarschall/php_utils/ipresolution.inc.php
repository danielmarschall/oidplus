<?php

/*
 * IP resolution functions
 * Copyright 2012 Daniel Marschall, ViaThinkSoft
 * Version 2012-02-02
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

/* -- Testcases --
print_r(gethostbynamel6('example.com'));
print_r(gethostbynamel6('ipv6.google.com'));
print_r(gethostbynamel6('google.de'));
print_r(gethostbynamel6('ipv6.google.de'));
print_r(gethostbynamel6('abc'));
print_r(gethostbynamel6('111.111.111.111'));
print_r(gethostbynamel6('2620::2d0:200:0:0:0:10'));
*/

function resolveip($host) {
	return gethostbynamel6($host);
}

# http://www.php.net/manual/en/function.gethostbyname.php#70936
# Modified by ViaThinkSoft

# VTS-Modified: try_a default false -> true
function gethostbyname6($host, $try_a = /* false */ true) {
	// get AAAA record for $host
	// if $try_a is true, if AAAA fails, it tries for A
	// the first match found is returned
	// otherwise returns false

	$dns = gethostbynamel6($host, $try_a);
	if ($dns == false) {
		return false;
	} else {
		return $dns[0];
	}
}

# VTS-Modified: try_a default false -> true
function gethostbynamel6($host, $try_a = /* false */ true) {
	# Added by VTS
	$ipfilter = filter_var($host,FILTER_VALIDATE_IP);
	if ($ipfilter != '') return array($ipfilter);

	// get AAAA records for $host,
	// if $try_a is true, if AAAA fails, it tries for A
	// results are returned in an array of ips found matching type
	// otherwise returns false

	$dns6 = dns_get_record($host, DNS_AAAA);
	if ($try_a == true) {
		$dns4 = dns_get_record($host, DNS_A);
		$dns = array_merge($dns4, $dns6);
	} else {
		$dns = $dns6;
	}
	$ip6 = array();
	$ip4 = array();
	foreach ($dns as $record) {
		if ($record["type"] == "A") {
			$ip4[] = $record["ip"];
		}
		if ($record["type"] == "AAAA") {
			$ip6[] = $record["ipv6"];
		}
	}

	# VTS-Modified: Output IP4+IP6 merged instead of giving only IPv6 or IPv4
	$merged = array_merge($ip4, $ip6);
	if (count($merged) < 1) {
		return false;
	} else {
		return $merged;
	}
	/*
	if (count($ip6) < 1) {
		if ($try_a == true) {
			if (count($ip4) < 1) {
				return false;
			} else {
				return $ip4;
			}
		} else {
			return false;
		}
	} else {
		return $ip6;
	}
	*/
}
