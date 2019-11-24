<?php /* <ViaThinkSoftSignature>
Ry2GSL8Q16B8ytPv1LExFhPnZ/J0l8wraAlsDf+DyesnAobgPpfA5R7iP4r3Rigdu
lFqTnRVRqQCWnqWd6pcRJSgvShSDw7sfIMWP8pg7KKcFlfdx0iZx0Tb7FmHPF4xg6
OyVue0SgAtQ0qGNBAnE7QSrhScTeZs+bXHO33l+iaJuF1kjA5QELiMtNXjCBr9Sw3
mcW4oPwc/H1yucGul+N0Wao0OpnZua9ai8afTD23K2ughXU+z/CmDNHcUWhthK7Xn
T35siXU5uf3Bys2L3raQnaFIGtGTnenNdGKBh+SIHq6uch4YwPEow3waxV2P8HTTO
lIaeqwQ4oSrCOGMGwuDYgOuX7OueBhvfng5TA7EZ3Q09PSx5t6rEl6Op1P6aXRv8d
Q4ezHNwucHJPi/JaCzH0Qt4Sj5W4zUKAqdNBtSfRuyIpWi3fJx0bmWoqWy9ufyZ87
4N0U5XbZOuz6AlCWfFkeOvFqEFT5l7JPUT4PJf1w2/+4uZvRR6JRtlgDy7Csh8LhC
ARLKDQv4hrRD/p9bIUR3NmpA0EdKD9ybSm8IhkMXxrQkZLP2Ykoc636OQxy1L1qsx
D49lwQ6MiIe0QKJwNIqrLQWZP+zIGakopg/2abs7S46nrJOOYc8X5kcaEk0GWO2yR
Nuo0hWdLIaGwoLmfrLOnb1TQV5X/ivP+w/yEnFK08gZ5z2XKJ2Pp2FJfMdihfOWa+
V7qjFtrrgnEXILsbKK9Vnh6fDq4vL/8EAdkYRfKU4Fu5Rdv1UdeSKGZIorW+IG8nO
r3VVv6pwDA/heiudcAd5dBsALBQplqFh22RlwtyyHD6wgDhjEyGnMsIsG/V8GqS3d
Q+H3RfCOdbIl6UzTS9RVgnwiWgpXX4Bvffzo2Re9GKRu+OqT9gXulkYD3tVvVqjt6
FUFt6sO//PZT1SLfNDGwuxqx8+OMnKQ6PX6F+8tr6tmMwQIS1ZakHq0srVsfF1yUa
ubX1tshGusLVtgL/y7ZPCXizuMSdN3SZ15YuBJivPCaUSY9R3XUV8QvqKUIRnfC6w
g3ud8TExKfd+jL4ikOHcBBh7STq4c7AM3S3i6OqhW/B/mG9rZx8PR0JUlzc+HVD+i
fCUTQKLsDFwUnPsw0vepmR03GRF0xsetGKmQE6eLCrcMUSl8HujehhAm2587JCFBl
ZqfZPn98kCtxHwak0RORN69/0ciovEh8HaG79Na1m41M3XvH20eqKt2an+xK6HeeX
h0P9KJyDV45vkgtETbl1EG92Otttdo2NXSsLovHhxbrJP1vQ3dhIoOw3zztmZdaTo
U3HAJ0XCRwTj1H4KzsPVjvZ7628YX8d5eCcea0wNroa3pBWhEXgC6n7gUO9mXaiWm
g==
</ViaThinkSoftSignature> */ ?>
<?php

/*

      VNag - Nagios Framework for PHP                  (C) 2014-2019
      __     ___      _____ _     _       _     ____         __ _
      \ \   / (_) __ |_   _| |__ (_)_ __ | | __/ ___|  ___  / _| |_
       \ \ / /| |/ _` || | | '_ \| | '_ \| |/ /\___ \ / _ \| |_| __|
        \ V / | | (_| || | | | | | | | | |   <  ___) | (_) |  _| |_
         \_/  |_|\__,_||_| |_| |_|_|_| |_|_|\_\|____/ \___/|_|  \__|

      Developed by Daniel Marschall             www.viathinksoft.com
      Licensed under the terms of the Apache 2.0 license
      Revision 2019-11-12

*/

