
SERVER_SECRET
=============

In the base configuration, you will see something like this:

    OIDplus::baseConfig()->setValue('SERVER_SECRET', '................................');

This value is chosen randomly by the configuration file generator (setup).

Where is SERVER_SECRET being used?
----------------------------------

System:
- Auth content Store (OIDplusAuthContentStoreJWT.class.php):
  Key to sign JWT tokens (used for Automated AJAX requests and logins with "Remember me") using PBKDF2+HMAC
  (ONLY if the server does not have a Public/Private key pair!)
  `JWT = HS512(hash_pbkdf2('sha512', SERVER_SECRET+"/OIDplusAuthContentStoreJWT", '', 10000, 64/*256bit*/, false))`
- Session Handler (OIDplusSessionHandler.class.php):
  Encryption of session contents (regular logins)
  if OpenSSL is installed:        sha512-pbkdf2 + AES-256-CBC + sha3-512-hmac
  if OpenSSL is not installed:    sha3-512-hmac
- Auth utils: Generation of auth keys
  `makeAuthKey(data) = sha3_512_hmac(data, "authkey:"+SERVER_SECRET);`
  used at plugin forgot RA password (public/091):
  `makeAuthKey("reset_password;" + email + ";" + timestamp)
  = sha3_512_hmac("reset_password;" + email + ";" + timestamp, "authkey:"+SERVER_SECRET);`
  used at plugin ViaThinkSoft FreeOID activation (public/200):
  `makeAuthKey("com.viathinksoft.freeoid.activate_freeoid;" + email + ";" + timestamp)
  = sha3_512_hmac("com.viathinksoft.freeoid.activate_freeoid;" + email + ";" + timestamp, "authkey:"+SERVER_SECRET);`
  used at plugin invite RA (ra/092):
  `makeAuthKey("activate_ra;" + email + ";" + timestamp)
  = sha3_512_hmac("activate_ra;" + email + ";" + timestamp, "authkey:"+SERVER_SECRET);`
  used at plugin change RA email (ra/102):
  `makeAuthKey("activate_new_ra_email;" + old_email + ";" + new_email + ";" + timestamp)
  = sha3_512_hmac("activate_new_ra_email;" + old_email + ";" + new_email + ";" + timestamp, "authkey:"+SERVER_SECRET);`

Plugin WHOIS (public/100):
- Authentication token for hidden OIDs = `smallhash(SERVER_SECRET + "/WHOIS/" + id);`

Plugin VNag version check (admin/901):
- Webreader password = `sha3_512(SERVER_SECRET + "/VNAG")`

---

Important: Please never use SERVER_SECRET alone for any hashing/HMAC without adding any context to it.

- Example: Bad `hmac(message, SERVER_SECRET)`
- Example: Good `hmac(message, 'xyz:'.SERVER_SECRET)`

Reason: Since the SERVER_SECRET is used at many different places, we must make sure that the calculated values do not reveal information about the SERVER_SECRET in any kind.
