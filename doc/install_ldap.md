
How to use LDAP / ActiveDirectory login
=======================================

(1) In your PHP.ini, make sure that the "LDAP" extension is activated, e.g.:

	extension_dir = "ext"
	extension=ldap

(2) In userdata/baseconfig/config.inc.php, please add following lines,
and adjust them to your configuration:

	OIDplus::baseConfig()->setValue('LDAP_ENABLED',                true);
	OIDplus::baseConfig()->setValue('LDAP_SERVER',                 'ldap://server1.contoso.local');
	OIDplus::baseConfig()->setValue('LDAP_PORT',                   389);
	OIDplus::baseConfig()->setValue('LDAP_BASE_DN',                'DC=CONTOSO,DC=local');
	OIDplus::baseConfig()->setValue('LDAP_AUTHENTICATE_UPN',       true);
	OIDplus::baseConfig()->setValue('LDAP_AUTHENTICATE_EMAIL',     false);
	OIDplus::baseConfig()->setValue('LDAP_ADMIN_GROUP',            '');
	OIDplus::baseConfig()->setValue('LDAP_RA_GROUP',               '');

An explanation of these settings can be found in **doc/config_values.md**.
