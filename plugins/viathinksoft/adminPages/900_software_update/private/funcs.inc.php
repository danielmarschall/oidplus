<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2024 Daniel Marschall, ViaThinkSoft
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

/**
 * @param string &$outscript
 * @param string $dir_old
 * @param string $dir_new
 * @param string|null $basepath_old
 * @param string|null $basepath_new
 * @return void
 */
function getDirContents_del(string &$outscript, string $dir_old, string $dir_new, string $basepath_old=null, string $basepath_new=null) {
	if (is_null($basepath_old)) $basepath_old = $dir_old;
	$basepath_old = my_realpath($basepath_old) . DIRECTORY_SEPARATOR;
	if ($basepath_old == '/') {
		fwrite(STDERR, 'ARG');
		die();
	}

	$dir_old = my_realpath($dir_old) . DIRECTORY_SEPARATOR;
	$dir_new = my_realpath($dir_new) . DIRECTORY_SEPARATOR;
	$files_old = my_scandir($dir_old);
	//$files_new = my_scandir($dir_new);

	foreach ($files_old as $file_old) {
		if ($file_old === '.') continue;
		if ($file_old === '..') continue;
		if ($file_old === '.svn') continue;
		if ($file_old === '.git') continue;

		$path_old = my_realpath($dir_old . DIRECTORY_SEPARATOR . $file_old);
		$path_new = my_realpath($dir_new . DIRECTORY_SEPARATOR . $file_old);

		$xpath_old = substr($path_old, strlen($basepath_old));

		if (is_dir($path_old)) {
			getDirContents_del($outscript, $path_old, $path_new, $basepath_old, $basepath_new);
		}

		// Note: We don't warn if a file-to-be-deleted has vanished. It would not be necessary to warn about it
		if (is_dir($path_old) && !is_dir($path_new)) {
			$outscript .= "// Dir deleted: $xpath_old\n";
			$outscript .= "@rmdir('$xpath_old');\n";
			$outscript .= "if (is_dir('$xpath_old')) {\n";
			$outscript .= "\twarn('Directory could not be deleted (was not empty?): $xpath_old');\n";
			$outscript .= "}\n";
			$outscript .= "\n";
		} else if (is_file($path_old) && !is_file($path_new)) {
			$outscript .= "// File deleted: $xpath_old\n";
			$outscript .= "@unlink('$xpath_old');\n";
			$outscript .= "if (is_file('$xpath_old')) {\n";
			$outscript .= "\twarn('File could not be deleted: $xpath_old');\n";
			$outscript .= "}\n";
			$outscript .= "\n";
		}
	}
}

/**
 * @param string &$outscript
 * @param string $dir_old
 * @param string $dir_new
 * @param string|null $basepath_old
 * @param string|null $basepath_new
 * @return void
 * @throws Exception
 */
