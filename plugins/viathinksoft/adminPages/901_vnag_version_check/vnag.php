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

include __DIR__ . '/../../../../vendor/danielmarschall/vnag/framework/vnag_framework.inc.php';
include __DIR__ . '/../../../../includes/oidplus.inc.php';

define('OIDPLUS_VNAG_MAX_CACHE_AGE', 60); // seconds (TODO: in base config?)

OIDplus::init(false);

if (OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_OIDplusPageAdminVNagVersionCheck', false)) {
	throw new OIDplusException(_L('This plugin was disabled by the system administrator!'));
}

class VNagMonitorDummy extends VNag {

	private $status;

	private $content;

	public function __construct($status, $content) {
		parent::__construct();
		$this->status = $status;
		$this->content = $content;
	}

	protected function cbRun($optional_args = array()) {
		$this->setStatus($this->status);
		$this->setHeadline($this->content);
	}
}

$cache_file = OIDplus::localpath() . 'userdata/cache/vnag_version_check.ser';

if ((file_exists($cache_file)) && (time()-filemtime($cache_file) <= OIDPLUS_VNAG_MAX_CACHE_AGE)) {
	// Anti DoS

	// TODO: There is a small flaw: If the admin fails to secure the "cache/" folder
	//       from the public, then people can read the version file, even if the
	//       VNag script is intended to be protected by a vnag_password.
	list($out_stat, $out_msg) = unserialize(file_get_contents($cache_file));

} else {

	$installType = OIDplus::getInstallType();

	if ($installType === 'ambigous') {
		$out_stat = VNag::STATUS_UNKNOWN;
		$out_msg  = 'Multiple version files/directories (oidplus_version.txt, .git and .svn) are existing! Therefore, the version is ambiguous!'; // do not translate
	} else if ($installType === 'unknown') {
		$out_stat = VNag::STATUS_UNKNOWN;
		$out_msg  = 'The version cannot be determined, and the update needs to be applied manually!'; // do not translate
	} else if (($installType === 'svn-wc') || ($installType === 'git-wc')) {
		$local_installation = OIDplus::getVersion();
		$newest_version = getLatestRevision();

		$requireInfo = ($installType === 'svn-wc') ? 'shell access with svn/svnversion tool, or PDO/SQLite3 PHP extension' : 'shell access with Git client'; // do not translate
		$updateCommand = ($installType === 'svn-wc') ? 'svn update' : 'git pull';

		if (!$newest_version) {
			$out_stat = VNag::STATUS_UNKNOWN;
			$out_msg  = 'OIDplus could not determine the latest version. Probably the ViaThinkSoft server could not be reached.'; // do not translate
		} else if (!$local_installation) {
			$out_stat = VNag::STATUS_UNKNOWN;
			$out_msg  = 'OIDplus could not determine its version (Required: ' . $requireInfo . '). Please update your system manually via the "' . $updateCommand . '" command regularly.'; // do not translate
		} else if ($local_installation == $newest_version) {
			$out_stat = VNag::STATUS_OK;
			$out_msg  = 'You are using the latest version of OIDplus (' . $local_installation . ' local / ' . $newest_version . ' remote)'; // do not translate
		} else {
			$out_stat = VNag::STATUS_WARNING;
			$out_msg  = 'OIDplus is outdated. (' . $local_installation . ' local / ' . $newest_version . ' remote)'; // do not translate
		}
	} else if ($installType === 'svn-snapshot') {
		$local_installation = OIDplus::getVersion();
		$newest_version = getLatestRevision();

		if (!$newest_version) {
			$out_stat = VNag::STATUS_UNKNOWN;
			$out_msg  = 'OIDplus could not determine the latest version. Probably the ViaThinkSoft server could not be reached.'; // do not translate
		} else if ($local_installation == $newest_version) {
			$out_stat = VNag::STATUS_OK;
			$out_msg  = 'You are using the latest version of OIDplus (' . $local_installation . ' local / ' . $newest_version . ' remote)'; // do not translate
		} else {
			$out_stat = VNag::STATUS_WARNING;
			$out_msg  = 'OIDplus is outdated. (' . $local_installation . ' local / ' . $newest_version . ' remote)'; // do not translate
		}
	} else {
		assert(false);
		die();
	}

	@file_put_contents($cache_file, serialize(array($out_stat, $out_msg)));
}

$job = new VNagMonitorDummy($out_stat, $out_msg);
if (OIDplus::config()->getValue('vnag_version_check_password_protected','1') == '1') {
	$job->http_visual_output = VNag::OUTPUT_NEVER;
	$job->password_out = OIDplusPageAdminVNagVersionCheck::vnag_password();
	$job->outputHTML(_L('This page contains an encrypted VNag machine-readable status.'));
} else {
	$job->http_visual_output = VNag::OUTPUT_ALWAYS;
}
if (OIDplus::getPkiStatus()) {
	$job->privkey = OIDplus::config()->getValue('oidplus_private_key');
}
$job->run();
unset($job);

# ---

function getLatestRevision() {
	try {
		$url = "https://www.oidplus.com/updates/releases.ser"; // TODO: in consts.ini
		$cont = @file_get_contents($url);
		if ($cont === false) return false;
		$ary = @unserialize($cont);
		if ($ary === false) return false;
		krsort($ary);
		$max_rev = array_keys($ary)[0];
		return 'svn-' . $max_rev;
	} catch (Exception $e) {
		return false;
	}
}

