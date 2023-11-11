<?php

/*

      VNag - Nagios Framework for PHP                  (C) 2014-2023
      __     ___      _____ _     _       _     ____         __ _
      \ \   / (_) __ |_   _| |__ (_)_ __ | | __/ ___|  ___  / _| |_
       \ \ / /| |/ _` || | | '_ \| | '_ \| |/ /\___ \ / _ \| |_| __|
        \ V / | | (_| || | | | | | | | | |   <  ___) | (_) |  _| |_
         \_/  |_|\__,_||_| |_| |_|_|_| |_|_|\_\|____/ \___/|_|  \__|

      Developed by Daniel Marschall             www.viathinksoft.com
      Licensed under the terms of the Apache 2.0 license
      Revision 2023-11-05

*/

/****************************************************************************************************

More information on how to develop your plugin, see doc/Plugin_Development.md

****************************************************************************************************/

if (!VNag::is_http_mode()) error_reporting(E_ALL);

# If you want to use -t/--timeout with your module, you must add following line in your module code:
// WONTFIX: declare(ticks=1) is deprecated? http://www.hackingwithphp.com/4/21/0/the-declare-function-and-ticks
// WONTFIX: check is the main script used declare(ticks=1). (Not possible in PHP)
declare(ticks=1);

# Attention: The -t/--timeout parameter does not respect the built-in set_time_limit() of PHP.
# PHP should set this time limit to infinite.
set_time_limit(0);

define('VNAG_JSONDATA_V1', 'oid:1.3.6.1.4.1.37476.2.3.1.1'); // {iso(1) identified-organization(3) dod(6) internet(1) private(4) enterprise(1) 37476 products(2) vnag(3) jsondata(1) v1(1)}

// Set this to an array to overwrite getopt() and $_REQUEST[], respectively.
// Useful for mock tests.
$OVERWRITE_ARGUMENTS = null;

function _empty($x) {
	// Returns true for '' or null. Does not return true for value 0 or '0' (like empty() does)
	return is_null($x) || (trim($x) == '');
}

abstract class VNag {
	/*public*/ const VNAG_VERSION = '2023-11-05';

	// Status 0..3 for STATUSMODEL_SERVICE (the default status model):
	# The guideline states: "Higher-level errors (such as name resolution errors, socket timeouts, etc) are outside of the control of plugins and should generally NOT be reported as UNKNOWN states."
	# We choose 4 as exitcode. The plugin developer is free to return any other status.
	/*public*/ const STATUS_OK       = 0;
	/*public*/ const STATUS_WARNING  = 1;
	/*public*/ const STATUS_CRITICAL = 2;
	/*public*/ const STATUS_UNKNOWN  = 3;
	/*public*/ const STATUS_ERROR    = 4; // and upwards

	// Status 0..1 for STATUSMODEL_HOST:
	// The page https://blog.centreon.com/good-practices-how-to-develop-monitoring-plugin-nagios/
	// states that host plugins may return following status codes:
	// 0=UP, 1=DOWN, Other=Maintains last known state
	/*public*/ const STATUS_UP       = 0;
	/*public*/ const STATUS_DOWN     = 1;
	/*public*/ const STATUS_MAINTAIN = 2; // and upwards

	/*public*/ const VERBOSITY_SUMMARY                = 0;
	/*public*/ const VERBOSITY_ADDITIONAL_INFORMATION = 1;
	/*public*/ const VERBOSITY_CONFIGURATION_DEBUG    = 2;
	/*public*/ const VERBOSITY_PLUGIN_DEBUG           = 3;
	/*public*/ const MAX_VERBOSITY = self::VERBOSITY_PLUGIN_DEBUG;

	/*public*/ const STATUSMODEL_SERVICE = 0;
	/*public*/ const STATUSMODEL_HOST    = 1;

	private $initialized = false;

	private $status = null;
	private $messages = array(); // array of messages which are all put together into the headline, comma separated
	private $verbose_info = ''; // all other lines
	private $warningRanges = array();
	private $criticalRanges = array();
	private $performanceDataObjects = array();
	private static $exitcode = 0;

	private $helpObj = null;
	private $argHandler = null;

	/*public*/ const OUTPUT_NEVER     = 0;
	/*public*/ const OUTPUT_SPECIAL   = 1; // illegal usage / help page, version page
	/*public*/ const OUTPUT_NORMAL    = 2;
	/*public*/ const OUTPUT_EXCEPTION = 4;
	/*public*/ const OUTPUT_ALWAYS    = 7; // = OUTPUT_SPECIAL+OUTPUT_NORMAL+OUTPUT_EXCEPTION

	public $http_visual_output    = self::OUTPUT_ALWAYS; // show a human-readable panel? ...
	public $http_invisible_output = self::OUTPUT_ALWAYS; // ... and/or output an invisible machine-readable tag?

	// $html_before and $html_after contain the output HTML which were sent by the user

	// before and after the visual output
	protected $html_before = '';
	protected $html_after = '';

	protected $statusmodel = self::STATUSMODEL_SERVICE;

	protected $show_status_in_headline = true;

	protected $default_status = self::STATUS_UNKNOWN;
	protected $default_warning_range = null;
	protected $default_critical_range = null;

	protected $argWarning;
	protected $argCritical;
	protected $argVersion;
	protected $argVerbosity;
	protected $argTimeout;
	protected $argHelp;
	protected $argUsage;

	// -----------------------------------------------------------

	// The ID will be used for writing AND reading of the machine-readable
	// Nagios output embedded in a website. (A web-reader acts as proxy, so the
	// input and output ID will be equal)
	// Attention: Once you run run(), $id will be "used" and resetted to null.
	// The ID can be any string, e.g. a GUID, an OID, a package name or something else.
	// It should be unique. If you don't set an ID, a serial number (0, 1, 2, 3, ...) will be
	// used for your outputs.
	public $id = null;
	protected static $http_serial_number = 0;

	// -----------------------------------------------------------

	// Private key: Optional feature used in writeInvisibleHTML (called by run in HTTP mode) in order to sign/encrypt the output
	public $privkey = null;
	public $privkey_password = null;
	public $sign_algo = null; // default: OPENSSL_ALGO_SHA256

	// Public key: Optional feature used in a web-reader [readInvisibleHTML) to check the integrity of a message
	public $pubkey = null;

	// -----------------------------------------------------------

	// These settings should be set by derivated classes where the user intuitively expects the
	// warning (w) or critical (c) parameter to mean something else than defined in the development guidelines.
	// Usually, the single value "-w X" means the same like "-w X:X", which means everything except X is bad.
	// This behavior is VNag::SINGLEVALUE_RANGE_DEFAULT.
	// But for plugins e.g. for checking disk space, the user expects the argument "-w X" to mean
	// "everything below X is bad" (if X is defined as free disk space).
	// So we would choose the setting VNag::SINGLEVALUE_RANGE_VAL_LT_X_BAD.
	// Note: This setting is implemented as array, so that each range number (in case you want to have more
	//       than one range, like in the PING plugin that checks latency and package loss)
	//       can have its individual behavior for single values.
	protected $warningSingleValueRangeBehaviors  = array(self::SINGLEVALUE_RANGE_DEFAULT);
	protected $criticalSingleValueRangeBehaviors = array(self::SINGLEVALUE_RANGE_DEFAULT);

	// Default behavior according to the development guidelines:
	//  x means  x:x, which means, everything except x% is bad.
	// @x means @x:x, which means, x is bad and everything else is good.
	const SINGLEVALUE_RANGE_DEFAULT = 0;

	// The single value x means, everything > x is bad. @x is not defined.
	const SINGLEVALUE_RANGE_VAL_GT_X_BAD = 1;

	// The single value x means, everything >= x is bad. @x is not defined.
	const SINGLEVALUE_RANGE_VAL_GE_X_BAD = 2;

	// The single value x means, everything < x is bad. @x is not defined.
	const SINGLEVALUE_RANGE_VAL_LT_X_BAD = 3;

	// The single value x means, everything <= x is bad. @x is not defined.
	const SINGLEVALUE_RANGE_VAL_LE_X_BAD = 4;

	// -----------------------------------------------------------

	// Encryption password: Optional feature used in writeInvisibleHTML (called by run in HTTP mode)
	public $password_out = null;

	// Decryption password: Used in readInvisibleHTML to decrypt an encrypted machine-readable info
	public $password_in = null;

	// -----------------------------------------------------------

	public static function is_http_mode() {
		return php_sapi_name() !== 'cli';
	}

	public function getHelpManager() {
		return $this->helpObj;
	}

	public function getArgumentHandler() {
		return $this->argHandler;
	}

	public function outputHTML($text, $after_visual_output=true) {
		if ($this->is_http_mode()) {
			if ($this->initialized) {
				if ($after_visual_output) {
					$this->html_after .= $text;
				} else {
					$this->html_before .= $text;
				}
			} else {
				echo $text;
			}
		}
	}

	protected function resetArguments() {
		$this->argWarning   = null;
		$this->argCritical  = null;
		$this->argVersion   = null;
		$this->argVerbosity = null;
		$this->argTimeout   = null;
		$this->argHelp      = null;
		// $this->argUsage  = null;

		// Also remove cache
		$this->argWarning   = null;
		$this->argCritical  = null;
	}

	// e.g. $args = "wcVvht"
	public function registerExpectedStandardArguments($args) {
		$this->resetArguments();

		for ($i=0; $i<strlen($args); $i++) {
			switch ($args[$i]) {
				case 'w':
					$this->addExpectedArgument($this->argWarning   = new VNagArgument('w', 'warning',  VNagArgument::VALUE_REQUIRED,  VNagLang::$argname_value, VNagLang::$warning_range));
					break;
				case 'c':
					$this->addExpectedArgument($this->argCritical  = new VNagArgument('c', 'critical', VNagArgument::VALUE_REQUIRED,  VNagLang::$argname_value, VNagLang::$critical_range));
					break;
				case 'V':
					$this->addExpectedArgument($this->argVersion   = new VNagArgument('V', 'version',  VNagArgument::VALUE_FORBIDDEN, null, VNagLang::$prints_version));
					break;
				case 'v':
					// In HTTP: -vvv is &v[]=&v[]=&v[]=
					$this->addExpectedArgument($this->argVerbosity = new VNagArgument('v', 'verbose',  VNagArgument::VALUE_FORBIDDEN, null, VNagLang::$verbosity_helptext));
					break;
				case 't':
					// Attention: not every plugin supports it because of declare(ticks=1) needs to be written in the main script
					$this->addExpectedArgument($this->argTimeout   = new VNagArgument('t', 'timeout',  VNagArgument::VALUE_REQUIRED,  VNagLang::$argname_seconds, VNagLang::$timeout_helptext));
					break;
				// case '?':
				case 'h':
					$this->addExpectedArgument($this->argHelp      = new VNagArgument('h', 'help',     VNagArgument::VALUE_FORBIDDEN, null, VNagLang::$help_helptext));
					break;
				default:
					$letter = $args[$i];
					throw new VNagInvalidStandardArgument(sprintf(VNagLang::$no_standard_arguments_with_letter, $letter));
					#break;
			}
		}
	}

