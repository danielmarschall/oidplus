# glip (Git Library In PHP)

glip is a Git Library In PHP. It allows you to access bare git repositories
from PHP scripts, even without having git installed.

Initially written by [Patrik Fimml](http://fimml.at/glip), it was forked slightly updated by Daniel Marschall in 2023.

## Changes in the fork ##

- Added composer.json
- Added README.md
- Removed Doxygen
- Made compatible with PHP 8
- Fixed assertion error in GitCommitStamp
- Added namespace and renamed files to their classname
- Misc fixes to make PHPstan tests pass

## Usage ##

Add dependency in composer using the command `git require danielmarschall/glip`.

Include the autoload file, as shown below:

```php
<?php

use ViaThinkSoft\Glip\Git;

require_once '/path/to/vendor/autoload.php';

$repo = new Git('project/.git');

```
