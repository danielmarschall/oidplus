#!/bin/bash

# NOTE: Please prefer creating the file using NroffEdit, since it generates the Table of Contents

DIR=$( dirname "$0" )

# "-ms" requires package "groff" to be installed
nroff -Tascii -ms "$DIR"/../draft-viathinksoft-oidip-06.nroff | "$DIR"/fix_formfeed.pl > "$DIR"/../draft-viathinksoft-oidip-06.txt

todos "$DIR"/../draft-viathinksoft-oidip-06.txt