	public function addExpectedArgument($argObj) {
		// Emulate C++ "friend" access to hidden functions

		// $this->helpObj->_addOption($argObj);
		$helpObjAddEntryMethod = new ReflectionMethod($this->helpObj, '_addOption');
		$helpObjAddEntryMethod->setAccessible(true);
		$helpObjAddEntryMethod->invoke($this->helpObj, $argObj);

		// $this->argHandler->_addExpectedArgument($argObj);
		$argHandlerAddEntryMethod = new ReflectionMethod($this->argHandler, '_addExpectedArgument');
		$argHandlerAddEntryMethod->setAccessible(true);
		$argHandlerAddEntryMethod->invoke($this->argHandler, $argObj);
	}

	protected function createArgumentHandler() {
		$this->argHandler = new VNagArgumentHandler();
	}

	protected function createHelpObject() {
		$this->helpObj = new VNagHelp();
	}

	protected function checkInitialized() {
		if (!$this->initialized) throw new VNagFunctionCallOutsideSession();
	}

	protected function getVerbosityLevel() {
		$this->checkInitialized(); // if (!$this->initialized) return false;

		if (!isset($this->argVerbosity)) {
			//The verbose argument is always optional
			//throw new VNagRequiredArgumentNotRegistered('-v');
			return self::VERBOSITY_SUMMARY;
		} else {
			$level = $this->argVerbosity->count();
			if ($level > self::MAX_VERBOSITY) $level = self::MAX_VERBOSITY;
			return $level;
		}
	}

	public function getWarningRange($argumentNumber=0) {
		$this->checkInitialized(); // if (!$this->initialized) return false;

		if (!isset($this->warningRanges[$argumentNumber])) {
			if (!is_null($this->argWarning)) {
				$warning = $this->argWarning->getValue();
				if (!is_null($warning)) {
					$vals = explode(',',$warning);
					foreach ($vals as $number => $val) {
						if (_empty($val)) {
							$this->warningRanges[$number] = null;
						} else {
							$singleValueBehavior = isset($this->warningSingleValueRangeBehaviors[$number]) ? $this->warningSingleValueRangeBehaviors[$number] : VNag::SINGLEVALUE_RANGE_DEFAULT;
							$this->warningRanges[$number] = new VNagRange($val, $singleValueBehavior);
						}
					}
				} else {
					$this->warningRanges[0] = $this->default_warning_range;
				}
			} else {
				return null;
			}
		}

		if (isset($this->warningRanges[$argumentNumber])) {
			return $this->warningRanges[$argumentNumber];
		} else {
			return null;
		}
	}

	public function getCriticalRange($argumentNumber=0) {
		$this->checkInitialized(); // if (!$this->initialized) return false;

		if (!isset($this->criticalRanges[$argumentNumber])) {
			if (!is_null($this->argCritical)) {
				$critical = $this->argCritical->getValue();
				if (!is_null($critical)) {
					$vals = explode(',',$critical);
					foreach ($vals as $number => $val) {
						$singleValueBehavior = isset($this->criticalSingleValueRangeBehaviors[$number]) ? $this->criticalSingleValueRangeBehaviors[$number] : VNag::SINGLEVALUE_RANGE_DEFAULT;
						$this->criticalRanges[$number] = new VNagRange($val, $singleValueBehavior);
					}
				} else {
					$this->criticalRanges[0] = $this->default_critical_range;
				}
			} else {
				return null;
			}
		}

		if (isset($this->criticalRanges[$argumentNumber])) {
			return $this->criticalRanges[$argumentNumber];
		} else {
			return null;
		}
	}

	public function checkAgainstWarningRange($values, $force=true, $autostatus=true, $argumentNumber=0) {
		$this->checkInitialized(); // if (!$this->initialized) return;

		if (!$this->getArgumentHandler()->isArgRegistered('w')) {
			// Developer's mistake: The argument is not in the list of expected arguments
			throw new VNagRequiredArgumentNotRegistered('-w');
		}

		$wr = $this->getWarningRange($argumentNumber);
		if (isset($wr)) {
			if ($wr->checkAlert($values)) {
				if ($autostatus) $this->setStatus(VNag::STATUS_WARNING);
				return true;
			} else {
				if ($autostatus) $this->setStatus(VNag::STATUS_OK);
				return false;
			}
		} else {
			if ($force) {
				// User's mistake: They did not pass the argument to the plugin
				if (($argumentNumber > 0) && (count($this->warningRanges) > 0)) {
					throw new VNagInvalidArgumentException(sprintf(VNagLang::$too_few_warning_ranges, $argumentNumber+1));
				} else {
					throw new VNagRequiredArgumentMissing('-w');
				}
			}
		}
	}

	public function checkAgainstCriticalRange($values, $force=true, $autostatus=true, $argumentNumber=0) {
		$this->checkInitialized(); // if (!$this->initialized) return;

		if (!$this->getArgumentHandler()->isArgRegistered('c')) {
			// Developer's mistake: The argument is not in the list of expected arguments
			throw new VNagRequiredArgumentNotRegistered('-c');
		}

		$cr = $this->getCriticalRange($argumentNumber);
		if (isset($cr)) {
			if ($cr->checkAlert($values)) {
				if ($autostatus) $this->setStatus(VNag::STATUS_CRITICAL);
				return true;
			} else {
				if ($autostatus) $this->setStatus(VNag::STATUS_OK);
				return false;
			}
		} else {
			if ($force) {
				// User's mistake: They did not pass the argument to the plugin
				if (($argumentNumber > 0) && (count($this->warningRanges) > 0)) {
					throw new VNagInvalidArgumentException(sprintf(VNagLang::$too_few_critical_ranges, $argumentNumber+1));
				} else {
					throw new VNagRequiredArgumentMissing('-c');
				}
			}
		}
	}

	protected static function getBaddestExitcode($code1, $code2) {
		return max($code1, $code2);
	}

	# DO NOT CALL MANUALLY
	# Unfortunately, this function has to be public, otherwise register_shutdown_function() wouldn't work
	public static function _shutdownHandler() {
		if (!self::is_http_mode()) {
			exit((int)self::$exitcode);
		}
	}

	protected function _exit($code) {
		self::$exitcode = $this->getBaddestExitcode($code, self::$exitcode);
	}

	private $constructed = false;
	function __construct() {
		$this->createHelpObject();
		$this->createArgumentHandler();

		$this->addExpectedArgument($this->argUsage = new VNagArgument('?', '', VNagArgument::VALUE_FORBIDDEN, null, VNagLang::$prints_usage));

		$this->constructed = true;
	}

	function __destruct() {
		if (Timeouter::started()) {
			Timeouter::end();
		}
	}

	public function run() {
		global $inside_vnag_run;

		$inside_vnag_run = true;
		try {
			if (!$this->constructed) {
				throw new VNagNotConstructed(VNagLang::$notConstructed);
			}

			try {
				$this->initialized = true;
				$this->html_before = '';
				$this->html_after = '';
				$this->setStatus(null, true);
				$this->messages = array();

				register_shutdown_function(array($this, '_shutdownHandler'));

				if ($this->argHandler->illegalUsage()) {
					$content = $this->helpObj->printUsagePage();
					$this->setStatus(VNag::STATUS_UNKNOWN);

					if ($this->is_http_mode()) {
						echo $this->html_before;
						if ($this->http_visual_output    & VNag::OUTPUT_SPECIAL) echo $this->writeVisualHTML($content);
						if ($this->http_invisible_output & VNag::OUTPUT_SPECIAL) echo $this->writeInvisibleHTML($content);
						echo $this->html_after;
						return; // cancel
					} else {
						echo $content;
						return $this->_exit($this->status);
					}
				}

				if (!is_null($this->argVersion) && ($this->argVersion->available())) {
					$content = $this->helpObj->printVersionPage();
					$this->setStatus(VNag::STATUS_UNKNOWN);

					if ($this->is_http_mode()) {
						echo $this->html_before;
						if ($this->http_visual_output    & VNag::OUTPUT_SPECIAL) echo $this->writeVisualHTML($content);
						if ($this->http_invisible_output & VNag::OUTPUT_SPECIAL) echo $this->writeInvisibleHTML($content);
						echo $this->html_after;
						return; // cancel
					} else {
						echo $content;
						return $this->_exit($this->status);
					}
				}

				if (!is_null($this->argHelp) && ($this->argHelp->available())) {
					$content = $this->helpObj->printHelpPage();
					$this->setStatus(VNag::STATUS_UNKNOWN);

					if ($this->is_http_mode()) {
						echo $this->html_before;
						if ($this->http_visual_output    & VNag::OUTPUT_SPECIAL) echo $this->writeVisualHTML($content);
						if ($this->http_invisible_output & VNag::OUTPUT_SPECIAL) echo $this->writeInvisibleHTML($content);
						echo $this->html_after;
						return; // cancel
					} else {
						echo $content;
						return $this->_exit($this->status);
					}
				}

				// Initialize ranges (and check their validity)
				$this->getWarningRange();
				$this->getCriticalRange();

				if (!is_null($this->argTimeout)) {
					$timeout = $this->argTimeout->getValue();
					if (!is_null($timeout)) {
						Timeouter::start($timeout);
					}
				}

				ob_start();
				$init_ob_level = ob_get_level();
				try {
					$this->cbRun();

					// This will NOT be put in the 'finally' block, because otherwise it would trigger if an Exception happened (Which clears the OB)
					if (ob_get_level() < $init_ob_level) throw new VNagImplementationErrorException(VNagLang::$output_level_lowered);
				} finally {
					while (ob_get_level() > $init_ob_level) @ob_end_clean();
				}

				if (is_null($this->status)) $this->setStatus($this->default_status,true);

				$outputType = VNag::OUTPUT_NORMAL;
			} catch (Exception $e) {
				$this->handleException($e);
				$outputType = VNag::OUTPUT_EXCEPTION;
			}

			if ($this->is_http_mode()) {
				echo $this->html_before;
				if ($this->http_invisible_output & $outputType) {
					echo $this->writeInvisibleHTML();
				}
				if ($this->http_visual_output & $outputType) {
					echo $this->writeVisualHTML();
				}
				echo $this->html_after;
			} else {
				echo $this->getNagiosConsoleText();
				return $this->_exit($this->status);
			}

			Timeouter::end();
		} finally {
			$inside_vnag_run = false;
		}
	}

