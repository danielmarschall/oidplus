#!/bin/bash

DIR=$( dirname "$0" )

cd "$DIR/.."

rm -rf vendor
rm composer.lock

composer update

shopt -s globstar

rm -rf vendor/**/.svn
rm -rf vendor/**/.git
rm -rf vendor/**/.github
rm -rf vendor/**/demo
rm -rf vendor/**/demos
rm -rf vendor/twbs/bootstrap/site/
rm -rf vendor/google/recaptcha/examples/
rm -rf vendor/**/tests
rm -rf vendor/**/test
rm vendor/danielmarschall/fileformats/example.php
rm -rf vendor/danielmarschall/vnag/logos
rm -rf vendor/danielmarschall/vnag/doc
rm -rf vendor/danielmarschall/vnag/plugins