function getDirContents_diff(string &$outscript, string $dir_old, string $dir_new, string $basepath_old=null, string $basepath_new=null) {
	if (is_null($basepath_old)) $basepath_old = $dir_old;
	$basepath_old = my_realpath($basepath_old) . DIRECTORY_SEPARATOR;
	if ($basepath_old == '/') {
		fwrite(STDERR, 'ARG');
		die();
	}

	$dir_old = my_realpath($dir_old) . DIRECTORY_SEPARATOR;
	$dir_new = my_realpath($dir_new) . DIRECTORY_SEPARATOR;
	$files_old = my_scandir($dir_old);
	$files_new = my_scandir($dir_new);

	foreach ($files_old as $file_old) {
		if ($file_old === '.') continue;
		if ($file_old === '..') continue;
		if ($file_old === '.svn') continue;
		if ($file_old === '.git') continue;

		$path_old = my_realpath($dir_old . DIRECTORY_SEPARATOR . $file_old);
		$path_new = my_realpath($dir_new . DIRECTORY_SEPARATOR . $file_old);

		$xpath_old = substr($path_old, strlen($basepath_old));

		if (is_file($path_old) && is_file($path_new)) {
			if (file_get_contents($path_old) != file_get_contents($path_new)) {
				$outscript .= "// Files different: $xpath_old\n";

				global $func_idx;
				$func_idx++;
				$outscript .= "function writefile_".$func_idx."() {\n";
				special_save_file($xpath_old, $path_new, $outscript, "\t");
				// Commented out because both SVN and GIT choose the current timestamp and not the original file timestamp
				//$outscript .= "\t@touch('$xpath_old',".filemtime($path_new).");\n";
				$outscript .= "}\n";

				$outscript .= "if (!is_file('$xpath_old')) {\n";
				$outscript .= "\twarn('File has vanished! Will re-create it: $xpath_old');\n";
				$outscript .= "\twritefile_".$func_idx."();\n";
				$outscript .= "\tif (!is_file('$xpath_old')) {\n";
				$outscript .= "\t\twarn('File cannot be created (not existing): $xpath_old');\n";
				$outscript .= "\t} else if (sha1_file('$xpath_old') != '".sha1_file($path_new)."') {\n";
				$outscript .= "\t\twarn('File cannot be created (checksum mismatch): $xpath_old');\n";
				$outscript .= "\t} else if ((DIRECTORY_SEPARATOR === '/') && !@chmod('$xpath_old', 0".sprintf('%o', fileperms($path_new) & 0777).")) {\n";
				$outscript .= "\t\twarn('Could not change file permissions of ".$xpath_old."');\n";
				$outscript .= "\t}\n";

				$outscript .= "} else {\n";

				$outscript .= "\tif (@sha1_file('$xpath_old') !== '".sha1_file($path_new)."') {\n"; // it is possible that the file is already updated (e.g. by a manual hotfix)
				$outscript .= "\t\tif (@sha1_file('$xpath_old') !== '".sha1_file($path_old)."') {\n";
				$outscript .= "\t\t\twarn('File was modified. Will overwrite the changes now: $xpath_old');\n";
				$outscript .= "\t\t\t\$tmp = pathinfo('$xpath_old');\n";
				$outscript .= "\t\t\t\$backup_name = \$tmp['dirname'].DIRECTORY_SEPARATOR.\$tmp['filename'].'.'.date('Ymdhis',@filemtime('$xpath_old')).(isset(\$tmp['extension']) ? '.'.\$tmp['extension'] : '');\n";
				$outscript .= "\t\t\twarn('Creating a backup as '.\$backup_name);\n";
				$outscript .= "\t\t\tif (!@copy('$xpath_old', \$backup_name)) {\n";
				$outscript .= "\t\t\t\twarn('Creation of backup failed');\n";
				$outscript .= "\t\t\t}\n";
				$outscript .= "\t\t}\n";
				$outscript .= "\t\twritefile_".$func_idx."();\n";
				$outscript .= "\t\tif (@sha1_file('$xpath_old') !== '".sha1_file($path_new)."') {\n";
				$outscript .= "\t\t\twarn('File cannot be written (checksum mismatch): $xpath_old');\n";
				$outscript .= "\t\t}\n";
				$outscript .= "\t}\n";

				$outscript .= "}\n";
				$outscript .= "\n";
			}
			if ((fileperms($path_old) & 0777) != (fileperms($path_new) & 0777)) {
				$outscript .= "// Different file chmod: $xpath_old\n";
				$outscript .= "if ((DIRECTORY_SEPARATOR === '/') && !@chmod('$xpath_old', 0".sprintf('%o', fileperms($path_new) & 0777).")) {\n";
				$outscript .= "\twarn('Could not change file permissions of ".$xpath_old."');\n";
				$outscript .= "}\n";
				$outscript .= "\n";
			}
		} else if (is_dir($path_old) && is_dir($path_new)) {
			/*
			$outscript .= "// Verify that directory exists: $xpath_old\n";
			$outscript .= "if (!is_dir('$xpath_old')) {\n";
			$outscript .= "\twarn('Directory has vanished! Will re-create it: $xpath_old');\n";
			$outscript .= "\t@mkdir('$xpath_old');\n";
			$outscript .= "\tif (!is_dir('$xpath_old')) {\n";
			$outscript .= "\t\twarn('Directory could not be created: $xpath_old');\n";
			$outscript .= "\t}\n";
			$outscript .= "}\n";
			$outscript .= "\n";
			*/

			if ((fileperms($path_old) & 0777) != (fileperms($path_new) & 0777)) {
				$outscript .= "// Different dir chmod: $xpath_old\n";
				$outscript .= "if ((DIRECTORY_SEPARATOR === '/') && !@chmod('$xpath_old', 0".sprintf('%o', fileperms($path_new) & 0777).")) {\n";
				$outscript .= "\twarn('Could not change dir permissions of ".$xpath_old."');\n";
				$outscript .= "}\n";
				$outscript .= "\n";
			}
		}

		if (is_dir($path_old)) {
			getDirContents_diff($outscript, $path_old, $path_new, $basepath_old, $basepath_new);
		}
	}
}

