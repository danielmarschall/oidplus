
File naming convention for OIDplus
==================================

`*.php`:
These are files that can (and should) be opened by the web browser.

`*.phps`:
These are files that MUST NOT be executed by the web server.
The source is visible to the public, e.g. if you want to demonstrate a code example.
It can also be used for scripts that should only be executed in the shell,
of course only if the script content is not confidential.

`*.class.php`:
These files contain a single class or interface.
Note that the ".class" suffix conflicts with PSR-4.

`*.class.phps`:
These files contain a single class or interface.
In addition, their code is visible to the public.

`*.inc.php`:
These files get included. They usually include functions.
They should not execute code, since they are not intended to
be executed standalone. (Remember, only *.php is meant to be executed in the web browser).

`*.sh`, `*.phps`, or without filename extension:
If these files begin with `#!/usr/bin/php` or `#!/usr/bin/env php`,
then they should be executed in CLI only.
Their source code can be viewed in a web browser.

## Overview

| Extension          | Shebang |   | Source visible | Code outside classes | Classes/Interfaces   |   | Usage example                                                     |
|--------------------|---------|---|----------------|----------------------|----------------------|---|-------------------------------------------------------------------|
| .php               | No      |   | No             | Yes (must)           | Optional             |   | Pages which the browser should open (e.g. index page, OOBE, etc.) |
| .phps              | No      |   | Yes            | Yes (must)           | Optional             |   | Public code, e.g. code example                                    |
| .class.php         | No      |   | No             | No (must not)        | Mandatory, exactly 1 |   | Contains exactly one class or interface, usually autoloaded       |
| .class.phps        | No      |   | Yes            | No (must not)        | Mandatory, exactly 1 |   | Contains exactly one class or interface, usually autoloaded       |
| .inc.php           | No      |   | No             | No (should not)      | Optional             |   | Usually contains methods (without OOP)                            |
| .sh, .phps or none | Yes     |   | Yes            | Yes (must)           | Optional             |   | Executable shell script                                           |