	private function getNagiosConsoleText() {
		// see https://nagios-plugins.org/doc/guidelines.html#AEN200
		// 1. space separated list of label/value pairs
		$ary_perfdata = $this->getPerformanceData();
		$performancedata_first = array_shift($ary_perfdata);
		$performancedata_rest  = implode(' ', $ary_perfdata);

		$status_text = VNagLang::status($this->status, $this->statusmodel);
		if (_empty($this->getHeadline())) {
			$content = $status_text;
		} else {
			if ($this->show_status_in_headline) {
				$content = $status_text.': '.$this->getHeadline();
			} else {
				$content = $this->getHeadline();
			}
		}

		if (!_empty($performancedata_first)) $content .= '|'.trim($performancedata_first);
		$content .= "\n";
		if (!_empty($this->verbose_info)) {
			//$content .= "\n".VNagLang::$verbose_info.":\n\n";
			$content .= trim($this->verbose_info);
		}
		if (!_empty($performancedata_rest)) $content .= '|'.trim($performancedata_rest);
		$content .= "\n";

		return trim($content)."\n";
	}

	abstract protected function cbRun();

	public function addPerformanceData($prefDataObj, $move_to_font=false, $verbosityLevel=VNag::VERBOSITY_SUMMARY) {
		$this->checkInitialized(); // if (!$this->initialized) return;

		if ((!isset($this->argVerbosity)) && ($verbosityLevel > VNag::VERBOSITY_SUMMARY)) throw new VNagRequiredArgumentNotRegistered('-v');
		if (self::getVerbosityLevel() < $verbosityLevel) return false;

		if ($move_to_font) {
			array_unshift($this->performanceDataObjects, $prefDataObj);
		} else {
			$this->performanceDataObjects[] = $prefDataObj;
		}

		return true;
	}

	public function getPerformanceData() {
		$this->checkInitialized(); // if (!$this->initialized) return null;

		// see https://nagios-plugins.org/doc/guidelines.html#AEN200
		// 1. space separated list of label/value pairs
		return $this->performanceDataObjects;
	}

	public function removePerformanceData($prefDataObj) {
		if (($key = array_search($prefDataObj, $this->performanceDataObjects, true)) !== false) {
			unset($this->performanceDataObjects[$key]);
			return true;
		} else {
			return false;
		}
	}

	public function clearPerformanceData() {
		$this->performanceDataObjects = array();
	}

	public function getVerboseInfo() {
		return $this->verbose_info;
	}

	public function clearVerboseInfo() {
		$this->verbose_info = '';
	}

	private function writeVisualHTML($special_content=null) {
		if (!_empty($special_content)) {
			$content = $special_content;
		} else {
			$content = strtoupper(VNagLang::$status.': '.VNagLang::status($this->status, $this->statusmodel))."\n\n";

			$content .= strtoupper(VNagLang::$message).":\n";
			$status_text = VNagLang::status($this->status, $this->statusmodel);
			if (_empty($this->getHeadline())) {
				$content .= $status_text;
			} else {
				if ($this->show_status_in_headline) {
					$content .= $status_text.': '.trim($this->getHeadline());
				} else {
					$content .= trim($this->getHeadline());
				}
			}
			$content .= "\n\n";

			if (!_empty($this->verbose_info)) {
				$content .= strtoupper(VNagLang::$verbose_info).":\n".trim($this->verbose_info)."\n\n";
			}

			$perfdata = $this->getPerformanceData();
			if (count($perfdata) > 0) {
				$content .= strtoupper(VNagLang::$performance_data).":\n";
				foreach ($perfdata as $pd) {
					$content .= trim($pd)."\n";
				}
				$content .= "\n";
			}
		}

		$colorinfo = '';
		$status = $this->getStatus();

		if ($status == VNag::STATUS_OK)                 $colorinfo = ' style="background-color:green;color:white;font-weight:bold"';
		else if ($status == VNag::STATUS_WARNING)       $colorinfo = ' style="background-color:yellow;color:black;font-weight:bold"';
		else if ($status == VNag::STATUS_CRITICAL)      $colorinfo = ' style="background-color:red;color:white;font-weight:bold"';
		else if ($status == VNag::STATUS_ERROR)         $colorinfo = ' style="background-color:purple;color:white;font-weight:bold"';
		else /* if ($status == VNag::STATUS_UNKNOWN) */ $colorinfo = ' style="background-color:lightgray;color:black;font-weight:bold"';

		$html_content = trim($content);
		$html_content = htmlentities($html_content);
		$html_content = str_replace(' ', '&nbsp;', $html_content);
		$html_content = nl2br($html_content);

		// FUT: Allow individual design via CSS
		return '<table border="1" cellspacing="2" cellpadding="2" style="width:100%" class="vnag_table">'.
			'<tr'.$colorinfo.' class="vnag_title_row">'.
			'<td>'.VNagLang::$nagios_output.'</td>'.
			'</tr>'.
			'<tr class="vnag_message_row">'.
			'<td><code>'.
			$html_content.
			'</code></td>'.
			'</tr>'.
			'</table>';
	}

	protected function readInvisibleHTML($html) {
		$this->checkInitialized(); // if (!$this->initialized) return;

		$doc = new DOMDocument(); // Requires: aptitude install php-dom
		@$doc->loadHTML($html);   // added '@' because we don't want a warning for the non-standard <vnag> tag

		$tags = $doc->getElementsByTagName('script');
		foreach ($tags as $tag) {
			$type = $tag->getAttribute('type');
			if ($type !== 'application/json') continue;

			$json = $tag->nodeValue;
			if (!$json) continue;

			$data = @json_decode($json,true);
			if (!is_array($data)) continue;

			if (!isset($data['type'])) continue;
			if ($data['type'] === VNAG_JSONDATA_V1) {
				if (!isset($data['datasets'])) throw new VNagWebInfoException(VNagLang::$dataset_missing);
				foreach ($data['datasets'] as $dataset) {
					$payload = base64_decode($dataset['payload']);
					if (!$payload) {
						throw new VNagWebInfoException(VNagLang::$payload_not_base64);
					}

					if (isset($dataset['encryption'])) {
						// The dataset is encrypted. We need to decrypt it first.

						$cryptInfo = $dataset['encryption'];
						if (!is_array($cryptInfo)) {
							throw new VNagWebInfoException(VNagLang::$dataset_encryption_no_array);
						}

						$password = is_null($this->password_in) ? '' : $this->password_in;

						$salt = base64_decode($cryptInfo['salt']);

						if ($cryptInfo['hash'] != hash('sha256',$salt.$password)) {
							if ($password == '') {
								throw new VNagWebInfoException(VNagLang::$require_password);
							} else {
								throw new VNagWebInfoException(VNagLang::$wrong_password);
							}
						}

						if (!function_exists('openssl_decrypt')) {
							throw new VNagException(VNagLang::$openssl_missing);
						}

						$payload = openssl_decrypt($payload, $cryptInfo['method'], $password, 0, $cryptInfo['iv']);
					}

					if (!is_null($this->pubkey) && ($this->pubkey !== '')) {
						if (substr($this->pubkey,0,3) === '---') {
							$public_key = $this->pubkey;
						} else {
							if (!file_exists($this->pubkey)) {
								throw new VNagInvalidArgumentException(sprintf(VNagLang::$pubkey_file_not_found, $this->pubkey));
							}

							$public_key = @file_get_contents($this->pubkey);
							if (!$public_key) {
								throw new VNagPublicKeyException(sprintf(VNagLang::$pubkey_file_not_readable, $this->pubkey));
							}
						}

						if (!isset($dataset['signature'])) {
							throw new VNagSignatureException(VNagLang::$signature_missing);
						}

						$signature = base64_decode($dataset['signature']);
						if (!$signature) {
							throw new VNagSignatureException(VNagLang::$signature_not_bas64);
						}

						if (!function_exists('openssl_verify')) {
							throw new VNagException(VNagLang::$openssl_missing);
						}

						$sign_algo = is_null($this->sign_algo) ? OPENSSL_ALGO_SHA256 : $this->sign_algo;
						if (!openssl_verify($payload, $signature, $public_key, $sign_algo)) {
							throw new VNagSignatureException(VNagLang::$signature_invalid);
						}
					}

					$payload = @json_decode($payload,true);
					if ($payload === null) {
						throw new VNagWebInfoException(VNagLang::$payload_not_json);
					}

					if ($payload['id'] == $this->id) {
						return $payload;
					}
				}
			}
		}

		return null;
	}

	private function getNextMonitorID($peek=false) {
		$result = is_null($this->id) ? self::$http_serial_number : $this->id;

		if (!$peek) {
			$this->id = null; // use manual ID only once
			self::$http_serial_number++;
		}

		return $result;
	}

