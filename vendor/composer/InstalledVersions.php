<?php











namespace Composer;

use Composer\Autoload\ClassLoader;
use Composer\Semver\VersionParser;








class InstalledVersions
{
private static $installed = array (
  'root' =>
  array (
    'pretty_version' => '2.0',
    'version' => '2.0.0.0',
    'aliases' =>
    array (
    ),
    'reference' => NULL,
    'name' => 'danielmarschall/oidplus',
  ),
  'versions' =>
  array (
    'components/jquery' =>
    array (
      'pretty_version' => '3.6.0',
      'version' => '3.6.0.0',
      'aliases' =>
      array (
      ),
      'reference' => '6cf38ee1fd04b6adf8e7dda161283aa35be818c3',
    ),
    'components/jqueryui' =>
    array (
      'pretty_version' => '1.12.1',
      'version' => '1.12.1.0',
      'aliases' =>
      array (
      ),
      'reference' => '44ecf3794cc56b65954cc19737234a3119d036cc',
    ),
    'danielmarschall/fileformats' =>
    array (
      'pretty_version' => 'dev-master',
      'version' => 'dev-master',
      'aliases' =>
      array (
        0 => '9999999-dev',
      ),
      'reference' => '880e97b497710dc14ee8f38b4e48250ce49202ee',
    ),
    'danielmarschall/oidplus' =>
    array (
      'pretty_version' => '2.0',
      'version' => '2.0.0.0',
      'aliases' =>
      array (
      ),
      'reference' => NULL,
    ),
    'danielmarschall/php_utils' =>
    array (
      'pretty_version' => 'dev-master',
      'version' => 'dev-master',
      'aliases' =>
      array (
        0 => '9999999-dev',
      ),
      'reference' => '0f6f43b78e7ffef69e23710e9e006c33d3d732bc',
    ),
    'danielmarschall/uuid_mac_utils' =>
    array (
      'pretty_version' => 'dev-master',
      'version' => 'dev-master',
      'aliases' =>
      array (
        0 => '9999999-dev',
      ),
      'reference' => 'f99346b5382b1debcdb84c4541941151f700c1be',
    ),
    'danielmarschall/vnag' =>
    array (
      'pretty_version' => 'dev-master',
      'version' => 'dev-master',
      'aliases' =>
      array (
        0 => '9999999-dev',
      ),
      'reference' => '4cee6e674e6628b4deeb0327ab65109da4f28f4d',
    ),
    'dcodeio/bcrypt.js' =>
    array (
      'pretty_version' => 'master',
      'version' => 'dev-master',
      'aliases' =>
      array (
      ),
      'reference' => 'master',
    ),
    'emn178/js-sha3' =>
    array (
      'pretty_version' => 'master',
      'version' => 'dev-master',
      'aliases' =>
      array (
      ),
      'reference' => 'master',
    ),
    'firebase/php-jwt' =>
    array (
      'pretty_version' => 'v5.5.1',
      'version' => '5.5.1.0',
      'aliases' =>
      array (
      ),
      'reference' => '83b609028194aa042ea33b5af2d41a7427de80e6',
    ),
    'gedmarc/layout' =>
    array (
      'pretty_version' => 'master',
      'version' => 'dev-master',
      'aliases' =>
      array (
      ),
      'reference' => 'master',
    ),
    'matthiasmullie/minify' =>
    array (
      'pretty_version' => '1.3.66',
      'version' => '1.3.66.0',
      'aliases' =>
      array (
      ),
      'reference' => '45fd3b0f1dfa2c965857c6d4a470bea52adc31a6',
    ),
    'matthiasmullie/path-converter' =>
    array (
      'pretty_version' => '1.1.3',
      'version' => '1.1.3.0',
      'aliases' =>
      array (
      ),
      'reference' => 'e7d13b2c7e2f2268e1424aaed02085518afa02d9',
    ),
    'n-other/php-sha3' =>
    array (
      'pretty_version' => 'dev-master',
      'version' => 'dev-master',
      'aliases' =>
      array (
        0 => '9999999-dev',
      ),
      'reference' => '54ee3b90986e88286d333319e6340b90bde4f71a',
    ),
    'spamspan/spamspan' =>
    array (
      'pretty_version' => 'master',
      'version' => 'dev-master',
      'aliases' =>
      array (
      ),
      'reference' => 'master',
    ),
    'symfony/polyfill-mbstring' =>
    array (
      'pretty_version' => 'v1.19.0',
      'version' => '1.19.0.0',
      'aliases' =>
      array (
      ),
      'reference' => 'b5f7b932ee6fa802fc792eabd77c4c88084517ce',
    ),
    'tinymce/tinymce' =>
    array (
      'pretty_version' => '5.10.2',
      'version' => '5.10.2.0',
      'aliases' =>
      array (
      ),
      'reference' => 'ef9962f1d40abbb80a4fd4f023151fd28f891a6c',
    ),
    'twbs/bootstrap' =>
    array (
      'pretty_version' => 'v5.1.3',
      'version' => '5.1.3.0',
      'aliases' =>
      array (
      ),
      'reference' => '1a6fdfae6be09b09eaced8f0e442ca6f7680a61e',
    ),
    'tweeb/tinymce-i18n' =>
    array (
      'pretty_version' => '2.0.3',
      'version' => '2.0.3.0',
      'aliases' =>
      array (
      ),
      'reference' => '9be0b61d0d91bba1f9a5c34f4830752b5da987ef',
    ),
    'twitter/bootstrap' =>
    array (
      'replaced' =>
      array (
        0 => 'v5.1.3',
      ),
    ),
    'vakata/jstree' =>
    array (
      'pretty_version' => '3.3.12',
      'version' => '3.3.12.0',
      'aliases' =>
      array (
      ),
      'reference' => '7a03954015eaea2467956dc05e6be78f4d1a1ff0',
    ),
  ),
);
private static $canGetVendors;
private static $installedByVendor = array();







public static function getInstalledPackages()
{
$packages = array();
foreach (self::getInstalled() as $installed) {
$packages[] = array_keys($installed['versions']);
}

if (1 === \count($packages)) {
return $packages[0];
}

return array_keys(array_flip(\call_user_func_array('array_merge', $packages)));
}









public static function isInstalled($packageName)
{
foreach (self::getInstalled() as $installed) {
if (isset($installed['versions'][$packageName])) {
return true;
}
}

return false;
}














public static function satisfies(VersionParser $parser, $packageName, $constraint)
{
$constraint = $parser->parseConstraints($constraint);
$provided = $parser->parseConstraints(self::getVersionRanges($packageName));

return $provided->matches($constraint);
}










public static function getVersionRanges($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

$ranges = array();
if (isset($installed['versions'][$packageName]['pretty_version'])) {
$ranges[] = $installed['versions'][$packageName]['pretty_version'];
}
if (array_key_exists('aliases', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['aliases']);
}
if (array_key_exists('replaced', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['replaced']);
}
if (array_key_exists('provided', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['provided']);
}

return implode(' || ', $ranges);
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getVersion($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['version'])) {
return null;
}

return $installed['versions'][$packageName]['version'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getPrettyVersion($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['pretty_version'])) {
return null;
}

return $installed['versions'][$packageName]['pretty_version'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getReference($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['reference'])) {
return null;
}

return $installed['versions'][$packageName]['reference'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getRootPackage()
{
$installed = self::getInstalled();

return $installed[0]['root'];
}








public static function getRawData()
{
@trigger_error('getRawData only returns the first dataset loaded, which may not be what you expect. Use getAllRawData() instead which returns all datasets for all autoloaders present in the process.', E_USER_DEPRECATED);

return self::$installed;
}







public static function getAllRawData()
{
return self::getInstalled();
}



















public static function reload($data)
{
self::$installed = $data;
self::$installedByVendor = array();
}





private static function getInstalled()
{
if (null === self::$canGetVendors) {
self::$canGetVendors = method_exists('Composer\Autoload\ClassLoader', 'getRegisteredLoaders');
}

$installed = array();

if (self::$canGetVendors) {
foreach (ClassLoader::getRegisteredLoaders() as $vendorDir => $loader) {
if (isset(self::$installedByVendor[$vendorDir])) {
$installed[] = self::$installedByVendor[$vendorDir];
} elseif (is_file($vendorDir.'/composer/installed.php')) {
$installed[] = self::$installedByVendor[$vendorDir] = require $vendorDir.'/composer/installed.php';
}
}
}

$installed[] = self::$installed;

return $installed;
}
}
