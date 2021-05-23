#!/bin/bash
DIR=$( dirname "$0" )
cd "$DIR/.."
grep -r "$1" | grep -v ".svn/" | grep -v "3p/" | grep -v "cache/phpstan/"
