#!/bin/bash
# Generate private key and self-signed certificate.
# Make sure script has execute permissions, if not:
#  $ chmod +x ./create-cert.sh
# Usage:
#  $ ./create-cert.sh NAME DAYS
# Where:
#  NAME - Key / certificate name
#  DAYS - Certificate validity days

## Generate password protected private key:
openssl genrsa -des3 -out ./prv-${1}.key 2048
## Create and self-sign certificate with private key:
openssl req -x509 -sha256 -new -config ./openssl.cnf -key ./prv-${1}.key -days ${2} -out ./pub-${1}.crt
