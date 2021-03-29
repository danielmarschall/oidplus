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

include '../../../3p/vts_vnag/vnag_framework.inc.php';
include '../../../includes/oidplus.inc.php';

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

$installType = OIDplus::getInstallType();

if ($installType === 'ambigous') {
	$out_stat = VNag::STATUS_UNKNOWN;
	$out_msg  = 'Multiple version files/directories (oidplus_version.txt, .git and .svn) are existing! Therefore, the version is ambiguous!'; // do not translate
} else if ($installType === 'unknown') {
	$out_stat = VNag::STATUS_UNKNOWN;
	$out_msg  = 'The version cannot be determined, and the update needs to be applied manually!'; // do not translate
} else if (($installType === 'svn-wc') || ($installType === 'git-wc')) {
	$local_installation = OIDplus::getVersion();
	try {
		$svn = new phpsvnclient(parse_ini_file(__DIR__.'/consts.ini')['svn']);
		$newest_version = 'svn-' . $svn->getVersion();
	} catch (Exception $e) {
		$newest_version = false;
	}

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
	try {
		$svn = new phpsvnclient(parse_ini_file(__DIR__.'/consts.ini')['svn']);
		$newest_version = 'svn-' . $svn->getVersion();
	} catch (Exception $e) {
		$newest_version = false;
	}

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
}

$job = new VNagMonitorDummy($out_stat, $out_msg);
$job->http_visual_output = VNag::OUTPUT_ALWAYS;
if (OIDplus::getPkiStatus(true)) {
	$job->privkey = OIDplus::config()->getValue('oidplus_private_key');
}
$job->run();
unset($job);
