# Migration from OIDplus 2.0.0/2.0.1 plugins to 2.0.2 plugins

## Help required?

In case you have developed plugins, we convert your plugins to 2.0.2 FOR FREE! Just send us a note!

## Change 1: PHP Namespace

Old behavior: All plugin class files could have any namespace. The autoloader only checked the class name (without namespace) and searched for `plugins/*/*/*/*.class`. This was very bad, because the namespace was completely ignored in the old version of OIDplus, and the `glob()` operations were slow.

New behavior: Every plugin directory must be mapped to an **unique** namespace. This behavior is similar to PSR-4, just with the difference that the extension is `.class.php` instead of `.php`. The namespace is defined in the JSON manifest of that plugin. For example: Plugin `plugins/contoso_ltd/publicPages/foobar` could define the namespace `ContosoLtd\FooBarPlugin`, and this means that the autoloader will load the class `ContosoLtd\FooBarPlugin\ABC` as `plugins/contoso_ltd/publicPages/foobar/ABC.class.php`. It is recommended (but not obligatory) to use the namespace scheme `ContosoLtd\OIDplus\Plugin\PublicPages\FooBar`.

## Change 2: Manifest

1. The manifest must be written in JSON instead of XML
4. JSON attribute `manifest/type` must be changed from `"ViaThinkSoft\\OIDplus\\XYZ"` to `"ViaThinkSoft\\OIDplus\\Core\\XYZ"`,
2. JSON attribute `manifest/php/classname` must only contain the classname without namespace (previously, it had to include the namespace)
3. New JSON attribute `manifest/php/namespace` must contain the unique namespace that is mapped to the plugin's directory. It must not start with a backslash, but it must end with a backslash.
5. It is recommended to delete the XML manifest file

## Change 3: Changed namespace for core and bundled plugins

1. The core classes (`includes/classes/*.class.php`) are now in namespace `ViaThinkSoft\OIDplus\Core` instead of `ViaThinkSoft\OIDplus`. You need to adjust your `use` clauses accordingly.

2. The official plugin classes and interfaces (`plugins/viathinksoft/*/*/*.class.php`) are now in namespace `ViaThinkSoft\OIDplus\Plugins\*\*` instead of `ViaThinkSoft\OIDplus`. So, if you communicate with the bundled plugins and/or their interfaces (`INTF_OID_...`), then you need to adjust your `use` clauses accordingly.

## Change 4: Changed method signatures

Due to the support of PHP 7.4, more parameter and return types are now allowed, which have been included in the source of the core files and bundled plugin interfaces. For example, some methods now have the return type `: void` which you need to add if you inherit these methods. PHPStorm or PHPStan will help you giving information which signatures are wrong and need to be updated.

## Change 5: `DISABLE_PLUGIN_` baseconfig setting

The basename setting `DISABLE_PLUGIN_...`  now requires the plugin OID rather than a classname. (Reason: The OID is unique, while a namespace can be changed, as we have now witnessed)

Example: `OIDplus::baseConfig()->setValue('DISABLE_PLUGIN_1.3.6.1.4.1.37476.2.5.2.4.7.300', '1')` disables the plugin "plugins/viathinksoft/logger/300_userdata_logfile".

So, you need to change your baseconfig files as well as your plugins.

Since the OID is not self explanatory, it is recommended that you add a comment near setValue() and getValue():

```
if (OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_1.3.6.1.4.1.37476.2.5.2.4.7.300')) { // check if plugin userdata_logfile is disabled
  throw new OIDplusException(_L("This plugin is disabled"));
}
```

## Other recommendations

1. In pure PHP files (*.php, not *.class.php) it is recommended to only use `use` but not define `namespace`.  `namespace` should only be used inside *.class.php files

2. It is recommended to NOT use fully-qualified class names in your code and instead use the `use` clauses at the head of each PHP file. This saves time in case the namespaces would change again in the future.

3. It is recommended to check if your plugin passes the checks of the IDE "PHP Storm" (free for OpenSource projects). It reports any missing `use` clause or unknown classes.

6. Your plugin shall NOT require PHP 8.0. The core and all plugins should be backwards-compatible to PHP 7.4. So, for example, you must not use union types (e.g. `int|false`) in the code; but you may use it in PHPdoc of course.