/**
 * @param string &$outscript
 * @param string $dir_old
 * @param string $dir_new
 * @param string|null $basepath_old
 * @param string|null $basepath_new
 * @return void
 * @throws Exception
 */
function getDirContents_add(string &$outscript, string $dir_old, string $dir_new, string $basepath_old=null, string $basepath_new=null) {
	if (is_null($basepath_new)) $basepath_new = $dir_new;
	$basepath_new = my_realpath($basepath_new) . DIRECTORY_SEPARATOR;
	if ($basepath_new == '/') {
		fwrite(STDERR, 'ARG');
		die();
	}

	$dir_old = my_realpath($dir_old) . DIRECTORY_SEPARATOR;
	$dir_new = my_realpath($dir_new) . DIRECTORY_SEPARATOR;
	//$files_old = my_scandir($dir_old);
	$files_new = my_scandir($dir_new);

	foreach ($files_new as $file_new) {
		if ($file_new === '.') continue;
		if ($file_new === '..') continue;
		if ($file_new === '.svn') continue;
		if ($file_new === '.git') continue;

		$path_old = my_realpath($dir_old . DIRECTORY_SEPARATOR . $file_new);
		$path_new = my_realpath($dir_new . DIRECTORY_SEPARATOR . $file_new);

		$xpath_new = substr($path_new, strlen($basepath_new));

		if (is_dir($path_new) && !is_dir($path_old)) {
			// Note: We are not warning if the dir was already created by the user
			$outscript .= "// Dir added: $xpath_new\n";
			$outscript .= "@mkdir('$xpath_new');\n";
			$outscript .= "if (!is_dir('$xpath_new')) {\n";
			$outscript .= "\twarn('Directory could not be created: $xpath_new');\n";
			$outscript .= "} else if ((DIRECTORY_SEPARATOR === '/') && !@chmod('$xpath_new', 0".sprintf('%o', fileperms($path_new) & 0777).")) {\n";
			$outscript .= "\twarn('Could not change directory permissions of ".$xpath_new."');\n";
			$outscript .= "}\n";
			$outscript .= "\n";

			// we create it locally, so that the recursive code still works
			mkdir($dir_old . DIRECTORY_SEPARATOR . $file_new);
			$path_old = my_realpath($dir_old . DIRECTORY_SEPARATOR . $file_new);

		} else if (is_file($path_new) && !is_file($path_old)) {
			$outscript .= "// File added: $xpath_new\n";

			global $func_idx;
			$func_idx++;
			$outscript .= "function writefile_".$func_idx."() {\n";
			special_save_file($xpath_new, $path_new, $outscript, "\t");
			// Commented out because both SVN and GIT choose the current timestamp and not the original file timestamp
			//$outscript .= "\t@touch('$xpath_new',".filemtime($path_new).");\n";
			$outscript .= "}\n";

			// Note: We will not warn if the file was created and is exactly the file we want
			$outscript .= "if (is_file('$xpath_new') && (sha1_file('$xpath_new') != '".sha1_file($path_new)."')) {\n";
			$outscript .= "\twarn('File was created by someone else. Will overwrite the changes now: $xpath_new');\n";
			$outscript .= "\t\$tmp = pathinfo('$xpath_new');\n";
			$outscript .= "\t\$backup_name = \$tmp['dirname'].DIRECTORY_SEPARATOR.\$tmp['filename'].'.'.date('Ymdhis',@filemtime('$xpath_new')).(isset(\$tmp['extension']) ? '.'.\$tmp['extension'] : '');\n";
			$outscript .= "\twarn('Creating a backup as '.\$backup_name);\n";
			$outscript .= "\tif (!@copy('$xpath_new', \$backup_name)) {\n";
			$outscript .= "\t\twarn('Creation of backup failed');\n";
			$outscript .= "\t}\n";
			$outscript .= "}\n";

			$outscript .= "writefile_".$func_idx."();\n";
			$outscript .= "if (!is_file('$xpath_new')) {\n";
			$outscript .= "\twarn('File cannot be created (not existing): $xpath_new');\n";
			$outscript .= "} else if (sha1_file('$xpath_new') != '".sha1_file($path_new)."') {\n";
			$outscript .= "\twarn('File cannot be created (checksum mismatch): $xpath_new');\n";
			$outscript .= "} else if ((DIRECTORY_SEPARATOR === '/') && !@chmod('$xpath_new', 0".sprintf('%o', fileperms($path_new) & 0777).")) {\n";
			$outscript .= "\twarn('Could not change file permissions of ".$xpath_new."');\n";
			$outscript .= "}\n";
			$outscript .= "\n";
		}

		if (is_dir($path_new)) {
			getDirContents_add($outscript, $path_old, $path_new, $basepath_old, $basepath_new);
		}
	}
}

