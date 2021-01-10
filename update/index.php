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

declare(ticks=1);

@set_time_limit(0);

require_once __DIR__ . '/../includes/oidplus.inc.php';

// Note: we don't want to use OIDplus::init() in this updater (it should be independent as much as possible)
OIDplus::baseConfig(); // This call will redirect to setup if userdata/baseconfig/config.inc.php is missing

define('OIDPLUS_REPO', 'https://svn.viathinksoft.com/svn/oidplus');

?><!DOCTYPE html>
<html lang="<?php echo substr(OIDplus::getCurrentLang(),0,2); ?>">

<head>
	<title><?php echo _L('OIDplus Update'); ?></title>
	<meta name="robots" content="noindex">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="../setup/setup.min.css.php">
	<link rel="shortcut icon" type="image/x-icon" href="../favicon.ico.php">
	<?php
	if (OIDplus::baseConfig()->getValue('RECAPTCHA_ENABLED', false)) {
	?>
	<script src="https://www.google.com/recaptcha/api.js"></script>
	<?php
	}
	?>
</head>

<body>

<?php

echo '<h1>'._L('Update OIDplus').'</h1>';

if (isset($_REQUEST['update_now'])) {
	if (OIDplus::baseConfig()->getValue('RECAPTCHA_ENABLED', false)) {
		$secret = OIDplus::baseConfig()->getValue('RECAPTCHA_PRIVATE', '');
		$response = $_POST["g-recaptcha-response"];
		$verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret}&response={$response}");
		$captcha_success = json_decode($verify);
	}
	if (OIDplus::baseConfig()->getValue('RECAPTCHA_ENABLED', false) && ($captcha_success->success==false)) {
		echo '<p><font color="red"><b>'._L('CAPTCHA not successfully verified').'</b></font></p>';
		echo '<p><a href="index.php">'._L('Try again').'</a></p>';
	} else {
		if (!OIDplus::authUtils()->adminCheckPassword($_REQUEST['admin_password'])) {
			echo '<p><font color="red"><b>'._L('Wrong password').'</b></font></p>';
			echo '<p><a href="index.php">'._L('Try again').'</a></p>';
		} else {
			echo '<h2>'._L('Updating ...').'</h2>';

			ob_start();
			try {
				$svn = new phpsvnclient(OIDPLUS_REPO);
				$svn->versionFile = 'oidplus_version.txt';

				// We are caching the changed file logs here only in the preview mode.
				// Reason: We want to avoid that the "update/" page becomes an
				// DoS attack vector if there hasn't been an update for a long time,
				// and the list is very large.
				// But we don't want to use cache in the real update, because
				// otherwise it might break the system if an update is made
				// while the ViaThinkSoft server is down (because the file list
				// is cached, and therefore "delete" actions can be made, while
				// adding/downloading does not work)
				$svn->use_cache = false;

				$svn->updateWorkingCopy(realpath(__DIR__.'/../oidplus_version.txt'), '/trunk', dirname(__DIR__), false);

				$cont = ob_get_contents();
				$cont = str_replace(realpath(__DIR__.'/../'), '...', $cont);
			} catch (Exception $e) {
				$cont = _L('Error: %1',$e->getMessage());
			}
			ob_end_clean();

			echo '<pre>'.$cont.'</pre>';

			echo '<p><a href="index.php">'._L('Back to update page').'</a></p>';
			echo '<hr>';
		}
	}

} else {

	class VNagMonitorDummy extends VNag {
		private $status;
		private $content;

		public function __construct($status, $content) {
			parent::__construct();
			$this->status = $status;
			$this->content = $content;
		}

		protected function cbRun($optional_args=array()) {
			$this->setStatus($this->status);
			$this->setHeadline($this->content);
		}
	}

	echo '<p><u>'._L('There are three possibilities how to keep OIDplus up-to-date').':</u></p>';

	echo '<p><b>'._L('Method A').'</b>: '._L('Install OIDplus using the subversion tool in your SSH/Linux shell using the command <code>svn co %1</code> and update it regularly with the command <code>svn update</code> . This will automatically download the latest version and check for conflicts. Highly recommended if you have a Shell/SSH access to your webspace!',htmlentities(OIDPLUS_REPO).'/trunk').'</p>';

	echo '<p><b>'._L('Method B').'</b>: '._L('Install OIDplus using the Git client in your SSH/Linux shell using the command <code>git clone %1</code> and update it regularly with the command <code>git pull</code> . This will automatically download the latest version and check for conflicts. Highly recommended if you have a Shell/SSH access to your webspace!','https://github.com/danielmarschall/oidplus.git').'</p>';

	echo '<p><b>'._L('Method C').':</b> '._L('Install OIDplus by downloading a ZIP file from www.viathinksoft.com, which contains an SVN snapshot, and extract it to your webspace. The ZIP file contains a file named "oidplus_version.txt" which contains the SVN revision of the snapshot. This update-tool will then try to update your files on-the-fly by downloading them from the ViaThinkSoft SVN repository directly into your webspace directory. A change conflict detection is NOT implemented. It is required that the files on your webspace have create/write/delete permissions. Only recommended if you have no access to the SSH/Linux shell.').'</p>';

	echo '<hr>';

	$installType = OIDplus::getInstallType();

	if ($installType === 'ambigous') {
		echo '<font color="red">'.strtoupper(_L('Error')).': '._L('Multiple version files/directories (oidplus_version.txt, .git and .svn) are existing! Therefore, the version is ambiguous!').'</font>';
		$job = new VNagMonitorDummy(VNag::STATUS_CRITICAL, 'Multiple version files/directories (oidplus_version.txt, .git and .svn) are existing! Therefore, the version is ambiguous!'); // do not translate
		$job->http_visual_output = VNag::OUTPUT_NEVER;
		$job->run();
		unset($job);
	} else if ($installType === 'unknown') {
		echo '<font color="red">'.strtoupper(_L('Error')).': '._L('The version cannot be determined, and the update needs to be applied manually!').'</font>';
		$job = new VNagMonitorDummy(VNag::STATUS_CRITICAL, 'The version cannot be determined, and the update needs to be applied manually!'); // do not translate
		$job->http_visual_output = VNag::OUTPUT_NEVER;
		$job->run();
		unset($job);
	} else if (($installType === 'svn-wc') || ($installType === 'git-wc')) {
		if ($installType === 'svn-wc') {
			echo '<p>'._L('You are using <b>method A</b> (SVN working copy).').'</p>';
		} else {
			echo '<p>'._L('You are using <b>method B</b> (Git working copy).').'</p>';
		}

		$local_installation = OIDplus::getVersion();
		try {
			$svn = new phpsvnclient(OIDPLUS_REPO);
			$newest_version = 'svn-'.$svn->getVersion();
		} catch (Exception $e) {
			$newest_version = false;
		}

		echo _L('Local installation: %1',($local_installation ? $local_installation : _L('unknown'))).'<br>';
		echo _L('Latest published version: %1',($newest_version ? $newest_version : _L('unknown'))).'<br>';

		$requireInfo = ($installType === 'svn-wc') ? _L('shell access with svn/svnversion tool, or PDO/SQLite3 PHP extension') : _L('shell access with Git client');
		$updateCommand = ($installType === 'svn-wc') ? 'svn update' : 'git pull';

		if (!$newest_version) {
			echo '<p><font color="red">'._L('OIDplus could not determine the latest version. Probably the ViaThinkSoft server could not be reached.').'</font></p>';
		}
		else if (!$local_installation) {
			echo '<p><font color="red">'._L('OIDplus could not determine its version. (Required: %1). Please update your system manually via the "%2" command regularly.',$requireInfo,$updateCommand).'</font></p>';

			$job = new VNagMonitorDummy(VNag::STATUS_WARNING, 'OIDplus could not determine its version. Please update your system manually via the "'.$updateCommand.'" command regularly.'); // do not translate
			$job->http_visual_output = VNag::OUTPUT_NEVER;
			$job->run();
			unset($job);
		} else if ($local_installation == $newest_version) {
			echo '<p><font color="green">'._L('You are already using the latest version of OIDplus.').'</font></p>';

			$job = new VNagMonitorDummy(VNag::STATUS_OK, 'You are using the latest version of OIDplus ('.$local_installation.' local / '.$newest_version.' remote)'); // do not translate
			$job->http_visual_output = VNag::OUTPUT_NEVER;
			$job->run();
			unset($job);
		} else {
			echo '<p><font color="blue">'._L('Please enter %1 into the SSH shell to update OIDplus to the latest version.','<code>'.$updateCommand.'</code>').'</font></p>';

			echo '<h2>'._L('Preview of update %1 &rarr; %2',$local_installation,$newest_version).'</h2>';

			ob_start();
			try {
				$svn = new phpsvnclient(OIDPLUS_REPO);
				$svn->use_cache = true;
				$svn->updateWorkingCopy(str_replace('svn-', '', $local_installation), '/trunk', realpath(__DIR__.'/../'), true);
				$cont = ob_get_contents();
				$cont = str_replace(realpath(__DIR__.'/../'), '...', $cont);
			} catch (Exception $e) {
				$cont = _L('Error: %1',$e->getMessage());
			}
			ob_end_clean();

			echo '<pre>'.$cont.'</pre>';

			$job = new VNagMonitorDummy(VNag::STATUS_WARNING, 'OIDplus is outdated. ('.$local_installation.' local / '.$newest_version.' remote)'); // do not translate
			$job->http_visual_output = VNag::OUTPUT_NEVER;
			$job->run();
			unset($job);
		}
	} else if ($installType === 'svn-snapshot') {
		echo '<p>'._L('You are using <b>method C</b> (Snapshot ZIP file with oidplus_version.txt file).').'</p>';

		$local_installation = OIDplus::getVersion();
		try {
			$svn = new phpsvnclient(OIDPLUS_REPO);
			$newest_version = 'svn-'.$svn->getVersion();
		} catch (Exception $e) {
			$newest_version = false;
		}

		echo _L('Local installation: %1',($local_installation ? $local_installation : _L('unknown'))).'<br>';
		echo _L('Latest published version: %1',($newest_version ? $newest_version : _L('unknown'))).'<br>';

		if (!$newest_version) {
			echo '<p><font color="red">'._L('OIDplus could not determine the latest version. Probably the ViaThinkSoft server could not be reached.').'</font></p>';
		}
		else if ($local_installation == $newest_version) {
			echo '<p><font color="green">'._L('You are already using the latest version of OIDplus.').'</font></p>';

			$job = new VNagMonitorDummy(VNag::STATUS_OK, 'You are using the latest version of OIDplus ('.$local_installation.' local / '.$newest_version.' remote)'); // do not translate
			$job->http_visual_output = VNag::OUTPUT_NEVER;
			$job->run();
			unset($job);
		} else {
			echo '<p><font color="blue">'._L('To update your OIDplus system, please enter the administrator password and click the button "Update NOW".').'</font></p>';
			echo '<p><font color="red">'.strtoupper(_L('Warning')).': '._L('Please make a backup of your files before updating. In case of an error, the OIDplus system (including this update-assistant) might become unavailable. Also, since the web-update does not contain collision-detection, changes you have applied (like adding, removing or modified files) might get reverted/lost! In case the update fails, you can download and extract the complete <a href="https://www.viathinksoft.com/projects/oidplus">SVN-Snapshot ZIP file</a> again. Since all your data should lay inside the folder "userdata", this should be safe.').'</font></p>';
			echo '<form method="POST" action="index.php">';

			if (OIDplus::baseConfig()->getValue('RECAPTCHA_ENABLED', false)) {
				echo '<noscript>';
				echo '<p><font color="red">'._L('You need to enable JavaScript to solve the CAPTCHA.').'</font></p>';
				echo '</noscript>';
				echo '<script> grecaptcha.render(document.getElementById("g-recaptcha"), { "sitekey" : "'.OIDplus::baseConfig()->getValue('RECAPTCHA_PUBLIC', '').'" }); </script>';
				echo '<div id="g-recaptcha" class="g-recaptcha" data-sitekey="'.OIDplus::baseConfig()->getValue('RECAPTCHA_PUBLIC', '').'"></div>';
			}

			echo '<input type="hidden" name="update_now" value="1">';
			echo '<input type="password" name="admin_password">';
			echo '<input type="submit" value="'._L('Update NOW').'">';
			echo '</form>';

			echo '<h2>'._L('Preview of update %1 &rarr; %2',$local_installation,$newest_version).'</h2>';

			ob_start();
			try {
				$svn = new phpsvnclient(OIDPLUS_REPO);
				$svn->use_cache = true;
				$svn->updateWorkingCopy(realpath(__DIR__.'/../oidplus_version.txt'), '/trunk', realpath(__DIR__.'/../'), true);
				$cont = ob_get_contents();
				$cont = str_replace(realpath(__DIR__.'/../'), '...', $cont);
			} catch (Exception $e) {
				$cont = _L('Error: %1',$e->getMessage());
			}
			ob_end_clean();

			echo '<pre>'.$cont.'</pre>';

			$job = new VNagMonitorDummy(VNag::STATUS_WARNING, 'OIDplus is outdated. ('.$local_installation.' local / '.$newest_version.' remote)'); // do not translate
			$job->http_visual_output = VNag::OUTPUT_NEVER;
			$job->run();
			unset($job);
		}
	}

	echo '<hr>';

	echo '<p><input type="button" onclick="document.location=\'../\'" value="'._L('Go back to OIDplus').'"></p>';

	echo '<br><h2>'._L('File Completeness Check').'</h2>';

	echo '<p>'._L('With this optional tool, you can check if your OIDplus installation is complete and no files are missing.').'</p>';

	echo '<p>'._L('Please enter your administrator password to run the tool.').'</p>';

	echo '<form method="POST" action="check.php">';
	if (OIDplus::baseConfig()->getValue('RECAPTCHA_ENABLED', false)) {
		echo '<noscript>';
		echo '<p><font color="red">'._L('You need to enable JavaScript to solve the CAPTCHA.').'</font></p>';
		echo '</noscript>';
		echo '<script> grecaptcha.render(document.getElementById("g-recaptcha"), { "sitekey" : "'.OIDplus::baseConfig()->getValue('RECAPTCHA_PUBLIC', '').'" }); </script>';
		echo '<div id="g-recaptcha" class="g-recaptcha" data-sitekey="'.OIDplus::baseConfig()->getValue('RECAPTCHA_PUBLIC', '').'"></div>';
	}
	if (!isset($local_installation)) $local_installation = 'svn-';
	echo '<input type="hidden" name="svn_version" value="'.(substr($local_installation,strlen('svn-'))).'">';
	echo '<input type="password" name="admin_password">';
	echo '<input type="submit" value="'._L('Check').'">';
	echo '<p>'._L('Attention: This will take some time!').'</p>';
	echo '</form>';

	echo '<h2>'._L('VNag integration').'</h2>';

	echo '<p>'._L('Did you know that this page contains an invisible VNag tag? You can watch this page using the "webreader" plugin of VNag, and then monitor it with any Nagios compatible software! <a href="https://www.viathinksoft.com/projects/vnag">More information</a>.').'</p>';
}

?>

</body>
</html>
