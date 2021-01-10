<?php

/*
 * This file includes:
 *
 * 1. "PHP SVN CLIENT" class
 *    Copyright (C) 2007-2008 by Sixdegrees <cesar@sixdegrees.com.br>
 *    Cesar D. Rodas
 *    https://code.google.com/archive/p/phpsvnclient/
 *    License: BSD License
 *    CHANGES by Daniel Marschall, ViaThinkSoft in 2019-2021:
 *    - The class has been customized and contains specific changes for the software "OIDplus"
 *    - Functions which are not used in the "SVN checkout" were removed.
 *      The only important functions are getVersion() and updateWorkingCopy()
 *    - The dependency class xml2array was removed and instead, SimpleXML is used
 *    - Added "revision log/comment" functionality
 *
 * 2. "xml2array" class
 *    Taken from http://www.php.net/manual/en/function.xml-parse.php#52567
 *    Modified by Martin Guppy <http://www.deadpan110.com/>
 *    CHANGES by Daniel Marschall, ViaThinkSoft in 2019:
 *    - Converted class into a single function and added that function into the phpsvnclient class
 */

if (!function_exists('_L')) {
	function _L($str, ...$sprintfArgs) {
	        $n = 1;
	        foreach ($sprintfArgs as $val) {
	                $str = str_replace("%$n", $val, $str);
	                $n++;
	        }
	        $str = str_replace("%%", "%", $str);
	        return $str;
	}
}

/**
 *  PHP SVN CLIENT
 *
 *  This class is an SVN client. It can perform read operations
 *  to an SVN server (over Web-DAV).
 *  It can get directory files, file contents, logs. All the operaration
 *  could be done for a specific version or for the last version.
 *
 *  @author Cesar D. Rodas <cesar@sixdegrees.com.br>
 *  @license BSD License
 */
class phpsvnclient {

	/*protected*/ const PHPSVN_NORMAL_REQUEST = '<?xml version="1.0" encoding="utf-8"?><propfind xmlns="DAV:"><prop><getlastmodified xmlns="DAV:"/> <checked-in xmlns="DAV:"/><version-name xmlns="DAV:"/><version-controlled-configuration xmlns="DAV:"/><resourcetype xmlns="DAV:"/><baseline-relative-path xmlns="http://subversion.tigris.org/xmlns/dav/"/><repository-uuid xmlns="http://subversion.tigris.org/xmlns/dav/"/></prop></propfind>';

	/*protected*/ const PHPSVN_VERSION_REQUEST = '<?xml version="1.0" encoding="utf-8"?><propfind xmlns="DAV:"><prop><checked-in xmlns="DAV:"/></prop></propfind>';

	/*protected*/ const PHPSVN_LOGS_REQUEST = '<?xml version="1.0" encoding="utf-8"?> <S:log-report xmlns:S="svn:"> <S:start-revision>%d</S:start-revision><S:end-revision>%d</S:end-revision><S:path></S:path><S:discover-changed-paths/></S:log-report>';

	/*public*/ const NO_ERROR = 1;
	/*public*/ const NOT_FOUND = 2;
	/*public*/ const AUTH_REQUIRED = 3;
	/*public*/ const UNKNOWN_ERROR = 4;

	public $use_cache = true;
	public $cache_dir = __DIR__.'/../../userdata/cache';

	/**
	 *  SVN Repository URL
	 *
	 *  @var string
	 *  @access private
	 */
	private $_url;

	/**
	 *  HTTP Client object
	 *
	 *  @var object
	 *  @access private
	 */
	private $_http;

	/**
	 *  Respository Version.
	 *
	 *  @access private
	 *  @var int
	 */
	private $_repVersion;

	/**
	 *  Last error number
	 *
	 *  Possible values are NO_ERROR, NOT_FOUND, AUTH_REQUIRED, UNKOWN_ERROR
	 *
	 *  @access protected
	 *  @var integer
	 */
	protected $errNro = self::NO_ERROR;
	public function getLastError() {
		return $this->errNro;
	}