/**
 * @param string &$outscript
 * @param string $dir_old
 * @param string $dir_new
 * @return void
 * @throws Exception
 */
function getDirContents(string &$outscript, string $dir_old, string $dir_new) {
	global $func_idx;
	$func_idx = 0;
	getDirContents_add($outscript, $dir_old, $dir_new);
	getDirContents_diff($outscript, $dir_old, $dir_new);
	getDirContents_del($outscript, $dir_old, $dir_new);
}

/**
 * @param string $version
 * @param string $dir
 * @return void
 */
function hotfix_dir(string $version, string $dir) {
	if ($version == "2.0.0.699") {
		// Fix syntax error that lead to a stalled update!
		$file = $dir.'/plugins/viathinksoft/adminPages/900_software_update/OIDplusPageAdminSoftwareUpdate.class.php';
		$cont = file_get_contents($file);
		$cont = str_replace("urlencode('oidplus:system_file_check',OIDplus::getEditionInfo()['downloadpage']))",
		                    "urlencode('oidplus:system_file_check'),OIDplus::getEditionInfo()['downloadpage'])",
		                    $cont);
		file_put_contents($file, $cont);

		// Fix syntax error that lead to a stalled update!
		$file = $dir.'/plugins/viathinksoft/adminPages/901_vnag_version_check/vnag.php';
		$cont = file_get_contents($file);
		$cont = str_replace("\t\tOIDplus::getEditionInfo()", "", $cont);
		file_put_contents($file, $cont);
	}
	if ($version == "2.0.0.830") {
		// Fix bug that caused system ID to get lost
		$file = $dir.'/includes/classes/OIDplus.class.php';
		$cont = file_get_contents($file);
		$cont = str_replace("if ((\$passphrase === false) || !is_privatekey_encrypted(\$privKey)) {",
		                    "if ((\$passphrase === false) || !is_privatekey_encrypted(OIDplus::config()->getValue('oidplus_private_key'))) {",
		                    $cont);
		file_put_contents($file, $cont);
	}
	if ($version == "2.0.0.856") {
		// Fix runtime error that lead to a stalled update!
		$file = $dir.'/includes/classes/OIDplus.class.php';
		$cont = file_get_contents($file);
		$cont = str_replace('$this->recanonizeObjects();', '', $cont);
		file_put_contents($file, $cont);
	}
	if ($version == "2.0.0.1108") {
		// Fix runtime error that lead to a stalled update!
		$file = $dir.'/vendor/danielmarschall/php_utils/vts_crypt.inc.php';
		$cont = file_get_contents($file);
		$cont = str_replace('echo "OK, password $password\n";', '', $cont);
		file_put_contents($file, $cont);
	}
	if ($version == "2.0.0.1186") {
		// Fix runtime error that lead to a stalled update!
		$file = $dir.'/includes/classes/OIDplusGui.class.php';
		$cont = file_get_contents($file);
		$cont = str_replace('public function html_exception_handler', 'public static function html_exception_handler', $cont);
		file_put_contents($file, $cont);
	}
	if ($version == "2.0.0.1248") {
		// Possible error message that interrupts AJAX contents if error output is enabled
		$file = $dir.'/vendor/danielmarschall/uuid_mac_utils/includes/mac_utils.inc.php';
		$cont = file_get_contents($file);
		$cont = str_replace(' inet_pton', ' @inet_pton', $cont);
		file_put_contents($file, $cont);

		// A PHP 8 function was used, making the update impossible on PHP 7.x systems
		$file = $dir.'/plugins/viathinksoft/objectTypes/mac/OIDplusObjectTypePluginMac.class.php';
		$cont = file_get_contents($file);
		$cont = str_replace("str_contains(\$static_node_id, ':')", "(strpos(\$static_node_id, ':') !== false)", $cont);
		$cont = str_replace("str_contains(\$static_node_id, '-')", "(strpos(\$static_node_id, '-') !== false)", $cont);
		file_put_contents($file, $cont);
	}
	if ($version == "2.0.0.1317") {
		// Exception is thrown when audience is wrong; therefore the user must clear their browset cache after update to get rid of the error message
		$file = $dir.'/includes/classes/OIDplusAuthContentStoreJWT.class.php';
		$cont = file_get_contents($file);
		$wrong = 'throw new OIDplusException(_L(\'Token has wrong audience: Given %1 but expected %2.\'), $contentProvider->getValue(\'aud\',\'\'), $contentProvider->getAudIss());';
		$correct = 'throw new OIDplusException(_L(\'Token has wrong audience: Given %1 but expected %2.\', $contentProvider->getValue(\'aud\',\'\'), $contentProvider->getAudIss()));';
		$cont = str_replace($wrong, $correct, $cont);
		file_put_contents($file, $cont);
	}
}