	private function writeInvisibleHTML($special_content=null) {
		// 1. Create the payload

		$payload['id'] = $this->getNextMonitorID();

		$payload['status'] = $this->getStatus();

		if (!_empty($special_content)) {
			$payload['text'] = $special_content;
		} else {
			$payload['headline'] = $this->getHeadline();
			$payload['verbose_info'] = $this->verbose_info;

			$payload['performance_data'] = array();
			foreach ($this->performanceDataObjects as $perfdata) {
				$payload['performance_data'][] = (string)$perfdata;
			}
		}

		$payload = json_encode($payload);

		// 2. Encode the payload as JSON and optionally sign and/or encrypt it

		$dataset = array();

		if (!is_null($this->privkey) && ($this->privkey !== '')) {
			if (!function_exists('openssl_pkey_get_private') || !function_exists('openssl_sign')) {
				throw new VNagException(VNagLang::$openssl_missing);
			}

			if (substr($this->privkey,0,3) === '---') {
				$pkeyid = @openssl_pkey_get_private($this->privkey, $this->privkey_password);
				if (!$pkeyid) {
					throw new VNagPrivateKeyException(sprintf(VNagLang::$privkey_not_readable));
				}
			} else {
				if (!file_exists($this->privkey)) {
					throw new VNagInvalidArgumentException(sprintf(VNagLang::$privkey_file_not_found, $this->privkey));
				}
				$pkeyid = @openssl_pkey_get_private('file://'.$this->privkey, $this->privkey_password);
				if (!$pkeyid) {
					throw new VNagPrivateKeyException(sprintf(VNagLang::$privkey_file_not_readable, $this->privkey));
				}
			}

			$signature = '';
			$sign_algo = is_null($this->sign_algo) ? OPENSSL_ALGO_SHA256 : $this->sign_algo;
			if (@openssl_sign($payload, $signature, $pkeyid, $sign_algo)) {
				if (version_compare(PHP_VERSION, '8.0.0') < 0) {
					openssl_free_key($pkeyid);
				}

				$dataset['signature'] = base64_encode($signature);
			} else {
				throw new VNagPrivateKeyException(sprintf(VNagLang::$signature_failed));
			}
		}

		if (!is_null($this->password_out) && ($this->password_out !== '')) {
			if (!function_exists('openssl_encrypt')) {
				throw new VNagException(VNagLang::$openssl_missing);
			}

			$password = $this->password_out;

			$method = 'aes-256-ofb';
			$iv = substr(hash('sha256', openssl_random_pseudo_bytes(32)), 0, 16);
			$salt = openssl_random_pseudo_bytes(32);

			$cryptInfo = array();
			$cryptInfo['method'] = $method;
			$cryptInfo['iv'] = $iv;
			$cryptInfo['salt'] = base64_encode($salt);
			$cryptInfo['hash'] = hash('sha256', $salt.$password);

			$payload = openssl_encrypt($payload, $method, $password, 0, $iv);
			$dataset['encryption'] = $cryptInfo;
		}

		$dataset['payload'] = base64_encode($payload);

		// 3. Encode everything as JSON+Base64 (again) and put it into the data block

		$json = array();
		$json['type'] = VNAG_JSONDATA_V1;
		$json['datasets'] = array($dataset); // we only output 1 dataset. We could technically output more than one into this data block.

		// Include the machine-readable information as data block
		// This method was chosen to support HTML 4.01, XHTML and HTML5 as well without breaking the standards
		// see https://stackoverflow.com/questions/51222713/using-an-individual-tag-without-breaking-the-standards/51223609#51223609
		return '<script type="application/json">'.
		       json_encode($json).
		       '</script>';
	}

	protected function appendHeadline($msg) {
		$this->checkInitialized(); // if (!$this->initialized) return;

		if (_empty($msg)) return false;
		$this->messages[] = $msg;

		return true;
	}

	protected function changeHeadline($msg) {
		$this->checkInitialized(); // if (!$this->initialized) return;

		if (_empty($msg)) {
			$this->messages = array();
		} else {
			$this->messages = array($msg);
		}

		return true;
	}

	public function setHeadline($msg, $append=false, $verbosityLevel=VNag::VERBOSITY_SUMMARY) {
		$this->checkInitialized(); // if (!$this->initialized) return;

		if ((!isset($this->argVerbosity)) && ($verbosityLevel > VNag::VERBOSITY_SUMMARY)) throw new VNagRequiredArgumentNotRegistered('-v');
		if (self::getVerbosityLevel() < $verbosityLevel) $msg = '';

		if ($append) {
			return $this->appendHeadline($msg);
		} else {
			return $this->changeHeadline($msg);
		}
	}

	public function getHeadline() {
		$this->checkInitialized(); // if (!$this->initialized) return '';

		return implode(', ', $this->messages);
	}

	public function addVerboseMessage($msg, $verbosityLevel=VNag::VERBOSITY_SUMMARY) {
		$this->checkInitialized(); // if (!$this->initialized) return;

		if (self::getVerbosityLevel() >= $verbosityLevel) {
			$this->verbose_info .= $msg."\n";
		}
	}

	public function setStatus($status, $force=false) {
		$this->checkInitialized(); // if (!$this->initialized) return;

		if (($force) || is_null($this->status) || ($status > $this->status)) {
			$this->status = $status;
		}
	}

	public function getStatus() {
		$this->checkInitialized(); // if (!$this->initialized) return;

		return $this->status;
	}

	protected static function exceptionText($exception) {
		// $this->checkInitialized(); // if (!$this->initialized) return false;

		$class = get_class($exception);
		$msg = $exception->getMessage();

		if (!_empty($msg)) {
			return sprintf(VNagLang::$exception_x, $msg, $class);
		} else {
			return sprintf(VNagLang::$unhandled_exception_without_msg, $class);
		}
	}

	protected function handleException($exception) {
		$this->checkInitialized(); // if (!$this->initialized) return;

		if (!VNag::is_http_mode()) {
			// On console output, remove anything we have written so far!
			while (ob_get_level() > 0) @ob_end_clean();
		}
		$this->clearVerboseInfo();
		$this->clearPerformanceData();

		if ($exception instanceof VNagException) {
			$this->setStatus($exception->getStatus());
		} else {
			$this->setStatus(self::STATUS_ERROR);
		}

		$this->setHeadline($this->exceptionText($exception), false);

		if ($exception instanceof VNagImplementationErrorException) {
			$this->addVerboseMessage($exception->getTraceAsString(), VNag::VERBOSITY_SUMMARY);
		} else {
			if (isset($this->argVerbosity)) {
				$this->addVerboseMessage($exception->getTraceAsString(), VNag::VERBOSITY_ADDITIONAL_INFORMATION);
			} else {
				// $this->addVerboseMessage($exception->getTraceAsString(), VNag::VERBOSITY_SUMMARY);
			}
		}
	}

	// This is not used by the framework itself, but can be useful for a lot of plugins
	// Note: For icinga2, the path is /var/lib/nagios/.vnag/cache/
	protected function get_cache_dir() {
		$homedir = @getenv('HOME');
		if ($homedir && is_dir($homedir)) {
			$try = "$homedir/.vnag/cache";
			if (is_dir($try)) return $try;
			if (@mkdir($try,0777,true)) return $try;
		}

		$user = posix_getpwuid(posix_geteuid());
		if (isset($user['dir']) && is_dir($user['dir'])) {
			$homedir = $user['dir'];
			$try = "$homedir/.vnag/cache";
			if (is_dir($try)) return $try;
			if (@mkdir($try,0777,true)) return $try;
		}

		if (isset($user['name']) && is_dir($user['name'])) {
			$username = $user['name'];
			$try = "/tmp/vnag/cache";
			if (is_dir($try)) return $try;
			if (@mkdir($try,0777,true)) return $try;
		}

		throw new VNagException("Cannot get cache dir"); // TODO: translate and own exception type
	}

	// This is not used by the framework itself, but can be useful for a lot of plugins
	protected function url_get_contents($url, $max_cache_time=1*60*60, $context=null) {
		$cache_file = $this->get_cache_dir().'/'.hash('sha256',$url);
		if (file_exists($cache_file) && (time()-filemtime($cache_file) < $max_cache_time)) {
			$cont = @file_get_contents($cache_file);
			if ($cont === false) throw new Exception("Failed to get contents from $cache_file");
		} else {
			$options = array(
			  'http'=>array(
			    'method'=>"GET",
			    'header'=>"Accept-language: en\r\n" .
			              "User-Agent: Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.102011-10-16 20:23:10\r\n"
			  )
			);
			if (is_null($context)) $context = stream_context_create($options);
			$cont = @file_get_contents($url, false, $context);
			if ($cont === false) throw new Exception("Failed to get contents from $url");
			file_put_contents($cache_file, $cont);
		}
		return $cont;
	}
}


class VNagException extends Exception {
	public function getStatus() {
		return VNag::STATUS_ERROR;
	}
}

class VNagTimeoutException extends VNagException {}

class VNagWebInfoException extends VNagException {}
class VNagSignatureException extends VNagException {}
class VNagPublicKeyException extends VNagException {}
class VNagPrivateKeyException extends VNagException {}

// VNagInvalidArgumentException are exceptions which result from a wrong use
// of arguments by the USER (CLI arguments or HTTP parameters)
class VNagInvalidArgumentException extends VNagException {
	public function getStatus() {
		return VNag::STATUS_UNKNOWN;
	}
}

class VNagValueUomPairSyntaxException extends VNagInvalidArgumentException {
	public function __construct($str) {
		$e_msg = sprintf(VNagLang::$valueUomPairSyntaxError, $str);
		parent::__construct($e_msg);
	}
}

class VNagInvalidTimeoutException extends VNagInvalidArgumentException {}

class VNagInvalidRangeException extends VNagInvalidArgumentException {
	public function __construct($msg) {
		$e_msg = VNagLang::$range_is_invalid;
		if (!_empty($msg)) $e_msg .= ': '.trim($msg);
		parent::__construct($e_msg);
	}
}

class VNagInvalidShortOpt extends VNagImplementationErrorException {}
class VNagInvalidLongOpt extends VNagImplementationErrorException {}
class VNagInvalidValuePolicy extends VNagImplementationErrorException {}
class VNagIllegalStatusModel extends VNagImplementationErrorException {}
class VNagNotConstructed extends VNagImplementationErrorException {}

// To enforce that people use the API correctly, we report flaws in the implementation
// as Exception.
class VNagImplementationErrorException extends VNagException {}

class VNagInvalidStandardArgument extends VNagImplementationErrorException  {}
class VNagFunctionCallOutsideSession extends VNagImplementationErrorException {}
class VNagIllegalArgumentValuesException extends VNagImplementationErrorException {}

class VNagRequiredArgumentNotRegistered extends VNagImplementationErrorException {
	// Developer's mistake: The argument is not in the list of expected arguments
	public function __construct($required_argument) {
		$e_msg = sprintf(VNagLang::$query_without_expected_argument, $required_argument);
		parent::__construct($e_msg);
	}
}

class VNagRequiredArgumentMissing extends VNagInvalidArgumentException {
	// User's mistake: They did not pass the argument to the plugin
	public function __construct($required_argument) {
		$e_msg = sprintf(VNagLang::$required_argument_missing, $required_argument);
		parent::__construct($e_msg);
	}
}

class VNagUnknownUomException extends VNagInvalidArgumentException {
	public function __construct($uom) {
		$e_msg = sprintf(VNagLang::$perfdata_uom_not_recognized, $uom);
		parent::__construct($e_msg);
	}
}

class VNagNoCompatibleRangeUomFoundException extends VNagException {}

