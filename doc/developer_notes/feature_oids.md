
Plugin-to-Plugin communication using "Features" (INTF_OID interfaces)
=====================================================================

System-to-Plugin and Plugin-to-System communication
---------------------------------------------------

PHP classes belonging to the OIDplus system (such as the class handling the tree menu structure,
the sitemap, the AJAX handling, etc) are communicating to plugins the "normal way", i.e.
the base classes define functions that are overridden by the plugins.

Plugin-to-Plugin communication
------------------------------

Plugins can offer interfaces/functionalities that can be used by other plugins. (Plugin-to-Plugin communication)

Example: The OOBE plugin queries other plugins if they want to be included
in the Out-Of-Box-Experience configuration. Therefore the plugins need to implement an
"oobeEntry" function.

For the plugin-to-plugin communication, the PHP interfaces offered by the plugin *MUST*
have the prefix "INTF_OID_" followed by an OID (dots replaced by underscores), e.g. "INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_1".
The OIDplus autoloader will make sure that interfaces following this naming convention
are replaced with an empty "fake" interface if the plugin defining the interface is not installed.
Therefore, the interface is "optional" and there won't be a fatal error if a plugin implements (references)
a feature that is unknown.

Previously, the plugin-to-plugin-communication used a function called implementsFeature(), which
has been discontinued as of 26 March 2023, because types could not be easily checked.

### Usage example (new method):

Implement feature in a class:

    class OIDplusPageAdminNostalgia
          extends OIDplusPagePluginAdmin
          implements INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8 /* getNotifications */
    {
        public function getNotifications(...) {
            /* Implements interface INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8 */
            ...
        }
    }

Call all plugins supporting the feature:

    foreach (OIDplus::getAllPlugins() as $plugin) {
        if ($plugin instanceof INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8) {
            $plugin->getNotifications(...);
        }
    }

### Old method (deprecated as of 26 March 2023):

Implement feature in a class:

    class OIDplusPageAdminNostalgia
          extends OIDplusPagePluginAdmin
    {
        public function implementsFeature(string $oid): bool {
            if ($oid == '1.3.6.1.4.1.37476.2.5.2.3.8') return true; /*getNotifications*/
            return false;
        }
        public function getNotifications(...) {
            /* Implements feature 1.3.6.1.4.1.37476.2.5.2.3.8 */
            ...
        }
    }

Call all plugins supporting the feature:

    foreach (OIDplus::getAllPlugins() as $plugin) {
        if ($plugin->implementsFeature('1.3.6.1.4.1.37476.2.5.2.3.8')) {
            $plugin->getNotifications(...);
        }
    }

List of defined features
------------------------

Currently, there are the following features defined in the standard plugins of OIDplus:

- plugins/viathinksoft/adminPages/050_oobe/INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_1.class.php containing the functions:
    - oobeEntry
    - oobeRequested

- plugins/viathinksoft/publicPages/000_objects/INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_2.class.php containing the functions:
    - modifyContent

- plugins/viathinksoft/publicPages/000_objects/INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_3.class.php containing the functions:
    - beforeObjectDelete
    - afterObjectDelete
    - beforeObjectUpdateSuperior
    - afterObjectUpdateSuperior
    - beforeObjectUpdateSelf
    - afterObjectUpdateSelf
    - beforeObjectInsert
    - afterObjectInsert

- plugins/viathinksoft/publicPages/100_whois/INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_4.class.php containing the functions:
    - whoisObjectAttributes
    - whoisRaAttributes

- plugins/viathinksoft/publicPages/090_login/INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_5.class.php containing the functions:
    - alternativeLoginMethods

- plugins/viathinksoft/publicPages/000_objects/INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_6.class.php containing the functions:
    - gridGeneratorLinks

- plugins/viathinksoft/publicPages/000_objects/INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_7.class.php containing the functions:
    - getAlternativesForQuery

- plugins/viathinksoft/adminPages/010_notifications/INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8.class.php containing the functions:
    - getNotifications

- plugins/viathinksoft/publicPages/002_rest_api/INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_9.class.php containing the functions:
    - restApiCall
    - restApiInfo

- plugins/viathinksoft/publicPages/998_polyfill/INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_10.class.php containing the functions:
    - requestPolyfills
