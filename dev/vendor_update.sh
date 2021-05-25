#!/bin/bash

# OIDplus 2.0
# Copyright 2019 - 2021 Daniel Marschall, ViaThinkSoft
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

DIR=$( dirname "$0" )

cd "$DIR/.."

# We temporarily move the .svn directory, otherwise
# we cannot checkout fileformats and vnag in the vendor
# directory
if [ -d ".svn" ]; then
	mv .svn _svn
fi

# Remove vendor and composer.lock, so we can download everything again
# (We need to receive everything again, because we had to delete the .git
# .svn files and therefore we cannot do a simple "svn update" delta update anymore)
rm -rf vendor
rm composer.lock

# Download everything again
composer update

# Remove stuff we don't want to publish or PHP files which could be
# executed (which would be a security risk, because the vendor/ directory
# can be accessed via the web-browser)
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

# It is important that symlinks are not existing, otherwise the .tar.gz dir
# cannot be correctly extracted in Windows
rm -rf vendor/bin
rm -rf vendor/matthiasmullie/minify/bin

# Enable SVN again
if [ -d "_svn" ]; then
	mv _svn .svn
fi