/****************************************************************************************************

Introduction:

	VNag is a small framework for Nagios Plugin Developers who use PHP CLI scripts.
	The main purpose of VNag is to make the development of plugins as easy as possible, so that
	the developer can concentrate on the actual work. VNag will try to automate as much
	as possible.

	Please note that your script should include the +x chmod flag:
		chmod +x myscript.php

	Please see the the demo/ folder for a few examples how to use this framework.

Arguments:

	Example:
		$this->addExpectedArgument($argSilent = new VNagArgument('s', 'silent', VNagArgument::VALUE_FORBIDDEN, null,       'Description for the --silent output', $defaultValue));
		$this->addExpectedArgument($argHost   = new VNagArgument('H', 'host',   VNagArgument::VALUE_REQUIRED,  'hostname', 'Description for the --host output',   $defaultValue));

	In the example above, the two argument objects $argSilent and $argHost were created.
	With these objects of the type VNagArgument, you can query the argument's value,
	how often the argument was passed and if it is set:

		$argSilent->count();      // 1 if "-s" is passed, 2 if "-s -s" is passed etc.
		$argSilent->available();  // true if "-s" is passed, false otherwise
		$argHost->getValue();     // "example.com" if "-h example.com" is passed

	It is recommended that you pass every argument to $this->addExpectedArgument() .
	Using this way, VNag can generate a --help page for you, which lists all your arguments.
	Future version of VNag may also require to have a complete list of all valid arguments,
	since the Nagios Development Guidelines recommend to output the usage information if an illegal
	argument is passed. Due to PHP's horrible bad implementation of GNU's getopt(), this check for
	unknown arguments is currently not possible, and the developer of VNag does not want to use
	dirty hacks/workarounds, which would not match to all argument notation variations/styles.
	See: https://bugs.php.net/bug.php?id=68806
	     https://bugs.php.net/bug.php?id=65673
	     https://bugs.php.net/bug.php?id=26818

Setting the status:

	You can set the status with:
		$this->setStatus(VNag::STATUS_OK);
	If you don't set a status, the script will return Unknown instead.
	setStatus($status) will keep the most severe status, e.g.
		$this->setStatus(VNag::STATUS_CRITICAL);
		$this->setStatus(VNag::STATUS_OK);
	will result in a status "Critical".
	If you want to completely overwrite the status, use $force=true:
		$this->setStatus(VNag::STATUS_CRITICAL);
		$this->setStatus(VNag::STATUS_OK, true);
	The status will now be "OK".

	Possible status codes are:
		(For service plugins:)
		VNag::STATUS_OK       = 0;
		VNag::STATUS_WARNING  = 1;
		VNag::STATUS_CRITICAL = 2;
		VNag::STATUS_UNKNOWN  = 3;

		(For host plugins:)
		VNag::STATUS_UP       = 0;
		VNag::STATUS_DOWN     = 1;

Output:

	After the callback function cbRun() of your job has finished,
	the framework will automatically output the results in the Nagios console output format,
	the visual HTML output and/or the invisible HTML output.

	In case of CLI invokation, the Shell exit code will be remembered and
	automatically returned by the shutdown handler once the script normally
	terminates. (In case you run different jobs, which is not recommended, the
	shutdown handler will output the baddest exit code).

	The Shell output format will be:
		<Service status text>: <Comma separates messages> | <whitespace separated primary performance data>
		"Verbose information:"
		<Multiline verbose output> | <Multiline secondary performance data>

	<Service status text> will be automatically created by VNag.

	Verbose information are printed below the first line. Most Nagios clients will only print the first line.
	If you have important output, use $this->setHeadline() instead.
	You can add verbose information with following method:
		$this->addVerboseMessage('foobar', $verbosity);

	Following verbosity levels are defined:
		VNag::VERBOSITY_SUMMARY                = 0; // always printed
		VNag::VERBOSITY_ADDITIONAL_INFORMATION = 1; // requires at least -v
		VNag::VERBOSITY_CONFIGURATION_DEBUG    = 2; // requiers at least -vv
		VNag::VERBOSITY_PLUGIN_DEBUG           = 3; // requiers at least -vvv

	All STDOUT outputs of your script (e.g. by echo) will be interpreted as "verbose" output
	and is automatically collected, so
		echo "foobar";
	has the same functionality as
		$this->addVerboseMessage('foobar', VNag::VERBOSITY_SUMMARY);

	You can set messages (which will be added into the first line, which is preferred for plugin outputs)
	using
		$this->setHeadline($msg, $append, $verbosity);
	Using the flag $append, you can choose if you want to append or replace the message.

	VNag will catch Exceptions of your script and will automatically end the plugin,
	returning a valid Nagios output.

Automatic handling of basic arguments:

	VNag will automatic handle of following CLI arguments:
		-?
		-V --version
		-h --help
		-v --verbose
		-t --timeout   (only works if you set declare(ticks=1) at the beginning of each of your scripts)
		-w --warning
		-c --critical

	You can performe range checking by using:
		$example_value = '10MB';
		$this->checkAgainstWarningRange($example_value);
	this is more or less the same as:
		$example_value = '10MB';
		$wr = $this->getWarningRange();
		if (isset($wr) && $wr->checkAlert($example_value)) {
			$this->setStatus(VNag::STATUS_WARNING);
		}

	In case that your script allows ranges which can be relative and absolute, you can provide multiple arguments;
	$wr->checkAlert() will be true, as soon as one of the arguments is in the warning range.
	The check will be done in this way:
		$example_values = array('10MB', '5%');
		$this->checkAgainstWarningRange($example_values);
	this is more or less the same as:
		$example_values = array('10MB', '5%');
		$wr = $this->getWarningRange();
		if (isset($wr) && $wr->checkAlert($example_values)) {
			$this->setStatus(VNag::STATUS_WARNING);
		}

	Note that VNag will automatically detect the UOM (Unit of Measurement) and is also able to convert them,
	e.g. if you use the range "-w 20MB:40MB", your script will be able to use $wr->checkAlert('3000KB')

	Please note that only following UOMs are accepted (as defined in the Plugin Development Guidelines):
	- no unit specified: assume a number (int or float) of things (eg, users, processes, load averages)
	- s, ms, us: seconds
	- %: percentage
	- B, KB, MB, TB: bytes	// NOTE: GB is not in the official development guidelines,probably due to an error, so I've added them anyway
	- c: a continous counter (such as bytes transmitted on an interface)

Multiple warning/critical ranges:

	The arguments -w and -c can have many different values, separated by comma.
	We can see this feature e.g. with the official plugin /usr/lib/nagios/plugins/check_ping:
	It has following syntax for the arguments -w and -c: <latency>,<packetloss>%

	When you are using checkAgainstWarningRange, you can set the fourth argument to the range number
	you would like to check (beginning with 0).

	Example:
		// -w 1MB:5MB,5%:10%
		$this->checkAgainstWarningRange('4MB', true, true, 0); // check value 4MB against range "1MB:5MB" (no warning)
		$this->checkAgainstWarningRange('15%', true, true, 1); // check value 15% gainst range "5%:10%" (gives warning)

Visual HTTP output:

	Can be enabled/disabled with $this->http_visual_output

	Valid values:

	VNag::OUTPUT_SPECIAL   = 1; // illegal usage / help page, version page
	VNag::OUTPUT_NORMAL    = 2;
	VNag::OUTPUT_EXCEPTION = 4;
	VNag::OUTPUT_ALWAYS    = 7;
	VNag::OUTPUT_NEVER     = 0;

Encryption and Decryption:

	In case you are emitting machine readable code in your HTTP output
	(can be enabled/disabled by $this->http_invisible_output),
	you can encrypt the machine readable part of your HTTP output by
	setting $this->password_out . If you want to read the information,
	you need to set $this->password_in at the web-reader plugin.

	You can sign the output by setting $this->privkey with a filename containing
	a private key created by OpenSSL. If it is encrypted, please also set
	$this->privkey_password .
	To check the signature, set $this->pubkey at your web-reader plugin with
	the filename of the public key file.

	Attention!
	- An empty string is also considered as password. If you don't want to encrypt the
	  machine readable output, please set $this->password_out to null.
	- Both features (encryption and signatures) require the OpenSSL plugin in PHP.

Performance data:

	You can add performance data using
		$this->addPerformanceData(new VNagPerformanceData($label, $value, $warn, $crit, $min, $max));
	or by the alternative constructor
		$this->addPerformanceData(VNagPerformanceData::createByString("'XYZ'=100;120;130;0;500"));
	$value may contain an UOM, e.g. "10MB". All other parameters may not contain an UOM.

Guidelines:

	This framework currently supports meets following guidelines:
	- https://nagios-plugins.org/doc/guidelines.html#PLUGOUTPUT (Plugin Output for Nagios)
	- https://nagios-plugins.org/doc/guidelines.html#AEN33 (Print only one line of text)
	- https://nagios-plugins.org/doc/guidelines.html#AEN41 (Verbose output)
	- https://nagios-plugins.org/doc/guidelines.html#AEN78 (Plugin Return Codes)
	- https://nagios-plugins.org/doc/guidelines.html#THRESHOLDFORMAT (Threshold and ranges)
	- https://nagios-plugins.org/doc/guidelines.html#AEN200 (Performance data)
	- https://nagios-plugins.org/doc/guidelines.html#PLUGOPTIONS (Plugin Options)
	- https://nagios-plugins.org/doc/guidelines.html#AEN302 (Option Processing)
	  Note: The screen output of the help page will (mostly) be limited to 80 characters width; but the max recommended length of 23 lines cannot be guaranteed.

	This framework does currently NOT support following guidelines:
	- https://nagios-plugins.org/doc/guidelines.html#AEN74 (Screen Output)
	- https://nagios-plugins.org/doc/guidelines.html#AEN239 (Translations)
	- https://nagios-plugins.org/doc/guidelines.html#AEN293 (Use DEFAULT_SOCKET_TIMEOUT)
	- https://nagios-plugins.org/doc/guidelines.html#AEN296 (Add alarms to network plugins)
	- https://nagios-plugins.org/doc/guidelines.html#AEN245 (Don't execute system commands without specifying their full path)
	- https://nagios-plugins.org/doc/guidelines.html#AEN249 (Use spopen() if external commands must be executed)
	- https://nagios-plugins.org/doc/guidelines.html#AEN253 (Don't make temp files unless absolutely required)
	- https://nagios-plugins.org/doc/guidelines.html#AEN259 (Validate all input)
	- https://nagios-plugins.org/doc/guidelines.html#AEN317 (Plugins with more than one type of threshold, or with threshold ranges)

	We will intentionally NOT follow the following guidelines:
	- https://nagios-plugins.org/doc/guidelines.html#AEN256 (Don't be tricked into following symlinks)
	  Reason: We believe that this guideline is contraproductive.
	          Nagios plugins usually run as user 'nagios'. It is the task of the system administrator
	          to ensure that the user 'nagios' must not read/write to files which are not intended
	          for access by the Nagios service. Instead, symlinks are useful for several tasks.
	          See also http://stackoverflow.com/questions/27112949/nagios-plugins-why-not-following-symlinks

VNag over HTTP:

	A script that uses the VNag framework can run as CLI script (normal Nagios plugin) or as web site (or both).
	Having the script run as website, you can include a Nagios information combined with a human friendly HTML output which can
	include colors, graphics (like charts) etc.

	For example:
	A script that measures traffic can have a website which shows graphs,
	and has a hidden Nagios output included, which can be read by a Nagios plugin that
	converts the hidden information on that website into an output that Nagios can evaluate.

	Here is a comparison of the usage and behavior of VNag in regards to CLI and HTTP calls:

	------------------------------------------------------------------------------------------
	  CLI script                                 HTTP script
	------------------------------------------------------------------------------------------
	* "echo" will be discarded.                  * "echo" output will be discarded.

	* Exceptions will be handled.                * Exceptions will be handled.

	* outputHTML() will be ignored.              * outputHTML() will be handled.
	  (This allows you to have the same script
	  running as CLI and HTML)

	* Arguments are passed via CLI.              * Arguments are passed via $_REQUEST
	                                               (i.e. GET or POST)

	* Arguments: "-vvv"                          * Arguments: GET ?v[]=&v[]=&v[]= or POST

	* When run() has finished, the program       * When run() has finished, the program
	  flow continues, although it is not           flow continues.
	  recommended that you do anything after it.
	  (The exit code is remembered for the
	   shutdown handler)

	* Exactly 1 job must be called, resulting     * You can call as many jobs as you want.
	  in a single output of that job.               A website can include more than one
	                                                Nagios output which are enumerated with
							a serial number (0,1,2,3,...) or manual ID.
	------------------------------------------------------------------------------------------

****************************************************************************************************/