/**
 * @param string $data
 * @param int $width
 * @return string
 */
function split_equal_length(string $data, int $width=65): string {
	$res = '';
	for ($i=0; $i<strlen($data); $i+=$width) {
		$res .= substr($data, $i, $width)."\n";
	}
	return $res;
}

/**
 * @param string $out_file
 * @param string $in_file
 * @param string $res
 * @param string $line_prefix
 * @param int $width
 * @return void
 * @throws Exception
 */
function special_save_file(string $out_file, string $in_file, string &$res, string $line_prefix, int $width=50) {
	$handle = @fopen($in_file, "rb");
	if (!$handle) {
		throw new Exception("Cannot open file $in_file");
	}
	$res .= $line_prefix."\$fp = @fopen('$out_file', 'w');\n";
	$res .= $line_prefix."if (\$fp === false) {\n"; // can happen if dir does not exist (e.g. plugin deleted), or missing write permissions
	$res .= $line_prefix."\twarn('File $out_file cannot be written');\n";
	$res .= $line_prefix."\treturn;\n";
	$res .= $line_prefix."}\n";

	while (!feof($handle)) {
		// important: must be a multiple of 3, otherwise we have base64 paddings!
		$buffer = fread($handle, $width*3);
		$base64 = base64_encode($buffer);
		$res .= $line_prefix."@fwrite(\$fp, base64_decode('".$base64."'));\n";
	}

	$res .= $line_prefix."@fclose(\$fp);\n";
	fclose($handle);
}

/**
 * @param string $name
 * @return string
 */
function my_realpath(string $name): string {
	$ret = realpath($name);
	return ($ret === false) ? $name : $ret;
}

/**
 * @param string $dir
 * @return array
 */
function my_scandir(string $dir): array {
	$ret = @scandir($dir);
	if ($ret === false) return array();
	return $ret;
}

/**
 * @param string $outdir_old
 * @param string $outdir_new
 * @param string $outfile
 * @param string $version
 * @param string $prev_version
 * @param string $priv_key
 * @return void
 */