class VNagMixedUomsNotImplemented extends VNagInvalidArgumentException {
	public function __construct($uom1, $uom2) {
		if (_empty($uom1)) $uom1 = VNagLang::$none;
		if (_empty($uom2)) $uom2 = VNagLang::$none;
		$e_msg = sprintf(VNagLang::$perfdata_mixed_uom_not_implemented, $uom1, $uom2);
		parent::__construct($e_msg);
	}
}

class VNagUomConvertException extends VNagInvalidArgumentException {
	// It is unknown where the invalid UOM that was passed to the normalize() function came from,
	// so it is not clear what parent this Exception class should have...
	// If the value comes from the developer: VNagImplementationErrorException
	// If the value came from the user: VNagInvalidArgumentException

	public function __construct($uom1, $uom2) {
		if (_empty($uom1)) $uom1 = VNagLang::$none;
		if (_empty($uom2)) $uom2 = VNagLang::$none;
		$e_msg = sprintf(VNagLang::$convert_x_y_error, $uom1, $uom2);
		parent::__construct($e_msg);
	}
}

class VNagInvalidPerformanceDataException extends VNagInvalidArgumentException {
	public function __construct($msg) {
		$e_msg = VNagLang::$performance_data_invalid;
		if (!_empty($msg)) $e_msg .= ': '.trim($msg);
		parent::__construct($e_msg);
	}
}

class Timeouter {
	// based on http://stackoverflow.com/questions/7493676/detecting-a-timeout-for-a-block-of-code-in-php

	private static $start_time = false;
	private static $timeout;
	private static $fired      = false;
	private static $registered = false;

	private function __construct() {
	}

	public static function start($timeout) {
		if (!is_numeric($timeout) || ($timeout <= 0)) {
			throw new VNagInvalidTimeoutException(sprintf(VNagLang::$timeout_value_invalid, $timeout));
		}

		self::$start_time = microtime(true);
		self::$timeout    = (float) $timeout;
		self::$fired      = false;
		if (!self::$registered) {
			self::$registered = true;
			register_tick_function(array('Timeouter', 'tick'));
		}
	}

	public static function started() {
		return self::$registered;
	}

	public static function end() {
		if (self::$registered) {
			unregister_tick_function(array('Timeouter', 'tick'));
			self::$registered = false;
		}
	}

	public static function tick() {
		if ((!self::$fired) && ((microtime(true) - self::$start_time) > self::$timeout)) {
			self::$fired = true; // do not fire again
			throw new VNagTimeoutException(VNagLang::$timeout_exception);
		}
	}
}

class VNagArgument {
	const VALUE_FORBIDDEN = 0;
	const VALUE_REQUIRED  = 1;
	const VALUE_OPTIONAL  = 2;

	protected $shortopt;
	protected $longopts;
	protected $valuePolicy;
	protected $valueName;
	protected $helpText;
	protected $defaultValue = null;

	protected static $all_short = '';
	protected static $all_long = array();

	public function getShortOpt() {
		return $this->shortopt;
	}

	public function getLongOpts() {
		return $this->longopts;
	}

	public function getValuePolicy() {
		return $this->valuePolicy;
	}

	public function getValueName() {
		return $this->valueName;
	}

	public function getHelpText() {
		return $this->helpText;
	}

	static private function validateShortOpt($shortopt) {
		$m = array();
		return preg_match('@^[a-zA-Z0-9\\+\\-\\?]$@', $shortopt, $m);
	}

	static private function validateLongOpt($longopt) {
		// FUT: Check if this is accurate
		$m = array();
		return preg_match('@^[a-zA-Z0-9\\+\\-\\?]+$@', $longopt, $m);
	}

	// Note: Currently, we do not support following:
	// 1. How many times may a value be defined (it needs to be manually described in $helpText)
	// 2. Is this argument mandatory? (No exception will be thrown if the plugin will be started without this argument)
	public function __construct($shortopt, $longopts, $valuePolicy, $valueName, $helpText, $defaultValue=null) {
		// Check if $valueName is defined correctly in regards to the policy $valuePolicy
		switch ($valuePolicy) {
			case VNagArgument::VALUE_FORBIDDEN:
				if (!_empty($valueName)) {
					throw new VNagImplementationErrorException(sprintf(VNagLang::$value_name_forbidden));
				}
				break;
			case VNagArgument::VALUE_REQUIRED:
				if (_empty($valueName)) {
					throw new VNagImplementationErrorException(sprintf(VNagLang::$value_name_required));
				}
				break;
			case VNagArgument::VALUE_OPTIONAL:
				if (_empty($valueName)) {
					throw new VNagImplementationErrorException(sprintf(VNagLang::$value_name_required));
				}
				break;
			default:
				throw new VNagInvalidValuePolicy(sprintf(VNagLang::$illegal_valuepolicy, $valuePolicy));
		}

		// We'll check: Does the shortopt contain illegal characters?
		// http://stackoverflow.com/questions/28522387/which-chars-are-valid-shortopts-for-gnu-getopt
		// We do not filter +, - and ?, since we might need it for other methods, e.g. VNagArgumentHandler::_addExpectedArgument
		if (!_empty($shortopt)) {
			if (!self::validateShortOpt($shortopt)) {
				throw new VNagInvalidShortOpt(sprintf(VNagLang::$illegal_shortopt, $shortopt));
			}
		}

		if (is_array($longopts)) { // $longopts is an array
			foreach ($longopts as $longopt) {
				if (!self::validateLongOpt($longopt)) {
					throw new VNagInvalidLongOpt(sprintf(VNagLang::$illegal_longopt, $longopt));
				}
			}
		} else if (!_empty($longopts)) { // $longopts is a string
			if (!self::validateLongOpt($longopts)) {
				throw new VNagInvalidLongOpt(sprintf(VNagLang::$illegal_longopt, $longopts));
			}
			$longopts = array($longopts);
		} else {
			$longopts = array();
		}

		# valuePolicy must be between 0..2 and being int
		switch ($valuePolicy) {
			case VNagArgument::VALUE_FORBIDDEN:
				$policyApdx = '';
				break;
			case VNagArgument::VALUE_REQUIRED:
				$policyApdx = ':';
				break;
			case VNagArgument::VALUE_OPTIONAL:
				$policyApdx = '::';
				break;
			default:
				throw new VNagInvalidValuePolicy(sprintf(VNagLang::$illegal_valuepolicy, $valuePolicy));
		}

		if ((!is_null($shortopt)) && ($shortopt != '?')) self::$all_short .= $shortopt.$policyApdx;
		if (is_array($longopts)) {
			foreach ($longopts as $longopt) {
				self::$all_long[] = $longopt.$policyApdx;
			}
		}

		$this->shortopt     = $shortopt;
		$this->longopts     = $longopts;
		$this->valuePolicy  = $valuePolicy;
		$this->valueName    = $valueName;
		$this->helpText     = $helpText;
		$this->defaultValue = $defaultValue;
	}

	protected static function getOptions() {
		// Attention: In PHP 5.6.19-0+deb8u1 (cli), $_REQUEST is always set, so we need is_http_mode() instead of isset($_REQUEST)!
		global $OVERWRITE_ARGUMENTS;

		if (!is_null($OVERWRITE_ARGUMENTS)) {
			return $OVERWRITE_ARGUMENTS;
		} else if (VNag::is_http_mode()) {
			return $_REQUEST;
		} else {
			return getopt(self::$all_short, self::$all_long);
		}
	}

	public function count() {
		$options = self::getOptions();

		$count = 0;

		if (isset($options[$this->shortopt])) {
			if (is_array($options[$this->shortopt])) {
				// e.g. -vvv
				$count += count($options[$this->shortopt]);
			} else {
				// e.g. -v
				$count += 1;
			}
		}

		if (!is_null($this->longopts)) {
			foreach ($this->longopts as $longopt) {
				if (isset($options[$longopt])) {
					if (is_array($options[$longopt])) {
						// e.g. --verbose --verbose --verbose
						$count += count($options[$longopt]);
					} else {
						// e.g. --verbose
						$count += 1;
					}
				}
			}
		}

		return $count;
	}

	public function available() {
		$options = self::getOptions();

		if (isset($options[$this->shortopt])) return true;
		if (!is_null($this->longopts)) {
			foreach ($this->longopts as $longopt) {
				if (isset($options[$longopt])) return true;
			}
		}
		return false;
	}

	public function require() {
		if (!$this->available() && is_null($this->defaultValue)) {
			$opt = $this->shortopt;
			$opt = !_empty($opt) ? '-'.$opt : (isset($this->longopts[0]) ? '--'.$this->longopts[0] : '?');
			throw new VNagRequiredArgumentMissing($opt);
		}
	}

	public function getValue() {
		$options = self::getOptions();

		if (isset($options[$this->shortopt])) {
			$x = $options[$this->shortopt];
			if (is_array($x) && (count($x) <= 1)) $options[$this->shortopt] = $options[$this->shortopt][0];
			return $options[$this->shortopt];
		}

		if (!is_null($this->longopts)) {
			foreach ($this->longopts as $longopt) {
				if (isset($options[$longopt])) {
					$x = $options[$longopt];
					if (is_array($x) && (count($x) <= 1)) $options[$longopt] = $options[$longopt][0];
					return $options[$longopt];
				}
			}
		}

		return $this->defaultValue;
	}
}

class VNagArgumentHandler {
	protected $expectedArgs = array();

	// Will be called by VNag via ReflectionMethod (like C++ style friends), because it should not be called manually.
	// Use VNag's function instead (since it adds to the helpObj too)
	protected function _addExpectedArgument($argObj) {
		// -? is always illegal, so it will trigger illegalUsage(). So we don't add it to the list of
		// expected arguments, otherwise illegalUsage() would be true.
		if ($argObj->getShortOpt() == '?') return false;

		// GNU extensions with a special meaning
		if ($argObj->getShortOpt() == '-') return false; // cancel parsing
		if ($argObj->getShortOpt() == '+') return false; // enable POSIXLY_CORRECT

		$this->expectedArgs[] = $argObj;
		return true;
	}

	public function getArgumentObj($shortopt) {
		foreach ($this->expectedArgs as $argObj) {
			if ($argObj->getShortOpt() == $shortopt) return $argObj;
		}
		return null;
	}

	public function isArgRegistered($shortopt) {
		return !is_null($this->getArgumentObj($shortopt));
	}

