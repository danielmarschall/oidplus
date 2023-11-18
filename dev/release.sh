#!/bin/bash

# OIDplus 2.0
# Copyright 2019 - 2023 Daniel Marschall, ViaThinkSoft
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

echo "==============================="
echo "PREPARE FOR NEW OIDPLUS VERSION"
echo "==============================="
echo ""

# Please make sure to do all these steps before committing ANYTHING:

DIR=$( dirname "$0" )

# 0. Search for SPONGE (Marker invented by Terry A Davis)
echo "0. Checking for forgotten sponges"
grep -r "SPONGE" | grep -v "\.svn/pristine" | grep -v "dev/release.sh"
if [ $? -eq 0 ]; then
	echo "STOP! There are missing files. Please fix this problem (remove them from the SVN)"
	exit 1
fi

# 1. Recommended: Run dev/vendor_update.sh and make sure new/deleted files are added/deleted from the SVN/Git working copy
echo "1. Run composer vendor update? (Recommended to do regularly)"
select yn in "Yes" "No"; do
    case $yn in
        Yes ) "$DIR"/vendor_update.sh; break;;
        No ) break;;
    esac
done

# 2. Make sure there are no unversioned files (otherwise systemfile check will generate wrong stuff)
# PLEASE MAKE SURE that the SVN/Git-Working copy has no unversioned files, otherwise they would be included in the checksum catalog
echo "2. Checking unversioned files"
if [ -d "$DIR"/../.svn ]; then
	cd "$DIR"/.. && svn stat | grep "^?"
	if [ $? -eq 0 ]; then
		echo "STOP! There are unversioned files. Please add or remove them. (Otherwise systemfile check plugin will add these files)"
		exit 1
	fi
	cd "$DIR"/.. && svn stat | grep "^!"
	if [ $? -eq 0 ]; then
		echo "STOP! There are missing files. Please fix this problem (remove them from the SVN)"
		exit 1
	fi
fi
if [ -d "$DIR"/../.git ]; then
	cd "$DIR"/.. && git status
	echo "Is everything OK? (All files committed, no unversioned stuff? Otherwise system file check plugin will not work correctly)"
	select yn in "Yes" "No"; do
	    case $yn in
	        Yes ) break;;
	        No ) echo "Please fix the issue first"; exit 1;;
	    esac
	done
fi

# 3. Run dev/translation/message_regenerate.phps and translate things which are missing in plugins/viathinksoft/language/dede/messages.xml (search for "TODO")
echo "3. Checking translation..."
while true; do
	"$DIR"/translation/message_regenerate.phps
	cat "$DIR"/../plugins/viathinksoft/language/dede/messages.xml | grep TODO > /dev/null
	if [ $? -eq 0 ]; then
		echo "Problem: There are untranslated strings! Please translate them."
		sleep 2
		nano "$DIR"/../plugins/viathinksoft/language/dede/messages.xml
	else
		break
	fi
done

# 4. Run dev/logger/verify_maskcodes.phps
echo "4. Verify OIDplus Logger Maskcodes..."
"$DIR"/logger/verify_maskcodes.phps
if [ $? -ne 0 ]; then
	echo "Please fix the issues and run release script again"
	exit 1
fi

# 5. Run phpstan
echo "5. Running PHPSTAN..."
cd "$DIR"/.. && phpstan
echo "Is PHPSTAN output OK?"
select yn in "Yes" "No"; do
    case $yn in
        Yes ) break;;
        No ) echo "Please fix the issues and run release script again"; exit 1;;
    esac
done

# 6. Only if you want to start a new release: Add new entry to the top of changelog.json.php
echo "6. Please edit changelog.json.php (add '-dev' for non-stable versions)"
sleep 2
while true; do
    nano "$DIR"/../changelog.json.php
    echo '<?php if (!@json_decode(@file_get_contents("'$DIR'/../changelog.json.php"))) exit(1);' | php
    if [ $? -eq 0 ]; then
        break
    else
        echo "JSON Syntax error! Please fix it"
	sleep 2
    fi
done

# 7. Run plugins/viathinksoft/adminPages/902_systemfile_check/private/gen_serverside_v3
echo "7. Generate system file check checksum file..."
"$DIR"/../plugins/viathinksoft/adminPages/902_systemfile_check/private/gen_serverside_v3

# 8. Commit to SVN or GIT
if [ -d "$DIR"/../.svn ]; then
	echo "8. Committing to SVN"
	svn commit
elif [ -d "$DIR"/../.git ]; then
	echo "8. ALL GOOD! PLEASE NOW COMMIT TO GIT"
else
	echo "8. ALL GOOD! YOU CAN RELEASE IT"
fi
exit 0

# 9. (ViaThinkSoft internal / runs automatically) Sync SVN to GitHub

# 10. (ViaThinkSoft internal / runs automatically) Run plugins/viathinksoft/adminPages/900_software_update/private/gen_serverside_git
#                                                  or  plugins/viathinksoft/adminPages/900_software_update/private/gen_serverside_svn
#                                                  depending wheather you want to use GIT or SVN as your development base
#                                                  (Repos are read from includes/edition.ini)