error_reporting(-1);

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
if (is_array($x)) debug_print_stacktrace();
	// Returns true for '' or null. Does not return true for value 0 or '0' (like empty() does)
	return trim($x) == '';
}

abstract class VNag {
	const VNAG_VERSION = '2019-11-12';

	// Status 0..3 for STATUSMODEL_SERVICE (the default status model):
	# The guideline states: "Higher-level errors (such as name resolution errors, socket timeouts, etc) are outside of the control of plugins and should generally NOT be reported as UNKNOWN states."
	# We choose 4 as exitcode. The plugin developer is free to return any other status.
	const STATUS_OK       = 0;
	const STATUS_WARNING  = 1;
	const STATUS_CRITICAL = 2;
	const STATUS_UNKNOWN  = 3;
	const STATUS_ERROR    = 4; // and upwards

	// Status 0..1 for STATUSMODEL_HOST:
	// The page https://blog.centreon.com/good-practices-how-to-develop-monitoring-plugin-nagios/
	// states that host plugins may return following status codes:
	// 0=UP, 1=DOWN, Other=Maintains last known state
	const STATUS_UP       = 0;
	const STATUS_DOWN     = 1;
	const STATUS_MAINTAIN = 2; // and upwards

	const VERBOSITY_SUMMARY                = 0;
	const VERBOSITY_ADDITIONAL_INFORMATION = 1;
	const VERBOSITY_CONFIGURATION_DEBUG    = 2;
	const VERBOSITY_PLUGIN_DEBUG           = 3;
	const MAX_VERBOSITY = self::VERBOSITY_PLUGIN_DEBUG;

