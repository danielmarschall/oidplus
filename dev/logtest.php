<?php

require_once __DIR__ . '/../includes/oidplus.inc.php';

OIDplus::init(true);

OIDplus::db()->set_charset("UTF8");
OIDplus::db()->query("SET NAMES 'utf8'");

// This file tests all log events (to verify that the logmasks are working)

$id = 'oid:2.999';
$email = 'oidra@viathinksoft.de';
$old_email = 'oidra@viathinksoft.de';
$new_email = 'oidra@viathinksoft.de';
$current_ra = 'oidra@viathinksoft.de';
$new_ra = 'oidra@viathinksoft.de';
$ra_email = 'oidra@viathinksoft.de';
$name = 'CFD';
$value = 'VAL';
$ra_name = 'Daniel';
$root_oid = '2.123';
$parent = 'oid:2';
$new_oid = 'oid:2.999.1';

OIDplus::logger()->log("RA($email)?/A?", "RA '$email' deleted");
OIDplus::logger()->log("OID($id)+SUPOIDRA($id)?/A?", "Object '$id' (recursively) deleted");
OIDplus::logger()->log("OID($id)+SUPOIDRA($id)?/A?", "RA of object '$id' changed from '$current_ra' to '$new_ra'");
OIDplus::logger()->log("RA($current_ra)!", "Lost ownership of object '$id' due to RA transfer of superior RA / admin.");
OIDplus::logger()->log("RA($new_ra)!", "Gained ownership of object '$id' due to RA transfer of superior RA / admin.");
OIDplus::logger()->log("OID($id)+SUPOIDRA($id)?/A?", "Identifiers/Confidential flag of object '$id' updated"); // TODO: Check if they were ACTUALLY updated!
OIDplus::logger()->log("OID($id)+SUPOIDRA($id)?/A?", "Title/Description of object '$id' updated");
OIDplus::logger()->log("OID($parent)+OIDRA($parent)?/A?", "Created child object '$id'");
OIDplus::logger()->log("OID($id)+SUPOIDRA($id)?/A?", "Object '$id' created, given to RA '".(empty($ra_email) ? '(undefined)' : $ra_email)."'");
OIDplus::logger()->log("OID($root_oid)+RA($email)!", "Requested a free OID for email '$email' to be placed into root '$root_oid'");
OIDplus::logger()->log("OID($root_oid)+OIDRA($root_oid)!", "Child OID '$new_oid' added automatically by '$email' (RA Name: '$ra_name')");
OIDplus::logger()->log("OID($new_oid)+RA($email)!",        "Free OID '$new_oid' activated (RA Name: '$ra_name')");
OIDplus::logger()->log("RA($email)!", "A new password for '$email' was requested (forgot password)");
OIDplus::logger()->log("RA($email)!", "RA '$email' has reset his password (forgot passwort)");
OIDplus::logger()->log("RA(".$email.")!", "RA '".$email."' logged in");
OIDplus::logger()->log("RA(".$email.")!", "RA '".$email."' logged out");
OIDplus::logger()->log("A!", "Admin logged in");
OIDplus::logger()->log("A!", "Admin logged out");
OIDplus::logger()->log("RA($email)!", "RA '$email' has been invited");
OIDplus::logger()->log("RA($email)!", "RA '$email' has been registered due to invitation");
OIDplus::logger()->log("RA($old_email)!+RA($new_email)!", "Requested email change from '$old_email' to '$new_email'");
OIDplus::logger()->log("RA($old_email)!", "Changed email address from '$old_email' to '$new_email'");
OIDplus::logger()->log("RA($new_email)!", "RA '$old_email' has changed its email address to '$new_email'");
OIDplus::logger()->log("RA($email)?/A?", "Password of RA '$email' changed");
OIDplus::logger()->log("RA($email)?/A?", "Changed RA '$email' contact data/details");
OIDplus::logger()->log("A?", "Changed system config setting '$name' to '$value'");