	public function illegalUsage() {
		// In this function, we should check if $argv (resp. getopts) contains stuff which is not expected or illegal,
		// so the script can show a usage information and quit the program.

		// WONTFIX: PHP's horrible implementation of GNU's getopt does not allow following intended tasks:
		// - check for illegal values/arguments (e.g. the argument -? which is always illegal)
		// - check for missing values (e.g. -H instead of -H localhost )
		// - check for unexpected arguments (e.g. -x if only -a -b -c are defined in $expectedArgs as expected arguments)
		// - Of course, everything behind "--" may not be evaluated
		// see also http://stackoverflow.com/questions/25388130/catch-unexpected-options-with-getopt

		// So the only way is to do this stupid hard coded check for '-?'
		// PHP sucks...
		global $argv;
		return (isset($argv[1])) && (($argv[1] == '-?') || ($argv[1] == '/?'));
	}
}

class VNagRange {
	// see https://nagios-plugins.org/doc/guidelines.html#THRESHOLDFORMAT
	// We allow UOMs inside the range definition, e.g. "-w @10M:50M"

	public /*VNagValueUomPair|'-inf'*/ $start;
	public /*VNagValueUomPair|'inf'*/ $end;
	public /*boolean*/ $warnInsideRange;

	public function __construct($rangeDef, $singleValueBehavior=VNag::SINGLEVALUE_RANGE_DEFAULT) {
		$m = array();
		//if (!preg_match('|(@){0,1}(\d+)(:){0,1}(\d+){0,1}|', $rangeDef, $m)) {
		if (!preg_match('|^(@){0,1}([^:]+)(:){0,1}(.*)$|', $rangeDef, $m)) {
			throw new VNagInvalidRangeException(sprintf(VNagLang::$range_invalid_syntax, $rangeDef));
		}

		$this->warnInsideRange = $m[1] === '@';

		$this->start = null;
		$this->end   = null;

		if ($m[3] === ':') {
			if ($m[2] === '~') {
				$this->start = '-inf';
			} else {
				$this->start = new VNagValueUomPair($m[2]);
			}

			if (_empty($m[4])) {
				$this->end = 'inf';
			} else {
				$this->end = new VNagValueUomPair($m[4]);
			}
		} else {
			assert(_empty($m[4]));
			assert(!_empty($m[2]));

			$x = $m[2];

			if ($singleValueBehavior == VNag::SINGLEVALUE_RANGE_DEFAULT) {
				// Default behavior according to the development guidelines:
				//  x means  0:x, which means, x>10 is bad
				// @x means @0:x, which means, x<=10 is bad
				$this->start = new VNagValueUomPair('0'.((new VNagValueUomPair($x))->getUom()));
				$this->end   = new VNagValueUomPair($x);
			} else if ($singleValueBehavior == VNag::SINGLEVALUE_RANGE_VAL_GT_X_BAD) {
				// The single value x means, everything > x is bad. @x is not defined.
				if ($this->warnInsideRange) throw new VNagInvalidRangeException(VNagLang::$singlevalue_unexpected_at_symbol);
				$this->warnInsideRange = 0;
				$this->start = '-inf';
				$this->end   = new VNagValueUomPair($x);
			} else if ($singleValueBehavior == VNag::SINGLEVALUE_RANGE_VAL_GE_X_BAD) {
				// The single value x means, everything >= x is bad. @x is not defined.
				if ($this->warnInsideRange) throw new VNagInvalidRangeException(VNagLang::$singlevalue_unexpected_at_symbol);
				$this->warnInsideRange = 1;
				$this->start = new VNagValueUomPair($x);
				$this->end   = 'inf';
			} else if ($singleValueBehavior == VNag::SINGLEVALUE_RANGE_VAL_LT_X_BAD) {
				// The single value x means, everything < x is bad. @x is not defined.
				if ($this->warnInsideRange) throw new VNagInvalidRangeException(VNagLang::$singlevalue_unexpected_at_symbol);
				$this->warnInsideRange = 0;
				$this->start = new VNagValueUomPair($x);
				$this->end   = 'inf';
			} else if ($singleValueBehavior == VNag::SINGLEVALUE_RANGE_VAL_LE_X_BAD) {
				// The single value x means, everything <= x is bad. @x is not defined.
				if ($this->warnInsideRange) throw new VNagInvalidRangeException(VNagLang::$singlevalue_unexpected_at_symbol);
				$this->warnInsideRange = 1;
				$this->start = '-inf';
				$this->end   = new VNagValueUomPair($x);
			} else {
				throw new VNagException(VNagLang::$illegalSingleValueBehavior);
			}
		}

		// Check if range is valid
		if (is_null($this->start)) {
			throw new VNagInvalidRangeException(VNagLang::$invalid_start_value);
		}
		if (is_null($this->end)) {
			throw new VNagInvalidRangeException(VNagLang::$invalid_end_value);
		}
		if (($this->start instanceof VNagValueUomPair) && ($this->end instanceof VNagValueUomPair) &&
		    (VNagValueUomPair::compare($this->start,$this->end) > 0)) {
			throw new VNagInvalidRangeException(VNagLang::$start_is_greater_than_end);
		}
	}

	public function __toString() {
		// Attention:
		// - this function assumes that $start and $end are valid.
		// - not the shortest result will be chosen

		$ret = '';
		if ($this->warnInsideRange) {
			$ret = '@';
		}

		if ($this->start === '-inf') {
			$ret .= '~';
		} else {
			$ret .= $this->start;
		}

		$ret .= ':';

		if ($this->end !== 'inf') {
			$ret .= $this->end;
		}

		return $ret;
	}

	public function checkAlert($values) {
		$compatibleCount = 0;

		if (!is_array($values)) $values = array($values);
		foreach ($values as $value) {
			if (!($value instanceof VNagValueUomPair)) $value = new VNagValueUomPair($value);

			assert(($this->start === '-inf') || ($this->start instanceof VNagValueUomPair));
			assert(($this->end   === 'inf' ) || ($this->end   instanceof VNagValueUomPair));

			if (($this->start !== '-inf') && (!$this->start->compatibleWith($value))) continue;
			if (($this->end   !== 'inf')  && (!$this->end->compatibleWith($value)))   continue;
			$compatibleCount++;

			if ($this->warnInsideRange) {
				return (($this->start === '-inf') || (VNagValueUomPair::compare($value,$this->start) >= 0)) &&
				       (($this->end   === 'inf')  || (VNagValueUomPair::compare($value,$this->end)   <= 0));
			} else {
				return (($this->start !== '-inf') && (VNagValueUomPair::compare($value,$this->start) <  0)) ||
				       (($this->end   !== 'inf')  && (VNagValueUomPair::compare($value,$this->end)   >  0));
			}
		}

		if ((count($values) > 0) and ($compatibleCount == 0)) {
			throw new VNagNoCompatibleRangeUomFoundException(VNagLang::$no_compatible_range_uom_found);
		}

		return false;
	}
}

class VNagValueUomPair {
	protected $value;
	protected $uom;
	public $roundTo = -1;

	public function isRelative() {
		return $this->uom === '%';
	}

	public function getValue() {
		return $this->value;
	}

	public function getUom() {
		return $this->uom;
	}

	public function __toString() {
		if ($this->roundTo == -1) {
			return $this->value.$this->uom;
		} else {
			return round($this->value,$this->roundTo).$this->uom;
		}
	}

	public function __construct($str) {
		$m = array();
		if (!preg_match('/^([\d\.]+)(.*)$/ism', $str, $m)) {
			throw new VNagValueUomPairSyntaxException($str);
		}
		$this->value = $m[1];
		$this->uom = isset($m[2]) ? $m[2] : '';

		if (!self::isKnownUOM($this->uom)) {
			throw new VNagUnknownUomException($this->uom);
		}
	}

	public static function isKnownUOM(string $uom) {
		// see https://nagios-plugins.org/doc/guidelines.html#AEN200
		// 10. UOM (unit of measurement) is one of:

		// no unit specified - assume a number (int or float) of things (eg, users, processes, load averages)
		$no_unit = ($uom === '');
		// s - seconds (also us, ms)
		$seconds = ($uom === 's') || ($uom === 'ms') || ($uom === 'us');
		// % - percentage
		$percentage = ($uom === '%');
		// B - bytes (also KB, MB, TB)
		// NOTE: GB is not in the official development guidelines,probably due to an error, so I've added them anyway
		$bytes = ($uom === 'B') || ($uom === 'KB') || ($uom === 'MB') || ($uom === 'GB') || ($uom === 'TB');
		// c - a continous counter (such as bytes transmitted on an interface)
		$counter = ($uom === 'c');

		return ($no_unit || $seconds || $percentage || $bytes || $counter);
	}

	public function normalize($target=null) {
		$res = clone $this;

		// The value is normalized to seconds or megabytes
		if ($res->uom === 'ms') {
			$res->uom = 's';
			$res->value /= 1000;
		}
		if ($res->uom === 'us') {
			$res->uom = 's';
			$res->value /= 1000 * 1000;
		}
		if ($res->uom === 'B') {
			$res->uom = 'MB';
			$res->value /= 1024 * 1024;
		}
		if ($res->uom === 'KB') {
			$res->uom = 'MB';
			$res->value /= 1024;
		}
		if ($res->uom === 'GB') {
			$res->uom = 'MB';
			$res->value *= 1024;
		}
		if ($res->uom === 'TB') {
			$res->uom = 'MB';
			$res->value *= 1024 * 1024;
		}
		if ($res->uom === 'c') {
			$res->uom = '';
		}

		// Now, if the user wishes, convert to another unit
		if (!is_null($target)) {
			if ($res->uom == 'MB') {
				if ($target == 'B') {
					$res->uom = 'B';
					$res->value *= 1024 * 1024;
				} else if ($target == 'KB') {
					$res->uom = 'KB';
					$res->value *= 1024;
				} else if ($target == 'MB') {
					$res->uom = 'MB';
					$res->value *= 1;
				} else if ($target == 'GB') {
					$res->uom = 'GB';
					$res->value /= 1024;
				} else if ($target == 'TB') {
					$res->uom = 'TB';
					$res->value /= 1024 * 1024;
				} else {
					throw new VNagUomConvertException($res->uom, $target);
				}
			} else if ($res->uom == 's') {
				if ($target == 's') {
					$res->uom = 's';
					$res->value /= 1;
				} else if ($target == 'ms') {
					$res->uom = 'ms';
					$res->value /= 1000;
				} else if ($target == 'us') {
					$res->uom = 'us';
					$res->value /= 1000 * 1000;
				} else {
					throw new VNagUomConvertException($res->uom, $target);
				}
			} else {
				throw new VNagUomConvertException($res->uom, $target);
			}
		}

		return $res;
	}

	public function compatibleWith(VNagValueUomPair $other) {
		$a = $this->normalize();
		$b = $other->normalize();

		return ($a->uom == $b->uom);
	}