function oidplus_create_changescript($outdir_old, $outdir_new, $outfile, $prev_version, $version, $priv_key) {
	$outscript  = "<?php\n";
	$outscript .= "\n";
	$outscript .= "/*\n";
	$outscript .= " * OIDplus 2.0\n";
	$outscript .= " * Copyright 2019 - ".date('Y')." Daniel Marschall, ViaThinkSoft\n";
	$outscript .= " *\n";
	$outscript .= " * Licensed under the Apache License, Version 2.0 (the \"License\");\n";
	$outscript .= " * you may not use this file except in compliance with the License.\n";
	$outscript .= " * You may obtain a copy of the License at\n";
	$outscript .= " *\n";
	$outscript .= " *     http://www.apache.org/licenses/LICENSE-2.0\n";
	$outscript .= " *\n";
	$outscript .= " * Unless required by applicable law or agreed to in writing, software\n";
	$outscript .= " * distributed under the License is distributed on an \"AS IS\" BASIS,\n";
	$outscript .= " * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.\n";
	$outscript .= " * See the License for the specific language governing permissions and\n";
	$outscript .= " * limitations under the License.\n";
	$outscript .= " */\n";
	$outscript .= "\n";
	$outscript .= "function info(\$str) { echo \"INFO: \$str\\n\"; }\n";
	$outscript .= "function warn(\$str) { echo \"WARNING: \$str\\n\"; }\n";
	$outscript .= "function err(\$str) { die(\"FATAL ERROR: \$str\\n\"); }\n";
	$outscript .= "\n";
	$outscript .= "@set_time_limit(0);\n";
	$outscript .= "\n";
	$outscript .= "@header('Content-Type: text/plain');\n";
	$outscript .= "\n";
	$outscript .= "chdir(__DIR__);\n";
	if (version_compare($prev_version,"2.0.1") >= 0) {
		// Version check see also OIDplus::getVersion()
		$outscript .= "\$cont = @file_get_contents('changelog.json.php');\n";
		$outscript .= "if (\$cont === false) err('Cannot read changelog.json.php');\n";
		$outscript .= "\$json = @json_decode(\$cont, true);\n";
		$outscript .= "if (\$json === null) err('Cannot parse changelog.json.php');\n";
		$outscript .= "\$latest_version = false;\n";
		$outscript .= "foreach (\$json as \$v) {\n";
		$outscript .= "\tif (isset(\$v['version']) && (substr(\$v['version'], -4) != '-dev')) {\n";
		$outscript .= "\t\t\$latest_version = \$v['version'];\n";
		$outscript .= "\t\tbreak; // the first item is the latest version\n";
		$outscript .= "\t}\n";
		$outscript .= "}\n";
		$outscript .= "if (\$latest_version != '$prev_version') err('This update can only be applied to OIDplus version $prev_version!');\n";
	} else if (version_compare($version, "2.0.0.662") >= 0) {
		$rev = (int)str_replace('2.0.0.', '', $version);
		$prev_rev = (int)str_replace('2.0.0.', '', $prev_version);
		$outscript .= "if (trim(@file_get_contents('.version.php')) !== '<?php // Revision $prev_rev') {\n";
		$outscript .= "\terr('This update can only be applied to OIDplus version 2.0.0.$prev_rev!');\n";
		$outscript .= "}\n";
	} else {
		$rev = (int)str_replace('2.0.0.', '', $version);
		$prev_rev = (int)str_replace('2.0.0.', '', $prev_version);
		$outscript .= "if (trim(@file_get_contents('oidplus_version.txt')) !== 'Revision $prev_rev') {\n";
		$outscript .= "\terr('This update can only be applied to OIDplus version 2.0.0.$prev_rev!');\n";
		$outscript .= "}\n";
	}
	$outscript .= "\n";
	/*
	if (version_compare($version,"2.0.999.999") >= 0) {
		... once we require PHP 7.1, we add the requirement here
		... also if we require fancy new PHP modules, we must add it here
		... the checks avoid that someone breaks their OIDplus installation if they update
	} else
	*/if (version_compare($version,"2.0.0.2") >= 0) {
		// Version 2.0.0.2 (SVN Revision 2) requires PHP 7.0.0
		$outscript .= "if (version_compare(PHP_VERSION, '7.0.0') < 0) {\n";
		$outscript .= "\terr('You need PHP Version 7.0 to update to this version');\n";
		$outscript .= "}\n";
	}
	$outscript .= "\n";
	//$outscript .= "info('Update to OIDplus version $version running...');\n";
	//$outscript .= "\n";
	getDirContents($outscript, $outdir_old, $outdir_new);
	$outscript .= "\n";
	if (version_compare($version,"2.0.1") >= 0) {
		// Nothing to do in order to set the version, because changelog.json.php contains this information
		if (version_compare($version, "2.0.1") == 0) {
			$outscript .= "@unlink('.version.php');\n";
			// This is just a warning, not an error, because the existance of that file does not cause an error anymore
			$outscript .= "if (is_file('.version.php')) warn('Could not delete .version.php! Please delete it manually');\n";
		}
	} else if (version_compare($version, "2.0.0.661") >= 0) {
		$rev = (int)str_replace('2.0.0.', '', $version);
		$outscript .= "file_put_contents('.version.php', \"<?php // Revision $rev\\n\");\n";
		$outscript .= "if (trim(@file_get_contents('.version.php')) !== '<?php // Revision $rev') err('Could not write to .version.php!');\n";
		if (version_compare($version, "2.0.0.661") == 0) {
			$outscript .= "@unlink('oidplus_version.txt');\n";
			$outscript .= "if (is_file('oidplus_version.txt')) err('Could not delete oidplus_version.txt! Please delete it manually');\n";
		}
	} else {
		$rev = (int)str_replace('2.0.0.', '', $version);
		$outscript .= "file_put_contents('oidplus_version.txt', \"Revision $rev\\n\");\n";
		$outscript .= "if (trim(@file_get_contents('oidplus_version.txt')) !== 'Revision $rev') err('Could not write to oidplus_version.txt!');\n";
	}
	$outscript .= "\n";
	$outscript .= "\n";
	//$outscript .= "info('Update to OIDplus version $version done!');\n";
	$outscript .= "echo 'DONE'; // This exact string will be compared in Update v3\n";
	$outscript .= "\n";
	$outscript .= "@unlink(__FILE__);\n";
	$outscript .= "if (is_file(__FILE__)) warn('Could not delete '.basename(__FILE__).'! Please delete it manually');\n";
	$outscript .= "\n";

	// Now add digital signature

	if (strpos($outscript, '<?php') === false) {
		echo "Not a PHP file\n"; // Should not happen
		return;
	}

	$naked = preg_replace('@<\?php /\* <ViaThinkSoftSignature>(.+)</ViaThinkSoftSignature> \*/ \?>\n@ismU', '', $outscript);

	if (version_compare($prev_version, "2.0.1") < 0) {
		$rev = (int)str_replace('2.0.0.', '', $prev_version);
		$hash = hash("sha256", $naked."update_".($rev)."_to_".($rev+1).".txt");
	} else {
		$hash = hash("sha256", $naked.basename($outfile));
	}

	$pkeyid = openssl_pkey_get_private('file://'.$priv_key);
	openssl_sign($hash, $signature, $pkeyid, OPENSSL_ALGO_SHA256);
	openssl_free_key($pkeyid);

	if (!$signature) {
		echo "ERROR: Signature failed\n";
		return;
	}

	$sign_line = '<?php /* <ViaThinkSoftSignature>'."\n".split_equal_length(base64_encode($signature),65).'</ViaThinkSoftSignature> */ ?>';

	// We have to put the signature at the beginning, because we don't know if the end of the file lacks a PHP closing tag
	if (substr($outscript,0,2) === '#!') {
		// Preserve shebang
		$shebang_pos = strpos($naked, "\n");
		$shebang = substr($naked, 0, $shebang_pos);
		$rest = substr($naked, $shebang_pos+1);
		$outscript = $shebang."\n".$sign_line."\n".$rest;
	} else {
		$outscript = $sign_line."\n".$naked;
	}

	// Write the file

	file_put_contents($outfile, $outscript);

	$ec = -1;
	$out = array();
	exec('php -l '.escapeshellarg($outfile), $out, $ec);
	if ($ec != 0) {
		fwrite(STDERR, "STOP! $outfile PHP syntax error!\n");
		@unlink($outfile);
		return;
	}
	file_put_contents($outfile.'.gz', gzencode($outscript));
}
