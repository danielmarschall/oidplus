
How to setup Google Authentication
==================================

(1) You need a Google account

(2) Go to the Google Cloud Platform
https://console.cloud.google.com/home/dashboard

In this dashboard, create a new project.
Wait until the project is created, and then switch to this project
using the drop-down-box at the left top.

(3) Select Burger menu => API & Services => OAuth consent screen

Choose "External", choose application title, etc.

(4) Select Burger menu => API & Services => Credentials

Create a new "OAuth 2.0-Client-ID"
- Application type: Web application
- Name: OIDplus 2.0
- Authorized JavaScript origins: None
- Authorized redirect URIs:
  Add `https://<Your OIDplus URL>/plugins/viathinksoft/publicPages/810_login_google/oauth.php`
	If your system has multiple URIs, add all possible URIs

You will now receive a client ID and a client key (secret!)

(5) In userdata/baseconfig/config.inc.php, add following lines:

	OIDplus::baseConfig()->setValue('GOOGLE_OAUTH2_ENABLED',       true);
	OIDplus::baseConfig()->setValue('GOOGLE_OAUTH2_CLIENT_ID',     '..............apps.googleusercontent.com');
	OIDplus::baseConfig()->setValue('GOOGLE_OAUTH2_CLIENT_SECRET', '.............');