	public static function compare(VNagValueUomPair $left, VNagValueUomPair $right) {
		$a = $left->normalize();
		$b = $right->normalize();

		// FUT: Also accept mixed UOMs, e.g. MB and %
		//      To translate between an absolute and a relative value, the
		//      reference value (100%=?) needs to be passed through this comparison
		//      function somehow.
		if ($a->uom != $b->uom) throw new VNagMixedUomsNotImplemented($a->uom, $b->uom);

		if ($a->value  > $b->value) return  1;
		if ($a->value == $b->value) return  0;
		if ($a->value  < $b->value) return -1;
	}
}

class VNagPerformanceData {
	// see https://nagios-plugins.org/doc/guidelines.html#AEN200
	//     https://www.icinga.com/docs/icinga1/latest/en/perfdata.html#formatperfdata

	protected $label;
	protected /*VNagValueUomPair*/ $value;
	protected $warn = null;
	protected $crit = null;
	protected $min = null;
	protected $max = null;

	public static function createByString($perfdata) {
		$perfdata = trim($perfdata);

		$ary = explode('=',$perfdata);
		if (count($ary) != 2) {
			throw new VNagInvalidPerformanceDataException(sprintf(VNagLang::$perfdata_line_invalid, $perfdata));
		}
		$label = $ary[0];
		$bry = explode(';',$ary[1]);
		if (substr($label,0,1) === "'") $label = substr($label, 1, strlen($label)-2);
		$value = $bry[0];
		$warn  = isset($bry[1]) ? $bry[1] : null;
		$crit  = isset($bry[2]) ? $bry[2] : null;
		$min   = isset($bry[3]) ? $bry[3] : null;
		$max   = isset($bry[4]) ? $bry[4] : null;

		// Guideline "7. min and max are not required if UOM=%" makes no sense, because
		// actually, all fields (except label and value) are optional.

		return new self($label, $value, $warn, $crit, $min, $max);
	}

	public function __construct($label, $value/*may include UOM*/, $warn=null, $crit=null, $min=null, $max=null) {
		// Not checked / Nothing to check:
		// - 4. label length is arbitrary, but ideally the first 19 characters are unique (due to a limitation in RRD). Be aware of a limitation in the amount of data that NRPE returns to Nagios
		// - 6. warn, crit, min or max may be null (for example, if the threshold is not defined or min and max do not apply). Trailing unfilled semicolons can be dropped
		// - 9. warn and crit are in the range format (see the Section called Threshold and ranges). Must be the same UOM
		// - 7. min and max are not required if UOM=%

		// 2. label can contain any characters except the equals sign or single quote (')
		if (strpos($label, '=') !== false) throw new VNagInvalidPerformanceDataException(VNagLang::$perfdata_label_equal_sign_forbidden);

		// 5. to specify a quote character, use two single quotes
		$label = str_replace("'", "''", $label);

		// 8. value, min and max in class [-0-9.]. Must all be the same UOM.
		//    value may be a literal "U" instead, this would indicate that the actual value couldn't be determined
		/*
		if (($value != 'U') && (!preg_match('|^[-0-9\\.]+$|', $value, $m))) {
			throw new VNagInvalidPerformanceDataException(VNagLang::$perfdata_value_must_be_in_class);
		}
		*/
		$m = array();
		if ((!_empty($min)) && (!preg_match('|^[-0-9\\.]+$|', $min, $m))) {
			throw new VNagInvalidPerformanceDataException(VNagLang::$perfdata_min_must_be_in_class);
		}
		if ((!_empty($max)) && (!preg_match('|^[-0-9\\.]+$|', $max, $m))) {
			throw new VNagInvalidPerformanceDataException(VNagLang::$perfdata_max_must_be_in_class);
		}

		// 10. UOM (unit of measurement) is one of ....
		//     => This rule is checked in the VNagValueUomPair constructor.

		$this->label = $label;
		$this->value = ($value == 'U') ? 'U' : new VNagValueUomPair($value);
		$this->warn  = $warn;
		$this->crit  = $crit;
		$this->min   = $min;
		$this->max   = $max;
	}

	public function __toString() {
		$label = $this->label;
		$value = $this->value;
		$warn  = $this->warn;
		$crit  = $this->crit;
		$min   = $this->min;
		$max   = $this->max;

		// 5. to specify a quote character, use two single quotes
		$label = str_replace("''", "'", $label);

		// 'label'=value[UOM];[warn];[crit];[min];[max]
		// 3. the single quotes for the label are optional. Required if spaces are in the label
		return "'$label'=$value".
		       ';'.(is_null($warn) ? '' : $warn).
		       ';'.(is_null($crit) ? '' : $crit).
		       ';'.(is_null($min)  ? '' : $min).
		       ';'.(is_null($max)  ? '' : $max);
	}
}

class VNagHelp {
	public $word_wrap_width = 80; // -1 = disable
	public $argument_indent = 7;

	public function printUsagePage() {
		$usage = $this->getUsage();

		if (_empty($usage)) {
			$usage = VNagLang::$no_syntax_defined;
		}

		return trim($usage)."\n";
	}

	public function printVersionPage() {
		$out = trim($this->getNameAndVersion())."\n";

		if ($this->word_wrap_width > 0) $out = wordwrap($out, $this->word_wrap_width, "\n", false);

		return $out;
	}

	static private function _conditionalLine($line, $terminator='', $prefix='') {
		if (!_empty($line)) {
			return trim($line).$terminator;
		}
		return '';
	}

	public function printHelpPage() {
		$out  = '';
		$out .= self::_conditionalLine($this->getNameAndVersion(), "\n");
		$out .= self::_conditionalLine($this->getCopyright(), "\n");
		$out .= ($out != '') ? "\n" : '';
		$out .= self::_conditionalLine($this->getShortDescription(), "\n\n\n");
		$out .= self::_conditionalLine($this->getUsage(), "\n\n");

		$out .= VNagLang::$options."\n";
		foreach ($this->options as $argObj) {
			$out .= $this->printArgumentHelp($argObj);
		}

		$out .= self::_conditionalLine($this->getFootNotes(), "\n\n", "\n");

		if ($this->word_wrap_width > 0) $out = wordwrap($out, $this->word_wrap_width, "\n", false);

		return $out;
	}

	protected /* VNagArgument[] */ $options = array();

	// Will be called by VNag via ReflectionMethod (like C++ style friends), because it should not be called manually.
	// Use VNag's function instead (since it adds to the argHandler too)
	protected function _addOption($argObj) {
		$this->options[] = $argObj;
	}

	# FUT: Automatic creation of usage page. Which arguments are necessary?
	protected function printArgumentHelp($argObj) {
		$identifiers = array();

		$shortopt = $argObj->getShortopt();
		if (!_empty($shortopt)) $identifiers[] = '-'.$shortopt;

		$longopts = $argObj->getLongopts();
		if (!is_null($longopts)) {
			foreach ($longopts as $longopt) {
				if (!_empty($longopt)) $identifiers[] = '--'.$longopt;
			}
		}

		if (count($identifiers) == 0) return;

		$valueName = $argObj->getValueName();

		$arginfo = '';
		switch ($argObj->getValuePolicy()) {
			case VNagArgument::VALUE_FORBIDDEN:
				$arginfo = '';
				break;
			case VNagArgument::VALUE_REQUIRED:
				$arginfo = '='.$valueName;
				break;
			case VNagArgument::VALUE_OPTIONAL:
				$arginfo = '[='.$valueName.']';
				break;
		}

		$out = '';
		$out .= implode(', ', $identifiers).$arginfo."\n";

		// https://nagios-plugins.org/doc/guidelines.html#AEN302 recommends supporting a 80x23 screen resolution.
		// While we cannot guarantee the vertical height, we can limit the width at least...

		$content = trim($argObj->getHelpText());
		if ($this->word_wrap_width > 0) $content = wordwrap($content, $this->word_wrap_width-$this->argument_indent, "\n", false);
		$lines = explode("\n", $content);

		foreach ($lines as $line) {
			$out .= str_repeat(' ', $this->argument_indent).$line."\n";
		}
		$out .= "\n";

		return $out;
	}

	// $pluginName should contain the name of the plugin, without version.
	protected $pluginName;
	public function setPluginName($pluginName) {
		$this->pluginName = $this->replaceStuff($pluginName);
	}
	public function getPluginName() {
		if (_empty($this->pluginName)) {
			global $argv;
			return basename($argv[0]);
		} else {
			return $this->pluginName;
		}
	}

	// $version should contain the version, not the program name or copyright.
	protected $version;
	public function setVersion($version) {
		$this->version = $this->replaceStuff($version);
	}
	public function getVersion() {
		return $this->version;
	}
	public function getNameAndVersion() {
		$ret = $this->getPluginName();
		if (_empty($ret)) return null;

		$ver = $this->getVersion();
		if (!_empty($ver)) {
			$ret = sprintf(VNagLang::$x_version_x, $ret, $ver);
		}
		$ret = trim($ret);

		return $ret;
	}

	// $copyright should contain the copyright only, no program name or version.
	// $CURYEAR$ will be replaced by the current year
	protected $copyright;
	public function setCopyright($copyright) {
		$this->copyright = $this->replaceStuff($copyright);
	}

	private function getVNagCopyright() {
		if (VNag::is_http_mode()) {
			$vts_email = 'www.viathinksoft.com'; // don't publish email address at web services because of spam bots
		} else {
			$vts_email = base64_decode('aW5mb0B2aWF0aGlua3NvZnQuZGU='); // protect email address from spambots which might parse this code
		}
		return "VNag Framework ".VNag::VNAG_VERSION." (C) 2014-".date('Y')." ViaThinkSoft <$vts_email>";
	}

	public function getCopyright() {
		if (_empty($this->copyright)) {
			return sprintf(VNagLang::$plugin_uses, $this->getVNagCopyright());
		} else {
			return trim($this->copyright)."\n".sprintf(VNagLang::$uses, $this->getVNagCopyright());
		}
	}

	// $shortDescription should describe what this plugin does.
	protected $shortDescription;
	public function setShortDescription($shortDescription) {
		$this->shortDescription = $this->replaceStuff($shortDescription);
	}
	public function getShortDescription() {
		if (_empty($this->shortDescription)) {
			return null;
		} else {
			$content = $this->shortDescription;
			if ($this->word_wrap_width > 0) $content = wordwrap($content, $this->word_wrap_width, "\n", false);
			return $content;
		}
	}

