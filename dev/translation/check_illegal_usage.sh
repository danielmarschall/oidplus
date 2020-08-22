#!/bin/bash

DIR=$( dirname "$0" )

cd "$DIR/../../"

grep -rE "logger\\(\\)\\->log\\(" | grep -v ".svn" | grep -E "_L\\("
grep -rE "config\\(\\)\\->getValue\\(" | grep -v ".svn" | grep -E "_L\\("
grep -rE "config\\(\\)\\->prepareConfigKey\\(" | grep -v ".svn" | grep -E "_L\\("
grep -rE "baseConfig\\(\\)\\->getValue\\(" | grep -v ".svn" | grep -E "_L\\("
grep -rE "query\\(" | grep -v ".svn" | grep -E "_L\\("

# JS
#grep -rE ":\s*_L\\(" | grep -v ".svn" | grep -v "3p/" | grep -v "example_js.html"
grep -irE "alert\\(\"" | grep -v ".svn" | grep -v "3p/" | grep -v "example_js.html" | grep -v "anti_xss.inc.php"
grep -irE "alert\\('" | grep -v ".svn" | grep -v "3p/" | grep -v "example_js.html" | grep -v "anti_xss.inc.php"
