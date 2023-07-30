
Compatibility notes
===================

Requirements regarding compatibility
------------------------------------

- The minimum required PHP version should be PHP 7.0, at least until the last old-stable Linux distro has lost its extended support.
- OIDplus should always be compatible with the latest available PHP version
- OIDplus must be compatible with all modern browsers
- Search engines must be able to crawl all public content. This is the reason why a page contains static content (loaded in index.php) for the first navigation, instead of fetching everything through ajax.php.

Dependencies
------------

When a new PHP extension is required, then please change the following:

1. README file

2. OIDplus product website
   https://oidplus.com/download.php

3. Add checks in includes/oidplus_dependency.inc.php

Notes about the required PHP Version
------------------------------------

Currently, OIDplus supports all PHP versions between 7.0.0 and 8.2.x.

Reasons why we currently require **at least** PHP 7.0:
- Return values (e.g. "function foo(): array") are used in many places
  of OIDplus as well as in dependencies ("vendor" folder)
- Some composer packages require PHP >= 7.0.0
    - phpseclib/phpseclib's dependency requires PHP>=7
    - danielmarschall repositories require PHP>=7, however, they *might*
      also work with a lower version of PHP (not tested)
- The compatibility (beside the things mentioned above)
  with PHP lower than 7.0 was not tested. There might be more issues.

Currently we DO NOT require 7.1, because some (old-)stable Linux distros are still using PHP 7.0.
Therefore, we commented out following features which would require PHP >=7.1:
- Nullable return values (e.g. "function foo(): ?array")
- void return value (e.g. "function foo(): void")
- private/protected/public consts
- In composer we cannot use the latest version of polyfill-mbstring,
  because this would require PHP 7.1
  Therefore in composer.json we write:
  "symfony/polyfill-mbstring": "<=1.19",
  the same thing with phpseclib which also now requires PHP 7.1,
  therefore we stay with 3.0 LTS:
  "phpseclib/phpseclib": "~3.0"
  Once PHP 7.1 is OK, we can go with the latest versions again.
- In dev/vendor_update.sh we patch composer packages pulled from GitHub
  to achieve PHP 7.0 compat


Note: How to check the effective PHP version in composer?

    composer show --tree
