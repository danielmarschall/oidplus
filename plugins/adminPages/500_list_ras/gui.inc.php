<?php

/*
 * OIDplus 2.0
 * Copyright 2019 Daniel Marschall, ViaThinkSoft
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

if ($id === 'oidplus:list_ra') {
	$handled = true;
	$out['title'] = 'RA Listing';
	$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/adminPages/'.basename(__DIR__).'/icon_big.png' : '';

	if (!OIDplus::authUtils()::isAdminLoggedIn()) {
		$out['icon'] = 'img/error_big.png';
		$out['text'] .= '<p>You need to <a href="?goto=oidplus:login">log in</a> as administrator.</p>';
	} else {
		$out['text'] = '';

		$tmp = array();
		$res = OIDplus::db()->query("select distinct BINARY(email) as email from ".OIDPLUS_TABLENAME_PREFIX."ra"); // "binary" because we want to ensure that 'distinct' is case sensitive
		while ($row = OIDplus::db()->fetch_array($res)) {
			$tmp[$row['email']] = 1;
		}
		$res = OIDplus::db()->query("select distinct BINARY(ra_email) as ra_email from ".OIDPLUS_TABLENAME_PREFIX."objects");
		while ($row = OIDplus::db()->fetch_array($res)) {
			if (!isset($tmp[$row['ra_email']])) {
				$tmp[$row['ra_email']] = 0;
			} else {
				$tmp[$row['ra_email']] = 2;
			}
		}
		ksort($tmp);

		foreach ($tmp as $ra_email => $registered) {
			if (empty($ra_email)) {
				$out['text'] .= '<p><b><a href="?goto=oidplus:rainfo$">(Objects with undefined RA)</a></b></p>';
			} else {
				if ($registered == 0) {
					$out['text'] .= '<p><b><a href="?goto=oidplus:rainfo$'.htmlentities($ra_email).'">'.htmlentities($ra_email).'</a></b> (has objects, is not registered)</p>';
				}
				if ($registered == 1) {
					$out['text'] .= '<p><b><a href="?goto=oidplus:rainfo$'.htmlentities($ra_email).'">'.htmlentities($ra_email).'</a></b> (registered, <font color="red">has no objects</font>)</p>';
				}
				if ($registered == 2) {
					$out['text'] .= '<p><b><a href="?goto=oidplus:rainfo$'.htmlentities($ra_email).'">'.htmlentities($ra_email).'</a></b></p>';
				}
			}
		}
	}
}