	/**
	 * Number of actual revision local repository.
	 * @var Integer, Long
	 */
	private $actVersion;
	private $file_size;
	private $file_size_founded = false;

	public function __construct($url)
	{
		$http =& $this->_http;
		$http             = new http_class;
		$http->user_agent = "phpsvnclient (https://code.google.com/archive/p/phpsvnclient/)";

		$this->_url = $url;

		$this->actVersion = $this->getVersion();
	}

	/**
	 * Function for creating directories.
	 * @param $path (string) The path to the directory that will be created.
	 */
	private function createDirs($path)
	{
		$dirs = explode("/", $path);

		foreach ($dirs as $dir) {
			if ($dir != "") {
				$createDir = substr($path, 0, strpos($path, $dir) + strlen($dir));
				@mkdir($createDir);
			}
		}
	}

	/**
	 * Function for the recursive removal of directories.
	 * @param $path (string) The path to the directory to be deleted.
	 * @return (string) Returns the status of a function or function rmdir unlink.
	 */
	private function removeDirs($path)
	{
		if (is_dir($path)) {
			$entries = scandir($path);
			if ($entries === false) {
				$entries = array();
			}
			foreach ($entries as $entry) {
				if ($entry != '.' && $entry != '..') {
					$this->removeDirs($path . '/' . $entry);
				}
			}
			return @rmdir($path);
		} else {
			return @unlink($path);
		}
	}

	/**
	 *  Public Functions
	 */

