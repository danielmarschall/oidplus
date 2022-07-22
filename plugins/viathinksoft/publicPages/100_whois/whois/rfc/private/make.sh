#!/bin/bash

# NOTE: Please prefer creating the file using NroffEdit, since it generates the Table of Contents

DIR=$( dirname "$0" )

# "-ms" requires package "groff" to be installed
nroff -Tascii -ms "$DIR"/../draft-viathinksoft-oidip-04.nroff | "$DIR"/fix.pl > "$DIR"/../draft-viathinksoft-oidip-04.txt
