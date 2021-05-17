#!/usr/bin/python

# This is an example script that shows how you can insert an OID
# (in this example "2.999.123") using an authenticated AJAX query ("RPC"-like)

# pip install requests
import requests

url = '<url>'
myobj = {
	'plugin': "1.3.6.1.4.1.37476.2.5.2.4.1.0", # OID of plugin "publicPages/000_objects"
	'action': "Insert",
	'parent': "oid:2.999",
	'id': 123,
	'ra_email': "test@example.com",
	'comment': None,
	'asn1ids': "aaa,bbb,ccc",
	'iris': None,
	'confidential': False,
	'weid': None,
	'OIDPLUS_AUTH_JWT': "<token>"
}

json = requests.post(url, data = myobj).json()

if json['error'] != None:
	print(json['error']);
elif json['status'] == 0: # OK
	print("Insert OK");
elif json['status'] == 1: # RaNotExisting
	print("Insert OK");
elif json['status'] == 2: # RaNotExistingNoInvitation
	print("Insert OK");
else:
	print("Error "+json);
