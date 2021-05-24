#!/bin/bash

DIR=$( dirname "$0" )

cat "$DIR"/*.conf | grep -v "^$" | cut -f 1 -d '=' | sort | uniq -c | grep -v " 1 "

exit 0
