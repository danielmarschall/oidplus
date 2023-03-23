<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2023 Daniel Marschall, ViaThinkSoft
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace ViaThinkSoft\OIDplus;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusPagePublicLoginLdap extends OIDplusPagePluginPublic {

	/**
	 * @param $ra
	 * @param $ldap_userinfo
	 * @return void
	 * @throws OIDplusException
	 */
	private function registerRA($ra, $ldap_userinfo) {
		$email = $ra->raEmail();

		$ra->register_ra(null); // create a user account without password

		/*
		OIDplus DB Field          ActiveDirectory field
		------------------------------------------------
		ra_name                   cn
		personal_name             displayname (or: givenname + " " + sn)
		organization              company
		office                    physicaldeliveryofficename or department
		street                    streetaddress
		zip_town                  postalcode + " " + l
		country                   co (human-readable) or c (ISO country code)
		phone                     telephonenumber or homephone
		mobile                    mobile
		fax                       facsimiletelephonenumber
		(none)                    wwwhomepage
		*/

		$opuserdata = array();
		$opuserdata['ra_name'] = \VtsLDAPUtils::getString($ldap_userinfo,'cn');
		if (!empty(\VtsLDAPUtils::getString($ldap_userinfo,'displayname'))) {
			$opuserdata['personal_name'] = \VtsLDAPUtils::getString($ldap_userinfo,'displayname');
		} else {
			$opuserdata['personal_name'] = trim(\VtsLDAPUtils::getString($ldap_userinfo,'givenname').' '.\VtsLDAPUtils::getString($ldap_userinfo,'sn'));
		}
		$opuserdata['organization'] = \VtsLDAPUtils::getString($ldap_userinfo,'company');
		if (!empty(\VtsLDAPUtils::getString($ldap_userinfo,'physicaldeliveryofficename'))) {
			$opuserdata['office'] = \VtsLDAPUtils::getString($ldap_userinfo,'physicaldeliveryofficename');
		} else {
			$opuserdata['office'] = \VtsLDAPUtils::getString($ldap_userinfo,'department');
		}
		$opuserdata['street'] = \VtsLDAPUtils::getString($ldap_userinfo,'streetaddress');
		$opuserdata['zip_town'] = trim(\VtsLDAPUtils::getString($ldap_userinfo,'postalcode').' '.\VtsLDAPUtils::getString($ldap_userinfo,'l'));
		$opuserdata['country'] = \VtsLDAPUtils::getString($ldap_userinfo,'co'); // ISO country code: \VtsLDAPUtils::getString($ldap_userinfo,'c')
		$opuserdata['phone'] = \VtsLDAPUtils::getString($ldap_userinfo,'telephonenumber'); // homephone for private phone number
		$opuserdata['mobile'] = \VtsLDAPUtils::getString($ldap_userinfo,'mobile');
		$opuserdata['fax'] = \VtsLDAPUtils::getString($ldap_userinfo,'facsimiletelephonenumber');

		foreach ($opuserdata as $dbfield => $val) {
			if (!empty($val)) {
				OIDplus::db()->query("update ###ra set ".$dbfield." = ? where email = ?", array($val, $email));
			}
		}
	}

	/**
	 * @param $remember_me
	 * @param $email
	 * @param $ldap_userinfo
	 * @return void
	 * @throws OIDplusException
	 */
	private function doLoginRA($remember_me, $email, $ldap_userinfo) {
		$ra = new OIDplusRA($email);
		if (!$ra->existing()) {
			$this->registerRA($ra, $ldap_userinfo);
			OIDplus::logger()->log("[INFO]RA($email)!", "RA '$email' was created because of successful LDAP login");
		}

		OIDplus::authUtils()->raLoginEx($email, $remember_me, 'LDAP');

		OIDplus::db()->query("UPDATE ###ra set last_login = ".OIDplus::db()->sqlDate()." where email = ?", array($email));
	}

	/**
	 * @param $upn
	 * @return int
	 * @throws OIDplusException
	 */
	private function getDomainNumber($upn) {
		$numDomains = OIDplus::baseConfig()->getValue('LDAP_NUM_DOMAINS', 1);
		for ($i=1; $i<=$numDomains; $i++) {
			$cfgSuffix = $i == 1 ? '' : "__$i";
			$upnSuffix = OIDplus::baseConfig()->getValue('LDAP_UPN_SUFFIX'.$cfgSuffix, '');
			if (str_ends_with($upn, $upnSuffix)) return $i;
		}
		return -1;
	}

	/**
	 * @param string $actionID
	 * @param array $params
	 * @return int[]
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	public function action(string $actionID, array $params): array {
		if ($actionID == 'ra_login_ldap') {
			if (!OIDplus::baseConfig()->getValue('LDAP_ENABLED', false)) {
				throw new OIDplusException(_L('LDAP authentication is disabled on this system.'));
			}

			if (!function_exists('ldap_connect')) throw new OIDplusConfigInitializationException(_L('PHP extension "%1" not installed','LDAP'));

			OIDplus::getActiveCaptchaPlugin()->captchaVerify($params, 'captcha');

			_CheckParamExists($params, 'email');
			_CheckParamExists($params, 'password');

			$upn = $params['email'];
			$password = $params['password'];

			$domainNumber = $this->getDomainNumber($upn);
			if ($domainNumber <= 0) {
				throw new OIDplusException(_L('The server is not configured to handle this domain (the part behind the at-sign)'));
			}
			$cfgSuffix = $domainNumber == 1 ? '' : "__$domainNumber";

			if (empty($upn)) {
				throw new OIDplusException(_L('Please enter a valid username'));
			}

			$ldap = new \VtsLDAPUtils();

			try {

				$cfg_ldap_server      = OIDplus::baseConfig()->getValue('LDAP_SERVER'.$cfgSuffix);
				$cfg_ldap_port        = OIDplus::baseConfig()->getValue('LDAP_PORT'.$cfgSuffix, 389);
				$cfg_ldap_base_dn     = OIDplus::baseConfig()->getValue('LDAP_BASE_DN'.$cfgSuffix);

				// Note: Will throw an Exception if connect fails
				$ldap->connect($cfg_ldap_server, $cfg_ldap_port);

				if (!$ldap->login($upn, $password)) {
					if (OIDplus::config()->getValue('log_failed_ra_logins', false)) {
						OIDplus::logger()->log("[WARN]A!", "Failed login to RA account '$upn' using LDAP");
					}
					throw new OIDplusException(_L('Wrong password or user not registered'));
				}

				$ldap_userinfo = $ldap->getUserInfo($upn, $cfg_ldap_base_dn);

				if (!$ldap_userinfo) {
					throw new OIDplusException(_L('The LDAP login was successful, but the own user %1 cannot be found. Please check the base configuration setting %2 and %3', $upn, "LDAP_BASE_DN$cfgSuffix", "LDAP_UPN_SUFFIX$cfgSuffix"));
				}

				$foundSomething = false;

				// ---

				$cfgAdminGroup = OIDplus::baseConfig()->getValue('LDAP_ADMIN_GROUP'.$cfgSuffix,'');
				if (!empty($cfgAdminGroup)) {
					$isAdmin = $ldap->isMemberOfRec($ldap_userinfo, $cfgAdminGroup);
				} else {
					$isAdmin = false;
				}
				if ($isAdmin) {
					$foundSomething = true;
					$remember_me = isset($params['remember_me']) && ($params['remember_me']);
					OIDplus::authUtils()->adminLoginEx($remember_me, 'LDAP login');
				}

				// ---

				$cfgRaGroup = OIDplus::baseConfig()->getValue('LDAP_RA_GROUP'.$cfgSuffix,'');
				if (!empty($cfgRaGroup)) {
					$isRA = $ldap->isMemberOfRec($ldap_userinfo, $cfgRaGroup);
				} else {
					$isRA = true;
				}
				if ($isRA) {
					if (OIDplus::baseConfig()->getValue('LDAP_AUTHENTICATE_UPN'.$cfgSuffix,true)) {
						$mail = \VtsLDAPUtils::getString($ldap_userinfo, 'userprincipalname');
						$foundSomething = true;
						$remember_me = isset($params['remember_me']) && ($params['remember_me']);
						$this->doLoginRA($remember_me, $mail, $ldap_userinfo);
					}
					if (OIDplus::baseConfig()->getValue('LDAP_AUTHENTICATE_EMAIL'.$cfgSuffix,false)) {
						$mails = \VtsLDAPUtils::getArray($ldap_userinfo, 'mail');
						foreach ($mails as $mail) {
							$foundSomething = true;
							$remember_me = isset($params['remember_me']) && ($params['remember_me']);
							$this->doLoginRA($remember_me, $mail, $ldap_userinfo);
						}
					}
				}

			} finally {
				$ldap->disconnect();
				$ldap = null;
			}

			if (!$foundSomething) {
				throw new OIDplusException(_L("Error: These credentials cannot be used with OIDplus. Please check the base configuration."));
			}

			return array("status" => 0);
		} else {
			return parent::action($actionID, $params);
		}
	}

	/**
	 * @param bool $html
	 * @return void
	 */
	public function init(bool $html=true) {
		// Nothing
	}

	/**
	 * @param string $id
	 * @param array $out
	 * @param bool $handled
	 * @return void
	 * @throws OIDplusException
	 */
	public function gui(string $id, array &$out, bool &$handled) {
		if ($id === 'oidplus:login_ldap') {
			$handled = true;
			$out['title'] = _L('Login using LDAP / ActiveDirectory');
			$out['icon']  = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png';

			if (!OIDplus::baseConfig()->getValue('LDAP_ENABLED', false)) {
				$out['icon'] = 'img/error.png';
				$out['text'] = _L('LDAP authentication is disabled on this system.');
				return;
			}

			if (!function_exists('ldap_connect')) {
				$out['icon'] = 'img/error.png';
				$out['text'] = _L('PHP extension "%1" not installed','LDAP');
				return;
			}

			$out['text']  = '<noscript>';
			$out['text'] .= '<p>'._L('You need to enable JavaScript to use the login area.').'</p>';
			$out['text'] .= '</noscript>';

			$out['text'] .= '<div id="loginLdapArea" style="visibility: hidden">';

			$out['text'] .= OIDplus::getActiveCaptchaPlugin()->captchaGenerate(_L('Before logging in, please solve the following CAPTCHA'));
			$out['text'] .= '<br>';

			$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:login').'><img src="img/arrow_back.png" width="16" alt="'._L('Go back').'"> '._L('Regular login method').'</a></p>';

			$out['text'] .= '<h2>'._L('Login as RA').'</h2>';

			$login_list = OIDplus::authUtils()->loggedInRaList();
			if (count($login_list) > 0) {
				foreach ($login_list as $x) {
					$out['text'] .= '<p>'._L('You are logged in as %1','<b>'.$x->raEmail().'</b>').' (<a href="#" onclick="return OIDplusPagePublicLogin.raLogout('.js_escape($x->raEmail()).');">'._L('Logout').'</a>)</p>';
				}
				$out['text'] .= '<p>'._L('If you have more accounts, you can log in with another account here.').'</p>';
			} else {
				$out['text'] .= '<p>'._L('Enter your domain username and your password to log in as Registration Authority.').'</p>';
			}
			$out['text'] .= '<form onsubmit="return OIDplusPagePublicLoginLDAP.raLoginLdapOnSubmit(this);">';
			$out['text'] .= '<div><label class="padding_label">'._L('Username').':</label><input type="text" name="username" value="" id="raLoginLdapUsername">';
			$out['text'] .= '&nbsp;&nbsp;';
			$out['text'] .= '<select id="ldapUpnSuffix" name="upnSuffix">';

			$numDomains = OIDplus::baseConfig()->getValue('LDAP_NUM_DOMAINS', 1);
			for ($i=1; $i<=$numDomains; $i++) {
				$cfgSuffix = $i == 1 ? '' : "__$i";
				$upnSuffix = OIDplus::baseConfig()->getValue('LDAP_UPN_SUFFIX'.$cfgSuffix, '');
				if ($upnSuffix == '') throw new OIDplusException(_L('Invalid base configuration setting: %1 is missing or empty', 'LDAP_UPN_SUFFIX'.$cfgSuffix));
				$out['text'] .= '<option value="'.htmlentities($upnSuffix).'">'.htmlentities($upnSuffix).'</option>';
			}

			$out['text'] .= '</select>';
			$out['text'] .= '</div>';
			$out['text'] .= '<div><label class="padding_label">'._L('Password').':</label><input type="password" name="password" value="" id="raLoginLdapPassword"></div>';
			if (OIDplus::baseConfig()->getValue('JWT_ALLOW_LOGIN_USER', true)) {
				if ((OIDplus::authUtils()->getAuthMethod() === OIDplusAuthContentStoreJWT::class)) {
					if (OIDplus::authUtils()->getExtendedAttribute('oidplus_generator',-1) === OIDplusAuthContentStoreJWT::JWT_GENERATOR_LOGIN) {
						$att = 'disabled checked';
					} else {
						$att = 'disabled';
					}
				} else if ((OIDplus::authUtils()->getAuthMethod() === OIDplusAuthContentStoreSession::class)) {
					$att = 'disabled';
				} else {
					$att = '';
				}
				$out['text'] .= '<div><input '.$att.' type="checkbox" value="1" id="remember_me_ldap" name="remember_me_ldap"> <label for="remember_me_ldap">'._L('Remember me').'</label></div>';
			}
			$out['text'] .= '<br><input type="submit" value="'._L('Login').'"><br><br>';
			$out['text'] .= '</form>';

			$invitePlugin = OIDplus::getPluginByOid('1.3.6.1.4.1.37476.2.5.2.4.2.92'); // OIDplusPageRaInvite
			$out['text'] .= '<p><abbr title="'._L('You don\'t need to register. Just enter your Windows/Company credentials.').'">'._L('How to register?').'</abbr></p>';

			$mins = ceil(OIDplus::baseConfig()->getValue('SESSION_LIFETIME', 30*60)/60);
			$out['text'] .= '<p><font size="-1">'._L('<i>Privacy information</i>: By using the login functionality, you are accepting that a "session cookie" is temporarily stored in your browser. The session cookie is a small text file that is sent to this website every time you visit it, to identify you as an already logged in user. It does not track any of your online activities outside OIDplus. The cookie will be destroyed when you log out or after an inactivity of %1 minutes (except if the "Remember me" option is used).', $mins);
			$privacy_document_file = 'OIDplus/privacy_documentation.html';
			$resourcePlugin = OIDplus::getPluginByOid('1.3.6.1.4.1.37476.2.5.2.4.1.500'); // OIDplusPagePublicResources
			if (!is_null($resourcePlugin) && file_exists(OIDplus::localpath().'res/'.$privacy_document_file)) {
				$out['text'] .= ' <a '.OIDplus::gui()->link('oidplus:resources$'.$privacy_document_file.'#cookies').'>'._L('More information about the cookies used').'</a>';
			}
			$out['text'] .= '</font></p></div>';

			$out['text'] .= '<script>$("#loginLdapArea")[0].style.visibility = "visible";</script>';
		}
	}

	/**
	 * @param array $out
	 * @return void
	 */
	public function publicSitemap(array &$out) {
		$out[] = 'oidplus:login_ldap';
	}

	/**
	 * @param array $json
	 * @param string|null $ra_email
	 * @param bool $nonjs
	 * @param string $req_goto
	 * @return bool
	 */
	public function tree(array &$json, string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
		return true;
	}

	/**
	 * @param string $request
	 * @return array|false
	 */
	public function tree_search(string $request) {
		return false;
	}

	/**
	 * @param string $id
	 * @return bool
	 */
	public function implementsFeature(string $id): bool {
		if (strtolower($id) == '1.3.6.1.4.1.37476.2.5.2.3.5') return true; // alternativeLoginMethods()
		if (strtolower($id) == '1.3.6.1.4.1.37476.2.5.2.3.8') return true; // getNotifications()
		return false;
	}

	/**
	 * Implements interface 1.3.6.1.4.1.37476.2.5.2.3.5
	 * @return array
	 * @throws OIDplusException
	 */
	public function alternativeLoginMethods() {
		$logins = array();
		if (OIDplus::baseConfig()->getValue('LDAP_ENABLED', false)) {
			$logins[] = array(
				'oidplus:login_ldap',
				_L('Login using LDAP / ActiveDirectory'),
				OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png'
			);
		}
		return $logins;
	}

	/**
	 * Implements interface 1.3.6.1.4.1.37476.2.5.2.3.8
	 * @param $user
	 * @return array
	 * @throws OIDplusException
	 */
	public function getNotifications($user=null): array {
		$notifications = array();
		if ((!$user || ($user == 'admin')) && OIDplus::authUtils()->isAdminLoggedIn()) {
			if (OIDplus::baseConfig()->getValue('LDAP_ENABLED', false)) {
				if (!function_exists('ldap_connect')) {
					$title = _L('LDAP Login');
					$notifications[] = array('ERR', _L('OIDplus plugin "%1" is enabled, but the required PHP extension "%2" is not installed.', htmlentities($title), 'php_ldap'));
				}
			}
		}
		return $notifications;
	}

}
