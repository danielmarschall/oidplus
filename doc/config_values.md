
Overview of all config.inc.php settings
=======================================

The file **userdata/baseconfig/config.inc.php** contains various settings
which are essential to connect to your database and other
things that should be known before the database connection
is opened.
Other settings are stored in the database (table "config")
and can be accessed using the admin login area.

The setup assistant (/setup/) will lead you through
the creation of the most important settings of **config.inc.php**.

Below you will find a list of all possible config settings
of the default OIDplus installation/plugins.
Please note that a plugin can define any key.


(1) Config settings provided by the setup assistant
---------------------------------------------------

### CONFIG_VERSION

    OIDplus::baseConfig()->setValue('CONFIG_VERSION',           2.1);

Always set to 2.1 in the latest format.

### ADMIN_PASSWORD

    OIDplus::baseConfig()->setValue('ADMIN_PASSWORD',           '<BCrypt hash, or base64 encoded SHA3-512 hash>');

If you want to have multiple valid administrator passwords
(e.g. if you want multiple users), then this value can
also be an array containing hashes.

### DATABASE_PLUGIN

    OIDplus::baseConfig()->setValue('DATABASE_PLUGIN',          '');

Valid values: see plugins (setup/).

### OCI_CONN_STR

    OIDplus::baseConfig()->setValue('OCI_CONN_STR',             'localhost/orcl');

### OCI_*

Can be a Oracle connection string/TNS or a hostname like.

    OIDplus::baseConfig()->setValue('OCI_USERNAME',             'hr');
    OIDplus::baseConfig()->setValue('OCI_PASSWORD',             'oracle');

Used by the Oracle database plugin.

### ODBC_*

    OIDplus::baseConfig()->setValue('ODBC_DSN',                 'DRIVER={SQL Server};SERVER=localhost;DATABASE=oidplus;CHARSET=UTF8');
    OIDplus::baseConfig()->setValue('ODBC_USERNAME',            'sa');
    OIDplus::baseConfig()->setValue('ODBC_PASSWORD',            base64_decode('<base64_encoded_password>')); // alternatively as plaintext

Used by the ODBC database plugin.

Username and password are not required if you want to use SQL Server Integrated Security,
or if the DBMS does not require credentials (e.g. a File DB).

