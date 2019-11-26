<?php

if (!defined('IN_OIDPLUS')) die();

/*
 * This file includes:
 *
 * 1. "PHP SVN CLIENT" class
 *    Copyright (C) 2007-2008 by Sixdegrees <cesar@sixdegrees.com.br>
 *    Cesar D. Rodas
 *    https://code.google.com/archive/p/phpsvnclient/
 *    License: BSD License
 *    CHANGES by Daniel Marschall, ViaThinkSoft in 2019:
 *    - The class has been customized and contains specific changes for the software "OIDplus"
 *    - Functions which are not used in the "SVN checkout" were removed.
 *      The only important functions are getVersion() and updateWorkingCopy()
 *    - The dependency class xml2array was converted from a class into a function and
 *      included into this class
 *    - Added "revision log/comment" functionality
 *
 * 2. "xml2array" class
 *    Taken from http://www.php.net/manual/en/function.xml-parse.php#52567
 *    Modified by Martin Guppy <http://www.deadpan110.com/>
 *    CHANGES by Daniel Marschall, ViaThinkSoft in 2019:
 *    - Converted class into a single function and added that function into the phpsvnclient class
 */

define("PHPSVN_NORMAL_REQUEST", '<?xml version="1.0" encoding="utf-8"?><propfind xmlns="DAV:"><prop>
<getlastmodified xmlns="DAV:"/> <checked-in xmlns="DAV:"/><version-name xmlns="DAV:"/><version-controlled-configuration xmlns="DAV:"/><resourcetype xmlns="DAV:"/><baseline-relative-path xmlns="http://subversion.tigris.org/xmlns/dav/"/><repository-uuid xmlns="http://subversion.tigris.org/xmlns/dav/"/></prop></propfind>');
define("PHPSVN_VERSION_REQUEST", '<?xml version="1.0" encoding="utf-8"?><propfind xmlns="DAV:"><prop><checked-in xmlns="DAV:"/></prop></propfind>');
define("PHPSVN_LOGS_REQUEST", '<?xml version="1.0" encoding="utf-8"?> <S:log-report xmlns:S="svn:"> <S:start-revision>%d</S:start-revision><S:end-revision>%d</S:end-revision><S:path></S:path><S:discover-changed-paths/></S:log-report>');

define("NOT_FOUND", 2);
define("AUTH_REQUIRED", 3);
define("UNKNOWN_ERROR", 4);
define("NO_ERROR", 1);

/**
 *  PHP SVN CLIENT
 *
 *  This class is a SVN client. It can perform read operations
 *  to a SVN server (over Web-DAV).
 *  It can get directory files, file contents, logs. All the operaration
 *  could be done for a specific version or for the last version.
 *
 *  @author Cesar D. Rodas <cesar@sixdegrees.com.br>
 *  @license BSD License
 */
class phpsvnclient
{
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
	 *  @var interger
	 */
	private $_repVersion;

	/**
	 *  Last error number
	 *
	 *  Possible values are NOT_ERROR, NOT_FOUND, AUTH_REQUIRED, UNKOWN_ERROR
	 *
	 *  @access public
	 *  @var integer
	 */
	public $errNro;

	/**
	 * Number of actual revision local repository.
	 * @var Integer, Long
	 */
	private $actVersion;
	private $storeDirectoryFiles = array();
	private $lastDirectoryFiles;
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
	 * @param type $path The path to the directory that will be created.
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
	 * @param type $path The path to the directory to be deleted.
	 * @return type Returns the status of a function or function rmdir unlink.
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
	* @param $from_revision Either a revision number or a text file with the
	*                       contents "Revision ..." (if it is a file,
	*                       the file revision will be updated if everything
	*                       was successful)
	* @param $folder        SVN remote folder
	* @param $outpath       Local path of the working copy
	* @param $preview       Only simulate, do not write to files
	**/
	public function updateWorkingCopy($from_revision='version.txt', $folder = '/trunk/', $outPath = '.', $preview = false)
	{
		if (!is_dir($outPath)) {
			echo "ERROR: Local path $outPath not existing\n";
			flush();
			return false;
		}

		if (!is_numeric($from_revision)) {
			$version_file = $from_revision;
                        $from_revision = -1;

			if (!file_exists($version_file)) {
				echo "ERROR: $version_file missing\n";
				flush();
				return false;
			} else {
				//Obtain the number of current version number of the local copy.
				$cont = file_get_contents($version_file);
				if (!preg_match('@Revision (\d+)@', $cont, $m)) {
					echo "ERROR: $version_file unknown format\n";
					flush();
					return false;
				}
				$from_revision = $m[1];

				echo "Found $version_file with revision information $from_revision\n";
				flush();
			}
		} else {
			$version_file = '';
		}

		$errors_happened = false;

		// First, do some read/write test (even if we are in preview mode, because we want to detect errors before it is too late)
		$file = $outPath . '/dummy_'.uniqid().'.tmp';
		$file = str_replace("///", "/", $file);
		if (@file_put_contents($file, 'Write Test') === false) {
			echo "Cannot write test file $file\n";
			flush();
			return false;
		}
		@unlink($file);
		if (file_exists($file)) {
			echo "Cannot delete test file $file\n";
			flush();
			return false;
		}

		//Get a list of objects to be updated.
		$objects_list = $this->getLogsForUpdate($folder, $from_revision + 1);
		if (!is_null($objects_list)) {
			// Output version information
			foreach ($objects_list['revisions'] as $revision) {
				$tex = "New revision ".$revision['versionName']." by ".$revision['creator']." (".date('Y-m-d H:i:s', strtotime($revision['date'])).") ";
				echo trim($tex . str_replace("\n", "\n".str_repeat(' ', strlen($tex)), $revision['comment']));
				echo "\n";
			}

			////Lets update dirs
			// Add dirs
			sort($objects_list['dirs']); // <-- added by Daniel Marschall: Sort folder list, so that directories will be created in the correct hierarchical order
			foreach ($objects_list['dirs'] as $file) {
				if ($file != '') {
					$localPath = str_replace($folder, "", $file);
                                        $localPath = rtrim($outPath,DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($localPath,DIRECTORY_SEPARATOR);

					echo "Added or modified directory: $file\n";
					flush();
					if (!$preview) {
						$this->createDirs($localPath);
						if (!is_dir($localPath)) {
							$errors_happened = true;
							echo "=> FAILED\n";
							flush();
						}
					}
				}
			}

			////Lets update files
			// Add files
			sort($objects_list['files']); // <-- added by Daniel Marschall: Sort list, just for cosmetic improvement
			foreach ($objects_list['files'] as $file) {
				if ($file != '') {
					$localFile = str_replace($folder, "", $file);
                                        $localFile = rtrim($outPath,DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($localFile,DIRECTORY_SEPARATOR);

					echo "Added or modified file: $file\n";
					flush();
					if (!$preview) {
						$contents = $this->getFile($file);
						if (@file_put_contents($localFile, $contents) === false) {
							$errors_happened = true;
							echo "=> FAILED\n";
							flush();
						}
					}
				}
			}
			//Remove files
			sort($objects_list['filesDelete']); // <-- added by Daniel Marschall: Sort list, just for cosmetic improvement
			foreach ($objects_list['filesDelete'] as $file) {
				if ($file != '') {
					$localFile = str_replace($folder, "", $file);
                                        $localFile = rtrim($outPath,DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($localFile,DIRECTORY_SEPARATOR);

					echo "Removed file: $file\n";
					flush();

					if (!$preview) {
						@unlink($localFile);
						if (file_exists($localFile)) {
							$errors_happened = true;
							echo "=> FAILED\n";
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

					echo "Removed directory: $file\n";
					flush();

					if (!$preview) {
						$this->removeDirs($localPath);
						if (is_dir($localPath)) {
							$errors_happened = true;
							echo "=> FAILED\n";
							flush();
						}
					}
				}
			}

			//Update version file
			// Changed by Daniel Marschall: Added $errors_happened
			if (!$preview && !empty($version_file)) {
				if (!$errors_happened) {
					if (@file_put_contents($version_file, "Revision " . $this->actVersion . "\n") === false) {
						echo "ERROR: Could not set the revision\n";
						flush();
						return false;
					} else {
						echo "Set revision to " . $this->actVersion . "\n";
						flush();
						return true;
					}
				} else {
					echo "Revision NOT set to " . $this->actVersion . " because some files/dirs could not be updated. Please try again.\n";
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
		$this->initQuery($args, "PROPFIND", $url);
		$args['Body']                      = PHPSVN_NORMAL_REQUEST;
		$args['Headers']['Content-Length'] = strlen(PHPSVN_NORMAL_REQUEST);

		if (!$this->Request($args, $headers, $body))
			throw new Exception("Cannot get rawDirectoryDump (Request failed)");

		return $this->xmlParse($body);
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
		if ($arrOutput = $this->rawDirectoryDump($folder, $version)) {
			$files = array();
			foreach ($arrOutput['children'] as $key => $value) {
				array_walk_recursive($value,
					function ($item, $key) {
						if ($key == 'name') {
							if (($item == 'D:HREF') || ($item == 'LP1:GETLASTMODIFIED') || ($item == 'LP1:VERSION-NAME') || ($item == 'LP2:BASELINE-RELATIVE-PATH') || ($item == 'LP3:BASELINE-RELATIVE-PATH') || ($item == 'D:STATUS')) {
								$this->lastDirectoryFiles = $item;
							}
						} elseif (($key == 'tagData') && ($this->lastDirectoryFiles != '')) {

							// Unsure if the 1st of two D:HREF's always returns the result we want, but for now...
							if (($this->lastDirectoryFiles == 'D:HREF') && (isset($this->storeDirectoryFiles['type'])))
								return;

							// Dump into the array
							switch ($this->lastDirectoryFiles) {
								case 'D:HREF':
									$var = 'type';
									break;
								case 'LP1:VERSION-NAME':
									$var = 'version';
									break;
								case 'LP1:GETLASTMODIFIED':
									$var = 'last-mod';
									break;
								case 'LP2:BASELINE-RELATIVE-PATH':
								case 'LP3:BASELINE-RELATIVE-PATH':
									$var = 'path';
									break;
								case 'D:STATUS':
									$var = 'status';
									break;
							}
							$this->storeDirectoryFiles[$var] = $item;
							$this->lastDirectoryFiles        = '';

							// Detect 'type' as either a 'directory' or 'file'
							if ((isset($this->storeDirectoryFiles['type'])) && (isset($this->storeDirectoryFiles['last-mod'])) && (isset($this->storeDirectoryFiles['path'])) && (isset($this->storeDirectoryFiles['status']))) {
								$this->storeDirectoryFiles['path'] = str_replace(' ', '%20', $this->storeDirectoryFiles['path']); //Hack to make filenames with spaces work.
								$len                               = strlen($this->storeDirectoryFiles['path']);
								if (substr($this->storeDirectoryFiles['type'], strlen($this->storeDirectoryFiles['type']) - $len) == $this->storeDirectoryFiles['path']) {
									$this->storeDirectoryFiles['type'] = 'file';
								} else {
									$this->storeDirectoryFiles['type'] = 'directory';
								}
							}
						} else {
							$this->lastDirectoryFiles = '';
						}
					}
				);
				array_push($files, $this->storeDirectoryFiles);
				unset($this->storeDirectoryFiles);
			}
			return $files;
		}
		return false;
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

		$url = $this->cleanURL($this->_url . "/!svn/bc/" . $version . "/" . $file . "/");
		$this->initQuery($args, "GET", $url);
		if (!$this->Request($args, $headers, $body))
			throw new Exception("Cannot call getFile (Request failed)");

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
			echo "Nothing updated\n";
			flush();
			return null;
		}

		$url = $this->cleanURL($this->_url . "/!svn/bc/" . $this->actVersion . "/" . $file . "/");
		$this->initQuery($args, "REPORT", $url);
		$args['Body']                      = sprintf(PHPSVN_LOGS_REQUEST, $vini, $vend);
		$args['Headers']['Content-Length'] = strlen($args['Body']);
		$args['Headers']['Depth']          = 1;

		if (!$this->Request($args, $headers, $body))
			throw new Exception("Cannot call getLogsForUpdate (Request failed)");

		$arrOutput = $this->xmlParse($body);

		$revlogs = array();

		$array = array();
		foreach ($arrOutput['children'] as $value) {
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
			foreach ($value['children'] as $entry) {
				if (($entry['name'] == 'S:ADDED-PATH') || ($entry['name'] == 'S:MODIFIED-PATH') || ($entry['name'] == 'S:DELETED-PATH')) {
					if ($entry['attrs']['NODE-KIND'] == "file") {
						$array['objects'][] = array(
							'object_name' => $entry['tagData'],
							'action' => $entry['name'],
							'type' => 'file'
						);
					} else if ($entry['attrs']['NODE-KIND'] == "dir") {
						$array['objects'][] = array(
							'object_name' => $entry['tagData'],
							'action' => $entry['name'],
							'type' => 'dir'
						);
					}
				} else if ($entry['name'] == 'D:VERSION-NAME') {
					$versionName = isset($entry['tagData']) ? $entry['tagData'] : '';
				} else if ($entry['name'] == 'S:DATE') {
					$date = isset($entry['tagData']) ? $entry['tagData'] : '';
				} else if ($entry['name'] == 'D:COMMENT') {
					$comment = isset($entry['tagData']) ? $entry['tagData'] : '';
				} else if ($entry['name'] == 'D:CREATOR-DISPLAYNAME') {
					$creator = isset($entry['tagData']) ? $entry['tagData'] : '';
				}
			}
                        $revlogs[] = array('versionName' => $versionName,
			                   'date' => $date,
					   'comment' => $comment,
					   'creator' => $creator);
		}
		$files       = "";
		$filesDelete = "";
		$dirs        = "";
		$dirsDelete  = "";

		foreach ($array['objects'] as $objects) {
			if ($objects['type'] == "file") {
				if ($objects['action'] == "S:ADDED-PATH" || $objects['action'] == "S:MODIFIED-PATH") {
					$file = $objects['object_name'] . "/*+++*/";
					$files .= $file;
					$filesDelete = str_replace($file, "", $filesDelete, $count);
				}
				if ($objects['action'] == "S:DELETED-PATH") {
					if (strpos($files, $objects['object_name']) !== false) {
						$file  = $objects['object_name'] . "/*+++*/";
						$count = 1;
						$files = str_replace($file, "", $files, $count);
					} else {
						$filesDelete .= $objects['object_name'] . "/*+++*/";
					}
				}
			}
			if ($objects['type'] == "dir") {
				if ($objects['action'] == "S:ADDED-PATH" || $objects['action'] == "S:MODIFIED-PATH") {
					$dir = $objects['object_name'] . "/*+++*/";
					$dirs .= $dir;
					$dirsDelete = str_replace($dir, "", $dirsDelete, $count);
				}
				if ($objects['action'] == "S:DELETED-PATH") {
					// Delete files from filelist
					$dir    = $objects['object_name'] . "/";
					$files1 = explode("/*+++*/", $files);
					for ($x = 0; $x < count($files1); $x++) {
						if (strpos($files1[$x], $dir) !== false) {
							unset($files1[$x]);
						}
					}
					$files = implode("/*+++*/", $files1);
					// END OF Delete files from filelist
					// Delete dirs from dirslist
					if (strpos($dirs, $objects['object_name']) !== false) {
						$dir   = $objects['object_name'] . "/*+++*/";
						$count = 1;
						$dirs  = str_replace($dir, "", $dirs, $count);
					} else {
						$dirsDelete .= $objects['object_name'] . "/*+++*/";
					}
					// END OF Delete dirs from dirslist
				}
			}
		}
		$files              = explode("/*+++*/", $files);
		$filesDelete        = explode("/*+++*/", $filesDelete);
		$dirs               = explode("/*+++*/", $dirs);
		$dirsDelete         = explode("/*+++*/", $dirsDelete);
		$out                = array();
		$out['files']       = $files;
		$out['filesDelete'] = $filesDelete;
		$out['dirs']        = $dirs;
		$out['dirsDelete']  = $dirsDelete;
		$out['revisions']   = $revlogs;
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
		$this->initQuery($args, "PROPFIND", $this->cleanURL($this->_url . "/!svn/vcc/default"));
		$args['Body']                      = PHPSVN_VERSION_REQUEST;
		$args['Headers']['Content-Length'] = strlen(PHPSVN_NORMAL_REQUEST);
		$args['Headers']['Depth']          = 0;

		if (!$this->Request($args, $tmp, $body))
			throw new Exception("Cannot get repository revision (Request failed)");

		$this->_repVersion = null;
		if (preg_match('@/(\d+)\s*</D:href>@ismU', $body, $m)) {
			$this->_repVersion = $m[1];
		} else {
			throw new Exception("Cannot get repository revision (RegEx failed)");
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
		if ($http->response_status[0] != 2) {
			switch ($http->response_status) {
				case 404:
					$this->errNro = NOT_FOUND;
					break;
				case 401:
					$this->errNro = AUTH_REQUIRED;
					break;
				default:
					$this->errNro = UNKNOWN_ERROR;
					break;
			}
			//            trigger_error("request to $args[RequestURI] failed: $http->response_status
			//Error: $http->error");
			$http->close();
			return false;
		}
		$this->errNro = NO_ERROR;
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

	/*
	  Taken from http://www.php.net/manual/en/function.xml-parse.php#52567
	  Modified by Martin Guppy <http://www.deadpan110.com/>
	  Usage
	  Grab some XML data, either from a file, URL, etc. however you want.
	  Assume storage in $strYourXML;
	  Converted "class" into a single function by Daniel Marschall, ViaThinkSoft
	 */
	private static function xmlParse($strInputXML) {
		$arrOutput = array();

		$resParser = xml_parser_create();
		xml_set_element_handler($resParser,
			function /*tagOpen*/($parser, $name, $attrs) use (&$arrOutput) {
				$tag = array("name" => $name, "attrs" => $attrs);
				array_push($arrOutput, $tag);
			},
			function /*tagClosed*/($parser, $name) use (&$arrOutput) {
				$arrOutput[count($arrOutput) - 2]['children'][] = $arrOutput[count($arrOutput) - 1];
				array_pop($arrOutput);
			}
		);
		xml_set_character_data_handler($resParser,
			function /*tagData*/($parser, $tagData) use (&$arrOutput) {
				if (trim($tagData)) {
					if (isset($arrOutput[count($arrOutput) - 1]['tagData'])) {
						$arrOutput[count($arrOutput) - 1]['tagData'] .= $tagData;
					} else {
						$arrOutput[count($arrOutput) - 1]['tagData'] = $tagData;
					}
				}
			}
		);

		if (!xml_parse($resParser, $strInputXML)) {
			die(sprintf("XML error: %s at line %d", xml_error_string(xml_get_error_code($resParser)), xml_get_current_line_number($resParser)));
		}

		xml_parser_free($resParser);

		return $arrOutput[0];
	}
}