	protected function replaceStuff($text) {
		global $argv;
		if (php_sapi_name() == 'cli') {
			$text = str_replace('$SCRIPTNAME$', $argv[0], $text);
		} else {
			$text = str_replace('$SCRIPTNAME$', basename($_SERVER['SCRIPT_NAME']), $text);
		}
		$text = str_replace('$CURYEAR$', date('Y'), $text);
		return $text;
	}

	// $syntax should contain the option syntax only, no explanations.
	// $SCRIPTNAME$ will be replaced by the actual script name
	// $CURYEAR$ will be replaced by the current year
	# FUT: Automatically generate syntax?
	protected $syntax;
	public function setSyntax($syntax) {
		$syntax = $this->replaceStuff($syntax);
		$this->syntax = $syntax;
	}
	public function getUsage() {
		if (_empty($this->syntax)) {
			return null;
		} else {
			return sprintf(VNagLang::$usage_x, $this->syntax);
		}
	}

	// $footNotes can be contact information or other notes which should appear in --help
	protected $footNotes;
	public function setFootNotes($footNotes) {
		$this->footNotes = $this->replaceStuff($footNotes);
	}
	public function getFootNotes() {
		return $this->footNotes;
	}
}

class VNagLang {
	public static function status($code, $statusmodel) {
		switch ($statusmodel) {
			case VNag::STATUSMODEL_SERVICE:
				switch ($code) {
					case VNag::STATUS_OK:
						return 'OK';
						#break;
					case VNag::STATUS_WARNING:
						return 'Warning';
						#break;
					case VNag::STATUS_CRITICAL:
						return 'Critical';
						#break;
					case VNag::STATUS_UNKNOWN:
						return 'Unknown';
						#break;
					default:
						return sprintf('Error (%d)', $code);
						#break;
				}
				#break;
			case VNag::STATUSMODEL_HOST:
				switch ($code) {
					case VNag::STATUS_UP:
						return 'Up';
						#break;
					case VNag::STATUS_DOWN:
						return 'Down';
						#break;
					default:
						return sprintf('Maintain last state (%d)', $code);
						#break;
				}
				#break;
			default:
				throw new VNagIllegalStatusModel(sprintf(self::$illegal_statusmodel, $statusmodel));
				#break;
		}
	}

	static $nagios_output = 'VNag-Output';
	static $verbose_info = 'Verbose information';
	static $status = 'Status';
	static $message = 'Message';
	static $performance_data = 'Performance data';
	static $status_ok = 'OK';
	static $status_warn = 'Warning';
	static $status_critical = 'Critical';
	static $status_unknown = 'Unknown';
	static $status_error = 'Error';
	static $unhandled_exception_without_msg = "Unhandled exception of type %s";
	static $plugin_uses = 'This plugin uses %s';
	static $uses = 'uses %s';
	static $x_version_x = '%s, version %s';

	// Argument names (help page)
	static $argname_value = 'value';
	static $argname_seconds = 'seconds';

	// Exceptions
	static $query_without_expected_argument = "The argument '%s' is queried, but was not added to the list of expected arguments. Please contact the plugin author.";
	static $required_argument_missing = "The argument '%s' is required.";
	static $performance_data_invalid = 'Performance data invalid.';
	static $no_standard_arguments_with_letter = "No standard argument with letter '%s' exists.";
	static $invalid_start_value = 'Invalid start value.';
	static $invalid_end_value = 'Invalid end value.';
	static $start_is_greater_than_end = 'Start is greater than end value.';
	static $value_name_forbidden = "Implementation error: You may not define a value name for the argument, because the value policy is VALUE_FORBIDDEN.";
	static $value_name_required = "Implementation error: Please define a name for the argument (so it can be shown in the help page).";
	static $illegal_shortopt = "Illegal shortopt '-%s'.";
	static $illegal_longopt = "Illegal longopt '--%s'.";
	static $illegal_valuepolicy = "valuePolicy has illegal value '%s'.";
	static $range_invalid_syntax = "Syntax error in range '%s'.";
	static $timeout_value_invalid = "Timeout value '%s' is invalid.";
	static $range_is_invalid = 'Range is invalid.';
	static $timeout_exception = 'Timeout!';
	static $perfdata_label_equal_sign_forbidden = 'Label may not contain an equal sign.';
	static $perfdata_value_must_be_in_class = 'Value must be in class [-0-9.] or be \'U\' if the actual value can\'t be determined.';
	static $perfdata_min_must_be_in_class = 'Min must be in class [-0-9.] or empty.';
	static $perfdata_max_must_be_in_class = 'Max must be in class [-0-9.] or empty.';
	static $perfdata_uom_not_recognized = 'UOM (unit of measurement) "%s" is not recognized.';
	static $perfdata_mixed_uom_not_implemented = 'Mixed UOMs (%s and %s) are currently not supported.';
	static $no_compatible_range_uom_found = 'Measured values are not compatible with the provided warning/critical parameter. Most likely, the UOM is incompatible.';
	static $exception_x = '%s (%s)';
	static $no_syntax_defined = 'The author of this plugin has not defined a syntax for this plugin.';
	static $usage_x = "Usage:\n%s";
	static $options = "Options:";
	static $illegal_statusmodel = "Invalid statusmodel %d.";
	static $none = '[none]';
	static $valueUomPairSyntaxError = 'Syntax error at "%s". Syntax must be Value[UOM].';
	static $too_few_warning_ranges = "You have too few warning ranges (currently trying to get element %d).";
	static $too_few_critical_ranges = "You have too few critical ranges (currently trying to get element %d).";
	static $dataset_missing = 'Dataset missing.';
	static $payload_not_base64 = 'The payload is not valid Base64.';
	static $payload_not_json = 'The payload is not valid JSON.';
	static $signature_missing = 'The signature is missing.';
	static $signature_not_bas64 = 'The signature is not valid Base64.';
	static $signature_invalid = 'The signature is invalid. The connection might have been tampered, or a different key is used.';
	static $pubkey_file_not_found = "Public key file %s was not found.";
	static $pubkey_file_not_readable = "Public key file %s is not readable.";
	static $privkey_file_not_found = "Private key file %s was not found.";
	static $privkey_not_readable = "Private key is not readable.";
	static $privkey_file_not_readable = "Private key file %s is not readable.";
	static $signature_failed = "Signature failed.";
	static $perfdata_line_invalid = "Performance data line %s is invalid.";
	static $singlevalue_unexpected_at_symbol = 'This plugin does not allow the @-symbol at ranges for single values.';
	static $illegalSingleValueBehavior = "Illegal value for 'singleValueBehavior'. Please contact the creator of the plugin.";
	static $dataset_encryption_no_array = 'Dataset encryption information invalid.';
	static $require_password = 'This resource is protected with a password. Please provide a password.';
	static $wrong_password = 'This resource is protected with a password. You have provided the wrong password, or it was changed.';
	static $convert_x_y_error = 'Cannot convert from UOM %s to UOM %s.';
	static $php_error = 'PHP has detected an error in the plugin. Please contact the plugin author.';
	static $output_level_lowered = "Output Buffer level lowered during cbRun(). Please contact the plugin author.";
	static $openssl_missing = "OpenSSL is missing. Therefore, encryption and signatures are not available.";

	// Help texts
	static $warning_range = 'Warning range';
	static $critical_range = 'Critical range';
	static $prints_version = 'Prints version';
	static $verbosity_helptext = 'Verbosity -v, -vv or -vvv';
	static $timeout_helptext = 'Sets timeout in seconds';
	static $help_helptext = 'Prints help page';
	static $prints_usage = 'Prints usage';

	static $notConstructed = 'Parent constructor not called with parent::__construct().';
}

function vnagErrorHandler($errorkind, $errortext, $file, $line) {
	// This function "converts" PHP runtime errors into Exceptions, which can then be handled by VNag::handleException()
	global $inside_vnag_run;

	if (!$inside_vnag_run && VNag::is_http_mode()) {
		// We want to avoid that the VNag-Exception will show up in a website that contains
		// an embedded VNag monitor, so if we are not inside a running VNag code,
		// we will call the normal PHP error handler.
		return false;
	}

	if (!(error_reporting() & $errorkind)) {
		// Code is not included in error_reporting. Don't do anything.
		return true;
	}

	// We want 100% clean scripts, so any error, warning or notice will shutdown the script
	// This also fixes the issue that PHP will end with result code 0, showing an error.

	// Error kinds see http://php.net/manual/en/errorfunc.constants.php
	if (defined('E_ERROR') && ($errorkind == E_ERROR)) $errorkind = 'Error';
	if (defined('E_WARNING') && ($errorkind == E_WARNING)) $errorkind = 'Warning';
	if (defined('E_PARSE') && ($errorkind == E_PARSE)) $errorkind = 'Parse';
	if (defined('E_NOTICE') && ($errorkind == E_NOTICE)) $errorkind = 'Notice';
	if (defined('E_CORE_ERROR') && ($errorkind == E_CORE_ERROR)) $errorkind = 'Core Error';
	if (defined('E_CORE_WARNING') && ($errorkind == E_CORE_WARNING)) $errorkind = 'Core Warning';
	if (defined('E_COMPILE_ERROR') && ($errorkind == E_COMPILE_ERROR)) $errorkind = 'Compile Error';
	if (defined('E_COMPILE_WARNING') && ($errorkind == E_COMPILE_WARNING)) $errorkind = 'Compile Warning';
	if (defined('E_USER_ERROR') && ($errorkind == E_USER_ERROR)) $errorkind = 'User Error';
	if (defined('E_USER_WARNING') && ($errorkind == E_USER_WARNING)) $errorkind = 'User Warning';
	if (defined('E_USER_NOTICE') && ($errorkind == E_USER_NOTICE)) $errorkind = 'User Notice';
	if (defined('E_STRICT') && ($errorkind == E_STRICT)) $errorkind = 'Strict';
	if (defined('E_RECOVERABLE_ERROR') && ($errorkind == E_RECOVERABLE_ERROR)) $errorkind = 'Recoverable Error';
	if (defined('E_DEPRECATED') && ($errorkind == E_DEPRECATED)) $errorkind = 'Deprecated';
	if (defined('E_USER_DEPRECATED') && ($errorkind == E_USER_DEPRECATED)) $errorkind = 'User Deprecated';
	throw new VNagException(VNagLang::$php_error . " $errortext at $file:$line (kind $errorkind)");

	// true = the PHP internal error handling will NOT be called.
	#return true;
}

$inside_vnag_run = false;
$old_error_handler = set_error_handler("vnagErrorHandler");
