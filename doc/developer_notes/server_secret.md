
SERVER_SECRET
=============

In the base configuration, you will see something like this:

    OIDplus::baseConfig()->setValue("SERVER_SECRET", "................................");

This value is chosen randomly by the configuration file generator (setup).

Derivation of secrets and auth keys
-----------------------------------

Important: The usage of `OIDplus::baseConfig()->getValue("SERVER_SECRET")`
is deprecated due to security considerations.

Instead, please always use `OIDplus::authUtils()->makeSecret()`
with a unique `$data` argument (prefer a GUID)
if you need a secret that is derived from the server secret.

If you want to generate an authentication key (e.g. to send via an email),
use `OIDplus::authUtils()->makeAuthKey()`
with a unique `$data` argument (prefer a GUID)
in combination with `OIDplus::authUtils()->validateAuthKey()`.
This auth key is NOT temporary by default so you need to make sure
that you encode a timestamp in it.

Implementation
--------------

`makeAuthKey(data) = makeSecret(data) = sha3_512_hmac(data, "OIDplus:" + SERVER_SECRET)`

Currently, the implementation of `makeAuthKey` and `makeSecret`
is the same, but you should only use `makeAuthKey`
if you combine it with `validateAuthKey`, otherwise, you
should use `makeSecret`. This makes the code easier to understand.

Where are makeAuthKey and makeSecret being used?
------------------------------------------------

System / Core:
- Auth content Store (OIDplusAuthContentStoreJWT.class.php):
  Key to sign JWT tokens (used for Automated AJAX requests, REST API and logins with "Remember me") using PBKDF2+HMAC
  * If a private/public key pair exists: Sign the JWT using that private key.
  * Otherwise sign it with:
    `JWT = HS512(hash_pbkdf2("sha512", OIDplus::authUtils()->makeSecret("0be35e52-f4ef-11ed-b67e-3c4a92df8582"), "", 10000, 64/*256bit*/, false))`
- Session Handler (OIDplusSessionHandler.class.php):
  Encryption of session contents (regular logins)
  * if OpenSSL is installed:        sha512-pbkdf2 + AES-256-CBC + sha3-512-hmac
  * if OpenSSL is not installed:    sha3-512-hmac
  * In both cases, the key is `OIDplus::authUtils()->makeSecret("b118abc8-f4ec-11ed-86ca-3c4a92df8582")`.

Temporary auth keys (sent via email etc.):
* used at plugin forgot RA password (public/091):
  `makeAuthKey("93a16dbe-f4fb-11ed-b67e-3c4a92df8582:" + email + "/" + timestamp)`
* used at plugin ViaThinkSoft FreeOID activation (public/200):
  `makeAuthKey("40c87e20-f4fb-11ed-86ca-3c4a92df8582:" + email + "/" + timestamp)`
* used at plugin invite RA (ra/092):
  `makeAuthKey("ed840c3e-f4fa-11ed-b67e-3c4a92df8582:" + email + "/" + timestamp)`
* used at plugin change RA email (ra/102):
  `makeAuthKey("5ef24124-f4fb-11ed-b67e-3c4a92df8582:" + old_email + "/" + new_email + "/" + timestamp)`

Plugin OID-IP (public/100):
- Authentication token for hidden OIDs = `smallhash(OIDplus::authUtils()->makeSecret("d8f44c7c-f4e9-11ed-86ca-3c4a92df8582:" + id))`

Plugin VNag version check (admin/901):
- Webreader password = `OIDplus::authUtils()->makeSecret("65d9f488-f4eb-11ed-b67e-3c4a92df8582")`

Plugin RDAP (frdl):
- `OIDplus::authUtils()->makeSecret("cee75760-f4f8-11ed-b67e-3c4a92df8582")` is used to generate a cache filename

Plugin VTS Client Challenge Captcha:
- Challenge integrity : `OIDplus::authUtils()->makeAuthKey("797bfc34-f4fa-11ed-86ca-3c4a92df8582:" + challenge)`
- Cache filename : `"vts_client_challenge_" + OIDplus::authUtils()->makeSecret("461f4a9e-f4fa-11ed-86ca-3c4a92df8582:" + ipTarget + "/" + random) + ".tmp"`

GUID Registry
-------------

The "real GUIDs" are documented at the [ViaThinkSoft OIDplus Registration Authority](https://oidplus.viathinksoft.com/oidplus/?goto=guid%3Aoidplus%2FauthRealms). 
