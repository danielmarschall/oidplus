
Compatibility notes
===================

Requirements regarding compatibility
------------------------------------

- The minimum required PHP version should be PHP 7.4, at least until the Debian old-old-stable distro has lost its extended support.
- OIDplus should always be compatible with the latest available PHP version
- OIDplus must be compatible with all modern browsers
- Search engines must be able to crawl all public content. This is the reason why a page contains static content (loaded in index.php) for the first navigation, instead of fetching everything through ajax.php.

Regarding the PHP version, we follow the [Debian LTS cycle](https://wiki.debian.org/LTS), so that we have the following constraints:

- PHP 7.1 - 7.3 is allowed after 2022-06-30, because this is the LTS end of Debian "Stretch" (that bundled PHP 7.0)
- PHP 7.4 is allowed after 2024-06-30, because this is the LTS end of Debian "Buster" (that bundled PHP 7.3)
- PHP 8.0 - 8.2 is allowed after 2026-08-31, because this is the LTS end of Debian "Bullseye" (that bundled PHP 7.4)
- PHP 8.3+ is allowed after 2028-06-30, because this is the LTS end of Debian "Bookworm" (that bundled PHP 8.2)

Dependencies
------------

When a new PHP extension is required, then please change the following:

1. README file

2. OIDplus product website
   https://oidplus.com/download.php

3. Add checks in includes/oidplus_dependency.inc.php

Notes about the required PHP Version
------------------------------------

Currently, OIDplus supports all PHP versions between 7.4.0 and 8.3.x.

Once we accept PHP 8.0+, we can change the following:
- More types can be used, e.g. "A|B" syntax
- More things to change or consider, see [the issue that handled the update "PHP 7.0 => 7.4"](https://github.com/danielmarschall/oidplus/issues/56)
	* Change README.md, doc/developer_notes/compatibility_notes.md, oidplus webpage, and oidplus webpage backup (in svn/git)
	* Change plugins/viathinksoft/adminPages/900_software_update/private generator to generate a check for the PHP check, to avoid that a system with an old PHP version will get bricked if it updates. The update must cancel if the old PHP version is used.
	* Change composer.json: change our own PHP min version
	* Do a check with PHPStan to see if it detects PHP version compatibility issues
	* Change min version check in oidplus.inc.php

Note: How to check the effective PHP version in composer?

    composer show --tree