	const STATUSMODEL_SERVICE = 0;
	const STATUSMODEL_HOST    = 1;

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

	const OUTPUT_SPECIAL   = 1; // illegal usage / help page, version page
	const OUTPUT_NORMAL    = 2;
	const OUTPUT_EXCEPTION = 4;
	const OUTPUT_ALWAYS    = 7;
	const OUTPUT_NEVER     = 0;

	// set to true if you want the output being shown in the HTML beside of the invisible tag
	// set to false if you just want to send the invisible tag
	public $http_visual_output    = self::OUTPUT_ALWAYS;
	public $http_invisible_output = self::OUTPUT_ALWAYS;

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

	// The ID will be used for writing AND reading of the machine readable
	// Nagios output embedded in a website. (A web-reader acts as proxy, so the
	// input and output ID will be equal)
	// Attention: Once you run run(), $id will be "used" and resetted to null.
	// The ID can be any string, e.g. a GUID, an OID, a package name or something else.
	// It should be unique. If you don't set an ID, a serial number (0, 1, 2, 3, ...) will be
	// used for your outputs.
	public $id = null;
	protected static $http_serial_number = 0;

	// -----------------------------------------------------------

	// Private key: Optional feature used in writeInvisibleHTML (called by run in HTTP mode)
	public $privkey = null;
	public $privkey_password = null;
	public $sign_algo = OPENSSL_ALGO_SHA256;