	/**
	* Updates a working copy
	* @param $from_revision (string) Either a revision number or a text file with the
	*                       contents "Revision ..." (if it is a file,
	*                       the file revision will be updated if everything
	*                       was successful)
	* @param $folder        (string) SVN remote folder
	* @param $outpath       (string) Local path of the working copy
	* @param $preview       (bool) Only simulate, do not write to files
	**/
	public function updateWorkingCopy($from_revision='version.txt', $folder = '/trunk/', $outPath = '.', $preview = false)
	{
		if (!is_dir($outPath)) {
			echo _L("ERROR: Local path %1 not existing",$outPath)."\n";
			flush();
			return false;
		}

		$webbrowser_update = !is_numeric($from_revision);

		if (!is_numeric($from_revision)) {
			$version_file = $from_revision;
                        $from_revision = -1;

			if (!file_exists($version_file)) {
				echo _L("ERROR: %1 missing",$version_file)."\n";
				flush();
				return false;
			} else {
				//Obtain the number of current version number of the local copy.
				$cont = file_get_contents($version_file);
				$m = array();
				if (!preg_match('@Revision (\d+)@', $cont, $m)) { // do not translate
					echo _L("ERROR: %1 unknown format",$version_file)."\n";
					flush();
					return false;
				}
				$from_revision = $m[1];

				echo _L("Found %1 with revision information %2",basename($version_file),$from_revision)."\n";
				flush();
			}
		} else {
			$version_file = '';
		}

		$errors_happened = false;

		if ($webbrowser_update) {
			// First, do some read/write test (even if we are in preview mode, because we want to detect errors before it is too late)
			$file = $outPath . '/dummy_'.uniqid().'.tmp';
			$file = str_replace("///", "/", $file);
			if (@file_put_contents($file, 'Write Test') === false) { // do not translate
				echo (!$preview ? _L("ERROR") : _L("WARNING")).": "._L("Cannot write test file %1 ! An update through the web browser will NOT be possible.",$file)."\n";
				flush();
				if (!$preview) return false;
			}
			@unlink($file);
			if (file_exists($file)) {
				echo (!$preview ? _L("ERROR") : _L("WARNING")).": "._L("Cannot delete test file %1 ! An update through the web browser will NOT be possible.",$file)."\n";
				flush();
				if (!$preview) return false;
			}
		}

		//Get a list of objects to be updated.
		$objects_list = $this->getLogsForUpdate($folder, $from_revision + 1);
		if (!is_null($objects_list)) {
			// Output version information
			foreach ($objects_list['revisions'] as $revision) {
				$comment = empty($revision['comment']) ? _L('No comment') : $revision['comment'];
				$tex = _L("New revision %1 by %2",$revision['versionName'],$revision['creator'])." (".date('Y-m-d H:i:s', strtotime($revision['date'])).") ";
				echo trim($tex . str_replace("\n", "\n".str_repeat(' ', strlen($tex)), $comment));
				echo "\n";
			}

			// Add dirs
			sort($objects_list['dirs']); // <-- added by Daniel Marschall: Sort folder list, so that directories will be created in the correct hierarchical order
			foreach ($objects_list['dirs'] as $file) {
				if ($file != '') {
					$localPath = str_replace($folder, "", $file);
                                        $localPath = rtrim($outPath,DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($localPath,DIRECTORY_SEPARATOR);

					echo _L("Added or modified directory: %1",$file)."\n";
					flush();
					if (!$preview) {
						$this->createDirs($localPath);
						if (!is_dir($localPath)) {
							$errors_happened = true;
							echo "=> "._L("FAILED")."\n";
							flush();
						}
					}
				}
			}

			// Add files
			sort($objects_list['files']); // <-- added by Daniel Marschall: Sort list, just for cosmetic improvement
			foreach ($objects_list['files'] as $file) {
				if ($file != '') {
					$localFile = str_replace($folder, "", $file);
                                        $localFile = rtrim($outPath,DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($localFile,DIRECTORY_SEPARATOR);

					echo _L("Added or modified file: %1",$file)."\n";
					flush();
					if (!$preview) {
						$contents = $this->getFile($file);
						if (@file_put_contents($localFile, $contents) === false) {
							$errors_happened = true;
							echo "=> "._L("FAILED")."\n";
							flush();
						}
					}
				}
			}

			// Remove files
			sort($objects_list['filesDelete']); // <-- added by Daniel Marschall: Sort list, just for cosmetic improvement
			foreach ($objects_list['filesDelete'] as $file) {
				if ($file != '') {
					$localFile = str_replace($folder, "", $file);
                                        $localFile = rtrim($outPath,DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($localFile,DIRECTORY_SEPARATOR);

					echo _L("Removed file: %1",$file)."\n";
					flush();

					if (!$preview) {
						@unlink($localFile);
						if (file_exists($localFile)) {
							$errors_happened = true;
							echo "=> "._L("FAILED")."\n";
							flush();
						}
					}
				}
			}

			// Remove dirs
			// Changed by Daniel Marschall: moved this to the end, because "add/update" requests for this directory might happen before the directory gets removed
			rsort($objects_list['dirsDelete']); // <-- added by Daniel Marschall: Sort list in reverse order, so that directories get deleted in the correct hierarchical order
			foreach ($objects_list['dirsDelete'] as $file) {
				if ($file != '') {
					$localPath = str_replace($folder, "", $file);
                                        $localPath = rtrim($outPath,DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($localPath,DIRECTORY_SEPARATOR);

					echo _L("Removed directory: %1",$file)."\n";
					flush();

					if (!$preview) {
						$this->removeDirs($localPath);
						if (is_dir($localPath)) {
							$errors_happened = true;
							echo "=> "._L("FAILED")."\n";
							flush();
						}
					}
				}
			}

			// Update version file
			// Changed by Daniel Marschall: Added $errors_happened
			if (!$preview && !empty($version_file)) {
				if (!$errors_happened) {
					if (@file_put_contents($version_file, "Revision ".$this->actVersion."\n") === false) { // do not translate
						echo _L("ERROR: Could not set the revision")."\n";
						flush();
						return false;
					} else {
						echo _L("Set revision to %1", $this->actVersion) . "\n";
						flush();
						return true;
					}
				} else {
					echo _L("Revision NOT set to %1 because some files/dirs could not be updated. Please try again.",$this->actVersion)."\n";
					flush();
					return false;
				}
			} else {
				return true;
			}
		}
	}

	/**
	 *  rawDirectoryDump
	 *
	 * Dumps SVN data for $folder in the version $version of the repository.
	 *
	 *  @param string  $folder Folder to get data
	 *  @param integer $version Repository version, -1 means actual
	 *  @return array SVN data dump.
	 */
	private function rawDirectoryDump($folder = '/trunk/', $version = -1)
	{
		if ($version == -1 || $version > $this->actVersion) {
			$version = $this->actVersion;
		}

		$url = $this->cleanURL($this->_url . "/!svn/bc/" . $version . "/" . $folder . "/");
		$args = array();
		$this->initQuery($args, "PROPFIND", $url);
		$args['Body']                      = self::PHPSVN_NORMAL_REQUEST;
		$args['Headers']['Content-Length'] = strlen(self::PHPSVN_NORMAL_REQUEST);

		$cache_file = $this->cache_dir.'/svnpropfind_'.md5($url.'|'.$args['Body']).'.xml';
		if ($this->use_cache && file_exists($cache_file)) {
			$body = file_get_contents($cache_file);
		} else {
			$headers = array();
			$body = '';
			if (!$this->Request($args, $headers, $body))
				throw new Exception(_L("Cannot get rawDirectoryDump (Request failed)"));
			if (is_dir($this->cache_dir)) {
				if ($body) @file_put_contents($cache_file, $body);
			}
		}

		return self::xmlParse($body);
	}

	/**
	 *  getDirectoryFiles
	 *
	 *  Returns all the files in $folder in the version $version of
	 *  the repository.
	 *
	 *  @param string  $folder Folder to get files
	 *  @param integer $version Repository version, -1 means actual
	 *  @return array List of files.
	 */
	private function getDirectoryFiles($folder = '/trunk/', $version = -1)
	{
		$responses = $this->rawDirectoryDump($folder, $version);

		if ($responses) {
			$files = array();
			foreach ($responses as $response) {

				if ((string)$response->{'D__propstat'}->{'D__prop'}->{'lp3__baseline-relative-path'} != '') {
					$fn = (string)$response->{'D__propstat'}->{'D__prop'}->{'lp3__baseline-relative-path'};
				} else {
					$fn = (string)$response->{'D__propstat'}->{'D__prop'}->{'lp2__baseline-relative-path'};
				}

				$storeDirectoryFiles = array(
					'type' => (string)$response->{'D__href'},
					'path' => $fn,
					'last-mod' => (string)$response->{'D__propstat'}->{'D__prop'}->{'lp1__getlastmodified'},
					'version' => (string)$response->{'D__propstat'}->{'D__prop'}->{'lp1__version-name'},
					'status' => (string)$response->{'D__propstat'}->{'D__status'},
				);

				// Detect 'type' as either a 'directory' or 'file'
				if (substr($storeDirectoryFiles['type'], strlen($storeDirectoryFiles['type']) - strlen($storeDirectoryFiles['path'])) == $storeDirectoryFiles['path']) {
					// Example:
					// <D:href>/svn/oidplus/!svn/bc/504/trunk/3p/vts_fileformats/VtsFileTypeDetect.class.php</D:href>
					// <lp2:baseline-relative-path>trunk/3p/vts_fileformats/VtsFileTypeDetect.class.php</lp2:baseline-relative-path>
					$storeDirectoryFiles['type'] = 'file';
				} else {
					// Example:
					// <D:href>/svn/oidplus/!svn/bc/504/trunk/plugins/publicPages/820_login_facebook/</D:href>
					// <lp2:baseline-relative-path>trunk/plugins/publicPages/820_login_facebook</lp2:baseline-relative-path>
					$storeDirectoryFiles['type'] = 'directory';
				}

				array_push($files, $storeDirectoryFiles);
			}

			return $files;

		} else {

			throw new Exception(_L("Error communicating with SVN server"));

		}
	}

	private static function dirToArray($dir, &$result) {
		$cdir = scandir($dir);
		foreach ($cdir as $key => $value) {
			if (!in_array($value,array('.','..'))) {
				if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
					$result[] = $dir.DIRECTORY_SEPARATOR.$value.DIRECTORY_SEPARATOR;
					self::dirToArray($dir.DIRECTORY_SEPARATOR.$value, $result);
				} else {
					$result[] = $dir.DIRECTORY_SEPARATOR.$value;
				}
			}
		}
	}

	public function compareToDirectory($local_folder, $svn_folder='/trunk/', $version=-1) {
		$local_cont = array();
		self::dirToArray($local_folder, $local_cont);
		foreach ($local_cont as $key => &$c) {
			$c = str_replace('\\', '/', $c);
			$c = substr($c, strlen($local_folder));
			if (substr($c,0,1) === '/') $c = substr($c, 1);
			if ($c === '') unset($local_cont[$key]);
			if (strpos($c,'.svn/') === 0) unset($local_cont[$key]);
			if ((strpos($c,'userdata/') === 0) && ($c !== 'userdata/info.txt') && ($c !== 'userdata/.htaccess') && ($c !== 'userdata/index.html') && (substr($c,-1) !== '/')) unset($local_cont[$key]);
		}
		unset($key);
		unset($c);
		natsort($local_cont);

		$svn_cont = array();
		$contents = $this->getDirectoryTree($svn_folder, $version, true);
		foreach ($contents as $cont) {
			if ($cont['type'] == 'directory') {
				$svn_cont[] = '/'.urldecode($cont['path']).'/';
			} else if ($cont['type'] == 'file') {
				$svn_cont[] = '/'.urldecode($cont['path']);
			}
		}
		foreach ($svn_cont as $key => &$c) {
			$c = str_replace('\\', '/', $c);
			$c = substr($c, strlen($svn_folder));
			if (substr($c,0,1) === '/') $c = substr($c, 1);
			if ($c === '') unset($svn_cont[$key]);
			if ((strpos($c,'userdata/') === 0) && ($c !== 'userdata/info.txt') && ($c !== 'userdata/.htaccess') && ($c !== 'userdata/index.html') && (substr($c,-1) !== '/')) unset($svn_cont[$key]);
		}
		unset($key);
		unset($c);
		unset($contents);
		unset($cont);
		natsort($svn_cont);

		$only_svn = array_diff($svn_cont, $local_cont);
		$only_local = array_diff($local_cont, $svn_cont);
		return array($svn_cont, $local_cont);
	}

	/**
	 *  getDirectoryTree
	 *
	 *  Returns the complete tree of files and directories in $folder from the
	 *  version $version of the repository. Can also be used to get the info
	 *  for a single file or directory.
	 *
	 *  @param string  $folder Folder to get tree
	 *  @param integer $version Repository version, -1 means current
	 *  @param boolean $recursive Whether to get the tree recursively, or just
	 *  the specified directory/file.
	 *
	 *  @return array List of files and directories.
	 */
	private function getDirectoryTree($folder = '/trunk/', $version = -1, $recursive = true)
	{
		$directoryTree = array();

		if (!($arrOutput = $this->getDirectoryFiles($folder, $version)))
			return false;

		if (!$recursive)
			return $arrOutput[0];

		while (count($arrOutput) && is_array($arrOutput)) {
			$array = array_shift($arrOutput);

			array_push($directoryTree, $array);

			if (trim($array['path'], '/') == trim($folder, '/'))
				continue;

			if ($array['type'] == 'directory') {
				$walk = $this->getDirectoryFiles($array['path'], $version);
				array_shift($walk);

				foreach ($walk as $step) {
					array_unshift($arrOutput, $step);
				}
			}
		}
		return $directoryTree;
	}

	/**
	 *  Returns file contents
	 *
	 *  @param	string  $file File pathname
	 *  @param	integer	$version File Version
	 *  @return	string	File content and information, false on error, or if a
	 *              directory is requested
	 */
	private function getFile($file, $version = -1)
	{
		if ($version == -1 || $version > $this->actVersion) {
			$version = $this->actVersion;
		}

		// check if this is a directory... if so, return false, otherwise we
		// get the HTML output of the directory listing from the SVN server.
		// This is maybe a bit heavy since it makes another connection to the
		// SVN server. Maybe add this as an option/parameter? ES 23/06/08
		$fileInfo = $this->getDirectoryTree($file, $version, false);
		if ($fileInfo["type"] == "directory")
			return false;

		$args = array();
		$url = $this->cleanURL($this->_url . "/!svn/bc/" . $version . "/" . $file . "/");
		$this->initQuery($args, "GET", $url);
		$headers = array();
		$body = '';
		if (!$this->Request($args, $headers, $body))
			throw new Exception(_L("Cannot call getFile (Request failed)"));

		return $body;
	}

	private function getLogsForUpdate($file, $vini = 0, $vend = -1)
	{
		$fileLogs = array();

		if ($vend == -1) {
			$vend = $this->actVersion;
		}

		if ($vini < 0)
			$vini = 0;

		if ($vini > $vend) {
			$vini = $vend;
			echo _L("Nothing updated")."\n";
			flush();
			return null;
		}

		$cache_file = $this->cache_dir.'/svnlog_'.md5($file).'_'.$vini.'_'.$vend.'.ser';
		if ($this->use_cache && file_exists($cache_file)) {
			return unserialize(file_get_contents($cache_file));
		}

		$url = $this->cleanURL($this->_url . "/!svn/bc/" . $this->actVersion . "/" . $file . "/");
		$args = array();
		$this->initQuery($args, "REPORT", $url);
		$args['Body']                      = sprintf(self::PHPSVN_LOGS_REQUEST, $vini, $vend);
		$args['Headers']['Content-Length'] = strlen($args['Body']);
		$args['Headers']['Depth']          = 1;

		$cache_file2 = $this->cache_dir.'/svnreport_'.md5($url.'|'.$args['Body']).'.xml';
		if ($this->use_cache && file_exists($cache_file2)) {
			$body = file_get_contents($cache_file2);
		} else {
			$headers = array();
			$body = '';
			if (!$this->Request($args, $headers, $body))
				throw new Exception(_L("Cannot call getLogsForUpdate (Request failed)"));
			if (is_dir($this->cache_dir)) {
				if ($body) @file_put_contents($cache_file2, $body);
			}
		}

		$arrOutput = self::xmlParse($body);

		$revlogs = array();

		$array = array();
		foreach ($arrOutput as $xmlLogItem) {
			/*
                        <S:log-item>
			<D:version-name>164</D:version-name>
			<S:date>2019-08-13T13:12:13.915920Z</S:date>
			<D:comment>Update assistant bugfix</D:comment>
			<D:creator-displayname>daniel-marschall</D:creator-displayname>
			<S:modified-path node-kind="file" text-mods="true" prop-mods="false">/trunk/update/index.php</S:modified-path>
			<S:modified-path node-kind="file" text-mods="true" prop-mods="false">/trunk/update/phpsvnclient.inc.php</S:modified-path>
			</S:log-item>
			*/

			$versionName = '';
			$date = '';
			$comment = '';
			$creator = '';

			foreach ($xmlLogItem as $tagName => $data) {
				$tagName = strtoupper($tagName);
				$tagName = str_replace('__', ':', $tagName);
				if (($tagName == 'S:ADDED-PATH') || ($tagName == 'S:MODIFIED-PATH') || ($tagName == 'S:DELETED-PATH')) {
					if ($data->attributes()['node-kind'] == "file") {
						$array['objects'][] = array(
							'object_name' => (string)$data,
							'action' => $tagName,
							'type' => 'file'
						);
					} else if ($data->attributes()['node-kind'] == "dir") {
						$array['objects'][] = array(
							'object_name' => (string)$data,
							'action' => $tagName,
							'type' => 'dir'
						);
					}
				} else if ($tagName == 'D:VERSION-NAME') {
					$versionName = (string)$data;
				} else if ($tagName == 'S:DATE') {
					$date = (string)$data;
				} else if ($tagName == 'D:COMMENT') {
					$comment = (string)$data;
				} else if ($tagName == 'D:CREATOR-DISPLAYNAME') {
					$creator = (string)$data;
				}
			}
                        $revlogs[] = array('versionName' => $versionName,
			                   'date' => $date,
					   'comment' => $comment,
					   'creator' => $creator);
		}

		$files       = array();
		$filesDelete = array();
		$dirs        = array();
		$dirsNew     = array();
		$dirsMod     = array();
		$dirsDelete  = array();

		if (!isset($array['objects'])) $array['objects'] = array();
		foreach ($array['objects'] as $objects) {
			// This section was completely changed by Daniel Marschall
			if ($objects['type'] == "file") {
				if ($objects['action'] == "S:ADDED-PATH" || $objects['action'] == "S:MODIFIED-PATH") {
					self::xarray_add($objects['object_name'], $files);
					self::xarray_remove($objects['object_name'], $filesDelete);
				}
				if ($objects['action'] == "S:DELETED-PATH") {
					self::xarray_add($objects['object_name'], $filesDelete);
					self::xarray_remove($objects['object_name'], $files);
				}
			}
			if ($objects['type'] == "dir") {
				if ($objects['action'] == "S:ADDED-PATH") {
					self::xarray_add($objects['object_name'], $dirs);
					self::xarray_add($objects['object_name'], $dirsNew);
					self::xarray_remove($objects['object_name'], $dirsDelete);
				}
				if ($objects['action'] == "S:MODIFIED-PATH") {
					self::xarray_add($objects['object_name'], $dirs);
					self::xarray_add($objects['object_name'], $dirsMod);
					self::xarray_remove($objects['object_name'], $dirsDelete);
				}
				if ($objects['action'] == "S:DELETED-PATH") {
					// Delete files from filelist
					$files_copy = $files;
					foreach ($files_copy as $file) {
						if (strpos($file, $objects['object_name'].'/') === 0) self::xarray_remove($file, $files);
					}
					// END OF Delete files from filelist
					// Delete dirs from dirslist
					self::xarray_add($objects['object_name'], $dirsDelete);
					self::xarray_remove($objects['object_name'], $dirs);
					self::xarray_remove($objects['object_name'], $dirsMod);
					self::xarray_remove($objects['object_name'], $dirsNew);
					// END OF Delete dirs from dirslist
				}
			}
		}
		foreach ($dirsNew as $dir) {
			// For new directories, also download all its contents
			try {
				$contents = $this->getDirectoryTree($dir, $vend, true);
			} catch (Exception $e) {
				// This can happen when you update from a very old version and a directory was new which is not existing in the newest ($vend) version
				// In this case, we don't need it and can ignore the error
				$contents = array();
			}
			foreach ($contents as $cont) {
				if ($cont['type'] == 'directory') {
					$dirname = '/'.urldecode($cont['path']);
					self::xarray_add($dirname, $dirs);
					self::xarray_remove($dirname, $dirsDelete);
				} else if ($cont['type'] == 'file') {
					$filename = '/'.urldecode($cont['path']);
					self::xarray_add($filename, $files);
					self::xarray_remove($filename, $filesDelete);
				}
			}
		}
		$out                = array();
		$out['files']       = $files;
		$out['filesDelete'] = $filesDelete;
		$out['dirs']        = $dirs;
		$out['dirsDelete']  = $dirsDelete;
		$out['revisions']   = $revlogs;

		if (is_dir($this->cache_dir)) {
			$data = serialize($out);
			if ($data) @file_put_contents($cache_file, $data);
		}

		return $out;
	}

	/**
	 *  Returns the repository version
	 *
	 *  @return integer Repository version
	 *  @access public
	 */
	public function getVersion()
	{
		if ($this->_repVersion > 0)
			return $this->_repVersion;

		$this->_repVersion = -1;
		$args = array();
		$this->initQuery($args, "PROPFIND", $this->cleanURL($this->_url . "/!svn/vcc/default"));
		$args['Body']                      = self::PHPSVN_VERSION_REQUEST;
		$args['Headers']['Content-Length'] = strlen(self::PHPSVN_NORMAL_REQUEST);
		$args['Headers']['Depth']          = 0;

		$tmp = array();
		$body = '';
		if (!$this->Request($args, $tmp, $body))
			throw new Exception(_L("Cannot get repository revision (Request failed)"));

		$this->_repVersion = null;
		$m = array();
		if (preg_match('@/(\d+)\s*</D:href>@ismU', $body, $m)) {
			$this->_repVersion = $m[1];
		} else {
			throw new Exception(_L("Cannot get repository revision (RegEx failed)"));
		}

		return $this->_repVersion;
	}

	/**
	 *  Private Functions
	 */

	/**
	 *  Prepare HTTP CLIENT object
	 *
	 *  @param array &$arguments Byreferences variable.
	 *  @param string $method Method for the request (GET,POST,PROPFIND, REPORT,ETC).
	 *  @param string $url URL for the action.
	 *  @access private
	 */
	private function initQuery(&$arguments, $method, $url)
	{
		$http =& $this->_http;
		$http->GetRequestArguments($url, $arguments);
		$arguments["RequestMethod"]           = $method;
		$arguments["Headers"]["Content-Type"] = "text/xml";
		$arguments["Headers"]["Depth"]        = 1;
	}

	/**
	 *  Open a connection, send request, read header
	 *  and body.
	 *
	 *  @param Array $args Connetion's argument
	 *  @param Array &$headers Array with the header response.
	 *  @param string &$body Body response.
	 *  @return boolean True is query success
	 *  @access private
	 */
	private function Request($args, &$headers, &$body)
	{
		$args['RequestURI'] = str_replace(' ', '%20', $args['RequestURI']); //Hack to make filenames with spaces work.
		$http =& $this->_http;
		$http->Open($args);
		$http->SendRequest($args);
		$http->ReadReplyHeaders($headers);
		if (substr($http->response_status,0,1) != 2) {
			switch ($http->response_status) {
				case 404:
					$this->errNro = self::NOT_FOUND;
					break;
				case 401:
					$this->errNro = self::AUTH_REQUIRED;
					break;
				default:
					$this->errNro = self::UNKNOWN_ERROR;
					break;
			}
			//throw new Exception(_L('HTTP Error: %1 at %2,$http->response_status,$args['RequestURI']));
			$http->close();
			return false;
		}
		$this->errNro = self::NO_ERROR;
		$body         = '';
		$tbody        = '';
		for (;;) {
			$error = $http->ReadReplyBody($tbody, 1000);
			if ($error != "" || strlen($tbody) == 0) {
				break;
			}
			$body .= ($tbody);
		}
		$http->close();
		return true;
	}

	/**
	 *  Returns $url stripped of '//'
	 *
	 *  Delete "//" on URL requests.
	 *
	 *  @param string $url URL
	 *  @return string New cleaned URL.
	 *  @access private
	 */
	private function cleanURL($url)
	{
		return preg_replace("/((^:)\/\/)/", "//", $url);
	}

	private static function xmlParse($strInputXML) {
		$strInputXML = preg_replace('@<([^>]+):@ismU','<\\1__',$strInputXML);
		return simplexml_load_string($strInputXML);
	}

	/*
	  Small helper functions
	*/

	private static function xarray_add($needle, &$array) {
		$key = array_search($needle, $array);
		if ($key === false) {
			$array[] = $needle;
		}
	}

	private static function xarray_remove($needle, &$array) {
		while (true) {
			$key = array_search($needle, $array);
			if ($key === false) break;
			unset($array[$key]);
		}
	}
}