The base64 encoding protects your password from being read if someone
"looks over your shoulder" at your display while you have the configuration file opened.
(Obviously, it doesn't protect you if they can make a photo or screenshot)

### PDO_*

    OIDplus::baseConfig()->setValue('PDO_DSN',                  'pgsql:host=localhost;dbname=oidplus');
    OIDplus::baseConfig()->setValue('PDO_USERNAME',             'postgres');
    OIDplus::baseConfig()->setValue('PDO_PASSWORD',             base64_decode('<base64_encoded_password>')); // alternatively as plaintext

Used by the PDO datbase plugin.

Username and password are not required if you want to use SQL Server Integrated Security,
or if the DBMS does not require credentials (e.g. a File DB).

The base64 encoding protects your password from being read if someone
"looks over your shoulder" at your display while you have the configuration file opened.
(Obviously, it doesn't protect you if they can make a photo or screenshot)

### ADO_*

    OIDplus::baseConfig()->setValue('ADO_CONNECTION_STRING',    'Provider=MSOLEDBSQL;Data Source=LOCALHOST\SQLEXPRESS;Initial Catalog=oidplus;Integrated Security=SSPI');

Used by the ADO datbase plugin.

### SQLSRV_*

    OIDplus::baseConfig()->setValue('SQLSRV_SERVER',            'localhost\oidplus');
    OIDplus::baseConfig()->setValue('SQLSRV_USERNAME',          '');
    OIDplus::baseConfig()->setValue('SQLSRV_PASSWORD',          base64_decode('<base64_encoded_password>')); // alternatively as plaintext
    OIDplus::baseConfig()->setValue('SQLSRV_DATABASE',          'oidplus');
    OIDplus::baseConfig()->setValue('SQLSRV_OPTIONS',           array());

Used by the SQLSRV datbase plugin.

SQLSRV_OPTIONS can be filled with various connection info options
(see PHP documentation for sqlsrv_connect).
The following fields will be automatically filled if they are not explicitly overridden:
- `UID` will be filled with `SQLSRV_USERNAME`
- `PWD` will be filled with `SQLSRV_PASSWORD`
- `Database` will be filled with `SQLSRV_DATABASE`
- `CharacterSet` will be filled with `"UTF-8"`

Username and password are not required if you want to use SQL Server Integrated Security,
or if the DBMS does not require credentials (e.g. a File DB).

The base64 encoding protects your password from being read if someone
"looks over your shoulder" at your display while you have the configuration file opened.
(Obviously, it doesn't protect you if they can make a photo or screenshot)

### MYSQL_*

    OIDplus::baseConfig()->setValue('MYSQL_HOST',               'localhost:3306');

The hostname to connect to. Port (:3306) is optional.

    OIDplus::baseConfig()->setValue('MYSQL_SOCKET',             '');

In case you connect via MySQL through a socket, use this setting.
(It is currently not included in setup/ and needs to be set manually).

    OIDplus::baseConfig()->setValue('MYSQL_USERNAME',           'root');
    OIDplus::baseConfig()->setValue('MYSQL_PASSWORD',           base64_decode('<base64_encoded_password>')); // alternatively as plaintext
    OIDplus::baseConfig()->setValue('MYSQL_DATABASE',           'oidplus');

Used by the MySQL database plugin.

The base64 encoding protects your password from being read if someone
"looks over your shoulder" at your display while you have the configuration file opened.
(Obviously, it doesn't protect you if they can make a photo or screenshot)

### PGSQL_*

    OIDplus::baseConfig()->setValue('PGSQL_HOST',               'localhost:5432');
    OIDplus::baseConfig()->setValue('PGSQL_SOCKET',             '');
    OIDplus::baseConfig()->setValue('PGSQL_USERNAME',           'postgres');
    OIDplus::baseConfig()->setValue('PGSQL_PASSWORD',           base64_decode('<base64_encoded_password>')); // alternatively as plaintext
    OIDplus::baseConfig()->setValue('PGSQL_DATABASE',           'oidplus');

Used by the PgSQL databse plugin.

The hostname to connect to. Port (:5432) is optional.

In case you connect via PostgreSQL through a socket, use this setting.
(It is currently not included in setup/ and needs to be set manually).

The base64 encoding protects your password from being read if someone
"looks over your shoulder" at your display while you have the configuration file opened.
(Obviously, it doesn't protect you if they can make a photo or screenshot)

### SQLITE3_*

    OIDplus::baseConfig()->setValue('SQLITE3_FILE',             'userdata/database/oidplus.db');
    OIDplus::baseConfig()->setValue('SQLITE3_ENCRYPTION',       '');

Attention: This file must be located in a location that is not world-readable/downloadable!

The encryption is optional.

### TABLENAME_PREFIX

    OIDplus::baseConfig()->setValue('TABLENAME_PREFIX',         'oidplus_');

Every table has this prefix, e.g. oidplus_config.

### SERVER_SECRET

    OIDplus::baseConfig()->setValue('SERVER_SECRET',            'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');

It is very very important that you choose a long sequence of
random characters. OIDplus uses this secret for various
security related purposes. If someone accidently received this secret,
please change the sequence.

### CAPTCHA_PLUGIN

    OIDplus::baseConfig()->setValue('CAPTCHA_PLUGIN',           'None');

Alternative Values (installed plugins)
- `reCAPTCHA`
- `hCaptcha`
- `ViaThinkSoft Client Challenge`

Please note that the value is case-sensitive.

### RECAPTCHA_VERSION

    OIDplus::baseConfig()->setValue('RECAPTCHA_VERSION',        \ViaThinkSoft\OIDplus\OIDplusCaptchaPluginRecaptcha::RECAPTCHA_V2_CHECKBOX);

Possible values:
- `\ViaThinkSoft\OIDplus\OIDplusCaptchaPluginRecaptcha::RECAPTCHA_V2_CHECKBOX`
- `\ViaThinkSoft\OIDplus\OIDplusCaptchaPluginRecaptcha::RECAPTCHA_V2_INVISIBLE`
- `\ViaThinkSoft\OIDplus\OIDplusCaptchaPluginRecaptcha::RECAPTCHA_V3`

### RECAPTCHA_ENABLED

    OIDplus::baseConfig()->setValue('RECAPTCHA_ENABLED',        true);

Deprecated!
- `RECAPTCHA_ENABLED=true`  becomes `CAPTCHA_PLUGIN=reCAPTCHA`
- `RECAPTCHA_ENABLED=false` becomes `CAPTCHA_PLUGIN=None`

### RECAPTCHA_*

    OIDplus::baseConfig()->setValue('RECAPTCHA_PUBLIC',         '');
    OIDplus::baseConfig()->setValue('RECAPTCHA_PRIVATE',        '');

Only used if `CAPTCHA_PLUGIN=reCAPTCHA`.

### HCAPTCHA_*

    OIDplus::baseConfig()->setValue('HCAPTCHA_SITEKEY',         '');
    OIDplus::baseConfig()->setValue('HCAPTCHA_SECRET',          '');

Only used if `CAPTCHA_PLUGIN=hCaptcha`.

### VTS_CAPTCHA_*

    OIDplus::baseConfig()->setValue('VTS_CAPTCHA_COMPLEXITY',   50000);
    OIDplus::baseConfig()->setValue('VTS_CAPTCHA_AUTOSOLVE',    true);
    OIDplus::baseConfig()->setValue('VTS_CAPTCHA_MAXTIME',      10*60/*10 minutes*/);

Only used if `CAPTCHA_PLUGIN=ViaThinkSoft Client Challenge`.

### ENFORCE_SSL

    OIDplus::baseConfig()->setValue('ENFORCE_SSL',              OIDplus::ENFORCE_SSL_AUTO);

Values are:
- `OIDplus::ENFORCE_SSL_NO` (0) = (off)
- `OIDplus::ENFORCE_SSL_YES` (1) = (on)
- `OIDplus::ENFORCE_SSL_AUTO` (2) = (auto detect SSL)


(2) System limitations 
----------------------

The system limitations are defined and documented in includes/oidplus_limits.inc.php
and can be overwritten by config.inc.php.

### LIMITS_MAX_ID_LENGTH

    OIDplus::baseConfig()->setValue('LIMITS_MAX_ID_LENGTH',     255);

Example: OID 2.999.123.456 has a length of 13 characters in dot notation.
OIDplus adds the prefix "oid:" in front of every OID,
so the overal length of the ID would be 17.

Default value: 255 digits (OIDs 251 digits)

Which value is realistic? In the oid-info.com database (April 2020), the OID with the greatest size is 65 characters (dot notation)

Maximum value: OIDs may only have a size of max 251 characters in dot notation.
Reason: The field defintion of *_objects.oid is defined as varchar(255),
and the OID will have the prefix 'oid:' (4 bytes).
You can increase the limit by changing the field definition in the database.

### LIMITS_MAX_OID_ASN1_ID_LEN

    OIDplus::baseConfig()->setValue('LIMITS_MAX_OID_ASN1_ID_LEN',  255);

Default value: 255 characters

Maximum value: 255, as defined in the database fields *_asn1id.name
You can change the database field definition if you really need more.

### LIMITS_MAX_OID_UNICODE_LABEL_LEN

    OIDplus::baseConfig()->setValue('LIMITS_MAX_OID_UNICODE_LABEL_LEN',  255);

Default value: 255 bytes (UTF-8 encoded!)

Maximum value: 255, as defined in the database fields *_iri.name
You can change the database field definition if you really need more.


(3) "Hidden"/undocumented config settings
-----------------------------------------

### OFFLINE_MODE

    OIDplus::baseConfig()->setValue('OFFLINE_MODE', false);

If set to true, OIDplus will not contact other servers. No system registration,
no hCaptcha, no reCAPTCHA, no system updates, etc.

### OIDINFO_API_URL

    OIDplus::baseConfig()->setValue('OIDINFO_API_URL', '<url>');

Currently only internal use for development utilities (dev/).
The API to oid-info.com is currently not public.

### REGISTRATION_HIDE_SYSTEM

    OIDplus::baseConfig()->setValue('REGISTRATION_HIDE_SYSTEM', true);

Set this if you have a clone of a productive system and you want
to avoid that the clone registers at the ViaThinkSoft directory
(which would overwrite the URL of the productive system and reveal
the URL of your testing system)

### MYSQL_FORCE_MYSQLND_SUPPLEMENT

    OIDplus::baseConfig()->setValue('MYSQL_FORCE_MYSQLND_SUPPLEMENT',         false);

The MySQLi plugin contains a supplement code to handle
prepared statements on servers which do not have the MySQLnd extension
installed. Set this flag to force the supplement to be used,
even if MySQLnd is available. (For testing purposes only)

### QUERY_LOGFILE

    OIDplus::baseConfig()->setValue('QUERY_LOGFILE',          '');

Set this setting to a filename where all queries including timestamps would be written.
This is used for performance analysis.
Please choose a directory that cannot be accessed by world-wide.

### SESSION_LIFETIME

    OIDplus::baseConfig()->setValue('SESSION_LIFETIME', 30*60);

Session lifetime in seconds.

### OBJECT_CACHING

    OIDplus::baseConfig()->setValue('OBJECT_CACHING',         true);

Object caching reads all objects in the memory. This increases performance
performance but also increases memory usage on large databases.

### FORCE_DBMS_SLANG

    OIDplus::baseConfig()->setValue('FORCE_DBMS_SLANG', '');

Currently valid values:
- `access`
- `firebird`
- `mssql`
- `mysql`
- `oracle`
- `pgsql`
- `sqlite`

### PREPARED_STATEMENTS_EMULATION

    OIDplus::baseConfig()->setValue('PREPARED_STATEMENTS_EMULATION', 'auto');

Currently only for ODBC database plugin.
- `auto` = Auto detect if prepared statements should be emulated
- `on` = Always emulate prepared statements
- `off` = Never emulate prepared statements

### MINIFY_CSS

    OIDplus::baseConfig()->setValue('MINIFY_CSS', true);

This enables the compression of CSS definitions. 
- Compressed approx:   220 KB
- Uncompressed approx: 224 KB

### MINIFY_JS

    OIDplus::baseConfig()->setValue('MINIFY_JS',  true);

This enables the compression of JavaScript code.
Please only disable this, if you want to debug
the code! You should not disable it on a productive
system, because otherwise the JavaScript code
would be several Megabytes large. 
- Compressed approx:  1133 KB
- Unompressed approx: 2761 KB

### DISABLE_PLUGIN_*

    OIDplus::baseConfig()->setValue('DISABLE_PLUGIN_...', true);

This gives you the possibility to disable a plugin without
requiring it to be removed from the file system.
(Removing a plugin from the file system can result in various
problems, e.g. they can be re-added during a SVN/software update.)
Replace "..." with the main PHP class of the plugin you want to disable.
The namespace must be included.
Example:
`"DISABLE_PLUGIN_ViaThinkSoft\OIDplus\OIDplusLoggerPluginUserdataLogfile"`
disables the plugin "logger/300_userdata_logfile".

### DISABLE_AJAX_TRANSACTIONS

    OIDplus::baseConfig()->setValue('DISABLE_AJAX_TRANSACTIONS', false);

This will disable the usage of database transactions in ajax.php
Do only use this if you have severe problems with the system running.
It might result in inconsistent data e.g. if you update an OID
and an error occurs in the middle of that process.

### CANONICAL_SYSTEM_URL

    OIDplus::baseConfig()->setValue('CANONICAL_SYSTEM_URL', '');

Setting this value to a system URL will override the absolute system URL detection.
It has the following effects:
1. The "canonical" metatag will use this explicit system URL
instead of the one the PHP script is detecting.
(This is important to avoid duplicate content at search indexes)
2. CLI WHOIS and other CLI (Command-line-interface) tools
will use this address when they need to output an URL.
Otherwise, the CLI tools would need to use the last known
URL that was detected when a webpage visitor has last visited the
page.
3. While most resources (images, CSS files, scripts, etc.) are loaded
via relative URLs, sometimes an absolute URL is required
(e.g., if an email is sent with an activation link).
The explicit absolute system URL will then be used rather
than the automatically detected one.
Note that setting an absolute system URL can be very useful if
OIDplus runs on a system (which detects itself as "X"),
while the canonical URL "Y" is a reverse-proxy.

### DEBUG

    OIDplus::baseConfig()->setValue('DEBUG', false);

Enables some special checks for plugins (e.g. a self-test for auth plugins).
It is highly recommended that you enable DEBUG if you are developing
plugins!
It is recommended to disable this switch in productive systems,
because the self-tests decrease the performance.
However, after installing a new plugin, you might want to enable
it for a few minutes, to make sure the plugin is working correctly.

### COOKIE_SAMESITE_POLICY

    OIDplus::baseConfig()->setValue('COOKIE_SAMESITE_POLICY', 'Strict');

Defined which "SameSite" policy should be used for the cookies OIDplus uses.
Can be "None", "Lax" or "Strict".
"Strict" is the most secure setting.
"Lax" allows that people stay logged in if they follow a link pointing
to your OIDplus installation.
"None" is not recommended and is deprecated by modern web browsers.
However, OIDplus itself provides an Anti-CSRF mechanism, so you should be
still safe.

### COOKIE_DOMAIN

    OIDplus::baseConfig()->setValue('COOKIE_DOMAIN', '');

Can be used to increase security by setting an explicit domain-name in the cookies.
Set to '' (empty string) to allow all (sub)domains.
Set to '(auto)' to automatically detect the domain based on the absolute canonical path.

### COOKIE_PATH

    OIDplus::baseConfig()->setValue('COOKIE_PATH', '/');

Can be used to increase security by setting an explicit pathname in the cookies.
Set to '/' to allow all paths.
Set to '(auto)' to automatically detect the path based on the absolute canonical path.
Note: If supported, you can use Apache's "ProxyPassReverseCookiePath" to translate
the cookie path in a reverse-proxy setting.

### RA_PASSWORD_PEPPER

    OIDplus::baseConfig()->setValue('RA_PASSWORD_PEPPER', '');

The pepper is stored inside the base configuration file
It prevents that an attacker with SQL write rights can
create accounts.

ATTENTION!!! If a pepper is used, then the
hashes are bound to that pepper. If you change the pepper,
then ALL passwords of RAs become INVALID!

### RA_PASSWORD_PEPPER_ALGO

    OIDplus::baseConfig()->setValue('RA_PASSWORD_PEPPER_ALGO', 'sha512');

The pepper is stored inside the base configuration file
It prevents that an attacker with SQL write rights can
create accounts.
ATTENTION!!! If a pepper is used, then the
hashes are bound to that pepper. If you change the pepper,
then ALL passwords of RAs become INVALID!

### DEFAULT_LANGUAGE

    OIDplus::baseConfig()->setValue('DEFAULT_LANGUAGE', 'enus');

Default language of the system. This is the language
a new visitor will see if no "lang=" parameter is used
and no cookie is set.
Must be a valid language in the plugins directory.
Currently available:
- `enus` = English USA (default)
- `dede` = German Germany

(4) LDAP settings
-----------------

(see also document ldap_installation.md)

### LDAP_ENABLED

    OIDplus::baseConfig()->setValue('LDAP_ENABLED',                true);

Set to true if you want to enable that users can log-in using LDAP / ActiveDirectory.

### LDAP_NUM_DOMAINS

    OIDplus::baseConfig()->setValue('LDAP_NUM_DOMAINS',            1);

Contains the number of domains/servers which are used.
For 2nd, 3rd, 4th, ... domain use the fields LDAP_xxx__2, LDAP_xxx__3, ...
e.g.   LDAP_SERVER__2
LDAP_PORT__2
LDAP_BASE_DN__2
...

### LDAP_SERVER

    OIDplus::baseConfig()->setValue('LDAP_SERVER',                 'ldap://server1.contoso.local');

The LDAP server of your company.

### LDAP_PORT

    OIDplus::baseConfig()->setValue('LDAP_PORT',                   389);

The port of the LDAP server.

### LDAP_BASE_DN

    OIDplus::baseConfig()->setValue('LDAP_BASE_DN',                'DC=CONTOSO,DC=local');

The base Distinguished Name (DN) of your directory.

### LDAP_UPN_SUFFIX

    OIDplus::baseConfig()->setValue('LDAP_UPN_SUFFIX',             '@contoso.local');

The UPN suffix of this domain.

### LDAP_AUTHENTICATE_UPN

    OIDplus::baseConfig()->setValue('LDAP_AUTHENTICATE_UPN',       true);

In the login mask, the users will log in using the UPN ("principal name") e.g. username@contoso.local,
and in OIDplus, a RA account with an email equal to the UPN will be created.

### LDAP_AUTHENTICATE_EMAIL

    OIDplus::baseConfig()->setValue('LDAP_AUTHENTICATE_EMAIL',     false);

In the login mask, the users will log in using the UPN ("principal name") e.g. username@contoso.local,
and in OIDplus, a RA account with an email equal to the "E-Mail-Address" field of the user in the directory will be created.
Note: If you did not set an email address to the user in the LDAP/ActiveDirectory, then the login will not be possible,
except if LDAP_AUTHENTICATE_UPN is additionally enabled.
Attention: Depending on your domain configuration, users might be able to change their own data,
e.g. email address. If this is the case, you must not enable this setting, otherwise,
users could authenticate with any address!

### LDAP_ADMIN_GROUP

    OIDplus::baseConfig()->setValue('LDAP_ADMIN_GROUP',            '');

If set to an empty string, the OIDplus administrator account cannot be accessed using LDAP authentication.
Otherwise, the user will be authenticated as administrator, if the LDAP user is a
member of the group specified in this setting.
Example values:
- `CN=Administrators,CN=Builtin,DC=CONTOSO,DC=local`
makes every domain administrator also an OIDplus administrator
- `CN=OIDplus Administrators,CN=Users,DC=CONTOSO,DC=local`
makes every user of the group (OIDplus Administrators) to OIDplus administrators

### LDAP_RA_GROUP

    OIDplus::baseConfig()->setValue('LDAP_RA_GROUP',               '');

If set to an empty string, every LDAP user can authenticate as RA, depending
on whether `LDAP_AUTHENTICATE_UPN` and/or `LDAP_AUTHENTICATE_EMAIL` is set.
Otherwise, the LDAP users must be a member of the group specified in this setting.


(5) Google OAuth2 settings 
--------------------------

(see also document google_oauth2_installation.md)

### GOOGLE_OAUTH2_*

    OIDplus::baseConfig()->setValue('GOOGLE_OAUTH2_ENABLED',       true);
    OIDplus::baseConfig()->setValue('GOOGLE_OAUTH2_CLIENT_ID',     '..............apps.googleusercontent.com');
    OIDplus::baseConfig()->setValue('GOOGLE_OAUTH2_CLIENT_SECRET', '.............');


(6) Facebook OAuth2 settings
----------------------------

(see also document facebook_oauth2_installation.md)

### FACEBOOK_OAUTH2_*

    OIDplus::baseConfig()->setValue('FACEBOOK_OAUTH2_ENABLED',       true);
    OIDplus::baseConfig()->setValue('FACEBOOK_OAUTH2_CLIENT_ID',     '.............'); // Your App ID
    OIDplus::baseConfig()->setValue('FACEBOOK_OAUTH2_CLIENT_SECRET', '.............'); // Your App Secret


(7) JWT authentication settings
-------------------------------

If a web request contains the field "OIDPLUS_AUTH_JWT" containing a signed JWT token,
an automatic one-time login is performed in order to execute commands.
This feature is used in the plugins "Automated AJAX calls" for admins and RAs.
With these switches you can disable this feature.

### JWT_ALLOW_AJAX_ADMIN

    OIDplus::baseConfig()->setValue('JWT_ALLOW_AJAX_ADMIN', true);

Allow JWT tokens that were created using the admin-plugin
"Automated AJAX calls".
    
### JWT_ALLOW_AJAX_USER

    OIDplus::baseConfig()->setValue('JWT_ALLOW_AJAX_USER', true);

Allow JWT tokens that were created using the RA-plugin
"Automated AJAX calls".

### JWT_ALLOW_LOGIN_ADMIN

    OIDplus::baseConfig()->setValue('JWT_ALLOW_LOGIN_ADMIN', true);

Allow "Remember me" logins for the administrator account.

### JWT_ALLOW_LOGIN_USER
    
    OIDplus::baseConfig()->setValue('JWT_ALLOW_LOGIN_USER', true);

Allow "Remember me" logins for a RA.

### JWT_ALLOW_MANUAL
 
    OIDplus::baseConfig()->setValue('JWT_ALLOW_MANUAL', false);

Allow JWT tokens which were manually created "by hand".
These can have any content you like, but they must
contain the claim "oidplus_generator" with value "2".

### JWT_TTL_LOGIN_USER
    
    OIDplus::baseConfig()->setValue('JWT_TTL_LOGIN_USER', 10*365*24*60*60);

How many seconds will a "remember me" login JWT token be valid?
(RA login)

### JWT_TTL_LOGIN_ADMIN

    OIDplus::baseConfig()->setValue('JWT_TTL_LOGIN_ADMIN', 10*365*24*60*60);

How many seconds will a "remember me" login JWT token be valid?
(Administrator login)


(8) Third-party plugins
-----------------------

### FrdlWeb RDAP plugin

    OIDplus::baseConfig()->setValue('RDAP_CACHE_ENABLED',   false );
    OIDplus::baseConfig()->setValue('RDAP_CACHE_DIRECTORY', OIDplus::localpath().'userdata/cache/' );
    OIDplus::baseConfig()->setValue('RDAP_BASE_URI',        OIDplus::webpath() );
    OIDplus::baseConfig()->setValue('RDAP_CACHE_EXPIRES',   60 * 3 );