	// Public key: Used in readInvisibleHTML
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

	// Decryption password: Used in readInvisibleHTML to decrypt an encrypted machine readable info
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
		$this->warning      = null;
		$this->critical     = null;
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
					break;
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

	// $optional_args will be forwarded to the callback function cbRun()
	public function run($optional_args=array()) {
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
				$this->cbRun($optional_args);

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

						$payload = openssl_decrypt($payload, $cryptInfo['method'], $password, 0, $cryptInfo['iv']);
					}

					if (!is_null($this->pubkey)) {
						if (!file_exists($this->pubkey)) {
							throw new VNagInvalidArgumentException(sprintf(VNagLang::$pubkey_file_not_found, $this->pubkey));
						}

						$public_key = @file_get_contents($this->pubkey);
						if (!$public_key) {
							throw new VNagPublicKeyException(sprintf(VNagLang::$pubkey_file_not_readable, $this->pubkey));
						}

						if (!isset($dataset['signature'])) {
							throw new VNagSignatureException(VNagLang::$signature_missing);
						}

						$signature = base64_decode($dataset['signature']);
						if (!$signature) {
							throw new VNagSignatureException(VNagLang::$signature_not_bas64);
						}

						if (!openssl_verify($payload, $signature, $public_key, $this->sign_algo)) {
							throw new VNagSignatureException(VNagLang::$signature_invalid);
						}
					}

