
How to setup Facebook Authentication
====================================

(1) Go to https://developers.facebook.com/ and register as developer

(2) Create a new project

(3) Setup "Facebook Login" feature
Select "Web", enter your OIDplus system URL and ignore all other steps.

(4) In the app settings (at the left menu):

Privacy Policy is set to:

    https://<...>/?goto=oidplus%3Aresources%24OIDplus%2Fprivacy_documentation.html

Note the App-ID (this is your OAuth Client ID) and App-Secret (this is your OAuth Client Key)

(5) "Facebook Login" product settings:

Verified oauth redirect target is set to:

    https://<...>/plugins/viathinksoft/publicPages/820_login_facebook/oauth.php

(6) In userdata/baseconfig/config.inc.php, add following lines:

	OIDplus::baseConfig()->setValue('FACEBOOK_OAUTH2_ENABLED',       true);
	OIDplus::baseConfig()->setValue('FACEBOOK_OAUTH2_CLIENT_ID',     '.............'); // Your App ID
	OIDplus::baseConfig()->setValue('FACEBOOK_OAUTH2_CLIENT_SECRET', '.............'); // Your App Secret