					$payload = @json_decode($payload,true);
					if (!$payload) {
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

		if (!is_null($this->privkey)) {
			if (!file_exists($this->privkey)) {
				throw new VNagInvalidArgumentException(sprintf(VNagLang::$privkey_file_not_found, $this->privkey));
			}
			$pkeyid = @openssl_pkey_get_private('file://'.$this->privkey, $privkey_password);
			if (!$pkeyid) {
				throw new VNagPrivateKeyException(sprintf(VNagLang::$privkey_file_not_readable, $this->privkey));
			}

			openssl_sign($payload, $signature, $pkeyid, $this->sign_algo);
			openssl_free_key($pkeyid);

			$dataset['signature'] = base64_encode($signature);
		}

		if (!is_null($this->password_out)) {
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

		// Include the machine readable information as data block
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

		while (ob_get_level() > 0) @ob_end_clean();
		$this->clearVerboseInfo();
		$this->clearPerformanceData();

		if ($exception instanceof VNagException) {
			$this->setStatus($exception->getStatus());
		} else {
			$this->setStatus(self::STATUS_ERROR);
		}

		$this->setHeadline($this->exceptionText($exception), false);

		if ($exception instanceof VNagImplementationException) {
			$this->addVerboseMessage($exception->getTraceAsString(), VNag::VERBOSITY_SUMMARY);
		} else {
			if (isset($this->argVerbosity)) {
				$this->addVerboseMessage($exception->getTraceAsString(), VNag::VERBOSITY_ADDITIONAL_INFORMATION);
			} else {
				// $this->addVerboseMessage($exception->getTraceAsString(), VNag::VERBOSITY_SUMMARY);
			}
		}
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
		return preg_match('@^[a-zA-Z0-9\\+\\-\\?]$@', $shortopt, $m);
	}

	static private function validateLongOpt($longopt) {
		// FUT: Check if this is accurate
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
			if (!is_null($longopts)) {
				foreach ($longopts as $longopt) {
					if (!self::validateLongOpt($longopt)) {
						throw new VNagInvalidLongOpt(sprintf(VNagLang::$illegal_longopt, $longopt));
					}
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
		if (!is_null($longopts)) {
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
		// so the script can show an usage information and quit the program.

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

	public /*VNagValueUomPair|'inf'*/ $start;
	public /*VNagValueUomPair|'inf'*/ $end;
	public /*boolean*/ $warnInsideRange;

	public function __construct($rangeDef, $singleValueBehavior=VNag::SINGLEVALUE_RANGE_DEFAULT) {
		//if (!preg_match('|(@){0,1}(\d+)(:){0,1}(\d+){0,1}|', $rangeDef, $m)) {
		if (!preg_match('|^(@){0,1}([^:]+)(:){0,1}(.*)$|', $rangeDef, $m)) {
			throw new VNagInvalidRangeException(sprintf(VNagLang::$range_invalid_syntax, $rangeDef));
		}

		$this->warnInsideRange = $m[1] === '@';

		$this->start = null;
		$this->end   = null;

		if ($m[3] === ':') {
			if ($m[2] === '~') {
				$this->start = 'inf';
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
				//  x means  x:x, which means, everything except x% is bad.
				// @x means @x:x, which means, x is bad and everything else is good.
				$this->start = new VNagValueUomPair($x);
				$this->end   = new VNagValueUomPair($x);
			} else if ($singleValueBehavior == VNag::SINGLEVALUE_RANGE_VAL_GT_X_BAD) {
				// The single value x means, everything > x is bad. @x is not defined.
				if ($this->warnInsideRange) throw new VNagInvalidRangeException(VNagLang::$singlevalue_unexpected_at_symbol);
				$this->warnInsideRange = 0;
				$this->start = 'inf';
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
				$this->start = 'inf';
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

		if ($this->start === 'inf') {
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

			assert(($this->start === 'inf') || ($this->start instanceof VNagValueUomPair));
			assert(($this->end   === 'inf') || ($this->end   instanceof VNagValueUomPair));

			if (($this->start !== 'inf') && (!$this->start->compatibleWith($value))) continue;
			if (($this->end   !== 'inf') && (!$this->end->compatibleWith($value)))   continue;
			$compatibleCount++;

			if ($this->warnInsideRange) {
				return (($this->start === 'inf') || (VNagValueUomPair::compare($value,$this->start) >= 0)) &&
				       (($this->end   === 'inf') || (VNagValueUomPair::compare($value,$this->end)   <= 0));
			} else {
				return (($this->start !== 'inf') && (VNagValueUomPair::compare($value,$this->start) <  0)) ||
				       (($this->end   !== 'inf') && (VNagValueUomPair::compare($value,$this->end)   >  0));
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
		if (!preg_match('/^([\d\.]+)(.*)$/ism', $str, $m)) {
			throw new VNagValueUomPairSyntaxException($str);
		}
		$this->value = $m[1];
		$this->uom = isset($m[2]) ? $m[2] : '';

		if (!self::isKnownUOM($this->uom)) {
			throw new VNagUnknownUomException($this->uom);
		}
	}

	public static function isKnownUOM($uom) {
		// see https://nagios-plugins.org/doc/guidelines.html#AEN200
		// 10. UOM (unit of measurement) is one of:
		return (
				// no unit specified - assume a number (int or float) of things (eg, users, processes, load averages)
				($uom === '') ||
				// s - seconds (also us, ms)
				($uom === 's') || ($uom === 'ms') || ($uom === 'us') ||
				// % - percentage
				($uom === '%') ||
				// B - bytes (also KB, MB, TB)
				($uom === 'B') || ($uom === 'KB') || ($uom === 'MB') || ($uom === 'GB') || ($uom === 'TB') || // NOTE: GB is not in the official development guidelines,probably due to an error, so I've added them anyway
				// c - a continous counter (such as bytes transmitted on an interface)
				($uom === 'c')
			);
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
				if ($target == 'ms') {
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

	private static function isCertified($file) {
		$cont = file_get_contents($file);

		$regex = '@<\?php /\* <ViaThinkSoftSignature>(.+)</ViaThinkSoftSignature> \*/ \?>\n@ismU';

		if (!preg_match($regex, $cont, $m)) {
			return false;
		}
		$signature = base64_decode($m[1]);

		$naked = preg_replace($regex, '', $cont);
		$hash = hash("sha256", $naked.basename($file));

		$public_key = <<<'VTSKEY'
-----BEGIN PUBLIC KEY-----
MIIEIjANBgkqhkiG9w0BAQEFAAOCBA8AMIIECgKCBAEA4UEmad2KHWzfGLcAzbOD
IhqWyoPA1Cg4zN5YK/CWUiE7sh2CNinIwYqGnIOhZLp54/Iyv3H05QeWJU7kD+jQ
5JwR8+pqk8ZGBfqlxXUBJ2bZhYIBJZYfilSROa7jgPPrrw0CjdGLmM3wmc8ztQRv
4GpP7MaKVyVOsRz5xEcpzghWZ+Cl8Nxq1Vo02RkMYOOPA16abxZ65lVM8Vv2EKGf
/VAVViTFvLWPxggvt1fbJJniC0cwt8gjzFXt6IJJSRlqc1lOO9ZIa/EWDKuHKQ1n
ENQCqnuVPFDZU3lU20Z+6+EA0YngcvNYi3ucdIvgBd4Yv5FetzuxiOZUoDRfh/3R
6dCJ8CvRiq0BSZcynTIWNmF3AVsH7vjxZe8kMDbwZNnR0suZ5MfBh6L/s1lCEWlS
GwmCLc3MnOLxq3JLnfmbVa509YxlUamdSswnvzes28AjnzQ3LQchspP2a8bSXH6/
qpbwvmV5WiNgwJck04VhaXrRRy3XFSwuk7KU/L4aqadXP26kgDqIYNvPXSa9JyGc
14zwdmAtn36o8vpXM/A7GhdWqgPLlJbdTdK6IfwpBs8P/JB6y3t6RzAGiEOITdj9
QUhW+sAoKno0j4WT7s80vWNWz37WoFJcvVLnVEYitnW6DqM+GOt2od3g6WgI6dOa
MESA4J44Y4x1gXBw/M6F/ZngP4EJoAUG0GbzsaZ6HKLt4pDTZmw8PnNcXrOMYkr/
N5EliTXil45DCaLkgNJmpdXjNpIvShW4ogq2osw+SQUalnAbW8ddiaOVCdgXkDFq
gvnl5QSeUrKPF5v+vlnwWar6Rp7iInQpnA+PTSbAlO3Dd9WqbWx+uNoI/kXUlN0O
a/vi5Uwat2Bz3N+jIpnBqg4+O+SG0z3UCVmT6Leg+kqO/rXbzoVv/DV7E30vTqdo
wsswdJEM1BI7Wyid6HPwBek+rdv77gUg3W37vUcdfKxsYRcoHriXLHpmENznJcEx
/nvilw6To1zx2LKmM/p56MQriKkXnqoOBpkpn3PaWyXZKY9xJNTAbcSP3haE7z9p
PzJw88KI8dnYuFg4yS/AgmVGAUtu3bhDG4qF9URu2ck868zViH996lraYkmFIWJG
r7h1LImhrwDEJvb/rOW8QvOZBX9H6pcSKs/LQbeoy6HMIOTlny+S15xtiS4t6Ayv
3m0ry5c0qkl/mgKvGpeRnNlrcr6mb2fzxxGvcuBzi25wgIbRLPgJoqsmeBvW1OLU
+9DpkNvitEJnPRo86v0VF86aou12Sm8Wb4mtrQ7h3qLIYvw2LN2mYh4WlgrSwPpx
YvE2+vWapilnnDWoiu2ZmDWa7WW/ihqvX9fmp/qzxQvJmBYIN8dFpgcNLqSx526N
bwIDAQAB
-----END PUBLIC KEY-----
VTSKEY;

		if (!openssl_verify($hash, $signature, $public_key, OPENSSL_ALGO_SHA256)) {
			return false;
		}

		return true;
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

		$certified = true;
		foreach (get_included_files() as $file) {
			$certified &= self::isCertified($file);
		}
		if ($certified) {
			$ret .= ' (' . VNagLang::$certified . ')';
		}

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
		$text = str_replace('$SCRIPTNAME$', $argv[0], $text);
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
						break;
					case VNag::STATUS_WARNING:
						return 'Warning';
						break;
					case VNag::STATUS_CRITICAL:
						return 'Critical';
						break;
					case VNag::STATUS_UNKNOWN:
						return 'Unknown';
						break;
					default:
						return sprintf('Error (%d)', $code);
						break;
				}
				break;
			case VNag::STATUSMODEL_HOST:
				switch ($code) {
					case VNag::STATUS_UP:
						return 'Up';
						break;
					case VNag::STATUS_DOWN:
						return 'Down';
						break;
					default:
						return sprintf('Maintain last state (%d)', $code);
						break;
				}
				break;
			default:
				throw new VNagIllegalStatusModel(sprintf(self::$illegal_statusmodel, $statusmodel));
				break;
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
	static $certified = 'Certified by ViaThinkSoft';

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
	static $privkey_file_not_readable = "Private key file %s is not readable.";
	static $perfdata_line_invalid = "Performance data line %s is invalid.";
	static $singlevalue_unexpected_at_symbol = 'This plugin does not allow the @-symbol at ranges for single values.';
	static $illegalSingleValueBehavior = "Illegal value for 'singleValueBehavior'. Please contact the creator of the plugin.";
	static $dataset_encryption_no_array = 'Dataset encryption information invalid.';
	static $require_password = 'This resource is protected with a password. Please provide a password.';
	static $wrong_password = 'This resource is protected with a password. You have provided the wrong password, or it was changed.';
	static $convert_x_y_error = 'Cannot convert from UOM %s to UOM %s.';
	static $php_error = 'PHP has detected an error in the plugin. Please contact the plugin author.';
	static $output_level_lowered = "Output Buffer level lowered during cbRun(). Please contact the plugin author.";

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
	if (!(error_reporting() & $errorkind)) {
		// Code is not included in error_reporting. Don't do anything.
		return;
	}

	while (ob_get_level() > 0) @ob_end_clean();

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

	// true = the PHP internal error handling will not be called.
	return true;
}

$old_error_handler = set_error_handler("vnagErrorHandler");

// === End of document ===
